<?php
namespace ACFWF\Models\BOGO;

use ACFWF\Abstracts\Abstract_Main_Plugin_Class;
use ACFWF\Helpers\Helper_Functions;
use ACFWF\Helpers\Plugin_Constants;
use ACFWF\Interfaces\Activatable_Interface;
use ACFWF\Interfaces\Initializable_Interface;
use ACFWF\Interfaces\Model_Interface;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

/**
 * Model that houses the logic of extending the coupon system of woocommerce.
 * It houses the logic of handling coupon url.
 * Public Model.
 *
 * @since 1.4
 */
class Admin implements Model_Interface, Initializable_Interface, Activatable_Interface
{

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
     */

    /**
     * Property that houses the model name to be used when calling publicly.
     *
     * @since 2.8
     * @access private
     * @var string
     */
    private $_model_name = 'BOGO_Admin';

    /**
     * Property that holds the single main instance of URL_Coupon.
     *
     * @since 1.4
     * @access private
     * @var Admin
     */
    private static $_instance;

    /**
     * Model that houses all the plugin constants.
     *
     * @since 1.4
     * @access private
     * @var Plugin_Constants
     */
    private $_constants;

    /**
     * Property that houses all the helper functions of the plugin.
     *
     * @since 1.4
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
     * @since 1.4
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

        $main_plugin->add_to_all_plugin_models($this, $this->_model_name);
        $main_plugin->add_to_public_models($this, $this->_model_name);

    }

    /**
     * Ensure that only one instance of this class is loaded or can be loaded ( Singleton Pattern ).
     *
     * @since 1.4
     * @access public
     *
     * @param Abstract_Main_Plugin_Class $main_plugin      Main plugin object.
     * @param Plugin_Constants           $constants        Plugin constants object.
     * @param Helper_Functions           $helper_functions Helper functions object.
     * @return Admin
     */
    public static function get_instance(Abstract_Main_Plugin_Class $main_plugin, Plugin_Constants $constants, Helper_Functions $helper_functions)
    {
        if (!self::$_instance instanceof self) {
            self::$_instance = new self($main_plugin, $constants, $helper_functions);
        }

        return self::$_instance;

    }

    /**
     * Migrate coupon types to BOGO on plugin activation or update.
     *
     * @since 3.0
     * @access private
     */
    private function _migrate_coupon_types_to_bogo_on_activate()
    {
        global $wpdb;

        if ('NO' === get_option('acfwf_bogo_migrate_coupon_type', 'NO')) {

            /**
             * This query will update the discount type for all coupons that have valid BOGO deals datato "Buy X, Get X (BOGO)" type.
             * When the store has no valid coupons, the query will still run but will not update anything.
             */
            $wpdb->query(
                "UPDATE {$wpdb->postmeta} AS t1
                JOIN (SELECT p.ID from {$wpdb->posts} AS p
                    INNER JOIN {$wpdb->postmeta} AS cm1 ON (cm1.post_id = p.ID AND cm1.meta_key = '_acfw_bogo_deals')
                    WHERE p.post_type = 'shop_coupon'
                        AND cm1.meta_value != 'a:0:{}'
                        AND cm1.meta_value IS NOT NULL
                ) AS t2
                ON t1.post_id = t2.ID
                SET t1.meta_value = 'acfw_bogo'
                WHERE t1.meta_key = 'discount_type'"
            );

            update_option('acfwf_bogo_migrate_coupon_type', Plugin_Constants::VERSION);
        }
    }

    /**
     * Register BOGO Deals coupon type.
     *
     * @since 3.0
     * @access public
     *
     * @param array $types Coupon types.
     * @return array Filtered coupon types.
     */
    public function register_bogo_coupon_type($types)
    {
        $types['acfw_bogo'] = __('Buy X Get X Deal (BOGO)', 'advanced-coupons-for-woocommerce-free');

        return $types;
    }

    /**
     * Sanitize conditions/deals product data.
     *
     * @since 1.4
     * @access private
     *
     * @param array $data Product data.
     * @return array Sanitized product data.
     */
    private function _sanitize_product_data($data, $type)
    {
        $sanitized = apply_filters('acfw_sanitize_bogo_deals_data', array(), $data, $type);

        // if sanitized via filter then return early.
        if (is_array($sanitized) && !empty($sanitized)) {
            return $sanitized;
        }

        // default sanitization script.
        if (is_array($data)) {

            foreach ($data as $key => $row) {

                if (!isset($row['product_id']) || !isset($row['quantity'])) {
                    continue;
                }

                $product_id      = intval($row['product_id']);
                $sanitized[$key] = array(
                    'product_id'    => $product_id,
                    'quantity'      => intval($row['quantity']) > 1 ? absint($row['quantity']) : 1,
                    'product_label' => sanitize_text_field($row['product_label']),
                );

                if (isset($row['discount_type'])) {
                    $sanitized[$key]['discount_type'] = sanitize_text_field($row['discount_type']);
                }

                if (isset($row['discount_value'])) {
                    $sanitized[$key]['discount_value'] = $this->_helper_functions->sanitize_discount_value($row['discount_value'], $row['discount_type'], $product_id);
                }

                if (isset($row['condition'])) {
                    $sanitized[$key]['condition'] = sanitize_text_field($row['condition']);
                }

                if (isset($row['condition_label'])) {
                    $sanitized[$key]['condition_label'] = sanitize_text_field($row['condition_label']);
                }

            }
        }

        return $sanitized;
    }

    /**
     * Save BOGO Deals.
     *
     * @since 1.4
     * @access private
     *
     * @param int   $coupon_id  Coupon ID.
     * @param array $conditions Conditions list.
     * @param array $deals      Dealslist.
     * @return mixed WP_Error on failure, otherwise the coupon id.
     */
    private function _save_bogo_deals($coupon_id, $bogo_deals)
    {
        return update_post_meta($coupon_id, Plugin_Constants::META_PREFIX . 'bogo_deals', $bogo_deals);
    }

    /**
     * Set default values to BOGO notice settings.
     *
     * @since 1.1.0
     * @access private
     */
    private function _set_notice_settings_default_values()
    {
        if (get_option(Plugin_Constants::BOGO_DEALS_DEFAULT_VALUES) === 'yes') {
            return;
        }

        if (get_option(Plugin_Constants::BOGO_DEALS_NOTICE_MESSAGE, 'no_value') === 'no_value') {
            update_option(Plugin_Constants::BOGO_DEALS_NOTICE_MESSAGE, __("Your current cart is eligible to redeem deals", 'advanced-coupons-for-woocommerce-free'));
        }

        if (get_option(Plugin_Constants::BOGO_DEALS_NOTICE_BTN_TEXT, 'no_value') === 'no_value') {
            update_option(Plugin_Constants::BOGO_DEALS_NOTICE_BTN_TEXT, __("View Deals", 'advanced-coupons-for-woocommerce-free'));
        }

        if (get_option(Plugin_Constants::BOGO_DEALS_NOTICE_TYPE, 'no_value') === 'no_value') {
            update_option(Plugin_Constants::BOGO_DEALS_NOTICE_TYPE, 'notice');
        }

        update_option('acfw_bogo_deals_default_values_set', 'yes');
    }

    /*
    |--------------------------------------------------------------------------
    | AJAX Functions
    |--------------------------------------------------------------------------
     */

    /**
     * AJAX Save Cart Conditions.
     *
     * @since 1.4
     * @access public
     */
    public function ajax_save_bogo_deals()
    {
        if (!defined('DOING_AJAX') || !DOING_AJAX) {
            $response = array('status' => 'fail', 'error_msg' => __('Invalid AJAX call', 'advanced-coupons-for-woocommerce-free'));
        } elseif (!current_user_can(apply_filters('acfw_ajax_save_bogo_deals', 'manage_woocommerce'))) {
            $response = array('status' => 'fail', 'error_msg' => __('You are not allowed to do this', 'advanced-coupons-for-woocommerce-free'));
        } elseif (!isset($_POST['coupon_id']) || !isset($_POST['conditions']) || !isset($_POST['deals']) || !isset($_POST['type'])) {
            $response = array('status' => 'fail', 'error_msg' => __('Missing required post data', 'advanced-coupons-for-woocommerce-free'));
        } else {

            // get function to use for sanitizing data.
            $conditions_type = isset($_POST['conditions_type']) ? sanitize_text_field($_POST['conditions_type']) : '';
            $deals_type      = isset($_POST['deals_type']) ? sanitize_text_field($_POST['deals_type']) : '';
            $notice_settings = isset($_POST['notice_settings']) && is_array($_POST['notice_settings']) ? $_POST['notice_settings'] : array();

            // prepare bogo deals data.
            $coupon_id  = intval($_POST['coupon_id']);
            $bogo_deals = array(
                'conditions'      => $this->_sanitize_product_data($_POST['conditions'], $conditions_type),
                'deals'           => $this->_sanitize_product_data($_POST['deals'], $deals_type),
                'conditions_type' => $conditions_type,
                'deals_type'      => $deals_type,
                'type'            => sanitize_text_field($_POST['type']),
                'notice_settings' => array(
                    'message'     => isset($notice_settings['message']) && $notice_settings['message'] ? sanitize_text_field($notice_settings['message']) : '',
                    'button_text' => isset($notice_settings['button_text']) && $notice_settings['button_text'] ? sanitize_text_field($notice_settings['button_text']) : '',
                    'button_url'  => isset($notice_settings['button_url']) && $notice_settings['button_url'] ? esc_url_raw($notice_settings['button_url']) : '',
                    'notice_type' => isset($notice_settings['notice_type']) && $notice_settings['notice_type'] ? $this->_helper_functions->sanitize_notice_type($notice_settings['notice_type']) : '',
                ),
            );

            // save bogo deals.
            $save_check = $this->_save_bogo_deals($coupon_id, $bogo_deals);

            if ($save_check) {
                $response = array('status' => 'success', 'message' => __('BOGO deals has been saved successfully!', 'advanced-coupons-for-woocommerce-free'));
            } else {
                $response = array('status' => 'fail');
            }

        }

        @header('Content-Type: application/json; charset=' . get_option('blog_charset'));
        echo wp_json_encode($response);
        wp_die();
    }

    /**
     * AJAX clear bogo deals.
     *
     * @since 1.4
     * @access public
     */
    public function ajax_clear_bogo_deals()
    {
        if (!defined('DOING_AJAX') || !DOING_AJAX) {
            $response = array('status' => 'fail', 'error_msg' => __('Invalid AJAX call', 'advanced-coupons-for-woocommerce-free'));
        } elseif (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'acfw_clear_bogo_deals') || !current_user_can(apply_filters('acfw_ajax_clear_bogo_deals', 'manage_woocommerce'))) {
            $response = array('status' => 'fail', 'error_msg' => __('You are not allowed to do this', 'advanced-coupons-for-woocommerce-free'));
        } elseif (!isset($_POST['coupon_id'])) {
            $response = array('status' => 'fail', 'error_msg' => __('Missing required post data', 'advanced-coupons-for-woocommerce-free'));
        } else {

            $coupon_id  = intval($_POST['coupon_id']);
            $bogo_deals = array();

            $save_check = $this->_save_bogo_deals($coupon_id, $bogo_deals);

            if ($save_check) {
                $response = array('status' => 'success', 'message' => __('BOGO deals has been cleared successfully!', 'advanced-coupons-for-woocommerce-free'));
            } else {
                $response = array('status' => 'fail', 'error_msg' => __('Failed on clearing or there were no changes to save.', 'advanced-coupons-for-woocommerce-free'));
            }

        }

        @header('Content-Type: application/json; charset=' . get_option('blog_charset'));
        echo wp_json_encode($response);
        wp_die();
    }

    /*
    |--------------------------------------------------------------------------
    | Fulfill implemented interface contracts
    |--------------------------------------------------------------------------
     */

    /**
     * Execute codes that needs to run plugin activation.
     *
     * @since 3.0
     * @access public
     * @implements ACFWF\Interfaces\Activatable_Interface
     */
    public function activate()
    {
        $this->_migrate_coupon_types_to_bogo_on_activate();
    }

    /**
     * Execute codes that needs to run plugin activation.
     *
     * @since 1.4
     * @access public
     * @implements ACFWF\Interfaces\Initializable_Interface
     */
    public function initialize()
    {
        if (!$this->_helper_functions->is_module(Plugin_Constants::BOGO_DEALS_MODULE)) {
            return;
        }

        $this->_set_notice_settings_default_values();

        add_action('wp_ajax_acfw_save_bogo_deals', array($this, 'ajax_save_bogo_deals'));
        add_action('wp_ajax_acfw_clear_bogo_deals', array($this, 'ajax_clear_bogo_deals'));
    }

    /**
     * Execute Admin class.
     *
     * @since 1.4
     * @access public
     * @inherit ACFWF\Interfaces\Model_Interface
     */
    public function run()
    {
        if (!$this->_helper_functions->is_module(Plugin_Constants::BOGO_DEALS_MODULE)) {
            return;
        }

        add_filter('woocommerce_coupon_discount_types', array($this, 'register_bogo_coupon_type'));
    }

}
