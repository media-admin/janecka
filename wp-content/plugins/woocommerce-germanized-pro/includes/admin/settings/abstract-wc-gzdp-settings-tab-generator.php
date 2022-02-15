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
abstract class WC_GZDP_Settings_Tab_Generator extends WC_GZD_Settings_Tab {

	abstract public function get_generator_id();

	public function get_tab_settings( $current_section = '' ) {
		return array();
	}

	public function is_enabled() {
		return true;
	}

	public function output() {
		$generator = WC_GZDP_Admin_Generator::instance();
		$generator->output( $this->get_generator_id() );
	}

	public function save() {
		global $current_section;
		$generator = WC_GZDP_Admin_Generator::instance();

		if ( isset( $_POST['generator_page_id'] ) && $generator->get_html( $this->get_generator_id() ) ) {
			$generator->save_to_page();
		} else {
			$settings = $generator->get_settings( $this->get_generator_id() );

			if ( ! empty( $settings ) ) {
				$this->before_save( $settings, $current_section );

				WC_Admin_Settings::save_fields( $settings );
				$generator->save( $settings );

				$this->after_save( $settings, $current_section );
			}
		}
	}
}