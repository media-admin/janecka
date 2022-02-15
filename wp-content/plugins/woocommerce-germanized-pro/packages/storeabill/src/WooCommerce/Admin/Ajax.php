<?php

namespace Vendidero\StoreaBill\WooCommerce\Admin;

use Vendidero\StoreaBill\Package;
use Vendidero\StoreaBill\WooCommerce\Helper;
use Vendidero\StoreaBill\WooCommerce\Order;

/**
 * WC_Ajax class.
 */
class Ajax {

	/**
	 * Hook in ajax handlers.
	 */
	public static function init() {
		self::add_ajax_events();
	}

	/**
	 * Hook in methods - uses WordPress ajax handlers (admin-ajax).
	 */
	public static function add_ajax_events() {

		$ajax_events = array(
			'order_sync',
			'order_finalize',
			'delete_document',
			'cancel_invoice',
			'refresh_document',
			'send_document',
			'toggle_invoice_payment_status'
		);

		foreach ( $ajax_events as $ajax_event ) {
			add_action( 'wp_ajax_storeabill_woo_admin_' . $ajax_event, array( __CLASS__, 'suppress_errors' ), 5 );
			add_action( 'wp_ajax_storeabill_woo_admin_' . $ajax_event, array( __CLASS__, $ajax_event ) );
		}
	}

	public static function suppress_errors() {
		if ( ! WP_DEBUG || ( WP_DEBUG && ! WP_DEBUG_DISPLAY ) ) {
			@ini_set( 'display_errors', 0 ); // Turn off display_errors during AJAX events to prevent malformed JSON.
		}

		$GLOBALS['wpdb']->hide_errors();
	}

	public static function toggle_invoice_payment_status() {
		check_ajax_referer( 'sab-toggle-invoice-payment-status', 'security' );

		if ( ! current_user_can( 'edit_invoice' ) || ! isset( $_POST['id'] ) || ! isset( $_POST['enable'] ) ) {
			wp_die( -1 );
		}

		$response_error = array(
			'success'  => false,
			'messages' => array(
				_x( 'This invoice cannot be edited.', 'storeabill-core', 'woocommerce-germanized-pro' )
			),
			'data'     => false,
		);

		$enable      = sab_string_to_bool( $_POST['enable'] );
		$document_id = absint( $_POST['id'] );

		if ( ! $document = sab_get_invoice( $document_id ) ) {
			wp_send_json( $response_error );
		}

		if ( ! is_a( $document, '\Vendidero\StoreaBill\Invoice\Invoice' ) || ! ( $order = $document->get_order() ) ) {
			wp_send_json( $response_error );
		}

		/**
		 * Make sure we are working with the instance controlled by the order.
		 */
		$document = $order->get_document( $document_id );

		if ( $document->update_payment_status( $enable ? 'complete' : 'pending' ) ) {
			/**
			 * Necessary to make sure the order knows
			 * all adjusted documents e.g. cancellations adjusted by their parent
			 * during payment status update.
			 */
			$order->refresh();

			wp_send_json( array(
				'success'   => true,
				'data'      => $enable,
				'fragments' => array(
					'#sab-order-payment-status' => self::get_order_payment_status_html( $order ),
				),
			) );
		} else {
			wp_send_json( array(
				'success'   => false,
				'data'      => ! $enable,
				'fragments' => array(),
				'messages' => array(
					_x( 'An error occurred while updating the payment status.', 'storeabill-core', 'woocommerce-germanized-pro' )
				),
			) );
		}
	}

	public static function delete_document() {
		check_ajax_referer( 'sab-edit-woo-documents', 'security' );

		if ( ! current_user_can( 'edit_invoice' ) || ! isset( $_POST['order_id'] ) || ! isset( $_POST['document_id'] ) ) {
			wp_die( -1 );
		}

		$response_error = array(
			'success'  => false,
			'messages' => array(
				_x( 'This document cannot be deleted.', 'storeabill-core', 'woocommerce-germanized-pro' )
			),
		);

		$order_id    = absint( $_POST['order_id'] );
		$document_id = absint( $_POST['document_id'] );

		if ( empty( $order_id ) || ( ! $order = Helper::get_order( $order_id ) ) ) {
			self::send_json_error( $response_error );
		}

		if ( ! $document = $order->get_document( $document_id ) ) {
			self::send_json_error( $response_error );
		}

		if ( $order->delete_document( $document_id ) ) {
			$order->save();
			self::send_json_success( array( 'document_id' => $document_id ), $order );
		} else {
			self::send_json_error( $response_error );
		}
	}

	public static function send_document() {
		check_ajax_referer( 'sab-edit-woo-documents', 'security' );

		if ( ! current_user_can( 'edit_invoice' ) || ! isset( $_POST['order_id'] ) || ! isset( $_POST['document_id'] ) ) {
			wp_die( -1 );
		}

		$response_error = array(
			'success'  => false,
			'messages' => array(
				_x( 'This document cannot be sent by email.', 'storeabill-core', 'woocommerce-germanized-pro' )
			),
		);

		$order_id    = absint( $_POST['order_id'] );
		$document_id = absint( $_POST['document_id'] );

		if ( empty( $order_id ) || ( ! $order = Helper::get_order( $order_id ) ) ) {
			self::send_json_error( $response_error );
		}

		if ( ! $document = $order->get_document( $document_id ) ) {
			self::send_json_error( $response_error );
		}

		$result = $document->send_to_customer();

		if ( ! is_wp_error( $result ) ) {
			self::send_json_success( array(
				'document_id' => $document_id,
				'message'     => _x( 'Successfully sent to customer by email', 'storeabill-core', 'woocommerce-germanized-pro' )
			), $order );
		} else {
			$response_error = array(
				'success'  => false,
				'messages' => $result->get_error_messages(),
			);

			self::send_json_error( $response_error );
		}
	}

	public static function cancel_invoice() {
		check_ajax_referer( 'sab-edit-woo-documents', 'security' );

		if ( ! current_user_can( 'edit_invoice' ) || ! isset( $_POST['order_id'] ) || ! isset( $_POST['document_id'] ) ) {
			wp_die( -1 );
		}

		$response_error = array(
			'success'  => false,
			'messages' => array(
				_x( 'This invoice cannot be cancelled.', 'storeabill-core', 'woocommerce-germanized-pro' )
			),
		);

		$order_id    = absint( $_POST['order_id'] );
		$document_id = absint( $_POST['document_id'] );

		if ( empty( $order_id ) || ( ! $order = Helper::get_order( $order_id ) ) ) {
			self::send_json_error( $response_error );
		}

		if ( ! $document = $order->get_document( $document_id ) ) {
			self::send_json_error( $response_error );
		}

		$result = $document->cancel();

		if ( ! is_wp_error( $result ) ) {
			$order->add_document( $result );
			$order->save();

			self::send_json_success( array( 'document_id' => $document_id ), $order );
		} else {
			$response_error = array(
				'success'  => false,
				'messages' => $result->get_error_messages(),
			);

			self::send_json_error( $response_error );
		}
	}

	public static function refresh_document() {
		check_ajax_referer( 'sab-edit-woo-documents', 'security' );

		if ( ! current_user_can( 'edit_invoice' ) || ! isset( $_POST['order_id'] ) || ! isset( $_POST['document_id'] ) ) {
			wp_die( -1 );
		}

		$response_error = array(
			'success'  => false,
			'messages' => array(
				_x( 'This document cannot be refreshed.', 'storeabill-core', 'woocommerce-germanized-pro' )
			),
		);

		$order_id    = absint( $_POST['order_id'] );
		$document_id = absint( $_POST['document_id'] );

		if ( empty( $order_id ) || ( ! $order = Helper::get_order( $order_id ) ) ) {
			self::send_json_error( $response_error );
		}

		if ( ! $document = $order->get_document( $document_id ) ) {
			self::send_json_error( $response_error );
		}

		if ( $document->is_finalized() && $document->has_file() ) {
			self::send_json_error( $response_error );
		}

		$result = $document->render();

		if ( ! is_wp_error( $result ) ) {
			self::send_json_success( array( 'document_id' => $document_id ), $order );
		} else {
			$response_error = array(
				'success'  => false,
				'messages' => $result->get_error_messages(),
			);

			self::send_json_error( $response_error );
		}

		if ( $order->delete_document( $document_id ) ) {
			$order->save();
			self::send_json_success( array( 'document_id' => $document_id ), $order );
		} else {
			self::send_json_error( $response_error );
		}
	}

	public static function order_sync() {
		check_ajax_referer( 'sab-edit-woo-documents', 'security' );

		if ( ! current_user_can( 'edit_invoice' ) || ! isset( $_POST['order_id'] ) ) {
			wp_die( -1 );
		}

		$response       = array();
		$response_error = array(
			'success'  => false,
			'messages' => array(
				_x( 'There was an error processing the document.', 'storeabill-core', 'woocommerce-germanized-pro' )
			),
		);

		$order_id = absint( $_POST['order_id'] );
		$add_new  = isset( $_POST['add_new'] ) ? sab_string_to_bool( sab_clean( $_POST['add_new'] ) ) : true;

		if ( empty( $order_id ) || ( ! $order = Helper::get_order( $order_id ) ) ) {
			self::send_json_error( $response_error );
		}

		$result = $order->sync_order( $add_new );

		if ( is_wp_error( $result ) ) {
			$response = array(
				'success'  => false,
				'messages' => $result->get_error_messages()
			);
		}

		self::send_json_success( $response, $order );
	}

	public static function order_finalize() {
		check_ajax_referer( 'sab-edit-woo-documents', 'security' );

		if ( ! current_user_can( 'edit_invoice' ) || ! isset( $_POST['order_id'] ) ) {
			wp_die( -1 );
		}

		$response       = array();
		$response_error = array(
			'success'  => false,
			'messages' => array(
				_x( 'There was an while finalizing the order.', 'storeabill-core', 'woocommerce-germanized-pro' )
			),
		);

		$order_id = absint( $_POST['order_id'] );

		if ( empty( $order_id ) || ( ! $order = Helper::get_order( $order_id ) ) ) {
			self::send_json_error( $response_error );
		}

		$result = $order->finalize();

		if ( ! is_wp_error( $result ) ) {
			self::send_json_success( $response, $order );
		} else {
			$response_error = array(
				'success'  => false,
				'messages' => $result->get_error_messages(),
			);

			self::send_json_error( $response_error );
		}
	}

	/**
	 * @param $response
	 * @param Order $order
	 */
	private static function send_json_success( $response, $order ) {
		$response['fragments'] = isset( $response['fragments'] ) ? $response['fragments'] : array();
		$response['success']   = isset( $response['success'] ) ? $response['success'] : true;

		$response['fragments']['#sab-order-invoices'] = self::get_order_html( $order );

		wp_send_json( $response );
	}

	/**
	 * @param $response
	 * @param Order $order
	 */
	private static function get_order_html( $order ) {
		/**
		 * Setup global document_order variable used within the script
		 */
		$sab_order = $order;

		ob_start();
		include( Package::get_path() . '/includes/admin/views/html-order-invoices.php' );
		$html = ob_get_clean();

		return $html;
	}

	/**
	 * @param $response
	 * @param Order $order
	 */
	private static function get_order_payment_status_html( $order ) {
		/**
		 * Setup global document_order variable used within the script
		 */
		$sab_order = $order;

		ob_start();
		include( Package::get_path() . '/includes/admin/views/html-order-payment-status.php' );
		$html = ob_get_clean();

		return $html;
	}

	/**
	 * @param $response
	 * @param Order|boolean $order
	 */
	private static function send_json_error( $response, $order = false ) {
		$response['fragments'] = isset( $response['fragments'] ) ? $response['fragments'] : array();
		$response['success']   = false;

		if ( $order ) {
			$response['fragments']['#sab-order-invoices'] = self::get_order_html( $order );
		}

		wp_send_json( $response );
	}
}