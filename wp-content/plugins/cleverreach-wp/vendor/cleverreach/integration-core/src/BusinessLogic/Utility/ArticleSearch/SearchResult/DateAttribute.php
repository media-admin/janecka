<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchResult;

/**
 * Class DateAttribute, Date attribute in search result.
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchResult
 */
class DateAttribute extends SearchResultItemAttribute
{
    /**
     * Search result value for date attribute.
     *
     * @var \DateTime
     */
    private $value;

    /**
     * DateAttribute constructor.
     *
     * @param string $code Search result attribute code.
     * @param \DateTime $value Search result value for date attribute.
     */
    public function __construct($code, \DateTime $value)
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
        return array($this->code => $this->value->format('Y-m-d\TH:i:s.u\Z'));
    }
}
