<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Components\InfrastructureServices;

use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\LoggerAdapter;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\Configuration;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\LogData;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\Logger;

/**
 * Class Logger_Service
 *
 * @package CleverReach\WordPress\Components\InfrastructureServices
 */
class Logger_Service implements LoggerAdapter {

	/**
	 * Log message in system
	 *
	 * @param LogData $data Data to be logged.
	 */
	public function logMessage( $data ) {
		/**
		 * Configuration service
		 *
		 * @var Configuration $config_service
		 */
		$config_service = Configuration::getInstance();
		$min_log_level  = $config_service->getMinLogLevel();
		$log_level      = $data->getLogLevel();

		// min log level is actually max log level.
		if ( (int) $log_level > (int) $min_log_level ) {
			return;
		}

		$level = 'info';
		switch ( $log_level ) {
			case Logger::ERROR:
				$level = 'error';
				break;
			case Logger::WARNING:
				$level = 'warning';
				break;
			case Logger::DEBUG:
				$level = 'debug';
				break;
		}

		$folder_name = self::get_log_folder();

		if ( ! file_exists( $folder_name ) ) {
			mkdir( $folder_name, 0777, true );

			$htaccess = fopen( $folder_name . '/.htaccess', 'a+' );
			if ( $htaccess ) {
				fwrite(
					$htaccess,
					'# Disabling log file access from outside
					<FilesMatch .*>
						<IfModule mod_authz_core.c>
							Require all denied
						</IfModule>
						<IfModule !mod_authz_core.c>
							Order allow,deny
							Deny from all
						</IfModule>
					</FilesMatch>
					
					Options -Indexes'
				);
				fclose( $htaccess );
			}
		}

		$log = fopen( $folder_name . '/cleverreach_' . date( 'Y_m_d', time() ) . '.log', 'a+' );
		if ( $log ) {
			fwrite(
				$log,
				"[$level][" . (string) $data->getTimestamp() . '][' .
				$data->getComponent() . '][' . $data->getUserAccount() . '] ' . $data->getMessage() . "\n"
			);
			fclose( $log );
		}
	}

	/**
	 * Get log folder
	 *
	 * @return string
	 */
	public static function get_log_folder() {
		$upload_dir = wp_upload_dir();

		return $upload_dir['basedir'] . '/cleverreach-logs';
	}
}
