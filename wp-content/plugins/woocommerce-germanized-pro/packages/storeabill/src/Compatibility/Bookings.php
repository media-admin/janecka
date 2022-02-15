<?php

namespace Vendidero\StoreaBill\Compatibility;

use Vendidero\StoreaBill\Document\Shortcodes;
use Vendidero\StoreaBill\Interfaces\Compatibility;
use Vendidero\StoreaBill\Invoice\Invoice;
use Vendidero\StoreaBill\Invoice\Item;

defined( 'ABSPATH' ) || exit;

class Bookings implements Compatibility {

	public static function is_active() {
		return class_exists( 'WC_Bookings' );
	}

	public static function init() {
		add_filter( 'storeabill_invoice_preview_product_item_meta_types', array( __CLASS__, 'register_item_preview_meta' ), 10, 3 );
		add_filter( 'storeabill_invoice_cancellation_preview_product_item_meta_types', array( __CLASS__, 'register_item_preview_meta' ), 10, 3 );

		add_filter( 'storeabill_document_item_data_shortcode_result', array( __CLASS__, 'shortcode_result' ), 10, 4 );
	}

	/**
	 * @param $result
	 * @param $atts
	 * @param \Vendidero\StoreaBill\Document\Item $item
	 * @param Shortcodes $shortcodes
	 */
	public static function shortcode_result( $result, $atts, $item, $shortcodes ) {
		if ( is_a( $item, '\Vendidero\StoreaBill\Invoice\Item' ) && 'product' === $item->get_item_type() ) {
			$meta_types = array(
				'booking_start_date',
				'booking_end_date',
				'booking_date_period',
				'booking_person_num',
				'booking_id',
				'booking_summary',
			);

			if ( in_array( $atts['data'], $meta_types ) ) {
				$bookings = self::get_bookings_by_item( $item );

				if ( ! empty( $bookings ) ) {
					$result = '';
					$count  = 0;

					foreach( $bookings as $booking ) {
						$booking_html = self::format_booking_data( $booking, $atts['data'] );
						$separator    = 'booking_summary' === $atts['data'] ? '' : ', ';

						if ( ! empty( $booking_html ) ) {
							$count++;

							$result .= ( $count > 1 ? apply_filters( 'storeabill_booking_invoice_separator', $separator, $booking, $atts['data'] ) : '' ) . $booking_html;
						}
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @param \WC_Booking $booking
	 * @param $data_type
	 */
	protected static function format_booking_data( $booking, $data_type ) {
		$data = '';

		switch( $data_type ) {
			case "booking_start_date":
				$data = $booking->get_start_date();
				break;
			case "booking_end_date":
				$data = $booking->get_end_date();
				break;
			case "booking_date_period":
				if ( strtotime( 'midnight', $booking->get_start() ) === strtotime( 'midnight', $booking->get_end() ) ) {
					$data = sprintf( '%1$s', $booking->get_start_date() );
				} else {
					$data = sprintf( '%1$s / %2$s', $booking->get_start_date(), $booking->get_end_date() );
				}
				break;
			case "booking_id":
				$data = $booking->get_id();
				break;
			case "booking_person_num":
				$data = $booking->has_persons() ? $booking->get_persons_total() : '';
				break;
			case "booking_summary":
				if ( function_exists( 'wc_bookings_get_summary_list' ) ) {
					ob_start();
					wc_bookings_get_summary_list( $booking );
					$data = ob_get_clean();
				}
				break;
		}

		return $data;
	}

	/**
	 * @param $meta
	 * @param Item $item
	 * @param Invoice $invoice
	 *
	 * @return array
	 */
	public static function register_item_preview_meta( $meta, $item, $invoice ) {
		$date_start           = $invoice->get_date_created();
		$date_end             = clone $invoice->get_date_created();
		$date_end             = $date_end->modify( '+7 days' );
		$date_range_formatted = $date_start->format( sab_date_format() ) . ' / ' . $date_end->format( sab_date_format() );
		$persons_formatted    = '2';

		$meta = array_merge( $meta, array(
			array(
				'title'   => _x( 'Booking Start Date', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'preview' => $date_start->format( sab_date_format() ),
				'icon'    => '',
				'type'    => 'booking_start_date'
			),
			array(
				'title'   => _x( 'Booking End Date', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'preview' => $date_end->format( sab_date_format() ),
				'icon'    => '',
				'type'    => 'booking_end_date'
			),
			array(
				'title'   => _x( 'Booking Date Period', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'preview' => $date_range_formatted,
				'icon'    => '',
				'type'    => 'booking_date_period'
			),
			array(
				'title'   => _x( 'Booking Number of Persons', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'preview' => $persons_formatted,
				'icon'    => '',
				'type'    => 'booking_person_num'
			),
			array(
				'title'   => _x( 'Booking Id', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'preview' => '5675',
				'icon'    => '',
				'type'    => 'booking_id'
			),
			array(
				'title'   => _x( 'Booking Summary', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'preview' => _x( 'Booking Summary List', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'icon'    => '',
				'type'    => 'booking_summary'
			),
		) );

		return $meta;
	}

	/**
	 * @param Item $item
	 *
	 * @return WC_Booking[]
	 */
	protected static function get_bookings_by_item( $item ) {
		$bookings = array();

		if ( ! class_exists( 'WC_Booking_Data_Store' ) || ! function_exists( 'get_wc_booking' ) ) {
			return $bookings;
		}

		$booking_data = new \WC_Booking_Data_Store();
		$booking_ids  = $booking_data->get_booking_ids_from_order_item_id( $item->get_reference_id() );

		foreach ( $booking_ids as $booking_id ) {
			if ( ! $booking = get_wc_booking( $booking_id ) ) {
				continue;
			}

			$bookings[] = $booking;
		}

		return $bookings;
	}
}