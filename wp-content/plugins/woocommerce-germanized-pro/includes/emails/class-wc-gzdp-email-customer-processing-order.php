<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_GZDP_Email_Customer_Processing_Order' ) ) :

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
class WC_GZDP_Email_Customer_Processing_Order extends WC_Email_Customer_Processing_Order {

	public $helper = null;

	/**
	 * Constructor
	 */
	function __construct() {

        // Call parent constructor
		parent::__construct();

		$this->customer_email   = true;

		$this->id               = 'customer_processing_order';
		$this->title            = __( 'Order Processing', 'woocommerce-germanized-pro' );
		
		$this->template_html 	= 'emails/customer-processing-order-pre.php';
		$this->template_plain 	= 'emails/plain/customer-processing-order-pre.php';
		$this->helper           = wc_gzdp_get_email_helper( $this );

		// Remove default actions
		remove_action( 'woocommerce_order_status_pending_to_processing_notification', array( $this, 'trigger' ), 10 );
		remove_action( 'woocommerce_order_status_pending_to_on-hold_notification', array( $this, 'trigger' ), 10 );
	}

	/**
	 * Get email subject.
	 *
	 * @since  3.1.0
	 * @return string
	 */
	public function get_default_subject() {
		return __( 'Your order {order_number} has been received', 'woocommerce-germanized-pro' );
	}

	/**
	 * Get email heading.
	 *
	 * @since  3.1.0
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Thank you for your order', 'woocommerce-germanized-pro' );
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

return new WC_GZDP_Email_Customer_Processing_Order();
