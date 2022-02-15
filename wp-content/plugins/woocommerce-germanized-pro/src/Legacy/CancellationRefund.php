<?php

namespace Vendidero\Germanized\Pro\Legacy;

defined( 'ABSPATH' ) || exit;

class CancellationRefund extends Cancellation {

	public function is_refund() {
		wc_deprecated_function( 'WC_GZDP_Invoice_Cancellation_Refund::is_refund', '3.0.0' );

		return false;
	}

	public function is_total_refund() {
		wc_deprecated_function( 'WC_GZDP_Invoice_Cancellation_Refund::is_total_refund', '3.0.0' );

		return false;
	}
}