<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Interfaces;

use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\OrderItem;

/**
 * Interface OrderItems
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Interfaces
 */
interface OrderItems
{
    const CLASS_NAME = __CLASS__;

    /**
     * Gets order items by passed IDs.
     *
     * @param string[]|null $orderItemsIds Array of order item IDs that needs to be fetched.
     *
     * @return OrderItem[]
     *   Array of OrderItems that matches passed IDs.
     */
    public function getOrderItems($orderItemsIds);
}
