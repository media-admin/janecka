<?php

namespace CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required;

use CleverReach\WordPress\IntegrationCore\BusinessLogic\DTO\OptionsDTO;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\Logger;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\HttpResponse;

/**
 * Class HttpClient
 *
 * @package CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required
 */
abstract class HttpClient
{
    const CLASS_NAME = __CLASS__;

    /**
     * Create, log and send request.
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE etc.)
     * @param string $url Request URL. Full URL where request should be sent.
     * @param array|null $headers Request headers to send. Key as header name and value as header content.
     * @param string $body Request payload. String data to send request payload in JSON format.
     *
     * @return HttpResponse
     *   Response object.
     * @throws HttpCommunicationException
     */
    public function request($method, $url, $headers = array(), $body = '')
    {
        Logger::logDebug(json_encode(array(
            'Type' => $method,
            'Endpoint' => $url,
            'Headers' => json_encode($headers),
            'Content' => $body
        )));

        /** @var HttpResponse $response */
        $response = $this->sendHttpRequest($method, $url, $headers, $body);

        Logger::logDebug(json_encode(array(
            'ResponseFor' => "{$method} at {$url}",
            'Status' => $response->getStatus(),
            'Headers' => json_encode($response->getHeaders()),
            'Content' => $response->getBody()
        )));

        return $response;
    }

    /**
     * Create, log and send request asynchronously.
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE etc.)
     * @param string $url Request URL. Full URL where request should be sent.
     * @param array|null $headers Request headers to send. Key as header name and value as header content.
     * @param string $body Request payload. String data to send request payload in JSON format.
     */
    public function requestAsync($method, $url, $headers = array(), $body = '')
    {
        Logger::logDebug(json_encode(array(
            'Type' => $method,
            'Endpoint' => $url,
            'Headers' => $headers,
            'Content' => $body
        )));
        
        $this->sendHttpRequestAsync($method, $url, $headers, $body);
    }

    /**
     * Tries to make a request with provided combinations within integration.
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE etc.)
     * @param string $url Request URL. Full URL where request should be sent.
     * @param array|null $headers Request headers to send. Key as header name and value as header content.
     * @param string $body Request payload. String data to send as HTTP request payload.
     *
     * @return bool
     *   When request is successful returns true. otherwise false.
     */
    public function autoConfigure($method, $url, $headers = array(), $body = '')
    {
        $passed = $this->isRequestSuccessful($method, $url, $headers, $body);
        if ($passed) {
            return true;
        }

        $combinations = $this->getAdditionalOptions();
        foreach ($combinations as $combination) {
            $this->setAdditionalOptions($combination);
            $passed = $this->isRequestSuccessful($method, $url, $headers, $body);
            if ($passed) {
                return true;
            }

            $this->resetAdditionalOptions();
        }

        return false;
    }

    /**
     * Get additional options for request
     *
     * @return array|void
     *   All possible combinations for additional curl options.
     */
    protected function getAdditionalOptions()
    {
        // Left blank intentionally so integrations can override this method,
        // in order to return all possible combinations for additional curl options
    }

    /**
     * Save additional options for request.
     *
     * @param OptionsDTO[]|null $options Options to save.
     */
    protected function setAdditionalOptions($options)
    {
        // Left blank intentionally so integrations can override this method,
        // in order to save combination to some persisted
        // array which `HttpClient` can use it later while creating request
    }

    /**
     * Reset additional options for request to default value
     */
    protected function resetAdditionalOptions()
    {
        // Left blank intentionally so integrations can override this method,
        // in order to reset to its default values persisted
        // array which `HttpClient` uses later while creating request
    }

    /**
     * Tries to make request using provided parameters.
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE etc.)
     * @param string $url Request URL. Full URL where request should be sent.
     * @param array|null $headers Request headers to send. Key as header name and value as header content.
     * @param string $body Request payload. String data to send request payload in JSON format.
     *
     * @return bool
     *   If request is made successfully returns true, otherwise false.
     *   Response must have 'status' attribute with 'success' value if it is made successfully.
     */
    private function isRequestSuccessful($method, $url, $headers = array(), $body = '')
    {
        try {
            /** @var HttpResponse $response */
            $response = $this->request($method, $url, $headers, $body);
        } catch (HttpCommunicationException $ex) {
            $response = null;
        }

        if ($response !== null) {
            $responseBody = $response->getBody();
            if (!empty($responseBody)) {
                $result = json_decode($responseBody, true);
                if (isset($result['status']) && $result['status'] === 'success') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Create and send request.
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE etc.)
     * @param string $url Request URL. Full URL where request should be sent.
     * @param array|null $headers Request headers to send. Key as header name and value as header content.
     * @param string $body Request payload. String data to send request payload in JSON format.
     *
     * @return HttpResponse
     *   Http response object.
     * @throws HttpCommunicationException
     *   Only in situation when there is no connection, no response, throw this exception.
     */
    abstract public function sendHttpRequest($method, $url, $headers = array(), $body = '');

    /**
     * Create and send request asynchronously.
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE etc.)
     * @param string $url Request URL. Full URL where request should be sent.
     * @param array|null $headers Request headers to send. Key as header name and value as header content.
     * @param string $body Request payload. String data to send request payload in JSON format.
     */
    abstract public function sendHttpRequestAsync($method, $url, $headers = array(), $body = '');
}
