<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_GZDP_Checkout_Step_Order extends WC_GZDP_Checkout_Step {

	public function __construct( $id, $title ) {
		parent::__construct( $id, $title, '#order-verify' );
	}

	public function submit() {
		do_action( 'woocommerce_gzdp_checkout_step_refresh', $this );
	}
}