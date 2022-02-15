<?php

namespace CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Interfaces;

use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Entity;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\QueryFilter\QueryFilter;

/**
 * Interface RepositoryInterface.
 *
 * @package CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Interfaces
 */
interface RepositoryInterface
{
    /**
     * Fully qualified name of this interface.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Returns full class name.
     *
     * @return string Full class name.
     */
    public static function getClassName();

    /**
     * Sets repository entity.
     *
     * @param string $entityClass Repository entity class.
     */
    public function setEntityClass($entityClass);

    /**
     * Executes select query.
     *
     * @param QueryFilter $filter Filter for query.
     *
     * @return Entity[] A list of found entities ot empty array.
     */
    public function select(QueryFilter $filter = null);

    /**
     * Executes select query and returns first result.
     *
     * @param QueryFilter $filter Filter for query.
     *
     * @return Entity|null First found entity or NULL.
     */
    public function selectOne(QueryFilter $filter = null);

    /**
     * Executes insert query and returns ID of created entity. Entity will be updated with new ID.
     *
     * @param Entity $entity Entity to be saved.
     *
     * @return int Identifier of saved entity.
     */
    public function save(Entity $entity);

    /**
     * Executes update query and returns success flag.
     *
     * @param Entity $entity Entity to be updated.
     *
     * @return bool TRUE if operation succeeded; otherwise, FALSE.
     */
    public function update(Entity $entity);

    /**
     * Executes delete query and returns success flag.
     *
     * @param Entity $entity Entity to be deleted.
     *
     * @return bool TRUE if operation succeeded; otherwise, FALSE.
     */
    public function delete(Entity $entity);

    /**
     * Removes records by a given query filter.
     *
     * @param \CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\QueryFilter\QueryFilter|null $filter
     *
     * @return int Count of deleted rows
     */
    public function deleteBy(QueryFilter $filter = null);

    /**
     * Counts records that match filter criteria.
     *
     * @param QueryFilter $filter Filter for query.
     *
     * @return int Number of records that match filter criteria.
     */
    public function count(QueryFilter $filter = null);
}
