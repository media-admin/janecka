<?php

namespace CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces;

use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\LogData;

/**
 * Interface LoggerAdapter
 *
 * @package CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces
 */
interface LoggerAdapter
{
    /**
     * Log message in the system.
     *
     * @param LogData|null $data Log data object.
     */
    public function logMessage($data);
}
