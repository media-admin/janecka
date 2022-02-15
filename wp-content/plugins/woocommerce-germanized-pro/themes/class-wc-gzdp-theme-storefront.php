<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_GZDP_Theme_Storefront extends WC_GZDP_Theme {

	public function __construct( $template ) {
		parent::__construct( $template );

        add_action( 'wp_enqueue_scripts', array( $this, 'add_inline_styles' ), 20 );
	}

    /**
     * Adds woocommerce checkout table background highlight color as inline css
     */
    public function add_inline_styles() {
        $color      = ( get_option( 'woocommerce_gzd_display_checkout_table_color' ) ? get_option( 'woocommerce_gzd_display_checkout_table_color' ) : '#eee' );
        $darker     = function_exists( 'wc_hex_darker' ) ? wc_hex_darker( $color, 10 ) : $color;
        $lighter    = function_exists( 'wc_hex_lighter' ) ? wc_hex_lighter( $color, 10 ) : $color;
        $custom_css = ".woocommerce-checkout .shop_table th { background-color: $darker; } .woocommerce-checkout .shop_table td { background-color: $lighter; }";

        if ( function_exists( 'wc_hex_is_light' ) && ! wc_hex_is_light( $darker ) ) {
            $custom_css .= ".woocommerce-checkout .shop_table th { color: #FFF; }";
        }

        if ( function_exists( 'wc_hex_is_light' ) && ! wc_hex_is_light( $lighter ) ) {
            $custom_css .= ".woocommerce-checkout .shop_table td { color: #FFF; }";
        }

        wp_add_inline_style( 'woocommerce-gzd-layout', $custom_css );
    }

	public function custom_hooks() {
		add_action( 'storefront_footer', array( $this, 'init_footer' ), 30 );
		
		if ( get_option( 'woocommerce_gzdp_checkout_enable' ) === 'yes' ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'deregister_sticky_payment' ), 30 );
		}
	}

	public function deregister_sticky_payment() {
		wp_dequeue_script( 'storefront-sticky-payment' );
	}

	public function init_footer() {
		if ( has_action( 'wp_footer', 'woocommerce_gzd_template_footer_vat_info' ) ) {
			echo do_shortcode( '[gzd_vat_info]' );
		}

		if ( has_action( 'wp_footer', 'woocommerce_gzd_template_footer_sale_info' ) ) {
			echo do_shortcode( '[gzd_sale_info]' );
		}

		remove_action ( 'wp_footer', 'woocommerce_gzd_template_footer_vat_info', wc_gzd_get_hook_priority( 'footer_vat_info' ) );
		remove_action ( 'wp_footer', 'woocommerce_gzd_template_footer_sale_info', wc_gzd_get_hook_priority( 'footer_sale_info' ) );
	}
}