<?php

namespace Vendidero\StoreaBill;

use Vendidero\StoreaBill\WooCommerce\SettingsHandler;

defined( 'ABSPATH' ) || exit;

trait Settings {

	protected $settings = array();

	protected $settings_helper = null;

	/**
	 * The posted settings data. When empty, $_POST data will be used.
	 *
	 * @var array
	 */
	protected $setting_data = array();

	abstract public function get_setting_id();

	abstract public function get_settings( $context = 'view' );

	/**
	 * Get the form fields after they are initialized.
	 *
	 * @return array of options
	 */
	public function get_setting_fields( $context = 'view' ) {
		return apply_filters( 'storeabill_settings_' . $this->get_setting_id(), array_map( array( $this, 'set_defaults' ), $this->get_settings( $context ) ) );
	}

	/**
	 * Output the admin options table.
	 */
	public function print_admin_settings() {
		$helper = $this->get_settings_helper();

		echo '<div class="sab-admin-settings wc-gzd-admin-settings"><table class="form-table">' . $helper->generate_settings_html( $this->get_setting_fields( 'edit' ), false ) . '</table></div>'; // WPCS: XSS ok.
	}

	public function get_setting_errors() {
		return $this->get_settings_helper()->get_errors();
	}

	public function add_setting_error( $error ) {
		$this->get_settings_helper()->add_error( $error );
	}

	public function display_setting_errors() {
		if ( $this->get_setting_errors() ) {
			echo '<div id="storeabill-message" class="error notice is-dismissible inline">';
			foreach ( $this->get_setting_errors() as $error ) {
				echo '<p>' . wp_kses_post( $error ) . '</p>';
			}
			echo '</div>';
		}
	}

	/**
	 * Update a single option.
	 *
	 * @since 3.4.0
	 * @param string $key Option key.
	 * @param mixed  $value Value to set.
	 * @return bool was anything saved?
	 */
	public function update_setting( $key, $value = '' ) {
		if ( empty( $this->settings ) ) {
			$this->init_settings();
		}

		$this->settings[ $key ] = $value;

		if ( $this->setting_supports_encryption( $key ) ) {
			$this->settings[ $key ] = apply_filters( 'storeabill_maybe_encrypt_sensitive_data', $this->settings[ $key ], $key );
		}

		return update_option( $this->get_setting_key(), apply_filters( "storeabill_settings_{$this->get_setting_id()}_sanitized_fields", $this->settings ), 'yes' );
	}

	/**
	 * Prefix key for settings.
	 *
	 * @param  string $key Field key.
	 * @return string
	 */
	public function get_setting_field_key( $key ) {
		return $this->get_setting_id() . '_' . $key;
	}

	protected function setting_supports_encryption( $key ) {
		return strstr( $key, 'token' ) !== false || strstr( $key, 'password' ) !== false;
	}

	/**
	 * Get option from DB.
	 *
	 * Gets an option from the settings API, using defaults if necessary to prevent undefined notices.
	 *
	 * @param  string $key Option key.
	 * @param  mixed  $empty_value Value when empty.
	 * @return string The value specified for the option or a default value for the option.
	 */
	public function get_setting( $key, $empty_value = null ) {
		if ( empty( $this->settings ) ) {
			$this->init_settings();
		}

		// Get option default if unset.
		if ( ! isset( $this->settings[ $key ] ) ) {
			$form_fields            = $this->get_setting_fields();
			$this->settings[ $key ] = isset( $form_fields[ $key ] ) ? $this->get_setting_field_default( $form_fields[ $key ] ) : '';
		}

		if ( ! is_null( $empty_value ) && '' === $this->settings[ $key ] ) {
			$this->settings[ $key ] = $empty_value;
		}

		$return_value = $this->settings[ $key ];

		if ( $this->setting_supports_encryption( $key ) ) {
			$return_value = apply_filters( 'storeabill_maybe_decrypt_sensitive_data', $return_value, $key );
		}

		return $return_value;
	}

	/**
	 * Get a fields default value. Defaults to "" if not set.
	 *
	 * @param  array $field Field key.
	 * @return string
	 */
	public function get_setting_field_default( $field ) {
		return empty( $field['default'] ) ? '' : $field['default'];
	}

	/**
	 * Get a fields default value. Defaults to "" if not set.
	 *
	 * @param  array $field Field key.
	 * @return string
	 */
	public function get_setting_field_type( $field ) {
		return empty( $field['type'] ) ? '' : $field['type'];
	}

	/**
	 * Returns the POSTed data, to be used to save the settings.
	 *
	 * @return array
	 */
	protected function get_setting_post_data() {
		if ( ! empty( $this->setting_data ) && is_array( $this->setting_data ) ) {
			return $this->setting_data;
		}

		return $_POST; // WPCS: CSRF ok, input var ok.
	}

	/**
	 * @return SettingsHandler
	 */
	protected function get_settings_helper() {
		if ( is_null( $this->settings_helper ) ) {
			$this->settings_helper = new SettingsHandler( $this, $this->get_setting_id() );
		}

		return $this->settings_helper;
	}

	/**
	 * Processes and saves options.
	 * If there is an error thrown, will continue to save and validate fields, but will leave the erroring field out.
	 *
	 * @return bool was anything saved?
	 */
	public function process_admin_settings() {
		$this->init_settings();

		$post_data = $this->get_setting_post_data();
		$helper    = $this->get_settings_helper();

		foreach ( $this->get_setting_fields() as $key => $field ) {
			if ( 'title' !== $helper->get_field_type( $field ) ) {
				try {
					$this->settings[ $key ] = $helper->get_field_value( $key, $field, $post_data );

					if ( ! empty( $this->settings[ $key ] ) && $this->setting_supports_encryption( $key ) ) {
						$this->settings[ $key ] = apply_filters( 'storeabill_maybe_encrypt_sensitive_data', $this->settings[ $key ], $key );
					}
				} catch ( \Exception $e ) {
					$this->add_setting_error( $e->getMessage() );
				}
			}
		}

		$result = update_option( $this->get_setting_key(), apply_filters( "storeabill_settings_{$this->get_setting_id()}_sanitized_fields", $this->settings ), 'yes' );

		$this->init_settings();
		$this->after_save();

		return $result;
	}

	protected function after_save() {}

	/**
	 * Return the name of the option in the WP DB.
	 *
	 * @since 2.6.0
	 * @return string
	 */
	public function get_setting_key() {
		return 'storeabill_' . $this->get_setting_id() . '_settings';
	}

	protected function init_settings() {
		$this->settings = get_option( $this->get_setting_key(), null );

		// If there are no settings defined, use defaults.
		if ( ! is_array( $this->settings ) ) {
			$form_fields    = $this->get_setting_fields();
			$this->settings = array_merge( array_fill_keys( array_keys( $form_fields ), '' ), wp_list_pluck( $form_fields, 'default' ) );
		}
	}

	/**
	 * Set default required properties for each field.
	 *
	 * @param array $field Setting field array.
	 * @return array
	 */
	protected function set_defaults( $field ) {
		if ( ! isset( $field['default'] ) ) {
			$field['default'] = '';
		}
		return $field;
	}
}
