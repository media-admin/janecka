<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Components\InfrastructureServices;

use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\HttpClient;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\HttpResponse;

/**
 * Class Http_Client_Service
 *
 * @package CleverReach\WordPress\Components\InfrastructureServices
 */
class Http_Client_Service extends HttpClient {

	/**
	 * CURL handler
	 *
	 * @var resource a cURL handle
	 */
	private $curl_session;

	/**
	 * Configuration service.
	 *
	 * @var Config_Service
	 */
	private $config_service;

	/**
	 * Create and send request.
	 *
	 * @param string     $method HTTP method (GET, POST, PUT, DELETE etc.).
	 * @param string     $url Request URL. Full URL where request should be sent.
	 * @param array|null $headers Request headers to send. Key as header name and value as header content.
	 * @param string     $body Request payload. String data to send request payload in JSON format.
	 *
	 * @return HttpResponse Http response object.
	 *
	 * @throws HttpCommunicationException Only in situation when there is no connection, no response, throw this exception.
	 */
	public function sendHttpRequest( $method, $url, $headers = array(), $body = '' ) {
		$this->set_curl_session_and_common_request_parts( $method, $url, $headers, $body );
		$this->set_curl_session_options_for_synchronous_request();

		return $this->execute_and_return_response_for_synchronous_request( $url );
	}

	/**
	 * Create and send request asynchronously.
	 *
	 * @param string     $method HTTP method (GET, POST, PUT, DELETE etc.).
	 * @param string     $url Request URL. Full URL where request should be sent.
	 * @param array|null $headers Request headers to send. Key as header name and value as header content.
	 * @param string     $body Request payload. String data to send request payload in JSON format.
	 *
	 * @return mixed
	 */
	public function sendHttpRequestAsync( $method, $url, $headers = array(), $body = '' ) {
		$this->set_curl_session_and_common_request_parts( $method, $url, $headers, $body );
		$this->set_curl_session_options_for_asynchronous_request();

		return curl_exec( $this->curl_session );
	}

	/**
	 * Sets cURL session and common request parts
	 *
	 * @param string $method Request method.
	 * @param string $url Request URL.
	 * @param array  $headers Array of request headers.
	 * @param string $body Request body.
	 */
	private function set_curl_session_and_common_request_parts( $method, $url, array $headers, $body ) {
		$this->initialize_curl_session();
		$this->set_curl_session_options_based_on_method( $method );
		$this->set_curl_session_url_headers_and_body( $url, $headers, $body );
		$this->set_common_options_for_curl_session();
	}

	/**
	 * Initialize curl session
	 */
	private function initialize_curl_session() {
		$this->curl_session = curl_init();
	}

	/**
	 * Sets cURL session option based on request method
	 *
	 * @param string $method Request method.
	 */
	private function set_curl_session_options_based_on_method( $method ) {
		if ( 'DELETE' === $method ) {
			curl_setopt( $this->curl_session, CURLOPT_CUSTOMREQUEST, 'DELETE' );
		}

		if ( 'POST' === $method ) {
			curl_setopt( $this->curl_session, CURLOPT_CUSTOMREQUEST, 'POST' );
		}

		if ( 'PUT' === $method ) {
			curl_setopt( $this->curl_session, CURLOPT_CUSTOMREQUEST, 'PUT' );
		}
	}

	/**
	 * Sets cURL session URL, headers, and request body.
	 *
	 * @param string $url Request URL.
	 * @param array  $headers Array of request headers.
	 * @param string $body Request body.
	 */
	private function set_curl_session_url_headers_and_body( $url, array $headers, $body ) {
		curl_setopt( $this->curl_session, CURLOPT_URL, $url );
		curl_setopt( $this->curl_session, CURLOPT_HTTPHEADER, $headers );
		if ( !empty($body) ) {
			curl_setopt( $this->curl_session, CURLOPT_POSTFIELDS, $body );
		}
	}

	/**
	 * Set common options for curl session
	 */
	private function set_common_options_for_curl_session() {
		curl_setopt( $this->curl_session, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $this->curl_session, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $this->curl_session, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $this->curl_session, CURLOPT_SSL_VERIFYHOST, false );
		// Set default user agent, because for some shops if user agent is missing, request will not work.
		curl_setopt(
			$this->curl_session,
			CURLOPT_USERAGENT,
			'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.186 Safari/537.36'
		);
	}

	/**
	 * Set curl session options for synchronous request
	 */
	private function set_curl_session_options_for_synchronous_request() {
		curl_setopt( $this->curl_session, CURLOPT_HEADER, true );
	}

	/**
	 * Set curl session options for asynchronous request
	 */
	private function set_curl_session_options_for_asynchronous_request() {
		// Always ensure the connection is fresh.
		curl_setopt( $this->curl_session, CURLOPT_FRESH_CONNECT, true );
		// Timeout super fast once connected, so it goes into async.
		curl_setopt( $this->curl_session, CURLOPT_TIMEOUT_MS, $this->get_config_service()->getAsyncProcessRequestTimeout() );
	}

	/**
	 * Executes and returns response for synchronous request
	 *
	 * @param string $url Request URL.
	 *
	 * @return HttpResponse
	 * @throws HttpCommunicationException HTTP communication exception.
	 */
	private function execute_and_return_response_for_synchronous_request( $url ) {
		$api_response = curl_exec( $this->curl_session );
		$status_code  = curl_getinfo( $this->curl_session, CURLINFO_HTTP_CODE );
		curl_close( $this->curl_session );

		if ( false === $api_response ) {
			throw new HttpCommunicationException( 'Request ' . $url . ' failed.' );
		}

		return new HttpResponse(
			$status_code,
			$this->get_headers_from_curl_response( $api_response ),
			$this->get_body_from_curl_response( $api_response )
		);
	}

	/**
	 * Returns array of headers from cURL response.
	 *
	 * @param string $response Response string.
	 *
	 * @return array
	 */
	private function get_headers_from_curl_response( $response ) {
		$headers                = array();
		$headers_body_delimiter = "\r\n\r\n";
		$header_text            = substr( $response, 0, strpos( $response, $headers_body_delimiter ) );
		$headers_delimiter      = "\r\n";

		foreach ( explode( $headers_delimiter, $header_text ) as $i => $line ) {
			if ( 0 === $i ) {
				$headers[] = $line;
			} else {
				list($key, $value) = explode( ': ', $line );
				$headers[ $key ]   = $value;
			}
		}

		return $headers;
	}

	/**
	 * Returns body from cURL response.
	 *
	 * @param string $response Response string.
	 *
	 * @return string
	 */
	private function get_body_from_curl_response( $response ) {
		$headers_body_delimiter        = "\r\n\r\n";
		$body_starting_position_offset = 4; // Number of special signs in delimiter.
		return substr(
			$response,
			strpos( $response, $headers_body_delimiter ) + $body_starting_position_offset,
			strlen( $response )
		);
	}

	/**
	 * Gets config service
	 *
	 * @return Config_Service
	 */
	private function get_config_service() {
		if ( empty( $this->config_service ) ) {
			$this->config_service = ServiceRegister::getService( Configuration::CLASS_NAME );
		}

		return $this->config_service;
	}
}
