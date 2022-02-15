<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync;

use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\OrderItem;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Interfaces\OrderItems;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Serializer;

/**
 * Class CampaignOrderSync
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync
 */
class CampaignOrderSync extends BaseSyncTask
{
    const INITIAL_PROGRESS_PERCENT = 10;

    /**
     * Associative array [item_id => mailing_id].
     *
     * @var array
     */
    private $orderItemsIdMailingIdMap;

    /**
     * CampaignOrderSync constructor.
     *
     * @param array $orderItemsIdMailingIdMap Associative array where Order item ID is key and mailing id is value.
     */
    public function __construct(array $orderItemsIdMailingIdMap)
    {
        $this->orderItemsIdMailingIdMap = $orderItemsIdMailingIdMap;
    }

    /**
     * Transforms array into entity.
     *
     * @param array $array
     *
     * @return \CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Serializable
     */
    public static function fromArray($array)
    {
        return new static($array['orderItemsIdMailingIdMap']);
    }

    /**
     * String representation of object
     *
     * @inheritdoc
     */
    public function serialize()
    {
        return Serializer::serialize($this->orderItemsIdMailingIdMap);
    }

    /**
     * Constructs the object.
     *
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $unserialized = Serializer::unserialize($serialized);

        if (isset($unserialized['orderItemsIdMailingIdMap'])) {
            $this->orderItemsIdMailingIdMap = $unserialized['orderItemsIdMailingIdMap'];
        } else {
            $this->orderItemsIdMailingIdMap = $unserialized;
        }
    }

    /**
     * Transforms entity to array.
     *
     * @return array
     */
    public function toArray()
    {
        return array('orderItemsIdMailingIdMap' => $this->orderItemsIdMailingIdMap);
    }

    /**
     * Runs task execution.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function execute()
    {
        $this->reportProgress(self::INITIAL_PROGRESS_PERCENT);
        /** @var OrderItems $orderItemService */
        $orderItemService = ServiceRegister::getService(OrderItems::CLASS_NAME);
        $orderItems = $orderItemService->getOrderItems(array_keys($this->orderItemsIdMailingIdMap));

        if (!empty($orderItems)) {
            $this->reportAlive();
            $this->setMailingIds($orderItems);
            $this->reportProgress(50);
            $this->updateRecipientWithOrderItemsInformation($orderItems);
        }

        $this->reportProgress(100);
    }

    /**
     * Iterate through passed OrderItems and sets mailing ID.
     *
     * @param OrderItem[]|null $orderItems array of Order item object.
     */
    private function setMailingIds($orderItems)
    {
        foreach ($orderItems as $orderItem) {
            $mailingId = $this->orderItemsIdMailingIdMap[$orderItem->getOrderItemId()];

            if ($mailingId !== null) {
                $orderItem->setMailingId($mailingId);
            }
        }
    }

    /**
     * Update recipient with with purchase information.
     *
     * @param OrderItem[] $orderItems array of OrderItem objects fetched from OrderItemsService
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    private function updateRecipientWithOrderItemsInformation($orderItems)
    {
        $firstItem = $this->getFirstOrderItem($orderItems);
        $recipientEmail = $firstItem ? $firstItem->getRecipientEmail() : null;
        $lastOrderDate = $firstItem ? $firstItem->getStamp() : null;

        $this->getProxy()->uploadOrderItems($recipientEmail, $orderItems, $lastOrderDate);
    }

    /**
     * Returns first item from array
     *
     * @param OrderItem[] $orderItems array of OrderItem objects fetched from OrderItemsService
     *
     * @return OrderItem|null
     */
    private function getFirstOrderItem($orderItems)
    {
        foreach ($orderItems as $orderItem) {
            return $orderItem;
        }

        return null;
    }
}
