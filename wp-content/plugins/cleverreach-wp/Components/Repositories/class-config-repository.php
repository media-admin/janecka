<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Components\Repositories;

use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\ConfigRepositoryInterface;
use CleverReach\WordPress\Components\Utility\Database;

/**
 * Class Config_Repository
 *
 * @package CleverReach\WordPress\Components\Repositories
 */
class Config_Repository extends Base_Repository_Legacy implements ConfigRepositoryInterface {

	/**
	 * Config_Repository constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->table_name = Database::table( Database::CONFIG_TABLE );
		$this->id_column  = 'key';
	}

	/**
	 * Get value by key.
	 *
	 * @param string $key Key to find value.
	 *
	 * @return string|int
	 */
	public function get( $key ) {
		$config = $this->find_by_pk( $key );

		return ( false !== $config && null !== $config ) ? $config['value'] : null;
	}

	/**
	 * Set config value for key
	 *
	 * @param string $key Key of config.
	 * @param mixed  $value Value pf config.
	 *
	 * @return int|bool
	 */
	public function set( $key, $value ) {
		$result = $this->valueExists( $key ) ?
			$this->update( array( 'value' => $value ), array( 'key' => $key ) ) :
			$this->insert(
				array(
					'key'   => $key,
					'value' => $value,
				)
			);

		return false !== $result;
	}

	/**
	 * Return value if exists for given key
	 *
	 * @param mixed $key Key to check.
	 *
	 * @return bool
	 */
	private function valueExists( $key ) {
		$config = $this->find_by_pk( $key );

		return ! empty( $config );
	}
}
