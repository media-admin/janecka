<?php

namespace Vendidero\Germanized\Pro\StoreaBill;

use Vendidero\StoreaBill\Document\Document;
use Vendidero\StoreaBill\Interfaces\Order;

defined( 'ABSPATH' ) || exit;

class PackingSlip extends Document {

	protected $extra_data = array(
		'order_id'     => 0,
		'order_number' => '',
	);

	protected $data_store_name = 'packing_slip';

	/**
	 * @var null|Shipment
	 */
	protected $shipment = null;

	/**
	 * @var null|\WC_Order
	 */
	protected $order = null;

	public function get_type() {
		return 'packing_slip';
	}

	public function get_item_types() {
		return apply_filters( $this->get_hook_prefix() . 'item_types', array(
			'product'
		), $this );
	}

	/**
	 * @return bool|Order
	 */
	public function get_reference() {
		if ( is_null( $this->shipment ) ) {
			try {
				$this->shipment = new Shipment( $this->get_shipment_id() );
			} catch( \Exception $e ) {
				$this->shipment = false;
			}
		}

		return $this->shipment;
	}

	public function get_shipment() {
		return $this->get_reference();
	}

	public function get_shipment_id( $context = 'view' ) {
		return $this->get_reference_id( $context );
	}

	public function set_shipment_id( $id ) {
		$this->set_reference_id( $id );
	}

	public function get_shipment_number( $context = 'view' ) {
		return $this->get_reference_number( $context );
	}

	public function get_order_id( $context = 'view' ) {
		return $this->get_prop( 'order_id', $context );
	}

	public function get_order_number( $context = 'view' ) {
		$order_number = $this->get_prop( 'order_number', $context );

		if ( 'view' === $context && empty( $order_number ) ) {
			$order_number = $this->get_order_id();
		}

		return $order_number;
	}

	public function set_order_id( $id ) {
		$this->set_prop( 'order_id', absint( $id ) );

		$this->order = null;
	}

	public function set_order_number( $number ) {
		$this->set_prop( 'order_number', $number );
	}

	public function set_reference_id( $reference_id ) {
		parent::set_reference_id( $reference_id );

		$this->shipment = null;
	}

	/**
	 * @return bool|\WC_Order
	 */
	public function get_order() {
		if ( is_null( $this->order ) ) {
			$this->order = wc_get_order( $this->get_order_id() );
		}

		return $this->order;
	}

	/**
	 * Returns a formatted price based on internal options.
	 *
	 * @param string $price
	 *
	 * @return string
	 */
	public function get_formatted_price( $price, $type = '' ) {
		$args = array();

		if ( $order = $this->get_order() ) {
			$args['currency'] = $order->get_currency();
		}

		return sab_format_price( $price, $args );
	}

	protected function get_additional_number_placeholders() {
		return array(
			'{shipment_number}' => $this->get_shipment_number(),
			'{order_number}'    => $this->get_order_number(),
		);
	}
}