<?php

namespace Vendidero\StoreaBill\Invoice;

defined( 'ABSPATH' ) || exit;

class CancellationCsvExporter extends CsvExporter {

	public function get_document_type() {
		return 'invoice_cancellation';
	}

	public function get_title() {
		return _x( 'Export cancellations as CSV', 'storeabill-core', 'woocommerce-germanized-pro' );
	}

	public function get_description() {
		return _x( 'This tool allows you to generate and download a CSV file containing a list of cancellations', 'storeabill-core', 'woocommerce-germanized-pro' );
	}
}
