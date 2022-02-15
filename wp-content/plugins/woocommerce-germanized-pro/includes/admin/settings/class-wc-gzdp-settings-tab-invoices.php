<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds Germanized Invoice settings.
 *
 * @class 		WC_GZDP_Settings_Tab_Invoices
 * @version		3.0.0
 * @author 		Vendidero
 */
class WC_GZDP_Settings_Tab_Invoices extends WC_GZD_Settings_Tab_Invoices {

	public function get_name() {
		return 'storeabill';
	}

	public function supports_disabling() {
		return true;
	}

	protected function get_enable_option_name() {
		return 'woocommerce_gzdp_invoice_enable';
	}

	public function is_enabled() {
		return 'yes' === get_option( $this->get_enable_option_name() );
	}

	public function get_tab_settings( $current_section = '' ) {
		if ( class_exists( '\Vendidero\StoreaBill\Admin\Settings' ) ) {
			return \Vendidero\StoreaBill\Admin\Settings::get_settings( $current_section );
		}

		return array();
	}

	public function get_sections() {
		if ( class_exists( '\Vendidero\StoreaBill\Admin\Settings' ) ) {
			return \Vendidero\StoreaBill\Admin\Settings::get_sections();
		}

		return array();
	}

	public function get_help_link() {
		if ( class_exists( '\Vendidero\StoreaBill\Admin\Settings' ) ) {
			return \Vendidero\StoreaBill\Admin\Settings::get_help_link();
		}

		return '';
	}

	public function output() {
		if ( class_exists( '\Vendidero\StoreaBill\Admin\Settings' ) ) {
			global $current_section;

			$getter = 'output' . ( ! empty( $current_section ) ? '_' . $current_section : '' );

			if ( is_callable( array( '\Vendidero\StoreaBill\Admin\Settings', $getter ) ) ) {
				\Vendidero\StoreaBill\Admin\Settings::$getter();
			} else {
				parent::output();
			}
		}
	}

	protected function before_save( $settings, $current_section = '' ) {
		parent::after_save( $settings, $current_section );

		if ( class_exists( '\Vendidero\StoreaBill\Admin\Settings' ) ) {
			\Vendidero\StoreaBill\Admin\Settings::before_save( $settings, $current_section);
		}
	}

	protected function after_save( $settings, $current_section = '' ) {
		parent::after_save( $settings, $current_section );

		if ( class_exists( '\Vendidero\StoreaBill\Admin\Settings' ) ) {
			\Vendidero\StoreaBill\Admin\Settings::after_save( $settings, $current_section);
		}
	}

	protected function get_breadcrumb() {
		$breadcrumb  = parent::get_breadcrumb();

		if ( class_exists( '\Vendidero\StoreaBill\Admin\Settings' ) ) {
			$breadcrumb = \Vendidero\StoreaBill\Admin\Settings::filter_breadcrumb( $breadcrumb );
		}

		return $breadcrumb;
	}
}