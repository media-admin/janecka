<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WooCommerce WC_AJAX
 *
 * AJAX Event Handler
 *
 * @class 		WC_AJAX
 * @version		2.2.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WC_GZDP_AJAX {

	public static function init() {

		$ajax_events = array(
			'confirm_order' => false,
		);

		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			add_action( 'wp_ajax_woocommerce_gzdp_' . $ajax_event, array( __CLASS__, $ajax_event ) );

			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_woocommerce_gzdp_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			}
		}
	}

	public static function confirm_order() {

		if ( current_user_can( 'edit_shop_orders' ) && check_admin_referer( 'woocommerce-gzdp-confirm-order' ) ) {
			$order_id = absint( $_GET['order_id'] );

			if ( $order_id ) {
				$order = wc_get_order( $order_id );
				WC_germanized_pro()->contract_helper->confirm_order( $order->get_id() );
			}
		}

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=shop_order' ) );
		die();
	}
}

WC_GZDP_AJAX::init();
