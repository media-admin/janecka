<?php
require_once __DIR__ .'/../libraries/XmlExportWooCommerce.php';
require_once __DIR__ .'/../libraries/XmlExportWooCommerceOrder.php';
require_once __DIR__ .'/../libraries/XmlExportWooCommerceCoupon.php';
require_once __DIR__ .'/../libraries/XmlExportWooCommerceReview.php';

function pmwe_pmxe_init_addons() {
    if(!\XmlExportEngine::$woo_export) {
        \XmlExportEngine::$woo_export = new XmlExportWooCommerce();
    }

    if(!\XmlExportEngine::$woo_order_export) {
        \XmlExportEngine::$woo_order_export = new XmlExportWooCommerceOrder();
    }

    if(!\XmlExportEngine::$woo_coupon_export) {
        \XmlExportEngine::$woo_coupon_export = new XmlExportWooCommerceCoupon();
    }

    if(property_exists('XmlExportEngine', 'woo_review_export')) {
        if (!\XmlExportEngine::$woo_review_export) {
            \XmlExportEngine::$woo_review_export = new XmlExportWooCommerceReview();
        }
    }
}