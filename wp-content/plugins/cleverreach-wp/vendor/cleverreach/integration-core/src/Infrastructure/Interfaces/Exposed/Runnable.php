<?php

namespace CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Exposed;

use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Serializable;

/**
 * Interface Runnable
 *
 * @package CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Exposed
 */
interface Runnable extends Serializable
{
    /**
     * Starts runnable run logic.
     */
    public function run();
}
