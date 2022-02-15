<?php if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (is_multisite()) {
    $license_settings_url = $current_url . "wp-admin/network/admin.php?page=acfw-ms-license-settings";
} else {
    $license_settings_url = admin_url() . 'admin.php?page=acfw-license';
}?>

<style type="text/css">
    .acfw-old-version-notice p {
        max-width: 1000px;
    }
    .acfw-old-version-notice p:after {
        content: '';
        display: table;
        clear: both;
    }
    .acfw-old-version-notice .heading img {
        float: left;
        margin-right: 15px;
        max-width: 190px;
    }
    .acfw-old-version-notice .heading span {
        float: left;
        display: inline-block;
        margin-top: 8px;
        font-size: 16px;
        font-weight: bold;
        text-transform: uppercase;
        color: #CB423B;
    }
    .acfw-old-version-notice .action-wrap .action-button {
        display: inline-block;
        padding: 8px 23px;
        margin-right: 10px;
        background: #C6CD2E;
        font-weight: bold;
        font-size: 16px;
        text-decoration: none;
        color: #000000;
    }
    .acfw-old-version-notice .action-wrap .action-button.disabled {
        opacity: 0.7 !important;
        pointer-events: none;
    }
    .acfw-old-version-notice .action-wrap .action-button.gray {
        background: #cccccc;
    }
    .acfw-old-version-notice .action-wrap .action-button:hover {
        opacity: 0.8;
    }

    .acfw-old-version-notice .action-wrap span {
        color: #035E6B;
    }
</style>
<div class="notice notice-error acfw-old-version-notice">
    <p class="heading">
        <img src="<?php echo $images_url . 'acfw-logo.png'; ?>">
        <span><?php _e('Action required', 'advanced-coupons-for-woocommerce-free');?></span>
    </p>
    <p><?php _e('Please update your copy of the Advanced Coupons premium plugin. The new features and changes added in the free version will not work properly with the old version of the premium plugin.', 'advanced-coupons-for-woocommerce-free');?></p>
    <?php if ($is_license_active && $license_key): ?>
        <p class="action-wrap">
            <?php if ($update_data): ?>
                <a class="action-button" href="<?php echo $update_url; ?>">
                    <?php _e('Update Plugin', 'advanced-coupons-for-woocommerce-free');?>
                </a>
                <span><?php _e('License detected. Update ready to proceed.', 'advanced-coupons-for-woocommerce-free');?></span>
            <?php else: ?>
                <a class="action-button disabled" href="#">
                    <?php _e('Update Plugin', 'advanced-coupons-for-woocommerce-free');?>
                </a>
                <span><?php _e('License detected, but update data was not yet fetched. Please try refreshing the page.', 'advanced-coupons-for-woocommerce-free');?></span>
            <?php endif;?>
        </p>
    <?php else: ?>
        <p><strong><?php _e('Please install a license so you can update, or deactivate this plugin.', 'advanced-coupons-for-woocommerce-free');?></strong></p>
        <p class="action-wrap">
            <a class="action-button" href="<?php echo $license_settings_url; ?>"><?php _e('Install License', 'advanced-coupons-for-woocommerce-free');?></a>
            <a class="action-button gray" href="<?php echo $deactivate_url; ?>"><?php _e('Deactivate', 'advanced-coupons-for-woocommerce-free');?></a>
            <span><?php _e('A valid license is required to update.', 'advanced-coupons-for-woocommerce-free');?></span>
        </p>
    <?php endif;?>

</div>