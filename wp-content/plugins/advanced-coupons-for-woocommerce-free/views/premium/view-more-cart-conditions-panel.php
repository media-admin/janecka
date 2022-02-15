<?php if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly ?>

<div class="more-cart-conditions panel" data-tab="moreconditions">
    <h2><?php _e('More Cart Conditions (Premium)', 'advanced-coupons-for-woocommerce-free');?></h2>
    <p><?php echo sprintf(__('Unlock the full power of Cart Conditions with <a href="%s" target="_blank" rel="norefer noopener">Advanced Coupons Premium</a>. Restrict your coupons using these premium cart conditions.', 'advanced-coupons-for-woocommerce-free'), 'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=morecartconditionslink'); ?></p>

    <div class="cart-conditions-list">
        <?php foreach ($cart_conditions as $cart_condition): ?>
            <div class="cart-condition">
                <h4><?php echo $cart_condition['title']; ?></h4>
                <p><?php echo $cart_condition['description']; ?></p>
            </div>
        <?php endforeach;?>
    </div>

    <a class="button button-primary button-large" href="https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=morecartconditionsbutton" target="_blank" rel="norefer noopener">See all features & pricing →</a>
</div>

<div class="acfw-dyk-notice-holder" style="display: none";>
<?php
\ACFWF()->Notices->display_did_you_know_notice(array(
    'classname'   => 'acfw-dyk-notice-cart-conditions-select',
    'description' => __('You can unlock a whole range of extra cart conditions.', 'advanced-coupons-for-woocommerce-free'),
    'button_link' => 'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=cartconditiontiplink',
));
?>
</div>