<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! function_exists( 'wc_gzdp_invoice_download_button' ) ) {

	function wc_gzdp_invoice_download_button( $actions, $order ) {
		return $actions;
	}
}

if ( ! function_exists( 'wc_gzdp_invoice_download_html' ) ) {
	function wc_gzdp_invoice_download_html( $order_id ) {

	}
}
