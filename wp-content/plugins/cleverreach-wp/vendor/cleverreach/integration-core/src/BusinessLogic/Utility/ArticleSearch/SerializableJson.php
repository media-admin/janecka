<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch;

/**
 * Base class for all objects that use utility json serialization methods.
 * 
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch
 */
abstract class SerializableJson
{
    /**
     * Prepares object for json serialization.
     * 
     * @return array
     *   Array representation of object.
     */
    abstract public function toArray();

    /**
     * Returns JSON representation of the object.
     *
     * @return string
     *   JSON representation of object.
     */
    public function toJson() {
        return json_encode(array('data' => $this->toArray()));
    }
}
