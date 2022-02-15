<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchResult;

/**
 * Base class for all simple types of attributes in search result.
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchResult
 */
abstract class SimpleAttribute extends SearchResultItemAttribute
{
    /**
     * Search result value for simple attribute.
     *
     * @var string
     */
    protected $value;

    /**
     * Get search result value for simple attribute.
     *
     * @return string
     *   Search result value (text).
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * SimpleAttribute constructor.
     *
     * @param string $code Search result attribute code.
     * @param string $value Search result value for simple attribute.
     */
    public function __construct($code, $value)
    {
        parent::__construct($code);
        
        $this->value = $value;
    }

    /**
     * Prepares object for json serialization.
     *
     * @return array
     *   Array representation of object.
     */
    public function toArray()
    {
        return array($this->code => $this->value); 
    }
}
