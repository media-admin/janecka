<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch;

use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Exceptions\InvalidSchemaMatching;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Schema\ComplexSchemaAttribute;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Schema\SchemaAttribute;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Schema\SearchableItemSchema;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchResult\SearchResult;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchResult\SearchResultItem;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\Logger;

/**
 * Validation of generated filters and search result.
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch
 */
class Validator
{
    const SEARCHABLE_ITEM_CODE_FIELD_NAME = 'itemCode';
    const SEARCHABLE_ITEM_ID_FIELD_NAME = 'id';

    /**
     * Validates generated filters based on item schema.
     *
     * @param Filter[] $filters Parsed filters from query string.
     * @param SearchableItemSchema $itemSchema Schema for the searched entity.
     *
     * @throws InvalidSchemaMatching
     */
    public function validateFilters(array $filters, SearchableItemSchema $itemSchema)
    {
        $flatSchemaAttributeMap = $this->generateFlatItemSchemaAttributes($itemSchema->getAttributes());

        /** @var Filter $filter */
        foreach ($filters as $filter) {
            /** @noinspection NotOptimalIfConditionsInspection */
            if (!in_array(
                    $filter->getAttributeCode(),
                    array(
                        static::SEARCHABLE_ITEM_CODE_FIELD_NAME,
                        static::SEARCHABLE_ITEM_ID_FIELD_NAME
                    ),
                    false
                )
                && !$this->isFilterMatchingTheSchema($flatSchemaAttributeMap, $filter)
            ) {
                $errorMessage = $filter->getAttributeCode() . ' does not match given schema.';
                Logger::logError($errorMessage);

                throw new InvalidSchemaMatching($errorMessage);
            }
        }
    }

    /**
     * Validates search result based on item schema.
     *
     * @param SearchResult $searchResult Results matching certain filters.
     * @param SearchableItemSchema $itemSchema Schema for the searched entity.
     *
     * @throws InvalidSchemaMatching
     */
    public function validateSearchResults(SearchResult $searchResult, SearchableItemSchema $itemSchema)
    {
        $flatSchemaAttributeCodes = $this->generateFlatItemSchemaAttributes($itemSchema->getAttributes());

        /** @var SearchResultItem $searchResultItem */
        foreach ($searchResult->getSearchResultItems() as $searchResultItem) {
            foreach (array_keys($searchResultItem->getAttributes()) as $attributeCodeForSearch) {
                if (!isset($flatSchemaAttributeCodes[$attributeCodeForSearch])) {
                    $errorMessage = $attributeCodeForSearch . ' does not exist in schema for ' .
                        $itemSchema->getItemCode();
                    Logger::logError($errorMessage);
                    throw new InvalidSchemaMatching($errorMessage);
                }
            }
        }
    }

    /**
     * Generates flat array of attribute codes.
     *
     * @param SchemaAttribute[] $attributes List of all attributes in schema.
     *
     * @return array
     *   Array where attribute code is key and SchemaAttribute is value.
     */
    private function generateFlatItemSchemaAttributes(array $attributes)
    {
        $allAttributeCodes = array();

        /** @var SchemaAttribute|ComplexSchemaAttribute $attribute */
        foreach ($attributes as $attribute) {
            $allAttributeCodes[$attribute->getCode()] = $attribute;

            if (!($attribute instanceof ComplexSchemaAttribute)) {
                continue;
            }

            $nestedAttributes = $attribute->getAttributes();
            if (is_array($nestedAttributes) && count($nestedAttributes) > 0) {
                /** @noinspection AdditionOperationOnArraysInspection */
                $allAttributeCodes += $this->generateFlatItemSchemaAttributes($nestedAttributes);
            }
        }

        return $allAttributeCodes;
    }

    /**
     * Validate if filter meets defined schema.
     *
     * @param SchemaAttribute[]|null $schemaAttributes List of all attributes in schema.
     * @param Filter $generatedFilter Filter object.
     *
     * @return bool
     *   If filter meets defined schema returns true, otherwise false.
     */
    private function isFilterMatchingTheSchema($schemaAttributes, Filter $generatedFilter)
    {
        return
            isset($schemaAttributes[$generatedFilter->getAttributeCode()]) &&
            $schemaAttributes[$generatedFilter->getAttributeCode()]->isSearchable() &&
            in_array(
                $generatedFilter->getCondition(),
                $schemaAttributes[$generatedFilter->getAttributeCode()]->getSearchableExpressions()) &&
            count(array_diff(
                $schemaAttributes[$generatedFilter->getAttributeCode()]->getSearchableExpressions(),
                $generatedFilter->getPossibleConditions()
            )) === 0;
    }
}
