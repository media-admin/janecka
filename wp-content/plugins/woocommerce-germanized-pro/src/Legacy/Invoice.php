<?php

namespace Vendidero\Germanized\Pro\Legacy;

defined( 'ABSPATH' ) || exit;

class Invoice extends Pdf {

	public function is_cancellation() {
		return $this->is_type( 'cancellation' );
	}

	public function is_cancelled() {
		return ( 'cancelled' === $this->document->get_status() ? true : false );
	}

	public function get_status( $readable = false ) {
		return $this->document->get_status();
	}

	public function is_delivered() {
		return $this->document->is_sent();
	}

	public function get_delivery_date( $format = 'd.m.Y H:i' ) {
		return $this->document->get_date_sent() ? $this->document->get_date_sent()->date_i18n( $format ) : '';
	}

	public function is_new() {
		return $this->get_id() <= 0;
	}

	public function is_locked() {
		return $this->document->is_finalized();
	}

	public function is_partially_refunded() {
		wc_deprecated_function( 'WC_GZDP_Invoice::is_partially_refunded', '3.0.0' );

		return false;
	}

	public function get_submit_button_text() {
		wc_deprecated_function( 'WC_GZDP_Invoice::get_submit_button_text', '3.0.0' );

		return '';
	}

	public function get_sender_address( $type = '' ) {
		wc_deprecated_function( 'WC_GZDP_Invoice::get_sender_address', '3.0.0' );

		return '';
	}

	public function number_format( $format ) {
		wc_deprecated_function( 'WC_GZDP_Invoice::get_sender_address', '3.0.0' );

		return '';
	}

	public function get_email_class() {
		wc_deprecated_function( 'WC_GZDP_Invoice::get_email_class', '3.0.0' );

		return '';
	}

	public function filter_export_data( $data = array() ) {
		wc_deprecated_function( 'WC_GZDP_Invoice::filter_export_data', '3.0.0' );

		return $data;
	}

	public function mark_as_sent() {
		if ( ! $this->document->get_date_sent() ) {
			$this->document->set_date_sent( current_time( 'timestamp', true ) );
			$this->document->save();
		}
	}

	public function get_summary() {
		wc_deprecated_function( 'WC_GZDP_Invoice::get_summary', '3.0.0' );

		return '';
	}

	public function refresh_post_data( $data, $order ) {
		wc_deprecated_function( 'WC_GZDP_Invoice::refresh_post_data', '3.0.0' );

		return false;
	}

	public function refresh_order_invoices( $order ) {
		wc_deprecated_function( 'WC_GZDP_Invoice::refresh_order_invoices', '3.0.0' );

		return false;
	}
}