<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Scheduler\Models;

use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Configuration\EntityConfiguration;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Configuration\IndexMap;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Entity;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Task;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Serializer;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\TimeProvider;

/** @noinspection PhpDocMissingThrowsInspection */

/**
 * Class Schedule
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Scheduler\Models
 */
class Schedule extends Entity
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Date and time of next schedule.
     *
     * @var \DateTime
     */
    public $nextSchedule;
    /**
     * Array of field names.
     *
     * @var array
     */
    protected $fields = array(
        'id',
        'queueName',
        'context',
        'minute',
        'hour',
        'day',
        'month',
        'recurring',
        'lastUpdateTimestamp'
    );
    /**
     * Queue name where task should be queued to.
     *
     * @var string
     */
    protected $queueName;
    /**
     * Context in which task will be executed.
     *
     * @var string
     */
    protected $context;
    /**
     * Schedule minute.
     *
     * @var int
     */
    protected $minute = 0;
    /**
     * Schedule hour.
     *
     * @var int
     */
    protected $hour = 0;
    /**
     * Schedule day.
     *
     * @var int
     */
    protected $day = 1;
    /**
     * Schedule month.
     *
     * @var int
     */
    protected $month = 1;
    /**
     * Task that is to be queued for execution.
     *
     * @var Task
     */
    protected $task;
    /**
     * Whether schedule should execute repeatedly.
     *
     * @var bool
     */
    protected $recurring = true;
    /**
     * Timestamp when schedule is last updated
     *
     * @var int
     */
    protected $lastUpdateTimestamp;

    /**
     * Schedule constructor.
     *
     * @param Task $task Task that is to be queued for execution
     * @param string $queueName Queue name in which task should be queued into
     * @param string $context Context in which task should be executed
     */
    public function __construct(Task $task = null, $queueName = null, $context = '')
    {
        $this->task = $task;
        $this->queueName = $queueName;
        $this->context = $context;
    }

    /**
     * Transforms raw array data to this entity instance.
     *
     * @param array $data Raw array data.
     */
    public function inflate(array $data)
    {
        parent::inflate($data);

        $this->task = Serializer::unserialize($data['task']);
    }

    /**
     * Transforms entity to its array format representation.
     *
     * @return array Entity in array format.
     */
    public function toArray()
    {
        $data = parent::toArray();
        $data['task'] = Serializer::serialize($this->task);

        return $data;
    }

    /**
     * Returns entity configuration object.
     *
     * @return EntityConfiguration Entity configuration with index.
     */
    public function getConfig()
    {
        $map = new IndexMap();
        $map->addDateTimeIndex('nextSchedule')
            ->addIntegerIndex('lastUpdateTimestamp')
            ->addStringIndex('taskType')
            ->addStringIndex('context');

        return new EntityConfiguration($map, 'Schedule');
    }

    /**
     * Returns task.
     *
     * @return Task Task for schedule.
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * Returns task type
     *
     * @return string Task type
     */
    public function getTaskType()
    {
        return $this->task->getType();
    }

    /**
     * Returns queue name.
     *
     * @return string Queue name.
     */
    public function getQueueName()
    {
        return $this->queueName;
    }

    /**
     * Sets queue name.
     *
     * @param string $queueName Queue name in which task is scheduled.
     */
    public function setQueueName($queueName)
    {
        $this->queueName = $queueName;
    }

    /**
     * /**
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
     * Returns next schedule date.
     *
     * @return \DateTime Next schedule.
     */
    public function getNextSchedule()
    {
        return $this->nextSchedule;
    }

    /**
     * Sets next schedule datetime.
     */
    public function setNextSchedule()
    {
        $this->nextSchedule = $this->calculateNextSchedule();
    }

    /**
     * Returns schedule minute.
     *
     * @return int Schedule minute.
     */
    public function getMinute()
    {
        return $this->minute;
    }

    /**
     * Sets schedule minute.
     *
     * @param int $minute Schedule minute.
     */
    public function setMinute($minute)
    {
        $this->minute = $minute;
    }

    /**
     * Returns schedule hour.
     *
     * @return int Schedule hour.
     */
    public function getHour()
    {
        return $this->hour;
    }

    /**
     * Sets schedule hour.
     *
     * @param int $hour Schedule hour.
     */
    public function setHour($hour)
    {
        $this->hour = $hour;
    }

    /**
     * Returns schedule day.
     *
     * @return int Schedule day.
     */
    public function getDay()
    {
        return $this->day;
    }

    /**
     * Returns schedule day.
     *
     * @param int $day Schedule day.
     */
    public function setDay($day)
    {
        $this->day = $day;
    }

    /**
     * Returns schedule month.
     *
     * @return int Month number, starting from 1 for January ending with 12 for December.
     */
    public function getMonth()
    {
        return $this->month;
    }

    /**
     * Sets schedule month.
     *
     * @param int $month Month number, starting from 1 for January ending with 12 for December.
     */
    public function setMonth($month)
    {
        $this->month = $month;
    }

    /**
     * Return whether the schedule is recurring.
     *
     * @return bool
     */
    public function isRecurring()
    {
        return $this->recurring;
    }

    /**
     * Set whether the schedule is recurring.
     *
     * @param bool $recurring
     */
    public function setRecurring($recurring)
    {
        $this->recurring = $recurring;
    }

    /**
     * Gets schedule last updated timestamp or null if schedule was never updated.
     *
     * @return int
     *   Schedule last updated timestamp.
     */
    public function getLastUpdateTimestamp()
    {
        return $this->lastUpdateTimestamp;
    }

    /**
     * Sets schedule last updated timestamp.
     *
     * @param int $lastUpdateTimestamp schedule last updated timestamp.
     */
    public function setLastUpdateTimestamp($lastUpdateTimestamp)
    {
        $this->lastUpdateTimestamp = $lastUpdateTimestamp;
    }

    /**
     * Calculates next schedule time.
     *
     * @return \DateTime Next schedule date.
     */
    protected function calculateNextSchedule()
    {
        return $this->now();
    }

    /**
     * Returns current date and time.
     *
     * @return \DateTime Date and time.
     */
    protected function now()
    {
        /** @var TimeProvider $timeProvider */
        $timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);

        return $timeProvider->getCurrentLocalTime();
    }
}
