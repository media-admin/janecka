<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic;

use CleverReach\WordPress\IntegrationCore\BusinessLogic\Scheduler\Interfaces\ScheduleRepositoryInterface;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Scheduler\Models\Schedule;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryClassException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\RepositoryRegistry as InfrastructureRepositoryRegistry;

/**
 * Class RepositoryRegistry
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic
 */
class RepositoryRegistry extends InfrastructureRepositoryRegistry
{
    /**
     * Returns schedule repository
     *
     * @return ScheduleRepositoryInterface
     *
     * @throws RepositoryClassException
     * @throws RepositoryNotRegisteredException
     */
    public static function getScheduleRepository()
    {
        /** @var ScheduleRepositoryInterface $repository */
        $repository = static::getRepository(Schedule::getClassName());
        if (!($repository instanceof ScheduleRepositoryInterface)) {
            throw new RepositoryClassException('Instance class is not implementation of ScheduleRepositoryInterface');
        }

        return $repository;
    }
}
