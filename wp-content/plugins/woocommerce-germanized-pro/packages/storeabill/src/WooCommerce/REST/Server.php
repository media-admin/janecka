<?php

namespace Vendidero\StoreaBill\WooCommerce\REST;

defined( 'ABSPATH' ) || exit;

/**
 * Class responsible for loading the REST API and all REST API namespaces.
 */
class Server {

	/**
	 * Hook into WordPress ready to init the REST API as needed.
	 */
	public static function init() {
		add_filter( 'woocommerce_rest_api_get_rest_namespaces', array( __CLASS__, 'register_controllers' ), 10 );
	}

	public static function register_controllers( $controller ) {
		$controller['wc/v3']['order-invoices'] = 'Vendidero\StoreaBill\WooCommerce\REST\Orders';

		return $controller;
	}
}
