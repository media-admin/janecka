<?php

namespace Vendidero\StoreaBill\WooCommerce\Admin;

use Vendidero\StoreaBill\Admin\MetaBoxes\OrderInvoices;
use Vendidero\StoreaBill\Package;
use Vendidero\StoreaBill\WooCommerce\Helper;

defined( 'ABSPATH' ) || exit;

class InvoiceMetaBox {

	/**
	 * Output the metabox.
	 *
	 * @param \WP_Post $post
	 */
	public static function output( $post ) {
		global $post, $thepostid, $theorder, $sab_order;

		if ( ! is_int( $thepostid ) ) {
			$thepostid = $post->ID;
		}

		if ( ! is_object( $theorder ) ) {
			$theorder = wc_get_order( $thepostid );
		}

		if ( ! is_object( $sab_order ) ) {
			$sab_order = Helper::get_order( $theorder );
		}

		if ( $sab_order ) {
			$active_invoice = isset( $_GET['invoice_id'] ) ? absint( $_GET['invoice_id'] ) : 0;

			include( Package::get_path() . '/includes/admin/views/html-order-invoices.php' );
		}
	}

	/**
	 * Save meta box data.
	 *
	 * @param int $document_id
	 */
	public static function save( $document_id ) {

	}
}
