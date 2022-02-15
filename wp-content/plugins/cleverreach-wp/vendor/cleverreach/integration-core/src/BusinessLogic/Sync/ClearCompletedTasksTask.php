<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync;

use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\TaskQueueStorage;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Task;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\TimeProvider;

/**
 * Class ClearCompletedTasksTask
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync
 */
class ClearCompletedTasksTask extends Task
{
    /**
     * @var TimeProvider
     */
    private $timeProvider;
    /**
     * @var Configuration
     */
    private $configurationService;

    /**
     * Removes all completed ScheduleCheckTask items which are older than 1 hour
     */
    public function execute()
    {
        /** @var TaskQueueStorage $taskQueueStorage */
        $taskQueueStorage = ServiceRegister::getService(TaskQueueStorage::CLASS_NAME);
        $deleteLimit = 1000;
        $excludedTypes = array(InitialSyncTask::getClassName(), RefreshUserInfoTask::getClassName());

        for ($i = 0; $i < 100; $i++) {
            $deletedCount = $taskQueueStorage->deleteBy($excludedTypes, $this->getFinishedTimestamp(), $deleteLimit);
            if ($deletedCount < $deleteLimit) {
                break;
            }

            $this->reportProgress($i + 1);
            $this->getTimeProvider()->sleep(1);
        }

        $this->reportProgress(100);
    }

    /**
     * Returns queue item finish timestamp.
     *
     * @return int
     */
    protected function getFinishedTimestamp()
    {
        $retentionPeriodInDays = $this->getConfigurationService()->getMaxQueueItemRetentionPeriod();
        $currentTimestamp = $this->getTimeProvider()->getCurrentLocalTime()->getTimestamp();

        return $currentTimestamp - $retentionPeriodInDays * 86400;
    }

    /**
     * Gets time provider instance.
     *
     * @return TimeProvider
     *   Instance of time provider.
     */
    protected function getTimeProvider()
    {
        if ($this->timeProvider === null) {
            $this->timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);
        }

        return $this->timeProvider;
    }

    /**
     * Gets configuration service instance.
     *
     * @return Configuration
     *   Instance of configuration service.
     */
    protected function getConfigurationService()
    {
        if ($this->configurationService === null) {
            $this->configurationService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configurationService;
    }
}
