<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Schema;

/**
 * Class SimpleCollectionSchemaAttribute, simple collection attribute in schema.
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Schema
 */
class SimpleCollectionSchemaAttribute extends SchemaAttribute
{
    /**
     * Multi value attribute.
     *
     * @var string
     */
    private $attributes;

    /**
     * SimpleSchemaAttribute constructor.
     *
     * @param string $code Schema attribute code.
     * @param string $name Schema attribute label.
     * @param bool $searchable Flag that indicates whether attribute is searchable or not.
     * @param array $searchableExpressions Conditions enum contains all possible values for searchable expressions.
     * @param string $attributes Multi value attribute.
     */
    public function __construct($code, $name, $searchable, array $searchableExpressions, $attributes)
    {
        parent::__construct($code, $name, $searchable, $searchableExpressions);

        $this->type = SchemaAttributeTypes::COLLECTION;
        $this->attributes = $attributes;
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
        $result['attributes'] = $this->attributes;

        return $result;
    }
}
