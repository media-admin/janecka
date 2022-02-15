<?php 
require_once ( 'Helpers/Plugin_Constants.php' );

use ACFWF\Helpers\Plugin_Constants;

if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) exit();

/**
 * Function that houses the code that cleans up the plugin on un-installation.
 *
 * @since 1.0
 */
function acfw_plugin_cleanup() {
    
    if ( get_option( Plugin_Constants::CLEAN_UP_PLUGIN_OPTIONS ) == 'yes' ) {

        // General settings section options
        delete_option( Plugin_Constants::COUPON_ENDPOINT );

        // Help settings section options
        delete_option( Plugin_Constants::CLEAN_UP_PLUGIN_OPTIONS );

    }

}

if ( function_exists( 'is_multisite' ) && is_multisite() ) {

    global $wpdb;

    $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

    foreach ( $blog_ids as $blog_id ) {

        switch_to_blog( $blog_id );
        acfw_plugin_cleanup();

    }

    restore_current_blog();

} else
    acfw_plugin_cleanup();
    