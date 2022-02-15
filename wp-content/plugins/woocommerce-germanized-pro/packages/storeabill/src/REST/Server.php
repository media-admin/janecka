<?php
/**
 * Initialize this version of the REST API.
 */
namespace Vendidero\StoreaBill\REST;

defined( 'ABSPATH' ) || exit;

use Vendidero\StoreaBill\Package;
use Vendidero\StoreaBill\Utilities\Singleton;

/**
 * Class responsible for loading the REST API and all REST API namespaces.
 */
class Server {
	use Singleton;

	/**
	 * REST API namespaces and endpoints.
	 *
	 * @var array
	 */
	protected $controllers = [];

	/**
	 * Hook into WordPress ready to init the REST API as needed.
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ), 10 );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes() {
		foreach ( $this->get_rest_namespaces() as $namespace => $controllers ) {
			foreach ( $controllers as $controller_name => $controller_class ) {
				$this->controllers[ $namespace ][ $controller_name ] = new $controller_class();
				$this->controllers[ $namespace ][ $controller_name ]->register_routes();
			}
		}
	}

	/**
	 * Get API namespaces - new namespaces should be registered here.
	 *
	 * @return array List of Namespaces and Main controller classes.
	 */
	protected function get_rest_namespaces() {
		return apply_filters(
			'storeabill_rest_api_get_rest_namespaces',
			[
				'sab/v1' => $this->get_v1_controllers(),
			]
		);
	}

	/**
	 * List of controllers in the sab/v1 namespace.
	 *
	 * @return array
	 */
	protected function get_v1_controllers() {
		$controllers = [
			'invoices'           => '\Vendidero\StoreaBill\REST\InvoiceController',
			'cancellations'      => '\Vendidero\StoreaBill\REST\CancellationController',
			'preview_fonts'      => '\Vendidero\StoreaBill\REST\PreviewFontsController',
			'preview_shortcodes' => '\Vendidero\StoreaBill\REST\PreviewShortcodesController',
		];

		if ( ! Package::enable_accounting() ) {
			$controllers = array_diff_key( $controllers, array_flip( array( 'invoices', 'cancellations' ) ) );
		}

		return $controllers;
	}

	public function get_controller( $controller_name, $namespace = 'sab/v1' ) {
		$controllers = $this->controllers;

		/**
		 * Seems like API has not been initiated
		 */
		if ( empty( $controllers ) ) {
			$controllers = $this->get_rest_namespaces();
		}

		if ( isset( $controllers[ $namespace ][ $controller_name ] ) ) {
			$result = $controllers[ $namespace ][ $controller_name ];

			if ( ! is_object( $result ) && class_exists( $result ) ) {
				$result = new $result();
			}

			return $result;
		}

		return false;
	}
}
