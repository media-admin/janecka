<?php

namespace Vendidero\StoreaBill\WooCommerce;
use Vendidero\StoreaBill\Interfaces\SyncableReferenceItem;
use Vendidero\StoreaBill\Invoice\FeeItem;
use Vendidero\StoreaBill\Invoice\VoucherItem;
use WC_Order_Item;

defined( 'ABSPATH' ) || exit;

/**
 * WooOrder class
 */
class OrderItemVoucher extends OrderItemTaxable {

	public function get_document_item_type() {
		return 'accounting_voucher';
	}

	/**
	 * @param VoucherItem $document_item
	 */
	public function sync( &$document_item, $args = array() ) {
		do_action( "{$this->get_hook_prefix()}before_sync", $this, $document_item, $args );

		$args = wp_parse_args( $args, array(
			'line_total' => '',
		) );

		OrderItem::sync( $document_item, $args );

		$props = array(
			'code' => $this->order_item->get_meta( '_code' )
		);

		$props = apply_filters( "{$this->get_hook_prefix()}sync_props", $props, $this, $args );

		$document_item->set_props( $props );

		do_action( "{$this->get_hook_prefix()}synced", $this, $document_item, $args );
	}
}