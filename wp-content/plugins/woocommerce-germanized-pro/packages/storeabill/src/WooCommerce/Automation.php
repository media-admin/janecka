<?php

namespace Vendidero\StoreaBill\WooCommerce;

use Vendidero\StoreaBill\Package;
use Vendidero\StoreaBill\Utilities\CacheHelper;

defined( 'ABSPATH' ) || exit;

class Automation {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'setup' ), 50 );

		add_action( 'storeabill_order_auto_sync_callback', array( __CLASS__, 'auto_sync_callback' ), 10 );
	}

	public static function create_invoices() {
		return 'yes' === Package::get_setting( 'invoice_woo_order_auto_create' );
	}

	public static function get_invoice_timing() {
		return Package::get_setting( 'invoice_woo_order_auto_create_timing' );
	}

	public static function has_invoice_timing( $timing ) {
		return $timing === self::get_invoice_timing();
	}

	public static function get_invoice_payment_method_statuses( $method_id = ''  ) {
		$options  = (array) Package::get_setting( 'invoice_woo_order_payment_method_statuses' );
		$statuses = $options;

		if ( ! empty( $method_id ) ) {
			$statuses = array();

			if ( array_key_exists( $method_id, $options ) ) {
				$statuses = $options[ $method_id ];
			}
		}

		$statuses = array_filter( $statuses );

		return $statuses;
	}

	public static function get_invoice_emails() {
		$emails = (array) Package::get_setting( 'invoice_woo_order_auto_create_emails' );

		return array_filter( $emails );
	}

	public static function get_invoice_order_statuses() {
		$statuses = (array) Package::get_setting( 'invoice_woo_order_auto_create_statuses' );

		return array_filter( $statuses );
	}

	public static function finalize_invoices() {
		return 'yes' === Package::get_setting( 'invoice_woo_order_auto_finalize' );
	}

	public static function invoice_gateway_specific() {
		return 'yes' === Package::get_setting( 'invoice_woo_order_auto_payment_gateway_specific' );
	}

	public static function get_invoice_gateways() {
		$gateways = (array) Package::get_setting( 'invoice_woo_order_auto_payment_gateways' );

		return array_filter( $gateways );
	}

	public static function setup() {
		if ( self::create_invoices() ) {
			if ( self::has_invoice_timing( 'checkout' ) ) {
				self::sync_invoices_on_checkout();
			} elseif( self::has_invoice_timing( 'paid' ) ) {
				self::sync_invoices_on_order_paid();
			} elseif( self::has_invoice_timing( 'status' ) ) {
				self::sync_invoices_on_order_status();
			} elseif( self::has_invoice_timing( 'status_payment_method' ) ) {
				self::sync_invoices_on_order_payment_method_status();
			} elseif( self::has_invoice_timing( 'email' ) ) {
				self::sync_invoices_on_transactional_email();
			}
		}

		/**
		 * Auto cancel invoices for failed and cancelled orders
		 */
		add_action( 'woocommerce_order_status_cancelled', array( __CLASS__, 'cancel_order_invoices' ), 10 );
		add_action( 'woocommerce_order_status_failed', array( __CLASS__, 'cancel_order_invoices' ), 10 );
		add_action( 'woocommerce_order_status_refunded', array( __CLASS__, 'cancel_order_invoices' ), 10 );
	}

	public static function cancel_order_invoices( $order_id ) {
		if ( $order = Helper::get_order( $order_id ) ) {
			$order->cancel( 'order_cancelled' );
		}
	}

	protected static function sync_invoices_on_transactional_email() {
		add_filter( 'woocommerce_email_attachments', function( $attachments, $email_id, $object, $email ) {
			$mails = self::get_invoice_emails();

			if ( ! in_array( $email_id, $mails ) ) {
				return $attachments;
			}

			if ( is_a( $object, 'WC_Order' ) && $order = Helper::get_order( $object ) ) {
				self::sync_invoices( $order, array( 'allow_defer' => false ) );

				foreach( $order->get_finalized_invoices() as $invoice ) {
					if ( $invoice->has_file() ) {
						$attachments[] = $invoice->get_path();
					}
				}
			}

			return $attachments;
		}, 20, 4 );
	}

	protected static function sync_invoices_on_order_payment_method_status() {
		add_action( 'woocommerce_order_status_changed', function( $order_id, $from, $to ) {
			if ( $order = Helper::get_order( $order_id ) ) {
				$payment_method = $order->get_payment_method();
				$statuses       = empty( $payment_method ) ? array() : array_map( array( 'Vendidero\StoreaBill\WooCommerce\Helper', 'clean_order_status' ), self::get_invoice_payment_method_statuses( $payment_method ) );

				foreach( $statuses as $status ) {
					if ( $to === $status ) {
						self::sync_invoices( $order_id );
						return;
					}
				}
			}
		}, 20, 3 );

		/**
		 * Fallback to after checkout for gateways without specific statuses or default order status.
		 */
		add_action( 'woocommerce_checkout_order_processed', function( $order_id ) {
			if ( $order = Helper::get_order( $order_id ) ) {
				$payment_method = $order->get_payment_method();
				$statuses       = empty( $payment_method ) ? array() : array_map( array( 'Vendidero\StoreaBill\WooCommerce\Helper', 'clean_order_status' ), self::get_invoice_payment_method_statuses( $payment_method ) );
				$status         = Helper::clean_order_status( $order->get_status() );

				if ( empty( $statuses ) || in_array( $status, $statuses ) ) {
					self::sync_invoices( $order_id );
				}
			}
		}, 50, 1 );
	}

	protected static function sync_invoices_on_order_status() {
		$callback = function( $order_id ) {
			self::sync_invoices( $order_id );
		};

		$statuses = array_map( array( '\Vendidero\StoreaBill\WooCommerce\Helper', 'clean_order_status' ), self::get_invoice_order_statuses() );

		$new_order_callback = function( $order ) use ( $statuses ) {
			$sync = false;

			if ( $sab_order = Helper::get_order( $order ) ) {
				if ( in_array( $sab_order->get_status(), $statuses ) ) {
					$sync = true;
				}
			}

			if ( $sync ) {
				self::sync_invoices( $order );
			}
		};

		/**
		 * The issue with the woocommerce_new_order hook is that this hook is getting executed before order items
		 * has been stored. This will lead to items not being available.
		 *
		 * Workaround: Hook into the woocommerce_after_order_object_save instead after an order has been created.
		 * Make sure to prevent multiple checks per request.
		 */
		add_action( 'woocommerce_new_order', function( $order_id ) use ( $new_order_callback ) {
			add_action( 'woocommerce_after_order_object_save', function( $order ) use ( $order_id, $new_order_callback ) {
				if ( $order_id === $order->get_id() ) {
					$new_order_callback( $order );
				}
			}, 150 );
		} );

		foreach( $statuses as $order_status ) {
			add_action( "woocommerce_order_status_{$order_status}", $callback, 20 );
		}
	}

	protected static function sync_invoices_on_order_paid() {
		$callback = function( $order_id ) {
			self::sync_invoices( $order_id );
		};

		$statuses = wc_get_is_paid_statuses();

		$new_order_callback = function( $order_id ) use ( $statuses ) {
			$sync = false;

			if ( $order = Helper::get_order( $order_id ) ) {
				if ( in_array( $order->get_status(), $statuses ) ) {
					$sync = true;
				}
			}

			if ( $sync ) {
				self::sync_invoices( $order_id );
			}
		};

		add_action( 'woocommerce_new_order', function( $order_id ) use ( $new_order_callback ) {
			add_action( 'woocommerce_after_order_object_save', function( $order ) use ( $order_id, $new_order_callback ) {
				if ( $order_id === $order->get_id() ) {
					$new_order_callback( $order );
				}
			}, 150 );
		} );

		add_action( 'woocommerce_payment_complete', $callback, 20 );

		foreach( $statuses as $status ) {
			add_action( "woocommerce_order_status_{$status}", $callback, 20 );
		}
	}

	protected static function sync_invoices_on_checkout() {
		add_action( 'woocommerce_checkout_order_processed', function( $order_id ) {
			self::sync_invoices( $order_id );
		}, 50, 1 );
	}

	public static function cancel_deferred_sync( $args ) {
		$queue = WC()->queue();

		/**
		 * Cancel outstanding events and queue new.
		 */
		$queue->cancel_all( 'storeabill_order_auto_sync_callback', $args, 'storeabill-order-sync' );
	}

	/**
	 * @param Order $order
	 * @param array $args
	 *
	 * @return bool|\WP_Error
	 */
	public static function sync_invoices( $order, $args = array() ) {
		$args = wp_parse_args( $args, array(
			'allow_defer' => sab_allow_deferring( 'auto' ),
		) );

		if ( is_numeric( $order ) || is_a( $order, 'WC_Order' ) ) {
			$order = Helper::get_order( $order );
		}

		if ( ! $order || ! is_a( $order, '\Vendidero\Storeabill\WooCommerce\Order' ) ) {
			return false;
		}

		$order_id        = $order->get_id();
		$payment_gateway = $order->get_payment_method();

		if ( self::invoice_gateway_specific() && ! empty( $payment_gateway ) ) {
			$gateways = self::get_invoice_gateways();

			if ( ! empty( $gateways ) && ! in_array( $payment_gateway, $gateways ) ) {
				return false;
			}
		}

		if ( $args['allow_defer'] ) {
			$defer = apply_filters( 'storeabill_woo_defer_auto_order_invoice_sync', true, $order_id );
		} else {
			$defer = false;
		}

		if ( ! apply_filters( 'storeabill_woo_auto_sync_order_invoices', true, $order_id ) ) {
			return false;
		}

		/**
		 * In case deferring is allowed here - defer the whole sync event
		 * to prevent race conditions from leading to multiple syncs (e.g. PayPal IPN/PDT requests).
		 */
		if ( $defer ) {
			Package::extended_log( 'Deferring new order #' . $order_id . ' invoice sync' );

			$queue = WC()->queue();

			$defer_args = array(
				'order_id' => $order_id,
			);

			/**
			 * Cancel outstanding events and queue new.
			 */
			self::cancel_deferred_sync( $defer_args );

			$queue->schedule_single(
				time() + 50,
				'storeabill_order_auto_sync_callback',
				$defer_args,
				'storeabill-order-sync'
			);
		} else {
			CacheHelper::prevent_caching();

			Package::extended_log( 'Starting order #' . $order_id . ' invoice sync (instant)' );

			$order->sync_order( true, $args );

			if ( self::finalize_invoices() && $order->needs_finalization() ) {
				return $order->finalize( $defer );
			}
		}

		return true;
	}

	public static function auto_sync_callback( $order_id ) {
		CacheHelper::prevent_caching();

		/**
		 * Maybe cancel duplicate deferred syncs.
		 */
		self::cancel_deferred_sync( array( 'order_id' => $order_id ) );

		$order = Helper::get_order( $order_id );

		if ( ! $order ) {
			return false;
		}

		Package::extended_log( 'Starting order #' . $order_id . ' invoice sync (deferred callback)' );

		$order->sync_order( true );

		if ( self::finalize_invoices() && $order->needs_finalization() ) {
			$order->finalize();
		}

		return true;
	}
}