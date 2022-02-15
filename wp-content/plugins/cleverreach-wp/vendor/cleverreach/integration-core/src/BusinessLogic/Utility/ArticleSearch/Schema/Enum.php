<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Schema;

/**
 * Enumeration object used for creating EnumSchemaAttribute instance.
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Schema
 */
class Enum
{
    /**
     * Enum label.
     *
     * @var string
     */
    private $label;
    /**
     * Enum value.
     *
     * @var mixed
     */
    private $value;

    /**
     * Enum constructor.
     *
     * @param string $label Enum label.
     * @param mixed $value Enum value.
     */
    public function __construct($label, $value)
    {
        $this->label = $label;
        $this->value = $value;
    }

    /**
     * Get enum label.
     *
     * @return string
     *   If not set, returns null.
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Get enum name.
     *
     * @return mixed
     *   If not set, returns null.
     */
    public function getValue()
    {
        return $this->value;
    }
}
