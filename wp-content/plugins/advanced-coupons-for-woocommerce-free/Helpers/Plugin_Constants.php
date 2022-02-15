<?php

namespace ACFWF\Helpers;

use ACFWF\Abstracts\Abstract_Main_Plugin_Class;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

/**
 * Model that houses all the plugin constants.
 * Note as much as possible, we need to make this class succinct as the only purpose of this is to house all the constants that is utilized by the plugin.
 * Therefore we omit class member comments and minimize comments as much as possible.
 * In fact the only verbouse comment here is this comment you are reading right now.
 * And guess what, it just got worse coz now this comment takes 5 lines instead of 3.
 *
 * @since 1.0
 */
class Plugin_Constants
{

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
     */

    private static $_instance;

    // Plugin configuration constants
    const TOKEN               = 'acfwf';
    const INSTALLED_VERSION   = 'acfwf_installed_version';
    const VERSION             = '3.1.2';
    const TEXT_DOMAIN         = 'advanced-coupons-for-woocommerce-free';
    const THEME_TEMPLATE_PATH = 'advanced-coupons-for-woocommerce-free';
    const META_PREFIX         = '_acfw_';
    const PREMIUM_PLUGIN      = 'advanced-coupons-for-woocommerce/advanced-coupons-for-woocommerce.php';
    const LOYALTY_PLUGIN      = 'loyalty-program-for-woocommerce/loyalty-program-for-woocommerce.php';

    // Notices
    const SHOW_GETTING_STARTED_NOTICE = 'acfwf_show_getting_started_notice';
    const UPRADE_NOTICE_CRON          = 'acfwf_upgrade_to_premium_cron';
    const SHOW_UPGRADE_NOTICE         = 'acfwf_show_upgrade_to_premium_notice';
    const PROMOTE_WWS_NOTICE_CRON     = 'acfwf_promote_wws_notice_cron';
    const SHOW_PROMOTE_WWS_NOTICE     = 'acfwf_show_promote_wws_notice';
    const SHOW_REVIEW_REQUEST_NOTICE  = 'acfwf_show_review_request_notice';
    const NOTICES_CRON                = 'acfwf_notices_cron';

    // WC Admin
    const REGISTER_WC_ADMIN_NOTE = 'acfwf_register_wc_admin_note';
    const DISMISS_WC_ADMIN_NOTE  = 'acfwf_dismiss_wc_admin_note';

    // Coupon Meta Constants
    const COUPON_USER_ROLES_RESTRICTION   = 'acfw_coupon_user_roles_restriction';
    const DISABLE_COUPON_URL              = 'acfw_disable_coupon_url';
    const COUPON_URL                      = 'acfw_coupon_url';
    const COUPON_CODE_URL_OVERRIDE        = 'acfw_coupon_code_url_override';
    const CUSTOM_SUCCESS_MESSAGE          = 'acfw_custom_success_message';
    const ADD_FREE_PRODUCT_ON_COUPON_USE  = 'acfw_add_free_product_on_coupon_use';
    const AFTER_APPLY_COUPON_REDIRECT_URL = 'ucfw_after_apply_coupon_redirect_url';

    // Coupon Categories Constants
    const COUPON_CAT_TAXONOMY       = 'shop_coupon_cat';
    const DEFAULT_COUPON_CATEGORY   = 'acfw_default_coupon_category';
    const DEFAULT_REDEEM_COUPON_CAT = 'acfw_default_redeemed_coupon_category';

    // Order Meta
    const ORDER_BOGO_DISCOUNTS = 'acfw_order_bogo_discounts';

    // REST API
    const REST_API_NAMESPACE = 'coupons/v1';

    // Settings Constants

    // General Section

    // Modules section

    const URL_COUPONS_MODULE        = 'acfw_url_coupons_module';
    const SCHEDULER_MODULE          = 'acfw_scheduler_module';
    const ROLE_RESTRICT_MODULE      = 'acfw_role_restrict_module';
    const CART_CONDITIONS_MODULE    = 'acfw_cart_conditions_module';
    const BOGO_DEALS_MODULE         = 'acfw_bogo_deals_module';
    const ADD_PRODUCTS_MODULE       = 'acfw_add_free_products_module'; // we don't change the actual meta name for backwards compatibility.
    const AUTO_APPLY_MODULE         = 'acfw_auto_apply_module';
    const APPLY_NOTIFICATION_MODULE = 'acfw_apply_notification_module';
    const SHIPPING_OVERRIDES_MODULE = 'acfw_shipping_overrides_module';
    const USAGE_LIMITS_MODULE       = 'acfw_advanced_usage_limits_module';
    const LOYALTY_PROGRAM_MODULE    = 'acfw_loyalty_program_module';
    const SORT_COUPONS_MODULE       = 'acfw_sort_coupons_module';
    const PAYMENT_METHODS_RESTRICT  = 'acfw_payment_methods_restrict_module';

    // URL Coupons section

    const COUPON_ENDPOINT                        = 'acfw_coupon_endpoint';
    const AFTER_APPLY_COUPON_REDIRECT_URL_GLOBAL = 'acfw_after_apply_coupon_redirect_url_global';
    const INVALID_COUPON_REDIRECT_URL            = 'acfw_invalid_coupon_redirect_url';
    const HIDE_COUPON_UI_ON_CART_AND_CHECKOUT    = 'acfw_hide_coupon_ui_on_cart_and_checkout';
    const CUSTOM_SUCCESS_MESSAGE_GLOBAL          = 'acfw_custom_success_message_global';
    const CUSTOM_DISABLE_MESSAGE                 = 'acfw_custom_disable_message';

    // Scheduler section
    const SCHEDULER_START_ERROR_MESSAGE  = 'acfw_scheduler_start_error_message';
    const SCHEDULER_EXPIRE_ERROR_MESSAGE = 'acfw_scheduler_expire_error_message';

    // Role restrictions section
    const ROLE_RESTRICTIONS_ERROR_MESSAGE = 'acfw_role_restrictions_error_message';

    // BOGO Deals section
    const ADD_AS_DEAL_BTN_TEXT       = 'acfw_add_as_deal_button_text';
    const ADD_AS_DEAL_BTN_ALT        = 'acfw_add_as_deal_button_alt';
    const HIDE_ADD_TO_CART_WHEN_DEAL = 'acfw_hide_add_to_cart_when_product_is_deal';
    const BOGO_DEALS_NOTICE_MESSAGE  = 'acfw_bogo_deals_notice_message';
    const BOGO_DEALS_NOTICE_BTN_TEXT = 'acfw_bogo_deals_notice_button_text';
    const BOGO_DEALS_NOTICE_BTN_URL  = 'acfw_bogo_deals_notice_button_url';
    const BOGO_DEALS_NOTICE_TYPE     = 'acfw_bogo_deals_notice_type';
    const BOGO_DEALS_DEFAULT_VALUES  = 'acfw_bogo_deals_default_values_set';
    const BOGO_SELECT_DEALS_PAGE     = 'acfw_bogo_create_select_deals_page';

    // Advance Usage Limits
    const USAGE_LIMITS_CRON = 'acfw_advanced_usage_limits_cron';

    // Cache options
    const AUTO_APPLY_COUPONS       = 'acfw_auto_apply_coupons';
    const APPLY_NOTIFICATION_CACHE = 'acfw_apply_notifcation_cache';

    // Help Section
    const CLEAN_UP_PLUGIN_OPTIONS = 'acfw_clean_up_plugin_options';

    // Reports
    const ACFW_REPORTS_TAB = 'acfw_reports';

    // Marketing
    const WWP_PLUGIN_BASENAME  = 'woocommerce-wholesale-prices/woocommerce-wholesale-prices.bootstrap.php';
    const WWPP_PLUGIN_BASENAME = 'woocommerce-wholesale-prices-premium/woocommerce-wholesale-prices-premium.bootstrap.php';
    const WWLC_PLUGIN_BASENAME = 'woocommerce-wholesale-lead-capture/woocommerce-wholesale-lead-capture.bootstrap.php';
    const WWOF_PLUGIN_BASENAME = 'woocommerce-wholesale-order-form/woocommerce-wholesale-order-form.bootstrap.php';

    // Permissions
    const ALLOW_FETCH_CONTENT_REMOTE = 'acfw_allow_fetch_content_remote_server';

    /*
    |--------------------------------------------------------------------------
    | Class Methods
    |--------------------------------------------------------------------------
     */

    public function __construct(Abstract_Main_Plugin_Class $main_plugin)
    {

        // Path constants
        $this->_MAIN_PLUGIN_FILE_PATH = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'advanced-coupons-for-woocommerce-free' . DIRECTORY_SEPARATOR . 'advanced-coupons-for-woocommerce-free.php';
        $this->_PLUGIN_DIR_PATH       = plugin_dir_path($this->_MAIN_PLUGIN_FILE_PATH);
        $this->_PLUGIN_DIR_URL        = plugin_dir_url($this->_MAIN_PLUGIN_FILE_PATH);
        $this->_PLUGIN_DIRNAME        = plugin_basename(dirname($this->_MAIN_PLUGIN_FILE_PATH));
        $this->_PLUGIN_BASENAME       = plugin_basename($this->_MAIN_PLUGIN_FILE_PATH);

        $this->_CSS_ROOT_URL    = $this->_PLUGIN_DIR_URL . 'css/';
        $this->_IMAGES_ROOT_URL = $this->_PLUGIN_DIR_URL . 'images/';
        $this->_JS_ROOT_URL     = $this->_PLUGIN_DIR_URL . 'js/';

        $this->_JS_ROOT_PATH        = $this->_PLUGIN_DIR_PATH . 'js/';
        $this->_VIEWS_ROOT_PATH     = $this->_PLUGIN_DIR_PATH . 'views/';
        $this->_TEMPLATES_ROOT_PATH = $this->_PLUGIN_DIR_PATH . 'templates/';
        $this->_LOGS_ROOT_PATH      = $this->_PLUGIN_DIR_PATH . 'logs/';

        $this->_THIRD_PARTY_PATH = $this->_PLUGIN_DIR_PATH . 'Models/Third_Party_Integrations/';
        $this->_THIRD_PARTY_URL  = $this->_PLUGIN_DIR_URL . 'Models/Third_Party_Integrations/';

        $this->_PREMIUM_PLUGIN_BASENAME = 'advanced-coupons-for-woocommerce' . DIRECTORY_SEPARATOR . 'advanced-coupons-for-woocommerce.php';

        $main_plugin->add_to_public_helpers($this);
    }

    public static function get_instance(Abstract_Main_Plugin_Class $main_plugin)
    {

        if (!self::$_instance instanceof self) {
            self::$_instance = new self($main_plugin);
        }

        return self::$_instance;
    }

    public function MAIN_PLUGIN_FILE_PATH()
    {
        return $this->_MAIN_PLUGIN_FILE_PATH;
    }

    public function PLUGIN_DIR_PATH()
    {
        return $this->_PLUGIN_DIR_PATH;
    }

    public function PLUGIN_DIR_URL()
    {
        return $this->_PLUGIN_DIR_URL;
    }

    public function PLUGIN_DIRNAME()
    {
        return $this->_PLUGIN_DIRNAME;
    }

    public function PLUGIN_BASENAME()
    {
        return $this->_PLUGIN_BASENAME;
    }

    public function CSS_ROOT_URL()
    {
        return $this->_CSS_ROOT_URL;
    }

    public function IMAGES_ROOT_URL()
    {
        return $this->_IMAGES_ROOT_URL;
    }

    public function JS_ROOT_URL()
    {
        return $this->_JS_ROOT_URL;
    }

    public function JS_ROOT_PATH()
    {
        return $this->_JS_ROOT_PATH;
    }

    public function VIEWS_ROOT_PATH()
    {
        return $this->_VIEWS_ROOT_PATH;
    }

    public function TEMPLATES_ROOT_PATH()
    {
        return $this->_TEMPLATES_ROOT_PATH;
    }

    public function LOGS_ROOT_PATH()
    {
        return $this->_LOGS_ROOT_PATH;
    }

    public function THIRD_PARTY_PATH()
    {
        return $this->_THIRD_PARTY_PATH;
    }

    public function THIRD_PARTY_URL()
    {
        return $this->_THIRD_PARTY_URL;
    }

    public function PREMIUM_PLUGIN_BASENAME()
    {
        return $this->_PREMIUM_PLUGIN_BASENAME;
    }

    public static function ALL_MODULES()
    {

        return array(
            self::URL_COUPONS_MODULE,
            self::ROLE_RESTRICT_MODULE,
            self::CART_CONDITIONS_MODULE,
            self::BOGO_DEALS_MODULE,
        );
    }

    public static function DEFAULT_MODULES()
    {

        return array(
            self::URL_COUPONS_MODULE,
            self::ROLE_RESTRICT_MODULE,
            self::CART_CONDITIONS_MODULE,
            self::BOGO_DEALS_MODULE,
        );
    }
}
