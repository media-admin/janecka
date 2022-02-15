<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Components\Sync;

use CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync\InitialSyncTask as BaseInitialSyncTask;

/**
 * Class Initial_Sync_Task
 *
 * @package CleverReach\WordPress\Components\Sync
 */
class Initial_Sync_Task extends BaseInitialSyncTask {

	/**
	 * InitialSyncTask constructor.
	 *
	 * @param array $subTasks        List of sub tasks.
	 * @param int   $initialProgress Initial progress percentage.
	 */
	public function __construct( array $subTasks = array(), $initialProgress = 0 ) {
		if ( empty( $subTasks ) && ! $this->getConfigService()->is_recipient_sync_enabled() ) {
			$this->setSubscriberListTasks( $subTasks );
		}

		parent::__construct( $subTasks, $initialProgress );
	}

	/**
	 * Returns progress by initial sync task groups:
	 *
	 * - First group: Group sync and Product search
	 * - Second group: Attributes and Filter
	 * - Third group: Recipients.
	 *
	 * @return array
	 *   Initial sync task group as keys and progress as value.
	 */
	public function getProgressByTask() {
		if ( ! $this->getConfigService()->is_recipient_sync_enabled() ) {
			return array(
				'subscriberList' => $this->getSubscriberListTasksProgress(),
			);
		}

		return parent::getProgressByTask();
	}

	/**
	 * Gets count of synchronized recipients.
	 *
	 * @return int
	 *   Number of synced recipients.
	 */
	public function getSyncedRecipientsCount() {
		if ( ! $this->getConfigService()->is_recipient_sync_enabled() ) {
			return 0;
		}

		return parent::getSyncedRecipientsCount();
	}

	/**
	 * Sets tasks for first group in initial sync to the list of sub tasks.
	 *
	 * First group: Group sync and Product search
	 *
	 * @param array $subTasks List of sub tasks used in this task.
	 */
	protected function setSubscriberListTasks( array &$subTasks ) {
		if ( ! $this->getConfigService()->is_recipient_sync_enabled() ) {
			$subTasks[ $this->getGroupSyncTaskName() ]            = 10;
			$subTasks[ $this->getRegisterEventHandlerTaskName() ] = 10;
			$subTasks[ $this->getProductSearchSyncTaskName() ]    = 20;
			$subTasks[ $this->getFormSyncTaskName() ]             = 20;
			$subTasks[ $this->getFormCacheSyncTaskName() ]        = 20;
			$subTasks[ $this->getAttributesSyncTaskName() ]       = 20;
		} else {
			parent::setSubscriberListTasks( $subTasks );
		}
	}

	/**
	 * Gets overall progress of tasks belonging to subscriber list task group.
	 *
	 * @return float
	 *   Current progress of subscriber list.
	 */
	protected function getSubscriberListTasksProgress()
	{
		if ( ! $this->getConfigService()->is_recipient_sync_enabled() ) {
			$subscriber_list_tasks_progress = $this->taskProgressMap[ $this->getGroupSyncTaskName() ];
			$subscriber_list_tasks_progress += $this->taskProgressMap[ $this->getAttributesSyncTaskName() ];

			$optional_tasks = $this->getOptionalTasks();
			if ( in_array( $this->getProductSearchSyncTaskName(), $optional_tasks, true ) ) {
				$subscriber_list_tasks_progress += $this->taskProgressMap[ $this->getProductSearchSyncTaskName() ];
			}

			if ( in_array( $this->getFormSyncTaskName(), $optional_tasks, true ) ) {
				$subscriber_list_tasks_progress += $this->taskProgressMap[ $this->getFormSyncTaskName() ];
			}

			if ( in_array( $this->getFormCacheSyncTaskName(), $optional_tasks, true ) ) {
				$subscriber_list_tasks_progress += $this->taskProgressMap[ $this->getFormCacheSyncTaskName() ];
			}

			return $subscriber_list_tasks_progress / ( 2 + count( $optional_tasks ) );
		}

		return parent::getSubscriberListTasksProgress();
	}
}
