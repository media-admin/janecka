<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Components\Utility;

use CleverReach\WordPress\Components\InfrastructureServices\Config_Service;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Exposed\TaskRunnerWakeup;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\Logger;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Queue;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\QueueItem;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Task;

/**
 * Class Task_Queue
 *
 * @package CleverReach\WordPress\Components\Utility
 */
class Task_Queue {

	/**
	 * Enqueues a task to the queue
	 *
	 * @param Task $task Task to be queued.
	 * @param bool $throw_exception If exception should be thrown.
	 *
	 * @throws QueueStorageUnavailableException Queue storage unavailable exception.
	 */
	public static function enqueue( Task $task, $throw_exception = false ) {
		try {
			/** @var Config_Service $config_service */
			$config_service = ServiceRegister::getService( Configuration::CLASS_NAME );
			/** @var Queue $queue_service */
			$queue_service = ServiceRegister::getService( Queue::CLASS_NAME );

			$initial_sync_task = $queue_service->findLatestByType( 'Initial_Sync_Task' );
			if ( null !== $initial_sync_task ) {
				$priority = QueueItem::PRIORITY_MEDIUM;
				if ( 'RefreshUserInfoTask' === $task->getType() ) {
					$priority = QueueItem::PRIORITY_HIGH;
				}

				$queue_service->enqueue( $config_service->getQueueName(), $task, '',  $priority );
			}
		} catch ( QueueStorageUnavailableException $ex ) {
			Logger::logDebug(
				wp_json_encode(
					array(
						'Message'          => 'Failed to enqueue task ' . $task->getType(),
						'ExceptionMessage' => $ex->getMessage(),
						'ExceptionTrace'   => $ex->getTraceAsString(),
						'TaskData'         => serialize( $task ),
					)
				),
				'Integration'
			);

			if ( $throw_exception ) {
				throw $ex;
			}
		}
	}

	/**
	 * Calls the wakeup on task runner
	 */
	public static function wakeup() {
		if ( Helper::is_curl_enabled() && Helper::is_plugin_enabled() ) {
			$wakeup_service = ServiceRegister::getService( TaskRunnerWakeup::CLASS_NAME );
			$wakeup_service->wakeup();
		}
	}
}
