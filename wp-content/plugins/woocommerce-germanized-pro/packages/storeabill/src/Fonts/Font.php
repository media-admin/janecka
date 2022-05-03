<?php

namespace Vendidero\StoreaBill\Fonts;

use Vendidero\StoreaBill\Package;
use Vendidero\StoreaBill\UploadManager;

defined( 'ABSPATH' ) || exit;

class Font {

	protected $data = array();

	protected $defaults = array(
		'name'             => '',
		'family'           => '',
		'variants'         => array(),
		'variant_mappings' => array(),
		'files'            => array(),
		'is_google_font'   => false,
		'css'              => '',
	);

	/**
	 * Font constructor.
	 *
	 * @param array|Font $args
	 */
	public function __construct( $label, $args = array() ) {
		$this->set_label( $label );

		$class_args = array();

		if ( is_array( $args ) ) {
			$class_args = $args;
		} elseif( is_a( $args, 'Vendidero\StoreaBill\Fonts\Font' ) ) {
			$class_args = $args->get_data();
		}

		$class_args = wp_parse_args( $class_args, $this->defaults );

		if ( empty( $class_args['variants'] ) ) {
			$class_args['variants'] = $this->get_variant_types();
		}

		$this->set_props( $class_args );

		if ( empty( $this->data['family'] ) ) {
			$this->set_family( $this->get_label() );
		}

		if ( empty( $this->data['name'] ) ) {
			$this->set_name( Fonts::clean_font_name( $this->get_label() ) );
		}
	}

	public function set_props( $props ) {
		foreach( $props as $key => $data ) {
			$setter = "set_{$key}";

			if ( is_callable( array( $this, $setter ) ) ) {
				$this->$setter( $data );
			}
		}
	}

	public function get_name() {
		return Fonts::clean_font_name(  $this->data['name'] );
	}

	public function set_name( $name ) {
		$this->data['name'] = $name;
	}

	public function get_label() {
		return $this->data['label'];
	}

	public function set_label( $label ) {
		$this->data['label'] = $label;
	}

	public function get_is_google_font() {
		return $this->data['is_google_font'];
	}

	public function set_is_google_font( $is_google_font ) {
		$this->data['is_google_font'] = sab_string_to_bool( $is_google_font );
	}

	public function is_google_font() {
		return $this->get_is_google_font() === true;
	}

	public function get_family() {
		return $this->data['family'];
	}

	public function set_family( $family ) {
		$this->data['family'] = $family;
	}

	public function get_variants() {
		return $this->data['variants'];
	}

	public function has_variant_mapping( $variant = 'regular' ) {
		$variants = $this->get_variant_mappings();

		return array_key_exists( $variant, $variants );
	}

	public function get_variant_mapping( $variant = 'regular' ) {
		$variants = $this->get_variant_mappings();

		if ( ! in_array( $variant, $this->get_variant_types() ) ) {
			$variant = 'regular';
		}

		if ( array_key_exists( $variant, $variants ) ) {
			return $variants[ $variant ];
		}

		return $variant;
	}

	public function get_variant_mappings() {
		return $this->data['variant_mappings'];
	}

	protected function get_variant_types() {
		return array_keys( sab_get_font_variant_types() );
	}

	public function set_variant_mappings( $mappings ) {
		$variants = wp_parse_args( $mappings, array() );

		$this->data['variant_mappings'] = $variants;
	}

	public function set_variants( $variants ) {
		$this->data['variants'] = $variants;
	}

	public function get_files( $type = '' ) {
		if ( empty( $type ) ) {
			return $this->data['files'];
		} elseif( array_key_exists( $type, $this->data['files'] ) ) {
			return $this->data['files'][ $type ];
		} else {
			return array();
		}
	}

	public function has_file( $variant = 'regular', $type = 'pdf' ) {
		return false !== $this->get_file( $variant, $type );
	}

	public function get_file( $variant = 'regular', $type = 'pdf' ) {
		$files = $this->get_files();

		if ( ! array_key_exists( $type, $files ) ) {
			$type = 'pdf';
		}

		return array_key_exists( $variant, $files[ $type ] ) ? $files[ $type ][ $variant ] : false;
	}

	public function get_local_file( $variant = 'regular', $type = 'pdf' ) {
		if ( $file = $this->get_file( $variant, $type ) ) {
			$file_name = basename( $file );

			return trailingslashit( UploadManager::get_font_path() ) . $file_name;
		}

		return false;
	}

	public function get_local_url( $variant = 'regular', $type = 'pdf' ) {
		if ( $file = $this->get_file( $variant, $type ) ) {
			$file_name = basename( $file );

			return trailingslashit( UploadManager::get_font_url() ) . $file_name;
		}

		return false;
	}

	public function set_files( $files ) {
		$files = wp_parse_args( $files, array(
			'pdf'  => array(),
			'html' => array(),
		) );

		foreach( $this->get_variant_types() as $variant ) {
			if ( ! array_key_exists( $variant, $files['html'] ) ) {
				$files['html'][ $variant ] = '';
			}

			if ( ! array_key_exists( $variant, $files['pdf'] ) ) {
				$files['pdf'][ $variant ] = '';
			}
		}

		$this->data['files'] = $files;
	}

	public function get_data() {
		$data = $this->data;

		return $data;
	}
}