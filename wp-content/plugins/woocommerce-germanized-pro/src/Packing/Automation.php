<?php

namespace Vendidero\Germanized\Pro\Packing;

use Vendidero\Germanized\Shipments\Package;
use Vendidero\Germanized\Shipments\Packing\Helper;

defined( 'ABSPATH' ) || exit;

class Automation {

	protected static $packaging = null;

	public static function init() {
		if ( is_callable( array( '\Vendidero\Germanized\Shipments\Package', 'is_packing_supported' ) ) && Package::is_packing_supported() ) {
			add_filter( 'woocommerce_gzd_auto_create_custom_shipments_for_order', array( __CLASS__, 'maybe_disable_auto_create' ), 10, 2 );
			add_action( 'woocommerce_gzd_after_auto_create_shipments_for_order', array( __CLASS__, 'pack_order' ), 10, 2 );
			add_filter( 'woocommerce_gzd_packaging_inner_dimension_use_percentage_buffer', array( __CLASS__, 'use_percentage_buffer' ) );
			add_filter( 'woocommerce_gzd_packaging_inner_dimension_percentage_buffer', array( __CLASS__, 'register_percentage_buffer' ) );
			add_filter( 'woocommerce_gzd_packaging_inner_dimension_fixed_buffer_mm', array( __CLASS__, 'register_fixed_buffer' ) );
		}
	}

	public static function register_percentage_buffer() {
		$buffer = wc_format_decimal( get_option( 'woocommerce_gzdp_shipment_packing_inner_percentage_buffer' ) );

		if ( empty( $buffer ) ) {
			$buffer = 0;
		}

		return $buffer;
	}

	public static function register_fixed_buffer() {
		$buffer = wc_format_decimal( get_option( 'woocommerce_gzdp_shipment_packing_inner_fixed_buffer' ) );

		if ( empty( $buffer ) ) {
			$buffer = 0;
		}

		return $buffer;
	}

	public static function use_percentage_buffer() {
		return 'percentage' === get_option( 'woocommerce_gzdp_shipment_packing_inner_buffer_type' );
	}

	public static function is_enabled() {
		return 'yes' === get_option( 'woocommerce_gzdp_enable_auto_shipment_packing' );
	}

	public static function maybe_disable_auto_create( $disable, $order_id ) {
		return apply_filters( 'woocommerce_gzdp_auto_pack_shipments_for_order', self::is_enabled(), $order_id );
	}

	public static function log( $message ) {
		WC_germanized_pro()->log( $message, 'info', 'packing' );
	}

	public static function pack_order( $order_id, $default_shipment_status = 'processing' ) {
		if ( ! apply_filters( 'woocommerce_gzdp_auto_pack_shipments_for_order', self::is_enabled(), $order_id ) ) {
			return;
		}

		if ( ! $order_shipment = wc_gzd_get_shipment_order( $order_id ) ) {
			return;
		}

		if ( $order_shipment->needs_shipping() ) {
			$group_by_shipping_class = apply_filters( 'woocommerce_gzdp_auto_pack_shipments_for_order_by_shipping_class', 'yes' === get_option( 'woocommerce_gzdp_shipment_packing_group_by_shipping_class' ), $order_id );
			$items_to_be_packed      = $order_shipment->get_items_to_pack_left_for_shipping( $group_by_shipping_class );

			if ( ! empty( $items_to_be_packed ) ) {
				foreach( $items_to_be_packed as $shipping_class => $items ) {
					self::log( sprintf( 'Calculate shipments for order %s. Shipping class: %s. Total items available: %s', $order_id, $shipping_class, sizeof( $items ) ) );

					$packer = new \DVDoug\BoxPacker\InfalliblePacker();
					/**
					 * Make sure to not try to spread/balance weights. Instead try to pack
					 * the first box as full as possible to make sure a smaller box can be used for a second box.
					 */
					$packer->setMaxBoxesToBalanceWeight( 0 );

					foreach( Helper::get_available_packaging() as $packaging ) {
						$packer->addBox( $packaging );
					}

					foreach( $items as $item ) {
						$packer->addItem( $item );
					}

					do_action( 'woocommerce_gzdp_before_auto_pack_shipments_for_order', $packer );

					$boxes = $packer->pack();

					// Items that do not fit in any box
					$items_too_large = $packer->getUnpackedItems();

					if ( ! empty( $items_too_large ) ) {
						foreach( $items_too_large as $item ) {
							self::log( sprintf( 'Warning: Item %s is too large to fit in any of the available packaging.', $item->get_order_item()->get_name() ) );
						}
					}

					foreach ( $boxes as $box ) {
						$packaging      = $box->getBox();
						$items          = $box->getItems();
						$shipment_items = array();

						self::log( sprintf( 'Add new shipment for order %s: ', $order_id ) );

						foreach ( $items as $item ) {
							$order_item = $item->getItem();

							if ( ! isset( $shipment_items[ $order_item->get_id() ] ) ) {
								$shipment_items[ $order_item->get_id() ] = 1;
							} else {
								$shipment_items[ $order_item->get_id() ]++;
							}

							self::log( sprintf( '- 1x Item %s', $order_item->get_order_item()->get_name() ) );
						}

						$shipment = wc_gzd_create_shipment( $order_shipment, array(
							'items' => $shipment_items,
							'props' => array(
								'packaging_id' => $packaging->get_id(),
								'status'       => $default_shipment_status
							),
						) );

						if ( ! is_wp_error( $shipment ) ) {
							$order_shipment->add_shipment( $shipment );
						}
					}
				}
			}
		}
	}
}