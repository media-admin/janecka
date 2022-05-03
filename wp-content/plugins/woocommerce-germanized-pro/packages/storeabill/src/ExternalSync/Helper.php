<?php

namespace Vendidero\StoreaBill\ExternalSync;

use Vendidero\StoreaBill\Interfaces\ExternalSync;
use Vendidero\StoreaBill\Interfaces\ExternalSyncable;
use Vendidero\StoreaBill\Invoice\Invoice;
use Vendidero\StoreaBill\Package;
use Vendidero\StoreaBill\References\Customer;

defined( 'ABSPATH' ) || exit;

class Helper {

	protected static $handler = null;

	public static function init() {
		if ( ! Package::enable_accounting() ) {
			return;
		}

		add_action( 'storeabill_external_sync_callback', array( __CLASS__, 'sync_callback' ), 10, 4 );
		add_action( 'init', array( __CLASS__, 'setup_sync_filters' ), 50 );

		add_filter( "storeabill_admin_invoice_actions", array( __CLASS__, 'add_invoice_actions' ), 10, 2 );
		add_filter( "storeabill_admin_invoice_cancellation_actions", array( __CLASS__, 'add_invoice_actions' ), 10, 2 );

		add_action( 'admin_notices', array( __CLASS__, 'auth_refresh_notice' ) );
	}

	/**
	 * @param SyncHandler $handler
	 *
	 * @return void
	 */
    public static function auth_successful( $handler ) {
	    if ( $needs_refresh = get_option( 'storeabill_sync_handler_needs_oauth_refresh' ) ) {
		    $needs_refresh = is_array( $needs_refresh ) ? array_filter( $needs_refresh ) : array( $needs_refresh );
            $needs_refresh = array_diff( $needs_refresh, array( $handler->get_name() ) );

            if ( empty( $needs_refresh ) ) {
	            delete_option( 'storeabill_sync_handler_needs_oauth_refresh' );
            } else {
	            update_option( 'storeabill_sync_handler_needs_oauth_refresh', $needs_refresh );
            }
	    }
    }

	public static function auth_refresh_notice() {
		if ( $needs_refresh = get_option( 'storeabill_sync_handler_needs_oauth_refresh' ) ) {
			$needs_refresh = is_array( $needs_refresh ) ? array_filter( $needs_refresh ) : array( $needs_refresh );

			foreach( $needs_refresh as $handler_name ) {
				if ( ( $handler = self::get_sync_handler( $handler_name ) ) && $handler->is_enabled() && 'oauth' === $handler->get_auth_api()->get_type() && $handler->get_auth_api()->is_connected() ) {
					?>
					<div class="notice notice-error error">
						<p><?php printf( _x( 'Your %1$s connection needs a refresh, as the API scope has changed. Please <a href="%2$s">refresh your API connection</a> now.', 'storeabill-core', 'woocommerce-germanized-pro' ), $handler->get_title(), $handler->get_admin_url() ); ?></p>
					</div>
					<?php
				}
			}
		}
	}

	/**
	 * @param string $reference_id The external reference id.
	 * @param string $sync_handler The sync handler name.
	 * @param string $object_type The object type
	 *
	 * @return false|Invoice
	 */
	public static function get_object_by_reference_id( $reference_id, $sync_handler, $object_type = 'invoice' ) {
		$object = false;
		$args   = array(
			'meta_query' => array(
				array(
					'key'     => '_external_sync_handlers',
					'value'   => $reference_id,
					'compare' => 'LIKE'
				),
				array(
					'key'     => '_external_sync_handlers',
					'value'   => $sync_handler,
					'compare' => 'LIKE'
				)
			)
		);

		switch( $object_type ) {
			case "invoice":
			case "invoice_simple":
			case "invoice_cancellation":
				$invoices = sab_get_invoices( array_merge( $args, array(
					'type'  => $object_type === 'invoice' ? array( 'simple', 'cancellation' ) : str_replace( 'invoice_', '', $object_type ),
					'limit' => 1,
				) ) );

				if ( ! empty( $invoices ) ) {
					$object = $invoices[0];
				}
				break;
		}

		return $object;
	}

	/**
	 * @param $actions
	 * @param Invoice $document
	 *
	 * @return mixed
	 */
	public static function add_invoice_actions( $actions, $document ) {

		if ( ! $document->is_finalized() ) {
			return $actions;
		}

		foreach( self::get_available_sync_handlers() as $handler ) {

			if ( ! $handler->is_syncable( $document ) ) {
				continue;
			}

			$url = add_query_arg( array(
				'action'      => 'storeabill_admin_external_sync',
				'object_id'   => $document->get_id(),
				'object_type' => $document->get_type(),
				'handler'     => $handler::get_name(),
				'do_ajax'     => true,
			), admin_url( 'admin-ajax.php' ) );

			$name    = _x( 'Sync', 'storeabill-core', 'woocommerce-germanized-pro' );
			$classes = '';

			if ( $date = $handler->get_date_last_synced( $document ) ) {
				$name     = sprintf( _x( 'last synced on %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $date->date_i18n( sab_date_format() ) );
				$classes .= ' inactive';
			}

			$actions["sync_{$handler::get_name()}"] = array(
				'url'     => wp_nonce_url( $url, 'sab-external-sync' ),
				'name'    => $name,
				'action'  => 'sync',
				'icon'    => $handler::get_icon(),
				'classes' => $classes,
			);
		}

		return $actions;
	}

	public static function setup_sync_filters() {
		foreach( self::get_available_sync_handlers() as $handler ) {
			$handler_name = $handler::get_name();

			foreach( $handler::get_supported_object_types() as $object_type ) {
				if ( $handler->enable_auto_sync( $object_type ) ) {

					$sync_callback = function( $object_id, $object = false ) use ( $handler_name, $object_type ) {
						/**
						 * Support hooks with one parameter (the object instance) only.
						 */
						if ( is_a( $object_id, '\Vendidero\StoreaBill\Interfaces\ExternalSyncable' ) ) {
							$object = $object_id;
						}

						$allow_defer = sab_allow_deferring( 'sync' );

						/**
						 * By default defer syncing to make sure requests are not blocked.
						 */
						if ( apply_filters( 'storeabill_defer_external_auto_sync', $allow_defer, $object, $handler_name ) ) {
							self::sync_deferred( $object, $handler_name );
						} else {
							$result = self::sync( $object, $handler_name );
						}
					};

					$sync_user_callback = function( $user_id ) use ( $sync_callback, $handler_name ) {
						if ( ! current_user_can( 'manage_storeabill' ) ) {
							return;
						}

						if ( $customer = Customer::get_customer( $user_id ) ) {
							if ( empty( $customer->get_last_name() ) ) {
								return;
							}

							$sync_callback( $customer );
						}
					};

					switch( $object_type ) {
						case "invoice":
						case "invoice_cancellation":
							/**
							 * Use the general rendered hook here. This hook is executed after a document has been rendered successfully.
							 * Rendering will be triggered on finalizing the document that's why no additional finalize hook is necessary here.
							 */
							add_action( "storeabill_{$object_type}_rendered", $sync_callback, 10 );
							/**
							 * Add an additional sync as soon as the payment status changes.
							 */
							add_action( "storeabill_{$object_type}_payment_status_complete", $sync_callback, 10, 2 );
							add_action( "storeabill_{$object_type}_payment_status_pending", $sync_callback, 10, 2 );
							break;
						case "customer":
							add_action( 'personal_options_update', $sync_user_callback, 100 );
							add_action( 'edit_user_profile_update', $sync_user_callback, 100 );
							break;
						default:
							break;
					}

					do_action( "storeabill_setup_{$object_type}_sync_filters", $handler, $handler_name, $sync_callback, $sync_user_callback );
				}
			}
		}

		do_action( 'storeabill_external_sync_handlers_setup_auto_sync' );
	}

	/**
	 * @return SyncHandler[]
	 */
	public static function get_sync_handlers() {
		if ( is_null( self::$handler ) ) {
			self::$handler = array();

			$handlers = apply_filters( 'storeabill_external_sync_handlers', array() );

			foreach( $handlers as $handler ) {

				if ( class_exists( $handler ) ) {
					try {
						$class = new \ReflectionClass($handler );

						if ( $class->implementsInterface( '\Vendidero\StoreaBill\Interfaces\ExternalSync' ) ) {
							self::$handler[ $handler::get_name() ] = new $handler();
						}
					} catch( \Exception $e ) {}
				}
			}
		}

		return self::$handler;
	}

	/**
	 * @return SyncHandler[]
	 */
	public static function get_available_sync_handlers() {
		$available = array();

		foreach( self::get_sync_handlers() as $helper ) {
			if ( $helper->is_enabled() ) {
				$available[ $helper::get_name() ] = $helper;
			}
		}

		return $available;
	}

	/**
	 * @param $name
	 *
	 * @return SyncHandler|boolean
	 */
	public static function get_sync_handler( $name ) {
		$handlers = self::get_sync_handlers();
		$handler  = false;

		if ( array_key_exists( $name, $handlers ) ) {
			$handler = $handlers[ $name ];
		}

		return apply_filters( 'storeabill_external_sync_handler', $handler, $name );
	}

	public static function get_object( $object_id, $object_type, $reference_type = '' ) {
		$object = false;

		if ( $document_type = sab_get_document_type( $object_type ) ) {
			$object = sab_get_document( $object_id, $object_type );
		} elseif ( 'customer' === $object_type ) {
			$object = \Vendidero\StoreaBill\References\Customer::get_customer( $object_id, $reference_type );
		}

		return $object;
	}

	public static function sync_callback( $object_id, $object_type, $handler, $reference_type = '' ) {
		$object = self::get_object( $object_id, $object_type, $reference_type );

		if ( is_a( $object, '\Vendidero\StoreaBill\Interfaces\ExternalSyncable' ) ) {
			$result = self::sync( $object, $handler );

			if ( is_wp_error( $result ) ) {
				foreach( $result->get_error_messages() as $message ) {
					Package::log( sprintf( 'Error while syncing %1$d of type %2$s with %3$s: %4$s', $object_id, $object_type, $handler, $message ), 'info', 'sync' );
				}
			}
		}
	}

	public static function cancel_deferred_sync( $object, $handler ) {
		$args = array(
			'object_id'      => $object->get_id(),
			'object_type'    => $object->get_type(),
			'handler'        => is_a( $handler, '\Vendidero\StoreaBill\Interfaces\ExternalSync' ) ? $handler::get_name() : $handler,
			'reference_type' => ''
		);

		if ( is_a( $object, '\Vendidero\StoreaBill\Interfaces\Reference' ) ) {
			$args['reference_type'] = $object->get_reference_type();
		}

		$queue = WC()->queue();

		/**
		 * Cancel outstanding events and queue new.
		 */
		$queue->cancel_all( 'storeabill_external_sync_callback', $args, 'storeabill-external-sync' );
	}

	/**
	 * @param ExternalSyncable $object
	 * @param string|SyncHandler $handler
	 */
	public static function sync_deferred( $object, $handler ) {
		$args = array(
			'object_id'      => $object->get_id(),
			'object_type'    => $object->get_type(),
			'handler'        => is_a( $handler, '\Vendidero\StoreaBill\Interfaces\ExternalSync' ) ? $handler::get_name() : $handler,
			'reference_type' => ''
		);

		if ( is_a( $object, '\Vendidero\StoreaBill\Interfaces\Reference' ) ) {
			$args['reference_type'] = $object->get_reference_type();
		}

		$queue = WC()->queue();

		/**
		 * Cancel outstanding events and queue new.
		 */
		self::cancel_deferred_sync( $object, $handler );

		$queue->schedule_single(
			time(),
			'storeabill_external_sync_callback',
			$args,
			'storeabill-external-sync'
		);
	}

	/**
	 * @param ExternalSyncable $object
	 * @param ExternalSync|string $handler
	 *
	 * @return boolean|\WP_Error
	 */
	public static function sync( $object, $handler ) {
		$errors = new \WP_Error();

		if ( ! is_a( $handler, '\Vendidero\StoreaBill\Interfaces\ExternalSync' ) ) {
			$handler = self::get_sync_handler( $handler );
		}

		if ( ! is_a( $handler, '\Vendidero\StoreaBill\Interfaces\ExternalSync' ) ) {
			$errors->add( 'sync-error', _x( 'Sync handler not found', 'storeabill-core', 'woocommerce-germanized-pro' ) );

			return $errors;
		}

		if ( ! $handler->is_syncable( $object ) ) {
			$errors->add( 'sync-error', sprintf( _x( 'This object is not supported by %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $handler::get_title() ) );

			return $errors;
		}

		try {
			/**
			 * Cancel deferred syncs to prevent overrides.
			 */
			self::cancel_deferred_sync( $object, $handler );

			$result = $handler->sync( $object );

			if ( is_wp_error( $result ) ) {
				Package::log( sprintf( 'An error occurred while syncing a %1$s with ID %2$s. You may want to investigate the issue.', $object->get_type(), $object->get_id() ), 'error', 'sync' );

				return $result;
			}

			return true;
		} catch( SyncException $e ) {
			/* translators: 1: external sync handler title 2: error message */
			$errors->add( $e->getErrorCode(), sprintf( _x( 'An error occurred while syncing with %1$s: %2$s', 'storeabill-core', 'woocommerce-germanized-pro' ), $handler::get_title(), $e->getMessage() ) );
		}

		if ( sab_wp_error_has_errors( $errors ) ) {
			return $errors;
		}

		return true;
	}
}