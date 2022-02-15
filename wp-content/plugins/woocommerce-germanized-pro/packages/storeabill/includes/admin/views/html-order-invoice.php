<?php
/**
 * Order invoices HTML.
 *
 * @package StoreaBill/Admin
 *
 * @var $invoice Vendidero\StoreaBill\Invoice\Simple
 * @var $is_active boolean
 */

use Vendidero\StoreaBill\Admin\Admin;

defined( 'ABSPATH' ) || exit;
?>
<div id="sab-document-<?php echo esc_attr( $invoice->get_id() ); ?>" class="order-document order-invoice invoice-<?php echo esc_attr( $invoice->get_invoice_type() ); ?> <?php echo ( $is_active ? 'active' : '' ); ?> <?php echo ( $invoice->is_editable() ? 'is-editable' : '' ); ?>" data-document-id="<?php echo esc_attr( $invoice->get_id() ); ?>">
	<div class="invoice-header document-header spread">
		<div class="left">
			<h3 class="document-title"><?php echo $invoice->get_title( false ); ?></h3>
			<?php if ( $invoice->is_finalized() ) : ?>
				<span class="document-locked invoice-locked sab-icon dashicons dashicons-lock"></span>
			<?php endif; ?>
		</div>

		<div class="right">
			<?php if ( $invoice->is_finalized() ) : ?>
                <fieldset class="sab-toggle-wrapper">
                    <a class="sab-toggle sab-toggle-ajax sab-toggle-invoice-payment-status" href="#" data-id="<?php echo esc_attr( $invoice->get_id() ); ?>" data-action="storeabill_woo_admin_toggle_invoice_payment_status" data-nonce="<?php echo wp_create_nonce( 'sab-toggle-invoice-payment-status' ); ?>">
                        <span class="sab-input-toggle sab-input-toggle-has-text woocommerce-input-toggle <?php echo ( $invoice->is_paid() ? 'sab-input-toggle--enabled' : 'sab-input-toggle--disabled' ); ?>">
                            <span class="toggle-text toggle-text-enabled"><?php _ex( 'Paid', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></span>
                            <span class="toggle-text toggle-text-disabled"><?php _ex( 'Unpaid', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></span>
                        </span>
                    </a>
                    <input type="checkbox" name="invoice_paid[<?php echo esc_attr( $invoice->get_id() ); ?>]" id="invoice_paid-<?php echo esc_attr( $invoice->get_id() ); ?>" value="1" <?php checked( 'paid', $invoice->get_payment_status() ); ?> />
                </fieldset>
			<?php endif; ?>

            <div class="document-actions">
	            <?php echo Admin::get_document_actions_html( Admin::get_document_actions( $invoice, 'order' ) ); ?>
            </div>

			<span class="invoice-total price sab-price"><?php echo $invoice->get_formatted_price( $invoice->get_total() ); ?></span>
		</div>
	</div>
</div>
