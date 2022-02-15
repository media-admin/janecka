<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchResult;

use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SerializableJson;

/**
 * Base class for all attributes in search result
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchResult
 */
abstract class SearchResultItemAttribute extends SerializableJson
{
    /**
     * Search result attribute code.
     *
     * @var string
     */
    protected $code;

    /**
     * SearchResultItemAttribute constructor.
     *
     * @param string $code Search result attribute code.
     */
    public function __construct($code)
    {
        $this->code = $code;
    }
}
