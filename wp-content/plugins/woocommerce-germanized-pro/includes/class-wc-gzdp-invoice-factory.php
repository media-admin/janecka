<?php

defined( 'ABSPATH' ) || exit;

class WC_GZDP_Invoice_Factory {

	public static function get_invoice( $the_invoice = false, $type = 'simple' ) {
		return WC_GZDP_Document_Factory::get_document( $the_invoice, 'simple' === $type ? 'invoice' : 'invoice_' . $type );
	}
}
