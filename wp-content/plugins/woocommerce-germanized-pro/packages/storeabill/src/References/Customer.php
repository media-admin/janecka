<?php

namespace Vendidero\StoreaBill\References;

defined( 'ABSPATH' ) || exit;

class Customer {

	/**
	 * Gets availables order references.
	 *
	 * @return mixed|void
	 */
	public static function get_references() {
		$references = apply_filters( 'storeabill_customer_reference_types', array(
			'woocommerce' => '\Vendidero\StoreaBill\WooCommerce\Customer'
		) );

		return $references;
	}

	/**
	 * @param $customer
	 * @param string $ref_type
	 *
	 * @return bool|\Vendidero\StoreaBill\Interfaces\Customer
	 */
	public static function get_customer( $customer, $ref_type = 'woocommerce' ) {
		$references        = self::get_references();
		$default_reference = '\Vendidero\StoreaBill\WooCommerce\Customer';

		if ( array_key_exists( $ref_type, $references ) ) {
			$reference = $references[ $ref_type ];
		} else {
			$reference = $default_reference;
		}

		$obj = false;

		try {
			$obj = new $reference( $customer );
		} catch( \Exception $e ) {}

		if ( ! $obj || ! is_a( $obj, '\Vendidero\StoreaBill\Interfaces\Customer' ) ) {
			try {
				$obj = new $default_reference( $customer );
			} catch( \Exception $e ) {}
		}

		return $obj;
	}
}