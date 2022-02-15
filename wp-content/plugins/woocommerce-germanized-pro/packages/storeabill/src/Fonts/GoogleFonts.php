<?php

namespace Vendidero\StoreaBill\Fonts;

use Vendidero\StoreaBill\Package;

defined( 'ABSPATH' ) || exit;

final class GoogleFonts {

	/**
	 * The object instance.
	 *
	 * @access private
	 * @var null|GoogleFonts
	 */
	private static $instance = null;

	/**
	 * An array of our google fonts.
	 *
	 * @static
	 * @access public
	 * @since 1.0.0
	 * @var array
	 */
	public static $google_fonts;

	/**
	 * Get the one, true instance of this class.
	 * Prevents performance issues since this is only loaded once.
	 *
	 * @return object Google
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * The class constructor.
	 */
	private function __construct() {}

	/**
	 * Returns the array of googlefonts from the JSON file.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_array() {
		ob_start();
		include trailingslashit( Package::get_path() ) . 'src/Fonts/assets/webfonts.json';
		return json_decode( ob_get_clean(), true );
	}

	/**
	 * Return an array of all available Google Fonts.
	 *
	 * @since 1.0.0
	 * @return array All Google Fonts.
	 */
	public function get_fonts() {

		// Get fonts from cache.
		self::$google_fonts = get_site_transient( 'storeabill_googlefonts_cache' );

		// If cache is populated, return cached fonts array.
		if ( self::$google_fonts && ! empty( self::$google_fonts ) ) {
			return self::$google_fonts;
		}

		// If we got this far, cache was empty so we need to get from JSON.
		$fonts = $this->get_array();

		self::$google_fonts = [];
		if ( is_array( $fonts ) ) {
			foreach ( $fonts['items'] as $font ) {
				$name = sanitize_key( $font['family'] );

				self::$google_fonts[ $name ] = new Font( $font['family'], array(
					'variants'       => $font['variants'],
					'category'       => $font['category'],
					'name'           => $name,
					'is_google_font' => true,
				) );
			}
		}

		self::$google_fonts = apply_filters( 'storeabill_fonts_google_fonts', self::$google_fonts );

		// Save the array in cache.
		$cache_time = apply_filters( 'storeabill_googlefonts_transient_time', HOUR_IN_SECONDS );
		set_site_transient( 'storeabill_googlefonts_cache', self::$google_fonts, $cache_time );

		return self::$google_fonts;
	}

	/**
	 * Returns an array of Google fonts matching our arguments.
	 *
	 * @since 1.0.0
	 * @param  array $args The arguments.
	 * @return array
	 */
	public function get_google_fonts_by_args( $args = [] ) {
		$cache_name = 'storeabill_googlefonts_' . md5( wp_json_encode( $args ) );
		$cache      = get_site_transient( $cache_name );

		if ( $cache ) {
			return $cache;
		}

		$args['sort'] = isset( $args['sort'] ) ? $args['sort'] : 'alpha';

		$fonts         = $this->get_array();
		$ordered_fonts = $fonts['order'][ $args['sort'] ];

		if ( isset( $args['count'] ) ) {
			$ordered_fonts = array_slice( $ordered_fonts, 0, $args['count'] );
			set_site_transient( $cache_name, $ordered_fonts, HOUR_IN_SECONDS );
			return $ordered_fonts;
		}

		set_site_transient( $cache_name, $ordered_fonts, HOUR_IN_SECONDS );

		return $ordered_fonts;
	}
}