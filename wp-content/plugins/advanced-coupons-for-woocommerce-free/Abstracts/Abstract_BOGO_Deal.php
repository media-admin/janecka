<?php
namespace ACFWF\Abstracts;

use ACFWF\Models\Objects\Advanced_Coupon;

abstract class Abstract_BOGO_Deal
{
    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
     */

    /**
     * Property that houses the type of BOGO feature.
     *
     * @since 1.4
     * @access protected
     * @var string
     */
    protected $_bogo_type = '';

    /**
     * Property that houses the coupon object.
     *
     * @since 1.4
     * @access protected
     * @var Advanced_Coupon
     */
    protected $_coupon = null;

    /**
     * Property that houses the public BOGO Deals data.
     *
     * @since 1.4
     * @access protected
     * @var array
     */
    protected $_data = array(
        'is_bogo_deal'    => false,
        'trigger_type'    => '',
        'triggers'        => array(),
        'deal_type'       => '',
        'deals'           => array(),
        'is_repeat'       => false,
        'needed_triggers' => array(),
        'allowed_deals'   => array(),
    );

    /*
    |--------------------------------------------------------------------------
    | Class Methods
    |--------------------------------------------------------------------------
     */

    /**
     * Create a new BOGO Type object.
     *
     * @since 1.4
     * @access public
     */
    public function __construct(Advanced_Coupon $coupon)
    {
        $this->_coupon = $coupon;

        $raw_data = $coupon->get_advanced_prop('bogo_deals');

        if ($raw_data) {
            $this->_init($raw_data);
            $this->_prepare_trigger_data($raw_data);
            $this->_prepare_deal_data($raw_data);
            $this->_set_repeat($raw_data);
            $this->set_data('is_bogo_deal', true);
        }
    }

    /**
     * Initialize data types.
     *
     * @since 1.4
     * @access protected
     *
     * @param array $raw_data BOGO Deal raw data.
     */
    protected function _init($raw_data)
    {
    }

    /**
     * Reset the counters for needed triggers and allowed deals.
     *
     * @since 1.4
     * @access public
     *
     * @param string $type Type of counter to reset.
     */
    public function reset_counters($type = 'both')
    {
        if (in_array($type, array('trigger', 'both'))) {
            $this->set_data('needed_triggers', array_column($this->triggers, 'quantity', 'entry_id'));
        }

        if (in_array($type, array('deal', 'both'))) {
            $this->set_data('allowed_deals', array_column($this->deals, 'quantity', 'entry_id'));
        }
    }

    /**
     * Prepare BOGO trigger data so it can be uniformly processed upon implementation.
     *
     * @since 1.4
     * @access protected
     *
     * @param array $raw_data Raw BOGO Deals data.
     */
    protected function _prepare_trigger_data($raw_data)
    {
    }

    /**
     * Prepare BOGO trigger data so it can be uniformly processed upon implementation.
     *
     * @since 1.4
     * @access protected
     *
     * @param array $raw_data Raw BOGO Deals data.
     */
    protected function _prepare_deal_data($raw_data)
    {
    }

    /*
    |--------------------------------------------------------------------------
    | Getter methods
    |--------------------------------------------------------------------------
     */

    /**
     * Access public BOGO Deals data.
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $prop Model to access.
     */
    public function __get($prop)
    {

        if (array_key_exists($prop, $this->_data)) {
            return $this->_data[$prop];
        } else {
            throw new \Exception("Trying to access unknown property " . $prop . " on Abstract_BOGO_Deal instance.");
        }

    }

    /**
     * Get BOGO Type.
     *
     * @since 1.4
     * @access public
     *
     * @return string Bogo type.
     */
    public function get_bogo_type()
    {
        return $this->_bogo_type;
    }

    /**
     * Return the parent coupon object.
     *
     * @since 1.4
     * @access public
     *
     * @return Advanced_Coupon Coupon object.
     */
    public function get_coupon()
    {
        return $this->_coupon;
    }

    /**
     * Get needed trigger quanttiy for either a specific entry, or for all entries.
     *
     * @since 1.4
     * @access public
     *
     * @param string $entry_id Entry ID.
     * @return int Needed trigger quantity.
     */
    public function get_needed_trigger_quantity($entry_id = '')
    {
        if ($entry_id) {
            return $this->needed_triggers[$entry_id];
        }

        return array_sum($this->needed_triggers);
    }

    /**
     * Get allowed deal quantity for either a specific entry, or for all entries.
     *
     * @since 1.4
     * @access public
     *
     * @param string $entry_id Entry ID.
     * @return int Needed trigger quantity.
     */
    public function get_allowed_deal_quantity($entry_id = '')
    {
        if ($entry_id) {
            return $this->allowed_deals[$entry_id];
        }

        return array_sum($this->allowed_deals);
    }

    /**
     * Get data for session.
     *
     * @since 1.4
     * @access public
     *
     * @return array Data for session.
     */
    public function get_data_for_session()
    {
        $remove_entry_id_cb = function ($e) {
            unset($e['entry_id']);
            return $e;
        };

        return array(
            'coupon'       => $this->get_coupon()->get_code(),
            'bogo_type'    => $this->get_bogo_type(),
            'is_repeat'    => $this->is_repeat,
            'trigger_type' => $this->trigger_type,
            'deal_type'    => $this->deal_type,
            'triggers'     => array_map($remove_entry_id_cb, $this->triggers),
            'deals'        => array_map($remove_entry_id_cb, $this->deals),
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Setter methods
    |--------------------------------------------------------------------------
     */

    /**
     * Set data propert value.
     *
     * @since 1.4
     * @access public
     *
     * @param string $prop Property key.
     * @param mixed  $value Property value.
     * @return boolean True if value was set, false otherwise.
     */
    public function set_data($prop, $value)
    {
        if (array_key_exists($prop, $this->_data) && gettype($value) === gettype($this->_data[$prop])) {
            $this->_data[$prop] = $value;
            return true;
        }

        return false;
    }

    /**
     * Set the value for needed triggers quantity for a specific trigger entry.
     *
     * @since 1.4
     * @access public
     *
     * @param string $entry_id Trigger entry ID.
     * @param int    $quantity Needed trigger quantity.
     * @return boolean True if value was set, false otherwise.
     */
    public function set_needed_triggers_quantity($entry_id, $quantity)
    {
        if (isset($this->needed_triggers[$entry_id])) {
            $needed_triggers            = $this->needed_triggers;
            $needed_triggers[$entry_id] = $quantity;
            return $this->set_data('needed_triggers', $needed_triggers);
        }

        return false;
    }

    /**
     * Set the value for allowed deals quantity for a specific deal entry.
     *
     * @since 1.4
     * @access public
     *
     * @param string $entry_id Deal entry ID.
     * @param int    $quantity Allowed deal quantity.
     * @return boolean True if value was set, false otherwise.
     */
    public function set_allowed_deal_quantity($entry_id, $quantity)
    {
        if (isset($this->allowed_deals[$entry_id])) {
            $allowed_deals            = $this->allowed_deals;
            $allowed_deals[$entry_id] = $quantity;
            return $this->set_data('allowed_deals', $allowed_deals);
        }

        return false;
    }

    /**
     * Set repeat toggle value.
     *
     * @since 1.4
     * @access protected
     *
     * @param array $raw_data Raw BOGO Deals data.
     */
    protected function _set_repeat($raw_data)
    {
        $this->set_data('is_repeat', isset($raw_data['repeat']) && 'repeat' === $raw_data['repeat']);
    }

    /**
     * Check if the provided cart item matches the items set in the trigger/deal entry.
     *
     * @since 1.4
     * @access public
     *
     * @param array   $cart_item Cart item data.
     * @param array   $entries   Trigger/deal entry.
     * @param boolean $is_deal   Flag if entry is for deal or not.
     * @return int|boolean The cart item compare value if matched, false otherwise.
     */
    public function is_cart_item_match_entries($cart_item, $entry)
    {
        $item_id = isset($item['variation_id']) && $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
        $item_id = apply_filters('acfw_filter_cart_item_product_id', $item_id); // filter for WPML support.
        return in_array($item_id, $entry['ids']) ? $item_id : false;
    }

    /**
     * Check if trigger has been verified.
     *
     * @since 1.4
     * @access public
     *
     * @return bool True if verified, false otherwise.
     */
    public function is_trigger_verified()
    {
        if (0 >= count($this->needed_triggers)) {
            return false;
        }

        return 0 >= $this->get_needed_trigger_quantity();
    }

    /**
     * Check if all deals have been fully fulfilled.
     *
     * @since 1.4
     * @access public
     *
     * @return bool True if fully fulfilled, false otherwise.
     */
    public function is_deal_fulfilled()
    {
        if (0 >= count($this->deals)) {
            return false;
        }

        return 0 >= $this->get_allowed_deal_quantity();
    }

    /**
     * Check if BOGO Deal has at least 1 deal fulfilled.
     *
     * @since 1.4
     * @access public
     *
     * @return boolean True if at least 1 deal item qty was fulfilled, false otherwise.
     */
    public function has_deal_fulfilled()
    {
        $allowed_qty   = $this->get_allowed_deal_quantity();
        $fresh_allowed = array_column($this->deals, 'quantity', 'entry_id');

        return array_sum($fresh_allowed) > $allowed_qty;
    }
}
