<?php

namespace Vendidero\StoreaBill\References;

defined( 'ABSPATH' ) || exit;

class RefundOrder {

	/**
	 * Gets availables order references.
	 *
	 * @return mixed|void
	 */
	public static function get_references() {
		$references = apply_filters( 'storeabill_refund_order_reference_types', array(
			'woocommerce' => '\Vendidero\StoreaBill\WooCommerce\RefundOrder'
		) );

		return $references;
	}

	/**
	 * @param $order
	 * @param string $ref_type
	 *
	 * @return bool|\Vendidero\StoreaBill\Interfaces\RefundOrder
	 */
	public static function get_refund_order( $order, $ref_type = 'woocommerce' ) {
		$references        = self::get_references();
		$default_reference = '\Vendidero\StoreaBill\WooCommerce\RefundOrder';

		if ( array_key_exists( $ref_type, $references ) ) {
			$reference = $references[ $ref_type ];
		} else {
			$reference = $default_reference;
		}

		$obj = false;

		try {
			$obj = new $reference( $order );
		} catch( \Exception $e ) {}

		if ( ! $obj || ! is_a( $obj, '\Vendidero\StoreaBill\Interfaces\RefundOrder' ) ) {
			try {
				$obj = new $default_reference( $order );
			} catch( \Exception $e ) {}
		}

		return $obj;
	}
}