<?php

if ( ! defined( 'ABSPATH' ) )
    exit;

class WC_GZDP_Theme_Astra extends WC_GZDP_Theme {

	public function __construct( $template ) {

		parent::__construct( $template );

		add_filter( 'woocommerce_gzd_shopmark_single_product_filters', array( $this, 'single_product_filters' ), 10 );
		add_filter( 'woocommerce_gzd_shopmark_product_loop_filters', array( $this, 'product_loop_filters' ), 10 );

		add_filter( 'woocommerce_gzd_shopmark_product_loop_defaults', array( $this, 'product_loop_defaults' ), 10 );
		add_filter( 'woocommerce_gzd_shopmark_single_product_defaults', array( $this, 'single_product_defaults' ), 10 );

		add_action( 'astra_woo_quick_view_product_summary', array( $this, 'quick_view_summary_hooks' ), 10 );

		add_action( 'admin_notices', array( $this, 'shopmark_notice' ), 30 );
	}

	public function quick_view_summary_hooks() {
		foreach( wc_gzd_get_single_product_shopmarks() as $shopmark ) {
			$shopmark->execute();
		}
	}

	public function set_single_product_filter( $filter ) {
		return 'astra_woo_single_price_after';
	}

	public function shopmark_notice() {
		$screen = get_current_screen();

		if ( $screen && 'woocommerce_page_wc-settings' === $screen->id && isset( $_GET['tab'] ) && 'germanized-shopmarks' === $_GET['tab'] ) {
			include( 'views/html-admin-notice-astra-shopmark.php' );
		}
 	}

	public function single_product_defaults( $defaults ) {
		if ( $this->extension_is_enabled() ) {
			$count = 10;

			foreach( $defaults as $type => $type_data ) {
				$defaults[ $type ]['default_filter']   = 'astra_woo_single_price_after';
				$defaults[ $type ]['default_priority'] = $count++;
			}
		}

		return $defaults;
	}

	public function product_loop_defaults( $defaults ) {
		$count = 10;

		foreach( $defaults as $type => $type_data ) {
			$defaults[ $type ]['default_filter']   = 'astra_woo_shop_price_after';
			$defaults[ $type ]['default_priority'] = $count++;
		}

		return $defaults;
	}

	public function product_loop_filters( $filters ) {
		$filters['astra_woo_shop_price_after'] = array(
			'title'            => __( 'Astra After Price', 'woocommerce-germanized-pro' ),
			'number_of_params' => 1,
			'is_action'        => true,
		);

		return $filters;
	}

	public function single_product_filters( $filters ) {

		if ( $this->extension_is_enabled() ) {
			$filters['astra_woo_single_price_after'] = array(
				'title'            => __( 'Astra After Price', 'woocommerce-germanized-pro' ),
				'number_of_params' => 1,
				'is_action'        => true,
			);
		}

		return $filters;
	}

	protected function extension_is_enabled() {
        return is_callable( array( 'Astra_Ext_Extension', 'is_active' ) ) && Astra_Ext_Extension::is_active('woocommerce' );
    }
}