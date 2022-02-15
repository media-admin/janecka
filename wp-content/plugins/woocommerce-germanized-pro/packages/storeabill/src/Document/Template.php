<?php

namespace Vendidero\StoreaBill\Document;

use Vendidero\StoreaBill\Data;
use WC_Data_Exception;
use WC_Data_Store;
use WC_DateTime;

defined( 'ABSPATH' ) || exit;

/**
 * DocumentTemplate class.
 */
abstract class Template extends Data {

	protected $data = array(
		'date_created'       => null,
		'date_modified'      => null,
		'pdf_template_id'    => 0,
		'parent_id'          => 0,
		'status'             => '',
		'title'              => '',
		'content'            => '',
		'template_name'      => '',
		'margins'            => array(),
	);

	protected $blocks = null;

	protected $header_blocks = null;

	protected $footer_blocks = null;

	protected $content_blocks = null;

	/**
	 * Stores meta in cache for future reads.
	 *
	 * A group must be set to to enable caching.
	 *
	 * @var string
	 */
	protected $cache_group = 'document-templates';

	/**
	 * Which data store to load.
	 *
	 * @var string
	 */
	protected $data_store_name = 'document_template';

	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'document_template';

	/**
	 * @param  int|object|Template $template Template to read.
	 */
	public function __construct( $template = 0 ) {
		parent::__construct( $template );

		if ( is_numeric( $template ) && $template > 0 ) {
			$this->set_id( $template );
		} elseif ( $template instanceof self ) {
			$this->set_id( $template->get_id() );
		} elseif ( ! empty( $template->ID ) ) {
			$this->set_id( $template->ID );
		} else {
			$this->set_object_read( true );
		}

		$this->data_store = sab_load_data_store( $this->data_store_name );

		if ( $this->get_id() > 0 ) {
			$this->data_store->read( $this );
		}
	}

	/**
	 * Get internal type.
	 *
	 * @return string
	 */
	public function get_type() {
		return $this->object_type;
	}

	abstract public function get_template_type();

	public function is_first_page() {
		return false;
	}

	public function get_hook_prefix() {
		return 'storeabill_document_template_';
	}

	abstract public function get_document_type( $context = 'view' );

	public function get_pdf_template_id( $context = 'view' ) {
		return $this->get_prop( 'pdf_template_id', $context );
	}

	public function set_pdf_template_id( $value ) {
		$this->set_prop( 'pdf_template_id', absint( $value ) );
	}

	public function get_template_name( $context = 'view' ) {
		return $this->get_prop( 'template_name', $context );
	}

	public function set_template_name( $value ) {
		$this->set_prop( 'template_name', $value );
	}

	public function get_line_item_types( $context = 'view' ) {
		return sab_get_document_type_line_item_types( $this->get_document_type() );
	}

	public function get_default_margins() {
		return array(
			'top'    => '1',
			'left'   => '1',
			'bottom' => '1',
			'right'  => '1'
		);
	}

	public function get_margins( $context = 'view' ) {
		return $this->get_prop( 'margins', $context );
	}

	public function set_margins( $value ) {
		$this->set_prop( 'margins', array_map( 'sab_format_decimal', $value ) );
	}

	public function get_margin( $type ) {
		$margins = $this->get_margins();

		return array_key_exists( $type, $margins ) ? $margins[ $type ] : 0;
	}

	public function get_pdf_template() {
		$template_id = $this->get_pdf_template_id();

		if ( $template_id > 0 && ( $file = get_attached_file( $template_id ) ) ) {
			return $file;
		}

		return false;
	}

	public function get_content( $context = 'view' ) {
		return $this->get_prop( 'content', $context );
	}

	public function set_content( $value ) {
		$this->set_prop( 'content', $value );
	}

	protected function get_blocks() {
		if ( is_null( $this->blocks ) ) {
			$this->blocks         = parse_blocks( $this->get_content() );
			$this->header_blocks  = array();
			$this->content_blocks = array();
			$this->footer_blocks  = array();

			foreach( $this->blocks as $block ) {

				if ( 'storeabill/header' === $block['blockName'] ) {
					$this->header_blocks = $block['innerBlocks'];
				} elseif( 'storeabill/footer' === $block['blockName'] ) {
					$this->footer_blocks = $block['innerBlocks'];
				} elseif( ! empty( $block['blockName'] ) ) {
					$this->content_blocks[] = $block;
				}
			}
		}

		return $this->blocks;
	}

	public function get_header_blocks() {
		$blocks = $this->get_blocks();

		return apply_filters( "{$this->get_hook_prefix()}header_blocks", $this->header_blocks, $this );
	}

	public function get_content_blocks() {
		$blocks = $this->get_blocks();

		return apply_filters( "{$this->get_hook_prefix()}content_blocks", $this->content_blocks, $this );
	}

	public function get_footer_blocks() {
		$blocks = $this->get_blocks();

		return apply_filters( "{$this->get_hook_prefix()}footer_blocks", $this->footer_blocks, $this );
	}

	public function has_block( $block_name ) {
		return has_block( $block_name, $this->get_content() );
	}

	public function get_additional_attribute_slugs() {
		$item_attributes        = $this->get_block_attributes( 'storeabill/item-attributes' );
		$custom_attribute_slugs = array();

		if ( isset( $item_attributes['customAttributes'] ) ) {
			$custom_attribute_slugs = $item_attributes['customAttributes'];
		}

		return $custom_attribute_slugs;
	}

	public function get_block_attributes( $block_name ) {
		$dynamic_block_names   = array( $block_name );
		$block_attributes      = array();
		$dynamic_block_pattern = (
			'/<!--\s+wp:(' .
			str_replace( '/', '\/',                 // Escape namespace, not handled by preg_quote.
				str_replace( 'core/', '(?:core/)?', // Allow implicit core namespace, but don't capture.
					implode( '|',                   // Join block names into capture group alternation.
						array_map( 'preg_quote',    // Escape block name for regular expression.
							$dynamic_block_names
						)
					)
				)
			) .
			')(\s+(\{.*?\}))?\s+(\/)?-->/'
		);

		preg_match( $dynamic_block_pattern, $this->get_content(), $block_match, PREG_OFFSET_CAPTURE );

		if ( ! empty( $block_match ) ) {
			// Reset attributes JSON to prevent scope bleed from last iteration.
			$block_attributes_json = null;

			if ( isset( $block_match[3] ) ) {
				$block_attributes_json = $block_match[3][0];
			}

			// Attempt to parse attributes JSON, if available.
			$attributes = array();

			if ( ! empty( $block_attributes_json ) ) {
				$decoded_attributes = json_decode( $block_attributes_json, true );

				if ( ! is_null( $decoded_attributes ) ) {
					$attributes = $decoded_attributes;
				}
			}

			$block_attributes = $attributes;
		}

		return $block_attributes;
	}

	public function get_block( $block_name ) {
		foreach( $this->get_blocks() as $block ) {
			if ( $block['blockName'] === $block_name ) {
				return $block;
			}
		}

		return false;
	}

	/**
	 * Get date_created.
	 *
	 * @param  string $context View or edit context.
	 * @return WC_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/**
	 * Get date_modified.
	 *
	 * @param  string $context View or edit context.
	 * @return WC_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_modified( $context = 'view' ) {
		return $this->get_prop( 'date_modified', $context );
	}

	/**
	 * Set date_created.
	 *
	 * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if there is no date.
	 * @throws WC_Data_Exception Exception may be thrown if value is invalid.
	 */
	public function set_date_created( $date = null ) {
		$this->set_date_prop( 'date_created', $date );
	}

	/**
	 * Set date_modified.
	 *
	 * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if there is no date.
	 * @throws WC_Data_Exception Exception may be thrown if value is invalid.
	 */
	public function set_date_modified( $date = null ) {
		$this->set_date_prop( 'date_modified', $date );
	}

	/**
	 * Return the order statuses without wc- internal prefix.
	 *
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		$status = $this->get_prop( 'status', $context );

		if ( empty( $status ) && 'view' === $context ) {
			// In view context, return the default status if no status has been set.
			$status = apply_filters( "{$this->get_hook_prefix()}default_status", 'draft' );
		}

		return $status;
	}

	/**
	 * Set order status.
	 *
	 * @since 3.0.0
	 * @param string $new_status Status to change the template to.
	 */
	public function set_status( $new_status ) {
		$this->set_prop( 'status', $new_status );
	}

	public function get_preview_url() {
		return get_preview_post_link( $this->get_id() );
	}

	public function get_edit_url() {
		return get_edit_post_link( $this->get_id(), 'edit' );
	}

	/**
	 * Return the order statuses without wc- internal prefix.
	 *
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_title( $context = 'view', $add_type_suffix = true ) {
		$title = $this->get_prop( 'title', $context );

		if ( empty( $title ) && 'view' === $context ) {
			// In view context, return the default status if no status has been set.
			$title = apply_filters( "{$this->get_hook_prefix()}default_title", $this->get_default_title( $add_type_suffix ) );
		}

		return $title;
	}

	protected function get_default_title( $add_type_suffix = true ) {
		$title_parts = array();

		if ( $add_type_suffix && ( $document_type = sab_get_document_type( $this->get_document_type() ) ) ) {
			$title_parts[] = sab_get_document_type_label( $document_type, 'plural' );
		}

		$id    = $this->get_id() > 0 ? $this->get_id() : _x( 'Draft', 'storeabill-core', 'woocommerce-germanized-pro' );
		$parts = implode( ' | ', $title_parts );

		/* translators: 1: template id 2: template title */
		return sprintf( _x( 'Template #%1$s %2$s', 'storeabill-core', 'woocommerce-germanized-pro' ), $id, ! empty( $parts ) ? ' - ' . $parts : '' );
	}

	public function set_title( $title ) {
		$this->set_prop( 'title', $title );
	}
}
