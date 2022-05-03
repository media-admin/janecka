<?php

namespace Vendidero\StoreaBill\Document;

use Vendidero\StoreaBill\Admin\Admin;
use Vendidero\StoreaBill\Admin\Notices;
use WC_DateTime;
use WP_List_Table;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class Table
 */
abstract class Table extends WP_List_Table {

	protected $query = null;

	protected $statuses = array();

	protected $counts = array();

	protected $notice = array();

	protected $document_type = '';

	protected $document_type_object = null;

	/**
	 * Constructor.
	 *
	 * @since 3.0.6
	 *
	 * @see WP_List_Table::__construct() for more information on default arguments.
	 *
	 * @param array $args An associative array of arguments.
	 */
	public function __construct( $args = array() ) {
		add_filter( 'removable_query_args', array( $this, 'enable_query_removing' ) );
		add_filter( 'default_hidden_columns', array( $this, 'set_default_hidden_columns' ), 10, 2 );

		$args = wp_parse_args( $args, array(
			'type' => '',
		) );

		$this->document_type = $args['type'];

		if ( ! $document_type_object = $this->get_document_type_object() ) {
		    wp_die( _x( 'This document type does not exist.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
        }

		parent::__construct(
			array(
				'plural'   => sab_get_document_type_label( $this->document_type, 'plural' ),
				'singular' => sab_get_document_type_label( $this->document_type ),
				'screen'   => isset( $args['screen'] ) ? $args['screen'] : null,
			)
		);
	}

	protected function get_document_type_object() {
	    if ( is_null( $this->document_type_object ) ) {
	        $this->document_type_object = sab_get_document_type( $this->document_type );
        }

	    return $this->document_type_object;
    }

	public function set_default_hidden_columns( $columns, $screen ) {
		if ( $this->screen->id === $screen->id ) {
			$columns = array_merge( $columns, $this->get_default_hidden_columns() );
		}

		return $columns;
	}

	protected function get_default_hidden_columns() {
		return array();
	}

	protected function get_hook_prefix() {
	    return 'storeabill_admin_' . $this->document_type . '_table_';
    }

	public function enable_query_removing( $args ) {
		$args = array_merge( $args, array(
			'changed',
			'bulk_action'
		) );

		return $args;
	}

	/**
	 * @param $id
	 *
	 * @return Document|bool
	 */
	abstract public function get_document( $id );

	/**
	 * @param $args
	 *
	 * @return Query
	 */
	abstract public function get_query( $args );

	/**
	 * Handle bulk actions.
	 *
	 * @param  string $redirect_to URL to redirect to.
	 * @param  string $action      Action name.
	 * @param  array  $ids         List of ids.
	 * @return string
	 */
	public function handle_bulk_actions( $action, $ids, $redirect_to ) {
		$ids         = array_reverse( array_map( 'absint', $ids ) );
		$changed     = 0;

		if ( false !== strpos( $action, 'mark_' ) ) {

			$statuses   = sab_get_document_statuses( $this->document_type );
			$new_status = substr( $action, 5 ); // Get the status name from action.

			// Sanity check: bail out if this is actually not a status, or is not a registered status.
			if ( isset( $statuses[ $new_status ] ) ) {

				foreach ( $ids as $id ) {

					if ( $document = $this->get_document( $id ) ) {
						$document->update_status( $new_status, true );

						do_action( "{$this->get_hook_prefix()}edit_status", $id, $new_status );
						$changed++;
					}
				}
			}
		} elseif( 'delete' === $action ) {
			foreach ( $ids as $id ) {
				if ( $document = $this->get_document( $id ) ) {
					if ( $document->delete( false ) ) {
						$changed++;
                    }
				}
			}
		}

		$changed = apply_filters( "{$this->get_hook_prefix()}bulk_action", $changed, $action, $ids, $redirect_to, $this );

		if ( $changed ) {
			$redirect_to = add_query_arg(
				array(
					'changed'     => $changed,
					'ids'         => join( ',', $ids ),
					'bulk_action' => $action
				),
				$redirect_to
			);
		}

		return esc_url_raw( $redirect_to );
	}

	public function output_notices() {
	    Notices::output( $this->screen->id );
	}

	/**
	 * Show confirmation message that order status changed for number of orders.
	 */
	public function set_bulk_notice() {

		$number            = isset( $_REQUEST['changed'] ) ? absint( $_REQUEST['changed'] ) : 0; // WPCS: input var ok, CSRF ok.
		$bulk_action       = isset( $_REQUEST['bulk_action'] ) ? sab_clean( wp_unslash( $_REQUEST['bulk_action'] ) ) : ''; // WPCS: input var ok, CSRF ok.

		if ( 'delete' === $bulk_action ) {
			$this->add_notice( sprintf( _nx( '%d document deleted.', '%d documents deleted.', $number, 'storeabill-core', 'woocommerce-germanized-pro' ), number_format_i18n( $number ) ) );
		} elseif( strpos( $bulk_action, 'mark_' ) !== false ) {
			$statuses = sab_get_document_statuses( $this->document_type );

			// Check if any status changes happened.
			foreach ( $statuses as $slug => $name ) {
				if ( 'mark_' . $slug === $bulk_action ) { // WPCS: input var ok, CSRF ok.
					$this->add_notice( sprintf( _nx( '%d document status changed.', '%d document statuses changed.', $number, 'storeabill-core', 'woocommerce-germanized-pro' ), number_format_i18n( $number ) ) );
					break;
				}
			}
		}

		do_action( "{$this->get_hook_prefix()}bulk_notice", $bulk_action, $this );
	}

	public function add_notice( $message, $type = 'success' ) {
		Notices::add( $message, $type, $this->screen->id );
	}

	/**
	 * @return bool
	 */
	public function ajax_user_can( $document = false ) {
		return current_user_can( "edit_{$this->document_type}" );
	}

	protected function user_can_delete( $document = false ) {
	    return current_user_can( "delete_{$this->document_type}s" );
    }

    protected function user_can_edit( $document = false ) {
	    return current_user_can( "edit_{$this->document_type}", $document ? $document->get_id() : '' );
    }

	public function get_page_option() {
		return 'woocommerce_page_sab_accounting_per_page';
	}

	/**
	 * @global array    $avail_post_stati
	 * @global WP_Query $wp_query
	 * @global int      $per_page
	 * @global string   $mode
	 */
	public function prepare_items() {
		global $per_page;

		$per_page        = $this->get_items_per_page( $this->get_page_option(), 10 );
		$per_page        = apply_filters( "{$this->get_hook_prefix()}edit_per_page", $per_page );
		$this->statuses  = sab_get_document_statuses( $this->document_type );
		$this->counts    = sab_get_documents_counts( $this->document_type );
		$paged           = $this->get_pagenum();

		$args = array(
			'limit'       => $per_page,
			'paginate'    => true,
			'offset'      => ( $paged - 1 ) * $per_page,
			'count_total' => true,
			'type'        => $this->document_type,
		);

		$raw_query_args = $_GET;

		foreach( $raw_query_args as $query_arg => $data ) {

		    if ( 'document_type' === $query_arg ) {
		        continue;
            }

		    if ( substr( $query_arg, 0, 9 ) === 'document_' ) {
		        $data      = sab_clean( wp_unslash( $data ) );
		        $query_arg = substr( $query_arg, 9 );

		        if ( ! empty( $data ) ) {
		            $args[ $query_arg ] = $data;
                }
            }
        }

		if ( isset( $_GET['orderby'] ) ) {
		    $args['orderby'] = sab_clean( wp_unslash( $_GET['orderby'] ) );
		}

		if ( isset( $_GET['order'] ) ) {
			$args['order'] = 'asc' === $_GET['order'] ? 'ASC' : 'DESC';
		}

		if ( isset( $_GET['m'] ) ) {
			$m          = sab_clean( wp_unslash( $_GET['m'] ) );
			$year       = substr( $m, 0, 4 );

			if ( ! empty( $year ) ) {
				$month      = '';
				$day        = '';

				if ( strlen( $m ) > 5 ) {
					$month = substr( $m, 4, 2 );
				}

				if ( strlen( $m ) > 7 ) {
					$day = substr( $m, 6, 2 );
				}

				$datetime = new WC_DateTime();
				$datetime->setDate( $year, 1, 1 );

				if ( ! empty( $month ) ) {
					$datetime->setDate( $year, $month, 1 );
				}

				if ( ! empty( $day ) ) {
					$datetime->setDate( $year, $month, $day );
				}

				$next_month = clone $datetime;
				$next_month->modify( '+ 1 month' );
				// Make sure to not include next month first day
				$next_month->modify( '-1 day' );

				$args['date_created'] = $datetime->format( 'Y-m-d' ) . '...' . $next_month->format( 'Y-m-d' );
			}
		}

		if ( isset( $_GET['s'] ) ) {
			$search = sab_clean( wp_unslash( $_GET['s'] ) );

			if ( ! is_numeric( $search ) ) {
				$search = '*' . $search . '*';
			}

			$args['search']         = $search;
            $args['search_columns'] = $this->get_search_columns( $search );
		}

		// Query the user IDs for this page
		$this->query = $this->get_query( $args );
		$this->items = $this->query->get_documents();

		$this->set_pagination_args(
			array(
				'total_items' => $this->query->get_total(),
				'per_page'    => $per_page,
			)
		);
	}

    protected function get_search_columns( $search ) {
        $search_columns = array();

	    if ( is_numeric( $search ) ) {
		    $search_columns = array( 'document_id', 'document_reference_id', 'document_author_id', 'document_customer_id', 'document_number', '_reference_number' );
	    } elseif ( strlen( $search ) === 2 ) {
		    $search_columns = array( 'document_country' );
	    } else {
		    $search_columns = array( 'document_id', 'document_formatted_number', '_reference_number' );
	    }

        return $search_columns;
    }

	/**
	 */
	public function no_items() {
		echo _x( 'No documents found', 'storeabill-core', 'woocommerce-germanized-pro' );
	}

	/**
	 * Determine if the current view is the "All" view.
	 *
	 * @since 4.2.0
	 *
	 * @return bool Whether the current view is the "All" view.
	 */
	protected function is_base_request() {
		$vars = $_GET;
		unset( $vars['paged'] );

		if ( empty( $vars ) ) {
			return true;
		}

		return 1 === count( $vars );
	}

	/**
	 * @global array $locked_post_status This seems to be deprecated.
	 * @global array $avail_post_stati
	 * @return array
	 */
	protected function get_views() {

		$status_links     = array();
		$num_documents    = $this->counts;
		$total_documents  = array_sum( (array) $num_documents );
		$class            = '';
		$all_args         = array();

		if ( empty( $class ) && ( $this->is_base_request() || isset( $_REQUEST['all_documents'] ) ) ) {
			$class = 'current';
		}

		$all_inner_html = sprintf(
			_nx(
				'All <span class="count">(%s)</span>',
				'All <span class="count">(%s)</span>',
				$total_documents, 'storeabill-core', 'woocommerce-germanized-pro'
			),
			number_format_i18n( $total_documents )
		);

		$status_links['all'] = $this->get_edit_link( $all_args, $all_inner_html, $class );

		foreach ( sab_get_document_statuses( $this->document_type ) as $status => $title ) {
			$class = '';

			if ( ! in_array( $status, array_keys( $this->statuses ) ) || empty( $num_documents[ $status ] ) ) {
				continue;
			}

			if ( isset( $_REQUEST['document_status'] ) && $status === $_REQUEST['document_status'] ) {
				$class = 'current';
			}

			$status_args = array(
				'document_status' => $status,
			);

			$status_label = sprintf(
				translate_nooped_plural( _nx_noop( $title . ' <span class="count">(%s)</span>', $title . ' <span class="count">(%s)</span>', 'storeabill-core', 'woocommerce-germanized-pro' ), $num_documents[ $status ] ),
				number_format_i18n( $num_documents[ $status ] )
			);

			$status_links[ $status ] = $this->get_edit_link( $status_args, $status_label, $class );
		}

		return $status_links;
	}

	/**
	 * Helper to create links to edit.php with params.
	 *
	 * @since 4.4.0
	 *
	 * @param string[] $args  Associative array of URL parameters for the link.
	 * @param string   $label Link text.
	 * @param string   $class Optional. Class attribute. Default empty string.
	 * @return string The formatted link string.
	 */
	protected function get_edit_link( $args, $label, $class = '' ) {
		$url = add_query_arg( $args, $this->get_main_page() );

		$class_html = $aria_current = '';
		if ( ! empty( $class ) ) {
			$class_html = sprintf(
				' class="%s"',
				esc_attr( $class )
			);

			if ( 'current' === $class ) {
				$aria_current = ' aria-current="page"';
			}
		}

		return sprintf(
			'<a href="%s"%s%s>%s</a>',
			esc_url( $url ),
			$class_html,
			$aria_current,
			$label
		);
	}

	/**
	 * @return string
	 */
	public function current_action() {
		if ( isset( $_REQUEST['delete_all'] ) || isset( $_REQUEST['delete_all2'] ) ) {
			return 'delete_all';
		}

		return parent::current_action();
	}

	/**
	 * Display a monthly dropdown for filtering items
	 *
	 * @since 3.0.6
	 *
	 * @global wpdb      $wpdb
	 * @global WP_Locale $wp_locale
	 *
	 * @param string $post_type
	 */
	protected function months_dropdown( $type = '' ) {
		global $wpdb, $wp_locale;

		$extra_checks = "";

		if ( isset( $_GET['document_status'] ) && 'all' !== $_GET['document_status'] ) {
			$extra_checks = $wpdb->prepare( ' AND document_status = %s', sab_clean( wp_unslash( $_GET['document_status'] ) ) );
		}

		$months = $wpdb->get_results("
            SELECT DISTINCT YEAR( document_date_created ) AS year, MONTH( document_date_created ) AS month
            FROM $wpdb->storeabill_documents
            WHERE 1=1
            $extra_checks
            ORDER BY document_date_created DESC
		" );

		$month_count = count( $months );

		if ( ! $month_count || ( 1 == $month_count && 0 == $months[0]->month ) ) {
			return;
		}

		$m = isset( $_GET['m'] ) ? (int) $_GET['m'] : 0;
		?>
		<label for="filter-by-date" class="screen-reader-text"><?php echo _x( 'Filter by date', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></label>
		<select name="m" id="filter-by-date">
			<option<?php selected( $m, 0 ); ?> value="0"><?php echo _x( 'All dates', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></option>
			<?php
			foreach ( $months as $arc_row ) {
				if ( 0 == $arc_row->year ) {
					continue;
				}

				$month = zeroise( $arc_row->month, 2 );
				$year  = $arc_row->year;

				printf(
					"<option %s value='%s'>%s</option>\n",
					selected( $m, $year . $month, false ),
					esc_attr( $arc_row->year . $month ),
					/* translators: 1: month name, 2: 4-digit year */
					sprintf( _x( '%1$s %2$d', 'storeabill-core', 'woocommerce-germanized-pro' ), $wp_locale->get_month( $month ), $year )
				);
			}
			?>
		</select>
		<?php
	}

	/**
	 * @param string $which
	 */
	protected function extra_tablenav( $which ) {
		?>
		<div class="alignleft actions">
			<?php
			if ( 'top' === $which && ! is_singular() ) {
				ob_start();

				$this->months_dropdown();
				$this->render_filters();

				/**
				 * Action that fires after outputting Shipments table view filters.
				 * Might be used to add custom filters to the Shipments table view.
				 *
				 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
				 * unique hook for a shipment type e.g. return. In case of simple shipments the type is omitted.
				 *
				 * Example hook name: woocommerce_gzd_return_shipments_table_filters
				 *
				 * @param string $which top or bottom.
				 *
				 * @since 3.0.0
				 * @package Vendidero/Germanized/Shipments
				 */
				do_action( "{$this->get_hook_prefix()}filters", $which );

				$output = ob_get_clean();

				if ( ! empty( $output ) ) {
					echo $output;

					submit_button( _x( 'Filter', 'storeabill-core', 'woocommerce-germanized-pro' ), '', 'filter_action', false, array( 'id' => 'document-query-submit' ) );
				}
			}
			?>
		</div>
		<?php
		do_action( 'manage_posts_extra_tablenav', $which );
	}

	protected function render_filters() {

	}

	/**
	 * @return array
	 */
	protected function get_table_classes() {
		return array( 'widefat', 'fixed', 'striped', 'posts', 'documents' );
	}

	protected function get_custom_columns() {
		$columns = array();

		$columns['cb']         = '<input type="checkbox" />';
		$columns['title']      = _x( 'Title', 'storeabill-core', 'woocommerce-germanized-pro' );
		$columns['date']       = _x( 'Date', 'storeabill-core', 'woocommerce-germanized-pro' );
		$columns['status']     = _x( 'Status', 'storeabill-core', 'woocommerce-germanized-pro' );
		$columns['actions']    = _x( 'Actions', 'storeabill-core', 'woocommerce-germanized-pro' );

		return $columns;
	}

	/**
	 * @return array
	 */
	public function get_columns() {
		$columns = $this->get_custom_columns();

		/**
		 * Filters the columns displayed in the Shipments list table.
		 *
		 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
		 * unique hook for a shipment type e.g. return. In case of simple shipments the type is omitted.
		 *
		 * Example hook name: woocommerce_gzd_return_shipments_table_edit_per_page
		 *
		 * @param string[] $columns An associative array of column headings.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/Shipments
		 */
		$columns = apply_filters( "{$this->get_hook_prefix()}columns", $columns );

		return $columns;
	}

	/**
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'date' => array( 'date_created', false ),
		);
	}

	/**
	 * Gets the name of the default primary column.
	 *
	 * @since 4.3.0
	 *
	 * @return string Name of the default primary column, in this case, 'title'.
	 */
	protected function get_default_primary_column_name() {
		return 'title';
	}

	/**
	 * Handles the default column output.
	 *
	 * @since 4.3.0
	 *
	 * @param Document $document The current shipment object.
	 * @param string   $column_name The current column name.
	 */
	public function column_default( $document, $column_name ) {
		do_action( "{$this->get_hook_prefix()}custom_column", $column_name, $document->get_id() );
	}

	public function get_main_page() {
		return 'admin.php?page=sab-' . str_replace( '_', '-', $this->document_type );
	}

	/**
	 * Handles actions.
	 *
	 * @since 0.0.1
	 *
	 * @param Document $document The current document object.
	 */
	protected function column_actions( $document ) {
		do_action( "{$this->get_hook_prefix()}actions_start", $document );

		echo Admin::get_document_actions_html( Admin::get_document_actions( $document, 'table' ) ); // WPCS: XSS ok.

		do_action( "{$this->get_hook_prefix()}actions_end", $document );
	}

	public function column_cb( $document ) {
		if ( $this->user_can_edit( $document ) ) :
			?>
			<label class="screen-reader-text" for="cb-select-<?php echo esc_attr( $document->get_id() ); ?>">
				<?php printf( _x( 'Select %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $document->get_id() ); ?>
			</label>
			<input id="cb-select-<?php echo esc_attr( $document->get_id() ); ?>" type="checkbox" name="document[]" value="<?php echo esc_attr( $document->get_id() ); ?>" />
		<?php
		endif;
	}

	/**
	 * Handles the post author column output.
	 *
	 * @since 4.3.0
	 *
	 * @param Document $document The current document object.
	 */
	public function column_status( $document ) {
		echo '<span class="sab-status sab-document-status sab-document-type-' . esc_attr( $document->get_type() ) . '-status sab-document-status-' . esc_attr( $document->get_status() ) . '">' . sab_get_document_status_name( $document->get_status(), $document->get_type() ) .'</span>';
	}

	/**
	 * Handles the post author column output.
	 *
	 * @since 4.3.0
	 *
	 * @param Document $document The current document object.
	 */
	public function column_date( $document ) {
		$timestamp = $document->get_date_created() ? $document->get_date_created()->getTimestamp() : '';

		if ( ! $timestamp ) {
			echo '&ndash;';
			return;
		}

		// Check if the order was created within the last 24 hours, and not in the future.
		if ( $timestamp > strtotime( '-1 day', current_time( 'timestamp', true ) ) && $timestamp <= current_time( 'timestamp', true ) ) {
			$show_date = sprintf(
                /* translators: %s: human-readable time difference */
				_x( '%s ago', 'storeabill-human-readable-time-difference', 'woocommerce-germanized-pro' ),
				human_time_diff( $document->get_date_created()->getTimestamp(), current_time( 'timestamp', true ) )
			);
		} else {
			$show_date = $document->get_date_created()->date_i18n( apply_filters( "{$this->get_hook_prefix()}date_format", sab_date_format() ) );
		}

		printf(
			'<time datetime="%1$s" title="%2$s">%3$s</time>',
			esc_attr( $document->get_date_created()->date( 'c' ) ),
			esc_html( $document->get_date_created()->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ),
			esc_html( $show_date )
		);
	}

	/**
	 *
	 * @param Document $document
	 */
	public function single_row( $document ) {
		$GLOBALS['document'] = $document;
		$classes             = 'document document-status-' . $document->get_status();
		?>
		<tr id="document-<?php echo $document->get_id(); ?>" class="<?php echo esc_attr( $classes ); ?>">
			<?php $this->single_row_columns( $document ); ?>
		</tr>
		<?php
	}

	protected function get_custom_bulk_actions( $actions ) {
		return $actions;
	}

	/**
	 * @return array
	 */
	protected function get_bulk_actions() {
		$actions = array();

		if ( $this->user_can_delete() ) {
			$actions['delete'] = _x( 'Delete Permanently', 'storeabill-core', 'woocommerce-germanized-pro' );
		}

		foreach( Admin::get_bulk_actions_handlers( $this->document_type ) as $bulk_action_handler ) {
		    $actions[ $bulk_action_handler->get_action() ] = $bulk_action_handler->get_title();
		}

		$actions = $this->get_custom_bulk_actions( $actions );

		return apply_filters( "{$this->get_hook_prefix()}bulk_actions", $actions );
	}
}