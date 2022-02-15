<?php

namespace Vendidero\StoreaBill\Interfaces;

use Vendidero\StoreaBill\ExternalSync\SyncData;

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
interface ExternalSyncable {

	public function get_id();

	public function get_formatted_identifier();

	public function get_type();

	public function has_been_externally_synced( $handler_name );

	public function update_external_sync_handler( $handler_name, $args = array() );

	public function remove_external_sync_handler( $handler_name );

	/**
	 * @param $handler_name
	 *
	 * @return bool|SyncData
	 */
	public function get_external_sync_handler_data( $handler_name );
}