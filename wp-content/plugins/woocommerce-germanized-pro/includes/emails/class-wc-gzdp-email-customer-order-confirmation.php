<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_GZDP_Email_Customer_Order_Confirmation' ) ) :

/**
 * Customer Processing Order Email
 *
 * An email sent to the customer when a new order is received/paid for.
 *
 * @class 		WC_Email_Customer_Processing_Order
 * @version		2.0.0
 * @package		WooCommerce/Classes/Emails
 * @author 		WooThemes
 * @extends 	WC_Email
 */
class WC_GZDP_Email_Customer_Order_Confirmation extends WC_Email_Customer_Processing_Order {

	public $helper = null;

	public function __construct() {

		parent::__construct();

		$this->customer_email = true;
		
		$this->id             = 'customer_order_confirmation';
		$this->title          = __( 'Order Confirmation', 'woocommerce-germanized-pro' );
		$this->description    = __( 'This email will confirm an order to a customer. Will not be sent automatically. Will be sent after confirming the order.', 'woocommerce-germanized-pro' );
		$this->helper         = wc_gzdp_get_email_helper( $this );

        $this->init_settings();

		// Settings
        $this->heading     = $this->get_option( 'heading', $this->heading );
        $this->subject     = $this->get_option( 'subject', $this->subject );
        $this->email_type  = $this->get_option( 'email_type' );
        $this->enabled     = $this->get_option( 'enabled' );

		// Remove default actions
		remove_action( 'woocommerce_order_status_pending_to_processing_notification', array( $this, 'trigger' ), 10 );
		remove_action( 'woocommerce_order_status_pending_to_on-hold_notification', array( $this, 'trigger' ), 10 );
		remove_action( 'woocommerce_order_status_pending_to_processing_notification', array( $this, 'trigger' ), 10 );

        // Save settings hook
        add_action( 'woocommerce_update_options_email_' . $this->id, array( $this, 'process_admin_options' ) );
        // Remove action so that it is not being saved upon saving processing order email template
        remove_action( 'woocommerce_update_options_email_customer_processing_order', array( $this, 'process_admin_options' ), 10 );
	}

	/**
	 * Get email subject.
	 *
	 * @since  3.1.0
	 * @return string
	 */
	public function get_default_subject() {
		return __( 'Confirmation of your order {order_number}', 'woocommerce-germanized-pro' );
	}

	/**
	 * Get email heading.
	 *
	 * @since  3.1.0
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Order confirmed', 'woocommerce-germanized-pro' );
	}

	public function trigger( $order_id, $order = false ) {
		if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
			$order = wc_get_order( $order_id );
		}

		if ( is_a( $order, 'WC_Order' ) ) {
			$this->object = $order;
		}

		$this->helper->setup_email_locale();

		parent::trigger( $order_id, $order );

		$this->helper->restore_email_locale();
	}
}

endif;

return new WC_GZDP_Email_Customer_Order_Confirmation();