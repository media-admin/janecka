<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Proxy;

use CleverReach\WordPress\IntegrationCore\BusinessLogic\Surveys\SurveyType;

class SurveyProxy extends BaseProxy
{
    const CLASS_NAME = __CLASS__;

    const API_VERSION = null;

    /**
     * Array of response codes for which errors should not be logged.
     *
     * @var array
     */
    private $disableLogCodes = array();

    /**
     * Returns available poll if exists.
     *
     * @param string $type Survey type.
     * @param string $lang Language in which the survey should be displayed.
     * @param bool $shouldIncludeCustomerId Whether to add customer ID query parameter or not.
     *
     * @return array
     */
    public function get($type, $lang = '', $shouldIncludeCustomerId = true)
    {
        if (!in_array(
            $type,
            array(
                SurveyType::PLUGIN_INSTALLED,
                SurveyType::INITIAL_SYNC_FINISHED,
                SurveyType::FIRST_FORM_USED,
                SurveyType::PERIODIC,
            ),
            true
        )) {
            return array();
        }

        if (empty($lang)) {
            $lang = $this->getConfigService()->getLanguage();
        }

        $queryParams = array(
            'user_id' => 1,
            'url' => '/' . strtolower($this->getConfigService()->getIntegrationName()) . '/' . $type,
            'lang' => $lang,
        );

        if ($shouldIncludeCustomerId) {
            $customerId = $this->getConfigService()->getUserAccountId();
            if (!empty($customerId)) {
                $queryParams['customer_id'] = $customerId;
            }
        }

        $uri = 'poll/xss?' . http_build_query($queryParams);
        $this->disableLogCodes = array(303, 404);

        try {
            $response = $this->call('GET', $uri);
        } catch (\Exception $e) {
            return array();
        }

        $results = json_decode($response->getBody(), true);

        if (empty($results['meta']['id'])) {
            return array();
        }

        $results['lang'] = $lang;
        $results['customer_id'] = $this->getConfigService()->getUserAccountId();

        return $results;
    }

    /**
     * Responds to a poll.
     *
     * @param string $token Token retrieved on requesting poll.
     * @param array $body Request body.
     * @param string $lang Language in which the survey should be displayed.
     * @param bool $shouldIncludeCustomerId Whether to add customer ID query parameter or not.
     *
     * @return int Response status code.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function post($token, $body, $lang = '', $shouldIncludeCustomerId = true)
    {
        $uri = 'poll/xss?' . http_build_query(array('token' => $token));

        if (empty($lang)) {
            $lang = $this->getConfigService()->getLanguage();
        }

        $params = array(
            'poll' => $body['pollId'],
            'language' => $lang,
            'user_id' => 1,
        );

        if (isset($body['result'])) {
            $params['result'] = $body['result'];
        }

        if (isset($body['comment'])) {
            $params['freetext'] = $body['comment'];
        }

        if ($shouldIncludeCustomerId) {
            $customerId = $this->getConfigService()->getUserAccountId();
            if (!empty($customerId)) {
                $params['customer_id'] = $customerId;
                $params['attributes'] = array(
                    'ID' => $customerId,
                    'userId' => $customerId,
                );
            }
        }

        $response = $this->call('POST', $uri, $params);

        return $response->getStatus();
    }

    /**
     * Ignores a poll.
     *
     * @param string $token Token retrieved on requesting poll.
     * @param string $pollId Poll ID.
     * @param string $customerId Customer ID.
     * @param bool $shouldIncludeCustomerId Whether to add customer ID query parameter or not.
     *
     * @return int Response status code.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function ignore($token, $pollId, $customerId = '', $shouldIncludeCustomerId = true)
    {
        $uri = 'poll/xss/' . $pollId . '/ignore';

        if ($shouldIncludeCustomerId && !empty($customerId)) {
            $uri .= '/' . $customerId;
        }

        $uri .= '?' . http_build_query(array('token' => $token));

        $response = $this->call('POST', $uri);

        return $response->getStatus();
    }

    /**
     * Returns true if access token is required for call method, false otherwise
     *
     * @return bool
     */
    protected function isAccessTokenRequired()
    {
        return false;
    }

    /**
     * Check if status code should be handled as error.
     *
     * @param int $httpCode HTTP status code
     *
     * @return bool
     */
    protected function isErrorCode($httpCode)
    {
        if (in_array($httpCode, $this->disableLogCodes, true)) {
            return false;
        }

        return parent::isErrorCode($httpCode);
    }
}
