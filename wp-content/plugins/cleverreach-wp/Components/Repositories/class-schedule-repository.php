<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Components\Repositories;

use CleverReach\WordPress\IntegrationCore\BusinessLogic\Scheduler\Exceptions\ScheduleSaveException;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Scheduler\Interfaces\ScheduleRepositoryInterface;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Scheduler\Models\Schedule;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\QueryFilter\Operators;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\QueryFilter\QueryFilter;

/**
 * Class Schedule_Repository
 *
 * @package CleverReach\WordPress\Components\Repositories
 */
class Schedule_Repository extends Base_Repository implements ScheduleRepositoryInterface {

	/**
	 * Returns full class name.
	 *
	 * @return string Full class name.
	 */
	public static function getClassName() {
		return __CLASS__;
	}

	/**
	 * Creates or updates given schedule. If schedule id is not set, new schedule will be created otherwise
	 * update will be performed.
	 *
	 * @param Schedule $schedule         Schedule to save
	 * @param array    $additional_where List of key/value pairs that must be satisfied upon saving schedule. Key is
	 *                                   schedule property and value is condition value for that property. Example for
	 *                                   MySql storage:
	 *                                   $storage->save($schedule, array('lastUpdateTimestamp' => 123456798)) should
	 *                                   produce query UPDATE schedule_storage_table SET .... WHERE .... AND
	 *                                   lastUpdateTimestamp = 123456798
	 *
	 * @return int|null
	 *   Id of saved queue item, null if failed
	 *
	 * @throws ScheduleSaveException if schedule could not be saved
	 */
	public function saveWithCondition( Schedule $schedule, array $additional_where = array() ) {
		try {
			if ( $schedule->getId() === null ) {
				return $this->save( $schedule );
			}

			if ( $this->update_schedule( $schedule, $additional_where ) ) {
				return $schedule->getId();
			}
		} catch ( \Exception $exception ) {
			throw new ScheduleSaveException(
				'Failed to save schedule. Error: ' . $exception->getMessage(),
				0,
				$exception
			);
		}

		return null;
	}

	/**
	 * Updates schedule if conditions met
	 *
	 * @param Schedule $schedule
	 * @param array    $additional_where
	 **
	 *
	 * @return bool
	 *
	 * @throws QueryFilterInvalidParamException
	 * @throws ScheduleSaveException
	 */
	private function update_schedule( Schedule $schedule, array $additional_where ) {
		$filter = new QueryFilter();
		$filter->where( 'id', Operators::EQUALS, $schedule->getId() );

		foreach ( $additional_where as $name => $value ) {
			if ( $value === null ) {
				$filter->where( $name, Operators::NULL );
			} else {
				$filter->where( $name, Operators::EQUALS, $value ? : '' );
			}
		}

		/** @var Schedule $item */
		$item = $this->selectOne( $filter );
		if ( $item === null ) {
			throw new ScheduleSaveException( "Can not update schedule with id {$schedule->getId()} ." );
		}

		return $this->update( $schedule );
	}
}
