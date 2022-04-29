<?php
namespace ACFWF\Models;

use ACFWF\Abstracts\Abstract_Main_Plugin_Class;
use ACFWF\Helpers\Helper_Functions;
use ACFWF\Helpers\Plugin_Constants;
use ACFWF\Interfaces\Activatable_Interface;
use ACFWF\Interfaces\Initializable_Interface;
use ACFWF\Interfaces\Model_Interface;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

/**
 * Model that houses the Notices module logic.
 * Public Model.
 *
 * @since 1.1
 */
class Notices implements Model_Interface, Initializable_Interface, Activatable_Interface
{

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
     */

    /**
     * Property that holds the single main instance of URL_Coupon.
     *
     * @since 1.1
     * @access private
     * @var Cart_Conditions
     */
    private static $_instance;

    /**
     * Model that houses all the plugin constants.
     *
     * @since 1.1
     * @access private
     * @var Plugin_Constants
     */
    private $_constants;

    /**
     * Property that houses all the helper functions of the plugin.
     *
     * @since 1.1
     * @access private
     * @var Helper_Functions
     */
    private $_helper_functions;

    /*
    |--------------------------------------------------------------------------
    | Class Methods
    |--------------------------------------------------------------------------
     */

    /**
     * Class constructor.
     *
     * @since 1.1
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
     * @since 1.1
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
    | Implementation.
    |--------------------------------------------------------------------------
     */

    /**
     * Get all ACFW admin notice options.
     *
     * @since 1.1
     * @access public
     *
     * @return array List of ACFW admin notice options.
     */
    public function get_all_admin_notice_options()
    {
        return apply_filters('acfw_admin_notice_option_names', array(
            'getting_started' => Plugin_Constants::SHOW_GETTING_STARTED_NOTICE,
            'promote_wws'     => Plugin_Constants::SHOW_PROMOTE_WWS_NOTICE,
            'review_request'  => Plugin_Constants::SHOW_REVIEW_REQUEST_NOTICE,
        ));
    }

    /**
     * Display upgrade notice on admin notices.
     *
     * @since 1.1
     * @access public
     */
    public function display_acfw_notices()
    {
        // only run when current user is atleast an administrator.
        if (!current_user_can('manage_options')) {
            return;
        }

        foreach ($this->get_all_admin_notice_options() as $notice_key => $notice_option) {

            if (!$notice_option || get_option($notice_option) !== 'yes' || 'review_request' === $notice_key) {
                continue;
            }

            $screen    = get_current_screen();
            $post_type = get_post_type();

            if (!$post_type && isset($_GET['post_type'])) {
                $post_type = $_GET['post_type'];
            }

            // display only on eligible screens.
            if (!$this->is_acfw_screen($screen, $post_type)) {
                continue;
            }

            $this->print_admin_notice_content($notice_key, $notice_option);
        }
    }

    /**
     * Display ACFW notices on settings page.
     *
     * @since 1.1
     * @access public
     */
    public function display_acfw_notices_on_settings()
    {
        // only run when current user is atleast an administrator.
        if (!current_user_can('manage_options')) {
            return;
        }

        foreach ($this->get_all_admin_notice_options() as $notice_key => $notice_option) {

            if (!$notice_option || get_option($notice_option) !== 'yes' || 'review_request' === $notice_key) {
                return;
            }

            $this->print_admin_notice_content($notice_key, $notice_option, true);
        }
    }

    /**
     * Display upgrade notice.
     *
     * @since 1.1
     * @access public
     *
     * @param string $notice_key    Notice key.
     * @param string $notice_option Notice show option name.
     * @param bool   $on_settings   Toggle if showing on settings page or not.
     */
    public function print_admin_notice_content($notice_key, $notice_option, $on_settings = false)
    {
        $notice_paths = apply_filters('acfw_admin_notice_view_paths', array(
            'getting_started' => $this->_constants->VIEWS_ROOT_PATH() . 'notices/view-getting-started-notice.php',
            'promote_wws'     => $this->_constants->VIEWS_ROOT_PATH() . 'notices/view-promote-wws-notice.php',
        ));

        $helper_funcs = $this->_helper_functions;
        $notice_class = $on_settings ? 'acfw-settings-notice' : 'notice';
        $acfw_logo    = $this->_constants->IMAGES_ROOT_URL() . '/acfw-logo.png';
        $wws_logo     = $this->_constants->IMAGES_ROOT_URL() . '/wws-logo.png';

        if (isset($notice_paths[$notice_key])) {
            include $notice_paths[$notice_key];
        }

    }

    /**
     * Display ACFW notice in settings.
     *
     * @since 1.1
     * @access public
     *
     * @param array  $settings        List of settings fields.
     * @param string $current_section Current section id.
     */
    public function display_acfw_notice_in_settings($settings, $current_section)
    {
        if ('acfw_premium' === $current_section || get_option(Plugin_Constants::SHOW_UPGRADE_NOTICE) !== 'yes') {
            return $settings;
        }

        $test = array_merge(array(

            array(
                'type' => 'acfw_admin_notices_display',
                'id'   => 'acfw_admin_notices_display',
            ),

        ), $settings);

        return $test;
    }

    /**
     * Get review request notice dialog content.
     *
     * @since 1.2
     * @access private
     *
     * @return string Notice content.
     */
    private function _get_review_request_content()
    {
        $img_logo = $this->_constants->IMAGES_ROOT_URL() . '/acfw-logo.png';

        ob_start();
        include $this->_constants->VIEWS_ROOT_PATH() . 'notices/view-acfw-review-request.php';
        return ob_get_clean();
    }

    /**
     * Enqueue admin notice styles and scripts.
     *
     * @since 1.1
     * @access public
     *
     * @param WP_Screen $screen    Current screen object.
     * @param string    $post_type Screen post type.
     */
    public function enqueue_admin_notice_scripts($screen, $post_type)
    {
        if (!$this->is_acfw_screen($screen, $post_type) || !current_user_can('manage_options')) {
            return;
        }

        $is_enqueued = false;

        foreach ($this->get_all_admin_notice_options() as $notice_key => $notice_option) {

            if (get_option($notice_option) !== 'yes') {
                continue;
            }

            wp_enqueue_style('acfw-notices');
            wp_enqueue_script('acfw-notices');
            $is_enqueued = true;

            break;
        }

        $acfw_notices = array('dummy' => true);

        if ($is_enqueued && get_option(Plugin_Constants::SHOW_REVIEW_REQUEST_NOTICE) === 'yes') {

            $acfw_notices = array(
                'review_request_content' => $this->_get_review_request_content(),
                'review_link'            => 'https://wordpress.org/support/plugin/advanced-coupons-for-woocommerce-free/reviews/#new-post',
                'review_actions'         => array(
                    'review'  => __('Review', 'advanced-coupons-for-woocommerce-free'),
                    'snooze'  => __('Review later', 'advanced-coupons-for-woocommerce-free'),
                    'dismiss' => __("Don't show again", 'advanced-coupons-for-woocommerce-free'),
                ),
            );

        };

        wp_localize_script('acfw-notices', 'acfw_notices', $acfw_notices);

    }

    /*
    |--------------------------------------------------------------------------
    | CRON related methods
    |--------------------------------------------------------------------------
     */

    /**
     * Get notices that needs to be scheduled via cron.
     *
     * @since 1.2
     * @access private
     */
    private function _get_cron_notices()
    {
        return apply_filters('acfwf_cron_notices', array(
            'promote_wws'    => array(
                'option' => Plugin_Constants::SHOW_PROMOTE_WWS_NOTICE,
                'days'   => 30,
            ),
            'review_request' => array(
                'option' => Plugin_Constants::SHOW_REVIEW_REQUEST_NOTICE,
                'days'   => 14,
            ),
        ));
    }

    /**
     * Schedule all notice crons.
     *
     * @since 1.2
     * @access private
     */
    private function _schedule_notice_crons()
    {
        $notices = $this->_get_cron_notices();

        foreach ($notices as $key => $notice) {
            $this->_schedule_single_notice_cron($key, $notice['option'], $notice['days']);
        }

    }

    /**
     * Schedule a single notice cron.
     *
     * @since 1.2
     * @access private
     *
     * @param string $key    Notice key.
     * @param string $option Notice option.
     * @param int    $days   Number of days delay.
     */
    private function _schedule_single_notice_cron($key, $option, $days)
    {
        if (wp_next_scheduled(Plugin_Constants::NOTICES_CRON, array($key)) || get_option($option, 'snooze') !== 'snooze') {
            return;
        }

        wp_schedule_single_event(time() + (DAY_IN_SECONDS * $days), Plugin_Constants::NOTICES_CRON, array($key));
    }

    /**
     * Trigger to show promote WWP notice.
     *
     * @deprecated 1.2
     *
     * @since 1.1
     * @access public
     */
    public function trigger_show_promote_wwp_notice()
    {
        $this->trigger_show_notice('promote_wws');
    }

    /**
     * Trigger to show a single notice.
     *
     * @since 1.1
     * @access public
     */
    public function trigger_show_notice($key)
    {
        $notices = $this->_get_cron_notices();
        $notice  = isset($notices[$key]) ? $notices[$key] : array();

        if (!isset($notice['option']) || get_option($notice['option']) === 'dismissed') {
            return;
        }

        update_option($notice['option'], 'yes');
    }

    /**
     * Reschedule a single notice cron based when snoozed.
     *
     * @since 1.2
     * @access public
     *
     * @param string $key   Notice key.
     * @param string $value Option value.
     */
    public function reschedule_notice_cron($key, $value)
    {
        if ('snooze' !== $value) {
            return;
        }

        $notices = $this->_get_cron_notices();
        $notice  = isset($notices[$key]) ? $notices[$key] : array();

        // unschedule cron if present.
        $timestamp = wp_next_scheduled(Plugin_Constants::NOTICES_CRON, array($key));
        if ($timestamp) {
            wp_unschedule_event($timestamp, Plugin_Constants::NOTICES_CRON, array($key));
        }

        $this->_schedule_single_notice_cron($key, $notice['option'], $notice['days']);
    }

    /*
    |--------------------------------------------------------------------------
    | AJAX methods
    |--------------------------------------------------------------------------
     */

    /**
     * AJAX dismiss admin notice.
     *
     * @since 1.1
     * @access public
     */
    public function ajax_dismiss_admin_notice()
    {
        if (defined('DOING_AJAX') && DOING_AJAX && current_user_can('manage_options')) {

            $notice_key = isset($_REQUEST['notice']) ? sanitize_text_field($_REQUEST['notice']) : '';
            $response   = isset($_REQUEST['response']) ? sanitize_text_field($_REQUEST['response']) : '';
            $response   = 'snooze' == $response ? 'snooze' : 'dismissed';

            $this->update_notice_option($notice_key, $response);
        }

        wp_die();
    }

    /*
    |--------------------------------------------------------------------------
    | Utility methods
    |--------------------------------------------------------------------------
     */

    /**
     * Check if current screen is related to ACFW.
     *
     * @since 1.1
     * @access private
     *
     * @param WP_Screen $screen      Current screen object.
     * @param string    $post_type   Screen post type.
     */
    public function is_acfw_screen($screen, $post_type)
    {
        $tab     = isset($_GET['tab']) ? $_GET['tab'] : '';
        $section = isset($_GET['section']) ? $_GET['section'] : '';

        $wc_screens = array(
            'woocommerce_page_wc-settings',
            'woocommerce_page_wc-reports',
            'woocommerce_page_wc-status',
            'woocommerce_page_wc-addons',
            'plugins',
            'coupons_page_acfw-settings',
            'coupons_page_acfw-loyalty-program',
            'coupons_page_acfw-help',
            'coupons_page_acfw-about',
            'coupons_page_acfw-license',
            'coupons_page_acfw-premium',
            'coupons_page_acfw-store-credits',
        );

        $post_types = array(
            'shop_coupon',
            'shop_order',
            'product',
        );

        return in_array($post_type, $post_types) || in_array($screen->id, $wc_screens);
    }

    /**
     * Update notice option.
     *
     * @since 1.1
     * @access private
     *
     * @param string $notice_key Notice key.
     * @param string $value      Option value.
     */
    public function update_notice_option($notice_key, $value)
    {
        $notice_options = $this->get_all_admin_notice_options();
        $option         = isset($notice_options[$notice_key]) ? $notice_options[$notice_key] : null;

        if (!$option) {
            return;
        }

        update_option($option, $value);

        do_action('acfw_notice_updated', $notice_key, $value, $option);
    }

    /**
     * Display did you know notice.
     *
     * @since 1.6
     * @access public
     *
     * @param array $args Notice arguments.
     * @param bool  $args Data only return toggle.
     */
    public function display_did_you_know_notice($args, $data_only = false)
    {
        $args = wp_parse_args($args, array(
            'classname'    => '',
            'title'        => __('Did you know?', 'advanced-coupons-for-woocommerce-free'),
            'description'  => '',
            'button_link'  => '',
            'button_text'  => __('Learn More ⟶', 'advanced-coupons-for-woocommerce-free'),
            'button_class' => 'button-secondary',
        ));

        if ($data_only) {
            return $args;
        }

        extract($args);
        include $this->_constants->VIEWS_ROOT_PATH() . 'notices/view-did-you-know-notice.php';
    }

    /*
    |--------------------------------------------------------------------------
    | Fulfill implemented interface contracts
    |--------------------------------------------------------------------------
     */

    /**
     * Execute codes that needs to run plugin activation.
     *
     * @since 1.1
     * @access public
     * @implements ACFWF\Interfaces\Activatable_Interface
     */
    public function activate()
    {
        if (get_option(Plugin_Constants::SHOW_GETTING_STARTED_NOTICE) !== 'dismissed') {
            update_option(Plugin_Constants::SHOW_GETTING_STARTED_NOTICE, 'yes');
        }

        $this->_schedule_notice_crons();
    }

    /**
     * Execute codes that needs to run plugin activation.
     *
     * @since 1.1
     * @access public
     * @implements ACFWF\Interfaces\Initializable_Interface
     */
    public function initialize()
    {
        add_action('wp_ajax_acfw_dismiss_admin_notice', array($this, 'ajax_dismiss_admin_notice'));
    }

    /**
     * Execute Notices class.
     *
     * @since 1.1
     * @access public
     * @inherit ACFWF\Interfaces\Model_Interface
     */
    public function run()
    {
        add_action('admin_notices', array($this, 'display_acfw_notices'));
        add_action('acfw_after_load_backend_scripts', array($this, 'enqueue_admin_notice_scripts'), 10, 2);
        add_filter('woocommerce_get_settings_acfw_settings', array($this, 'display_acfw_notice_in_settings'), 10, 2);
        add_action('woocommerce_admin_field_acfw_admin_notices_display', array($this, 'display_acfw_notices_on_settings'));
        add_action(Plugin_Constants::PROMOTE_WWS_NOTICE_CRON, array($this, 'trigger_show_promote_wwp_notice'));
        add_action(Plugin_Constants::NOTICES_CRON, array($this, 'trigger_show_notice'));
        add_action('acfw_notice_updated', array($this, 'reschedule_notice_cron'), 10, 2);
    }

}
