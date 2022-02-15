<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$upload_dir = \Vendidero\StoreaBill\UploadManager::get_upload_dir();
$path       = $upload_dir['basedir'];
$dirname    = basename( $path );
?>

<h1><?php _e( 'Invoices', 'woocommerce-germanized-pro' ); ?></h1>

<p class="headliner"><?php printf( __( 'Germanized Pro offers some nice invoicing functionality. You may activate our invoicing functionality now and configure it later within the corresponding settings.', 'woocommerce-germanized-pro' ), '' ); ?></p>

<p><?php _e( 'Please make sure to grant write access to the following directory:', 'woocommerce-germanized-pro' ); ?></p>

<pre><code>wp-content/uploads/<?php echo $dirname; ?></code></pre>