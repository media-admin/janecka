<?php

namespace Vendidero\StoreaBill\Invoice;

defined( 'ABSPATH' ) || exit;

class SimpleCsvExporter extends CsvExporter {

	public function get_document_type() {
		return 'invoice';
	}

	protected function get_additional_query_args() {
		$query_args = parent::get_additional_query_args();

		if ( $payment_status = $this->get_filter( 'payment_status' ) ) {
			$query_args['payment_status'] = (array) $payment_status;
		}

		return $query_args;
	}

	public function render_filters() {
		parent::render_filters();
		?>
		<tr>
			<th scope="row">
				<label for="sab-exporter-payment-status"><?php echo esc_html_x( 'Which payment statuses?', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></label>
			</th>
			<td>
				<select id="sab-exporter-payment-status" name="payment_status[]" class="sab-exporter-payment-status sab-enhanced-select" style="width:100%;" multiple data-placeholder="<?php echo esc_html_x( 'Export all statuses', 'storeabill-core', 'woocommerce-germanized-pro' ); ?>">
					<?php
					$default_statuses = $this->get_default_filter_setting( 'payment_status', array() );

					foreach ( sab_get_invoice_payment_statuses( 'view' ) as $payment_status => $title ) {
						echo '<option value="' . esc_attr( $payment_status ) . '" ' . selected( $payment_status, in_array( $payment_status, $default_statuses ) ? $payment_status : '', false ) . '>' . esc_html( $title ) . '</option>';
					}
					?>
				</select>
			</td>
		</tr>
		<?php
	}
}
