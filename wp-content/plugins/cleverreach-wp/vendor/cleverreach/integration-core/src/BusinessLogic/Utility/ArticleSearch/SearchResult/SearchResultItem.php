<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchResult;

use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SerializableJson;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\Logger;

/**
 * Class SearchResultItem
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchResult
 */
class SearchResultItem extends SerializableJson
{
    /**
     * Search result entity code.
     *
     * @var string
     */
    private $itemCode;
    /**
     * Unique identifier of search result.
     *
     * @var string
     */
    private $id;
    /**
     * Search result title.
     *
     * @var string
     */
    private $title;
    /**
     * Search result date.
     *
     * @var \DateTime
     */
    private $date;
    /**
     * List of search result attributes.
     *
     * @var SearchResultItemAttribute[]
     */
    private $attributes;

    /**
     * SearchResult constructor.
     *
     * @param string $itemCode Search result entity code.
     * @param string $id Unique identifier of search result.
     * @param string $title Search result title.
     * @param \DateTime $date Search result date.
     * @param SearchResultItemAttribute[] $attributes List of search result attributes.
     */
    public function __construct($itemCode, $id, $title, \DateTime $date, array $attributes)
    {
        $this->validateSearchResult($itemCode, $id);

        $this->itemCode = $itemCode;
        $this->id = $id;
        $this->title = $title;
        $this->date = $date;
        $this->attributes = $attributes;
    }

    /**
     * Get list of search result attributes.
     *
     * @return SearchResultItemAttribute[]
     *   List of search result attributes.
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Adds new attribute to the list of search result attributes.
     *
     * @param SearchResultItemAttribute $attribute Search result attribute.
     */
    public function addAttribute(SearchResultItemAttribute $attribute)
    {
        $this->attributes[] = $attribute;
    }

    /**
     * Prepares object for json serialization.
     *
     * @return array
     *   Array representation of object.
     */
    public function toArray()
    {
        $formattedAttributes = array();

        foreach ($this->attributes as $attribute) {
            $attributeMap = $attribute->toArray();
            $attributeCodes = array_keys($attributeMap);
            $formattedAttributes[$attributeCodes[0]] = $attributeMap[$attributeCodes[0]];
        }

        return array(
          'itemCode' => $this->itemCode,
          'id' => $this->id,
          'attributes' => array_merge(
              array(
                  'title' => $this->title,
                  'date' => $this->date->format('Y-m-d\TH:i:s.u\Z'),
              ),
              $formattedAttributes
          ),
        );
    }

    /**
     * Validates parameters passed in constructor.
     *
     * @param string $itemCode Search result entity code.
     * @param string $id Unique identifier of search result.
     */
    private function validateSearchResult($itemCode, $id)
    {
        if (empty($itemCode)) {
            Logger::logError('Item code for search result is mandatory.');
            throw new \InvalidArgumentException('Item code for search result is mandatory.');
        }

        if (empty($id)) {
            Logger::logError('Id for search result is mandatory.');
            throw new \InvalidArgumentException('Id for search result is mandatory.');
        }
    }
}
