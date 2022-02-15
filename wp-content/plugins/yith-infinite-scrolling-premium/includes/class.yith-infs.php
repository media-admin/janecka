<?php
/**
 * Main class
 *
 * @author  YITH
 * @package YITH Infinite Scrolling
 * @version 1.0.0
 */

defined( 'YITH_INFS' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_INFS' ) ) {
	/**
	 * YITH Infinite Scrolling
	 *
	 * @since 1.0.0
	 */
	class YITH_INFS {

		/**
		 * Single instance of the class
		 *
		 * @since 1.0.0
		 * @var YITH_INFS
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
		 * Plugin object
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $obj = null;

		/**
		 * Returns single instance of the class
		 *
		 * @since 1.0.0
		 * @return YITH_INFS
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
		 * @since 1.0.0
		 * @return void
		 */
		public function __construct() {

			// Load Plugin Framework.
			add_action( 'plugins_loaded', array( $this, 'plugin_fw_loader' ), 15 );
			// Register plugin to licence/update system.
			add_action( 'wp_loaded', array( $this, 'register_plugin_for_activation' ), 99 );
			add_action( 'admin_init', array( $this, 'register_plugin_for_updates' ) );

			// Class admin.
			if ( $this->is_admin() ) {
				require_once 'class.yith-infs-admin.php';
				require_once 'class.yith-infs-admin-premium.php';

				YITH_INFS_Admin_Premium();
			} elseif ( $this->load_frontend() ) {
				require_once 'class.yith-infs-frontend.php';
				require_once 'class.yith-infs-frontend-premium.php';

				// Frontend class.
				YITH_INFS_Frontend_Premium();
			}

			// Register strings for WPML.
			add_action( 'init', array( $this, 'register_wpml_strings' ) );
		}

		/**
		 * Check if is admin
		 *
		 * @since  1.0.6
		 * @author Francesco Licandro
		 * @return boolean
		 */
		public function is_admin() {
			$check_ajax    = defined( 'DOING_AJAX' ) && DOING_AJAX;
			$check_context = isset( $_REQUEST['context'] ) && 'frontend' === sanitize_text_field( wp_unslash( $_REQUEST['context'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			return is_admin() && ! ( $check_ajax && $check_context );
		}

		/**
		 * Check if load frontend class
		 *
		 * @since  1.0.6
		 * @author Francesco Licandro
		 * @return boolean
		 */
		public function load_frontend() {
			$enable        = 'yes' === yinfs_get_option( 'yith-infs-enable', 'yes' );
			$active_mobile = 'yes' === yinfs_get_option( 'yith-infs-enable-mobile', 'yes' );

			return apply_filters( 'yith_infinite_scrolling_load_frontend', ( $enable && ( ! wp_is_mobile() || ( wp_is_mobile() && $active_mobile ) ) ) );
		}

		/**
		 * Load Plugin Framework
		 *
		 * @since  1.0.0
		 * @access public
		 * @author Andrea Grillo <andrea.grillo@yithemes.com>
		 * @return void
		 */
		public function plugin_fw_loader() {
			if ( ! defined( 'YIT_CORE_PLUGIN' ) ) {
				global $plugin_fw_data;
				if ( ! empty( $plugin_fw_data ) ) {
					$plugin_fw_file = array_shift( $plugin_fw_data );
					require_once $plugin_fw_file;
				}
			}
		}

		/**
		 * Register a string to be translated using WPML
		 *
		 * @since  1.0.0
		 * @author Francesco Licandro
		 * @return void
		 */
		public function register_wpml_strings() {
			$options = yinfs_get_option( 'yith-infs-section' );
			if ( ! is_array( $options ) ) {
				return;
			}
			foreach ( $options as $section => $option ) {
				if ( isset( $option['buttonLabel'] ) ) {
					do_action( 'wpml_register_single_string', 'yith-infinite-scrolling', 'plugin_yit_infs_' . $section . '_buttonLabel', $option['buttonLabel'] );
				}
			}
		}


		/**
		 * Register plugins for activation tab
		 *
		 * @since 2.0.0
		 * @return void
		 */
		public function register_plugin_for_activation() {

			if ( ! class_exists( 'YIT_Plugin_Licence' ) ) {
				require_once YITH_INFS_DIR . 'plugin-fw/lib/yit-plugin-licence.php';
			}

			YIT_Plugin_Licence()->register( YITH_INFS_INIT, YITH_INFS_SECRET_KEY, YITH_INFS_SLUG );
		}

		/**
		 * Register plugins for update tab
		 *
		 * @since 2.0.0
		 * @return void
		 */
		public function register_plugin_for_updates() {
			if ( ! class_exists( 'YIT_Upgrade' ) ) {
				require_once YITH_INFS_DIR . 'plugin-fw/lib/yit-upgrade.php';
			}

			YIT_Upgrade()->register( YITH_INFS_SLUG, YITH_INFS_INIT );
		}
	}
}

/**
 * Unique access to instance of YITH_INFS class
 *
 * @since 1.0.0
 * @return YITH_INFS
 */
function YITH_INFS() { // phpcs:ignore
	return YITH_INFS::get_instance();
}
