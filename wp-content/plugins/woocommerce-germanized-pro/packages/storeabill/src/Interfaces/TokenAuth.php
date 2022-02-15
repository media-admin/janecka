<?php

namespace Vendidero\StoreaBill\Interfaces;

/**
 * Invoice
 *
 * @package  Germanized/StoreaBill/Interfaces
 * @version  1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TokenAuth
 */
interface TokenAuth extends Auth {

	public function get_token_url();

	public function get_token();
}