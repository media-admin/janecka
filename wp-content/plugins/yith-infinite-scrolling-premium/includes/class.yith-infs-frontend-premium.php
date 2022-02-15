<?php
/**
 * Frontend class
 *
 * @author  YITH
 * @package YITH Infinite Scrolling
 * @version 1.0.0
 */

defined( 'YITH_INFS' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_INFS_Frontend_Premium' ) ) {
	/**
	 * YITH Infinite Scrolling
	 *
	 * @since 1.0.0
	 */
	class YITH_INFS_Frontend_Premium extends YITH_INFS_Frontend {

		/**
		 * Array of preset loader
		 *
		 * @since 1.0.0
		 * @var array
		 */
		public $preset_loader = array();

		/**
		 * Returns single instance of the class
		 *
		 * @since 1.0.0
		 * @return YITH_INFS_Frontend_Premium
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor
		 *
		 * @access public
		 * @since  1.0.0
		 * @author Francesco Licandro <francesco.licandro@yithemes.com>
		 */
		public function __construct() {

			parent::__construct();

			$this->preset_loader = yinfs_get_preset_loader();
		}

		/**
		 * Pass premium options to script
		 *
		 * @since  1.0.0
		 * @access public
		 * @author Francesco Licandro <francesco.licandro@yithemes.com>
		 * @return void
		 */
		public function enqueue_scripts() {

			$min = ( ! defined( 'SCRIPT_DEBUG' ) || ! SCRIPT_DEBUG ) ? '.min' : '';

			wp_enqueue_script( 'jquery-blockui', YITH_INFS_ASSETS_URL . '/js/jquery.blockUI.min.js', array( 'jquery' ), YITH_INFS_VERSION, true );

			$options   = yinfs_get_option( 'yith-infs-section' );
			$custom_js = yinfs_get_option( 'yith-infs-custom-js', '' );

			// Set preset loader.
			if ( ! empty( $options ) && is_array( $options ) ) {

				foreach ( $options as $section => &$option ) {

					if ( isset( $option['buttonLabel'] ) ) {
						$option['buttonLabel'] = apply_filters( 'wpml_translate_single_string', $option['buttonLabel'], 'yith-infinite-scrolling', 'plugin_yit_infs_' . $section . '_buttonLabel' );
					}

					if ( ! isset( $option['presetLoader'] ) ) {
						continue;
					}

					foreach ( $this->preset_loader as $key => $value ) {

						if ( $option['presetLoader'] === $key ) {
							$option['presetLoader'] = $value;
							break;
						}
					}
				}

				wp_enqueue_style( 'yith-infs-style', YITH_INFS_ASSETS_URL . '/css/frontend.css', array(), YITH_INFS_VERSION );
				wp_enqueue_script( 'yith-infinitescroll', YITH_INFS_ASSETS_URL . '/js/yith.infinitescroll' . $min . '.js', array( 'jquery' ), YITH_INFS_VERSION, true );
				wp_enqueue_script( 'yith-infs', YITH_INFS_ASSETS_URL . '/js/yith-infs' . $min . '.js', array( 'jquery' ), YITH_INFS_VERSION, true );

				wp_localize_script(
					'yith-infs',
					'yith_infs_premium',
					array(
						'options' => $options,
					)
				);

				wp_localize_script(
					'yith-infinitescroll',
					'yith_infs_script',
					apply_filters(
						'yith_infinitescroll_localize_args',
						array(
							'shop'               => function_exists( 'WC' ) && ( is_shop() || is_product_category() || is_product_tag() ),
							'block_loader'       => apply_filters( 'yith_infs_block_loader_frontend', YITH_INFS_ASSETS_URL . '/images/block-loader.gif' ),
							'change_url'         => 'yes' === yinfs_get_option( 'yith-infs-change-url', 'no' ),
							'use_cache'          => apply_filters( 'yith_infs_use_ajax_cache', true ),
							'elem_height'        => null,
							'scroll_restoration' => apply_filters( 'yith_infs_scroll_restoration', 'manual' ),
							'scroll_top_on_click_load_more' => apply_filters( 'yith_infs_scroll_top_on_click_load_more', true ),
						)
					)
				);
			}

			if ( $custom_js ) {
				wp_add_inline_script( 'yith-infinitescroll', $custom_js );
			}
		}
	}
}

/**
 * Unique access to instance of YITH_INFS_Frontend class
 *
 * @since 1.0.0
 * @return YITH_INFS_Frontend_Premium
 */
function YITH_INFS_Frontend_Premium() { // phpcs:ignore
	return YITH_INFS_Frontend_Premium::get_instance();
}
