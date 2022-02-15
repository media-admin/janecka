<?php

namespace Vendidero\Germanized\DPD;

use DateTime;
use DateTimeZone;
use Exception;
use Vendidero\Germanized\DPD\Api\Api;
use Vendidero\Germanized\Shipments\ShippingProvider\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class Package {

	/**
	 * Version.
	 *
	 * @var string
	 */
	const VERSION = '1.1.0';

	protected static $api = null;

	protected static $iso = null;

	/**
	 * Init the package - load the REST API Server class.
	 */
	public static function init() {

		if ( self::has_dependencies() ) {
			// Add shipping provider
			add_filter( 'woocommerce_gzd_shipping_provider_class_names', array( __CLASS__, 'add_shipping_provider_class_name' ), 20, 1 );
		}

		if ( ! did_action( 'woocommerce_gzd_shipments_init' ) ) {
			add_action( 'woocommerce_gzd_shipments_init', array( __CLASS__, 'on_shipments_init' ), 20 );
		} else {
			self::on_shipments_init();
		}
	}

	public static function on_shipments_init() {
		if ( ! self::has_dependencies() ) {
			return;
		}

		self::includes();

		if ( self::is_enabled() ) {
			if ( self::has_load_dependencies() ) {
				self::init_hooks();
			} else {
				add_action( 'admin_notices', array( __CLASS__, 'load_dependencies_notice' ) );
			}
		}
	}

	public static function load_dependencies_notice() {
		?>
		<div class="notice notice-error error">
			<p><?php printf( _x( 'To enable communication between your shop and DPD, the PHP <a href="%s">SOAPClient</a> is required. Please contact your host and make sure that SOAPClient is <a href="%s">installed</a>.', 'dpd', 'woocommerce-germanize-dpd' ), 'https://www.php.net/manual/class.soapclient.php', admin_url( 'admin.php?page=wc-status' ) ); ?></p>
		</div>
		<?php
	}

	public static function has_dependencies() {
		return ( class_exists( 'WooCommerce' ) && class_exists( '\Vendidero\Germanized\Shipments\Package' ) && self::base_country_is_supported() && apply_filters( 'woocommerce_gzd_dpd_enabled', true ) );
	}

	public static function has_load_dependencies() {
		return ( ! class_exists( 'SoapClient' ) ? false : true );
	}

	public static function base_country_is_supported() {
		return in_array( self::get_base_country(), self::get_supported_countries() );
	}

	public static function get_supported_countries() {
		return array( 'DE', 'AT' );
	}

	public static function get_date_de_timezone( $format = 'Y-m-d' ) {
		try {
			$tz_obj         = new DateTimeZone(  'Europe/Berlin' );
			$current_date   = new DateTime( "now", $tz_obj );
			$date_formatted = $current_date->format( $format );

			return $date_formatted;
		} catch( Exception $e ) {
			return date( $format );
		}
	}

	public static function is_enabled() {
		return ( self::is_dpd_enabled() );
	}

	public static function is_dpd_enabled() {
		$is_enabled = false;

		if ( method_exists( '\Vendidero\Germanized\Shipments\ShippingProvider\Helper', 'is_shipping_provider_activated' ) ) {
			$is_enabled = Helper::instance()->is_shipping_provider_activated( 'dpd' );
		} else {
			if ( $provider = self::get_dpd_shipping_provider() ) {
				$is_enabled = $provider->is_activated();
			}
		}

		return $is_enabled;
	}

	public static function get_api_username() {
	    if ( self::is_debug_mode() && defined( 'WC_GZD_DPD_API_USERNAME' ) ) {
	        return WC_GZD_DPD_API_USERNAME;
	    } else {
	        return self::get_dpd_shipping_provider()->get_api_username();
	    }
	}

	public static function get_api_password() {
		if ( self::is_debug_mode() && defined( 'WC_GZD_DPD_API_PASSWORD' ) ) {
			return WC_GZD_DPD_API_PASSWORD;
		} else {
			return self::get_dpd_shipping_provider()->get_setting( 'api_password' );
		}
	}

    public static function get_cloud_api_partner_name() {
	    if ( self::is_debug_mode() ) {
		    if ( defined( 'WC_GZD_DPD_CLOUD_API_PARTNER_NAME' ) ) {
                return WC_GZD_DPD_CLOUD_API_PARTNER_NAME;
		    } else {
                return 'DPD Sandbox';
		    }
	    } else {
		    return 'Vendidero';
	    }
    }

    public static function get_cloud_api_partner_token() {
	    if ( self::is_debug_mode() && defined( 'WC_GZD_DPD_CLOUD_API_PARTNER_TOKEN' ) ) {
            return WC_GZD_DPD_CLOUD_API_PARTNER_TOKEN;
	    } else {
		    return 'C412B4B6B4C746230786';
	    }
    }

	public static function get_cloud_api_username() {
		if ( self::is_debug_mode() && defined( 'WC_GZD_DPD_CLOUD_API_USERNAME' ) ) {
			return WC_GZD_DPD_CLOUD_API_USERNAME;
		} else {
			return self::get_dpd_shipping_provider()->get_setting( 'cloud_api_username' );
		}
	}

	public static function get_cloud_api_password() {
		if ( self::is_debug_mode() && defined( 'WC_GZD_DPD_CLOUD_API_PASSWORD' ) ) {
			return WC_GZD_DPD_CLOUD_API_PASSWORD;
		} else {
			return self::get_dpd_shipping_provider()->get_setting( 'cloud_api_password' );
		}
	}

	public static function get_api_language() {
		return 'de_DE';
	}

    public static function get_current_api_type() {
        $api_type = 'cloud';

        if ( $provider = self::get_dpd_shipping_provider() ) {
            $api_type = $provider->get_api_type();
        }

        return apply_filters( "woocommerce_gzd_dpd_api_type", $api_type );
    }

	/**
	 * @return Api
	 */
	public static function get_api() {
        if ( 'cloud' === self::get_current_api_type() ) {
	        $api = \Vendidero\Germanized\DPD\Api\Cloud\Api::instance();
        } else {
	        $api = \Vendidero\Germanized\DPD\Api\WebConnect\Api::instance();
        }

		if ( self::is_debug_mode() ) {
			$api::dev();
		} else {
			$api::prod();
		}

	    return $api;
	}

	private static function includes() {

	}

	public static function init_hooks() {
		// Filter templates
		add_filter( 'woocommerce_gzd_default_plugin_template', array( __CLASS__, 'filter_templates' ), 10, 3 );
	}

	public static function filter_templates( $path, $template_name ) {
		if ( file_exists( self::get_path() . '/templates/' . $template_name ) ) {
			$path = self::get_path() . '/templates/' . $template_name;
		}

		return $path;
	}

	/**
	 * @return false
	 */
	public static function get_dpd_shipping_provider() {
		$provider = wc_gzd_get_shipping_provider( 'dpd' );

		if ( ! is_a( $provider, '\Vendidero\Germanized\DPD\ShippingProvider\DPD' ) ) {
			return false;
		}

		return $provider;
	}

	public static function add_shipping_provider_class_name( $class_names ) {
		$class_names['dpd'] = '\Vendidero\Germanized\DPD\ShippingProvider\DPD';

		return $class_names;
	}

	public static function install() {
		self::on_shipments_init();
		Install::install();
	}

	public static function install_integration() {
		self::install();
	}

	public static function is_integration() {
		return class_exists( 'WooCommerce_Germanized' ) ? true : false;
	}

	/**
	 * Return the version of the package.
	 *
	 * @return string
	 */
	public static function get_version() {
		return self::VERSION;
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_path() {
		return dirname( __DIR__ );
	}

	public static function get_template_path() {
		return 'woocommerce-germanized/';
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_url() {
		return plugins_url( '', __DIR__ );
	}

	public static function get_assets_url() {
		return self::get_url() . '/assets';
	}

	public static function is_debug_mode() {
		$is_debug_mode = ( defined( 'WC_GZD_DPD_DEBUG' ) && WC_GZD_DPD_DEBUG );

		return $is_debug_mode;
	}

	public static function enable_logging() {
		return ( defined( 'WC_GZD_DPD_LOG_ENABLE' ) && WC_GZD_DPD_LOG_ENABLE ) || self::is_debug_mode();
	}

	private static function define_constant( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	public static function log( $message, $type = 'info' ) {
		$logger         = wc_get_logger();
		$enable_logging = self::enable_logging() ? true : false;

		if ( ! $logger ) {
			return false;
		}

		/**
		 * Filter that allows adjusting whether to enable or disable
		 * logging for the DPD package (e.g. API requests).
		 *
		 * @param boolean $enable_logging True if logging should be enabled. False otherwise.
		 *
		 * @package Vendidero/Germanized/DPD
		 */
		if ( ! apply_filters( 'woocommerce_gzd_dpd_enable_logging', $enable_logging ) ) {
			return false;
		}

		if ( ! is_callable( array( $logger, $type ) ) ) {
			$type = 'info';
		}

		$logger->{$type}( $message, array( 'source' => 'woocommerce-germanized-dpd' ) );

		return true;
	}

	public static function get_base_country() {
		$base_location = wc_get_base_location();
		$base_country  = $base_location['country'];

		/**
		 * Filter to adjust the DPD base country.
		 *
		 * @param string $country The country as ISO code.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/DPD
		 */
		return apply_filters( 'woocommerce_gzd_dpd_base_country', $base_country );
	}

	/**
     * Returns a weight in g
     *
	 * @param $weight
	 * @param string $base_unit
	 *
	 * @return float
	 */
	public static function convert_weight( $weight, $base_unit = 'kg' ) {
	    if ( 'g' !== $base_unit ) {
		    $weight = wc_get_weight( $weight, 'g', $base_unit );
	    }

		$weight = ((float) $weight) / 10;

		return \Automattic\WooCommerce\Utilities\NumberUtil::round( $weight, 0 );
	}

	/**
     * Returns a dimension in cm without decimal points
     *
	 * @param $dimension
	 * @param string $base_unit
	 *
	 * @return float
	 */
	public static function convert_dimension( $dimension, $base_unit = 'cm' ) {
	    if ( 'cm' !== $base_unit ) {
		    $dimension = wc_get_dimension( $dimension, 'cm', $base_unit );
	    }

		return \Automattic\WooCommerce\Utilities\NumberUtil::round( $dimension, 0 );
	}
}