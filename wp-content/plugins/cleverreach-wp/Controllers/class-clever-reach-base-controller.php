<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Controllers;

/**
 * Class Clever_Reach_Base_Controller
 *
 * @package CleverReach\WordPress\Controllers
 */
class Clever_Reach_Base_Controller {

	/**
	 * Is request internal
	 *
	 * @var bool
	 */
	protected $is_internal = true;

	/**
	 * Processes request. Reads 'action' parameter and calls action method if provided
	 *
	 * @param string $action Action to be called.
	 */
	public function process( $action = '' ) {
		if ( $this->is_internal ) {
			$this->validate_internal_call();
		}

		if ( empty( $action ) ) {
			$action = $this->get_param( 'action' );
		}

		if ( $action ) {
			if ( method_exists( $this, $action ) ) {
				$this->$action();
			} else {
				$this->die_json( array( 'error' => "Method $action does not exist!" ) );
			}
		}
	}

	/**
	 * Validates if call made from plugin code is secure by checking session token
	 * If call is not secure, returns 401 status and terminates request.
	 */
	protected function validate_internal_call() {
		$logged_user_id = get_current_user_id();
		if ( empty( $logged_user_id ) ) {
			auth_redirect();
		}
	}

	/**
	 * Sets response header content type to json, echos supplied $data as a json string and terminates request
	 *
	 * @param array $data Array to be returned as a json response.
	 */
	protected function die_json( array $data ) {
		wp_send_json( $data );
	}

	/**
	 * Checks whether current request is POST
	 *
	 * @return bool
	 */
	protected function is_post() {
		return isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'];
	}

	/**
	 * Gets request parameter if exists. Otherwise, returns null
	 *
	 * @param string $key Key to be checked in request.
	 *
	 * @return mixed
	 */
	protected function get_param( $key ) {
		return isset( $_REQUEST[ $key ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ $key ] ) ) : null;
	}

	/**
	 * Gets raw request
	 *
	 * @return string
	 */
	protected function get_raw_input() {
		return file_get_contents( 'php://input' );
	}

	/**
	 * Returns 404 response and terminates request
	 */
	protected function redirect_404() {
		status_header( 404 );
		nocache_headers();

		require get_404_template();

		exit();
	}

	/**
	 * Dies with defined status code
	 *
	 * @param int $status response status code.
	 */
	protected function die_with_status( $status ) {
		status_header( $status );
		die();
	}

	/**
	 * Downloads the file
	 *
	 * @param string $file File path.
	 */
	protected function return_file( $file ) {
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . basename( $file ) . '"' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . filesize( $file ) );
		readfile( $file );

		exit();
	}
}
