<?php
namespace ACFWF\Models;

use ACFWF\Abstracts\Abstract_Main_Plugin_Class;
use ACFWF\Helpers\Helper_Functions;
use ACFWF\Helpers\Plugin_Constants;
use ACFWF\Interfaces\Model_Interface;
use ACFWF\Models\Objects\Advanced_Coupon;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

/**
 * Model that houses the logic of the Role_Restrictions module.
 *
 * @since 1.0
 */
class Role_Restrictions implements Model_Interface
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
     * @var Role_Restrictions
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

    /**
     * Coupon endpoint set.
     *
     * @since 1.0
     * @access private
     * @var string
     */
    private $_coupon_endpoint;

    /**
     * Coupon base url.
     *
     * @since 1.0
     * @access private
     * @var string
     */
    private $_coupon_base_url;

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
     * @return Role_Restrictions
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
    | Role_Restrictions implementation
    |--------------------------------------------------------------------------
     */

    /**
     * Implement coupon role restriction feature.
     *
     * @since 1.0
     * @access public
     *
     * @param bool      $return Filter return value.
     * @param WC_Coupon $coupon WC_Coupon object.
     * @return bool True if valid, false otherwise.
     */
    public function implement_role_restrictions($return, $coupon)
    {
        $coupon = new Advanced_Coupon($coupon);

        if ($coupon->get_advanced_prop('enable_role_restriction') !== 'yes') {
            return $return;
        }

        $current_user      = wp_get_current_user();
        $restriction_type  = $coupon->get_advanced_prop('role_restrictions_type', 'allowed');
        $restriction_roles = $coupon->get_advanced_prop('role_restrictions', array());
        $error_message     = $coupon->get_advanced_prop('role_restrictions_error_msg', __("You are not allowed to use this coupon.", 'advanced-coupons-for-woocommerce-free'), true);
        $user_roles        = $current_user->ID ? $current_user->roles : array('guest');

        if (is_user_logged_in() && is_object(\WC()->session) && \WC()->session->get('acfw_guest_user_object')) {
            $user_roles[] = 'guest';
        }

        $intersect_roles = array_intersect($user_roles, $restriction_roles);

        if (('allowed' === $restriction_type && empty($intersect_roles)) || ('allowed' !== $restriction_type && !empty($intersect_roles))) {
            throw new \Exception($error_message);
        }

        return $return;
    }

    /**
     * Save guest users object to session.
     *
     * @since 1.0
     * @access public
     */
    public function save_guest_user_object_to_session()
    {
        // should ony run for guest users once.
        if (is_user_logged_in()
            || get_option('woocommerce_enable_signup_and_login_from_checkout') !== 'yes'
            || !is_object(WC()->session)
            || \WC()->session->get('acfw_guest_user_object')) {
            return;
        }

        \WC()->session->set('acfw_guest_user_object', wp_get_current_user());
    }

    /**
     * Unset guest users object from session after order has been processed.
     *
     * @since 1.0
     * @access public
     */
    public function unset_guest_user_object_from_session()
    {
        if (!is_object(\WC()->session) || !\WC()->session->get('acfw_guest_user_object')) {
            return;
        }

        \WC()->session->set('acfw_guest_user_object', null);
    }

    /**
     * Unset guest user object from session on customer manual login.
     *
     * @since 1.0
     * @since 1.3.6 Make sure function only runs on frontend.
     * @access public
     */
    public function unset_guest_user_object_on_customer_login()
    {
        if (is_admin() || !isset($_REQUEST['woocommerce-login-nonce']) || !isset($_POST['username'])) {
            return;
        }

        $this->unset_guest_user_object_from_session();
    }

    /*
    |--------------------------------------------------------------------------
    | Fulfill implemented interface contracts
    |--------------------------------------------------------------------------
     */

    /**
     * Execute Role_Restrictions class.
     *
     * @since 1.0
     * @access public
     * @inherit ACFWF\Interfaces\Model_Interface
     */
    public function run()
    {
        // need to always run even when module is disabled.
        add_action('woocommerce_before_calculate_totals', array($this, 'save_guest_user_object_to_session'));
        add_action('woocommerce_checkout_order_processed', array($this, 'unset_guest_user_object_from_session'));
        add_action('wp_loaded', array($this, 'unset_guest_user_object_on_customer_login'));

        if (!$this->_helper_functions->is_module(Plugin_Constants::ROLE_RESTRICT_MODULE)) {
            return;
        }

        add_filter('woocommerce_coupon_is_valid', array($this, 'implement_role_restrictions'), 10, 2);
    }

}
