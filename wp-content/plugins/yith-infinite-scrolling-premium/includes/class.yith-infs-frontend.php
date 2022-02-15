<?php
/**
 * Frontend class
 *
 * @author  YITH
 * @package YITH Infinite Scrolling
 * @version 1.0.0
 */

defined( 'YITH_INFS' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_INFS_Frontend' ) ) {
	/**
	 * YITH Infinite Scrolling
	 *
	 * @since 1.0.0
	 */
	class YITH_INFS_Frontend {

		/**
		 * Single instance of the class
		 *
		 * @since 1.0.0
		 * @var YITH_INFS_Frontend
		 */
		protected static $instance;

		/**
		 * Plugin version
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $version = YITH_INFS_VERSION;

		/**
		 * Returns single instance of the class
		 *
		 * @since 1.0.0
		 * @return YITH_INFS_Frontend
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
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		/**
		 * Enqueue scripts
		 *
		 * @since  1.0.0
		 * @access public
		 * @author Francesco Licandro <francesco.licandro@yithemes.com>
		 * @return void
		 */
		public function enqueue_scripts() {

			$min = ( ! defined( 'SCRIPT_DEBUG' ) || ! SCRIPT_DEBUG ) ? '.min' : '';

			wp_enqueue_style( 'yith-infs-style', YITH_INFS_ASSETS_URL . '/css/frontend.css', array(), YITH_INFS_VERSION );

			wp_enqueue_script( 'yith-infinitescroll', YITH_INFS_ASSETS_URL . '/js/yith.infinitescroll' . $min . '.js', array( 'jquery' ), YITH_INFS_VERSION, true );
			wp_enqueue_script( 'yith-infs', YITH_INFS_ASSETS_URL . '/js/yith-infs' . $min . '.js', array( 'jquery', 'yith-infinitescroll' ), YITH_INFS_VERSION, true );

			if ( ! ( defined( 'YITH_INFS_PREMIUM' ) && YITH_INFS_PREMIUM ) ) {
				$this->options_to_script();
			}
		}

		/**
		 * Pass options to script
		 *
		 * @since  1.0.0
		 * @access public
		 * @author Francesco Licandro <francesco.licandro@yithemes.com>
		 * @return void
		 */
		public function options_to_script() {
			$nav_selector     = yinfs_get_option( 'yith-infs-navselector', 'nav.navigation' );
			$next_selector    = yinfs_get_option( 'yith-infs-nextselector', 'nav.navigation a.next' );
			$item_selector    = yinfs_get_option( 'yith-infs-itemselector', 'article.post' );
			$content_selector = yinfs_get_option( 'yith-infs-contentselector', '#main' );
			$loader           = yinfs_get_option( 'yith-infs-loader-image', YITH_INFS_ASSETS_URL . '/images/loader.gif' );

			wp_localize_script(
				'yith-infs',
				'yith_infs',
				array(
					'navSelector'     => $nav_selector,
					'nextSelector'    => $next_selector,
					'itemSelector'    => $item_selector,
					'contentSelector' => $content_selector,
					'loader'          => $loader,
					'shop'            => function_exists( 'WC' ) && ( is_shop() || is_product_category() || is_product_tag() ),
				)
			);
		}
	}
}

/**
 * Unique access to instance of YITH_INFS_Frontend class
 *
 * @since 1.0.0
 * @return YITH_INFS_Frontend
 */
function YITH_INFS_Frontend() { // phpcs:ignore
	return YITH_INFS_Frontend::get_instance();
}
