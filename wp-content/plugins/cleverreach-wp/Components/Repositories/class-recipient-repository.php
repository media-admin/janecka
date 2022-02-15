<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Components\Repositories;

use CleverReach\WordPress\Components\Utility\Database;

/**
 * Class Recipient_Repository
 *
 * @package CleverReach\WordPress\Components\Repositories
 */
class Recipient_Repository extends Base_Repository_Legacy {

	/**
	 * Recipient_Repository constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->id_column = 'ID';
	}

	/**
	 * Gets IDs of all users
	 *
	 * @return array
	 */
	public function get_all_user_ids() {
		$users = get_users( array( 'fields' => array( $this->id_column ) ) );

		return $this->get_non_super_admin_user_ids( $users );
	}

	/**
	 * Gets users with provided IDs
	 *
	 * @param array $user_ids User ids.
	 * @param array $additional_params Additional parameters for user search.
	 *
	 * @return array
	 */
	public function get_users( $user_ids, $additional_params = array() ) {
		$params = array( 'include' => $user_ids );
		$params = array_merge( $params, $additional_params );

		$users = get_users( $params );

		return ! empty( $users ) && is_array( $users ) ? $users : array();
	}

	/**
	 * Gets user by email
	 *
	 * @param string $email User email.
	 *
	 * @return null|\WP_User
	 */
	public function get_user_by_email( $email ) {
		$user = get_user_by( 'email', $email );

		return $user ?: null;
	}

	/**
	 * Gets users data with provided ID
	 *
	 * @param int $user_id User id.
	 *
	 * @return \stdClass|null
	 */
	public function get_user_data_by_id( $user_id ) {
		$user_data = get_userdata( $user_id );

		return ! empty( $user_data ) ? $user_data : null;
	}

	/**
	 * Gets user IDs for provided role IDs
	 *
	 * @param array $role_ids Role ids.
	 *
	 * @return array
	 */
	public function get_user_ids_for_roles( $role_ids ) {
		$users = get_users(
			array(
				'role__in' => $role_ids,
				'fields'   => array( $this->id_column ),
			)
		);

		return $this->get_non_super_admin_user_ids( $users );
	}

	/**
	 * Update users newsletter field
	 *
	 * @param array $user_ids Users ids.
	 * @param int   $value Newsletter value.
	 */
	public function update_users_newsletter_field( $user_ids, $value ) {
		foreach ( $user_ids as $user_id ) {
			update_user_meta( $user_id, Database::get_newsletter_column(), $value );
		}
	}

	/**
	 * Gets non super admin user ids
	 *
	 * @param array $users Users entity.
	 *
	 * @return array
	 */
	private function get_non_super_admin_user_ids( $users ) {
		$user_ids = array();
		foreach ( $users as $user ) {
			if ( ! is_super_admin( $user->ID ) ) {
				$user_ids[] = $user->ID;
			}
		}

		return $user_ids;
	}
}
