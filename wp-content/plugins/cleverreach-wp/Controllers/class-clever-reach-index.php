<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Controllers;

/**
 * Class CleverReachIndex
 *
 * @package CleverReach\WordPress\Controllers
 */
class Clever_Reach_Index extends Clever_Reach_Base_Controller {

	/**
	 * Index action
	 */
	public function index() {
		$controller_name = $this->get_param( 'cleverreach_wp_controller' );

		if ( ! $this->validate_controller_name( $controller_name ) ) {
			status_header( 404 );
			nocache_headers();

			require get_404_template();

			exit();
		}

		$class_name = '\CleverReach\WordPress\Controllers\Clever_Reach_' . $controller_name . '_Controller';

		/**
		 * Controller instance.
		 *
		 * @var Clever_Reach_Base_Controller $controller
		 */
		$controller = new $class_name();
		$controller->process();
	}

	/**
	 * Validates controller name by checking whether it exists in the list of known controller names.
	 *
	 * @param string $controller_name Controller name from request input.
	 *
	 * @return bool
	 */
	private function validate_controller_name( $controller_name ) {
		if ( ! in_array(
			$controller_name,
			array(
				'Article_Search',
				'Async_Process',
				'Callback',
				'Check_Status',
				'Config',
				'Event_Handler',
				'Frontend',
				'Log',
				'Forms',
				'Integrations',
				'Survey',
			),
			true
		) ) {
			return false;
		}

		return true;
	}
}
