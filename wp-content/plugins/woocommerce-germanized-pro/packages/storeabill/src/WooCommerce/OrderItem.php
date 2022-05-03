<?php

namespace Vendidero\StoreaBill\WooCommerce;
use Vendidero\StoreaBill\Document\Attribute;
use Vendidero\StoreaBill\Document\Item;
use Vendidero\StoreaBill\Interfaces\SyncableReferenceItem;

use WC_Order_Item;

defined( 'ABSPATH' ) || exit;

/**
 * WooOrder class
 */
class OrderItem implements SyncableReferenceItem {

	/**
	 * The actual order item object
	 *
	 * @var WC_Order_Item
	 */
	protected $order_item;

	/**
	 * @param WC_Order_Item|integer $order_item
	 */
	public function __construct( $order_item ) {
		if ( is_numeric( $order_item ) ) {
			$order_item = \WC_Order_Factory::get_order_item( $order_item );
		}

		if ( ! is_a( $order_item, 'WC_Order_Item' ) ) {
			throw new \Exception( _x( 'Invalid order item.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}

		$this->order_item = $order_item;
	}

	public function get_reference_type() {
		return 'woocommerce';
	}

	public function get_hook_prefix() {
		return "storeabill_woo_order_item_{$this->get_type()}_";
	}

	/**
	 * Returns the Woo WC_Order_Item original object
	 *
	 * @return object|WC_Order_Item
	 */
	public function get_order_item() {
		return $this->order_item;
	}

	public function get_object() {
		return $this->get_order_item();
	}

	public function get_id() {
		return $this->order_item->get_id();
	}

	public function get_name() {
		return $this->order_item->get_name();
	}

	public function get_quantity() {
		return $this->order_item->get_quantity();
	}

	public function get_type() {
		return $this->order_item->get_type();
	}

	public function get_document_item_type() {
		return Helper::get_document_item_type( $this->get_type() );
	}

	/**
	 * @param Item $document_item
	 */
	public function sync( &$document_item, $args = array() ) {
		$args = wp_parse_args( $args, array(
			'quantity'       => 1,
			'reference_id'   => $this->get_id(),
			'name'           => $this->get_name(),
			'attributes'     => $this->get_attributes( $document_item )
		) );

		$document_item->set_props( $args );
	}

	/**
	 * @param Item $document_item
	 *
	 * @return Attribute[]
	 */
	public function get_attributes( $document_item ) {
		do_action( 'storeabill_woo_order_item_before_retrieve_attributes', $document_item, $this );
		$meta = $this->order_item->get_formatted_meta_data( apply_filters( "{$this->get_hook_prefix()}hide_meta_prefix", '_', $this ), apply_filters( "{$this->get_hook_prefix()}include_all_meta", false, $this, $document_item ) );
		do_action( 'storeabill_woo_order_item_after_retrieve_attributes', $document_item, $this );

		$meta = apply_filters( "{$this->get_hook_prefix()}order_item_meta_to_sync", $meta, $this, $document_item );

		$attributes = array();
		$order      = 0;

		foreach( $meta as $entry ) {
			$order ++;

			$attributes[] = new Attribute( array(
				'key'   => $entry->key,
				'value' => str_replace( array( '<p>', '</p>' ), '', $entry->display_value ),
				'label' => $entry->display_key,
				'order' => $order,
			) );
		}

		return $attributes;
	}

	public function get_meta( $key, $single = true, $context = 'view' ) {
		return $this->get_order_item()->get_meta( $key, $single, $context );
	}

	/**
	 * Check if a method is callable by checking the underlying order item object.
	 * Necessary because is_callable checks will alway return true for this object
	 * due to overloading __call.
	 *
	 * @param $method
	 *
	 * @return bool
	 */
	public function is_callable( $method ) {
		if ( method_exists( $this, $method ) ) {
			return true;
		} elseif( is_callable( array( $this->get_order_item(), $method ) ) ) {
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

		if ( method_exists( $this->order_item, $method ) ) {
			return call_user_func_array( array( $this->order_item, $method ), $args );
		}

		return false;
	}
}