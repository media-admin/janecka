<?php
namespace ACFWF\Models;

use ACFWF\Abstracts\Abstract_Main_Plugin_Class;
use ACFWF\Helpers\Helper_Functions;
use ACFWF\Helpers\Plugin_Constants;
use ACFWF\Interfaces\Initializable_Interface;
use ACFWF\Interfaces\Model_Interface;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

/**
 * Model that houses the Admin_App module logic.
 * Public Model.
 *
 * @since 1.2
 */
class Admin_App implements Model_Interface, Initializable_Interface
{

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
     */

    /**
     * Property that holds the single main instance of URL_Coupon.
     *
     * @since 1.2
     * @access private
     * @var Cart_Conditions
     */
    private static $_instance;

    /**
     * Model that houses all the plugin constants.
     *
     * @since 1.2
     * @access private
     * @var Plugin_Constants
     */
    private $_constants;

    /**
     * Property that houses all the helper functions of the plugin.
     *
     * @since 1.2
     * @access private
     * @var Helper_Functions
     */
    private $_helper_functions;

    /**
     * Property that holds list of app pages.
     *
     * @since 1.2
     * @access private
     * @var string
     */
    private $_app_pages;

    /*
    |--------------------------------------------------------------------------
    | Class Methods
    |--------------------------------------------------------------------------
     */

    /**
     * Class constructor.
     *
     * @since 1.2
     * @access public
     *
     * @param Abstract_Main_Plugin_Class $main_plugin      Main plugin object.
     * @param Plugin_Constants           $constants        Plugin constants object.
     * @param Helper_Functions           $helper_functions Helper functions object.
     */
    public function __construct(Abstract_Main_Plugin_Class $main_plugin, Plugin_Constants $constants, Helper_Functions $helper_functions)
    {

        $this->_constants        = $constants;
        $this->_helper_functions = $helper_functions;

        $main_plugin->add_to_all_plugin_models($this);
        $main_plugin->add_to_public_models($this);

    }

    /**
     * Ensure that only one instance of this class is loaded or can be loaded ( Singleton Pattern ).
     *
     * @since 1.2
     * @access public
     *
     * @param Abstract_Main_Plugin_Class $main_plugin      Main plugin object.
     * @param Plugin_Constants           $constants        Plugin constants object.
     * @param Helper_Functions           $helper_functions Helper functions object.
     * @return Cart_Conditions
     */
    public static function get_instance(Abstract_Main_Plugin_Class $main_plugin, Plugin_Constants $constants, Helper_Functions $helper_functions)
    {

        if (!self::$_instance instanceof self) {
            self::$_instance = new self($main_plugin, $constants, $helper_functions);
        }

        return self::$_instance;

    }

    /*
    |--------------------------------------------------------------------------
    | Implementation.
    |--------------------------------------------------------------------------
     */

    /**
     * Register settings related submenus.
     *
     * @since 1.2
     * @access public
     *
     * @param string $toplevel_menu Top level menu slug.
     */
    public function register_submenus($toplevel_menu)
    {

        $show_app         = $this->_show_with_acfwp();
        $this->_app_pages = apply_filters('acfw_admin_app_pages', array(
            'acfw-store-credits' => array(
                'slug'  => 'acfw-store-credits',
                'label' => __('Store Credits', 'advanced-coupons-for-woocommerce-free'),
                'page'  => 'store_credits_page',
            ),
            'acfw-settings'      => array(
                'slug'  => $show_app ? 'acfw-settings' : 'wc-settings&tab=acfw_settings',
                'label' => __('Settings', 'advanced-coupons-for-woocommerce-free'),
                'page'  => 'settings_page',
            ),
            'acfw-license'       => array(
                'slug'  => $show_app ? 'acfw-license' : 'wc-settings&tab=acfw_settings&section=acfw_slmw_settings_section',
                'label' => __('License', 'advanced-coupons-for-woocommerce-free'),
                'page'  => 'license_page',
            ),
            'acfw-help'          => array(
                'slug'  => $show_app ? 'acfw-help' : 'wc-settings&tab=acfw_settings&section=acfw_setting_help_section',
                'label' => __('Help', 'advanced-coupons-for-woocommerce-free'),
                'page'  => 'help_page',
            ),
            'acfw-about'         => array(
                'slug'  => $show_app ? 'acfw-about' : false,
                'label' => __('About', 'advanced-coupons-for-woocommerce-free'),
                'page'  => 'about_page',
            ),
        ), $show_app);

        if (!$this->_helper_functions->is_module(Plugin_Constants::STORE_CREDITS_MODULE)) {
            unset($this->_app_pages['acfw-store-credits']);
        }

        foreach ($this->_app_pages as $key => $app_page) {

            if (!$app_page['slug']) {
                continue;
            }

            add_submenu_page(
                $toplevel_menu,
                $app_page['label'],
                $app_page['label'],
                'manage_woocommerce',
                $app_page['slug'],
                array($this, 'display_settings_app')
            );

            // don't proceed if we're not showing the app.
            if (!$show_app) {
                continue;
            }

            if ($this->_helper_functions->is_wc_admin_active() && function_exists('wc_admin_connect_page')) {

                wc_admin_connect_page(
                    array(
                        'id'        => $key,
                        'title'     => __('Advanced Coupons', 'advanced-coupons-for-woocommerce-free'),
                        'screen_id' => 'coupons_page_' . $key,
                        'path'      => 'admin.php?page=' . $key,
                        'js_page'   => false,
                    )
                );
            }

        }
    }

    /**
     * Display settings app.
     *
     * @since 1.2
     * @access public
     */
    public function display_settings_app()
    {
        echo '<div class="wrap">';
        echo '<hr class="wp-header-end">';
        echo '<div id="acfw_admin_app"></div>';

        do_action('acfw_admin_app');

        echo '</div>'; // end .wrap
    }

    /**
     * Enqueue settings react app styles and scripts.
     *
     * @since 1.2
     * @access public
     *
     * @param WP_Screen $screen    Current screen object.
     * @param string    $post_type Screen post type.
     */
    public function register_react_scripts($screen, $post_type)
    {

        // check if we need to show with ACFWP plugin when active.
        if (!$this->_show_with_acfwp()) {
            return;
        }

        // get the actual app page from screen id.
        $temp         = explode('_page_', $screen->id);
        $current_page = isset($temp[1]) ? $temp[1] : '';

        if (!is_array($this->_app_pages) || !in_array($current_page, array_keys($this->_app_pages))) {
            return;
        }

        // Important: Must enqueue this script in order to use WP REST API via JS
        wp_enqueue_script('wp-api');

        wp_localize_script('wp-api', 'acfwAdminApp', apply_filters('acfwf_admin_app_localized',
            array(
                'logo_alt'           => __('Advanced Coupons', 'advanced-coupons-for-woocommerce-free'),
                'admin_url'          => admin_url(),
                'title'              => __('Settings', 'advanced-coupons-for-woocommerce-free'),
                'desc'               => __('Adjust the global settings options for Advanced Coupons for WooCommerce.', 'advanced-coupons-for-woocommerce-free'),
                'logo'               => $this->_constants->IMAGES_ROOT_URL() . 'acfw-logo.png',
                'coupon_nav'         => array(
                    'toplevel' => __('Coupons', 'advanced-coupons-for-woocommerce-free'),
                    'links'    => array(
                        array(
                            'link' => admin_url('edit.php?post_type=shop_coupon'),
                            'text' => __('All Coupons', 'advanced-coupons-for-woocommerce-free'),
                        ),
                        array(
                            'link' => admin_url('post-new.php?post_type=shop_coupon'),
                            'text' => __('Add New', 'advanced-coupons-for-woocommerce-free'),
                        ),
                        array(
                            'link' => admin_url('edit-tags.php?taxonomy=shop_coupon_cat&post_type=shop_coupon'),
                            'text' => __('Coupon Categories', 'advanced-coupons-for-woocommerce-free'),
                        ),
                    ),
                ),
                'validation'         => array(
                    'default' => __('Please enter a valid value.', 'advanced-coupons-for-woocommerce-free'),
                ),
                'app_pages'          => array_values($this->_app_pages),
                'action_notices'     => array(
                    'success' => __('successfully updated', 'advanced-coupons-for-woocommerce-free'),
                    'fail'    => __('failed to update', 'advanced-coupons-for-woocommerce-free'),
                ),
                'premium_upsell'     => false,
                'license_page'       => array(
                    'title'              => __('Advanced Coupons License Activation', 'advanced-coupons-for-woocommerce-free'),
                    'desc'               => __('Advanced Coupons comes in two versions - the free version (with feature limitations) and the Premium add-on.', 'advanced-coupons-for-woocommerce-free'),
                    'feature_comparison' => array(
                        'link' => apply_filters('acfwp_upsell_link', 'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=licensefeaturecomparison'),
                        'text' => __('See feature comparison ', 'advanced-coupons-for-woocommerce-free'),
                    ),
                    'license_status'     => array(
                        'label' => __('Your current license for Advanced Coupons:', 'advanced-coupons-for-woocommerce-free'),
                        'link'  => apply_filters('acfwp_upsell_link', 'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=licenseupgradetopremium'),
                        'text'  => __('Upgrade To Premium', 'advanced-coupons-for-woocommerce-free'),
                    ),
                    'content'            => array(
                        'title' => __('Free Version', 'advanced-coupons-for-woocommerce-free'),
                        'text'  => __('You are currently using Advanced Coupons for WooCommerce Free on a GPL license. The free version includes a heap of great extra features for your WooCommerce coupons. The only requirement for the free version is that you have WooCommerce installed.', 'advanced-coupons-for-woocommerce-free'),
                    ),
                    'specs'              => array(
                        array(
                            'label' => __('Plan', 'advanced-coupons-for-woocommerce-free'),
                            'value' => __('Free Version', 'advanced-coupons-for-woocommerce-free'),
                        ),
                        array(
                            'label' => __('Version', 'advanced-coupons-for-woocommerce-free'),
                            'value' => Plugin_Constants::VERSION,
                        ),
                    ),
                ),
                'help_page'          => array(
                    'title' => __('Getting Help', 'advanced-coupons-for-woocommerce-free'),
                    'desc'  => __('We’re here to help you get the most out of Advanced Coupons for WooCommerce.', 'advanced-coupons-for-woocommerce-free'),
                    'cards' => array(
                        array(
                            'title'   => __('Knowledge Base', 'advanced-coupons-for-woocommerce-free'),
                            'content' => __('Access our self-service help documentation via the Knowledge Base. You’ll find answers and solutions for a wide range of well known situations. You’ll also find a Getting Started guide here for the plugin.', 'advanced-coupons-for-woocommerce-free'),
                            'action'  => array(
                                'link' => 'https://advancedcouponsplugin.com/knowledge-base/?utm_source=acfwf&utm_medium=helppage&utm_campaign=helpkbbutton',
                                'text' => __('Open Knowledge Base', 'advanced-coupons-for-woocommerce-free'),
                            ),
                        ),
                        array(
                            'title'   => __('Free Version WordPress.org Help Forums', 'advanced-coupons-for-woocommerce-free'),
                            'content' => __('Our support staff regularly check and help our free users at the official plugin WordPress.org help forums. Submit a post there with your question and we’ll get back to you as soon as possible.', 'advanced-coupons-for-woocommerce-free'),
                            'action'  => array(
                                'link' => 'https://wordpress.org/support/plugin/advanced-coupons-for-woocommerce-free/',
                                'text' => __('Visit WordPress.org Forums', 'advanced-coupons-for-woocommerce-free'),
                            ),
                        ),
                    ),
                ),
                'free_guide'         => array(
                    'tag'      => __('Recommended', 'advanced-coupons-for-woocommerce-free'),
                    'title'    => __('FREE GUIDE: How To Grow A WooCommerce Store Using Coupons', 'advanced-coupons-for-woocommerce-free'),
                    'subtitle' => __('The key to growing an online store is promoting it!', 'advanced-coupons-for-woocommerce-free'),
                    'content'  => __('If you’ve ever wanted to grow a store to 6, 7 or 8-figures and beyond <strong>download this guide</strong> now. You’ll learn how smart store owners are using coupons to grow their WooCommerce stores.', 'advanced-coupons-for-woocommerce-free'),
                    'image'    => $this->_constants->IMAGES_ROOT_URL() . 'coupons-free-guide.png',
                    'button'   => array(
                        'link'      => 'https://advancedcouponsplugin.com/how-to-grow-your-woocommerce-store-with-coupons/?utm_source=acfwf&utm_medium=settings&utm_campaign=helpfreeguidebutton',
                        'text'      => __('Get FREE Training Guide', 'advanced-coupons-for-woocommerce-free'),
                        'help_link' => 'https://advancedcouponsplugin.com/how-to-grow-your-woocommerce-store-with-coupons/?utm_source=acfwf&utm_medium=helppage&utm_campaign=helpfreeguidebutton',
                    ),
                    'list'     => array(
                        __('How "smart store owners" use coupons differently', 'advanced-coupons-for-woocommerce-free'),
                        __('3x hot deals that you can implement NOW to increase sales permanently', 'advanced-coupons-for-woocommerce-free'),
                        __('How to get 4-10x the sales on your next once-off coupon campaign', 'advanced-coupons-for-woocommerce-free'),
                        __('The tools you need to run these deals in your WooCommerce store', 'advanced-coupons-for-woocommerce-free'),
                    ),
                ),
                'about_page'         => array(
                    'title'        => __('About Advanced Coupons', 'advanced-coupons-for-woocommerce-free'),
                    'desc'         => __('Hello and welcome to Advanced Coupons, the plugin that makes your WooCommerce coupons better!', 'advanced-coupons-for-woocommerce-free'),
                    'main_card'    => array(
                        'title'   => __('About The Makers - Rymera Web Co', 'advanced-coupons-for-woocommerce-free'),
                        'content' => array(
                            __('Over the years we’ve worked with thousands of smart store owners that were  frustrated with the options for promoting their WooCommerce stores.', 'advanced-coupons-for-woocommerce-free'),
                            __('That’s why we decided to make Advanced Coupons - a state of the art coupon feature extension plugin that delivers on the promise of “making your store’s marketing better.”', 'advanced-coupons-for-woocommerce-free'),
                            __('Advanced Coupons is brought to you by the same team that’s behind the largest and most comprehensive wholesale plugin for WooCommerce, Wholesale Suite. We’ve also been in the WordPress space for over a decade.', 'advanced-coupons-for-woocommerce-free'),
                            __('We’re thrilled you’re using our tool and invite you to try our other tools as well.', 'advanced-coupons-for-woocommerce-free'),
                        ),
                        'image'   => $this->_constants->IMAGES_ROOT_URL() . 'rymera-team.jpg',
                    ),
                    'cards'        => array(
                        array(
                            'icon'    => $this->_constants->IMAGES_ROOT_URL() . 'acfw-icon.png',
                            'title'   => __('Advanced Coupons (Premium Version)', 'advanced-coupons-for-woocommerce-free'),
                            'content' => __('Premium adds even more great coupon features, unlocks all of the Cart Conditions, advanced BOGO functionality, lets you add products with a coupon, gives you auto-apply and one-click notifications and loads more.', 'advanced-coupons-for-woocommerce-free'),
                            'action'  => $this->_get_acfwp_action_link(),
                        ),
                        array(
                            'icon'    => $this->_constants->IMAGES_ROOT_URL() . 'wws-icon.png',
                            'title'   => __('WooCommerce Wholesale Prices', 'advanced-coupons-for-woocommerce-free'),
                            'content' => __('WooCommerce Wholesale Prices gives WooCommerce store owners the ability to supply specific users with wholesale pricing for their product range. We’ve made entering wholesale prices as simple as it should be.', 'advanced-coupons-for-woocommerce-free'),
                            'action'  => $this->_get_wwp_action_link(),
                        ),
                    ),
                    'status'       => __('Status', 'advanced-coupons-for-woocommerce-free'),
                    'status_texts' => array(
                        'not_installed' => __('Not installed', 'advanced-coupons-for-woocommerce-free'),
                        'installed'     => __('Installed', 'advanced-coupons-for-woocommerce-free'),
                        'active'        => __('Active', 'advanced-coupons-for-woocommerce-free'),
                    ),
                    'button_texts' => array(
                        'not_installed' => __('Install Plugin', 'advanced-coupons-for-woocommerce-free'),
                        'installed'     => __('Activate Plugin', 'advanced-coupons-for-woocommerce-free'),
                    ),
                ),
                'store_credits_page' => array(
                    'title'          => __('Store Credits Dashboard', 'advanced-coupons-for-woocommerce-free'),
                    'currency'       => array(
                        'decimal_separator'  => wc_get_price_decimal_separator(),
                        'thousand_separator' => wc_get_price_thousand_separator(),
                        'decimals'           => wc_get_price_decimals(),
                        'symbol'             => html_entity_decode(get_woocommerce_currency_symbol()),
                    ),
                    'tabs'           => array(
                        array(
                            'label' => __('Dashboard', 'advanced-coupons-for-woocommerce-free'),
                            'key'   => 'dashboard',
                        ),
                        array(
                            'label' => __('Customers', 'advanced-coupons-for-woocommerce-free'),
                            'key'   => 'customers',
                        ),
                    ),
                    'period_options' => array(
                        array(
                            'label' => __('Week to Date', 'advanced-coupons-for-woocommerce-free'),
                            'value' => 'week_to_date',
                        ),
                        array(
                            'label' => __('Month to Date', 'advanced-coupons-for-woocommerce-free'),
                            'value' => 'month_to_date',
                        ),
                        array(
                            'label' => __('Quarter to Date', 'advanced-coupons-for-woocommerce-free'),
                            'value' => 'quarter_to_date',
                        ),
                        array(
                            'label' => __('Year to Date', 'advanced-coupons-for-woocommerce-free'),
                            'value' => 'year_to_date',
                        ),
                        array(
                            'label' => __('Last Week', 'advanced-coupons-for-woocommerce-free'),
                            'value' => 'last_week',
                        ),
                        array(
                            'label' => __('Last Month', 'advanced-coupons-for-woocommerce-free'),
                            'value' => 'last_month',
                        ),
                        array(
                            'label' => __('Last Quarter', 'advanced-coupons-for-woocommerce-free'),
                            'value' => 'last_quarter',
                        ),
                        array(
                            'label' => __('Last Year', 'advanced-coupons-for-woocommerce-free'),
                            'value' => 'last_year',
                        ),
                        array(
                            'label' => __('Custom Range', 'advanced-coupons-for-woocommerce-free'),
                            'value' => 'custom',
                        ),
                    ),
                    'adjust_modal'   => array(
                        'title'           => __('Adjust Store Credit', 'advanced-coupons-for-woocommerce-free'),
                        'description'     => __('Adjust Store credit for this user. Remember store credits are worth the same as your base currency in the store.', 'advanced-coupons-for-woocommerce-free'),
                        'current_balance' => __('Current balance: {balance}', 'advanced-coupons-for-woocommerce-free'),
                        'new_balance'     => __('New balance: {balance}', 'advanced-coupons-for-woocommerce-free'),
                        'increase'        => __('Increase Store Credit', 'advanced-coupons-for-woocommerce-free'),
                        'decrease'        => __('Decrease Store Credit', 'advanced-coupons-for-woocommerce-free'),
                        'invalid_price'   => __('The price entered is not valid', 'advanced-coupons-for-woocommerce-free'),
                        'make_adjustment' => __('Make Adjustment', 'advanced-coupons-for-woocommerce-free'),
                    ),
                    'labels'         => array(
                        'status'         => __('Store Credits Status', 'advanced-coupons-for-woocommerce-free'),
                        'statistics'     => __('Store Credits Statistics', 'advanced-coupons-for-woocommerce-free'),
                        'sources'        => __('Store Credits Sources', 'advanced-coupons-for-woocommerce-free'),
                        'source'         => __('Source', 'advanced-coupons-for-woocommerce-free'),
                        'amount'         => sprintf(__('Amount (%s)', 'advanced-coupons-for-woocommerce-free'), html_entity_decode(get_woocommerce_currency_symbol())),
                        'customers_list' => __('Customers List', 'advanced-coupons-for-woocommerce-free'),
                        'search_label'   => __('Search by name or email', 'advanced-coupons-for-woocommerce-free'),
                        'customer_name'  => __('Customer Name', 'advanced-coupons-for-woocommerce-free'),
                        'email'          => __('Email', 'advanced-coupons-for-woocommerce-free'),
                        'balance'        => __('Store Credit Balance', 'advanced-coupons-for-woocommerce-free'),
                        'view_stats'     => __('View Stats', 'advanced-coupons-for-woocommerce-free'),
                        'adjust'         => __('Adjust', 'advanced-coupons-for-woocommerce-free'),
                        'history'        => __('Store Credit History', 'advanced-coupons-for-woocommerce-free'),
                        'date'           => __('Date', 'advanced-coupons-for-woocommerce-free'),
                        'activity'       => __('Activity', 'advanced-coupons-for-woocommerce-free'),
                        'related'        => __('Related', 'advanced-coupons-for-woocommerce-free'),
                    ),
                ),
            )
        ));

        wp_localize_script('wp-api', 'acfwpElements', array(
            'is_acfwp_active' => (int) $this->_helper_functions->is_plugin_active(Plugin_Constants::PREMIUM_PLUGIN),
            'is_lpfw_active'  => (int) $this->_helper_functions->is_plugin_active(Plugin_Constants::LOYALTY_PLUGIN),
        ));

        do_action('acfw_admin_app_enqueue_scripts_before', $screen, $post_type, false); // last parameter deprecated.

        wp_enqueue_script('acfw-axios', $this->_constants->JS_ROOT_URL() . '/lib/axios/axios.min.js', array(), Plugin_Constants::VERSION, true);

        if (defined('ACFW_ADMIN_APP_URL') && ACFW_ADMIN_APP_URL) {

            wp_enqueue_script('acfwp-edit-coupon-app-bundle', ACFW_ADMIN_APP_URL . '/static/js/bundle.js', array('wp-api'), Plugin_Constants::VERSION, true);
            wp_enqueue_script('acfwp-edit-coupon-app-vendor', ACFW_ADMIN_APP_URL . '/static/js/vendors~main.chunk.js', array('wp-api'), Plugin_Constants::VERSION, true);
            wp_enqueue_script('acfwp-edit-coupon-app-main', ACFW_ADMIN_APP_URL . '/static/js/main.chunk.js', array('wp-api'), Plugin_Constants::VERSION, true);

        } else {

            $app_js_path  = $this->_constants->JS_ROOT_PATH() . '/app/admin-app/build/static/js/';
            $app_css_path = $this->_constants->JS_ROOT_PATH() . '/app/admin-app/build/static/css/';
            $app_js_url   = $this->_constants->JS_ROOT_URL() . 'app/admin-app/build/static/js/';
            $app_css_url  = $this->_constants->JS_ROOT_URL() . 'app/admin-app/build/static/css/';

            if (\file_exists($app_js_path)) {
                if ($js_files = \scandir($app_js_path)) {
                    foreach ($js_files as $key => $js_file) {
                        if (strpos($js_file, '.js') !== false && strpos($js_file, '.js.map') === false && strpos($js_file, '.js.LICENSE.txt') === false) {
                            $handle = Plugin_Constants::TOKEN . $key;
                            wp_enqueue_script($handle, $app_js_url . $js_file, array('wp-api', 'acfw-axios'), Plugin_Constants::VERSION, true);
                        }
                    }
                }
            }

            if (\file_exists($app_css_path)) {
                if ($css_files = \scandir($app_css_path)) {
                    foreach ($css_files as $key => $css_file) {
                        if (strpos($css_file, '.css') !== false && strpos($css_file, '.css.map') === false) {
                            wp_enqueue_style(Plugin_Constants::TOKEN . $key, $app_css_url . $css_file, array(), Plugin_Constants::VERSION, 'all');
                        }
                    }
                }
            }

        }

        do_action('acfw_admin_app_enqueue_scripts_after', $screen, $post_type, false); // last parameter deprecated.
    }

    /**
     * Hide ACFW Settings in WC settings page if we can show the app.
     *
     * @since 1.2
     * @access public
     *
     * @return boolean True if hide, false otherwise.
     */
    public function hide_acfw_settings_in_wc()
    {
        return $this->_show_with_acfwp();
    }

    /*
    |--------------------------------------------------------------------------
    | Utility methods
    |--------------------------------------------------------------------------
     */

    /**
     * Check if we can show new settings React JS app with ACFWP installed.
     *
     * @since 1.2
     * @access private
     *
     * @return boolean True if show, false otherwise.
     */
    private function _show_with_acfwp()
    {

        if (!$this->_helper_functions->is_plugin_active(Plugin_Constants::PREMIUM_PLUGIN)) {
            return true;
        }

        return version_compare(ACFWP()->Plugin_Constants->VERSION, '2.2', '>=');
    }

    /**
     * Get ACFWP action link.
     *
     * @since 1.2
     * @access private
     *
     * @return string ACFWP action link.
     */
    private function _get_acfwp_action_link()
    {

        if ($this->_helper_functions->is_plugin_active(Plugin_Constants::PREMIUM_PLUGIN)) {
            return array('status' => 'active', 'link' => '');
        }

        if ($this->_helper_functions->is_plugin_installed(Plugin_Constants::PREMIUM_PLUGIN)) {
            $basename = plugin_basename(Plugin_Constants::PREMIUM_PLUGIN);
            return array(
                'status'   => 'installed',
                'link'     => htmlspecialchars_decode(wp_nonce_url('plugins.php?action=activate&amp;plugin=' . $basename . '&amp;plugin_status=all&amp;s', 'activate-plugin_' . $basename)),
                'external' => false,
            );
        }

        return array(
            'status'   => 'not_installed',
            'link'     => apply_filters('acfwp_upsell_link', 'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=aboutpageupgradebutton'),
            'external' => true,
        );
    }

    /**
     * Get ACFWP action link.
     *
     * @since 1.2
     * @access private
     *
     * @return string ACFWP action link.
     */
    private function _get_wwp_action_link()
    {

        $basename = plugin_basename('woocommerce-wholesale-prices/woocommerce-wholesale-prices.bootstrap.php');

        if ($this->_helper_functions->is_plugin_active($basename)) {
            return array('status' => 'active', 'link' => '', 'external' => false);
        }

        if ($this->_helper_functions->is_plugin_installed($basename)) {
            return array(
                'status'   => 'installed',
                'link'     => htmlspecialchars_decode(wp_nonce_url('plugins.php?action=activate&amp;plugin=' . $basename . '&amp;plugin_status=all&amp;s', 'activate-plugin_' . $basename)),
                'external' => false,
            );
        }

        $plugin_key = 'woocommerce-wholesale-prices';

        return array(
            'status'   => 'not_installed',
            'link'     => htmlspecialchars_decode(wp_nonce_url('update.php?action=install-plugin&amp;plugin=' . $plugin_key, 'install-plugin_' . $plugin_key)),
            'external' => false,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Fulfill implemented interface contracts
    |--------------------------------------------------------------------------
     */

    /**
     * Execute codes that needs to run plugin activation.
     *
     * @since 1.2
     * @access public
     * @implements ACFWF\Interfaces\Initializable_Interface
     */
    public function initialize()
    {

    }

    /**
     * Execute Admin_App class.
     *
     * @since 1.2
     * @access public
     * @inherit ACFWF\Interfaces\Model_Interface
     */
    public function run()
    {

        add_action('acfw_register_admin_submenus', array($this, 'register_submenus'));
        add_action('acfw_after_load_backend_scripts', array($this, 'register_react_scripts'), 10, 2);
        add_filter('acfw_hide_wc_settings_tab', array($this, 'hide_acfw_settings_in_wc'));
    }

}
