<?php

namespace Vendidero\StoreaBill\Invoice;

use Vendidero\StoreaBill\Package;

defined( 'ABSPATH' ) || exit;

/**
 * CancellationInvoice class
 */
class Cancellation extends Invoice {

	protected $refund_order = null;

	protected $extra_data = array(
		'parent_formatted_number' => '',
		'parent_number'           => '',
		'refund_order_id'         => 0,
		'refund_order_number'     => '',
		'reason'                   => ''
	);

	public function get_invoice_type() {
		return 'cancellation';
	}

	public function get_journal_type( $context = 'view' ) {
		$type = $this->get_prop( 'journal_type', $context );

		if ( 'view' === $context && empty( $type ) ) {
			$type = $this->get_type();

			if ( 'no' === Package::get_setting( 'invoice_cancellation_separate_numbers' ) ) {
				$type = 'invoice';
			}
		}

		return $type;
	}

	public function get_parent_formatted_number( $context = 'view' ) {
		return $this->get_prop( 'parent_formatted_number', $context );
	}

	public function set_parent_formatted_number( $number ) {
		$this->set_prop( 'parent_formatted_number', $number );
	}

	public function get_parent_number( $context = 'view' ) {
		return $this->get_prop( 'parent_number', $context );
	}

	public function set_parent_number( $number ) {
		$this->set_prop( 'parent_number', $number );
	}

	public function get_parent() {
		return ( $this->get_parent_id() > 0 ) ? sab_get_invoice( $this->get_parent_id() ) : false;
	}

	public function get_refund_order_id( $context = 'view' ) {
		return $this->get_prop( 'refund_order_id', $context );
	}

	public function get_reason( $context = 'view' ) {
		return $this->get_prop( 'reason', $context );
	}

	public function set_refund_order_id( $refund_id ) {
		$this->set_prop( 'refund_order_id', $refund_id );

		$this->refund_order = null;
	}

	public function has_refund_order() {
		$refund_id = $this->get_refund_order_id();

		return ( ! empty( $refund_id ) ? true : false );
	}

	public function get_refund_order_number( $context = 'view' ) {
		$number = $this->get_prop( 'refund_order_number', $context );

		if ( 'view' === $context && empty( $number ) ) {
			$number = $this->get_refund_order_id();
		}

		return $number;
	}

	public function set_refund_order_number( $refund_id ) {
		$this->set_prop( 'refund_order_number', $refund_id );
	}

	public function set_reason( $reason ) {
		$this->set_prop( 'reason', $reason );
	}

	/**
	 * @return bool|\Vendidero\StoreaBill\Interfaces\RefundOrder
	 */
	public function get_refund_order() {
		if ( is_null( $this->refund_order ) ) {
			$this->refund_order = \Vendidero\StoreaBill\References\RefundOrder::get_refund_order( $this->get_refund_order_id(), $this->get_order_type() );
		}

		return $this->refund_order;
	}

	/**
	 * Returns a formatted price based on internal options.
	 *
	 * @param string $price
	 *
	 * @return string
	 */
	public function get_formatted_price( $price, $type = '' ) {
		$price = ( $price > 0 || $price < 0 ) ? $price * -1 : $price;

		/**
		 * Discounts should not be negative.
		 */
		if ( strpos( $type, 'discount' ) !== false && $price < 0 ) {
			$price *= -1;
		}

		return sab_format_price( $price, array( 'currency' => $this->get_currency() ) );
	}
}