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

use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\AuthInfo;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\WordPress\Components\InfrastructureServices\Config_Service;
use CleverReach\WordPress\Components\Repositories\Queue_Repository;
use CleverReach\WordPress\Components\Utility\Helper;
use CleverReach\WordPress\Components\Utility\Task_Queue;

/**
 * Class Clever_Reach_Config_Controller
 *
 * @package CleverReach\WordPress\Controllers
 */
class Clever_Reach_Config_Controller extends Clever_Reach_Base_Controller {

	/**
	 * Configuration service
	 *
	 * @var Config_Service
	 */
	private $config_service;

	/**
	 * Clever_Reach_Config_Controller constructor
	 */
	public function __construct() {
		$this->config_service = ServiceRegister::getService( Configuration::CLASS_NAME );
	}

	/**
	 * Run config
	 */
	public function run() {
		Task_Queue::wakeup();

		if ( $this->is_post() ) {
			$result = $this->update();
		} else {
			$result = $this->get_config_parameters();
		}

		$this->die_json( $result );
	}

	/**
	 * Returns all configuration parameters for diagnostics purposes
	 *
	 * @return array
	 */
	private function get_config_parameters() {
		$queue_repository = new Queue_Repository();
		$all              = $queue_repository->find_all();

		return array(
			'integrationId'                      => $this->config_service->getIntegrationId(),
			'integrationName'                    => $this->config_service->getIntegrationName(),
			'minLogLevel'                        => $this->config_service->getMinLogLevel(),
			'isProductSearchEnabled'             => $this->config_service->isProductSearchEnabled(),
			'productSearchParameters'            => $this->config_service->getProductSearchParameters(),
			'recipientsSynchronizationBatchSize' => $this->config_service->getRecipientsSynchronizationBatchSize(),
			'isDefaultLoggerEnabled'             => $this->config_service->isDefaultLoggerEnabled(),
			'maxStartedTasksLimit'               => $this->config_service->getMaxStartedTasksLimit(),
			'maxTaskExecutionRetries'            => $this->config_service->getMaxTaskExecutionRetries(),
			'maxTaskInactivityPeriod'            => $this->config_service->getMaxTaskInactivityPeriod(),
			'taskRunnerMaxAliveTime'             => $this->config_service->getTaskRunnerMaxAliveTime(),
			'taskRunnerStatus'                   => $this->config_service->getTaskRunnerStatus(),
			'taskRunnerWakeupDelay'              => $this->config_service->getTaskRunnerWakeupDelay(),
			'asyncRequestTimeout'                => $this->config_service->getAsyncProcessRequestTimeout(),
			'queueName'                          => $this->config_service->getQueueName(),
			'webhookUrl'                         => $this->config_service->getCrEventHandlerURL(),
			'logLocation'                        => Helper::get_log_file_download_url(),
			'pluginActive'                       => Helper::is_plugin_enabled(),
			'accessToken'                        => $this->config_service->getAccessToken(),
			'queueItems'                         => wp_json_encode( $all ),
		);
	}

	/**
	 * Updates configuration from POST request
	 *
	 * @return array
	 */
	private function update() {
		$payload = json_decode( $this->get_raw_input(), true );

		if ( array_key_exists( 'minLogLevel', $payload ) ) {
			$this->config_service->saveMinLogLevel( $payload['minLogLevel'] );
		}

		if ( array_key_exists( 'defaultLoggerStatus', $payload ) ) {
			$this->config_service->setDefaultLoggerEnabled( $payload['defaultLoggerStatus'] );
		}

		if ( array_key_exists( 'asyncRequestTimeout', $payload ) ) {
			$this->config_service->setAsyncProcessRequestTimeout( $payload[ 'asyncRequestTimeout' ] );
		}

		if ( array_key_exists( 'maxStartedTasksLimit', $payload ) ) {
			$this->config_service->setMaxStartedTaskLimit( $payload['maxStartedTasksLimit'] );
		}

		if ( array_key_exists( 'taskRunnerWakeUpDelay', $payload ) ) {
			$this->config_service->setTaskRunnerWakeUpDelay( $payload['taskRunnerWakeUpDelay'] );
		}

		if ( array_key_exists( 'taskRunnerMaxAliveTime', $payload ) ) {
			$this->config_service->setTaskRunnerMaxAliveTime( $payload['taskRunnerMaxAliveTime'] );
		}

		if ( array_key_exists( 'maxTaskExecutionRetries', $payload ) ) {
			$this->config_service->setMaxTaskExecutionRetries( $payload['maxTaskExecutionRetries'] );
		}

		if ( array_key_exists( 'maxTaskInactivityPeriod', $payload ) ) {
			$this->config_service->setMaxTaskInactivityPeriod( $payload['maxTaskInactivityPeriod'] );
		}

		if ( array_key_exists( 'productSearchEndpointPassword', $payload ) ) {
			$this->config_service->setProductSearchEndpointPassword( $payload['productSearchEndpointPassword'] );
		}

		if ( array_key_exists( 'recipientsSynchronizationBatchSize', $payload ) ) {
			$this->config_service->setRecipientsSynchronizationBatchSize( (int) $payload['recipientsSynchronizationBatchSize'] );
		}

		if ( array_key_exists( 'asyncProcessRequestTimeout', $payload ) ) {
			$this->config_service->setAsyncProcessRequestTimeout( $payload['asyncProcessRequestTimeout'] );
		}

		if ( array_key_exists( 'sendWakeupSignal', $payload ) ) {
			Task_Queue::wakeup();
		}

		if ( isset( $payload['resetToken'] ) ) {
			$this->reset_token();
		}

		return array( 'message' => 'Successfully updated config values!' );
	}

	/**
	 * Reset the plugin to initial state
	 */
	private function reset_token() {
		$queue_repository = new Queue_Repository();
		$queue_repository->delete( array() );
		$this->config_service->setAuthInfo( new AuthInfo(null, 0, null ) );
		$this->config_service->setUserInfo( null );
		$this->config_service->setIsFirstEmailBuilt( '0' );
		$this->config_service->setImportStatisticsDisplayed( false );
		$this->config_service->setNumberOfSyncedRecipients( 0 );
		$this->config_service->set_default_newsletter_status( '' );
	}
}
