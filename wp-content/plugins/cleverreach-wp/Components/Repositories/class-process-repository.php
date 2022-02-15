<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Components\Repositories;

use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Exposed\Runnable;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\Logger;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\ProcessStorageGetException;
use CleverReach\WordPress\Components\Utility\Database;

/**
 * Class Process_Repository
 *
 * @package CleverReach\WordPress\Components\Repositories
 */
class Process_Repository extends Base_Repository_Legacy {

	/**
	 * Process_Repository constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->table_name = Database::table( Database::PROCESS_TABLE );
	}

	/**
	 * Saves process
	 *
	 * @param string   $guid Unique generated code.
	 * @param Runnable $runner Task runner.
	 */
	public function save( $guid, Runnable $runner ) {
		$this->insert(
			array(
				'id'     => $guid,
				'runner' => serialize( $runner ),
			)
		);
	}

	/**
	 * Gets runner for provided GUID
	 *
	 * @param string $guid Unique generated code.
	 *
	 * @return Runnable
	 * @throws ProcessStorageGetException Get process storage exception.
	 */
	public function get_runner( $guid ) {
		$process = $this->find_by_pk( $guid );

		if ( null === $process ) {
			throw new ProcessStorageGetException( 'Process runner with guid ' . $guid . ' does not exist.' );
		}

		return unserialize( $process['runner'] );
	}

	/**
	 * Deletes process for provided GUID
	 *
	 * @param string $guid Unique generated code.
	 */
	public function delete_process( $guid ) {
		$process = $this->find_by_pk( $guid );

		if ( null === $process ) {
			Logger::logError( 'Could not delete process with guid ' . $guid );
		} else {
			$this->delete( array( 'id' => $guid ) );
		}
	}
}
