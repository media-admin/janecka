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
 * Model that houses the logic of extending the coupon system of woocommerce.
 * It houses the logic of handling coupon url.
 * Public Model.
 *
 * @deprecated 1.4
 *
 * @since 1.0
 */
class BOGO_Deals implements Model_Interface, Initializable_Interface
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
     * @var BOGO_Deals
     */
    private static $_instance;

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
     * @return BOGO_Deals
     */
    public static function get_instance(Abstract_Main_Plugin_Class $main_plugin, Plugin_Constants $constants, Helper_Functions $helper_functions)
    {
        if (!self::$_instance instanceof self) {
            self::$_instance = new self($main_plugin, $constants, $helper_functions);
        }

        return self::$_instance;

    }

    /**
     * Log deprecated notice for class methods.
     *
     * @since 1.4
     * @access private
     */
    private function _deprecated($function_name, $is_admin = false)
    {
        $replacement = $is_admin ? 'BOGO_Admin' : 'BOGO_Frontend';
        \wc_deprecated_function('BOGO_Deals::' . $function_name, '1.4', $replacement);
    }

    /**
     * Get valid item quantities of given product IDs that are currently present in the cart.
     *
     * @since 1.0
     * @access private
     *
     * @param array  $product_ids Product IDs list.
     * @param string $cond_id     Condition id.
     * @param bool   $variation   True if ids can be variation, false otherwise.
     * @return array Cart item quantities.
     */
    public function get_quantities_of_condition_products_in_cart($product_ids, $cond_id = '', $variation = false)
    {
        $this->_deprecated(__FUNCTION__);
        return array();
    }

    /*
    |--------------------------------------------------------------------------
    | Implementation related functions.
    |--------------------------------------------------------------------------
     */

    /**
     * Implement BOGO Deals for all applied coupon in the cart.
     *
     * @since 1.0
     * @access public
     */
    public function implement_bogo_deals()
    {
        $this->_deprecated(__FUNCTION__);
    }

    /**
     * Calculate condition concurrence.
     *
     * @since 1.0
     * @access public
     *
     * @param array $product_ids   Condition product ids.
     * @param int   $quantity      Condition quantity.
     * @param int   $cart_quantity Quantity of condition items in the cart.
     * @param array $matched       Matched deals in cart.
     * @return int Condition concurrence.
     */
    public function calculate_concurrence($product_ids, $quantity, $cart_quantity, $matched)
    {
        $this->_deprecated(__FUNCTION__);
        return 0;
    }

    /**
     * Display discounted price on cart price column.
     *
     * @since 1.0
     * @access public
     *
     * @param string $price_html Item price.
     * @param array  $item       Cart item data.
     * @param string $key        Cart item key.
     * @return string Filtered item price.
     */
    public function display_discounted_price($price_html, $item)
    {
        $this->_deprecated(__FUNCTION__);
        return $price_html;
    }

    /**
     * Display BOGO discounts summary on the coupons cart total row.
     *
     * @since 1.0
     * @access public
     *
     * @param string    $coupon_html Coupon row html.
     * @param WC_Coupon $coupon      Coupon object.
     * @return string Filtered Coupon row html.
     */
    public function display_bogo_discount_summary($coupon_html, $coupon, $discount_amount_html)
    {
        $this->_deprecated(__FUNCTION__);
        return $coupon_html;
    }

    /**
     * Save bogo discounts to order.
     *
     * @since 1.0
     * @access public
     *
     * @param int $order_id Order id.
     */
    public function save_bogo_discounts_to_order($order_id)
    {
        $this->_deprecated(__FUNCTION__);
    }

    /*
    |--------------------------------------------------------------------------
    | AJAX Functions
    |--------------------------------------------------------------------------
     */

    /**
     * AJAX Save Cart Conditions.
     *
     * @since 1.0
     * @access public
     */
    public function ajax_save_bogo_deals()
    {
        $this->_deprecated(__FUNCTION__, true);
        wp_die();
    }

    /**
     * AJAX clear bogo deals.
     *
     * @since 1.0
     * @access public
     */
    public function ajax_clear_bogo_deals()
    {
        $this->_deprecated(__FUNCTION__, true);
        wp_die();
    }

    /*
    |--------------------------------------------------------------------------
    | Utility Functions
    |--------------------------------------------------------------------------
     */

    /**
     * Create trigger entry.
     *
     * @since 1.3.5
     * @access public
     *
     * @param string $cart_item_key Cart item key.
     * @param string $condition_id  Condition ID.
     */
    public function create_trigger_entry($cart_item_key, $condition_id)
    {
        $this->_deprecated(__FUNCTION__);
    }

    /**
     * Get trigger entries for a cart item key and matching coupon code.
     *
     * @since 1.3.5
     * @access public
     *
     * @param string $cart_item_key Cart item key.
     * @param string $coupon_code   Coupon code.
     * @return array List of trigger entries.
     */
    public function get_item_trigger_entries($cart_item_key, $coupon_code)
    {
        $this->_deprecated(__FUNCTION__);
        return array();
    }

    /**
     * Generate needed trigger quantities for each matched conditions.
     *
     * @since 1.3.6
     * @access public
     *
     * @param array $matched_conditions Matched triggers data.
     * @param int   $concurrence        Concurrence value.
     * @return array Needed trigger quantities.
     */
    public function get_needed_trigger_quantities($matched_conditions, $concurrence)
    {
        $this->_deprecated(__FUNCTION__);
        return array();
    }

    /**
     * Create apply entry.
     *
     * @since 1.3.5
     * @access public
     *
     * @param string $cart_item_key Cart item key.
     * @param string $deal_id       Deal ID.
     */
    public function create_apply_entry($cart_item_key, $deal_id)
    {
        $this->_deprecated(__FUNCTION__);
    }

    /**
     * Get apply entries for a cart item key and matching coupon code.
     *
     * @since 1.3.5
     * @access public
     *
     * @param string $cart_item_key Cart item key.
     * @param string $coupon_code   Coupon code.
     * @return array List of apply entries.
     */
    public function get_item_apply_entries($cart_item_key, $coupon_code)
    {
        $this->_deprecated(__FUNCTION__);
        return array();
    }

    /**
     * Set deals property.
     *
     * @since 1.0
     * @access public
     *
     * @deprecated 1.3.5
     *
     * @param string $key   Property key.
     * @param mixed  $value Property value.
     */
    public function set_deals_prop($key, $value)
    {
        $this->_deprecated(__FUNCTION__);
    }

    /**
     * Get all deals data.
     *
     * @since 1.0
     * @since 1.3.5 Modify return data to return deal ids for provided coupon. Add backwards compatibility for previous ACFWP version.
     * @access public
     *
     * @return array List of deals data.
     */
    public function get_deals_data($coupon_code = '')
    {
        $this->_deprecated(__FUNCTION__);
        return array();
    }

    /**
     * Set price display prop.
     *
     * @since 1.0
     * @access public
     *
     * @param string $key   Property key.
     * @param mixed  $value Property value.
     */
    public function set_price_display($key, $value)
    {
        $this->_deprecated(__FUNCTION__);
    }

    /**
     * Check if the provided key is already present in price display data.
     *
     * @since 1.0
     * @since 1.3.5 Add backwards compatibility for version 2.4.1 and lower.
     * @access public
     *
     * @param string $key Property key.
     * @return bool True if exists, false otherwise.
     */
    public function isset_price_display($key)
    {
        $this->_deprecated(__FUNCTION__);
        return false;
    }

    /**
     * Get price display prop base on provided key.
     *
     * @since 1.2
     * @since 1.3.5 Add backwards compatibility for version 2.4.1 and lower.
     * @access public
     *
     * @param string $key Property key.
     * @return mixed Property value.
     */
    public function get_price_display($key)
    {
        $this->_deprecated(__FUNCTION__);
        return array();
    }

    /**
     * Check if the cart item is valid as a deal or trigger.
     *
     * @since 1.3.1
     * @access public
     *
     * @param WC_Cart_item $item Cart item object.
     * @return bool True if valid, false otherwise.
     */
    public function is_item_valid($item)
    {
        $this->_deprecated(__FUNCTION__);
        return apply_filters('acfw_bogo_is_item_valid', true, $item);
    }

    /**
     * Add item quantity entry.
     *
     * @since 1.3.5
     * @access public
     *
     * @param string $cart_item_key Cart item key.
     * @param string $coupon_code   Coupon code.
     * @param string $entry_id      Condition/Deal ID.
     * @param int    $quantity      Item quantity for condition/deal.
     * @param float  $discount      Discount value.
     * @param bool   $is_for_allowed Toggle if data needs to be added to allowed property instead.
     */
    public function add_item_quantities_entry($cart_item_key, $coupon_code, $entry_id, $type, $quantity, $discount = 0, $is_for_allowed = false)
    {
        $this->_deprecated(__FUNCTION__);
    }

    /**
     * Get item quantity entries for a single cart item.
     *
     * @since 1.3.5
     * @access public
     *
     * @param string $cart_item_key Cart item key.
     * @return array List of item quantity entries.
     */
    public function get_item_quantity_entries($cart_item_key, $is_for_allowed = false)
    {
        $this->_deprecated(__FUNCTION__);
        return array();
    }

    /**
     * Calculate the spare quantity of a cart item by the deducting the sum of all item quantity entries.
     *
     * @since 1.3.5
     * @access public
     *
     * @param array $cart_item Cart item data.
     * @return int Cart item spare quantity.
     */
    public function calculate_cart_item_spare_quantity($cart_item)
    {
        $this->_deprecated(__FUNCTION__);
        return 0;
    }

    /**
     * Remove all item quantity entries that was added with the provided coupon code.
     *
     * @since 1.3.5
     * @access public
     *
     * @param string $coupon_code Coupon code.
     */
    public function _unset_coupon_quantity_entries($coupon_code)
    {
        $this->_deprecated(__FUNCTION__);
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
        $this->_deprecated(__FUNCTION__, true);
    }

    /**
     * Execute BOGO_Deals class.
     *
     * @since 1.0
     * @access public
     * @inherit ACFWF\Interfaces\Model_Interface
     */
    public function run()
    {
        $this->_deprecated(__FUNCTION__);
    }

}
