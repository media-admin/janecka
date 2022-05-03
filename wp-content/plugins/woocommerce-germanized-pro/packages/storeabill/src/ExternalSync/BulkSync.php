<?php

namespace Vendidero\StoreaBill\ExternalSync;

use Vendidero\StoreaBill\Document\BulkActionHandler;

defined( 'ABSPATH' ) || exit;

class BulkSync extends BulkActionHandler {

	protected $handler_name = '';

	protected $handler = null;

	public function __construct( $args = array() ) {
		parent::__construct( $args );

		$handler_name = str_replace( 'handler_', '', substr( $this->get_id(), strpos( $this->get_id(), 'handler_' ) ) );
		$this->set_sync_handler( $handler_name );
	}

	public function set_sync_handler( $handler_name ) {
		$this->handler_name = $handler_name;
	}

	public function get_handler_name() {
		return $this->handler_name;
	}

	public function get_admin_url() {
		if ( 'customer' === $this->get_object_type() ) {
			return admin_url( 'users.php' );
		} else {
			return parent::get_admin_url();
		}
	}

	public function parse_ids_ascending() {
		if ( 'customer' === $this->get_object_type() ) {
			return false;
		} else {
			return parent::parse_ids_ascending();
		}
	}

	/**
	 * @return bool|SyncHandler
	 */
	public function get_handler() {
		if ( is_null( $this->handler ) ) {
			$this->handler = Helper::get_sync_handler( $this->get_handler_name() );
		}

		return $this->handler;
	}

	public function get_title() {
		$handler_title = $this->get_handler() ? $this->get_handler()::get_title() : $this->get_handler_name();

		return sprintf( _x( 'Sync with %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $handler_title );
	}

	protected function get_object( $object_id ) {
		return Helper::get_object( $object_id, $this->get_object_type(), $this->get_reference_type() );
	}

	public function handle() {
		$current = $this->get_current_ids();

		if ( ! $handler = $this->get_handler() ) {
			$this->add_notice( _x( 'Sync handler not found', 'storeabill-core', 'woocommerce-germanized-pro' ) );
			return;
		}

		if ( ! empty( $current ) ) {
			foreach ( $current as $object_id ) {
				if ( $object = $this->get_object( $object_id ) ) {

					if ( ! $handler->is_syncable( $object ) ) {
						continue;
					}

					/**
					 * Cancel outstanding events.
					 */
					Helper::cancel_deferred_sync( $object, $handler );

					$result = $handler->sync( $object );

					if ( is_wp_error( $result ) ) {
						foreach( $result->get_error_messages() as $error ) {
							$this->add_notice( sprintf( _x( 'Error while syncing %1$s: %2$s', 'storeabill-core', 'woocommerce-germanized-pro' ), $object->get_formatted_identifier(), $error ), 'error' );
						}
					}
				}
			}
		}
	}

	public function get_success_message() {
		return _x( 'Synced successfully', 'storeabill-core', 'woocommerce-germanized-pro' );
	}

	public function get_limit() {
		return 1;
	}

	public function get_action_name() {
		return "sync_{$this->get_object_type()}_handler_{$this->get_handler_name()}";
	}
}