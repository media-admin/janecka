<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_GZDP_Dependencies {

	/**
	 * Single instance of WooCommerce Germanized Main Class
	 *
	 * @var object
	 */
	protected static $_instance = null;

	/**
	 * This is the minimum WP version supported by Germanized
	 *
	 * @var string
	 */
	public $wp_minimum_version_required = '5.4';

	/**
	 * This is the minimum Woo version supported by Germanized
	 *
	 * @var string
	 */
	public $wc_minimum_version_required = '3.9';

	/**
	 * This is the minimum Woo Germanized version supported by Germanized Pro
	 *
	 * @var string
	 */
	public $wc_gzd_minimum_version_required = '3.4';

	/**
	 * This is the maximum Woo Germanized version supported by Germanized Pro
	 *
	 * @var string
	 */
	public $wc_gzd_maximum_version_supported = '3.9';

	/**
	 * Lazy initiated activated plugins list
	 *
	 * @var null|array
	 */
	protected $active_plugins = null;

	public $loadable = true;

	public static function instance( $plugin = null ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $plugin );
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
	
	public function __construct( $plugin = null ) {
		if ( ! $plugin ) {
			$plugin = WC_germanized_pro();
		}

		$this->plugin = $plugin;

		if ( $this->is_wp_outdated() ) {
			$this->loadable = false;

			add_action( 'admin_notices', array( $this, 'wp_version_notice' ) );
		} elseif ( ! $this->is_woocommerce_activated() || $this->is_woocommerce_outdated() ) {
			$this->loadable = false;

			add_action( 'admin_notices', array( $this, 'dependencies_notice' ) );
		} elseif( ! $this->is_woocommerce_gzd_activated() || $this->is_woocommerce_gzd_outdated() || $this->is_woocommerce_gzd_unsupported() ) {
			$this->loadable = false;

			add_action( 'admin_notices', array( $this, 'dependencies_gzd_notice' ) );
		}
	}

	public function is_plugin_activated( $plugin_slug ) {
		if ( is_null( $this->active_plugins ) ) {
			$this->active_plugins = (array) get_option( 'active_plugins', array() );

			if ( is_multisite() ) {
				$this->active_plugins = array_merge( $this->active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
			}
		}

		if ( strpos( $plugin_slug, '.php' ) === false ) {
			$plugin_slug = trailingslashit( $plugin_slug ) . $plugin_slug . '.php';
		}

		return ( in_array( $plugin_slug, $this->active_plugins ) || array_key_exists( $plugin_slug, $this->active_plugins ) );
	}

	public function get_wc_min_version_required() {
		return $this->wc_minimum_version_required;
	}

	public function get_wp_min_version_required() {
		return $this->wp_minimum_version_required;
	}

	public function get_wc_gzd_min_version_required() {
		return $this->wc_gzd_minimum_version_required;
	}

	public function get_wc_gzd_max_version_supported() {
		return $this->wc_gzd_maximum_version_supported;
	}

	/**
	 * This method removes accuration from $ver2 if this version is more accurate than $main_ver
	 */
	public function compare_versions( $main_ver, $ver2, $operator ) {
		$expl_main_ver = explode( '.', $main_ver );
		$expl_ver2     = explode( '.', $ver2 );

		// Check if ver2 string is more accurate than main_ver
		if ( sizeof( $expl_main_ver ) == 2 && sizeof( $expl_ver2 ) > 2 ) {
			$new_ver_2 = array_slice( $expl_ver2, 0, 2 );
			$ver2      = implode( '.', $new_ver_2 );
		}

		return version_compare( $main_ver, $ver2, $operator );
	}

	/**
	 * Checks if WooCommerce is activated
	 *
	 * @return boolean true if WooCommerce is activated
	 */
	public function is_woocommerce_activated() {
		return $this->is_plugin_activated( 'woocommerce/woocommerce.php' );
	}

	public function is_woocommerce_outdated() {
		$version = get_option( 'woocommerce_db_version' );

		if ( empty( $version ) ) {
			$version = get_option( 'woocommerce_version' );
		}

		return $this->compare_versions( $version, $this->get_wc_min_version_required(), '<' );
	}

	public function is_wp_outdated() {
		global $wp_version;

		return $this->compare_versions( $wp_version, $this->get_wp_min_version_required(), '<' );
	}

	public function get_plugin_version( $plugin_slug ) {
		$version = $this->parse_version( get_option( $plugin_slug . '_version', '1.0' ) );

		return $version;
	}

	protected function parse_version( $version ) {
		$version = preg_replace( '#(\.0+)+($|-)#', '', $version );

		// Remove/ignore beta, alpha, rc status from version strings
		$version = trim( preg_replace( '#(beta|alpha|rc)#', ' ', $version ) );

		// Make sure version has at least 2 signs, e.g. 3 -> 3.0
		if ( strlen( $version ) === 1 ) {
			$version = $version . '.0';
		}

		return $version;
	}

	public function is_wpml_activated() {
		return ( $this->is_plugin_activated( 'sitepress-multilingual-cms/sitepress.php' ) && $this->is_plugin_activated( 'woocommerce-multilingual/wpml-woocommerce.php' ) );
	}

	/**
	 * Checks if WooCommerce Germanized is activated
	 *  
	 * @return boolean true if WooCommerce Germanized is activated
	 */
	public function is_woocommerce_gzd_activated() {
		return $this->is_plugin_activated( 'woocommerce-germanized/woocommerce-germanized.php' );
	}

	public function get_woocommerce_gzd_version() {
		$version = get_option( 'woocommerce_gzd_db_version' );

		if ( empty( $version ) ) {
			$version = get_option( 'woocommerce_gzd_version' );
		}

		return $this->parse_version( $version );
	}

	public function is_woocommerce_gzd_outdated() {
		return $this->compare_versions( $this->get_woocommerce_gzd_version(), $this->get_wc_gzd_min_version_required(), '<' );
	}

	public function is_woocommerce_gzd_unsupported() {
		return $this->compare_versions( $this->get_wc_gzd_max_version_supported(), $this->get_woocommerce_gzd_version(), '<' );
	}

	public function is_loadable() {
		return apply_filters( 'woocommerce_gzdp_is_loadable', $this->loadable );
	}

	public function wp_version_notice() {
		global $dependencies;
		$dependencies = $this;

		include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/admin/views/html-notice-wp-version.php';
	}

	protected function is_network_wide_install( $plugin = 'woocommerce-germanized-pro/woocommerce-germanized-pro.php' ) {
		return function_exists( 'is_plugin_active_for_network' ) && is_plugin_active_for_network( $plugin );
	}

	public function dependencies_notice() {
		/**
		 * Do not display dependency notices in case this plugin is network-wide activated but is not the main site.
		 */
		if ( current_user_can( 'activate_plugins' ) && ( ! $this->is_network_wide_install() || is_main_site() ) ) {
			global $dependencies;
			$dependencies = $this;

			include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/admin/views/html-notice-dependencies.php';
		}
	}

	public function dependencies_gzd_notice() {
		/**
		 * Do not display dependency notices in case this plugin is network-wide activated but is not the main site.
		 */
		if ( current_user_can( 'activate_plugins' ) && ( ! $this->is_network_wide_install() || is_main_site() ) ) {
			global $dependencies;
			$dependencies = $this;

			include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/admin/views/html-notice-dependencies-gzd.php';
		}
	}
}
