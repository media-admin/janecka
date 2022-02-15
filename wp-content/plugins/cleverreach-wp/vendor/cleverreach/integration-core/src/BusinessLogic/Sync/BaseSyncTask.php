<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync;

use CleverReach\WordPress\IntegrationCore\BusinessLogic\Interfaces\Proxy;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Task;

/**
 * Class BaseSyncTask
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync
 */
abstract class BaseSyncTask extends Task
{
    /**
     * Instance of proxy class.
     *
     * @var Proxy
     */
    private $proxy;

    /**
     * Gets proxy class instance.
     *
     * @return \CleverReach\WordPress\IntegrationCore\BusinessLogic\Proxy
     *   Instance of proxy class.
     */
    protected function getProxy()
    {
        if ($this->proxy === null) {
            $this->proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
        }

        return $this->proxy;
    }
}
