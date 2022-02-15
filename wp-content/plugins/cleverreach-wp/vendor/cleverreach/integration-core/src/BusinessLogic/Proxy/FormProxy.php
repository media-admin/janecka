<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Proxy;

use CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException;

/**
 * Class FormProxy
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Proxy
 */
class FormProxy extends BaseProxy
{
    const CLASS_NAME = __CLASS__;

    const BASE_ENDPOINT = 'forms.json';

    /**
     * Creates CleverReach form
     *
     * @param int $groupId Integration identifier on CleverReach
     * @param string $formName
     * @param string $formTemplate
     *
     * @return array
     *   ['id' => id_of_created_form, 'success' => true]
     *
     * @throws InvalidConfigurationException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws RefreshTokenExpiredException
     */
    public function createForm($groupId, $formName, $formTemplate = 'default')
    {
        $endpoint = self::BASE_ENDPOINT . "/{$groupId}/createfromtemplate/{$formTemplate}";
        $body = array(
            'name' => $formName,
            'title' => $formName,
        );

        $response = $this->call('POST', $endpoint, $body);
        $results = json_decode($response->getBody(), true);

        return !empty($results['success']) ? $results : array();
    }

    /**
     * Returns all existing forms
     *
     * @return array
     *
     * @throws InvalidConfigurationException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws RefreshTokenExpiredException
     */
    public function getFormList()
    {
        $endpoint = self::BASE_ENDPOINT;
        $response = $this->call('GET', $endpoint);
        $results = json_decode($response->getBody(), true);

        return is_array($results) ? $results : array();
    }

    /**
     * Returns form HTML filtered by form id
     *
     * @param int $formId
     *
     * @return string html form
     *
     * @throws InvalidConfigurationException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws RefreshTokenExpiredException
     */
    public function getFormById($formId)
    {
        $endpoint = self::BASE_ENDPOINT . "/{$formId}/code?badget=false&embedded=true";
        $response = $this->call('GET', $endpoint);

        return json_decode($response->getBody(), false);
    }

    /**
     * Performs proxy call to send confirmation email to the recipient.
     *
     * @param string $formId CleverReach form ID.
     * @param string $email User email.
     * @param array $data DOI data (user IP, referer and user agent)
     *
     * @return mixed
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function sendDoiEmail($formId, $email, $data)
    {
        if (!array_key_exists('ip', $data)
            || !array_key_exists('referer', $data)
            || !array_key_exists('agent', $data)
        ) {
            throw new \InvalidArgumentException('Required DOI data argument missing');
        }

        $endpoint = self::BASE_ENDPOINT . "/{$formId}/send/activate";
        $params = array(
            'email' => $email,
            'doidata' => array(
                'user_ip' => $data['ip'],
                'referer' => $data['referer'],
                'user_agent' => $data['agent'],
            ),
        );

        $response = $this->call('POST', $endpoint, $params);

        return json_decode($response->getBody(), false);
    }
}
