<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

$oldest_invoice     = Vendidero\Germanized\Pro\Legacy\Importer::get_legacy_invoices( 1 );
$year_from          = date_i18n( 'Y' );
$current_year       = date_i18n( 'Y' );
$hide_skip_button   = isset( $hide_skip_button ) ? $hide_skip_button : false;

if ( ! empty( $oldest_invoice ) ) {
	$oldest_invoice = $oldest_invoice[0];
} else {
	$oldest_invoice = false;
}

if ( $oldest_invoice ) {
	$year_from = date_i18n( 'Y', strtotime( $oldest_invoice->post_date ) );
}
?>
<form action="<?php echo admin_url( 'admin-post.php' ); ?>" style="margin-bottom: 1em;" method="post">
	<p class="" style="margin-bottom: 0;">
		<label><?php _e( 'Import starting from', 'woocommerce-germanized-pro' ); ?></label>
		<select name="import_after">
			<?php for ( $i = $year_from; $i <= $current_year; $i++ ): ?>
				<option value="<?php echo absint( $i ); ?>"><?php echo $i; ?></option>
			<?php endfor; ?>
		</select>
        <input type="hidden" name="action" value="wc_gzdp_legacy_invoice_import_start" />
        <?php wp_nonce_field( 'wc-gzdp-legacy-invoice-import-start' ); ?>
		<button style="vertical-align: middle; margin-left: 1em;" type="submit" class="button button-primary"><?php _e( 'Start import', 'woocommerce-germanized-pro' ); ?></button>
        <?php if ( ! $hide_skip_button ) : ?>
            <button class="button button-secondary" name="skip" type="submit" style="vertical-align: middle; margin-left: 1em; float: right;"><?php _e( 'Skip import', 'woocommerce-germanized-pro' ); ?></button>
	    <?php endif; ?>
    </p>
    <div class="clearfix" style="clear: both;"></div>
</form>
