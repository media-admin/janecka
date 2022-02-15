<?php

namespace Vendidero\Germanized\Pro\StoreaBill\PackingSlip;

defined( 'ABSPATH' ) || exit;

class Shortcodes extends \Vendidero\StoreaBill\Document\Shortcodes {

	public function get_shortcodes() {
		$shortcodes = array(
			'shipment'              => array( $this, 'shipment_data' ),
			'order'                 => array( $this, 'order_data' ),
			'if_shipment'           => array( $this, 'if_shipment_data' ),
			'if_order'              => array( $this, 'if_order_data' ),
			'return_reasons'        => array( $this, 'return_reasons_data' ),
			'order_item'            => array( $this, 'order_item_data' ),
		);

		return apply_filters( 'storeabill_packing_slip_shortcodes', $shortcodes, $this );
	}

	public function return_reasons_data( $atts ) {
		$atts = wp_parse_args( $atts, array(
			'format' => 'plain',
		) );

		$reasons = wc_gzd_get_return_shipment_reasons();
		$content = '';
		$count   = 0;

		foreach( $reasons as $reason ) {
			$count++;

			if ( 'list' !== $atts['format'] && $count > 1 ) {
				$content .= ' | ';
			}

			$content .= ( 'list' === $atts['format'] ? '<li>' : '' ) . sprintf( _x( '%1$s: %2$s', 'return-reason-format', 'woocommerce-germanized-pro' ), $reason->get_order(), $reason->get_reason() ) . ( 'list' === $atts['format'] ? '</li>' : '' );
		}

		if ( ! empty( $content ) && 'list' === $atts['format'] ) {
			$content = '<ul>' . $content . '</ul>';
		}

		return $content;
	}

	public function shipment_data( $atts ) {
		return $this->document_reference_data( $atts );
	}

	/**
	 * @return bool|\WC_Order
	 */
	public function get_order() {
		$shipment = $this->get_document_reference();

		if ( $shipment && is_a( $shipment, '\Vendidero\Germanized\Pro\StoreaBill\Shipment' ) ) {
			return $shipment->get_shipment()->get_order();
		}

		return false;
	}

	/**
	 * @return bool|\WC_Order_Item
	 */
	public function get_order_item() {
		$item = $this->get_document_item_reference();

		if ( $item ) {
			return $item->get_order_item();
		}

		return false;
	}

	public function order_item_data( $atts ) {
		$atts = $this->parse_args( $atts );

		return apply_filters( 'woocommerce_gzdp_packing_slip_order_item_shortcode_result', $this->format_result( $this->get_order_item_data( $atts, $this->get_order_item() ), $atts ), $atts, $this->get_order_item(), $this );
	}

	/**
	 * @param $atts
	 * @param \WC_Order_Item $order_item
	 *
	 * @return array|mixed|string
	 */
	public function get_order_item_data( $atts, $order_item ) {
		$atts   = $this->parse_args( $atts );
		$result = '';

		if ( $order_item ) {
			if ( ! empty( $atts['data'] ) ) {
				$result = $this->get_object_data( $order_item, $atts['data'], $atts['args'] );
			}
		}

		return $result;
	}

	public function order_data( $atts ) {
		$atts = $this->parse_args( $atts );

		return apply_filters( 'woocommerce_gzdp_packing_slip_order_shortcode_result', $this->format_result( $this->get_order_data( $atts, $this->get_order() ), $atts ), $atts, $this->get_order(), $this );
	}

	/**
	 * @param $atts
	 * @param \WC_Order $order
	 *
	 * @return mixed
	 */
	protected function get_order_data( $atts, $order ) {
		$atts   = $this->parse_args( $atts );
		$result = '';

		if ( $order ) {
			if ( ! empty( $atts['data'] ) ) {
				$result = $this->get_object_data( $order, $atts['data'], $atts['args'] );
			}
		}

		return $result;
	}

	public function if_order_data( $atts, $content = '' ) {
		$atts = $this->parse_comparison_args( $atts );

		$data = $this->get_order_data( $atts, $this->get_order() );
		$show = $this->compare( $atts['compare'], $data, $atts['value'], $atts['chain'] );

		if ( apply_filters( 'woocommerce_gzdp_packing_slip_if_order_shortcode_result', $show, $atts, $this->get_order(), $this ) ) {
			return do_shortcode( $content );
		} else {
			return '';
		}
	}

	public function if_shipment_data( $atts, $content = '' ) {
		return $this->if_document_reference_data( $atts, $content );
	}

	public function supports( $document_type ) {
		return in_array( $document_type, array( 'packing_slip' ) );
	}
}