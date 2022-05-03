<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_GZDP_Theme_Twentytwentytwo extends WC_GZDP_Theme {

	public function __construct( $template ) {
		parent::__construct( $template );

		add_action( 'woocommerce_gzdp_frontend_styles', array( $this, 'add_inline_styles_checkout' ), 100 );
	}

	/**
	 * Adds woocommerce checkout table background highlight color as inline css
	 */
	public function add_inline_styles_checkout() {
		if ( $this->has_multistep_checkout() ) {
			$custom_css = '
				.woocommerce-multistep-checkout .woocommerce-checkout .woocommerce-gzdp-checkout-verify-data .col2-set {
					width: 100%;
					float: none;
					clear: both;
				} 
				.woocommerce-multistep-checkout .woocommerce-checkout .woocommerce-gzdp-checkout-verify-data .col2-set h4 {
					margin-top: 0;
				}
			';

			wp_add_inline_style( 'wc-gzdp-checkout', $custom_css );
		}
	}

	protected function has_multistep_checkout() {
		return 'yes' === get_option( 'woocommerce_gzdp_checkout_enable' );
	}

	public function custom_hooks() {
		if ( $this->has_multistep_checkout() ) {
			remove_action( 'woocommerce_checkout_before_order_review_heading', array( 'WC_Twenty_Twenty_Two', 'before_order_review' ) );
			remove_action( 'woocommerce_checkout_after_order_review', array( 'WC_Twenty_Twenty_Two', 'after_order_review' ) );
		}
	}
}