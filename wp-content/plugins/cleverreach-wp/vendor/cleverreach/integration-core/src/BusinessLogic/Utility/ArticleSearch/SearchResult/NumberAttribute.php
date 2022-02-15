<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchResult;

use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\Logger;

/**
 * Class NumberAttribute, number attribute for search result
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchResult
 */
class NumberAttribute extends SimpleAttribute
{
    /**
     * NumberAttribute constructor.
     *
     * @param string $code Search result attribute code.
     * @param string $value Search result value for number attribute.
     */
    public function __construct($code, $value)
    {
        parent::__construct($code, $value);

        if (!is_numeric($value)) {
            Logger::logError('Passed value: ' . $this->value . ' can not be cast to numeric type.');
            throw new \InvalidArgumentException('Passed value: ' . $this->value . ' can not be cast to numeric type.');
        }

        if (strpos($value, '.') > 0) {
            $this->value = (float)$value;
        } else {
            $this->value = (int)$value;
        }
    }
}
