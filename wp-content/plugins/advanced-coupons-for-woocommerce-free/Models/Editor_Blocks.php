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
 * Model that houses the Editor_Blocks module logic.
 * Public Model.
 *
 * @since 1.1
 */
class Editor_Blocks implements Model_Interface, Initializable_Interface
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
        $this->_coupon_endpoint  = $this->_helper_functions->get_coupon_url_endpoint();

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

    /**
     * Register custom gutenberg blocks.
     *
     * @since 3.1
     * @access private
     */
    private function _register_blocks()
    {
        wp_register_style(
            'acfw-blocks-frontend',
            $this->_constants->CSS_ROOT_URL() . 'acfw-blocks-frontend.css',
            array(),
            Plugin_Constants::VERSION,
            "all"
        );

        // coupons by category block.
        \register_block_type(
            'acfw/coupons-category',
            array(
                'description'     => __('Display a grid of advanced coupons from your selected categories.', 'advanced-coupons-for-woocommerce-free'),
                'render_callback' => array($this, 'render_coupons_by_category_block'),
                'attributes'      => $this->_get_coupons_by_category_atts(),
                'style'           => 'acfw-blocks-frontend',
            )
        );

        // single coupon block.
        \register_block_type(
            'acfw/single-coupon',
            array(
                'description'     => __("Display a single advanced coupon that you've selected.", 'advanced-coupons-for-woocommerce-free'),
                'render_callback' => array($this, 'render_single_coupon_block'),
                'attributes'      => $this->_get_single_coupon_atts(),
                'style'           => 'acfw-blocks-frontend',
            )
        );

        // coupons by customer block.
        \register_block_type(
            'acfw/coupons-customer',
            array(
                'description'     => __('Display a grid of coupons and/or virtual coupons that is assigned to a customer.', 'advanced-coupons-for-woocommerce-free'),
                'render_callback' => array($this, 'render_coupons_by_customer_block'),
                'attributes'      => $this->_get_coupons_by_customer_atts(),
                'style'           => 'acfw-blocks-frontend',
            )
        );
    }

    /**
     * Register advanced coupons block category in Gutenberg.
     *
     * @since 3.1
     * @access public
     *
     * @param array $categories List of gutenberg block categories.
     * @return array Filtered list of gutenberg block categories.
     */
    public function register_acfw_block_category($categories)
    {
        $cat_slugs = wp_list_pluck($categories, 'slug');
        if (!in_array('advancedcoupons', $cat_slugs, true)) {
            $categories[] = array(
                'slug'  => 'advancedcoupons',
                'title' => 'Advanced Coupons',
                'icon'  => null,
            );
        }

        return $categories;
    }

    /**
     * Render the coupons by category block on frontend.
     *
     * @since 3.1
     * @access public
     *
     * @param array $attributes Block attributes
     * @return string HTML markup of block.
     */
    public function render_coupons_by_category_block($attributes)
    {
        $defaults   = $this->_get_coupons_by_category_atts(true);
        $attributes = wp_parse_args($attributes, $defaults);

        // don't proceed if there are no categories set.
        if (empty($attributes['categories'])) {
            return;
        }

        // build query object.
        $args = $this->_append_sort_arguments_for_query(
            array(
                'post_type'           => 'shop_coupon',
                'post_status'         => 'publish',
                'posts_per_page'      => $attributes['count'],
                'fields'              => 'ids',
                'ignore_sticky_posts' => true,
                'tax_query'           => array(
                    array(
                        'taxonomy' => 'shop_coupon_cat',
                        'field'    => 'term_id',
                        'terms'    => $attributes['categories'],
                    ),
                ),
            ),
            $attributes['order_by']
        );
        $query = new \WP_Query($args);

        // don't proceed if there are no coupons detected in loop.
        if (empty($query->posts)) {
            return;
        }

        // map list of IDs to list of advanced coupon objects.
        $coupons = $this->_get_coupons_from_queried_ids($query->posts);

        /**
         * sort coupons by expiration when order by is set to 'expire/desc'.
         * This needs to be handled via PHP so coupons with expiry are prioritized than coupons that have no expiry.
         */
        if ('expire/asc' === $attributes['order_by']) {
            $this->sort_coupons_list_by_expiry($coupons, 'asc');
        }

        ob_start();

        $this->load_coupons_list_template(
            $coupons,
            'acfw-coupons-by-category-block',
            $attributes
        );

        // reset the wp query global data to make sure we don't override the default post object in the page.
        wp_reset_postdata();

        return ob_get_clean();
    }

    /**
     * Render the single coupon block on frontend.
     *
     * @since 3.1
     * @access public
     *
     * @param array $attributes Block attributes
     * @return string HTML markup of block.
     */
    public function render_single_coupon_block($attributes)
    {
        $defaults   = $this->_get_single_coupon_atts(true);
        $attributes = wp_parse_args($attributes, $defaults);
        extract($attributes);

        // don't proceed if there is no valid coupon ID.
        if (!$coupon_id) {
            return;
        }

        $is_premium = $this->_helper_functions->is_plugin_active(Plugin_Constants::PREMIUM_PLUGIN);
        $coupon     = $is_premium ? new \ACFWP\Models\Objects\Advanced_Coupon(absint($coupon_id)) : new Advanced_Coupon(absint($coupon_id));

        ob_start();
        $this->_helper_functions->load_single_coupon_template($coupon, (object) $contentVisibility, $className);
        return ob_get_clean();
    }

    /**
     * Render the single coupon block on frontend.
     *
     * @since 3.1
     * @access public
     *
     * @param array $attributes Block attributes
     * @return string HTML markup of block.
     */
    public function render_coupons_by_customer_block($attributes)
    {
        $defaults   = $this->_get_coupons_by_customer_atts(true);
        $attributes = wp_parse_args($attributes, $defaults);

        return apply_filters('acfw_render_coupons_by_customer_block', '', $attributes);
    }

    /*
    |--------------------------------------------------------------------------
    | Templates
    |--------------------------------------------------------------------------
     */

    /**
     * Load coupons list template.
     *
     * @since 3.1
     * @access public
     *
     * @param array $coupons Coupons objects list.
     * @param string $classname List unique classname.
     */
    public function load_coupons_list_template($coupons, $block_class, $attributes)
    {
        extract($attributes);

        $classnames     = array('acfw-coupons-list-block');
        $classnames[]   = $block_class;
        $column_percent = 100 / $columns;
        $styles         = array(
            'grid-template-columns:' . str_repeat(' ' . $column_percent . '%', $columns),
            'max-width: ' . ($columns * 300) . 'px',
        );

        // add custom class value from "Advanced" panel.
        if (isset($className)) {
            $classnames[] = $className;
        }

        $this->_helper_functions->load_template(
            'acfw-blocks/coupons-list.php',
            array(
                'coupons'           => $coupons,
                'classnames'        => $classnames,
                'helper_functions'  => $this->_helper_functions,
                'columns'           => $columns,
                'styles'            => $styles,
                'contentVisibility' => (object) $contentVisibility,
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Utilities
    |--------------------------------------------------------------------------
     */

    /**
     * Append the sort parameters to the query arguments based on the selected 'order_by' attribute of the block.
     *
     * NOTE: When $order_by_raw is set to "expire/asc", the coupons with no expiration date value will be listed first.
     *       This is due to the nature of the ASC order for a custom meta value as WP will always prioritize coupons/posts
     *       that doesn't have the custom meta value.
     *
     * @since 3.1
     * @access private
     *
     * @param array $args Query args.
     * @param string $order_by_raw Order by attribute value.
     * @return array Query args with sort parameters.
     */
    private function _append_sort_arguments_for_query($args, $order_by_raw)
    {
        list($order_by, $order) = explode('/', $order_by_raw);

        if ('expire' === $order_by) {

            if ($this->_helper_functions->is_plugin_active(Plugin_Constants::PREMIUM_PLUGIN)) {
                $args['orderby']   = 'meta_value';
                $args['meta_key']  = Plugin_Constants::META_PREFIX . 'schedule_end';
                $args['meta_type'] = 'DATETIME';
            } else {
                $args['orderby']  = 'meta_value_num';
                $args['meta_key'] = 'date_expires';
            }

        } else {
            $args['orderby'] = $order_by;
        }

        $args['order'] = \strtoupper($order);

        return $args;
    }

    /**
     * Sort coupons by expiration date.
     * This function ensures that coupons with set expiration date are listed first than coupons that have no expiry.
     *
     * @since 3.1
     * @access public
     *
     * @param array  $coupons Array of advanced coupon objects.
     * @param string $order   Sort order
     */
    public function sort_coupons_list_by_expiry(&$coupons, $order)
    {
        usort($coupons, function ($a, $b) use ($order) {
            $a_date = $a->get_date_expires('edit');
            $b_date = $b->get_date_expires('edit');

            if ($a_date === $b_date) {
                return 0;
            }

            if (!$a_date && $b_date) {
                return 'desc' === $order ? -1 : 1;
            }

            if ($a_date && !$b_date) {
                return 'desc' === $order ? 1 : -1;
            }

            $condition = 'desc' === $order ? $a_date < $b_date : $a_date > $b_date;
            return ($condition) ? 1 : -1;
        });
    }

    /**
     * Get coupon objects from queried IDs.
     *
     * @since 3.1
     * @access private
     *
     * @param int[] $coupon_ids List of coupon IDs.
     */
    private function _get_coupons_from_queried_ids($coupon_ids)
    {
        $is_premium = $this->_helper_functions->is_plugin_active(Plugin_Constants::PREMIUM_PLUGIN);

        return array_map(function ($id) use ($is_premium) {
            return $is_premium ? new \ACFWP\Models\Objects\Advanced_Coupon(absint($id)) : new Advanced_Coupon(absint($id));
        }, $coupon_ids);
    }

    /**
     * Get display data for a single coupon.
     *
     * @since 3.1
     * @access private
     *
     * @param int $coupon_id Coupon ID.
     */
    private function _get_coupon_display_data($coupon_id)
    {
        return array(
            'coupon_id'   => $coupon_id,
            'coupon_code' => wc_get_coupon_code_by_id($coupon_id),
        );
    }

    /**
     * Get default attributes for the coupons by category gutenberg block.
     *
     * @since 3.1
     * @access private
     *
     * @param bool $defaults_only Set to true to only return default values.
     * @return array List of attributes or default values.
     */
    private function _get_coupons_by_category_atts($defaults_only = false)
    {
        $category_atts = array(
            'categories' => array(
                'type'    => 'array',
                'default' => []
            ),
        );

        $attributes = array_merge(
            $category_atts,
            $this->_get_layout_atts(),
            $this->_get_shared_atts()
        );

        return $defaults_only ? $this->_get_attributes_default_values($attributes) : $attributes;
    }

    /**
     * Get default attributes for the single coupon gutenberg block.
     *
     * @since 3.1
     * @access private
     *
     * @param bool $defaults_only Set to true to only return default values.
     * @return array List of attributes or default values.
     */
    private function _get_single_coupon_atts($defaults_only = false)
    {
        $attributes = array_merge(
            array(
                'coupon_id'   => array(
                    'type'    => 'number',
                    'default' => 0,
                ),
                'coupon_code' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
            ),
            $this->_get_shared_atts()
        );

        return $defaults_only ? $this->_get_attributes_default_values($attributes) : $attributes;
    }

    /**
     * Get default attributes for the coupons by customer gutenberg block.
     *
     * @since 3.1
     * @access private
     *
     * @param bool $defaults_only Set to true to only return default values.
     * @return array List of attributes or default values.
     */
    private function _get_coupons_by_customer_atts($defaults_only = false)
    {
        $attributes = array_merge(
            array(
                'display_type' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
            ),
            $this->_get_layout_atts(),
            $this->_get_shared_atts()
        );

        return $defaults_only ? $this->_get_attributes_default_values($attributes) : $attributes;
    }

    /**
     * Get the default values only from the list of attributes.
     *
     * @since 3.1
     * @access private
     *
     * @param array $attributes Gutenberg block attributes.
     * @return array List of default values for each attribute.
     */
    private function _get_attributes_default_values($attributes)
    {
        // add default classname attribute from "Advanced" panel.
        $attributes['className'] = array('default' => '');

        return array_map(function ($a) {
            return $a['default'];
        }, $attributes);
    }

    /**
     * Get attributes that is shared for blocks that require layout configurations.
     *
     * @since 3.1
     * @access private
     */
    private function _get_layout_atts()
    {
        return array(
            'order_by' => array(
                'type'    => 'string',
                'default' => 'date/desc',
            ),
            'columns'  => array(
                'type'    => 'number',
                'default' => 3,
            ),
            'count'    => array(
                'type'    => 'number',
                'default' => 10,
            ),
        );
    }

    /**
     * Get attributes that is required for all blocks.
     *
     * @since 3.1
     * @access private
     */
    private function _get_shared_atts()
    {
        return array(
            'contentVisibility' => array(
                'type'    => 'object',
                'default' => (object) array(
                    'discount_value' => true,
                    'description'    => true,
                    'usage_limit'    => true,
                    'schedule'       => true,
                ),
            ),
            'isPreview'         => array(
                'type'    => 'boolean',
                'default' => false,
            ),
        );
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
        $this->_register_blocks();

    }

    /**
     * Execute Editor_Blocks class.
     *
     * @since 1.1
     * @access public
     * @inherit ACFWF\Interfaces\Model_Interface
     */
    public function run()
    {
        if (version_compare($GLOBALS['wp_version'], '5.8', '<')) {
            add_filter('block_categories', array($this, 'register_acfw_block_category'));
        } else {
            add_filter('block_categories_all', array($this, 'register_acfw_block_category'));
        }
    }

}
