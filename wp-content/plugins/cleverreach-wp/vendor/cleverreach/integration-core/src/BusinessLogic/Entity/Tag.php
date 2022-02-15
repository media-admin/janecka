<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity;

/**
 * Class Tag
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity
 */
class Tag extends AbstractTag
{
    /**
     * Tag constructor.
     *
     * @param string $name Tag name.
     * @param string $type Tag type.
     */
    public function __construct($name, $type)
    {
        parent::__construct($name, $type);
    }
}
