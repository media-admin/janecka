<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Schema;

/**
 * Used for creating complex collection attribute for schema.
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Schema
 */
class ComplexCollectionSchemaAttribute extends ComplexSchemaAttribute
{
    /**
     * ComplexCollectionSchemaAttribute constructor.
     *
     * @param string $code Schema attribute code.
     * @param string $name Schema attribute label.
     * @param bool $searchable Flag that indicates whether attribute is searchable or not.
     * @param array $searchableExpressions Conditions enum contains all possible values for searchable expressions.
     * @param SchemaAttribute[] $attributes List of attributes that belong to this complex collection type.
     */
    public function __construct($code, $name, $searchable, array $searchableExpressions, array $attributes)
    {
        parent::__construct($code, $name, $searchable, $searchableExpressions, $attributes);

        $this->type = SchemaAttributeTypes::COLLECTION;
    }
}
