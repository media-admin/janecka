<?php

namespace ACFWF\Models\Objects\BOGO;

use ACFWF\Abstracts\Abstract_BOGO_Deal;
use ACFWF\Models\Objects\Advanced_Coupon;
use ACFWF\Models\Objects\BOGO\Advanced;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Calculation
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
     * @var Calculation
     */
    private static $_instance;

    /**
     * Property that houses data of matched triggers/deals in cart.
     *
     * @since 1.4
     * @access private
     * @var array
     */
    private $_matched_entries = array();

    /**
     * Property that houses the temporarily matched triggers in cart.
     *
     * @since 1.4
     * @access private
     * @var array
     */
    private $_temp_entries = array();

    /**
     * Property that houses data of allowed deals in cart.
     *
     * @since 1.4
     * @access private
     * @var array
     */
    private $_allowed_entries = array();

    /**
     * Property that houses the notices to be displayed on cart.
     *
     * @since 1.4
     * @access private
     * @var array
     */
    private $_notices = array();

    /**
     * Property that houses the current BOGO Deal being processed.
     *
     * @since 1.4
     * @access private
     * @var string
     */
    private $_bogo_deal;

    /**
     * Property that houses all BOGO Deals in cart.
     *
     * @since 1.4
     * @access private
     * @var array
     */
    private $_all_bogo_deals = array();

    /**
     * Trigger/deal entry default and allowed prop values.
     * This acts as a template for the entry.
     *
     * @since 1.4
     * @access private
     * @var array
     */
    private $_default_entry = array(
        'key'           => '',
        'coupon'        => '',
        'entry_id'      => '',
        'type'          => 'deal',
        'quantity'      => 0,
        'discount'      => 0,
        'discount_type' => '',
        'name'          => '',
    );

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
     */
    public function __construct()
    {
        foreach (\WC()->cart->get_applied_coupons() as $coupon_code) {

            $coupon    = new Advanced_Coupon($coupon_code);
            $bogo_deal = $coupon->is_type('acfw_bogo') ? $this->_get_bogo_deal_object($coupon) : null;

            // skip if coupon has no BOGO deal.
            if (!$bogo_deal || !$bogo_deal->is_bogo_deal) {
                continue;
            }

            $this->_all_bogo_deals[$coupon->get_code()] = $bogo_deal;
        }
    }

    /**
     * Get calculation object instance (singleton pattern).
     *
     * @since 1.4
     * @access public
     */
    public static function get_instance()
    {
        if (!self::$_instance instanceof self) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /*
    |--------------------------------------------------------------------------
    | Session Methods
    |--------------------------------------------------------------------------
     */

    /**
     * Get cart session hash for BOGO.
     *
     * @since 1.4
     * @access public
     *
     * @return string BOGO cart hash.
     */
    public function get_cart_session_hash()
    {
        $cart_session = array(
            'bogo_data' => array_map(function ($b) {
                return $b->get_data_for_session();
            }, $this->_all_bogo_deals),
            'items'     => array_map(function ($i) {
                return array(
                    'key'          => $i['key'],
                    'product_id'   => $i['product_id'],
                    'variation_id' => $i['variation_id'],
                    'quantity'     => $i['quantity'],
                );
            }, \WC()->cart->get_cart()),
        );

        return !empty($cart_session) ? md5(wp_json_encode($cart_session)) : '';
    }

    /**
     * Check if BOGO calculated data is available when cart item's have not changed.
     *
     * @since 1.4
     * @access public
     *
     * @return bool True if session data is available, false otherwise.
     */
    public function is_calculated_from_session()
    {
        // compare bogo cart hash session from actual cart hash session.
        $bogo_hash = \WC()->session->get('acfw_bogo_cart_hash');
        if (!$bogo_hash || $this->get_cart_session_hash() !== $bogo_hash) {
            return false;
        }

        // make sure matched entries data are available.
        $session_entries = \WC()->session->get('acfw_bogo_entries');
        if (!is_array($session_entries) || !isset($session_entries['matched']) || empty($session_entries['matched'])) {
            return false;
        }

        // load data from session.
        $this->_matched_entries = $session_entries['matched'];
        $this->_notices         = isset($session_entries['notices']) ? $session_entries['notices'] : array();

        return true;
    }

    /**
     * Set BOGO session data.
     *
     * @since 1.4
     * @access public
     */
    public function set_session_data()
    {
        \WC()->session->set('acfw_bogo_cart_hash', $this->get_cart_session_hash());
        \WC()->session->set('acfw_bogo_entries', array(
            'matched' => $this->_matched_entries,
            'notices' => $this->_notices,
        ));
    }

    /**
     * Clear BOGO session data.
     *
     * @since 1.4
     * @access public
     */
    public static function clear_session_data()
    {
        \WC()->session->set('acfw_bogo_cart_hash', null);
        \WC()->session->set('acfw_bogo_entries', null);
    }

    /*
    |--------------------------------------------------------------------------
    | Getter Methods
    |--------------------------------------------------------------------------
     */

    /**
     * Get all matched/allowed entries.
     *
     * @since 1.4
     * @access public
     *
     * @param string $db Entries db.
     * @return array Matched/allowed entries.
     */
    public function get_all_entries($db = 'matched')
    {
        switch ($db) {
            case 'matched':
                return $this->_matched_entries;
            case 'allowed':
                return $this->_allowed_entries;
            case 'temp':
                return $this->_temp_entries;
            case 'all_matched':
                return array_merge(array_values($this->_matched_entries), array_values($this->_temp_entries));
        }

        return array();
    }

    /**
     * Get cart item entries by cart item key and/or coupon code.
     *
     * @since 1.4
     * @access public
     *
     * @param string $coupon_code Coupon code.
     * @param string $db          Entries db.
     * @return array Matched/allowed entries.
     */
    public function get_entries_by_coupon($coupon_code, $type = 'deal', $db = 'matched')
    {
        return array_filter($this->get_all_entries($db), function ($e) use ($coupon_code, $type) {
            return $e['coupon'] === $coupon_code && $e['type'] === $type;
        });
    }

    /**
     * Get entries by entry ID and coupon code.
     *
     * @since 1.4
     * @access public
     *
     * @param string $entry_id    Entry ID.
     * @param string $coupon_code Coupon code.
     * @param string $db          Entries db.
     * @return array Matched/allowed entries.
     */
    public function get_entries_by_id_and_coupon($entry_id, $coupon_code, $db = 'matched')
    {
        return array_filter($this->get_all_entries($db), function ($e) use ($entry_id, $coupon_code) {
            return $e['entry_id'] === $entry_id && $e['coupon'] === $coupon_code;
        });
    }

    /**
     * Get cart item entries by cart item key.
     *
     * @since 1.4
     * @access public
     *
     * @param string $cart_item_key Cart item key.
     * @param string $type          trigger/deal/both.
     * @return array Matched/allowed entries.
     */
    public function get_entries_by_cart_item($cart_item_key, $type = 'both')
    {
        return array_filter(
            $this->get_all_entries('all_matched'),
            function ($e) use ($cart_item_key, $type) {
                if ('both' === $type) {
                    return $e['key'] === $cart_item_key;
                }

                return $e['key'] === $cart_item_key && $e['type'] === $type;
            }
        );
    }

    /**
     * Get all notices.
     *
     * @since 1.4
     * @access public
     *
     * @return array
     */
    public function get_notices()
    {
        return $this->_notices;
    }

    /**
     * Get the BOGO Deal that is currently being processed in calculation.
     *
     * @since 1.4
     * @access public
     *
     * @return Abstract_BOGO_Deal $bogo_deal
     */
    public function get_current_bogo_deal()
    {
        return $this->_bogo_deal;
    }

    /**
     * Get all BOGO Deals in cart.
     *
     * @since 1.4
     * @access public
     *
     * @return array List of all BOGO Deals
     */
    public function get_all_bogo_deals()
    {
        return $this->_all_bogo_deals;
    }

    /*
    |--------------------------------------------------------------------------
    | Setter Methods
    |--------------------------------------------------------------------------
     */

    /**
     * Set the current BOGO Deal to be processed.
     *
     * @since 1.4
     * @access public
     *
     * @param Abstract_BOGO_Deal $bogo_deal   BOGO Deal object.
     * @param string             $coupon_code Coupon code.
     */
    public function set_bogo_deal(Abstract_BOGO_Deal $bogo_deal)
    {
        $this->_bogo_deal = $bogo_deal;
    }

    /**
     * Append matched/allowed entry.
     *
     * @since 1.4
     * @access public
     *
     * @param array  $args      Entry arguments.
     * @param string $db        Entries property.
     * @param string $entry_key Entry key (if provided, then skip matching defaults and generating new key).
     */
    public function append_entry($args, $db = 'matched', $entry_key = '')
    {
        // get values from arguments that only exists in the default entry.
        $args = $entry_key ? $args : shortcode_atts($this->_default_entry, $args);

        // if required data is missing, then skip.
        if (!$args['key'] || !$args['coupon'] || !$args['entry_id']) {
            return false;
        }

        // generate a unique hash key based on the arguments.
        $entry_key = $entry_key ? $entry_key : $this->_generate_entry_key($args);

        if ('allowed' === $db) {
            $this->_allowed_entries[$entry_key] = $args;
        } elseif ('temp' === $db) {
            $this->_temp_entries[$entry_key] = $args;
        } else {
            $this->_matched_entries[$entry_key] = $args;
        }

        return true;
    }

    /**
     * Update entry by incrementing the quantity if it already exists.
     * Create new entry if it's not yet existing.
     *
     * @since 1.4
     * @access public
     *
     * @param array  $args Entry arguments.
     * @param string $db   Entries property.
     */
    public function update_entry($args, $db = 'matched')
    {
        // get values from arguments that only exists in the default entry.
        $args = shortcode_atts($this->_default_entry, $args);

        // generate a unique hash key based on the arguments.
        $entry_key = $this->_generate_entry_key($args);

        if ('allowed' === $db && array_key_exists($entry_key, $this->_allowed_entries)) {
            $this->_allowed_entries[$entry_key]['quantity'] += $args['quantity']; // increment quantity in allowed.
        } elseif ('temp' === $db && array_key_exists($entry_key, $this->_temp_entries)) {
            $this->_temp_entries[$entry_key]['quantity'] += $args['quantity']; // increment quantity in temp.
        } elseif ('matched' === $db && array_key_exists($entry_key, $this->_matched_entries)) {
            $this->_matched_entries[$entry_key]['quantity'] += $args['quantity']; // increment quantity in matched.
        } else {
            $this->append_entry($args, $db, $entry_key); // create new entry.
        }
    }

    /**
     * Append notice for coupon.
     *
     * @since 1.4
     * @access public
     */
    public function add_notice($message, $type, $coupon_code)
    {
        $this->_notices[$coupon_code] = array(
            'message'     => $message,
            'type'        => $type,
            'coupon_code' => $coupon_code,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Calculate and implementation methods
    |--------------------------------------------------------------------------
     */

    /**
     * Calculate the spare quantity of a cart item by the deducting the sum of all item quantity entries.
     *
     * @since 1.4
     * @access public
     *
     * @param array $cart_item Cart item data.
     * @return int Cart item spare quantity.
     */
    public function calculate_cart_item_spare_quantity($cart_item)
    {
        if (!is_array($cart_item) || empty($cart_item)) {
            return 0;
        }

        $key          = $cart_item['key'];
        $quantity     = $cart_item['quantity'];
        $item_entries = $this->get_entries_by_cart_item($cart_item['key']);

        return max(0, $quantity - array_sum(array_column($item_entries, 'quantity')));
    }

    /**
     * Verify triggers of a BOGO Deal from cart spare quantities.
     *
     * @since 1.4
     * @access public
     *
     * @param boolean $save_temp Toggle if we need to save temporary matched triggers or not.
     * @return boolean True if verified, false otherwise.
     */
    public function verify_triggers($trigger_only = false, $save_temp = true)
    {
        $context    = $trigger_only ? 'trigger_only' : 'trigger';
        $cart_items = $this->get_sorted_cart_items_by_price($context); // sort prices from highest to lowest.

        foreach ($this->_bogo_deal->triggers as $trigger) {

            $entry_id = $trigger['entry_id'];

            foreach ($cart_items as $key => $cart_item) {

                if (
                    $this->is_item_valid($cart_item) // make sure item is not already discounted by other ACFW features.
                     && $this->_bogo_deal->is_cart_item_match_entries($cart_item, $trigger) // check if item matches the trigger ids.
                ) {
                    $needed_qty = $this->_bogo_deal->get_needed_trigger_quantity($entry_id);
                    $quantity   = min($needed_qty, $this->calculate_cart_item_spare_quantity($cart_item));

                    if ($quantity) {
                        $this->_bogo_deal->set_needed_triggers_quantity($entry_id, max(0, $needed_qty - $quantity));

                        if ($save_temp) {
                            $this->update_entry(
                                array(
                                    'key'      => $cart_item['key'],
                                    'coupon'   => $this->_bogo_deal->get_coupon()->get_code(),
                                    'entry_id' => $entry_id,
                                    'type'     => 'trigger',
                                    'quantity' => $quantity,
                                    'name'     => $cart_item['data']->get_name(),
                                ),
                                'temp'
                            );
                        }
                    }
                }
            } // endforeach cart items

            // if a single row trigger wasn't fully met for this instance then skip other rows.
            if (0 < $this->_bogo_deal->get_needed_trigger_quantity($entry_id)) {
                break;
            }
        } // endforeach triggers

        /**
         * When sum of the needed quantity for all triggers is 0, then it means trigger for the BOGO deal is verified.
         * The function needs to return true to confirm that the triggers are verified.
         */
        return 1 > $this->_bogo_deal->get_needed_trigger_quantity();
    }

    /**
     * Assign spare quantity of matching items as deals for the provided BOGO Deal object.
     *
     * @since 1.4
     * @access public
     */
    public function verify_deals()
    {
        $cart_items = $this->get_sorted_cart_items_by_price('deal'); // sort prices from lowest to highest.

        foreach ($this->_bogo_deal->deals as $deal) {
            
            $entry_id = $deal['entry_id'];

            foreach ($cart_items as $cart_item) {

                if (
                    $this->is_item_valid($cart_item) // make sure item is not already discounted by other ACFW features.
                     && $this->_bogo_deal->is_cart_item_match_entries($cart_item, $deal, true) // check if item matches the trigger ids.
                ) {
                    $allowed_qty = $this->_bogo_deal->get_allowed_deal_quantity($entry_id);
                    $quantity    = min($allowed_qty, $this->calculate_cart_item_spare_quantity($cart_item));

                    if ($quantity) {
                        $this->_bogo_deal->set_allowed_deal_quantity($entry_id, max(0, $allowed_qty - $quantity));
                        $this->update_entry(
                            array(
                                'key'           => $cart_item['key'],
                                'coupon'        => $this->_bogo_deal->get_coupon()->get_code(),
                                'entry_id'      => $entry_id,
                                'type'          => 'deal',
                                'quantity'      => $quantity,
                                'discount_type' => $deal['type'],
                                'discount'      => $deal['discount'],
                                'name'          => $cart_item['data']->get_name(),
                            ),
                            'temp'
                        );
                    }
                }

            } // endforeach cart items

        } // endforeach deals

        return $this->_bogo_deal->has_deal_fulfilled();
    }

    /**
     * Process the allowed deals data.
     *
     * @since 1.4
     * @access public
     */
    public function process_allowed_deals_data()
    {
        foreach ($this->_bogo_deal->allowed_deals as $entry_id => $allowed_quantity) {
            if ($allowed_quantity) {
                $this->update_entry(
                    array(
                        'key'      => 'allowed',
                        'coupon'   => $this->_bogo_deal->get_coupon()->get_code(),
                        'entry_id' => $entry_id,
                        'type'     => 'deal',
                        'quantity' => $allowed_quantity,
                    ),
                    'allowed'
                );
            }
        } // endforeach allowed deals
    }

    /**
     * Confirm the temporarily matched triggers.
     *
     * @since 1.4
     * @access public
     */
    public function confirm_matched_triggers()
    {
        foreach ($this->_temp_entries as $temp_entry) {
            $this->update_entry($temp_entry);
        }
    }

    /**
     * Clear temporary matched entries.
     *
     * @since 1.4
     * @access public
     *
     * @param string $type Type of entries to clear.
     */
    public function clear_temp_entries($type = 'both')
    {
        if ('both' === $type) {
            $this->_temp_entries = array();
        } else {
            $this->_temp_entries = array_filter($this->_temp_entries, function ($e) use ($type) {
                return $e['type'] !== $type;
            });
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Utility methods
    |--------------------------------------------------------------------------
     */

    /**
     * Get BOGO Deal object for coupon.
     *
     * @since 1.4
     * @access private
     *
     * @param Advanced_Coupon $coupon Coupon object.
     * @return Abstract_BOGO_Deal BOGO Deal object.
     */
    private function _get_bogo_deal_object(Advanced_Coupon $coupon)
    {
        return new Advanced($coupon);
    }

    /**
     * Get sorted cart items by price descendingly (highest to lowest).
     *
     * @since 1.4
     * @access public
     *
     * @param string $order Sort order.
     * @param array  $cart_items Cart items.
     * @return array Cart items data.
     */
    public function get_sorted_cart_items_by_price($context = 'trigger', $cart_items = array())
    {
        if (!$cart_items || empty($cart_items)) {
            $cart_items = \WC()->cart->get_cart();
        }

        $order = 'deal' === $context ? 'asc' : 'desc';
        $order = apply_filters('acfw_bogo_sort_cart_items_order', $order, $context, $this->_bogo_deal);

        $sorted = \ACFWF()->Helper_Functions->sort_cart_items_by_price($cart_items, $order);

        // return cart items that can only be matched as triggers.
        if ('trigger_only' === $context) {

            $deal_ids = array_unique(array_reduce($this->_bogo_deal->deals, function ($c, $d) {
                return array_merge($c, $d['ids']);
            }, array()));

            $trigger_items = array_filter($sorted, function ($i) use ($deal_ids) {
                $intersect = array_intersect(array($i['product_id'], $i['variation_id']), $deal_ids);
                return empty($intersect);
            });

            return $trigger_items;
        }

        return $sorted;
    }

    /**
     * Generate a hash key based on a trigger/deal entry cart item key, coupon, entry ID and type.
     *
     * @since 1.4
     * @access public
     *
     * @param array $entry Trigger/deal entry.
     * @return string Entry key hash value.
     */
    private function _generate_entry_key($entry)
    {
        extract($entry);
        return md5(sprintf('%s_%s_%s_%s', $key, $coupon, $entry_id, $type));
    }

    /**
     * Check if the cart item is valid as a deal or trigger.
     *
     * @since 1.4
     * @access public
     *
     * @param array $cart_item Cart item data.
     * @return bool True if valid, false otherwise.
     */
    public function is_item_valid($cart_item)
    {
        return apply_filters('acfw_bogo_is_item_valid', true, $cart_item);
    }
}
