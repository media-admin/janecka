<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds Germanized Multistep Checkout settings.
 *
 * @class 		WC_GZDP_Settings_Tab_Multistep_Checkout
 * @version		3.0.0
 * @author 		Vendidero
 */
class WC_GZDP_Settings_Tab_Multistep_Checkout extends WC_GZD_Settings_Tab_Multistep_Checkout {

	public function get_tab_settings( $current_section = '' ) {
		$helper   = WC_GZDP_Multistep_Checkout::instance();
		$settings = $helper->get_settings();

		return $settings;
	}

	public function supports_disabling() {
		return true;
	}

	protected function get_enable_option_name() {
		return 'woocommerce_gzdp_checkout_enable';
	}

	public function is_enabled() {
		return 'yes' === get_option( $this->get_enable_option_name() );
	}
}