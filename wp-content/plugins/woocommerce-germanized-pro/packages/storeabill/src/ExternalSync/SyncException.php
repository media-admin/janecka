<?php

namespace Vendidero\StoreaBill\ExternalSync;

defined( 'ABSPATH' ) || exit;

class SyncException extends \Exception {

	/** @var string sanitized error code */
	protected $error_code;

	/**
	 * Setup exception, requires 3 params:
	 *
	 * error code - machine-readable, e.g. `storeabill_invalid_document`
	 * error message - friendly message, e.g. 'Document is invalid'
	 * http status code - proper HTTP status code to respond with, e.g. 400
	 *
	 * @since 2.2
	 * @param string $error_code
	 * @param string $error_message user-friendly translated error message
	 * @param int $http_status_code HTTP status code to respond with
	 */
	public function __construct( $error_code, $error_message ) {
		$this->error_code = $error_code;

		parent::__construct( $error_message, 500 );
	}

	/**
	 * Returns the error code
	 *
	 * @since 2.2
	 * @return string
	 */
	public function getErrorCode() {
		return $this->error_code;
	}
}
