<?php
/**
 * @var $document \Vendidero\StoreaBill\Document\Document
 *
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit;

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php /* translators: %s: Document title */ ?>
	<p><?php printf( esc_html_x( '%s has been created. Find the document attached to this email.', 'storeabill-core', 'woocommerce-germanized-pro' ), $document->get_title() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
<?php

do_action( 'storeabill_email_document_details', $document, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
