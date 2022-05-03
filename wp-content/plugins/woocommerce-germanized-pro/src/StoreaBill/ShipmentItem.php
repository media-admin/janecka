<?php

namespace Vendidero\Germanized\Pro\StoreaBill;

use Vendidero\StoreaBill\Document\Attribute;
use Vendidero\StoreaBill\Interfaces\SyncableReferenceItem;

defined( 'ABSPATH' ) || exit;

class ShipmentItem implements SyncableReferenceItem {

	/**
	 * @var null|\Vendidero\Germanized\Shipments\ShipmentItem
	 */
	protected $shipment_item = null;

	public function __construct( $item ) {
		$this->shipment_item = $item;
	}

	public function get_type() {
		return $this->shipment_item->get_type();
	}

	public function get_hook_prefix() {
		return "storeabill_woo_shipment_item_{$this->get_type()}_";
	}

	/**
	 * @return \Vendidero\Germanized\Shipments\ShipmentItem
	 */
	public function get_item() {
		return $this->shipment_item;
	}

	public function get_object() {
		return $this->get_item();
	}

	public function get_reference_type() {
		return 'germanized';
	}

	public function get_id() {
		return $this->shipment_item->get_id();
	}

	public function get_quantity() {
		return $this->shipment_item->get_quantity();
	}

	public function get_name() {
		return $this->shipment_item->get_name();
	}

	public function get_document_item_type() {
		return 'shipments_product';
	}

	/**
	 * @param \Vendidero\Germanized\Pro\StoreaBill\PackingSlip\ProductItem $object
	 *
	 * @return array
	 */
	public function get_attributes( $object ) {
		$meta = array();

		if ( $order_item = $this->shipment_item->get_order_item() ) {
			do_action( 'storeabill_woo_shipment_item_before_retrieve_attributes', $object, $this );
			$meta = $order_item->get_formatted_meta_data( apply_filters( "{$this->get_hook_prefix()}hide_meta_prefix", '_', $this ), apply_filters( "{$this->get_hook_prefix()}include_all_meta", false, $this, $object ) );
			do_action( 'storeabill_woo_shipment_item_after_retrieve_attributes', $object, $this );
		}

		$meta = apply_filters( "{$this->get_hook_prefix()}order_item_meta_to_sync", $meta, $this, $object );

		$attributes     = array();
		$order          = 0;
		$existing_slugs = array();

		foreach( $meta as $entry ) {
			$order ++;

			$attributes[] = new Attribute( array(
				'key'   => $entry->key,
				'value' => str_replace( array( '<p>', '</p>' ), '', $entry->display_value ),
				'label' => $entry->display_key,
				'order' => $order,
			) );

			$existing_slugs[] = $entry->key;
		}

		$custom_attribute_slugs = array();

		if ( $document = $object->get_document() ) {
			if ( $template = $document->get_template() ) {
				$custom_attribute_slugs = $template->get_additional_attribute_slugs();
			}
		}

		if ( ! empty( $custom_attribute_slugs ) ) {
			if ( $product = \Vendidero\StoreaBill\References\Product::get_product( $this->shipment_item->get_product(), 'woocommerce' ) ) {
				$attributes = array_merge( $attributes, $product->get_additional_attributes( $custom_attribute_slugs, $existing_slugs ) );
			}
		}

		return $attributes;
	}

	public function get_sku() {
		return $this->shipment_item->get_sku();
	}

	public function get_price() {
		if ( $this->shipment_item->get_quantity() > 0 ) {
			return sab_format_decimal( $this->shipment_item->get_total() / $this->shipment_item->get_quantity() );
		} else {
			return 0;
		}
	}

	public function get_total() {
		return $this->shipment_item->get_total();
	}

	/**
	 * @param \Vendidero\Germanized\Pro\StoreaBill\PackingSlip\ProductItem $object
	 * @param array $args
	 */
	public function sync( &$object, $args = array() ) {
		do_action( "storeabill_woo_gzd_shipment_item_before_sync", $this, $object, $args );

		$props = wp_parse_args( $args, array(
			'quantity'     => $this->get_quantity(),
			'reference_id' => $this->get_id(),
			'name'         => $this->get_name(),
			'attributes'   => $this->get_attributes( $object ),
			'sku'          => $this->get_sku(),
			'price'        => $this->get_price(),
			'total'        => $this->get_total(),
		) );

		$props = apply_filters( "storeabill_woo_gzd_shipment_item_sync_props", $props, $this, $args );

		$object->set_props( $props );

		do_action( "storeabill_woo_gzd_shipment_item_synced", $this, $object, $args );
	}

	public function get_meta( $key, $single = true, $context = 'view' ) {
		return $this->get_item()->get_meta( $key, $single, $context );
	}

	public function is_callable( $method ) {
		if ( method_exists( $this, $method ) ) {
			return true;
		} elseif( is_callable( array( $this->get_item(), $method ) ) ) {
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

		if ( method_exists( $this->get_item(), $method ) ) {
			return call_user_func_array( array( $this->get_item(), $method ), $args );
		}

		return false;
	}
}