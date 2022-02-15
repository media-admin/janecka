<?php

namespace Vendidero\StoreaBill\References;

use Vendidero\StoreaBill\WooCommerce\Helper;

defined( 'ABSPATH' ) || exit;

class Product {

	/**
	 * Gets availables order references.
	 *
	 * @return mixed|void
	 */
	public static function get_references() {
		$references = apply_filters( 'storeabill_product_reference_types', array(
			'woocommerce' => '\Vendidero\StoreaBill\WooCommerce\Product'
		) );

		return $references;
	}

	public static function get_product_types() {
		$product_types = array(
			'default'      => _x( 'Normal', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'virtual'      => _x( 'Virtual', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'service'      => _x( 'Service', 'storeabill-core', 'woocommerce-germanized-pro' ),
		);

		return apply_filters( 'storeabill_product_types', $product_types );
	}

	/**
	 * @param $product
	 * @param string $ref_type
	 *
	 * @return bool|\Vendidero\StoreaBill\Interfaces\Product
	 */
	public static function get_product( $product, $ref_type = 'woocommerce' ) {
		$references        = self::get_references();
		$default_reference = '\Vendidero\StoreaBill\WooCommerce\Product';

		if ( array_key_exists( $ref_type, $references ) ) {
			$reference = $references[ $ref_type ];
		} else {
			$reference = $default_reference;
		}

		$obj = false;

		try {
			$obj = new $reference( $product );
		} catch( \Exception $e ) {}

		if ( ! $obj || ! is_a( $obj, '\Vendidero\StoreaBill\Interfaces\Product' ) ) {
			try {
				$obj = new $default_reference( $product );
			} catch( \Exception $e ) {}
		}

		return $obj;
	}
}