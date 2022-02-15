<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

/*
Plugin Name: CleverReach® WP
Plugin URI: https://wordpress.org/plugins/cleverreach-wp/
Description: Spotify, Levi’s and DHL create and send their newsletters with CleverReach®: easy to handle and at the same time all requirements for professional email marketing.
Version: 1.5.14
Author: CleverReach GmbH & Co. KG
Author URI: https://www.cleverreach.com
License: GPL
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $wpdb;

require_once plugin_dir_path( __FILE__ ) . '/vendor/autoload.php';
require_once trailingslashit( __DIR__ ) . 'inc/autoloader.php';

\CleverReach\WordPress\Plugin::instance( $wpdb, __FILE__ );
