<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

abstract class WC_GZDP_Theme {

	public $name = '';

	public function __construct( $template ) {

		$this->theme = wp_get_theme( $template );
		$this->name  = $this->theme->get_template();

		// Load before WC GZD Hooks
		add_filter( 'woocommerce_germanized_filter_template', array( $this, 'template_filter' ), 0, 4 );
		add_filter( 'woocommerce_gzdp_checkout_template_not_found', array( $this, 'template_filter' ), 0, 3 );

		add_action( 'after_setup_theme', array( $this, 'custom_hooks' ), 0 );
		add_action( 'after_setup_theme', array( $this, 'load_theme_support' ), 10 );
		add_action( 'wp_print_styles', array( $this, 'load_styles' ), 11 );

		// Load after WooCommerce Frontend scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ), 150 );
	}

	public function load_theme_support() {
		add_theme_support( 'woocommerce-germanized' );
	}

	public function template_filter( $template, $template_name, $template_path ) {
		
		// Do not override child themes
		if ( strpos( $template, 'child' ) !== false ) {
			return $template;
		}

		$custom_template = WC_germanized_pro()->plugin_path() . '/themes/' . $this->name . '/templates/' . $template_name;
			
		if ( file_exists( $custom_template ) ) {
			$template = $custom_template;
		}

		return $template;
	}

	public function custom_hooks() {}

	public function load_styles() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$css    = WC_germanized_pro()->plugin_path() . '/themes/assets/css/wc-gzdp-' . $this->name . $suffix . '.css';

		if ( file_exists( $css ) ) {
			wp_register_style( 'wc-gzdp-' . $this->name, WC_germanized_pro()->plugin_url() . '/themes/assets/css/wc-gzdp-' . $this->name . $suffix . '.css', array(), WC_GERMANIZED_PRO_VERSION );	
			wp_enqueue_style( 'wc-gzdp-' . $this->name );
		}
	}

	public function load_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$css    = WC_germanized_pro()->plugin_path() . '/themes/assets/js/wc-gzdp-' . $this->name . $suffix . '.js';

		if ( file_exists( $css ) ) {
			wp_register_script( 'wc-gzdp-' . $this->name, WC_germanized_pro()->plugin_url() . '/themes/assets/js/wc-gzdp-' . $this->name . $suffix . '.js', array(), WC_GERMANIZED_PRO_VERSION, true );
			wp_enqueue_script( 'wc-gzdp-' . $this->name );
		}
	}
}