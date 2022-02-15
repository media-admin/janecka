<?php

namespace Vendidero\StoreaBill\ExternalSync;

use Vendidero\StoreaBill\Document\Document;
use Vendidero\StoreaBill\Interfaces\ExternalSync;
use Vendidero\StoreaBill\Invoice\Invoice;
use Vendidero\StoreaBill\Settings;

defined( 'ABSPATH' ) || exit;

abstract class SyncHandler implements ExternalSync {
	use Settings;

	public function __construct() {

		// Init settings.
		$this->init_settings();
	}

	protected function get_hook_prefix() {
		$name = static::get_name();

		return "storeabill_external_sync_{$name}_";
	}

	abstract public static function get_name();

	public static function get_admin_url() {
		return add_query_arg( array( 'sync_handler_name' => static::get_name(), 'section' => 'sync_handlers' ), \Vendidero\StoreaBill\Admin\Settings::get_admin_url() );
	}

	public static function is_object_type_supported( $type ) {
		return in_array( $type, static::get_supported_object_types() ) ? true : false;
	}

	public static function get_help_link() {
		return false;
	}

	public function get_setting_id() {
		return 'sync_handler_' . static::get_name();
	}

	/**
	 * @param Invoice|array $invoice
	 */
	protected function get_invoice_remark( $invoice, $seperator = ' - ' ) {
		if ( ! is_array( $invoice ) ) {
			$remark_data = $this->get_invoice_remark_data( $invoice );
		} else {
			$remark_data = $invoice;
		}

		return implode( $seperator, $remark_data );
	}

	/**
	 * @param Invoice $invoice
	 */
	protected function get_invoice_remark_data( $invoice ) {
		$remark_data = array();

		$remark_data[] = sprintf( _x( 'Invoice number %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $invoice->get_formatted_number() );

		if ( $invoice->get_order_number() ) {
			$remark_data[] = sprintf( _x( 'Order %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $invoice->get_order_number() );
		}

		if ( 'cancellation' === $invoice->get_invoice_type() ) {
			$remark_data[] = sprintf( _x( 'Cancellation to %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $invoice->get_parent_formatted_number() );

			if ( $invoice->has_refund_order() ) {
				$remark_data[] = sprintf( _x( 'Refund %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $invoice->get_refund_order_number() );
			}
		}

		if ( $invoice->get_payment_method_title() ) {
			if ( $invoice->is_paid() ) {
				$remark_data[] = sprintf( _x( 'Paid via %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $invoice->get_payment_method_title() );
			} else {
				$remark_data[] = sprintf( _x( 'Awaiting payment via %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $invoice->get_payment_method_title() );
			}
		}

		if ( $invoice->get_payment_transaction_id() ) {
			$remark_data[] = sprintf( _x( 'Payment transaction id %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $invoice->get_payment_transaction_id() );
		}

		return $remark_data;
	}

	public function get_settings( $context = 'view' ) {
		$auth = $this->get_auth_api();

		$settings = array(
			'enabled' => array(
				'title'   => _x( 'Enable', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'    => 'sab_toggle',
				'label'   => sprintf( _x( 'Enable syncing with %s.', 'storeabill-core', 'woocommerce-germanized-pro' ), static::get_title() ),
				'default' => 'no',
			),
		);

		/**
		 * This is an OAuth API
		 */
		if ( $this->has_oauth() ) {
			if ( 'edit' === $context ) {
				if ( ! $auth->is_connected() ) {
					$settings['auth'] = array(
						'title'                    => _x( 'Connect', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'                     => 'sab_oauth_connect',
						'url'                      => $auth->get_authorization_url(),
						'is_manual'                => $auth->is_manual_authorization(),
						'authorization_input_name' => $this->get_setting_field_key( 'authorization_code' ),
						'description'              => $auth->get_description(),
						'handler_label'            => static::get_title()
					);
				} else {
					$settings['auth'] = array(
						'title'                    => _x( 'Refresh', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'                     => 'sab_oauth_connected',
						'description'              => sprintf( _x( 'To refresh your connection with %s, click on the refresh button and reauthorize StoreaBill. After that you will be redirected to a page showing an authorization code. Please copy & paste that code into the field below.', 'storeabill-core', 'woocommerce-germanized-pro' ), static::get_title() ),
						'input_name'               => $this->get_setting_field_key( 'disconnect' ),
						'refresh_url'              => $auth->get_authorization_url(),
						'is_manual'                => $auth->is_manual_authorization(),
						'authorization_input_name' => $this->get_setting_field_key( 'authorization_code' ),
						'expires_on'               => $auth->get_refresh_code_expires_on() ? $auth->get_refresh_code_expires_on()->getTimestamp() : '',
					);
				}
			}
		} elseif( 'token' === $auth->get_type() ) {
			$settings['token'] = array(
				'title'       => _x( 'Token', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'password',
				'description' => '<a href="' . esc_url( $auth->get_token_url() ) . '" target="_blank" class="button button-secondary sab-auth-token-url">' . _x( 'Retrieve token', 'storeabill-core', 'woocommerce-germanized-pro' ) . '</a><div class="sab-additional-desc">' . $auth->get_description() . '</div>',
				'desc_tip'    => false,
				'default'     => ''
			);
		}

		return $settings;
	}

	protected function has_oauth() {
		return ( $auth = $this->get_auth_api() ) && is_a( $auth, '\Vendidero\StoreaBill\Interfaces\OAuth' );
	}

	protected function after_save() {
		if ( $this->has_oauth() && ( $auth = $this->get_auth_api() ) ) {
			$connect_key    = $this->get_setting_field_key( 'authorization_code' );
			$disconnect_key = $this->get_setting_field_key( 'disconnect' );
			$is_disabled    = ! $this->is_enabled();

			if ( $auth->is_manual_authorization() && isset( $_POST[ $connect_key ] ) && ! empty( $_POST[ $connect_key ] ) ) {
				$key    = sab_clean( wp_unslash( $_POST[ $connect_key ] ) );
				$result = $auth->auth( $key );

				if ( is_wp_error( $result ) ) {
					foreach( $result->get_error_messages() as $message ) {
						$this->add_setting_error( $message );
					}
				}
			}

			if ( $auth->is_connected() && ( isset( $_POST[ $disconnect_key ] ) && 'yes' === $_POST[ $disconnect_key ] ) || $is_disabled ) {
				$result = $auth->disconnect();

				if ( is_wp_error( $result ) ) {
					foreach( $result->get_error_messages() as $message ) {
						$this->add_setting_error( $message );
					}
				}
			}
		}
	}

	public function is_enabled() {
		return sab_string_to_bool( $this->get_setting( 'enabled' ) );
	}

	public function is_syncable( $object ) {
		if ( ! in_array( $object->get_type(), static::get_supported_object_types() ) ) {
			return false;
		}

		if ( is_a( $object, '\Vendidero\StoreaBill\Document\Document' ) ) {
			/**
			 * Exclude certain document version from sync.
			 */
			$unsupported_versions = array(
				'0.0.1-legacy-incomplete-placeholder',
			);

			if ( in_array( $object->get_meta( '_legacy_version' ), $unsupported_versions ) ) {
				return false;
			}
		}

		if ( is_a( $object, '\Vendidero\StoreaBill\Invoice\Invoice' ) && ! $object->is_finalized() ) {
			return false;
		}

		return true;
	}
}