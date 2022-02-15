<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Components\Repositories;

/**
 * Class Base_Repository
 *
 * @package CleverReach\WordPress\Components\Repositories
 */
class Base_Repository_Legacy {

	/**
	 * Database connection
	 *
	 * @var \wpdb
	 */
	protected $db;

	/**
	 * Table name
	 *
	 * @var string
	 */
	protected $table_name;

	/**
	 * Id column
	 *
	 * @var string
	 */
	protected $id_column = 'id';

	/**
	 * Base_Repository constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->db = $wpdb;
	}

	/**
	 * Inserts single record and returns insert ID, if available.
	 *
	 * @param array $item Array of key value pairs.
	 *
	 * @return int
	 */
	public function insert( $item ) {
		$sql  = "INSERT INTO `$this->table_name` (`" . implode( '`, `', array_keys( $item ) ) . '`) VALUES ';
		$sql .= '(' . implode( ',', $this->escape_values( $item ) ) . ')';
		$this->db->query( $sql );

		return (int) $this->db->insert_id;
	}

	/**
	 * Updates single record
	 *
	 * @param array $item An item array of record as key-value.
	 * @param array $condition List of simple search filters as key-value pair to find records to update.
	 *
	 * @return false|int
	 * @throws \InvalidArgumentException Invalid argument exception.
	 */
	public function update( $item, $condition ) {
		if ( empty( $condition ) && empty( $item[ $this->id_column ] ) ) {
			throw new \InvalidArgumentException( 'Condition needs to be set for update' );
		}

		$set = array();
		foreach ( $item as $field => $value ) {
			$set[] = "`$field` = " . $this->escape_value( $value );
		}

		$sql = "UPDATE `$this->table_name` SET " . implode( ', ', $set ) . $this->build_condition( $condition );

		return $this->db->query( $sql );
	}

	/**
	 * Deletes single record
	 *
	 * @param array $condition Where condition.
	 */
	public function delete( $condition ) {
		$this->db->query( "DELETE FROM `$this->table_name` " . $this->build_condition( $condition ) );
	}

	/**
	 * Finds record by primary key.
	 *
	 * @param mixed $id Primary key.
	 *
	 * @return array|null
	 */
	public function find_by_pk( $id ) {
		return $this->find_one( array( $this->id_column => $id ) );
	}

	/**
	 * Finds one record by provided conditions.
	 *
	 * @param array $filter_by List of simple search filters as key-value pair. Leave empty for unfiltered result.
	 * @param array $sort_by List of sorting options where key is field and value is sort direction
	 *  ("ASC" or "DESC"). Leave empty for default sorting.
	 * @param bool  $lock Lock for update.
	 *
	 * @return array
	 */
	public function find_one( $filter_by = null, $sort_by = null, $lock = false ) {
		$item = $this->find_all( $filter_by, $sort_by, 0, 1, null, $lock );

		return ! empty( $item ) ? $item[0] : null;
	}

	/**
	 * Finds all records for provided conditions ordered in provided sort.
	 *
	 * @param array $filter_by List of simple search filters as key-value pair. Leave empty for unfiltered result.
	 * @param array $sort_by List of sorting options where key is field and value is sort direction
	 *  ("ASC" or "DESC"). Leave empty for default sorting.
	 * @param int   $start From which record index result set should start.
	 * @param int   $limit Max number of records that should be returned (default is 10).
	 * @param array $select List of table columns to return. Column names could have alias as well.
	 *      If empty, all columns are returned.
	 * @param bool  $lock Lock for update.
	 *
	 * @return array
	 */
	public function find_all( $filter_by = null, $sort_by = null, $start = 0, $limit = 0, $select = null, $lock = false ) {
		$sql = 'SELECT ' . $this->build_select( $select )
			. " FROM `$this->table_name` "
			. $this->build_condition( $filter_by )
			. $this->build_order_by( $sort_by );

		$sql = $sql . ( $limit > 0 ? " LIMIT $start,$limit" : '' );
		if ( $lock ) {
			$sql .= ' FOR UPDATE';
		}

		$result = $this->db->get_results( $sql, ARRAY_A );

		return false !== $result ? $result : null;
	}

	/**
	 * Escape string value
	 *
	 * @param string $value Value to escape.
	 *
	 * @return string
	 */
	protected function escape( $value ) {
		return addslashes( $value );
	}

	/**
	 * Escape given value
	 *
	 * @param mixed $value Value to escape.
	 *
	 * @return string
	 */
	protected function escape_value( $value ) {
		return null === $value ? 'NULL' : "'" . $this->escape( $value ) . "'";
	}

	/**
	 * Escape given values
	 *
	 * @param array $values Array of values to escape.
	 *
	 * @return array
	 */
	protected function escape_values( $values ) {
		$result = array();
		foreach ( $values as $value ) {
			$result[] = $this->escape_value( $value );
		}

		return $result;
	}

	/**
	 * Build select parameters
	 *
	 * @param array $select Select columns.
	 *
	 * @return string
	 */
	private function build_select( $select ) {
		if ( empty( $select ) ) {
			return '*';
		}

		$result = array();
		foreach ( $select as $field ) {
			$result[] = '`' . $field . '`';
		}

		return implode( ', ', $result );
	}

	/**
	 * Build condition
	 *
	 * @param array $filter_by Filter by.
	 *
	 * @return string
	 */
	protected function build_condition( $filter_by ) {
		if ( empty( $filter_by ) ) {
			return '';
		}

		$where = array();
		foreach ( $filter_by as $key => $value ) {
			if ( null === $value ) {
				$where[] = "`$key` IS NULL";
			} else {
				$where[] = "`$key` = '" . $this->escape( $value ) . "'";
			}
		}

		return ' WHERE ' . implode( ' AND ', $where );
	}

	/**
	 * Build order by statement
	 *
	 * @param array $order_by Order by.
	 *
	 * @return string
	 */
	private function build_order_by( $order_by ) {
		if ( empty( $order_by ) ) {
			return '';
		}

		$sort = array();
		foreach ( $order_by as $key => $order ) {
			$sort[] = "`$key` $order";
		}

		return ' ORDER BY ' . implode( ', ', $sort );
	}
}
