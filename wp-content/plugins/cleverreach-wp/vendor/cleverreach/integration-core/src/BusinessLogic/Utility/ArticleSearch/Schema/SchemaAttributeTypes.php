<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Schema;

/**
 * Enumeration representing valid schema attribute types for article search.
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch
 */
abstract class SchemaAttributeTypes
{
    const AUTHOR = 'author';
    const URL = 'url';
    const TEXT = 'text';
    const NUMBER = 'number';
    const IMAGE = 'image';
    const DATE = 'date';
    const HTML = 'html';
    const OBJECT = 'object';
    const COLLECTION = 'collection';
    const ENUM = 'enumeration';
    const BOOL = 'bool';
}
