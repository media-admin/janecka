<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds Germanized Term Generator settings.
 *
 * @class 		WC_GZDP_Settings_Tab_Terms_Generator
 * @version		3.0.0
 * @author 		Vendidero
 */
class WC_GZDP_Settings_Tab_Terms_Generator extends WC_GZDP_Settings_Tab_Generator {

	public function get_generator_id() {
		return 'agbs';
	}

	public function get_description() {
		return __( 'Easily generate your custom terms & conditions through our API.', 'woocommerce-germanized-pro' );
	}

	public function get_label() {
		return __( 'TOS Generator', 'woocommerce-germanized-pro' );
	}

	public function get_name() {
		return 'terms_generator';
	}

	public function is_pro() {
		return true;
	}
}