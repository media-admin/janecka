<?php
namespace ACFWF\Models;

use ACFWF\Abstracts\Abstract_Main_Plugin_Class;
use ACFWF\Helpers\Helper_Functions;
use ACFWF\Helpers\Plugin_Constants;
use ACFWF\Interfaces\Activatable_Interface;
use ACFWF\Interfaces\Initializable_Interface;
use ACFWF\Interfaces\Model_Interface;
use ACFWF\Models\Cart_Conditions;
use ACFWF\Models\Objects\Advanced_Coupon;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

/**
 * Model that houses the logic of handling the interface of adding/editng the Advanced Coupon features.
 * Public Model.
 *
 * @since 1.0
 */
class Edit_Coupon implements Model_Interface, Initializable_Interface, Activatable_Interface
{

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
     */

    /**
     * Property that holds the single main instance of Edit_Coupon.
     *
     * @since 1.0
     * @access private
     * @var Edit_Coupon
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
     * Property that houses the main instance of Cart_Conditions.
     *
     * @since 1.0
     * @access private
     * @var Cart_Conditions
     */
    private $_cart_conditions;

    /**
     * Property that houses all the helper functions of the plugin.
     *
     * @since 1.0
     * @access private
     * @var Helper_Functions
     */
    private $_helper_functions;

    /**
     * Property that holds the shared Advanced_Coupon object.
     *
     * @since 1.0
     * @access private
     */
    private $_advanced_coupon;

    /**
     * Property that holds the shared default category object
     *
     * @since 1.10
     * @access private
     * @var WP_Term
     */
    private $_default_category;

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
    public function __construct(Abstract_Main_Plugin_Class $main_plugin, Plugin_Constants $constants, Helper_Functions $helper_functions, Cart_Conditions $cart_conditions)
    {
        $this->_constants        = $constants;
        $this->_helper_functions = $helper_functions;
        $this->_cart_conditions  = $cart_conditions;
        $this->_coupon_endpoint  = $this->_helper_functions->get_coupon_url_endpoint();
        $this->_coupon_base_url  = home_url('/') . $this->_coupon_endpoint;

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
     * @return Edit_Coupon
     */
    public static function get_instance(Abstract_Main_Plugin_Class $main_plugin, Plugin_Constants $constants, Helper_Functions $helper_functions, Cart_Conditions $cart_conditions)
    {
        if (!self::$_instance instanceof self) {
            self::$_instance = new self($main_plugin, $constants, $helper_functions, $cart_conditions);
        }

        return self::$_instance;

    }

    /**
     * Get shared Advanced_Coupon object.
     *
     * @since 1.0
     * @access public
     *
     * @param int $coupon_id Advanced_Coupon id.
     * @return Advanced_Coupon object.
     */
    public function get_shared_advanced_coupon($coupon_id)
    {
        if (is_object($this->_advanced_coupon) && $this->_advanced_coupon->get_id() == $coupon_id) {
            return $this->_advanced_coupon;
        }

        // if ACFWP is active then get advanced coupon object from it, otherwise return ACFWF Advanced_Coupon object.
        if (function_exists('ACFWP')) {
            return $this->_advanced_coupon = ACFWP()->Helper_Functions->get_advanced_coupon($coupon_id);
        } else {
            return $this->_advanced_coupon = new Advanced_Coupon($coupon_id);
        }

    }

    /**
     * Override the parent file set for screens that are related for the 'shop_coupon' post type.
     *
     * @since 1.2
     * @access public
     *
     * @param string $parent_file Parent file name
     * @return string Filtered parent file name.
     */
    public function override_coupon_categories_parent_file($parent_file)
    {
        $screen = get_current_screen();

        $post_type = $screen->post_type;
        if (!$post_type && isset($_GET['post_type'])) {
            $post_type = $_GET['post_type'];
        }

        if (is_admin() && isset($_GET['taxonomy']) && Plugin_Constants::COUPON_CAT_TAXONOMY === $_GET['taxonomy']) {
            $parent_file = 'acfw-admin';
        }

        return $parent_file;
    }

    /**
     * Add "Coupons" top level menu and transfer all coupon related submenus to it.
     *
     * @since 1.2
     * @access public
     *
     * @global $submenu Global submenu list.
     */
    public function add_coupon_admin_menus()
    {
        global $submenu;

        // if woocommerce menu is present for current user then don't proceed.
        if (!isset($submenu['woocommerce']) || !is_array($submenu['woocommerce'])) {
            return;
        }

        $toplevel_slug = 'acfw-admin';

        // filter out all coupon categories related menus under WooCommerce.
        $wc_coupon_submenus = array_filter($submenu['woocommerce'], function ($s) {
            return strpos($s[2], Plugin_Constants::COUPON_CAT_TAXONOMY) !== false;
        });

        // remove all coupon categories related submenus under WooCommerce.
        $submenu['woocommerce'] = array_filter($submenu['woocommerce'], function ($s) use ($wc_coupon_submenus) {
            return !in_array($s, $wc_coupon_submenus);
        });

        add_menu_page(
            '',
            __('Coupons', 'advanced-coupons-for-woocommerce-free'),
            'edit_shop_coupons',
            $toplevel_slug,
            '',
            'dashicons-tickets-alt',
            '55.51'
        );

        add_submenu_page(
            $toplevel_slug,
            '',
            __('All Coupons', 'advanced-coupons-for-woocommerce-free'),
            'edit_shop_coupons',
            'edit.php?post_type=shop_coupon&acfw'
        );

        add_submenu_page(
            $toplevel_slug,
            '',
            __('Add New', 'advanced-coupons-for-woocommerce-free'),
            'edit_shop_coupons',
            'post-new.php?post_type=shop_coupon&acfw'
        );

        add_submenu_page(
            $toplevel_slug,
            '',
            __('Coupon Categories', 'advanced-coupons-for-woocommerce-free'),
            'edit_shop_coupons',
            'edit-tags.php?taxonomy=' . Plugin_Constants::COUPON_CAT_TAXONOMY . '&amp;post_type=shop_coupon'
        );

        if ($this->_helper_functions->is_wc_admin_active() && function_exists('wc_admin_connect_page')) {

            wc_admin_connect_page(
                array(
                    'id'        => 'shop_coupon_cat',
                    'title'     => __('Advanced Coupons', 'advanced-coupons-for-woocommerce-free'),
                    'screen_id' => 'edit-shop_coupon_cat',
                    'path'      => 'edit-tags.php?post_type=shop_coupon&taxonomy=' . Plugin_Constants::COUPON_CAT_TAXONOMY,
                    'js_page'   => false,
                )
            );
        }

        // unset the first submenu entry created by add_menu_page.
        unset($submenu[$toplevel_slug][0]);

        do_action('acfw_register_admin_submenus', $toplevel_slug);
    }

    /**
     * Set custom icon style for Coupon top level admin menu.
     *
     * @since 1.2
     * @access public
     */
    public function coupon_admin_menu_icon_css()
    {
        ?>
        <style type="text/css">
        #toplevel_page_acfw-admin .wp-menu-image:before {
            font-family: WooCommerce !important;
            content: '\e600';
        }
        </style>
        <?php
}

    /**
     * Add coupons list in order preview popup.
     *
     * @deprecated 1.4.1
     *
     * @since 1.3
     * @access public
     *
     * @param array    $data  Order preview data.
     * @param WC_Order $order Order object.
     */
    public function add_coupons_list_in_order_preview_popup($data, $order)
    {
        wc_doing_it_wrong(__METHOD__, __('Edit_Coupon::add_coupons_list_in_order_preview_popup method is now deprecated. Please use Order_Details::add_coupons_list_in_order_preview_popup method instead.', 'advanced-coupons-for-woocommerce-free'), '1.4.1');
        return \ACFWF()->Order_Details->add_coupons_list_in_order_preview_popup($data, $order);
    }

    /*
    |--------------------------------------------------------------------------
    | URL Coupons Data
    |--------------------------------------------------------------------------
     */

    /**
     * Add new url coupon data tab to woocommerce coupon admin data tabs.
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $coupon_data_tabs Array of coupon admin data tabs.
     * @return array Modified array of coupon admin data tabs.
     */
    public function url_coupons_admin_data_tab($coupon_data_tabs)
    {
        $coupon_data_tabs['acfw_url_coupon'] = array(
            'label'  => __('URL Coupons', 'advanced-coupons-for-woocommerce-free'),
            'target' => 'acfw_url_coupon',
            'class'  => '',
        );

        return $coupon_data_tabs;
    }

    /**
     * Add url cuopun data panel to woocommerce coupon admin data panels.
     *
     * @since 1.0.0
     * @access public
     *
     * @param int $coupon_id WC_Coupon ID.
     */
    public function url_coupons_admin_data_panel($coupon_id)
    {

        $panel_id           = 'acfw_url_coupon';
        $help_slug          = 'url-coupons';
        $descriptions       = array(__("Allow your customers to apply this coupon by visiting a URL. This coupon will generate a unique coupon URL which can be used in all sorts of scenarios (eg. email marketing, blog links, live chat support).", 'advanced-coupons-for-woocommerce-free'));
        $coupon             = $this->get_shared_advanced_coupon($coupon_id);
        $url_warning        = $this->_get_special_symbols_warning_text($coupon);
        $additional_classes = 'toggle-enable-fields';
        $title              = __('URL Coupons', 'advanced-coupons-for-woocommerce-free');
        $kb_link            = sprintf(__('<a href="%s" target="_blank">Learn more.</a>', 'advanced-coupons-for-woocommerce-free'), 'https://advancedcouponsplugin.com/knowledgebase/how-to-use-the-coupon-url/?utm_source=acfwf&utm_medium=help_modal&utm_campaign=kb_article&utm_term=how-to-use-the-coupon-url');
        $fields             = apply_filters('acfw_url_coupons_admin_data_panel_fields', array(
            array(
                'cb'   => 'woocommerce_wp_checkbox',
                'args' => array(
                    'id'          => Plugin_Constants::META_PREFIX . 'enable_coupon_url',
                    'label'       => __('Enable Coupon URL', 'advanced-coupons-for-woocommerce-free'),
                    'class'       => 'toggle-trigger-field',
                    'description' => __('When checked, it enables the coupon url functionality for the current coupon.', 'advanced-coupons-for-woocommerce-free'),
                    'value'       => $coupon->get_advanced_prop_edit('disable_url_coupon', 'no') === 'yes' ? 'no' : 'yes',
                ),
            ),
            array(
                'cb'   => 'woocommerce_wp_text_input',
                'args' => array(
                    'id'                => Plugin_Constants::META_PREFIX . 'coupon_url',
                    'style'             => 'width: 50%;box-shadow: 0 1px 0 #ccc;box-sizing: border-box;height: 26px;vertical-align: top;',
                    'label'             => __('Coupon URL', 'advanced-coupons-for-woocommerce-free'),
                    'description'       => $url_warning . __('<br>Visitors to this link will have the coupon code applied to their cart automatically.', 'advanced-coupons-for-woocommerce-free') . '<br/>' . $kb_link,
                    'type'              => 'url',
                    'data_type'         => 'url',
                    'value'             => $coupon->get_coupon_url(),
                    'custom_attributes' => array('readonly' => true),
                ),
            ),
            array(
                'cb'   => 'woocommerce_wp_text_input',
                'args' => array(
                    'id'          => Plugin_Constants::META_PREFIX . 'code_url_override',
                    'label'       => __('Code URL Override', 'advanced-coupons-for-woocommerce-free'),
                    'description' => __('Customize the coupon code on the coupon url. Leave blank to disable feature.', 'advanced-coupons-for-woocommerce-free'),
                    'desc_tip'    => true,
                    'type'        => 'text',
                    'data_type'   => 'text',
                    'value'       => $coupon->get_advanced_prop_edit('code_url_override'),
                ),
            ),
            array(
                'cb'        => 'woocommerce_wp_checkbox',
                'args'      => array(
                    'id'          => Plugin_Constants::META_PREFIX . 'force_apply_url_coupon',
                    'label'       => __('Force Apply', 'advanced-coupons-for-woocommerce-free'),
                    'description' => __('When checked, the coupon will be forcefully applied by removing all auto-applied coupons that is set to be excluded with this coupon.', 'advanced-coupons-for-woocommerce-free'),
                    'value'       => $coupon->get_advanced_prop_edit('force_apply_url_coupon'),
                ),
                'condition' => $this->_helper_functions->is_plugin_active(Plugin_Constants::PREMIUM_PLUGIN),
            ),
            array(
                'cb'   => 'woocommerce_wp_text_input',
                'args' => array(
                    'id'          => Plugin_Constants::META_PREFIX . 'after_redirect_url',
                    'label'       => __('Redirect To URL', 'advanced-coupons-for-woocommerce-free'),
                    'description' => __("This will redirect the user to the provided URL after it has been attempted to be applied. You can also pass query args to the URL for the following variables: {acfw_coupon_code}, {acfw_coupon_is_applied} or {acfw_coupon_error_message} and they will be replaced with proper data. Eg. ?foo={acfw_coupon_error_message}, then test the 'foo' query arg to get the message if there is one.", 'advanced-coupons-for-woocommerce-free'),
                    'desc_tip'    => true,
                    'type'        => 'text',
                    'data_type'   => 'text',
                    'value'       => $coupon->get_advanced_prop_edit('after_redirect_url'),
                    'placeholder' => $this->_helper_functions->get_option(Plugin_Constants::AFTER_APPLY_COUPON_REDIRECT_URL_GLOBAL, wc_get_cart_url()),
                ),
            ),
            array(
                'cb'   => 'woocommerce_wp_textarea_input',
                'args' => array(
                    'id'          => Plugin_Constants::META_PREFIX . 'success_message',
                    'label'       => __('Custom Success Message', 'advanced-coupons-for-woocommerce-free'),
                    'description' => __('Message that will be displayed when a coupon has been applied successfully. Leave blank to use the default message.', 'advanced-coupons-for-woocommerce-free'),
                    'desc_tip'    => true,
                    'type'        => 'text',
                    'data_type'   => 'text',
                    'placeholder' => $this->_helper_functions->get_option(Plugin_Constants::CUSTOM_SUCCESS_MESSAGE_GLOBAL, __('Coupon applied successfully', 'advanced-coupons-for-woocommerce-free')),
                    'value'       => $coupon->get_advanced_prop_edit('success_message'),
                ),
            ),
        ));

        $fields = array_filter($fields, function ($f) {
            return !isset($f['condition']) || $f['condition'];
        });

        include $this->_constants->VIEWS_ROOT_PATH() . 'coupons' . DIRECTORY_SEPARATOR . 'view-generic-admin-data-panel.php';

    }

    /**
     * Get the warning text when coupon code has special symbols that can't be supported in the URL.
     *
     * @since 1.3
     * @access private
     *
     * @param Advanced_Coupon $coupon Coupon object.
     * @return string Warning text when need to be showned or empty string.
     */
    private function _get_special_symbols_warning_text($coupon)
    {
        if (!preg_match('/[\'"£%\/\]\[><>?#,:]/', $coupon->get_code()) || $coupon->get_advanced_prop('code_url_override')) {
            return '';
        }

        return sprintf(
            '<span class="acfw-warn-url-coupon">%s</span>',
            __("<strong>Warning:</strong> The URL for this coupon may not work as it contains special symbols. Please remove it from the coupon code or use the code override field below.", 'advanced-coupons-for-woocommerce-free')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Role Restrictions Data
    |--------------------------------------------------------------------------
     */

    /**
     * Add new role restriction data tab to woocommerce coupon admin data tabs.
     *
     * @since 1.0
     * @access public
     *
     * @param array $coupon_data_tabs Array of coupon admin data tabs.
     * @return array Modified array of coupon admin data tabs.
     */
    public function role_restriction_admin_data_tab($coupon_data_tabs)
    {
        $coupon_data_tabs['acfw_role_restrictions'] = array(
            'label'  => __('Role Restrictions', 'advanced-coupons-for-woocommerce-free'),
            'target' => 'acfw_role_restrictions',
            'class'  => '',
        );

        return $coupon_data_tabs;
    }

    /**
     * Add scheduler data panel to woocommerce coupon admin data panels.
     *
     * @since 1.0
     * @access public
     *
     * @param int $coupon_id WC_Coupon ID.
     */
    public function role_restriction_admin_data_panel($coupon_id)
    {
        $panel_id           = 'acfw_role_restrictions';
        $help_slug          = 'role-restrictions';
        $descriptions       = array(__("Role restrictions are tested prior to a coupon being applied and will stop customers using this coupon if they don’t satisfy the user role rules below.", 'advanced-coupons-for-woocommerce-free'));
        $coupon             = $this->get_shared_advanced_coupon($coupon_id);
        $additional_classes = 'toggle-enable-fields';
        $title              = __('Role Restrictions', 'advanced-coupons-for-woocommerce-free');
        $fields             = apply_filters('acfw_role_restrictions_admin_data_panel_fields', array(

            array(
                'cb'   => 'woocommerce_wp_checkbox',
                'args' => array(
                    'id'          => Plugin_Constants::META_PREFIX . 'enable_role_restriction',
                    'class'       => 'toggle-trigger-field',
                    'label'       => __('Enable role restrictions', 'advanced-coupons-for-woocommerce-free'),
                    'description' => __('When checked, will enable role restrictions check when coupon is applied', 'advanced-coupons-for-woocommerce-free'),
                    'value'       => $coupon->get_advanced_prop_edit('enable_role_restriction'),
                ),
            ),
            array(
                'cb'   => 'woocommerce_wp_select',
                'args' => array(
                    'id'          => Plugin_Constants::META_PREFIX . 'role_restrictions_type',
                    'style'       => 'width:50%;',
                    'label'       => __('Type', 'advanced-coupons-for-woocommerce-free'),
                    'description' => __('The type of implementation for this restriction. Select "allowed" to allow coupon only to users under the selected roles. Select "disallowed" to only allow coupon to users that don\'t fall under the selected roles.', 'advanced-coupons-for-woocommerce-free'),
                    'desc_tip'    => true,
                    'type'        => 'select',
                    'options'     => array(
                        'allowed'    => __('Allowed', 'advanced-coupons-for-woocommerce-free'),
                        'disallowed' => __('Disallowed', 'advanced-coupons-for-woocommerce-free'),
                    ),
                    'value'       => $coupon->get_advanced_prop_edit('role_restrictions_type'),
                ),
            ),
            array(
                'cb'   => array($this, 'acfw_multiselect_field'),
                'args' => array(
                    'id'          => Plugin_Constants::META_PREFIX . 'role_restrictions',
                    'class'       => 'wc-enhanced-select',
                    'style'       => 'width:50%;',
                    'label'       => __('User Roles', 'advanced-coupons-for-woocommerce-free'),
                    'description' => __('The user roles that should/shouldn\'t have access to this coupon. Make sure you include admin and shop manager roles if you want them to be able to test this coupon. Guests are defined as logged out users.', 'advanced-coupons-for-woocommerce-free'),
                    'desc_tip'    => true,
                    'type'        => 'text',
                    'options'     => $this->_helper_functions->get_default_allowed_user_roles(),
                    'value'       => $coupon->get_advanced_prop_edit('role_restrictions'),
                ),

            ),
            array(
                'cb'   => 'woocommerce_wp_textarea_input',
                'args' => array(
                    'id'          => Plugin_Constants::META_PREFIX . 'role_restrictions_error_msg',
                    'label'       => __('Invalid role error message', 'advanced-coupons-for-woocommerce-free'),
                    'description' => __('The message that should be displayed to users if they are not allowed to apply this coupon.', 'advanced-coupons-for-woocommerce-free'),
                    'desc_tip'    => true,
                    'type'        => 'text',
                    'placeholder' => $this->_helper_functions->get_option(Plugin_Constants::ROLE_RESTRICTIONS_ERROR_MESSAGE, __('You are not allowed to use this coupon.', 'advanced-coupons-for-woocommerce-free')),
                    'value'       => $coupon->get_advanced_prop_edit('role_restrictions_error_msg'),
                ),
            ),
        ));

        include $this->_constants->VIEWS_ROOT_PATH() . 'coupons' . DIRECTORY_SEPARATOR . 'view-generic-admin-data-panel.php';

    }

    /*
    |--------------------------------------------------------------------------
    | Cart conditions data
    |--------------------------------------------------------------------------
     */

    /**
     * Register cart conditions metabox.
     *
     * @since 1.0
     * @access public
     *
     * @param string  $post_type Post type.
     * @param WP_Post $post      Post object.
     */
    public function register_cart_conditions_metabox($post_type, $post)
    {
        if ('shop_coupon' !== $post_type) {
            return;
        }

        add_meta_box(
            'acfw-cart-conditions',
            __('Cart Conditions', 'advanced-coupons-for-woocommerce-free'),
            array($this, 'cart_conditions_admin_data_panel'),
            'shop_coupon',
            'normal',
            'low'
        );
    }

    /**
     * Add cart conditions data panel to woocommerce coupon admin data panels.
     *
     * @since 1.0
     * @access public
     *
     * @param int $coupon_id WC_Coupon ID.
     */
    public function cart_conditions_admin_data_panel($post)
    {
        $coupon_id             = $post->ID;
        $panel_id              = 'acfw_cart_conditions';
        $coupon                = $this->get_shared_advanced_coupon($coupon_id);
        $spinner_img           = $this->_constants->IMAGES_ROOT_URL() . 'spinner-2x.gif';
        $notice_settings       = $coupon->get_advanced_prop_edit('cart_condition_notice');
        $cart_conditions_model = $this->_cart_conditions;
        $is_premium_active     = $this->_helper_functions->is_plugin_active(Plugin_Constants::PREMIUM_PLUGIN);

        $nqm_placeholder = __("Your current cart hasn't met the conditions set for this coupon.", 'advanced-coupons-for-woocommerce-free');
        $notice_message  = isset($notice_settings['message']) ? $notice_settings['message'] : '';
        $notice_btn_text = isset($notice_settings['btn_text']) ? $notice_settings['btn_text'] : '';
        $notice_btn_url  = isset($notice_settings['btn_url']) ? $notice_settings['btn_url'] : '';

        $panel_data_atts = apply_filters('acfw_cart_conditions_panel_data_atts', array(
            'cart_conditions' => $coupon->get_formatted_cart_conditions_edit(),
        ));

        $tabs = apply_filters('acfw_cart_condition_panel_tabs', array(
            'rules'    => __('Rules', 'advanced-coupons-for-woocommerce-free'),
            'settings' => __('Non-Qualifying Settings', 'advanced-coupons-for-woocommerce-free'),
        ));

        include $this->_constants->VIEWS_ROOT_PATH() . 'coupons' . DIRECTORY_SEPARATOR . 'view-cart-conditions-data-panel.php';
    }

    /*
    |--------------------------------------------------------------------------
    | BOGO Deals Data
    |--------------------------------------------------------------------------
     */

    /**
     * Add new "add free products" data tab to woocommerce coupon admin data tabs.
     *
     * @deprecated 3.0
     * @since 1.0
     * @access public
     *
     *
     * @param array $coupon_data_tabs Array of coupon admin data tabs.
     * @return array Modified array of coupon admin data tabs.
     */
    public function bogo_deals_admin_data_tab($coupon_data_tabs)
    {
        \wc_deprecated_function('Edit_Coupon::' . __FUNCTION__, '3.0');
        return $coupon_data_tabs;
    }

    /**
     * Add "BOGO deals" data panel to woocommerce coupon admin data panels.
     *
     * @since 1.0
     * @access public
     *
     * @param int $coupon_id WC_Coupon ID.
     */
    public function bogo_deals_admin_data_panel($coupon_id)
    {
        $panel_id        = 'acfw_bogo_deals';
        $coupon          = $this->get_shared_advanced_coupon($coupon_id);
        $bogo_deals      = $coupon->get_formatted_bogo_deals_edit();
        $cond_type       = isset($bogo_deals['conditions_type']) ? $bogo_deals['conditions_type'] : '';
        $deals_type      = isset($bogo_deals['deals_type']) ? $bogo_deals['deals_type'] : '';
        $deals           = isset($bogo_deals['deals']) ? $bogo_deals['deals'] : array();
        $type            = isset($bogo_deals['type']) ? $bogo_deals['type'] : 'once';
        $spinner_img     = $this->_constants->IMAGES_ROOT_URL() . 'spinner-2x.gif';
        $notice_message  = isset($bogo_deals['notice_settings']['message']) ? $bogo_deals['notice_settings']['message'] : '';
        $notice_btn_text = isset($bogo_deals['notice_settings']['button_text']) ? $bogo_deals['notice_settings']['button_text'] : '';
        $notice_btn_url  = isset($bogo_deals['notice_settings']['button_url']) ? $bogo_deals['notice_settings']['button_url'] : '';
        $notice_type     = isset($bogo_deals['notice_settings']['notice_type']) ? $bogo_deals['notice_settings']['notice_type'] : 'global';

        $notice_types = array(
            'notice'  => __('Info', 'advanced-coupons-for-woocommerce-free'),
            'success' => __('Success', 'advanced-coupons-for-woocommerce-free'),
            'error'   => __('Error', 'advanced-coupons-for-woocommerce-free'),
        );

        // global variables.
        $global_notice_message   = $this->_helper_functions->get_option(Plugin_Constants::BOGO_DEALS_NOTICE_MESSAGE, __("Your current cart is eligible to redeem deals", 'advanced-coupons-for-woocommerce-free'));
        $global_notice_btn_text  = $this->_helper_functions->get_option(Plugin_Constants::BOGO_DEALS_NOTICE_BTN_TEXT, __("View Deals", 'advanced-coupons-for-woocommerce-free'));
        $global_notice_btn_url   = $this->_helper_functions->get_option(Plugin_Constants::BOGO_DEALS_NOTICE_BTN_URL, get_permalink(wc_get_page_id('shop')));
        $global_notice_type      = $this->_helper_functions->get_option(Plugin_Constants::BOGO_DEALS_NOTICE_TYPE, 'notice');
        $globa_notice_type_label = $global_notice_type ? $notice_types[$global_notice_type] : '';

        $trigger_type_options = apply_filters('acfw_bogo_trigger_type_options', array(
            'specific-products' => __('Specific Product/s', 'advanced-coupons-for-woocommerce-free'),
        ));

        $apply_type_options = apply_filters('acfw_bogo_apply_type_options', array(
            'specific-products' => __('Specific Product/s', 'advanced-coupons-for-woocommerce-free'),
        ), true);

        include $this->_constants->VIEWS_ROOT_PATH() . 'coupons' . DIRECTORY_SEPARATOR . 'view-bogo-deals-data-panel.php';
    }

    /*
    |--------------------------------------------------------------------------
    | Save Coupon
    |--------------------------------------------------------------------------
     */

    /**
     * Update Advanced coupon data whenever the coupon is saved.
     *
     * @since 1.0
     * @access public
     *
     * @param int $post_id Id of the coupon post.
     */
    public function save_url_coupons_data($coupon_id)
    {
        if (!$this->_helper_functions->check_if_valid_save_post_action($coupon_id, 'shop_coupon')) {
            return;
        }

        $coupon       = $this->get_shared_advanced_coupon($coupon_id);
        $allowed_html = wp_kses_allowed_html('post');

        do_action('acfw_before_save_coupon', $coupon->get_id(), $coupon);

        // URL Coupons meta
        if ($this->_helper_functions->is_module(Plugin_Constants::URL_COUPONS_MODULE)) {

            $enable_coupon_url  = isset($_POST[Plugin_Constants::META_PREFIX . 'enable_coupon_url']) ? sanitize_text_field($_POST[Plugin_Constants::META_PREFIX . 'enable_coupon_url']) : '';
            $force_apply_coupon = isset($_POST[Plugin_Constants::META_PREFIX . 'force_apply_url_coupon']) ? sanitize_text_field($_POST[Plugin_Constants::META_PREFIX . 'force_apply_url_coupon']) : '';
            $code_url_override  = isset($_POST[Plugin_Constants::META_PREFIX . 'code_url_override']) ? sanitize_title($_POST[Plugin_Constants::META_PREFIX . 'code_url_override']) : '';
            $success_message    = isset($_POST[Plugin_Constants::META_PREFIX . 'success_message']) ? wp_kses($_POST[Plugin_Constants::META_PREFIX . 'success_message'], $allowed_html) : '';
            $after_redirect_url = isset($_POST[Plugin_Constants::META_PREFIX . 'after_redirect_url']) ? sanitize_text_field($_POST[Plugin_Constants::META_PREFIX . 'after_redirect_url']) : '';

            $coupon->set_advanced_prop('disable_url_coupon', 'yes' === $enable_coupon_url ? '' : 'yes');

            if ('yes' === $enable_coupon_url) {
                $coupon->set_advanced_prop('force_apply_url_coupon', 'yes' == $force_apply_coupon ? 'yes' : '');
                $coupon->set_advanced_prop('code_url_override', $code_url_override);
                $coupon->set_advanced_prop('success_message', $success_message);
                $coupon->set_advanced_prop('after_redirect_url', $after_redirect_url);
            }

            $this->_force_update_coupon_post_slug($coupon->get_id());
        }

        // Role restriction module
        if ($this->_helper_functions->is_module(Plugin_Constants::ROLE_RESTRICT_MODULE)) {

            $enable_role_restrictions    = isset($_POST[Plugin_Constants::META_PREFIX . 'enable_role_restriction']) ? sanitize_text_field($_POST[Plugin_Constants::META_PREFIX . 'enable_role_restriction']) : '';
            $role_restrictions_type      = isset($_POST[Plugin_Constants::META_PREFIX . 'role_restrictions_type']) ? sanitize_text_field($_POST[Plugin_Constants::META_PREFIX . 'role_restrictions_type']) : 'allowed';
            $role_restrictions           = isset($_POST[Plugin_Constants::META_PREFIX . 'role_restrictions']) && is_array($_POST[Plugin_Constants::META_PREFIX . 'role_restrictions']) ? array_map('sanitize_text_field', $_POST[Plugin_Constants::META_PREFIX . 'role_restrictions']) : array();
            $role_restrictions_error_msg = isset($_POST[Plugin_Constants::META_PREFIX . 'role_restrictions_error_msg']) ? wp_kses($_POST[Plugin_Constants::META_PREFIX . 'role_restrictions_error_msg'], $allowed_html) : '';

            $coupon->set_advanced_prop('enable_role_restriction', $enable_role_restrictions);

            if ('yes' === $enable_role_restrictions) {
                $coupon->set_advanced_prop('role_restrictions', $role_restrictions);
                $coupon->set_advanced_prop('role_restrictions_type', $role_restrictions_type);
                $coupon->set_advanced_prop('role_restrictions_error_msg', $role_restrictions_error_msg);
            }
        }

        $coupon->advanced_save();

        $this->_save_with_default_coupon_category($coupon_id);

        do_action('acfw_save_coupon', $coupon->get_id(), $coupon);
    }

    /**
     * Force update coupon slug (post name) when a coupon is saved.
     *
     * @since 1.4.2
     * @access private
     *
     * @param int $coupon_id Coupon ID
     */
    private function _force_update_coupon_post_slug($coupon_id)
    {
        remove_action('save_post', array($this, 'save_url_coupons_data'), 10, 1);

        wp_update_post(array(
            'ID'        => $coupon_id,
            'post_name' => '',
        ));

        add_action('save_post', array($this, 'save_url_coupons_data'), 10, 1);
    }

    /**
     * Prevent multiple spaces in coupon code on save.
     *
     * @since 1.0
     * @access public
     *
     * @param array $data Post save data.
     * @return array Filtered post save data.
     */
    public function prevent_multiple_spaces_in_coupon_code($data)
    {

        if ('shop_coupon' === $data['post_type'] && isset($data['post_title'])) {
            $data['post_title'] = trim(preg_replace('!\s+!', ' ', sanitize_text_field($data['post_title'])));
        }

        return $data;
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Fields
    |--------------------------------------------------------------------------
     */

    /**
     * Display ACFW multiselect field.
     *
     * @since 1.0
     * @access public
     *
     * @param array $args Field arguments.
     * @return bool Value to return (always true) to pass for call_user_func_array function.
     */
    public function acfw_multiselect_field($args)
    {
        global $thepostid, $post;

        $thepostid = empty($thepostid) ? $post->ID : $thepostid;
        $field     = wp_parse_args($args, array(
            'class'             => 'select short',
            'style'             => '',
            'wrapper_class'     => '',
            'value'             => array(),
            'name'              => $args['id'],
            'desc_tip'          => false,
            'custom_attributes' => array(),
        )
        );

        $wrapper_attributes = array(
            'class' => $field['wrapper_class'] . " form-field {$field['id']}_field",
        );

        $label_attributes = array(
            'for' => $field['id'],
        );

        if (!is_array($field['value'])) {
            $field['value'] = array();
        }

        $field_attributes             = (array) $field['custom_attributes'];
        $field_attributes['style']    = $field['style'];
        $field_attributes['id']       = $field['id'];
        $field_attributes['name']     = $field['name'] . '[]';
        $field_attributes['class']    = $field['class'];
        $field_attributes['multiple'] = true;

        $tooltip     = !empty($field['description']) && false !== $field['desc_tip'] ? $field['description'] : '';
        $description = !empty($field['description']) && false === $field['desc_tip'] ? $field['description'] : '';
        ?>
        <p <?php echo wc_implode_html_attributes($wrapper_attributes); ?>>
            <label <?php echo wc_implode_html_attributes($label_attributes); ?>><?php echo wp_kses_post($field['label']); ?></label>
            <?php if ($tooltip): ?>
                <?php echo wc_help_tip($tooltip); ?>
            <?php endif;?>
            <select <?php echo wc_implode_html_attributes($field_attributes); ?>>
        <?php
foreach ($field['options'] as $key => $value) {
            echo '<option value="' . esc_attr($key) . '" ' . selected(true, in_array($key, $field['value']), false) . '>' . esc_html($value) . '</option>';
        }
        ?>
            </select>
            <?php if ($description): ?>
                <span class="description"><?php echo wp_kses_post($description); ?></span>
            <?php endif;?>
        </p>
        <?php
}

    /*
    |--------------------------------------------------------------------------
    | Coupon categories taxonomy related functions
    |--------------------------------------------------------------------------
     */

    /**
     * Register coupon categories taxonomy.
     *
     * @since 1.0
     * @access private
     */
    private function _register_coupon_category_taxonomy()
    {
        $labels = array(
            'name'                       => _x('Coupon Categories', 'Taxonomy General Name', 'advanced-coupons-for-woocommerce-free'),
            'singular_name'              => _x('Coupon Category', 'Taxonomy Singular Name', 'advanced-coupons-for-woocommerce-free'),
            'menu_name'                  => __('Coupon Categories', 'advanced-coupons-for-woocommerce-free'),
            'all_items'                  => __('All Categories', 'advanced-coupons-for-woocommerce-free'),
            'parent_item'                => __('Parent Category', 'advanced-coupons-for-woocommerce-free'),
            'parent_item_colon'          => __('Parent Category:', 'advanced-coupons-for-woocommerce-free'),
            'new_item_name'              => __('New Category Name', 'advanced-coupons-for-woocommerce-free'),
            'add_new_item'               => __('Add New Category', 'advanced-coupons-for-woocommerce-free'),
            'edit_item'                  => __('Edit Category', 'advanced-coupons-for-woocommerce-free'),
            'update_item'                => __('Update Category', 'advanced-coupons-for-woocommerce-free'),
            'view_item'                  => __('View Category', 'advanced-coupons-for-woocommerce-free'),
            'separate_items_with_commas' => __('Separate categories with commas', 'advanced-coupons-for-woocommerce-free'),
            'add_or_remove_items'        => __('Add or remove categories', 'advanced-coupons-for-woocommerce-free'),
            'choose_from_most_used'      => __('Choose from the most used', 'advanced-coupons-for-woocommerce-free'),
            'popular_items'              => __('Popular Categories', 'advanced-coupons-for-woocommerce-free'),
            'search_items'               => __('Search Categories', 'advanced-coupons-for-woocommerce-free'),
            'not_found'                  => __('Not Found', 'advanced-coupons-for-woocommerce-free'),
            'no_terms'                   => __('No categories', 'advanced-coupons-for-woocommerce-free'),
            'items_list'                 => __('Categories list', 'advanced-coupons-for-woocommerce-free'),
            'items_list_navigation'      => __('Categories list navigation', 'advanced-coupons-for-woocommerce-free'),
        );

        $capabilities = array(
            'manage_terms' => 'manage_woocommerce',
            'edit_terms'   => 'manage_woocommerce',
            'delete_terms' => 'manage_woocommerce',
            'assign_terms' => 'manage_woocommerce',
        );

        $args = array(
            'labels'            => $labels,
            'hierarchical'      => true,
            'public'            => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => false,
            'show_tagcloud'     => false,
            'capabilities'      => $capabilities,
            'show_in_rest'      => true,
        );

        register_taxonomy(Plugin_Constants::COUPON_CAT_TAXONOMY, array('shop_coupon'), $args);

    }

    /**
     * Manually add coupon categories admin submenu under WooCommerce.
     *
     * @deprecated 1.2
     *
     * @since 1.0
     * @access public
     *
     * @global array $submenu WordPress submenu instance list.
     */
    public function coupon_category_admin_menu()
    {
    }

    /**
     * Get shared default category value.
     *
     * @since 1.0
     * @access private
     *
     * @return WP_Term Default category term object.
     */
    private function _get_default_category()
    {
        if (!$this->_default_category || !is_a($this->_default_category, 'WP_Term')) {

            $category                = (int) get_option(Plugin_Constants::DEFAULT_COUPON_CATEGORY);
            $this->_default_category = get_term_by('id', $category, Plugin_Constants::COUPON_CAT_TAXONOMY);
        }

        return $this->_default_category;
    }

    /**
     * Save coupon with default coupon category.
     *
     * @since 1.0
     * @access private
     *
     * @param int $coupon_id Coupon ID.
     */
    private function _save_with_default_coupon_category($coupon_id)
    {
        if (get_the_terms($coupon_id, Plugin_Constants::COUPON_CAT_TAXONOMY)) {
            return;
        }

        $default_category = (int) get_option(Plugin_Constants::DEFAULT_COUPON_CATEGORY);

        // create the default term if it doesn't exist
        if (!term_exists($default_category, Plugin_Constants::COUPON_CAT_TAXONOMY)) {

            $default_cat_name = __('Uncategorized', 'advanced-coupons-for-woocommerce-free');
            wp_insert_term($default_cat_name, Plugin_Constants::COUPON_CAT_TAXONOMY);

            $default_term = get_term_by('name', $default_cat_name, Plugin_Constants::COUPON_CAT_TAXONOMY);

        } else {
            $default_term = get_term_by('id', $default_category, Plugin_Constants::COUPON_CAT_TAXONOMY);
        }

        wp_set_post_terms($coupon_id, $default_term->term_id, Plugin_Constants::COUPON_CAT_TAXONOMY);
    }

    /**
     * Modify coupon category row actions.
     *
     * @since 1.0
     * @access public
     *
     * @param array   $actions List of row actions.
     * @param WP_term $term    Row term object.
     * @return array Filtered list of row actions.
     */
    public function coupon_category_row_actions($actions, $term)
    {
        $default_term_id = (int) get_option(Plugin_Constants::DEFAULT_COUPON_CATEGORY);

        if ($default_term_id === $term->term_id) {
            unset($actions['delete']);
        }

        return $actions;
    }

    /**
     * Remove bulk checkbox for default category.
     *
     * @since 1.0
     * @access public
     */
    public function remove_bulk_checkbox_default_category()
    {
        $screen = get_current_screen();
        if ('edit-shop_coupon_cat' !== $screen->id) {
            return;
        }

        $default_cat = $this->_get_default_category();
        if (!is_a($default_cat, 'WP_Term')) {
            return;
        }

        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $( 'input[name="delete_tags[]"][value="<?php echo $default_cat->term_id; ?>"]' ).remove();
        });
        </script>
        <?php
}

    /**
     * Prevent default category term deletion by disabling the user caps when checking the 'delete_term' map meta cap.
     *
     * @since 1.0
     * @access public
     *
     * @param array $capabilities List of user capabilities.
     * @param array $caps         Required primitive capabilities for the requested capability.
     * @param array $args         Capability check arguments.
     */
    public function prevent_default_category_term_delete($capabilities, $caps, $args)
    {
        if (in_array('delete_term', $args, true)) {

            $default_cat = (int) get_option(Plugin_Constants::DEFAULT_COUPON_CATEGORY);

            if (in_array($default_cat, $args, true)) {
                foreach ((array) $caps as $cap) {
                    unset($capabilities[$cap]);
                }

            }
        }

        return $capabilities;
    }

    /**
     * Add category column in admin coupons list.
     *
     * @since 1.0
     * @access public
     *
     * @param array $columns List of columns.
     * @return array Filtered list of columns.
     */
    public function add_coupon_list_category_column($columns)
    {
        $columns['coupon_categories'] = __('Categories', 'advanced-coupons-for-woocommerce-free');

        return $columns;
    }

    /**
     * Render custom category column content.
     *
     * @since 1.0
     * @access public
     *
     * @param string $column    Column name.
     * @param int    $coupon_id Coupon ID.
     */
    public function coupon_list_category_column_content($column, $coupon_id)
    {

        if ('coupon_categories' !== $column) {
            return;
        }

        $categories = get_the_terms($coupon_id, Plugin_Constants::COUPON_CAT_TAXONOMY);

        if (!is_array($categories) || empty($categories)) {
            echo '–';
            return;
        }

        $content = array_map(function ($term) {
            $filter_link = admin_url('edit.php?post_type=shop_coupon&' . Plugin_Constants::COUPON_CAT_TAXONOMY . '=' . $term->slug);
            return sprintf('<a href="%s">%s</a>', $filter_link, $term->name);
        }, $categories);

        echo implode(', ', $content);
    }

    /**
     * Add shop coupon category dropdown filter field.
     *
     * @since 1.0
     * @access public
     *
     * @global WP_Query $wp_query Main query object.
     *
     * @param string $post_type Post type.
     */
    public function add_shop_coupon_category_filter_selection($post_type)
    {

        global $wp_query;

        if ('shop_coupon' !== $post_type) {
            return;
        }

        $args = array(
            'pad_counts'         => true,
            'show_count'         => true,
            'hierarchical'       => true,
            'hide_empty'         => false,
            'show_uncategorized' => true,
            'orderby'            => 'name',
            'selected'           => isset($wp_query->query_vars[Plugin_Constants::COUPON_CAT_TAXONOMY]) ? $wp_query->query_vars[Plugin_Constants::COUPON_CAT_TAXONOMY] : '',
            'show_option_none'   => __('Select a category', 'advanced-coupons-for-woocommerce-free'),
            'option_none_value'  => '',
            'value_field'        => 'slug',
            'taxonomy'           => Plugin_Constants::COUPON_CAT_TAXONOMY,
            'name'               => Plugin_Constants::COUPON_CAT_TAXONOMY,
            'class'              => 'dropdown_' . Plugin_Constants::COUPON_CAT_TAXONOMY,
        );

        wp_dropdown_categories($args);
    }

    /**
     * Setup default coupon category on plugin activation and assign it to all existing coupons.
     *
     * @since 1.0
     * @access private
     */
    private function _setup_default_category_on_activation()
    {

        global $wpdb;

        if (get_option(Plugin_Constants::DEFAULT_COUPON_CATEGORY, 'NONE') !== 'NONE') {
            return;
        }

        $this->_register_coupon_category_taxonomy();

        $default_cat_name = __('Uncategorized', 'advanced-coupons-for-woocommerce-free');

        if (!term_exists($default_cat_name, Plugin_Constants::COUPON_CAT_TAXONOMY)) {
            wp_insert_term($default_cat_name, Plugin_Constants::COUPON_CAT_TAXONOMY);
        }

        $default_term = get_term_by('name', $default_cat_name, Plugin_Constants::COUPON_CAT_TAXONOMY);

        update_option(Plugin_Constants::DEFAULT_COUPON_CATEGORY, $default_term->term_id);

        $query = new \WP_Query(array(
            'post_type'      => 'shop_coupon',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ));

        if (empty($query->posts)) {
            return;
        }

        $term_id = $default_term->term_id;
        $values  = array_map(function ($coupon_id) use ($term_id) {
            return "('$coupon_id', '$term_id', '0')";
        }, $query->posts);

        $values_str   = implode(', ', $values);
        $insert_query = "INSERT INTO $wpdb->term_relationships (`object_id`, `term_taxonomy_id`, `term_order`) VALUES $values_str";

        $wpdb->query($insert_query);
    }

    /*
    |--------------------------------------------------------------------------
    | Clone Coupon
    |--------------------------------------------------------------------------
     */

    /**
     * Add clone coupon link in "Publish" metabox above "move to trash" link.
     *
     * @since 1.5
     * @access public
     */
    public function register_clone_coupon_post_action()
    {
        global $post;

        if ('shop_coupon' !== $post->post_type || !isset($_GET['action']) || get_post_status($post) !== 'publish') {
            return;
        }

        ?>
        <div id="acfw-clone-coupon-action">
            <a href="<?php echo wp_nonce_url('admin.php?action=acfw_clone_coupon&post=' . $post->ID, 'acfw_clone_coupon_nonce', 'nonce'); ?>">
                <?php _e('Clone Coupon', 'advanced-coupons-for-woocommerce-free');?>
            </a>
        </div>
        <?php
}

    /**
     * Register clone coupon metabox.
     *
     * @since 1.0
     * @access public
     *
     * @deprecated 1.5
     *
     * @param string  $post_type Post type.
     * @param WP_Post $post      Post object.
     */
    public function register_clone_coupon_metabox($post_type, $post)
    {
    }

    /**
     * Register clone coupon post link.
     *
     * @since 1.0
     *
     * @param array   $actions Post action links.
     * @param WP_Post $post    Post WP_Post object.
     * @return array Filtered post action links.
     */
    public function register_clone_coupon_post_link($actions, $post)
    {

        if (current_user_can('manage_woocommerce') && get_post_type($post) === 'shop_coupon' && get_post_status($post) === 'publish') {

            $clone_coupon_url         = wp_nonce_url('admin.php?action=acfw_clone_coupon&post=' . $post->ID, 'acfw_clone_coupon_nonce', 'nonce');
            $clone_coupon_anchor_text = __('Clone', 'advanced-coupons-for-woocommerce-free');
            $actions['clone_coupon']  = sprintf('<a href="%s">%s</a>', $clone_coupon_url, $clone_coupon_anchor_text);
        }

        return $actions;
    }

    /**
     * Clone coupon admin action.
     *
     * @since 1.0
     * @access public
     */
    public function clone_coupon_admin_action()
    {

        global $wpdb;

        $coupon_id = isset($_GET['post']) ? absint($_GET['post']) : null;

        if (!$coupon_id || get_post_type($coupon_id) !== 'shop_coupon' || get_post_status($coupon_id) === 'trash') {
            wp_die('The selected coupon to clone is not valid.');
        }

        if (!current_user_can('manage_woocommerce') || !isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'acfw_clone_coupon_nonce')) {
            wp_die('You are not allowed to do this.');
        }

        $coupon   = $this->get_shared_advanced_coupon($coupon_id);
        $clone_id = $coupon->advanced_clone();

        wp_redirect(admin_url('post.php?action=edit&post=' . $clone_id));
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | AJAX functions
    |--------------------------------------------------------------------------
     */

    /**
     * AJAX search free products.
     *
     * @deprecated 1.2
     *
     * @since 1.0
     * @access public
     *
     * @global wpdb $wpdb Object that contains a set of functions used to interact with a database.
     */
    public function ajax_search_free_products()
    {

        $this->ajax_search_products();
    }

    /**
     * AJAX search products.
     *
     * @since 1.2
     * @access public
     *
     * @global wpdb $wpdb Object that contains a set of functions used to interact with a database.
     */
    public function ajax_search_products()
    {

        global $wpdb;

        check_ajax_referer('search-products', 'security');

        if (!isset($_GET['term']) || empty($_GET['term'])) {
            wp_die();
        }

        // get coupon_id
        $coupon_id       = intval(preg_replace('/[^0-9]/', '', $_SERVER['HTTP_REFERER']));
        $exclude_ids     = isset($_GET['exclude']) && is_array($_GET['exclude']) ? array_map('intval', $_GET['exclude']) : array();
        $exclude_ids_str = is_array($exclude_ids) ? implode(',', $exclude_ids) : '';
        $exclude_query   = $exclude_ids_str ? 'AND posts.ID NOT IN ( ' . $exclude_ids_str . ' )' : '';

        $like_term     = '%' . $wpdb->esc_like(wc_clean(stripslashes($_REQUEST['term']))) . '%';
        $post_statuses = current_user_can('edit_private_products') ? array('private', 'publish') : array('publish');

        $product_ids = $wpdb->get_col(
            "SELECT DISTINCT posts.ID FROM {$wpdb->posts} posts
            LEFT JOIN {$wpdb->postmeta} postmeta ON ( posts.ID = postmeta.post_id AND postmeta.meta_key = '_sku' )
            WHERE (
                posts.post_title LIKE '$like_term'
                OR posts.post_content LIKE '$like_term'
                OR postmeta.meta_value LIKE '$like_term'
            )
            AND ( posts.post_type = 'product_variation' OR ( posts.post_type = 'product' AND posts.post_parent = 0 AND ( SELECT COUNT(posts2.ID) FROM {$wpdb->posts} posts2 WHERE posts2.post_parent = posts.ID AND posts2.post_type IN ( 'product' , 'product_variation' ) ) = 0 ) )
            AND posts.post_status IN ('" . implode("','", $post_statuses) . "')
            $exclude_query
            ORDER BY posts.post_parent ASC, posts.post_title ASC"
        );

        $ids = wp_parse_id_list($product_ids);

        $product_objects = array_filter(array_map('wc_get_product', $ids), 'wc_products_array_filter_editable');
        $products        = array();
        $supported_types = apply_filters('acfw_product_search_allowed_types', array('simple', 'variation', 'subscription', 'subscription_variation'));

        foreach ($product_objects as $product_object) {
            if (in_array($product_object->get_type(), $supported_types)) {
                $products[$product_object->get_id()] = wc_clean(rawurldecode($product_object->get_formatted_name()));
            }

        }

        wp_send_json(apply_filters('acfw_json_search_products_response', $products, $product_objects, $_GET));
    }

    /**
     * AJAX search product category.
     *
     * @since 1.0
     * @access public
     */
    public function ajax_search_product_category()
    {

        global $wpdb;

        check_ajax_referer('search-products', 'security');

        if (!isset($_GET['term']) || empty($_GET['term'])) {
            wp_die();
        }

        // get coupon_id
        $coupon_id   = intval(preg_replace('/[^0-9]/', '', $_SERVER['HTTP_REFERER']));
        $exclude_ids = isset($_GET['exclude']) && is_array($_GET['exclude']) ? array_map('intval', $_GET['exclude']) : array();
        $search      = sanitize_text_field($_GET['term']);

        $args = array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => 'false',
            'exclude'    => $exclude_ids,
            'search'     => $search,
        );

        $terms   = get_terms($args);
        $options = array();

        foreach ($terms as $term) {
            $options[$term->term_id] = $term->name . ' (' . $term->slug . ')';
        }

        wp_send_json(apply_filters('acfw_json_search_product_categories_response', $options));
    }

    /**
     * AJAX search for simple and variable products.
     *
     * @since 1.0
     * @access public
     */
    public function ajax_search_simple_variable_products()
    {

        global $wpdb;

        check_ajax_referer('search-products', 'security');

        if (!isset($_GET['term']) || empty($_GET['term'])) {
            wp_die();
        }

        $exclude_ids = isset($_GET['exclude']) && is_array($_GET['exclude']) ? array_map('intval', $_GET['exclude']) : array();
        $search      = sanitize_text_field($_GET['term']);

        $args = array(
            'post_type'      => 'product',
            'status'         => 'publish',
            's'              => $search,
            'post__not_in'   => $exclude_ids,
            'posts_per_page' => -1,
            'fields'         => 'ids',
        );

        $query = new \WP_Query($args);

        $product_objects = array_map('wc_get_product', $query->posts);
        $products        = array();

        foreach ($product_objects as $product_object) {
            if (($product_object->get_type() == 'simple') || ($product_object->get_type() == 'variable')) {
                $products[$product_object->get_id()] = rawurldecode($product_object->get_formatted_name());
            }

        }

        wp_send_json(apply_filters('acfw_json_search_products_response', $products));
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
     * @implements ACFWF\Interfaces\Activatable_Interface
     */
    public function activate()
    {

        $this->_setup_default_category_on_activation();
    }

    /**
     * Execute codes that needs to run plugin activation.
     *
     * @since 1.0
     * @access public
     * @implements ACFWF\Interfaces\Initializable_Interface
     */
    public function initialize()
    {

        $this->_register_coupon_category_taxonomy();

        add_action('wp_ajax_acfw_search_free_products', array($this, 'ajax_search_products'));
        add_action('wp_ajax_acfw_search_products', array($this, 'ajax_search_products'));
        add_action('wp_ajax_acfw_search_product_category', array($this, 'ajax_search_product_category'));
        add_action('wp_ajax_acfw_search_products', array($this, 'ajax_search_simple_variable_products'));
    }

    /**
     * Execute Edit_Coupon class.
     *
     * @since 1.0
     * @access public
     * @inherit ACFWF\Interfaces\Model_Interface
     */
    public function run()
    {

        add_filter('parent_file', array($this, 'override_coupon_categories_parent_file'), 20);
        add_action('admin_menu', array($this, 'add_coupon_admin_menus'), 20);
        add_action('admin_head', array($this, 'coupon_admin_menu_icon_css'));
        add_action('save_post', array($this, 'save_url_coupons_data'), 10, 1);
        add_filter('wp_insert_post_data', array($this, 'prevent_multiple_spaces_in_coupon_code'));

        // Coupon categories
        add_filter(Plugin_Constants::COUPON_CAT_TAXONOMY . '_row_actions', array($this, 'coupon_category_row_actions'), 10, 2);
        add_action('admin_footer', array($this, 'remove_bulk_checkbox_default_category'));
        add_action('user_has_cap', array($this, 'prevent_default_category_term_delete'), 10, 3);
        add_filter('manage_edit-shop_coupon_columns', array($this, 'add_coupon_list_category_column'));
        add_filter('manage_shop_coupon_posts_custom_column', array($this, 'coupon_list_category_column_content'), 10, 2);
        add_action('restrict_manage_posts', array($this, 'add_shop_coupon_category_filter_selection'), 10);

        // URL Coupons meta
        if ($this->_helper_functions->is_module(Plugin_Constants::URL_COUPONS_MODULE)) {

            add_filter('woocommerce_coupon_data_tabs', array($this, 'url_coupons_admin_data_tab'), 60, 1);
            add_action('woocommerce_coupon_data_panels', array($this, 'url_coupons_admin_data_panel'));
        }

        // Cart conditions module
        if ($this->_helper_functions->is_module(Plugin_Constants::CART_CONDITIONS_MODULE)) {
            add_action('add_meta_boxes', array($this, 'register_cart_conditions_metabox'), 10, 2);
        }

        // Add bogo deals module
        if ($this->_helper_functions->is_module(Plugin_Constants::BOGO_DEALS_MODULE)) {
            add_action('woocommerce_coupon_options', array($this, 'bogo_deals_admin_data_panel'), 20);
        }

        // Role restriction module
        if ($this->_helper_functions->is_module(Plugin_Constants::ROLE_RESTRICT_MODULE)) {

            add_filter('woocommerce_coupon_data_tabs', array($this, 'role_restriction_admin_data_tab'), 50, 1);
            add_action('woocommerce_coupon_data_panels', array($this, 'role_restriction_admin_data_panel'));
        }

        // Clone coupon
        add_action('post_submitbox_start', array($this, 'register_clone_coupon_post_action'));
        add_filter('post_row_actions', array($this, 'register_clone_coupon_post_link'), 10, 2);
        add_action('admin_action_acfw_clone_coupon', array($this, 'clone_coupon_admin_action'));
    }

}
