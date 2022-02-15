<?php if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<div class="<?php echo $notice_class; ?> acfw-admin-notice acfwf-getting-started-notice notice-success is-dismissable" data-notice="getting_started">
    <p class="heading">
        <img src="<?php echo $acfw_logo; ?>">
        <span><?php _e( 'IMPORTANT INFORMATION' , 'advanced-coupons-for-woocommerce-free' ); ?></span>
    </p>
    <p><?php _e( 'Thank you for choosing Advanced Coupons for WooCommerce – the free Advanced Coupons plugin gives WooCommerce store owners extra features on their WooCommerce coupons so they can market their stores better.' , 'advanced-coupons-for-woocommerce-free' ); ?></p>
    <p><?php _e( 'Would you like to find out how to drive it?' , 'advanced-coupons-for-woocommerce-free' ); ?>
    <p class="action-wrap">
        <a class="action-button" href="https://advancedcouponsplugin.com/knowledgebase/advanced-coupon-for-woocommerce-free-getting-started-guide/?utm_source=acfwf&utm_medium=kb&utm_campaign=acfwfgettingstarted" target="_blank">
            <?php _e( 'Read The Getting Started Guide &rarr;' , 'advanced-coupons-for-woocommerce-free' ); ?>
        </a>
        <a class="acfw-notice-dismiss" href="javascript:void(0);"><?php _e( 'Dismiss' , 'advanced-coupons-for-woocommerce-free' ); ?></a>
    </p>
    <button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php _e( 'Dismiss this notice..' , 'advanced-coupons-for-woocommerce-free' ); ?></span></button>
</div>



