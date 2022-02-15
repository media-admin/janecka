<?php

namespace Vendidero\StoreaBill\WooCommerce;

use Vendidero\StoreaBill\Document\BulkMerge;
use Vendidero\StoreaBill\Package;

defined( 'ABSPATH' ) || exit;

/**
 * Shipment Order
 *
 * @class 		WC_GZD_Shipment_Order
 * @version		1.0.0
 * @author 		Vendidero
 */
class BulkDownload extends BulkMerge {

	public function get_object_type() {
		return 'shop_order';
	}

	public function get_title() {
		return _x( 'Download invoices', 'storeabill-core', 'woocommerce-germanized-pro' );
	}

	public function get_admin_url() {
		return admin_url( 'edit.php?post_type=shop_order' );
	}

	public function can_download() {
		return current_user_can( 'read_invoice' );
	}

	public function handle() {
		$current = $this->get_current_ids();

		/**
		 * Reset file.
		 */
		if ( 1 === $this->get_step() ) {
			delete_user_meta( get_current_user_id(), $this->get_file_option_name() );
			delete_user_meta( get_current_user_id(), $this->get_files_option_name() );
		}

		if ( ! empty( $current ) ) {
			foreach ( $current as $order_id ) {
				if ( $order = Helper::get_order( $order_id ) ) {
					foreach( $order->get_finalized_documents() as $document ) {
						if ( $document->has_file() ) {
							$this->add_file( $document->get_relative_path() );
						}
					}
				}
			}
		}

		if ( $this->is_last_step() ) {
			$this->merge();
		}
	}

	public function get_limit() {
		return 15;
	}

	public function get_action_name() {
		return 'merge_invoices';
	}
}