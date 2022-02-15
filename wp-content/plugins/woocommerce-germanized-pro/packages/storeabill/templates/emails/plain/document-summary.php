<?php
/**
 * @var \Vendidero\StoreaBill\Document\Document $document
 *
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit;

echo esc_html( wc_strtoupper( esc_html_x( 'Document summary', 'storeabill-core', 'woocommerce-germanized-pro' ) ) ) . "\n\n";

foreach ( $fields as $field ) {
	echo wp_kses_post( $field['label'] ) . ': ' . wp_kses_post( $field['value'] ) . "\n";
}
