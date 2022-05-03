<?php

namespace Vendidero\StoreaBill\Invoice;

defined( 'ABSPATH' ) || exit;

class Shortcodes extends \Vendidero\StoreaBill\Document\Shortcodes {

	public function get_shortcodes() {
		$shortcodes = parent::get_shortcodes();

		$shortcodes = array_merge( $shortcodes, array(
			'order'      => array( $this, 'order_data' ),
			'if_order'   => array( $this, 'if_order_data' ),
			'order_item' => array( $this, 'order_item_data' ),
			'invoice'    => array( $this, 'invoice_data' ),
			'if_document_has_differing_shipping_address' => array( $this, 'if_document_has_differing_shipping_address' ),
		) );

		return apply_filters( 'storeabill_invoice_shortcodes', $shortcodes, $this );
	}

	public function order_data( $atts ) {
		return $this->document_reference_data( $atts );
	}

	public function invoice_data( $atts ) {
		return $this->document_data( $atts );
	}

	public function order_item_data( $atts ) {
		return $this->document_item_reference_data( $atts );
	}

	public function if_order_data( $atts, $content = '' ) {
		return $this->if_document_reference_data( $atts, $content );
	}

	public function if_document_has_differing_shipping_address( $atts, $content = '' ) {
		$atts = wp_parse_args( $atts, array() );

		$show = false;

		if ( $invoice = $this->get_document() ) {
			if ( is_callable( array( $invoice, 'has_differing_shipping_address' ) ) ) {
				$show = $invoice->has_differing_shipping_address();
			}
		}

		if ( apply_filters( 'storeabill_if_document_has_differing_shipping_address', $show, $atts, $this->get_document(), $this ) ) {
			return do_shortcode( $content );
		} else {
			return '';
		}
	}

	public function supports( $document_type ) {
		return in_array( $document_type, array( 'invoice', 'invoice_cancellation' ) );
	}
}