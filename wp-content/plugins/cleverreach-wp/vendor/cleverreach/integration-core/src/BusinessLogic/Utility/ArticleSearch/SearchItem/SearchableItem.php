<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchItem;

use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SerializableJson;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\Logger;

/**
 * Class SearchableItem
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchItem
 */
class SearchableItem extends SerializableJson
{
    /**
     * Searchable entity code.
     *
     * @var string
     */
    private $code;
    /**
     * Searchable entity name.
     *
     * @var string
     */
    private $name;

    /**
     * SearchableItem constructor.
     *
     * @param string $code Searchable entity code.
     * @param string $name Searchable entity name.
     */
    public function __construct($code, $name)
    {
        $this->validateSearchableItem($code, $name);

        $this->code = $code;
        $this->name = $name;
    }

    /**
     * Prepares object for json serialization.
     *
     * @return array
     *   Array representation of object.
     */
    public function toArray()
    {
        return array('code' => $this->code, 'name' => $this->name);
    }

    /**
     * Validates passed parameters in constructor.
     *
     * @param string $code Searchable entity code.
     * @param string $name Searchable entity name.
     */
    private function validateSearchableItem($code, $name)
    {
        if (empty($code)) {
            Logger::logError('Code for searchable item is mandatory.');
            throw new \InvalidArgumentException('Code for searchable item is mandatory.');
        }

        if (empty($name)) {
            Logger::logError('Name for searchable item is mandatory.');
            throw new \InvalidArgumentException('Name for searchable item is mandatory.');
        }
    }
}
