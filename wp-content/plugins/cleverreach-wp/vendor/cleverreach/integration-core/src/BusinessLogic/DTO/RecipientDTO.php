<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\DTO;

use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\Recipient;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\TagCollection;

/**
 * Class RecipientDTO
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\DTO
 */
class RecipientDTO
{
    /**
     * Recipient entity object.
     *
     * @var Recipient
     */
    private $recipientEntity;
    /**
     * Collection of tags for delete.
     *
     * @var TagCollection
     */
    private $tagsForDelete;
    /**
     * Flag that indicates whether orders should be sent or not.
     *
     * @var bool
     */
    private $includeOrdersActivated;
    /**
     * Flag that indicates whether activated field should sent or not.
     *
     * @var bool
     */
    private $activatedFieldForSending;
    /**
     * Flag that indicates whether deactivated field should sent or not.
     *
     * @var bool
     */
    private $deactivatedFieldForSending;

    /**
     * RecipientDTO constructor.
     *
     * @param Recipient|null $recipientEntity Recipient entity object
     * @param TagCollection|null $tagsForDelete Collection of tags for delete.
     * @param bool $shouldIncludeOrders Flag that indicates whether orders should sent or not.
     * @param bool $shouldSendActivated Flag that indicates whether activated field should sent or not.
     * @param bool $shouldSendDeactivated Flag that indicates whether deactivated field should sent or not.
     */
    public function __construct(
        $recipientEntity,
        $tagsForDelete,
        $shouldIncludeOrders,
        $shouldSendActivated,
        $shouldSendDeactivated
    ) {
        $this->recipientEntity = $recipientEntity;
        $this->tagsForDelete = $tagsForDelete;
        $this->includeOrdersActivated = $shouldIncludeOrders;
        $this->activatedFieldForSending = $shouldSendActivated;
        $this->deactivatedFieldForSending = $shouldSendDeactivated;
    }

    /**
     * Gets recipient entity.
     *
     * @return Recipient
     *   Recipient entity.
     */
    public function getRecipientEntity()
    {
        return $this->recipientEntity;
    }

    /**
     * Gets tags for delete.
     *
     * @return TagCollection
     *   TagCollection entity.
     */
    public function getTagsForDelete()
    {
        return $this->tagsForDelete;
    }

    /**
     * Get flag that indicates whether orders should sent or not.
     *
     * @return boolean
     *   Returns true when orders should be sent, otherwise false.
     */
    public function isIncludeOrdersActivated()
    {
        return $this->includeOrdersActivated;
    }

    /**
     * Set flag that indicates whether orders should sent or not.
     *
     * @param boolean $shouldIncludeOrders Flag that indicates whether orders should sent or not.
     */
    public function setIncludeOrdersActivated($shouldIncludeOrders)
    {
        $this->includeOrdersActivated = $shouldIncludeOrders;
    }

    /**
     * Get flag that indicates whether activated field should sent or not.
     *
     * @return boolean
     *   Returns true when activated field should be sent, otherwise false.
     */
    public function shouldActivatedFieldBeSent()
    {
        return $this->activatedFieldForSending;
    }

    /**
     * Set flag that indicates whether activated field should sent or not.
     *
     * @param boolean $activatedFieldForSending Flag that indicates whether activated field should sent or not.
     */
    public function setActivatedFieldForSending($activatedFieldForSending)
    {
        $this->activatedFieldForSending = $activatedFieldForSending;
    }

    /**
     * Set flag that indicates whether deactivated field should sent or not.
     *
     * @return boolean
     *   Returns true when deactivated field should be sent, otherwise false.
     */
    public function shouldDeactivatedFieldBeSent()
    {
        return $this->deactivatedFieldForSending;
    }

    /**
     * Set flag that indicates whether deactivated field should sent or not.
     *
     * @param boolean $deactivatedFieldForSending Flag that indicates whether deactivated field should sent or not.
     */
    public function setDeactivatedFieldForSending($deactivatedFieldForSending)
    {
        $this->deactivatedFieldForSending = $deactivatedFieldForSending;
    }
}
