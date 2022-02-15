<?php

namespace CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required;

/**
 * Interface Serializable
 *
 * @package CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required
 */
interface Serializable extends \Serializable
{
    /**
     * Transforms array into entity.
     *
     * @param array $array
     *
     * @return \CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Serializable
     */
    public static function fromArray($array);

    /**
     * Transforms entity to array.
     *
     * @return array
     */
   public function toArray();
}
