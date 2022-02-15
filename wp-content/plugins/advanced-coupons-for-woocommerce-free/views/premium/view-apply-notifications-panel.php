<?php if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly ?>

<div id="acfw_apply_notification" class="panel woocommerce_options_panel acfw_premium_panel">
    <div class="acfw-help-link" data-module="one-click-apply"></div>
    <div class="apply-notifications-info">
        <h3><?php _e('One-Click Apply Notifications', 'advanced-coupons-for-woocommerce-free');?></h3>

        <p><?php echo sprintf(
    __('In the <a href="%s" target="_blank">Premium add-on of Advanced Coupons</a> you make coupons that are applied by clicking a notice message on the cart.', 'advanced-coupons-for-woocommerce-free'),
    apply_filters('acfwp_upsell_link', 'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=oneclicknotifications')
); ?></p>

        <p><?php _e('You can also combine this feature with cart conditions to only show the message when customers qualify.', 'advanced-coupons-for-woocommerce-free');?></p>

        <p><a class="button button-primary button-large" href="<?php echo apply_filters('acfwp_upsell_link', 'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=oneclicknotifications'); ?>" target="_blank">
            <?php _e('See all features & pricing &rarr;', 'advanced-coupons-for-woocommerce-free');?>
        </a></p>
    </div>
</div>