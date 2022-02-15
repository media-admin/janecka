<?php

namespace CleverReach\WordPress\IntegrationCore\Infrastructure\ORM;

use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryClassException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Interfaces\RepositoryInterface;

/**
 * Class RepositoryRegistry.
 *
 * @package CleverReach\WordPress\IntegrationCore\Infrastructure\ORM
 */
class RepositoryRegistry
{
    /**
     * @var RepositoryInterface[]
     */
    protected static $instantiated = array();
    /**
     * @var array
     */
    protected static $repositories = array();

    /**
     * Returns an instance of repository that is responsible for handling the entity
     *
     * @param string $entityClass Class name of entity.
     *
     * @return RepositoryInterface
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public static function getRepository($entityClass)
    {
        if (!array_key_exists($entityClass, static::$repositories)) {
            throw new RepositoryNotRegisteredException("Repository for entity $entityClass not found or registered.");
        }

        if (!array_key_exists($entityClass, static::$instantiated)) {
            $repositoryClass = static::$repositories[$entityClass];
            /** @var RepositoryInterface $repository */
            $repository = new $repositoryClass();
            $repository->setEntityClass($entityClass);
            static::$instantiated[$entityClass] = $repository;
        }

        return static::$instantiated[$entityClass];
    }

    /**
     * Registers repository for provided entity class
     *
     * @param string $entityClass Class name of entity.
     * @param string $repositoryClass Class name of repository.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryClassException
     */
    public static function registerRepository($entityClass, $repositoryClass)
    {
        if (!is_subclass_of($repositoryClass, RepositoryInterface::CLASS_NAME)) {
            throw new RepositoryClassException("Class $repositoryClass is not implementation of RepositoryInterface.");
        }

        unset(static::$instantiated[$entityClass]);
        static::$repositories[$entityClass] = $repositoryClass;
    }
}
