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

use CleverReach\WordPress\Components\InfrastructureServices\Config_Service;
use CleverReach\WordPress\Components\Sync\Initial_Sync_Task;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Queue;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\QueueItem;

/**
 * Class Clever_Reach_Check_Status_Controller
 *
 * @package CleverReach\WordPress\Controllers
 */
class Clever_Reach_Check_Status_Controller extends Clever_Reach_Base_Controller {

	/**
	 * Queue service
	 *
	 * @var Queue
	 */
	private $queue;

	/**
	 * Gets status of refreshing user info task
	 */
	public function refresh_user_info() {
		$status     = 'finished';
		$queue_item = $this->get_queue_service()->findLatestByType( 'RefreshUserInfoTask' );

		if ( isset( $queue_item ) ) {
			$queue_status = $queue_item->getStatus();
			if ( QueueItem::FAILED !== $queue_status && QueueItem::COMPLETED !== $queue_status ) {
				$status = 'in_progress';
			}
		}

		$this->die_json( array( 'status' => $status ) );
	}

	/**
	 * Gets status of initial sync task
	 *
	 * @throws QueueItemDeserializationException Queue item deserialization exception.
	 */
	public function initial_sync() {
		$sync_task_queue_item = $this->get_queue_service()->findLatestByType( 'Initial_Sync_Task' );
		if ( empty( $sync_task_queue_item ) ) {
			$this->die_json( array( 'status' => QueueItem::FAILED ) );
		}

		/**
		 * Initial sync task
		 *
		 * @var Initial_Sync_Task $sync_task
		 */
		$sync_task     = $sync_task_queue_item->getTask();
		$sync_progress = $sync_task->getProgressByTask();

		$statuses = array(
			'status'       => $sync_task_queue_item->getStatus(),
			'taskStatuses' => array(
				'subscriber_list' => array(
					'status'   => $this->get_status( $sync_progress[ 'subscriberList' ] ),
					'progress' => $sync_progress[ 'subscriberList' ],
				),
			),
		);

		/** @var Config_Service $config_service */
		$config_service = ServiceRegister::getService( Configuration::CLASS_NAME );
		if ( $config_service->is_recipient_sync_enabled() ) {
			$statuses [ 'taskStatuses' ][ 'add_fields' ] = array(
				'status'   => $this->get_status( $sync_progress[ 'fields' ] ),
				'progress' => $sync_progress[ 'fields' ],
			);
			$statuses [ 'taskStatuses' ][ 'recipient_sync' ] = array(
				'status'   => $this->get_status( $sync_progress[ 'recipients' ] ),
				'progress' => $sync_progress[ 'recipients' ],
			);
		}

		$this->die_json( $statuses );
	}

	/**
	 * Gets task status based on progress
	 *
	 * @param float $progress Task progress.
	 *
	 * @return string
	 */
	private function get_status( $progress ) {
		$status = QueueItem::QUEUED;
		if ( 0 < $progress && $progress < 100 ) {
			$status = QueueItem::IN_PROGRESS;
		} elseif ( $progress >= 100 ) {
			$status = QueueItem::COMPLETED;
		}

		return $status;
	}

	/**
	 * Gets queue service
	 *
	 * @return Queue
	 */
	private function get_queue_service() {
		if ( empty( $this->queue ) ) {
			$this->queue = ServiceRegister::getService( Queue::CLASS_NAME );
		}

		return $this->queue;
	}
}
