<?php

namespace Vendidero\StoreaBill\API;

use Vendidero\StoreaBill\Package;

defined( 'ABSPATH' ) || exit;

abstract class REST {

	abstract public function get_url();

	protected function get_basic_auth() {
		return false;
	}

	protected function get_timeout( $request_type = 'GET' ) {
		return 'GET' === $request_type ? 30 : 100;
	}

	protected function get_content_type() {
		return 'application/json';
	}

	protected function maybe_encode_body( $body_args ) {
		if ( 'application/json' === $this->get_content_type() ) {
			return json_encode( $body_args, JSON_PRETTY_PRINT );
		}

		return $body_args;
	}

	protected function get_response( $url, $type = 'GET', $body_args = array(), $header = array() ) {
		$response = false;

		if ( 'GET' === $type ) {
			$response = wp_remote_get( $url, array(
				'headers' => $this->get_header( $header ),
				'timeout' => $this->get_timeout( $type )
			) );
		} elseif( 'POST' === $type ) {
			$response = wp_remote_post( $url, array(
				'headers' => $this->get_header( $header ),
				'timeout' => $this->get_timeout( $type ),
				'body'    => $this->maybe_encode_body( $body_args ),
			) );
		} elseif( 'PUT' === $type ) {
			$response = wp_remote_request( $url, array(
				'headers' => $this->get_header( $header ),
				'timeout' => $this->get_timeout( $type ),
				'body'    => $this->maybe_encode_body( $body_args ),
				'method'  => 'PUT'
			) );
		}

		if ( false !== $response ) {

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$code = wp_remote_retrieve_response_code( $response );
			$body = wp_remote_retrieve_body( $response );

			return new RESTResponse( $code, $body, $type );
		}

		return new \WP_Error( 'rest-error', sprintf( _x( 'Error while trying to perform REST request to %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $url ) );
	}

	protected function get_request_url( $endpoint = '', $query_args = array() ) {
		if ( strpos( $endpoint, 'http://' ) === false && strpos( $endpoint, 'https://' ) === false ) {
			$endpoint = trailingslashit( $this->get_url() ) . $endpoint;
		}

		return add_query_arg( $query_args, $endpoint );
	}

	/**
	 * @param string $endpoint
	 * @param array  $query_args
	 *
	 * @return RESTResponse|\WP_Error
	 */
	public function get( $endpoint = '', $query_args = array(), $header = array() ) {
		return $this->get_response( $this->get_request_url( $endpoint, $query_args ), 'GET', array(), $header );
	}

	/**
	 * @param string $endpoint
	 * @param array  $query_args
	 *
	 * @return RESTResponse|\WP_Error
	 */
	public function post( $endpoint = '', $body_args = array(), $header = array() ) {
		return $this->get_response( $this->get_request_url( $endpoint ), 'POST', $body_args, $header );
	}

	/**
	 * @param string $endpoint
	 * @param array  $query_args
	 *
	 * @return RESTResponse|\WP_Error
	 */
	public function put( $endpoint = '', $body_args = array(), $header = array() ) {
		return $this->get_response( $this->get_request_url( $endpoint ), 'PUT', $body_args, $header );
	}

	protected function get_header( $header = array() ) {
		$headers = array();

		$headers['Content-Type'] = $this->get_content_type();
		$headers['Accept']       = 'application/json';

		if ( $this->get_basic_auth() ) {
			$headers['Authorization'] = $this->get_basic_auth();
		}

		$headers['User-Agent'] = 'StoreaBill/' . Package::get_version();

		/**
		 * Optionally replace request headers lazily.
		 */
		$headers = array_replace_recursive( $headers, $header );

		return $headers;
	}
}
