<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync;

use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\RecipientAttribute;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Interfaces\Attributes;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\Helper;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Serializer;

/**
 * Class AttributesSyncTask
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync
 */
class AttributesSyncTask extends BaseSyncTask
{
    const INITIAL_PROGRESS_PERCENT = 5;

    /**
     * Array of global attribute IDs on CleverReach.
     *
     * @var array
     */
    private $globalAttributesIdsFromCR;

    /**
     * Map of default global attributes
     *
     * @var array [attribute_name => attribute_type]
     */
    private static $defaultAttributesMap = array(
        'salutation' => 'text',
        'title' => 'text',
        'firstname' => 'text',
        'lastname' => 'text',
        'street' => 'text',
        'zip' => 'text',
        'city' => 'text',
        'company' => 'text',
        'state' => 'text',
        'country' => 'text',
        'birthday' => 'date',
        'phone' => 'text',
        'shop' => 'text',
        'customernumber' => 'text',
        'language' => 'text',
        'newsletter' => 'text',
        'lastorderdate' => 'date',
    );

    /**
     * Transforms array into entity.
     *
     * @param array $array
     *
     * @return \CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Serializable
     */
    public static function fromArray($array)
    {
        $entity = new static();

        $entity->globalAttributesIdsFromCR = $array['globalAttributesIdsFromCR'];

        return $entity;
    }

    /**
     * String representation of object
     *
     * @inheritdoc
     */
    public function serialize()
    {
        return Serializer::serialize($this->globalAttributesIdsFromCR);
    }

    /**
     * Constructs the object.
     *
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $this->globalAttributesIdsFromCR = Serializer::unserialize($serialized);
    }

    /**
     * Transforms entity to array.
     *
     * @return array
     */
    public function toArray()
    {
        return array('globalAttributesIdsFromCR' => $this->globalAttributesIdsFromCR);
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
        $globalAttributes = $this->getGlobalAttributesIdsFromCleverReach();
        // After retrieving all global attributes set initial progress
        $progressPercent = self::INITIAL_PROGRESS_PERCENT;
        $this->reportProgress($progressPercent);

        $attributesToSend = $this->getAllAttributes();

        // Calculate progress step after setting initially progress
        $totalAttributes = count($attributesToSend);
        $progressStep = (100 - self::INITIAL_PROGRESS_PERCENT) / $totalAttributes;
        $i = 0;
        foreach ($attributesToSend as $attribute) {
            $i++;
            if (isset($globalAttributes[$attribute['name']])) {
                $attributeIdOnCR = $globalAttributes[$attribute['name']];
                $this->getProxy()->updateGlobalAttribute($attributeIdOnCR, $attribute);
            } else {
                $this->getProxy()->createGlobalAttribute($attribute);
            }

            $progressPercent += $progressStep;
            if ($i === $totalAttributes) {
                $this->reportProgress(100);
            } else {
                $this->reportProgress($progressPercent);
            }
        }
    }

    /**
     * Get global attributes IDs from CleverReach.
     *
     * @return array
     *   Array of global attribute IDs on CleverReach.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    private function getGlobalAttributesIdsFromCleverReach()
    {
        if (empty($this->globalAttributesIdsFromCR)) {
            $this->globalAttributesIdsFromCR = $this->getProxy()->getAllGlobalAttributes();
        }

        return $this->globalAttributesIdsFromCR;
    }

    /**
     * Return all global attributes formatted for sending to CleverReach.
     *
     * Example:
     * [
     *   [
     *     'name' => 'email',
     *     'type' => 'text',
     *   ],
     *   [
     *     'name' => 'birthday',
     *     'type' => 'date',
     *   ]
     * ]
     *
     * @return array
     *   Array of global attributes supported by integration.
     */
    private function getAllAttributes()
    {
        /** @var Attributes $attributesService */
        $attributesService = ServiceRegister::getService(Attributes::CLASS_NAME);
        $integrationAttributes = $attributesService->getAttributes();
        $attributesToSend = array();

        $attributeNames = array_keys(self::$defaultAttributesMap);
        foreach ($attributeNames as $attributeName) {
            if (($attribute = Helper::getAttributeByName($integrationAttributes, $attributeName)) !== null) {
                $attributesToSend[] = $this->createAttributeForSend($attribute);
            }
        }

        return $attributesToSend;
    }

    /**
     * Return formatted attribute.
     *
     * @param RecipientAttribute $attribute Attribute entity returned from AttributeService.
     *
     * @return array
     *   Array representation of attribute.
     */
    private function createAttributeForSend(RecipientAttribute $attribute)
    {
        $attributeToSend['name'] = $attribute->getName();
        $attributeToSend['type'] = $attribute->getType() ?: static::$defaultAttributesMap[$attribute->getName()];
        $attributeToSend['description'] = $attribute->getDescription();
        $attributeToSend['preview_value'] = $attribute->getPreviewValue();
        $attributeToSend['default_value'] = $attribute->getDefaultValue();

        return $attributeToSend;
    }
}
