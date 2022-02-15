<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\DTO;

/**
 * Class OptionsDTO
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\DTO
 */
class OptionsDTO
{
    /**
     * Option label.
     *
     * @var string
     */
    private $name;
    /**
     * Option value.
     *
     * @var string
     */
    private $value;

    /**
     * OptionsDTO constructor.
     *
     * @param string $name Option label.
     * @param string $value Option value.
     */
    public function __construct($name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * Get option label.
     *
     * @return string
     *   Option label.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set option label.
     *
     * @param string $name Option name.
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get option value.
     *
     * @return string
     *   Option value.
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set option value.
     *
     * @param string $value Option value.
     */
    public function setValue($value)
    {
        $this->value = $value;
    }
}
