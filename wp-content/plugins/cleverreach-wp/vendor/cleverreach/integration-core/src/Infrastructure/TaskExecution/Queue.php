<?php

namespace CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution;

use CleverReach\WordPress\IntegrationCore\BusinessLogic\Proxy\AuthProxy;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\TaskQueueStorage;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Exposed\TaskRunnerWakeup as TaskRunnerWakeupInterface;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\TimeProvider;

/**
 * Class Queue
 *
 * @package CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution
 */
class Queue
{
    const CLASS_NAME = __CLASS__;
    /** Maximum failure retries count */
    const MAX_RETRIES = 5;
    /**
     * Task queue storage instance.
     *
     * @var TaskQueueStorage
     */
    private $storage;
    /**
     * Time provider instance.
     *
     * @var TimeProvider
     */
    private $timeProvider;
    /**
     * Task runner wakeup service instance.
     *
     * @var TaskRunnerWakeupInterface
     */
    private $taskRunnerWakeup;
    /**
     * Configuration service instance.
     *
     * @var Configuration
     */
    private $configService;

    /**
     * Enqueues queue item to a given queue and stores changes.
     *
     * @param string $queueName Name of a queue where queue item should be queued
     * @param Task $task Task to enqueue.
     * @param string $context Task execution context. If integration supports multiple
     *      accounts (middleware integration) context based on account id should be provided.
     *      Failing to do this will result in global task context and unpredictable task execution.
     * @param int $priority Queue item priority.
     *
     * @return \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\QueueItem
     *   Created queue item.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function enqueue($queueName, Task $task, $context = '', $priority = QueueItem::PRIORITY_MEDIUM)
    {
        $queueItem = new QueueItem($task);
        $queueItem->setStatus(QueueItem::QUEUED);
        $queueItem->setQueueName($queueName);
        $queueItem->setPriority($priority);
        $queueItem->setContext($context);
        $queueItem->setQueueTimestamp($this->getTimeProvider()->getCurrentLocalTime()->getTimestamp());

        try {
            $this->save($queueItem);
            $this->getTaskRunnerWakeup()->wakeup();
        } catch (QueueItemSaveException $exception) {
            throw new QueueStorageUnavailableException(
                'Unable to enqueue task. Queue storage failed to save item.',
                0,
                $exception
            );
        }

        return $queueItem;
    }

    /**
     * Starts task execution, puts queue item in "in_progress" state and stores queue item changes.
     *
     * @param QueueItem $queueItem Queue item to start
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws Exceptions\QueueStorageUnavailableException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function start(QueueItem $queueItem)
    {
        if ($queueItem->getStatus() !== QueueItem::QUEUED) {
            $this->throwIllegalTransitionException($queueItem->getStatus(), QueueItem::IN_PROGRESS);
        }

        $lastUpdateTimestamp = $queueItem->getLastUpdateTimestamp();

        $queueItem->setStatus(QueueItem::IN_PROGRESS);
        $queueItem->setStartTimestamp($this->getTimeProvider()->getCurrentLocalTime()->getTimestamp());
        $queueItem->setLastUpdateTimestamp($queueItem->getStartTimestamp());

        try {
            $this->save(
                $queueItem,
                array('status' => QueueItem::QUEUED, 'lastUpdateTimestamp' => $lastUpdateTimestamp)
            );

            /** @noinspection NullPointerExceptionInspection */
            $queueItem->getTask()->execute();
        } catch (\CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException $exception) {
            /** @var AuthProxy $authProxy */
            $authProxy = ServiceRegister::getService(AuthProxy::CLASS_NAME);
            if ($authProxy->isAlive()) {
                $this->getConfigService()->setUserOnline(false);
            }

            throw $exception;
        } catch (QueueItemSaveException $exception) {
            throw new QueueStorageUnavailableException(
                'Unable to start task. Queue storage failed to save item.',
                0,
                $exception
            );
        }
    }

    /**
     * Puts queue item in finished status and stores changes.
     *
     * @param QueueItem $queueItem Queue item to finish.
     *
     * @throws Exceptions\QueueStorageUnavailableException
     */
    public function finish(QueueItem $queueItem)
    {
        if ($queueItem->getStatus() !== QueueItem::IN_PROGRESS) {
            $this->throwIllegalTransitionException($queueItem->getStatus(), QueueItem::COMPLETED);
        }

        $queueItem->setStatus(QueueItem::COMPLETED);
        $queueItem->setFinishTimestamp($this->getTimeProvider()->getCurrentLocalTime()->getTimestamp());
        $queueItem->setProgressBasePoints(10000);

        try {
            $this->save(
                $queueItem,
                array('status' => QueueItem::IN_PROGRESS, 'lastUpdateTimestamp' => $queueItem->getLastUpdateTimestamp())
            );
        } catch (QueueItemSaveException $exception) {
            throw new QueueStorageUnavailableException(
                'Unable to finish task. Queue storage failed to save item.', 0, $exception
            );
        }
    }

    /**
     * Returns queue item back to queue and sets last execution progress to current progress value.
     *
     * @param QueueItem $queueItem Queue item to requeue.
     *
     * @throws Exceptions\QueueStorageUnavailableException
     */
    public function requeue(QueueItem $queueItem)
    {
        if ($queueItem->getStatus() !== QueueItem::IN_PROGRESS) {
            $this->throwIllegalTransitionException($queueItem->getStatus(), QueueItem::QUEUED);
        }

        $lastExecutionProgress = $queueItem->getLastExecutionProgressBasePoints();

        $queueItem->setStatus(QueueItem::QUEUED);
        $queueItem->setStartTimestamp(null);
        $queueItem->setLastExecutionProgressBasePoints($queueItem->getProgressBasePoints());

        try {
            $this->save(
                $queueItem,
                array(
                    'status' => QueueItem::IN_PROGRESS,
                    'lastExecutionProgress' => $lastExecutionProgress,
                    'lastUpdateTimestamp' => $queueItem->getLastUpdateTimestamp()
                )
            );
        } catch (QueueItemSaveException $exception) {
            throw new QueueStorageUnavailableException(
                'Unable to requeue task. Queue storage failed to save item.', 0, $exception
            );
        }
    }

    /**
     * Returns queue item back to queue and increments retries count.
     *
     * When max retries count is reached puts item in failed status.
     *
     * @param QueueItem $queueItem Queue item to fail.
     * @param string $failureDescription Verbal description of failure.
     *
     * @throws \BadMethodCallException
     *   Queue item must be in "in_progress" status for fail method.
     * @throws Exceptions\QueueStorageUnavailableException
     */
    public function fail(QueueItem $queueItem, $failureDescription)
    {
        if ($queueItem->getStatus() !== QueueItem::IN_PROGRESS) {
            $this->throwIllegalTransitionException($queueItem->getStatus(), QueueItem::FAILED);
        }

        $lastExecutionProgress = $queueItem->getLastExecutionProgressBasePoints();

        $queueItem->setRetries($queueItem->getRetries() + 1);
        $queueItem->setFailureDescription($failureDescription);

        if ($queueItem->getRetries() > $this->getMaxRetries()) {
            $queueItem->setStatus(QueueItem::FAILED);
            $queueItem->setFailTimestamp($this->getTimeProvider()->getCurrentLocalTime()->getTimestamp());
        } else {
            $queueItem->setStatus(QueueItem::QUEUED);
            $queueItem->setStartTimestamp(null);
        }

        try {
            $this->save(
                $queueItem,
                array(
                    'status' => QueueItem::IN_PROGRESS,
                    'lastExecutionProgress' => $lastExecutionProgress,
                    'lastUpdateTimestamp' => $queueItem->getLastUpdateTimestamp()
                )
            );
        } catch (QueueItemSaveException $exception) {
            throw new QueueStorageUnavailableException(
                'Unable to fail task. Queue storage failed to save item.', 0, $exception
            );
        }
    }

    /**
     * Updates current progress of execution.
     *
     * @param QueueItem $queueItem Queue item to update.
     * @param int $progress Current progress of execution.
     *
     * @throws Exceptions\QueueStorageUnavailableException
     */
    public function updateProgress(QueueItem $queueItem, $progress)
    {
        if ($queueItem->getStatus() !== QueueItem::IN_PROGRESS) {
            throw new \BadMethodCallException('Progress reported for not started queue item.');
        }

        $lastExecutionProgress = $queueItem->getLastExecutionProgressBasePoints();
        $lastUpdateTimestamp = $queueItem->getLastUpdateTimestamp();

        $queueItem->setProgressBasePoints($progress);
        $queueItem->setLastUpdateTimestamp($this->getTimeProvider()->getCurrentLocalTime()->getTimestamp());

        try {
            $this->save(
                $queueItem,
                array(
                    'status' => QueueItem::IN_PROGRESS,
                    'lastExecutionProgress' => $lastExecutionProgress,
                    'lastUpdateTimestamp' => $lastUpdateTimestamp
                )
            );
        } catch (QueueItemSaveException $exception) {
            throw new QueueStorageUnavailableException(
                'Unable to update task progress. Queue storage failed to save item.', 0, $exception
            );
        }
    }

    /**
     * Keep task alive and in progress.
     *
     * @param QueueItem $queueItem Queue item to keep alive.
     *
     * @throws Exceptions\QueueStorageUnavailableException
     */
    public function keepAlive(QueueItem $queueItem)
    {
        $lastExecutionProgress = $queueItem->getLastExecutionProgressBasePoints();
        $lastUpdateTimestamp = $queueItem->getLastUpdateTimestamp();
        $queueItem->setLastUpdateTimestamp($this->getTimeProvider()->getCurrentLocalTime()->getTimestamp());

        try {
            $this->save(
                $queueItem,
                array(
                    'status' => QueueItem::IN_PROGRESS,
                    'lastExecutionProgress' => $lastExecutionProgress,
                    'lastUpdateTimestamp' => $lastUpdateTimestamp
                )
            );
        } catch (QueueItemSaveException $exception) {
            throw new QueueStorageUnavailableException(
                'Unable to keep task alive. Queue storage failed to save item.', 0, $exception
            );
        }
    }

    /**
     * Finds queue item by ID.
     *
     * @param int $id ID of a queue item to find.
     *
     * @return QueueItem|null
     *   Found queue item or null when queue item does not exist.
     */
    public function find($id)
    {
        return $this->getStorage()->find($id);
    }

    /**
     * Finds latest queue item by type.
     *
     * @param string $type Type of a queue item to find.
     * @param string $context Task scope restriction, default is global scope.
     *
     * @return QueueItem|null
     *   Found queue item or null when queue item does not exist
     */
    public function findLatestByType($type, $context = '')
    {
        return $this->getStorage()->findLatestByType($type, $context);
    }

    /**
     * Finds queue items with status "in_progress".
     *
     * @return QueueItem[]
     *   Running queue items
     */
    public function findRunningItems()
    {
        return $this->getStorage()->findAll(array('status' => QueueItem::IN_PROGRESS));
    }

    /**
     * Finds list of earliest queued queue items per queue.
     *
     * Only queues that doesn't have running tasks are taken in consideration.
     *
     * @param int $limit Result set limit. By default 10 earliest queue items will be returned.
     *
     * @return \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\QueueItem[]
     *   Found queue item list.
     */
    public function findOldestQueuedItems($limit = 10)
    {
        return $this->getStorage()->findOldestQueuedItems($limit);
    }

    /**
     * Creates or updates given queue item using storage service.
     *
     * If queue item id is not set, new queue item will be created otherwise
     * update will be performed.
     *
     * @param QueueItem $queueItem Item to save
     * @param array $additionalWhere List of key/value pairs to set in where clause when saving queue item.
     *
     * @return int
     *   ID of saved queue item.
     *
     * @throws Exceptions\QueueItemSaveException
     *   If save fails.
     */
    private function save(QueueItem $queueItem, array $additionalWhere = array())
    {
        $id = $this->getStorage()->save($queueItem, $additionalWhere);
        $queueItem->setId($id);

        return $id;
    }

    /**
     * Gets task queue storage service instance.
     *
     * @return TaskQueueStorage
     *   Instance of storage service.
     */
    private function getStorage()
    {
        if ($this->storage === null) {
            $this->storage = ServiceRegister::getService(TaskQueueStorage::CLASS_NAME);
        }

        return $this->storage;
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
     * Gets task runner wakeup service instance.
     *
     * @return TaskRunnerWakeupInterface
     *   Instance of task runner wakeup service.
     */
    private function getTaskRunnerWakeup()
    {
        if ($this->taskRunnerWakeup === null) {
            $this->taskRunnerWakeup = ServiceRegister::getService(TaskRunnerWakeupInterface::CLASS_NAME);
        }

        return $this->taskRunnerWakeup;
    }

    /**
     * Gets configuration service instance.
     *
     * @return Configuration
     *   Instance of configuration service.
     */
    private function getConfigService()
    {
        if ($this->configService === null) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }

    /**
     * Throws exception when illegal transition is detected.
     *
     * @param string $fromStatus Status A.
     * @param string $toStatus Status B.
     */
    private function throwIllegalTransitionException($fromStatus, $toStatus)
    {
        throw new \BadMethodCallException(
            sprintf(
                'Illegal queue item state transition from "%s" to "%s"',
                $fromStatus,
                $toStatus
            )
        );
    }

    /**
     * Gets maximum number of failed task execution retries.
     *
     * @return int|null
     *   Default system value (5).
     */
    private function getMaxRetries()
    {
        $configurationValue = $this->getConfigService()->getMaxTaskExecutionRetries();

        return $configurationValue !== null ? $configurationValue : self::MAX_RETRIES;
    }
}
