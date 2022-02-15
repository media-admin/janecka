<?php
/**
 * Shipment label HTML for meta box.
 *
 * @package WooCommerce_Germanized/DHL/Admin
 */
defined( 'ABSPATH' ) || exit;

/**
 * @var \Vendidero\Germanized\Pro\StoreaBill\PackingSlip $packing_slip
 */
?>

<div class="wc-gzd-shipment-packing-slip column column-spaced col-12" data-packing_slip="<?php echo ( $packing_slip ? esc_attr( $packing_slip->get_id() ) : '' ); ?>">
	<h4><?php echo ( $packing_slip ? $packing_slip->get_title() : __( 'Packing Slip', 'woocommerce-germanized-pro' ) ); ?></h4>

	<?php if ( $packing_slip ) : ?>
		<div class="wc-gzd-shipment-packing-slip-content">
			<div class="shipment-packing-slip-actions">
				<div class="shipment-packing-slip-actions-wrapper shipment-packing-slip-create">
                    <a class="button button-secondary download-shipment-packing-slip" data-packing_slip="<?php echo esc_attr( $packing_slip->get_id() ); ?>" href="<?php echo $packing_slip->get_download_url();?>" target="_blank"><?php _e( 'Download', 'woocommerce-germanized-pro' ); ?></a>
                    <a class="create-packing-slip" href="#" title="<?php _e( 'Refresh packing slip', 'woocommerce-germanized-pro' ); ?>"><?php _e( 'Refresh', 'woocommerce-germanized-pro' ); ?></a>
                    <a class="remove-packing-slip delete" data-packing_slip="<?php echo esc_attr( $packing_slip->get_id() ); ?>" href="#"><?php _e( 'Delete', 'woocommerce-germanized-pro' ); ?></a>
                </div>
			</div>
		</div>
	<?php else: ?>
		<a class="button button-secondary create-packing-slip" href="#" title="<?php _e( 'Create packing slip', 'woocommerce-germanized-pro' ); ?>"><?php _e( 'Create', 'woocommerce-germanized-pro' ); ?></a>
	<?php endif; ?>
</div>