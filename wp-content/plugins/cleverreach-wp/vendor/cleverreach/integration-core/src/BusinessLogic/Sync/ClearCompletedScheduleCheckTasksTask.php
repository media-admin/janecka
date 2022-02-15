<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync;

use CleverReach\WordPress\IntegrationCore\BusinessLogic\Scheduler\ScheduleCheckTask;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\TaskQueueStorage;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Task;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\TimeProvider;

/**
 * Class ClearCompletedScheduleCheckTasksTask
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync
 */
class ClearCompletedScheduleCheckTasksTask extends Task
{
    const INITIAL_PROGRESS_PERCENT = 10;

    const HOURS = 1;

    /**
     * Removes all completed ScheduleCheckTask items which are older than 1 hour
     */
    public function execute()
    {
        $this->reportProgress(self::INITIAL_PROGRESS_PERCENT);
        /** @var TaskQueueStorage $taskQueueStorage */
        $taskQueueStorage = ServiceRegister::getService(TaskQueueStorage::CLASS_NAME);
        $taskQueueStorage->deleteCompletedQueueItems(ScheduleCheckTask::getClassName(), $this->getFinishedTimestamp());

        $this->reportProgress(100);
    }

    /**
     * Returns queue item finish timestamp.
     *
     * @return int
     */
    private function getFinishedTimestamp()
    {
        /** @var TimeProvider $timeProvider */
        $timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);

        $currentTimestamp = $timeProvider->getCurrentLocalTime()->getTimestamp();

        return $currentTimestamp - self::HOURS * 3600;
    }
}
