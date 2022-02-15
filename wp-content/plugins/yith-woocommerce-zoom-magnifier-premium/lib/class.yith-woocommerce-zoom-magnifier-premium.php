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

if ( ! class_exists( 'YITH_WooCommerce_Zoom_Magnifier_Premium' ) ) {
	/**
	 * YITH WooCommerce Product Gallery & Image Zoom  Premium
	 *
	 * @since 1.0.0
	 */
	class YITH_WooCommerce_Zoom_Magnifier_Premium extends YITH_WooCommerce_Zoom_Magnifier {

		/**
		 * Plugin panel page
		 *
		 * @var string
		 */
		protected $_panel_page = 'yith_woocommerce_zoom-magnifier_panel';


		/**
		 * Constructor
		 *
		 * @return mixed|YITH_WCMG_Admin|YITH_WCMG_Frontend
		 * @since 1.0.0
		 */
		public function __construct() {

			add_action(
				'wp_ajax_nopriv_yith_wc_zoom_magnifier_get_main_image',
				array(
					$this,
					'yith_wc_zoom_magnifier_get_main_image_call_back',
				),
				10
			);

			add_action(
				'wp_ajax_yith_wc_zoom_magnifier_get_main_image',
				array(
					$this,
					'yith_wc_zoom_magnifier_get_main_image_call_back',
				),
				10
			);

			// actions.
			add_action( 'init', array( $this, 'init' ) );

			if ( is_admin() && ( ! isset( $_REQUEST['action'] ) || ( isset( $_REQUEST['action'] ) && 'yith_load_product_quick_view' !== $_REQUEST['action'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended

				$this->obj = new YITH_WCMG_Admin();
			} else {

				/** Stop the plugin on mobile devices */
				if ( ( 'yes' == get_option( 'ywzm_hide_zoom_mobile' ) ) && wp_is_mobile() ) {
					return;
				}

				$this->obj = new YITH_WCMG_Frontend_Premium();
			}

			add_action( 'ywzm_products_exclusion', array( $this, 'show_products_exclusion_table' ) );

			return $this->obj;
		}

		/**
		 * Ajax method to retrieve the product main imavge
		 *
		 * @access public
		 * @author Daniel Sanchez Saez
		 * @since  1.3.4
		 */
		public function yith_wc_zoom_magnifier_get_main_image_call_back() {

			// set the main wp query for the product.
			global $post, $product;

			$product_id = isset( $_POST['product_id'] ) ? $_POST['product_id'] : 0; // phpcs:ignore
			$post       = get_post( $product_id ); // phpcs:ignore
			$product    = wc_get_product( $product_id );

			if ( empty( $product ) ) {
				wp_send_json_error();
			}

			$url = wp_get_attachment_image_src( get_post_thumbnail_id( $product_id ), 'full' );

			if ( function_exists( 'YITH_WCCL_Frontend' ) && function_exists( 'yith_wccl_get_variation_gallery' ) ) {

				$gallery = yith_wccl_get_variation_gallery( $product );
				// filter gallery based on current variation.
				if ( ! empty( $gallery ) ) {

					add_filter( 'woocommerce_product_variation_get_gallery_image_ids', array( YITH_WCCL_Frontend(), 'filter_gallery_ids' ), 10, 2 );
				}
			}

			ob_start();
			wc_get_template( 'single-product/product-thumbnails-magnifier.php', array(), '', YITH_YWZM_DIR . 'templates/' );
			$gallery_html = ob_get_clean();

			wp_send_json(
				array(
					'url'     => isset( $url[0] ) ? $url[0] : '',
					'gallery' => $gallery_html,
				)
			);

		}


		/**
		 * Show product category exclusion table.
		 */
		public function show_products_exclusion_table() {

			$get           = $_GET; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$template_file = YITH_YWZM_VIEW_PATH . 'ywzm-exclusions-table.php';

			if ( isset( $get['page'] ) && $get['page'] === $this->_panel_page && isset( $get['tab'] ) && 'exclusions' === $get['tab'] && file_exists( $template_file ) ) {

				$exclusions_prod = array_filter( explode( ',', get_option( 'yith-ywzm-exclusions-prod-list' ) ) );
				$exclusions_cat  = array_filter( explode( ',', get_option( 'yith-ywzm-exclusions-cat-list' ) ) );
				$exclusions_tag  = array_filter( explode( ',', get_option( 'yith-ywzm-exclusions-tag-list' ) ) );
				$list            = array_merge( $exclusions_prod, $exclusions_cat, $exclusions_tag );

				$is_blank = count( $list ) == 0;

				wp_enqueue_style( 'ywzm_exclusion_list' );
				wp_enqueue_script( 'ywzm_exclusion_list' );
				include_once YITH_YWZM_LIB_DIR . '/class.yith-ywzm-exclusions-list-table.php';

				$table = new YWZM_Exclusions_List_Table();
				$table->prepare_items();

				include_once $template_file;
			}

		}

		/**
		 * Check if current product have to be ignored by the plugin.
		 * We want to be alerted only if we are working on a valid product on which a product rule or catefory rule is active.
		 *
		 * @return bool product should be ignored
		 */
		public function is_product_excluded() {
			global $post;

			$is_excluded = false;

			// if current post is not a product, there is nothing to report.
			if ( ! is_product() ) {
				return false;
			}

			$product = wc_get_product( $post->ID );

			if ( $product->is_type( 'gift-card') ){
				return true;
			}

			$product_exclusion_list = array_filter( explode( ',', get_option( 'yith-ywzm-exclusions-prod-list', '' ) ) );
			$category_exclusion_list = array_filter( explode( ',', get_option( 'yith-ywzm-exclusions-cat-list', '' ) ) );
			$tag_exclusion_list = array_filter( explode( ',', get_option( 'yith-ywzm-exclusions-tag-list', '' ) ) );

			if ( is_array( $product_exclusion_list ) && in_array( $product->get_id(), $product_exclusion_list ) ){
				$is_excluded = true;
			}

			if ( !empty( array_intersect( $category_exclusion_list, $product->get_category_ids() ) ) ){
				$is_excluded = true;
			}

			if ( !empty( array_intersect( $tag_exclusion_list, $product->get_tag_ids() ) ) ){
				$is_excluded = true;
			}


			return $is_excluded;
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
		public function plugin_row_meta( $new_row_meta_args, $plugin_meta, $plugin_file, $plugin_data, $status, $init_file = 'YITH_YWZM_INIT' ) {
			$new_row_meta_args = parent::plugin_row_meta( $new_row_meta_args, $plugin_meta, $plugin_file, $plugin_data, $status, $init_file );

			if ( defined( $init_file ) && constant( $init_file ) === $plugin_file ) {
				$new_row_meta_args['is_premium'] = true;
			}

			return $new_row_meta_args;
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
			$links = yith_add_action_links( $links, 'yith_woocommerce_zoom-magnifier_panel', true, YITH_YWZM_SLUG );
			return $links;
		}
	}
}
