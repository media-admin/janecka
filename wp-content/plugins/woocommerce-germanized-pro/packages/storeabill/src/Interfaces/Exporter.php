<?php

namespace Vendidero\StoreaBill\Interfaces;

/**
 * Invoice
 *
 * @package  Germanized/StoreaBill/Interfaces
 * @version  1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Invoice class.
 */
interface Exporter {

	public function get_title();

	public function get_description();

	public function get_type();

	public function render_filters();

	public function get_document_type();

	public function get_filename();

	public function set_filename( $filename );

	public function set_start_date( $datetime );

	public function set_end_date( $datetime );

	public function get_end_date();

	public function get_start_date();

	public function get_filters();

	public function get_default_settings();

	public function get_default_setting( $key );

	public function set_filters( $filters );

	public function get_filter( $filter );

	public function get_nonce_action();

	public function get_nonce_download_action();

	public function get_file();

	public function generate_file();

	public function get_file_extension();

	/**
	 * @return \WP_Error
	 */
	public function get_errors();

	public function has_errors();

	public function add_error( $error );

	public function export();

	public function get_limit();

	public function get_page();

	public function set_page( $page );

	public function get_percent_complete();

	public function get_total_exported();

	public function send_headers();

	public function get_admin_url();
}
