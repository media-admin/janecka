<?php

namespace Vendidero\StoreaBill\Interfaces;

/**
 * Invoice
 *
 * @package  StoreaBill/Interfaces
 * @version  1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * OAuth
 */
interface OAuth extends Auth {

	public function get_expires_on();

	public function has_expired();

	public function get_refresh_code_expires_on();

	public function refresh_code_has_expired();

	public function auth( $authorization_code = '' );

	public function disconnect();

	public function get_authorization_url();

	public function is_manual_authorization();

	public function get_access_token();

	public function get_refresh_token();

	public function refresh();

	public function is_connected();
}
