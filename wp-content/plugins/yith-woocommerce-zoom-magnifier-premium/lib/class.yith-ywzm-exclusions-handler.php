<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * This file belongs to the YIT Plugin Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @package YITH WooCommerce Product Gallery & Image Zoom  Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.

/**
 * Implements the YITH_YWZM_Exclusions_Handler class.
 *
 * @class    YITH_YWZM_Exclusions_Handler
 * @package  YITH
 * @since    2.0.0
 * @author   YITH
 */
if ( ! class_exists( 'YITH_YWZM_Exclusions_Handler' ) ) {
	/**
	 * YITH_YWZM_Exclusions_Handler
	 *
	 * @since 2.0.0
	 */
	class YITH_YWZM_Exclusions_Handler {

		/**
		 * Single instance of the class for each token
		 *
		 * @var \YITH_YWZM_Exclusions_Handler
		 * @since 2.0.0
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class
		 *
		 * @return \YITH_YWZM_Exclusions_Handler
		 * @since 2.0.0
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor method
		 *
		 * @since 2.0.0
		 */
		public function __construct() {

			// save exclusions list.
			add_action( 'admin_init', array( $this, 'handle_save_exclusion' ) );

			// remove item from exclusions list.
			add_action( 'wp_ajax_ywzm_delete_from_exclusion_list', array( $this, 'handle_delete_from_exclusion_list' ) );
			add_action( 'wp_ajax_nopriv_ywzm_delete_from_exclusion_list', array( $this, 'handle_delete_from_exclusion_list' ) );

			// search products.
			add_action( 'wp_ajax_yith_ywzm_search_products', array( $this, 'search_products_ajax' ) );
			add_action( 'wp_ajax_nopriv_yith_ywzm_search_products', array( $this, 'search_products_ajax' ) );

			// search categories.
			add_action( 'wp_ajax_yith_ywzm_search_categories', array( $this, 'search_categories_ajax' ) );
			add_action( 'wp_ajax_nopriv_yith_ywzm_search_categories', array( $this, 'search_categories_ajax' ) );

			// search tags.
			add_action( 'wp_ajax_yith_ywzm_search_tags', array( $this, 'search_tags_ajax' ) );
			add_action( 'wp_ajax_nopriv_yith_ywzm_search_tags', array( $this, 'search_tags_ajax' ) );
		}

		/**
		 * Save the exclusion.
		 */
		public function handle_save_exclusion() {

			$posted = $_POST; //phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( ! isset( $posted['_nonce'], $posted['ywzm-exclusion-type'] ) || ! wp_verify_nonce( $posted['_nonce'], 'yith_ywzm_add_exclusions' ) ) {
				return;
			}

			switch ( $posted['ywzm-exclusion-type'] ) {
				case 'product':
					if ( isset( $posted['add_products'] ) ) {
						$this->save_exclusions_prod();
					}
					break;
				case 'product_cat':
					if ( isset( $posted['add_categories'] ) ) {
						$this->save_exclusions_cat();
					}
					break;
				case 'product_tag':
					if ( isset( $posted['add_tags'] ) ) {
						$this->save_exclusions_tag();
					}
					break;
			}

		}

		/**
		 * Handle delete action from exclusion list
		 */
		public function handle_delete_from_exclusion_list() {
			$posted = $_POST; //phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( ! isset( $posted['nonce'], $posted['type'], $posted['id'] ) || ! wp_verify_nonce( $posted['nonce'], 'yith_ywzm_delete_exclusions' ) ) {
				return;
			}

			switch ( $posted['type'] ) {
				case 'product':
					$this->delete_exclusion_prod();
					break;
				case 'product_cat':
					$this->delete_exclusion_cat();
					break;
				case 'product_tag':
					$this->delete_exclusion_tag();
					break;
			}

			wp_send_json(
				array(
					'success' => 1,
				)
			);

		}

		/**
		 * Save products exclusions
		 *
		 * @since 2.0.0
		 * @author Francesco Licandro
		 */
		public function save_exclusions_prod() {

			$posted = $_POST; //phpcs:ignore WordPress.Security.NonceVerification.Missing

			// get older items.
			$old_items = array_filter( explode( ',', get_option( 'yith-ywzm-exclusions-prod-list' ) ) );
			// get new items.
			$new_items = $posted['add_products'];

			if ( ! is_array( $new_items ) ) {
				$new_items = explode( ',', $new_items );
			}

			$new_items = array_filter( $new_items );

			// merge old with new.
			$exclusions = array_merge( $old_items, $new_items );

			update_option( 'yith-ywzm-exclusions-prod-list', implode( ',', $exclusions ) );
		}

		/**
		 * Delete product from exclusions list
		 *
		 * @since 2.0.0
		 * @author Francesco Licandro
		 */
		public function delete_exclusion_prod() {
			$get = $_REQUEST; //phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$exclusions = array_filter( explode( ',', get_option( 'yith-ywzm-exclusions-prod-list' ) ) );
			$key        = array_search( $get['id'], $exclusions ); //phpcs:ignore
			if ( false !== $key ) {
				unset( $exclusions[ $key ] );
			}

			update_option( 'yith-ywzm-exclusions-prod-list', implode( ',', $exclusions ) );
		}

		/**
		 * Ajax action search products
		 *
		 * @since 2.0.0
		 * @author Francesco Licandro <francesco.licandro@yithemes.com>
		 */
		public function search_products_ajax() {
			ob_start();

			check_ajax_referer( 'search-products', 'security' );

			$term       = isset( $_GET['term'] ) ? (string) stripslashes( sanitize_text_field( wp_unslash( $_GET['term'] ) ) ) : '';
			$post_types = array( 'product' );

			if ( empty( $term ) ) {
				die();
			}

			$args = array(
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				's'              => $term,
				'fields'         => 'ids',
			);

			if ( is_numeric( $term ) ) {

				$args2 = array(
					'post_type'      => $post_types,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'post__in'       => array( 0, $term ),
					'fields'         => 'ids',
				);

				$args3 = array(
					'post_type'      => $post_types,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'post_parent'    => $term,
					'fields'         => 'ids',
				);

				$args4 = array(
					'post_type'      => $post_types,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'meta_query'     => array( //phpcs:ignore
						array(
							'key'     => '_sku',
							'value'   => $term,
							'compare' => 'LIKE',
						),
					),
					'fields'         => 'ids',
				);

				$posts = array_unique( array_merge( get_posts( $args ), get_posts( $args2 ), get_posts( $args3 ), get_posts( $args4 ) ) );

			} else {

				$args2 = array(
					'meta_query' => array( //phpcs:ignore
						array(
							'key'     => '_sku',
							'value'   => $term,
							'compare' => 'LIKE',
						),
					),
				);

				$posts = array_unique( array_merge( get_posts( $args ), get_posts( $args2 ) ) );

			}

			$found_products = array();
			// get excluded products.
			$excluded = array_filter( explode( ',', get_option( 'yith-ywzm-exclusions-prod-list', '' ) ) );
			$posts    = array_diff( $posts, $excluded );
			if ( $posts ) {
				foreach ( $posts as $post ) {
					$product                 = wc_get_product( $post );
					$found_products[ $post ] = rawurldecode( $product->get_formatted_name() );
				}
			}

			wp_send_json( $found_products );
		}

		/**
		 * Save categories exclusions
		 *
		 * @since 2.0.0
		 * @author Francesco Licandro
		 */
		public function save_exclusions_cat() {

			$posted = $_POST; //phpcs:ignore WordPress.Security.NonceVerification.Missing

			// get older items.
			$old_items = array_filter( explode( ',', get_option( 'yith-ywzm-exclusions-cat-list' ) ) );
			// get new items.
			$new_items = $posted['add_categories'];

			if ( ! is_array( $new_items ) ) {
				$new_items = explode( ',', $posted['add_categories'] );
			}

			$new_items = array_filter( $new_items );

			// merge old with new.
			$exclusions = array_merge( $old_items, $new_items );

			update_option( 'yith-ywzm-exclusions-cat-list', implode( ',', $exclusions ) );
		}


		/**
		 * Delete category from exclusions list
		 *
		 * @since 2.0.0
		 * @author Francesco Licandro
		 */
		public function delete_exclusion_cat() {

			$get = $_REQUEST; //phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$exclusions = array_filter( explode( ',', get_option( 'yith-ywzm-exclusions-cat-list' ) ) );
			$key        = array_search( $get['id'], $exclusions ); //phpcs:ignore
			if ( false !== $key ) {
				unset( $exclusions[ $key ] );
			}

			update_option( 'yith-ywzm-exclusions-cat-list', implode( ',', $exclusions ) );

			$args = array( 'remove_nonce', 'remove_cat_exclusion' );
			$url  = esc_url_raw( remove_query_arg( $args ) );

			wp_safe_redirect( $url );
			exit();
		}

		/**
		 * Ajax action search tags
		 *
		 * @since 2.2.0
		 * @author Francesco Licandro
		 */
		public function search_categories_ajax() {
			ob_start();

			$term = isset( $_GET['term'] ) ? wc_clean( stripslashes( sanitize_text_field( wp_unslash( $_GET['term'] ) ) ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( empty( $term ) ) {
				die();
			}

			$tax = array( 'product_cat' );

			// search by name.
			$args = array(
				'hide_empty' => false,
				'fields'     => 'id=>name',
				'name__like' => $term,
			);

			$categories = get_terms( $tax, $args );

			if ( is_numeric( $term ) ) {
				// search by id.
				$args = array(
					'hide_empty' => false,
					'fields'     => 'id=>name',
					'include'    => array( $term ),
				);

				$found = get_terms( $tax, $args );

				foreach ( $found as $id => $name ) {
					if ( array_key_exists( $id, $categories ) ) {
						continue;
					}
					$categories[ $id ] = $name;
				}
			} else {
				// search by slug.
				$args = array(
					'hide_empty' => false,
					'fields'     => 'id=>name',
					'slug'       => $term,
				);

				$found = get_terms( $tax, $args );

				foreach ( $found as $id => $name ) {
					if ( array_key_exists( $id, $categories ) ) {
						continue;
					}
					$categories[ $id ] = $name;
				}
			}

			// get excluded categories.
			$excluded = array_filter( explode( ',', get_option( 'yith-ywzm-exclusions-cat-list', '' ) ) );

			if ( $categories ) {
				foreach ( $excluded as $id ) {
					if ( array_key_exists( $id, $categories ) ) {
						unset( $categories[ $id ] );
					}
				}
			}

			wp_send_json( $categories );
		}

		/**
		 * Save tag exclusions
		 *
		 * @since 2.2.0
		 * @author Emanuela Castorina <emanuela.castorina@yithemes.com>
		 */
		public function save_exclusions_tag() {

			$posted = $_POST; //phpcs:ignore WordPress.Security.NonceVerification.Missing

			// get older items.
			$old_items = array_filter( explode( ',', get_option( 'yith-ywzm-exclusions-tag-list' ) ) );
			// get new items.
			$new_items = $posted['add_tags'];

			if ( ! is_array( $new_items ) ) {
				$new_items = explode( ',', $posted['add_tags'] );
			}

			$new_items = array_filter( $new_items );

			// merge old with new.
			$exclusions = array_merge( $old_items, $new_items );
			update_option( 'yith-ywzm-exclusions-tag-list', implode( ',', $exclusions ) );
		}


		/**
		 * Delete tag from exclusions list
		 *
		 * @since 2.2.0
		 * @author Emanuela Castorina <emanuela.castorina@yithemes.com>
		 */
		public function delete_exclusion_tag() {

			$get = $_REQUEST; //phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$exclusions = array_filter( explode( ',', get_option( 'yith-ywzm-exclusions-tag-list' ) ) );
			$key        = array_search( $get['id'], $exclusions, true );
			if ( false !== $key ) {
				unset( $exclusions[ $key ] );
			}

			update_option( 'yith-ywzm-exclusions-tag-list', implode( ',', $exclusions ) );

		}

		/**
		 * Ajax action search tags
		 *
		 * @since 2.2.0
		 * @author Emanuela Castorina <emanuela.castorina@yithemes.com>
		 */
		public function search_tags_ajax() {
			ob_start();

			$term = isset( $_GET['term'] ) ? wc_clean( stripslashes( sanitize_text_field( wp_unslash( $_GET['term'] ) ) ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( empty( $term ) ) {
				die();
			}

			$tax = array( 'product_tag' );

			// search by name.
			$args = array(
				'hide_empty' => false,
				'fields'     => 'id=>name',
				'name__like' => $term,
			);

			$tags = get_terms( $tax, $args );

			if ( is_numeric( $term ) ) {
				// search by id.
				$args = array(
					'hide_empty' => false,
					'fields'     => 'id=>name',
					'include'    => array( $term ),
				);

				$found = get_terms( $tax, $args );

				foreach ( $found as $id => $name ) {
					if ( array_key_exists( $id, $tags ) ) {
						continue;
					}
					$tags[ $id ] = $name;
				}
			} else {
				// search by slug.
				$args = array(
					'hide_empty' => false,
					'fields'     => 'id=>name',
					'slug'       => $term,
				);

				$found = get_terms( $tax, $args );

				foreach ( $found as $id => $name ) {
					if ( array_key_exists( $id, $tags ) ) {
						continue;
					}
					$tags[ $id ] = $name;
				}
			}

			// get excluded categories.
			$excluded = array_filter( explode( ',', get_option( 'yith-ywzm-exclusions-tag-list', '' ) ) );

			if ( $tags ) {
				foreach ( $excluded as $id ) {
					if ( array_key_exists( $id, $tags ) ) {
						unset( $tags[ $id ] );
					}
				}
			}

			wp_send_json( $tags );
		}

	}
}

/**
 * Unique access to instance of YITH_YWZM_Exclusions_Handler class
 *
 * @return \YITH_YWZM_Exclusions_Handler
 * @since 2.0.0
 */
function YITH_YWZM_Exclusions_Handler() { //phpcs:ignore
	return YITH_YWZM_Exclusions_Handler::get_instance();
}
