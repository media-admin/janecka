<?php

namespace Vendidero\StoreaBill\WooCommerce\Admin;

use Vendidero\StoreaBill\WooCommerce\Helper;

defined( 'ABSPATH' ) || exit;

class Settings {

	public static function get_invoice_settings() {
		$email_select   = array();
		$gateway_select = array();

		foreach( Helper::get_order_emails() as $email ) {
			$email_select[ $email->id ] = $email->get_title();
		}

		foreach( Helper::get_available_payment_methods() as $method ) {
			$gateway_select[ $method->id ] = $method->get_title();
		}

		$settings = array(
			array(
				'title' 	     => _x( 'Automation', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc' 		     => _x( 'Automatically create invoices to orders.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'id' 		     => 'storeabill_invoice_woo_order_auto_create',
				'default'	     => 'yes',
				'type' 		     => 'sab_toggle',
			),
			array(
				'title' 	     => _x( 'Timing', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc' 		     => '<div class="sab-additional-desc">' . _x( 'Choose when to automatically generate an invoice to an order. In WooCommerce detecting whether an order has been paid or not works by checking it\'s status. That\'s why an order marked as completed or processing is considered paid.', 'storeabill-core', 'woocommerce-germanized-pro' ) . '</div>',
				'id' 		     => 'storeabill_invoice_woo_order_auto_create_timing',
				'default'	     => 'paid',
				'type'           => 'select',
				'class'          => 'sab-enhanced-select',
				'options'        => array(
					'checkout'              => _x( 'After checkout', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'paid'                  => _x( 'After payment', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'status'                => _x( 'On status(es)', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'status_payment_method' => _x( 'On status per payment method', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'email'                 => _x( 'On transactional email', 'storeabill-core', 'woocommerce-germanized-pro' )
				),
				'custom_attributes' => array(
					'data-show_if_storeabill_invoice_woo_order_auto_create' => 'yes'
				),
			),
			array(
				'title' 	     => _x( 'Order status(es)', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc' 		     => '<div class="sab-additional-desc">' . sprintf( _x( 'Select one or more order statuses. An invoice is generated as soon as an order reaches one of the order statuses selected.', 'storeabill-core', 'woocommerce-germanized-pro' ) ) . '</div>',
				'id' 		     => 'storeabill_invoice_woo_order_auto_create_statuses',
				'default'	     => array(),
				'type'           => 'multiselect',
				'class'          => 'sab-enhanced-select',
				'options'        => Helper::get_order_statuses(),
				'custom_attributes' => array(
					'data-show_if_storeabill_invoice_woo_order_auto_create' => 'yes',
					'data-show_if_storeabill_invoice_woo_order_auto_create_timing' => 'status'
				),
			),
			array(
				'title' 	     => _x( 'Transactional email(s)', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc' 		     => '<div class="sab-additional-desc">' . sprintf( _x( 'Choose one ore more email templates. The invoice will be generated and attached to the email as soon as the transactional email is delivered to the recipient. Be aware that the invoice is generated as soon as the email is sent - there is no way to determine the timing other than that.', 'storeabill-core', 'woocommerce-germanized-pro' ) ) . '</div>',
				'id' 		     => 'storeabill_invoice_woo_order_auto_create_emails',
				'default'	     => array(),
				'type'           => 'multiselect',
				'class'          => 'sab-enhanced-select',
				'options'        => $email_select,
				'custom_attributes' => array(
					'data-show_if_storeabill_invoice_woo_order_auto_create' => 'yes',
					'data-show_if_storeabill_invoice_woo_order_auto_create_timing' => 'email'
				),
			),
			array(
				'title' 	     => _x( 'Status(es) per method', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc' 		     => '<div class="sab-additional-desc">' . sprintf( _x( 'Select one or more order statuses. An invoice is generated as soon as an order reaches one of the order statuses selected.', 'storeabill-core', 'woocommerce-germanized-pro' ) ) . '</div>',
				'type'           => 'sab_woo_payment_method_statuses'
			),
			array(
				'title' 	     => _x( 'Gateways', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc' 		     => _x( 'Do create invoices for specific payment gateways only.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'id' 		     => 'storeabill_invoice_woo_order_auto_payment_gateway_specific',
				'default'	     => 'no',
				'type' 		     => 'sab_toggle',
				'custom_attributes' => array(
					'data-show_if_storeabill_invoice_woo_order_auto_create' => 'yes',
				),
			),
			array(
				'title' 	     => _x( 'Payment gateways', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc' 		     => '<div class="sab-additional-desc">' . _x( 'Choose payment gateways for which an invoice should be generated automatically. For all non-selected gateways no invoice will be generated.', 'storeabill-core', 'woocommerce-germanized-pro' ) . '</div>',
				'id' 		     => 'storeabill_invoice_woo_order_auto_payment_gateways',
				'default'	     => array(),
				'type'           => 'multiselect',
				'class'          => 'sab-enhanced-select',
				'options'        => $gateway_select,
				'custom_attributes' => array(
					'data-placeholder' => _x( 'All payment gateways', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'data-show_if_storeabill_invoice_woo_order_auto_create' => 'yes',
					'data-show_if_storeabill_invoice_woo_order_auto_payment_gateway_specific' => 'yes'
				),
			),
			array(
				'title' 	     => _x( 'Finalize', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc' 		     => _x( 'Finalize invoice after automatically creating it.', 'storeabill-core', 'woocommerce-germanized-pro' ) . '<div class="sab-additional-desc">' . sprintf( _x( 'This option will make sure that the invoice is <a href="%s" target="_blank">finalized</a> right after creating it automatically. If you decide to not finalize the invoice after creating it, it will be in draft mode and cannot be sent to the customer or synced via external interfaces until it has been finalized manually.', 'storeabill-core', 'woocommerce-germanized-pro' ), apply_filters( 'storeabill_invoice_finalize_help_link', '#' ) ) . '</div>',
				'id' 		     => 'storeabill_invoice_woo_order_auto_finalize',
				'default'	     => 'yes',
				'type' 		     => 'sab_toggle',
				'custom_attributes' => array(
					'data-show_if_storeabill_invoice_woo_order_auto_create' => 'yes'
				),
			),
			array(
				'title' 	     => _x( 'Free orders', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc' 		     => _x( 'Create invoices to orders with zero total.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'id' 		     => 'storeabill_invoice_woo_order_free',
				'default'	     => 'yes',
				'type' 		     => 'sab_toggle',
			),
			array(
				'title' 	     => _x( 'Download', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc' 		     => _x( 'Add download link to customer orders panel.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'id' 		     => 'storeabill_invoice_woo_order_invoice_customer_download',
				'default'	     => 'yes',
				'type' 		     => 'sab_toggle',
			),
		);

		return $settings;
	}

	public static function get_cancellation_settings() {
		return array(
			array(
				'title' 	     => _x( 'Download', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc' 		     => _x( 'Add download link to customer orders panel.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'id' 		     => 'storeabill_invoice_woo_order_invoice_cancellation_customer_download',
				'default'	     => 'yes',
				'type' 		     => 'sab_toggle',
			),
		);
	}

	public static function after_save_invoices() {
		$auto_statuses     = isset( $_POST['auto_order_status'] ) ? (array) $_POST['auto_order_status'] : array();
		$new_auto_statuses = array();
		$gateways          = Helper::get_available_payment_methods();

		foreach( $auto_statuses as $method_id => $statuses ) {
			$method_id = sab_clean( $method_id );
			$statuses  = array_filter( array_map( 'sab_clean', $statuses ) );

			if ( ! array_key_exists( $method_id, $gateways ) ) {
				continue;
			}

			$new_auto_statuses[ $method_id ] = $statuses;
		}

		update_option( 'storeabill_invoice_woo_order_payment_method_statuses', $new_auto_statuses );
	}
}