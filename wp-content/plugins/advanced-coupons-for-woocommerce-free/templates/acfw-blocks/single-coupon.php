<?php if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>

<div class="<?php echo implode(' ', $classnames); ?>">
    <?php do_action('acfwf_before_single_coupon_block', $coupon);?>
    <?php if ($has_usage_limit): ?>
        <span class="acfw-coupon-usage-limit"><?php echo sprintf(_n('%s use remaining', '%s uses remaining', $coupon->get_usage_limit(), 'advanced-coupons-for-woocommerce-free'), $coupon->get_usage_limit()); ?></span>
    <?php endif;?>
    <div class="acfw-coupon-content <?php echo $has_description ? 'has-description' : ''; ?>">
        <?php if ('yes' !== $coupon->get_advanced_prop('disable_url_coupon')): ?>
            <a href="<?php echo esc_url($coupon->get_coupon_url()); ?>" title="<?php echo esc_attr($coupon->get_code()); ?>" rel="nofollow">
                <span class="acfw-coupon-code"><?php echo $coupon->get_code(); ?></span>
            </a>
        <?php else: ?>
            <span class="acfw-coupon-code"><?php echo $coupon->get_code(); ?></span>
        <?php endif;?>
        <?php if ($has_discount_value): ?>
            <span class="acfw-coupon-discount-info"><?php echo $coupon->get_discount_value_string(); ?></span>
        <?php endif;?>
        <?php if ($has_description): ?>
            <span class="acfw-coupon-description"><?php echo $coupon->get_description(); ?></span>
        <?php endif;?>
    </div>
    <?php if ($has_schedule): ?>
        <span class="acfw-coupon-schedule"><?php echo $schedule_string; ?></span>
    <?php endif;?>
    <?php do_action('acfwf_after_single_coupon_block', $coupon);?>
</div>