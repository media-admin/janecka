<?php
/**
 * Order invoices HTML.
 *
 * @package StoreaBill/Admin
 *
 * @var $sab_order Vendidero\StoreaBill\Interfaces\Order
 */
defined( 'ABSPATH' ) || exit;

$payment_status = $sab_order->get_invoice_payment_status();
?>
<?php if ( 'pending' === $payment_status ) : ?>
	<span id="sab-order-payment-status" class="sab-status sab-payment-status sab-payment-status-pending sab-status-red sab-tip" data-tip="<?php echo esc_attr_x( 'Amount waiting for payment', 'storeabill-core', 'woocommerce-germanized-pro' ); ?>">
	    <?php echo wc_price( $sab_order->get_invoice_total_unpaid() ); ?>
	</span>
<?php elseif ( 'complete' === $payment_status ): ?>
	<span id="sab-order-payment-status" class="sab-status sab-payment-status sab-payment-status-complete sab-status-green">
	    <?php echo _x( 'Paid', 'storeabill-core', 'woocommerce-germanized-pro' ); ?>
	</span>
<?php elseif ( 'cancelled' === $payment_status ): ?>
    <span id="sab-order-payment-status" class="sab-status sab-payment-status sab-payment-status-cancelled sab-status-red">
	    <?php echo _x( 'Cancelled', 'storeabill-core', 'woocommerce-germanized-pro' ); ?>
	</span>
<?php endif; ?>

