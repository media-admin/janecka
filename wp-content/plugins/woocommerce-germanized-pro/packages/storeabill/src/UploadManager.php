<?php

namespace Vendidero\StoreaBill;

defined( 'ABSPATH' ) || exit;

class UploadManager {

	protected static $upload_dir_folder = null;

	/**
	 * Hook in methods.
	 */
	public static function init() {
		self::maybe_set_upload_dir();
	}

	public static function maybe_set_upload_dir() {
		if ( is_null( self::$upload_dir_folder ) ) {
			if ( ! get_option( 'storeabill_upload_dir_folder', false ) ) {
				self::$upload_dir_folder = 'storeabill-' . sab_get_random_key( 10 );
				update_option( 'storeabill_upload_dir_folder', self::$upload_dir_folder, false );
			} else {
				self::$upload_dir_folder = get_option( 'storeabill_upload_dir_folder' );
			}
		}
	}

	public static function get_upload_dir_folder() {
		self::maybe_set_upload_dir();

		return self::$upload_dir_folder;
	}

	public static function get_font_path( $upload_dir = array() ) {
		$upload_dir = ( ! empty( $upload_dir ) ) ? $upload_dir : self::get_upload_dir();

		return apply_filters( 'storeabill_fonts_path', $upload_dir['basedir'] . '/fonts' );
	}

	public static function get_font_url( $upload_dir = array() ) {
		$upload_dir = ( ! empty( $upload_dir ) ) ? $upload_dir : self::get_upload_dir();

		return apply_filters( 'storeabill_fonts_url', $upload_dir['baseurl'] . '/fonts' );
	}

	public static function get_upload_dir() {
		self::set_upload_dir_filter();
		$upload_dir = wp_upload_dir();
		self::unset_upload_dir_filter();

		/**
		 * Filter to adjust the upload directory used to store shipment related files. By default
		 * files are stored in a custom directory under wp-content/uploads.
		 *
		 * @param array $upload_dir Array containing `wp_upload_dir` data.
		 *
		 * @since 0.0.1
		 * @package Vendidero/StoreaBill
		 */
		return apply_filters( 'storeabill_upload_dir', $upload_dir );
	}

	public static function get_relative_upload_dir( $path ) {
		self::set_upload_dir_filter();
		$path = _wp_relative_upload_path( $path );
		self::unset_upload_dir_filter();

		/**
		 * Filter to retrieve the relative upload path used for storing documents.
		 *
		 * @param array $path Relative path.
		 *
		 * @since 0.0.1
		 * @package Vendidero/StoreaBill
		 */
		return apply_filters( 'storeabill_relative_upload_dir', $path );
	}

	public static function set_upload_dir_filter() {
		add_filter( 'upload_dir', array( __CLASS__, "filter_upload_dir" ), 150, 1 );
	}

	public static function unset_upload_dir_filter() {
		remove_filter( 'upload_dir', array( __CLASS__, "filter_upload_dir" ), 150 );
	}

	public static function filter_upload_dir( $args ) {
		$upload_base = trailingslashit( $args['basedir'] );
		$upload_url  = trailingslashit( $args['baseurl'] );
		$folder      = self::get_upload_dir_folder();

		/**
		 * Filter to adjust the upload path used to store documents. By default
		 * files are stored in a custom directory under wp-content/uploads.
		 *
		 * @param string $path Path to the upload directory.
		 *
		 * @since 0.0.1
		 * @package Vendidero/StoreaBill
		 */
		$args['basedir'] = apply_filters( 'storeabill_upload_path', $upload_base . $folder );

		/**
		 * Filter to adjust the upload URL used to retrieve documents. By default
		 * files are stored in a custom directory under wp-content/uploads.
		 *
		 * @param string $url URL to the upload directory.
		 *
		 * @since 0.0.1
		 * @package Vendidero/StoreaBill
		 */
		$args['baseurl'] = apply_filters( 'storeabill_upload_url', $upload_url . $folder );

		$args['path'] = $args['basedir'] . $args['subdir'];
		$args['url']  = $args['baseurl'] . $args['subdir'];

		return $args;
	}
}