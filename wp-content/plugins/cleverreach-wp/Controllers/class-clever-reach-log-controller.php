<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Controllers;

use CleverReach\WordPress\Components\InfrastructureServices\Logger_Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Clever_Reach_Log_Controller
 *
 * @package CleverReach\WordPress\Controllers
 */
class Clever_Reach_Log_Controller extends Clever_Reach_Base_Controller {

	/**
	 * Returns log file
	 */
	public function run() {
		$log_date = $this->get_param( 'logDate' ) ?: date( 'Y_m_d' );
		$file     = Logger_Service::get_log_folder() . '/cleverreach_' . $log_date . '.log';

		if ( file_exists( $file ) ) {
			$this->return_file( $file );
		}
	}
}
