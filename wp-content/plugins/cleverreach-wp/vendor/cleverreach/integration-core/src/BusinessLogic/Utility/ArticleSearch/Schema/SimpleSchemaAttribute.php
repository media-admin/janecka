<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Schema;

use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\Logger;

/**
 * Class SimpleSchemaAttribute, simple type of attribute in schema.
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Schema
 */
class SimpleSchemaAttribute extends SchemaAttribute
{
    /**
     * All possible attribute types.
     *
     * @var array
     */
    private $attributeTypes = array(
        SchemaAttributeTypes::AUTHOR,
        SchemaAttributeTypes::URL,
        SchemaAttributeTypes::TEXT,
        SchemaAttributeTypes::NUMBER,
        SchemaAttributeTypes::IMAGE,
        SchemaAttributeTypes::DATE,
        SchemaAttributeTypes::HTML,
        SchemaAttributeTypes::BOOL,
    );

    /**
     * SimpleSchemaAttribute constructor.
     *
     * @param string $code Schema attribute code.
     * @param string $name Schema attribute label.
     * @param bool $searchable Flag that indicates whether attribute is searchable or not.
     * @param array $searchableExpressions Conditions enum contains all possible values for searchable expressions.
     * @param string $type Type of simple attribute.
     */
    public function __construct($code, $name, $searchable, array $searchableExpressions, $type)
    {
        $this->validate($type);

        parent::__construct($code, $name, $searchable, $searchableExpressions);

        $this->type = $type;
    }

    /**
     * Validates if type is valid based on predefined types.
     *
     * @param string $type Type of simple attribute.
     */
    private function validate($type)
    {
        if (!in_array($type, $this->attributeTypes)) {
            $errorMessage = 'Invalid type for schema attribute: ' . $type . '. ' .
                'Type must value from enum: ' . implode(',', $this->attributeTypes);
            Logger::logError($errorMessage);
            throw new \InvalidArgumentException($errorMessage);
        }
    }
}
