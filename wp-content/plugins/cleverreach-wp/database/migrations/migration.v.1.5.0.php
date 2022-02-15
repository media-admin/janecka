<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Database\Migrations;

use CleverReach\WordPress\Components\InfrastructureServices\Config_Service;
use CleverReach\WordPress\Components\Utility\Database;
use CleverReach\WordPress\Components\Utility\Update\Update_Schema;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync\AttributesSyncTask;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Queue;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\QueueItem;

/**
 * Class Migration_1_5_0
 *
 * @package CleverReach\WordPress\Database\Migrations
 */
class Migration_1_5_0 extends Update_Schema {
	/**
	 * @inheritDoc
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
	 */
	public function update() {
		$this->add_priority_column_to_queue_table();
		$this->add_composite_index_to_queue_table();
		$this->update_attributes();
	}

	/**
	 * Adds a new column that signifies queue item priority to the queue table.
	 */
	private function add_priority_column_to_queue_table() {
		$this->db->query( 'ALTER TABLE `' . Database::table( Database::QUEUE_TABLE )
		                  . '` ADD `priority` INT(11) NOT NULL DEFAULT ' . QueueItem::PRIORITY_MEDIUM );
	}

	/**
	 * Adds composite index to queue table.
	 */
	private function add_composite_index_to_queue_table() {
		$this->db->query( 'ALTER TABLE `' . Database::table( Database::QUEUE_TABLE )
		                  . '` ADD INDEX `cleverreach_queue_idx_status_queuename_priority` (`status`,`queueName`,`priority`)' );
	}

	/**
	 * Updates attributes by enqueuing attributes sync task.
	 *
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
	 */
	private function update_attributes() {
		/** @var Queue $queue_service */
		$queue_service = ServiceRegister::getService(Queue::CLASS_NAME);
		/** @var Config_Service $config_service */
		$config_service = ServiceRegister::getService(Configuration::CLASS_NAME);

		$initial_sync_task = $queue_service->findLatestByType('Initial_Sync_Task');
		if ($initial_sync_task !== null && $initial_sync_task->getStatus() === QueueItem::COMPLETED) {
			$queue_service->enqueue($config_service->getQueueName(), new AttributesSyncTask());
		}
	}

}
