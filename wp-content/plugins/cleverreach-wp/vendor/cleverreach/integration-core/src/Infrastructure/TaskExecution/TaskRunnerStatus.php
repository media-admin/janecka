<?php

namespace CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution;

use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\TimeProvider;

/**
 * Class TaskRunnerStatus
 *
 * @package CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution
 */
class TaskRunnerStatus
{
    /** Maximal time allowed for runner instance to stay in alive (running) status in seconds */
    const MAX_ALIVE_TIME = 60;

    /**
     * Current task runner instance identifier.
     *
     * @var string
     */
    private $guid;
    /**
     * Timestamp since runner is in alive status or null if runner was never alive.
     *
     * @var int|null
     */
    private $aliveSinceTimestamp;
    /**
     * Instance of time provider.
     *
     * @var TimeProvider
     */
    private $timeProvider;
    /**
     * Instance of configuration service.
     *
     * @var Configuration
     */
    private $configService;

    /**
     * TaskRunnerStatus constructor.
     *
     * @param string $guid Runner Instance identifier.
     * @param int|null $aliveSinceTimestamp Timestamp when runner started.
     */
    public function __construct($guid, $aliveSinceTimestamp)
    {
        $this->guid = $guid;
        $this->aliveSinceTimestamp = $aliveSinceTimestamp;
        $this->timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);
        $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
    }

    /**
     * Starts new instance of task runner status.
     *
     * @return TaskRunnerStatus
     *   New runner instance with null status.
     */
    public static function createNullStatus()
    {
        return new self('', null);
    }

    /**
     * Gets runner instance identifier.
     *
     * @return string
     *   Unique identifier.
     */
    public function getGuid()
    {
        return $this->guid;
    }

    /**
     * Gets timestamp since runner is in alive status.
     *
     * @return int|null
     *   Null if runner was never alive, otherwise timestamp since runner is alive.
     */
    public function getAliveSinceTimestamp()
    {
        return $this->aliveSinceTimestamp;
    }

    /**
     * Checks if runner has been expired.
     *
     * @return bool
     *   If expired returns true, otherwise false.
     */
    public function isExpired()
    {
        $currentTimestamp = $this->timeProvider->getCurrentLocalTime()->getTimestamp();

        /** @noinspection IsEmptyFunctionUsageInspection */
        return !empty($this->aliveSinceTimestamp)
            && ($this->aliveSinceTimestamp + $this->getMaxAliveTimestamp() < $currentTimestamp);
    }

    /**
     * Gets max alive time in seconds.
     *
     * @return int|null
     *   If not set explicitly in configuration, default value (60s) is returned.
     */
    private function getMaxAliveTimestamp()
    {
        $configurationValue = $this->configService->getTaskRunnerMaxAliveTime();

        return $configurationValue !== null ? $configurationValue : self::MAX_ALIVE_TIME;
    }
}
