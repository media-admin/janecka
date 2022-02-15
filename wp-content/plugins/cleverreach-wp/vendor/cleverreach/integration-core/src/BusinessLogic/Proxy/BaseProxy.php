<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Proxy;

use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\AuthInfo;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Interfaces\Proxy;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\HttpClient;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\Logger;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\HttpResponse;

/**
 * Class BaseProxy
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Proxy
 */
abstract class BaseProxy implements Proxy
{
    const API_VERSION = 'v3';
    const BASE_URL = 'https://rest.cleverreach.com/';
    const HTTP_STATUS_CODE_DEFAULT = 400;
    const HTTP_STATUS_CODE_UNAUTHORIZED = 401;
    const HTTP_STATUS_CODE_FORBIDDEN = 403;
    const HTTP_STATUS_CODE_CONFLICT = 409;
    const HTTP_STATUS_CODE_NOT_SUCCESSFUL_FOR_DEFINED_BATCH_SIZE = 413;
    /**
     * Instance of HttpClient service.
     *
     * @var HttpClient
     */
    protected $client;
    /**
     * Instance of Configuration service.
     *
     * @var Configuration
     */
    protected $configService;

    /**
     * Call HTTP client.
     *
     * @inheritdoc
     *
     * @throws \InvalidArgumentException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws RefreshTokenExpiredException
     * @throws InvalidConfigurationException
     */
    public function call($method, $endpoint, $body = array())
    {
        $headers = $this->getHeaders();
        $url = $this->getBaseUrl() . $endpoint;
        $payload = $this->formatPayload($method, $body);

        return $this->makeRequest($method, $url, $headers, $payload);
    }

    /**
     * Execute HTTP Request
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE etc.)
     * @param string $url Request URL. Full URL where request should be sent.
     * @param array|null $headers Request headers to send. Key as header name and value as header content.
     * @param string $payload Request payload. String data to send request payload in JSON format.
     *
     * @return HttpResponse
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    protected function makeRequest($method, $url, $headers, $payload)
    {
        $response = $this->getClient()->request($method, $url, $headers, $payload);
        $this->validateResponse($response);

        return $response;
    }

    /**
     * Returns json encoded body for POST or PUT requests
     *
     * @param string $method HTTP method
     * @param array $body Request body
     *
     * @return string
     *   json encoded body
     */
    protected function formatPayload($method, array $body)
    {
        return in_array(strtoupper($method), array('POST', 'PUT')) ? json_encode($body) : '';
    }

    /**
     * Return HTTP header
     *
     * @return array
     *
     * @throws InvalidConfigurationException
     * @throws HttpCommunicationException
     * @throws RefreshTokenExpiredException
     */
    protected function getHeaders()
    {
        $headers = $this->getBaseHeaders();
        if ($this->isAccessTokenRequired()) {
            $headers['token'] = 'Authorization: Bearer ' . $this->getValidAccessToken();
        }

        return $headers;
    }

    /**
     * Return base HTTP header
     *
     * @return array
     */
    protected function getBaseHeaders()
    {
        return array(
            'accept' => 'Accept: application/json',
            'content' => 'Content-Type: application/json',
        );
    }

    /**
     * Returns true if access token is required for call method
     *
     * @return bool
     */
    protected function isAccessTokenRequired()
    {
        return true;
    }

    /**
     * Retrieves valid access token.
     *
     * @return string
     *   Valid access token.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    protected function getValidAccessToken()
    {
        // Try to get access token from config and validate expiration
        $token = $this->getConfigService()->getAccessToken();

        if ($this->getConfigService()->isAccessTokenExpired()) {
            try {
                $result = $this->refreshAccessToken();
                $token = $result['access_token'];
            } catch (RefreshTokenExpiredException $e) {
                $this->getConfigService()->setRefreshToken(null);
                throw $e;
            }

            if (isset($result['access_token'], $result['expires_in'], $result['refresh_token'])) {
                $this->getConfigService()->setAuthInfo(
                    new AuthInfo(
                        $result['access_token'],
                        $result['expires_in'],
                        $result['refresh_token']
                    )
                );
            }
        }

        if (empty($token)) {
            throw new InvalidConfigurationException('Access token missing');
        }

        return $token;
    }

    /**
     * Refreshes access token.
     *
     * @return array
     *   An associative array with tokens
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     */
    protected function refreshAccessToken()
    {
        $refreshToken = $this->getConfigService()->getRefreshToken();
        if (empty($refreshToken)) {
            throw new InvalidConfigurationException('Refresh token not found! User must re-authenticate.');
        }

        $payload = '&grant_type=refresh_token&refresh_token=' . $refreshToken;
        $identity = base64_encode($this->getConfigService()->getClientId() . ':' . $this->getConfigService()->getClientSecret());
        $header = array('Authorization: Basic ' . $identity);

        $response = $this->getClient()->request('POST', $this->getTokenUrl(), $header, $payload);
        if (!$response->isSuccessful()) {
            throw new RefreshTokenExpiredException('Refresh token expired! User must re-authenticate.');
        }

        $result = json_decode($response->getBody(), true);
        if (empty($result['access_token']) || empty($result['expires_in'])) {
            throw new HttpCommunicationException('CleverReach API invalid response.');
        }

        return $result;
    }

    /**
     * Gets CleverReach REST API authentication url.
     *
     * @return string
     *   Authentication url.
     */
    protected function getAuthenticationUrl()
    {
        return static::BASE_URL . 'oauth/authorize.php';
    }

    /**
     * Gets CleverReach REST API token url.
     *
     * @return string
     *   Token url.
     */
    protected function getTokenUrl()
    {
        return static::BASE_URL . 'oauth/token.php';
    }

    /**
     * Gets CleverReach REST API base url.
     *
     * @return string
     *   Base url.
     */
    protected function getBaseUrl()
    {
        $baseUrl = static::BASE_URL;
        $apiVersion = $this->getApiVersion();

        return !empty($apiVersion) ? $baseUrl . $apiVersion . '/' : $baseUrl;
    }

    /**
     * Return API version
     *
     * @return string
     */
    protected function getApiVersion()
    {
        return static::API_VERSION;
    }

    /**
     * Validate response.
     *
     * @param HttpResponse|null $response Http response.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     */
    protected function validateResponse($response)
    {
        $httpCode = $response->getStatus();
        if ($this->isErrorCode($httpCode)) {
            list($message, $httpCode) = $this->extractMessageAndCode($response);

            if ($this->isUnauthorizedOrForbidden($httpCode)) {
                Logger::logInfo($message);
                throw new HttpAuthenticationException($message, $httpCode);
            }

            $this->logAndThrowHttpRequestException($message, $httpCode);
        }
    }

    /**
     * Check if status code outside the range [200, 300)
     *
     * @param int $httpCode HTTP status code
     *
     * @return bool
     */
    protected function isErrorCode($httpCode)
    {
        return ($httpCode !== null) && ($httpCode < 200 || $httpCode >= 300);
    }

    /**
     * Check if status code is 401 or 403
     *
     * @param int $httpCode
     *
     * @return bool
     */
    protected function isUnauthorizedOrForbidden($httpCode)
    {
        return ($httpCode === self::HTTP_STATUS_CODE_UNAUTHORIZED) || ($httpCode === self::HTTP_STATUS_CODE_FORBIDDEN);
    }

    /**
     * Logs provided message as error and throws exception.
     *
     * @param string $message Message to be logged and put to exception.
     * @param int $code Status code.
     * @param null $previousException
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     */
    protected function logAndThrowHttpRequestException($message, $code = 0, $previousException = null)
    {
        Logger::logError($message);

        throw new HttpRequestException($message, $code, $previousException);
    }

    /**
     * Get instance of http client.
     *
     * @return HttpClient
     *   Http client object.
     *
     * @throws \InvalidArgumentException
     */
    protected function getClient()
    {
        if ($this->client === null) {
            $this->client = ServiceRegister::getService(HttpClient::CLASS_NAME);
        }

        return $this->client;
    }

    /**
     * @return Configuration
     */
    protected function getConfigService()
    {
        if ($this->configService === null) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }

    /**
     * Extract error message and status code from response
     *
     * @param HttpResponse $response Object.
     *
     * @return array
     *   [message, statusCode]
     */
    protected function extractMessageAndCode($response)
    {
        $httpCode = $response->getStatus();
        $body = $response->getBody();
        $message = var_export($body, true);

        $error = json_decode($body, true);
        if (is_array($error)) {
            if (isset($error['error']['message'])) {
                $message = $error['error']['message'];
            }

            if (isset($error['error']['code'])) {
                $httpCode = $error['error']['code'];
            }
        }

        return array($message, $httpCode);
    }
}
