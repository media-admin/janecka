<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Scheduler\Interfaces;

use CleverReach\WordPress\IntegrationCore\BusinessLogic\Scheduler\Exceptions\ScheduleSaveException;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Scheduler\Models\Schedule;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Interfaces\RepositoryInterface;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\QueryFilter\QueryFilter;

/**
 * Interface ScheduleRepositoryInterface
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Scheduler\Interfaces
 * @method Schedule[] select(QueryFilter $filter = null)
 * @method Schedule selectOne(QueryFilter $filter = null)
 */
interface ScheduleRepositoryInterface extends RepositoryInterface
{
    /**
     * Creates or updates given schedule. If schedule id is not set, new schedule will be created otherwise
     * update will be performed.
     *
     * @param Schedule $schedule Schedule to save
     * @param array $additionalWhere List of key/value pairs that must be satisfied upon saving schedule. Key is
     *  schedule property and value is condition value for that property. Example for MySql storage:
     *  $storage->save($schedule, array('lastUpdateTimestamp' => 123456798)) should produce query
     *  UPDATE schedule_storage_table SET .... WHERE .... AND lastUpdateTimestamp = 123456798
     *
     * @return int Id of saved queue item
     * @throws ScheduleSaveException if schedule could not be saved
     */
    public function saveWithCondition(Schedule $schedule, array $additionalWhere = array());
}
