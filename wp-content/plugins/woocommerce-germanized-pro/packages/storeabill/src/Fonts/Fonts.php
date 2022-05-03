<?php

namespace Vendidero\StoreaBill\Fonts;

defined( 'ABSPATH' ) || exit;

/**
 * The Fonts object.
 */
final class Fonts {

	/**
	 * Holds a single instance of this object.
	 *
	 * @static
	 * @access private
	 * @var null|object
	 */
	private static $instance = null;

	/**
	 * An array of our google fonts.
	 *
	 * @static
	 * @access public
	 * @var array
	 */
	protected static $google_fonts = null;

	protected static $standard_fonts = null;

	protected static $default_font = null;

	/**
	 * The class constructor.
	 */
	private function __construct() {}

	/**
	 * Get the one, true instance of this class.
	 * Prevents performance issues since this is only loaded once.
	 *
	 * @return Fonts
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Compile font options from different sources.
	 *
	 * @return Font[] All available fonts.
	 */
	public static function get_all_fonts() {
		$standard_fonts = self::get_standard_fonts();
		$google_fonts   = self::get_google_fonts();

		return apply_filters( 'storeabill_fonts', array_merge( $standard_fonts, $google_fonts ) );
	}

	public static function get_fonts_select() {
		$fonts  = self::get_all_fonts();
		$select = array();

		foreach( $fonts as $name => $font ) {
			$select[ $name ] = array(
				'name'     => $font->get_name(),
				'variants' => $font->get_variants(),
				'label'    => $font->get_label(),
				'family'   => $font->get_family(),
			);
		}

		return $select;
	}

	public static function get_font( $name ) {
		$fonts = self::get_all_fonts();

		return array_key_exists( $name, $fonts ) ? $fonts[ $name ] : false;
	}

	/**
	 * @return Font
	 */
	public static function get_default_font() {
		if ( is_null( self::$default_font ) ) {
			self::$default_font = new Font( 'publicsans', array(
				'label' => 'PublicSans',
				'files' => array(
					'pdf' => array(
						'regular'     => 'PublicSans-Regular.ttf',
						'bold'        => 'PublicSans-Bold.ttf',
						'italic'      => 'PublicSans-Italic.ttf',
						'bold_italic' => 'PublicSans-BoldItalic.ttf',
					),
					'html' => array(
						'regular'     => 'PublicSans-Regular.woff',
						'bold'        => 'PublicSans-Bold.woff',
						'italic'      => 'PublicSans-Italic.woff',
						'bold_italic' => 'PublicSans-BoldItalic.woff',
					),
				)
			) );
		}

		return apply_filters( 'storeabill_default_font', self::$default_font );
	}

	public static function clean_font_name( $name ) {
		return str_replace( '_', '-', sanitize_title( $name ) );
	}

	public static function clean_font_family( $family ) {
		$family = preg_replace("/[^a-zA-Z0-9]+/", "", $family );

		return $family;
	}

	/**
	 * Return an array of standard websafe fonts.
	 *
	 * @return Font[]
	 */
	public static function get_standard_fonts() {
		if ( ! self::$standard_fonts ) {
			self::$standard_fonts = array();

			$standard_fonts = apply_filters( 'storeabill_standard_fonts', array() );

			foreach( $standard_fonts as $name => $font_data ) {
				$font_data['name'] = $name;

				if ( ! isset( $font_data['label'] ) ) {
					$font_data['label'] = $name;
				}

				self::$standard_fonts[ $name ] = new Font( $font_data['label'], $font_data );
			}

			$default_font = self::get_default_font();

			self::$standard_fonts[ $default_font->get_name() ] = $default_font;
		}

		return self::$standard_fonts;
	}

	/**
	 * Return an array of all available Google Fonts.
	 *
	 * @return array All Google Fonts.
	 */
	public static function get_google_fonts() {
		if ( ! self::$google_fonts ) {
			self::$google_fonts = GoogleFonts::get_instance()->get_fonts();
		}

		return self::$google_fonts;
	}

	/**
	 * Returns an array of all available subsets.
	 *
	 * @static
	 * @access public
	 * @return array
	 */
	public static function get_font_subsets() {
		return [
			'cyrillic'     => 'Cyrillic',
			'cyrillic-ext' => 'Cyrillic Extended',
			'devanagari'   => 'Devanagari',
			'greek'        => 'Greek',
			'greek-ext'    => 'Greek Extended',
			'khmer'        => 'Khmer',
			'latin'        => 'Latin',
			'latin-ext'    => 'Latin Extended',
			'vietnamese'   => 'Vietnamese',
			'hebrew'       => 'Hebrew',
			'arabic'       => 'Arabic',
			'bengali'      => 'Bengali',
			'gujarati'     => 'Gujarati',
			'tamil'        => 'Tamil',
			'telugu'       => 'Telugu',
			'thai'         => 'Thai',
		];
	}

	/**
	 * Returns an array of all available variants.
	 *
	 * @static
	 * @access public
	 * @return array
	 */
	public static function get_all_variants() {
		return array(
			'100'       => esc_html_x( 'Ultra-Light 100', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'100light'  => esc_html_x( 'Ultra-Light 100', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'100italic' => esc_html_x( 'Ultra-Light 100 Italic', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'200'       => esc_html_x( 'Light 200', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'200italic' => esc_html_x( 'Light 200 Italic', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'300'       => esc_html_x( 'Book 300', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'300italic' => esc_html_x( 'Book 300 Italic', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'400'       => esc_html_x( 'Normal 400', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'regular'   => esc_html_x( 'Normal 400', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'italic'    => esc_html_x( 'Normal 400 Italic', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'500'       => esc_html_x( 'Medium 500', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'500italic' => esc_html_x( 'Medium 500 Italic', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'600'       => esc_html_x( 'Semi-Bold 600', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'600bold'   => esc_html_x( 'Semi-Bold 600', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'600italic' => esc_html_x( 'Semi-Bold 600 Italic', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'700'       => esc_html_x( 'Bold 700', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'700italic' => esc_html_x( 'Bold 700 Italic', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'800'       => esc_html_x( 'Extra-Bold 800', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'800bold'   => esc_html_x( 'Extra-Bold 800', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'800italic' => esc_html_x( 'Extra-Bold 800 Italic', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'900'       => esc_html_x( 'Ultra-Bold 900', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'900bold'   => esc_html_x( 'Ultra-Bold 900', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'900italic' => esc_html_x( 'Ultra-Bold 900 Italic', 'storeabill-core', 'woocommerce-germanized-pro' ),
		);
	}

	/**
	 * Determine if a font-name is a valid google font or not.
	 *
	 * @static
	 * @access public
	 * @param string $fontname The name of the font we want to check.
	 * @return bool
	 */
	public static function is_google_font( $fontname ) {
		if ( is_string( $fontname ) ) {
			$fonts = self::get_google_fonts();

			return isset( $fonts[ $fontname ] );
		}

		return false;
	}
}