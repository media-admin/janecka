<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Components\Repositories;

use CleverReach\WordPress\Components\Utility\Database;
use CleverReach\WordPress\Components\Utility\Helper;

/**
 * Class Roles_Repository
 *
 * @package CleverReach\WordPress\Components\Repositories
 */
class Role_Repository extends Base_Repository_Legacy {

	/**
	 * Role_Repository constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->table_name = Database::table( Database::ROLES_TABLE );
	}

	/**
	 * Gets all groups in default admin language as array of items with 'id' as key and array with 'name' keys.
	 * Example:
	 * [
	 *      1 => ['name' => 'Default'],
	 *      2 => ['name' => 'Business'],
	 * ]
	 *
	 * @return array
	 */
	public function get_all_roles() {
		$roles         = get_option( $this->table_name );
		$result        = array();
		$language_code = Helper::get_sync_language();
		load_textdomain( "admin-$language_code", WP_LANG_DIR . "/admin-$language_code.mo" );
		$translations = get_translations_for_domain( "admin-$language_code" );
		foreach ( $roles as $role => $details ) {
			// For single site skip administrator role.
			if ( ! is_multisite() && ( 'administrator' === $role ) ) {
				continue;
			}

			$result[ $role ] = $translations->translate( $details['name'], 'User role' );
		}

		return $result;
	}
}
