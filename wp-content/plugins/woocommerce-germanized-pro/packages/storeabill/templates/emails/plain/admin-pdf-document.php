<?php
/**
 * @var $document \Vendidero\StoreaBill\Document\Document
 *
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: Document title */
echo sprintf( esc_html_x( '%s has been created. Find the document attached to this email.', 'storeabill-core', 'woocommerce-germanized-pro' ), $document->get_title() ) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

do_action( 'storeabill_email_document_details', $document, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );