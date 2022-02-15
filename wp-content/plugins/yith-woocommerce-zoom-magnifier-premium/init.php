<?php
/**
 * Plugin Name: YITH WooCommerce Product Gallery & Image Zoom Premium
 * Plugin URI: https://yithemes.com/themes/plugins/yith-woocommerce-zoom-magnifier/
 * Description: <code><strong>YITH WooCommerce Product Gallery & Image Zoom </strong></code> allows you to add a zoom effect to product images and a thumbnail slider for the product image gallery. <a href="https://yithemes.com/" target="_blank">Get more plugins for your e-commerce shop on <strong>YITH</strong></a>.
 * Version: 2.2.0
 * Author: YITH
 * Author URI: https://yithemes.com/
 * Text Domain: yith-woocommerce-zoom-magnifier
 * Domain Path: /languages/
 * WC requires at least: 5.6
 * WC tested up to: 5.9
 *
 * @package YITH\ZoomMagnifier
 **/

/*
	Copyright 2015-2021 Your Inspiration Solutions (email : plugins@yithemes.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

// region    ****    Check if prerequisites are satisfied before enabling and using current plugin.

if ( ! function_exists( 'is_plugin_active' ) ) {
	if ( ! function_exists( 'get_plugin_data' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
}

/**
 * Print a notice if WooCommerce is not installed.
 */
function yith_ywzm_premium_install_woocommerce_admin_notice() {
	?>
	<div class="error">
		<p><?php esc_html_e( 'YITH WooCommerce Product Gallery & Image Zoom  is enabled but not effective. It requires WooCommerce in order to work.', 'yith-woocommerce-zoom-magnifier' ); ?></p>
	</div>
	<?php
}

/**
 * Check if a free version is currently active and try disabling before activating this one
 */
if ( ! function_exists( 'yit_deactive_free_version' ) ) {
	require_once 'plugin-fw/yit-deactive-plugin.php';
}
yit_deactive_free_version( 'YITH_YWZM_FREE_INIT', plugin_basename( __FILE__ ) );


if ( ! function_exists( 'yith_plugin_registration_hook' ) ) {
	require_once 'plugin-fw/yit-plugin-registration-hook.php';
}
register_activation_hook( __FILE__, 'yith_plugin_registration_hook' );

// region    ****    Define constants.
if ( ! defined( 'YITH_YWZM_INIT' ) ) {
	define( 'YITH_YWZM_INIT', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'YITH_YWZM_PREMIUM' ) ) {
	define( 'YITH_YWZM_PREMIUM', '1' );
}

if ( ! defined( 'YITH_YWZM_SLUG' ) ) {
	define( 'YITH_YWZM_SLUG', 'yith-woocommerce-zoom-magnifier' );
}

if ( ! defined( 'YITH_YWZM_SECRET_KEY' ) ) {
	define( 'YITH_YWZM_SECRET_KEY', 'KNCq0eCQTDT1bApY4poK' );
}

if ( ! defined( 'YITH_YWZM_VERSION' ) ) {
	define( 'YITH_YWZM_VERSION', '2.2.0' );
}

if ( ! defined( 'YITH_YWZM_SCRIPT_VERSION' ) ) {
	define( 'YITH_YWZM_SCRIPT_VERSION', '2.2.0' );
}

if ( ! defined( 'YITH_YWZM_FILE' ) ) {
	define( 'YITH_YWZM_FILE', __FILE__ );
}

if ( ! defined( 'YITH_YWZM_DIR' ) ) {
	define( 'YITH_YWZM_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'YITH_YWZM_URL' ) ) {
	define( 'YITH_YWZM_URL', plugins_url( '/', __FILE__ ) );
}

if ( ! defined( 'YITH_YWZM_ASSETS_URL' ) ) {
	define( 'YITH_YWZM_ASSETS_URL', YITH_YWZM_URL . 'assets' );
}

if ( ! defined( 'YITH_YWZM_TEMPLATE_DIR' ) ) {
	define( 'YITH_YWZM_TEMPLATE_DIR', YITH_YWZM_DIR . 'templates' );
}

if ( ! defined( 'YITH_YWZM_ASSETS_IMAGES_URL' ) ) {
	define( 'YITH_YWZM_ASSETS_IMAGES_URL', YITH_YWZM_ASSETS_URL . '/images/' );
}

if ( ! defined( 'YITH_YWZM_LIB_DIR' ) ) {
	define( 'YITH_YWZM_LIB_DIR', YITH_YWZM_DIR . 'lib/' );
}

if ( ! defined( 'YITH_YWZM_VIEW_PATH' ) ) {
	define( 'YITH_YWZM_VIEW_PATH', YITH_YWZM_DIR . 'view/' );
}


// endregion.

/* Plugin Framework Version Check */
if ( ! function_exists( 'yit_maybe_plugin_fw_loader' ) && file_exists( YITH_YWZM_DIR . 'plugin-fw/init.php' ) ) {
	require_once YITH_YWZM_DIR . 'plugin-fw/init.php';
}
yit_maybe_plugin_fw_loader( YITH_YWZM_DIR );

/**
 * Let's start the game
 */
function yith_ywzm_premium_init() {

	/**
	 * Load text domain and start plugin
	 */
	load_plugin_textdomain( 'yith-woocommerce-zoom-magnifier', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	add_option( 'yith_wcmg_slider_direction', apply_filters( 'yith_wcmg_slider_direction', 'left' ) );

	define( 'YITH_WCMG', true );
	define( 'YITH_WCMG_URL', plugin_dir_url( __FILE__ ) );

	// Load required classes and functions.
	require_once YITH_YWZM_LIB_DIR . 'class.yith-wcmg-admin.php';
	require_once YITH_YWZM_LIB_DIR . 'class.yith-wcmg-frontend.php';
	require_once YITH_YWZM_LIB_DIR . 'class.yith-woocommerce-zoom-magnifier.php';
	require_once YITH_YWZM_LIB_DIR . 'class.yith-woocommerce-zoom-magnifier-premium.php';
	require_once YITH_YWZM_LIB_DIR . 'class.yith-ywzm-plugin-fw-loader.php';
	require_once YITH_YWZM_LIB_DIR . 'class.yith-wcmg-frontend-premium.php';
	require_once YITH_YWZM_LIB_DIR . 'functions.yith-ywzm.php';


	if ( is_admin() ) {
		require_once YITH_YWZM_LIB_DIR . 'class.yith-ywzm-exclusions-handler.php';
		YITH_YWZM_Exclusions_Handler();
	}



	require_once YITH_YWZM_LIB_DIR . 'compatibility/class-yith-wczm-compatibility.php';

	YITH_YWZM_Plugin_FW_Loader::get_instance();

	// Let's start the game!
	global $yith_wcmg;

	$yith_wcmg = new YITH_WooCommerce_Zoom_Magnifier_Premium();
}

add_action( 'yith_ywzm_premium_init', 'yith_ywzm_premium_init' );

/**
 * Check WC installation
 */
function yith_ywzm_premium_install() {

	if ( ! function_exists( 'WC' ) ) {
		add_action( 'admin_notices', 'yith_ywzm_premium_install_woocommerce_admin_notice' );
	} else {
		do_action( 'yith_ywzm_premium_init' );
	}

}

add_action( 'plugins_loaded', 'yith_ywzm_premium_install', 11 );

if ( ! function_exists( 'yith_plugin_onboarding_registration_hook' ) ) {
	include_once 'plugin-upgrade/functions-yith-licence.php';
}
register_activation_hook( __FILE__, 'yith_plugin_onboarding_registration_hook' );
