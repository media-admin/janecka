<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchResult;

/**
 * Used for creating Complex collection attribute.
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchResult
 */
class ComplexCollectionAttribute extends ComplexAttribute
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
            if ($attribute instanceof ObjectAttribute) {
                $result[$this->code][] = $attributeMap[$attributeCodes[0]];
            } else {
                $result[$this->code][][$attributeCodes[0]] = $attributeMap[$attributeCodes[0]];
            }
        }

        return $result;
    }
}
