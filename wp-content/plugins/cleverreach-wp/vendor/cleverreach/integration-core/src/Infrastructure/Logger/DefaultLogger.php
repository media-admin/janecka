<?php

namespace CleverReach\WordPress\IntegrationCore\Infrastructure\Logger;

use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\DefaultLoggerAdapter;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\HttpClient;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;

/**
 * Class DefaultLogger
 *
 * @package CleverReach\WordPress\IntegrationCore\Infrastructure\Logger
 */
class DefaultLogger implements DefaultLoggerAdapter
{
    /**
     * Sending log data to CleverReach API.
     *
     * @param LogData|null $data Log data object.
     */
    public function logMessage($data)
    {
        /** @var HttpClient $httpClient */
        $httpClient = ServiceRegister::getService(HttpClient::CLASS_NAME);
        // Waiting on CR to define API endpoint
        $httpClient->requestAsync('POST', '', array(), json_encode(get_object_vars($data)));
    }
}
