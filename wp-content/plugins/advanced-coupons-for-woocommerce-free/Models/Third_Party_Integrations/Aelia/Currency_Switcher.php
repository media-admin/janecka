<?php
namespace ACFWF\Models\Third_Party_Integrations\Aelia;

use ACFWF\Abstracts\Abstract_Main_Plugin_Class;
use ACFWF\Helpers\Helper_Functions;
use ACFWF\Helpers\Plugin_Constants;
use ACFWF\Interfaces\Model_Interface;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

/**
 * Model that houses the logic of the Currency_Switcher module.
 *
 * @since 1.4
 */
class Currency_Switcher implements Model_Interface
{

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
     */

    /**
     * Property that holds the single main instance of URL_Coupon.
     *
     * @since 1.4
     * @access private
     * @var Currency_Switcher
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

    /**
     * Coupon endpoint set.
     *
     * @since 1.4
     * @access private
     * @var string
     */
    private $_coupon_endpoint;

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

        $main_plugin->add_to_all_plugin_models($this);
        $main_plugin->add_to_public_models($this);

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
     * @return Currency_Switcher
     */
    public static function get_instance(Abstract_Main_Plugin_Class $main_plugin, Plugin_Constants $constants, Helper_Functions $helper_functions)
    {

        if (!self::$_instance instanceof self) {
            self::$_instance = new self($main_plugin, $constants, $helper_functions);
        }

        return self::$_instance;

    }

    /**
     * Get the Aelia Currency Switcher main plugin object.
     *
     * @since 1.4
     * @access public
     *
     * @return WC_Aelia_CurrencySwitcher
     */
    public function aelia_obj()
    {
        return $GLOBALS['woocommerce-aelia-currencyswitcher'];
    }

    /**
     * Convert amount to from base currency to user selected currency (or reverse).
     *
     * @since 1.4
     * @access public
     *
     * @param float $amount Amount to convert.
     * @param bool  $is_reverse Convert from user to base currency if true.
     * @return float Converted amount.
     */
    public function convert_amount_to_user_selected_currency($amount, $is_reverse = false)
    {

        $user_currency = $this->aelia_obj()->get_selected_currency();
        $site_currency = $this->aelia_obj()->base_currency();

        if ($site_currency === $user_currency) {
            return $amount;
        }

        if ($is_reverse) {
            return $this->aelia_obj()->convert($amount, $user_currency, $site_currency);
        }
        // convert from user to base.
        else {
            return $this->aelia_obj()->convert($amount, $site_currency, $user_currency);
        }
        // convert from base to user.
    }

    /*
    |--------------------------------------------------------------------------
    | Fulfill implemented interface contracts
    |--------------------------------------------------------------------------
     */

    /**
     * Execute Currency_Switcher class.
     *
     * @since 1.4
     * @access public
     * @inherit ACFWF\Interfaces\Model_Interface
     */
    public function run()
    {

        if (!$this->_helper_functions->is_plugin_active('woocommerce-aelia-currencyswitcher/woocommerce-aelia-currencyswitcher.php')) {
            return;
        }

        add_filter('acfw_filter_amount', array($this, 'convert_amount_to_user_selected_currency'), 10, 2);
    }

}
