<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Schema;

use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SerializableJson;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\Logger;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Conditions;

/**
 * Class SchemaAttribute, base schema attribute
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Schema
 */
abstract class SchemaAttribute extends SerializableJson
{
    /**
     * Schema attribute code.
     *
     * @var string
     */
    protected $code;
    /**
     * Schema attribute name.
     *
     * @var string
     */
    protected $name;
    /**
     * Schema attribute type.
     *
     * @var string
     * @see \CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Schema\SchemaAttributeTypes
     */
    protected $type;
    /**
     * Flag that indicates whether attribute is searchable or not.
     *
     * @var bool
     */
    protected $searchable = false;
    /**
     * Conditions enum contains all possible values for searchable expressions.
     *
     * @var array
     * @see \CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Conditions
     */
    protected $searchableExpressions = array();
    
    /**
     * All possible searchable conditions.
     *
     * @var array
     */
    private $possibleConditions = array(
        Conditions::CONTAINS,
        Conditions::EQUALS,
        Conditions::GREATER_EQUAL,
        Conditions::GREATER_THAN,
        Conditions::LESS_EQUAL,
        Conditions::LESS_THAN,
        Conditions::NOT_EQUAL,
    );

    /**
     * SchemaAttribute constructor.
     *
     * @param string $code Schema attribute code.
     * @param string $name Schema attribute label.
     * @param bool $searchable Flag that indicates whether attribute is searchable or not.
     * @param array $searchableExpressions Conditions enum contains all possible values for searchable expressions.
     */
    protected function __construct($code, $name, $searchable, array $searchableExpressions = array()) {
        $this->validateSchemaAttribute($code, $name, $searchableExpressions);

        $this->code = $code;
        $this->name = $name;
        $this->searchable = $searchable;
        $this->searchableExpressions = $searchableExpressions;
    }

    /**
     * Get schema attribute code.
     *
     * @return string
     *   Attribute code.
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Get is schema attribute searchable or not.
     *
     * @return boolean
     *   If searchable returns true, otherwise false.
     */
    public function isSearchable()
    {
        return $this->searchable;
    }

    /**
     * Get all searchable conditions supported for this attribute.
     *
     * @return array
     *   List of supported searchable conditions.
     * @see \CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Conditions
     */
    public function getSearchableExpressions()
    {
        return $this->searchableExpressions;
    }

    /**
     * Prepares object for json serialization.
     *
     * @return array
     *   Array representation of object.
     */
    public function toArray()
    {
        $result = array(
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
        );

        if ($this->searchable) {
            $result['searchable'] = $this->searchable;
        }

        if (is_array($this->searchableExpressions) && count($this->searchableExpressions) > 0) {
            $result['searchableExpressions'] = $this->searchableExpressions;
        }

        return $result;
    }

    /**
     * Validates passed parameters in constructor.
     *
     * @param string $code Schema attribute code.
     * @param string $name Schema attribute label.
     * @param array $searchableExpressions Conditions enum contains all possible values for searchable expressions.
     */
    private function validateSchemaAttribute($code, $name, array $searchableExpressions)
    {
        if (empty($code)) {
            Logger::logError('Item code for schema attribute is mandatory.');
            throw new \InvalidArgumentException('Item code for schema attribute is mandatory.');
        }

        if (empty($name)) {
            Logger::logError('Name for schema attribute is mandatory.');
            throw new \InvalidArgumentException('Name for schema attribute is mandatory.');
        }

        foreach ($searchableExpressions as $expression) {
            if (!in_array($expression, $this->possibleConditions)) {
                $errorMessage = 'Invalid expression : ' . $expression . '. ' .
                    'Expression must be value from enum: ' . implode(',', $this->possibleConditions);
                Logger::logError($errorMessage);
                throw new \InvalidArgumentException($errorMessage);
            }
        }
    }

}
