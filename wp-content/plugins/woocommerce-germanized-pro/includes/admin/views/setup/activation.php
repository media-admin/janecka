<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$gzdp = WC_germanized_pro();
$url  = $gzdp->sanitize_domain( get_bloginfo( 'url' ) );

?>

<h1><?php _e( 'Activate Germanized Pro', 'woocommerce-germanized-pro' ); ?></h1>

<?php if ( $gzdp->is_registered() ) : ?>

	<p class="headliner no-border"><?php printf( __( 'Perfect, your Plugin is already registered. You may manage your license <a href="%s" target="_blank">here</a>.', 'woocommerce-germanized-pro' ), ( is_multisite() ? network_admin_url( 'index.php?page=vendidero' ) : admin_url( 'index.php?page=vendidero' ) ) ); ?></p>

<?php else: ?>

	<p class="headliner"><?php _e( 'Enter your license key to receive automatic Plugin updates and access to the generator API. We will automatically install our Helper Plugin for you.', 'woocommerce-germanized-pro' ); ?></p>

	<p class="form-wrapper">
		<input type="text" name="license_key" id="license_key" value="" placeholder="<?php _e( 'License Key', 'woocommerce-germanized-pro' ); ?>" />
	</p>

	<p class="desc">
		<?php printf( __( 'Find your license key within your <a href="%s" target="_blank">customer account</a>. Please make sure that you have registered <code>%s</code> <a href="%s" target="_blank">as a Domain</a>.', 'woocommerce-germanized-pro' ), 'https://vendidero.de/dashboard/products', $url, 'https://vendidero.de/dashboard/products' ); ?>
	</p>

<?php endif; ?>
