<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchResult;

use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SerializableJson;

/**
 * Class SearchResult
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchResult
 */
class SearchResult extends SerializableJson
{
    /**
     * List of search result items.
     *
     * @var SearchResultItem[]
     */
    private $searchResultItems;

    /**
     * Get list of search result items.
     *
     * @return SearchResultItem[]
     *   List of search result items.
     */
    public function getSearchResultItems()
    {
        return $this->searchResultItems;
    }

    /**
     * Append new searchable item to search result.
     *
     * @param SearchResultItem $searchResultItem Search result item.
     */
    public function addSearchResultItem(SearchResultItem $searchResultItem)
    {
        $this->searchResultItems[] = $searchResultItem;
    }

    /**
     * Prepares object for json serialization.
     *
     * @return array
     *   Array representation of object.
     */
    public function toArray()
    {
        $data = array();

        if (is_array($this->searchResultItems)) {
            /** @var SearchResultItem $searchableItem */
            foreach ($this->searchResultItems as $searchResultItem) {
                $data[] = $searchResultItem->toArray();
            }
        }

        return $data;
    }
}
