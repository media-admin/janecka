<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchResult;

/**
 * Class ComplexAttribute, base class for all complex types of attributes.
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchResult
 */
abstract class ComplexAttribute extends SearchResultItemAttribute
{
    /**
     * Child search result attributes.
     *
     * @var SearchResultItemAttribute[]
     */
    protected $attributes;

    /**
     * ComplexCollectionAttribute constructor.
     *
     * @param string $code Search result attribute code.
     * @param SearchResultItemAttribute[] $attributes List of child search result attributes.
     */
    public function __construct($code, array $attributes = array())
    {
        parent::__construct($code);
        $this->attributes = $attributes;
    }

    /**
     * Adds new attribute to the list of attributes.
     * 
     * @param SearchResultItemAttribute $attribute Search result attribute.
     */
    public function addAttribute(SearchResultItemAttribute $attribute)
    {
        $this->attributes[] = $attribute;
    }
}
