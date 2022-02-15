<?php
/**
 * REST Support for Germanized
 *
 * @author vendidero
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_GZDP_REST_API {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	}

	private function __construct() {
		add_filter( 'woocommerce_gzd_rest_controller', array( $this, 'register' ) );
	}

	public function register( $controller ) {
		include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/api/class-wc-gzdp-rest-customers-controller.php';
		include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/api/class-wc-gzdp-rest-orders-controller.php';

		$controller[] = 'WC_GZDP_REST_Customers_Controller';
		$controller[] = 'WC_GZDP_REST_Orders_Controller';

		return $controller;
	}

}

WC_GZDP_REST_API::instance();