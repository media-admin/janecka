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
 * Model that houses the logic of extending the coupon system of woocommerce.
 * It houses the logic of handling coupon url.
 * Public Model.
 *
 * @since 1.0
 */
class URL_Coupons implements Model_Interface
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
     * @var URL_Coupons
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

    /**
     * List of removed forced applied coupons.
     *
     * @since 1.3.4
     * @access private
     * @var string[]
     */
    private $_rerun_removed_coupon = array();

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
     * @return URL_Coupons
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
    | URL Coupon implementation
    |--------------------------------------------------------------------------
     */

    /**
     * Implement URL Coupon.
     *
     * @since 1.0
     * @access public
     */
    public function implement_url_coupon()
    {

        global $wp_query;

        if (!isset($wp_query->query['post_type']) || 'shop_coupon' !== $wp_query->query['post_type']) {
            return;
        }

        // Coupon codes are just post titles. So we pass it through 'sanitize_title' to get the slug and then fetch the title using it.
        $coupon_slug = isset($wp_query->query['name']) ? sanitize_title($wp_query->query['name']) : '';
        $coupon_id   = $this->_get_coupon_id_by_slug($coupon_slug);

        // If coupon is not present, then we just redirect normally to the cart.
        if (!$coupon_id || !apply_filters('acfw_check_additional_coupon_endpoint_args', true)) {

            do_action('acfw_incomplete_coupon_endpoint_args');
            wp_redirect(wc_get_cart_url());
            exit();
        }

        $coupon_args = apply_filters('acfw_extract_coupon_endpoint_args', array('code' => $coupon_slug, 'id' => $coupon_id));
        $coupon      = new Advanced_Coupon($coupon_id);

        // Initialize cart session
        \WC()->session->set_customer_session_cookie(true);

        // if coupon is invalid, then don't proceed.
        if (!$coupon->get_id() || !$coupon->is_coupon_url_valid()) {

            $error_message = __('Invalid Coupon', 'advanced-coupons-for-woocommerce-free');
            do_action('acfw_invalid_coupon', $coupon, $coupon_args, $error_message);
            $this->_after_apply_redirect_invalid($coupon, $coupon_args, $error_message);
        }

        // modify the success message by adding a filter before applying the coupon.
        add_filter('woocommerce_coupon_message', function ($msg, $msg_code) use ($coupon) {
            return $coupon->get_advanced_prop('success_message', __('Coupon applied successfully', 'advanced-coupons-for-woocommerce-free'), true);
        }, 10, 2);

        // check if we need to implement "force apply" for URL coupon.
        if ($this->_is_coupon_force_apply($coupon)) {
            $this->_remove_auto_applied_coupons_from_cart();
        }

        do_action('acfw_before_apply_coupon', $coupon, $coupon_args);

        // Apply coupon.
        $is_applied = WC()->cart->apply_coupon($coupon->get_code());

        do_action('acfw_after_apply_coupon', $coupon, $coupon_args, $is_applied);
        $this->_after_apply_redirect_success($coupon, $coupon_args, $is_applied);
    }

    /**
     * Check if URL coupon can be force applied.
     *
     * @since 1.3.2
     * @access private
     *
     * @param Advanced_Coupon $coupon Coupon object.
     */
    private function _is_coupon_force_apply($coupon)
    {
        return (
            $coupon->get_advanced_prop('force_apply_url_coupon') === 'yes'
            && function_exists('ACFWP')
            && $this->_helper_functions->is_module('acfw_auto_apply_module')
        );
    }

    /**
     * This is needed to implement "force apply" for URL coupons by removing all auto applied coupons from the cart.
     * Note that the auto applied coupons that excludes or excluded by the url coupon will not be applied, but other
     * non-conflicting coupons will still be applied after the whole cart page is loaded.
     *
     * @since 1.3.2
     * @access private
     */
    private function _remove_auto_applied_coupons_from_cart()
    {

        remove_action('woocommerce_removed_coupon', array($this, 'rerun_autoapply_after_removing_force_apply_coupon'));

        $auto_coupons    = get_option(Plugin_Constants::AUTO_APPLY_COUPONS, array());
        $applied_coupons = \WC()->cart->get_applied_coupons();

        if (is_array($auto_coupons) && !empty($auto_coupons) && !empty($applied_coupons)) {
            foreach ($auto_coupons as $coupon_id) {
                $coupon = new Advanced_Coupon($coupon_id);

                if (in_array($coupon->get_code(), $applied_coupons)) {
                    \WC()->cart->remove_coupon($coupon->get_code());
                }

            }
        }
    }

    /**
     * Rerun Auto Apply coupons when a coupon forced applied via URL is removed.
     *
     * @since 1.3.2
     * @access public
     *
     * @param string $coupon_code Coupon code.
     */
    public function rerun_autoapply_after_removing_force_apply_coupon($coupon_code)
    {
        // if auto apply already rerun, then skip.
        if (in_array($coupon_code, $this->_rerun_removed_coupon)) {
            return;
        }

        $coupon = new Advanced_Coupon($coupon_code);

        if ($this->_is_coupon_force_apply($coupon) && $coupon->get_advanced_prop('disable_url_coupon') === 'yes') {
            $this->_rerun_removed_coupon[] = $coupon_code;
            \ACFWP()->Auto_Apply->implement_auto_apply_coupons();
        }
    }

    /**
     * Redirect after applying coupon successfully.
     *
     * @since 1.0
     * @access private
     *
     * @param Advanced_Coupon $coupon      Advanced coupon object.
     * @param array           $coupon_args URL Coupon additional arguments.
     */
    private function _after_apply_redirect_success($coupon, $coupon_args, $is_applied)
    {

        if ($redirect_url = $coupon->get_valid_redirect_url()) {

            $query_args = apply_filters(
                'acfw_after_apply_coupon_redirect_url_query_args',
                array('{acfw_coupon_code}', '{acfw_coupon_is_applied}', '{acfw_coupon_error_message}')
            );

            $coupon_code             = isset($coupon_args['code']) ? rawurlencode($coupon_args['code']) : '';
            $coupon_error_message    = rawurlencode($this->_hackish_fetch_coupon_error_message());
            $is_applied_response     = $is_applied ? 'true' : 'false';
            $query_args_replacements = apply_filters(
                'acfw_after_apply_coupon_redirect_url_query_args_replacements',
                array($coupon_code, $is_applied_response, $coupon_error_message)
            );

            $redirect_url = str_replace($query_args, $query_args_replacements, $redirect_url);

        } else {
            $redirect_url = wc_get_cart_url();
        }

        // Clear notices when redirecting to an external URL.
        if (strpos($redirect_url, home_url()) === false) {
            wc_clear_notices();
        }

        wp_redirect($redirect_url);
        exit();
    }

    /**
     * Redirect after applying invalid coupon.
     *
     * @since 1.0
     * @access private
     *
     * @param Advanced_Coupon $coupon        Advanced coupon object.
     * @param array           $coupon_args   URL Coupon additional arguments.
     * @param array           $error_message Invalid coupon error message.
     */
    private function _after_apply_redirect_invalid($coupon, $coupon_args, $error_message)
    {

        $redirect_url = get_option(Plugin_Constants::INVALID_COUPON_REDIRECT_URL, '');

        if (filter_var($redirect_url, FILTER_VALIDATE_URL)) {
            $redirect_url = $this->_process_invalid_coupon_redirect_url_query_args($coupon, $coupon_args, $error_message, $redirect_url);
        } else {
            $redirect_url = wc_get_cart_url();
        }

        // Display error notice if redirecting to an internal page.
        if (strpos($redirect_url, home_url()) !== false) {
            $adv_error_message = $coupon->get_advanced_error_message();
            wc_add_notice($adv_error_message ? $adv_error_message : $error_message, 'error');
        }

        wp_redirect($redirect_url);
        exit();
    }

    /**
     * Process invalid coupon redirect url query vars. Replace em with actual data.
     *
     * @since 1.0
     * @access public
     *
     * @param Advanced_Coupon $coupon        WooCommerce coupon object. Could be valid or invalid coupon object.
     * @param array           $coupon_args   Coupon url additional arguments.
     * @param string          $error_message Coupon error message.
     */
    private function _process_invalid_coupon_redirect_url_query_args($coupon, $coupon_args, $error_message, $redirect_url)
    {

        $query_args = apply_filters(
            'acfw_invalid_coupon_redirect_url_query_args',
            array('{acfw_coupon_code}', '{acfw_coupon_error_message}')
        );

        $coupon_code          = isset($coupon_args['code']) ? rawurlencode($coupon_args['code']) : '';
        $coupon_error_message = rawurlencode($coupon->get_advanced_error_message());

        $query_args_replacements = apply_filters(
            'acfw_invalid_coupon_redirect_url_query_args_replacements',
            array($coupon_code, $coupon_error_message)
        );

        return str_replace($query_args, $query_args_replacements, $redirect_url);
    }

    /**
     * Override the WooCommerce post type registration for shop_coupon.
     *
     * @since 1.0
     * @access public
     *
     * @param array $args shop_coupon post type registration args.
     * @return array Filtered shop_coupon post type registration args.
     */
    public function override_wc_coupon_registration($args)
    {

        $args['publicly_queryable'] = true;
        $args['rewrite']            = array(
            'slug'  => $this->_coupon_endpoint,
            'pages' => false,
        );

        // flush rewrite rules when transient is set.
        if ('true' == get_transient(Plugin_Constants::COUPON_ENDPOINT . '_flush_rules')) {

            flush_rewrite_rules(false);
            delete_transient(Plugin_Constants::COUPON_ENDPOINT . '_flush_rules');
        }

        return $args;
    }

    /**
     * Sanitize coupon endpoint option value.
     *
     * @since 1.0
     * @access public
     */
    public function sanitize_coupon_endpoint_option_value($value, $option, $raw_value)
    {

        return $value ? sanitize_title($value) : 'coupon';
    }

    /**
     * Hide coupon UI.
     *
     * @since 1.0
     * @since 2.2.3 Make sure at $wp_query global variable is availabe before running page conditional queries.
     * @access public
     *
     * @param bool $return Filter return value.
     * @param bool Filtered return value.
     */
    public function hide_coupon_fields($return)
    {

        global $wp_query;

        if ($wp_query && (is_cart() || is_checkout()) && get_option(Plugin_Constants::HIDE_COUPON_UI_ON_CART_AND_CHECKOUT) === 'yes') {
            return false;
        }

        return $return;
    }

    /**
     * Fetch coupon error message (hackish method).
     *
     * @since 1.0
     * @access private
     *
     * @return string Coupon error message.
     */
    private function _hackish_fetch_coupon_error_message()
    {

        // NOTE: This is the only way of retrieving what might be the error that caused the coupon to not be applied successfully.
        // This isn't reliable as we are only getting the latest added error notice from wc_notices, which might not be set by the coupon
        // but its better than nothing, its a bit ok too cause if coupon failed to apply, woocommerce add error notice about it anyways
        $coupon_error_message = wc_get_notices('error');
        $notice               = is_array($coupon_error_message) && !empty($coupon_error_message) ? end($coupon_error_message) : null;

        if (is_array($notice)) {
            return $notice['notice'];
        } else {
            return $notice ? $notice : '';
        }

    }

    /**
     * Set transient to force flush rewrite rules when coupon endpoint value is changed.
     *
     * @since 1.2
     * @access public
     */
    public function flush_rewrite_rules_on_coupon_endpoint_change()
    {

        set_transient(Plugin_Constants::COUPON_ENDPOINT . '_flush_rules', 'true', 5 * 60);
    }

    /**
     * Get page by path.
     *
     * @since 1.4.2
     * @access private
     *
     * @param string $coupon_slug Coupon post slug.
     * @return int Coupon ID.
     */
    private function _get_coupon_id_by_slug($coupon_slug)
    {
        $post      = $coupon_slug ? get_page_by_path($coupon_slug, OBJECT, 'shop_coupon') : null;
        $coupon_id = $post ? $post->ID : 0;

        // check for coupon override value if coupon ID was not detected.
        if ($coupon_slug && !$coupon_id) {
            $coupon_id = $this->_get_coupon_id_from_override($coupon_slug);
        }

        return $coupon_id;
    }

    /**
     * Get the actual coupon code from the given override code.
     *
     * @since 1.4.2
     * @access private
     */
    private function _get_coupon_id_from_override($coupon_slug)
    {
        global $wpdb;

        $meta  = Plugin_Constants::META_PREFIX . "code_url_override";
        $query = $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} AS posts_table
            INNER JOIN {$wpdb->postmeta} AS post_meta_table
            ON posts_table.ID = post_meta_table.post_id
            WHERE posts_table.post_type = 'shop_coupon'
                AND post_meta_table.meta_key = %s
                AND post_meta_table.meta_value = %s
            LIMIT 1",
            $meta,
            $coupon_slug
        );

        return absint($wpdb->get_var($query));
    }

    /*
    |--------------------------------------------------------------------------
    | Fulfill implemented interface contracts
    |--------------------------------------------------------------------------
     */

    /**
     * Execute URL_Coupons class.
     *
     * @since 1.0
     * @access public
     * @inherit ACFWF\Interfaces\Model_Interface
     */
    public function run()
    {

        if (!$this->_helper_functions->is_module(Plugin_Constants::URL_COUPONS_MODULE)) {
            return;
        }

        add_filter('woocommerce_register_post_type_shop_coupon', array($this, 'override_wc_coupon_registration'), 10, 1);
        add_filter('woocommerce_admin_settings_sanitize_option_' . Plugin_Constants::COUPON_ENDPOINT, array($this, 'sanitize_coupon_endpoint_option_value'), 10, 3);
        add_filter('woocommerce_coupons_enabled', array($this, 'hide_coupon_fields'));
        add_action('update_option_' . Plugin_Constants::COUPON_ENDPOINT, array($this, 'flush_rewrite_rules_on_coupon_endpoint_change'));

        add_action('template_redirect', array($this, 'implement_url_coupon'));
        add_action('woocommerce_removed_coupon', array($this, 'rerun_autoapply_after_removing_force_apply_coupon'));

    }

}
