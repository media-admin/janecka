<?php
/**
 * StoreaBill Document Functions
 *
 * Document related functions available on both the front-end and admin.
 *
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sab_document_head() {
	do_action( 'storeabill_document_head' );
}

function sab_document_body_class() {
	global $document;

	$classes = array(
		'type-' .   $document->get_type(),
		'status-' . $document->get_status(),
	);

	// Separates classes with a single space, collates classes for body element
	echo 'class="' . join( ' ', apply_filters( 'storeabill_document_body_class', $classes ) ) . '"';
}

function sab_document_enqueue_scripts() {
	/**
	 * Fires when scripts and styles are enqueued.
	 *
	 * @since 2.8.0
	 */
	do_action( 'storeabill_document_enqueue_scripts' );
}

/**
 * Registers default styles.
 */
function sab_document_register_styles() {
	sab_document_register_style( 'storeabill', \Vendidero\StoreaBill\Package::get_build_url() . '/document/sab-document.css', false );

	$inline_css = '';

	if ( $template = sab_get_current_document_template() ) {
		$fonts = $template->get_fonts();

		if ( ! empty( $fonts ) ) {
			$embed       = new \Vendidero\StoreaBill\Fonts\Embed( $fonts, $template->get_font_display_types(), 'pdf' );
			$inline_css .= $embed->get_inline_css();
		}

		$inline_css .= 'html, body {
			font-size: ' . esc_attr( $template->get_font_size() ) . 'px;
			color: ' . esc_attr( $template->get_color() ) . ';
		}';
	}

	foreach( sab_get_document_font_sizes() as $type => $size ) {
		$inline_css .= '.has-' . sanitize_key( $size['slug'] ) . '-font-size {
			font-size: ' . esc_attr( $size['size'] ) . 'px;
		} ';
	}

	foreach( sab_get_color_names() as $name => $hex ) {
		$inline_css .= '.has-' . sanitize_key( $name ) . '-color {
			color: ' . esc_attr( $hex ) . ';
		} ';

		$inline_css .= '.has-' . sanitize_key( $name ) . '-background-color {
			background-color: ' . esc_attr( $hex ) . ';
		} ';

		$inline_css .= '.has-' . sanitize_key( $name ) . '-border-color {
			border-color: ' . esc_attr( $hex ) . ';
		} ';
	}

	sab_document_add_inline_style( 'storeabill', $inline_css );
	sab_document_enqueue_style( 'storeabill' );
}

/**
 * @return bool|\Vendidero\StoreaBill\Document\Document
 */
function sab_get_current_document() {
	return isset( $GLOBALS['document'] ) ? $GLOBALS['document'] : false;
}

/**
 * @return bool|\Vendidero\StoreaBill\Document\Template
 */
function sab_get_current_document_template() {
	if ( $document = sab_get_current_document() ) {
		return $document->get_template();
	}

	return false;
}