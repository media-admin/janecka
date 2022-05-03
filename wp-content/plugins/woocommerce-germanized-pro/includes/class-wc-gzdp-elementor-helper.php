<?php

if ( ! defined( 'ABSPATH' ) )
    exit;

class WC_GZDP_Elementor_Helper {

    protected static $_instance = null;

    protected $widgets = array(
        'delivery_time',
        'shipping_notice',
        'tax_notice',
        'unit_price',
        'units',
	    'defect_description',
	    'deposit',
	    'deposit_packaging_type',
	    'nutrients',
	    'ingredients',
	    'allergenic',
	    'nutri_score'
    );

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function __construct() {
        remove_all_actions( 'woocommerce_gzd_show_elementor_upgrade_notice' );
        add_filter( 'woocommerce_gzd_show_elementor_upgrade_notice', array( $this, 'hide_notice' ), 10 );

        foreach( $this->widgets as $widget ) {
            remove_all_actions( "woocommerce_gzd_elementor_widget_{$widget}_controls" );
            remove_all_actions( "woocommerce_gzd_elementor_widget_{$widget}_render" );

            add_action( "woocommerce_gzd_elementor_widget_{$widget}_controls", array( $this, 'register_controls' ), 10, 1 );
            add_action( "woocommerce_gzd_elementor_widget_{$widget}_render", array( $this, 'render' ), 10, 2 );
        }
    }

    public function hide_notice() {
        return false;
    }

    public function register_controls( $widget ) {
        include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/class-wc-gzdp-elementor-widget-helper.php';

        WC_GZDP_Elementor_Widget_Helper::register_controls( $widget );
    }

    public function render( $product, $widget ) {
        include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/class-wc-gzdp-elementor-widget-helper.php';

        WC_GZDP_Elementor_Widget_Helper::render( $product, $widget );
    }
}

return WC_GZDP_Elementor_Helper::instance();