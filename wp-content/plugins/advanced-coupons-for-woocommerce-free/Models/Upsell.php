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
 * Model that houses the Upsell module logic.
 * Public Model.
 *
 * @since 1.0
 */
class Upsell implements Model_Interface, Initializable_Interface
{

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
     */

    /**
     * Property that holds the single main instance of URL_Coupon.
     *
     * @since 1.0
     * @access private
     * @var Cart_Conditions
     */
    private static $_instance;

    /**
     * Model that houses all the plugin constants.
     *
     * @since 1.0
     * @access private
     * @var Plugin_Constants
     */
    private $_constants;

    /**
     * Property that houses all the helper functions of the plugin.
     *
     * @since 1.0
     * @access private
     * @var Helper_Functions
     */
    private $_helper_functions;

    /*
    |--------------------------------------------------------------------------
    | Class Methods
    |--------------------------------------------------------------------------
     */

    /**
     * Class constructor.
     *
     * @since 1.0
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
     * @since 1.0
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
    | Side metabox.
    |--------------------------------------------------------------------------
     */

    /**
     * Register upsell metabox.
     *
     * @since 1.0
     * @since 1.1 Add auto apply metabox upsell.
     * @access public
     *
     * @param string  $post_type Post type.
     * @param WP_Post $post      Post object.
     */
    public function register_upsell_metabox($post_type, $post)
    {
        if ('shop_coupon' !== $post_type) {
            return;
        }

        add_meta_box(
            'acfw-auto-apply-coupon',
            __('Auto Apply Coupon (premium)', 'advanced-coupons-for-woocommerce-free'),
            array($this, 'display_auto_apply_upsell_metabox'),
            'shop_coupon',
            'side'
        );

        add_meta_box(
            'acfw-premium-upsell',
            __('Upgrade to premium', 'advanced-coupons-for-woocommerce-free'),
            array($this, 'display_upsell_metabox'),
            'shop_coupon',
            'side',
            'low'
        );
    }

    /**
     * Display upsell metabox content.
     *
     * @since 1.0
     * @access public
     *
     * @param int $coupon_id WC_Coupon ID.
     */
    public function display_upsell_metabox($post)
    {
        $link = apply_filters('acfwp_upsell_link', 'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=sidebar');
        echo '<a href="' . $link . '" target="_blank">
        <img style="margin-left: -12px;" src="' . $this->_constants->IMAGES_ROOT_URL() . '/premium-add-on-sidebar.png" alt="Advanced Coupons Premium" />
        </a>';

    }

    /**
     * Display  auto apply upsell metabox content.
     *
     * @since 1.0
     * @access public
     *
     * @param int $coupon_id WC_Coupon ID.
     */
    public function display_auto_apply_upsell_metabox($post)
    {
        echo '<label>
            <input id="acfw_auto_apply_coupon_field" type="checkbox" value="yes">
            ' . __('Enable auto apply for this coupon.', 'advanced-coupons-for-woocommerce-free') . '
        </label>';
    }

    /*
    |--------------------------------------------------------------------------
    | WooCommerce coupons metabox panels.
    |--------------------------------------------------------------------------
     */

    /**
     * Register upsell panels in WooCommerce coupons metabox.
     *
     * @since 1.0
     * @access public
     *
     * @param array $coupon_data_tabs Array of coupon admin data tabs.
     * @return array Modified array of coupon admin data tabs.
     */
    public function register_upsell_panels($panels)
    {

        $is_role_restrictions = isset($panels['acfw_role_restrictions']);
        $filtered_panels      = array();
        $upsell_panels        = array(
            'acfw_add_products'                => array(
                'label'  => __('Add Products (Premium)', 'advanced-coupons-for-woocommerce-free'),
                'target' => 'acfw_add_products',
                'class'  => '',
            ),
            'acfw_scheduler'                   => array(
                'label'  => __('Scheduler (Premium)', 'advanced-coupons-for-woocommerce-free'),
                'target' => 'acfw_scheduler',
                'class'  => '',
            ),
            'acfw_payment_method_restrictions' => array(
                'label'  => __('Payment Methods Restriction (Premium)', 'advanced-coupons-for-woocommerce-free'),
                'target' => 'acfw_payment_methods_restriction',
                'class'  => '',
            ),
            'acfw_shipping_overrides'          => array(
                'label'  => __('Shipping Overrides (Premium)', 'advanced-coupons-for-woocommerce-free'),
                'target' => 'acfw_shipping_overrides',
                'class'  => '',
            ),
            'acfw_apply_notification'          => array(
                'label'  => __('One Click Apply (Premium)', 'advanced-coupons-for-woocommerce-free'),
                'target' => 'acfw_apply_notification',
                'class'  => '',
            ),
        );

        // try to add panels on optimal locations.
        foreach ($panels as $key => $panel) {

            $filtered_panels[$key] = $panel;

            // add panels after BOGO Deals
            if ('usage_limit' === $key) {
                $filtered_panels['acfw_add_products'] = $upsell_panels['acfw_add_products'];
                $filtered_panels['acfw_scheduler']    = $upsell_panels['acfw_scheduler'];

                if (!$is_role_restrictions) {
                    $filtered_panels['acfw_payment_method_restrictions'] = $upsell_panels['acfw_payment_method_restrictions'];
                }
            }

            // add panels after Role Restrictions
            if ($is_role_restrictions && 'acfw_role_restrictions' === $key) {
                $filtered_panels['acfw_payment_method_restrictions'] = $upsell_panels['acfw_payment_method_restrictions'];
            }
        }

        // add all other panels, and ones that' weren't added due to the set previous module for it is inactive.
        foreach ($upsell_panels as $key => $panel) {
            if (!isset($filtered_panels[$key])) {
                $filtered_panels[$key] = $panel;
            }
        }

        return $filtered_panels;
    }

    /**
     * Display upsell panel views.
     *
     * @since 1.0
     * @access public
     *
     * @param int $coupon_id WC_Coupon ID.
     */
    public function display_upsell_panel_views($coupon_id)
    {
        include $this->_constants->VIEWS_ROOT_PATH() . 'premium' . DIRECTORY_SEPARATOR . 'view-add-products-panel.php';
        include $this->_constants->VIEWS_ROOT_PATH() . 'premium' . DIRECTORY_SEPARATOR . 'view-payment-methods-restriction-panel.php';
        include $this->_constants->VIEWS_ROOT_PATH() . 'premium' . DIRECTORY_SEPARATOR . 'view-scheduler-panel.php';
        include $this->_constants->VIEWS_ROOT_PATH() . 'premium' . DIRECTORY_SEPARATOR . 'view-apply-notifications-panel.php';
        include $this->_constants->VIEWS_ROOT_PATH() . 'premium' . DIRECTORY_SEPARATOR . 'view-shipping-overrides-panel.php';
    }

    /**
     * Display did you know notice under general tab in coupon editor.
     *
     * @since 1.6
     * @access public
     *
     * @param int $coupon_id Coupon ID.
     */
    public function display_did_you_know_notice_in_general($coupon_id)
    {
        \ACFWF()->Notices->display_did_you_know_notice(array(
            'classname'   => 'acfw-dyk-notice-general',
            'description' => __('You can unlock even more advanced coupon types & features.', 'advanced-coupons-for-woocommerce-free'),
            'button_link' => 'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=generaltabtiplink',
        ));
    }

    /**
     * Display did you know notice under ACFW generic panels.
     *
     * @since 1.6
     * @access public
     *
     * @param string $panel_id Coupon ID.
     */
    public function display_did_you_know_notice_in_generic_panel($panel_id)
    {
        if ("acfw_url_coupon" === $panel_id) {
            \ACFWF()->Notices->display_did_you_know_notice(array(
                'classname'   => 'acfw-dyk-notice-url-coupons',
                'description' => __('You can also use auto apply or one-click apply notifications to apply coupons without manually typing.', 'advanced-coupons-for-woocommerce-free'),
                'button_link' => 'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=urlcouponstiplink',
            ));
        }
    }

    /**
     * Advanced usage limits fields.
     *
     * @since 1.1
     * @access public
     *
     * @param int $coupon_id Coupon ID.
     */
    public function upsell_advanced_usage_limits_fields($coupon_id)
    {
        woocommerce_wp_select(array(
            'id'          => 'reset_usage_limit_period',
            'label'       => __('Reset usage count every:', 'advanced-coupons-for-woocommerce-free'),
            'options'     => array(
                'none'    => __('Never reset', 'advanced-coupons-for-woocommerce-free'),
                'yearly'  => __('Every year (premium)', 'advanced-coupons-for-woocommerce-free'),
                'monthly' => __('Every month (premium)', 'advanced-coupons-for-woocommerce-free'),
                'weekly'  => __('Every week (premium)', 'advanced-coupons-for-woocommerce-free'),
                'daily'   => __('Every day (premium)', 'advanced-coupons-for-woocommerce-free'),
            ),
            'description' => __('Set the time period to reset the usage limit count. <strong>Yearly:</strong> resets at start of the year. <strong>Monthly:</strong> resets at start of the month. <strong>Weekly:</strong> resets at the start of every week (day depends on the <em>"Week Starts On"</em> setting). <strong>Daily:</strong> resets everyday. Time is always set at 12:00am of the local timezone settings.', 'advanced-coupons-for-woocommerce-free'),
            'desc_tip'    => true,
            'value'       => 'none',
        ));

        \ACFWF()->Notices->display_did_you_know_notice(array(
            'classname'   => 'acfw-dyk-notice-usage-limit',
            'description' => __('You can reset usage limits on a timer either daily, weekly, monthly, or yearly.', 'advanced-coupons-for-woocommerce-free'),
            'button_link' => 'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=usagelimitstiplink',
        ));

    }

    /**
     * Add exclude coupon upsell field in usage restrictions tab.
     *
     * @since 1.1
     * @access public
     *
     * @param int $coupon_id Coupon ID.
     */
    public function uspell_exclude_coupons_restriction($coupon_id)
    {
        woocommerce_wp_select(array(
            'id'                => 'exclude_coupon_ids',
            'class'             => 'wc-product-search',
            'style'             => 'width:50%;',
            'label'             => __('Exclude coupons (premium)', 'advanced-coupons-for-woocommerce-free'),
            'description'       => __('This is the advanced version of the "Individual use only" field. Coupons listed here cannot be used in conjunction with this coupon.', 'advanced-coupons-for-woocommerce-free'),
            'desc_tip'          => true,
            'options'           => array(),
            'custom_attributes' => array(
                'multiple'         => true,
                'data-placeholder' => __('Search coupons&hellip;', 'advanced-coupons-for-woocommerce-free'),
            ),
        ));
    }

    /**
     * Cart condition premium field options upsell.
     *
     * @since 1.0
     * @since 1.5 Changed filter to 'acfw_condition_fields_localized_data'
     * @access public
     *
     * @param array $options Field options list.
     * @return array Filtered field options list.
     */
    public function cart_condition_premium_field_options($options = array())
    {
        $premium = array(
            'product_quantity'           => array(
                'group' => 'products',
                'key'   => 'product-quantity',
                'title' => __('Product Quantities Exists In Cart (Premium)', 'advanced-coupons-for-woocommerce-free'),
            ),
            'customer_registration_date' => array(
                'group' => 'customers',
                'key'   => 'customer-registration-date',
                'title' => __('Within Hours After Customer Registered (Premium)', 'advanced-coupons-for-woocommerce-free'),
            ),
            'customer_last_ordered'      => array(
                'group' => 'customers',
                'key'   => 'customer-last-ordered',
                'title' => __('Within Hours After Customer Last Order (Premium)', 'advanced-coupons-for-woocommerce-free'),
            ),
            'total_customer_spend'       => array(
                'group' => 'customers',
                'key'   => 'total-customer-spend',
                'title' => __('Total Customer Spend (Premium)', 'advanced-coupons-for-woocommerce-free'),
            ),
            'has_ordered_before'         => array(
                'group' => 'products',
                'key'   => 'has-ordered-before',
                'title' => __('Customer Has Ordered Products Before (Premium)', 'advanced-coupons-for-woocommerce-free'),
            ),
            'shipping_zone_region'       => array(
                'group' => 'customers',
                'key'   => 'shipping-zone-region',
                'title' => __('Shipping Zone And Region (Premium)', 'advanced-coupons-for-woocommerce-free'),
            ),
            'custom_taxonomy'            => array(
                'group' => 'product-categories',
                'key'   => 'custom-taxonomy',
                'title' => __('Custom Taxonomy Exists In Cart (Premium)', 'advanced-coupons-for-woocommerce-free'),
            ),
            'custom_user_meta'           => array(
                'group' => 'advanced',
                'key'   => 'custom-user-meta',
                'title' => __('Custom User Meta (Premium)', 'advanced-coupons-for-woocommerce-free'),
            ),
            'custom_cart_item_meta'      => array(
                'group' => 'advanced',
                'key'   => 'custom-cart-item-meta',
                'title' => __('Custom Cart Item Meta (Premium)', 'advanced-coupons-for-woocommerce-free'),
            ),
        );

        return array_merge($options, $premium);
    }

    /**
     * Register more cart conditions tab.
     *
     * @since 1.6
     * @access public
     *
     * @param array $tabs Cart condition panel tabs.
     * @return array Filtered cart condition panel tabs.
     */
    public function register_more_cart_conditions_tab($tabs)
    {
        $tabs['moreconditions'] = __('More Cart Conditions (Premium)', 'advanced-coupons-for-woocommerce-free');
        return $tabs;
    }

    /**
     * Display more cart conditions panel.
     *
     * @since 1.6
     * @access public
     */
    public function display_more_cart_conditions_panel()
    {
        $cart_conditions = array(
            array(
                'title'       => __('Product Quantity In The Cart (Premium)', 'advanced-coupons-for-woocommerce-free'),
                'description' => __('Check for the product (or products) and measure their quantity to see if the customer is eligible to use that coupon based on that.', 'advanced-coupons-for-woocommerce-free'),
            ),
            array(
                'title'       => __('Custom Taxonomy In The Cart (Premium)', 'advanced-coupons-for-woocommerce-free'),
                'description' => __('If you have a custom taxonomy on Products, for example “Brands”, this would let you check on those before applying a coupon.', 'advanced-coupons-for-woocommerce-free'),
            ),
            array(
                'title'       => __('Within Hours After Customer Registered (Premium)', 'advanced-coupons-for-woocommerce-free'),
                'description' => __('It can be useful to check when a customer was registered on your store before applying a coupon.', 'advanced-coupons-for-woocommerce-free'),
            ),
            array(
                'title'       => __('Within Hours After Customer Last Order (Premium)', 'advanced-coupons-for-woocommerce-free'),
                'description' => __('Restrict a coupon for use within a certain time period to encourage a follow-up order.', 'advanced-coupons-for-woocommerce-free'),
            ),
            array(
                'title'       => __('Total Customer Spend (Premium)', 'advanced-coupons-for-woocommerce-free'),
                'description' => __('Create a coupon that is only allowed if they’ve spent a certain historical amount.', 'advanced-coupons-for-woocommerce-free'),
            ),
            array(
                'title'       => __('Has Ordered Before (Premium)', 'advanced-coupons-for-woocommerce-free'),
                'description' => __('Check if a customer has ordered something before letting them apply a coupon.', 'advanced-coupons-for-woocommerce-free'),
            ),
            array(
                'title'       => __('Custom User Meta (Premium)', 'advanced-coupons-for-woocommerce-free'),
                'description' => __('Great for developers, test any extra user metadata on customer user records before applying a coupon.', 'advanced-coupons-for-woocommerce-free'),
            ),
            array(
                'title'       => __('Custom Cart Item Meta (Premium)', 'advanced-coupons-for-woocommerce-free'),
                'description' => __('Great for developers, in specific situations where custom cart item meta has been added and you need to target a coupon for that.', 'advanced-coupons-for-woocommerce-free'),
            ),
            array(
                'title'       => __('Shipping Zone And Region (Premium)', 'advanced-coupons-for-woocommerce-free'),
                'description' => __('Restricting coupons based on the shipping zone is great when you need to apply coupons geographically.', 'advanced-coupons-for-woocommerce-free'),
            ),
        );
        include $this->_constants->VIEWS_ROOT_PATH() . 'premium' . DIRECTORY_SEPARATOR . 'view-more-cart-conditions-panel.php';
    }

    /**
     * Register did you know notice html attribute in cart conditions panel.
     *
     * @since 1.6
     * @access public
     *
     * @param array $atts
     */
    public function register_dyk_notice_html_attribute($atts)
    {
        $atts['premium-conditions'] = array_column($this->cart_condition_premium_field_options(), 'key');
        return $atts;
    }

    /**
     * BOGO Deals premium trigger and apply type descriptions.
     *
     * @since 1.0
     * @access public
     *
     * @param array $descs Descriptions
     * @return array Filtered descriptions.
     */
    public function bogo_premium_trigger_apply_type_descs($descs)
    {
        $link    = sprintf('<a href="%s" target="_blank" rel="noreferer noopener">%s</a>', 'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=bogodescriptionlink', __('Premium', 'advanced-coupons-for-woocommerce-free'));
        $premium = array(
            'combination-products' => sprintf(__('Combination of Products (%s) – good when dealing with variable products or multiple products', 'advanced-coupons-for-woocommerce-free'), $link),
            'product-categories'   => sprintf(__('Product Categories (%s) – good when you want to trigger or apply a range of products from a particular category or set of categories', 'advanced-coupons-for-woocommerce-free'), $link),
            'any-products'         => sprintf(__('Any Products (%s) – good when you want to trigger or apply all of the products present in the cart', 'advanced-coupons-for-woocommerce-free'), $link),
        );

        return array_merge($descs, $premium);
    }

    /**
     * Get trigger and apply options.
     *
     * @since 2.6
     * @access private
     *
     * @param bool $is_apply Is apply flag.
     * @return array List of options.
     */
    private function _get_trigger_apply_options($is_apply = false)
    {
        $options = array(
            'combination-products' => __('Any Combination of Products (Premium)', 'advanced-coupons-for-woocommerce-free'),
            'product-categories'   => __('Product Categories (Premium)', 'advanced-coupons-for-woocommerce-free'),
            'any-products'         => __('Any Products (Premium)', 'advanced-coupons-for-woocommerce-free'),
        );

        return $options;
    }

    /**
     * BOGO Deals premium trigger type options.
     *
     * @since 1.0
     * @access public
     *
     * @param array $options Field options list.
     * @return array Filtered field options list.
     */
    public function bogo_premium_trigger_type_options($options)
    {
        return array_merge($options, $this->_get_trigger_apply_options());
    }

    /**
     * BOGO Deals premium trigger type options.
     *
     * @since 1.0
     * @access public
     *
     * @param array $options Field options list.
     * @return array Filtered field options list.
     */
    public function bogo_premium_apply_type_options($options)
    {
        return array_merge($options, $this->_get_trigger_apply_options(true));
    }

    /**
     * Upsell BOGO automatically add deal products feature.
     * 
     * @since 4.1
     * @access public
     * 
     * @param array $bogo_deals Coupon BOGO Deals data.
     */
    public function upsell_automatically_add_deal_products_feature($bogo_deals) 
    {
        $deals_type = isset($bogo_deals['deals_type']) ? $bogo_deals['deals_type'] : 'specific-products';

        include $this->_constants->VIEWS_ROOT_PATH() . 'premium/view-coupon-bogo-additional-settings.php';
    }

    /*
    |--------------------------------------------------------------------------
    | Settings.
    |--------------------------------------------------------------------------
     */

    /**
     * Register upsell settings section.
     *
     * @since 1.0
     * @since 1.1 Add license placeholder settings page.
     * @access public
     *
     * @param array $sections ACFW settings sections.
     * @return array Filtered ACFW settings sections.
     */
    public function register_upsell_settings_section($sections)
    {
        $sections['acfw_slmw_settings_section'] = __('License', 'advanced-coupons-for-woocommerce-free');
        $sections['acfw_premium']               = __('Upgrade', 'advanced-coupons-for-woocommerce-free');

        return $sections;
    }

    /**
     * Get upsell settings section fields.
     *
     * @since 1.0
     * @since 1.1 Add display for license placeholder settings page.
     * @access public
     *
     * @param array  $settings        List of settings fields.
     * @param string $current_section Current section id.
     */
    public function get_upsell_settings_section_fields($settings, $current_section)
    {
        if (!in_array($current_section, array('acfw_premium', 'acfw_slmw_settings_section'))) {
            return $settings;
        }

        // hide save changes button.
        $GLOBALS['hide_save_button'] = true;

        $settings = array(
            array(
                'title' => '',
                'type'  => 'title',
                'desc'  => '',
                'id'    => 'acfw_upsell_main_title',
            ),
        );

        // display premium upsell content
        if ('acfw_premium' === $current_section) {
            $settings[] = array(
                'type' => 'acfw_premium',
                'id'   => 'acfw_premium_content',
            );
        }

        // display license upsell content
        if ('acfw_slmw_settings_section' === $current_section) {
            $settings[] = array(
                'type' => 'acfw_license_placeholder',
                'id'   => 'acfw_license_placeholder_content',
            );
        }

        $settings[] = array(
            'type' => 'sectionend',
            'id'   => 'acfw_upsell_end',
        );

        return $settings;
    }

    /**
     * Append did you know notice data on BOGO Deals settings.
     *
     * @since 1.6
     * @access public
     *
     * @param array $settings Setting fields array.
     */
    public function bogo_settings_append_dyk_notice($settings)
    {
        $filtered = array();
        foreach ($settings as $setting) {
            $filtered[] = $setting;
            if (Plugin_Constants::BOGO_DEALS_NOTICE_TYPE === $setting['id']) {
                $filtered[] = array(
                    'title'      => '',
                    'type'       => 'notice',
                    'id'         => 'acfw_bogo_dyk_notice',
                    'noticeData' => \ACFWF()->Notices->display_did_you_know_notice(
                        array(
                            'description' => __('You can apply BOGO deals on combinations of products, product categories, or even on any product in the store.', 'advanced-coupons-for-woocommerce-free'),
                            'button_link' => 'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=settingsbogotip',
                        ),
                        true
                    ),
                );
            }
        }

        return $filtered;
    }

    /**
     * Register premium modules.
     *
     * @since 1.6
     * @access public
     *
     * @param array $modules Modules settings list.
     * @return array Filtered modules settings list.
     */
    public function register_premium_modules_settings($modules)
    {

        $modules[] = array(
            'title'   => __('Auto Apply', 'advanced-coupons-for-woocommerce-free'),
            'type'    => 'premiummodule',
            'desc'    => __("Have your coupon automatically apply once it's able to be applied.", 'advanced-coupons-for-woocommerce-free'),
            'id'      => Plugin_Constants::AUTO_APPLY_MODULE,
            'default' => 'yes',
        );

        $modules[] = array(
            'title'   => __('Coupon Scheduler', 'advanced-coupons-for-woocommerce-free'),
            'type'    => 'premiummodule',
            'desc'    => __('Schedule start and end dates for coupons.', 'advanced-coupons-for-woocommerce-free'),
            'id'      => Plugin_Constants::SCHEDULER_MODULE,
            'default' => 'yes',
        );

        $modules[] = array(
            'title'   => __('Advanced Usage Limits', 'advanced-coupons-for-woocommerce-free'),
            'type'    => 'premiummodule',
            'desc'    => __('Improves the usage limits feature of coupons, allowing you to set a time period to reset the usage counts.', 'advanced-coupons-for-woocommerce-free'),
            'id'      => Plugin_Constants::USAGE_LIMITS_MODULE,
            'default' => 'yes',
        );

        $modules[] = array(
            'title'   => __('Shipping Overrides', 'advanced-coupons-for-woocommerce-free'),
            'type'    => 'premiummodule',
            'desc'    => __('Lets you provide coupons that can discount shipping prices for any shipping method.', 'advanced-coupons-for-woocommerce-free'),
            'id'      => Plugin_Constants::SHIPPING_OVERRIDES_MODULE,
            'default' => 'yes',
        );

        $modules[] = array(
            'title'   => __('Add Products', 'advanced-coupons-for-woocommerce-free'),
            'type'    => 'premiummodule',
            'desc'    => __('On application of the coupon add certain products to the cart automatically after applying coupon.', 'advanced-coupons-for-woocommerce-free'),
            'id'      => Plugin_Constants::ADD_PRODUCTS_MODULE,
            'default' => 'yes',
        );

        $modules[] = array(
            'title'   => __('One Click Apply Notification', 'advanced-coupons-for-woocommerce-free'),
            'type'    => 'premiummodule',
            'desc'    => __('Lets you show a WooCommerce notice to a customer if the coupon is able to be applied with a button to apply it.', 'advanced-coupons-for-woocommerce-free'),
            'id'      => Plugin_Constants::APPLY_NOTIFICATION_MODULE,
            'default' => 'yes',
        );

        $modules[] = array(
            'title'   => __('Payment Methods Restriction', 'advanced-coupons-for-woocommerce-free'),
            'type'    => 'premiummodule',
            'desc'    => __('Restrict coupons to be used by certain payment method gateways only.', 'advanced-coupons-for-woocommerce-free'),
            'id'      => Plugin_Constants::PAYMENT_METHODS_RESTRICT,
            'default' => 'yes',
        );

        $modules[] = array(
            'title'   => __('Sort Coupons in Cart', 'advanced-coupons-for-woocommerce-free'),
            'type'    => 'premiummodule',
            'desc'    => __('Set priority for each coupon and automatically sort the applied coupons on cart/checkout. This will also sort coupons under auto apply and apply notifications.', 'advanced-coupons-for-woocommerce-free'),
            'id'      => Plugin_Constants::SORT_COUPONS_MODULE,
            'default' => '',
        );

        return $modules;
    }

    /**
     * Register upsell modal in settings localized data.
     *
     * @since 1.6
     * @access public
     *
     * @param array $data Localized data.
     * @return array Filtered localized data.
     */
    public function register_upsell_modal_settings_localized_data($data)
    {
        $data['upsellModal'] = array(
            'title'     => __('Premium Module', 'advanced-coupons-for-woocommerce-free'),
            'content'   => array(
                __('You are currently using Advanced Coupons for WooCommerce (Free Version). This module is only available for Premium license holders.', 'advanced-coupons-for-woocommerce-free'),
                __('Upgrade to premium today and gain access to this module & more!', 'advanced-coupons-for-woocommerce-free'),
            ),
            'buttonTxt' => __('Upgrade to Premium', 'advanced-coupons-for-woocommerce-free'),
        );

        return $data;
    }

    /**
     * Register general license field.
     *
     * @since 1.6
     * @access public
     *
     * @param array $settings Setting fields.
     * @return array Filtered setting fields.
     */
    public function register_general_license_field($settings)
    {
        $settings[] = array(
            'title'          => __('License', 'advanced-coupons-for-woocommerce-free'),
            'type'           => 'acfwflicense',
            'id'             => 'acfwf_license_field',
            'licenseContent' => array(
                __("You're using Advanced Coupons for WooCommerce Free - no license needed. Enjoy! 🙂", 'advanced-coupons-for-woocommerce-free'),
                sprintf(
                    __('To unlock more features consider <a href="%s" rel="noopener noreferer" target="blank">upgrading to Premium</a>', 'advanced-coupons-for-woocommerce-free'),
                    'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=generalsettingslicenselink'
                ),
                __("As a valued Advanced Coupons for WooCommerce Free user you receive up to <em>50% off</em>, automatically applied at checkout!", 'advanced-coupons-for-woocommerce-free'),
            ),
        );
        return $settings;
    }

    /**
     * Add upgrade section to help settings page.
     *
     * @since 1.0
     * @access public
     *
     * @param array $settings Setting fields.
     * @return array Filtered setting fields.
     */
    public function help_settings_upgrade_section($settings)
    {
        $section_start = array($settings[0]);

        unset($settings[0]);

        $upgrade_section = array(

            array(
                'title' => __('Upgrade', 'advanced-coupons-for-woocommerce-free'),
                'type'  => 'acfw_divider_row',
                'id'    => 'acfw_upgrade_divider_row',
            ),

            array(
                'title'     => __('Premium Add-on', 'advanced-coupons-for-woocommerce-free'),
                'type'      => 'acfw_upgrade_setting_field',
                'desc'      => __('Advanced Coupons Premium adds even more advanced features to your coupons so you can market your store better.', 'advanced-coupons-for-woocommerce-free'),
                'link_text' => __('Click here to read more and upgrade →', 'advanced-coupons-for-woocommerce-free'),
                'link_url'  => apply_filters('acfwp_upsell_link', 'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=helppage'),
            ),

        );

        return array_merge($section_start, $upgrade_section, $settings);
    }

    /**
     * Render ACFW premium settings content.
     *
     * @since 1.0
     * @access public
     *
     * @param array $value Array of options data. May vary depending on option type.
     */
    public function render_acfw_premium_settings_content($value)
    {
        $img_logo = $this->_constants->IMAGES_ROOT_URL() . '/acfw-logo-alt.png';?>
        <tr valign="top" class="<?php echo esc_attr($value['id']) . '-row'; ?>">
            <td colspan="2">

                <?php include $this->_constants->VIEWS_ROOT_PATH() . 'premium' . DIRECTORY_SEPARATOR . 'view-upgrade-settings-page.php';?>

            </td>
        </tr>
        <?php
}

    /**
     * Render ACFW license placeholder settings content.
     *
     * @since 1.1
     * @access public
     *
     * @param array $value Array of options data. May vary depending on option type.
     */
    public function render_acfw_license_placeholder_content($value)
    {
        $plugin_version = Plugin_Constants::VERSION;?>
        <tr valign="top" class="<?php echo esc_attr($value['id']) . '-row'; ?>">
            <td colspan="2">

                <?php include $this->_constants->VIEWS_ROOT_PATH() . 'premium' . DIRECTORY_SEPARATOR . 'view-license-placeholder-settings-page.php';?>

            </td>
        </tr>
        <?php
}

    /**
     * Enqueue upgrade settings tab styles and scripts.
     *
     * @since 1.0
     * @access public
     *
     * @param WP_Screen $screen    Current screen object.
     * @param string    $post_type Screen post type.
     */
    public function enqueue_upgrade_settings_scripts($screen, $post_type)
    {
        $section = isset($_GET['section']) ? $_GET['section'] : '';
        if ('woocommerce_page_wc-settings' === $screen->id && in_array($section, array('acfw_premium', 'acfw_slmw_settings_section'))) {
            wp_enqueue_style('acfwf_upgrade_settings', $this->_constants->CSS_ROOT_URL() . 'acfw-upgrade-settings.css', array(), Plugin_Constants::VERSION, 'all');
        }

        // wc-admin upsells
        if ('woocommerce_page_wc-admin' === $screen->id || 'edit-shop_coupon' === $screen->id) {
            wp_enqueue_style('acfw-wc-admin', $this->_constants->JS_ROOT_URL() . 'app/wc-admin/dist/acfw-wc-admin.css', array(), Plugin_Constants::VERSION, 'all');
            wp_enqueue_script('acfw-wc-admin', $this->_constants->JS_ROOT_URL() . 'app/wc-admin/dist/acfw-wc-admin.js', array('wc-components'), Plugin_Constants::VERSION, true);
            wp_localize_script('acfw-wc-admin', 'acfwWCAdmin', array(
                'sharedProps'         => array(
                    'upgradePremium' => __('Upgrade To Premium', 'advanced-coupons-for-woocommerce-free'),
                    'premiumLink'    => admin_url('admin.php?page=acfw-premium'),
                    'bonusText'      => __('<strong>Bonus:</strong> Advanced Coupons free version users get up to 50% off the regular price, automatically applied at checkout.', 'advanced-coupons-for-woocommerce-free'),
                ),
                'analyticsUpsell'     => array(
                    'title'       => __('Unlock more coupon features with Advanced Coupons Premium', 'advanced-coupons-for-woocommerce-free'),
                    'description' => sprintf(
                        __('Advanced Coupons Premium is the 5-star %s add-on that adds even more features to your coupons. Gain access to premium Cart Conditions, advanced BOGO deals, adding products during coupon apply, one-click notices, auto apply coupons, better scheduling, and more!', 'advanced-coupons-for-woocommerce-free'),
                        '<span class="stars">★★★★★</span>'
                    ),
                ),
                'recommendExtensions' => array(
                    'title'       => __('Recommended coupon extensions', 'advanced-coupons-for-woocommerce-free'),
                    'description' => sprintf(
                        __('Advanced Coupons Premium is the 5-star %s add-on that adds even more features to your coupons. Gain access to premium Cart Conditions, advanced BOGO deals, adding products during coupon apply, one-click notices, auto apply coupons, better scheduling, and more!', 'advanced-coupons-for-woocommerce-free'),
                        '<span class="stars">★★★★★</span>'
                    ),
                ),
            ));
        }
    }

    /**
     * Render help resources controls.
     *
     * @since 1.0
     * @access public
     *
     * @param $value Array of options data. May vary depending on option type.
     */
    public function render_acfw_upgrade_setting_field($value)
    {
        ?>

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for=""><?php echo sanitize_text_field($value['title']); ?></label>
            </th>
            <td class="forminp forminp-<?php echo sanitize_title($value['type']); ?>">
                <p><?php echo esc_html($value['desc']); ?></p>
                <p><a class="button button-primary" id="<?php echo esc_attr($value['id']); ?>" href="<?php echo esc_url($value['link_url']); ?>" target="_blank">
                    <?php echo sanitize_text_field($value['link_text']); ?>
                </a></p>
            </td>
        </tr>

        <?php
}

    /**
     * Add help link in usage restrictions tab.
     *
     * @since 1.5
     * @access public
     */
    public function usage_restrictions_add_help_link()
    {
        echo '<div class="acfw-help-link" data-module="usage-restrictions"></div>';
    }

    /**
     * Add help link in usage limits tab.
     *
     * @since 1.5
     * @access public
     */
    public function usage_limits_add_help_link()
    {
        echo '<div class="acfw-help-link" data-module="usage-limits"></div>';
    }

    /*
    |--------------------------------------------------------------------------
    | Edit advanced coupon JS upsell.
    |--------------------------------------------------------------------------
     */

    /**
     * Add upsell localized data on edit advanced coupon JS.
     *
     * @since 1.0
     * @access public
     *
     * @param array $data Localized data.
     * @return array Filtered localized data.
     */
    public function add_upsell_localized_script_data_on_edit_advanced_coupon_js($data)
    {
        $data['premium_cart_condition_fields'] = array(
            'product-quantity',
            'custom-taxonomy',
            'customer-registration-date',
            'customer-last-ordered',
            'custom-user-meta',
            'custom-cart-item-meta',
            'total-customer-spend',
            'has-ordered-before',
            'shipping-zone-region',
        );

        $data['upsell'] = array(
            'cart_condition_field' => sprintf(
                __('<img src="%s" alt="Advanced Coupons Premium" style="height: 50px;" />
                <h2>Premium Cart Condition</h2>
                <p>This premium cart condition and more are available in the Premium add-on for Advanced Coupons</p>
                <p><a href="%s" target="_blank">See all features & pricing &rarr;</a>', 'advanced-coupons-for-woocommerce-free'),
                $this->_constants->IMAGES_ROOT_URL() . '/acfw-logo.png',
                apply_filters('acfwp_upsell_link', 'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=cartcondition')
            ),
            'bogo_deals_type'      => sprintf(
                __('You can do advanced BOGO deals in the <a href="%s" target="_blank">Premium add-on for Advanced Coupons</a>.', 'advanced-coupons-for-woocommerce-free'),
                apply_filters('acfwp_upsell_link', 'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=bogo')
            ),
            'usage_limits'         => sprintf(
                __('<img src="%s" alt="Advanced Coupons Premium" />
                <h3>Upgrade To Reset Coupon Usage On Timer</h3>
                <p>In Advanced Coupons Premium you can reset the usage counts of a coupon on a timer. This is great for running recurring deals such as daily deals, giving coupons to influencers to redeem samples and more.</p>
                <a href="%s" target="_blank">See all features & pricing &rarr;</a>', 'advanced-coupons-for-woocommerce-free'),
                $this->_constants->IMAGES_ROOT_URL() . '/acfw-logo.png',
                apply_filters('acfwp_upsell_link', 'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=usagelimits')
            ),
            'usage_restriction'    => sprintf(
                __('<img src="%s" alt="Advanced Coupons Premium" />
                <h3>Upgrade To Get Advanced Coupons Restrictions</h3>
                <p>In Advanced Coupons Premium you can restrict the usage of this coupon more granularly with other specific coupons. This is great if you have a coupon that is allowed to work with some coupons but not others.</p>
                <a href="%s" target="_blank">See all features & pricing &rarr;</a>', 'advanced-coupons-for-woocommerce-free'),
                $this->_constants->IMAGES_ROOT_URL() . '/acfw-logo.png',
                apply_filters('acfwp_upsell_link', 'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=usagerestriction')
            ),
            'auto_apply'           => sprintf(
                __('<img src="%s" alt="Advanced Coupons Premium" />
                <h3>Upgrade To Apply Coupons Automatically</h3>
                <p>In Advanced Coupons Premium you can have coupons automatically apply to a customer’s cart once the Cart Conditions match! Surprise and delight your customers with auto apply coupons.</p>
                <a href="%s" target="_blank">See all features & pricing &rarr;</a>', 'advanced-coupons-for-woocommerce-free'),
                $this->_constants->IMAGES_ROOT_URL() . '/acfw-logo.png',
                apply_filters('acfwp_upsell_link', 'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=autoapply')
            ),
            'bogo_auto_add_get_products' => sprintf(
                __('<img src="%s" alt="Advanced Coupons Premium" />
                <h3>Upgrade To Apply "Get" Product Automatically</h3>
                <p>In Advanced Coupons Premium, BOGO coupons with the Specific Product "Get" type can automatically apply the product to a customer’s cart! This is only available for Specific Product type. It’s a great user experience upgrade for your customers to have it done for them.</p>
                <a href="%s" target="_blank">See all features & pricing &rarr;</a>', 'advanced-coupons-for-woocommerce-free'),
                $this->_constants->IMAGES_ROOT_URL() . '/acfw-logo.png',
                apply_filters('acfwp_upsell_link', 'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=bogoautoadd')
            ),
        );

        return $data;
    }

    /*
    |--------------------------------------------------------------------------
    | Plugin action link.
    |--------------------------------------------------------------------------
     */

    /**
     * Add settings link to plugin actions links.
     *
     * @since 1.0
     * @access public
     *
     * @param $links Plugin action links
     * @return array Filtered plugin action links
     */
    public function plugin_upgrade_action_link($links)
    {
        $upgrade_links = array(sprintf(
            __('<a href="%s" target="_blank"><b>Upgrade to Premium</b></a>', 'advanced-coupons-for-woocommerce-free'),
            apply_filters('acfwp_upsell_link', 'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=pluginpage')
        ));

        return array_merge($upgrade_links, $links);
    }

    /*
    |--------------------------------------------------------------------------
    | After two weeks upgrade notice.
    |--------------------------------------------------------------------------
     */

    /**
     * Schedule upgrade notice to be displayed two weeks after plugin is activated.
     *
     * @since 1.0
     * @access public
     */
    public function schedule_upgrade_notice_for_later()
    {
        if (wp_next_scheduled(Plugin_Constants::UPRADE_NOTICE_CRON) || get_option(Plugin_Constants::SHOW_UPGRADE_NOTICE)) {
            return;
        }

        wp_schedule_single_event(time() + (WEEK_IN_SECONDS * 2), Plugin_Constants::UPRADE_NOTICE_CRON);
    }

    /**
     * Trigger to show upgrade notice.
     *
     * @since 1.0
     * @access public
     */
    public function trigger_show_upgrade_notice_for_later()
    {
        if (get_option(Plugin_Constants::SHOW_UPGRADE_NOTICE) === 'dismissed') {
            return;
        }

        update_option(Plugin_Constants::SHOW_UPGRADE_NOTICE, 'yes');
    }

    /**
     * Display upgrade notice on admin notices.
     *
     * @since 1.0
     * @since 1.1 Don't show on ACFW settings upgrade page.
     * @access public
     *
     * @param array $notice_options List of notice options.
     * @return array Filtered list of notice options.
     */
    public function register_upgrade_notice_option($notice_options)
    {
        $tab     = isset($_GET['tab']) ? $_GET['tab'] : '';
        $section = isset($_GET['section']) ? $_GET['section'] : '';

        if ('acfw_settings' !== $tab || 'acfw_premium' !== $section) {
            $notice_options['upgrade'] = Plugin_Constants::SHOW_UPGRADE_NOTICE;
        }

        return $notice_options;
    }

    /**
     * Register upgrade notice view path.
     *
     * @since 1.0
     * @access public
     *
     * @param array $notice_paths List of notice paths.
     * @return array Filtered list of notice paths.
     */
    public function register_upgrade_notice_view_path($notice_paths)
    {
        $notice_paths['upgrade'] = $this->_constants->VIEWS_ROOT_PATH() . 'notices/view-upgrade-notice.php';

        return $notice_paths;
    }

    /*
    |--------------------------------------------------------------------------
    | WC Marketing
    |--------------------------------------------------------------------------
     */

    /**
     * Filter through the WC Marketing recommended extensions transient and prepend our extensions.
     *
     * @deprecated 1.6
     *
     * @since 1.1
     * @access public
     *
     * @param array $recommended_plugins List of recommended plugins.
     * @return array Filtered list of recommended plugins.
     */
    public function filter_wc_marketing_recommended_plugins($recommended_plugins)
    {
        return $recommended_plugins;
    }

    /**
     * Filter through the WC Marketing knowledgebase articles transient and prepend our own articles.
     *
     * @since 1.1
     * @since 1.2.3 Add transient parameter.
     * @access public
     *
     * @param array $knowledge_base List of WC kb articles.
     * @param array $transient      Current transient.
     * @param array Filtered list of WC kb articles.
     */
    public function filter_wc_marketing_knowledge_base($knowledge_base, $transient)
    {
        // force fetch WC knowledge base data if its not yet avaiable or when transient has expired.
        $category = strpos($transient, 'coupon') !== false ? 'coupons' : 'marketing';
        if (false === $knowledge_base) {
            remove_filter('transient_' . $transient, array($this, 'filter_wc_marketing_knowledge_base'));
            $wcmarketing    = new \Automattic\WooCommerce\Admin\Features\Marketing();
            $knowledge_base = $wcmarketing->get_knowledge_base_posts($category);
        }

        $wws_ebook_check = !empty($knowledge_base) ? array_filter($knowledge_base, function ($kb) {
            return (isset($kb['id']) && 'wwsebook' === $kb['id']);
        }) : array();

        if (empty($wws_ebook_check) && 'coupons' !== $category) {
            array_unshift(
                $knowledge_base,
                array(
                    'id'            => 'wwsebook',
                    'title'         => __('How To Setup Wholesale On Your WooCommerce Store', 'advanced-coupons-for-woocommerce-free'),
                    'date'          => date('Y-m-d\TH:i:s', time()),
                    'link'          => 'https://wholesalesuiteplugin.com/free-guide/?utm_source=acfwf&utm_medium=wcmarketing&utm_campaign=knowledgebase',
                    'author_name'   => 'Josh Kohlbach',
                    'author_avatar' => 'https://secure.gravatar.com/avatar/2f2da8c07f7031a969ae1bd233437a29?s=32&amp;d=mm&amp;r=g',
                    'image'         => $this->_constants->IMAGES_ROOT_URL() . 'wws-free-ebook.png',
                )
            );
        }

        $acfw_ebook_check = !empty($knowledge_base) ? array_filter($knowledge_base, function ($kb) {
            return (isset($kb['id']) && 'acfwebook' === $kb['id']);
        }) : array();

        if (empty($acfw_ebook_check)) {
            array_unshift(
                $knowledge_base,
                array(
                    'id'            => 'acfwebook',
                    'title'         => __('How To Grow A WooCommerce Store Using Coupon Deals', 'advanced-coupons-for-woocommerce-free'),
                    'date'          => date('Y-m-d\TH:i:s', time()),
                    'link'          => 'https://advancedcouponsplugin.com/how-to-grow-your-woocommerce-store-with-coupons/?utm_source=acfwf&utm_medium=wcmarketing&utm_campaign=knowledgebase',
                    'author_name'   => 'Josh Kohlbach',
                    'author_avatar' => 'https://secure.gravatar.com/avatar/2f2da8c07f7031a969ae1bd233437a29?s=32&amp;d=mm&amp;r=g',
                    'image'         => $this->_constants->IMAGES_ROOT_URL() . 'acfw-free-ebook.png',
                )
            );
        }

        return $knowledge_base;
    }

    /*
    |--------------------------------------------------------------------------
    | Upsell admin app page.
    |--------------------------------------------------------------------------
     */

    /**
     * Register upsell admin app page.
     *
     * @since 1.2
     * @access public
     *
     * @param array $app_pages List of app pages.
     * @return array Filtered list of app pages.
     */
    public function register_upsell_admin_app_page($app_pages, $show_app)
    {
        $app_pages['acfw-premium'] = array(
            'label' => __('Upgrade to Premium', 'advanced-coupons-for-woocommerce-free'),
            'slug'  => $show_app ? 'acfw-premium' : 'wc-settings&tab=acfw_settings&section=acfw_premium',
            'page'  => 'premium_upgrade',
        );

        return $app_pages;
    }

    /**
     * Register the advanced coupons premium link under WC Marketing top level menu.
     *
     * @since 1.6
     * @access public
     */
    public function register_acfwp_link_in_marketing_top_level_menu()
    {
        add_submenu_page(
            'woocommerce-marketing',
            __('Advanced Coupons Premium', 'advanced-coupons-for-woocommerce-free'),
            __('Advanced Coupons Premium', 'advanced-coupons-for-woocommerce-free'),
            'manage_woocommerce',
            'admin.php?page=acfw-premium'
        );
    }

    /**
     * Append upsell data for admin app page localized script.
     *
     * @since 1.2
     * @access public
     *
     * @param array $data Localized data.
     * @return array Filtered localized data.
     */
    public function upsell_localized_data_for_admin_app($data)
    {
        $data['coupon_nav']['premium'] = __('Upgrade to Premium', 'advanced-coupons-for-woocommerce-free');

        $data['premium_page'] = array(
            'image'  => $this->_constants->IMAGES_ROOT_URL() . '/acfw-logo-alt.png',
            'title'  => __('<strong>Free</strong> vs <strong>Premium</strong>', 'advanced-coupons-for-woocommerce-free'),
            'desc'   => __('If you are serious about growing your sales within your WooCommerce store then the Premium add-on to the free Advanced Coupons for WooCommerce plugin that you are currently using can help you.', 'advanced-coupons-for-woocommerce-free'),
            'header' => array(
                'feature' => __('Features', 'advanced-coupons-for-woocommerce-free'),
                'free'    => __('Free Plugin', 'advanced-coupons-for-woocommerce-free'),
                'premium' => __('Premium Add-on', 'advanced-coupons-for-woocommerce-free'),
            ),
            'rows'   => array(
                array(
                    'feature' => __('Restrict Applying Coupons Using Cart Conditions', 'advanced-coupons-for-woocommerce-free'),
                    'free'    => __('Basic set of cart conditions only', 'advanced-coupons-for-woocommerce-free'),
                    'premium' => __('Advanced cart conditions to let you control exactly when coupons should be allowed to apply.', 'advanced-coupons-for-woocommerce-free'),
                ),
                array(
                    'feature' => __('Run BOGO deals with coupons', 'advanced-coupons-for-woocommerce-free'),
                    'free'    => __('Simple BOGO deals only', 'advanced-coupons-for-woocommerce-free'),
                    'premium' => __('Run advanced BOGO deals with multiple products or across product categories.', 'advanced-coupons-for-woocommerce-free'),
                ),
                array(
                    'feature' => __('Schedule coupon start and end date', 'advanced-coupons-for-woocommerce-free'),
                    'free'    => __('Only WordPress scheduled post', 'advanced-coupons-for-woocommerce-free'),
                    'premium' => __('Show a nice message before and after specific start/end dates so you can recapture lost sales.', 'advanced-coupons-for-woocommerce-free'),
                ),
                array(
                    'feature' => __('One-click Apply Notifications', 'advanced-coupons-for-woocommerce-free'),
                    'free'    => __('Not available', 'advanced-coupons-for-woocommerce-free'),
                    'premium' => __('Show a message at the cart with a one-click apply button when the customer is eligible for a coupon.', 'advanced-coupons-for-woocommerce-free'),
                ),
                array(
                    'feature' => __('Auto Apply Coupons', 'advanced-coupons-for-woocommerce-free'),
                    'free'    => __('Not available', 'advanced-coupons-for-woocommerce-free'),
                    'premium' => __('Automatically apply a coupon to the cart when a customer becomes eligible.', 'advanced-coupons-for-woocommerce-free'),
                ),
                array(
                    'feature' => __('Shipping Override Coupons', 'advanced-coupons-for-woocommerce-free'),
                    'free'    => __('Not available', 'advanced-coupons-for-woocommerce-free'),
                    'premium' => __("Run more creative discounts on your store's shipping methods.", 'advanced-coupons-for-woocommerce-free'),
                ),
                array(
                    'feature' => __('Timed Usage Resets', 'advanced-coupons-for-woocommerce-free'),
                    'free'    => __('Not available', 'advanced-coupons-for-woocommerce-free'),
                    'premium' => __("Give coupons with usage limits that reset after a time - great for influencer marketing or daily deals.", 'advanced-coupons-for-woocommerce-free'),
                ),
            ),
            'action' => array(
                'title'    => __("+ 100's of other premium features", 'advanced-coupons-for-woocommerce-free'),
                'btn_text' => __("See the full feature list →", 'advanced-coupons-for-woocommerce-free'),
                'btn_link' => apply_filters('acfwp_upsell_link', 'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=upgradepage'),
            ),
        );

        return $data;
    }

    /**
     * Highlight upgrade to premium submenu link.
     *
     * @since 1.6
     * @access public
     */
    public function highlight_upgrade_to_premium_submenu_link()
    {
        ?>
        <script type="text/javascript">
        (function($){
            $link = $('.toplevel_page_acfw-admin').find('a[href="admin.php?page=acfw-premium"]');
            $link.css({background: '#6bb738', color: '#fff', fontWeight: 'bold'});
        })(jQuery);
        </script>
    <?php
}

    /*
    |--------------------------------------------------------------------------
    | Fulfill implemented interface contracts
    |--------------------------------------------------------------------------
     */

    /**
     * Execute codes that needs to run plugin activation.
     *
     * @since 1.0
     * @access public
     * @implements ACFWF\Interfaces\Initializable_Interface
     */
    public function initialize()
    {
    }

    /**
     * Execute Upsell class.
     *
     * @since 1.0
     * @access public
     * @inherit ACFWF\Interfaces\Model_Interface
     */
    public function run()
    {
        add_filter('transient_wc_marketing_knowledge_base_marketing', array($this, 'filter_wc_marketing_knowledge_base'), 10, 2);
        add_filter('transient_wc_marketing_knowledge_base_coupons', array($this, 'filter_wc_marketing_knowledge_base'), 10, 2);

        if ($this->_helper_functions->is_plugin_active(Plugin_Constants::PREMIUM_PLUGIN)) {
            return;
        }

        add_action('add_meta_boxes', array($this, 'register_upsell_metabox'), 10, 2);
        add_filter('woocommerce_coupon_data_tabs', array($this, 'register_upsell_panels'), 99, 1);
        add_action('woocommerce_coupon_options', array($this, 'display_did_you_know_notice_in_general'));
        add_action('acfw_after_coupon_generic_panel', array($this, 'display_did_you_know_notice_in_generic_panel'));
        add_filter('acfw_condition_fields_localized_data', array($this, 'cart_condition_premium_field_options'));
        add_filter('acfw_cart_condition_panel_tabs', array($this, 'register_more_cart_conditions_tab'));
        add_action('acfw_cart_condition_tabs_panels', array($this, 'display_more_cart_conditions_panel'));
        add_filter('acfw_cart_conditions_panel_data_atts', array($this, 'register_dyk_notice_html_attribute'));
        add_filter('acfw_bogo_trigger_apply_type_descs', array($this, 'bogo_premium_trigger_apply_type_descs'));
        add_filter('acfw_bogo_trigger_type_options', array($this, 'bogo_premium_trigger_type_options'));
        add_filter('acfw_bogo_apply_type_options', array($this, 'bogo_premium_apply_type_options'));
        add_action('acfw_bogo_before_additional_settings', array($this, 'upsell_automatically_add_deal_products_feature'), 10, 2);
        add_filter('woocommerce_get_sections_acfw_settings', array($this, 'register_upsell_settings_section'));
        add_filter('woocommerce_get_settings_acfw_settings', array($this, 'get_upsell_settings_section_fields'), 10, 2);
        add_action('acfw_settings_help_section_options', array($this, 'help_settings_upgrade_section'));
        add_filter('acfw_setting_general_options', array($this, 'register_general_license_field'));
        add_filter('acfw_setting_bogo_deals_options', array($this, 'bogo_settings_append_dyk_notice'));
        add_filter('acfw_modules_settings', array($this, 'register_premium_modules_settings'));
        add_filter('acfwf_admin_app_localized', array($this, 'register_upsell_modal_settings_localized_data'));
        add_action('woocommerce_admin_field_acfw_premium', array($this, 'render_acfw_premium_settings_content'));
        add_action('woocommerce_admin_field_acfw_license_placeholder', array($this, 'render_acfw_license_placeholder_content'));
        add_action('woocommerce_admin_field_acfw_upgrade_setting_field', array($this, 'render_acfw_upgrade_setting_field'));
        add_action('acfw_after_load_backend_scripts', array($this, 'enqueue_upgrade_settings_scripts'), 10, 2);
        add_action('woocommerce_coupon_data_panels', array($this, 'display_upsell_panel_views'));
        add_action('woocommerce_coupon_options_usage_limit', array($this, 'upsell_advanced_usage_limits_fields'));
        add_action('woocommerce_coupon_options_usage_restriction', array($this, 'uspell_exclude_coupons_restriction'));
        add_action('woocommerce_coupon_options_usage_restriction', array($this, 'usage_restrictions_add_help_link'));
        add_action('woocommerce_coupon_options_usage_limit', array($this, 'usage_limits_add_help_link'));

        add_filter('acfw_edit_advanced_coupon_localize', array($this, 'add_upsell_localized_script_data_on_edit_advanced_coupon_js'));
        add_filter('plugin_action_links_' . $this->_constants->PLUGIN_BASENAME(), array($this, 'plugin_upgrade_action_link'), 20);

        add_action('admin_init', array($this, 'schedule_upgrade_notice_for_later'));
        add_action(Plugin_Constants::UPRADE_NOTICE_CRON, array($this, 'trigger_show_upgrade_notice_for_later'));
        add_filter('acfw_admin_notice_option_names', array($this, 'register_upgrade_notice_option'));
        add_filter('acfw_admin_notice_view_paths', array($this, 'register_upgrade_notice_view_path'));

        // admin app related
        add_filter('acfw_admin_app_pages', array($this, 'register_upsell_admin_app_page'), 10, 2);
        add_action('acfw_register_admin_submenus', array($this, 'register_acfwp_link_in_marketing_top_level_menu'));
        add_filter('acfwf_admin_app_localized', array($this, 'upsell_localized_data_for_admin_app'));

        add_action('admin_footer', array($this, 'highlight_upgrade_to_premium_submenu_link'));
    }

}
