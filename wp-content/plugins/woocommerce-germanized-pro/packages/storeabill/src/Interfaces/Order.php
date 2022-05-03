<?php

namespace Vendidero\StoreaBill\Interfaces;

/**
 * Order Interface
 *
 * @package  Germanized/StoreaBill/Interfaces
 * @version  1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order class.
 */
interface Order extends SyncableReference {

	public function is_paid();

	public function get_date_paid();

	public function is_reverse_charge();

	public function get_vat_id();

	public function get_email();

	public function get_payment_method();

	public function get_status();

	public function get_transaction_id();

	public function get_refund_transaction_id( $refund );

	public function get_taxable_country();

	public function get_taxable_postcode();

	public function get_order_item( $item_id );

	public function get_documents( $type = '' );

	public function get_finalized_documents( $type = '' );

	public function get_document( $document_id );

	public function add_document( &$document );

	public function delete_document( $document_id );

	public function get_invoices();

	public function get_finalized_invoices();

	public function get_cancellations();

	public function get_finalized_cancellations();

	public function get_total_billed();

	public function get_edit_url();

	public function get_formatted_number();

	public function validate( $cancellation_props = array() );

	public function cancel( $reason = '', $cancellation_props = array() );

	public function get_invoice_total_unpaid();

	public function get_invoice_payment_status();

	public function sync_order( $add_new = true, $args = array() );

	public function needs_finalization();

	public function needs_sync();

	public function has_draft();

	public function finalize( $defer_render = false );

	public function save();

	/**
	 * Reload documents.
	 *
	 * @return mixed
	 */
	public function refresh();
}
