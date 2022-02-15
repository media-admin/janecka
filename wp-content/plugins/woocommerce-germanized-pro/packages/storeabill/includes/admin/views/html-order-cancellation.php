<?php
/**
 * Order invoices HTML.
 *
 * @package StoreaBill/Admin
 *
 * @var $cancellation Vendidero\StoreaBill\Invoice\Cancellation
 * @var $is_active boolean
 */

use Vendidero\StoreaBill\Admin\Admin;

defined( 'ABSPATH' ) || exit;
?>
<div id="sab-document-<?php echo esc_attr( $cancellation->get_id() ); ?>" class="order-document order-invoice invoice-<?php echo esc_attr( $cancellation->get_invoice_type() ); ?> <?php echo ( $is_active ? 'active' : '' ); ?> <?php echo ( $cancellation->is_editable() ? 'is-editable' : '' ); ?>" data-document-id="<?php echo esc_attr( $cancellation->get_id() ); ?>">
	<div class="invoice-header document-header spread">
		<div class="left">
			<h3 class="document-title">
                <?php echo $cancellation->get_title( false ); ?>
            </h3>
            <span class="cancellation-parent"><?php printf( _x( 'Invoice %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $cancellation->get_parent_formatted_number() ); ?></span>
            <?php if ( $cancellation->is_finalized() ) : ?>
				<span class="document-locked invoice-locked sab-icon dashicons dashicons-lock"></span>
			<?php endif; ?>
		</div>

		<div class="right">
            <div class="document-actions">
                <?php echo Admin::get_document_actions_html( Admin::get_document_actions( $cancellation, 'order' ) ); ?>
            </div>
			<span class="invoice-total price sab-price"><?php echo $cancellation->get_formatted_price( $cancellation->get_total() ); ?></span>
		</div>
	</div>
</div>
