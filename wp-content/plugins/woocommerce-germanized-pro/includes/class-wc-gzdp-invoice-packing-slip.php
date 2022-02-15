<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_GZDP_Invoice_Packing_Slip extends \Vendidero\Germanized\Pro\StoreaBill\PackingSlip {

	public $content_type = 'packing_slip';

	public function __get( $name ) {
		$getter = "get_{$name}";

		if ( is_callable( array( $this, $getter ) ) ) {
			return $this->$getter();
		}

		return false;
	}

	public function is_type( $type ) {
		return 'simple' === $type ? true : false;
	}

	public function is_delivered() {
		return $this->is_sent();
	}

	public function get_delivery_date( $format = 'd.m.Y H:i' ) {
		return $this->get_date_sent() ? $this->get_date_sent()->date_i18n( $format ) : '';
	}

	public function is_new() {
		return $this->get_id() <= 0;
	}

	public function mark_as_sent() {
		$this->maybe_set_date_sent();
	}

	public function refresh() {
		do_action( 'woocommerce_gzdp_before_pdf_refresh', $this );

		do_action( 'woocommerce_gzdp_before_pdf_save', $this );

		$this->render();
	}

	public function generate_pdf( $preview = false ) {
		do_action( 'woocommerce_gzdp_generate_pdf', $this );

		$this->render( $preview );

		return $this->get_path();
	}

	public function get_pdf_url( $force = false ) {
		return $this->get_download_url( $force );
	}

	public function has_attachment() {
		return $this->has_file();
	}

	public function get_pdf_path() {
		return $this->get_path();
	}

	public function get_date( $format ) {
		return $this->get_date_created()->date_i18n( $format );
	}

	public function get_title_pdf() {
		return apply_filters( 'woocommerce_gzdp_' . $this->content_type . '_title_pdf', $this->get_title( true ), $this );
	}

	public function populate() {
		wc_deprecated_function( 'WC_GZDP_Invoice_Packing_Slip::populate', '3.0.0' );

		return;
	}

	public function get_option( $key, $default = false, $suppress_typing = false ) {
		wc_deprecated_function( 'WC_GZDP_Invoice_Packing_Slip::get_option', '3.0.0' );

		return false;
	}

	public function get_color_option( $key ) {
		wc_deprecated_function( 'WC_GZDP_Invoice_Packing_Slip::get_color_option', '3.0.0' );

		return false;
	}

	public function get_static_pdf_text( $where = '' ) {
		wc_deprecated_function( 'WC_GZDP_Invoice_Packing_Slip::get_static_pdf_text', '3.0.0' );

		return '';
	}

	public function get_font() {
		wc_deprecated_function( 'WC_GZDP_Invoice_Packing_Slip::get_font', '3.0.0' );

		return '';
	}

	public function has_custom_font() {
		wc_deprecated_function( 'WC_GZDP_Invoice_Packing_Slip::has_custom_font', '3.0.0' );

		return false;
	}

	public function get_font_size() {
		wc_deprecated_function( 'WC_GZDP_Invoice_Packing_Slip::get_font_size', '3.0.0' );

		return 8;
	}

	public function save_attachment( $file ) {
		wc_deprecated_function( 'WC_GZDP_Invoice_Packing_Slip::save_attachment', '3.0.0' );

		return false;
	}

	public function get_pdf_template( $first = false ) {
		wc_deprecated_function( 'WC_GZDP_Invoice_Packing_Slip::get_pdf_template', '3.0.0' );

		return false;
	}

	public function has_pdf_footer() {
		wc_deprecated_function( 'WC_GZDP_Invoice_Packing_Slip::has_pdf_footer', '3.0.0' );

		return false;
	}

	public function has_pdf_header() {
		wc_deprecated_function( 'WC_GZDP_Invoice_Packing_Slip::has_pdf_header', '3.0.0' );

		return false;
	}

	public function has_pdf_header_first() {
		wc_deprecated_function( 'WC_GZDP_Invoice_Packing_Slip::has_pdf_header_first', '3.0.0' );

		return false;
	}

	public function filename_exists() {
		wc_deprecated_function( 'WC_GZDP_Invoice_Packing_Slip::filename_exists', '3.0.0' );

		return false;
	}

	public function locate_template( $template ) {
		wc_deprecated_function( 'WC_GZDP_Invoice_Packing_Slip::locate_template', '3.0.0' );

		return false;
	}

	public function get_template_content( $template, $pdf ) {
		wc_deprecated_function( 'WC_GZDP_Invoice_Packing_Slip::get_template_content', '3.0.0' );

		return '';
	}

	public function keep_filename( $filename, $ext, $dir ) {
		wc_deprecated_function( 'WC_GZDP_Invoice_Packing_Slip::keep_filename', '3.0.0' );

		return '';
	}

	public function get_summary() {
		wc_deprecated_function( 'WC_GZDP_Invoice_Packing_Slip::get_summary', '3.0.0' );

		return '';
	}

	public function refresh_post_data( $data, $order ) {
		wc_deprecated_function( 'WC_GZDP_Invoice_Packing_Slip::refresh_post_data', '3.0.0' );

		return false;
	}

	public function refresh_order_invoices( $order ) {
		wc_deprecated_function( 'WC_GZDP_Invoice_Packing_Slip::refresh_order_invoices', '3.0.0' );

		return false;
	}

	public function is_cancellation() {
		wc_deprecated_function( 'WC_GZDP_Invoice_Packing_Slip::is_cancellation', '3.0.0' );

		return false;
	}

	public function is_cancelled() {
		wc_deprecated_function( 'WC_GZDP_Invoice_Packing_Slip::is_cancelled', '3.0.0' );

		return false;
	}

	public function is_locked() {
		wc_deprecated_function( 'WC_GZDP_Invoice_Packing_Slip::is_locked', '3.0.0' );

		return false;
	}

	public function is_partially_refunded() {
		wc_deprecated_function( 'WC_GZDP_Invoice_Packing_Slip::is_partially_refunded', '3.0.0' );

		return false;
	}

	public function get_submit_button_text() {
		wc_deprecated_function( 'WC_GZDP_Invoice_Packing_Slip::get_submit_button_text', '3.0.0' );

		return '';
	}

	public function get_sender_address( $type = '' ) {
		wc_deprecated_function( 'WC_GZDP_Invoice_Packing_Slip::get_sender_address', '3.0.0' );

		return '';
	}

	public function number_format( $format ) {
		wc_deprecated_function( 'WC_GZDP_Invoice_Packing_Slip::number_format', '3.0.0' );

		return '';
	}

	public function get_email_class() {
		wc_deprecated_function( 'WC_GZDP_Invoice_Packing_Slip::get_email_class', '3.0.0' );

		return '';
	}

	public function filter_export_data( $data = array() ) {
		wc_deprecated_function( 'WC_GZDP_Invoice_Packing_Slip::filter_export_data', '3.0.0' );

		return $data;
	}
}