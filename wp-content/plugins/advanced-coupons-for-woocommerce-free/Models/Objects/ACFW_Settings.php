<?php
namespace ACFWF\Models\Objects;

use ACFWF\Helpers\Helper_Functions;
use ACFWF\Helpers\Plugin_Constants;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

class ACFW_Settings extends \WC_Settings_Page
{

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
     */

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

    /*
    |--------------------------------------------------------------------------
    | Class Methods
    |--------------------------------------------------------------------------
     */

    /**
     * ACFW_Settings constructor.
     *
     * @since 1.0
     * @access public
     *
     * @param Plugin_Constants $constants        Plugin constants object.
     * @param Helper_Functions $helper_functions Helper functions object.
     */
    public function __construct(Plugin_Constants $constants, Helper_Functions $helper_functions)
    {

        $this->_constants        = $constants;
        $this->_helper_functions = $helper_functions;
        $this->id                = 'acfw_settings';
        $this->label             = __('Advanced Coupons', 'advanced-coupons-for-woocommerce-free');

        add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_page'), 30); // 30 so it is after the API tab
        add_action('woocommerce_settings_' . $this->id, array($this, 'output'));
        add_action('woocommerce_settings_save_' . $this->id, array($this, 'save'));
        add_action('woocommerce_sections_' . $this->id, array($this, 'output_sections'));

        // Custom settings fields
        add_action('woocommerce_admin_field_acfw_help_resources_field', array($this, 'render_acfw_help_resources_field'));
        add_action('woocommerce_admin_field_acfw_social_links_field', array($this, 'render_acfw_social_links_option_field'));
        add_action('woocommerce_admin_field_acfw_divider_row', array($this, 'render_acfw_divider_row'));
        add_action('woocommerce_admin_field_acfw_bogo_deals_custom_js', array($this, 'render_acfw_bogo_deals_custom_js'));
        add_action('woocommerce_admin_field_acfw_taxonomy_term_options', array($this, 'render_acfw_taxonomy_terms_as_options_field'));

        do_action('acfw_settings_construct');

    }

    /**
     * Get sections.
     *
     * @since 1.0
     * @access public
     *
     * @return array
     */
    public function get_sections()
    {

        $sections = array(
            ''                             => __('Modules', 'advanced-coupons-for-woocommerce-free'),
            'acfw_setting_general_section' => __('General', 'advanced-coupons-for-woocommerce-free'),
        );

        if ($this->_helper_functions->is_module(Plugin_Constants::BOGO_DEALS_MODULE)) {
            $sections['acfw_setting_bogo_deals_section'] = __('BOGO Deals', 'advanced-coupons-for-woocommerce-free');
        }

        if ($this->_helper_functions->is_module(Plugin_Constants::ROLE_RESTRICT_MODULE)) {
            $sections['acfw_setting_role_restrictions_section'] = __('Role Restrictions', 'advanced-coupons-for-woocommerce-free');
        }

        if ($this->_helper_functions->is_module(Plugin_Constants::URL_COUPONS_MODULE)) {
            $sections['acfw_setting_url_coupons_section'] = __('URL Coupons', 'advanced-coupons-for-woocommerce-free');
        }

        $sections['acfw_setting_help_section'] = __('Help', 'advanced-coupons-for-woocommerce-free');

        return apply_filters('woocommerce_get_sections_' . $this->id, $sections);

    }

    /**
     * Output the settings.
     *
     * @since 1.0
     * @access public
     */
    public function output()
    {

        global $current_section;

        $settings = $this->get_settings($current_section);
        \WC_Admin_Settings::output_fields($settings);

    }

    /**
     * Save settings.
     *
     * @since 1.0
     * @access public
     */
    public function save()
    {

        global $current_section;

        $settings = $this->get_settings($current_section);

        do_action('acfw_before_save_settings', $current_section, $settings);

        \WC_Admin_Settings::save_fields($settings);

        do_action('acfw_after_save_settings', $current_section, $settings);

    }

    /**
     * Get settings array.
     *
     * @since 1.0
     * @access public
     *
     * @param  string $current_section Current settings section.
     * @return array  Array of options for the current setting section.
     */
    public function get_settings($current_section = '')
    {

        $module = '';

        switch ($current_section) {

            case 'acfw_setting_help_section':
                $settings = apply_filters('acfw_setting_help_section_options', $this->_get_help_section_options());
                break;

            case 'acfw_setting_bogo_deals_section':
                $module   = Plugin_Constants::BOGO_DEALS_MODULE;
                $settings = apply_filters('acfw_setting_bogo_deals_options', $this->_get_bogo_deals_section_options());
                break;

            case 'acfw_setting_general_section':
                $settings = apply_filters('acfw_setting_general_options', $this->_get_general_section_options());
                break;

            case 'acfw_setting_url_coupons_section':
                $module   = Plugin_Constants::URL_COUPONS_MODULE;
                $settings = apply_filters('acfw_setting_url_coupons_options', $this->_get_url_coupons_section_options());
                break;

            case 'acfw_setting_role_restrictions_section':
                $module   = Plugin_Constants::ROLE_RESTRICT_MODULE;
                $settings = apply_filters('acfw_setting_role_restrictions_options', $this->_get_role_restrictions_section_options());
                break;

            case 'acfw_setting_modules_section':
            default:
                $settings = apply_filters('acfw_setting_modules_section_options', $this->_get_modules_section_options());
                break;
        }

        // if module is disabled then set settings to empty array.
        if ($module && !$this->_helper_functions->is_module($module)) {
            $settings = array();
        }

        return apply_filters('woocommerce_get_settings_' . $this->id, $settings, $current_section);

    }

    /*
    |--------------------------------------------------------------------------------------------------------------
    | Section Settings
    |--------------------------------------------------------------------------------------------------------------
     */

    /**
     * Get modules section options.
     *
     * @since 1.0
     * @access private
     *
     * @return array
     */
    private function _get_modules_section_options()
    {

        $modules = apply_filters('acfw_modules_settings', array(

            array(
                'title'   => __('URL Coupons', 'advanced-coupons-for-woocommerce-free'),
                'type'    => 'checkbox',
                'desc'    => __('Create apply links for your URLs for use in email campaigns, social media sharing, etc.', 'advanced-coupons-for-woocommerce-free'),
                'id'      => Plugin_Constants::URL_COUPONS_MODULE,
                'default' => 'yes',
            ),

            array(
                'title'   => __('Role Restrictions', 'advanced-coupons-for-woocommerce-free'),
                'type'    => 'checkbox',
                'desc'    => __('Restrict coupons to be used by certain user roles only.', 'advanced-coupons-for-woocommerce-free'),
                'id'      => Plugin_Constants::ROLE_RESTRICT_MODULE,
                'default' => 'yes',
            ),

            array(
                'title'   => __('Cart Conditions', 'advanced-coupons-for-woocommerce-free'),
                'type'    => 'checkbox',
                'desc'    => __('Create conditions that must be satisfied before a coupon is allowed to be applied.', 'advanced-coupons-for-woocommerce-free'),
                'id'      => Plugin_Constants::CART_CONDITIONS_MODULE,
                'default' => 'yes',
            ),

            array(
                'title'   => __('BOGO Deals', 'advanced-coupons-for-woocommerce-free'),
                'type'    => 'checkbox',
                'desc'    => __('Buy one, get one style deals where you can set conditions if a customer has a certain amount of a product in the cart, they get another product for a special deal.', 'advanced-coupons-for-woocommerce-free'),
                'id'      => Plugin_Constants::BOGO_DEALS_MODULE,
                'default' => 'yes',
            ),

        ));

        $settings = array_merge(
            array(
                array(
                    'title' => __('Modules', 'advanced-coupons-for-woocommerce-free'),
                    'type'  => 'title',
                    'desc'  => __("You can control which parts of the Advanced Coupons interface are shown in the Coupon edit screen. It can be helpful to users and better for overall performance to turn off features that aren't in use if you or your staff don't use them.", 'advanced-coupons-for-woocommerce-free'),
                    'id'    => 'acfw_modules_main_title',
                ),
            ),
            $modules,
            array(
                array(
                    'type' => 'sectionend',
                    'id'   => 'acfw_modules_sectionend',
                ),
            )
        );

        return apply_filters('acfw_modules_section_options', $settings);
    }

    /**
     * Get BOGO deals section options.
     *
     * @since 1.0
     * @access private
     *
     * @return array
     */
    private function _get_bogo_deals_section_options()
    {

        return array(

            array(
                'title' => __('BOGO Deals', 'advanced-coupons-for-woocommerce-free'),
                'type'  => 'title',
                'desc'  => '',
                'id'    => 'acfw_bogo_deals_main_title',
            ),

            array(
                'title'       => __('Global notice message', 'advanced-coupons-for-woocommerce-free'),
                'type'        => 'textarea',
                'desc'        => __("Message of the notice to show customer when they have triggered the BOGO deal but the \"Apply products\" are not present in the cart.", 'advanced-coupons-for-woocommerce-free'),
                'desc_tip'    => __("Custom variables available: {acfw_bogo_remaining_deals_quantity} to display the count of product deals that can be added to the cart, and {acfw_bogo_coupon_code} for displaying the coupon code that offered the deal.", 'advanced-coupons-for-woocommerce-free'),
                'id'          => Plugin_Constants::BOGO_DEALS_NOTICE_MESSAGE,
                'placeholder' => __("Your current cart is eligible to redeem deals", 'advanced-coupons-for-woocommerce-free'),
                'css'         => 'width: 500px; display: block;',
            ),

            array(
                'title'       => __('Global notice button text', 'advanced-coupons-for-woocommerce-free'),
                'type'        => 'text',
                'id'          => Plugin_Constants::BOGO_DEALS_NOTICE_BTN_TEXT,
                'placeholder' => __("View Deals", 'advanced-coupons-for-woocommerce-free'),
                'css'         => 'width: 500px; display: block;',
            ),

            array(
                'title'       => __('Global notice button URL', 'advanced-coupons-for-woocommerce-free'),
                'type'        => 'url',
                'id'          => Plugin_Constants::BOGO_DEALS_NOTICE_BTN_URL,
                'placeholder' => get_permalink(wc_get_page_id('shop')),
                'css'         => 'width: 500px; display: block;',
            ),

            array(
                'title'   => __('Global notice type', 'advanced-coupons-for-woocommerce-free'),
                'type'    => 'select',
                'id'      => Plugin_Constants::BOGO_DEALS_NOTICE_TYPE,
                'options' => array(
                    'notice'  => __('Info', 'advanced-coupons-for-woocommerce-free'),
                    'success' => __('Success', 'advanced-coupons-for-woocommerce-free'),
                    'error'   => __('Error', 'advanced-coupons-for-woocommerce-free'),
                ),
            ),

            array(
                'type' => 'acfw_bogo_deals_custom_js',
                'id'   => 'acfw_Bogo_deals_custom_js',
            ),

            array(
                'type' => 'sectionend',
                'id'   => 'acfw_bogo_deals_sectionend',
            ),

        );
    }

    /**
     * Get general section options.
     *
     * @since 1.0.0
     * @access private
     *
     * @return array
     */
    private function _get_general_section_options()
    {

        return array(

            array(
                'title' => __('General', 'advanced-coupons-for-woocommerce-free'),
                'type'  => 'title',
                'desc'  => '',
                'id'    => 'acfw_general_main_title',
            ),

            array(
                'title'       => __('Default coupon category', 'advanced-coupons-for-woocommerce-free'),
                'type'        => 'select',
                'desc_tip'    => __('If a coupon is saved without specifying a category, give it this default category. This is useful when third-party tools create coupons or for coupons created via API.', 'advanced-coupons-for-woocommerce-free'),
                'id'          => Plugin_Constants::DEFAULT_COUPON_CATEGORY,
                'class'       => 'wc-enhanced-select',
                'placeholder' => __('Select a category', 'advanced-coupons-for-woocommerce-free'),
                'taxonomy'    => Plugin_Constants::COUPON_CAT_TAXONOMY,
                'options'     => $this->_helper_functions->get_all_coupon_categories_as_options(),
            ),

            array(
                'type' => 'sectionend',
                'id'   => 'acfw_general_sectionend',
            ),

        );
    }

    /**
     * Get URL coupons section options.
     *
     * @since 1.0
     * @access private
     *
     * @return array
     */
    private function _get_url_coupons_section_options()
    {

        $url_prefix  = get_option(Plugin_Constants::COUPON_ENDPOINT, 'coupon');
        $coupon_name = __('[coupon-name]', 'advanced-coupons-for-woocommerce-free');
        $cart_url    = wc_get_cart_url();

        return array(

            array(
                'title' => __('URL Coupons', 'advanced-coupons-for-woocommerce-free'),
                'type'  => 'title',
                'desc'  => '',
                'id'    => 'acfw_url_coupons_main_title',
            ),

            array(
                'title'       => __('URL prefix', 'advanced-coupons-for-woocommerce-free'),
                'type'        => 'text',
                'desc'        => sprintf(__('The prefix to be used before the coupon code. Eg. %s', 'advanced-coupons-for-woocommerce-free'), home_url($url_prefix . '/' . $coupon_name)),
                'id'          => Plugin_Constants::COUPON_ENDPOINT,
                'default'     => 'coupon', // Don't translate, its an endpoint
                'placeholder' => 'coupon',
            ),

            array(
                'title'       => __('Redirect to URL after applying coupon', 'advanced-coupons-for-woocommerce-free'),
                'type'        => 'text',
                'desc'        => __("Optional. This will redirect the user to the provided URL after the has been attempted to be applied. You can also pass query args to the URL for the following variables: {acfw_coupon_code}, {acfw_coupon_is_applied} or {acfw_coupon_error_message} and they will be replaced with proper data. Eg. ?foo={acfw_coupon_error_message}, then test the 'foo' query arg to get the message if there is one.", 'advanced-coupons-for-woocommerce-free'),
                'id'          => Plugin_Constants::AFTER_APPLY_COUPON_REDIRECT_URL_GLOBAL,
                'placeholder' => $cart_url,
                'css'         => 'width: 500px; display: block;',
            ),

            array(
                'title'       => __('Redirect to URL if invalid coupon is visited', 'advanced-coupons-for-woocommerce-free'),
                'type'        => 'text',
                'desc'        => __("Optional. Will redirect the user to the provided URL when an invalid coupon has been attempted. You can also pass query args to the URL for the following variables {acfw_coupon_code} or {acfw_coupon_error_message} and it will be replaced with proper data. Eg. ?foo={acfw_coupon_error_message}, then test the 'foo' query arg to get the message if there is one.", 'advanced-coupons-for-woocommerce-free'),
                'id'          => Plugin_Constants::INVALID_COUPON_REDIRECT_URL,
                'placeholder' => $cart_url,
                'css'         => 'width: 500px; display: block;',
            ),

            array(
                'title'       => __('Custom success message', 'advanced-coupons-for-woocommerce-free'),
                'type'        => 'textarea',
                'desc'        => __('Optional. Message that will be displayed when a coupon has been applied successfully. Leave blank to use the default message.', 'advanced-coupons-for-woocommerce-free'),
                'id'          => Plugin_Constants::CUSTOM_SUCCESS_MESSAGE_GLOBAL,
                'css'         => 'width: 500px; display: block;',
                'placeholder' => __('Coupon applied successfully', 'advanced-coupons-for-woocommerce-free'),
            ),

            array(
                'title'       => __('Custom disable message', 'advanced-coupons-for-woocommerce-free'),
                'type'        => 'textarea',
                'desc'        => __('Optional. Message that will be displayed when the coupon url functionality is disabled. Leave blank to use the default message.', 'advanced-coupons-for-woocommerce-free'),
                'id'          => Plugin_Constants::CUSTOM_DISABLE_MESSAGE,
                'css'         => 'width: 500px; display: block;',
                'placeholder' => __('Inactive coupon url', 'advanced-coupons-for-woocommerce-free'),
            ),

            array(
                'title' => __('Hide coupon fields', 'advanced-coupons-for-woocommerce-free'),
                'type'  => 'checkbox',
                'desc'  => __('Hide the coupon fields from the cart and checkout pages on the front end.', 'advanced-coupons-for-woocommerce-free'),
                'id'    => Plugin_Constants::HIDE_COUPON_UI_ON_CART_AND_CHECKOUT,
            ),

            array(
                'type' => 'sectionend',
                'id'   => 'acfw_url_coupons_sectionend',
            ),
        );
    }

    /**
     * Get role restrictions section options.
     *
     * @since 1.0.0
     * @access private
     *
     * @return array
     */
    private function _get_role_restrictions_section_options()
    {

        return array(

            array(
                'title' => __('Role Restriction', 'advanced-coupons-for-woocommerce-free'),
                'type'  => 'title',
                'desc'  => '',
                'id'    => 'acfw_role_restrictions_main_title',
            ),

            array(
                'title'       => __('Invalid user role error message (global)', 'advanced-coupons-for-woocommerce-free'),
                'type'        => 'textarea',
                'desc'        => __("Optional. Message that will be displayed when the coupon being applied is not valid for the current user. Leave blank to use the default message.", 'advanced-coupons-for-woocommerce-free'),
                'id'          => Plugin_Constants::ROLE_RESTRICTIONS_ERROR_MESSAGE,
                'css'         => 'width: 500px; display: block;',
                'placeholder' => __("You are not allowed to use this coupon.", 'advanced-coupons-for-woocommerce-free'),
            ),

            array(
                'type' => 'sectionend',
                'id'   => 'acfw_role_restrictions_sectionend',
            ),
        );
    }

    /**
     * Get help section options
     *
     * @since 1.0
     * @access private
     *
     * @return array
     */
    private function _get_help_section_options()
    {

        // hide save changes button.
        $GLOBALS['hide_save_button'] = true;

        return apply_filters('acfw_settings_help_section_options', array(

            array(
                'title' => __('Help', 'advanced-coupons-for-woocommerce-free'),
                'type'  => 'title',
                'desc'  => 'Links to knowledge base and other helpful resources.',
                'id'    => 'acfw_help_main_title',
            ),

            array(
                'title' => __('Knowledge Base', 'advanced-coupons-for-woocommerce-free'),
                'type'  => 'acfw_divider_row',
                'id'    => 'acfw_knowledge_base_divider_row',
            ),

            array(
                'title'     => __('Documentation', 'advanced-coupons-for-woocommerce-free'),
                'type'      => 'acfw_help_resources_field',
                'desc'      => __('Guides, troubleshooting, FAQ and more.', 'advanced-coupons-for-woocommerce-free'),
                'link_text' => __('Knowledge Base', 'advanced-coupons-for-woocommerce-free'),
                'link_url'  => 'http://advancedcouponsplugin.com/knowledge-base/?utm_source=Plugin&utm_medium=Help&utm_campaign=Knowledge%20Base%20Link',
            ),

            array(
                'title'     => __('Our Blog', 'advanced-coupons-for-woocommerce-free'),
                'type'      => 'acfw_help_resources_field',
                'desc'      => __('Learn & grow your store – covering coupon marketing ideas, strategies, management, tutorials & more.', 'advanced-coupons-for-woocommerce-free'),
                'id'        => 'acfw_help_blog_link',
                'link_text' => __('Advanced Coupons Marketing Blog', 'advanced-coupons-for-woocommerce-free'),
                'link_url'  => 'https://advancedcouponsplugin.com/blog/?utm_source=Plugin&utm_medium=Help&utm_campaign=Blog%20Link',
            ),

            array(
                'title' => __('Join the Community', 'advanced-coupons-for-woocommerce-free'),
                'type'  => 'acfw_social_links_field',
                'id'    => 'acfw_social_links',
            ),

            array(
                'type' => 'sectionend',
                'id'   => 'acfw_help_sectionend',
            ),

        ));

    }

    /*
    |--------------------------------------------------------------------------------------------------------------
    | Custom Settings Fields
    |--------------------------------------------------------------------------------------------------------------
     */

    /**
     * Render ACFW divider row.
     *
     * @since 1.0
     * @access public
     *
     * @param $value Array of options data. May vary depending on option type.
     */
    public function render_acfw_divider_row($value)
    {
        ?>

        <tr valign="top" class="acfw-divider-row">
            <th scope="row">
                <h3><?php echo sanitize_text_field($value['title']); ?></h3>
            </th>
            <td> </td>
        </tr>

        <?php
}

    /**
     * Render help resources controls.
     *
     * @since 1.0
     * @access public
     *
     * @param $value Array of options data. May vary depending on option type.
     */
    public function render_acfw_help_resources_field($value)
    {
        ?>

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for=""><?php echo sanitize_text_field($value['title']); ?></label>
            </th>
            <td class="forminp forminp-<?php echo sanitize_title($value['type']); ?>">
                <a id="<?php echo esc_attr($value['id']); ?>" href="<?php echo esc_url($value['link_url']); ?>" target="_blank">
                    <?php echo sanitize_text_field($value['link_text']); ?>
                </a>
                <br>
                <?php echo esc_html($value['desc']); ?>
            </td>
        </tr>

        <?php
}

    /**
     * Render custom "social_links" field.
     *
     * @since 1.0
     * @access public
     *
     * @param array $value Array of options data. May vary depending on option type.
     */
    public function render_acfw_social_links_option_field($value)
    {

        ?>
        <tr valign="top" class="<?php echo esc_attr($value['id']) . '-row'; ?>">
            <th scope="row"><?php echo sanitize_text_field($value['title']); ?></th>
            <td>
                <ul style="margin:0">
                    <li>
                        <a href="https://facebook.com/advancedcoupons/"><?php _e('Like us on Facebook', 'advanced-coupons-for-woocommerce-free');?></a>
                        <iframe src="//www.facebook.com/plugins/like.php?href=https%3A%2F%2Fwww.facebook.com%2Fadvancedcoupons&amp;send=false&amp;layout=button_count&amp;width=450&amp;show_faces=false&amp;font=arial&amp;colorscheme=light&amp;action=like&amp;height=21" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:450px; height:21px; vertical-align: bottom;" allowTransparency="true"></iframe>
                    </li>
                    <li>
                        <a href="https://twitter.com/advancedcoupons"><?php _e('Follow us on Twitter', 'advanced-coupons-for-woocommerce-free');?></a>
                        <a href="https://twitter.com/advancedcoupons" class="twitter-follow-button" data-show-count="true" style="vertical-align: bottom;">Follow @advancedcoupons</a><script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?"http":"https";if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document, "script", "twitter-wjs");</script>
                    </li>
                    <li>
                        <a href="https://www.linkedin.com/company/rymera-web-co/"><?php _e('Follow us on Linkedin', 'advanced-coupons-for-woocommerce-free');?></a>
                    </li>
                </ul>
            </td>
        </tr>
        <?php
}

    /**
     * BOGO Deals settings custom javascript.
     *
     * @since 1.0
     * @access public
     */
    public function render_acfw_bogo_deals_custom_js()
    {

        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {

            var $notice_field = $( "#acfw_bogo_deals_notice_message" ),
                $field_row    = $notice_field.closest( "tr" );

            $field_row.on( 'mouseenter' , 'th' , function() {
                $('#tiptip_content').css({
                    width: '200px',
                    maxWidth: '300px'
                });
            });

            $field_row.on( 'mouseleave' , 'th' , function() {
                $('#tiptip_content').css({
                    width: 'auto',
                    maxWidth: '150px'
                });
            });
        });
        </script>
        <?php
}

    /**
     * Render hierarchical taxonomy terms as options list.
     *
     * @since 1.10
     * @access public
     *
     * @param array $value Field value data.
     */
    public function render_acfw_taxonomy_terms_as_options_field($value)
    {

        $taxonomy   = isset($value['taxonomy']) ? $value['taxonomy'] : 'category';
        $field_desc = \WC_Admin_Settings::get_field_description($value);
        $desc       = $field_desc['description'];
        $tooltip    = $field_desc['tooltip_html'];

        $args = array(
            'pad_counts'         => false,
            'show_count'         => false,
            'hierarchical'       => true,
            'hide_empty'         => false,
            'show_uncategorized' => true,
            'orderby'            => 'name',
            'selected'           => (int) get_option(Plugin_Constants::DEFAULT_COUPON_CATEGORY),
            'show_option_none'   => $value['placeholder'],
            'option_none_value'  => '',
            'value_field'        => 'id',
            'taxonomy'           => $taxonomy,
            'name'               => $value['id'],
            'class'              => 'dropdown_' . $taxonomy . ' ' . $value['class'],
        );

        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr($value['id']); ?>"><?php echo esc_html($value['title']); ?>
                <?php echo $tooltip; ?>
            </th>
            <td class="forminp">
                <?php wp_dropdown_categories($args);?>
            </td>
        </tr>
        <?php
}

}
