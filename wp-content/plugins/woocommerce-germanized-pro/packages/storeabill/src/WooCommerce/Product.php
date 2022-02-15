<?php

namespace Vendidero\StoreaBill\WooCommerce;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * WooProduct class
 */
class Product implements \Vendidero\StoreaBill\Interfaces\Product {

	/**
	 * The actual product object
	 *
	 * @var WC_Product
	 */
	protected $product;

	/**
	 * @param WC_Product|integer $product
	 */
	public function __construct( $product ) {
		if ( is_numeric( $product ) ) {
			$product = wc_get_product( $product );

			if ( ! is_a( $product, 'WC_Product' ) ) {
				throw new \Exception( _x( 'Invalid product.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
			}
		}

		$this->product = $product;
	}

	/**
	 * Returns the Woo WC_Product original object
	 *
	 * @return object|WC_Product
	 */
	public function get_product() {
		return $this->product;
	}

	public function get_object() {
		return $this->get_product();
	}

	public function get_reference_type() {
		return 'woocommerce';
	}

	public function get_id() {
		return $this->product->get_id();
	}

	public function get_name() {
		return $this->product->get_name();
	}

	public function get_sku() {
		return $this->product->get_sku();
	}

	public function get_type() {
		return $this->product->get_type();
	}

	public function is_type( $type ) {
		return $this->product->is_type( $type );
	}

	public function is_virtual() {
		return $this->product->is_virtual() || $this->product->is_downloadable();
	}

	public function is_service() {
		$is_service = sab_string_to_bool( $this->product->get_meta( '_service' ) );

		return $is_service;
	}

	public function get_parent_id() {
		return $this->product->get_parent_id();
	}

	public function get_parent() {
		$parent_id = $this->get_parent_id();

		if ( $parent_id > 0 ) {
			return \Vendidero\StoreaBill\References\Product::get_product( $parent_id, $this->get_reference_type() );
		}

		return false;
	}

	public function get_attribute_by_slug( $slug ) {
		$attribute      = false;
		$slug           = wc_sanitize_taxonomy_name( $slug );
		$attribute_name = $slug;
		$wc_product     = $this->get_product();

		// If this is a global taxonomy (prefixed with pa_) use the prefix to determine the name.
		if ( taxonomy_exists( wc_attribute_taxonomy_name( $slug ) ) ) {
			$attribute_name = wc_attribute_taxonomy_name( $slug );
		}

		if ( $attribute_value = $wc_product->get_attribute( $slug ) ) {
			$label = wc_attribute_label( $attribute_name, $wc_product );

			$attribute = new \Vendidero\StoreaBill\Document\Attribute( array(
				'key'   => $slug,
				'value' => $attribute_value,
				'label' => $label
			) );
		} elseif ( $wc_product->get_parent_id() > 0 ) {
			/*
			 * In case the product is a child product - lets check the parent product
			 * for the attribute data in case it was not found for the child.
			 */
			if ( $parent = wc_get_product( $wc_product->get_parent_id() ) ) {
				if ( $attribute_value = $parent->get_attribute( $slug ) ) {
					$label = wc_attribute_label( $attribute_name, $parent );

					$attribute = new \Vendidero\StoreaBill\Document\Attribute( array(
						'key'   => $slug,
						'value' => $attribute_value,
						'label' => $label
					) );
				}
			}
		}

		return $attribute;
	}

	public function get_additional_attributes( $custom_attribute_slugs, $existing_slugs = array() ) {
		$attributes = array();

		foreach( $custom_attribute_slugs as $slug ) {
			$slug = wc_sanitize_taxonomy_name( $slug );

			/**
			 * Slug does already exist
			 */
			if ( in_array( $slug, $existing_slugs ) ) {
				continue;
			}

			if ( $attribute = $this->get_attribute_by_slug( $slug ) ) {
				$attributes[] = $attribute;
			}
		}

		return $attributes;
	}

	public function get_image_url( $size = '', $placeholder = false ) {
		$size  = 'woocommerce_thumbnail';
		$image = '';

		if ( $this->product->get_image_id() ) {
			$image = wp_get_attachment_image_src( $this->product->get_image_id(), $size, false );
		} elseif ( $this->get_parent_id() ) {
			$parent_product = wc_get_product( $this->get_parent_id() );

			if ( $parent_product && $parent_product->get_image_id() ) {
				$image = wp_get_attachment_image_src( $parent_product->get_image_id(), $size, false );
			}
		}

		if ( is_array( $image ) && ! empty( $image ) ) {
			$image = $image[0];
		}

		if ( ! $image && $placeholder ) {
			$image = sab_placeholder_img( $size );
		}

		return $image;
	}

	public function get_meta( $key, $single = true, $context = 'view' ) {
		return $this->product->get_meta( $key, $single, $context );
	}

	public function get_category_list( $sep = ', ', $before = '', $after = '' ) {
		$product = $this->product;

		if ( $this->product->get_parent_id() > 0 ) {
			if ( $parent_product = wc_get_product( $this->product->get_parent_id() ) ) {
				$product = $parent_product;
			}
		}

		return strip_tags( wc_get_product_category_list( $product->get_id(), $sep, $before, $after ) );
	}

	/**
	 * Check if a method is callable by checking the underlying order object.
	 * Necessary because is_callable checks will always return true for this object
	 * due to overloading __call.
	 *
	 * @param $method
	 *
	 * @return bool
	 */
	public function is_callable( $method ) {
		if ( method_exists( $this, $method ) ) {
			return true;
		} elseif( is_callable( array( $this->get_product(), $method ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Call child methods if the method does not exist.
	 *
	 * @param $method
	 * @param $args
	 *
	 * @return bool|mixed
	 */
	public function __call( $method, $args ) {
		if ( method_exists( $this->product, $method ) ) {
			return call_user_func_array( array( $this->product, $method ), $args );
		}

		return false;
	}
}