<?php if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly ?>

<div id="acfw_shipping_overrides" class="panel woocommerce_options_panel acfw_premium_panel">
    <div class="acfw-help-link" data-module="shipping-overrides"></div>
    <div class="shipping-overrides-info">
        <h3><?php _e('Shipping Overrides', 'advanced-coupons-for-woocommerce-free');?></h3>

        <p><?php echo sprintf(
    __('In the <a href="%s" target="_blank">Premium add-on of Advanced Coupons</a> you can give discounts on any shipping method in your store.', 'advanced-coupons-for-woocommerce-free'),
    apply_filters('acfwp_upsell_link', 'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=shippingoverrides')
); ?></p>

        <p><?php _e('Get more creative with your shipping discounts beyond just your usual "free shipping" offer. Give short term discounts on express shipping, specific carriers or even just certain areas. Shipping offers are extremely effective and in Premium you will be able to do more creative shipping offers.', 'advanced-coupons-for-woocommerce-free');?></p>

        <p><a class="button button-primary button-large" href="<?php echo apply_filters('acfwp_upsell_link', 'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=shippingoverrides'); ?>" target="_blank">
            <?php _e('See all features & pricing &rarr;', 'advanced-coupons-for-woocommerce-free');?>
        </a></p>
    </div>
</div>