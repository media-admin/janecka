<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_GZDP_Theme_Flatsome extends WC_GZDP_Theme {

	public function __construct( $template ) {
		parent::__construct( $template );

		// Remove privacy policy text from Flatsome
		add_action( 'init', array( $this, 'privacy_policy' ), 30 );
	}

	public function privacy_policy() {
		if ( apply_filters( 'woocommerce_gzd_disable_wc_privacy_policy_checkbox', true ) ) {
			remove_action( 'woocommerce_checkout_after_order_review', 'wc_checkout_privacy_policy_text', 1 );
		}
	}

	public function set_priorities() {
		$this->priorities = array(
			'loop_price_unit'          => 10,
			'loop_tax_info'            => 11,
			'loop_shipping_costs_info' => 12,
			'loop_delivery_time_info'  => 13,
			'loop_product_units'       => 14,
		);
	}

    public function custom_hooks() {

		if ( ! function_exists( 'wc_gzd_get_shopmark' ) ) {
			return;
		}

		// Add Quick View Compatibility
		add_action( 'flatsome_product_box_actions', array( $this, 'enqeue_variations_script' ), 60 );

		if ( $shopmark = wc_gzd_get_shopmark( 'single_product', 'unit_price' ) ) {
			if ( $shopmark->is_enabled() ) {
				add_action( 'woocommerce_single_product_lightbox_summary', 'woocommerce_gzd_template_single_price_unit', 11 );
			}
		}

	    if ( $shopmark = wc_gzd_get_shopmark( 'single_product', 'legal' ) ) {
		    if ( $shopmark->is_enabled() ) {
			    add_action( 'woocommerce_single_product_lightbox_summary', 'woocommerce_gzd_template_single_legal_info', 11 );
		    }
	    }

	    if ( $shopmark = wc_gzd_get_shopmark( 'single_product', 'delivery_time' ) ) {
		    if ( $shopmark->is_enabled() ) {
			    add_action( 'woocommerce_single_product_lightbox_summary', 'woocommerce_gzd_template_single_delivery_time_info', 25 );
		    }
	    }

		$this->footer_init();

		remove_action ( 'wp_footer', 'woocommerce_gzd_template_footer_vat_info', wc_gzd_get_hook_priority( 'footer_vat_info' ) );
		remove_action ( 'wp_footer', 'woocommerce_gzd_template_footer_sale_info', wc_gzd_get_hook_priority( 'footer_sale_info' ) );

		// Product widget small - support for custom flatsome widgets
		add_filter( 'wc_get_template_part', array( $this, 'check_product_small_template' ), 10, 3 );

		// Override form-checkout.php with default template
		add_filter( 'woocommerce_gzdp_multistep_checkout_force_template_override', array( $this, 'force_template_override' ), 10, 1 );
	}

	public function check_product_small_template( $template, $slug, $name ) {
		if ( "{$slug}-{$name}.php" === "content-product-small.php" ) {
			add_filter( 'woocommerce_get_price_html', array( $this, 'price_html' ), 100, 2 );
		}

		return $template;
	}

	public function price_html( $html, $product ) {
		if ( function_exists( 'woocommerce_gzd_template_add_price_html_suffixes' ) ) {
			$html = woocommerce_gzd_template_add_price_html_suffixes( $html, $product, array() );
		}

		remove_filter( 'woocommerce_get_price_html', array( $this, 'price_html' ), 100 );

		return $html;
	}

	public function force_template_override( $override ) {
		return true;
	}

	public function enqeue_variations_script() {
		if ( ! is_singular( 'product' ) && wp_script_is( 'wc-add-to-cart-variation' ) ) {

			// Add filter for wrapper
			add_filter( 'woocommerce_gzd_add_to_cart_variation_params', array( $this, 'set_quick_view_wrapper' ), 10, 1 );
			wp_enqueue_script( 'wc-gzd-add-to-cart-variation' );

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_register_script( 'wc-gzdp-flatsome-quick-view', WC_germanized_pro()->plugin_url() . '/themes/assets/js/wc-gzdp-flatsome-quick-view' . $suffix . '.js', array( 'flatsome-theme-woocommerce-js' ), WC_GERMANIZED_PRO_VERSION, true );

			if ( wp_script_is( 'wc-gzd-add-to-cart-variation' ) ) {
				wp_enqueue_script( 'wc-gzdp-flatsome-quick-view' );
            }
		}
	}

	public function set_quick_view_wrapper( $params ) {
		$params['wrapper'] = '.product-lightbox-inner';
		return $params;
	}

	public function footer_init() {
		if ( has_action( 'wp_footer', 'woocommerce_gzd_template_footer_vat_info' ) ) {
			add_action( 'flatsome_absolute_footer_primary', 'woocommerce_gzd_template_footer_vat_info', 20 );
        }

		if ( has_action( 'wp_footer', 'woocommerce_gzd_template_footer_sale_info' ) ) {
			add_action( 'flatsome_absolute_footer_primary', 'woocommerce_gzd_template_footer_sale_info', 20 );
        }
	}
}