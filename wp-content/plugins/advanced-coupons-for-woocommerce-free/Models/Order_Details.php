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
 * Model that houses the logic of extending the coupon system of woocommerce.
 * It houses the logic of handling coupon url.
 * Public Model.
 *
 * @since 1.0
 */
class Order_Details implements Model_Interface
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
     * @var Order_Details
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
     * @return Order_Details
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
    | Order Preview Popup
    |--------------------------------------------------------------------------
     */

    /**
     * Add coupons list in order preview popup.
     * Moved and refactored from \ACFWF\Models\Edit_Coupon.
     *
     * @since 1.3.7
     * @access public
     *
     * @param array    $data  Order preview data.
     * @param WC_Order $order Order object.
     */
    public function add_coupons_list_in_order_preview_popup($data, $order)
    {
        $coupons_list = $this->_get_used_coupons_list_markup($order, true);

        ob_start();
        include $this->_constants->VIEWS_ROOT_PATH() . 'orders' . DIRECTORY_SEPARATOR . 'order-preview-popup-coupons-list.php';
        $markup = ob_get_clean();

        $data['item_html'] = $markup . $data['item_html'];

        return $data;
    }

    /**
     * Add used coupons in email order totals that are sent to admin.
     *
     * @since 1.3.7
     * @access public
     *
     * @param array    $total_rows Email total rows data.
     * @param WC_Order $order      Order object.
     * @return array   Filtered total email total rows data.
     */
    public function add_coupons_in_email_after_order_table($total_rows, $order)
    {

        $coupons_list  = $this->_get_used_coupons_list_markup($order);
        $filtered_rows = array();
        $index         = isset($total_rows['discount']) ? 'discount' : 'cart_subtotal';

        foreach ($total_rows as $key => $row) {
            $filtered_rows[$key] = $row;

            // add coupons row after either subtotal or discount row.
            if ($index === $key) {
                $filtered_rows['acfw_coupons'] = array(
                    'label' => __('Coupons:', 'advanced-coupons-for-woocommerce-free'),
                    'value' => $coupons_list,
                );
            }
        }

        return $filtered_rows;
    }

    /**
     * Register hook for used coupons list in email when it is sent to admin.
     *
     * @since 1.3.7
     * @access public
     *
     * @param WC_Order $order         Order object.
     * @param bool     $sent_to_admin Toggle if email sent to admin.
     */
    public function coupons_list_email_register_hook($order, $sent_to_admin)
    {
        if ($sent_to_admin) {
            add_filter('woocommerce_get_order_item_totals', array($this, 'add_coupons_in_email_after_order_table'), 10, 2);
        }

    }

    /**
     * Unregister hook for used coupons list in email when it is sent to admin.
     *
     * @since 1.3.7
     * @access public
     *
     * @param WC_Order $order         Order object.
     * @param bool     $sent_to_admin Toggle if email sent to admin.
     */
    public function coupons_list_email_unregister_hook($order, $sent_to_admin)
    {
        if ($sent_to_admin) {
            remove_filter('woocommerce_get_order_item_totals', array($this, 'add_coupons_in_email_after_order_table'), 10, 2);
        }

    }

    /*
    |--------------------------------------------------------------------------
    | WC REST API Overrides.
    |--------------------------------------------------------------------------
     */

    /**
     * Remove empty meta data for advanced coupon fields.
     *
     * @since 1.4.2
     * @access public
     *
     * @param array $metadata Meta data list.
     * @param mixed $coupon   Object passed in filter.
     * @return array Filtered meta data list.
     */
    public function remove_empty_advanced_coupon_meta_data($metadata, $coupon)
    {
        // handle requests for standalone coupon meta data.
        if ($coupon instanceof \WC_Coupon) {
            $metadata = array_filter($metadata, function ($m) {
                return !$this->_is_advanced_coupon_meta_data_empty($m->meta_key, $m->meta_value);
            });
        }

        // handle requests for coupon meta data inside orders.
        if ($coupon instanceof \WC_Order_Item_Coupon) {
            $metadata = array_map(function ($m) {

                if ('coupon_data' === $m->meta_key) {
                    $data = maybe_unserialize($m->meta_value);

                    $data['meta_data'] = array_filter($data['meta_data'], function ($m) {
                        return !$this->_is_advanced_coupon_meta_data_empty($m->key, $m->value);
                    });

                    $m->meta_value = maybe_serialize($data);
                }

                return $m;
            }, $metadata);
        }

        return $metadata;
    }

    /*
    |--------------------------------------------------------------------------
    | Utilities
    |--------------------------------------------------------------------------
     */

    /**
     * Backward compatibility helper to fetch used coupons for an order.
     *
     * @since 1.3.7
     * @access private
     *
     * @param WC_Order $order Order object.
     * @return array List of used coupon codes for the order.
     */
    private function _get_order_used_coupons($order)
    {
        return method_exists($order, 'get_coupon_codes') ? $order->get_coupon_codes() : $order->get_used_coupons();
    }

    /**
     * Get used couponst list markup for order.
     *
     * @since 1.3.7
     * @access private
     *
     * @param WC_Order $order Order object.
     * @param bool     $is_edit Toggle if coupons needs to be linked to edit screen.
     * @return string Coupons list markup.
     */
    private function _get_used_coupons_list_markup($order, $is_edit = false)
    {
        $used_coupons = $this->_get_order_used_coupons($order);
        return array_reduce($used_coupons, function ($c, $coupon) use ($is_edit) {

            // add comma if there is already coupon present in the loop.
            $c = $c ? $c . ', ' : $c;

            $coupon_id   = wc_get_coupon_id_by_code($coupon);
            $edit_coupon = $coupon_id && $is_edit ? admin_url('post.php?post=' . $coupon_id . '&action=edit') : '';
            $coupon_html = $edit_coupon ? sprintf('<a class="used-coupon" href="%s">%s</a>', $edit_coupon, $coupon) : sprintf('<span class="used-coupon">%s</span>', $coupon);

            return $c . $coupon_html;
        }, '');
    }

    /**
     * Check if a given meta key is for ACFW and if the value is empty or not.
     *
     * @since 1.4.2
     * @access private
     *
     * @param string $key   Meta key
     * @param mixed  $value Meta value.
     * @return bool True if empty, false otherwise.
     */
    private function _is_advanced_coupon_meta_data_empty($key, $value)
    {
        if (strpos($key, '_acfw_') !== false) {
            $value = maybe_unserialize($value);

            if (is_array($value)) {
                return empty($value);
            } else {
                return '' === $value;
            }
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Fulfill implemented interface contracts
    |--------------------------------------------------------------------------
     */

    /**
     * Execute Order_Details class.
     *
     * @since 1.0
     * @access public
     * @inherit ACFWF\Interfaces\Model_Interface
     */
    public function run()
    {
        add_filter('woocommerce_admin_order_preview_get_order_details', array($this, 'add_coupons_list_in_order_preview_popup'), 10, 2);

        add_action('woocommerce_email_before_order_table', array($this, 'coupons_list_email_register_hook'), 10, 2);
        add_action('woocommerce_email_after_order_table', array($this, 'coupons_list_email_unregister_hook'), 10, 2);
        add_filter('woocommerce_data_store_wp_post_read_meta', array($this, 'remove_empty_advanced_coupon_meta_data'), 10, 2);
        add_filter('woocommerce_data_store_wp_order_item_read_meta', array($this, 'remove_empty_advanced_coupon_meta_data'), 10, 2);
    }

}
