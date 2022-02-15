<?php
/**
 * WPML Helper
 *
 * Specific configuration for WPML
 *
 * @class 		WC_GZD_WPML_Helper
 * @category	Class
 * @author 		vendidero
 */
class WC_GZDP_WPML_Helper {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {
		if ( ! $this->is_activated() ) {
			return;
		}

		add_action( 'init', array( $this, 'init' ), 10 );
	}

	public function init() {
		add_action( 'woocommerce_gzd_reload_locale', array( $this, 'reload_locale' ) );
		add_action( 'storeabill_reload_locale', array( $this, 'reload_locale' ) );

		// Multistep step name refresh after init
		$this->refresh_step_names();
	}

	public function refresh_step_names() {
		if ( isset( WC_germanized_pro()->multistep_checkout ) ) {

			$step_names = WC_germanized_pro()->multistep_checkout->get_step_names();
			$steps      = WC_germanized_pro()->multistep_checkout->steps;

			foreach ( $steps as $key => $step ) {
				$step->title = $step_names[ $step->id ];
			}
		}
	}

	public function reload_locale() {
        unload_textdomain( 'woocommerce-germanized-pro' );

		WC_germanized_pro()->load_plugin_textdomain();
	}

	public function get_gzd_compatibility() {
	    $gzd = WC_germanized();

	    if ( is_callable( array( $gzd, 'get_compatibility' ) ) ) {
	        return $gzd->get_compatibility( 'wpml' );
        }

        return false;
    }

	public function is_activated() {
		return WC_GZDP_Dependencies::instance()->is_wpml_activated();
	}
}

return WC_GZDP_WPML_Helper::instance();