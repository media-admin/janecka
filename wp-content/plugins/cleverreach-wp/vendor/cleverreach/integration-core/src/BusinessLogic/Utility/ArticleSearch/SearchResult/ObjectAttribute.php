<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchResult;

/**
 * Class ObjectAttribute, object type of attribute for search result
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchResult
 */
class ObjectAttribute extends ComplexAttribute
{
    /**
     * Prepares object for json serialization.
     *
     * @return array
     *   Array representation of object.
     */
    public function toArray()
    {
        $result = array($this->code => array());

        foreach ($this->attributes as $attribute) {
            $attributeMap = $attribute->toArray();
            $attributeCodes = array_keys($attributeMap);
            $result[$this->code][$attributeCodes[0]] = $attributeMap[$attributeCodes[0]];
        }

        return $result;
    }
}
