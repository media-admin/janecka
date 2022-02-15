<?php
namespace ACFWF\Models;

use ACFWF\Abstracts\Abstract_Main_Plugin_Class;
use ACFWF\Helpers\Helper_Functions;
use ACFWF\Helpers\Plugin_Constants;
use ACFWF\Interfaces\Initializable_Interface;
use ACFWF\Interfaces\Model_Interface;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

/**
 * Model that houses the logic of handling the interface of adding/editng the Advanced Coupon features.
 * Public Model.
 *
 * @since 1.5
 */
class Help_Links implements Model_Interface, Initializable_Interface
{

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
     */

    /**
     * Property that holds the single main instance of Help_Links.
     *
     * @since 1.5
     * @access private
     * @var Help_Links
     */
    private static $_instance;

    /**
     * Model that houses all the plugin constants.
     *
     * @since 1.5
     * @access private
     * @var Plugin_Constants
     */
    private $_constants;

    /**
     * Property that houses all the helper functions of the plugin.
     *
     * @since 1.5
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
     * @since 1.5
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
     * @since 1.5
     * @access public
     *
     * @param Abstract_Main_Plugin_Class $main_plugin      Main plugin object.
     * @param Plugin_Constants           $constants        Plugin constants object.
     * @param Helper_Functions           $helper_functions Helper functions object.
     * @return Help_Links
     */
    public static function get_instance(Abstract_Main_Plugin_Class $main_plugin, Plugin_Constants $constants, Helper_Functions $helper_functions)
    {

        if (!self::$_instance instanceof self) {
            self::$_instance = new self($main_plugin, $constants, $helper_functions);
        }

        return self::$_instance;

    }

    /**
     * Fetch JSON file data.
     *
     * @since 1.5
     * @access private
     *
     * @param string $module Module slug.
     * @return array|WP_Error Remote get data if successful, error object on fail.
     */
    private function _fetch_json_file_data($module)
    {
        if ("yes" !== get_option(Plugin_Constants::ALLOW_FETCH_CONTENT_REMOTE)) {
            return new \WP_Error(401, "permission_error");
        }

        $mode      = defined('ACFW_TEST_MODE') && ACFW_TEST_MODE ? 'draft' : 'live';
        $fetch_url = sprintf('https://plugin.advancedcouponsplugin.com/help/%s/%s.json', $module, $mode);
        return wp_remote_get($fetch_url);
    }

    /**
     * Get error data.
     *
     * @since 1.5
     * @access private
     */
    private function _get_error_data($code, $data = array())
    {
        $error_texts = array(
            __('There was an error trying to load the content.', 'advanced-coupons-for-woocommerce-free'),
            sprintf(
                __('Please make sure <a href="%s"><strong>%s</strong> is working on your server</a>.', 'advanced-coupons-for-woocommerce-free'),
                admin_url('admin.php?page=wc-status'),
                'cURL'
            ),
            __('Refresh the page and try again.', 'advanced-coupons-for-woocommerce-free'),
            __('An administrator or shop manager account is required to do this action.', 'advanced-coupons-for-woocommerce-free'),
        );

        switch ($code) {

            case 401:
                $error_mssg = $error_texts[3];
                break;

            case 404:
                $error_msg = sprintf('%s %s %s', $error_texts[0], $error_texts[1], $error_texts[2]);
                break;

            case 400:
            default:
                $error_msg = sprintf('%s %s', $error_texts[0], $error_texts[2]);
                break;
        }

        return array_merge(array('errorMsg' => $error_msg), $data);
    }

    /**
     * Get youtube video ID.
     *
     * @since 1.5
     * @access private
     *
     * @param string $url Vide URL
     * @return string Video ID.
     */
    private function _get_youtube_video_id($url)
    {
        // convert the url query string into an array and store on $video_query variable.
        parse_str(parse_url($url, PHP_URL_QUERY), $video_query);

        return isset($video_query['v']) ? sanitize_text_field($video_query['v']) : '';
    }

    /**
     * Get youtube video thumbnail image.
     *
     * @since 1.5
     * @access public
     *
     * @param string $url Video ID.
     * @return string Video thumbnail src.
     */
    private function _get_youtube_thumbnail_image($video_id)
    {
        return $video_id ? sprintf('https://img.youtube.com/vi/%s/0.jpg', $video_id) : '';
    }

    /**
     * Get youtube video gallery markup.
     *
     * @since 1.5
     * @access private
     *
     * @param array $video_urls List of video urls.
     * @return string Video gallery markup.
     */
    private function _get_video_gallery_markup($video_urls)
    {
        $video_embeds = array_map(function ($url) {
            $video_id = $this->_get_youtube_video_id($url);
            return array(
                'url'       => $url,
                'video_id'  => $video_id,
                'embed'     => wp_oembed_get($url),
                'thumbnail' => $this->_get_youtube_thumbnail_image($video_id),
            );
        }, $video_urls);

        ob_start();
        include $this->_constants->VIEWS_ROOT_PATH() . 'help' . DIRECTORY_SEPARATOR . 'view-video-gallery.php';
        return ob_get_clean();
    }

    /*
    |--------------------------------------------------------------------------
    | AJAX functions
    |--------------------------------------------------------------------------
     */

    /**
     * AJAX save permission request confirmation.
     *
     * @since 1.5
     * @access public
     */
    public function ajax_save_permission()
    {
        check_ajax_referer('acfw_fetch_help_data', '_nonce');

        // restrict permission save for administrator and shop manager accounts.
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json($this->_get_error_data(401), 401);
        }

        // save permission option.
        update_option(Plugin_Constants::ALLOW_FETCH_CONTENT_REMOTE, "yes");

        // return success response.
        wp_send_json(array('success' => true));
    }

    /**
     * AJAX Fetch help data for module from https://plugin.advancedcouponsplugin.com
     *
     * @since 1.5
     * @access public
     */
    public function ajax_fetch_help_data()
    {
        check_ajax_referer('acfw_fetch_help_data', '_nonce');

        // validate module slug.
        if (!isset($_REQUEST['module']) || empty($_REQUEST['module'])) {
            wp_send_json($this->_get_error_data(400), 400);
        }

        // fetch content data.
        $module   = sanitize_text_field($_REQUEST['module']);
        $response = $this->_fetch_json_file_data($module);

        // validate fetch response data.
        if (is_wp_error($response)) {
            wp_send_json($this->_get_error_data(404), 404);
        }

        $response = apply_filters('acfwf_help_modal_content_response', $response, $module);

        // validate response status code.
        if (
            200 !== wp_remote_retrieve_response_code($response) ||
            'application/json' !== wp_remote_retrieve_header($response, 'content-type')
        ) {
            wp_send_json($this->_get_error_data(400), 400);
        }

        // serve response body directly as JSON format.
        @header('Content-Type: application/json; charset=' . get_option('blog_charset'));
        echo wp_remote_retrieve_body($response);
        exit;
    }

    /**
     * AJAX Get help videos gallery html markup.
     *
     * @since 1.5
     * @access public
     */
    public function ajax_get_help_videos_gallery()
    {
        check_ajax_referer('acfw_fetch_help_data', '_nonce');

        if (!isset($_REQUEST['urls']) || empty($_REQUEST['urls'])) {
            wp_send_json($this->_get_error_data(400), 400);
        }

        $video_urls   = array_map('esc_url_raw', $_REQUEST['urls']);
        $video_width  = isset($_REQUEST['width']) ? intval($_REQUEST['width']) : 620;
        $video_embeds = array_map(function ($url) use ($video_width) {
            $video_id = $this->_get_youtube_video_id($url);
            return array(
                'url'       => $url,
                'videoid'   => $video_id,
                'embed'     => wp_oembed_get($url, array('width' => $video_width)),
                'thumbnail' => $this->_get_youtube_thumbnail_image($video_id),
            );
        }, $video_urls);

        wp_send_json($video_embeds);
    }

    /**
     * AJAX search help knowledge base articles from https://advancedcouponsplugin.com/
     *
     * @since 1.5
     * @access public
     */
    public function ajax_search_help_kb_articles()
    {
        check_ajax_referer('acfw_fetch_help_data', '_nonce');

        if (!isset($_REQUEST['term']) || empty($_REQUEST['term']) || "yes" !== get_option(Plugin_Constants::ALLOW_FETCH_CONTENT_REMOTE)) {
            wp_die();
        }

        $search    = sanitize_text_field($_REQUEST['term']);
        $fetch_url = sprintf('https://advancedcouponsplugin.com/wp-json/wp/v2/knowledgebase?search=%s', $search);
        $response  = wp_remote_get($fetch_url);

        // validate fetch response data.
        if (is_wp_error($response)) {
            wp_die();
        }

        $results = json_decode(wp_remote_retrieve_body($response), ARRAY_A);
        $options = array_map(function ($r) {
            return array(
                'url'   => $r['link'],
                'title' => $r['title']['rendered'],
            );
        }, $results);

        wp_send_json($options);
    }

    /*
    |--------------------------------------------------------------------------
    | Fulfill implemented interface contracts
    |--------------------------------------------------------------------------
     */

    /**
     * Execute codes that needs to run plugin activation.
     *
     * @since 1.5
     * @access public
     * @implements ACFWF\Interfaces\Initializable_Interface
     */
    public function initialize()
    {
        add_action('wp_ajax_acfw_save_fetch_help_permission', array($this, 'ajax_save_permission'));
        add_action('wp_ajax_acfw_fetch_help_data', array($this, 'ajax_fetch_help_data'));
        add_action('wp_ajax_acfw_get_help_videos_oembed', array($this, 'ajax_get_help_videos_gallery'));
        add_action('wp_ajax_acfw_search_help_kb_articles', array($this, 'ajax_search_help_kb_articles'));
    }

    /**
     * Execute Help_Links class.
     *
     * @since 1.5
     * @access public
     * @inherit ACFWF\Interfaces\Model_Interface
     */
    public function run()
    {
    }
}
