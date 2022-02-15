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

<?php /* translators: %s: Customer first name */ ?>
	<p><?php echo apply_filters( 'storeabill_email_document_customer_salutation', sprintf( _x( 'Hi %s,', 'storeabill-core', 'woocommerce-germanized-pro' ), sab_get_document_salutation( $document ) ), $document, $email ); ?></p><?php // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>

	<p>
		<?php
		/* translators: %s: Site title */
		printf( _x( '%s has been attached to this email. Find details below for your reference:', 'storeabill-core', 'woocommerce-germanized-pro' ), $document->get_title() ); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
		?>
	</p>
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
