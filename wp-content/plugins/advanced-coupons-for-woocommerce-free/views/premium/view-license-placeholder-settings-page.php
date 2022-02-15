<?php if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<div id="license-placeholder" class="acfwf-license-placeholder-settings-block">

    <div class="overview">
        <h1><?php _e( 'Advanced Coupons License Activation' , 'advanced-coupons-for-woocommerce-free' ); ?></h1>
        <p><?php _e( 'Advanced Coupons comes in two versions - the free version (with feature limitations) and the Premium add-on.' , 'advanced-coupons-for-woocommerce-free' ); ?></p>
        <a class="action-button feature-comparison" href="<?php echo apply_filters( 'acfwp_upsell_link' , 'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=licensefeaturecomparison' ); ?>" target="_blank">
            <?php _e( 'See feature comparison ' , 'advanced-coupons-for-woocommerce-free' ); ?>
        </a>
    </div>

    <div class="license-info">

        <div class="heading">
            <div class="left">
                <span><?php _e( 'Your current license for Advanced Coupons:' , 'advanced-coupons-for-woocommerce-free' ); ?></span>
            </div>
            <div class="right">
                <a class="action-button upgrade-premium" href="<?php apply_filters( 'acfwp_upsell_link' , 'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=licenseupgradetopremium' ); ?>" target="_blank">
                    <?php _e( 'Upgrade To Premium' , 'advanced-coupons-for-woocommerce-free' ); ?>
                </a>
            </div>
        </div>

        <div class="content">

            <h2><?php _e( 'Free Version' , 'advanced-coupons-for-woocommerce-free' ); ?></h2>
            <p><?php _e( 'You are currently using Advanced Coupons for WooCommerce Free on a GPL license. The free version includes a heap of great extra features for your WooCommerce coupons. The only requirement for the free version is that you have WooCommerce installed.' , 'advanced-coupons-for-woocommerce-free' ); ?></p>

            <table class="license-specs">
                <tr>
                    <th><?php _e( 'Plan' , 'advanced-coupons-for-woocommerce-free' ); ?></th>
                    <th><?php _e( 'Version' , 'advanced-coupons-for-woocommerce-free' ); ?></th>
                </tr>
                <tr>
                    <td><?php _e( 'Free Version' , 'advanced-coupons-for-woocommerce-free' ); ?></td>
                    <td><?php echo $plugin_version; ?></td>
                </tr>
            </table>
        </div>
    </div>

</div>