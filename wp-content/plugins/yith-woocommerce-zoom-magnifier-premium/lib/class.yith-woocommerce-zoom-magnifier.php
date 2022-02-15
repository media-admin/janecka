<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Main class
 *
 * @author YITH
 * @package YITH\ZoomMagnifier\Classes
 * @version 1.1.2
 */

if ( ! defined( 'YITH_WCMG' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WooCommerce_Zoom_Magnifier' ) ) {
	/**
	 * YITH WooCommerce Product Gallery & Image Zoom
	 *
	 * @since 1.0.0
	 */
	class YITH_WooCommerce_Zoom_Magnifier {

		/**
		 * Plugin object
		 *
		 * @var string
		 * @since 1.0.0
		 */
		public $obj = null;

		/**
		 * Constructor
		 *
		 * @return mixed|YITH_WCMG_Admin|YITH_WCMG_Frontend
		 * @since 1.0.0
		 */
		public function __construct() {

			// actions.
			add_action( 'init', array( $this, 'init' ) );

			if ( is_admin() && ( ! isset( $_REQUEST['action'] ) || ( isset( $_REQUEST['action'] ) && 'yith_load_product_quick_view' !== $_REQUEST['action'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$this->obj = new YITH_WCMG_Admin();
			} else {


				/** Stop the plugin on mobile devices */
				if ( ( 'yes' == get_option( 'ywzm_hide_zoom_mobile' ) ) && wp_is_mobile() ) {
					return;
				}

				$this->obj = new YITH_WCMG_Frontend();
			}

			return $this->obj;
		}

		/**
		 * Init method:
		 *  - default options
		 *
		 * @access public
		 * @since  1.0.0
		 */
		public function init() {

			/* === Show Plugin Information === */
			add_filter( 'plugin_action_links_' . plugin_basename( YITH_YWZM_DIR . '/' . basename( YITH_YWZM_FILE ) ), array( $this, 'action_links' ) );
			add_filter( 'yith_show_plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 5 );
		}

		/**
		 * Action links.
		 *
		 * @param array $links Action links.
		 * @since    1.4.1
		 * @author   Carlos Rodríguez <carlos.rodriguez@youirinspiration.it>
		 *
		 * @return array
		 */
		public function action_links( $links ) {
			$links = yith_add_action_links( $links, $this->panel_page, false, YITH_YWZM_SLUG );
			return $links;
		}
		/**
		 * Plugin Row Meta.
		 *
		 * @param mixed $new_row_meta_args Row meta args.
		 * @param mixed $plugin_meta Plugin meta.
		 * @param mixed $plugin_file Plugin file.
		 * @param mixed $plugin_data Plugin data.
		 * @param mixed $status Status.
		 * @param mixed $init_file Init file.
		 *
		 * @since    1.4.1
		 * @author   Carlos Rodríguez <carlos.rodriguez@youirinspiration.it>
		 *
		 * @return array
		 */
		public function plugin_row_meta( $new_row_meta_args, $plugin_meta, $plugin_file, $plugin_data, $status, $init_file = 'YITH_YWZM_FREE_INIT' ) {
			if ( defined( $init_file ) && constant( $init_file ) === $plugin_file ) {
				$new_row_meta_args['slug'] = YITH_YWZM_SLUG;
			}

			return $new_row_meta_args;
		}
	}
}
