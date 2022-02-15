<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Schema;

use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\Logger;

/**
 * Base class for all complex attributes that will be used in schema.
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Schema
 */
abstract class ComplexSchemaAttribute extends SchemaAttribute
{
    /**
     * List of attributes that belong to this complex type.
     *
     * @var SchemaAttribute[]
     */
    private $attributes;

    /**
     * ComplexSchemaAttribute constructor.
     *
     * @param string $code Schema attribute code.
     * @param string $name Schema attribute label.
     * @param bool $searchable Flag that indicates whether attribute is searchable or not.
     * @param array $searchableExpressions Conditions enum contains all possible values for searchable expressions.
     * @param SchemaAttribute[] $attributes List of attributes that belong to this complex type.
     */
    public function __construct(
        $code,
        $name,
        $searchable,
        array $searchableExpressions = array(),
        array $attributes = array()
    ) {
        $this->validate($attributes);
        parent::__construct($code, $name, $searchable, $searchableExpressions);

        $this->attributes = $attributes;
    }

    /**
     * Get list of attributes that belong to this complex type.
     *
     * @return SchemaAttribute[]
     *   List of child attributes.
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Adds attribute to a schema.
     *
     * @param SchemaAttribute $attribute Child attribute.
     */
    public function addSchemaAttribute(SchemaAttribute $attribute)
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
        $result = parent::toArray();

        foreach ($this->attributes as $attribute) {
            $result['attributes'][] = $attribute->toArray();
        }

        return $result;
    }

    /**
     * Validates if attributes array has valid elements.
     *
     * @param SchemaAttribute[] $attributes List of child attributes.
     */
    private function validate(array $attributes)
    {
        foreach ($attributes as $attribute) {
            if (!($attribute instanceof SchemaAttribute)) {
                Logger::logError('Invalid attribute type passed to complex schema attribute.');
                throw new \InvalidArgumentException('Invalid attribute type passed to complex schema attribute.');
            }
        }
    }
}
