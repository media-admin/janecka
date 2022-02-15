<?php
/**
 * Order invoices HTML.
 *
 * @package StoreaBill/Admin
 *
 * @var $sab_order Vendidero\StoreaBill\Interfaces\Order
 */
defined( 'ABSPATH' ) || exit;

$active_invoice = isset( $active_invoice ) ? $active_invoice : false;
$cancellations  = $sab_order->get_cancellations();
$invoices       = $sab_order->get_invoices();
?>
<div id="sab-order-invoice-list">
	<?php if ( ! empty( $invoices ) ) : ?>
		<div class="panel-inner">
			<?php foreach( $invoices as $invoice ) :
				$is_active = ( $active_invoice && $invoice->get_id() === $active_invoice ) ? true : false;
				include 'html-order-invoice.php'; ?>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $cancellations ) ) : ?>
		<div class="panel-title title-spread panel-inner panel-order-cancellation-title">
			<h2 class="order-documents-title order-cancellation-title"><?php echo _x( 'Cancellations', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></h2>
		</div>

		<div class="panel-inner">
			<?php foreach( $cancellations as $cancellation ) :
				$is_active = ( $active_invoice && $cancellation->get_id() === $active_invoice ) ? true : false;
				include 'html-order-cancellation.php'; ?>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
