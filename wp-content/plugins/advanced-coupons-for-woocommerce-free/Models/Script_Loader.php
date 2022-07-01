<?php
namespace ACFWF\Models;

use ACFWF\Abstracts\Abstract_Main_Plugin_Class;
use ACFWF\Helpers\Helper_Functions;
use ACFWF\Helpers\Plugin_Constants;
use ACFWF\Interfaces\Model_Interface;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

/**
 * Model that houses the logic of loading plugin scripts.
 * Private Model.
 *
 * @since 1.0
 */
class Script_Loader implements Model_Interface
{

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
     */

    /**
     * Property that holds the single main instance of Bootstrap.
     *
     * @since 1.0
     * @access private
     * @var Bootstrap
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
     * @return Bootstrap
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
    | Backend Scripts
    |--------------------------------------------------------------------------
     */

    /**
     * Register scripts to be used on the backend.
     *
     * @since 1.0
     * @access private
     */
    private function _register_backend_scripts()
    {

        $acfw_backend_styles = apply_filters('acfw_register_backend_styles', array(

            'vex'                       => array(
                'src'   => $this->_constants->JS_ROOT_URL() . 'lib/vex/vex.css',
                'deps'  => array(),
                'ver'   => Plugin_Constants::VERSION,
                'media' => 'all',
            ),

            'vex-theme-plain'           => array(
                'src'   => $this->_constants->JS_ROOT_URL() . 'lib/vex/vex-theme-plain.css',
                'deps'  => array(),
                'ver'   => Plugin_Constants::VERSION,
                'media' => 'all',
            ),

            // edit advanced coupon CSS
            'acfw-edit-advanced-coupon' => array(
                'src'   => $this->_constants->JS_ROOT_URL() . 'app/edit-advanced-coupon/dist/edit-advanced-coupon.css',
                'deps'  => array('jquery-ui-style', 'vex', 'vex-theme-plain'),
                'ver'   => Plugin_Constants::VERSION,
                'media' => 'all',
            ),

            // jQuery timepicker
            'acfw-jquery-timepicker'    => array(
                'src'   => $this->_constants->JS_ROOT_URL() . 'lib/timepicker/jquery.timepicker.min.css',
                'deps'  => array(),
                'ver'   => Plugin_Constants::VERSION,
                'media' => 'all',
            ),

            // ACFW Notices
            'acfw-notices'              => array(
                'src'   => $this->_constants->CSS_ROOT_URL() . 'acfw-notices.css',
                'deps'  => array('vex', 'vex-theme-plain'),
                'ver'   => Plugin_Constants::VERSION,
                'media' => 'all',
            ),

        ));

        $acfw_backend_scripts = apply_filters('acfw_register_backend_scripts', array(

            // vex js
            'vex'                       => array(
                'src'    => $this->_constants->JS_ROOT_URL() . 'lib/vex/vex.combined.min.js',
                'deps'   => array('jquery'),
                'ver'    => Plugin_Constants::VERSION,
                'footer' => true,
            ),

            'acfw-clipboard' => array(
                'src'    => $this->_constants->JS_ROOT_URL() . 'lib/clipboard.min.js',
                'deps'   => array('jquery'),
                'ver'    => Plugin_Constants::VERSION,
                'footer' => true,
            ),

            // edit advanced coupon JS
            'acfw-edit-advanced-coupon' => array(
                'src'    => $this->_constants->JS_ROOT_URL() . 'app/edit-advanced-coupon/dist/edit-advanced-coupon.js',
                'deps'   => array('jquery-ui-core', 'jquery-ui-datepicker', 'vex', 'acfw-clipboard'),
                'ver'    => Plugin_Constants::VERSION,
                'footer' => true,
            ),

            // jQuery timepicker
            'acfw-jquery-timepicker'    => array(
                'src'    => $this->_constants->JS_ROOT_URL() . 'lib/timepicker/jquery.timepicker.min.js',
                'deps'   => array('jquery'),
                'ver'    => Plugin_Constants::VERSION,
                'footer' => true,
            ),

            // ACFW Notices
            'acfw-notices'              => array(
                'src'    => $this->_constants->JS_ROOT_URL() . 'acfw-notices.js',
                'deps'   => array('jquery', 'vex'),
                'ver'    => Plugin_Constants::VERSION,
                'footer' => true,
            ),

            // ACFW edit order
            'acfw-edit-order'           => array(
                'src'    => $this->_constants->JS_ROOT_URL() . 'acfw-edit-order.js',
                'deps'   => array('jquery'),
                'ver'    => Plugin_Constants::VERSION,
                'footer' => true,
            ),

        ));

        // register backend styles via a loop
        foreach ($acfw_backend_styles as $id => $style) {
            wp_register_style($id, $style['src'], $style['deps'], $style['ver'], $style['media']);
        }

        // register backend scripts via a loop
        foreach ($acfw_backend_scripts as $id => $script) {
            wp_register_script($id, $script['src'], $script['deps'], $script['ver'], $script['footer']);
        }
    }

    /**
     * Load backend js and css scripts.
     *
     * @since 1.0
     * @access public
     *
     * @param string $handle Unique identifier of the current backend page.
     */
    public function load_backend_scripts($handle)
    {
        global $post;

        // register all scripts required in the backend.
        $this->_register_backend_scripts();

        $screen = get_current_screen();
        $locale = localeconv();

        $post_type = get_post_type();
        if (!$post_type && isset($_GET['post_type'])) {
            $post_type = $_GET['post_type'];
        }

        do_action('acfw_before_load_backend_scripts', $screen, $post_type);

        // edit coupon screen
        if ('post' == $screen->base && 'shop_coupon' == $screen->id && 'shop_coupon' == $post_type) {

            $decimal_separator = wc_get_price_decimal_separator();
            $decimal_separator = $decimal_separator ? $decimal_separator : $locale['decimal_point'];

            wp_enqueue_style('vex');
            wp_enqueue_style('acfw-edit-advanced-coupon');

            wp_enqueue_script('acfw-edit-advanced-coupon');
            wp_add_inline_script('acfw-edit-advanced-coupon', 'vex.defaultOptions.className = "vex-theme-plain"', 'after');
            wp_localize_script('acfw-edit-advanced-coupon', 'acfw_edit_coupon',
                apply_filters('acfw_edit_advanced_coupon_localize', array(
                    'modules'                         => $this->_helper_functions->get_active_modules(),
                    'currency_symbol'                 => get_woocommerce_currency_symbol(),
                    'empty_cart_conditions_field'     => __('This is an empty condition group, just waiting for you to add some conditions to it…', 'advanced-coupons-for-woocommerce-free'),
                    'no_condition_group_msg'          => __('No condition group', 'advanced-coupons-for-woocommerce-free'),
                    'no_products_added'               => __('No products added', 'advanced-coupons-for-woocommerce-free'),
                    'no_categories_added'             => __('No categories added', 'advanced-coupons-for-woocommerce-free'),
                    'remove_cgroup_label'             => __('Remove condition group', 'advanced-coupons-for-woocommerce-free'),
                    'remove_cfield_label'             => __('Remove condition field', 'advanced-coupons-for-woocommerce-free'),
                    'fill_form_propery_error_msg'     => __('Please fill the form properly', 'advanced-coupons-for-woocommerce-free'),
                    'discount_type_value_error_msg'   => __('Discount value must not be 0 for fixed or percentage discount types.', 'advanced-coupons-for-woocommerce-free'),
                    'product_exists_in_table'         => __('Product has already been added to the table', 'advanced-coupons-for-woocommerce-free'),
                    'cart_conditions_invalid'         => __('The set cart conditions data or part of it is invalid. Please fill the form properly.', 'advanced-coupons-for-woocommerce-free'),
                    'cart_notice_url_invalid'         => __('The value set in "Non-qualifying button URL" is not a valid url.', 'advanced-coupons-for-woocommerce-free'),
                    'unpublished_coupon_save'         => __("You saved the data on an unpublished coupon. To make sure your changes won't be lost, the coupon will need to be saved. The page will now refresh.", 'advanced-coupons-for-woocommerce-free'),
                    'bogo_deals_save_error_msg'       => __("Error on saving. Please fill up the conditions, deals and type properly.", 'advanced-coupons-for-woocommerce-free'),
                    'duplicate_product'               => __("Product selected have already been added.", 'advanced-coupons-for-woocommerce-free'),
                    'duplicate_category'              => __("Category selected have already been added.", 'advanced-coupons-for-woocommerce-free'),
                    'within_a_period_label'           => __('No. of previous days', 'advanced-coupons-for-woocommerce-free'),
                    'number_of_orders_label'          => __('No. of orders', 'advanced-coupons-for-woocommerce-free'),
                    'add_product_label'               => __('Add Product', 'advanced-coupons-for-woocommerce-free'),
                    'add_prod_cat_label'              => __('Add Category', 'advanced-coupons-for-woocommerce-free'),
                    'no_shipping_overrides_added'     => __('No shipping overrides added', 'advanced-coupons-for-woocommerce-free'),
                    'no_shipping_overrides_save'      => __('No shipping overrides to save.', 'advanced-coupons-for-woocommerce-free'),
                    'condition_label'                 => __('Condition', 'advanced-coupons-for-woocommerce-free'),
                    'shipping_zone_already_added'     => __("Selected shipping zone is already added.", 'advanced-coupons-for-woocommerce-free'),
                    'fail_add_condition_field'        => __("Failed to add condition field.", 'advanced-coupons-for-woocommerce-free'),
                    'add_new_and_rule'                => __("Add a New 'AND' Rule", 'advanced-coupons-for-woocommerce-free'),
                    'each_product_same_product_error' => __("Any products trigger type based on each product's quantity can only work with <em>Same Products</em> apply type.", 'advanced-coupons-for-woocommerce-free'),
                    'copied_label'                    => __('Copied', 'advanced-coupons-for-woocommerce-free'),
                    'product_table_buttons'           => array(
                        'add'    => __('Add', 'advanced-coupons-for-woocommerce-free'),
                        'edit'   => __('Edit', 'advanced-coupons-for-woocommerce-free'),
                        'cancel' => __('Cancel', 'advanced-coupons-for-woocommerce-free'),
                    ),
                    'logic_field_options'             => array(
                        'and' => __('AND', 'advanced-coupons-for-woocommerce-free'),
                        'or'  => __('OR', 'advanced-coupons-for-woocommerce-free'),
                    ),
                    'bogo_instructions'               => array(
                        'trigger_default'     => __('When all of the following products and quantities are matched, trigger the deal.', 'advanced-coupons-for-woocommerce-free'),
                        'apply_default'       => __('Once the deal is triggered, apply the following products to the cart.', 'advanced-coupons-for-woocommerce-free'),
                        'multiple_items'      => __('If multiple items are eligible, the cheapest will be given.', 'advanced-coupons-for-woocommerce-free'),
                        'anyproducts_trigger' => __('The BOGO deal will trigger when the products are detected in the cart.', 'advanced-coupons-for-woocommerce-free'),
                        'anyproducts_apply'   => __('When the deal is triggered, the customer can add the following quantity of any items in the store and they will be discounted. The cheapest eligible items will be the ones discounted.', 'advanced-coupons-for-woocommerce-free'),
                        'same_apply'          => __('Once the deal is triggered, get the same items listed on trigger table/block on per product basis.', 'advanced-coupons-for-woocommerce-free'),
                    ),
                    'bogo_form_fields'                => array(
                        'product_in_cart'     => __('Product in cart', 'advanced-coupons-for-woocommerce-free'),
                        'product_cat_in_cart' => __('Product category in cart', 'advanced-coupons-for-woocommerce-free'),
                        'type_to_search'      => __('Type to search', 'advanced-coupons-for-woocommerce-free'),
                        'quantity'            => __('Quantity', 'advanced-coupons-for-woocommerce-free'),
                        'trigger_quantity'    => __('Trigger Quantity', 'advanced-coupons-for-woocommerce-free'),
                        'product'             => __('Product', 'advanced-coupons-for-woocommerce-free'),
                        'products'            => __('Products', 'advanced-coupons-for-woocommerce-free'),
                        'product_cat'         => __('Product Category', 'advanced-coupons-for-woocommerce-free'),
                        'price_discount'      => __('Price/Discount', 'advanced-coupons-for-woocommerce-free'),
                        'discount_type'       => __('Discount Type', 'advanced-coupons-for-woocommerce-free'),
                    ),
                    'condition_field_options'         => array(
                        'exactly'   => __('EXACTLY', 'advanced-coupons-for-woocommerce-free'),
                        'anyexcept' => __('ANYTHING EXCEPT', 'advanced-coupons-for-woocommerce-free'),
                        'morethan'  => __('MORE THAN', 'advanced-coupons-for-woocommerce-free'),
                        'lessthan'  => __('LESS THAN', 'advanced-coupons-for-woocommerce-free'),
                    ),
                    'discount_field_options'          => array(
                        'override'   => __('Override price', 'advanced-coupons-for-woocommerce-free'),
                        'percent'    => __('Percentage discount', 'advanced-coupons-for-woocommerce-free'),
                        'fixed'      => __('Fixed discount', 'advanced-coupons-for-woocommerce-free'),
                        'nodiscount' => __('No discount', 'advanced-coupons-for-woocommerce-free'),
                    ),
                    'decimal_separator'               => $decimal_separator,
                    'price_decimals'                  => wc_get_price_decimals(),
                    'post_status'                     => $post ? get_post_status($post) : '',
                    'user_role_options'               => $this->_helper_functions->get_default_allowed_user_roles(),
                    'cart_condition_fields'           => array(),
                    'help_modal'                      => array(
                        'is_premium'    => function_exists('ACFWP'),
                        'allow_fetch'   => get_option(Plugin_Constants::ALLOW_FETCH_CONTENT_REMOTE) === "yes",
                        'help_text'     => __('Help', 'advanced-coupons-for-woocommerce-free'),
                        'images_url'    => $this->_constants->IMAGES_ROOT_URL(),
                        '_secure_nonce' => wp_create_nonce('acfw_fetch_help_data'),
                        'json_base_url' => 'https://plugin.advancedcouponsplugin.com/help/',
                        'labels'        => array(
                            'loading_content'    => __('Loading help content…', 'advanced-coupons-for-woocommerce-free'),
                            'loading_videos'     => __('Loading videos…', 'advanced-coupons-for-woocommerce-free'),
                            'rel_links'          => __('Relevant Links', 'advanced-coupons-for-woocommerce-free'),
                            'kb_articles'        => __('Knowledge Base Articles', 'advanced-coupons-for-woocommerce-free'),
                            'tut_guides'         => __('Tutorials & Guides', 'advanced-coupons-for-woocommerce-free'),
                            'search_placeholder' => __('Search Knowledge Base', 'advanced-coupons-for-woocommerce-free'),
                            'search_no_results'  => __('No results', 'advanced-coupons-for-woocommerce-free'),
                            'upgrade_to_premium' => __('Upgrade To Premium', 'advanced-coupons-for-woocommerce-free'),
                            'permission_request' => __('Grant permission to fetch and show help content. Click the button below to proceed.', 'advanced-coupons-for-woocommerce-free'),
                            'allow'              => __('Show Help Content', 'advanced-coupons-for-woocommerce-free'),
                            'cancel'             => __('Cancel', 'advanced-coupons-for-woocommerce-free'),
                            'no_video'           => __('There was an issue trying to embed the video. Please click the image to watch the video directly in youtube.com', 'advanced-coupons-for-woocommerce-free'),
                        ),
                    ),
                ))
            );

        }

        // enqueue script to replace icons in wc-admin marketing.
        if ('woocommerce_page_wc-admin' === $screen->id || 'edit-shop_coupon' === $screen->id) {
            wp_enqueue_script('acfw-wc-admin-icons', $this->_constants->JS_ROOT_URL() . 'acfw-wc-admin-icons.js', array('jquery'), Plugin_Constants::VERSION, true);
            wp_localize_script('acfw-wc-admin-icons', 'acfwAdminIcons', array(
                'imgUrl' => $this->_constants->IMAGES_ROOT_URL(),
            ));
        }

        // enqueue script in edit order page
        if ('post' == $screen->base && 'shop_order' == $screen->id && 'shop_order' == $post_type) {

            $order = wc_get_order($post->ID);

            // only enqueue the script when order is assigned to a customer with account.
            if ($this->_helper_functions->is_module(Plugin_Constants::STORE_CREDITS_MODULE) && $order->get_customer_id()) {
                wp_enqueue_script('acfw-edit-order');
                wp_localize_script('acfw-edit-order', 'acfwEditOrder', array(
                    'button_text' => sprintf(
                        __('Refund <span class="amount">%s</span> to Store Credits', 'advanced-coupons-for-woocommerce-free'),
                        $this->_helper_functions->api_wc_price(0.0, array('currency' => $order->get_currency()))
                    ),
                ));
            }
        }

        do_action('acfw_after_load_backend_scripts', $screen, $post_type);
    }

    /*
    |--------------------------------------------------------------------------
    | Frontend Scripts
    |--------------------------------------------------------------------------
     */

    /**
     * Load frontend js and css scripts.
     *
     * @since 1.0
     * @access public
     */
    public function load_frontend_scripts()
    {
        // jquery webui popover
        wp_register_style('jquery-webui-popover', $this->_constants->JS_ROOT_URL() . 'lib/webui-popover/jquery.webui-popover.min.css', array(), Plugin_Constants::VERSION, 'all');
        wp_register_script('jquery-webui-popover', $this->_constants->JS_ROOT_URL() . 'lib/webui-popover/jquery.webui-popover.min.js', array('jquery'), Plugin_Constants::VERSION, true);

        if (is_checkout()) {

            wp_enqueue_style('acfwf-checkout', $this->_constants->CSS_ROOT_URL() . 'acfwf-checkout.css', array('jquery-webui-popover'), Plugin_Constants::VERSION, 'all');
            wp_enqueue_script('acfwf-checkout', $this->_constants->JS_ROOT_URL() . 'acfwf-checkout.js', array('jquery', 'jquery-webui-popover'), Plugin_Constants::VERSION, true);
            wp_localize_script('acfwf-checkout', 'acfwfCheckout', array(
                'decimal_separator'  => wc_get_price_decimal_separator(),
                'thousand_separator' => wc_get_price_thousand_separator(),
                'redeem_nonce'       => wp_create_nonce('acfwf_redeem_store_credits_checkout'),
                'enter_valid_price'  => __('Please enter a valid price', 'advanced-coupons-for-woocommerce-free'),
            ));
        }

        if (is_account_page()) {
            wp_enqueue_style('acfwf-my-account', $this->_constants->CSS_ROOT_URL() . 'acfw-my-account.css', array(), Plugin_Constants::VERSION, 'all');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Gutenberg scripts
    |--------------------------------------------------------------------------
     */

    /**
     * Load script and styles for gutenberg editor.
     *
     * @since 3.1
     * @access public
     */
    public function load_gutenberg_editor_scripts()
    {
        $edit_block_assets = include $this->_constants->JS_ROOT_PATH() . 'app/blocks/dist/acfwf-blocks.asset.php';

        wp_enqueue_style(
            'acfwf-blocks-edit',
            $this->_constants->JS_ROOT_URL() . 'app/blocks/dist/acfwf-blocks.css',
            array(),
            $edit_block_assets['version'],
            "all"
        );

        wp_enqueue_script(
            'acfwf-blocks-edit',
            $this->_constants->JS_ROOT_URL() . 'app/blocks/dist/acfwf-blocks.js',
            $edit_block_assets['dependencies'],
            $edit_block_assets['version']
        );

        $is_premium = $this->_helper_functions->is_plugin_active(Plugin_Constants::PREMIUM_PLUGIN);

        wp_localize_script('acfwf-blocks-edit', 'acfwfBlocksi18n', array(
            'isPremium'                    => $is_premium,
            'defaultCategory'              => (int) get_option(Plugin_Constants::DEFAULT_COUPON_CATEGORY),
            'couponsCategoryTexts'         => array(
                'title'            => __('Advanced Coupons by Category', 'advanced-coupons-for-woocommerce-free'),
                'description'      => __('Display a grid of advanced coupons from your selected categories.', 'advanced-coupons-for-woocommerce-free'),
                'emptyDesc'        => __('No coupons were found that matched your selection.', 'advanced-coupons-for-woocommerce-free'),
                'selectDesc'       => __('Select at least one category to display its coupons.', 'advanced-coupons-for-woocommerce-free'),
                'searchMessages'   => array(
                    'clear'   => __("Clear all coupon categories", 'advanced-coupons-for-woocommerce-free'),
                    'list'    => __("Coupon Categories", 'advanced-coupons-for-woocommerce-free'),
                    'noItems' => __("Your store doesn't have any coupon categories", 'advanced-coupons-for-woocommerce-free'),
                    'search'  => __("Search for coupon categories", 'advanced-coupons-for-woocommerce-free'),
                ),
                'selectCategories' => __('Select categories', 'advanced-coupons-for-woocommerce-free'),
            ),
            'couponsCustomerTexts'         => array(
                'title'       => $is_premium ? __('Advanced Coupons by Customer', 'advanced-coupons-for-woocommerce-free') : __('Advanced Coupons by Customer (Premium)', 'advanced-coupons-for-woocommerce-free'), // add (premium) tag for upsell.
                'description' => __('Display a grid of coupons and/or virtual coupons that is assigned to a customer.', 'advanced-coupons-for-woocommerce-free'),
                'emptyDesc'   => __('No coupons were found for the currently logged in customer.', 'advanced-coupons-for-woocommerce-free'),
            ),
            'singleCouponTexts'            => array(
                'title'       => __('Single Advanced Coupon', 'advanced-coupons-for-woocommerce-free'),
                'description' => __("Display a single advanced coupon that you've selected.", 'advanced-coupons-for-woocommerce-free'),
                'emptyDesc'   => __('No coupon to display.', 'advanced-coupons-for-woocommerce-free'),
                'selectDesc'  => __('Search and select a coupon to display it.', 'advanced-coupons-for-woocommerce-free'),
            ),
            'orderTypeFieldTexts'          => array(
                'label'   => __('Order by', 'advanced-coupons-for-woocommerce-free'),
                'options' => array(
                    'newestToOldest'   => __('Newest to oldest', 'advanced-coupons-for-woocommerce-free'),
                    'oldestToNewest'   => __('Oldest to newest', 'advanced-coupons-for-woocommerce-free'),
                    'aToZ'             => __('A → Z', 'advanced-coupons-for-woocommerce-free'),
                    'zToA'             => __('Z → A', 'advanced-coupons-for-woocommerce-free'),
                    'earliestToExpire' => __('Earliest to expire', 'advanced-coupons-for-woocommerce-free'),
                ),
            ),
            'displayTypeFieldTexts'        => array(
                'label'   => __('Display type', 'advanced-coupons-for-woocommerce-free'),
                'options' => array(
                    'couponsAndVirtualCoupons' => __('Coupons and virtual coupons', 'advanced-coupons-for-woocommerce-free'),
                    'couponsOnly'              => __('Coupons only', 'advanced-coupons-for-woocommerce-free'),
                    'virtualCouponsOnly'       => __('Virtual coupons only', 'advanced-coupons-for-woocommerce-free'),
                ),
            ),
            'numberofItemsFieldLabel'      => __('Number of items', 'advanced-coupons-for-woocommerce-free'),
            'numberofColumnsFieldLabel'    => __('Number of columns', 'advanced-coupons-for-woocommerce-free'),
            'contentDisplaySettings'       => array(
                'title'                => __('Content display settings', 'advanced-coupons-for-woocommerce-free'),
                'displayDiscountValue' => __('Display discount value', 'advanced-coupons-for-woocommerce-free'),
                'displayDescription'   => __('Display description', 'advanced-coupons-for-woocommerce-free'),
                'displayUsageLimit'    => __('Display usage limit', 'advanced-coupons-for-woocommerce-free'),
                'displaySchedule'      => __('Display schedule', 'advanced-coupons-for-woocommerce-free'),
            ),
            'currentlySelectedCouponLabel' => __('Currently selected coupon:', 'advanced-coupons-for-woocommerce-free'),
            'searchAndSelectCouponLabel'   => __('Search and select coupon', 'advanced-coupons-for-woocommerce-free'),
            'premiumUpsellMessage'         => sprintf(
                __('This block is only available in the <a href="%s" target="_blank" rel="noopener noreferer">Premium add-on for Advanced Coupons.</a>', 'advanced-coupons-for-woocommerce-free'),
                'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=upsell&utm_campaign=gutenberg'
            ),

            'emptyCouponSearch'            => __('No coupons found.', 'advanced-coupons-for-woocommerce-free'),
            'doneBtnText'                  => __('Done', 'advanced-coupons-for-woocommerce-free'),
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Fulfill implemented interface contracts
    |--------------------------------------------------------------------------
     */

    /**
     * Execute plugin script loader.
     *
     * @since 1.0
     * @access public
     * @inherit ACFWF\Interfaces\Model_Interface
     */
    public function run()
    {
        add_action('admin_enqueue_scripts', array($this, 'load_backend_scripts'), 10, 1);
        add_action('wp_enqueue_scripts', array($this, 'load_frontend_scripts'));

        add_action('enqueue_block_editor_assets', array($this, 'load_gutenberg_editor_scripts'));
    }

}
