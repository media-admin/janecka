<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Frontend class
 *
 * @author YITH
 * @package YITH\ZoomMagnifier\Classes
 * @version 1.1.2
 */

if ( ! defined( 'YITH_WCMG' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCMG_Frontend' ) ) {
	/**
	 * Admin class.
	 * The class manage all the Frontend behaviors.
	 *
	 * @since 1.0.0
	 */
	class YITH_WCMG_Frontend {


		/**
		 * Constructor
		 *
		 * @access public
		 * @since  1.0.0
		 */
		public function __construct() {

			// add the action only when the loop is initializate.
			add_action( 'template_redirect', array( $this, 'render' ) );
		}

		/**
		 * Render zoom.
		 */
		public function render() {
			if ( ! apply_filters( 'yith_wczm_featured_video_enabled', false ) ) {


				//Zoom template
				remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
				add_action( 'woocommerce_before_single_product_summary', array( $this, 'show_product_images' ), 20 );

				//Slider template
				remove_action( 'woocommerce_product_thumbnails', 'woocommerce_show_product_thumbnails', 20 );

				if ( get_option( 'ywzm_hide_thumbnails', 'no' ) !== 'yes'  )
					add_action( 'woocommerce_product_thumbnails', array( $this, 'show_product_thumbnails' ), 20 );


				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ) );

				// add attributes to product variations.
				add_filter( 'woocommerce_available_variation', array( $this, 'available_variation' ), 10, 3 );
			}
		}


		/**
		 * Change product-single.php template
		 *
		 * @access public
		 * @return void
		 * @since  1.0.0
		 */
		public function show_product_images() {
			wc_get_template( 'single-product/product-image-magnifier.php', array(), '', YITH_YWZM_DIR . 'templates/' );
		}


		/**
		 * Change product-thumbnails.php template
		 *
		 * @access public
		 * @return void
		 * @since  1.0.0
		 */
		public function show_product_thumbnails() {

			wc_get_template( 'single-product/product-thumbnails-magnifier.php', array(), '', YITH_YWZM_DIR . 'templates/' );
		}


		/**
		 * Enqueue styles and scripts
		 *
		 * @access public
		 * @return void
		 * @since  1.0.0
		 */
		public function enqueue_styles_scripts() {
			global $post;

			if ( is_product() || ( ! empty( $post->post_content ) && strpos($post->post_content, 'product_page') !== false ) ) {

				wp_register_script(
					'ywzm-magnifier',
					apply_filters( 'ywzm_magnifier_script_register_path', YITH_WCMG_URL . 'assets/js/' . yit_load_js_file( 'yith_magnifier.js' ) ),
					array( 'jquery' ),
					YITH_YWZM_SCRIPT_VERSION,
					true
				);

				wp_localize_script(
					'ywzm-magnifier',
					'yith_wc_zoom_magnifier_storage_object',
					apply_filters(
						'yith_wc_zoom_magnifier_front_magnifier_localize',
						array(
							'ajax_url'          => admin_url( 'admin-ajax.php' ),
							'mouse_trap_width'  => apply_filters( 'yith_wczm_mouse_trap_with', '100%' ),
							'mouse_trap_height' => apply_filters( 'yith_wczm_mouse_trap_height', '100%' ),
						)
					)
				);

				wp_register_script(
					'ywzm_frontend',
					YITH_WCMG_URL . 'assets/js/' . yit_load_js_file( 'ywzm_frontend.js' ),
					array(
						'jquery',
						'ywzm-magnifier',
					),
					YITH_YWZM_SCRIPT_VERSION,
					true
				);

				wp_register_style( 'ywzm-magnifier', YITH_WCMG_URL . 'assets/css/yith_magnifier.css', array(), YITH_YWZM_SCRIPT_VERSION );

				$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';


				$colors_default = Array(
					'background' => 'white',
					'background_hover' => 'white',
					'icon' => 'black',
					'icon_hover' => 'white',
				);

				$slider_colors_default = Array(
					'background' => 'white',
					'border' => 'black',
					'arrow' => 'black',
				);

				$lighbox_background_colors_array = get_option( 'ywzm_lightbox_icon_colors_background', $colors_default );
				$lighbox_icon_colors_array = get_option( 'ywzm_lightbox_icon_colors_icon', $colors_default );

				$zoom_background_colors_array = get_option( 'ywzm_zoom_icon_colors_background', $colors_default );
				$zoom_icon_colors_array = get_option( 'ywzm_zoom_icon_colors_icon', $colors_default );

				$change_image_on = get_option( 'ywzm_change_image_on', 'click' );
				$thumbnails_opacity = get_option( 'ywzm_thumbnails_opacity', 'no' );
				$thumbnails_grey_scale = get_option( 'ywzm_thumbnails_grey_scale', 'no' );


				$slider_colors_array = get_option( 'yith_wcmg_slider_style_colors', $slider_colors_default );
				$slider_colors_hover_array = get_option( 'yith_wcmg_slider_style_colors_hover', $slider_colors_default );

				wp_localize_script(
					'ywzm_frontend',
					'ywzm_data',
					array(
						'lighbox_background_colors' => $lighbox_background_colors_array,
						'lighbox_icon_colors' => $lighbox_icon_colors_array,
						'zoom_background_colors' => $zoom_background_colors_array,
						'zoom_icon_colors' => $zoom_icon_colors_array,
						'change_image_on' => $change_image_on,
						'thumbnails_opacity' => $thumbnails_opacity,
						'thumbnails_grey_scale' => $thumbnails_grey_scale,
						'slider_colors_array' => $slider_colors_array,
						'slider_colors_hover_array' => $slider_colors_hover_array,
					)
				);

				// Enqueue PrettyPhoto style and script.
				$wc_assets_path = str_replace( array( 'http:', 'https:' ), '', WC()->plugin_url() ) . '/assets/';

				// Enqueue scripts.
				wp_enqueue_script( 'prettyPhoto', $wc_assets_path . 'js/prettyPhoto/jquery.prettyPhoto' . $suffix . '.js', array( 'jquery' ), WC()->version, true );
//				wp_enqueue_script( 'prettyPhoto-init', $wc_assets_path . 'js/prettyPhoto/jquery.prettyPhoto.init' . $suffix . '.js', array( 'jquery' ), WC()->version, true );
				wp_enqueue_script( 'ywzm-magnifier' );
				wp_enqueue_script( 'ywzm_frontend' );

				// Enqueue Style.
				$css = file_exists( get_stylesheet_directory() . '/woocommerce/yith_magnifier.css' ) ? get_stylesheet_directory_uri() . '/woocommerce/yith_magnifier.css' : YITH_WCMG_URL . 'assets/css/frontend.css';
				wp_enqueue_style( 'ywzm-prettyPhoto', $wc_assets_path . 'css/prettyPhoto.css', array(), YITH_YWZM_SCRIPT_VERSION );
				wp_enqueue_style( 'ywzm-magnifier' );
				wp_enqueue_style( 'ywzm_frontend', $css, array(), YITH_YWZM_SCRIPT_VERSION );

				wp_add_inline_style( 'ywzm_frontend', $this->get_custom_css() );
				wp_add_inline_style( 'ywzm-prettyPhoto', $this->get_custom_css_prettyphoto() );

				/**
				 * Add custom init PrettyPhoto
				 */

				wp_localize_script(
					'ywzm_frontend',
					'ywzm_prettyphoto_data',
					array(
						'lighbox_background_colors' => $lighbox_background_colors_array,
						'lighbox_icon_colors' => $lighbox_icon_colors_array,
						'zoom_background_colors' => $zoom_background_colors_array,
						'zoom_icon_colors' => $zoom_icon_colors_array,
					)
				);

				wp_enqueue_script( //phpcs:ignore
					'yith-ywzm-prettyPhoto-init',
					apply_filters( 'ywzm_src_prettyphoto_script', YITH_WCMG_URL . 'assets/js/init.prettyPhoto.js' ),
					array(
						'jquery',
						'prettyPhoto',
					),
					false,
					true
				);

			}
		}

		public function get_custom_css(){

			$custom_css         = '';

			$slider_colors_default = Array(
					'background' => 'white',
					'border' => 'black',
					'arrow' => 'black',
				);

			$sizes_default = Array(
				'dimensions' => array(
				'slider' => '25',
				'arrow' => '22',
				'border' => '2',
				));

			$colors_default = Array(
				'background' => 'white',
				'background_hover' => 'white',
				'icon' => 'black',
				'icon_hover' => 'white',
			);

			$slider_colors_array = get_option( 'yith_wcmg_slider_style_colors', $slider_colors_default );
			$slider_colors_hover_array = get_option( 'yith_wcmg_slider_style_colors_hover', $slider_colors_default );
			$border_radius = get_option( 'yith_wcmg_slider_radius', '50' );
			$sizes = get_option( 'yith_wcmg_slider_sizes', $sizes_default );

			$custom_css .= "
                    .yith_magnifier_loading {
                       background-color: {$slider_colors_array['arrow']};
					}
                    ";

			if ( get_option( 'ywzm_slider_arrows_display', 'hover' ) === 'fixed' ){
				$custom_css .= "
                    .single-product.woocommerce .thumbnails #slider-prev,
                    .single-product.woocommerce .thumbnails #slider-next {
                        display: block !important;
					}
                    ";
			}

			if ( is_array($slider_colors_array) ) {

				$custom_css .= "
                    #slider-prev, #slider-next {
                        background-color: {$slider_colors_array['background']};
                        border: {$sizes['dimensions']['border']}px solid {$slider_colors_array['border']};
                        border-radius:{$border_radius}% ;
                        width:{$sizes['dimensions']['slider']}px !important;
                        height:{$sizes['dimensions']['slider']}px !important;
                    }

                    .yith_slider_arrow span{
                        width:{$sizes['dimensions']['slider']}px !important;
                        height:{$sizes['dimensions']['slider']}px !important;
                    }
                    ";

				$custom_css .= "
                    #slider-prev:hover, #slider-next:hover {
                        background-color: {$slider_colors_hover_array['background']};
                        border: {$sizes['dimensions']['border']}px solid {$slider_colors_hover_array['border']};
                    }
                    ";

				$custom_css .= "
                   .thumbnails.slider path:hover {
                        fill:{$slider_colors_hover_array['arrow']};
                    }
                    ";

				$custom_css .= "
                    .thumbnails.slider path {
                        fill:{$slider_colors_array['arrow']};
                        width:{$sizes['dimensions']['slider']}px !important;
                        height:{$sizes['dimensions']['slider']}px !important;
                    }

                    .thumbnails.slider svg {
                       width: {$sizes['dimensions']['arrow']}px;
                       height: {$sizes['dimensions']['arrow']}px;
                    }

                    ";

			}


				//Lighbox expand icon

				$show_lightbox_icon = get_option( 'ywzm_enable_lightbox_feature', 'yes' ) == 'yes' ? 'inline' : 'none';

				$custom_css .= "
                    .yith_magnifier_mousetrap .yith_expand {
                        display: {$show_lightbox_icon} !important;
                    }
                    ";

				$lighbox_background_colors_array = get_option( 'ywzm_lightbox_icon_colors_background', $colors_default );
				$lighbox_icon_colors_array = get_option( 'ywzm_lightbox_icon_colors_icon', $colors_default );
				$lighbox_icon_size = get_option( 'ywzm_lightbox_icon_size', '25' );
				$lighbox_radius = get_option( 'yith_wcmg_lightbox_radius', '0' );
				$lighbox_icon_position = get_option( 'ywzm_lightbox_icon_position', 'top-right' );
				$zoom_icon_position = get_option( 'ywzm_zoom_icon_position', 'top-right' );

				if ( $lighbox_icon_position == $zoom_icon_position && get_option( 'ywzm_zoom_icon', 'yes' ) == 'yes' ){

					$arr = explode("-", $lighbox_icon_position, 2);
					$position = $arr[0];

					if ( $position == 'top' ){
						$top = '40px';
						$bottom = 'initial';
					}
					else{
						$top = 'initial';
						$bottom = '40px';
					}
				}
				else{

					$arr = explode("-", $lighbox_icon_position, 2);
					$position = $arr[0];

					if ( $position == 'top' ){
						$top = '10px';
						$bottom = 'initial';
					}
					else{
						$top = 'initial';
						$bottom = '10px';
					}
				}

				if ( $lighbox_icon_position === 'top-right' || $lighbox_icon_position === 'bottom-right' ){
					$left = 'initial';
					$right = '10px';
				}
				else{
					$left = '10px';
					$right = 'initial';
				}

				$custom_css .= "
                    div.pp_woocommerce a.yith_expand {
                     background-color: {$lighbox_background_colors_array['background']};
                     width: {$lighbox_icon_size}px;
                     height: {$lighbox_icon_size}px;
                     top: {$top};
                     bottom: {$bottom};
                     left: {$left};
                     right: {$right};
                     border-radius: {$lighbox_radius}%;
                    }

                    .expand-button-hidden svg{
                       width: {$lighbox_icon_size}px;
                       height: {$lighbox_icon_size}px;
					}

					.expand-button-hidden path{
                       fill: {$lighbox_icon_colors_array['icon']};
					}
                    ";


				//zoom icon

				$show_zoom_icon = get_option( 'ywzm_zoom_icon', 'yes' ) == 'yes' ? 'inline' : 'none';

				$custom_css .= "
                    .yith_magnifier_mousetrap .yith_zoom_icon {
                        display: {$show_zoom_icon} !important;
                    }
                    ";

				$zoom_background_colors_array = get_option( 'ywzm_zoom_icon_colors_background', $colors_default );
				$zoom_icon_colors_array = get_option( 'ywzm_zoom_icon_colors_icon', $colors_default );
				$zoom_icon_size = get_option( 'ywzm_zoom_icon_size', '25' );
				$zoom_radius = get_option( 'yith_wcmg_zoom_radius', '0' );


				switch ( $zoom_icon_position ) {
					case 'top-left':
						$top = '10px';
						$bottom = 'initial';
						$left = '10px';
						$right = 'initial';
						break;
					case 'bottom-left':
						$top = 'initial';
						$bottom = '10px';
						$left = '10px';
						$right = 'initial';
						break;
					case 'bottom-right':
						$top = 'initial';
						$bottom = '10px';
						$left = 'initial';
						$right = '10px';
						break;
					default: //top-right
						$top = '10px';
						$bottom = 'initial';
						$left = 'initial';
						$right = '10px';
						break;
				}


				$custom_css .= "
                   div.pp_woocommerce span.yith_zoom_icon {
                     background-color: {$zoom_background_colors_array['background']};
                     width: {$zoom_icon_size}px;
                     height: {$zoom_icon_size}px;
                     top: {$top};
                     bottom: {$bottom};
                     left: {$left};
                     right: {$right};
                     border-radius: {$zoom_radius}%;

                    }

                    .zoom-button-hidden svg{
                       width: {$zoom_icon_size}px;
                       height: {$zoom_icon_size}px;
					}

					.zoom-button-hidden path{
                       fill: {$zoom_icon_colors_array['icon']};
					}

                    ";

				// Thumbnails CSS

				$active_thumbnail_border_color = get_option( 'ywzm_active_thumbnails_border_color', '#000' );

				$custom_css .= "
						.yith_magnifier_gallery .yith_magnifier_thumbnail.active-thumbnail img{
						 border: 2px solid {$active_thumbnail_border_color};
						}
                    ";

				if ( get_option( 'ywzm_thumbnails_opacity', 'no' ) === 'yes' ){

					$custom_css .= "
						.yith_magnifier_thumbnail:not(.active-thumbnail) img {
							opacity: 0.5 !important;
						}
                    ";
				}

				if ( get_option( 'ywzm_thumbnails_grey_scale', 'no' ) === 'yes' ){

					$custom_css .= "
						.yith_magnifier_thumbnail:not(.active-thumbnail) img {
							opacity: 0.5 !important;
							filter: grayscale(100%) !important;
						}
                    ";
				}

				 if ( get_option( 'ywzm_thumbnails_zoom_effect') === 'yes' ){

					 $custom_css .= "

					.yith_magnifier_thumbnail img{
                        -webkit-transform: scale(1);
						transform: scale(1);
						-webkit-transition: .3s ease-in-out;
						transition: .3s ease-in-out;
					}

					.yith_magnifier_thumbnail:hover img{
                        -webkit-transform: scale(1.2);
						transform: scale(1.2);
					}

                    ";

				 }


				 if ( defined( 'YITH_PROTEO_VERSION' ) ){

					 $custom_css .= "
					 @media only screen and (max-width: 992px) {
                        .single-product-layout-cols .images {
                            width: 100%;
						}
					  }
					  ";
				 }


			return apply_filters( 'yith_ywzm_custom_css', $custom_css );
		}

		public function get_custom_css_prettyphoto (){

			$colors_default = Array(
				'background' => 'white',
				'background_hover' => 'white',
				'icon' => 'black',
				'icon_hover' => 'white',
			);

			$lightbox_overlay_color = get_option( 'ywzm_lightbox_overlay_color', '#000' );
			$lighbox_icon_size = get_option( 'ywzm_lightbox_icon_size', '25' );
			$lighbox_background_colors_array = get_option( 'ywzm_lightbox_icon_colors_background', $colors_default );
			$lighbox_icon_colors_array = get_option( 'ywzm_lightbox_icon_colors_icon', $colors_default );

			$custom_css = '';

			$custom_css .= "
                     div.pp_overlay {
                        background-color: {$lightbox_overlay_color};
                    }

                    div.pp_woocommerce a.pp_contract, div.pp_woocommerce a.pp_expand{
                        content: unset !important;
                        background-color: {$lighbox_background_colors_array['background']};
                        width: {$lighbox_icon_size}px;
                        height: {$lighbox_icon_size}px;
                        margin-top: 5px;
						margin-left: 5px;
                    }

                    a.pp_expand:before, a.pp_contract:before{
                    content: unset !important;
                    }

                     a.pp_expand .expand-button-hidden svg, a.pp_contract .expand-button-hidden svg{
                       width: {$lighbox_icon_size}px;
                       height: {$lighbox_icon_size}px;
                       padding: 5px;
					}

					.expand-button-hidden path{
                       fill: {$lighbox_icon_colors_array['icon']};
					}

					div.pp_woocommerce a.pp_close {
                        top: 3px;
                        right: 8px;
                    }

					div.pp_woocommerce a.pp_close:before {
                        font-size: 40px;
                    }
                    ";


			return apply_filters( 'yith_ywzm_custom_css_prettyphoto', $custom_css );
		}


		/**
		 * Add attributes to product variations
		 *
		 * @param array                $data Data.
		 * @param WC_Product_Variable  $wc_prod Variable product.
		 * @param WC_Product_Variation $variation Variation.
		 *
		 * @return mixed
		 */
		public function available_variation( $data, $wc_prod, $variation ) {

			$attachment_id = get_post_thumbnail_id( $variation->get_id() );
			$attachment    = wp_get_attachment_image_src( $attachment_id, 'shop_magnifier' );

			$data['image_magnifier'] = $attachment ? current( $attachment ) : '';

			return $data;
		}
	}
}
