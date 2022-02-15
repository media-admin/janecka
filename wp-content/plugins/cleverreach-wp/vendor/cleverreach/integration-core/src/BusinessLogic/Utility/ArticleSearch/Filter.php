<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch;

use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\Logger;

/**
 * Class Filter
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch
 */
class Filter
{
    /**
     * All possible operators.
     *
     * @var array
     */
    private static $possibleOperators = array(
        Operators::AND_OPERATOR,
    );

    /**
     * All possible conditions.
     *
     * @var array
     */
    private static $possibleConditions = array(
        Conditions::CONTAINS,
        Conditions::EQUALS,
        Conditions::GREATER_EQUAL,
        Conditions::GREATER_THAN,
        Conditions::LESS_EQUAL,
        Conditions::LESS_THAN,
        Conditions::NOT_EQUAL,
    );

    /**
     * Filter attribute code.
     *
     * @var string
     */
    private $attributeCode;
    /**
     * Filter attribute value.
     *
     * @var string
     */
    private $attributeValue;
    /**
     * Filter condition.
     *
     * @var string
     */
    private $condition;
    /**
     * Filter operator.
     *
     * @var string
     */
    private $operator;

    /**
     * Filter constructor.
     *
     * @param string $attributeCode Filter attribute code.
     * @param string $attributeValue Filter attribute value.
     * @param string $condition Filter condition.
     * @param string $operator Filter operator.
     */
    public function __construct($attributeCode, $attributeValue, $condition, $operator)
    {
        $this->validateFilter($attributeCode, $attributeValue, $condition, $operator);

        $this->attributeCode = $attributeCode;
        $this->attributeValue = $attributeValue;
        $this->condition = $condition;
        $this->operator = $operator;
    }

    /**
     * Get all supported conditions.
     *
     * @return array
     *   Array of all conditions.
     */
    public static function getPossibleConditions()
    {
        return static::$possibleConditions;
    }

    /**
     * Get filter attribute code.
     *
     * @return string
     *   Attribute code.
     */
    public function getAttributeCode()
    {
        return $this->attributeCode;
    }

    /**
     * Get filter attribute value.
     *
     * @return string
     *   Attribute value.
     */
    public function getAttributeValue()
    {
        return $this->attributeValue;
    }

    /**
     * Get filter condition.
     *
     * @return string
     *   Filter condition.
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * Get filter operator.
     *
     * @return string
     *   Filter operator.
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * Validates passed parameters in constructor.
     *
     * @param string $attributeCode Filter attribute code.
     * @param string $attributeValue Filter attribute value.
     * @param string $condition Filter condition.
     * @param string $operator Filter operator.
     */
    private function validateFilter($attributeCode, $attributeValue, $condition, $operator)
    {
        if ($attributeCode === null) {
            Logger::logError('Attribute code for filter is mandatory.');
            throw new \InvalidArgumentException('Attribute code for filter is mandatory.');
        }

        if ($attributeValue === null) {
            Logger::logError('Attribute value for filter is mandatory.');
            throw new \InvalidArgumentException('Attribute value for filter is mandatory.');
        }

        if (!in_array($condition, static::$possibleConditions)) {
            $errorMessage = 'Condition for filter must be in the set of values: ' . 
                json_encode(static::$possibleConditions);
            Logger::logError($errorMessage);
            throw new \InvalidArgumentException($errorMessage);
        }

        if (!in_array($operator, static::$possibleOperators)) {
            $errorMessage = 'Operator for filter must be in the set of values: ' . json_encode(static::$possibleOperators);
            Logger::logError($errorMessage);
            throw new \InvalidArgumentException($errorMessage);
        }
    }
}
