<?php

namespace Vendidero\Germanized\Pro\Legacy;

defined( 'ABSPATH' ) || exit;

class Cancellation extends Invoice {

	public function set_parent( $invoice_id ) {
		wc_deprecated_function( 'WC_GZDP_Invoice_Cancellation::set_parent', '3.0.0' );
	}

	public function negate_string( $val ) {
		wc_deprecated_function( 'WC_GZDP_Invoice_Cancellation::negate_string', '3.0.0' );

		return $val;
	}
}