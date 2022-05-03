<?php
/**
 * Plugin Name: Germanized for WooCommerce Pro
 * Plugin URI: https://vendidero.de/woocommerce-germanized
 * Description: Extends Germanized for WooCommerce with professional features such as PDF invoices, legal text generators and many more.
 * Version: 3.5.1
 * Author: vendidero
 * Author URI: https://vendidero.de
 * Requires at least: 5.4
 * Tested up to: 6.0
 * WC requires at least: 3.9
 * WC tested up to: 6.5
 *
 * Text Domain: woocommerce-germanized-pro
 * Domain Path: /i18n/languages/
 * Update URI: false
 *
 * @author vendidero
 */

use Vendidero\Germanized\Pro\Autoloader;
use Vendidero\Germanized\Pro\Packages;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Load core packages and the autoloader.
 *
 * The new packages and autoloader require PHP 5.6+.
 */
if ( version_compare( PHP_VERSION, '5.6.0', '>=' ) ) {
	require __DIR__ . '/src/Autoloader.php';
	require __DIR__ . '/src/Packages.php';

	if ( ! Autoloader::init() ) {
		return;
	}

	Packages::init();
} else {
	function wc_gzdp_admin_php_notice() {
		?>
        <div id="message" class="error">
            <p>
				<?php
				printf(
				    /* translators: %s is the word upgrade with a link to a support page about upgrading */
					__( 'Germanized Pro requires at least PHP 5.6 to work. Please %s your PHP version.', 'woocommerce-germanized-pro' ),
					'<a href="https://wordpress.org/support/update-php/">' . esc_html__( 'upgrade', 'woocommerce-germanized-pro' ) . '</a>'
				);
				?>
            </p>
        </div>
		<?php
	}

	add_action( 'admin_notices', 'wc_gzdp_admin_php_notice', 20 );

	return;
}

if ( ! class_exists( 'WooCommerce_Germanized_Pro' ) ) :

final class WooCommerce_Germanized_Pro {

	/**
	 * Current WooCommerce Germanized Version
	 *
	 * @var string
	 */
	public $version = '3.5.1';

	/**
	 * Single instance of WooCommerce Germanized Main Class
	 *
	 * @var object
	 */
	protected static $_instance = null;

	public $contract_helper = null;

	public $multistep_checkout = null;

	public $plugin_file;

	/**
	 * Main WooCommerceGermanized Instance
	 *
	 * Ensures that only one instance of WooCommerceGermanized is loaded or can be loaded.
	 *
	 * @static
	 * @see WC_germanized_pro()
	 * @return WooCommerce_Germanized_Pro $instance Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-germanized-pro' ), '1.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-germanized-pro' ), '1.0' );
	}

	/**
	 * Global getter
	 *
	 * @param string  $key
	 * @return mixed
	 */
	public function __get( $key ) {
		return self::$key;
	}

	/**
	 * adds some initialization hooks and inits WooCommerce Germanized
	 */
	public function __construct() {

		// Auto-load classes on demand
		if ( function_exists( "__autoload" ) ) {
			spl_autoload_register( "__autoload" );
		}

		spl_autoload_register( array( $this, 'autoload' ) );

		$this->plugin_file = plugin_basename( __FILE__ );

		// Define constants
		$this->define_constants();

		// Vendidero Helper Functions
		include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/vendidero/vendidero-functions.php';

		// Check if dependencies are installed and up to date
		$init = WC_GZDP_Dependencies::instance( $this );

		add_filter( 'storeabill_enable_accounting', array( $this, 'enable_invoicing' ) );
		add_filter( 'storeabill_script_translations_i18n_path', array( $this, 'filter_i18n_path' ) );
		add_filter( 'storeabill_script_translations_i18n_domain', array( $this, 'filter_i18n_domain' ) );
		
		if ( ! $init->is_loadable() ) {
			// Make sure to at least register for updates
			add_filter( 'vendidero_updateable_products', array( $this, 'register_updates' ) );
			return;
		}

		include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/class-wc-gzdp-install.php';
		register_activation_hook( __FILE__, array( 'WC_GZDP_Install', 'install' ) );

		if ( ! did_action( 'plugins_loaded' ) ) {
			add_action( 'plugins_loaded', array( $this, 'load' ) );
		} else {
			$this->load();
		}
	}

	public function load() {
		do_action( 'woocommerce_gzdp_before_load' );

		$this->includes();
		$this->load_modules();

		// Hooks
		add_action( 'init', array( $this, 'init' ), 0 );
		add_filter( 'plugin_action_links_' . $this->plugin_file, array( $this, 'action_links' ) );
		add_action( 'pre_get_posts', array( $this, 'hide_attachments' ) );
		add_filter( 'vendidero_updateable_products', array( $this, 'register_updates' ) );
		
		// Loaded action
		do_action( 'woocommerce_gzdp_loaded' );
	}

	public function filter_i18n_path() {
	    return $this->language_path();
    }

    public function filter_i18n_domain() {
	    return 'woocommerce-germanized-pro';
    }

	/**
	 * Init WooCommerceGermanized when WordPress initializes.
	 */
	public function init() {
		$this->load_plugin_textdomain();
		
		// Before init action
		do_action( 'before_woocommerce_gzdp_init' );
		add_filter( 'woocommerce_locate_template', array( $this, 'filter_templates' ), 5, 3 );

		// Init action
		do_action( 'woocommerce_gzdp_init' );
	}

	public function enable_invoicing() {
	    return 'yes' === get_option( 'woocommerce_gzdp_invoice_enable' );
    }

	/**
	 * Define WC_Germanized Constants
	 */
	private function define_constants() {
		define( 'WC_GERMANIZED_PRO_PLUGIN_FILE', __FILE__ );
		define( 'WC_GERMANIZED_PRO_ABSPATH', dirname( WC_GERMANIZED_PRO_PLUGIN_FILE ) . '/' );
		define( 'WC_GERMANIZED_PRO_VERSION', $this->version );
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	private function includes() {
		include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/wc-gzdp-core-functions.php';
		include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/abstracts/abstract-wc-gzdp-theme.php';
		include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/abstracts/abstract-wc-gzdp-checkout-step.php';
		include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/abstracts/abstract-wc-gzdp-post-pdf.php';

		if ( is_admin() ) {
			include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/admin/class-wc-gzdp-admin.php';

			if ( class_exists( 'Vendidero\Germanized\Shipments\Admin\BulkActionHandler' ) ) {
				include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/admin/class-wc-gzdp-admin-packing-slip-bulk-handler.php';
			}

			include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/admin/settings/class-wc-gzdp-settings.php';
		}

		if ( defined( 'DOING_AJAX' ) ) {
			$this->ajax_includes();
		}

		include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/class-wc-gzdp-assets.php';
		include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/emails/class-wc-gzdp-email-helper.php';

		// API
		include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/api/class-wc-gzdp-rest-api.php';

		// Unit Price Helper
		include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/class-wc-gzdp-unit-price-helper.php';

		// Legal Checkbox Helper
		include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/class-wc-gzdp-legal-checkbox-helper.php';

		// Privacy Helper
		include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/class-wc-gzdp-privacy.php';

		// WPML Helper
		include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/class-wc-gzdp-wpml-helper.php';
	}

	/**
	 * Include required ajax files.
	 */
	public function ajax_includes() {
		include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/class-wc-gzdp-ajax.php';
	}

	public function sanitize_domain( $domain ) {
        $domain = esc_url_raw( $domain );
        $parsed = @parse_url( $domain );

        if ( empty( $parsed ) || empty( $parsed['host'] ) ) {
            return '';
        }

        // Remove www. prefix
        $parsed['host'] = str_replace( 'www.', '', $parsed['host'] );
        $domain         = $parsed['host'];

        return $domain;
    }

	public function load_modules() {
        $this->load_invoice_module();

		if ( get_option( 'woocommerce_gzdp_enable_vat_check' ) == 'yes' ) {
			$this->load_vat_module();
		}

		$this->load_checkout_module();
		$this->load_contract_module();
		$this->load_food_module();

		if ( apply_filters( 'woocommerce_gzdp_enable_legal_generator', true ) ) {
			$this->load_generator_module();
		}

		$this->load_theme_module();
		$this->load_elementor_module();

		\Vendidero\Germanized\Pro\StoreaBill\LegalPages::init();
		\Vendidero\Germanized\Pro\Packing\Automation::init();
	}

	/**
	 * Auto-load WC_Germanized classes on demand to reduce memory consumption.
	 *
	 * @param mixed   $class
	 * @return void
	 */
	public function autoload( $class ) {
        $class = strtolower( $class );

        if ( 0 !== strpos( $class, 'wc_gzdp_' ) ) {
            return;
        }

		$path = $this->plugin_path() . '/includes/';
		$file = 'class-' . str_replace( '_', '-', $class ) . '.php';
		
		if ( strpos( $class, 'wc_gzdp_pdf' ) === 0 ) {
			$path = $this->plugin_path() . '/includes/abstracts/';
			$file = str_replace( 'class-', 'abstract-', $file );
		} elseif ( strpos( $class, 'wc_gzdp_meta_box' ) === 0 ) {
			$path = $this->plugin_path() . '/includes/admin/meta-boxes/';
		} elseif ( strpos( $class, 'wc_gzdp_admin' ) === 0 ) {
			$path = $this->plugin_path() . '/includes/admin/';
		} elseif ( strpos( $class, 'wc_gzdp_theme' ) === 0 ) {
			$path = $this->plugin_path() . '/themes/';
		} elseif ( strpos( $class, 'wc_gzdp_checkout_step' ) === 0 ) {
			$path = $this->plugin_path() . '/includes/checkout/';
		} elseif ( strpos( $class, 'wc_gzdp_checkout_compatibility' ) === 0 ) {
			$path = $this->plugin_path() . '/includes/checkout/compatibility/';
		}
		
		if ( $path && is_readable( $path . $file ) ) {
			include_once $path . $file;
			return;
		}
	}

	/**
	 * Filter WooCommerce templates to look for woocommerce-germanized-pro templates
	 *  
	 * @param  string $template      
	 * @param  string $template_name 
	 * @param  string $template_path
	 * @return string                
	 */
	public function filter_templates( $template, $template_name, $template_path ) {
		$template_path = $this->template_path();
		$template_name = apply_filters( 'woocommerce_gzdp_template_name', $template_name );

		// Load Default
		if ( file_exists( apply_filters( 'woocommerce_gzdp_default_plugin_template', $this->plugin_path() . '/templates/' . $template_name, $template_name ) ) ) {
			// Check Theme
			$theme_template = locate_template( array(
				trailingslashit( $template_path ) . $template_name,
			) );

            if ( ! $theme_template ) {
	            $template = apply_filters( 'woocommerce_gzdp_default_plugin_template', $this->plugin_path() . '/templates/' . $template_name, $template_name );
            } else {
	            $template = $theme_template;
            }
		}
		
		return apply_filters( 'woocommerce_gzdp_filter_template', $template, $template_name, $template_path );
	}

	/**
	 * Get the plugin url.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Get the language path
	 *
	 * @return string
	 */
	public function language_path() {
		return $this->plugin_path() . '/i18n/languages';
	}

	/**
	 * Path to template folter
	 *  
	 * @return string 
	 */
	public function template_path() {
		return apply_filters( 'woocommerce_gzd_template_path', 'woocommerce-germanized-pro/' );
	}

	public function load_invoice_module() {
		include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/abstracts/abstract-wc-gzdp-invoice.php';

		include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/wc-gzdp-invoice-functions.php';
		include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/class-wc-gzdp-invoice-factory.php';
		include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/class-wc-gzdp-invoice-shortcodes.php';

		\Vendidero\Germanized\Pro\StoreaBill\PackingSlips::init();
		\Vendidero\Germanized\Pro\StoreaBill\AccountingHelper::init();
	}

	public function load_generator_module() {
	    add_action( 'woocommerce_gzdp_check_generator_versions', array( $this, 'check_generator_versions' ), 10 );
		add_filter( 'woocommerce_gzd_admin_notes', array( $this, 'register_generator_version_note' ), 10 );

		if ( is_admin() ) {
			include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/admin/class-wc-gzdp-admin-generator.php';
		}
	}

	public function register_generator_version_note( $notes ) {
		include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/admin/notes/class-wc-gzdp-admin-note-generator-versions.php';

	    $notes[] = 'WC_GZDP_Admin_Note_Generator_Versions';

	    return $notes;
    }

	public function check_generator_versions() {
	    if ( ! function_exists( 'VD' ) ) {
	        return;
        }

		include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/admin/class-wc-gzdp-admin-generator.php';

        $generators = WC_GZDP_Admin_Generator::instance();
        $outdated   = array();

        foreach( $generators->get_generators() as $generator => $data ) {
            if ( $page_id = $generators->get_page_id( $generator ) ) {
                $version = get_post_meta( $page_id, 'woocommerce_gzdp_generator_version_' . $generator, true );

                if ( $version && ! empty( $version ) ) {
	                $product = WC_germanized_pro()->get_vd_product();

	                if ( ! $product || ! $product->is_registered() ) {
		                return;
	                }

	                /**
	                 * Init (just in case the request is from the frontend)
	                 */
	                if ( is_null( VD()->api ) ) {
	                    VD()->init();
                    }

	                $api = VD()->api;

	                if ( is_null( $api ) ) {
	                    return;
                    }

                    $remote_data = $api->generator_version_check( $product, $generator );

	                if ( ! $remote_data ) {
	                    return;
                    }

	                $remote_version = $remote_data->version;

	                /**
	                 * Check if current page version is outdated
	                 */
	                if ( version_compare( $version, $remote_version, "<" ) ) {
                        $outdated[ $generator ] = array(
                            'page_id'     => $page_id,
                            'new_version' => $remote_version,
                            'old_version' => $version,
                        );
	                }
                }
            }
        }

        update_option( 'woocommerce_gzdp_generator_outdated_data', $outdated );
    }

	public function load_vat_module() {
		include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/class-wc-gzdp-vat-helper.php';
	}

	public function load_contract_module() {
		if ( get_option( 'woocommerce_gzdp_contract_after_confirmation' ) == "yes" ) {
			$this->contract_helper = include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/class-wc-gzdp-contract-helper.php';
		}
	}

	public function load_food_module() {
		\Vendidero\Germanized\Pro\Food\Helper::init();
		\Vendidero\Germanized\Pro\Food\Deposits\Helper::init();
	}

	public function load_checkout_module() {
		$this->multistep_checkout = include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/class-wc-gzdp-multistep-checkout.php';
	}

	public function load_theme_module() {
        include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/class-wc-gzdp-theme-helper.php';
	}

    public function load_elementor_module() {
	    include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/class-wc-gzdp-elementor-helper.php';
    }

    public function is_registered() {
        if ( function_exists( 'VD' ) ) {
            if ( $plugin = $this->get_vd_product() ) {
                return $plugin->is_registered();
            }
        }

        return false;
    }

	public function register_updates( $products ) {
		array_push( $products, vendidero_register_product( $this->plugin_file, '148' ) );

		return $products;
	}

    public function get_vd_product() {
		$product = VD()->get_product( $this->plugin_file );

		// Make sure that the helper has loaded products
		if ( is_null( $product ) || ! $product ) {
			VD()->load();

			$product = VD()->get_product( $this->plugin_file );
		}

        return $product;
    }

	public function log( $message, $type = 'info', $source = 'core' ) {
		$logger   = wc_get_logger();
		$is_debug = defined( 'WP_DEBUG' ) && WP_DEBUG ? true : false;

		if ( ! $logger || ! apply_filters( 'woocommerce_gzdp_enable_logging', $is_debug ) ) {
			return;
		}

		if ( ! is_callable( array( $logger, $type ) ) ) {
			$type = 'info';
		}

		$logger->{$type}( $message, array( 'source' => 'wc-gzdp-' . $source ) );
	}

	/**
	 * Hide invoices from attachment listings
	 *  
	 * @param  object $query 
	 * @return object        
	 */
	public function hide_attachments( $query ) {
		
		$filter = false;
		$post_type = $query->get( 'post_type' );

		if ( $query->is_attachment || ( ! is_array( $post_type ) && $post_type == 'attachment' ) || ( is_array( $post_type ) && in_array( 'attachment', $post_type ) ) )
			$filter = true;

		if ( $filter ) {

			$meta_query = ( $query->get( 'meta_query' ) ? $query->get( 'meta_query' ) : array() );

			// Nest existing meta queries to make sure relation is being kept
			if ( ! empty( $meta_query ) ) {
				$meta_query = array( $meta_query );
			}

			// Add new meta query to unselect private attachments
			$meta_query[] = array(
				'relation' => 'AND',
				array(
				    'key'     => '_wc_gzdp_private',
					'compare' => 'NOT EXISTS'
				)
			);
			
			$query->set( 'meta_query', $meta_query );

		}

		return $query;
	}

	/**
	 * Filter Email template to include WooCommerce Germanized template files
	 *
	 * @param string  $core_file
	 * @param string  $template
	 * @param string  $template_base
	 * @return string
	 */
	public function email_templates( $core_file, $template, $template_base ) {
		if ( ! file_exists( $template_base . $template ) && file_exists( $this->plugin_path() . '/templates/' . $template ) ) {
			$core_file = $this->plugin_path() . '/templates/' . $template;
		}
		
		return apply_filters( 'woocommerce_germanized_pro_email_template_hook', $core_file, $template, $template_base );
	}

	public function get_upload_dir_suffix() {
		return get_option( 'woocommerce_gzdp_invoice_path_suffix' );
	}

	public function get_upload_dir() {
		$this->set_upload_dir_filter();
		$upload_dir = wp_upload_dir();
		$this->unset_upload_dir_filter();

		return apply_filters( 'woocommerce_gzdp_upload_dir', $upload_dir );
	}

	public function get_relative_upload_path( $path ) {
		$this->set_upload_dir_filter();
		$path = _wp_relative_upload_path( $path );
		$this->unset_upload_dir_filter();

		return apply_filters( 'woocommerce_gzdp_relative_upload_path', $path );
	}

	public function set_upload_dir_filter() {
		add_filter( 'upload_dir', array( $this, "filter_upload_dir" ), 150, 1 );
	}

	public function unset_upload_dir_filter() {
		remove_filter( 'upload_dir', array( $this, "filter_upload_dir" ), 150 );
	}

	public function filter_upload_dir( $args ) {
		$upload_base = trailingslashit( $args['basedir'] );
		$upload_url = trailingslashit( $args['baseurl'] );

		$args['basedir'] = apply_filters( 'wc_germanized_pro_upload_path', $upload_base . 'wc-gzdp-' . $this->get_upload_dir_suffix() );
		$args['baseurl'] = apply_filters( 'wc_germanized_pro_upload_url', $upload_url . 'wc-gzdp-' . $this->get_upload_dir_suffix() );

		$args['path'] = $args['basedir'] . $args['subdir'];
		$args['url'] = $args['baseurl'] . $args['subdir'];

		return $args;
	}

	/**
	 * Load Localisation files for WooCommerce Germanized.
	 */
	public function load_plugin_textdomain() {
		if ( function_exists( 'determine_locale' ) ) {
			$locale = determine_locale();
		} else {
			// @todo Remove when start supporting WP 5.0 or later.
			$locale = is_admin() ? get_user_locale() : get_locale();
		}

		$locale = apply_filters( 'plugin_locale', $locale, 'woocommerce-germanized-pro' );

		unload_textdomain( 'woocommerce-germanized-pro' );
		load_textdomain( 'woocommerce-germanized-pro', trailingslashit( WP_LANG_DIR ) . 'woocommerce-germanized-pro/woocommerce-germanized-pro-' . $locale . '.mo' );
		load_plugin_textdomain( 'woocommerce-germanized-pro', false, plugin_basename( dirname( __FILE__ ) ) . '/i18n/languages/' );
	}

	/**
	 * Load a single translation by textdomain
	 *
	 * @param string  $path
	 * @param string  $textdomain
	 * @param string  $prefix
	 */
	public function load_translation( $path, $textdomain, $prefix ) {
		if ( is_readable( $path . $prefix . '-de_DE.mo' ) ) {
			load_textdomain( $textdomain, $path . $prefix . '-de_DE.mo' );
		}
	}

	/**
	 * Show action links on the plugin screen
	 *
	 * @param mixed   $links
	 * @return array
	 */
	public function action_links( $links ) {
		return array_merge( array(
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=germanized' ) . '">' . __( 'Settings', 'woocommerce-germanized-pro' ) . '</a>',
			'<a href="https://vendidero.de/dashboard/help-desk">' . __( 'Support', 'woocommerce-germanized-pro' ) . '</a>',
		), $links );
	}
}

endif;

/**
 * @return WooCommerce_Germanized_Pro $pro instance
 */
function WC_germanized_pro() {
	return WooCommerce_Germanized_Pro::instance();
}

$GLOBALS['woocommerce_germanized_pro'] = WC_germanized_pro();
?>
