<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Interfaces;

use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\HttpResponse;

/**
 * Interface Proxy
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Interfaces
 */
interface Proxy
{
    const CLASS_NAME = __CLASS__;

    /**
     * Call HTTP client.
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE etc.)
     * @param string $endpoint Specific endpoint that should be called.
     * @param array|null $body Request body.
     *
     * @return HttpResponse
     *   Response object that contains status, headers and body.
     */
    public function call($method, $endpoint, $body = array());
}
