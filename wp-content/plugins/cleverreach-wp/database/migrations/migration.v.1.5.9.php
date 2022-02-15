<?php

namespace CleverReach\WordPress\Database\Migrations;

use CleverReach\WordPress\Components\Utility\Database;
use CleverReach\WordPress\Components\Utility\Update\Update_Schema;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\RepositoryRegistry;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\QueryFilter\QueryFilter;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\QueryFilter\Operators;

/**
 * Class Migration_1_5_9
 *
 * @package CleverReach\WordPress\Database\Migrations
 */
class Migration_1_5_9 extends Update_Schema {
	/**
	 * @inheritDoc
	 */
	public function update() {
		$this->remove_survey_task_scheduler();
		$this->remove_obsolete_config_values();
	}

	/**
	 * Removes unnecessary scheduler for the survey check task.
	 */
	private function remove_survey_task_scheduler() {
		$schedule_repository = RepositoryRegistry::getScheduleRepository();

		$filter = new QueryFilter();
		$filter->where( 'taskType', Operators::EQUALS, 'SurveyCheckTask' );

		$schedule_repository->deleteBy( $filter );
	}

	/**
	 * Removes configuration values not needed by the plugin.
	 */
	private function remove_obsolete_config_values() {
		global $wpdb;
		$table_name = Database::table( Database::CONFIG_TABLE );

		$wpdb->query( "DELETE FROM `$table_name` WHERE `key` IN (
			'IS_FIRST_FORM_USED', 
			'IS_PLUGIN_INSTALLED_FORM_OPENED', 
			'IS_INITIAL_SYNC_FINISHED_FORM_OPENED'
		)" );
	}
}
