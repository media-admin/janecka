<?php

namespace CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution;

use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Exposed\TaskRunnerStatusStorage as TaskRunnerStatusStorageInterface;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Exposed\TaskRunnerWakeup as TaskRunnerWakeupInterface;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\Logger;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\TimeProvider;

/**
 * Class TaskRunner
 *
 * @package CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution
 */
class TaskRunner
{
    const CLASS_NAME = __CLASS__;

    /** Automatic task runner wakeup delay in seconds */
    const WAKEUP_DELAY = 5;

    /**
     * Runner guid.
     *
     * @var string
     */
    protected $guid;
    /**
     * Instance of queue.
     *
     * @var Queue
     */
    private $queue;
    /**
     * Instance of task runner storage.
     *
     * @var TaskRunnerStatusStorageInterface
     */
    private $runnerStorage;
    /**
     * Instance of configuration service.
     *
     * @var Configuration
     */
    private $configurationService;
    /**
     * Instance of time provider.
     *
     * @var TimeProvider
     */
    private $timeProvider;
    /**
     * Instance of task runner wakeup service.
     *
     * @var TaskRunnerWakeupInterface
     */
    private $taskWakeup;
    /**
     * Waiting time for whole batch with sub-batches to finish in seconds with microsecond precession
     *
     * @var float
     */
    private $batchStarterWaitTime = 0;

    /**
     * Sets task runner guid.
     *
     * @param string $guid Runner guid to set.
     */
    public function setGuid($guid)
    {
        $this->guid = $guid;
    }

    /**
     * Starts task runner lifecycle.
     */
    public function run()
    {
        try {
            $this->logDebug(array('Message' => 'Task runner: lifecycle started.'));

            if ($this->isCurrentRunnerAlive()) {
                $this->failOrRequeueExpiredTasks();
                $this->startOldestQueuedItems();
            }

            $this->wakeup();

            $this->logDebug(array('Message' => 'Task runner: lifecycle ended.'));
        } catch (\Exception $ex) {
            $this->logDebug(array(
                'Message' => 'Fail to run task runner. Unexpected error occurred.',
                'ExceptionMessage' => $ex->getMessage(),
                'ExceptionTrace' => $ex->getTraceAsString()
            ));
        }
    }

    /**
     * If extended inactivity period is reached, stop task runner, otherwise requeue.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     */
    private function failOrRequeueExpiredTasks()
    {
        $this->logDebug(array('Message' => 'Task runner: expired tasks cleanup started.'));

        $runningItems = $this->getQueue()->findRunningItems();
        if (!$this->isCurrentRunnerAlive()) {
            return;
        }

        foreach ($runningItems as $runningItem) {
            if ($this->isItemExpired($runningItem) && $this->isCurrentRunnerAlive()) {
                $this->logMessageFor($runningItem, 'Task runner: Expired task detected.');
                $this->getConfigurationService()->setContext($runningItem->getContext());
                if ($runningItem->getProgressBasePoints() > $runningItem->getLastExecutionProgressBasePoints()) {
                    $this->logMessageFor($runningItem, 'Task runner: Task requeue for execution continuation.');
                    $this->getQueue()->requeue($runningItem);
                } else {
                    $runningItem->reconfigureTask();
                    $this->getQueue()->fail(
                        $runningItem,
                        sprintf('Task %s failed due to extended inactivity period.', $this->getItemDescription($runningItem))
                    );
                }
            }
        }
    }

    /**
     * Starts oldest queue item from all queues respecting following criteria:
     *
     * - Queue must be without already running queue items
     * - For one queue only one (oldest queued) item should be started
     * - Number of running tasks must NOT be greater than maximal allowed by integration configuration
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\ProcessStarterSaveException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    private function startOldestQueuedItems()
    {
        $this->logDebug(array('Message' => 'Task runner: available task detection started.'));

        // Calculate how many queue items can be started
        $maxRunningTasks = $this->getConfigurationService()->getMaxStartedTasksLimit();
        $alreadyRunningItems = $this->getQueue()->findRunningItems();
        $numberOfAvailableSlotsForTaskRunning = $maxRunningTasks - count($alreadyRunningItems);
        if ($numberOfAvailableSlotsForTaskRunning <= 0) {
            $this->logDebug(array('Message' => 'Task runner: max number of active tasks reached.'));
            return;
        }

        $items = $this->getQueue()->findOldestQueuedItems($numberOfAvailableSlotsForTaskRunning);

        if (!$this->isCurrentRunnerAlive() || empty($items)) {
            return;
        }

        $asyncStarterBatchSize = $this->getConfigurationService()->getAsyncStarterBatchSize();
        $batchStarter = new AsyncBatchStarter($asyncStarterBatchSize);
        foreach ($items as $item) {
            $this->logMessageFor($item, 'Task runner: Adding task to a batch starter for async execution.');
            $batchStarter->addRunner(new QueueItemStarter($item->getId()));
        }

        if (!$this->isCurrentRunnerAlive()) {
            return;
        }

        $this->logDebug(array('Message' => 'Task runner: Starting batch starter execution.'));
        $startTime = $this->getTimeProvider()->getMicroTimestamp();
        $batchStarter->run();
        $endTime = $this->getTimeProvider()->getMicroTimestamp();

        $averageRequestTime = ($endTime - $startTime) / $asyncStarterBatchSize;
        $this->batchStarterWaitTime = $batchStarter->getWaitTime($averageRequestTime);
        $this->logDebug(array(
            'Message' => 'Task runner: Batch starter execution finished.',
            'ExecutionTime' => ($endTime - $startTime).'s',
            'AverageRequestTime' => $averageRequestTime.'s',
            'StartedItems' => count($items),
        ));
    }

    /**
     * Wake up task runner.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusChangeException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     */
    private function wakeup()
    {
        $this->logDebug(array('Message' => 'Task runner: starting self deactivation.'));
        $this->getTimeProvider()->sleep(ceil($this->batchStarterWaitTime + $this->getWakeupDelay()));

        $this->getRunnerStorage()->setStatus(TaskRunnerStatus::createNullStatus());

        $this->logDebug(array('Message' => 'Task runner: sending task runner wakeup signal.'));
        $this->getTaskWakeup()->wakeup();
    }

    /**
     * Checks if current runner is alive.
     *
     * @return bool
     *   If is alive returns true, otherwise false.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     */
    private function isCurrentRunnerAlive()
    {
        $runnerStatus = $this->getRunnerStorage()->getStatus();
        $runnerExpired = $runnerStatus->isExpired();
        $runnerGuidIsCorrect = $this->guid === $runnerStatus->getGuid();

        if ($runnerExpired) {
            $this->logWarning(array('Message' => 'Task runner: Task runner started but it is expired.'));
        }

        if (!$runnerGuidIsCorrect) {
            $this->logWarning(array('Message' => 'Task runner: Task runner started but it is not active anymore.'));
        }

        return !$runnerExpired && $runnerGuidIsCorrect;
    }

    /**
     * Checks if queue item is expired based on configuration value.
     *
     * @param QueueItem $item Queue item to be checked.
     *
     * @return bool
     *   If expired return true, otherwise false.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    private function isItemExpired(QueueItem $item)
    {
        $currentTimestamp = $this->getTimeProvider()->getCurrentLocalTime()->getTimestamp();
        /** @noinspection NullPointerExceptionInspection */
        $maxTaskInactivityPeriod = $item->getTask()->getMaxInactivityPeriod();

        return ($item->getLastUpdateTimestamp() + $maxTaskInactivityPeriod) < $currentTimestamp;
    }

    /**
     * Get queue item description.
     *
     * @param QueueItem $item
     *   Queue item instance.
     *
     * @return string
     *   Queue item description in format id(type).
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    private function getItemDescription(QueueItem $item)
    {
        return "{$item->getId()}({$item->getTaskType()})";
    }

    /**
     * Gets queue service instance.
     *
     * @return Queue
     *   Instance of queue service.
     */
    private function getQueue()
    {
        if ($this->queue === null) {
            $this->queue = ServiceRegister::getService(Queue::CLASS_NAME);
        }

        return $this->queue;
    }

    /**
     * Gets task runner status storage instance.
     *
     * @return TaskRunnerStatusStorageInterface
     *   Instance of runner status storage service.
     */
    private function getRunnerStorage()
    {
        if ($this->runnerStorage === null) {
            $this->runnerStorage = ServiceRegister::getService(TaskRunnerStatusStorageInterface::CLASS_NAME);
        }

        return $this->runnerStorage;
    }

    /**
     * Gets configuration service instance.
     *
     * @return Configuration
     *   Instance of configuration service.
     */
    private function getConfigurationService()
    {
        if ($this->configurationService === null) {
            $this->configurationService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configurationService;
    }

    /**
     * Gets time provider instance.
     *
     * @return TimeProvider
     *   Instance of time provider.
     */
    private function getTimeProvider()
    {
        if ($this->timeProvider === null) {
            $this->timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);
        }

        return $this->timeProvider;
    }

    /**
     * Gets task runner wakeup instance.
     *
     * @return TaskRunnerWakeupInterface
     *   Instance of runner wakeup service.
     */
    private function getTaskWakeup()
    {
        if ($this->taskWakeup === null) {
            $this->taskWakeup = ServiceRegister::getService(TaskRunnerWakeupInterface::CLASS_NAME);
        }

        return $this->taskWakeup;
    }

    /**
     * Gets wakeup delay from configuration.
     *
     * @return int
     *   If not provided in configuration, default value is returned.
     */
    private function getWakeupDelay()
    {
        $configurationValue = $this->getConfigurationService()->getTaskRunnerWakeupDelay();

        return $configurationValue !== null ? $configurationValue : self::WAKEUP_DELAY;
    }

    /**
     * Logs message and queue item details.
     *
     * @param QueueItem $queueItem Queue to be logged.
     * @param string $message Log message.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    private function logMessageFor(QueueItem $queueItem, $message)
    {
        $this->logDebug(array(
            'RunnerGuid' => $this->guid,
            'Message' => $message,
            'TaskId' => $queueItem->getId(),
            'TaskType' => $queueItem->getTaskType(),
            'TaskRetries' => $queueItem->getRetries(),
            'TaskProgressBasePoints' => $queueItem->getProgressBasePoints(),
            'TaskLastExecutionProgressBasePoints' => $queueItem->getLastExecutionProgressBasePoints(),
        ));
    }

    /**
     * Log debug message.
     *
     * @param array $debugContent
     *   Log parameters.
     */
    private function logDebug(array $debugContent)
    {
        $debugContent['RunnerGuid'] = $this->guid;
        Logger::logDebug(json_encode($debugContent));
    }

    /**
     * Log warning message.
     *
     * @param array $debugContent
     *   Log parameters.
     */
    private function logWarning(array $debugContent)
    {
        $debugContent['RunnerGuid'] = $this->guid;
        Logger::logWarning(json_encode($debugContent));
    }
}
