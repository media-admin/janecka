<?php

namespace Vendidero\StoreaBill\WooCommerce;

use Vendidero\StoreaBill\Admin\Fields;
use Vendidero\StoreaBill\Settings;
use Vendidero\StoreaBill\WooCommerce\Admin\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Shipment Order
 *
 * @class 		WC_GZD_Shipment_Order
 * @version		1.0.0
 * @author 		Vendidero
 */
class SettingsHandler extends \WC_Settings_API {

	/**
	 * The plugin ID. Used for option names.
	 *
	 * @var string
	 */
	public $plugin_id = 'storeabill_';

	/**
	 * @var null|Settings
	 */
	protected $instance = null;

	public function __construct( $instance, $id = '' ) {
		$this->instance = $instance;
		$this->id = $id;
	}

	public function get_option( $key, $empty_value = null ) {
		return $this->instance->get_setting( $key, $empty_value );
	}

	public function get_option_key() {
		return $this->instance->get_setting_key();
	}

	public function get_field_key( $key ) {
		return $this->instance->get_setting_field_key( $key );
	}

	public function validate_sab_toggle_field( $key, $value ) {
		return Fields::sanitize_toggle_input_field( $value );
	}

	/**
	 * Generate Settings HTML.
	 *
	 * Generate the HTML for the fields on the "settings" screen.
	 *
	 * @param array $form_fields (default: array()) Array of form fields.
	 * @param bool  $echo Echo or return.
	 * @return string the html for the settings
	 * @since  1.0.0
	 * @uses   method_exists()
	 */
	public function generate_settings_html( $form_fields = array(), $echo = true ) {
		if ( empty( $form_fields ) ) {
			$form_fields = $this->get_form_fields();
		}

		$html = '';

		foreach ( $form_fields as $k => $v ) {
			$type = $this->get_field_type( $v );

			if ( method_exists( $this->instance, 'generate_' . $type . '_html' ) ) {
				$html .= $this->instance->{'generate_' . $type . '_html'}( $k, $v );
			} elseif ( method_exists( $this, 'generate_' . $type . '_html' ) ) {
				$html .= $this->{'generate_' . $type . '_html'}( $k, $v );
			} else {
				$html .= $this->generate_text_html( $k, $v );
			}
		}

		if ( $echo ) {
			echo $html; // WPCS: XSS ok.
		} else {
			return $html;
		}
	}

	protected function get_field_args( $key, $data ) {
		$defaults = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
		);

		$data = wp_parse_args( $data, $defaults );
		$data = array_merge( $data, array(
			'id'       => $this->get_field_key( $key ),
			'value'    => $this->get_option( $key ),
			'suffix'   => '',
			'desc'     => $data['description']
		) );

		return $data;
	}

	public function generate_sab_toggle_html( $key, $data ) {
		$data = $this->get_field_args( $key, $data );

		ob_start();
		\Vendidero\StoreaBill\WooCommerce\Admin\Fields::toggle_input_field( $data );
		return ob_get_clean();
	}

	public function generate_sab_oauth_connect_html( $key, $data ) {
		$data = $this->get_field_args( $key, $data );

		ob_start();
		\Vendidero\StoreaBill\WooCommerce\Admin\Fields::oauth_connect_field( $data );
		return ob_get_clean();
	}

	public function generate_sab_oauth_connected_html( $key, $data ) {
		$data = $this->get_field_args( $key, $data );

		ob_start();
		\Vendidero\StoreaBill\WooCommerce\Admin\Fields::oauth_connected_field( $data );
		return ob_get_clean();
	}
}