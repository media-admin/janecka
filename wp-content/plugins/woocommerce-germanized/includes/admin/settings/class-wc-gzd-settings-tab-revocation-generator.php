<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds Germanized Tax settings.
 *
 * @class        WC_GZD_Settings_Tab_Taxes
 * @version        3.0.0
 * @author        Vendidero
 */
class WC_GZD_Settings_Tab_Revocation_Generator extends WC_GZD_Settings_Tab {

	public function get_description() {
		return __( 'Easily generate your revocation terms through our API.', 'woocommerce-germanized' );
	}

	public function get_label() {
		return __( 'Revocation Terms Generator', 'woocommerce-germanized' ) . ' <span class="wc-gzd-pro wc-gzd-pro-outlined">' . __( 'pro', 'woocommerce-germanized' ) . '</span>';
	}

	public function get_name() {
		return 'revocation_generator';
	}

	public function is_pro() {
		return true;
	}

	public function get_tab_settings( $current_section = '' ) {
		return array(
			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'revocation_generator_options',
				'desc'  => '',
			),

			array(
				'title' => '',
				'id'    => 'woocommerce_gzdp_terms_generator',
				'img'   => WC_Germanized()->plugin_url() . '/assets/images/pro/settings-widerruf.png?v=' . WC_germanized()->version,
				'href'  => 'https://vendidero.de/woocommerce-germanized/features#accounting',
				'type'  => 'image',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'revocation_generator_options',
			),
		);
	}

	public function is_enabled() {
		return false;
	}
}
