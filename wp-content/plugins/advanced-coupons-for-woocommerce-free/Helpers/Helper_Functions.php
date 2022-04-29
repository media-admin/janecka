<?php
namespace ACFWF\Helpers;

use ACFWF\Abstracts\Abstract_Main_Plugin_Class;
use ACFWF\Helpers\Plugin_Constants;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

/**
 * Model that houses all the helper functions of the plugin.
 *
 * 1.0.0
 */
class Helper_Functions
{

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
     */

    /**
     * Property that holds the single main instance of Helper_Functions.
     *
     * @since 1.0
     * @access private
     * @var Helper_Functions
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
     * @param Plugin_Constants $constants Plugin constants object.
     */
    public function __construct(Abstract_Main_Plugin_Class $main_plugin, Plugin_Constants $constants)
    {
        $this->_constants = $constants;
        $main_plugin->add_to_public_helpers($this);

    }

    /**
     * Ensure that only one instance of this class is loaded or can be loaded ( Singleton Pattern ).
     *
     * @since 1.0
     * @access public
     *
     * @param Plugin_Constants $constants Plugin constants object.
     * @return Helper_Functions
     */
    public static function get_instance(Abstract_Main_Plugin_Class $main_plugin, Plugin_Constants $constants)
    {
        if (!self::$_instance instanceof self) {
            self::$_instance = new self($main_plugin, $constants);
        }

        return self::$_instance;

    }

    /*
    |--------------------------------------------------------------------------
    | Helper Functions
    |--------------------------------------------------------------------------
     */

    /**
     * Write data to plugin log file.
     *
     * @since 1.0
     * @access public
     *
     * @param mixed Data to log.
     */
    public function write_debug_log($log)
    {
        error_log("\n[" . current_time('mysql') . "]\n" . $log . "\n--------------------------------------------------\n", 3, $this->_constants->LOGS_ROOT_PATH() . 'debug.log');

    }

    /**
     * Check if current user is authorized to manage the plugin on the backend.
     *
     * @since 1.0
     * @access public
     *
     * @param WP_User $user WP_User object.
     * @return boolean True if authorized, False otherwise.
     */
    public function current_user_authorized($user = null)
    {
        // Array of roles allowed to access/utilize the plugin
        $admin_roles = apply_filters('acfw_admin_roles', array('administrator'));

        if (is_null($user)) {
            $user = wp_get_current_user();
        }

        if ($user->ID) {
            return count(array_intersect((array) $user->roles, $admin_roles)) ? true : false;
        } else {
            return false;
        }

    }

    /**
     * Returns the timezone string for a site, even if it's set to a UTC offset
     *
     * Adapted from http://www.php.net/manual/en/function.timezone-name-from-abbr.php#89155
     *
     * Reference:
     * http://www.skyverge.com/blog/down-the-rabbit-hole-wordpress-and-timezones/
     *
     * @since 1.0
     * @access public
     *
     * @return string Valid PHP timezone string
     */
    public function get_site_current_timezone()
    {
        // if site timezone string exists, return it
        if ($timezone = trim(get_option('timezone_string'))) {
            return $timezone;
        }

        // get UTC offset, if it isn't set then return UTC
        $utc_offset = trim(get_option('gmt_offset', 0));

        if (filter_var($utc_offset, FILTER_VALIDATE_INT) === 0 || '' === $utc_offset || is_null($utc_offset)) {
            return 'UTC';
        }

        return $this->convert_utc_offset_to_timezone($utc_offset);

    }

    /**
     * Conver UTC offset to timezone.
     *
     * @since 1.2.0
     * @access public
     *
     * @param float/int/string $utc_offset UTC offset.
     * @return string valid PHP timezone string
     */
    public function convert_utc_offset_to_timezone($utc_offset)
    {
        // adjust UTC offset from hours to seconds
        $utc_offset *= 3600;

        // attempt to guess the timezone string from the UTC offset
        if ($timezone = timezone_name_from_abbr('', $utc_offset, 0)) {
            return $timezone;
        }

        // last try, guess timezone string manually
        $is_dst = date('I');

        foreach (timezone_abbreviations_list() as $abbr) {
            foreach ($abbr as $city) {
                if ($city['dst'] == $is_dst && $city['offset'] == $utc_offset) {
                    return $city['timezone_id'];
                }
            }
        }

        // fallback to UTC
        return 'UTC';

    }

    /**
     * Get all user roles.
     *
     * @since 1.0
     * @access public
     *
     * @global WP_Roles $wp_roles Core class used to implement a user roles API.
     *
     * @return array Array of all site registered user roles. User role key as the key and value is user role text.
     */
    public function get_all_user_roles()
    {
        global $wp_roles;
        return $wp_roles->get_names();

    }

    /**
     * Check validity of a save post action.
     *
     * @since 1.0
     * @access public
     *
     * @param int    $post_id   Id of the coupon post.
     * @param string $post_type Post type to check.
     * @return bool True if valid save post action, False otherwise.
     */
    public function check_if_valid_save_post_action($post_id, $post_type)
    {
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id) || get_post_type($post_id) != $post_type || empty($_POST)) {
            return false;
        } else {
            return true;
        }

    }

    /**
     * Utility function that determines if a plugin is active or not.
     *
     * @since 1.0
     * @access public
     *
     * @param string $plugin_basename Plugin base name. Ex. woocommerce/woocommerce.php
     * @return boolean True if active, false otherwise.
     */
    public function is_plugin_active($plugin_basename)
    {
        // Makes sure the plugin is defined before trying to use it
        if (!function_exists('is_plugin_active')) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return is_plugin_active($plugin_basename);

    }

    /**
     * Utility function that determines if a plugin is installed or not.
     *
     * @since 1.1
     * @access public
     *
     * @param string $plugin_basename Plugin base name. Ex. woocommerce/woocommerce.php
     * @return boolean True if active, false otherwise.
     */
    public function is_plugin_installed($plugin_basename)
    {
        $plugin_file_path = trailingslashit(WP_PLUGIN_DIR) . plugin_basename($plugin_basename);
        return file_exists($plugin_file_path);
    }

    /**
     * Check if a given active plugin's version is older than the set version to compare.
     *
     * @since 1.3.5
     * @access public
     *
     * @param string $plugin_version     Current version of the installed plugin.
     * @param string $version_to_compare Version to compare.
     * @return bool True if plugin is older, false otherwise.
     */
    public function is_plugin_older_than($plugin_version, $version_to_compare)
    {
        return version_compare($plugin_version, $version_to_compare, '<');
    }

    /**
     * Exclusive function to check if ACFWP is older than the provided version number.
     *
     * @since 1.3.5
     * @access public
     *
     * @return bool True if ACFWP plugin is older, false otherwise.
     */
    public function is_acfwp_older_than($version_to_compare)
    {
        // explicity return false if ACFWP is not active.
        if (!$this->is_plugin_active(Plugin_Constants::PREMIUM_PLUGIN)) {
            return false;
        }

        return $this->is_plugin_older_than(\ACFWP()->Plugin_Constants->VERSION, $version_to_compare);
    }

    /**
     * Get coupon url endpoint. If option value is equivalent to false, return 'coupon'.
     *
     * @since 1.0
     * @access public
     *
     * @return string Coupon endpoint.
     */
    public function get_coupon_url_endpoint()
    {

        $endpoint = trim(get_option(Plugin_Constants::COUPON_ENDPOINT, 'coupon'));
        return $endpoint ? $endpoint : 'coupon';

    }

    /**
     * Check if module is active or not.
     *
     * @since 1.0
     * @access public
     *
     * @param string $module Module option ID.
     * @return string "yes" if active, otherwise blank.
     */
    public function is_module($module)
    {

        $default_modules = class_exists('\ACFWP\Helpers\Plugin_Constants') ? \ACFWP\Helpers\Plugin_Constants::DEFAULT_MODULES() : Plugin_Constants::DEFAULT_MODULES();
        $default         = in_array($module, $default_modules) ? 'yes' : '';

        return apply_filters('acfw_is_module_enabled', get_option($module, $default) === 'yes', $module, $default);
    }

    /**
     * Get all currently active modules.
     *
     * @since 1.0
     * @access public
     *
     * @return array List of active modules.
     */
    public function get_active_modules()
    {
        $all_modules    = class_exists('\ACFWP\Helpers\Plugin_Constants') ? \ACFWP\Helpers\Plugin_Constants::ALL_MODULES() : Plugin_Constants::ALL_MODULES();
        $active_modules = array();

        foreach ($all_modules as $module) {
            if ($this->is_module($module)) {
                $active_modules[] = $module;
            }
        }

        return $active_modules;
    }

    /**
     * Get default allowed user roles.
     *
     * @since 1.0
     * @access public
     *
     * @return array Array of default allowed user roles including "guest".
     */
    public function get_default_allowed_user_roles()
    {
        $roles = $this->get_all_user_roles();
        $guest = array('guest' => __('Guest', 'advanced-coupons-for-woocommerce-free'));

        return apply_filters('acfw_default_allowed_user_roles', array_merge($guest, $roles));

    }

    /**
     * This function is an alias for WP get_option(), but will return the default value if option value is empty or invalid.
     *
     * @since 1.0
     * @access public
     *
     * @param string $option_name   Name of the option of value to fetch.
     * @param mixed  $default_value Defaut option value.
     * @return mixed Option value.
     */
    public function get_option($option_name, $default_value = '')
    {
        $option_value = get_option($option_name, $default_value);

        return (gettype($option_value) === gettype($default_value) && $option_value && !empty($option_value)) ? $option_value : $default_value;
    }

    /**
     * Get all the product category terms of the current site via wpdb.
     *
     * @since 1.0
     * @access public
     *
     * @param null $limit
     * @param string $order_by
     * @return mixed
     */
    public static function get_all_product_category_terms($limit = null, $order_by = 'DESC')
    {
        global $wpdb;

        $query = "
				  SELECT * FROM $wpdb->terms
				  INNER JOIN $wpdb->term_taxonomy ON $wpdb->terms.term_id = $wpdb->term_taxonomy.term_id
				  WHERE $wpdb->term_taxonomy.taxonomy = 'product_cat'
				  ORDER BY $wpdb->terms.name " . $order_by . "
			     ";

        if ($limit && is_numeric($limit)) {
            $query .= " LIMIT " . $limit;
        }

        return $wpdb->get_results($query);

    }

    /**
     * Compare two values based on a given condition.
     *
     * @since 1.0
     * @access public
     *
     * @param mixed  $bool_1    First boolean value.
     * @param mixed  $bool_2    Second boolean value.
     * @param string $condition Condition to compare.
     * @return bool Result value of comparison.
     */
    public function compare_condition_values($value_1, $value_2, $condition = null)
    {
        $compare = null;

        switch ($condition) {

            case 'and':
                $compare = $value_1 && $value_2;
                break;

            case 'or':
                $compare = $value_1 || $value_2;
                break;

            case '=':
                $compare = $value_1 == $value_2;
                break;

            case '!=':
                $compare = $value_1 != $value_2;
                break;

            case '>':
            case '&rt;':
                $compare = $value_1 > $value_2;
                break;

            case '<':
            case '&lt;':
                $compare = $value_1 < $value_2;
                break;

            default:
                $compare = (bool) $value_2;
                break;
        }

        return $compare;
    }

    /**
     * Get all registered coupons and return as options for <select> element.
     *
     * @since 1.0
     * @access public
     *
     * @return array All registered coupons as options of <select> element.
     */
    public function get_all_coupons_as_options()
    {
        global $wpdb;

        $query = "SELECT `ID`,`post_title` FROM $wpdb->posts
                  WHERE post_type = 'shop_coupon'
                  AND post_status = 'publish'";

        $raw_results = $wpdb->get_results($query, ARRAY_A);
        $options     = array();

        if (!is_array($raw_results) || empty($raw_results)) {
            return $options;
        }

        foreach ($raw_results as $row) {
            $options[intval($row['ID'])] = sanitize_text_field($row['post_title']);
        }

        return $options;
    }

    /**
     * Get all coupon categories as options
     *
     * @since 1.2
     * @access public
     *
     * @return array Categories as options.
     */
    public function get_all_coupon_categories_as_options()
    {
        $terms = get_terms(array(
            'taxonomy'   => Plugin_Constants::COUPON_CAT_TAXONOMY,
            'hide_empty' => false,
        ));

        $options = array();
        foreach ($terms as $term) {
            $options[$term->term_id] = $term->name;
        }

        return $options;
    }

    /**
     * Get all products under category
     *
     * @since 1.0
     * @access public
     *
     * @param mixed $category Category id or ids.
     * @return array Product ids list.
     */
    public function get_all_products_by_category($category)
    {
        $args = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'tax_query'      => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $category,
                    'operator' => 'IN',
                ),
            ),
        );

        $products = new \WP_Query($args);
        return $products->posts;
    }

    /**
     * Sanitize notice type value.
     *
     * @since 1.0
     * @access public
     *
     * @param string $type Notice type.
     * @return string Sanitized notice type.
     */
    public function sanitize_notice_type($type)
    {
        $allowed = apply_filters('acfw_sanitize_allowed_notice_types', array('global', 'notice', 'success', 'error'));
        $key     = array_search($type, $allowed);

        return $key > -1 ? $allowed[$key] : 'notice';
    }

    /**
     * Check if the current page being viewed is the cart page.
     * This makes sure that it will work for both logged-in and non logged-in users.
     *
     * @since 1.0
     * @since 1.1 make sure request is not wc-ajax related if checking for is_cart()
     * @access public
     *
     * @return bool True if viewing cart page, false otherwise.
     */
    public function is_cart()
    {
        // if the default is_cart function works and request is not wc-ajax related, then don't proceed.
        if (is_cart() && !isset($_REQUEST['wc-ajax'])) {
            return true;
        }

        $protocol = ((!empty($_SERVER['HTTPS']) && 'off' != $_SERVER['HTTPS']) || 443 == $_SERVER['SERVER_PORT']) ? "https://" : "http://";
        $url      = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        return wc_get_cart_url() === $url;
    }

    /**
     * Check if the current code is running via the checkout fragments refresh AJAX.
     * This makes sure that it will work for both logged-in and non logged-in users.
     *
     * @since 1.0
     * @since 1.1 wc-ajax request value should only be 'update_order_review'
     * @access public
     *
     * @return bool True if viewing cart page, false otherwise.
     */
    public function is_checkout_fragments()
    {
        if (!isset($_REQUEST['wc-ajax'])) {
            return false;
        }

        if (!in_array($_REQUEST['wc-ajax'], array('update_order_review', 'checkout'))) {
            return false;
        }

        // if the default is_checkout function works, then don't proceed.
        if (is_checkout()) {
            return true;
        }

        return isset($_SERVER['HTTP_REFERER']) && wc_get_checkout_url() === $_SERVER['HTTP_REFERER'];
    }

    /**
     * Check if customer is applying a coupon.
     *
     * @since 1.0
     * @access public
     *
     * @return bool True if applying coupon, false otherwise.
     */
    public function is_apply_coupon()
    {
        if (!isset($_REQUEST['wc-ajax']) || 'apply_coupon' !== $_REQUEST['wc-ajax']) {
            return false;
        }

        if (is_checkout() || (isset($_SERVER['HTTP_REFERER']) && wc_get_checkout_url() === $_SERVER['HTTP_REFERER'])) {
            return false;
        }

        return true;
    }

    /**
     * Calculate discount by type.
     *
     * @since 1.0
     * @access public
     *
     * @param string $type  Discount type.
     * @param float  $value Discount value.
     * @param float  $cost  Item cost.
     * @return float Calculated discount.
     */
    public function calculate_discount_by_type($type, $value, $cost)
    {
        switch ($type) {

            case 'percent':
                $discount = $cost * ($value / 100);
                break;

            case 'fixed':
                $discount = apply_filters('acfw_filter_amount', $value);
                break;

            case 'override':
            default:
                // if set value is greater than the cost, then
                $value = apply_filters('acfw_filter_amount', $value);

                // this filter when set to true allows to limit the discount price to the value of the cost to zero.
                // As of version 1.4.1 we are defaulting to allow BOGO price override to be greater than the regular price of the product.
                if (apply_filters('acfwf_filter_override_discount_price_max_limit', false, $type, $value, $cost)) {
                    $discount = $value < $cost ? $cost - $value : 0;
                } else {
                    $discount = $cost - $value;
                }

                break;
        }

        return min($discount, $cost);
    }

    /**
     * Sanitize price string as float.
     *
     * @since 1.0
     * @access public
     *
     * @param string $price Price string.
     * @return float Sanitized price.
     */
    public function sanitize_price($price)
    {
        $thousand_sep = get_option('woocommerce_price_thousand_sep');
        $decimal_sep  = get_option('woocommerce_price_decimal_sep');

        if ($thousand_sep) {
            $price = str_replace($thousand_sep, '', $price);
        }

        if ($decimal_sep) {
            $price = str_replace($decimal_sep, '.', $price);
        }

        $price = str_replace(get_woocommerce_currency_symbol(), '', $price);

        return (float) $price;
    }

    /**
     * Get price with WWP/P support.
     *
     * @since 1.0
     * @since 4.2 Add "Always use regular price" setting
     * @access private
     *
     * @param WC_Product $product Product object
     * @return float Product price.
     */
    public function get_price($product)
    {
        global $wc_wholesale_prices;

        // get wholesale price if present.
        if (is_object($wc_wholesale_prices) && class_exists('WWP_Wholesale_Prices')) {

            $wwp_wholesale_roles = $wc_wholesale_prices->wwp_wholesale_roles->getUserWholesaleRole();

            if (is_array($wwp_wholesale_roles) && !empty($wwp_wholesale_roles) && method_exists('WWP_Wholesale_Prices', 'get_product_wholesale_price_on_shop_v3')) {

                $data = \WWP_Wholesale_Prices::get_product_wholesale_price_on_shop_v3($product->get_id(), $wwp_wholesale_roles);

                if ($data['wholesale_price']) {
                    return (float) $data['wholesale_price'];
                }

            }

        }

        // return regular price when setting is set to yes.
        if (get_option(Plugin_Constants::ALWAYS_USE_REGULAR_PRICE) === 'yes') {
            return (float) $product->get_regular_price();
        }

        return $product->is_on_sale() ? (float) $product->get_sale_price() : (float) $product->get_regular_price();
    }

    /**
     * Sanitize condition select value.
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $value   Condition select user inputted value.
     * @param string $default Default value.
     * @return string Sanitized condition select value.
     */
    public function sanitize_condition_select_value($value, $default = 'and')
    {
        $allowed_values = apply_filters('acfw_condition_select_allowed_values', array('=', '!=', '>', '<', 'and', 'or'));
        $key            = array_search($value, $allowed_values);

        return $key > -1 && isset($allowed_values[$key]) ? $allowed_values[$key] : $default;
    }

    /**
     * Sanitize discount value based on type.
     *
     * @since 1.6
     * @access public
     *
     * @param string $discount_value Discount value
     * @param string $discount_type  Discount type
     * @param int    $product_id     Product ID
     * @return float Sanitized discount value.
     */
    public function sanitize_discount_value($discount_value, $discount_type, $product_id = 0)
    {
        $sanitized_value = (float) wc_format_decimal($discount_value);
        if ("percent" === $discount_type) {
            return min(100.0, $sanitized_value);
        }

        if ("fixed" === $discount_type && $product_id) {
            $product = wc_get_product($product_id);
            return min($product->get_regular_price(), $sanitized_value);
        }

        return $sanitized_value;
    }

    /**
     * Prepare setting fields for API.
     *
     * @since 1.2
     * @access public
     *
     * @param array $raw_field Raw setting fields.
     * @param string $section Section slug.
     * @return array Processed setting fields for API.
     */
    public function prepare_setting_fields_for_api($raw_fields, $section)
    {
        $fields = array_map(function ($o) {

            // fetch current setting value for field
            $o['value'] = 'title' !== $o['type'] || 'sectionend' !== $o['type'] ? get_option($o['id']) : null;

            // if field has options then propragate it.
            if (isset($o['options'])) {

                $temp = array();
                foreach ($o['options'] as $key => $label) {
                    $temp[] = array('key' => $key, 'label' => $label);
                }

                $o['options'] = $temp;
            }

            if (isset($o['class']) && strpos($o['class'], 'wc_input_price') !== false) {
                $o['type'] = 'price';
            }

            return $o;
        }, $raw_fields);

        $exclude_fields = apply_filters('acfw_api_exclude_setting_fields', array('acfw_bogo_deals_custom_js', 'acfw_admin_notices_display', 'sectionend'));
        $fields         = array_filter($fields, function ($f) use ($exclude_fields) {
            return !in_array($f['type'], $exclude_fields);
        });

        // change module fields type from checkbox to module.
        if ('modules_section' === $section) {
            $fields = array_map(function ($f) {
                $f['type'] = 'checkbox' === $f['type'] ? 'module' : $f['type'];
                return $f;
            }, $fields);
        }

        return array_values($fields);
    }

    /**
     * Check if WC Admin is active
     *
     * @since 1.2
     * @access public
     *
     * @return boolean True if active, false otherwise.
     */
    public function is_wc_admin_active()
    {
        $package_active = false;
        if (class_exists('\Automattic\WooCommerce\Admin\Composer\Package') && defined('WC_ADMIN_APP') && WC_ADMIN_APP) {
            $package_active = \Automattic\WooCommerce\Admin\Composer\Package::is_package_active();
        } elseif (self::is_plugin_active('woocommerce-admin/woocommerce-admin.php')) {
            return true;
        }

        return $package_active;

    }

    /**
     * Sanitize API request value.
     *
     * @since 1.2
     * @access public
     *
     * @param mixed  $value Unsanitized value.
     * @param string $type Value type.
     * @return mixed $value Sanitized value.
     */
    public function api_sanitize_value($value, $type = 'string')
    {
        switch ($type) {

            case 'post':
                $sanitized = wp_kses($value, 'post');
                break;

            case 'arraystring':
                $sanitized = array_map('sanitize_text_field', $value);
                break;

            case 'arrayint':
                $sanitized = array_map('intval', $value);
                break;

            case 'url':
                $sanitized = esc_url_raw($value);
                break;

            case 'price':
            case 'float':
                $sanitized = (float) sanitize_text_field($value);
                break;

            case 'number':
                $sanitized = intval($value);
                break;

            case 'switch':
            case 'text':
            case 'textarea':
                $sanitized = sanitize_text_field($value);
                break;

            default:
                $sanitized = apply_filters('acfw_sanitize_api_request_value', $value, $type);
        }

        return $sanitized;
    }

    /**
     * Sanitize query parameters.
     *
     * @since 4.0
     * @access private
     *
     * @param array $params Query parameters.
     * @return array Sanitized parameters.
     */
    public function api_sanitize_query_parameters($params)
    {
        if (!is_array($params) || empty($params)) {
            return array();
        }

        $sanitized = array();
        foreach ($params as $param => $value) {
            switch ($param) {
                case 'page':
                case 'per_page':
                    $sanitized[$param] = intval($value);

                case 'search':
                    $sanitized[$param] = esc_sql($value);
                    break;

                case 'user_id':
                case 'object_id':
                    $sanitized[$param] = absint($value);
                    break;

                default:
                    $sanitized[$param] = sanitize_text_field($value);
            }
        }

        return $sanitized;
    }

    /**
     * Format BOGO trigger/deal entry.
     *
     * @since 1.4
     * @access public
     *
     * @param array $args Entry arguments.
     * @param boolean $is_deal Check if for deal or not.
     * @return array Formatted BOGO trigger/deal entry.
     */
    public function format_bogo_trigger_deal_entry($args, $is_deal = false)
    {
        // outputs: $ids, $quantity, $discount, $type
        extract($args);

        $id_prefix = $is_deal ? 'deal_' : 'trigger_';
        $formatted = array(
            'entry_id' => uniqid($id_prefix), // create a unique ID for the entry.
            'ids'      => !is_array($ids) ? array($ids) : $ids,
            'quantity' => (int) $quantity,
        );

        if ($is_deal) {
            $formatted['discount'] = (float) $discount;
            $formatted['type']     = $type;
        }

        return $formatted;
    }

    /**
     * Sort cart items list by price.
     *
     * @since 1.4
     * @access public
     *
     * @param array  $cart_items Cart items array.
     * @param string $sort_order 'desc' for descending or 'asc' for ascending.
     * @return array Sorted cart items.
     */
    public function sort_cart_items_by_price($cart_items, $sort_order = 'desc')
    {
        usort($cart_items, function ($a, $b) use ($sort_order) {
            if ($a['key'] == $b['key']) {
                return 0;
            }

            $a_price = $this->get_price($a['data']);
            $b_price = $this->get_price($b['data']);

            if ('desc' === $sort_order) {
                return ($a_price > $b_price) ? -1 : 1;
            } else {
                return ($a_price < $b_price) ? -1 : 1;
            }
        });

        return $cart_items;
    }

    /**
     * Get cart item data with the provided cart key.
     *
     * @since 1.4.2
     * @access public
     *
     * @param string $cart_key Cart key.
     * @return array Cart item data.
     */
    public function get_cart_item($cart_key)
    {
        $item = \WC()->cart->get_cart_item($cart_key);

        // WPML support. Needed to properly detect products via cart key.
        if (empty($item)) {
            $item = current(array_filter(
                \WC()->cart->get_cart(),
                function ($i) use ($cart_key) {
                    return $i['key'] === $cart_key;
                }
            ));
        }

        return $item;
    }

    /**
     * Load templates in an overridable manner.
     *
     * @since 3.1
     * @access public
     *
     * @param string $template Template path
     * @param array  $args     Options to pass to the template
     * @param string $path     Default template path
     */
    public function load_template($template, $args = array(), $path = '')
    {
        $path = $path ? $path : $this->_constants->TEMPLATES_ROOT_PATH();
        wc_get_template($template, $args, '', $path);
    }

    /**
     * Check if REST API request is valid.
     * 1.) Does the request came from the same site (not external site or any external requests)
     * 2.) Does the nonce provided is valid (CSRF protection)
     *
     * @since 4.0
     * @access public
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return bool|WP_Error True if the request has read access for the item, WP_Error object otherwise.
     */
    public function check_if_valid_api_request(\WP_REST_Request $request)
    {
        $headers = $request->get_headers();

        if (isset($headers['x_wp_nonce']) || apply_filters('acfwf_restrict_api_access_to_site_only', false, $headers, $request)) {

            if (
                !is_array($headers) || !isset($headers['referer']) || // Make sure headers are set and necessary data are present
                strpos($headers['referer'][0], \get_site_url()) !== 0 || // We only allow requests originating from our own site
                !\wp_verify_nonce($headers['x_wp_nonce'][0], 'wp_rest') // We verify the REST API nonce
            ) {
                return new \WP_Error(
                    'rest_forbidden_context',
                    __('Sorry, you are not allowed access to this endpoint.', 'advanced-coupons-for-woocommerce-free'),
                    array('status' => \rest_authorization_required_code())
                );
            }

        }

        return true;
    }

    /**
     * wc_price function for API display.
     *
     * @since 4.0
     * @access public
     *
     * @param float $price Price in float.
     * @return string Sanitized price.
     */
    public function api_wc_price($price, $settings = array())
    {
        // ensure that default currency is always set to site currency when displaying prices.
        if (!isset($settings['currency'])) {
            $settings['currency'] = get_option('woocommerce_currency');
        }

        return html_entity_decode(wc_clean(wc_price($price, $settings)));
    }

    /**
     * Get customer display name.
     *
     * @since 4.0
     * @access public
     *
     * @param int|WC_Customer $cid Customer ID.
     * @return string Customer name.
     */
    public function get_customer_name($cid)
    {
        $customer      = $cid instanceof \WC_Customer ? $cid : new \WC_Customer($cid);
        $customer_name = sprintf('%s %s', $customer->get_first_name(), $customer->get_last_name());

        // set customer name to email if user has no set first and last name.
        if (!trim($customer_name)) {
            $customer_name = $this->get_customer_email($customer);
        }

        return $customer_name;
    }

    /**
     * Get customer display email.
     *
     * @since 4.0
     * @access public
     *
     * @param int|WC_Customer $cid Customer ID.
     * @return string Customer email.
     */
    public function get_customer_email($cid)
    {
        $customer = $cid instanceof \WC_Customer ? $cid : new \WC_Customer($cid);
        return $customer->get_billing_email() ? $customer->get_billing_email() : $customer->get_email();
    }

    /**
     * Get order frontend link.
     *
     * @since 4.0
     * @access public
     *
     * @param WC_Order $order Order object
     * @return string Order view frontend URL
     */
    public function get_order_frontend_link($order)
    {
        $order = $order instanceof \WC_Order ? $order : \wc_get_order($order);
        return $order->get_view_order_url();
    }

    /**
     * Load single coupon template.
     *
     * @since 3.1
     * @access public
     *
     * @param Advanced_Coupon $coupon
     * @param array           $visibility Coupon visibility options.
     * @param string          $classname  Custom classname.
     */
    public function load_single_coupon_template($coupon, $visibility, $classname = '')
    {
        $schedule_string = $coupon->get_schedule_string();

        // make sure that content visibility values are not of type string.
        foreach ($visibility as $key => $value) {
            if ('true' === $value) {
                $visibility->$key = true;
            } elseif ('false' === $value) {
                $visibility->$key = false;
            }
        }

        $classnames = array(
            'acfw-single-coupon-block',
            'acfw-coupon-type-' . $coupon->get_discount_type(),
        );

        if ($classname) {
            $classnames[] = $classname;
        }

        $this->load_template(
            'acfw-blocks/single-coupon.php',
            array(
                'coupon'             => $coupon,
                'has_discount_value' => $visibility->discount_value && $coupon->get_amount(),
                'has_usage_limit'    => $visibility->usage_limit && (int) $coupon->get_usage_limit(),
                'has_description'    => $visibility->description && $coupon->get_description(),
                'has_discount_value' => $visibility->discount_value && ($coupon->get_amount() || 'acfw_bogo' === $coupon->get_discount_type()),
                'has_schedule'       => $visibility->schedule && $schedule_string,
                'schedule_string'    => $schedule_string,
                'classnames'          => $classnames,
            )
        );
    }
}
