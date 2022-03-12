<?php
/**
 * Plugin Name:     Date and Time Picker by Input WP
 * Plugin URI:      https://cmoreira.net/date-and-time-picker-for-wordpress/
 * Description:     The Date and Time Picker plugin by Input WP allows you to convert a text input into an advanced Date and Time Picker using a CSS Selector. It works perfectly with Contact Form 7 and Divi.
 * Author:          Input WP
 * Author URI:      https://www.inputwp.com/
 * Text Domain:     date-time-picker-field
 * Domain Path:     /lang
 * Version:         2.1
 * License: GPL v2 or later
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @package date-time-picker-field
 *
 * Version Log
 * v.1.8.2 - 29 October 2020
 * - New date format added
 *
 * v.1.8.1 - 27 April 2020
 * - utc offset bug fixed
 * - minimum date considered for 'disable past dates'
 * - days offset option added
 *
 * * v.1.8 - 10 Feb 2020
 * - Offset and min_date improvements

 * * v.1.7.8 - 4 Feb 2020
 * - New date formats added
 * - UTC issue fixed
 * - Month scroll disabled
 *
 * * v.1.7.9.4 - 03.09.2019
 * - Display inline option added
 *
 * * v.1.7.9.3 - 31.07.2019
 * - undefined index error fix
 *
 * * v.1.7.9.2 - 31.07.2019
 * - dirname() error fix (min.req PHP7)
 *
 * * v.1.7.9.1 - 03.07.2019
 * - time scroll fix
 * - load custom version of jquery.datetimepicker plugin
 *
 * * v.1.7.9 - 27.06.2019
 * - add minimum date option
 * - set field type to text
 * - fix mousewheel issue ( https://github.com/xdan/datetimepicker/pull/685 )
 *
 *  * v.1.7.8.2 - 06.06.2019
 * - default values fix
 *
 *  * v.1.7.8.1 - 29.05.2019
 * - Refractor code
 * - Language Improvements
 *
 *  * v.1.7.7 - 23.05.2019
 * - Option to set maximum date
 * - Option to detect language automatically
 *
 *  * v.1.7.6 - 24.04.2019
 * - option to disable specific dates
 * - improved time handling - it will now consider the site timezone
 *
 *  * v.1.7.5 - 17.04.2019
 * - improved default time value
 *
 *  * v.1.7.4.1 - 08.04.2019
 * - fixed get_plugin_data() error
 *
 *  * v.1.7.4 - 06.04.2019
 * - language files
 * - add version to loaded scrips and styles
 * - remove unused files
 * - fixed bug on AM/PM time format
 *
 *  * v.1.7.3 - 03.04.2019
 * - Fixed data format issue in some languages
 * - Removed moment library in favour of custom formatter
 *
 * v.1.7.2 - 03.04.2019
 * - Fix IE11 issue
 *
 * v.1.7.1 - 02.04.2019
 * - Added advanced options to better control time options for individual days
 *
 *  * v.1.6 - 16.01.2019
 * - Start of the week now follows general settings option
 * - Added new Day.Month.Year format
 *
 * v.1.5 - 04.10.2018
 * - Option to add minimum and maximum time entries
 * - Option to disable past dates
 *
 * v.1.4 - 05.09.2018
 * - Option to add script also in admin
 *
 * v.1.3 - 24.07.2018
 * - PHP Error "missing file" solved
 *
 * v.1.2.2 - 16.07.2018
 * - Included option to prevent keyboard edit
 *
 * v.1.2.1 - 16.07.2018
 * - Added option to allow original placeholder to be kept
 *
 * v.1.2 - 26.06.2018
 * - Solved bug on date and hour format
 *
 * V.1.1 - 26.06.2018
 * - Improved options handling
 * - Added direct link to settings page from plugins screen
 *
 * v.1.0
 * - Initial Release
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

defined( 'DATEPKR_FILE' ) or define( 'DATEPKR_FILE', plugin_basename( __FILE__ ) );


//Freemius integrtaion
if ( ! function_exists( 'datepkr' ) ) {
    // Create a helper function for easy SDK access.
    function datepkr() {
        global $datepkr;
        if ( ! isset( $datepkr ) ) {
            // Include Freemius SDK.
            require_once dirname(__FILE__) . '/vendor/freemius/start.php';
            $datepkr = fs_dynamic_init( array(
                'id'                  => '7830',
                'slug'                => 'date-time-picker',
                'type'                => 'plugin',
                'public_key'          => 'pk_5c8d4db8cfdb8033ce2dcbad06114',
                'is_premium'          => false,
                'has_addons'          => false,
                'has_paid_plans'      => false,
                'menu'                => array(
                    'support'        => false,
                ),
            ) );
        }
        return $datepkr;
    }
    // Init Freemius.
    datepkr();
    // Signal that SDK was initiated.
    do_action( 'datepkr_loaded' );
}

function dtpicker_fs_custom_connect_message_on_update(
    $message,
    $user_first_name,
    $product_title,
    $user_login,
    $site_link,
    $freemius_link
) {
    return sprintf(
        __( 'Hey %1$s', 'date-time-picker-field' ) . ',<br>' .
				__( 'Never miss an important update! Opt-in to our security and feature updates notifications, and non-sensitive diagnostic tracking with %2$s.', 'date-time-picker-field' ),
        $user_first_name,
				$freemius_link
    );
}
datepkr()->add_filter('connect_message_on_update', 'dtpicker_fs_custom_connect_message_on_update', 10, 6);

// composer autoload.
if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
	require_once dirname( __FILE__ ) . '/vendor/autoload.php';
}

// check if class init exists and load static method.
if ( class_exists( 'CMoreira\\Plugins\\DateTimePicker\\Init' ) ) {
	CMoreira\Plugins\DateTimePicker\Init::init();
}

//Remove optin/optout link on plugins page
add_action('admin_head', 'hide_option_option');
function hide_option_option() {
  echo '<style>
    .plugins .row-actions .opt-in-or-opt-out.date-time-picker {
		display:none;
    }
  </style>';
}
