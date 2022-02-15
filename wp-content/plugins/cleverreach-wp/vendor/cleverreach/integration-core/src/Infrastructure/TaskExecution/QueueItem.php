<?php

namespace CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution;

use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\Logger;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\TaskEvents\AliveAnnouncedTaskEvent;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\TaskEvents\ProgressedTaskEvent;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Serializer;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\TimeProvider;

/**
 * Class QueueItem
 *
 * @package CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution
 */
class QueueItem
{
    /**
     * Queue item statuses.
     */
    const CREATED = 'created';
    const QUEUED = 'queued';
    const IN_PROGRESS = 'in_progress';
    const COMPLETED = 'completed';
    const FAILED = 'failed';

    /**
     * Queue item priorities;
     */
    const PRIORITY_LOW = 10;
    const PRIORITY_MEDIUM = 20;
    const PRIORITY_HIGH = 30;

    /**
     * Queue item ID.
     *
     * @var int
     */
    private $id;
    /**
     * Queue item status.
     *
     * @var string
     */
    private $status;
    /**
     * Task associated to queue item.
     *
     * @var Task
     */
    private $task;
    /**
     * Context in which task will be executed.
     *
     * @var string
     */
    private $context;
    /**
     * String representation of task.
     *
     * @var string
     */
    private $serializedTask;
    /**
     * Integration queue name.
     *
     * @var string
     */
    private $queueName;
    /**
     * Last execution progress base points (integer value of 0.01%).
     *
     * @var int $lastExecutionProgressBasePoints
     */
    private $lastExecutionProgressBasePoints;
    /**
     * Current execution progress in base points (integer value of 0.01%).
     *
     * @var int $progressBasePoints
     */
    private $progressBasePoints;
    /**
     * Number of attempts to execute task.
     *
     * @var int
     */
    private $retries;
    /**
     * Description of failure when task fails.
     *
     * @var string
     */
    private $failureDescription;
    /**
     * Datetime when queue item is created.
     *
     * @var \DateTime
     */
    private $createTime;
    /**
     * Datetime when queue item is started.
     *
     * @var \DateTime
     */
    private $startTime;
    /**
     * Datetime when queue item is finished.
     *
     * @var \DateTime
     */
    private $finishTime;
    /**
     * Datetime when queue item is failed.
     *
     * @var \DateTime
     */
    private $failTime;
    /**
     * Min datetime when queue item can start.
     *
     * @var \DateTime
     */
    private $earliestStartTime;
    /**
     * Datetime when queue item is enqueued.
     *
     * @var \DateTime
     */
    private $queueTime;
    /**
     * Datetime when queue item is last updated.
     *
     * @var \DateTime
     */
    private $lastUpdateTime;
    /**
     * @var int
     */
    private $priority;
    /**
     * Instance of time provider.
     *
     * @var TimeProvider
     */
    private $timeProvider;

    /**
     * QueueItem constructor.
     *
     * @param Task|null $task Associated task object.
     * @param string $context Context in which task will be executed.
     * @param int $priority Queue item priority.
     */
    public function __construct(Task $task = null, $context = '', $priority = self::PRIORITY_MEDIUM)
    {
        $this->timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);

        $this->task = $task;
        $this->context = $context;
        $this->status = self::CREATED;
        $this->lastExecutionProgressBasePoints = 0;
        $this->progressBasePoints = 0;
        $this->retries = 0;
        $this->failureDescription = '';
        $this->createTime = $this->timeProvider->getCurrentLocalTime();
        $this->priority = $priority;

        $this->attachTaskEventHandlers();
    }

    /**
     * Attach Task event handlers.
     */
    private function attachTaskEventHandlers()
    {
        if ($this->task === null) {
            return;
        }

        $self = $this;
        $this->task->when(
            ProgressedTaskEvent::CLASS_NAME,
            function (ProgressedTaskEvent $event) use ($self) {
                $queue = new Queue();
                $queue->updateProgress($self, $event->getProgressBasePoints());
            }
        );

        $this->task->when(
            AliveAnnouncedTaskEvent::CLASS_NAME,
            function () use ($self) {
                $queue = new Queue();
                $queue->keepAlive($self);
            }
        );
    }

    /**
     * Gets queue item status.
     *
     * @return string
     *   Queue item status.
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Sets queue item status.
     *
     * @param string $status Queue item status.
     */
    public function setStatus($status)
    {
        if (!in_array(
            $status,
            array(
                self::CREATED,
                self::QUEUED,
                self::IN_PROGRESS,
                self::COMPLETED,
                self::FAILED,
            ),
            false
        )) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid QueueItem status: "%s". Status must be one of "%s", "%s", "%s", "%s" or "%s" values.',
                    $status,
                    self::CREATED,
                    self::QUEUED,
                    self::IN_PROGRESS,
                    self::COMPLETED,
                    self::FAILED
                )
            );
        }

        $this->status = $status;
    }

    /**
     * Gets queue name.
     *
     * @return string
     *   Integration queue name.
     */
    public function getQueueName()
    {
        return $this->queueName;
    }

    /**
     * Sets queue item queue name.
     *
     * @param string $queueName Queue item queue name.
     */
    public function setQueueName($queueName)
    {
        $this->queueName = $queueName;
    }

    /**
     * Gets queue item last execution progress in base points as value between 0 and 10000.
     *
     * One base point is equal to 0.01%.
     * For example 23.58% is equal to 2358 base points.
     *
     * @return int
     *   Last execution progress expressed in base points.
     */
    public function getLastExecutionProgressBasePoints()
    {
        return $this->lastExecutionProgressBasePoints;
    }

    /**
     * Sets queue item last execution progress in base points, as value between 0 and 10000.
     *
     * One base point is equal to 0.01%.
     * For example 23.58% is equal to 2358 base points.
     *
     * @param int $lastExecutionProgressBasePoints Queue item last execution progress in base points
     */
    public function setLastExecutionProgressBasePoints($lastExecutionProgressBasePoints)
    {
        if (!is_int($lastExecutionProgressBasePoints) ||
            $lastExecutionProgressBasePoints < 0 ||
            10000 < $lastExecutionProgressBasePoints) {
            throw new \InvalidArgumentException('Last execution progress percentage must be value between 0 and 100.');
        }

        $this->lastExecutionProgressBasePoints = $lastExecutionProgressBasePoints;
    }

    /**
     * Gets progress in percentage rounded to 2 decimal value.
     *
     * @return float
     *   QueueItem progress in percentage rounded to 2 decimal value.
     */
    public function getProgressFormatted()
    {
        return round($this->progressBasePoints / 100, 2);
    }

    /**
     * Gets queue item progress in base points as value between 0 and 10000.
     *
     * One base point is equal to 0.01%.
     * For example 23.58% is equal to 2358 base points.
     *
     * @return int
     *   Queue item progress percentage in base points.
     */
    public function getProgressBasePoints()
    {
        return $this->progressBasePoints;
    }

    /**
     * Sets queue item progress in base points, as value between 0 and 10000.
     *
     * One base point is equal to 0.01%.
     * For example 23.58% is equal to 2358 base points.
     *
     * @param int $progressBasePoints Queue item progress in base points.
     */
    public function setProgressBasePoints($progressBasePoints)
    {
        if (!is_int($progressBasePoints) || $progressBasePoints < 0 || 10000 < $progressBasePoints) {
            throw new \InvalidArgumentException('Progress percentage must be value between 0 and 100.');
        }

        $this->progressBasePoints = $progressBasePoints;
    }

    /**
     * Gets queue item retries count.
     *
     * @return int
     *   Queue item retries count.
     */
    public function getRetries()
    {
        return $this->retries;
    }

    /**
     * Sets queue item retries count.
     *
     * @param int $retries Queue item retries count.
     */
    public function setRetries($retries)
    {
        $this->retries = $retries;
    }

    /**
     * Gets serialized queue item task.
     *
     * @return string
     *   Serialized representation of queue item task.
     */
    public function getSerializedTask()
    {
        if ($this->task === null) {
            return $this->serializedTask;
        }

        return Serializer::serialize($this->task);
    }

    /**
     * Sets serialized task representation.
     *
     * @param string $serializedTask Serialized representation of task.
     */
    public function setSerializedTask($serializedTask)
    {
        $this->serializedTask = $serializedTask;
        $this->task = null;
    }

    /**
     * Gets task execution context.
     *
     * @return string
     *   Context in which task will be executed.
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Sets task execution context. Context in which task will be executed.
     *
     * @param string $context Execution context.
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * Gets queue item failure description.
     *
     * @return string
     *   Queue item failure description.
     */
    public function getFailureDescription()
    {
        return $this->failureDescription;
    }

    /**
     * Sets queue item failure description.
     *
     * @param string $failureDescription
     *   Queue item failure description.
     */
    public function setFailureDescription($failureDescription)
    {
        $this->failureDescription = $failureDescription;
    }

    /**
     * Gets queue item created timestamp.
     *
     * @return int|null
     *   Queue item created timestamp.
     */
    public function getCreateTimestamp()
    {
        return $this->getTimestamp($this->createTime);
    }

    /**
     * Sets queue item created timestamp.
     *
     * @param int $timestamp
     *   Sets queue item created timestamp.
     */
    public function setCreateTimestamp($timestamp)
    {
        $this->createTime = $this->getDateTimeFromTimestamp($timestamp);
    }

    /**
     * Gets queue item start timestamp or null if task is not started.
     *
     * @return int|null
     *   Queue item start timestamp.
     */
    public function getStartTimestamp()
    {
        return $this->getTimestamp($this->startTime);
    }

    /**
     * Sets queue item start timestamp.
     *
     * @param int $timestamp
     *   Queue item start timestamp.
     */
    public function setStartTimestamp($timestamp)
    {
        $this->startTime = $this->getDateTimeFromTimestamp($timestamp);
    }

    /**
     * Gets queue item finish timestamp or null if task is not finished.
     *
     * @return int|null
     *   Queue item finish timestamp.
     */
    public function getFinishTimestamp()
    {
        return $this->getTimestamp($this->finishTime);
    }

    /**
     * Sets queue item finish timestamp.
     *
     * @param int $timestamp Queue item finish timestamp.
     */
    public function setFinishTimestamp($timestamp)
    {
        $this->finishTime = $this->getDateTimeFromTimestamp($timestamp);
    }

    /**
     * Gets queue item fail timestamp or null if task is not failed.
     *
     * @return int|null
     *   Queue item fail timestamp.
     */
    public function getFailTimestamp()
    {
        return $this->getTimestamp($this->failTime);
    }

    /**
     * Sets queue item fail timestamp.
     *
     * @param int $timestamp Queue item fail timestamp.
     */
    public function setFailTimestamp($timestamp)
    {
        $this->failTime = $this->getDateTimeFromTimestamp($timestamp);
    }

    /**
     * Gets queue item earliest start timestamp or null if not set.
     *
     * @return int|null
     *   Queue item earliest start timestamp.
     */
    public function getEarliestStartTimestamp()
    {
        return $this->getTimestamp($this->earliestStartTime);
    }

    /**
     * Sets queue item earliest start timestamp.
     *
     * @param int $timestamp Queue item earliest start timestamp.
     */
    public function setEarliestStartTimestamp($timestamp)
    {
        $this->earliestStartTime = $this->getDateTimeFromTimestamp($timestamp);
    }

    /**
     * Gets queue item queue timestamp or null if task is not queued.
     *
     * @return int|null
     *   Queue item queue timestamp.
     */
    public function getQueueTimestamp()
    {
        return $this->getTimestamp($this->queueTime);
    }

    /**
     * Gets queue item queue timestamp.
     *
     * @param int $timestamp Queue item queue timestamp.
     */
    public function setQueueTimestamp($timestamp)
    {
        $this->queueTime = $this->getDateTimeFromTimestamp($timestamp);
    }

    /**
     * Gets queue item last updated timestamp or null if task was never updated.
     *
     * @return int|null
     *   Queue item last updated timestamp.
     */
    public function getLastUpdateTimestamp()
    {
        return $this->getTimestamp($this->lastUpdateTime);
    }

    /**
     * Reconfigures underlying task.
     *
     * @throws Exceptions\QueueItemDeserializationException
     */
    public function reconfigureTask()
    {
        $task = $this->getTask();

        if ($task !== null && $task->canBeReconfigured()) {
            $task->reconfigure();
            $this->setRetries(0);
            Logger::logDebug('Task ' . $this->getTaskType() . ' reconfigured.');
        }
    }

    /**
     * Gets queue item associated task or null if not set.
     *
     * @return Task|null
     *   Queue item associated task.
     *
     * @throws Exceptions\QueueItemDeserializationException
     */
    public function getTask()
    {
        if ($this->task === null) {
            try {
                $this->task = Serializer::unserialize($this->serializedTask);
            } catch (\Exception $e) {
                $this->task = null;
            }

            if (empty($this->task)) {
                throw new QueueItemDeserializationException(
                    json_encode(
                        array(
                            'Message' => 'Unable to deserialize queue item task',
                            'SerializedTask' => $this->serializedTask,
                            'QueueItemId' => $this->getId(),
                        )
                    )
                );
            }

            $this->attachTaskEventHandlers();
        }

        return !empty($this->task) ? $this->task : null;
    }

    /**
     * Gets queue item ID.
     *
     * @return int
     *   Queue item ID.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets queue item ID.
     *
     * @param int $id Queue item ID.
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Gets queue item task type.
     *
     * @return string
     *   Queue item task type.
     * @throws Exceptions\QueueItemDeserializationException
     */
    public function getTaskType()
    {
        $task = $this->getTask();

        return $task !== null ? $task->getType() : '';
    }

    /**
     * Sets queue item last updated timestamp.
     *
     * @param int $timestamp
     *   Queue item last updated timestamp.
     */
    public function setLastUpdateTimestamp($timestamp)
    {
        $this->lastUpdateTime = $this->getDateTimeFromTimestamp($timestamp);
    }

    /**
     * Gets timestamp of datetime.
     *
     * @param \DateTime|null $time Datetime object.
     *
     * @return int|null
     *   Timestamp of provided datetime or null if time is not defined.
     */
    protected function getTimestamp(\DateTime $time = null)
    {
        return $time !== null ? $time->getTimestamp() : null;
    }

    /**
     * Gets @see \DateTime object from timestamp.
     *
     * @param int $timestamp Timestamp in seconds.
     *
     * @return \DateTime|null
     *  Object if successful; otherwise, null;
     */
    protected function getDateTimeFromTimestamp($timestamp)
    {
        return !empty($timestamp) ? $this->timeProvider->getDateTime($timestamp) : null;
    }

    /**
     * Returns queue item priority.
     *
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Sets queue item priority.
     *
     * @param int $priority
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    }
}
