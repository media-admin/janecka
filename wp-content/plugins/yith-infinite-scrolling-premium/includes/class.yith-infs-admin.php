<?php
/**
 * Admin class
 *
 * @author  YITH
 * @package YITH Infinite Scrolling
 * @version 1.0.0
 */

defined( 'YITH_INFS' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_INFS_Admin' ) ) {
	/**
	 * Admin class.
	 * The class manage all the admin behaviors.
	 *
	 * @since 1.0.0
	 */
	class YITH_INFS_Admin {

		/**
		 * Single instance of the class
		 *
		 * @since 1.0.0
		 * @var YITH_INFS_Admin
		 */
		protected static $instance;

		/**
		 * Plugin options
		 *
		 * @since  1.0.0
		 * @var array
		 * @access public
		 */
		public $options = array();

		/**
		 * Plugin version
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $version = YITH_INFS_VERSION;

		/**
		 * Panel Object
		 *
		 * @var YIT_Plugin_Panel
		 */
		protected $panel;

		/**
		 * Premium tab template file name
		 *
		 * @var string
		 */
		protected $premium = 'premium.php';

		/**
		 * Premium version landing link
		 *
		 * @var string
		 */
		protected $premium_landing = 'https://yithemes.com/themes/plugins/yith-infinite-scrolling/';

		/**
		 * Infinite Scrolling panel page
		 *
		 * @var string
		 */
		protected $panel_page = 'yith_infs_panel';

		/**
		 * Various links
		 *
		 * @since  1.0.0
		 * @var string
		 * @access public
		 */
		public $doc_url = 'https://yithemes.com/docs-plugins/yith-infinite-scrolling/';

		/**
		 * Returns single instance of the class
		 *
		 * @since 1.0.0
		 * @return YITH_INFS_Admin
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
		 */
		public function __construct() {

			add_action( 'admin_menu', array( $this, 'register_panel' ), 5 );

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_style' ) );

			// Add action links.
			add_filter( 'plugin_action_links_' . plugin_basename( YITH_INFS_DIR . '/' . basename( YITH_INFS_FILE ) ), array( $this, 'action_links' ) );
			add_filter( 'yith_show_plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 5 );

			if ( ! ( defined( 'YITH_INFS_PREMIUM' ) && YITH_INFS_PREMIUM ) ) {
				add_action( 'yith_infinite_scrolling_premium', array( $this, 'premium_tab' ) );
			}

			// YITH INFS Loaded.
			do_action( 'yith_infs_loaded' );
		}

		/**
		 * Enqueue style
		 *
		 * @since  1.0.0
		 * @author Francesco Licandro <francesco.licandro@yithems.com>
		 * @access public
		 */
		public function enqueue_style() {
			if ( isset( $_GET['page'] ) && sanitize_text_field( wp_unslash( $_GET['page'] ) ) === $this->panel_page ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				wp_enqueue_style( 'yith-infs-admin', YITH_INFS_ASSETS_URL . '/css/admin.css', array(), YITH_INFS_VERSION );
			}
		}

		/**
		 * Action Links
		 * add the action links to plugin admin page
		 *
		 * @since    1.0
		 * @author   Andrea Grillo <andrea.grillo@yithemes.com>
		 * @param array $links Links plugin array.
		 * @return   array
		 */
		public function action_links( $links ) {
			$links = yith_add_action_links( $links, $this->panel_page, true, YITH_INFS_SLUG );

			return $links;
		}

		/**
		 * Add a panel under YITH Plugins tab
		 *
		 * @since    1.0
		 * @author   Andrea Grillo <andrea.grillo@yithemes.com>
		 * @use      /Yit_Plugin_Panel class
		 * @return   void
		 * @see      plugin-fw/lib/yit-plugin-panel.php
		 */
		public function register_panel() {

			if ( ! empty( $this->panel ) ) {
				return;
			}

			$admin_tabs = array(
				'general' => __( 'Settings', 'yith-infinite-scrolling' ),
			);

			if ( ! ( defined( 'YITH_INFS_PREMIUM' ) && YITH_INFS_PREMIUM ) ) {
				$admin_tabs['premium'] = __( 'Premium Version', 'yith-infinite-scrolling' );
			}

			$args = array(
				'create_menu_page' => true,
				'parent_slug'      => '',
				'page_title'       => 'Infinite Scrolling',
				'menu_title'       => 'Infinite Scrolling',
				'parent'           => 'infs',
				'parent_page'      => 'yith_plugin_panel',
				'plugin-url'       => YITH_INFS_URL,
				'page'             => $this->panel_page,
				'admin-tabs'       => $admin_tabs,
				'options-path'     => YITH_INFS_DIR . 'plugin-options',
				'class'            => yith_set_wrapper_class(),
			);

			if ( ! class_exists( 'YIT_Plugin_Panel' ) ) {
				require_once YITH_INFS_DIR . '/plugin-fw/lib/yit-plugin-panel.php';
			}

			$this->panel = new YIT_Plugin_Panel( $args );
		}

		/**
		 * Premium Tab Template
		 * Load the premium tab template on admin page
		 *
		 * @since    1.0
		 * @author   Andrea Grillo <andrea.grillo@yithemes.com>
		 * @return   void
		 */
		public function premium_tab() {
			$premium_tab_template = YITH_INFS_TEMPLATE_PATH . '/admin/' . $this->premium;
			if ( file_exists( $premium_tab_template ) ) {
				include_once $premium_tab_template;
			}

		}

		/**
		 * Add the action links to plugin admin page
		 *
		 * @since    1.0
		 * @author   Andrea Grillo <andrea.grillo@yithemes.com>
		 * @use      plugin_row_meta
		 * @param array    $new_row_meta_args An array of plugin row meta.
		 * @param string[] $plugin_meta An array of the plugin's metadata,
		 *                                    including the version, author,
		 *                                    author URI, and plugin URI.
		 * @param string   $plugin_file Path to the plugin file relative to the plugins directory.
		 * @param array    $plugin_data An array of plugin data.
		 * @param string   $status Status of the plugin. Defaults are 'All', 'Active',
		 *                                    'Inactive', 'Recently Activated', 'Upgrade', 'Must-Use',
		 *                                    'Drop-ins', 'Search', 'Paused'.
		 * @return   array
		 */
		public function plugin_row_meta( $new_row_meta_args, $plugin_meta, $plugin_file, $plugin_data, $status ) {

			if ( defined( 'YITH_INFS_INIT' ) && YITH_INFS_INIT === $plugin_file ) {
				$new_row_meta_args['slug'] = YITH_INFS_SLUG;

				if ( defined( 'YITH_INFS_PREMIUM' ) ) {
					$new_row_meta_args['is_premium'] = true;
				}
			}

			return $new_row_meta_args;
		}

		/**
		 * Get the premium landing uri
		 *
		 * @since   1.0.0
		 * @author  Andrea Grillo <andrea.grillo@yithemes.com>
		 * @return  string The premium landing link
		 */
		public function get_premium_landing_uri() {
			return defined( 'YITH_REFER_ID' ) ? $this->premium_landing . '?refer_id=' . YITH_REFER_ID : $this->premium_landing . '?refer_id=1030585';
		}

		/**
		 * Get options from db
		 *
		 * @access public
		 * @since  1.0.0
		 * @author Francesco Licandro <francesco.licandro@yithemes.com>
		 * @param string $option The option key.
		 * @param mixed  $default Default option value.
		 * @return mixed
		 */
		public static function get_option( $option, $default = false ) {
			return yinfs_get_option( $option, $default );
		}
	}
}
/**
 * Unique access to instance of YITH_WCQV_Admin class
 *
 * @since 1.0.0
 * @return YITH_INFS_Admin
 */
function YITH_INFS_Admin() { // phpcs:ignore
	return YITH_INFS_Admin::get_instance();
}
