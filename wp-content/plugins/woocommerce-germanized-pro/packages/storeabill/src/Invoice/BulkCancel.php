<?php

namespace Vendidero\StoreaBill\Invoice;

use Vendidero\StoreaBill\Document\BulkActionHandler;
use Vendidero\StoreaBill\Utilities\CacheHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Shipment Order
 *
 * @class 		WC_GZD_Shipment_Order
 * @version		1.0.0
 * @author 		Vendidero
 */
class BulkCancel extends BulkActionHandler {

	public function get_title() {
		return _x( 'Cancel invoices', 'storeabill-core', 'woocommerce-germanized-pro' );
	}

	public function handle() {
		$current = $this->get_current_ids();

		if ( ! empty( $current ) ) {
			foreach ( $current as $invoice_id ) {
				CacheHelper::prevent_caching();

				if ( $invoice = sab_get_invoice( $invoice_id, 'simple' ) ) {
					if ( $invoice->is_cancelable() ) {
						$result = $invoice->cancel();

						if ( is_wp_error( $result ) ) {
							foreach( $result->get_error_messages() as $error ) {
								/* translators: 1: invoice title 2: error message */
								$this->add_notice( sprintf( _x( '%1$s error: %2$s', 'storeabill-core', 'woocommerce-germanized-pro' ), $invoice->get_title(), $error ), 'error' );
							}
						}
					}
				}
			}
		}
	}

	public function get_success_message() {
		return _x( 'Invoices cancelled successfully', 'storeabill-core', 'woocommerce-germanized-pro' );
	}

	public function get_limit() {
		return 1;
	}

	public function get_action_name() {
		return 'cancel';
	}
}