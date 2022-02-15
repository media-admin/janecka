<?php if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly ?>

<div id="acfw_scheduler" class="panel woocommerce_options_panel acfw_premium_panel">
    <div class="acfw-help-link" data-module="scheduler"></div>
    <div class="scheduler-info">
        <h3><?php _e('Scheduler', 'advanced-coupons-for-woocommerce-free');?></h3>

        <p><?php echo sprintf(
    __('In the <a href="%s" target="_blank">Premium add-on of Advanced Coupons</a> you can schedule coupons with a specific start date and end date and show a nice notice message when they attempt to use the coupon outside of those times.', 'advanced-coupons-for-woocommerce-free'),
    apply_filters('acfwp_upsell_link', 'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=scheduler')
); ?></p>

        <p><?php _e("This can be great for redirecting people to another page or deal so you don't sales from attempted usage of old coupons or coupons that haven't started yet.", 'advanced-coupons-for-woocommerce-free');?></p>

        <p><a class="button button-primary button-large" href="<?php echo apply_filters('acfwp_upsell_link', 'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=scheduler'); ?>" target="_blank">
            <?php _e('See all features & pricing &rarr;', 'advanced-coupons-for-woocommerce-free');?>
        </a></p>
    </div>
</div>