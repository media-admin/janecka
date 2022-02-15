<?php

namespace Vendidero\StoreaBill\API;

defined( 'ABSPATH' ) || exit;

class RESTResponse {

	protected $body = '';

	protected $code = '';

	protected $type = 'GET';

	public function __construct( $code, $body, $type = 'GET' ) {
		$this->code = absint( $code );
		$this->body = $body;
		$this->type = $type;
	}

	public function get_body_raw() {
		return $this->body;
	}

	public function get_body() {
		return json_decode( $this->get_body_raw(), true );
	}

	public function get( $prop ) {
		$body = $this->get_body();

		return isset( $body[ $prop ] ) ? $body[ $prop ] : null;
	}

	public function get_code() {
		return $this->code;
	}

	public function get_type() {
		return $this->type;
	}

	public function is_error() {
		return $this->get_code() >= 300;
	}
}