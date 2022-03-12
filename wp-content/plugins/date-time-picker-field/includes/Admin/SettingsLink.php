<?php
/**
 * WordPress settings link for Date Time Picker in plugins page
 *
 * @package date-time-picker-field
 *
 * @author Carlos Moreira
 */

namespace CMoreira\Plugins\DateTimePicker\Admin;

/**
 * Adds link to settings in plugin entry on plugins page.
 */
class SettingsLink {

	public $basename = null;

	/**
	 * Register hooks.
	 */
	public function __construct() {

		$this->basename = plugin_basename( dirname( dirname( dirname( __FILE__ ) ) ) );
		$this->register();
	}

	/**
	 * Register class - adds link
	 *
	 * @return void
	 */
	public function register() {
		$this_plugin = DATEPKR_FILE;
		add_filter( 'plugin_action_links', array( $this, 'menu_page_link' ), 10, 2 );
		add_action("in_plugin_update_message-{$this_plugin}", array( $this, 'show_upgrade_notification'), 10, 2);
	}

	/**
	 * Adds settings link
	 *
	 * @param [type] $links
	 * @return void
	 */
	public function menu_page_link( $links, $file ) {

			static $this_plugin;
			if ( ! $this_plugin ) {
				$this_plugin = DATEPKR_FILE;
			}
			if ( $file == $this_plugin ) {
				$shift_link = array();
				$shift_link[] = sprintf(
					'<a href="/wp-admin/admin.php?page=dtpicker">%s</a>',
					__( 'Settings', 'date-time-picker-field' )
				);
				foreach( $shift_link as $val ) {
					array_unshift( $links, $val );
				}
			}
			return $links;
	}


	public function show_upgrade_notification($currentPluginMetadata, $newPluginMetadata) {

		if (isset($newPluginMetadata->upgrade_notice) && strlen(trim($newPluginMetadata->upgrade_notice)) > 0){
        echo '<p style="padding: 10px; margin-top: 10px"><strong style="color: #d54e21;">Take a minute to upgrade, here is why:</strong> ';
				echo '<ul>';
				echo '<li>' . 'Enhancement: Option to select the date picker type.';
				echo '</li><li>' . 'Enhancement: Backend UI Fixes.';
				echo '</li><li>' . 'Enhancement: Separated the Form integration from the date picker definition.';
				echo '</li><li>' . 'Enhancement: Migration engine that connects with PRO.';
				echo '</li><li>' . 'Enhancement: Tested up to 5.7.2 (WordPress)';
				echo '</li><li>' . 'Fix: Default time in datepicker is not coming correct as per the Hour Format selected in datepicker plugin.';
				echo '</li><li>' . 'Fix: JS bugs on compatibility with Contact Form 7.';
				echo '</li></ul>';
        echo esc_html($newPluginMetadata->upgrade_notice), '</p>';
   }
	}
}
