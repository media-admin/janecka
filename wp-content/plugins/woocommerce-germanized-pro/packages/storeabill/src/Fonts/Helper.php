<?php

namespace Vendidero\StoreaBill\Fonts;

use Vendidero\StoreaBill\Package;
use Vendidero\StoreaBill\UploadManager;

defined( 'ABSPATH' ) || exit;

/**
 * The Helper object.
 *
 * @since 1.0.0
 */
final class Helper {

	public static function get_remote_font( $url, $type = 'woff' ) {
		/**
		 * See https://github.com/majodev/google-webfonts-helper/blob/539f0c41be4735b5b9d7005aef5022d0d9729fbe/server/logic/conf.js
		 */
		$user_agent = 'Mozilla/5.0 (X11; Linux i686; rv:21.0) Gecko/20100101 Firefox/21.0';

		/**
		 * See https://stackoverflow.com/questions/25011533/google-font-api-uses-browser-detection-how-to-get-all-font-variations-for-font
		 */
		if ( 'ttf' === $type ) {
			$user_agent = 'Safari 5.0.5';
		}

		$args = array(
			'headers' => array(
				'user-agent' => $user_agent,
			),
		);

		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$html = wp_remote_retrieve_body( $response );

		if ( is_wp_error( $html ) ) {
			return $html;
		}

		return $html;
	}

	public static function get_fonts_dir( $upload_dir = array() ) {
		$upload_dir = ( ! empty( $upload_dir ) ? UploadManager::filter_upload_dir( $upload_dir ) : UploadManager::get_upload_dir() );

		$upload_dir['path'] = UploadManager::get_font_path( $upload_dir );
		$upload_dir['url']  = UploadManager::get_font_url( $upload_dir );

		if ( ! file_exists( $upload_dir['path'] ) ) {
			wp_mkdir_p( $upload_dir['path'] );
		}

		return $upload_dir;
	}

	public static function download_font_file( $url, $type = 'woff' ) {
		$saved_fonts = get_option( 'storeabill_font_local_filenames', array() );

		if ( isset( $saved_fonts[ $url ] ) && file_exists( $saved_fonts[ $url ]['file'] ) ) {
			return str_replace(
				wp_normalize_path( untrailingslashit( WP_CONTENT_DIR ) ),
				untrailingslashit( content_url() ),
				$saved_fonts[ $url ]['file']
			);
		}

		require_once ABSPATH . 'wp-admin/includes/file.php'; // phpcs:ignore WPThemeReview.CoreFunctionality.FileInclude

		$timeout_seconds = 5;

		// Download file to temp dir.
		$temp_file = download_url( $url, $timeout_seconds );

		if ( is_wp_error( $temp_file ) ) {
			return false;
		}

		$file = [
			'name'     => basename( $url ),
			'type'     => 'font/' . $type,
			'tmp_name' => $temp_file,
			'error'    => 0,
			'size'     => filesize( $temp_file ),
		];

		$overrides = [
			'test_type' => false,
			'test_form' => false,
			'test_size' => true,
		];

		// Move the temporary file into the fonts uploads directory.
		add_filter( 'upload_dir', array( __CLASS__, 'get_fonts_dir' ) );
		$results = wp_handle_sideload( $file, $overrides );
		remove_filter( 'upload_dir', array( __CLASS__, 'get_fonts_dir' ) );

		if ( empty( $results['error'] ) ) {
			$saved_fonts[ $url ] = $results;
			update_option( 'storeabill_font_local_filenames', $saved_fonts, false );

			return $results['url'];
		}

		return false;
	}

	public static function get_root_path() {
		return self::get_fonts_dir()['path'];
	}

	public static function get_root_url() {
		return self::get_fonts_dir()['url'];
	}
}