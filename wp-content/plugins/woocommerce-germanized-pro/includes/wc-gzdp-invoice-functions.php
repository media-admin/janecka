<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

function wc_gzdp_get_invoice_tax_share( $items, $type = 'shipping' ) {
	wc_deprecated_function( 'wc_gzdp_get_invoice_tax_share', '3.0.0' );

	return array();
}

function wc_gzdp_get_invoice_types( $type = '' ) {
	wc_deprecated_function( 'wc_gzdp_get_invoice_types', '3.0.0' );

	return array();
}

/**
 * @param $args
 *
 * @return \Vendidero\Germanized\Pro\StoreaBill\PackingSlip[]|\Vendidero\StoreaBill\Document\Document[]
 */
function wc_gzdp_get_packing_slips( $args ) {
	$query = new \Vendidero\Germanized\Pro\StoreaBill\PackingSlip\Query( $args );

	return $query->get_packing_slips();
}

/**
 * @param integer|\Vendidero\Germanized\Shipments\Shipment $shipment_id
 *
 * @return bool|WC_GZDP_Invoice_Packing_Slip
 */
function wc_gzdp_get_packing_slip_by_shipment( $shipment_id ) {
	$packing_slip = false;

	if ( $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
		$packing_slips = wc_gzdp_get_packing_slips( array(
			'reference_id'   => $shipment->get_id(),
			'reference_type' => 'germanized',
			'limit'          => 1,
		) );

		if ( ! empty( $packing_slips ) ) {
			return wc_gzdp_get_packing_slip( $packing_slips[0] );
		}
	}

	return $packing_slip;
}

/**
 * @param int|\Vendidero\Germanized\Pro\StoreaBill\PackingSlip $packing_slip
 *
 * @return bool|WC_GZDP_Invoice_Packing_Slip
 */
function wc_gzdp_get_packing_slip( $packing_slip ) {
	$packing_slip = wc_gzdp_get_invoice( $packing_slip, 'packing_slip' );

	return $packing_slip;
}

function wc_gzdp_get_default_invoice_status() {
	wc_deprecated_function( 'wc_gzdp_get_default_invoice_status', '3.0.0' );

	return 'draft';
}

function wc_gzdp_get_invoice_statuses() {
	return sab_get_document_statuses( 'invoice' );
}

function wc_gzdp_get_next_invoice_number( $type ) {
	wc_deprecated_function( 'wc_gzdp_get_next_invoice_number', '3.0.0' );
}

function wc_gzdp_get_tax_label( $rate_id, $order = false ) {
	wc_deprecated_function( 'wc_gzdp_get_tax_label', '3.0.0' );
}

function wc_gzdp_order_has_invoice_type( $order, $type = 'simple' ) {
	wc_deprecated_function( 'wc_gzdp_order_has_invoice_type', '3.0.0' );

	return false;
}

function wc_gzdp_order_supports_new_invoice( $order ) {
	wc_deprecated_function( 'wc_gzdp_order_supports_new_invoice', '3.0.0' );

	return false;
}

/**
 * @param WC_Order|integer $order
 * @param bool $type
 *
 * @return WC_GZDP_Invoice[]
 */
function wc_gzdp_get_invoices_by_order( $order, $type = false ) {
	$return = array();

	if ( $sab_order = \Vendidero\StoreaBill\WooCommerce\Helper::get_order( $order ) ) {
		if ( ! $type ) {
			$return = $sab_order->get_finalized_documents();
		} else {
			if ( 'simple' === $type ) {
				$type = 'invoice';
			} elseif( 'cancellation' === $type ) {
				$type = 'invoice_cancellation';
			}

			$return = $sab_order->get_finalized_documents( $type );
		}
	}

	foreach( $return as $key => $document ) {
		$return[ $key ] = wc_gzdp_get_invoice( $document, $document->get_invoice_type() );
	}

	return $return;
}

function wc_gzdp_get_order_last_invoice( $order ) {
	$invoices   = wc_gzdp_get_invoices_by_order( $order, 'simple' );
	$best_match = null;

	foreach ( $invoices as $invoice ) {
		if ( 'cancelled' !== $invoice->get_status() ) {
			$best_match = $invoice;
		}
	}

	if ( is_null( $best_match ) && ! empty( $invoices ) ) {
		$best_match = end( $invoices );
	}

	return $best_match;
}

function wc_gzdp_is_invoice( $invoice ) {
	wc_deprecated_function( 'wc_gzdp_is_invoice', '3.0.0' );

	return false;
}

function wc_gzdp_get_invoice_download_url( $invoice_id ) {
	if ( $invoice = sab_get_invoice( $invoice_id ) ) {
		if ( $invoice->is_finalized() ) {
			return $invoice->get_download_url();
		}
	}

	return '';
}

function wc_gzdp_get_invoice( $invoice = false, $type = 'simple' ) {
	if ( 'packing_slip' === $type ) {
		return WC_GZDP_Document_Factory::get_document( $invoice, 'packing_slip' );
	} else {
		return WC_GZDP_Invoice_Factory::get_invoice( $invoice, $type );
	}
}

function wc_gzdp_get_invoice_frontend_types() {
	wc_deprecated_function( 'wc_gzdp_get_invoice_frontend_types', '3.0.0' );

	return array();
}

function wc_gzdp_get_invoice_total_refunded_amount( $invoice ) {
	wc_deprecated_function( 'wc_gzdp_get_invoice_total_refunded_amount', '3.0.0' );

	return 0;
}

function wc_gzdp_invoice_fully_refunded( $invoice ) {
	wc_deprecated_function( 'wc_gzdp_invoice_fully_refunded', '3.0.0' );

	return false;
}

function wc_gzdp_get_order_meta( $product, $item ) {
	wc_deprecated_function( 'wc_gzdp_get_order_meta', '3.0.0' );

	return false;
}

/**
 * @param $product
 * @param WC_Order_Item $item
 *
 * @return mixed|void
 */
function wc_gzdp_get_order_meta_print( $product, $item ) {
	wc_deprecated_function( 'wc_gzdp_get_order_meta_print', '3.0.0' );

	return '';
}

function wc_gzdp_get_order_item_tax_rate( $item, $order ) {
	wc_deprecated_function( 'wc_gzdp_get_order_item_tax_rate', '3.0.0' );

	return '';
}

/**
 * @param $cart_item
 *
 * @return bool|mixed
 */
function wc_gzdp_get_invoice_unit_price_excl( $cart_item ) {
	wc_deprecated_function( 'wc_gzdp_get_invoice_unit_price_excl', '3.0.0' );

	return false;
}

function wc_gzdp_invoice_order_price( $price, $invoice ) {
	wc_deprecated_function( 'wc_gzdp_invoice_order_price', '3.0.0' );

	return $price;
}

function wc_gzdp_get_invoice_quantity( $item ) {
	wc_deprecated_function( 'wc_gzdp_get_invoice_quantity', '3.0.0' );

	return 1;
}

function wc_gzdp_get_invoice_default_author() {
	wc_deprecated_function( 'wc_gzdp_get_invoice_default_author', '3.0.0' );

	return 1;
}

function wc_gzdp_get_invoice_item_total_discount( $item, $tax_display = 'incl' ) {
	wc_deprecated_function( 'wc_gzdp_get_invoice_item_total_discount', '3.0.0' );

	return '';
}
