<?php
/**
 * Shortcodes Premium class
 *
 * @author YITH
 * @package YITH\Wishlist\Classes
 * @version 3.0.0
 */

if ( ! defined( 'YITH_WCWL' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCWL_Shortcode_Premium' ) ) {
	/**
	 * YITH WCWL Shortcodes Premium
	 *
	 * @since 1.0.0
	 */
	class YITH_WCWL_Shortcode_Premium {

		/**
		 * Constructor
		 *
		 * @return \YITH_WCWL_Shortcode_Premium
		 * @since 2.0.0
		 */
		public function __construct() {
			// process init.
			self::init();

			// Filters applied to add params to wishlist views.
			add_filter( 'yith_wcwl_available_wishlist_views', array( 'YITH_WCWL_Shortcode_Premium', 'add_wishlist_views' ) );
			add_filter( 'yith_wcwl_wishlist_params', array( 'YITH_WCWL_Shortcode_Premium', 'wishlist_view' ), 5, 6 );
			add_filter( 'yith_wcwl_wishlist_params', array( 'YITH_WCWL_Shortcode_Premium', 'wishlist_create' ), 10, 6 );
			add_filter( 'yith_wcwl_wishlist_params', array( 'YITH_WCWL_Shortcode_Premium', 'wishlist_manage' ), 15, 6 );
			add_filter( 'yith_wcwl_wishlist_params', array( 'YITH_WCWL_Shortcode_Premium', 'wishlist_search' ), 20, 6 );

			// Filters applied to add params to add-to-wishlist view.
			add_filter( 'yith_wcwl_add_to_wishlist_params', array( 'YITH_WCWL_Shortcode_Premium', 'add_to_wishlist_popup' ), 10, 2 );
		}

		/**
		 * Init shortcodes available for the plugin
		 *
		 * @return void
		 */
		public static function init() {
			// register shortcodes.
			add_shortcode( 'yith_wcwl_show_public_wishlist', array( 'YITH_WCWL_Shortcode_Premium', 'show_public_wishlist' ) );

			// register gutenberg blocks.
			add_action( 'init', array( 'YITH_WCWL_Shortcode_Premium', 'register_gutenberg_blocks' ) );
		}

		/**
		 * Register available gutenberg blocks
		 *
		 * @return void
		 */
		public static function register_gutenberg_blocks() {
			$blocks = array(
				'yith-wcwl-show-public-wishlist' => array(
					'style'          => 'yith-wcwl-main',
					'script'         => 'jquery-yith-wcwl',
					'title'          => _x( 'YITH public wishlist link', '[gutenberg]: block name', 'yith-woocommerce-wishlist' ),
					'description'    => _x( 'Shows all public wishlists', '[gutenberg]: block description', 'yith-woocommerce-wishlist' ),
					'shortcode_name' => 'yith_wcwl_show_public_wishlist',

				),
			);

			yith_plugin_fw_gutenberg_add_blocks( $blocks );
		}

		/**
		 * Add premium wishlist views
		 *
		 * @param array $views Array of available views.
		 * @return array New array of available views
		 * @since 2.0.0
		 */
		public static function add_wishlist_views( $views ) {
			return array_merge( $views, array( 'create', 'manage', 'search' ) );
		}

		/**
		 * Filters template params, to add view-specific variables
		 *
		 * @param array  $additional_params Array of params to filter.
		 * @param string $action            Action from query string.
		 * @param array  $action_params     Array of query-string params.
		 * @param string $pagination        Whether or not pagination is enabled for template (not always required; value showuld be "yes" or "no").
		 * @param string $per_page          Number of elements per page (required only if $pagination == 'yes'; should be a numeric string).
		 * @param array  $atts              Original attributes passed via shortcode.
		 *
		 * @return array Filtered array of params
		 * @since 2.0.0
		 */
		public static function wishlist_view( $additional_params, $action, $action_params, $pagination, $per_page, $atts ) {
			/* === VIEW TEMPLATE === */
			if ( ! empty( $additional_params['template_part'] ) && 'view' === $additional_params['template_part'] ) {

				$layout          = ! empty( $additional_params['layout'] ) ? $additional_params['layout'] : get_option( 'yith_wcwl_wishlist_layout', 'traditional' );
				$wishlist        = isset( $additional_params['wishlist'] ) ? $additional_params['wishlist'] : false;
				$is_user_owner   = isset( $additional_params['is_user_owner'] ) ? $additional_params['is_user_owner'] : false;
				$no_interactions = isset( $additional_params['no_interactions'] ) ? $additional_params['no_interactions'] : false;

				$ask_estimate_url              = false;
				$ask_an_estimate_fields        = array();
				$ask_an_estimate_classes       = '';
				$ask_an_estimate_icon          = '';
				$ask_an_estimate_text          = '';
				$show_ask_estimate_button      = $wishlist && 'yes' === get_option( 'yith_wcwl_show_estimate_button' ) && $wishlist->current_user_can( 'ask_an_estimate' );
				$multi_wishlist                = YITH_WCWL()->is_multi_wishlist_enabled();
				$additional_info               = 'yes' === get_option( 'yith_wcwl_show_additional_info_textarea' );
				$additional_info_label         = get_option( 'yith_wcwl_additional_info_textarea_label' );
				$move_to_another_wishlist      = 'yes' === get_option( 'yith_wcwl_show_move_to_another_wishlist' );
				$move_to_another_wishlist_type = get_option( 'yith_wcwl_move_to_another_wishlist_type', 'select' );
				$move_to_another_wishlist_url  = false;
				$show_cb                       = 'yes' === get_option( 'yith_wcwl_cb_show' );
				$show_quantity                 = 'yes' === get_option( 'yith_wcwl_quantity_show' );
				$show_price_variations         = 'yes' === get_option( 'yith_wcwl_price_changes_show' );
				$enable_add_all_to_cart        = 'yes' === get_option( 'yith_wcwl_enable_add_all_to_cart' );
				$enable_drag_n_drop            = 'yes' === get_option( 'yith_wcwl_enable_drag_and_drop' );

				if ( $show_ask_estimate_button && $wishlist ) {
					$ask_estimate_url = esc_url(
						wp_nonce_url(
							add_query_arg(
								'ask_an_estimate',
								$wishlist->get_token(),
								$wishlist->get_url()
							),
							'ask_an_estimate',
							'estimate_nonce'
						)
					);

					$ask_an_estimate_text = get_option( 'yith_wcwl_ask_an_estimate_label', __( 'Ask for an estimate', 'yith-woocommerce-wishlist' ) );

					if ( $additional_info ) {
						$ask_an_estimate_fields = yith_wcwl_maybe_format_field_array( get_option( 'yith_wcwl_ask_an_estimate_fields', array() ) );
					}

					$ask_an_estimate_style   = get_option( 'yith_wcwl_ask_an_estimate_style' );
					$ask_an_estimate_classes = 'link' !== $ask_an_estimate_style ? 'button btn' : '';

					if ( 'button_custom' === $ask_an_estimate_style ) {
						$ask_an_estimate_icon        = get_option( 'yith_wcwl_ask_an_estimate_icon' );
						$ask_an_estimate_custom_icon = get_option( 'yith_wcwl_ask_an_estimate_custom_icon' );

						if ( $ask_an_estimate_icon && ! in_array( $ask_an_estimate_icon, array( 'none', 'custom' ), true ) ) {
							$ask_an_estimate_icon = "<i class='fa {$ask_an_estimate_icon}'></i>";
						} elseif ( 'custom' === $ask_an_estimate_icon && $ask_an_estimate_custom_icon ) {
							$ask_an_estimate_alt_text = __( 'Ask for an estimate', 'yith-woocommerce-wishlist' );
							$ask_an_estimate_icon     = "<img src='{$ask_an_estimate_custom_icon}' alt='{$ask_an_estimate_alt_text}'/>";
						} else {
							$ask_an_estimate_icon = '';
						}
					}
				}

				$move_to_another_wishlist = $multi_wishlist && $move_to_another_wishlist && $is_user_owner && ! $no_interactions;

				if ( $move_to_another_wishlist && 'popup' === $move_to_another_wishlist_type && $wishlist ) {
					$move_to_another_wishlist_url = esc_url(
						wp_nonce_url(
							add_query_arg(
								'move_to_another_wishlist',
								$wishlist->get_token(),
								$wishlist->get_url()
							),
							'move_to_another_wishlist',
							'move_to_another_wishlist_nonce'
						)
					);
				}

				$show_update = $wishlist && $wishlist->current_user_can( 'update_wishlist' ) && ! $no_interactions && ( $show_quantity || $enable_drag_n_drop );

				$additional_params = array_merge(
					$additional_params,
					array(
						'layout'                        => ( $layout && 'traditional' !== $layout ) ? $layout : '',
						'show_ask_estimate_button'      => $show_ask_estimate_button && ! $no_interactions,
						'ask_estimate_url'              => $ask_estimate_url,
						'ask_an_estimate_fields'        => $ask_an_estimate_fields,
						'ask_an_estimate_classes'       => $ask_an_estimate_classes,
						'ask_an_estimate_icon'          => $ask_an_estimate_icon,
						'ask_an_estimate_text'          => isset( $ask_an_estimate_text ) ? $ask_an_estimate_text : '',
						'additional_info'               => $additional_info && ! $no_interactions,
						'additional_info_label'         => $additional_info_label,
						'users_wishlists'               => YITH_WCWL()->get_wishlists(),
						'available_multi_wishlist'      => $multi_wishlist,
						'move_to_another_wishlist'      => $move_to_another_wishlist,
						'move_to_another_wishlist_type' => $move_to_another_wishlist_type,
						'move_to_another_wishlist_url'  => $move_to_another_wishlist_url,
						'show_last_column'              => $additional_params['show_last_column'] || $move_to_another_wishlist,
						'show_cb'                       => $show_cb && ! $no_interactions,
						'show_quantity'                 => $show_quantity,
						'show_price_variations'         => $show_price_variations,
						'enable_add_all_to_cart'        => $enable_add_all_to_cart,
						'enable_drag_n_drop'            => $enable_drag_n_drop && $wishlist && 1 < $wishlist->count_items() && $wishlist->current_user_can( 'sort_items' ) && ! $no_interactions,
						'show_update'                   => apply_filters( 'yith_wcwl_show_wishlist_update_button', $show_update, $wishlist ),
					)
				);
			}

			return $additional_params;
		}

		/**
		 * Filters template params, to add create-specific variables
		 *
		 * @param array  $additional_params Array of params to filter.
		 * @param string $action            Action from query string.
		 * @param array  $action_params     Array of query-string params.
		 * @param string $pagination        Whether or not pagination is enabled for template (not always required; value showuld be "yes" or "no").
		 * @param string $per_page          Number of elements per page (required only if $pagination == 'yes'; should be a numeric string).
		 * @param array  $atts              Original attributes passed via shortcode.
		 *
		 * @return array Filtered array of params
		 * @since 2.0.0
		 */
		public static function wishlist_create( $additional_params, $action, $action_params, $pagination, $per_page, $atts ) {
			/* === CREATE TEMPLATE === */
			if ( ! empty( $action ) && 'create' === $action && YITH_WCWL()->is_multi_wishlist_enabled() ) {
				/*
				 * no wishlist has to be loaded
				 */

				$template_part = 'create';

				$page_title = get_option( 'yith_wcwl_wishlist_create_title' );

				$additional_params = array(
					'page_title'    => $page_title,
					'template_part' => $template_part,
				);
			}

			return $additional_params;
		}

		/**
		 * Filters template params, to add manage-specific variables
		 *
		 * @param array  $additional_params Array of params to filter.
		 * @param string $action            Action from query string.
		 * @param array  $action_params     Array of query-string params.
		 * @param string $pagination        Whether or not pagination is enabled for template (not always required; value showuld be "yes" or "no").
		 * @param string $per_page          Number of elements per page (required only if $pagination == 'yes'; should be a numeric string).
		 * @param array  $atts              Original attributes passed via shortcode.
		 *
		 * @return array Filtered array of params
		 * @since 2.0.0
		 */
		public static function wishlist_manage( $additional_params, $action, $action_params, $pagination, $per_page, $atts ) {
			/* === MANAGE TEMPLATE === */
			if ( ! empty( $action ) && 'manage' === $action && YITH_WCWL()->is_multi_wishlist_enabled() ) {
				/*
				 * someone is managing his wishlists
				 * loads all logged user wishlist
				 */

				$template_part = 'manage';

				$layout = ! empty( $atts['layout'] ) ? $atts['layout'] : get_option( 'yith_wcwl_wishlist_manage_layout', 'traditional' );

				$page_title            = get_option( 'yith_wcwl_wishlist_manage_title' );
				$show_number_of_items  = get_option( 'yith_wcwl_manage_num_of_items_show' );
				$show_date_of_creation = get_option( 'yith_wcwl_manage_creation_date_show' );
				$show_download_as_pdf  = get_option( 'yith_wcwl_manage_download_pdf_show' );
				$show_rename_wishlist  = get_option( 'yith_wcwl_manage_rename_wishlist_show' );
				$show_delete_wishlist  = get_option( 'yith_wcwl_manage_delete_wishlist_show' );

				// retrieve user wishlist.
				$user_wishlists = YITH_WCWL()->get_current_user_wishlists();

				$additional_params = array(
					'layout'                => ( $layout && 'traditional' !== $layout ) ? $layout : '',
					'page_title'            => $page_title,
					'template_part'         => $template_part,
					'user_wishlists'        => $user_wishlists,
					'show_number_of_items'  => 'yes' === $show_number_of_items,
					'show_date_of_creation' => 'yes' === $show_date_of_creation,
					'show_download_as_pdf'  => 'yes' === $show_download_as_pdf,
					'show_rename_wishlist'  => 'yes' === $show_rename_wishlist,
					'show_delete_wishlist'  => 'yes' === $show_delete_wishlist,
				);

				$additional_params['fragment_options'] = YITH_WCWL_Frontend()->format_fragment_options( $additional_params, 'wishlist_manage' );
			}

			return $additional_params;
		}

		/**
		 * Filters template params, to add search-specific variables
		 *
		 * @param array  $additional_params Array of params to filter.
		 * @param string $action            Action from query string.
		 * @param array  $action_params     Array of query-string params.
		 * @param string $pagination        Whether or not pagination is enabled for template (not always required; value showuld be "yes" or "no").
		 * @param string $per_page          Number of elements per page (required only if $pagination == 'yes'; should be a numeric string).
		 * @param array  $atts              Original attributes passed via shortcode.
		 *
		 * @return array Filtered array of params
		 * @since 2.0.0
		 */
		public static function wishlist_search( $additional_params, $action, $action_params, $pagination, $per_page, $atts ) {
			/* === SEARCH TEMPLATE === */
			if ( ! empty( $action ) && 'search' === $action ) {
				/*
				 * someone is searching a wishlist
				 * loads wishlist corresponding to search
				 */

				$wishlist_search = isset( $action_params[1] ) ? $action_params[1] : false;

				if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'wishlist_search' ) && ! $wishlist_search && isset( $_REQUEST['wishlist_search'] ) ) {
					$wishlist_search = sanitize_text_field( wp_unslash( $_REQUEST['wishlist_search'] ) );
				}

				$template_part = 'search';

				$page_title     = get_option( 'yith_wcwl_wishlist_search_title' );
				$search_results = false;

				if ( ! empty( $wishlist_search ) ) {
					$search_args = array(
						's'                   => $wishlist_search,
						'wishlist_visibility' => 0,
						'user_id'             => false,
						'session_id'          => false,
					);

					$count = YITH_WCWL_Wishlist_Factory::get_wishlists_count( $search_args );

					// sets current page, number of pages and element offset.
					$current_page = max( 1, get_query_var( 'paged' ) );
					$offset       = 0;

					// sets variables for pagination, if shortcode atts is set to yes.
					if ( 'yes' === $pagination && $count > 1 ) {
						$pages = ceil( $count / $per_page );

						if ( $current_page > $pages ) {
							$current_page = $pages;
						}

						$offset = ( $current_page - 1 ) * $per_page;

						if ( $pages > 1 ) {
							$page_links = paginate_links(
								array(
									'base'     => esc_url( add_query_arg( array( 'paged' => '%#%' ), YITH_WCWL()->get_wishlist_url( 'search/' . $wishlist_search ) ) ),
									'format'   => '?paged=%#%',
									'current'  => $current_page,
									'total'    => $pages,
									'show_all' => true,
								)
							);
						}
					} else {
						$per_page = false;
					}

					$search_args['limit']  = $per_page;
					$search_args['offset'] = $offset;

					$search_results = YITH_WCWL_Wishlist_Factory::get_wishlists( $search_args );
				}

				$default_wishlist_title = get_option( 'yith_wcwl_wishlist_title' );

				$additional_params = array(
					'page_title'             => $page_title,
					'pages_links'            => isset( $page_links ) ? $page_links : false,
					'search_string'          => $wishlist_search,
					'search_results'         => $search_results,
					'template_part'          => $template_part,
					'default_wishlist_title' => $default_wishlist_title,
				);
			}

			return $additional_params;
		}

		/**
		 * Add additional params to use in wishlist popup
		 *
		 * @param array $additional_info Array of parameters.
		 * @param array $atts Array of shortcode attributes.
		 *
		 * @return array Filtered array of params
		 * @since 2.0.0
		 */
		public static function add_to_wishlist_popup( $additional_info, $atts ) {
			$multi_wishlist = YITH_WCWL()->is_multi_wishlist_enabled();
			$show_popup     = 'default' !== get_option( 'yith_wcwl_modal_enable', 'yes' );
			$lists          = YITH_WCWL()->get_current_user_wishlists();

			$is_single                   = yith_wcwl_is_single();
			$popup_title                 = __( 'Select a wishlist', 'yith-woocommerce-wishlist' );
			$label                       = apply_filters( 'yith_wcwl_button_popup_label', get_option( 'yith_wcwl_add_to_wishlist_popup_text' ) );
			$use_custom_button           = get_option( 'yith_wcwl_add_to_wishlist_style' );
			$popup_classes               = in_array( $use_custom_button, array( 'button_custom', 'button_default' ), true ) ? 'popup_button button alt' : 'popup_button';
			$disable_wishlist            = 'yes' === get_option( 'yith_wcwl_disable_wishlist_for_unauthenticated_users' );
			$show_exists_in_a_wishlist   = 'add' !== get_option( 'yith_wcwl_after_add_to_wishlist_behaviour' );
			$show_count                  = 'yes' === get_option( 'yith_wcwl_show_counter' ) && $is_single;
			$show_view                   = $is_single;
			$add_to_wishlist_modal       = get_option( 'yith_wcwl_modal_enable', 'yes' );
			$added_to_wishlist_behaviour = get_option( 'yith_wcwl_after_add_to_wishlist_behaviour', 'view' );

			if ( ! $additional_info['exists'] && $multi_wishlist ) {
				$found_in_list = YITH_WCWL()->get_wishlist_for_product( $additional_info['product_id'] );

				$additional_info['found_in_list'] = $found_in_list;
				$additional_info['found_item']    = $found_in_list ? $found_in_list->get_product( $additional_info['product_id'] ) : false;
				$additional_info['exists']        = (bool) $found_in_list;

				if ( $show_exists_in_a_wishlist && $found_in_list ) {
					$additional_info['wishlist_url'] = $found_in_list->get_url();
				}
			}

			if ( $show_count ) {
				$additional_info['container_classes'] .= ' with-count';
			}

			$template_part = $additional_info['template_part'];
			$template_part = ( 'add' === $added_to_wishlist_behaviour ) ? 'button' : $template_part;
			$template_part = ( $multi_wishlist && $show_popup && 'yes' === $add_to_wishlist_modal && ( ! isset( $atts['added_to_wishlist'] ) || 'add' === $added_to_wishlist_behaviour ) ) ? 'popup' : $template_part;
			$template_part = ( 'no' === $add_to_wishlist_modal && $multi_wishlist ) ? 'dropdown' : $template_part;
			$template_part = ( $multi_wishlist && $show_exists_in_a_wishlist && $additional_info['exists'] && 'added' !== $template_part ) ? 'browse' : $template_part;

			if ( ! empty( $additional_info['found_in_list'] ) && in_array( $template_part, array( 'browse', 'added' ), true ) ) {
				$template_part = 'modal' === $added_to_wishlist_behaviour && $multi_wishlist ? 'move' : $template_part;
				$template_part = 'remove' === $added_to_wishlist_behaviour ? 'remove' : $template_part;
			}

			if ( 'popup' === $template_part ) {
				$popup_classes .= ' add_to_wishlist single_add_to_wishlist';
			}

			if ( 'remove' === $template_part ) {
				$additional_info['link_classes'] = str_replace( array( 'single_add_to_wishlist', 'add_to_wishlist' ), '', $additional_info['link_classes'] );
				$additional_info['label']        = apply_filters( 'yith_wcwl_remove_from_wishlist_label', __( 'Remove from list', 'yith-woocommerce-wishlist' ) );
			}

			if ( 'move' === $template_part ) {
				$additional_info['link_classes'] = str_replace( array( 'single_add_to_wishlist', 'add_to_wishlist', 'button' ), '', $additional_info['link_classes'] );
				$additional_info['label']        = apply_filters( 'yith_wcwl_move_from_wishlist_label', __( 'Move &rsaquo;', 'yith-woocommerce-wishlist' ) );

				$popup_title = __( 'Move to another wishlist', 'yith-woocommerce-wishlist' );
			}

			$additional_info = array_merge(
				$additional_info,
				array(
					'template_part'            => $template_part,
					'product_image'            => '',
					'popup_title'              => apply_filters( 'yith_wcwl_add_to_wishlist_popup_title', $popup_title, $template_part ),
					'lists'                    => $lists,
					'label_popup'              => $label,
					'available_multi_wishlist' => $multi_wishlist,
					'show_exists'              => $show_exists_in_a_wishlist,
					'link_popup_classes'       => apply_filters( 'yith_wcwl_add_to_wishlist_popup_classes', $popup_classes ),
					'disable_wishlist'         => $disable_wishlist,
					'show_count'               => $show_count,
					'show_view'                => $show_view,
					'add_to_wishlist_modal'    => $add_to_wishlist_modal,
				)
			);

			return $additional_info;
		}

		/**
		 * Show Public Wishlist
		 *
		 * @return string HTML markup containing all public wishlists
		 * @since 2.0.0
		 */
		public static function show_public_wishlist() {

			$wishlists = YITH_WCWL()->get_wishlists(
				array(
					'user_id'             => false,
					'wishlist_visibility' => 'public',
					'show_empty'          => false,
				)
			);
			$atts      = array(
				'wishlists' => $wishlists,
			);

			$template = yith_wcwl_get_template( 'wishlist-public-list.php', $atts, true );

			return apply_filters( 'yith_wcwl_public_wishlist_html', $template );

		}
	}
}

return new YITH_WCWL_Shortcode_Premium();
