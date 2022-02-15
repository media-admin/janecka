<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch;

/**
 * Class FilterParser
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch
 */
class FilterParser
{
    /**
     * Generates array of filter objects based on passed parameters.
     *
     * @param string $itemCode Code of searchable item.
     * @param string|null $itemId (optional) Id of searchable item.
     * @param string|null $query (optional) Url encoded URL.
     *
     * @return Filter[]
     *   List of parsed filters.
     * @throws \InvalidArgumentException
     */
    public function generateFilters($itemCode, $itemId = null, $query = null)
    {
        $generatedFilters = array(
            new Filter('itemCode', $itemCode, Conditions::EQUALS, Operators::AND_OPERATOR)
        );

        if (!empty($itemId)) {
            $generatedFilters[]= new Filter('itemId', $itemId, Conditions::EQUALS, Operators::AND_OPERATOR);
        }

        if (!empty($query)) {
            $generatedFilters = array_merge($generatedFilters, $this->createFiltersFromQuery($query));
        }
        
        return $generatedFilters;
    }

    /**
     * Parses query based on known rules. Only AND operator is supported.
     *
     * Each expression is in format:
     * "{$attributeName} {$condition} {$attributeValue}".
     *
     * @param string $query In format "title ct 'great' and date gt '2012-04-23T18:25:43.511Z'".
     *
     * @return Filter[]
     *   List of parsed filters.
     * @throws \InvalidArgumentException
     */
    private function createFiltersFromQuery($query)
    {
        $query = urldecode($query);
        $operatorPartInQuery = ' ' . Operators::AND_OPERATOR . ' ';
        $query = str_replace($operatorPartInQuery, strtolower($operatorPartInQuery), $query);
        $queryExpressions = explode(strtolower($operatorPartInQuery), $query);
        $generatedFilters = array();

        foreach ($queryExpressions as $queryExpression) {
            $expressionCondition = $this->findConditionInQueryExpression($queryExpression);
            $conditionPartInQuery = ' ' . $expressionCondition . ' ';

            $filterParts = !empty($expressionCondition) ? explode($conditionPartInQuery, $queryExpression) : array();

            $attributeCode = isset($filterParts[0]) ? $filterParts[0] : null;

            if (empty($expressionCondition)) {
                // If condition is invalid just add whole string as value.
                $attributeValue = $attributeCode;
            } else {
                $attributeValue = isset($filterParts[1]) ? str_replace("'", '', $filterParts[1]) : null;
            }
            
            $generatedFilters[] = new Filter(
                trim($attributeCode),
                trim($attributeValue),
                $expressionCondition,
                Operators::AND_OPERATOR
            );
        }

        return $generatedFilters;
    }

    /**
     * Finds condition in expression in query.
     *
     * @param string $queryExpression In format "title ct 'great' and date gt '2012-04-23T18:25:43.511Z'".
     *
     * @return string
     *   Condition found in passed query, if not found empty string is returned.
     */
    private function findConditionInQueryExpression($queryExpression)
    {
        $expressionCondition = '';

        foreach (Filter::getPossibleConditions() as $condition) {
            $conditionPartInQuery = ' ' . $condition . ' ';
            if (stripos($queryExpression, $conditionPartInQuery) > -1) {
                $expressionCondition = $condition;
                break;
            }
        }

        return $expressionCondition;
    }
}
