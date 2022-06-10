<?php
namespace ACFWF\Models;

use ACFWF\Abstracts\Abstract_Main_Plugin_Class;
use ACFWF\Helpers\Helper_Functions;
use ACFWF\Helpers\Plugin_Constants;
use ACFWF\Interfaces\Initializable_Interface;
use ACFWF\Interfaces\Model_Interface;
use ACFWF\Models\Objects\Advanced_Coupon;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

/**
 * Model that houses the Cart_Conditions module logic.
 * Public Model.
 *
 * @since 1.0
 */
class Cart_Conditions implements Model_Interface, Initializable_Interface
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
     * @var Cart_Conditions
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

    /**
     * Non qualify cart condition notice display check.
     *
     * @since 1.4
     * @access private
     * @var array
     */
    private $_notice_display = array();

    /**
     * Property that houses all of the condition field options.
     *
     * @since 1.5
     * @access private
     * @var array
     */
    private $_condition_field_options = array();

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
     * @return Cart_Conditions
     */
    public static function get_instance(Abstract_Main_Plugin_Class $main_plugin, Plugin_Constants $constants, Helper_Functions $helper_functions)
    {
        if (!self::$_instance instanceof self) {
            self::$_instance = new self($main_plugin, $constants, $helper_functions);
        }

        return self::$_instance;

    }

    /**
     * Get product category condition options.
     *
     * @since 1.0
     * @access public
     *
     * @return array List of category options.
     */
    public function get_product_category_options()
    {
        $categories  = $this->_helper_functions->get_all_product_category_terms();
        $cat_options = array();

        foreach ($categories as $category) {
            $cat_options[$category->term_id] = sprintf(__('[Slug: %s] %s', 'advanced-coupons-for-woocommerce-free'), $category->slug, $category->name);
        }

        return apply_filters('acfwf_cart_condition_category_option', $cat_options);
    }

    /**
     * Sanitize cart conditions.
     *
     * @since 1.0
     * @access private
     *
     * @param array $cart_conditions Advanced coupon cart conditions.
     * @return array Sanitized advanced coupon cart conditions.
     */
    private function _sanitize_cart_conditions($cart_conditions)
    {
        $sanitized = array();

        foreach ($cart_conditions as $condition_group) {

            if ('group' == $condition_group['type']) {

                $sanitized[] = array(
                    'type'   => 'group',
                    'fields' => $this->_sanitize_condition_group($condition_group['fields']),
                );

            } else {

                $sanitized[] = array(
                    'type'  => sanitize_text_field($condition_group['type']),
                    'value' => sanitize_text_field($condition_group['value']),
                );
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize condition group.
     *
     * @since 1.0
     * @access private
     *
     * @param array $condition_group_fields Condition group fields.
     * @return array Sanitized condition group fields.
     */
    private function _sanitize_condition_group($condition_group_fields)
    {
        $sanitized_group = array();

        foreach ($condition_group_fields as $condition_field) {
            $sanitized_group[] = $this->_sanitize_condition_field($condition_field);
        }

        return $sanitized_group;
    }

    /**
     * Sanitize condition field.
     *
     * @since 1.0
     * @access private
     *
     * @param array $condition_field Condition field.
     * @return array Sanitized condition field.
     */
    private function _sanitize_condition_field($condition_field)
    {
        $type = sanitize_text_field($condition_field['type']);
        $data = null;

        switch ($type) {

            case "logic":
                $data = isset($condition_field['data']) ? sanitize_text_field($condition_field['data']) : 'and';
                break;

            case "cart-quantity":
                $data = array(
                    'condition' => isset($condition_field['data']['condition']) ? $this->_helper_functions->sanitize_condition_select_value($condition_field['data']['condition'], '=') : '=',
                    'value'     => isset($condition_field['data']['value']) ? intval($condition_field['data']['value']) : '',
                );
                break;

            case "cart-subtotal":
                $data = array(
                    'condition' => isset($condition_field['data']['condition']) ? $this->_helper_functions->sanitize_condition_select_value($condition_field['data']['condition'], '=') : '=',
                    'value'     => isset($condition_field['data']['value']) ? (float) wc_format_decimal($condition_field['data']['value']) : '',
                );
                break;

            case "product-category":
                $data = array(
                    'condition' => isset($condition_field['data']['condition']) ? $this->_helper_functions->sanitize_condition_select_value($condition_field['data']['condition'], '>') : '>',
                    'value'     => isset($condition_field['data']['value']) ? array_map('sanitize_text_field', $condition_field['data']['value']) : array(),
                    'quantity'  => isset($condition_field['data']['quantity']) ? intval($condition_field['data']['quantity']) : 0,
                );
                break;

            case "customer-user-role":
            case "disallowed-customer-user-role":
                $data = is_array($condition_field['data']) ? array_map('sanitize_text_field', $condition_field['data']) : array();
                break;

            case "customer-logged-in-status":
                $data = sanitize_text_field($condition_field['data']);
                break;

            default:
                $data = is_string($condition_field['data']) ? $this->_helper_functions->sanitize_condition_select_value($condition_field['data']) : $data;
                break;
        }

        $data = apply_filters('acfw_sanitize_cart_condition_field', $data, $condition_field, $type);

        return array('type' => $type, 'data' => $data);
    }

    /**
     * Save cart conditions.
     *
     * @since 1.0
     * @access private
     *
     * @param int   $coupon_id       Coupon ID.
     * @param array $cart_conditions Advanced coupon cart conditions.
     * @return mixed WP_Error on failure, otherwise the advance coupon id (int).
     */
    private function _save_cart_conditions($coupon_id, $cart_conditions)
    {
        return update_post_meta($coupon_id, Plugin_Constants::META_PREFIX . 'cart_conditions', $cart_conditions);
    }

    /**
     * Save cart condition notice settings.
     *
     * @sinc 1.0
     * @access private
     *
     * @param int    $coupon_id          Coupon ID.
     * @param array  $cart_conditions    Advanced coupon cart conditions.
     * @param string $auto_apply_display Auto apply display check value.
     * @return mixed WP_Error on failure, otherwise the advance coupon id (int).
     */
    private function _save_cart_condition_notice_settings($coupon_id, $notice_settings, $auto_apply_display = '')
    {
        update_post_meta($coupon_id, Plugin_Constants::META_PREFIX . 'cart_condition_display_notice_auto_apply', $auto_apply_display);
        return update_post_meta($coupon_id, Plugin_Constants::META_PREFIX . 'cart_condition_notice', $notice_settings);
    }

    /*
    |--------------------------------------------------------------------------
    | Implement Cart Conditions
    |--------------------------------------------------------------------------
     */

    /**
     * Get product category condition field value.
     *
     * @since 1.0
     * @since 1.2 Add support for condition and quantity count.
     * @since 1.3.6 Add support for products under child categories.
     * @access private
     *
     * @param array $data Condition field data.
     * @return bool Condition field value.
     */
    private function _get_product_category_condition_field_value($data)
    {
        // if value prop is not available, then data is from old version before 1.2
        $condition_cats = isset($data['value']) ? $data['value'] : $data;

        // make sure condition categories is a valid array.
        $condition_cats = is_array($condition_cats) ? apply_filters('acfw_filter_product_tax_terms', $condition_cats, 'product_cat') : array();

        // get all children categories from all condition categories.
        $children_cats = array_reduce($condition_cats, function ($c, $cat) {

            $term_children = get_term_children($cat, 'product_cat');

            if (is_wp_error($term_children) || empty($term_children)) {
                return $c;
            } else {
                return array_merge($c, $term_children);
            }
        }, array());

        // merge children categories to main condition categories array.
        if (!empty($children_cats)) {
            $condition_cats = array_merge($condition_cats, $children_cats);
        }

        $quantity_cond  = isset($data['condition']) ? $data['condition'] : '>';
        $quantity_value = isset($data['quantity']) ? (int) $data['quantity'] : 0;
        $cart_quantity  = 0;

        foreach (WC()->cart->get_cart_contents() as $cart_id => $cart_item) {

            if (!$this->is_cart_item_valid($cart_item)) {
                continue;
            }

            if (is_a($cart_item['data'], 'WC_Product_Variation')) {

                $parent_prod  = wc_get_product($cart_item['data']->get_parent_id());
                $product_cats = $parent_prod->get_category_ids();

            } else {
                $product_cats = $cart_item['data']->get_category_ids();
            }

            $intersect = array_intersect($product_cats, $condition_cats);

            if (!empty($intersect)) {
                $cart_quantity += (int) $cart_item['quantity'];
            }

        }

        return $this->_helper_functions->compare_condition_values($cart_quantity, $quantity_value, $quantity_cond);
    }

    /**
     * Get customer logged in status condition field value.
     *
     * @since 1.0
     * @access private
     *
     * @param string $condition_status Condition status.
     * @return bool Condition field value.
     */
    private function _get_customer_logged_in_status_condition_field_value($condition_status)
    {
        $user_logged_in_status = is_user_logged_in() ? 'logged-in' : 'guest';
        return $condition_status === $user_logged_in_status;
    }

    /**
     * Get customer user role condition field value.
     *
     * @since 1.0
     * @access private
     *
     * @param array $condition_status Condition roles.
     * @return bool Condition field value.
     */
    private function _get_customer_user_role_condition_field_value($condition_roles)
    {
        $user  = wp_get_current_user();
        $roles = $user->ID ? $user->roles : array('guest');

        if (is_user_logged_in() && WC()->session->get('acfw_guest_user_object')) {
            $roles[] = 'guest';
        }

        $intersect = array_intersect($condition_roles, $roles);

        return !empty($intersect);
    }

    /**
     * Get customer user role condition field value.
     *
     * @since 1.0
     * @access private
     *
     * @param array $condition_status Condition roles.
     * @return bool Condition field value.
     */
    private function _get_disallowed_customer_user_role_condition_field_value($condition_roles)
    {
        $user  = wp_get_current_user();
        $roles = $user->ID ? $user->roles : array('guest');

        if (is_user_logged_in() && WC()->session->get('acfw_guest_user_object')) {
            $roles[] = 'guest';
        }

        $intersect = array_intersect($condition_roles, $roles);

        return empty($intersect);
    }

    /**
     * Get cart quantity condition field value.
     *
     * @since 1.0
     * @access private
     *
     * @param array $condition_status Condition roles.
     * @return bool Condition field value.
     */
    private function _get_cart_quantity_condition_field_value($condition_data)
    {
        // outputs $condition and $value.
        extract($condition_data);

        $count = array_reduce(\WC()->cart->get_cart_contents(), function ($carry, $item) {
            if (!$this->is_cart_item_valid($item)) {
                return $carry;
            }

            return $carry + $item['quantity'];
        }, 0);

        return $this->_helper_functions->compare_condition_values($count, $value, $condition);
    }

    /**
     * Get cart subtotal condition field value.
     *
     * @since 1.0
     * @access private
     *
     * @param array $condition_data Condition data.
     * @return bool Condition field value.
     */
    private function _get_cart_subtotal_condition_field_value($condition_data)
    {
        // outputs $condition and $value.
        extract($condition_data);

        $subtotal = WC()->cart->display_prices_including_tax() ? WC()->cart->get_subtotal() + WC()->cart->get_subtotal_tax() : WC()->cart->get_subtotal();
        $value    = apply_filters('acfw_filter_amount', (float) $value);

        return $this->_helper_functions->compare_condition_values((float) $subtotal, $value, $condition);
    }

    /**
     * Get condition group value.
     *
     * @since 1.0
     * @access private
     *
     * @param array $condition_group_fields Condition group list of fields.
     * @param bool Condition group value.
     */
    private function _get_condition_group_value($condition_group_fields)
    {
        $group_value = null;

        foreach ($condition_group_fields as $key => $condition_field) {

            if ('logic' == $condition_field['type']) {
                continue;
            }

            // get the condition which is the previous key.
            $condition = $key > 0 ? $condition_group_fields[$key - 1]['data'] : null;

            // get method for given field type.
            $field_type   = str_replace('-', '_', $condition_field['type']);
            $field_method = '_get_' . $field_type . '_condition_field_value';

            // fetch current condition field value.
            if (method_exists($this, $field_method)) {
                $current_value = $this->$field_method($condition_field['data']);
            } else {
                $current_value = apply_filters('acfw_get_cart_condition_field_value', null, $condition_field, $field_method);
            }

            // compare previous and current values.
            $group_value = $this->_helper_functions->compare_condition_values($group_value, $current_value, $condition);
        }

        return $group_value;
    }

    /**
     * Implement cart conditions feature.
     *
     * @since 1.0
     * @since 3.0.1 Make sure the cart conditions implementation will only run when WC_Cart instance is available.
     * @access public
     *
     * @param bool      $return Filter return value.
     * @param WC_Coupon $coupon WC_Coupon object.
     * @return bool True if valid, false otherwise.
     */
    public function implement_cart_conditions($return, $coupon)
    {
        if (function_exists('WC') && \WC()->cart instanceof \WC_Cart) {

            $coupon          = new Advanced_Coupon($coupon);
            $current_user    = wp_get_current_user();
            $cart_conditions = $coupon->get_advanced_prop('cart_conditions');
            $condition_value = null;

            // skip if cart conditions is empty or invalid.
            if (!is_array($cart_conditions) || empty($cart_conditions)) {
                return $return;
            }

            foreach ($cart_conditions as $key => $condition_group) {

                if ('group_logic' == $condition_group['type']) {
                    continue;
                }

                // get the condition which is the previous key.
                $condition = $key > 0 ? $cart_conditions[$key - 1]['value'] : null;

                // fetch condition value
                $current_condition = (bool) $this->_get_condition_group_value($condition_group['fields']);

                // compare previous and current values.
                $condition_value = $this->_helper_functions->compare_condition_values($condition_value, $current_condition, $condition);
            }

            if (!$condition_value) {
                throw new \Exception($this->_get_cart_condition_notice($coupon));
            }

        }

        return $return;
    }

    /**
     * Get cart condition notice.
     *
     * @since 1.0
     * @access private
     *
     * @param Advanced_Coupon $coupon Advanced coupon object.
     * @return string Notice markup.
     */
    private function _get_cart_condition_notice($coupon)
    {
        $settings = $coupon->get_advanced_prop('cart_condition_notice');
        $message  = isset($settings['message']) ? apply_filters('acfw_string_meta', $settings['message'], 'cart_condition_notice_message', $coupon) : '';
        $btn_text = isset($settings['btn_text']) ? apply_filters('acfw_string_meta', $settings['btn_text'], 'cart_condition_notice_btn_text', $coupon) : '';
        $btn_url  = isset($settings['btn_url']) ? apply_filters('acfw_string_meta', $settings['btn_url'], 'cart_condition_notice_btn_url', $coupon) : '';

        if (!$message) {
            return __("Your current cart hasn't met the conditions set for this coupon.", 'advanced-coupons-for-woocommerce-free');
        }

        $button = $btn_text && $btn_url ? sprintf('<a class="button" href="%s">%s</a>', esc_url($btn_url), $btn_text) : '';

        return sprintf('%s %s', $message, $button);
    }

    /**
     * Display cart condition notice.
     *
     * @since 1.0
     * @access public
     *
     * @param Advanced_Coupon $coupon Coupon object.
     */
    public function display_cart_condition_notice($coupon)
    {
        if ($coupon->get_advanced_prop('cart_condition_display_notice_auto_apply') !== 'yes' || in_array($coupon->get_code(), $this->_notice_display)) {
            return;
        }

        $message = $this->_get_cart_condition_notice($coupon);
        wc_print_notice($message, 'error');
        $this->_notice_display[] = $coupon->get_code();
    }

    /*
    |--------------------------------------------------------------------------
    | AJAX Functions
    |--------------------------------------------------------------------------
     */

    /**
     * AJAX Save Cart Conditions.
     *
     * @since 1.0.0
     * @access public
     */
    public function ajax_save_cart_conditions()
    {
        if (!defined('DOING_AJAX') || !DOING_AJAX) {
            $response = array('status' => 'fail', 'error_msg' => __('Invalid AJAX call', 'advanced-coupons-for-woocommerce-free'));
        } elseif (!current_user_can(apply_filters('acfw_ajax_save_cart_conditions', 'manage_woocommerce'))) {
            $response = array('status' => 'fail', 'error_msg' => __('You are not allowed to do this', 'advanced-coupons-for-woocommerce-free'));
        } elseif (!isset($_POST['coupon_id'])) {
            $response = array('status' => 'fail', 'error_msg' => __('Missing required post data', 'advanced-coupons-for-woocommerce-free'));
        } else {

            $coupon_id          = intval($_POST['coupon_id']);
            $cart_conditions    = isset($_POST['cart_conditions']) && !empty($_POST['cart_conditions']) ? $this->_sanitize_cart_conditions($_POST['cart_conditions']) : array();
            $notice_settings    = isset($_POST['notice_settings']) && is_array($_POST['notice_settings']) ? array_map('sanitize_text_field', $_POST['notice_settings']) : array();
            $auto_apply_display = isset($_POST['auto_apply_display']) && 'yes' == $_POST['auto_apply_display'] ? 'yes' : '';
            $save_check         = $this->_save_cart_conditions($coupon_id, $cart_conditions);
            $notice_save        = $this->_save_cart_condition_notice_settings($coupon_id, $notice_settings, $auto_apply_display);

            if ($save_check || $notice_save) {
                $response = array('status' => 'success', 'message' => __('Cart conditions has been saved successfully!', 'advanced-coupons-for-woocommerce-free'));
            } else {
                $response = array('status' => 'fail');
            }

        }

        @header('Content-Type: application/json; charset=' . get_option('blog_charset'));
        echo wp_json_encode($response);
        wp_die();
    }

    /*
    |--------------------------------------------------------------------------
    | Utilities.
    |--------------------------------------------------------------------------
     */

    /**
     * Check if a cart item is valid or not.
     *
     * @since 1.4.2
     * @access public
     *
     * @param array $item Cart item data.
     * @return bool True if valid, false otherwise.
     */
    public function is_cart_item_valid($item)
    {
        // invalidate if item is discounted via add products feature in ACFWP.
        $is_valid = !isset($item['acfw_add_product']);

        return apply_filters('acfw_cart_conditions_is_item_valid', $is_valid, $item);
    }

    /*
    |--------------------------------------------------------------------------
    | Localized data.
    |--------------------------------------------------------------------------
     */

    /**
     * Condition fields localized data.
     *
     * @since 1.4
     * @access public
     *
     * @param $localize array Localized JS data.
     * @return array Filtered localized JS data.
     */
    public function condition_fields_localized_data($localize)
    {
        // cart condition data.
        $cart_condition_data = apply_filters('acfw_condition_fields_localized_data', array(
            'product_category'              => array(
                'group'       => 'product-categories',
                'key'         => 'product-category',
                'title'       => __('Product Categories Exists In Cart', 'advanced-coupons-for-woocommerce-free'),
                'placeholder' => __('Select product categories', 'advanced-coupons-for-woocommerce-free'),
                'options'     => $this->get_product_category_options(),
                'field'       => __('Categories', 'advanced-coupons-for-woocommerce-free'),
            ),
            'customer_logged_in_status'     => array(
                'group'   => 'customers',
                'key'     => 'customer-logged-in-status',
                'title'   => __('Customer Logged In Status', 'advanced-coupons-for-woocommerce-free'),
                'options' => array(
                    'logged-in' => __('Logged In', 'advanced-coupons-for-woocommerce-free'),
                    'guest'     => __('Guest', 'advanced-coupons-for-woocommerce-free'),
                ),
            ),
            'customer_user_role'            => array(
                'group'       => 'customers',
                'key'         => 'customer-user-role',
                'title'       => __('Allowed Customer User Role', 'advanced-coupons-for-woocommerce-free'),
                'placeholder' => __('Select user roles', 'advanced-coupons-for-woocommerce-free'),
            ),
            'disallowed_customer_user_role' => array(
                'group'       => 'customers',
                'key'         => 'disallowed-customer-user-role',
                'title'       => __('Disallowed Customer User Role', 'advanced-coupons-for-woocommerce-free'),
                'placeholder' => __('Select user roles', 'advanced-coupons-for-woocommerce-free'),
            ),
            'cart_quantity'                 => array(
                'group' => 'cart-items',
                'key'   => 'cart-quantity',
                'title' => __('Cart Quantity', 'advanced-coupons-for-woocommerce-free'),
                'desc'  => __('Total number of cart items', 'advanced-coupons-for-woocommerce-free'),
                'field' => __('Cart Quantity', 'advanced-coupons-for-woocommerce-free'),
            ),
            'cart_subtotal'                 => array(
                'group' => 'cart-items',
                'key'   => 'cart-subtotal',
                'title' => __('Cart Subtotal', 'advanced-coupons-for-woocommerce-free'),
                'desc'  => __('After any price modifications or discounts', 'advanced-coupons-for-woocommerce-free'),
                'field' => sprintf(__('Cart Subtotal (%s)', 'advanced-coupons-for-woocommerce-free'), get_woocommerce_currency_symbol()),
            ),
        ));

        // cart condition field keys.
        $cart_conditon_field_options = array_values(array_map(function ($c) {
            return $c['key'];
        }, $cart_condition_data));
        $cart_conditon_field_options[] = 'logic';

        // cart condition field groups.
        $cart_condition_field_groups = apply_filters('acfw_condition_field_groups_localized_data', array(
            array(
                'group' => 'cart-items',
                'label' => __('Cart Items', 'advanced-coupons-for-woocommerce-free'),
            ),
            array(
                'group' => 'products',
                'label' => __('Products', 'advanced-coupons-for-woocommerce-free'),
            ),
            array(
                'group' => 'product-categories',
                'label' => __('Product Categories', 'advanced-coupons-for-woocommerce-free'),
            ),
            array(
                'group' => 'customers',
                'label' => __('Customers', 'advanced-coupons-for-woocommerce-free'),
            ),
            array(
                'group' => 'advanced',
                'label' => __('Advanced', 'advanced-coupons-for-woocommerce-free'),
            ),
            array(
                'group' => 'others',
                'label' => __('Others', 'advanced-coupons-for-woocommerce-free'),
            ),
        ));

        $localize['cart_condition_fields']       = $cart_condition_data;
        $localize['cart_conditon_field_options'] = $cart_conditon_field_options;
        $localize['cart_condition_field_groups'] = $cart_condition_field_groups;
        return $localize;
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

        if (!$this->_helper_functions->is_module(Plugin_Constants::CART_CONDITIONS_MODULE)) {
            return;
        }

        add_action('wp_ajax_acfw_save_cart_conditions', array($this, 'ajax_save_cart_conditions'));

    }

    /**
     * Execute Cart_Conditions class.
     *
     * @since 1.0
     * @access public
     * @inherit ACFWF\Interfaces\Model_Interface
     */
    public function run()
    {

        if (!$this->_helper_functions->is_module(Plugin_Constants::CART_CONDITIONS_MODULE)) {
            return;
        }

        add_filter('woocommerce_coupon_is_valid', array($this, 'implement_cart_conditions'), 10, 2);
        add_filter('acfw_edit_advanced_coupon_localize', array($this, 'condition_fields_localized_data'));
        add_action('acfw_auto_apply_coupon_invalid', array($this, 'display_cart_condition_notice'), 10, 1);
    }

}
