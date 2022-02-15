<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchResult;

/**
 * Simple collection type of attribute for search result.
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchResult
 */
class SimpleCollectionAttribute extends SearchResultItemAttribute
{
    /**
     * Child search result attributes.
     *
     * @var array
     */
    private $attributes;

    /**
     * ComplexCollectionAttribute constructor.
     *
     * @param string $code Search result attribute code.
     * @param SimpleAttribute[] $attributes List of child search result attributes.
     */
    public function __construct($code, array $attributes = array())
    {
        parent::__construct($code);
        $this->attributes = $attributes;
    }

    /**
     * Adds new attribute to the list of attributes.
     *
     * @param SimpleAttribute $attribute Instance of simple attribute.
     */
    public function addAttribute(SimpleAttribute $attribute)
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
            $attributeMapValues = array_values($attributeMap);
            $formattedAttributes[] = reset($attributeMapValues);
        }
        
        return array($this->code => $formattedAttributes);
    }
}
