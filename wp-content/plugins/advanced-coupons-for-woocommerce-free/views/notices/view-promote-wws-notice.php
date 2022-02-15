<?php if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$basename        = plugin_basename( 'woocommerce-wholesale-prices/woocommerce-wholesale-prices.bootstrap.php' );
$wwp_plugin_path = trailingslashit( WP_PLUGIN_DIR ) . $basename;
$plugin_key      = 'woocommerce-wholesale-prices';

// if plugin is already installed and active, then we dismiss the notice.
if ( is_plugin_active( $basename ) ) {
    update_option( $notice_option , 'dismissed' );
    return;
}

if ( file_exists( $wwp_plugin_path ) ) {
    $action_url  = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $basename . '&amp;plugin_status=all&amp;s' , 'activate-plugin_' . $basename );
    $action_text = __( 'Activate Plugin' , 'advanced-coupons-for-woocommerce-free' );
} else {
    $action_url  = wp_nonce_url( 'update.php?action=install-plugin&amp;plugin=' . $plugin_key , 'install-plugin_' . $plugin_key );
    $action_text = __( 'Install Plugin' , 'advanced-coupons-for-woocommerce-free' );
}

?>

<div class="<?php echo $notice_class; ?> acfw-admin-notice acfwf-promote-wws-notice notice-success is-dismissable" data-notice="promote_wws">
    <p class="heading">
        <img src="<?php echo $wws_logo; ?>">
        <span><?php _e( 'FREE PLUGIN AVAILABLE' , 'advanced-coupons-for-woocommerce-free' ); ?></span>
    </p>
    <p><?php _e( "Hey store owner! Do you sell to wholesale customers? Did you know that Advanced Coupons has a sister plugin called <strong>Wholesale Suite</strong> which lets you add wholesale pricing to your existing WooCommerce products? Best of all, it's free! You can add basic wholesale pricing to your store and have your wholesale customers make their orders online." , 'advanced-coupons-for-woocommerce-free' ); ?></p>
    <p><?php _e( '<strong>Click here to install WooCommerce Wholesale Prices</strong>' , 'advanced-coupons-for-woocommerce-free' ); ?>
    <p class="action-wrap">
        <a class="action-button" href="<?php echo $action_url; ?>">
            <?php echo $action_text; ?>
        </a>
        <a class="acfw-notice-dismiss" href="javascript:void(0);"><?php _e( 'Dismiss' , 'advanced-coupons-for-woocommerce-free' ); ?></a>
    </p>
    <button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php _e( 'Dismiss this notice..' , 'advanced-coupons-for-woocommerce-free' ); ?></span></button>
</div>