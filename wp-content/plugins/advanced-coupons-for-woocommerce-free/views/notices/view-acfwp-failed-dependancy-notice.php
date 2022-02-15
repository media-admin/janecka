<?php if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly ?>

<style type="text/css">
    .acfwf-missing-dependencies-notice p {
        max-width: 1000px;
    }
    .acfwf-missing-dependencies-notice p:after {
        content: '';
        display: table;
        clear: both;
    }
    .acfwf-missing-dependencies-notice .heading img {
        float: left;
        margin-right: 15px;
        max-width: 190px;
    }
    .acfwf-missing-dependencies-notice .heading span {
        float: left;
        display: inline-block;
        margin-top: 8px;
        font-size: 16px;
        font-weight: bold;
        text-transform: uppercase;
        color: #CB423B;
    }
    .acfwf-missing-dependencies-notice .action-wrap {
        margin-bottom: 15px;
    }
    .acfwf-missing-dependencies-notice .action-wrap .action-button {
        display: inline-block;
        padding: 8px 23px;
        margin-right: 10px;
        background: #C6CD2E;
        font-weight: bold;
        font-size: 16px;
        text-decoration: none;
        color: #000000;
    }
    .acfwf-missing-dependencies-notice .action-wrap .action-button.disabled {
        opacity: 0.7 !important;
        pointer-events: none;
    }
    .acfwf-missing-dependencies-notice .action-wrap .action-button.gray {
        background: #cccccc;
    }
    .acfwf-missing-dependencies-notice .action-wrap .action-button:hover {
        opacity: 0.8;
    }

    .acfwf-missing-dependencies-notice .action-wrap span {
        color: #035E6B;
    }
</style>
<div class="notice notice-error acfwf-missing-dependencies-notice">
    <p class="heading">
        <img src="<?php echo $acfw_logo; ?>">
        <span><?php _e('Action required', 'advanced-coupons-for-woocommerce-free');?></span>
    </p>
    <p><?php _e('<b>Advanced Coupons for WooCommerce Free</b> plugin missing dependency.<br/>', 'advanced-coupons-for-woocommerce-free');?></p>
    <?php echo $admin_notice_msg; ?>
</div>