<?php

namespace ACFWF\Models\Objects;

use ACFWF\Helpers\Helper_Functions;
use ACFWF\Helpers\Plugin_Constants;

/**
 * Model that houses the data model of an advanced coupon object.
 *
 * @since 1.0
 */
class Advanced_Coupon extends \WC_Coupon
{

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
     */

    /**
     * Model that houses the main plugin object.
     *
     * @since 1.0
     * @access private
     * @var Abstract_Main_Plugin_Class
     */
    private $_main_plugin;

    /**
     * Model that houses all the plugin constants.
     *
     * @since 1.0
     * @access protected
     * @var Plugin_Constants
     */
    protected $_constants;

    /**
     * Property that houses all the helper functions of the plugin.
     *
     * @since 1.0
     * @access protected
     * @var Helper_Functions
     */
    protected $_helper_functions;

    /**
     * Stores advanced coupon data.
     *
     * @since 1.0
     * @access private
     * @var array
     */
    protected $advanced_data = array();

    /**
     * This is where changes to the $data will be saved.
     *
     * @since 1.0
     * @access private
     * @var object
     */
    protected $advanced_changes = array();

    /**
     * Stores boolean if the data has been read from the database or not.
     *
     * @since 1.0
     * @access private
     * @var object
     */
    protected $advanced_read = false;

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
     * @param mixed $code WC_Coupon ID, code or object.
     */
    public function __construct($code)
    {
        $this->_constants        = \ACFWF()->Plugin_Constants;
        $this->_helper_functions = \ACFWF()->Helper_Functions;

        // if provided value is int, then get the equivalent coupon code.
        if (is_int($code)) {
            $code = absint($code);
            $this->set_id($code);
        } elseif (is_string($code)) {

            // check if code has a valid equivalent ID.
            $temp = wc_get_coupon_id_by_code($code);
        }

        // make sure that the provided parameter is valid.
        if (is_a($code, 'WC_Coupon') || is_string($code) || is_int($code)) {
            // construct parent object and set the code.
            parent::__construct($code);
        } else {
            trigger_error('Invalid parameter provided for Advanced_Coupon. It either needs a coupon code string, coupon ID, or a WC_Coupon object.');
            return;
        }

        $this->advanced_read();
    }

    /**
     * Read data from DB and save on instance.
     *
     * @since 1.0
     * @access public
     */
    private function advanced_read()
    {
        if ($this->advanced_read || get_post_type($this->id) !== 'shop_coupon') {
            return;
        }

        $default_data        = $this->_default_advanced_data();
        $this->advanced_data = $default_data;
        $meta_data           = get_metadata('post', $this->id);

        foreach ($default_data as $prop => $value) {

            // if meta doesn't exist yet, then set value to null.
            if (!isset($meta_data[Plugin_Constants::META_PREFIX . $prop])) {
                $this->advanced_data[$prop] = $this->handle_read_empty_value($prop, $meta_data);
                continue;
            }

            // fetch raw meta data if present.
            $raw_data = maybe_unserialize(current($meta_data[Plugin_Constants::META_PREFIX . $prop]));

            // make sure fetched data has the correct type.
            switch ($prop) {

                case 'disable_url_coupon':
                case 'force_apply_url_coupon':
                case 'enable_role_restriction':
                case 'role_restrictions_type':
                case 'success_message':
                case 'after_redirect_url':
                case 'role_restrictions_error_msg':
                    $this->advanced_data[$prop] = 'string' === gettype($raw_data) ? $raw_data : $default_data[$prop];
                    break;

                // always return the editable slug version for override value.
                case 'code_url_override':
                    $this->advanced_data[$prop] = 'string' === gettype($raw_data) ? apply_filters('editable_slug', $raw_data) : $default_data[$prop];
                    break;

                case 'cart_conditions':
                case 'cart_condition_notice':
                case 'role_restrictions':
                case 'bogo_deals':
                    $this->advanced_data[$prop] = is_array($raw_data) ? $raw_data : $default_data[$prop];
                    break;

                default:
                    $prop_value                 = $this->advanced_read_property($raw_data, $prop, $default_data[$prop], $meta_data);
                    $this->advanced_data[$prop] = apply_filters('acfw_read_advanced_coupon_property', $prop_value, $prop, $default_data, $this->id, $meta_data);
                    break;
            }
        }

        $this->advanced_read = true;

    }

    /**
     * Advanced read property.
     *
     * @since 1.0
     * @access protected
     *
     * @param mixed  $raw_data     Property raw data value.
     * @param string $prop         Property name.
     * @param string $default_data Default data value.
     * @param array  $meta_data    Coupon metadata list.
     * @return mixed Data value.
     */
    protected function advanced_read_property($raw_data, $prop, $default_data, $meta_data)
    {
        return $raw_data;
    }

    /**
     * Handle when propert meta value is empty.
     *
     * @since 1.4
     * @access protected
     *
     * @param string $prop Property name.
     * @param array  $meta_data Coupon meta data.
     * @return mixed Property value.
     */
    protected function handle_read_empty_value($prop, $meta_data)
    {
        return null;
    }

    /**
     * Return advanced coupon default data.
     *
     * @since 1.0
     * @access private
     *
     * @return array Advanced coupon default data.
     */
    private function _default_advanced_data()
    {
        $default_advanced_data = array(
            'disable_url_coupon'          => '',
            'force_apply_url_coupon'      => '',
            'code_url_override'           => '',
            'enable_role_restriction'     => '',
            'role_restrictions_type'      => 'allowed',
            'role_restrictions_error_msg' => '',
            'cart_conditions'             => array(),
            'cart_condition_notice'       => array(),
            'role_restrictions'           => array(),
            'bogo_deals'                  => array(),
            'success_message'             => '',
            'after_redirect_url'          => '',
        );

        return array_merge(
            $default_advanced_data,
            $this->extra_default_advanced_data(),
            apply_filters('acfw_default_data', array()) // for third party integration.
        );
    }

    /**
     * Return extra default advanced data.
     *
     * @since 1.0
     * @access protected
     *
     * @return array Extra default advanced data.
     */
    protected function extra_default_advanced_data()
    {
        return array();
    }

    /*
    |--------------------------------------------------------------------------
    | Data getters
    |--------------------------------------------------------------------------
     */

    /**
     * Return data property.
     *
     * @since 1.0
     * @access public
     *
     * @param string $prop    Data property slug.
     * @param mixed  $default Set property default value (optional).
     * @param bool   $global  Toggle check for getting global option value.
     * @return mixed Property data.
     */
    public function get_advanced_prop($prop, $default = null, $global = false)
    {
        if (!isset($this->_default_advanced_data()[$prop])) {
            return;
        }

        // try to return saved value.
        $value = isset($this->advanced_data[$prop]) ? $this->advanced_data[$prop] : null;
        if ($this->is_advanced_value_valid($value, $prop)) {
            return $this->get_string_meta($value, $prop);
        }

        // try to return global value if available.
        $value = $global ? $this->get_advanced_prop_global_value($prop) : null;
        if (!is_null($value)) {
            return $value;
        }

        // return default argument value.
        if (!is_null($default)) {
            return $default;
        }

        // return default prop value.
        return $this->_default_advanced_data()[$prop];
    }

    /**
     * Return data property for edit context.
     *
     * @since 1.3
     * @access public
     *
     * @param string $prop    Data property slug.
     * @param mixed  $default Set property default value (optional).
     * @return mixed Property data.
     */
    public function get_advanced_prop_edit($prop, $default = null)
    {
        if (!isset($this->_default_advanced_data()[$prop])) {
            return;
        }

        // try to return saved value.
        $value = isset($this->advanced_data[$prop]) ? $this->advanced_data[$prop] : null;
        if ($this->is_advanced_value_valid($value, $prop)) {
            return $value;
        }

        // return default argument value.
        if (!is_null($default)) {
            return $default;
        }

        // return default prop value.
        return $this->_default_advanced_data()[$prop];
    }

    /**
     * Return changed data property.
     *
     * @since 1.0
     * @access public
     *
     * @param string $prop    Data property slug.
     * @param mixed  $default Set property default value (optional).
     * @return mixed Property data.
     */
    public function get_advanced_changed_prop($prop, $default = null)
    {
        return isset($this->advanced_changes[$prop]) ? $this->advanced_changes[$prop] : $this->get_advanced_prop_edit($prop, $default);

    }

    /**
     * Get the properties global option value.
     *
     * @since 1.0
     * @access public
     *
     * @param string $prop Name of property.
     * @return string|null Global option value.
     */
    public function get_advanced_prop_global_value($prop)
    {
        $option = '';
        switch ($prop) {
            case 'success_message':
                $option = Plugin_Constants::CUSTOM_SUCCESS_MESSAGE_GLOBAL;
                break;
            case 'after_redirect_url':
                $option = Plugin_Constants::AFTER_APPLY_COUPON_REDIRECT_URL_GLOBAL;
                break;
            case 'role_restrictions_error_msg':
                $option = Plugin_Constants::ROLE_RESTRICTIONS_ERROR_MESSAGE;
                break;
            default:
                $option = $this->get_extra_advanced_prop_global_value($prop);
                break;
        }

        $value = $option ? $this->get_string_option($option) : null;
        return $this->is_advanced_value_valid($value, $prop) ? $value : null;
    }

    /**
     * Get extra get advanced prop global value.
     *
     * @since 1.0
     * @access protected
     *
     * @param string $prop Property name.
     * @return string Property global option name.
     */
    protected function get_extra_advanced_prop_global_value($prop)
    {
        return '';
    }

    /**
     * Get coupon URL.
     *
     * @since 1.0
     * @access public
     *
     * @return string Coupon URL
     */
    public function get_coupon_url()
    {
        if (get_post_status($this->id) == 'auto-draft') {
            return;
        }

        $coupon_permalink = get_permalink($this->id, true);
        $override         = $this->get_advanced_prop('code_url_override');
        $slug             = $override ? $override : $this->get_code();

        // sanitize for comma and colon.
        $slug = str_replace(array(':', ','), array('%3A', '%2C'), $slug);

        // build permalink.
        $coupon_permalink = str_replace('%shop_coupon%', $slug, $coupon_permalink);

        return $coupon_permalink;

    }

    /**
     * Get valid redirect URL.
     *
     * @since 1.0
     * @access public
     *
     * @return string Valid redirect URL.
     */
    public function get_valid_redirect_url()
    {
        $global_redirect_url = $this->get_string_option(Plugin_Constants::AFTER_APPLY_COUPON_REDIRECT_URL_GLOBAL, '');
        $redirect_url        = $this->get_advanced_prop('after_redirect_url', $global_redirect_url);

        return filter_var($redirect_url, FILTER_VALIDATE_URL);
    }

    /**
     * Get advanced coupon error message.
     *
     * @since 1.0
     * @access public
     *
     * @return string Valid redirect URL.
     */
    public function get_advanced_error_message()
    {
        if (!$this->id) {
            return sprintf(__('Coupon "%s" is either incorrect, disabled or does not exist.', 'advanced-coupons-for-woocommerce-free'), $this->data['code']);
        }

        if ($this->get_advanced_prop('disable_url_coupon') != 'yes') {
            return;
        }

        $custom_message = trim($this->get_string_option(Plugin_Constants::CUSTOM_DISABLE_MESSAGE, ''));
        return $custom_message ? $custom_message : __('Inactive coupon url', 'advanced-coupons-for-woocommerce-free');
    }

    /**
     * Get BOGO apply deals notice settings.
     *
     * @since 1.0
     * @access public
     *
     * @return array BOGO apply deals notice settings.
     */
    public function get_bogo_notice_settings()
    {
        $bogo_deals = $this->get_advanced_prop('bogo_deals', array());
        if (!is_array($bogo_deals) || empty($bogo_deals)) {
            return;
        }

        $temp = isset($bogo_deals['notice_settings']) && is_array($bogo_deals['notice_settings']) ? $bogo_deals['notice_settings'] : array();

        return array(
            'message'     => isset($temp['message']) && $temp['message'] ? $this->get_string_meta($temp['message'], 'bogo_deals_notice_message') : $this->get_string_option(Plugin_Constants::BOGO_DEALS_NOTICE_MESSAGE),
            'button_text' => isset($temp['button_text']) && $temp['button_text'] ? $this->get_string_meta($temp['button_text'], 'bogo_deals_notice_button_text') : $this->get_string_option(Plugin_Constants::BOGO_DEALS_NOTICE_BTN_TEXT),
            'button_url'  => isset($temp['button_url']) && $temp['button_url'] ? $this->get_string_meta($temp['button_url'], 'bogo_deals_notice_button_url') : $this->get_string_option(Plugin_Constants::BOGO_DEALS_NOTICE_BTN_URL),
            'notice_type' => isset($temp['notice_type']) && 'global' !== $temp['notice_type'] ? $temp['notice_type'] : get_option(Plugin_Constants::BOGO_DEALS_NOTICE_TYPE),
        );
    }

    /**
     * Get string meta value.
     * Add support for 3rd part translation plugins.
     *
     * @since 1.3
     * @access public
     *
     * @param mixed  $value   Prop value.
     * @param string $prop    Prop name.
     * @return mixed Filtered prop value.
     */
    public function get_string_meta($value, $prop)
    {
        return $value && gettype($value) === "string" ? apply_filters('acfw_string_meta', $value, $prop, $this) : $value;
    }

    /**
     * Get string option value.
     * Add support for 3rd party translation plugins.
     *
     * @since 1.3
     * @access public
     *
     * @param string $option Option value.
     * @return string Filtered option value.
     */
    public function get_string_option($option)
    {
        $value = get_option($option);
        return $value && gettype($value) === "string" ? apply_filters('acfw_string_option', $value, $option) : $value;
    }

    /**
     * Get cart condition data for editing context.
     * NOTE: this only formats the subtotal field value to make sure it properly displayed based on local currency settings.
     *
     * @since 1.3.3
     * @since 1.6   Remove group logics that are next to empty groups.
     * @access public
     *
     * @return array Formatted cart conditions data.
     */
    public function get_formatted_cart_conditions_edit()
    {
        $cart_conditions = $this->get_advanced_prop_edit('cart_conditions', array());
        $formatted       = array();

        if (!is_array($cart_conditions) || empty($cart_conditions)) {
            return array();
        }

        $prev_group_type = "";

        foreach ($cart_conditions as $condition_group) {
            if ('group' === $condition_group['type']) {

                $prev_group_type = "group";

                $fields = array_map(function ($f) {

                    switch ($f['type']) {

                        case "cart-subtotal":
                            $f['data']['value'] = wc_format_localized_price($f['data']['value']);
                            break;

                        default:
                            $f = apply_filters('acfw_format_edit_cart_condition_field', $f, $this);
                    }

                    return $f;

                }, $condition_group['fields']);

                $formatted[] = array(
                    'type'   => 'group',
                    'fields' => $fields,
                );
            } else {

                // make sure that the previous data is a valid group and not a logic.
                if ($prev_group_type && "group" === $prev_group_type) {
                    $formatted[] = $condition_group;
                }

                $prev_group_type = "group_logic";
            }
        }

        // if the last data added was for logic, then remove it.
        // the last item of the cart condition needs to be a valid group.
        if ("group_logic" === $prev_group_type) {
            array_pop($formatted);
        }

        return $formatted;
    }

    /**
     * Get BOGO deals data for editing context.
     * NOTE: function only formats the discount value to make sure it properly displayed based on local currency settings.
     *
     * @since 1.3.3
     * @access public
     *
     * @return array Formatted cart conditions data.
     */
    public function get_formatted_bogo_deals_edit()
    {
        $bogo_deals = $this->get_advanced_prop_edit('bogo_deals');

        if (!is_array($bogo_deals) || !isset($bogo_deals['deals'])) {
            return array();
        }

        $formatted_deals = $bogo_deals['deals'];

        switch ($bogo_deals['deals_type']) {
            case "specific-products":
                $formatted_deals = array_map(function ($r) {
                    $r['discount_value'] = wc_format_localized_price($r['discount_value']);
                    return $r;
                }, $bogo_deals['deals']);
                break;

            default:
                $formatted_deals = apply_filters('acfw_format_bogo_apply_data', $formatted_deals, $bogo_deals, $this);
                break;
        }

        // overwrite apply data with formated version.
        $bogo_deals['deals'] = $formatted_deals;

        return apply_filters('acfw_format_bogo_deals_edit', $bogo_deals, $this);
    }

    /*
    |--------------------------------------------------------------------------
    | Data setters
    |--------------------------------------------------------------------------
     */

    /**
     * Set new value to properties and save it to $changes property.
     * This stores changes in a special array so we can track what needs to be saved on the DB later.
     *
     * @since 1.0
     * @access public
     *
     * @param string $prop Data property slug.
     * @param string $value New property value.
     */
    public function set_advanced_prop($prop, $value)
    {
        if ($this->_validate_advanced_change($prop, $value)) {

            if ('schedule_expire' === $prop) {
                return;
            }

            if (gettype($value) == gettype($this->_default_advanced_data()[$prop])) {
                $this->advanced_changes[$prop] = $value;
            } else {

                // TODO: handle error here.

            }

        } else {

            // false save
            $this->advanced_data[$prop] = $value;
        }

    }

    /**
     * Validate advanced change property value.
     *
     * @since 1.4.2
     * @access protected
     *
     * @param string $prop Data property slug.
     * @param string $value New property value.
     * @return bool True if valid, false otherwise
     */
    protected function _validate_advanced_change($prop, $value)
    {
        if (array_key_exists($prop, $this->_default_advanced_data()) && !is_null($value)) {

            $type = gettype($this->_default_advanced_data()[$prop]);

            switch ($type) {

                case 'boolean':
                    return is_bool($value);

                case 'array':
                    return is_array($value);

                case 'integer':
                case 'double':
                case 'string':
                default:
                    return !is_null($value);
            }
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Save advanced coupon data to DB
    |--------------------------------------------------------------------------
     */

    /**
     * Save data in $changes to the database.
     *
     * @since 1.0
     * @access public
     *
     * @return WP_Error | int On success will return the post ID, otherwise it will return a WP_Error object.
     */
    public function advanced_save()
    {
        if (empty($this->advanced_changes)) {
            return new \WP_Error('acfw_advanced_coupon_no_changes', 'Unable to save advanced coupon as there are no changes registered on the object yet.', array('changes' => $this->advanced_changes, 'coupon' => $this));
        }

        foreach ($this->advanced_changes as $prop => $value) {

            if ($this->is_skip_save_advanced_prop($value, $prop)) {
                continue;
            }

            update_post_meta($this->id, Plugin_Constants::META_PREFIX . $prop, $value);
        }

        // re-read the object
        $this->advanced_read = false;
        $this->advanced_read();

        return $this->id;
    }

    /**
     * Check if to skip saving the advanced prop value as post meta.
     *
     * @since 1.0
     * @access protected
     *
     * @param mixed  $value Property value.
     * @param string $prop  Property name.
     * @param bool True if skip, false otherwise.
     */
    protected function is_skip_save_advanced_prop($value, $prop)
    {
        return false;
    }

    /**
     * Set coupon property to a global option cache.
     *
     * @since 1.0
     * @access protected
     */
    protected function save_prop_to_global_option_cache($option_name, $value)
    {
        $global_option = $this->_helper_functions->get_option($option_name, array());

        if ($value) {
            $global_option[] = $this->id;
        } else {
            $key = array_search($this->id, $global_option);
            if (false !== $key) {
                unset($global_option[$key]);
            }

        }

        update_option($option_name, array_unique($global_option));
    }

    /*
    |--------------------------------------------------------------------------
    | Utility Functions.
    |--------------------------------------------------------------------------
     */

    /**
     * Is advanced value valid
     *
     * @since 1.4
     * @access public
     *
     * @param mixed $value Value to check.
     * @param string $prop Property name.
     * @return boolean True if valid, false otherwise.
     */
    public function is_advanced_value_valid($value, $prop)
    {
        $type = gettype($this->_default_advanced_data()[$prop]);

        switch ($type) {
            case 'string':
                return !is_null($value) && !empty($value);
            case 'array':
                return is_array($value) && !empty($value);
            default:
                return !is_null($value);
        }
    }

    /**
     * Check if coupon is valid.
     *
     * @since 1.0
     * @access public
     * @deprecated 1.3.4
     *
     * @return boolean Flag that determines if coupon is valid or not.
     */
    public function is_advanced_coupon_valid()
    {
        wc_deprecated_function(
            "\ACFWF\Models\Objects\Advanced_Coupon::is_advanced_coupon_valid",
            "1.3.4",
            "\ACFWF\Models\Objects\Advanced_Coupon::is_coupon_url_valid"
        );

        return $this->is_coupon_url_valid();
    }

    /**
     * Check if coupon URL is valid.
     *
     * @since 1.3.4
     * @access public
     *
     * @return boolean Flag that determines if coupon is valid or not.
     */
    public function is_coupon_url_valid()
    {
        global $wp_query;

        // check if code override is turned on, and make sure override value and the query name is the same.
        // we sanitize the override value again cause query name will always have a sanitized equivalent value.
        // we also need to transform query name to all lowercase value here, cause sanitize_title replaces accents with small characters.
        $override = sanitize_title($this->get_advanced_prop('code_url_override'));
        if ($override && strtolower($wp_query->query['name']) !== $override) {
            return;
        }

        return $this->get_advanced_prop('disable_url_coupon') !== 'yes';
    }

    /**
     * Clone coupon post.
     *
     * @since 1.0
     * @access public
     *
     * @global wpdb $wpdb Object that contains a set of functions used to interact with a database.
     *
     * @return int Clone coupon ID.
     */
    public function advanced_clone()
    {
        global $wpdb;

        $orig_metas = get_post_meta($this->get_id());
        $categories = wp_get_post_terms($this->get_id(), Plugin_Constants::COUPON_CAT_TAXONOMY, array('fields' => 'ids'));

        // reset ID to 0.
        $this->set_id(0);

        // add suffix to coupon code.
        $suffix = apply_filters('acfw_advanced_clone_coupon_code_suffix', '-clone');
        $code   = $this->get_code() . $suffix;

        // update code, save (clone) coupon, update object id and save meta data.
        $this->set_code($code);
        $this->set_usage_count(0); // make sure usage count is set to 0.
        $this->set_used_by(array()); // make sure usage count per user is set to 0.
        $this->set_date_created(time()); // set date created to current time.
        $this->set_date_modified(time()); // set date modified to current time.
        $this->set_id($this->save()); // save new coupon.
        $this->save_meta_data();

        // save advanced meta data.
        $this->advanced_changes = $this->advanced_data;
        $this->advanced_save();

        // set status to draft.
        $post = array('ID' => $this->get_id(), 'post_status' => 'draft');
        wp_update_post($post);

        if (!is_wp_error($categories) && !empty($categories)) {
            wp_set_post_terms($this->get_id(), $categories, Plugin_Constants::COUPON_CAT_TAXONOMY);
        }

        $clone_id    = $this->get_id();
        $clone_metas = get_post_meta($clone_id);
        $other_metas = array_filter($orig_metas, function ($key) use ($clone_metas) {
            return !isset($clone_metas[$key]) && '_used_by' !== $key;
        }, ARRAY_FILTER_USE_KEY);

        // clone other non WC or ACFW related meta data.
        if (is_array($other_metas) && !empty($other_metas)) {

            $query = "INSERT INTO $wpdb->postmeta ( post_id , meta_key , meta_value ) VALUES";
            $first = true;

            foreach ($other_metas as $key => $value) {

                if (!$first) {
                    $query .= ",";
                }

                if (count($value) == 1) {

                    $clean_value = esc_sql($value[0]);
                    $query .= " ( $clone_id , '$key' , '$clean_value' )";

                } else {

                    $temp = array_map(function ($v) use ($clone_id, $key) {

                        $clean_value = esc_sql($v);
                        return " ( $clone_id , '$key' , '$clean_value' )";

                    }, $value);

                    $query .= implode(",", $temp);
                }

                $first = false;
            }

            if ($query) {
                $wpdb->query($query);
            }

        }

        return $this->get_id();
    }

    /**
     * Get coupon's discount value string (e.g. $20.00 fixed cart discount).
     *
     * @since 3.1
     * @access public
     *
     * @return string Discount value string.
     */
    public function get_discount_value_string()
    {
        $coupon_types  = wc_get_coupon_types();
        $discount_type = $this->get_discount_type();

        /**
         * Get proper discount type label.
         */
        if (isset($coupon_types[$discount_type])) {
            $discount_label = apply_filters('acfwf_discount_label', strtolower($coupon_types[$discount_type]));
        } else {
            $discount_label = apply_filters('acfwf_discount_label_missing', $this->get_discount_type(), $this);
        }

        /**
         * Get proper amount string format.
         * fixed price values needs to be formatted as price.
         * percent value needs to have the correct formatting.
         */
        switch ($discount_type) {
            case 'percent':
                $amount = sprintf('%s%%', wc_trim_zeros(
                    number_format(
                        $this->get_amount(),
                        wc_get_price_decimals(),
                        wc_get_price_decimal_separator(),
                        wc_get_price_thousand_separator()
                    )
                ));
                break;
            case 'fixed_cart':
            case 'fixed_product':
                $amount = wc_price($this->get_amount());
                break;
            default:
                $amount = apply_filters('acfwf_display_coupon_amount_value', $this->get_amount(), $discount_type, $this);
        }

        // set custom discount label for BOGO Deal type coupons.
        $message = 'acfw_bogo' === $discount_type ? __('BOGO Deal', 'advanced-coupons-for-woocommerce-free') : sprintf('%s %s', $amount, $discount_label);

        return apply_filters('acfwf_single_coupon_discount_value', $message, $amount, $discount_label, $this);
    }

    /**
     * Get coupon schedule string.
     *
     * @since 3.1
     * @access public
     *
     * @return string Coupon schedule string.
     */
    public function get_schedule_string()
    {
        $expire_date = $this->get_date_expires();
        return $expire_date instanceof \WC_DateTime ? sprintf(__('Valid until %s', ''), $expire_date->date_i18n(get_option('date_format'))) : '';
    }

}
