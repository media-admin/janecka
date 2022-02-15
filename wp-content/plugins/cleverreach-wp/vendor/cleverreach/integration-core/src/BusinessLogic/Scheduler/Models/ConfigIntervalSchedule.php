<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Scheduler\Models;

use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\ConfigRepositoryInterface;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Task;

/**
 * Class ConfigIntervalSchedule
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Scheduler\Models
 */
class ConfigIntervalSchedule extends Schedule
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Default schedule interval in minutes
     */
    const DEFAULT_INTERVAL = 1;

    /**
     * Array of field names.
     *
     * @var array
     */
    protected $fields = array('id', 'queueName', 'context', 'intervalConfigKey', 'lastUpdateTimestamp');

    /**
     * @var string
     */
    protected $intervalConfigKey;

    /**
     * @var ConfigRepositoryInterface
     */
    protected $configRepository;

    /**
     * Schedule constructor.
     *
     * @param Task $task Task that is to be queued for execution
     * @param string $queueName Queue name in which task should be queued into
     * @param string $context Context in which task should be executed
     * @param string $intervalConfigKey Configuration key from where schedule interval value will be read. If empty
     * default interval value of 1 minute will be used.
     */
    public function __construct(Task $task = null, $queueName = null, $context = '', $intervalConfigKey = '')
    {
        parent::__construct($task, $queueName, $context);
        $this->intervalConfigKey = $intervalConfigKey;
    }

    /**
     * @return string
     */
    public function getIntervalConfigKey()
    {
        return $this->intervalConfigKey;
    }

    /**
     * Returns schedule interval.
     *
     * @return int Interval in minuets.
     */
    public function getInterval()
    {
        if (empty($this->intervalConfigKey)) {
            return self::DEFAULT_INTERVAL;
        }

        return $this->getConfigRepository()->get($this->intervalConfigKey) ?: self::DEFAULT_INTERVAL;
    }

    /**
     * Calculates next schedule time.
     *
     * @return \DateTime Next schedule date.
     * @throws \Exception Emits Exception in case of an error while creating DateTime instance.
     */
    protected function calculateNextSchedule()
    {
        $interval = new \DateInterval("PT{$this->getInterval()}M");

        return $this->now()->add($interval);
    }

    /**
     * Gets instance on configuration service.
     *
     * @return ConfigRepositoryInterface
     *   Instance of configuration service.
     */
    protected function getConfigRepository()
    {
        if ($this->configRepository === null) {
            $this->configRepository = ServiceRegister::getService(ConfigRepositoryInterface::CLASS_NAME);
        }

        return $this->configRepository;
    }
}
