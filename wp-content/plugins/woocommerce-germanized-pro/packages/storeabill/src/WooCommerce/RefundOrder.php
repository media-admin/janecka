<?php

namespace Vendidero\StoreaBill\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * WooOrder class
 */
class RefundOrder implements \Vendidero\StoreaBill\Interfaces\RefundOrder {

	/**
	 * The actual order object
	 *
	 * @var \WC_Order_Refund
	 */
	protected $order;

	/**
	 * @param \WC_Order_Refund|integer $order
	 *
	 * @throws \Exception
	 */
	public function __construct( $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( ! is_a( $order, 'WC_Order_Refund' ) ) {
			throw new \Exception( _x( 'Invalid order.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}

		$this->order = $order;
	}

	public function get_reference_type() {
		return 'woocommerce';
	}

	/**
	 * Returns the Woo WC_Order original object
	 *
	 * @return object|\WC_Order_Refund
	 */
	public function get_order() {
		return $this->order;
	}

	public function get_object() {
		return $this->get_order();
	}

	public function get_hook_prefix() {
		return 'storeabill_woo_order_refund_';
	}

	public function get_id() {
		return $this->order->get_id();
	}

	public function get_formatted_number() {
		return $this->get_order()->get_id();
	}

	public function get_reason() {
		return $this->get_order()->get_reason();
	}

	public function get_meta( $key, $single = true, $context = 'view' ) {
		return $this->get_order()->get_meta( $key, $single, $context );
	}

	public function get_transaction_id() {
		$transaction_id = '';

		if ( $this->get_meta( '_sab_matched_refund_transaction_id' ) ) {
			$transaction_id = $this->get_meta( '_sab_matched_refund_transaction_id' );
		}

		return apply_filters( "{$this->get_hook_prefix()}transaction_id", $transaction_id, $this );
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
		} elseif( is_callable( array( $this->get_order(), $method ) ) ) {
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

		if ( method_exists( $this->order, $method ) ) {
			return call_user_func_array( array( $this->order, $method ), $args );
		}

		return false;
	}
}