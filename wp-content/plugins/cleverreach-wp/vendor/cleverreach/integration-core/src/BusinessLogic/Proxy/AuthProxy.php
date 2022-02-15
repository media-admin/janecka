<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Proxy;

use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\AuthInfo;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\ConnectionStatusResponse;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\BadAuthInfoException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\Logger;

/**
 * Class AuthProxy
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Proxy
 */
class AuthProxy extends BaseProxy
{
    const CLASS_NAME = __CLASS__;
    /**
     * @var bool
     */
    private $accessTokenRequired = true;

    /**
     * Sends request that informs CleverReach that OAuth process is completed.
     *
     * @param array $parameters Associative array containing request payload.
     *   * finished: boolean
     *   * name: string
     *   * client_id" string
     *   * brand: string
     *
     * @return void
     */
    public function finishOAuth($parameters)
    {
        $uri = 'oauth/finish.json';
        try {
            $this->call('POST', $uri, $parameters);
        } catch (\Exception $e) {
            Logger::logError('Failed to finish OAuth because: ' . $e->getMessage());
        }
    }

    /**
     * Sends request that deletes the connection on the CleverReach side.
     */
    public function revokeOAuth()
    {
        $uri = 'oauth/token.json';
        try {
            $this->call('DELETE', $uri);
        } catch (\Exception $e) {
            Logger::logError('Failed to revoke access token because: ' . $e->getMessage());
        }
    }

    /**
     * Checks whether token is alive and accessible.
     *
     * @return bool
     */
    public function isConnected()
    {
        $connectionStatusResponse = $this->checkConnectionStatus();
        $status = $connectionStatusResponse->getStatus();
        $this->getConfigService()->setUserOnline($status);

        return $status;
    }

    /**
     * Validates connection to the CleverReach
     *
     * @return \CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\ConnectionStatusResponse
     */
    public function checkConnectionStatus()
    {
        $uri = 'debug/validate.json';
        try {
            $response = $this->call('GET', $uri);
            $userConnected = $response->getBody() === '"true"';
            $message = '';
        } catch (\Exception $e) {
            $userConnected = false;
            $message = $e->getMessage();
        }

        return new ConnectionStatusResponse($userConnected, $message);
    }

    /**
     * Checks whether CleverReach API is alive.
     *
     * @return bool
     */
    public function isAlive()
    {
        $uri = 'debug/ping.json';
        $isAlive = true;

        try {
            $this->accessTokenRequired = false;
            $this->call('GET', $uri);

        } catch (\Exception $e) {
            $isAlive = false;
        }

        $this->accessTokenRequired = true;

        return $isAlive;
    }

    /**
     * Returns authentication information (AuthInfo).
     *
     * @param string $code Access code.
     * @param string $redirectUrl Url for callback.
     *
     * @return AuthInfo Authentication information object.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\BadAuthInfoException
     */
    public function getAuthInfo($code, $redirectUrl)
    {
        $header = array(
            'accept' => 'Accept: application/json',
            'content' => 'Content-Type: application/json',
        );

        // Assemble POST parameters for the request.
        $postFields = array(
            'grant_type' => 'authorization_code',
            'client_id' => $this->getConfigService()->getClientId(),
            'client_secret' => $this->getConfigService()->getClientSecret(),
            'code' => $code,
            'redirect_uri' => urlencode($redirectUrl),
        );

        $response = $this->getClient()->request('POST', $this->getTokenUrl(), $header, json_encode($postFields));
        $result = json_decode($response->getBody(), true);
        if (isset($result['error'])
            || empty($result['access_token'])
            || empty($result['expires_in'])
            || empty($result['refresh_token'])
        ) {
            throw new BadAuthInfoException(
                isset($result['error_description']) ? $result['error_description'] : ''
            );
        }

        return new AuthInfo($result['access_token'], $result['expires_in'], $result['refresh_token']);
    }

    /**
     * Returns auth URL.
     *
     * @param string $redirectUrl Redirect URL.
     * @param string $registerData Data for user registration on CleverReach.
     * @param array $additionalParams Additional params in query.
     *
     * @return string
     *   CleverReach auth url.
     */
    public function getAuthUrl($redirectUrl, $registerData = '', array $additionalParams = array())
    {
        $url = $this->getAuthenticationUrl()
            . '?response_type=code'
            . '&grant=basic'
            . '&client_id=' . $this->getConfigService()->getClientId()
            . '&redirect_uri=' . urlencode($redirectUrl)
            . '&bg=' . $this->getConfigService()->getAuthIframeColor();

        if (!empty($registerData)) {
            $url .= '&registerdata=' . $registerData;
        }

        if (!empty($additionalParams)) {
            $url .= '&' . http_build_query($additionalParams);
        }

        return $url;
    }

    /**
     * @inheritDoc
     *
     * @return bool
     */
    protected function isAccessTokenRequired()
    {
        return $this->accessTokenRequired;
    }
}
