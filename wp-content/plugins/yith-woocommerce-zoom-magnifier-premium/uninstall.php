<?php
/**
 * Uninstall plugin
 *
 * @author YITH
 * @package YITH\ZoomMagnifier
 * @version 1.1.2
 */

// If uninstall not called from WordPress exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;
