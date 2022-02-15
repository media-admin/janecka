<?php

namespace Vendidero\StoreaBill\Compatibility;

use Vendidero\StoreaBill\Document\Shortcodes;
use Vendidero\StoreaBill\Interfaces\Compatibility;
use Vendidero\StoreaBill\Invoice\Invoice;
use Vendidero\StoreaBill\Invoice\Simple;
use Vendidero\StoreaBill\WooCommerce\Automation;
use Vendidero\StoreaBill\WooCommerce\Helper;
use Vendidero\StoreaBill\WooCommerce\Order;
use Vendidero\StoreaBill\WooCommerce\OrderItem;

defined( 'ABSPATH' ) || exit;

class Subscriptions implements Compatibility {

	public static function is_active() {
		return class_exists( 'WC_Subscriptions' );
	}

	public static function init() {
		/**
		 * Hide the invoice meta box from main subscription order
		 */
		add_filter( 'storeabill_woo_order_type_shop_subscription_add_invoice_meta_box', '__return_false', 10 );

		/**
		 * Sync the date of service with the invoice
		 */
		add_action( 'storeabill_woo_order_synced_invoice', array( __CLASS__, 'sync_invoice_date_of_service' ), 50, 2 );

		/**
		 * Sync the date of service with the invoice
		 */
		add_action( 'storeabill_woo_order_item_belongs_to_invoice', array( __CLASS__, 'maybe_exclude_item_from_invoice' ), 50, 5 );

		/**
		 * Add order related shortcodes.
		 */
		add_filter( 'storeabill_shortcode_get_document_reference_data', array( __CLASS__, 'shortcode_result' ), 10, 4 );

		/**
		 * Register editor shortcodes.
		 */
		add_filter( 'storeabill_document_template_editor_available_shortcodes', array( __CLASS__, 'register_editor_shortcodes' ), 10, 2 );

		/**
		 * On renewals
		 */
		add_filter( 'wcs_renewal_order_created', array( __CLASS__, 'maybe_trigger_auto' ), 5000, 2 );
	}

	/**
	 * This filter ensures that only subscriptions items of the same billing period (e.g. 1 month)
	 * are included within the same invoice to make sure the invoice date of service is in sync for the whole document.
	 *
	 * @param boolean $include
	 * @param OrderItem $order_item
	 * @param array $props
	 * @param Invoice $invoice
	 * @param Order $order
	 */
	public static function maybe_exclude_item_from_invoice( $include, $order_item, $props, $invoice, $order ) {
		if ( self::item_is_subscription( $order_item, $order ) ) {
			if ( ! self::item_belongs_to_invoice( $invoice, $order_item, $order ) ) {
				$include = false;
			}
		}

		return $include;
	}

	/**
	 * @param Invoice $invoice
	 * @param Order $order
	 */
	protected static function get_subscription_dates_of_service_end( $invoice, $order ) {
		$items = array();

		foreach( $invoice->get_items( 'product' ) as $item ) {
			if ( $reference = $item->get_reference() ) {
				if ( $end_date = self::get_date_of_service_end_by_item( $order, $reference, $invoice ) ) {
					$items[ $item->get_id() ] = $end_date;
				}
			}
		}

		return $items;
	}

	/**
	 * @param OrderItem $order_item
	 * @param Order $order
	 */
	protected static function item_is_subscription( $order_item, $order ) {
		$woo_order            = $order->get_order();
		$item_is_subscription = false;

		if ( function_exists( 'wcs_order_contains_subscription' ) &&
		     function_exists( 'wcs_add_time' ) &&
		     function_exists( 'wcs_get_subscriptions_for_order' ) &&
		     function_exists( 'wcs_get_canonical_product_id' ) )
		{
			if ( 'line_item' === $order_item->get_type() ) {
				$woo_order_item         = $order_item->get_object();
				$order_items_product_id = wcs_get_canonical_product_id( $woo_order_item );

				foreach ( wcs_get_subscriptions_for_order( $woo_order, array( 'order_type' => array( 'parent', 'renewal' ) ) ) as $subscription ) {
					foreach ( $subscription->get_items() as $line_item ) {
						if ( wcs_get_canonical_product_id( $line_item ) == $order_items_product_id ) {
							$item_is_subscription = true;
							break 2;
						}
					}
				}
			}
		}

		return $item_is_subscription;
	}

	/**
	 * @param Order $order
	 * @param OrderItem $order_item
	 * @param Invoice $invoice
	 */
	protected static function get_date_of_service_end_by_item( $order, $order_item, $invoice ) {
		$woo_order           = $order->get_order();
		$woo_order_item      = $order_item->get_object();
		$date_of_service_end = null;

		if ( function_exists( 'wcs_order_contains_subscription' ) &&
		     function_exists( 'wcs_add_time' ) &&
		     function_exists( 'wcs_get_subscriptions_for_order' ) &&
		     function_exists( 'wcs_get_canonical_product_id' ) &&
		     wcs_order_contains_subscription( $woo_order, 'any' ) )
		{
			$order_items_product_id = wcs_get_canonical_product_id( $woo_order_item );
			$start_date             = $invoice->get_date_of_service();

			foreach ( wcs_get_subscriptions_for_order( $woo_order, array( 'order_type' => array( 'parent', 'renewal' ) ) ) as $subscription ) {
				foreach ( $subscription->get_items() as $line_item ) {
					if ( wcs_get_canonical_product_id( $line_item ) == $order_items_product_id ) {
						if ( $end_date = self::get_subscription_date_of_service_end( $subscription, $start_date ) ) {
							$date_of_service_end = $end_date;
							break 2;
						}
					}
				}
			}
		}

		return $date_of_service_end;
	}

	protected static function get_subscription_date_of_service_end( $subscription, $start_date ) {
		$end_date = null;

		if ( function_exists( 'wcs_add_time' ) ) {
			$end_date = wcs_add_time( $subscription->get_billing_interval(), $subscription->get_billing_period(), $start_date->getTimestamp() );
			/**
			 * Remove one day from the end date, e.g. for one month billing period the end of service date should be the last day of the month
			 */
			$end_date = strtotime('-1 day', $end_date );

			/**
			 * In case the start date equals the end date, do not add
			 */
			if ( $start_date->getTimestamp() == $end_date ) {
				$end_date = null;
			}
		}

		return $end_date;
	}

	/**
	 * @param Invoice $invoice
	 * @param OrderItem $order_item
	 * @param Order $order
	 */
	public static function item_belongs_to_invoice( $invoice, $order_item, $order ) {
		$should_belong = true;

		if ( $item_date_of_service_end = self::get_date_of_service_end_by_item( $order, $order_item, $invoice ) ) {
			$subscription_items = self::get_subscription_dates_of_service_end( $invoice, $order );

			if ( ! empty( $subscription_items ) ) {
				foreach( $subscription_items as $item_id => $date_of_service_end ) {
					if ( $item_date_of_service_end != $date_of_service_end ) {
						$should_belong = false;
						break;
					}
				}
			}
		}

		return $should_belong;
	}

	public static function maybe_trigger_auto( $renewal_order, $subscription ) {
		/**
		 * In case the after checkout automation option has been chosen
		 * lets create invoices for renewals right after they have been created
		 *
		 * In case other timing exists, lets check whether the default order status has already been set and maybe sync immediately.
		 */
		if ( Automation::create_invoices() ) {
			if ( Automation::has_invoice_timing( 'checkout' ) ) {
				Automation::sync_invoices( $renewal_order->get_id() );
			} elseif ( Automation::has_invoice_timing( 'paid' ) || Automation::has_invoice_timing( 'status' ) || Automation::has_invoice_timing( 'status_payment_method' ) ) {
				$statuses       = wc_get_is_paid_statuses();
				$payment_method = $renewal_order->get_payment_method();

				if ( Automation::has_invoice_timing( 'status' ) ) {
					$statuses = Automation::get_invoice_order_statuses();
				} elseif ( Automation::has_invoice_timing( 'status_payment_method' ) ) {
					// Somehow the renewal order seems to miss the payment method - use subscription payment method as fallback
					$payment_method = empty( $payment_method ) ? $subscription->get_payment_method() : $payment_method;
					$statuses       = empty( $payment_method ) ? array() : Automation::get_invoice_payment_method_statuses( $payment_method );
				}

				$statuses = array_map( array( '\Vendidero\StoreaBill\WooCommerce\Helper', 'clean_order_status' ), $statuses );
				$sync     = false;

				if ( $order = Helper::get_order( $renewal_order ) ) {
					if ( in_array( $order->get_status(), $statuses ) ) {
						$sync = true;
					} elseif( empty( $statuses ) && ( Automation::has_invoice_timing( 'status' ) || Automation::has_invoice_timing( 'status_payment_method' ) ) ) {
						// If no status was selected within the status settings - sync right away
						$sync = true;
					}
				}

				if ( $sync ) {
					Automation::sync_invoices( $renewal_order->get_id() );
				}
			}
		}

		return $renewal_order;
	}

	public static function register_editor_shortcodes( $shortcodes, $document_type ) {
		if ( in_array( $document_type, array( 'invoice', 'invoice_cancellation' ) ) ) {
			$shortcodes['document'][] = array(
				'shortcode' => 'document_reference?data=subscription_numbers',
				'title'     => _x( 'Subscription order number(s)', 'storeabill-core', 'woocommerce-germanized-pro' ),
			);
		}

		return $shortcodes;
	}

	/**
	 * @param $result
	 * @param $atts
	 * @param Order $order
	 * @param Shortcodes $shortcodes
	 */
	public static function shortcode_result( $result, $atts, $order, $shortcodes ) {
		if ( $order ) {
			if ( is_a( $order, '\Vendidero\StoreaBill\WooCommerce\Order' ) && 'subscription_numbers' === $atts['data'] ) {
				$result = array();

				if ( function_exists( 'wcs_order_contains_subscription' ) &&
				     function_exists( 'wcs_get_subscriptions_for_order' ) &&
				     wcs_order_contains_subscription( $order->get_id() )
				) {
					$subscriptions = wcs_get_subscriptions_for_order( $order->get_id() );

					if ( ! empty( $subscriptions ) ) {
						foreach( $subscriptions as $subscription ) {
							$result[] = $subscription->get_order_number();
						}
					}
				/**
				 * Check if it is a renewal
				 */
				} elseif ( function_exists( 'wcs_order_contains_renewal' ) &&
				     function_exists( 'wcs_get_subscriptions_for_renewal_order' ) &&
				     wcs_order_contains_renewal( $order->get_id() )
				) {
					$subscriptions = wcs_get_subscriptions_for_renewal_order( $order->get_id() );

					if ( ! empty( $subscriptions ) ) {
						foreach ( $subscriptions as $subscription ) {
							$result[] = $subscription->get_order_number();
						}
					}
				}
			}
		} elseif( $document = $shortcodes->get_document() ) {
			if ( 'subscription_numbers' === $atts['data'] && is_a( $document, 'Vendidero\StoreaBill\Interfaces\Previewable' )  ) {
				$result = array( '1234' );
			}
		}

		return $result;
	}

	/**
	 * @param Simple $invoice
	 * @param Order $order
	 */
	public static function sync_invoice_date_of_service( $invoice, $order ) {
		$woo_order = $order->get_order();

		if ( function_exists( 'wcs_order_contains_subscription' ) && wcs_order_contains_subscription( $woo_order, 'any' ) ) {
			foreach( $invoice->get_items( 'product' ) as $item ) {
				if ( $order_item = $item->get_reference() ) {
					if ( self::item_is_subscription( $order_item, $order ) ) {
						$end_date = self::get_date_of_service_end_by_item( $order, $order_item, $invoice );

						if ( $end_date ) {
							$invoice->set_date_of_service_end( $end_date );
							break;
						}
					}
				}
			}
		}
	}
}