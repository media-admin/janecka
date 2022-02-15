<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\Logger;
use CleverReach\WordPress\Components\Repositories\Process_Repository;
use CleverReach\WordPress\Components\Utility\Helper;

/**
 * Class Clever_Reach_Async_Process_Controller
 *
 * @package CleverReach\WordPress\Controllers
 */
class Clever_Reach_Async_Process_Controller extends Clever_Reach_Base_Controller {

	/**
	 * Clever_Reach_Async_Process_Controller constructor
	 */
	public function __construct() {
		$this->is_internal = false;
	}

	/**
	 * Runs process defined by guid request parameter
	 */
	public function run() {
		if ( ! Helper::is_plugin_enabled() ) {
			exit();
		}

		if ( ! $this->is_post() ) {
			$this->redirect_404();
		}

		$guid               = $this->get_param( 'guid' );
		$process_repository = new Process_Repository();

		try {
			$runner = $process_repository->get_runner( $guid );
			$runner->run();
			$process_repository->delete_process( $guid );
		} catch ( \Exception $e ) {
			Logger::logError( $e->getMessage(), 'Integration' );
		}

		exit();
	}
}
