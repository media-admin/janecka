<?php if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly ?>

<div class="acfw-order-used-coupons" style="padding: 0 1.5em 1.5em;">
    <strong><?php _e('Coupons', 'advanced-coupons-for-woocommerce-free')?></strong><br>
    <?php if ($coupons_list): ?>
        <?php echo $coupons_list; ?>
    <?php else: ?>
        <span class="no-coupons"><?php _e('No coupons used', 'advanced-coupons-for-woocommerce-free');?></span>
    <?php endif;?>
</div>
