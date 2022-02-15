<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Schema;

/**
 * Class EnumSchemaAttribute, enum type of attribute for schema
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Schema
 */
class EnumSchemaAttribute extends SchemaAttribute
{
    /**
     * List of possible options for this enum type.
     *
     * @var array
     */
    private $possibleValues;

    /**
     * EnumSchemaAttribute constructor.
     *
     * @param string $code Schema attribute code.
     * @param string $name Schema attribute label.
     * @param bool $searchable Flag that indicates whether attribute is searchable or not.
     * @param array $searchableExpressions Conditions enum contains all possible values for searchable expressions.
     * @param Enum[] $possibleValues List of possible options for this enum type.
     */
    public function __construct($code, $name, $searchable, array $searchableExpressions, array $possibleValues)
    {
        parent::__construct($code, $name, $searchable, $searchableExpressions);

        $this->type = SchemaAttributeTypes::ENUM;
        $this->possibleValues = $possibleValues;
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
        /** @var Enum $value */
        foreach ($this->possibleValues as $value) {
            $result['possibleValues'][] = array('label' => $value->getLabel(), 'value' => $value->getValue());
        }

        return $result;
    }
}
