<?php

/*
Plugin Name: Advanced Woo Search PRO
Description: Advance ajax WooCommerce product search.
Version: 2.46
Author: ILLID
Author URI: https://advanced-woo-search.com/
Text Domain: advanced-woo-search
WC requires at least: 3.0.0
WC tested up to: 6.1.0
*/


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'AWS_PRO_FILE' ) ) {
    define( 'AWS_PRO_FILE', __FILE__ );
}

if ( ! class_exists( 'AWS_PRO_Main' ) ) :

/**
 * Main plugin class
 *
 * @class AWS_PRO_Main
 */
final class AWS_PRO_Main {

	/**
	 * @var AWS_PRO_Main The single instance of the class
	 */
	protected static $_instance = null;
        
    /**
     * @var AWS_PRO_Main Cache instance
     */
    public $cache = null;

    /**
     * @var AWS_PRO_Main License instance
     */
    public $license = null;

	/**
	 * Main AWS_PRO_Main Instance
	 *
	 * Ensures only one instance of AWS_PRO_Main is loaded or can be loaded.
	 *
	 * @static
	 * @return AWS_PRO_Main - Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {

        $this->define_constants();

		$this->includes();

		add_filter( 'widget_text', 'do_shortcode' );

		add_shortcode( 'aws_search_form', array( $this, 'markup' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );

		add_filter( 'plugin_action_links', array( $this, 'add_settings_link' ), 10, 2 );

        load_plugin_textdomain( 'advanced-woo-search', false, dirname( plugin_basename( __FILE__ ) ). '/languages/' );

        add_action( 'init', array( $this, 'init' ), 1 );

        add_filter( 'wcml_multi_currency_ajax_actions', array( $this, 'add_wpml_ajax_actions' ) );

        if ( get_option( 'aws_pro_seamless' ) && get_option( 'aws_pro_seamless' ) === 'true' ) {
            add_filter( 'get_search_form', array( $this, 'markup' ), 999999 );
            add_filter( 'get_product_search_form', array( $this, 'markup' ), 999999 );
        }

    }

    /**
     * Define constants
     */
    private function define_constants() {

        $this->define( 'AWS_PRO_VERSION', '2.46' );
        $this->define( 'AWS_PRO_DIR', plugin_dir_path( AWS_PRO_FILE ) );
        $this->define( 'AWS_PRO_URL', plugin_dir_url( AWS_PRO_FILE ) );
        $this->define( 'AWS_PRO_BASENAME', plugin_basename( AWS_PRO_FILE ) );

        $this->define( 'AWS_PRO_UPDATE_URL', 'https://portal.advanced-woo-search.com/wp-json/up/v1/updater' );

        $this->define( 'AWS_INDEX_TABLE_NAME', 'aws_index' );
        $this->define( 'AWS_CACHE_TABLE_NAME', 'aws_cache' );

    }

    /**
     * Include required core files used in admin and on the frontend.
     */
    public function includes() {

        include_once( 'includes/class-aws-helpers.php' );
        include_once( 'includes/class-aws-versions.php' );
        include_once( 'includes/class-aws-table.php' );
        include_once( 'includes/class-aws-table-data.php' );
		include_once( 'includes/class-aws-markup.php' );
		include_once( 'includes/class-aws-search.php' );
        include_once( 'includes/class-aws-search-filters.php' );
        include_once( 'includes/class-aws-tax-search.php' );
        include_once( 'includes/class-aws-users-search.php' );
        include_once( 'includes/class-aws-cache.php' );
        include_once( 'includes/class-aws-plurals.php' );
        include_once( 'includes/class-aws-search-page.php' );
        include_once( 'includes/class-aws-order.php' );
        include_once( 'includes/class-aws-translate.php' );
        include_once( 'includes/class-aws-integrations.php' );
        include_once( 'includes/widget.php' );

        // Admin
        include_once( 'includes/admin/class-aws-admin.php' );
        include_once( 'includes/admin/class-aws-admin-helpers.php' );
        include_once( 'includes/admin/class-aws-admin-filters.php' );
        include_once( 'includes/admin/class-aws-admin-filters-helpers.php' );
        include_once( 'includes/admin/class-aws-admin-fields.php' );
        include_once( 'includes/admin/class-aws-admin-options.php' );
        include_once( 'includes/admin/class-aws-admin-ajax.php' );
        include_once( 'includes/admin/class-aws-admin-meta-boxes.php' );

        // License
        include_once( 'license/class-aws-license.php' );

    }

	/*
	 * Generate search box markup
	 */
	public function markup( $atts = array() ) {

        if ( ! isset( $atts['id'] ) ) {
            $settings = $this->get_settings();
            if ( $settings ) {
                foreach( $settings as $search_instance_num => $search_instance_settings ) {
                    $id = $search_instance_num;
                    break;
                }
            } else {
                $id = 1;
            }
        } else {
            $id = $atts['id'];
        }

		$markup = new AWS_Markup( $id );

		return $markup->markup();
	}

    /*
	 * Sort products
	 */
    public function order( $products, $order_by ) {

        $order = new AWS_Order( $products, $order_by );

        return $order->result();

    }
        
    /*
     * Init plugin classes
     */
    public function init() {

        $this->cache = AWS_Cache::factory();
        AWS_Integrations::instance();

        if ( is_admin() ) {
            $this->license = new AWS_License( AWS_PRO_VERSION, AWS_PRO_UPDATE_URL, AWS_PRO_BASENAME );
        }

    }

	/*
	 * Load assets for search form
	 */
	public function load_scripts() {
		wp_enqueue_style( 'aws-pro-style', AWS_PRO_URL . 'assets/css/common.css', array(), 'pro' . AWS_PRO_VERSION );
        if ( is_rtl() ) {
            wp_enqueue_style( 'aws-pro-style-rtl', AWS_PRO_URL . 'assets/css/common-rtl.css', array(), 'pro' . AWS_PRO_VERSION );
        }
		wp_enqueue_script( 'aws-pro-script', AWS_PRO_URL . 'assets/js/common.js', array('jquery'), 'pro' . AWS_PRO_VERSION, true );
    }

	/*
	 * Add settings link to plugins
	 */
	public function add_settings_link( $links, $file ) {
		$plugin_base = plugin_basename( __FILE__ );

		if ( $file == $plugin_base ) {
			$setting_link = '<a href="' . admin_url('admin.php?page=aws-options') . '">'.esc_html__( 'Settings', 'advanced-woo-search' ).'</a>';
			array_unshift( $links, $setting_link );
		}

		return $links;
	}

    /*
     * Get plugin settings
     */
    public function get_settings( $name = 0, $form_id = 0, $filter_id = 0, $depends = false ) {

        if ( $depends && ! AWS_Helpers::is_plugin_active( $depends ) ) {
            return false;
        }

        $plugin_options = get_option( 'aws_pro_settings' );

        if ( $name && $form_id ) {
            if ( $filter_id ) {
				$return_value = isset( $plugin_options[ $form_id ]['filters'][ $filter_id ][ $name ] ) ? $plugin_options[ $form_id ]['filters'][ $filter_id ][ $name ] : '';
                return $return_value;
            } else {
				$return_value = isset( $plugin_options[ $form_id ][ $name ] ) ? $plugin_options[ $form_id ][ $name ] : '';
				return $return_value;
            }
        } else {
            return $plugin_options;
        }

    }

    /*
     * Get plugin common settings
     */
    public function get_common_settings( $name = 0 ) {
        $plugin_options = get_option( 'aws_pro_common_opts' );
        if ( $name ) {
            $return_value = isset( $plugin_options[ $name ] ) ? $plugin_options[ $name ] : '';
            return $return_value;
        } else {
            return $plugin_options;
        }
    }

    /*
     * Define constant if not already set
     */
    private function define( $name, $value ) {
        if ( ! defined( $name ) ) {
            define( $name, $value );
        }
    }

    /*
     * Add ajax action to WPML plugin
     */
    function add_wpml_ajax_actions( $actions ){
        $actions[] = 'aws_action';
        return $actions;
    }

}

endif;

/**
 * Returns the main instance of AWS_PRO_Main
 *
 * @return AWS_PRO_Main
 */
function AWS_PRO() {
    return AWS_PRO_Main::instance();
}


/*
 * Check if WooCommerce is active
 */
if ( ! aws_pro_is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
    add_action( 'admin_notices', 'aws_pro_install_woocommerce_admin_notice' );
} elseif ( aws_pro_is_plugin_active( 'advanced-woo-search/advanced-woo-search.php' ) ) {
    add_action( 'admin_notices', 'aws_pro_disable_old_version' );
} else {
    add_action( 'woocommerce_loaded', 'aws_pro_init' );
}


/*
 * Check whether the plugin is active by checking the active_plugins list.
 */
function aws_pro_is_plugin_active( $plugin ) {
    return in_array( $plugin, (array) get_option( 'active_plugins', array() ) ) || aws_pro_is_plugin_active_for_network( $plugin );
}


/*
 * Check whether the plugin is active for the entire network
 */
function aws_pro_is_plugin_active_for_network( $plugin ) {
    if ( !is_multisite() )
        return false;

    $plugins = get_site_option( 'active_sitewide_plugins');
    if ( isset($plugins[$plugin]) )
        return true;

    return false;
}

/*
 * Error notice if WooCommerce plugin is not active
 */
function aws_pro_install_woocommerce_admin_notice() {
	?>
	<div class="error">
		<p><?php esc_html_e( 'Advanced Woo Search plugin is enabled but not effective. It requires WooCommerce in order to work.', 'advanced-woo-search' ); ?></p>
	</div>
	<?php
}

/*
 * Error notice if WooCommerce plugin is not active
 */
function aws_pro_disable_old_version() {
    ?>
    <div class="error">
        <p><?php esc_html_e( 'Advanced Woo Search PRO plugin is enabled but not effective. Please disable the lite version of plugin.', 'advanced-woo-search' ); ?></p>
    </div>
<?php
}


/*
 * Activation hook
 */
register_activation_hook( __FILE__, 'aws_pro_on_activation' );
function aws_pro_on_activation() {
    $hide_notice = get_option( 'aws_hide_welcome_notice' );
    if ( ! $hide_notice ) {
        $free_plugin_version = get_option( 'aws_plugin_ver' );
        $pro_plugin_version = get_option( 'aws_pro_plugin_ver' );
        $hide = 'false';
        if ( $free_plugin_version || $pro_plugin_version ) {
            $hide = 'true';
        }
        update_option( 'aws_hide_welcome_notice', $hide, false );
    }
}


/*
 * Init AWS plugin
 */
function aws_pro_init() {
    AWS_PRO();
}


if ( ! function_exists( 'aws_get_search_form' ) ) {

    /**
     * Returns search form html
     *
     * @since 1.36
     * @return string
     */
    function aws_get_search_form( $echo = true, $args = array() ) {

        $form = '';

        if ( ! aws_pro_is_plugin_active( 'advanced-woo-search/advanced-woo-search.php' ) ) {
            if ( aws_pro_is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
                $form = AWS_PRO()->markup( $args );
            }
        }

        if ( $echo ) {
            echo $form;
        } else {
            return $form;
        }

    }

}