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

$text_align = is_rtl() ? 'right' : 'left';

/*
 * Action that fires before outputting a Shipment's table in an Email.
 *
 * @param \Vendidero\StoreaBill\Document\Document $document The document instance.
 * @param boolean                                  $sent_to_admin Whether to send this email to admin or not.
 * @param boolean                                  $plain_text Whether this email is in plaintext format or not.
 * @param WC_Email                                 $email The email instance.
 */
do_action( 'storeabill_email_before_document_table', $document, $sent_to_admin, $plain_text, $email ); ?>

<h2>
	<?php
	if ( $sent_to_admin && ( $url = $document->get_edit_url() ) ) {
		$before = '<a class="link" href="' . esc_url( $url ) . '">';
		$after  = '</a>';
	} else {
		$before = '';
		$after  = '';
	}
	/* translators: %s: Order ID. */
	echo wp_kses_post( $before . sprintf( _x( 'Details to your %s', 'storeabill-core', 'woocommerce-germanized-pro' ), sab_get_document_type_label( $document->get_type() ) ) . $after );
	?>
</h2>

<div style="margin-bottom: 40px;">
	<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
		<thead>
		<tr>
			<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php echo esc_html_x( 'Item', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></th>
			<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php echo esc_html_x( 'Quantity', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php
		echo \Vendidero\StoreaBill\Emails\Mailer::get_items_html( $document, array( // WPCS: XSS ok.
			'plain_text'    => $plain_text,
			'sent_to_admin' => $sent_to_admin,
			'columns'       => array( 'name', 'quantity' )
		) );
		?>
		</tbody>
	</table>
</div>

<?php
do_action( 'storeabill_email_after_document_table', $document, $sent_to_admin, $plain_text, $email ); ?>
