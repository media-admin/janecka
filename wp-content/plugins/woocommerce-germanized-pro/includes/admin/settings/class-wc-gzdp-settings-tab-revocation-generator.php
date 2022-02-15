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
class WC_GZDP_Settings_Tab_Revocation_Generator extends WC_GZDP_Settings_Tab_Generator {

	public function get_generator_id() {
		return 'widerruf';
	}

	public function get_description() {
		return __( 'Easily generate your custom cancellation policy through our API.', 'woocommerce-germanized-pro' );
	}

	public function get_label() {
		return __( 'Cancellation Policy Generator', 'woocommerce-germanized-pro' );
	}

	public function get_name() {
		return 'revocation_generator';
	}

	public function is_pro() {
		return true;
	}
}