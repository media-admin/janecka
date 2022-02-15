<?php

namespace Vendidero\StoreaBill\Document;

use Vendidero\StoreaBill\Fonts\Fonts;
use WC_Data_Exception;
use WC_Data_Store;
use WC_DateTime;

defined( 'ABSPATH' ) || exit;

/**
 * DocumentTemplate class.
 */
class DefaultTemplate extends Template {

	protected $extra_data = array(
		'fonts'           => array(),
		'font_size'       => '',
		'color'           => '',
		'document_type'   => '',
		'line_item_types' => array(),
	);

	protected $content_blocks = null;

	protected $first_page_template = null;

	public function apply_changes() {
		if ( function_exists( 'array_replace' ) ) {
			$this->data = array_replace( $this->data, $this->changes ); // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.array_replaceFound
		} else { // PHP 5.2 compatibility.
			foreach ( $this->changes as $key => $change ) {
				$this->data[ $key ] = $change;
			}
		}

		$this->changes = array();
	}

	public function get_template_type() {
		return 'default';
	}

	public function get_document_type( $context = 'view' ) {
		return $this->get_prop( 'document_type', $context );
	}

	public function set_document_type( $type ) {
		$this->set_prop( 'document_type', $type );
	}

	public function get_font_size( $context = 'view' ) {
		$font_size = $this->get_prop( 'font_size', $context );

		if ( empty( $font_size ) && 'view' === $context ) {
			$font_size = sab_get_document_default_font_size();
		}

		return $font_size;
	}

	public function set_font_size( $value ) {
		$this->set_prop( 'font_size', $value );
	}

	public function get_color( $context = 'view' ) {
		$color = $this->get_prop( 'color', $context );

		if ( 'view' === $context && empty( $color ) ) {
			$color = sab_get_document_default_color();
		}

		return $color;
	}

	public function set_color( $color ) {
		$this->set_prop( 'color', $color );
	}

	public function get_line_item_types( $context = 'view' ) {
		$line_item_types = $this->get_prop( 'line_item_types', $context );

		if ( 'view' === $context && empty( $line_item_types ) ) {
			$line_item_types = sab_get_document_type_line_item_types( $this->get_document_type() );
		}

		return $line_item_types;
	}

	public function set_line_item_types( $value ) {
		$this->set_prop( 'line_item_types', $value );
	}

	public function is_first_page() {
		return false;
	}

	public function get_fonts( $context = 'view' ) {
		$fonts = $this->get_prop( 'fonts', $context );

		if ( empty( $fonts ) ) {
			$fonts = array();
		}

		if ( 'view' === $context ) {
			foreach( array_keys( $this->get_font_display_types() ) as $display_type ) {
				if ( ! array_key_exists( $display_type, $fonts ) || empty( $fonts[ $display_type ] ) ) {
					$fonts[ $display_type ] = $this->get_default_font();
				}
			}
		}

		return $fonts;
	}

	public function set_fonts( $value ) {
		$this->set_prop( 'fonts', $value );
	}

	public function get_font_display_types() {
		return array(
			'default' => array(
				'title' => _x( 'Default', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'name'  => 'default',
				'selectors' => array(
					'pdf'  => 'body',
					'html' => '.block-editor .editor-styles-wrapper',
				),
			),
		);
	}

	public function get_font_display_type( $type_name = '' ) {
		if ( empty( $type_name ) ) {
			$type_name = 'default';
		}

		$types = $this->get_font_display_types();
		$type  = array_key_exists( $type_name, $types ) ? $types[ $type_name ] : $types['default'];
		$type  = wp_parse_args( $type, array(
			'title'     => '',
			'selectors' => array(),
			'name'      => $type_name,
		) );

		$type['selectors']  = wp_parse_args( $type['selectors'], array(
			'editor'   => '.editor-styles-wrapper',
			'document' => 'body'
		) );

		return $type;
	}

	public function get_default_font() {
		$font = array(
			'name'     => apply_filters( "{$this->get_hook_prefix()}default_font_name", Fonts::get_default_font()->get_name(), $this ),
			'variants' => array(
				'regular'     => apply_filters( "{$this->get_hook_prefix()}default_font_variant_regular", Fonts::get_default_font()->get_variant_mapping( 'regular' ), $this ),
				'bold'        => apply_filters( "{$this->get_hook_prefix()}default_font_variant_bold", Fonts::get_default_font()->get_variant_mapping( 'bold' ), $this ),
				'italic'      => apply_filters( "{$this->get_hook_prefix()}default_font_variant_italic", Fonts::get_default_font()->get_variant_mapping( 'italic' ), $this ),
				'bold_italic' => apply_filters( "{$this->get_hook_prefix()}default_font_variant_bold_italic", Fonts::get_default_font()->get_variant_mapping( 'bold_italic' ), $this )
			),
		);

		return $font;
	}

	public function get_font( $display_type ) {
		$fonts        = $this->get_fonts();
		$default_font = $this->get_default_font();

		if ( array_key_exists( $display_type, $fonts ) ) {

			$font = wp_parse_args( $fonts[ $display_type ], array(
				'name'     => '',
				'variants' => array(),
			) );

			$fonts['variants'] = wp_parse_args( $fonts['variants'], array(
				'regular'     => 'regular',
				'bold'        => 'regular',
				'italic'      => 'regular',
				'bold_italic' => 'regular'
			) );

			return $font;
		}

		return $default_font;
	}

	public function get_font_variant( $display_type, $variant = 'regular' ) {

		if ( $font = $this->get_font( $display_type ) ) {
			return array_key_exists( $variant, $font['variants'] ) ? $font['variants'][ $variant ] : 'regular';
		}

		return 'regular';
	}

	public function get_font_name( $display_type ) {

		$result_font = $this->get_font( 'regular' );

		if ( $font = $this->get_font( $display_type ) ) {
			$result_font = $font;
		}

		return $result_font['name'];
	}

	/**
	 * @return DefaultTemplate|FirstPageTemplate
	 */
	public function get_first_page() {
		if ( is_null( $this->first_page_template ) ) {
			$this->first_page_template = $this;

			if ( $child = $this->data_store->get_first_page( $this ) ) {
				$this->first_page_template = $child;
			}
		}

		return $this->first_page_template;
	}

	public function has_custom_first_page() {
		if ( $this->get_first_page()->get_id() !== $this->get_id() ) {
			return true;
		}

		return false;
	}

	public function delete( $force_delete = false ) {
		if ( $this->has_custom_first_page() && ( $first_page = $this->get_first_page() ) ) {
			$first_page->delete( $force_delete );
		}

		return parent::delete( $force_delete );
	}
}
