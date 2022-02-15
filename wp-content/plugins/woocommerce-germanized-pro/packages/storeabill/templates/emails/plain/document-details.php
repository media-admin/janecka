<?php
/**
 * Email document details
 *
 * This template can be overridden by copying it to yourtheme/storeabill/emails/document-details.php.
 *
 * HOWEVER, on occasion StoreaBill will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @version 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Action that fires before outputting a Shipment's table in an Email.
 *
 * @param \Vendidero\StoreaBill\Document\Document $document The document instance.
 * @param boolean                                  $sent_to_admin Whether to send this email to admin or not.
 * @param boolean                                  $plain_text Whether this email is in plaintext format or not.
 * @param WC_Email                                 $email The email instance.
 */
do_action( 'storeabill_email_before_document_table', $document, $sent_to_admin, $plain_text, $email );

echo wp_kses_post( wc_strtoupper( sprintf( esc_html_x( 'Details to your %s', 'storeabill-core', 'woocommerce-germanized-pro' ), sab_get_document_type_label( $document->get_type() ) ) ) ) . "\n";
echo "\n" . \Vendidero\StoreaBill\Emails\Mailer::get_items_html( $document, array( // WPCS: XSS ok.
    'plain_text'    => $plain_text,
    'sent_to_admin' => $sent_to_admin,
    'columns'       => array( 'name', 'quantity' )
) );

echo "==========\n\n";

do_action( 'storeabill_email_after_document_table', $document, $sent_to_admin, $plain_text, $email ); ?>
