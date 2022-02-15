<?php

namespace Vendidero\StoreaBill\Document;

defined( 'ABSPATH' ) || exit;

/**
 * Total class
 */
class Total {

	protected $total = 0;

	protected $document = null;

	protected $type = '';

	protected $placeholders = array();

	protected $label = '';

	public function __construct( $document, $args = array() ) {
		$this->document = $document;

		foreach( $args as $key => $arg ) {
			$setter = 'set_' . $key;

			if ( is_callable( array( $this, $setter ) ) ) {
				$this->$setter( $arg );
			}
		}
	}

	public function get_type() {
		return $this->type;
	}

	public function set_type( $type ) {
		$this->type = $type;
	}

	public function get_total() {
		return $this->total;
	}

	public function set_total( $total ) {
		$this->total = $total;
	}

	public function get_placeholders() {
		return $this->placeholders;
	}

	public function set_placeholders( $placeholders ) {
		$this->placeholders = (array) $placeholders;
	}

	public function replace( $str ) {
		$placeholders = $this->get_placeholders();

		/**
		 * In case this seems to be a default title (containing print arguments e.g. %s)
		 * replace with placeholders.
		 */
		if ( ! empty( $placeholders ) && strpos( $str, '%s' ) !== false ) {
			$str = vsprintf( $str, array_keys( $placeholders ) );
		}

		$str = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $str );

		return $str;
	}

	public function get_label() {
		$label = $this->label;

		if ( empty( $label ) ) {
			/**
			 * Search the default label
			 */
			if ( $document_type = sab_get_document_type( $this->document->get_type() ) ) {
				$types = $document_type->total_types;

				if ( array_key_exists( $this->get_type(), $types ) ) {
					$label = $types[ $this->get_type() ]['title'];
				}
			}
		}

		return $label;
	}

	public function get_formatted_label() {
		return $this->replace( $this->get_label() );
	}

	public function set_label( $label ) {
		$this->label = $label;
	}

	public function get_formatted_total() {
		return ( is_callable( array( $this->document, 'get_formatted_price' ) ) ? $this->document->get_formatted_price( $this->get_total(), $this->get_type() ) : sab_format_price( $this->get_total() ) );
	}

	public function get_data() {
		return array(
			'total'           => $this->get_total(),
			'total_formatted' => $this->get_formatted_total(),
			'placeholders'    => $this->get_placeholders(),
			'type'            => $this->get_type()
		);
	}
}