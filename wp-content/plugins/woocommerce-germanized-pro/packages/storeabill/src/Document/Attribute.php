<?php

namespace Vendidero\StoreaBill\Document;

defined( 'ABSPATH' ) || exit;

/**
 * Attribute class
 */
class Attribute {

	protected $data = array();

	protected $defaults = array(
		'key'    => '',
		'value'  => '',
		'label'  => '',
		'order'  => 1,
	);

	/**
	 * Attribute constructor.
	 *
	 * @param array|Attribute $args
	 */
	public function __construct( $args ) {
		$class_args = array();

		if ( is_array( $args ) ) {
			$class_args = $args;
		} elseif( is_a( $args, 'Vendidero\StoreaBill\Document\Attribute' ) ) {
			$class_args = $args->get_data();
		}

		$class_args = wp_parse_args( $class_args, $this->defaults );

		foreach( $class_args as $key => $data ) {
			$setter = "set_{$key}";

			if ( is_callable( array( $this, $setter ) ) ) {
				$this->$setter( $data );
			}
		}
	}

	public function get_key() {
		return $this->data['key'];
	}

	public function set_key( $key ) {
		$this->data['key'] = sanitize_key( $key );
	}

	public function get_label() {
		$label = $this->data['label'];

		return ( empty( $label ) ? $this->get_key() : $label );
	}

	public function set_label( $label ) {
		$this->data['label'] = $label;
	}

	public function get_order() {
		return $this->data['order'];
	}

	public function set_order( $order ) {
		$this->data['order'] = absint( $order );
	}

	public function get_value() {
		return $this->data['value'];
	}

	public function set_value( $value ) {
		$this->data['value'] = $value;
	}

	public function get_data() {
		$data = $this->data;

		$data['formatted_label'] = $this->get_formatted_label();
		$data['formatted_value'] = $this->get_formatted_value( true );

		return $data;
	}

	public function get_formatted_value( $autop = false ) {
		$value = $this->get_value();
		$value = $autop ? wp_kses_post( $value ) : wp_kses_post( make_clickable( trim( $value ) ) );

		return $value;
	}

	public function get_formatted_label() {
		$label = $this->get_label();
		$label = wp_kses_post( $this->get_label() );

		return $label;
	}

	public function toArray() {
		return $this->data;
	}
}