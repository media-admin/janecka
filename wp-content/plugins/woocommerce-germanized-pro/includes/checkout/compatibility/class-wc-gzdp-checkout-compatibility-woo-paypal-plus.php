<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_GZDP_Checkout_Compatibility_Woo_Paypal_Plus {

	public function __construct() {
		$this->reorder_payment_methods();
	}

	private function reorder_payment_methods() {

		$gateways = WC()->payment_gateways()->get_available_payment_gateways();
		
		if ( isset( $gateways[ 'paypal_plus' ] ) ) {
		
			$gateway = $gateways[ 'paypal_plus' ];
		
			if ( $gateway->is_available() ) {
				add_action( 'woocommerce_review_order_before_payment', array( $this, 'open_payment_manual_wrapper' ), 0 );
				// -5 is being used by order verify data to make sure it is loaded before checkbox
				add_action( 'woocommerce_review_order_after_payment', array( $this, 'close_payment_manual_wrapper' ), ( wc_gzd_get_hook_priority( 'checkout_legal' ) - 6 ) );
			}

		}

	}

	public function open_payment_manual_wrapper() {  ?>
		<div id="payment-manual">
			<style type="text/css">
				#ppplus iframe { width: 100% !important };
			</style>
		<?php 
	}

	public function close_payment_manual_wrapper() { ?>
		</div>
		<?php
	}

}