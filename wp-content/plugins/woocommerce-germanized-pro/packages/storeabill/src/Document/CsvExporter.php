<?php

namespace Vendidero\StoreaBill\Document;

use Vendidero\StoreaBill\Interfaces\Exporter;
use Vendidero\StoreaBill\Package;
use Vendidero\StoreaBill\REST\DocumentController;
use Vendidero\StoreaBill\REST\Server;
use Vendidero\StoreaBill\UploadManager;

defined( 'ABSPATH' ) || exit;

/**
 * Include dependencies.
 */
if ( ! class_exists( 'WC_CSV_Exporter', false ) ) {
	require_once WC_ABSPATH . 'includes/export/abstract-wc-csv-batch-exporter.php';
}

abstract class CsvExporter extends \WC_CSV_Batch_Exporter implements Exporter {
    use ExporterTrait;

    protected $filename = 'sab-export.csv';

	protected $excluded_column_names = array(
		'formatted_address',
		'meta_data',
	);

	protected $spreadable_column_names = array(
		'address',
	);

	protected $date_column_names = array(
        'date_created',
        'date_modified',
        'date_sent'
    );

	/**
	 * Columns ids and names.
	 *
	 * @var array
	 */
	protected $column_names = array();

	/**
	 * Constructor.
	 */
	public function __construct( $document_type = '' ) {
		parent::__construct();

		$this->export_type = $this->get_document_type();
	}

	public function get_type() {
		return 'csv';
	}

	public function get_file_extension() {
		return 'csv';
	}

	/**
	 * Get file path to export to.
	 *
	 * @return string
	 */
	protected function get_file_path() {
		$upload_dir = UploadManager::get_upload_dir();

		return trailingslashit( $upload_dir['basedir'] ) . $this->get_filename();
	}

	/**
	 * Get batch limit.
	 *
	 * @since 3.1.0
	 * @return int
	 */
	public function get_limit() {
		return apply_filters( "{$this->get_hook_prefix()}batch_limit", $this->limit, $this );
	}

	/**
	 * Return the delimiter to use in CSV file
	 *
	 * @since 3.9.0
	 * @return string
	 */
	public function get_delimiter() {
		return apply_filters( "{$this->get_hook_prefix()}delimiter", $this->delimiter );
	}

	/**
	 * Generate and return a filename.
	 *
	 * @return string
	 */
	public function get_filename() {
		return sanitize_file_name( apply_filters( "{$this->get_hook_prefix()}get_filename", $this->filename, $this ) );
	}

	protected function get_excluded_column_names() {
		return apply_filters( "{$this->get_hook_prefix()}excluded_column_names", $this->excluded_column_names, $this );
	}

	protected function get_spreadable_column_names() {
		return apply_filters( "{$this->get_hook_prefix()}spreadable_column_names", $this->spreadable_column_names, $this );
	}

	protected function get_query_args() {
		$query_args = array(
			'per_page' => $this->get_limit(),
			'page'     => $this->get_page(),
		);

		if ( $start_date = $this->get_start_date() ) {
			$query_args['after'] = $this->get_gm_date( $start_date );
		}

		if ( $end_date = $this->get_end_date() ) {
			$query_args['before'] = $this->get_gm_date( $end_date );
		}

		$query_args = array_replace( $query_args, $this->get_additional_query_args() );

		return apply_filters( "{$this->get_hook_prefix()}query_args", $query_args, $this );
	}

	/**
	 * Return an array of supported column names and ids.
	 *
	 * @since 3.1.0
	 * @return array
	 */
	public function get_column_names() {
		return apply_filters( "{$this->get_hook_prefix()}column_names", $this->column_names, $this );
	}

	protected function include_column( $prop ) {
		return in_array( $prop, $this->get_excluded_column_names() ) ? false : true;
	}

	protected function spread_column( $prop ) {
		return in_array( $prop, $this->get_spreadable_column_names() ) ? true : false;
	}

	/**
	 * Return an array of columns to export.
	 *
	 * @since 3.1.0
	 * @return array
	 */
	public function get_columns_to_export() {
	    $filter = (array) $this->get_filter( 'columns' );

		if ( $filter && ! empty( $filter ) ) {
		    return $filter;
        }

		return array();
	}

	protected function get_schema_label( $prop, $fallback ) {
	    $label       = isset( $prop['label'] ) ? $prop['label'] : '';
		$description = isset( $prop['description'] ) ? $prop['description'] : '';

		if ( empty( $label ) ) {
		    $label = $description;
        }

		if ( empty( $label ) ) {
		    $label = $fallback;
        }

		if ( ! empty( $label ) && substr( $label, -1 ) === '.' ) {
			$label = substr( $label, 0, -1 );
		}

		return $label;
    }

	public function get_default_column_names() {
		$columns = array();

		if ( $controller = $this->get_controller() ) {
			$schema  = $controller->get_item_schema()['properties'];
			$columns = array();

			foreach( $schema as $prop => $args ) {
				if ( ! $this->include_column( $prop ) ) {
					continue;
				}

				if ( 'object' === $args['type'] && is_array( $args['properties'] ) && $this->spread_column( $prop ) ) {
					foreach( $args['properties'] as $inner_prop => $inner_args ) {
						$columns[ $prop . '_' . $inner_prop ] = $this->get_schema_label( $inner_args, $inner_prop );
					}
				} else {
					$columns[ $prop ] = $this->get_schema_label( $args, $prop );
                }
			}
		}

		$columns = array_merge( $columns, $this->get_additional_default_column_names() );

		return apply_filters( "{$this->get_hook_prefix()}default_column_names", $columns, $this );
	}

	protected function get_additional_default_column_names() {
	    return array();
    }

	/**
	 * Prepare data for export.
	 *
	 * @since 3.1.0
	 */
	public function prepare_data_to_export() {
		$result = $this->get_documents();

		$this->total_rows = $result['total'];
		$this->row_data   = array();

		foreach ( $result['documents'] as $document ) {
			$this->row_data[] = $this->generate_row_data( $document );
		}

		/**
		 * Remove columns with extra handling from header.
		 */
		foreach( $this->get_column_names() as $column_id => $name ) {
			if ( $this->column_needs_extra_handling( $column_id ) ) {
			    unset( $this->column_names[ $column_id ] );
            }
		}
	}

	/**
	 * Generate the CSV file.
	 *
	 * @since 3.1.0
	 */
	public function generate_file() {
		if ( 1 === $this->get_page() ) {
			$this->update_default_settings();
		}

		parent::generate_file();
	}

	/**
	 * Get total % complete.
	 *
	 * Forces an int from parent::get_percent_complete(), which can return a float.
	 *
	 * @return int Percent complete.
	 */
	public function get_percent_complete() {
		return intval( parent::get_percent_complete() );
	}

	public function render_filters() {
		?>
		<tr>
			<th scope="row">
				<label for="sab-exporter-columns"><?php echo esc_html_x( 'Which columns should be exported?', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></label>
			</th>
			<td>
				<select id="sab-exporter-columns" name="columns[]" class="sab-exporter-columns sab-enhanced-select" style="width:100%;" multiple data-placeholder="<?php echo esc_html_x( 'Export all columns', 'storeabill-core', 'woocommerce-germanized-pro' ); ?>">
					<?php
                    $default_columns = $this->get_default_filter_setting( 'columns', array() );

					foreach ( $this->get_default_column_names() as $column_id => $column_name ) {
						echo '<option value="' . esc_attr( $column_id ) . '" ' . selected( $column_id, in_array( $column_id, $default_columns ) ? $column_id : '', false ) . '>' . esc_html( $column_name ) . '</option>';
					}
					?>
				</select>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="sab-exporter-meta"><?php echo esc_html_x( 'Export custom meta?', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></label>
			</th>
			<td>
				<input type="checkbox" id="sab-exporter-meta" value="yes" name="enable_meta" <?php checked( $this->get_default_filter_setting( 'enable_meta', 'no' ), 'yes' ); ?> />
				<label for="sab-exporter-meta"><?php echo esc_html_x( 'Yes, export all custom meta', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></label>
			</td>
		</tr>
		<?php
	}

	protected function get_columns_with_extra_handling() {
	    return array( 'meta', 'address' );
	}

	protected function column_needs_extra_handling( $column_id ) {
	    return apply_filters( "{$this->get_hook_prefix()}column_needs_extra_handling", in_array( $column_id, $this->get_columns_with_extra_handling(), true ), $column_id );
	}

	protected function is_date_column( $column_id ) {
	    $column_id = str_replace( '_gmt', '', $column_id );

		return apply_filters( "{$this->get_hook_prefix()}is_date_column", in_array( $column_id, $this->date_column_names, true ), $column_id );
	}

	/**
	 * Take a document and generate row data from it for export.
	 *
	 * @param array $document Document object.
	 *
	 * @return array
	 */
	protected function generate_row_data( $document ) {
		$columns = $this->get_column_names();
		$row     = array();

		foreach ( $columns as $column_id => $column_name ) {
			$column_id = strstr( $column_id, ':' ) ? current( explode( ':', $column_id ) ) : $column_id;
			$value     = '';

			// Skip some columns if dynamically handled later or if we're being selective.
			if ( $this->column_needs_extra_handling( $column_id ) || ! $this->is_column_exporting( $column_id ) ) {
				continue;
			}

			if ( has_filter( "{$this->get_hook_prefix()}column_{$column_id}" ) ) {
				// Filter for 3rd parties.
				$value = apply_filters( "{$this->get_hook_prefix()}column_{$column_id}", '', $document, $column_id );

			} elseif ( is_callable( array( $this, "get_column_value_{$column_id}" ) ) ) {
				// Handle special columns which don't map 1:1 to product data.
				$value = $this->{"get_column_value_{$column_id}"}( $document );

			} elseif ( array_key_exists( $column_id, $document ) ) {
				// Default and custom handling.
				$value = $document[ $column_id ];

				if ( $this->is_date_column( $column_id ) ) {
				    $value = $this->format_date_time( $value );
				}
			}

			$row[ $column_id ] = $value;
		}

		$this->prepare_address_for_export( $document, $row );
		$this->prepare_meta_for_export( $document, $row );
		$this->prepare_extra_data_for_export( $document, $row );

		return apply_filters( "{$this->get_hook_prefix()}row_data", $row, $document );
	}

	protected function format_date_time( $value ) {
	    if ( ! empty( $value ) ) {
	        try {
		        $value = sab_string_to_datetime( $value );
	        } catch( \Exception $e ) {}
	    }

	    return $value;
	}

	protected function prepare_extra_data_for_export( $document, &$row ) {

	}

	protected function prepare_address_for_export( $document, &$row ) {
		foreach( $document['address'] as $address_prop => $value ) {
			if ( $this->is_column_exporting( 'address_' . $address_prop ) ) {
				$row[ 'address_' . $address_prop ] = $value;
			}
		}
	}

	protected function has_meta_support() {
	    $has_meta_support = $this->get_filter( 'enable_meta' ) ? sab_string_to_bool( $this->get_filter( 'enable_meta' ) ) : false;

	    return $has_meta_support;
	}

	/**
	 * Export meta data.
	 *
	 * @param array $document Document being exported.
	 * @param array $row Row data.
	 *
	 * @since 3.1.0
	 */
	protected function prepare_meta_for_export( $document, &$row ) {
		if ( $this->has_meta_support() ) {
			$meta_data = isset( $document['meta_data'] ) ? $document['meta_data'] : array();

			if ( count( $meta_data ) ) {
				$meta_keys_to_skip = apply_filters( "{$this->get_hook_prefix()}skip_meta_keys", array(), $document );

				$i = 1;
				foreach ( $meta_data as $meta ) {

					if ( in_array( $meta->key, $meta_keys_to_skip, true ) ) {
						continue;
					}

					// Allow 3rd parties to process the meta, e.g. to transform non-scalar values to scalar.
					$meta_value = apply_filters( "{$this->get_hook_prefix()}meta_value", $meta->value, $meta, $document, $row );

					if ( ! is_scalar( $meta_value ) ) {
						continue;
					}

					$column_key = 'meta:' . esc_attr( $meta->key );
					/* translators: %s: meta data name */
					$this->column_names[ $column_key ] = sprintf( _x( 'Meta: %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $meta->key );
					$row[ $column_key ]                = $meta_value;
					$i ++;
				}
			}
		}
	}
}
