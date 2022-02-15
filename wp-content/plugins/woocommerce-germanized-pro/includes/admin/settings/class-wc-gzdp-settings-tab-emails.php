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
class WC_GZDP_Settings_Tab_Emails extends WC_GZD_Settings_Tab_Emails {

	protected function get_attachment_settings() {
		$settings = array();

		/**
		 * Make sure that StoreaBill has been loaded before calling (e.g. during Woo updates).
		 */
		if ( did_action( 'storeabill_registered_core_document_types' ) ) {
			$settings = \Vendidero\Germanized\Pro\StoreaBill\LegalPages::get_settings();
		}

		return $settings;
	}

	protected function get_attachment_pdf_settings() {
		return array();
	}

	protected function after_save( $settings, $current_section = '' ) {
		if ( 'attachments' === $current_section ) {
			\Vendidero\Germanized\Pro\StoreaBill\LegalPages::on_save_settings();
		}

		parent::after_save( $settings, $current_section );
	}
}