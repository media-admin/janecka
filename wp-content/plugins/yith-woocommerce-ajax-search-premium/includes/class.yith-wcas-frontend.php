<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Frontend class
 *
 * @author  YITH
 * @package YITH WooCommerce Ajax Search Premium
 * @version 1.2
 */

if ( ! defined( 'YITH_WCAS' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCAS_Frontend' ) ) {
	/**
	 * Admin class.
	 * The class manage all the Frontend behaviors.
	 *
	 * @since 1.0.0
	 */
	class YITH_WCAS_Frontend {

		/**
		 * Constructor
		 *
		 * @access public
		 * @since  1.0.0
		 */
		public function __construct() {

			// custom styles and javascripts.
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ) );
			add_filter( 'yith_wcas_ajax_search_icon', array( $this, 'ajax_loader' ), 11 );
			add_filter( 'body_class', array( $this, 'add_theme_name_to_body' ) );
			add_action( 'after_setup_theme', array( $this, 'porto_customizations' ) );

		}

		/**
		 * Check if the current theme is Porto and override its customizations
		 *
		 * @return void
		 * @since  1.8.4
		 * @author Alberto Ruggiero <alberto.ruggiero@yithemes.com>
		 */
		public function porto_customizations() {

			$template = get_stylesheet_directory() . '/woocommerce/yith-woocommerce-ajax-search.php';
			if ( defined( 'PORTO_VERSION' ) && ( ! is_child_theme() || ( is_child_theme() && ! file_exists( $template ) ) ) ) {
				add_filter( 'woocommerce_locate_template', array( $this, 'override_porto_template' ), 99999, 2 );
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_porto_styles' ), 9999 );
				add_filter( 'yith_wcas_submit_as_input', '__return_false' );
				add_filter( 'yith_wcas_submit_label', array( $this, 'porto_submit_label' ) );
				add_filter( 'yith_wcas_porto_header', '__return_true' );
			}
		}

		/**
		 * Overide the template path
		 *
		 * @param string $template      The template path.
		 * @param string $template_name The template file name.
		 *
		 * @return string
		 * @since  1.8.4
		 * @author Alberto Ruggiero <alberto.ruggiero@yithemes.com>
		 */
		public function override_porto_template( $template, $template_name ) {
			if ( 'yith-woocommerce-ajax-search.php' === $template_name ) {
				$template = trailingslashit( YITH_WCAS_DIR . 'templates/' ) . $template_name;
			}

			return $template;
		}

		/**
		 * Set an icon in the label of the search button
		 *
		 * @return string
		 * @since  1.8.4
		 * @author Alberto Ruggiero <alberto.ruggiero@yithemes.com>
		 */
		public function porto_submit_label() {
			return '<i class="fas fa-search"></i>';
		}

		/**
		 * Enqueue styles for Porto theme
		 *
		 * @return void
		 * @since  1.8.4
		 * @author Alberto Ruggiero <alberto.ruggiero@yithemes.com>
		 */
		public function enqueue_porto_styles() {
			$b = porto_check_theme_options();

			$show_type       = get_option( 'yith_wcas_show_search_list' );
			$show_categories = get_option( 'yith_wcas_show_category_list' );
			$search_width    = 390;


			if ( ( 'yes' === $show_type && 'no' === $show_categories ) || ( 'no' === $show_type && 'yes' === $show_categories ) ) {
				$search_width = 260;
			} elseif ( 'yes' === $show_type && 'yes' === $show_categories ) {
				$search_width = 160;
			}

			ob_start();
			?>
			.searchform div.yith-ajaxsearchform-container,
			.searchform div.yith-ajaxsearchform-container .yith-ajaxsearchform-select {
			display: flex;
			}

			.searchform div.yith-ajaxsearchform-container .search-navigation {
			order: 1;
			}

			.searchform div.yith-ajaxsearchform-container .yith-ajaxsearchform-select {
			order: 2;
			font-size: 0;
			}

			.searchform div.yith-ajaxsearchform-container #yith-searchsubmit {
			order: 3;
			}

			.searchform div.yith-ajaxsearchform-container input {
			border-right: <?php echo( ( 'simple' === $b['search-layout'] ) ? '1px solid ' . esc_html( $b['searchform-border-color'] ) . '!important' : 'none' ); ?>;
			width: <?php echo esc_attr( $search_width ); ?>px!important;
			max-width: 100%!important;
			}

			.searchform div.yith-ajaxsearchform-container > .yith-ajaxsearchform-select select {
			width: 130px!important;
			background-image: url(<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/images/select-bg.svg)!important;
			background-position-x: 96%!important;
			background-position-y: 49%!important;
			background-size: 26px 60px!important;
			background-repeat: no-repeat!important;
			background-attachment: initial!important;
			background-origin: initial!important;
			background-clip: initial!important;
			}

			.autocomplete-suggestions {
			margin-top: <?php echo( ( 'simple' === $b['search-layout'] || 'large' === $b['search-layout'] ) ? '15px' : 0 ); ?>;
			}

			<?php
			$custom_css = ob_get_clean();
			wp_add_inline_style( 'yith_wcas_frontend', $custom_css );
			ob_start()
			?>
			jQuery(
			function ( $ ) {
			$( document ).on(
			'yith_wcas_done',
			function () {
			$( '.search-toggle' ).addClass( 'opened' );
			$( '.searchform' ).show();
			}
			);
			}
			);
			<?php
			$custom_js = ob_get_clean();
			wp_add_inline_script( 'yith_wcas_frontend', $custom_js );

		}

		/**
		 * Enqueue styles and scripts
		 *
		 * @access public
		 * @return void
		 * @since  1.0.0
		 */
		public function enqueue_styles_scripts() {

			$autocomplete_enabled = apply_filters( 'yith_wcas_enable_autocomplete', true );

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			if ( $autocomplete_enabled ) {

				wp_register_script( 'yith_autocomplete', YITH_WCAS_URL . 'assets/js/yith-autocomplete' . $suffix . '.js', array( 'jquery' ), YITH_WCAS_VERSION, true );
				wp_register_script( 'yith_wcas_jquery-autocomplete', YITH_WCAS_URL . 'assets/js/devbridge-jquery-autocomplete' . $suffix . '.js', array( 'jquery' ), YITH_WCAS_VERSION, true );

				wp_register_script( 'yith_wcas_frontend', YITH_WCAS_URL . 'assets/js/frontend' . $suffix . '.js', array( 'jquery' ), YITH_WCAS_VERSION, true );

				wp_localize_script(
					'yith_wcas_frontend',
					'yith_wcas_params',
					array(
						'loading'       => YITH_WCAS_ASSETS_IMAGES_URL . 'ajax-loader.gif',
						'show_all'      => get_option( 'yith_wcas_search_show_view_all' ) === 'yes' ? 'true' : 'false',
						'price_label'   => get_option( 'yith_wcas_search_price_label' ),
						'show_all_text' => get_option( 'yith_wcas_search_show_view_all_text' ),
						'ajax_url'      => $this->get_ajax_url(),
					)
				);

				wp_enqueue_script( 'yith_autocomplete' );
			}

			$css = file_exists( get_stylesheet_directory() . '/woocommerce/yith_ajax_search.css' ) ? get_stylesheet_directory_uri() . '/woocommerce/yith_ajax_search.css' : YITH_WCAS_URL . 'assets/css/yith_wcas_ajax_search.css';
			wp_enqueue_style( 'yith_wcas_frontend', $css, array(), YITH_WCAS_VERSION );

			$padding_to_item = ( get_option( 'yith_wcas_show_sale_badge' ) === 'yes' ) ? '20px' : '0px';

			$sale_color         = get_option( 'yith_wcas_sale_badge' );
			$sale_badge_bgcolor = isset( $sale_color['bgcolor'] ) ? $sale_color['bgcolor'] : '#7eb742';
			$sale_badge_color   = isset( $sale_color['color'] ) ? $sale_color['color'] : '#ffffff';

			$outofstock_color         = get_option( 'yith_wcas_outofstock' );
			$outofstock_badge_bgcolor = isset( $outofstock_color['bgcolor'] ) ? $outofstock_color['bgcolor'] : '#7a7a7a';
			$outofstock_badge_color   = isset( $outofstock_color['color'] ) ? $outofstock_color['color'] : '#ffffff';

			$featured_color         = get_option( 'yith_wcas_featured_badge' );
			$featured_badge_bgcolor = isset( $featured_color['bgcolor'] ) ? $featured_color['bgcolor'] : '#c0392b';
			$featured_badge_color   = isset( $featured_color['color'] ) ? $featured_color['color'] : '#ffffff';

			$thumb_size  = get_option( 'yith_wcas_search_show_thumbnail_dim' );
			$title_color = get_option( 'yith_wcas_search_title_color' );
			$min_height  = $thumb_size + 10;
			$custom_css  = "
                .autocomplete-suggestion{
                    padding-right: {$padding_to_item};
                }
                .woocommerce .autocomplete-suggestion  span.yith_wcas_result_on_sale,
                .autocomplete-suggestion  span.yith_wcas_result_on_sale{
                        background: {$sale_badge_bgcolor};
                        color: {$sale_badge_color}
                }
                .woocommerce .autocomplete-suggestion  span.yith_wcas_result_outofstock,
                .autocomplete-suggestion  span.yith_wcas_result_outofstock{
                        background: {$outofstock_badge_bgcolor};
                        color: {$outofstock_badge_color}
                }
                .woocommerce .autocomplete-suggestion  span.yith_wcas_result_featured,
                .autocomplete-suggestion  span.yith_wcas_result_featured{
                        background: {$featured_badge_bgcolor};
                        color: {$featured_badge_color}
                }
                .autocomplete-suggestion img{
                    width: {$thumb_size}px;
                }
                .autocomplete-suggestion .yith_wcas_result_content .title{
                    color: {$title_color};
                }
                ";
			if ( get_option( 'yith_wcas_show_thumbnail' ) !== 'none' ) {
				$custom_css .= ".autocomplete-suggestion{
                                    min-height: {$min_height}px;
                                }";
			}
			wp_add_inline_style( 'yith_wcas_frontend', $custom_css );

		}

		/**
		 * Return the address to use ajax in javascript
		 *
		 * @return string
		 */
		public function get_ajax_url() {
			$ajax_url = version_compare( WC()->version, '2.4.0', '<' ) ? 'admin-ajax.php?action=yith_ajax_search_products' : WC_AJAX::get_endpoint( '%%endpoint%%' );

			return apply_filters( 'ywcas_ajax_url', $ajax_url );
		}

		/**
		 * Return the images loader updated in settings
		 *
		 * @access public
		 *
		 * @param string $value Value of ajax loader.
		 *
		 * @return string
		 * @since  1.0.0
		 */
		public function ajax_loader( $value ) {
			if ( get_option( 'yith_wcas_loader_url' ) ) {
				$value = get_option( 'yith_wcas_loader_url' );
			}

			return $value;
		}

		/**
		 * Add a class in the body with the name of theme
		 *
		 * @access public
		 *
		 * @param array $classes Array of classes.
		 *
		 * @return string
		 * @since  1.0.0
		 */
		public function add_theme_name_to_body( $classes ) {

			$theme = wp_get_theme();

			if ( ! $theme ) {
				return;
			}

			if ( defined( 'YITH_PROTEO_VERSION' ) ) {
				$classes[] = 'ywcas-proteo-theme';
			} else {
				$classes[] = 'ywcas-' . sanitize_title( $theme->get( 'Name' ) );
			}

			return array_unique( $classes );
		}

	}
}
