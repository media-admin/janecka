<?php if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly ?>

<div class="woocommerce">

    <ul class="acfw-cart-condition-tabs wc-tabs">
        <?php foreach ($tabs as $key => $label): ?>
            <li class="<?php echo $key; ?>-tab <?php echo 'rules' === $key ? 'active' : ''; ?>">
                <a href="javascript:void(0)" data-tab="<?php echo $key; ?>">
                    <span><?php echo $label; ?></span>
                </a>
            </li>
        <?php endforeach;?>
    </ul>

    <div id="<?php echo esc_attr($panel_id); ?>" class="panel woocommerce_options_panel"
        <?php foreach ($panel_data_atts as $data_key => $data_value):
    echo sprintf('data-%s="%s"', $data_key, esc_attr(json_encode($data_value)));
endforeach;?>>
        <div class="acfw-help-link" data-module="cart-conditions"></div>
        <div class="condition-data-wrap panel" data-tab="rules">
            <div class="acfw-tab-info">
                <h3><?php _e('Cart Conditions', 'advanced-coupons-for-woocommerce-free')?></h3>
                <p><?php _e("Apply this coupon to be applied if the following condition groups and conditions are true.", 'advanced-coupons-for-woocommerce-free');?></p>
            </div>
            <h2><?php _e('If…', 'advanced-coupons-for-woocommerce-free');?></h2>
            <div class="condition-groups"></div>
            <div class="add-condition-group-trigger">
                <div class="field-control">
                    <button type="button" class="button" id="add-cart-condition-group">
                        <i class="dashicons dashicons-plus"></i>
                        <?php _e("Add a New 'OR' Group", 'advanced-coupons-for-woocommerce-free');?>
                    </button>
                </div>
            </div>
            <h2><?php _e('…Then allow coupon to be applied', 'advanced-coupons-for-woocommerce-free');?></h2>
        </div>

        <div class="additional-settings panel" data-tab="settings">
            <div class="acfw-tab-info">
                <h3><?php _e('Non-Qualifying Settings', 'advanced-coupons-for-woocommerce-free')?></h3>
                <p><?php _e("If the cart conditions rules are found to be false, optionally show a WooCommerce notification to the user.", 'advanced-coupons-for-woocommerce-free');?></p>
            </div>
            <div class="fields-wrap">
                <div class="condition-settings-field">
                    <label><?php _e('Non-qualifying message', 'advanced-coupons-for-woocommerce-free');?></label>
                    <textarea class="text-input non-qualifying-message-field" placeholder="<?php echo esc_attr($nqm_placeholder); ?>"><?php echo $notice_message; ?></textarea>
                </div>
                <div class="condition-settings-field">
                    <label><?php _e('Non-qualifying button text', 'advanced-coupons-for-woocommerce-free');?></label>
                    <input type="text" class="text-input non-qualifying-btn-text-field" value="<?php echo esc_attr($notice_btn_text); ?>">
                </div>
                <div class="condition-settings-field">
                    <label><?php _e('Non-qualifying button URL', 'advanced-coupons-for-woocommerce-free');?></label>
                    <input type="url" class="text-input non-qualifying-btn-url-field" value="<?php echo esc_url_raw($notice_btn_url); ?>">
                </div>
                <?php if ($is_premium_active): ?>
                    <div class="condition-settings-field">
                        <label><?php _e('Display notice when auto applied', 'advanced-coupons-for-woocommerce-free');?></label>
                        <input type="checkbox" class="display-notice-auto-apply-field" value="yes" <?php checked($coupon->get_advanced_prop_edit('cart_condition_display_notice_auto_apply'), 'yes');?>>
                    </div>
                <?php endif;?>
            </div>
        </div>

        <?php do_action('acfw_cart_condition_tabs_panels', $coupon);?>

        <div class="cart-conditions-main-actions">
            <button type="button" class="button-primary" id="save-cart-conditions" disabled><?php _e('Save Cart Conditions', 'advanced-coupons-for-woocommerce-free');?></button>
        </div>

        <div class="acfw-overlay" style="background-image:url(<?php echo esc_attr($spinner_img); ?>)"></div>

    </div>
    <div class="clear"></div>
</div>
