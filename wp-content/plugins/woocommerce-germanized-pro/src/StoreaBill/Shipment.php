<?php

namespace Vendidero\Germanized\Pro\StoreaBill;

use Vendidero\StoreaBill\Interfaces\SyncableReference;

defined( 'ABSPATH' ) || exit;

class Shipment implements SyncableReference {

	/**
	 * @var bool|\Vendidero\Germanized\Shipments\Shipment|null
	 */
	protected $shipment = null;

	/**
	 * @var null|PackingSlip
	 */
	protected $document = null;

	public function __construct( $shipment ) {
		if ( is_numeric( $shipment ) ) {
			$shipment = wc_gzd_get_shipment( $shipment );
		}

		if ( ! is_a( $shipment, '\Vendidero\Germanized\Shipments\Shipment' ) ) {
			throw new \Exception( __( 'Invalid shipment.', 'woocommerce-germanized-pro' ) );
		}

		$this->shipment = $shipment;
	}

	public function get_id() {
		return $this->shipment->get_id();
	}

	public function get_reference_type() {
		return 'germanized';
	}

	/**
	 * @return \Vendidero\Germanized\Shipments\Shipment
	 */
	public function get_shipment() {
		return $this->shipment;
	}

	public function get_object() {
		return $this->get_shipment();
	}

	/**
	 * @param \Vendidero\Germanized\Pro\StoreaBill\PackingSlip $packing_slip
	 * @param array $args
	 */
	public function sync( &$packing_slip, $args = array() ) {
		do_action( "woocommerce_gzdp_before_sync_packing_slip", $this->shipment, $args );

		$packing_slip_args = wp_parse_args( $args, array(
			'reference_id'     => $this->get_id(),
			'reference_number' => $this->shipment->get_shipment_number(),
			'reference_type'   => 'germanized',
			'order_id'         => $this->shipment->get_order_id(),
			'order_number'     => $this->shipment->get_order_number(),
			'country'          => $this->shipment->get_country(),
			'address'          => $this->shipment->get_address(),
		) );

		if ( $order = $this->shipment->get_order() ) {
			$packing_slip_args['customer_id'] = $order->get_customer_id();
		}

		$packing_slip->set_props( $packing_slip_args );

		foreach( $this->shipment->get_items() as $item ) {
			if ( $shipment_item = $this->get_item( $item ) ) {

				$is_new        = false;
				$document_item = $packing_slip->get_item_by_reference_id( $item->get_id() );

				if ( ! $document_item ) {
					$document_item = sab_get_document_item( 0, $shipment_item->get_document_item_type() );
					$is_new        = true;
				}

				$document_item->set_document( $packing_slip );
				$shipment_item->sync( $document_item );

				if ( $is_new ) {
					$packing_slip->add_item( $document_item );
				}
			}
		}

		/**
		 * Remove items that do not exist in parent shipment any longer.
		 */
		foreach( $packing_slip->get_items() as $item ) {
			if ( ! $shipment_item = $this->shipment->get_item( $item->get_reference_id() ) ) {
				$packing_slip->remove_item( $item->get_id() );
			}
		}

		do_action( "woocommerce_gzdp_synced_packing_slip", $packing_slip, $this->shipment, $args );

		do_action( "woocommerce_gzdp_after_sync_packing_slip", $this->shipment, $args );

		$this->document = $packing_slip;
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\ShipmentItem $item
	 *
	 * @return ShipmentItem
	 */
	public function get_item( $item ) {
		$classname = '\Vendidero\Germanized\Pro\StoreaBill\ShipmentItem';

		return new $classname( $item );
	}

	public function get_packing_slip() {
		if ( is_null( $this->document ) ) {
			$this->document = wc_gzdp_get_packing_slip_by_shipment( $this->get_id() );
		}

		return $this->document;
	}

	public function get_meta( $key, $single = true, $context = 'view' ) {
		return $this->get_shipment()->get_meta( $key, $single, $context );
	}

	public function is_callable( $method ) {
		if ( method_exists( $this, $method ) ) {
			return true;
		} elseif( is_callable( array( $this->get_shipment(), $method ) ) ) {
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

		if ( method_exists( $this->get_shipment(), $method ) ) {
			return call_user_func_array( array( $this->get_shipment(), $method ), $args );
		}

		return false;
	}
}