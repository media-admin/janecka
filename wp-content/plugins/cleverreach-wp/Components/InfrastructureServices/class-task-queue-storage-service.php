<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Components\InfrastructureServices;

use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\Logger;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\TaskQueueStorage;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\QueueItem;
use CleverReach\WordPress\Components\Repositories\Queue_Repository;

/**
 * Class Task_Queue_Storage_Service
 *
 * @package CleverReach\WordPress\Components\InfrastructureServices
 */
class Task_Queue_Storage_Service implements TaskQueueStorage {

	/**
	 * Queue repository.
	 *
	 * @var Queue_Repository
	 */
	protected $task_queue_repository;

	/**
	 * Task_Queue_Storage_Service constructor
	 */
	public function __construct() {
		$this->task_queue_repository = new Queue_Repository();
	}

	/**
	 * Creates or updates given queue item. If queue item id is not set,
	 * new queue item will be created otherwise update will be performed.
	 *
	 * @param QueueItem $queue_item       Item to save.
	 * @param array     $additional_where List of key/value pairs that must be satisfied upon saving queue item.
	 *                                    Key is queue item property and value is condition value for that property.
	 *                                    Example for MySql storage:
	 *                                    $storage->save($queueItem, array('status' => 'queued')) should produce query
	 *                                    UPDATE queue_storage_table SET .... WHERE .... AND status => 'queued'.
	 *
	 * @return int Id of saved queue item
	 * @throws QueueItemSaveException If queue item could not be saved.
	 */
	public function save( QueueItem $queue_item, array $additional_where = array() ) {
		$item_id = null;
		try {
			$queue_item_id = $queue_item->getId();
			if ( null === $queue_item_id || $queue_item_id <= 0 ) {
				$item_id = $this->task_queue_repository->insert( $this->queue_item_to_array( $queue_item ) );
			} else {
				$this->update_queue_item( $queue_item, $additional_where );
				$item_id = $queue_item_id;
			}
		} catch ( \Exception $exception ) {
			throw new QueueItemSaveException(
				'Failed to save queue item with id: ' . $item_id,
				0,
				$exception
			);
		}

		return $item_id;
	}

	/**
	 * Finds queue item by id.
	 *
	 * @param int $id ID of a queue item to find.
	 *
	 * @return QueueItem|null Found queue item or null when queue item does not exist
	 */
	public function find( $id ) {
		$queue_item = $this->queue_item_from_array( $this->task_queue_repository->find_by_pk( $id ) );
		if ( null === $queue_item ) {
			$message = 'Failed to fetch queue item with id: ' . $id . '. Queue item  does not exist.';
			Logger::logDebug( wp_json_encode( array( 'Message' => $message ) ) );
		}

		return $queue_item;
	}

	/**
	 * Finds latest queue item by type.
	 *
	 * @param string $type    Type of a queue item to find.
	 * @param string $context Context.
	 *
	 * @return QueueItem|null Found queue item or null when queue item does not exist
	 */
	public function findLatestByType( $type, $context = '' ) {
		$queue_item = $this->queue_item_from_array( $this->task_queue_repository->find_latest( $type ) );
		if ( null === $queue_item ) {
			$message = 'Failed to fetch queue item with type: ' . $type . '. Queue item  does not exist.';
			Logger::logDebug( wp_json_encode( array( 'Message' => $message ) ) );
		}

		return $queue_item;
	}

	/**
	 * Finds list of earliest queued queue items per queue.
	 * Following list of criteria for searching must be satisfied:
	 *      - Queue must be without already running queue items
	 *      - For one queue only one (oldest queued) item should be returned
	 *
	 * @param int $limit Result set limit. By default max 10 earliest queue items will be returned.
	 *
	 * @return QueueItem[] Found queue items list
	 */
	public function findOldestQueuedItems( $limit = 10 ) {
		return $this->queue_items_from_array( $this->task_queue_repository->find_oldest_queued_items( (int) $limit ) );
	}

	/**
	 * Finds all queue items from all queues.
	 *
	 * @param array $filter_by List of simple search filters, where key is queue item property and value is condition
	 *                         value for that property. Leave empty for unfiltered result.
	 * @param array $sort_by   List of sorting options where key is queue item property and value sort direction
	 *                         ("ASC" or "DESC"). Leave empty for default sorting (ASC).
	 * @param int   $start     From which record index result set should start.
	 * @param int   $limit     Max number of records that should be returned (default is 10).
	 *
	 * @return QueueItem[] Found queue item list
	 */
	public function findAll( array $filter_by = array(), array $sort_by = array(), $start = 0, $limit = 10 ) {
		return $this->queue_items_from_array(
			$this->task_queue_repository->find_all( $filter_by, $sort_by, (int) $start, (int) $limit )
		);
	}

	/**
	 * Removes completed queue items specified by type and finished time
	 *
	 * @param string $type Type of queue item do delete
	 * @param int|null $timestamp Finish timestamp of queue items. All items
	 *      which are finished before provided timestamp should be deleted.
	 *      If not provided, remove all completed queue items with specified type.
	 */
	public function deleteCompletedQueueItems( $type, $timestamp = null ) {
		$this->task_queue_repository->delete_completed_items_by_type( $type, $timestamp );
	}

	/**
	 * Removes completed queue items that are not in excluded types and older than finished time
	 *
	 * @param array    $excludeTypes Queue item types that should be ignored. Leave empty to delete all queue item types
	 * @param int|null $timestamp    Finish timestamp condition. Only items that have finished before provided timestamp
	 *                               will be removed. Leave default null value to delete all regardless of finish timestamp
	 * @param int      $limit        Delete record limit. Max number of records to delete
	 *
	 * @return int Count of deleted rows
	 */
	public function deleteBy( array $excludeTypes = array(), $timestamp = null, $limit = 1000 ) {
		return $this->task_queue_repository->delete_non_excluded_items( $excludeTypes, $timestamp, $limit );
	}

	/**
	 * Updates database record with data from provided $queueItem.
	 *
	 * @param QueueItem $queue_item Queue item.
	 * @param array     $conditions Array of update conditions.
	 *
	 * @throws QueueItemDeserializationException Queue item deserialization exception.
	 * @throws QueueItemSaveException Queue item save exception.
	 */
	private function update_queue_item( $queue_item, array $conditions = array() ) {
		$conditions = array_merge( $conditions, array( 'id' => $queue_item->getId() ) );

		$item = $this->task_queue_repository->find_one( $conditions, null, true );
		$this->check_if_record_with_where_conditions_exists( $item, $conditions );

		$item = array_merge( $item, $this->queue_item_to_array( $queue_item ) );
		$this->task_queue_repository->update( $item, $conditions );
	}

	/**
	 * Validates if item exists.
	 *
	 * @param array $item Queue item.
	 * @param array $condition WHERE condition.
	 *
	 * @throws QueueItemSaveException Queue item save exception.
	 */
	private function check_if_record_with_where_conditions_exists( $item, $condition ) {
		if ( empty( $item ) ) {
			$message = 'Failed to save queue item, update condition(s) not met.';
			Logger::logDebug(
				wp_json_encode(
					array(
						'Message'        => $message,
						'WhereCondition' => $condition,
					)
				)
			);

			throw new QueueItemSaveException( $message );
		}
	}

	/**
	 * Serializes instance of QueueItem to array of values.
	 *
	 * @param QueueItem $queue_item Queue item.
	 *
	 * @return array
	 * @throws QueueItemDeserializationException  Queue item deserialization exception.
	 */
	private function queue_item_to_array( QueueItem $queue_item ) {
		return array(
			'id'                    => $queue_item->getId(),
			'type'                  => $queue_item->getTaskType(),
			'status'                => $queue_item->getStatus(),
			'priority'              => $queue_item->getPriority(),
			'queueName'             => $queue_item->getQueueName(),
			'progress'              => $queue_item->getProgressBasePoints(),
			'lastExecutionProgress' => $queue_item->getLastExecutionProgressBasePoints(),
			'retries'               => $queue_item->getRetries(),
			'failureDescription'    => $queue_item->getFailureDescription(),
			'serializedTask'        => $queue_item->getSerializedTask(),
			'createTimestamp'       => $queue_item->getCreateTimestamp(),
			'queueTimestamp'        => $queue_item->getQueueTimestamp(),
			'lastUpdateTimestamp'   => $queue_item->getLastUpdateTimestamp(),
			'startTimestamp'        => $queue_item->getStartTimestamp(),
			'finishTimestamp'       => $queue_item->getFinishTimestamp(),
			'failTimestamp'         => $queue_item->getFailTimestamp(),
		);
	}

	/**
	 * Transforms array of values to QueueItem instance.
	 *
	 * @param array $item Array queue item.
	 *
	 * @return QueueItem
	 */
	private function queue_item_from_array( $item ) {
		if ( empty( $item ) ) {
			return null;
		}

		$queue_item = new QueueItem();
		$queue_item->setId( (int) $item['id'] );
		$queue_item->setStatus( $item['status'] );
		$queue_item->setQueueName( $item['queueName'] );
		$queue_item->setPriority( $item['priority'] );
		$queue_item->setProgressBasePoints( (int) $item['progress'] );
		$queue_item->setLastExecutionProgressBasePoints( (int) $item['lastExecutionProgress'] );
		$queue_item->setRetries( (int) $item['retries'] );
		$queue_item->setFailureDescription( $item['failureDescription'] );
		$queue_item->setSerializedTask( $item['serializedTask'] );
		$queue_item->setCreateTimestamp( (int) $item['createTimestamp'] );
		$queue_item->setQueueTimestamp( (int) $item['queueTimestamp'] );
		$queue_item->setLastUpdateTimestamp( (int) $item['lastUpdateTimestamp'] );
		$queue_item->setStartTimestamp( (int) $item['startTimestamp'] );
		$queue_item->setFinishTimestamp( (int) $item['finishTimestamp'] );
		$queue_item->setFailTimestamp( (int) $item['failTimestamp'] );

		return $queue_item;
	}

	/**
	 * Transforms array of values to array of QueueItem instances.
	 *
	 * @param array $items Array of queue items.
	 *
	 * @return QueueItem[]
	 */
	private function queue_items_from_array( $items ) {
		$result = array();
		if ( empty( $items ) ) {
			return $result;
		}

		foreach ( $items as $item ) {
			$result[] = $this->queue_item_from_array( $item );
		}

		return $result;
	}
}
