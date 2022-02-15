<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Schema;

/**
 * Class ObjectSchemaAttribute, object type of attribute for schema.
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Schema
 */
class ObjectSchemaAttribute extends ComplexSchemaAttribute
{
    /**
     * ObjectSchemaAttribute constructor.
     *
     * @param string $code Schema attribute code.
     * @param string $name Schema attribute label.
     * @param bool $searchable Flag that indicates whether attribute is searchable or not.
     * @param array $searchableExpressions Conditions enum contains all possible values for searchable expressions.
     * @param SchemaAttribute[] $attributes List of attributes that belong to this object type.
     */
    public function __construct($code, $name, $searchable, array $searchableExpressions, array $attributes)
    {
        parent::__construct($code, $name, $searchable, $searchableExpressions, $attributes);

        $this->type = SchemaAttributeTypes::OBJECT;
    }
}
