<?php

namespace Vendidero\StoreaBill\Document;

use WC_Data_Exception;
use WC_Data_Store;
use WC_DateTime;

defined( 'ABSPATH' ) || exit;

/**
 * DocumentTemplate class.
 */
class FirstPageTemplate extends Template {

	protected $default_template = null;

	/**
	 * @return bool|DefaultTemplate
	 */
	protected function get_default_template() {
		if ( $this->get_parent_id() > 0 && is_null( $this->default_template ) ) {
			$this->default_template = sab_get_document_template( $this->get_parent_id(), true );
		}

		if ( is_null( $this->default_template ) ) {
			$this->default_template = false;
		}

		return $this->default_template;
	}

	public function get_document_type( $context = 'view' ) {
		if ( $template = $this->get_default_template() ) {
			return $template->get_document_type( $context );
		}

		return false;
	}

	public function get_template_type() {
		return 'first_page';
	}

	public function is_first_page() {
		return true;
	}

	public function get_parent_id( $context = 'view' ) {
		return $this->get_prop( 'parent_id', $context );
	}

	public function set_parent_id( $value ) {
		$this->set_prop( 'parent_id', absint( $value ) );

		$this->default_template = null;
	}

	public function get_margins( $context = 'view' ) {
		$margins = $this->get_prop( 'margins', $context );

		if ( 'view' === $context ) {
			if ( $default = $this->get_default_template() ) {
				$margins['left']  = $default->get_margin( 'left' );
				$margins['right'] = $default->get_margin( 'right' );
			}
		}

		return $margins;
	}

	public function get_default_margins() {
		$margins = parent::get_default_margins();

		if ( $default = $this->get_default_template() ) {
			$margins['left']  = $default->get_margin( 'left' );
			$margins['right'] = $default->get_margin( 'right' );
		}

		return $margins;
	}

	public function set_margins( $value ) {
		$value = array_diff_key( $value, array_flip( array( 'left', 'right' ) ) );

		$this->set_prop( 'margins', array_map( 'sab_format_decimal', $value ) );
	}

	/**
	 * Return the order statuses without wc- internal prefix.
	 *
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_title( $context = 'view', $add_type_suffix = true ) {
		if ( $template = $this->get_default_template() ) {
			$title  = $template->get_title( $context, $add_type_suffix );
			$title .= ' | ' . _x( 'First Page', 'storeabill-core', 'woocommerce-germanized-pro' );

			return $title;
		}

		return '';
	}

	public function get_line_item_types( $context = 'view' ) {
		if ( $template = $this->get_default_template() ) {
			return $template->get_line_item_types( $context );
		}

		return parent::get_line_item_types();
	}

	public function get_margin( $type ) {
		$margins = $this->get_margins();

		return array_key_exists( $type, $margins ) ? $margins[ $type ] : 0;
	}

	public function get_content_blocks() {
		return array();
	}

	public function get_version( $context = 'view' ) {
		if ( $template = $this->get_default_template() ) {
			return $template->get_version( $context );
		}

		return '1.0.0';
	}

	public function get_fonts( $context = 'view' ) {
		if ( $template = $this->get_default_template() ) {
			return $template->get_fonts();
		}

		return array();
	}

	public function get_font_display_types() {
		if ( $template = $this->get_default_template() ) {
			return $template->get_font_display_types();
		}

		return array();
	}

	public function get_font_display_type( $type_name = '' ) {
		if ( $template = $this->get_default_template() ) {
			return $template->get_font_display_type( $type_name );
		}

		return array();
	}

	public function get_default_font() {
		if ( $template = $this->get_default_template() ) {
			return $template->get_default_font();
		}

		return array();
	}

	public function get_font( $display_type ) {
		if ( $template = $this->get_default_template() ) {
			return $template->get_font( $display_type );
		}

		return array();
	}

	public function get_font_variant( $display_type, $variant = 'regular' ) {

		if ( $template = $this->get_default_template() ) {
			return $template->get_font_variant( $display_type, $variant );
		}

		return 'regular';
	}

	public function get_font_name( $display_type ) {

		if ( $template = $this->get_default_template() ) {
			return $template->get_font_name( $display_type );
		}

		return '';
	}
}
