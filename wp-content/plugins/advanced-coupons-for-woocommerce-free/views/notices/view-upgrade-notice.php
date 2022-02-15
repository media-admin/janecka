<?php if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use ACFWF\Helpers\Plugin_Constants;
?>

<div class="<?php echo $notice_class; ?> acfw-admin-notice acfwf-upgrade-premium notice-success is-dismissable" data-notice="upgrade">
    <p><img src="<?php echo $acfw_logo; ?>"></p>
    <p><?php _e( 'We hope you’ve been enjoying the free version of Advanced Coupons. Did you know there is a Premium add-on?' , 'advanced-coupons-for-woocommerce-free' ); ?></p>
    <p><?php _e( 'It adds even more advanced features to your coupon so you can market your store better.' , 'advanced-coupons-for-woocommerce-free' ); ?>
    <p class="action-wrap">
        <?php if ( $helper_funcs->is_plugin_installed( Plugin_Constants::PREMIUM_PLUGIN ) ) : ?>
            <a class="acfw-upgrade-button" href="<?php echo wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . Plugin_Constants::PREMIUM_PLUGIN . '&amp;plugin_status=all&amp;s' , 'activate-plugin_' . Plugin_Constants::PREMIUM_PLUGIN ); ?>">
                <?php _e( 'Click here to activate  &rarr;' , 'advanced-coupons-for-woocommerce-free' ); ?>
            </a>
            <span class="plugin-detected"><em><?php _e( 'Plugin detected' , 'advanced-coupons-for-woocommerce-free' ); ?></em></span>
        <?php else : ?>
            <a class="acfw-upgrade-button" href="<?php echo apply_filters( 'acfwp_upsell_link' , 'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=adminnotice' ); ?>" target="_blank">
                <?php _e( 'Click here to see pricing & features  &rarr;' , 'advanced-coupons-for-woocommerce-free' ); ?>
            </a>
        <?php endif; ?>
    </p>
    <button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php _e( 'Dismiss this notice.' , 'advanced-coupons-for-woocommerce-free' ); ?></span></button>
</div>