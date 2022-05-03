<?php

namespace Vendidero\Germanized\Pro\Food;

defined( 'ABSPATH' ) || exit;

class Allergen {

	/**
	 * @var \WP_Term
	 */
	private $term = null;

	/**
	 * @param \WP_Term $term
	 */
	public function __construct( $term ) {
		if ( ! is_a( $term, 'WP_Term' ) ) {
			$term = get_term_by( is_numeric( $term ) ? 'id' : 'slug', $term, 'product_allergen' );

			if ( ! is_a( $term, 'WP_Term' ) ) {
				throw new \Exception( __( 'This allergen does not exist', 'woocommerce-germanized-pro' ) );
			}
		}

		$this->term = $term;
	}

	public function get_name() {
		return apply_filters( 'woocommerce_gzdp_allergen_name', $this->term->name, $this );
	}

	public function get_included_value() {
		return apply_filters( 'woocommerce_gzdp_allergen_included_value', _x( 'Yes', 'allergen-included', 'woocommerce-germanized-pro' ), $this );
	}

	public function get_id() {
		return $this->term->term_id;
	}

	public function get_slug() {
		return $this->term->slug;
	}

	public function get_meta( $key, $single = true ) {
		return get_term_meta( $this->get_id(), $key, $single );
	}

	public function update_meta_data( $key, $value ) {
		return update_term_meta( $this->get_id(), $key, $value );
	}

	public function __toString() {
		return $this->get_name();
	}
}