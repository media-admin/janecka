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
 * Model that houses the WC_Admin_Notes module logic.
 * Public Model.
 *
 * @since 1.2
 */
class WC_Admin_Notes implements Model_Interface, Initializable_Interface, Activatable_Interface
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

        $main_plugin->add_to_all_plugin_models($this);
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
    | Implementation.
    |--------------------------------------------------------------------------
     */

    /**
     * ACFW notes data for WC Admin.
     * Each notice are scheduled based on the 'days' property, which means days after the plugin has been activated or updated to version 1.2+
     *
     * @since 1.2
     * @access private
     *
     * @return array Notes data.
     */
    private function _notes_data()
    {

        $notes         = array();
        $wwp_actions   = $this->_get_wwp_note_actions();
        $acfwp_actions = $this->_get_acfwp_note_actions();

        $notes['acfw-install-wwp'] = array(
            'days'      => 30,
            'name'      => 'acfw-install-wwp',
            'title'     => __('Install Wholesale Prices (FREE PLUGIN)', 'advanced-coupons-for-woocommerce-free'),
            'content'   => __('This free plugin lets you easily add wholesale pricing to your existing WooCommerce products. Install the free plugin now.', 'advanced-coupons-for-woocommerce-free'),
            'icon'      => 'cloud-download',
            'type'      => 'info',
            'condition' => !empty($wwp_actions),
            'croncheck' => true,
            'actions'   => $wwp_actions,
        );

        $notes['acfw-premium-upgrade'] = array(
            'days'      => 7,
            'name'      => 'acfw-premium-upgrade',
            'title'     => __('Get Advanced Coupons Premium', 'advanced-coupons-for-woocommerce-free'),
            'content'   => __('Get more advanced BOGO deals, premium cart conditions, auto-apply, 1-click coupons, free gifts & more with the best WooCommerce coupon plugin.', 'advanced-coupons-for-woocommerce-free'),
            'icon'      => 'trophy',
            'type'      => 'info',
            'condition' => !empty($acfwp_actions),
            'croncheck' => true,
            'actions'   => $acfwp_actions,
        );

        $notes['wc-admin-wwp-join-store-owner-tips'] = array(
            'days'      => 45,
            'name'      => 'wc-admin-wwp-join-store-owner-tips',
            'title'     => __('Join Store Owner Tips Facebook Group', 'advanced-coupons-for-woocommerce-free'),
            'content'   => __('Want tips on how to grow your store? Come and join the Store Owner Tips Facebook group. Tips, articles and more, just for store owners.', 'advanced-coupons-for-woocommerce-free'),
            'icon'      => 'thumbs-up',
            'type'      => 'info',
            'condition' => true,
            'actions'   => array(
                'join-store-owner-tips' => array(
                    'text'    => __('Join Store Owner Tips on Facebook', 'advanced-coupons-for-woocommerce-free'),
                    'link'    => 'https://www.facebook.com/groups/storeownertips',
                    'type'    => 'actioned',
                    'primary' => true,
                ),
            ),
        );

        $notes['wc-admin-acfw-youtube'] = array(
            'days'      => 60,
            'name'      => 'wc-admin-acfw-youtube',
            'title'     => __('Follow Advanced Coupons on Youtube', 'advanced-coupons-for-woocommerce-free'),
            'content'   => __('Get all the WooCommerce coupon tips, store growth tips & more at the Advanced Coupons Youtube channel. Click here to join.', 'advanced-coupons-for-woocommerce-free'),
            'icon'      => 'video',
            'type'      => 'info',
            'condition' => true,
            'actions'   => array(
                'acfw-youtube' => array(
                    'text'    => __('Advanced Coupons Youtube Channel', 'advanced-coupons-for-woocommerce-free'),
                    'link'    => 'https://www.youtube.com/channel/UCPpM1oDXkgjQUkMYWKW7ccA',
                    'type'    => 'actioned',
                    'primary' => true,
                ),
            ),
        );

        $notes['acfw-review-plugin'] = array(
            'days'      => 14,
            'name'      => 'acfw-review-plugin',
            'title'     => __('Review Advanced Coupons', 'advanced-coupons-for-woocommerce-free'),
            'content'   => __('We notice you’ve been using Advanced Coupons for a couple of weeks now. We’d love to get your review on our plugin! Your review helps give others the confidence to try our plugin.', 'advanced-coupons-for-woocommerce-free'),
            'icon'      => 'star',
            'type'      => 'info',
            'condition' => true,
            'actions'   => array(
                'acfw-plugin-review' => array(
                    'text'    => __('Review Advanced Coupons', 'advanced-coupons-for-woocommerce-free'),
                    'link'    => 'https://wordpress.org/support/plugin/advanced-coupons-for-woocommerce-free/reviews/#new-post',
                    'type'    => 'actioned',
                    'primary' => true,
                ),
            ),
        );

        return $notes;
    }

    /**
     * Get a single ACFW note.
     *
     * @since 1.2
     * @access private
     *
     * @param string $name Note name.
     * @return array|null Note data if exist, null otherwise.
     */
    private function _get_note($name)
    {

        $notes = $this->_notes_data();
        return isset($notes[$name]) ? $notes[$name] : null;
    }

    /**
     * Get note actions for WWP admin note.
     *
     * @since 1.2
     * @access private
     *
     * @return array List of actions.
     */
    private function _get_wwp_note_actions()
    {

        $basename = 'woocommerce-wholesale-prices/woocommerce-wholesale-prices.bootstrap.php';
        $actions  = array();

        // if plugin already active, then return empty actions.
        if ($this->_helper_functions->is_plugin_active($basename)) {
            return $actions;
        }

        $actions['wwp-learn-more'] = array(
            'text'    => __('Learn more', 'advanced-coupons-for-woocommerce-free'),
            'link'    => 'https://wholesalesuiteplugin.com/?utm_source=acfwf&utm_medium=wcinbox&utm_campaign=wcinboxwwplearnmorebutton',
            'type'    => 'unactioned',
            'primary' => false,
        );

        if ($this->_helper_functions->is_plugin_installed($basename)) {

            $actions['wwp-plugin-activate'] = array(
                'text'    => __('Activate plugin', 'advanced-coupons-for-woocommerce-free'),
                'link'    => admin_url() . 'admin-ajax.php?action=acfw_admin_note_install_wwp&type=activate',
                'type'    => 'actioned',
                'primary' => true,
            );

        } else {

            $actions['wwp-plugin-install'] = array(
                'text'    => __('Install now', 'advanced-coupons-for-woocommerce-free'),
                'link'    => admin_url() . 'admin-ajax.php?action=acfw_admin_note_install_wwp&type=install',
                'type'    => 'actioned',
                'primary' => true,
            );
        }

        return $actions;
    }

    /**
     * Get note actions for ACFWP admin note.
     *
     * @since 1.2
     * @access private
     *
     * @return array List of actions.
     */
    private function _get_acfwp_note_actions()
    {

        $actions = array();

        // if plugin already active, then return empty actions.
        if ($this->_helper_functions->is_plugin_active(Plugin_Constants::PREMIUM_PLUGIN)) {
            return $actions;
        }

        if ($this->_helper_functions->is_plugin_installed(Plugin_Constants::PREMIUM_PLUGIN)) {

            $actions['acfwp-plugin-activate'] = array(
                'text'    => __('Activate plugin', 'advanced-coupons-for-woocommerce-free'),
                'link'    => admin_url() . 'admin-ajax.php?action=acfw_admin_note_install_acfwp&type=activate',
                'type'    => 'actioned',
                'primary' => true,
            );

        } else {

            $actions['acfwp-pricing'] = array(
                'text'    => __('See features & pricing', 'advanced-coupons-for-woocommerce-free'),
                'link'    => apply_filters('acfwp_upsell_link', 'https://advancedcouponsplugin.com/pricing/?utm_source=acfwf&utm_medium=wcinbox&utm_campaign=wcinboxpremiumupsell'),
                'type'    => 'actioned',
                'primary' => true,
            );
        }

        return $actions;
    }

    /**
     * Register notes to WC Admin.
     *
     * @since 1.2
     * @access public
     *
     * @param string $note Name of admin note.
     */
    public function register_admin_note($name)
    {

        // only run if WC Admin is active.
        if (!$this->_helper_functions->is_wc_admin_active()) {
            return;
        }

        $data_store = \WC_Data_Store::load('admin-note');
        $data       = $this->_get_note($name);

        // check note condition
        if (!$data['condition']) {
            return;
        }

        // skip if note is already saved.
        $note_ids = $data_store->get_notes_with_name($name);
        if (!empty($note_ids)) {
            return;
        }

        // create admin note instance.
        $note = $this->_wc_admin_note();

        $note->set_title($data['title']);
        $note->set_content($data['content']);
        $note->set_type($data['type']);
        $note->set_name($data['name']);
        $note->set_content_data((object) array());
        $note->set_source('woocommerce-admin');

        // As of WC 4.3 icon has been replaced with image.
        // We only apply the icon if set_image method is not available.
        if (!is_callable(array($note, 'set_image'))) {
            $note->set_icon($data['icon']);
        }

        foreach ($data['actions'] as $key => $action) {
            $note->add_action($key, $action['text'], $action['link'], $action['type'], $action['primary']);
        }

        $note->save();

        // schedule hourly cron for notes that needs to be checked periodically for dismissal.
        if (isset($data['croncheck']) && $data['croncheck'] && !wp_next_scheduled(Plugin_Constants::DISMISS_WC_ADMIN_NOTE, array($name))) {
            wp_schedule_event(time(), 'hourly', Plugin_Constants::DISMISS_WC_ADMIN_NOTE, array($name));
        }

    }

    /**
     * Schedule admin notices.
     *
     * @since 1.2
     * @access private
     */
    private function _schedule_admin_notes()
    {
        // only run if WC Admin is active.
        if (!$this->_helper_functions->is_wc_admin_active()) {
            return;
        }

        /**
         * We are wrapping this code with a try/catch block as some stores disable the admin notes WC feature.
         * This is needed to prevent uncaught exception errors when the data store is invalid.
         */
        try {
            $data_store = \WC_Data_Store::load('admin-note');

            foreach ($this->_notes_data() as $name => $data) {

                // if already scheduled, then skip.
                if (wp_next_scheduled(Plugin_Constants::REGISTER_WC_ADMIN_NOTE, array($name))) {
                    continue;
                }

                $note_ids = $data_store->get_notes_with_name($name);

                if (empty($note_ids) && $data['condition']) {
                    wp_schedule_single_event(strtotime('+' . $data['days'] . " days"), Plugin_Constants::REGISTER_WC_ADMIN_NOTE, array($name));
                }

            }

        } catch (\Exception $e) {
            // do nothing.
        }
    }

    /**
     * Check if note should be dismissed (when the note's condition is already false).
     *
     * @since 1.2
     * @access public
     *
     * @param string $note Name of admin note.
     */
    public function check_dismissable_note($name)
    {

        // only run if WC Admin is active.
        if (!$this->_helper_functions->is_wc_admin_active()) {
            return;
        }

        $data = $this->_get_note($name);

        // don't proceed if note condition is still true.
        if ($data['condition']) {
            return;
        }

        $data_store = \WC_Data_Store::load('admin-note');
        $note_ids   = $data_store->get_notes_with_name($name);

        if (!empty($note_ids)) {

            $note_id = current($note_ids);
            $note    = $this->_wc_admin_note($note_id);
            $note->set_status('actioned');
            $note->save();

        }

        // remove scheduled cron for dismissing the current notice.
        $timestamp = wp_next_scheduled(Plugin_Constants::DISMISS_WC_ADMIN_NOTE, array($name));
        wp_unschedule_event($timestamp, Plugin_Constants::DISMISS_WC_ADMIN_NOTE, array($name));
    }

    /*
    |--------------------------------------------------------------------------
    | Utility Functions
    |--------------------------------------------------------------------------
     */

    /**
     * Get WC Admin Note object.
     *
     * @since 1.3.3
     * @access private
     *
     * @param mixed $data Note data, object or ID.
     * @return object Admin note.
     */
    private function _wc_admin_note($data = '')
    {

        if (version_compare(WC()->version, '4.8.0', '>=')) {
            return new \Automattic\WooCommerce\Admin\Notes\Note($data);
        }

        return new \Automattic\WooCommerce\Admin\Notes\WC_Admin_Note($data);
    }

    /*
    |--------------------------------------------------------------------------
    | AJAX Functions
    |--------------------------------------------------------------------------
     */

    /**
     * AJAX function to create a nonced url to install WWP plugin.
     * NOTE: This is needed as creating the url via cron doesn't work.
     *       Also the link needs to be created dynamically as nonced urls normally expire after a day.
     *
     * @since 1.2
     * @access public
     */
    public function ajax_redirect_install_wwp_plugin()
    {

        $basename   = plugin_basename('woocommerce-wholesale-prices/woocommerce-wholesale-prices.bootstrap.php');
        $plugin_key = 'woocommerce-wholesale-prices';

        if (
            $this->_helper_functions->is_wc_admin_active()
            && current_user_can('install_plugins')
            && !$this->_helper_functions->is_plugin_active($basename)
        ) {

            if ('activate' === $_REQUEST['type'] && $this->_helper_functions->is_plugin_installed($basename)) {
                $url = htmlspecialchars_decode(wp_nonce_url(admin_url() . 'plugins.php?action=activate&amp;plugin=' . $basename . '&amp;plugin_status=all&amp;s', 'activate-plugin_' . $basename));
            } else {
                $url = htmlspecialchars_decode(wp_nonce_url(admin_url() . 'update.php?action=install-plugin&amp;plugin=' . $plugin_key, 'install-plugin_' . $plugin_key));
            }

        } else {
            $url = admin_url();
        }

        wp_redirect($url);
        exit;
    }

    /**
     * AJAX function to create a nonced url to install ACFWP plugin.
     * NOTE: This is needed as creating the url via cron doesn't work.
     *       Also the link needs to be created dynamically as nonced urls normally expire after a day.
     *
     * @since 1.2
     * @access public
     */
    public function ajax_redirect_install_acfwp_plugin()
    {

        $basename = plugin_basename(Plugin_Constants::PREMIUM_PLUGIN);

        if (
            $this->_helper_functions->is_wc_admin_active()
            && current_user_can('install_plugins')
            && !$this->_helper_functions->is_plugin_active($basename)
        ) {

            if ('activate' === $_REQUEST['type'] && $this->_helper_functions->is_plugin_installed($basename)) {
                $url = htmlspecialchars_decode(wp_nonce_url(admin_url() . 'plugins.php?action=activate&amp;plugin=' . $basename . '&amp;plugin_status=all&amp;s', 'activate-plugin_' . $basename));
            }

        } else {
            $url = admin_url();
        }

        wp_redirect($url);
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Fulfill implemented interface contracts
    |--------------------------------------------------------------------------
     */

    /**
     * Execute codes that needs to run plugin activation.
     *
     * @since 1.2
     * @access public
     * @implements ACFWF\Interfaces\Activatable_Interface
     */
    public function activate()
    {

        $this->_schedule_admin_notes();
    }

    /**
     * Execute codes that needs to run plugin activation.
     *
     * @since 1.2
     * @access public
     * @implements ACFWF\Interfaces\Initializable_Interface
     */
    public function initialize()
    {

        add_action('wp_ajax_acfw_admin_note_install_wwp', array($this, 'ajax_redirect_install_wwp_plugin'));
        add_action('wp_ajax_acfw_admin_note_install_acfwp', array($this, 'ajax_redirect_install_acfwp_plugin'));
    }

    /**
     * Execute WC_Admin_Notes class.
     *
     * @since 1.2
     * @access public
     * @inherit ACFWF\Interfaces\Model_Interface
     */
    public function run()
    {

        add_action(Plugin_Constants::REGISTER_WC_ADMIN_NOTE, array($this, 'register_admin_note'));
        add_action(Plugin_Constants::DISMISS_WC_ADMIN_NOTE, array($this, 'check_dismissable_note'));

    }

}
