<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic;

use CleverReach\WordPress\IntegrationCore\BusinessLogic\DTO\RecipientDTO;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\AuthInfo;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\OrderItem;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\Recipient;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\SpecialTag;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Proxy\BaseProxy;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync\RegisterEventHandlerTask;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\Filter;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\Helper;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\Rule;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\Logger;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpBatchSizeTooBigException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\HttpResponse;

/**
 * Class Proxy
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic
 */
class Proxy extends BaseProxy
{
    /**
     * @var string
     */
    private $apiVersion;
    /**
     * @var string
     */
    private $accessToken;

    /**
     * Proxy constructor.
     */
    public function __construct()
    {
        $this->apiVersion = static::API_VERSION;
    }

    /**
     * Uploads order items to CleverReach.
     *
     * @param string $recipientEmail email of recipient for update
     * @param OrderItem[] $orderItems Order item that needs to be uploaded.
     * @param \DateTime $lastOrderDate Date of the last recipient's order.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function uploadOrderItems($recipientEmail, $orderItems, $lastOrderDate = null)
    {
        $formattedRecipientForUpdate = array(
            array(
                'email' => $recipientEmail,
                'tags' => array((string)SpecialTag::buyer()),
                'orders' => $this->formatOrdersForApiCall($orderItems),
                'global_attributes' => array(
                    'lastorderdate' => ($lastOrderDate !== null) ? date_format($lastOrderDate, 'Y-m-d') : ''
                ),
            )
        );

        $this->upsertPlus($formattedRecipientForUpdate);
    }

    /**
     * Exchanges old access token for new refresh and access tokens.
     *
     * @return AuthInfo
     *   Authentication information object.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     */
    public function exchangeToken()
    {
        $uri = 'debug/exchange.json';
        $response = $this->call('GET', $uri);
        $body = json_decode($response->getBody(), true);
        if (!isset($body['access_token'], $body['expires_in'], $body['refresh_token'])) {
            $this->logAndThrowHttpRequestException('Token exchange failed. Invalid response from CR.');
        }

        return new AuthInfo($body['access_token'], $body['expires_in'], $body['refresh_token']);
    }

    /**
     * Registers event handler for webhooks and returns call token that will be used
     * as header information for all webhooks.
     *
     * @param array $eventParameters Associative array with URL and verification token. Array keys:
     *   * url: event handler URL
     *   * event: entity name of events
     *   * verify: token for URL verification
     *
     * @return string
     *   If registration succeeds, returns call token.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     */
    public function registerEventHandler($eventParameters)
    {
        $e = null;
        $response = null;
        try {
            $eventParameters['condition'] = (string)$this->getConfigService()->getIntegrationId();
            $response = $this->callWithoutApiVersion('POST', 'hooks/eventhook', $eventParameters);
        } catch (HttpAuthenticationException $e) {
        } catch (HttpCommunicationException $e) {
        } catch (HttpRequestException $e) {
        }

        if ($e !== null) {
            $response = $this->tryToRegisterNewEventHandler($eventParameters);
            if ($response === null) {
                return '';
            }
        }

        $results = json_decode($response->getBody(), true);
        if (!array_key_exists('call_token', $results) || empty($results['success'])) {
            $this->logAndThrowHttpRequestException('Registration of webhook failed. Invalid response body from CR.');
        }

        return $results['call_token'];
    }

    /**
     * Tries to delete event handler and re-register it.
     *
     * @param array $eventParameters Associative array with URL and verification token. Array keys:
     *   * url: event handler URL
     *   * event: entity name of events
     *   * verify: token for URL verification
     *
     * @return \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\HttpResponse|null
     *   HTTP response of a call if successful; otherwise, null.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     */
    protected function tryToRegisterNewEventHandler($eventParameters)
    {
        try {
            $this->deleteEventHandler($eventParameters['event']);

            return $this->callWithoutApiVersion('POST', 'hooks/eventhook', $eventParameters);
        } catch (HttpAuthenticationException $e) {
        } catch (HttpCommunicationException $e) {
        } catch (HttpRequestException $e) {
        }

        Logger::logError('Cannot register CleverReach event hook! Error: ' . $e->getMessage());

        return null;
    }

    /**
     * Deletes webhooks for Recipient events.
     *
     * @return bool
     *   True if call succeeded; otherwise, false.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     */
    public function deleteReceiverEvent()
    {
        return $this->deleteEventHandler(RegisterEventHandlerTask::RECEIVER_EVENT);
    }

    /**
     * Deletes webhooks for form events.
     *
     * @return bool
     *   True if call succeeded; otherwise, false.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     */
    public function deleteFormEvent()
    {
        return $this->deleteEventHandler(RegisterEventHandlerTask::FORM_EVENT);
    }

    /**
     * Removes event handler.
     *
     * @param string $eventName Name of the event.
     *
     * @return bool
     *   True if call succeeded; otherwise, false.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     */
    protected function deleteEventHandler($eventName)
    {
        $endpoint = 'hooks/eventhook/' . $eventName . '?condition=' . $this->getConfigService()->getIntegrationId();

        try {
            $response = $this->callWithoutApiVersion('DELETE', $endpoint);

            return $response->isSuccessful();
        } catch (HttpAuthenticationException $e) {
        } catch (HttpCommunicationException $e) {
        } catch (HttpRequestException $e) {
        }

        return false;
    }

    /**
     * Calls CleverReach API without version in base url
     *
     * @param string $method HTTP method.
     * @param string $endpoint Endpoint URL.
     * @param array $body Associative array with request data that will be sent as body or query string.
     *
     * @return HttpResponse
     *   HTTP response of a call.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    protected function callWithoutApiVersion($method, $endpoint, array $body = array())
    {
        $apiVersion = $this->apiVersion;
        $this->apiVersion = '';
        $response = $this->call($method, $endpoint, $body);
        $this->apiVersion = $apiVersion;

        return $response;
    }

    /**
     * Returns recipient from CleverReach
     *
     * @param string $groupId List Id
     * @param string $poolId Recipient email or ID
     *
     * @return Recipient
     *   Recipient object with data from CleverReach.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     */
    public function getRecipient($groupId, $poolId)
    {
        $sourceRecipient = $this->getRecipientAsArray($groupId, $poolId);
        if (empty($sourceRecipient['email'])) {
            $this->logAndThrowHttpRequestException(
                'Invalid response body from CleverReach: empty email field on recipient.'
            );
        }

        return Helper::createRecipientEntity($sourceRecipient, $this->getConfigService()->getIntegrationName());
    }

    /**
     * Returns recipient from CleverReach as array
     *
     * @param string $groupId CleverReach group ID.
     * @param string $poolId Email or recipient ID.
     *
     * @return array
     *   Recipient fetched from CleverReach.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function getRecipientAsArray($groupId, $poolId)
    {
        $response = $this->call('GET', 'groups.json/' . $groupId . '/receivers/' . $poolId);
        $results = json_decode($response->getBody(), true);
        if (empty($results['email'])) {
            return array();
        }

        return $results;
    }

    /**
     * Deletes recipient from CleverReach.
     *
     * @param string $groupId CleverReach group ID.
     * @param string $poolId Email or recipient ID.
     *
     * @return bool
     *   True if request succeeded; otherwise, false.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function deleteRecipient($groupId, $poolId)
    {
        $response = $this->call('DELETE', 'groups.json/' . $groupId . '/receivers/' . $poolId);
        if ($response->getBody() === 'true') {
            return true;
        }

        return $this->processDeletingFailedResponse($response, 'recipient');
    }

    /**
     * Check if group with given name exists.
     *
     * @param string $serviceName Group name (integration list name).
     *
     * @return int|null
     *   If found returns group ID, otherwise null.
     *
     * @throws \InvalidArgumentException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     */
    public function getGroupId($serviceName)
    {
        $response = $this->call('GET', 'groups.json');
        $allGroups = json_decode($response->getBody(), true);

        if ($allGroups !== null && is_array($allGroups)) {
            foreach ($allGroups as $group) {
                if ($group['name'] === $serviceName) {
                    return $group['id'];
                }
            }
        }

        return null;
    }

    /**
     * Returns group by provided ID.
     *
     * @return array
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function getGroups()
    {
        $response = $this->call('GET', 'groups.json');

        return json_decode($response->getBody(), true);
    }

    /**
     * Creates new group on CleverReach.
     *
     * @param string $name Group name.
     *
     * @return int Group ID on CleverReach.
     *
     * @throws \InvalidArgumentException
     *
     * @return int
     *   Group ID on CleverReach.
     *
     * @throws \InvalidArgumentException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     */
    public function createGroup($name)
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('Argument null not allowed');
        }

        $argument = array('name' => $name);
        $response = $this->call('POST', 'groups.json', $argument);
        $result = json_decode($response->getBody(), true);
        if (!isset($result['id'])) {
            $this->logAndThrowHttpRequestException('Creation of new group failed. Invalid response body from CR.');
        }

        return $result['id'];
    }

    /**
     * Updates group name on CleverReach.
     *
     * @param int $id Group ID on CleverReach.
     * @param string $name New group name.
     *
     * @return bool
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function updateGroupName($id, $name)
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('Argument null not allowed');
        }

        $argument = array('name' => $name);

        $response = $this->call('PUT', "groups.json/{$id}", $argument);

        return $response->getStatus() === 200;
    }

    /**
     * Creates new filter on CleverReach.
     *
     * @param Filter $filter Filter that needs to be created.
     * @param int $integrationID CleverReach integration ID.
     *
     * @return array
     *   Associative array that contains ID of created filter.
     *
     * @throws \InvalidArgumentException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     */
    public function createFilter(Filter $filter, $integrationID)
    {
        if (!is_numeric($integrationID)) {
            throw new \InvalidArgumentException('Integration ID must be numeric!');
        }

        $response = $this->call('POST', 'groups.json/' . $integrationID . '/filters', $filter->toArray());
        $result = json_decode($response->getBody(), true);
        if (!isset($result['id'])) {
            $this->logAndThrowHttpRequestException(
                'Creation of new filter failed. Invalid response body from CR.'
            );
        }

        return $result;
    }

    /**
     * Delete filter in CleverReach.
     *
     * @param int $filterID Unique identifier for filter.
     * @param int $integrationID CleverReach integration ID.
     *
     * @return bool
     *   On success return true, otherwise false.
     *
     * @throws \InvalidArgumentException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     */
    public function deleteFilter($filterID, $integrationID)
    {
        if (!is_numeric($filterID) || !is_numeric($integrationID)) {
            throw new \InvalidArgumentException('Both arguments must be integers.');
        }

        $response = $this->call('DELETE', 'groups.json/' . $integrationID . '/filters/' . $filterID);

        if ($response->getBody() === 'true') {
            return true;
        }

        return $this->processDeletingFailedResponse($response, 'filter');
    }

    /**
     * Return all segments from CleverReach.
     *
     * @param int $integrationId CleverReach integration ID.
     *
     * @return Filter[]
     *   List of filter objects.
     *
     * @throws \InvalidArgumentException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     */
    public function getAllFilters($integrationId)
    {
        $response = $this->call('GET', 'groups.json/' . $integrationId . '/filters');
        $allSegments = json_decode($response->getBody(), true);

        return $this->formatAllFilters($allSegments);
    }

    /**
     * Converts array result fetched from API to filter objects.
     *
     * @param array|null $allSegments Segments retrieved from CleverReach.
     *
     * @return Filter[]
     *   List of filter objects.
     */
    private function formatAllFilters($allSegments)
    {
        $results = array();
        if (empty($allSegments)) {
            return $results;
        }

        foreach ($allSegments as $segment) {
            if (empty($segment['rules'])) {
                continue;
            }

            $rule = new Rule(
                $segment['rules'][0]['field'],
                $segment['rules'][0]['logic'],
                $segment['rules'][0]['condition']
            );

            $filter = new Filter($segment['name'], $rule);

            for ($i = 1, $iMax = count($segment['rules']); $i < $iMax; $i++) {
                $rule = new Rule(
                    $segment['rules'][$i]['field'],
                    $segment['rules'][$i]['logic'],
                    $segment['rules'][$i]['condition']
                );

                $filter->addRule($rule);
            }

            $filter->setId($segment['id']);
            $results[] = $filter;
        }

        return $results;
    }

    /**
     * Get all global attributes ids from CleverReach.
     *
     * @return array
     *   Associative array where key is attribute name and value is attribute ID.
     *
     * @throws \InvalidArgumentException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     */
    public function getAllGlobalAttributes()
    {
        $response = $this->call('GET', 'attributes.json');
        $globalAttributes = json_decode($response->getBody(), true);
        $globalAttributesIds = array();

        if ($globalAttributes !== null && is_array($globalAttributes)) {
            foreach ($globalAttributes as $globalAttribute) {
                $attributeKey = strtolower($globalAttribute['name']);
                $globalAttributesIds[$attributeKey] = $globalAttribute['id'];
            }
        }

        return $globalAttributesIds;
    }

    /**
     * Create global attribute in CleverReach.
     *
     * Request example:
     * array(
     *   "name" => "FirstName",
     *   "type" => "text",
     *   "description" => "Description",
     *   "preview_value" => "real name",
     *   "default_value" => "Bruce"
     * )
     *
     * @param array|null $attribute Attribute that needs to be created.
     *
     * @throws \InvalidArgumentException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     */
    public function createGlobalAttribute($attribute)
    {
        try {
            $response = $this->call('POST', 'attributes.json', $attribute);
            $result = json_decode($response->getBody(), true);
        } catch (HttpRequestException $ex) {
            // Conflict status code means product search endpoint is already created
            if ($ex->getCode() === static::HTTP_STATUS_CODE_CONFLICT) {
                Logger::logInfo('Global attribute: ' . $attribute['name'] . ' endpoint already exists on CR.');

                return;
            }

            throw $ex;
        }

        if (!isset($result['id'])) {
            $this->logAndThrowHttpRequestException(
                'Creation of global attribute "' . $attribute['name'] . '" failed. Invalid response body from CR.'
            );
        }
    }

    /**
     * Updates global attribute in CleverReach.
     *
     * Request example:
     * array(
     *   "type" => "text",
     *   "description" => "Description",
     *   "preview_value" => "real name"
     * )
     *
     * @param int $id Attribute ID.
     * @param array|null $attribute Attribute data to be updated.
     *
     * @throws \InvalidArgumentException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     */
    public function updateGlobalAttribute($id, $attribute)
    {
        $response = $this->call('PUT', 'attributes.json/' . $id, $attribute);
        $result = json_decode($response->getBody(), true);

        if (!isset($result['id'])) {
            $this->logAndThrowHttpRequestException(
                'Update of global attribute "' . $attribute['name'] . '" failed. Invalid response body from CR.'
            );
        }
    }

    /**
     * Register or update product search endpoint and return ID of registered content.
     *
     * Request data:
     * array(
     *   "name" => "My Shop name (http://myshop.com)",
     *   "url" => "http://myshop.com/myendpoint",
     *   "password" => "as243FF3"
     * )
     *
     * @param array|null $data Associative array with keys name, url and password.
     *
     * @return string
     *   ID of registered content.
     *
     * @throws \InvalidArgumentException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     */
    public function addOrUpdateProductSearch($data)
    {
        try {
            $response = $this->call('POST', 'mycontent.json', $data);
            $result = json_decode($response->getBody(), true);
            $result = !is_array($result) ? array() : $result;

            if (!array_key_exists('id', $result)) {
                $this->logAndThrowHttpRequestException(
                    'Registration/update of product search endpoint failed. Invalid response body from CR.'
                );
            }

            return $result['id'];
        } catch (HttpRequestException $ex) {
            // Conflict status code means product search endpoint is already created
            if ($ex->getCode() === static::HTTP_STATUS_CODE_CONFLICT) {
                Logger::logInfo('Product search endpoint already exists on CR.');

                return $this->resolveProductSearchEndpointConflict($data);
            }

            throw $ex;
        }
    }

    /**
     * Removes current endpoint and registers new one.
     *
     * Request data:
     * array(
     *   "name" => "My Shop name (http://myshop.com)",
     *   "url" => "http://myshop.com/myendpoint",
     *   "password" => "as243FF3"
     * )
     *
     * @param array|null $data Associative array with keys name, url and password.
     *
     * @return string ID of registered content.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    private function resolveProductSearchEndpointConflict($data)
    {
        $response = $this->call('GET', 'mycontent.json');
        $result = json_decode($response->getBody(), true);
        $result = !is_array($result) ? array() : $result;

        $message = 'Registration/update of product search endpoint failed. Invalid response body from CR.';
        if (empty($result)) {
            $this->logAndThrowHttpRequestException($message);
        }

        foreach ($result as $content) {
            if ($content['name'] === $data['name'] || $content['url'] === $data['url']) {
                $this->deleteProductSearchEndpoint($content['id']);

                return $this->addOrUpdateProductSearch($data);
            }
        }

        $this->logAndThrowHttpRequestException($message);

        return null;
    }

    /**
     * Delete product search endpoint.
     *
     * @param string $id Content ID.
     *
     * @return bool
     *   On success return true, otherwise false.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function deleteProductSearchEndpoint($id)
    {
        try {
            $this->call('DELETE', 'mycontent.json/' . $id);
        } catch (HttpRequestException $ex) {
            if ($ex->getCode() !== 404) {
                return false;
            }
        }

        return true;
    }

    /**
     * Does mass update by sending the whole batch to CleverReach.
     *
     * @param array $recipients Array of objects @see \CleverReach\WordPress\IntegrationCore\BusinessLogic\DTO\RecipientDTO
     *
     * @throws \InvalidArgumentException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpBatchSizeTooBigException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function recipientsMassUpdate(array $recipients)
    {
        $formattedRecipients = $this->prepareRecipientsForApiCall($recipients);

        try {
            $response = $this->upsertPlus($formattedRecipients);
            $this->checkMassUpdateRequestSuccess($response, $recipients);
        } catch (HttpRequestException $ex) {
            $batchSize = count($recipients);
            $this->checkMassUpdateBatchSizeValidity($ex, $batchSize);

            throw $ex;
        }
    }

    /**
     * Update newsletter status for passed recipient emails.
     *
     * @param array|null $emails Array of recipient emails.
     *
     * @throws \InvalidArgumentException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @deprecated
     */
    public function updateNewsletterStatus($emails)
    {
        $receiversForUpdate = $this->getReceiversForNewsletterStatusUpdate($emails);
        $deactivatedReceivers = $this->upsertPlus($receiversForUpdate);

        $this->checkUpdateNewsletterStatusRecipientsResponse($deactivatedReceivers, $emails);
    }

    /**
     * Deactivates recipients.
     *
     * @param Recipient[] $recipients Array of recipient entities.
     *
     * @throws \InvalidArgumentException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function deactivateRecipients($recipients)
    {
        $receiversForDeactivation = $this->getReceiversForDeactivation($recipients);

        if (!empty($receiversForDeactivation)) {
            $deactivatedReceivers = $this->upsertPlus($receiversForDeactivation);

            $this->checkDeactivateRecipientsResponse($deactivatedReceivers, $receiversForDeactivation);
        }
    }

    /**
     * Prepares all recipients in a format needed for API call.
     *
     * @param RecipientDTO[] $recipientDTOs Array of objects @see \CleverReach\WordPress\IntegrationCore\BusinessLogic\DTO\RecipientDTO
     *
     * @return array
     *   Array of recipients in CleverReach API format.
     */
    private function prepareRecipientsForApiCall(array $recipientDTOs)
    {
        $formattedRecipients = array();

        /** @var RecipientDTO $recipientDTO */
        foreach ($recipientDTOs as $recipientDTO) {
            /** @var Recipient $recipientEntity */
            $recipientEntity = $recipientDTO->getRecipientEntity();
            $registered = $recipientEntity->getRegistered();

            $formattedRecipient = array(
                'email' => $recipientEntity->getEmail(),
                'registered' => $registered !== null ? $registered->getTimestamp() : strtotime('01-01-2010'),
                'source' => $recipientEntity->getSource(),
                'attributes' => $this->formatAttributesForApiCall($recipientEntity->getAttributes()),
                'global_attributes' => $this->formatGlobalAttributesForApiCall($recipientEntity),
                'tags' => $this->formatAndMergeExistingAndTagsForDelete(
                    $recipientEntity->getTags(),
                    $recipientDTO->getTagsForDelete()
                ),
            );

            if ($recipientDTO->shouldActivatedFieldBeSent()) {
                // We use activated timestamp for handling both activation and
                // deactivation. When activated timestamp is set to 0, recipient
                // will be inactive in CleverReach. Setting activated to value > 0
                // will reactivate recipient in CleverReach but only if recipient
                // was not deactivated withing CleverReach system.
                if ($recipientEntity->isActive()) {
                    $formattedRecipient['activated'] = $recipientEntity->getActivated()->getTimestamp();
                } else {
                    $formattedRecipient['activated'] = 0;
                }
            }

            if ($recipientDTO->shouldDeactivatedFieldBeSent()) {
                $formattedRecipient['deactivated'] = $recipientEntity->getDeactivated()->getTimestamp();
            }

            if ($recipientDTO->isIncludeOrdersActivated()) {
                $formattedRecipient['orders'] = $this->formatOrdersForApiCall($recipientEntity->getOrders());
            }

            $formattedRecipients[] = $formattedRecipient;
        }

        return $formattedRecipients;
    }

    /**
     * Formats attributes for CleverReach API call.
     *
     * @param array|null $rawAttributes Associative array ['attribute_name' => 'attribute_value'].
     *
     * @return string
     *   CleverReach API format attr1:val1,attr2:val2
     */
    private function formatAttributesForApiCall($rawAttributes)
    {
        $formattedAttributes = array();

        foreach ($rawAttributes as $key => $value) {
            $formattedAttributes[] = $key . ':' . $value;
        }

        return implode(',', $formattedAttributes);
    }

    /**
     * Formats recipient global attributes to appropriate format for sending.
     *
     * @param Recipient $recipient Recipient object.
     *
     * @return array
     *   CleverReach API format for recipient global attributes.
     */
    private function formatGlobalAttributesForApiCall(Recipient $recipient)
    {
        $newsletterSubscription = $recipient->getNewsletterSubscription() ? 'yes' : 'no';
        $birthday = $recipient->getBirthday();
        $lastOrderDate = $recipient->getLastOrderDate();

        $formattedGlobalAttributes = array(
            'firstname' => $recipient->getFirstName(),
            'lastname' => $recipient->getLastName(),
            'salutation' => $recipient->getSalutation(),
            'title' => $recipient->getTitle(),
            'street' => $recipient->getStreet(),
            'zip' => $recipient->getZip(),
            'city' => $recipient->getCity(),
            'company' => $recipient->getCompany(),
            'state' => $recipient->getState(),
            'country' => $recipient->getCountry(),
            'birthday' => ($birthday !== null) ? date_format($birthday, 'Y-m-d') : '',
            'lastorderdate' => ($lastOrderDate !== null) ? date_format($lastOrderDate, 'Y-m-d') : '',
            'phone' => $recipient->getPhone(),
            'shop' => $recipient->getShop(),
            'customernumber' => (string)$recipient->getCustomerNumber(),
            'language' => $recipient->getLanguage(),
            'newsletter' => $newsletterSubscription,
        );

        Helper::removeUnsupportedAttributes($recipient, $formattedGlobalAttributes);

        return $formattedGlobalAttributes;
    }

    /**
     * Formats list of order objects to CleverReach API format.
     *
     * @param OrderItem[] $orders List of order objects.
     *
     * @return array
     *   CleverReach API format for orders.
     */
    private function formatOrdersForApiCall(array $orders)
    {
        $formattedOrders = array();

        /** @var OrderItem $order */
        foreach ($orders as $order) {
            $formattedOrders[] = $this->getOrderFormattedForRequest($order);
        }

        return $formattedOrders;
    }

    /**
     * Formats single order object to CleverReach API format.
     *
     * @param OrderItem|null $orderItem Order object.
     *
     * @return array
     *   CleverReach API format for single order.
     */
    private function getOrderFormattedForRequest($orderItem)
    {
        $formattedOrder = array();
        $formattedOrder['order_id'] = $orderItem->getOrderId();
        $formattedOrder['product_id'] = (string)$orderItem->getProductId();
        $formattedOrder['product'] = $orderItem->getProduct();

        $dateOfOrder = $orderItem->getStamp();
        $formattedOrder['stamp'] = $dateOfOrder !== null ? $dateOfOrder->getTimestamp() : '';

        $formattedOrder['price'] = $orderItem->getPrice();
        $formattedOrder['currency'] = $orderItem->getCurrency();
        $formattedOrder['quantity'] = $orderItem->getAmount();
        $formattedOrder['product_source'] = $orderItem->getProductSource();
        $formattedOrder['brand'] = $orderItem->getBrand();
        $formattedOrder['product_category'] = implode(',', $orderItem->getProductCategory());
        $formattedOrder['attributes'] = $this->formatAttributesForApiCall($orderItem->getAttributes());

        $mailingId = $orderItem->getMailingId();

        if ($mailingId !== null) {
            $formattedOrder['mailing_id'] = $mailingId;
        }

        return $formattedOrder;
    }

    /**
     * Format and merge tags that already exist with tags for delete.
     *
     * @param \CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\TagCollection|null $recipientTags Existing tags.
     * @param \CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\TagCollection|null $tagsForDelete Tags for delete.
     *
     * @return array
     *   Array representation of tag collection.
     */
    private function formatAndMergeExistingAndTagsForDelete($recipientTags, $tagsForDelete)
    {
        $tagsForDelete->markDeleted();

        return $recipientTags->merge($tagsForDelete)->toStringArray();
    }

    /**
     * Validates if update was successful.
     *
     * @param HttpResponse|null $response Http response object.
     * @param array|null $recipientDTOs List of recipient DTOs.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     */
    private function checkMassUpdateRequestSuccess($response, $recipientDTOs)
    {
        $responseBody = json_decode($response->getBody(), true);
        if ($responseBody === false) {
            $firstRecipient = !empty($recipientDTOs[0]) ? $recipientDTOs[0]->getRecipientEntity()->getEmail() : '';
            $this->logAndThrowHttpRequestException(
                'Upsert of recipients not done for batch starting from recipient id ' . $firstRecipient . '. '
                . 'Batch size is ' . count($recipientDTOs) . '.'
            );
        }
    }

    /**
     * Checks if request is successful for passed batch size.
     *
     * @param \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException $ex Exception object.
     * @param int $batchSize Test batch size.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpBatchSizeTooBigException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpBatchSizeTooBigException
     */
    private function checkMassUpdateBatchSizeValidity($ex, $batchSize)
    {
        if ($ex->getCode() === static::HTTP_STATUS_CODE_NOT_SUCCESSFUL_FOR_DEFINED_BATCH_SIZE) {
            Logger::logInfo('Upsert of recipients not done for batch size ' . $batchSize . '.');

            throw new HttpBatchSizeTooBigException(
                'Batch size ' . $batchSize . ' too big for upsert'
            );
        }
    }

    /**
     * Get user information from CleverReach.
     *
     * @param string $accessToken User access token.
     *
     * @return array
     *   Associative array that contains CleverReach user information.
     *
     * @throws \InvalidArgumentException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     */
    public function getUserInfo($accessToken)
    {
        try {
            $this->accessToken = $accessToken;
            $response = $this->call('GET', 'debug/whoami.json');
            $this->accessToken = null;
            $userInfo = json_decode($response->getBody(), true);
            if (!isset($userInfo['id'])) {
                $this->logAndThrowHttpRequestException('Get user information failed. Invalid response body from CR.');
            }
        } catch (HttpAuthenticationException $ex) {
            // Invalid access token
            return array();
        }

        return $userInfo;
    }

    /**
     * Gets CleverReach recipient status format.
     *
     * @param array|null $emails List of recipient emails for update.
     *
     * @return array
     *   CleverReach format for newsletter status update.
     */
    private function getReceiversForNewsletterStatusUpdate($emails)
    {
        $receivers = array();
        foreach ($emails as $email) {
            $receivers[] = array(
                'email' => $email,
                'global_attributes' => array(
                    'newsletter' => 'no',
                ),
            );
        }

        return $receivers;
    }

    /**
     * Validates response for newsletter status update.
     *
     * @param HttpResponse|null $response Http response object.
     * @param array|null $emails List of recipient emails for update.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     */
    private function checkUpdateNewsletterStatusRecipientsResponse($response, $emails)
    {
        $responseBody = json_decode($response->getBody(), true);
        if (empty($responseBody) || !is_array($responseBody)) {
            $this->logAndThrowHttpRequestException(
                'Update newsletter status of recipients with emails: ' . implode(',', $emails)
                . ' failed. Invalid response body from CR.'
            );
        }
    }

    /**
     * Prepares request data for deactivating recipients.
     *
     * @param Recipient[] $recipients List of recipients.
     *
     * @return array
     *   CleverReach API format for recipient deactivation.
     */
    private function getReceiversForDeactivation($recipients)
    {
        $receivers = array();
        foreach ($recipients as $recipient) {
            $receivers[] = array(
                'email' => $recipient->getEmail(),
                'activated' => 0,
                'registered' => $recipient->getRegistered()->getTimestamp(),
                'tags' => $recipient->getTags()->toStringArray(),
                'global_attributes' => array(
                    'newsletter' => 'no',
                ),
            );
        }

        return $receivers;
    }

    /**
     * Checks if operation executed successfully.
     *
     * @param HttpResponse|null $response Http response object.
     * @param array|null $recipients List of recipients.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     */
    private function checkDeactivateRecipientsResponse($response, $recipients)
    {
        $responseBody = json_decode($response->getBody(), true);
        if ((empty($responseBody) || !is_array($responseBody)) && $response->getStatus() !== 200) {
            $emails = array();
            foreach ($recipients as $recipient) {
                if (!empty($recipient['email'])) {
                    $emails[] = $recipient['email'];
                }
            }

            $this->logAndThrowHttpRequestException(
                'Deactivation of recipients with emails: ' . implode(',', $emails)
                . ' failed. Invalid response body from CR.'
            );
        }
    }

    /**
     * Calls upsert method for given receivers and returns updated data.
     *
     * @param array|null $receivers CleverReach receivers.
     *
     * @return \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\HttpResponse
     *   Http response object.
     *
     * @throws \InvalidArgumentException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     */
    private function upsertPlus($receivers)
    {
        return $this->call(
            'POST',
            'groups.json/' . $this->getConfigService()->getIntegrationId() . '/receivers/upsertplus',
            $receivers
        );
    }

    /**
     * Process deleting failed response.
     *
     * @param HttpResponse|null $response Http response.
     * @param string $entity Entity code.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     */
    private function processDeletingFailedResponse($response, $entity)
    {
        // invalid API response
        $response = json_decode($response->getBody(), true);

        // default error message
        $errorMessage = 'Deleting ' . $entity . ' failed. Invalid response body from CR.';

        if (!empty($response['error']['message'])) {
            $errorMessage .= ' Response message: ' . $response['error']['message'];
        }

        $errorCode = $response['error']['code'] ?: static::HTTP_STATUS_CODE_DEFAULT;

        $this->logAndThrowHttpRequestException($errorMessage, $errorCode);
    }

    /**
     * Gets CleverReach REST API base url.
     *
     * @return string
     *   Base url.
     */
    protected function getApiVersion()
    {
        return $this->apiVersion;
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
        return ($this->accessToken !== null) ? $this->accessToken : parent::getValidAccessToken();
    }
}
