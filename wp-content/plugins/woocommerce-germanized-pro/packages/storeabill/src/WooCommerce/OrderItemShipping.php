<?php

namespace Vendidero\StoreaBill\WooCommerce;
use Vendidero\StoreaBill\Interfaces\SyncableReferenceItem;
use Vendidero\StoreaBill\Invoice\ShippingItem;
use WC_Order_Item;

defined( 'ABSPATH' ) || exit;

/**
 * WooOrder class
 */
class OrderItemShipping extends OrderItemTaxable {

	/**
	 * @param ShippingItem $document_item
	 */
	public function sync( &$document_item, $args = array() ) {
		do_action( "{$this->get_hook_prefix()}before_sync", $this, $document_item, $args );

		parent::sync( $document_item, $args );

		$props = apply_filters( "{$this->get_hook_prefix()}sync_props", array(), $this, $args );

		$document_item->set_props( $props );

		do_action( "{$this->get_hook_prefix()}synced", $this, $document_item, $args );
	}

	public function get_attributes( $document_item ) {
		$attributes = parent::get_attributes( $document_item );

		foreach( $attributes as $key => $attribute ) {
			/**
			 * The Position meta includes a string of the items which are shipping costs related.
			 * This string-representation may not be relevant as invoices may not reflect order 1:1.
			 */
			if ( strstr( strtolower( $attribute->get_key() ), 'position' ) ) {
				unset( $attributes[ $key ] );
			}
		}

		return $attributes;
	}
}