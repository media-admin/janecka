<?php
namespace ACFWF\Models\REST_API;

use ACFWF\Abstracts\Abstract_Main_Plugin_Class;
use ACFWF\Helpers\Helper_Functions;
use ACFWF\Helpers\Plugin_Constants;
use ACFWF\Interfaces\Model_Interface;
use ACFWF\Models\Objects\ACFW_Settings;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

/**
 * Model that houses the Settings module logic.
 * Public Model.
 *
 * @since 1.2
 */
class API_Settings implements Model_Interface
{

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
     */

    /**
     * Property that holds the single main instance of URL_Coupon.
     *
     * @since 1.2
     * @access private
     * @var Cart_Conditions
     */
    private static $_instance;

    /**
     * Model that houses all the plugin constants.
     *
     * @since 1.2
     * @access private
     * @var Plugin_Constants
     */
    private $_constants;

    /**
     * Property that houses all the helper functions of the plugin.
     *
     * @since 1.2
     * @access private
     * @var Helper_Functions
     */
    private $_helper_functions;

    /**
     * Property that houses the ACFW_Settings instance.
     *
     * @since 1.2
     * @access private
     * @var ACFW_Settings
     */
    private $_acfw_settings;

    /**
     * Custom REST API base.
     *
     * @since 1.2
     * @access private
     * @var string
     */
    private $_base = 'settings';

    /**
     * Property that holds all settings sections.
     *
     * @since 1.2
     * @access private
     * @var array
     */
    private $_settings_sections;

    /**
     * Property that holds all settings sections options.
     *
     * @since 1.2
     * @access private
     * @var array
     */
    private $_sections_options;

    /*
    |--------------------------------------------------------------------------
    | Class Methods
    |--------------------------------------------------------------------------
     */

    /**
     * Class constructor.
     *
     * @since 1.2
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

        $main_plugin->add_to_all_plugin_models($this);
        $main_plugin->add_to_public_models($this);

    }

    /**
     * Ensure that only one instance of this class is loaded or can be loaded ( Singleton Pattern ).
     *
     * @since 1.2
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

    /*
    |--------------------------------------------------------------------------
    | Routes.
    |--------------------------------------------------------------------------
     */

    /**
     * Initialize ACFW Settings.
     *
     * @since 1.2
     * @access private
     */
    private function _init_acfw_settings()
    {
        if (!class_exists('WC_Settings_Page', false)) {
            include_once WP_PLUGIN_DIR . '/woocommerce/includes/admin/settings/class-wc-settings-page.php';
        }

        $this->_acfw_settings = new ACFW_Settings($this->_constants, $this->_helper_functions);
    }

    /**
     * Register settings API routes.
     *
     * @since 1.2
     * @access public
     */
    public function register_routes()
    {
        $this->_init_acfw_settings();

        \register_rest_route(
            Plugin_Constants::REST_API_NAMESPACE,
            '/' . $this->_base . '/sections',
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'permission_callback' => array($this, 'get_settings_admin_permissions_check'),
                    'callback'            => array($this, 'get_settings_sections'),
                    'args'                => $this->get_settings_admin_collection_params(),
                ),
            )
        );

        \register_rest_route(
            Plugin_Constants::REST_API_NAMESPACE,
            '/' . $this->_base . '/sections/(?P<section>[\w]+)',
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'permission_callback' => array($this, 'get_settings_admin_permissions_check'),
                    'callback'            => array($this, 'get_settings_section_options'),
                ),
            )
        );

        \register_rest_route(
            Plugin_Constants::REST_API_NAMESPACE,
            '/' . $this->_base . '/(?P<id>[\w]+)',
            array(
                'args' => array(
                    'id' => array(
                        'description' => __('Unique identified for the settings option', 'advanced-coupons-for-woocommerce-free'),
                        'type'        => 'string',
                    ),
                ),
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'permission_callback' => array($this, 'check_if_valid_request'),
                    'callback'            => array($this, 'get_setting_option'),
                ),
                array(
                    'methods'             => \WP_REST_Server::EDITABLE,
                    'permission_callback' => array($this, 'update_setting_option_permissions_check'),
                    'callback'            => array($this, 'update_setting_option'),
                ),
                array(
                    'methods'             => \WP_REST_Server::DELETABLE,
                    'permission_callback' => array($this, 'update_setting_option_permissions_check'),
                    'callback'            => array($this, 'delete_setting_option'),
                ),
            )
        );

        do_action('acfw_after_register_routes');
    }

    /*
    |--------------------------------------------------------------------------
    | Permissions.
    |--------------------------------------------------------------------------
     */

    /**
     * Checks if a given request has access to read list of settings options.
     *
     * @since 1.2
     * @access public
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
     */
    public function get_settings_admin_permissions_check($request)
    {
        if (!current_user_can('manage_woocommerce')) {
            return new \WP_Error('rest_forbidden_context', __('Sorry, you are not allowed to edit settings options.', 'advanced-coupons-for-woocommerce-free'), array('status' => \rest_authorization_required_code()));
        }

        return apply_filters('acfw_get_settings_admin_permissions_check', $this->check_if_valid_request($request));
    }

    /**
     * Checks if a given request has access to read update a setting option.
     *
     * @since 1.2
     * @access public
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
     */
    public function update_setting_option_permissions_check($request)
    {
        if (!current_user_can('manage_woocommerce')) {
            return new \WP_Error('rest_forbidden_context', __('Sorry, you are not allowed to edit settings options.', 'advanced-coupons-for-woocommerce-free'), array('status' => \rest_authorization_required_code()));
        }

        return apply_filters('acfw_get_settings_admin_permissions_check', $this->check_if_valid_request($request));
    }

    /*
    |--------------------------------------------------------------------------
    | CRUD methods.
    |--------------------------------------------------------------------------
     */

    /**
     * Retrieves a collection of settings sections.
     *
     * @since 1.2
     * @access public
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_settings_sections($request)
    {
        do_action('acfw_before_get_setting_fields');

        $current_section = $request->get_header('section');
        $response        = \rest_ensure_response($this->_get_settings_sections($current_section));

        return apply_filters('acfw_filter_get_settings_admin', $response);
    }

    /**
     * Retrieves a collection of options for the specificed settings section.
     *
     * @since 1.2
     * @access public
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_settings_section_options($request)
    {
        do_action('acfw_before_get_setting_fields');

        $section  = sanitize_text_field($request['section']);
        $response = \rest_ensure_response(array(
            'id'     => $section,
            'fields' => $this->_get_single_section_fields($section),
        ));

        return apply_filters('acfw_get_settings_section_options', $response, $section);
    }

    /**
     * Retrieves a single option value.
     *
     * @since 1.2
     * @access public
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_setting_option($request)
    {
        $option = sanitize_text_field($request['id']);

        if (!$this->_is_setting_valid($option)) {
            return new \WP_Error(
                'rest_forbidden_context',
                __('The provided ID is not a valid Advanced Coupons setting.', 'advanced-coupons-for-woocommerce-free'),
                array(
                    'status' => 400,
                    'data'   => $option,
                )
            );
        }

        $response = \rest_ensure_response(array(
            'id'    => $option,
            'value' => get_option($option),
        ));

        return apply_filters('acfw_get_setting_option', $response, $option);
    }

    /**
     * Updates a single option value and returns updated value.
     *
     * @since 1.2
     * @access public
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function update_setting_option($request)
    {
        $option = sanitize_text_field($request['id']);

        if (!$this->_is_setting_valid($option)) {
            return new \WP_Error(
                'rest_forbidden_context',
                __('The provided ID is not a valid Advanced Coupons setting.', 'advanced-coupons-for-woocommerce-free'),
                array(
                    'status' => 400,
                    'data'   => $option,
                )
            );
        }

        $type  = sanitize_text_field($request->get_param('type'));
        $value = $this->_helper_functions->api_sanitize_value($request->get_param('value'), $type);

        if (update_option($option, $value)) {
            $value = get_option($option);
        }

        $response = \rest_ensure_response(array(
            'id'    => $option,
            'value' => $value,
        ));

        return apply_filters('acfw_update_setting_option', $response);

    }

    /**
     * Deletes the specified option entry.
     *
     * @since 1.2
     * @access public
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function delete_setting_option($request)
    {
        $option = sanitize_text_field($request['id']);

        if (!$this->_is_setting_valid($option)) {
            return new \WP_Error(
                'rest_forbidden_context',
                __('The provided ID is not a valid Advanced Coupons setting.', 'advanced-coupons-for-woocommerce-free'),
                array(
                    'status' => 400,
                    'data'   => $option,
                )
            );
        }

        $previous = array(
            'id'    => $option,
            'value' => get_option($option),
        );

        $response = \rest_ensure_response(array(
            'updated'  => delete_option($option),
            'previous' => $previous,
        ));

        return apply_filters('acfw_delete_setting_option', $response, $option, $previous);
    }

    /**
     * Get all settings sections.
     *
     * @since 1.2
     * @access private
     *
     * @param string $current_section Current section id.
     * @return array List of sections and its readable names.
     */
    private function _get_settings_sections($current_section = null)
    {
        $sections = array(
            array(
                'id'     => 'general_section',
                'title'  => __('General', 'advanced-coupons-for-woocommerce-free'),
                'fields' => 'general_section' === $current_section ? $this->_get_single_section_fields($current_section) : array(),
                'show'   => true,
                'module' => false,
            ),
            array(
                'id'     => 'bogo_deals_section',
                'title'  => __('BOGO Deals', 'advanced-coupons-for-woocommerce-free'),
                'fields' => 'bogo_deals_section' === $current_section ? $this->_get_single_section_fields($current_section) : array(),
                'show'   => $this->_helper_functions->is_module(Plugin_Constants::BOGO_DEALS_MODULE),
                'module' => Plugin_Constants::BOGO_DEALS_MODULE,
            ),
            array(
                'id'     => 'role_restrictions_section',
                'title'  => __('Role Restrictions', 'advanced-coupons-for-woocommerce-free'),
                'fields' => 'role_restrictions_section' === $current_section ? $this->_get_single_section_fields($current_section) : array(),
                'show'   => $this->_helper_functions->is_module(Plugin_Constants::ROLE_RESTRICT_MODULE),
                'module' => Plugin_Constants::ROLE_RESTRICT_MODULE,
            ),
            array(
                'id'     => 'url_coupons_section',
                'title'  => __('URL Coupons', 'advanced-coupons-for-woocommerce-free'),
                'fields' => 'url_coupons_section' === $current_section ? $this->_get_single_section_fields($current_section) : array(),
                'show'   => $this->_helper_functions->is_module(Plugin_Constants::URL_COUPONS_MODULE),
                'module' => Plugin_Constants::URL_COUPONS_MODULE,
            ),
            array(
                'id'     => 'modules_section',
                'title'  => __('Modules', 'advanced-coupons-for-woocommerce-free'),
                'fields' => 'modules_section' === $current_section ? $this->_get_single_section_fields($current_section) : array(),
                'show'   => true,
                'module' => false,
            ),
        );

        return apply_filters('acfw_api_settings_get_sections', $sections, $current_section);
    }

    /**
     * Get single section fields.
     *
     * @since 1.2
     * @access private
     *
     * @param string $section Section id.
     * @return array Section fields.
     */
    private function _get_single_section_fields($section)
    {
        $raw = $this->_acfw_settings->get_settings('acfw_setting_' . $section);

        return $this->_helper_functions->prepare_setting_fields_for_api($raw, $section);
    }

    /*
    |--------------------------------------------------------------------------
    | Utility methods.
    |--------------------------------------------------------------------------
     */

    /**
     * Check if REST API request is valid.
     * 1.) Does the request came from the same site (not external site or any external requests)
     * 2.) Does the nonce provided is valid (CSRF protection)
     *
     * @since 1.2
     * @access public
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return bool|WP_Error True if the request has read access for the item, WP_Error object otherwise.
     */
    public function check_if_valid_request($request)
    {
        $headers = $request->get_headers();

        if (isset($headers['x_wp_nonce']) || apply_filters('acfwf_restrict_api_access_to_site_only', false, $headers, $request)) {

            if (
                !is_array($headers) || !isset($headers['referer']) || // Make sure headers are set and necessary data are present
                strpos($headers['referer'][0], \home_url()) !== 0 || // We only allow requests originating from our own site
                !\wp_verify_nonce($headers['x_wp_nonce'][0], 'wp_rest') // We verify the REST API nonce
            ) {
                return new \WP_Error('rest_forbidden_context', __('Sorry, you are not allowed to access this api endpoint', 'advanced-coupons-for-woocommerce-free'), array('status' => \rest_authorization_required_code()));
            }

        }

        return true;

    }

    /**
     * Retrieves the query params for the settings admin collection.
     *
     * @since 1.2
     * @access public
     *
     * @return array Collection parameters.
     */
    public function get_settings_admin_collection_params()
    {
        $query_params = array(
            'settings_sections' => array(
                'description' => __('Order sort attribute ascending or descending.', 'advanced-coupons-for-woocommerce-free'),
                'type'        => 'array',
                'items'       => array(
                    'type' => 'string',
                    'enum' => array_keys($this->_get_settings_sections()),
                ),
            ),
        );

        return \apply_filters("wpb_filter_settings_admin_collection_params", $query_params);
    }

    /**
     * Check if setting is valid.
     *
     * @since 3.1.1
     * @access private
     *
     * @param string $setting Setting name to validate.
     * @return bool True if valid, false othewise.
     */
    private function _is_setting_valid($setting)
    {
        $sections       = array_keys($this->_acfw_settings->get_sections());
        $valid_settings = array_reduce(
            $sections,
            function ($c, $section) {
                return array_merge(
                    $c,
                    array_column($this->_acfw_settings->get_settings($section), 'id')
                );
            },
            array()
        );

        return in_array($setting, $valid_settings, true);
    }

    /*
    |--------------------------------------------------------------------------
    | Fulfill implemented interface contracts
    |--------------------------------------------------------------------------
     */

    /**
     * Execute Settings class.
     *
     * @since 1.2
     * @access public
     * @inherit ACFWF\Interfaces\Model_Interface
     */
    public function run()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

}
