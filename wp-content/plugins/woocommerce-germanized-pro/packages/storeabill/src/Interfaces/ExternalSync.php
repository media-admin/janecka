<?php

namespace Vendidero\StoreaBill\Interfaces;

use Vendidero\StoreaBill\ExternalSync\SyncException;

/**
 * Invoice
 *
 * @package  Germanized/StoreaBill/Interfaces
 * @version  1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * External sync.
 */
interface ExternalSync {

	public static function get_name();

	public static function get_title();

	public static function get_description();

	public static function get_supported_object_types();

	public static function is_object_type_supported( $type );

	public static function get_api_type();

	public static function get_icon();

	public static function get_admin_url();

	public static function get_help_link();

	/**
	 * @return Auth
	 */
	public function get_auth_api();

	public function is_sandbox();

	/**
	 * @param ExternalSyncable $object
	 *
	 * @return \WP_Error|boolean
	 */
	public function sync( &$object );

	/**
	 * @param ExternalSyncable $object
	 *
	 * @return bool
	 */
	public function has_synced( $object );

	/**
	 * @param ExternalSyncable $object
	 *
	 * @return null|\WC_DateTime
	 */
	public function get_date_last_synced( $object );

	/**
	 * @param ExternalSyncable $object
	 *
	 * @return bool
	 */
	public function is_syncable( $object );

	/**
	 * @param string $object_type
	 *
	 * @return bool
	 */
	public function enable_auto_sync( $object_type );

	public function is_enabled();

	/**
	 * @param Customer
	 *
	 * @return []|boolean
	 */
	public function get_customer_details( $customer );

	/**
	 * @param $search
	 *
	 * @return []
	 */
	public function search_customers( $search );
}
