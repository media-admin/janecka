<?php

namespace Vendidero\StoreaBill\Lexoffice\API;

use Vendidero\StoreaBill\API\REST;
use Vendidero\StoreaBill\API\RESTResponse;
use Vendidero\StoreaBill\Lexoffice\Package;
use Vendidero\StoreaBill\Lexoffice\Sync;

defined( 'ABSPATH' ) || exit;

class Resources extends REST {

	protected $sync_helper = null;

	public function __construct( $helper ) {
		$this->sync_helper = $helper;
	}

	/**
	 * @return Auth $auth
	 */
	protected function get_auth() {
		return $this->get_sync_helper()->get_auth_api();
	}

	/**
	 * @return Sync|null
	 */
	protected function get_sync_helper() {
		return $this->sync_helper;
	}

	protected function get_basic_auth() {
		return 'Bearer ' . $this->get_auth()->get_access_token();
	}

	public function get_url() {
		return Package::get_api_url();
	}

	public function revoke() {
		$result = $this->get_sync_helper()->parse_response( $this->post( 'revoke' ) );

		if ( ! is_wp_error( $result ) ) {
			return true;
		}

		return $result;
	}

	protected function get_response( $url, $type = 'GET', $body_args = array(), $header = array() ) {
		if ( $this->get_auth()->has_expired() ) {
			$this->get_auth()->refresh();
		}

		return parent::get_response( $url, $type, $body_args, $header );
	}

	public function ping() {
		$result = $this->get_sync_helper()->parse_response( $this->get( 'ping' ) );

		if ( ! is_wp_error( $result ) ) {
			return true;
		}

		return false;
	}

	public function get_voucher_link( $id ) {
		return trailingslashit( Package::get_app_url() ) . 'permalink/vouchers/view/' . $id;
	}

	/**
	 * Only returns false in case the voucher cannot be found (remotely deleted)
	 * to prevent duplicates when the API fails.
	 *
	 * @param $id
	 *
	 * @return false|mixed|RESTResponse|\WP_Error
	 */
	public function get_voucher( $id ) {
		$result = $this->get_sync_helper()->parse_response( $this->get( 'vouchers/' . $id ) );

		if ( ! is_wp_error( $result ) ) {
			return $result->get_body();
		} else {
			return $result;
		}
	}

	/**
	 * @param \WP_Error|boolean|mixed $api_result
	 *
	 * @return bool
	 */
	public function has_failed( $api_result ) {
		return ( is_wp_error( $api_result ) || false === $api_result ) ? true : false;
	}

	/**
	 * Seems like lexoffice API returns a 403 instead of a 404
	 * in case the object exists but is not linked to the current account.
	 *
	 * @param \WP_Error|boolean|mixed $result
	 *
	 * @return bool
	 */
	public function is_404( $result ) {
		$allowed_codes = array( 404, 403 );

		return ( is_wp_error( $result ) && in_array( $result->get_error_code(), $allowed_codes ) );
	}

	public function update_voucher( $id, $data ) {
		return $this->get_sync_helper()->parse_response( $this->put( 'vouchers/' . $id, $data ) );
	}

	public function create_voucher( $data ) {
		return $this->get_sync_helper()->parse_response( $this->post( 'vouchers', $data ) );
	}

	public function create_voucher_transaction_hint( $id, $payment_transaction_id ) {
		return $this->get_sync_helper()->parse_response( $this->post( 'transaction-assignment-hint', array(
			'voucherId'         => $id,
			'externalReference' => $payment_transaction_id,
		) ) );
	}

	public function update_voucher_file( $id, $file ) {
		try {
			$curl_file = new \CURLFile( $file, 'application/pdf' );

			/**
			 * Prevent WP from overriding CURLOPT_POSTFIELDS with string data.
			 *
			 * @param $handle
			 */
			$callback = function( $handle ) use ( $curl_file ) {
				if ( function_exists( 'curl_init' ) && function_exists( 'curl_exec' ) ) {
					curl_setopt( $handle, CURLOPT_POSTFIELDS, array( 'file' => $curl_file, 'type' => 'voucher' ) );
				}
			};

			add_action( 'http_api_curl', $callback, 10, 3 );
			$result = $this->get_sync_helper()->parse_response( $this->post( 'vouchers/' . $id . '/files', array( 'file' => $curl_file ), array( 'Content-Type' => 'multipart/form-data' ) ) );
			remove_action( 'http_api_curl', $callback, 10 );

			return $result;
		} catch( \Exception $e ) {}

		return new \WP_Error( 'api-error', _x( 'Error while uploading file to voucher', 'lexoffice', 'woocommerce-germanized-pro' ) );
	}

	public function get_contact( $id ) {
		$result = $this->get_sync_helper()->parse_response( $this->get( 'contacts/' . $id ) );

		if ( ! is_wp_error( $result ) ) {
			return $result->get_body();
		} else {
			return $result;
		}
	}

	public function search_contacts( $term ) {
		if ( is_numeric( $term ) ) {
			$result = $this->get_sync_helper()->parse_response( $this->get( 'contacts', array(
				'customer' => true,
				'number'   => $term,
			) ) );
		} else {
			$result = $this->get_sync_helper()->parse_response( $this->get( 'contacts', array(
				'customer' => true,
				'name'     => $term,
			) ) );
		}

		if ( ! is_wp_error( $result ) ) {
			return $result->get_body();
		} else {
			return $result;
		}
	}

	public function create_contact( $data ) {
		return $this->get_sync_helper()->parse_response( $this->post( 'contacts', $data ) );
	}

	public function update_contact( $id, $data ) {
		return $this->get_sync_helper()->parse_response( $this->put( 'contacts/' . $id, $data ) );
	}

	public function filter_contacts( $args ) {
		/**
		 * Available filters:
		 * - email
		 * - name (at least 3 chars)
		 * - number (contact customer number)
		 * - customer (true to only find customers)
		 * - vendor (true to only find vendors)
		 */
		$result = $this->get_sync_helper()->parse_response( $this->get( 'contacts', $args ) );

		return $result;
	}
}