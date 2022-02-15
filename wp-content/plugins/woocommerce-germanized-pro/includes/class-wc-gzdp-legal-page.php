<?php

defined( 'ABSPATH' ) || exit;

class WC_GZDP_Legal_Page extends \Vendidero\Germanized\Pro\StoreaBill\PostDocument {

	public function __construct( $page_id, $page = '' ) {
		parent::__construct( $page_id );
	}

	public function __get( $name ) {
		$getter = "get_{$name}";

		if ( is_callable( array( $this, $getter ) ) ) {
			return $this->$getter();
		}

		return false;
	}

	public function is_enabled() {
		wc_deprecated_function( 'WC_GZDP_Legal_Page::is_enabled', '3.0.0' );

		return false;
	}

	public function get_content_pdf() {
	 	return apply_filters( 'woocommerce_gzdp_legal_page_pdf_content', $this->get_content(), $this );
	}

	public function is_type( $type ) {
		return 'simple' === $type ? true : false;
	}

	public function is_new() {
		return $this->get_id() <= 0;
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

	public function is_delivered() {
		wc_deprecated_function( 'WC_GZDP_Legal_Page::is_delivered', '3.0.0' );

		return false;
	}

	public function get_delivery_date( $format = 'd.m.Y H:i' ) {
		wc_deprecated_function( 'WC_GZDP_Legal_Page::get_delivery_date', '3.0.0' );

		return false;
	}

	public function mark_as_sent() {
		wc_deprecated_function( 'WC_GZDP_Legal_Page::mark_as_sent', '3.0.0' );

		return false;
	}

	public function empty_shortcode( $args = array(), $content = '' ) {
		wc_deprecated_function( 'WC_GZDP_Legal_Page::empty_shortcode', '3.0.0' );

		return '';
	}

	public function filter_html( $content ) {
		wc_deprecated_function( 'WC_GZDP_Legal_Page::is_enabled', '3.0.0' );

		return $content;
	}

	public function get_title_pdf() {
		return apply_filters( 'woocommerce_gzdp_' . $this->content_type . '_title_pdf', $this->get_title( true ), $this );
	}

	public function populate() {
		wc_deprecated_function( 'WC_GZDP_Legal_Page::populate', '3.0.0' );

		return;
	}

	public function get_option( $key, $default = false, $suppress_typing = false ) {
		wc_deprecated_function( 'WC_GZDP_Legal_Page::get_option', '3.0.0' );

		return false;
	}

	public function get_color_option( $key ) {
		wc_deprecated_function( 'WC_GZDP_Legal_Page::get_color_option', '3.0.0' );

		return false;
	}

	public function get_static_pdf_text( $where = '' ) {
		wc_deprecated_function( 'WC_GZDP_Legal_Page::get_static_pdf_text', '3.0.0' );

		return '';
	}

	public function get_font() {
		wc_deprecated_function( 'WC_GZDP_Legal_Page::get_font', '3.0.0' );

		return '';
	}

	public function has_custom_font() {
		wc_deprecated_function( 'WC_GZDP_Legal_Page::has_custom_font', '3.0.0' );

		return false;
	}

	public function get_font_size() {
		wc_deprecated_function( 'WC_GZDP_Legal_Page::get_font_size', '3.0.0' );

		return 8;
	}

	public function save_attachment( $file ) {
		wc_deprecated_function( 'WC_GZDP_Legal_Page::save_attachment', '3.0.0' );

		return false;
	}

	public function get_pdf_template( $first = false ) {
		wc_deprecated_function( 'WC_GZDP_Legal_Page::get_pdf_template', '3.0.0' );

		return false;
	}

	public function has_pdf_footer() {
		wc_deprecated_function( 'WC_GZDP_Legal_Page::has_pdf_footer', '3.0.0' );

		return false;
	}

	public function has_pdf_header() {
		wc_deprecated_function( 'WC_GZDP_Legal_Page::has_pdf_header', '3.0.0' );

		return false;
	}

	public function has_pdf_header_first() {
		wc_deprecated_function( 'WC_GZDP_Legal_Page::has_pdf_header_first', '3.0.0' );

		return false;
	}

	public function filename_exists() {
		wc_deprecated_function( 'WC_GZDP_Legal_Page::filename_exists', '3.0.0' );

		return false;
	}

	public function locate_template( $template ) {
		wc_deprecated_function( 'WC_GZDP_Legal_Page::locate_template', '3.0.0' );

		return false;
	}

	public function get_template_content( $template, $pdf ) {
		wc_deprecated_function( 'WC_GZDP_Legal_Page::get_template_content', '3.0.0' );

		return '';
	}

	public function keep_filename( $filename, $ext, $dir ) {
		wc_deprecated_function( 'WC_GZDP_Legal_Page::keep_filename', '3.0.0' );

		return '';
	}

	public function refresh_post_data( $data, $order ) {
		wc_deprecated_function( 'WC_GZDP_Legal_Page::refresh_post_data', '3.0.0' );

		return false;
	}

	public function get_option_page_slug_prefix() {
		wc_deprecated_function( 'WC_GZDP_Legal_Page::get_option_page_slug_prefix', '3.0.0' );

		return '';
	}
}

?>