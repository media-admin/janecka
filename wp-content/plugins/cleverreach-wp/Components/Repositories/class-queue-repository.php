<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Components\Repositories;

use CleverReach\WordPress\Components\InfrastructureServices\Config_Service;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\TaskQueueStorage;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\QueueItem;
use CleverReach\WordPress\Components\Utility\Database;

/**
 * Class Queue_Repository
 *
 * @package CleverReach\WordPress\Components\Repositories
 */
class Queue_Repository extends Base_Repository_Legacy {

	/**
	 * @var Config_Service
	 */
	private $config_service;

	/**
	 * Queue_Repository constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->table_name = Database::table( Database::QUEUE_TABLE );
	}

	/**
	 * Finds latest queue item by type.
	 *
	 * @param string $type Task type.
	 *
	 * @return array|null
	 */
	public function find_latest( $type ) {
		return $this->find_one( array( 'type' => $type ), array( 'queueTimestamp' => TaskQueueStorage::SORT_DESC ) );
	}

	/**
	 * Finds list of earliest queued queue items per queue. Following list of criteria for searching must be satisfied:
	 *      - Queue must be without already running queue items
	 *      - For one queue only one (oldest queued) item should be returned
	 *
	 * @param int $limit Result set limit. By default max 10 earliest queue items will be returned.
	 *
	 * @return array Found queue item list
	 */
	public function find_oldest_queued_items( $limit = 10 ) {
		$queues_to_be_skipped = $this->find_running_queues();

		$queued_status = QueueItem::QUEUED;
		$result        = array();
		if ( ! $this->get_config_service()->isUserOnline() ) {
			$queues_to_be_skipped[] = $this->get_config_service()->getQueueName();
		}

		$priorities = array(
			QueueItem::PRIORITY_HIGH,
			QueueItem::PRIORITY_MEDIUM,
			QueueItem::PRIORITY_LOW
		);

		foreach ( $priorities as $priority ) {
			$additional_where = '';

			if ( ! empty( $queues_to_be_skipped ) ) {
				$queue_array      = implode( "','", $queues_to_be_skipped );
				$additional_where = "AND queueName NOT IN ('{$queue_array}') ";
			}

			$query = "SELECT *
                FROM `$this->table_name`
                WHERE id IN (
                   SELECT MIN(id) AS id
                   FROM `$this->table_name`
                   WHERE
                        priority = $priority AND
                        status = '$queued_status'
                        $additional_where
                   GROUP BY queueName
                )
                ORDER BY id
                LIMIT $limit";

			$priority_items = $this->db->get_results( $query, ARRAY_A );

			$result = array_merge( $result, $priority_items );
			$limit  -= \count( $priority_items );
			if ( $limit <= 0 ) {
				break;
			}

			$queues_to_be_skipped = array_merge(
				$queues_to_be_skipped,
				array_map(
					function ( $item ) {
						return $item[ 'queueName' ];
					},
					$priority_items
				)
			);
		}

		return ! empty( $result ) ? $result : null;
	}

	/**
	 * Removes completed queue items specified by type and finished time
	 *
	 * @param string   $type      Type of queue item do delete
	 * @param int|null $timestamp Finish timestamp of queue items. All items
	 *                            which are finished before provided timestamp should be deleted.
	 *                            If not provided, remove all completed queue items with specified type.
	 */
	public function delete_completed_items_by_type( $type, $timestamp = null ) {
		$where_condition = $this->build_condition( array( 'type' => $type, 'status' => QueueItem::COMPLETED ) );
		if ( $timestamp ) {
			$where_condition .= ' AND `finishTimestamp` < ' . (int) $timestamp;
		}

		$this->db->query( "DELETE FROM `$this->table_name` " . $where_condition );
	}

	/**
	 * Removes completed queue items that are not in excluded types and have finished before a specified timestamp.
	 *
	 * @param array    $exclude_types
	 * @param int|null $timestamp Finish timestamp of queue items. All items
	 *                            which are finished before provided timestamp should be deleted.
	 *                            If not provided, remove all completed queue items with specified type.
	 * @param int      $limit
	 *
	 * @return int Number of affected rows.
	 */
	public function delete_non_excluded_items( array $exclude_types = array(), $timestamp = null, $limit = 1000 ) {
		$completed_status = QueueItem::COMPLETED;
		$where_condition  = "WHERE `status` = '{$completed_status}'";

		if ( ! empty( $exclude_types ) ) {
			$where_condition .= " AND `type` NOT IN ('" . implode( "','", $exclude_types ) . "')";
		}

		if ( $timestamp ) {
			$where_condition .= ' AND `finishTimestamp` < ' . (int) $timestamp;
		}

		return $this->db->query( "DELETE FROM `$this->table_name`" . $where_condition . " LIMIT {$limit}" );
	}

	/**
	 * Returns names of queues that currently have items in progress.
	 *
	 * @return array
	 */
	private function find_running_queues() {
		$running_queue_items = $this->find_all(
			array( 'status' => QueueItem::IN_PROGRESS ),
			array(),
			0,
			10000
		);

		return array_map( function ( $running_queue_item ) {
			return $running_queue_item[ 'queueName' ];
		}, $running_queue_items );
	}

	/**
	 * Returns an instance of configuration service.
	 *
	 * @return Config_Service
	 */
	private function get_config_service() {
		if ( null === $this->config_service ) {
			$this->config_service = ServiceRegister::getService( Configuration::CLASS_NAME );
		}

		return $this->config_service;
	}
}
