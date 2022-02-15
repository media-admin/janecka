<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_GZDP_Theme_Enfold extends WC_GZDP_Theme {

	public function __construct( $template ) {
		parent::__construct( $template );

		add_filter( 'avia_load_shortcodes', array( $this, 'filter_shortcodes' ), 100, 1 );
	}

	public function filter_shortcodes( $paths )  {
	    $paths = array_merge( $paths, array( WC_germanized_pro()->plugin_path() . '/themes/enfold/shortcodes/' ) );

	    return $paths;
    }

	public function custom_hooks() {
		// Footer info
		$this->footer_init();

		remove_action( 'wp_footer', 'woocommerce_gzd_template_footer_vat_info', wc_gzd_get_hook_priority( 'footer_vat_info' ) );
		remove_action( 'wp_footer', 'woocommerce_gzd_template_footer_sale_info', wc_gzd_get_hook_priority( 'footer_sale_info' ) );

		// Avada Builder Loop Product Info
		add_filter( 'avf_masonry_loop_prepare', array( $this, 'masonry_loop_products' ), 10, 2 );
	}

	public function masonry_loop_products( $entry, $query ) {
		if ( ! isset( $entry['post_type'] ) || 'product' !== $entry['post_type'] ) {
			return $entry;
		}

		$html = woocommerce_gzd_template_add_price_html_suffixes( '', wc_get_product(  $entry['ID'] ) );

		// Remove href-links because not supported by mansory
		$entry['text_after'] .= '<div class="enfold-gzd-loop-info">' . strip_tags( $html, '<del><p><div><span>' ) . '</div>';

		return $entry;
	}

	public function footer_init() {
		global $avia;

		if ( isset( $avia->options['avia']['copyright'] ) ) {

			if ( has_action( 'wp_footer', 'woocommerce_gzd_template_footer_vat_info' ) ) {
				$avia->options['avia']['copyright'] .= '[nolink]' . do_shortcode( '[gzd_vat_info]' );
            }

			if ( has_action( 'wp_footer', 'woocommerce_gzd_template_footer_sale_info' ) ) {
				$avia->options['avia']['copyright'] .= '[nolink]' . do_shortcode( '[gzd_sale_info]' );
            }
		}
	}
}