<?php

namespace Vendidero\StoreaBill\WooCommerce;

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
class BulkSync extends BulkActionHandler {

	public function get_object_type() {
		return 'shop_order';
	}

	public function get_title() {
		return _x( 'Create and finalize invoices', 'storeabill-core', 'woocommerce-germanized-pro' );
	}

	public function get_admin_url() {
		return admin_url( 'edit.php?post_type=shop_order' );
	}

	public function handle() {
		$current = $this->get_current_ids();

		if ( ! empty( $current ) ) {
			foreach ( $current as $order_id ) {
				CacheHelper::prevent_caching();

				if ( $order = Helper::get_order( $order_id ) ) {
					$result = $order->sync_order( true, array( 'created_via' => 'bulk_action' ) );

					if ( ! is_wp_error( $result ) ) {
						$result = $order->finalize();
					}

					if ( is_wp_error( $result ) ) {
						foreach( $result->get_error_messages() as $error ) {
							/* translators: 1: order id 2: error message */
							$this->add_notice( sprintf( _x( '%1$s error: %2$s', 'storeabill-core', 'woocommerce-germanized-pro' ), $order->get_id(), $error ), 'error' );
						}
					}
				}
			}
		}
	}

	public function get_success_message() {
		return _x( 'Invoices updated successfully', 'storeabill-core', 'woocommerce-germanized-pro' );
	}

	public function get_limit() {
		return 1;
	}

	public function get_action_name() {
		return 'sync_invoices';
	}
}