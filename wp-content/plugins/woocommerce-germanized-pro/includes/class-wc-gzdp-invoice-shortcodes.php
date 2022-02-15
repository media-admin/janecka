<?php

if ( ! defined( 'ABSPATH' ) )
    exit;

class WC_GZDP_Invoice_Shortcodes {

    public static function init() {}

	/**
	 * @return WC_Order|bool
	 */
    public static function get_order() {
	    wc_deprecated_function( 'WC_GZDP_Invoice_Shortcodes::get_order', '3.0.0' );

	    return false;
    }

    public static function get_invoice() {
	    wc_deprecated_function( 'WC_GZDP_Invoice_Shortcodes::get_invoice', '3.0.0' );

	    return false;
    }

    public static function reverse_charge( $atts ) {
	    wc_deprecated_function( 'WC_GZDP_Invoice_Shortcodes::reverse_charge', '3.0.0' );

	    return '';
    }

    public static function if_invoice_shipping_vat_id( $atts, $content = '' ) {
	    wc_deprecated_function( 'WC_GZDP_Invoice_Shortcodes::if_invoice_shipping_vat_id', '3.0.0' );

	    return '';
    }

    public static function third_party_country( $atts ) {
	    wc_deprecated_function( 'WC_GZDP_Invoice_Shortcodes::third_party_country', '3.0.0' );

	    return '';
    }

    public static function small_business_info( $atts ) {
	    wc_deprecated_function( 'WC_GZDP_Invoice_Shortcodes::small_business_info', '3.0.0' );

	    return '';
    }

    public static function order_data( $atts ) {
	    wc_deprecated_function( 'WC_GZDP_Invoice_Shortcodes::order_data', '3.0.0' );

	    return '';
    }

    public static function order_user_data( $atts ) {
	    wc_deprecated_function( 'WC_GZDP_Invoice_Shortcodes::order_user_data', '3.0.0' );

	    return '';
    }

    public static function if_order_data( $atts, $content = '' ) {
	    wc_deprecated_function( 'WC_GZDP_Invoice_Shortcodes::if_order_data', '3.0.0' );

	    return '';
    }

    public static function invoice_data( $atts ) {
	    wc_deprecated_function( 'WC_GZDP_Invoice_Shortcodes::invoice_data', '3.0.0' );

	    return '';
    }

    public static function if_invoice_data( $atts, $content = '' ) {
	    wc_deprecated_function( 'WC_GZDP_Invoice_Shortcodes::if_invoice_data', '3.0.0' );

	    return '';
    }
}