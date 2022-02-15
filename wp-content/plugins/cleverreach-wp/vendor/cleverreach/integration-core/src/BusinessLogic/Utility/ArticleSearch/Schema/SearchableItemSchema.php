<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Schema;

use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SerializableJson;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\Logger;

/**
 * Class SearchableItemSchema
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Schema
 */
class SearchableItemSchema extends SerializableJson
{
    /**
     * Unique entity code used for fetching schema.
     *
     * @var string
     */
    private $itemCode;
    /**
     * List of schema attributes for passed entity.
     *
     * @var SchemaAttribute[]
     */
    private $attributes;

    /**
     * SearchableItemSchema constructor.
     *
     * @param string $itemCode Unique entity code used for fetching schema.
     * @param SchemaAttribute[] $attributes List of schema attributes for passed entity.
     */
    public function __construct($itemCode, array $attributes)
    {
        $this->validateSearchableItemSchema($itemCode, $attributes);

        $this->itemCode = $itemCode;
        $this->attributes = $attributes;
    }

    /**
     * Get unique entity code used for fetching schema.
     *
     * @return string
     *   Unique entity code.
     */
    public function getItemCode()
    {
        return $this->itemCode;
    }

    /**
     * Get list of schema attributes for entity.
     *
     * @return SchemaAttribute[]
     *   List of schema attributes.
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Adds an attribute to the list of attributes.
     *
     * @param SchemaAttribute $attribute Schema attribute.
     */
    public function addAttribute(SchemaAttribute $attribute)
    {
        $this->attributes[] = $attribute;
    }

    /**
     * Prepares object for json serialization.
     *
     * @return array
     *   Array representation of object.
     */
    public function toArray()
    {
        $attributes = array();

        /** @var SchemaAttribute $attribute */
        foreach ($this->attributes as $attribute) {
            $attributes[] = $attribute->toArray();
        }

        return array('itemCode' => $this->itemCode, 'attributes' => $attributes);
    }

    /**
     * Validates passed parameters in constructor.
     *
     * @param string $itemCode Unique entity code used for fetching schema.
     * @param SchemaAttribute[] $attributes List of schema attributes for passed entity.
     */
    private function validateSearchableItemSchema($itemCode, array $attributes)
    {
        if (empty($itemCode)) {
            Logger::logError('Item code for item schema is mandatory.');
            throw new \InvalidArgumentException('Item code for item schema is mandatory.');
        }

        foreach ($attributes as $attribute) {
            if (!($attribute instanceof SchemaAttribute)) {
                Logger::logError('All attributes must be instances of SchemaAttribute class.');
                throw new \InvalidArgumentException('All attributes must be instances of SchemaAttribute class.');
            }
        }
    }
}
