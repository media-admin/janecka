<?php if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

do_action('acfwf_before_coupons_list_block', $coupons, $classnames);?>

<div class="<?php echo implode(' ', $classnames); ?>">
    <div class="acfw-coupons-grid" style="<?php echo implode('; ', $styles); ?>">
    <?php if (is_array($coupons) && !empty($coupons)): ?>
        <?php foreach ($coupons as $coupon): ?>
            <?php $helper_functions->load_single_coupon_template($coupon, $contentVisibility);?>
        <?php endforeach;?>
    <?php endif;?>
    </div>
</div>

<?php do_action('acfwf_after_coupons_list_block', $coupons, $classnames);?>