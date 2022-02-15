<?php if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly ?>

<div id="acfw_payment_methods_restriction" class="panel woocommerce_options_panel acfw_premium_panel">
    <div class="acfw-help-link" data-module="payment-methods-restriction"></div>
    <div class="add-products-info">
        <h3><?php _e('Payment Methods Restriction', 'advanced-coupons-for-woocommerce-free');?></h3>

        <p><?php echo sprintf(
    __('In the <a href="%s" target="_blank">Premium add-on of Advanced Coupons</a> you can make coupons that automatically filter the available payment gateways visible on the checkout.', 'advanced-coupons-for-woocommerce-free'),
    apply_filters('acfwp_upsell_link', 'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=paymentmethodsrestriction')
); ?></p>

        <p><?php _e("If the coupon is applied, the list of gateways is filtered, effectively restricting which payment options are allowed to be used when alongside this coupon.", 'advanced-coupons-for-woocommerce-free');?></p>

        <p><a class="button button-primary button-large" href="<?php echo apply_filters('acfwp_upsell_link', 'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=paymentmethodsrestriction'); ?>" target="_blank">
            <?php _e('See all features & pricing &rarr;', 'advanced-coupons-for-woocommerce-free');?>
        </a></p>

    </div>
</div>