<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$upload_dir = \Vendidero\StoreaBill\UploadManager::get_upload_dir();
$path       = $upload_dir['basedir'];
$dirname    = basename( $path );

$oldest_invoice     = Vendidero\Germanized\Pro\Legacy\Importer::get_legacy_invoices( 1 );
$year_from          = date_i18n( 'Y' );
$current_year       = date_i18n( 'Y' );
$hide_skip_button   = isset( $hide_skip_button ) ? $hide_skip_button : false;
$hide_submit_button = isset( $hide_submit_button ) ? $hide_submit_button : false;
$automation_notice  = get_option( '_wc_gzdp_show_invoice_automation_upgrade_disabled_notice' );

if ( ! empty( $oldest_invoice ) ) {
	$oldest_invoice = $oldest_invoice[0];
} else {
	$oldest_invoice = false;
}

if ( $oldest_invoice ) {
	$year_from = date_i18n( 'Y', $oldest_invoice->post_date );
}
?>

<h1><?php _e( 'Import Documents', 'woocommerce-germanized-pro' ); ?></h1>

<p class="headliner">
    <?php printf( __( '3.0 is a major update with exciting <a href="%1$s" target="_blank">new features</a>. To make your existing documents (invoices, cancellations, packing slips) available within the latest version we will need to import and convert them. Depending on your number of documents that might take some time. The import will be queued and scheduled (processing 10 documents at a time). You can optionally choose to import documents starting from a certain year only. We will not delete old data to make sure no data is lost.', 'woocommerce-germanized-pro' ), 'https://vendidero.de/germanized-pro-3-0' ); ?>
</p>

<div class="notice notice-warning">
    <p>
		<?php printf( __( 'We\'ve temporarily disabled automatic invoice creation to prevent duplicates. After the import is finished (you\'ll be notified) you might want to <a href="%s">re-enable the setting</a>.', 'woocommerce-germanized-pro' ), admin_url( 'admin.php?page=wc-settings&tab=germanized-storeabill' ) ); ?>
    </p>
</div>

<p class="" style="margin-bottom: 0;">
    <label><?php _e( 'Import starting from', 'woocommerce-germanized-pro' ); ?></label>

    <select name="import_after">
        <?php for ( $i = $year_from; $i <= $current_year; $i++ ): ?>
            <option value="<?php echo absint( $i ); ?>"><?php echo $i; ?></option>
        <?php endfor; ?>
    </select>
</p>
