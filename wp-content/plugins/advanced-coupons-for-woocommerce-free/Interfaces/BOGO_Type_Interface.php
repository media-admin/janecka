<?php
namespace ACFWF\Interfaces;

/**
 * Abstraction that provides contract relating to creating a BOGO type object.
 *
 * @since 1.4
 */
interface BOGO_Type_Interface
{

    /**
     * Get BOGO Type.
     *
     * @since 1.4
     * @access public
     */
    public function get_bogo_type();

    /**
     * Prepare BOGO trigger data so it can be uniformly processed upon implementation.
     *
     * @since 1.4
     * @access protected
     *
     * @param array $raw_data Raw BOGO Deals data.
     */
    function _prepare_trigger_data($raw_data);

    /**
     * Prepare BOGO trigger data so it can be uniformly processed upon implementation.
     *
     * @since 1.4
     * @access public
     *
     * @param array $raw_data Raw BOGO Deals data.
     */
    function _prepare_deals_data($raw_data);

    /**
     * Get matching triggers for a cart item.
     * A trigger is matched when the cart item has a relationship with the trigger (product id, variation id, category id, etc.)
     *
     * @since 1.4
     * @access public
     *
     * @param array $cart_item Cart item data.
     * @return array Matched triggers.
     */
    public function get_cart_item_matching_triggers($cart_item);

    /**
     * Get matching triggers for a cart item.
     * A deal is matched when the cart item has a relationship with the trigger (product id, variation id, category id, etc.)
     *
     * @since 1.4
     * @access public
     *
     * @param array $cart_item Cart item data.
     * @return array Matched deals.
     */
    public function get_cart_item_matching_deals($cart_item);
}
