<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Database\Migrations;

use CleverReach\WordPress\Components\Utility\Database;
use CleverReach\WordPress\Components\Utility\Update\Update_Schema;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\RepositoryRegistry;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Scheduler\Models\HourlySchedule;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync\ClearCompletedScheduleCheckTasksTask;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync\FormSyncTask;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync\InitialSyncTask;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync\SurveyCheckTask;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryClassException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\QueueItem;

/**
 * Class Migration_1_4_0
 *
 * @package CleverReach\WordPress\Database\Migrations
 */
class Migration_1_4_0 extends Update_Schema {

	/**
	 * @inheritDoc
	 *
	 * @throws QueueStorageUnavailableException
	 * @throws RepositoryClassException
	 * @throws RepositoryNotRegisteredException
	 */
	public function update() {

		$this->add_entity_table();
		$initial_sync_task_item = $this->task_queue_storage->findLatestByType( InitialSyncTask::getClassName() );
		$this->add_scheduled_tasks();

		if ( $initial_sync_task_item && $initial_sync_task_item->getStatus() === QueueItem::COMPLETED ) {
			$this->queue_service->enqueue( $this->config_service->getQueueName(), new FormSyncTask() );
		}

		$this->config_service->set_entity_table_created();
	}

	/**
	 * Creates cleverreach entity table
	 */
	private function add_entity_table() {
		$sql = 'CREATE TABLE IF NOT EXISTS `' . Database::table( Database::ENTITY_TABLE ) . '` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `type` VARCHAR(127),
            `index_1` VARCHAR(127),
            `index_2` VARCHAR(127),
            `index_3` VARCHAR(127),
            `index_4` VARCHAR(127),
            `index_5` VARCHAR(127),
            `index_6` VARCHAR(127),
            `index_7` VARCHAR(127),
            `data` LONGTEXT,
            PRIMARY KEY (`id`)
        )';

		$this->db->query( $sql );
	}

	/**
	 * @throws RepositoryClassException
	 * @throws RepositoryNotRegisteredException
	 */
	private function add_scheduled_tasks() {
		$schedule_repository = RepositoryRegistry::getScheduleRepository();

		$survey_schedule = new HourlySchedule( new SurveyCheckTask(), $this->config_service->getQueueName() );
		$survey_schedule->setInterval( 12 );
		$survey_schedule->setNextSchedule();
		$schedule_repository->save( $survey_schedule );

		$clear_scheduled_check_tasks_schedule = new HourlySchedule(
			new ClearCompletedScheduleCheckTasksTask(),
			$this->config_service->getQueueName()
		);

		$clear_scheduled_check_tasks_schedule->setNextSchedule();
		$schedule_repository->save( $clear_scheduled_check_tasks_schedule );
	}
}
