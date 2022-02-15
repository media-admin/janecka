<?php

defined( 'ABSPATH' ) || exit;

class WC_GZDP_Document_Factory {

	public static function get_document( $the_document = false, $type = 'invoice' ) {
		$document = sab_get_document( $the_document, $type );

		if ( ! $document ) {
			$document = sab_get_document( 0, $type );
		}

		if ( 'invoice' === $type || 'invoice_simple' === $type ) {
			return new WC_GZDP_Invoice_Simple( $document );
		} elseif ( 'invoice_cancellation' === $type ) {
			return new WC_GZDP_Invoice_Cancellation( $document );
		} elseif ( 'packing_slip' === $type ) {
			return new WC_GZDP_Invoice_Packing_Slip( $document );
		}

		return false;
	}
}
