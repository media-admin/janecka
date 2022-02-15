<?php

namespace Vendidero\StoreaBill\Editor\Templates;

defined( 'ABSPATH' ) || exit;

class Block {

	protected $data = array();

	protected $children = array();

	protected $type = '';

	public function __construct( $type, $args = array() ) {
		$args = wp_parse_args( $args, array() );

		$this->data = $args;
		$this->type = $type;
	}

	public function get( $prop, $default = null ) {
		if ( array_key_exists( $prop, $this->data ) ) {
			return $this->data[ $prop ];
		}

		return $default;
	}

	public function get_data() {
		return $this->data;
	}

	/**
	 * @param Block $block
	 *
	 * @return Block
	 */
	public function add_child( $block ) {
		$this->children[] = $block;

		return $block;
	}

	/**
	 * @return Block[]
	 */
	public function get_children() {
		return $this->children;
	}

	/**
	 * @return string
	 */
	public function get_type() {
		return $this->type;
	}

	public function has_children() {
		return sizeof( $this->children ) > 0;
	}

	public function to_array() {
		$data = array(
			$this->get_type(),
			$this->get_data()
		);

		if ( $this->has_children() ) {
			$children = array();

			foreach( $this->get_children() as $child ) {
				$children[] = $child->to_array();
			}

			$data[] = $children;
		}

		return $data;
	}
}