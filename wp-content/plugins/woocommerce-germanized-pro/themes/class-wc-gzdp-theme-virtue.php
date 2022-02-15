<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_GZDP_Theme_Virtue extends WC_GZDP_Theme {

	public function __construct( $template ) {
		parent::__construct( $template );

		add_filter( 'woocommerce_gzd_shopmark_single_product_filters', array( $this, 'single_product_filters' ), 10 );
		add_filter( 'woocommerce_gzd_shopmark_single_product_defaults', array( $this, 'single_product_defaults' ), 10 );

		add_action( 'admin_notices', array( $this, 'shopmark_notice' ), 30 );
	}

	public function shopmark_notice() {
		$screen = get_current_screen();

		if ( $screen && 'woocommerce_page_wc-settings' === $screen->id && isset( $_GET['tab'] ) && 'germanized-shopmarks' === $_GET['tab'] ) {
			include( 'views/html-admin-notice-virtue-shopmark.php' );
		}
	}

	public function single_product_filters( $filters ) {
		$filters['woocommerce_gzdp_virtue_single_product_price_box'] = array(
			'title'            => __( 'Virtue Price Box', 'woocommerce-germanized-pro' ),
			'number_of_params' => 1,
			'is_action'        => true,
		);

		return $filters;
	}

	public function single_product_defaults( $defaults ) {
		$defaults['unit_price']['default_filter']   = 'woocommerce_gzdp_virtue_single_product_price_box';
		$defaults['unit_price']['default_priority'] = 10;

		$defaults['legal']['default_filter']        = 'woocommerce_gzdp_virtue_single_product_price_box';
		$defaults['legal']['default_priority']      = 11;

		return $defaults;
	}

	public function custom_hooks() {

		// Footer
		$this->footer_info();

		remove_action ( 'wp_footer', 'woocommerce_gzd_template_footer_vat_info', wc_gzd_get_hook_priority( 'footer_vat_info' ) );
		remove_action ( 'wp_footer', 'woocommerce_gzd_template_footer_sale_info', wc_gzd_get_hook_priority( 'footer_sale_info' ) );
	}

	public function footer_info() {
		global $virtue;
		
		if ( isset( $virtue['footer_text'] ) ) {
			if ( has_action( 'wp_footer', 'woocommerce_gzd_template_footer_vat_info' ) ) {
				$virtue['footer_text'] .= ' [gzd_vat_info]';
			}

			if ( has_action( 'wp_footer', 'woocommerce_gzd_template_footer_sale_info' ) ) {
				$virtue['footer_text'] .= ' [gzd_sale_info]';
			}
		}
	}
}