<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Components\InfrastructureServices;

use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\AsyncProcessStarter;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\HttpClient;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Exposed\Runnable;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\Logger;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\ProcessStarterSaveException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\GuidProvider;
use CleverReach\WordPress\Components\Repositories\Process_Repository;
use CleverReach\WordPress\Components\Utility\Helper;

/**
 * Class Async_Process_Starter_Service
 *
 * @package CleverReach\WordPress\Components\InfrastructureServices
 */
class Async_Process_Starter_Service implements AsyncProcessStarter {

	/**
	 * HTTP client service
	 *
	 * @var HttpClient
	 */
	private $http_client_service;

	/**
	 * Starts async process
	 *
	 * @param Runnable $runner Task runner.
	 *
	 * @throws HttpRequestException HTTP request exception.
	 * @throws ProcessStarterSaveException Process starter exception.
	 */
	public function start( Runnable $runner ) {
		$guid_provider = new GuidProvider();
		$guid          = trim( $guid_provider->generateGuid() );

		$this->save_guid_and_runner( $guid, $runner );
		$this->start_runner_asynchronously( $guid );
	}

	/**
	 * Saves runner and guid to storage
	 *
	 * @param string   $guid Globally Unique Identifier.
	 * @param Runnable $runner Task runner.
	 *
	 * @throws ProcessStarterSaveException Process starter exception.
	 */
	private function save_guid_and_runner( $guid, Runnable $runner ) {
		try {
			$process_repository = new Process_Repository();
			$process_repository->save( $guid, $runner );
		} catch ( \Exception $e ) {
			Logger::logError( $e->getMessage(), 'Integration' );
			throw new ProcessStarterSaveException( $e->getMessage(), 0, $e );
		}
	}

	/**
	 * Starts runnable asynchronously
	 *
	 * @param string $guid Globally Unique Identifier.
	 *
	 * @throws HttpRequestException HTTP request exception.
	 */
	private function start_runner_asynchronously( $guid ) {
		try {
			$this->get_http_client()->requestAsync( 'POST', $this->format_url( $guid ), array( 'Content-Length: 0' ) );
		} catch ( \Exception $e ) {
			Logger::logError( $e->getMessage(), 'Integration' );
			throw new HttpRequestException( $e->getMessage(), 0, $e );
		}
	}

	/**
	 * Returns formatted url for async request
	 *
	 * @param string $guid Globally Unique Identifier.
	 *
	 * @return string
	 */
	private function format_url( $guid ) {
		$url = Helper::get_controller_url(
			'Async_Process',
			array(
				'action' => 'run',
				'guid'   => $guid,
			)
		);

		return $url;
	}

	/**
	 * Gets http client service
	 *
	 * @return HttpClient
	 */
	private function get_http_client() {
		if ( empty( $this->http_client_service ) ) {
			$this->http_client_service = ServiceRegister::getService( HttpClient::CLASS_NAME );
		}

		return $this->http_client_service;
	}
}
