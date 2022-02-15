<?php
/**
 * StoreaBill Invoice Functions
 *
 * Invoice related functions available on both the front-end and admin.
 *
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Vendidero\StoreaBill\Invoice\Invoice;
use Vendidero\StoreaBill\Invoice\Simple;
use Vendidero\StoreaBill\Invoice\Cancellation;
use Vendidero\StoreaBill\Package;

/**
 * @param int    $invoice_id
 * @param string $invoice_type
 *
 * @return bool|Invoice|Simple|Cancellation
 */
function sab_get_invoice( $invoice_id = 0, $invoice_type = '' ) {
	$document_type = '';

	if ( ! empty( $invoice_type ) ) {
		$document_type = 'simple' === $invoice_type ? 'invoice' : 'invoice_' . $invoice_type;
	}

	if ( empty( $invoice_id ) && empty( $invoice_type ) ) {
		$document_type = 'invoice';
	}

	return sab_get_document( $invoice_id, $document_type );
}

/**
 * Standard way of retrieving invoices based on certain parameters.
 *
 * @param  array $args Array of args (above).
 *
 * @return Invoice[] The invoices found.
 * @since  1.0.0
 */
function sab_get_invoices( $args ) {
	$query = new \Vendidero\StoreaBill\Invoice\Query( $args );

	return $query->get_invoices();
}

/**
 * Get all available invoice statuses.
 *
 * @return array
 */
function sab_get_invoice_payment_statuses( $context = 'edit' ) {
	$payment_statuses = array(
		'pending'  => _x( 'Pending', 'storeabill-core', 'woocommerce-germanized-pro' ),
		'partial'  => _x( 'Partial', 'storeabill-core', 'woocommerce-germanized-pro' ),
		'complete' => _x( 'Complete', 'storeabill-core', 'woocommerce-germanized-pro' ),
	);

	if ( 'view' === $context ) {
		unset( $payment_statuses['partial'] );
	}

	/**
	 * Add or adjust available invoice payment statuses.
	 *
	 * @param array $statuses The available payment statuses.
	 *
	 * @since 1.0.0
	 * @package Vendidero/StoreaBill
	 */
	return apply_filters( 'storeabill_invoice_payment_statuses', $payment_statuses, $context );
}

function sab_get_invoice_payment_status_name( $status ) {
	$status_name = '';
	$statuses    = sab_get_invoice_payment_statuses();

	if ( array_key_exists( $status, $statuses ) ) {
		$status_name = $statuses[ $status ];
	}

	/**
	 * Filter to adjust the invoice payment status name or title.
	 *
	 * @param string  $status_name The status name or title.
	 * @param integer $status The status slug.
	 *
	 * @since 1.0.0
	 * @package Vendidero/StoreaBill
	 */
	return apply_filters( 'storeabill_invoice_payment_status_name', $status_name, $status );
}

function sab_get_invoice_payment_statuses_counts( $type = 'simple' ) {
	$counts = array();

	foreach( array_keys( sab_get_invoice_payment_statuses() ) as $status ) {
		$counts[ $status ] = sab_get_invoice_payment_status_count( $type, $status );
	}

	return $counts;
}

function sab_get_invoice_payment_status_count( $type, $status ) {
	$count    = 0;
	$statuses = array_keys( sab_get_invoice_payment_statuses() );

	if ( ! in_array( $status, $statuses, true ) ) {
		return 0;
	}

	$cache_key    = \Vendidero\StoreaBill\Utilities\CacheHelper::get_cache_prefix( 'invoices' ) . $status . $type;
	$cached_count = wp_cache_get( $cache_key, 'counts' );

	if ( false !== $cached_count ) {
		return $cached_count;
	}

	try {
		$data_store = sab_load_data_store( 'invoice' );

		if ( $data_store ) {
			$count += $data_store->get_payment_status_count( $status, $type );
		}

		wp_cache_set( $cache_key, $count, 'counts' );
	} catch( Exception $e ) {}

	return $count;
}

/**
 * @param WC_DateTime $date_created
 *
 * @return mixed
 */
function sab_calculate_invoice_date_due( $date_created ) {
	if ( 'days' === Package::get_setting( 'invoice_due' ) ) {
		$number_of_days = absint( Package::get_setting( 'invoice_due_days' ) );
		$number_of_days = empty( $number_of_days ) ? 14 : $number_of_days;

		$new_date_due = clone $date_created;
		$new_date_due->modify( '+ ' . $number_of_days . ' ' . ( $number_of_days > 1 ? 'days' : 'day' ) );

		return $new_date_due;
	} else {
		return $date_created;
	}
}

/**
 * @param Invoice $invoice
 */
function sab_invoice_has_line_total_after_discounts( $invoice ) {
	if ( $template = $invoice->get_template() ) {

		if ( $total_block = $template->get_block( 'storeabill/item-totals' ) ) {
			foreach( $total_block['innerBlocks'] as $total_row ) {
				$attributes = wp_parse_args( $total_row['attrs'], array(
					'totalType'   => '',
					'hideIfEmpty' => false,
					'heading'     => '',
					'content'     => '{total}',
				) );

				if ( in_array( $attributes['totalType'], array( 'line_subtotal_after', 'line_subtotal_after_net' ) ) ) {
					return true;
				}
			}
		}
	}

	return false;
}

function sab_get_invoice_discount_types() {
	return array(
		'single_purpose' => _x( 'Single-purpose', 'storeabill-discount-type', 'woocommerce-germanized-pro' ),
		'multi_purpose'  => _x( 'Multipurpose', 'storeabill-discount-type', 'woocommerce-germanized-pro' )
	);
}

/**
 * Make sure to automatically transform total types from the invoice template
 * to the right total type for very special cases, e.g. vouchers.
 *
 * @param string $total_type
 * @param Invoice $invoice
 */
function sab_map_invoice_total_type( $total_type, $invoice ) {
	/**
	 * Check whether to use subtotals (before discounts) or totals (after discounts) for fees, shipping.
	 */
	if ( in_array( $total_type, array( 'fee', 'shipping', 'fee_net', 'shipping_net' ) ) ) {
		$template  = $invoice->get_template();
		$item_type = str_replace( '_net', '', $total_type );

		/**
		 * Use subtotals in case the item type is not shown as line item type
		 * e.g. discounts cannot be removed for shipping and fees before showing totals.
		 */
		if ( ! in_array( $item_type, $template->get_line_item_types() ) ) {
			$total_type = $item_type . '_subtotal' . ( strpos( $total_type, '_net' ) !== false ? '_net' : '' );
		}
	} elseif ( in_array( $total_type, array( 'discount' ) ) ) {
		/**
		 * Replace default discount with the additional costs discount (e.g. for shipping and fees).
		 */
		if ( sab_invoice_has_line_total_after_discounts( $invoice ) ) {
			$total_type = 'additional_costs_discount';
		}
	}

	return $total_type;
}