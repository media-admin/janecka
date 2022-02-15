<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync;

use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Serializer;

/**
 * Class RecipientStatusUpdateSyncTask
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync
 */
abstract class RecipientStatusUpdateSyncTask extends BaseSyncTask
{
    /**
     * Array of recipient emails that should be updated.
     *
     * @var array
     */
    public $recipientEmails;

    /**
     * RecipientStatusUpdateSyncTask constructor.
     *
     * @param array $recipientEmails Array of recipient emails that should be updated.
     */
    public function __construct(array $recipientEmails)
    {
        $this->recipientEmails = $recipientEmails;
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
        return new static($array['recipientEmails']);
    }

    /**
     * String representation of object
     *
     * @inheritdoc
     */
    public function serialize()
    {
        return Serializer::serialize($this->recipientEmails);
    }

    /**
     * Constructs the object.
     *
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $this->recipientEmails = Serializer::unserialize($serialized);
    }

    /**
     * Transforms entity to array.
     *
     * @return array
     */
    public function toArray()
    {
        return array('recipientEmails' => $this->recipientEmails);
    }
}
