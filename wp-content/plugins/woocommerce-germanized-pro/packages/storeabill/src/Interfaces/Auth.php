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
 * Auth
 */
interface Auth {

	public function get_type();

	public function get_description();

	/**
	 * @return boolean
	 */
	public function ping();
}