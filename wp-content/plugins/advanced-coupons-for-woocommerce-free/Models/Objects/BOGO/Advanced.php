<?php

namespace ACFWF\Models\Objects\BOGO;

use ACFWF\Abstracts\Abstract_BOGO_Deal;
use ACFWF\Models\Objects\Advanced_Coupon;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Advanced extends Abstract_BOGO_Deal
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
    protected $_bogo_type = 'advanced';

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
     * @param Advanced_Coupon $coupon Coupon object.
     */
    public function __construct(Advanced_Coupon $coupon)
    {
        parent::__construct($coupon);
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
        $this->set_data('trigger_type', isset($raw_data['conditions_type']) ? $raw_data['conditions_type'] : 'specific-products');
        $this->set_data('deal_type', isset($raw_data['deals_type']) ? $raw_data['deals_type'] : 'specific-products');
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
        $raw_triggers = isset($raw_data['conditions']) ? $raw_data['conditions'] : array();
        $triggers     = array();

        switch ($this->trigger_type) {

            case 'specific-products':
                foreach ($raw_triggers as $raw_trigger) {
                    $product_id = absint($raw_trigger['product_id']);
                    $triggers[] = \ACFWF()->Helper_Functions->format_bogo_trigger_deal_entry(
                        array(
                            'ids'      => $product_id,
                            'quantity' => $raw_trigger['quantity'],
                        )
                    );
                }
                break;

            default:
                $triggers = apply_filters('acfw_bogo_advanced_prepare_trigger_data', $triggers, $raw_triggers, $this->trigger_type, $this);
                break;
        }

        $this->set_data('triggers', $triggers);
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
        $raw_deals = isset($raw_data['deals']) ? $raw_data['deals'] : array();
        $deals     = array();

        switch ($this->deal_type) {

            case 'specific-products':
                foreach ($raw_deals as $raw_deal) {
                    $product_id = absint($raw_deal['product_id']);
                    $deals[]    = \ACFWF()->Helper_Functions->format_bogo_trigger_deal_entry(
                        array(
                            'ids'      => $product_id,
                            'quantity' => $raw_deal['quantity'],
                            'discount' => $raw_deal['discount_value'],
                            'type'     => $raw_deal['discount_type'],
                        ),
                        true
                    );
                }
                break;

            default:
                $deals = apply_filters('acfw_bogo_advanced_prepare_deal_data', $deals, $raw_deals, $this->deal_type, $this);
                break;
        }

        $this->set_data('deals', $deals);
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
    public function is_cart_item_match_entries($cart_item, $entry, $is_deal = false)
    {
        $type = $is_deal ? $this->deal_type : $this->trigger_type;

        switch ($type) {
            case 'specific-products':
                $item_id = isset($cart_item['variation_id']) && $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
                $item_id = apply_filters('acfw_filter_cart_item_product_id', $item_id); // filter for WPML support.=
                return in_array($item_id, $entry['ids']) ? $item_id : false;

            default:
                return apply_filters('acfw_bogo_is_cart_item_match_entries', false, $cart_item, $entry, $is_deal, $type, $this);
        }
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
        if (isset($raw_data['repeat'])) {
            $is_repeat = 'repeat' === $raw_data['repeat'];
        } elseif (isset($raw_data['type'])) {
            $is_repeat = 'repeat' === $raw_data['type'];
        } else {
            $is_repeat = false;
        }

        $this->set_data('is_repeat', $is_repeat);

    }
}
