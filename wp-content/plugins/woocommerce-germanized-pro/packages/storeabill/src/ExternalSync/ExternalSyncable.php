<?php

namespace Vendidero\StoreaBill\ExternalSync;

defined( 'ABSPATH' ) || exit;

trait ExternalSyncable {

	abstract public function get_external_sync_handlers();

	abstract public function set_external_sync_handlers( $handlers );

	abstract public function save();

	/**
	 * @param $handler_name
	 *
	 * @return bool|SyncData
	 */
	public function get_external_sync_handler_data( $handler_name ) {
		$handlers = $this->get_external_sync_handlers();

		if ( array_key_exists( $handler_name, $handlers ) ) {
			return new SyncData( $handler_name, array_key_exists( $handler_name, $handlers ) ? $handlers[ $handler_name ] : array() );
		}

		return false;
	}

	public function has_been_externally_synced( $handler_name ) {
		$external_sync_handlers = $this->get_external_sync_handlers();

		if ( array_key_exists( $handler_name, $external_sync_handlers ) && ! empty( $external_sync_handlers[ $handler_name ]['id'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @param $handler_name
	 * @param array|SyncData $args
	 */
	public function update_external_sync_handler( $handler_name, $args = array() ) {
		$sync_data = false;

		if ( ! is_object( $args ) || ! is_a( $args, '\Vendidero\StoreaBill\ExternalSync\SyncData' ) ) {
			$sync_data = new SyncData( $handler_name, $args );
		}

		if ( ! is_a( $sync_data, '\Vendidero\StoreaBill\ExternalSync\SyncData' ) ) {
			return false;
		}

		$external_sync_handlers = $this->get_external_sync_handlers();
		$now                    = sab_string_to_datetime( 'now' );
		$data                   = array();

		$sync_data->set_last_updated( $now->getTimestamp() );

		if ( array_key_exists( $handler_name, $external_sync_handlers ) ) {
			$data = (array) $external_sync_handlers[ $handler_name ];
		}

		$new_sync_data = $sync_data->get_data();

		/**
		 * Make sure we do only update values explicitly set by arguments
		 * to allow merging sync data without losing data.
		 */
		$new_sync_data = array_intersect_key( $new_sync_data, $args );
		$data          = array_replace_recursive( $data, $new_sync_data );

		/**
		 * Force updating last updated value
		 */
		$data['last_updated'] = $sync_data->get_last_updated() ? $sync_data->get_last_updated()->getTimestamp() : null;
		$external_sync_handlers[ $handler_name ] = $data;

		$this->set_external_sync_handlers( $external_sync_handlers );
		$this->save();

		return true;
	}

	public function remove_external_sync_handler( $handler_name ) {
		$external_sync_handlers = $this->get_external_sync_handlers();

		if ( array_key_exists( $handler_name, $external_sync_handlers ) ) {
			unset( $external_sync_handlers[ $handler_name ] );

			$this->set_external_sync_handlers( $external_sync_handlers );
			$this->save();

			return true;
		}

		return false;
	}
}