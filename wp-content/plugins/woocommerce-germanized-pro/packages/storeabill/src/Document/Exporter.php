<?php

namespace Vendidero\StoreaBill\Document;

use Vendidero\StoreaBill\Package;
use Vendidero\StoreaBill\UploadManager;

defined( 'ABSPATH' ) || exit;

abstract class Exporter implements \Vendidero\StoreaBill\Interfaces\Exporter {
	use ExporterTrait;

	protected $filename = '';

	protected $page = 1;

	protected $total = 0;

	protected $total_exported = 0;

	/**
	 * Batch limit.
	 *
	 * @var integer
	 */
	protected $limit = 50;

	abstract protected function send_content();

	abstract protected function prepare_data_to_export();

	abstract protected function write_data();

	/**
	 * Get page.
	 *
	 * @since 3.1.0
	 * @return int
	 */
	public function get_page() {
		return $this->page;
	}

	/**
	 * Set page.
	 *
	 * @since 3.1.0
	 * @param int $page Page Nr.
	 */
	public function set_page( $page ) {
		$this->page = absint( $page );
	}

	/**
	 * Get file path to export to.
	 *
	 * @return string
	 */
	protected function get_file_path() {
		$upload_dir = UploadManager::get_upload_dir();

		return trailingslashit( $upload_dir['basedir'] ) . $this->get_filename();
	}

	/**
	 * Get batch limit.
	 *
	 * @since 3.1.0
	 * @return int
	 */
	public function get_limit() {
		return apply_filters( "{$this->get_hook_prefix()}batch_limit", $this->limit, $this );
	}

	/**
	 * Set batch limit.
	 *
	 * @since 3.1.0
	 * @param int $limit Limit to export.
	 */
	public function set_limit( $limit ) {
		$this->limit = absint( $limit );
	}

	/**
	 * Generate and return a filename.
	 *
	 * @return string
	 */
	public function get_filename() {
		return sanitize_file_name( apply_filters( "{$this->get_hook_prefix()}get_filename", $this->filename, $this ) );
	}

	public function set_filename( $filename ) {
		$this->filename = $filename;
	}

	/**
	 * Get count of records exported.
	 *
	 * @since 3.1.0
	 * @return int
	 */
	public function get_total_exported() {
		return ( ( $this->get_page() - 1 ) * $this->get_limit() ) + $this->total_exported;
	}

	/**
	 * Get total % complete.
	 *
	 * @since 3.1.0
	 * @return int
	 */
	public function get_percent_complete() {
		return $this->total ? floor( ( $this->get_total_exported() / $this->total ) * 100 ) : 100;
	}

	/**
	 * Serve the file and remove once sent to the client.
	 *
	 * @since 3.1.0
	 */
	public function export() {
		$this->send_headers();
		$this->send_content();
		@unlink( $this->get_file_path() ); // phpcs:ignore WordPress.VIP.FileSystemWritesDisallow.file_ops_unlink, Generic.PHP.NoSilencedErrors.Discouraged
		die();
	}

	public function get_file_extension() {
		return 'csv';
	}

	/**
	 * Generate the CSV file.
	 *
	 * @since 3.1.0
	 */
	public function generate_file() {
		if ( 1 === $this->get_page() ) {
			@unlink( $this->get_file_path() ); // phpcs:ignore WordPress.VIP.FileSystemWritesDisallow.file_ops_unlink, Generic.PHP.NoSilencedErrors.Discouraged,
			$this->update_default_settings();
		}

		$this->prepare_data_to_export();
		$this->write_data();

		if ( $this->get_percent_complete() >= 100 ) {
			$this->complete();
		}
	}

	protected function complete() {

	}

	/**
	 * Get the file contents.
	 *
	 * @since 3.1.0
	 * @return string
	 */
	public function get_file() {
		$file = '';
		if ( @file_exists( $this->get_file_path() ) ) { // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
			$file = @file_get_contents( $this->get_file_path() ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.WP.AlternativeFunctions.file_system_read_file_get_contents
		} else {
			@file_put_contents( $this->get_file_path(), '' ); // phpcs:ignore WordPress.VIP.FileSystemWritesDisallow.file_ops_file_put_contents, Generic.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
			@chmod( $this->get_file_path(), 0664 ); // phpcs:ignore WordPress.VIP.FileSystemWritesDisallow.chmod_chmod, WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents, Generic.PHP.NoSilencedErrors.Discouraged
		}
		return $file;
	}

	protected function get_query_args() {
		$query_args = array(
			'per_page' => $this->get_limit(),
			'page'     => $this->get_page(),
		);

		if ( $start_date = $this->get_start_date() ) {
			$query_args['after'] = $this->get_gm_date( $start_date );
		}

		if ( $end_date = $this->get_end_date() ) {
			$query_args['before'] = $this->get_gm_date( $end_date );
		}

		$query_args = array_replace( $query_args, $this->get_additional_query_args() );

		return apply_filters( "{$this->get_hook_prefix()}query_args", $query_args, $this );
	}
}