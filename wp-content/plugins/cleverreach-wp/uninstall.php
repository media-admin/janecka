<?php
/**
 * CleverReach Uninstall
 *
 * Uninstalling CleverReach deletes all user data.
 *
 * @author      CleverReach
 * @category    Core
 * @package     CleverReach/Uninstaller
 * @version     1.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . '/vendor/autoload.php';
require_once trailingslashit( __DIR__ ) . 'inc/autoloader.php';

global $wpdb;

\CleverReach\WordPress\Components\Utility\Initializer::register();

$hook_handler = new \CleverReach\WordPress\Components\Hook_Handler();
$hook_handler->cleverreach_uninstall();
