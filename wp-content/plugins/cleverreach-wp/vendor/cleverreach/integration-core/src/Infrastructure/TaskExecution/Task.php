<?php

namespace CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution;

use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Serializable;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\TaskEvents\AliveAnnouncedTaskEvent;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\TaskEvents\ProgressedTaskEvent;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Events\EventEmitter;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Serializer;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\TimeProvider;

/**
 * Class Task
 *
 * @package CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution
 */
abstract class Task extends EventEmitter implements Serializable
{
    /** Max inactivity period for a task in seconds */
    const MAX_INACTIVITY_PERIOD = 300;
    /** Minimal number of seconds that must pass between two alive signals */
    const ALIVE_SIGNAL_FREQUENCY = 2;
    /**
     * Datetime when last alive signal is emitted.
     *
     * @var \DateTime
     */
    private $lastAliveSignalTime;
    /**
     * Configuration instance service.
     *
     * @var Configuration
     */
    private $configService;

    /**
     * Transforms array into entity.
     *
     * @param array $array
     *
     * @return \CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Serializable
     */
    public static function fromArray($array)
    {
        return new static();
    }

    /**
     * Runs task execution.
     *
     * @return mixed
     *   Task execution result.
     */
    abstract public function execute();

    /**
     * String representation of object
     *
     * @inheritdoc
     */
    public function serialize()
    {
        return Serializer::serialize(array());
    }

    /**
     * Constructs the object.
     *
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        // This method was intentionally left blank because this task
        // doesn't have any properties which needs to encapsulate
    }

    /**
     * Transforms entity to array.
     *
     * @return array
     */
    public function toArray()
    {
        return array();
    }

    /**
     * Reports task progress by emitting ProgressedTaskEvent and defers next AliveAnnouncedTaskEvent.
     *
     * @param float|int $progressPercent Float representation of progress percentage,
     *      value between 0 and 100 that immediately will be converted to base points.
     *      One base point is equal to 0.01%. For example 23.58% is equal to 2358 base points
     *
     * @throws \InvalidArgumentException
     *   In case when progress percent is outside of 0 - 100 boundaries or not an float.
     */
    public function reportProgress($progressPercent)
    {
        if (!is_int($progressPercent) && !is_float($progressPercent)) {
            throw new \InvalidArgumentException('Progress percentage must be value integer or float value');
        }

        /** @var TimeProvider $timeProvider */
        $timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);
        $this->lastAliveSignalTime = $timeProvider->getCurrentLocalTime();

        $this->fire(new ProgressedTaskEvent($this->percentToBasePoints($progressPercent)));
    }

    /**
     * Reports that task is alive by emitting AliveAnnouncedTaskEvent.
     */
    public function reportAlive()
    {
        /** @var TimeProvider $timeProvider */
        $timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);
        $currentTime = $timeProvider->getCurrentLocalTime();
        if ($this->lastAliveSignalTime === null
            || $this->lastAliveSignalTime->getTimestamp() + self::ALIVE_SIGNAL_FREQUENCY < $currentTime->getTimestamp()
        ) {
            $this->lastAliveSignalTime = $currentTime;
            $this->fire(new AliveAnnouncedTaskEvent());
        }
    }

    /**
     * Gets max inactivity period for a task.
     *
     * @return int
     *   Max inactivity period for a task in seconds.
     */
    public function getMaxInactivityPeriod()
    {
        $configurationValue = $this->getConfigService()->getMaxTaskInactivityPeriod();

        return $configurationValue !== null ? $configurationValue : static::MAX_INACTIVITY_PERIOD;
    }

    /**
     * Gets name of the class.
     *
     * Alias method for static method {@see self::getClassName()}
     *
     * @return string
     *   Class name.
     */
    public function getType()
    {
        return static::getClassName();
    }

    /**
     * Gets name of the class.
     *
     * @return string
     *   Class name.
     */
    public static function getClassName()
    {
        $namespaceParts = explode('\\', get_called_class());
        $name = end($namespaceParts);

        if ($name === 'Task') {
            throw new \RuntimeException('Constant CLASS_NAME not defined in class ' . get_called_class());
        }

        return $name;
    }

    /**
     * Indicates whether task can be configured or not.
     *
     * @return bool
     *   If task is possible to be configured returns true, otherwise false.
     */
    public function canBeReconfigured()
    {
        return false;
    }

    /**
     * Reconfigures the task.
     */
    public function reconfigure()
    {
    }

    /**
     * Calculates base points for progress tracking from percent value.
     *
     * @param float $percentValue Percentage that should be converted to base points.
     *
     * @return int
     *   Base points converted from percentage.
     */
    private function percentToBasePoints($percentValue)
    {
        return (int)round($percentValue * 100, 2);
    }

    /**
     * Gets configuration service instance.
     *
     * @return Configuration
     *   Instance of configuration service.
     */
    protected function getConfigService()
    {
        if ($this->configService === null) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }
}
