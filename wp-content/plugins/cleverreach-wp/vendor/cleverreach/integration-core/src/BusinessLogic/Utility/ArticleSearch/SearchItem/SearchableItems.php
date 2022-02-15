<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchItem;

use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SerializableJson;

/**
 * Class SearchableItems
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchItem
 */
class SearchableItems extends SerializableJson
{
    /**
     * List of searchable entities.
     *
     * @var SearchableItem[]
     */
    private $searchableItems;

    /**
     * Adds new searchable item.
     *
     * @param SearchableItem $searchableItem Searchable item.
     */
    public function addSearchableItem(SearchableItem $searchableItem)
    {
        $this->searchableItems[] = $searchableItem;
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

        /** @var SearchableItem $searchableItem */
        foreach ($this->searchableItems as $searchableItem) {
            $data[] = $searchableItem->toArray();
        }

        return $data;
    }
}
