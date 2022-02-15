<?php if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly ?>

<div id="acfw_add_products" class="panel woocommerce_options_panel acfw_premium_panel">
    <div class="acfw-help-link" data-module="add-products"></div>
    <div class="add-products-info">
        <h3><?php _e('Add Products', 'advanced-coupons-for-woocommerce-free');?></h3>

        <p><?php echo sprintf(
    __('In the <a href="%s" target="_blank">Premium add-on of Advanced Coupons</a> you make coupons that automatically add products to the cart.', 'advanced-coupons-for-woocommerce-free'),
    apply_filters('acfwp_upsell_link', 'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=addproducts')
); ?></p>

        <p><?php _e("This can also be combined with other features like Cart Conditions and Auto Apply to make products appear in the customer's cart like magic once conditions are met.", 'advanced-coupons-for-woocommerce-free');?></p>

        <p><a class="button button-primary button-large" href="<?php echo apply_filters('acfwp_upsell_link', 'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=addproducts'); ?>" target="_blank">
            <?php _e('See all features & pricing &rarr;', 'advanced-coupons-for-woocommerce-free');?>
        </a></p>

    </div>
</div>