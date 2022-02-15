<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch;

/**
 * Enumeration representing valid conditions for article search
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch
 */
abstract class Conditions
{
    const EQUALS = 'eq';
    const NOT_EQUAL = 'ne';
    const GREATER_THAN = 'gt';
    const LESS_THAN = 'lt';
    const LESS_EQUAL = 'le';
    const GREATER_EQUAL = 'ge';
    const CONTAINS = 'ct';
}
