<?php

/**
 * Plugin Name: Date Time Picker for Contact Form 7
 * Plugin URI: https://wpapplab.com/
 * Description: This plugin can be used to display date and time picker into Contact Form 7 text input field by using css class. This plugin specifically designed to work with Contact Form 7. Other form plugins are not tested.
 * Version: 1.1.0
 * Author: Ruhul Amin
 * Author URI: https://mircode.com
 * Text Domain: walcf7-datetimepicker
 *
 * @package WAL Demo
 */


define('WALCF7_DTP_NAME', __('Date Time Picker for Contact form 7', 'walcf7-datetimepicker'));
define('WALCF7_DTP_FILE', __FILE__);
define('WALCF7_DTP_BASE', plugin_basename(WALCF7_DTP_FILE));
define('WALCF7_DTP_DIR', plugin_dir_path(WALCF7_DTP_FILE));
define('WALCF7_DTP_URI', plugins_url('/', WALCF7_DTP_FILE));


// Load backend scripts
function walcf7_dtp_script_loader()
{

	wp_enqueue_script(
		'walcf7-datepicker-js',
		WALCF7_DTP_URI . 'assets/js/jquery.datetimepicker.full.min.js',
		array("jquery"),
		false,
		true
	);
	wp_enqueue_style(
		'walcf7-datepicker-css',
		WALCF7_DTP_URI . 'assets/css/jquery.datetimepicker.min.css',
		false,
		'1.0.0',
		'all'
	);

	wp_enqueue_script(
		'walcf7-datepicker',
		WALCF7_DTP_URI . 'assets/js/datetimepicker.js',
		array(
			'jquery', // make sure this only loads if jQuery has loaded
		),
		'1.0.0',
		true // Outputs this at footer
	); // Custom Child Theme jQuery
}

add_action('wp_enqueue_scripts', 'walcf7_dtp_script_loader');

add_filter('plugin_row_meta', 'walcf7_plugin_row_meta', 10, 2);

function walcf7_plugin_row_meta($links, $file)
{
	if (plugin_basename(__FILE__) == $file) {
		$row_meta = array(
			'walcf7_pro'    => '<a href="' . esc_url('https://wpapplab.com/plugins/date-time-picker-for-contact-form-7-pro/') . '" target="_blank" aria-label="' . esc_attr__('Date Time Picker Pro', 'walcf7-datetimepicker') . '" style="color:red;"><b>' . esc_html__('Get Pro Version', 'walcf7-datetimepicker') . '</b></a>'
		);

		return array_merge($links, $row_meta);
	}
	return (array) $links;
}
