<?php

/**
 * WordPress settings for Date Time Picker plugin
 *
 * @package date-time-picker-field
 *
 * @author Carlos Moreira
 */

namespace CMoreira\Plugins\DateTimePicker\Admin;

use CMoreira\Plugins\DateTimePicker\Integration\IntegrationHelper as IntegrationHelper;

if ( ! class_exists( 'SettingsPage' ) ) {
	class SettingsPage {

		private $settings_api;

		private $integration_api;

		public static $menu_svg = 'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyB3aWR0aD0iMTI4cHgiIGhlaWdodD0iMTI4cHgiIHZpZXdCb3g9IjAgMCAxMjggMTI4IiB2ZXJzaW9uPSIxLjEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiPgogICAgPHRpdGxlPmlucHV0d3AtaWNvbi0xMjg8L3RpdGxlPgogICAgPGcgaWQ9ImlucHV0d3AtaWNvbi0xMjgiIHN0cm9rZT0ibm9uZSIgc3Ryb2tlLXdpZHRoPSIxIiBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPgogICAgICAgIDxjaXJjbGUgaWQ9IkNvbnRhaW5lciIgZmlsbD0iI0ZGRkZGRiIgb3BhY2l0eT0iMCIgY3g9IjY0IiBjeT0iNjQiIHI9IjY0Ij48L2NpcmNsZT4KICAgICAgICA8cGF0aCBkPSJNMjEuMzMzMzMzMywzNC4xMzMzMzMzIEwyMS4zMzMzMzMzLDgxLjA2NjY2NjcgQzIxLjMzMzMzMzMsOTUuMDYzNzcxNCAzMi41NjY3NTk3LDEwNi40MzcxNjkgNDYuNTA5OTkwNSwxMDYuNjYzMjM3IEw0Ni45MzMzMzMzLDEwNi42NjY2NjcgTDgxLjA2NjY2NjcsMTA2LjY2NjY2NyBDOTUuMDYzNzcxNCwxMDYuNjY2NjY3IDEwNi40MzcxNjksOTUuNDMzMjQwMyAxMDYuNjYzMjM3LDgxLjQ5MDAwOTUgTDEwNi42NjY2NjcsODEuMDY2NjY2NyBMMTA2LjY2NjY2NywzNC4xMzMzMzMzIEwxMjAuNjE3NjQsMzQuMTMzMzMzMyBDMTI1LjMzMTEyLDQzLjA0OTc0ODUgMTI4LDUzLjIxMzc5MzkgMTI4LDY0LjAwMTE4OTYgQzEyOCw5OS4zNDc0MTM1IDk5LjM0NjIyNCwxMjguMDAxMTkgNjQsMTI4LjAwMTE5IEMyOC42NTM3NzYsMTI4LjAwMTE5IDAsOTkuMzQ3NDEzNSAwLDY0LjAwMTE4OTYgQzAsNTMuMjEzNzkzOSAyLjY2ODg3OTg4LDQzLjA0OTc0ODUgNy4zODIzNTk3NSwzNC4xMzMzMzMzIEwyMS4zMzMzMzMzLDM0LjEzMzMzMzMgWiBNNDIuNjY2NjY2Nyw3NC42NjY2NjY3IEM0Ni4yMDEyODkxLDc0LjY2NjY2NjcgNDkuMDY2NjY2Nyw3Ny41MzIwNDQzIDQ5LjA2NjY2NjcsODEuMDY2NjY2NyBDNDkuMDY2NjY2Nyw4NC42MDEyODkxIDQ2LjIwMTI4OTEsODcuNDY2NjY2NyA0Mi42NjY2NjY3LDg3LjQ2NjY2NjcgQzM5LjEzMjA0NDMsODcuNDY2NjY2NyAzNi4yNjY2NjY3LDg0LjYwMTI4OTEgMzYuMjY2NjY2Nyw4MS4wNjY2NjY3IEMzNi4yNjY2NjY3LDc3LjUzMjA0NDMgMzkuMTMyMDQ0Myw3NC42NjY2NjY3IDQyLjY2NjY2NjcsNzQuNjY2NjY2NyBaIE02NCw3NC42NjY2NjY3IEM2Ny41MzQ2MjI0LDc0LjY2NjY2NjcgNzAuNCw3Ny41MzIwNDQzIDcwLjQsODEuMDY2NjY2NyBDNzAuNCw4NC42MDEyODkxIDY3LjUzNDYyMjQsODcuNDY2NjY2NyA2NCw4Ny40NjY2NjY3IEM2MC40NjUzNzc2LDg3LjQ2NjY2NjcgNTcuNiw4NC42MDEyODkxIDU3LjYsODEuMDY2NjY2NyBDNTcuNiw3Ny41MzIwNDQzIDYwLjQ2NTM3NzYsNzQuNjY2NjY2NyA2NCw3NC42NjY2NjY3IFogTTg1LjMzMzMzMzMsNzQuNjY2NjY2NyBDODguODY3OTU1Nyw3NC42NjY2NjY3IDkxLjczMzMzMzMsNzcuNTMyMDQ0MyA5MS43MzMzMzMzLDgxLjA2NjY2NjcgQzkxLjczMzMzMzMsODQuNjAxMjg5MSA4OC44Njc5NTU3LDg3LjQ2NjY2NjcgODUuMzMzMzMzMyw4Ny40NjY2NjY3IEM4MS43OTg3MTA5LDg3LjQ2NjY2NjcgNzguOTMzMzMzMyw4NC42MDEyODkxIDc4LjkzMzMzMzMsODEuMDY2NjY2NyBDNzguOTMzMzMzMyw3Ny41MzIwNDQzIDgxLjc5ODcxMDksNzQuNjY2NjY2NyA4NS4zMzMzMzMzLDc0LjY2NjY2NjcgWiBNODUuMzMzMzMzMyw0Ni45MzMzMzMzIEM5Mi40MDI1NzgxLDQ2LjkzMzMzMzMgOTguMTMzMzMzMyw1Mi42NjQwODg1IDk4LjEzMzMzMzMsNTkuNzMzMzMzMyBDOTguMTMzMzMzMyw2Ni44MDI1NzgxIDkyLjQwMjU3ODEsNzIuNTMzMzMzMyA4NS4zMzMzMzMzLDcyLjUzMzMzMzMgQzc4LjI2NDA4ODUsNzIuNTMzMzMzMyA3Mi41MzMzMzMzLDY2LjgwMjU3ODEgNzIuNTMzMzMzMyw1OS43MzMzMzMzIEM3Mi41MzMzMzMzLDUyLjY2NDA4ODUgNzguMjY0MDg4NSw0Ni45MzMzMzMzIDg1LjMzMzMzMzMsNDYuOTMzMzMzMyBaIE00Mi42NjY2NjY3LDUzLjMzMzMzMzMgQzQ2LjIwMTI4OTEsNTMuMzMzMzMzMyA0OS4wNjY2NjY3LDU2LjE5ODcxMDkgNDkuMDY2NjY2Nyw1OS43MzMzMzMzIEM0OS4wNjY2NjY3LDYzLjI2Nzk1NTcgNDYuMjAxMjg5MSw2Ni4xMzMzMzMzIDQyLjY2NjY2NjcsNjYuMTMzMzMzMyBDMzkuMTMyMDQ0Myw2Ni4xMzMzMzMzIDM2LjI2NjY2NjcsNjMuMjY3OTU1NyAzNi4yNjY2NjY3LDU5LjczMzMzMzMgQzM2LjI2NjY2NjcsNTYuMTk4NzEwOSAzOS4xMzIwNDQzLDUzLjMzMzMzMzMgNDIuNjY2NjY2Nyw1My4zMzMzMzMzIFogTTY0LDUzLjMzMzMzMzMgQzY3LjUzNDYyMjQsNTMuMzMzMzMzMyA3MC40LDU2LjE5ODcxMDkgNzAuNCw1OS43MzMzMzMzIEM3MC40LDYzLjI2Nzk1NTcgNjcuNTM0NjIyNCw2Ni4xMzMzMzMzIDY0LDY2LjEzMzMzMzMgQzYwLjQ2NTM3NzYsNjYuMTMzMzMzMyA1Ny42LDYzLjI2Nzk1NTcgNTcuNiw1OS43MzMzMzMzIEM1Ny42LDU2LjE5ODcxMDkgNjAuNDY1Mzc3Niw1My4zMzMzMzMzIDY0LDUzLjMzMzMzMzMgWiBNODUuMzMzMzMzMyw1My4zMzMzMzMzIEM4MS43OTg3MTA5LDUzLjMzMzMzMzMgNzguOTMzMzMzMyw1Ni4xOTg3MTA5IDc4LjkzMzMzMzMsNTkuNzMzMzMzMyBDNzguOTMzMzMzMyw2My4yNjc5NTU3IDgxLjc5ODcxMDksNjYuMTMzMzMzMyA4NS4zMzMzMzMzLDY2LjEzMzMzMzMgQzg4Ljg2Nzk1NTcsNjYuMTMzMzMzMyA5MS43MzMzMzMzLDYzLjI2Nzk1NTcgOTEuNzMzMzMzMyw1OS43MzMzMzMzIEM5MS43MzMzMzMzLDU2LjE5ODcxMDkgODguODY3OTU1Nyw1My4zMzMzMzMzIDg1LjMzMzMzMzMsNTMuMzMzMzMzMyBaIE05My44NjY2NjY3LDEyLjggQzEwMC45MzU5MTEsMTIuOCAxMDYuNjY2NjY3LDE4LjUzMDc1NTIgMTA2LjY2NjY2NywyNS42IEwxMDYuNjY2NjY3LDM0LjEzMzMzMzMgTDIxLjMzMzMzMzMsMzQuMTMzMzMzMyBMMjEuMzMzMzMzMywyNS42IEMyMS4zMzMzMzMzLDE4LjUzMDc1NTIgMjcuMDY0MDg4NSwxMi44IDM0LjEzMzMzMzMsMTIuOCBMOTMuODY2NjY2NywxMi44IFoiIGlkPSJDb21iaW5lZC1TaGFwZSIgZmlsbD0iIzlFQTNBOSI+PC9wYXRoPgogICAgPC9nPgo8L3N2Zz4=';

		public function __construct() {
			$this->settings_api = new SettingsAPI();
			$this->integration_api = new IntegrationAPI();
			$this->integration = new IntegrationHelper();

			add_action( 'admin_init', array( $this, 'admin_init' ) );
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		}

		public function admin_init() {

			// set the settings.
			$this->settings_api->set_sections( $this->get_settings_sections() );
			$this->settings_api->set_navigation( $this->get_settings_navigation() );
			$this->settings_api->set_fields( $this->get_settings_fields() );

			// initialize settings.
			$this->settings_api->admin_init();
		}

		public function admin_menu() {

			$title = __( 'Input WP', 'date-time-picker-field' );
			$settings_title = __( 'Settings', 'date-time-picker-field' );
			$integration_title = __( 'Integration', 'date-time-picker-field' );

			add_menu_page( $title, $title, 'manage_options', 'dtpicker', array( $this, 'plugin_page' ), 'data:image/svg+xml;base64,' . self::$menu_svg );
			add_submenu_page( 'dtpicker', $settings_title, $settings_title, 'manage_options', 'dtpicker', array( $this, 'plugin_page' ));
			add_submenu_page( 'dtpicker', $integration_title, $integration_title, 'manage_options', 'dtp_integration', array( $this, 'integration_page' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		}

		/**
		 * Enqueue scripts and styles
		 */
		public function admin_enqueue_scripts() {

			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_script( 'jquery' );

			wp_register_style( 'dtpkr-slider-style', plugins_url( '../../assets/css/', __FILE__  ) . 'slider.css', false, '1.0.0' );
			wp_register_style( 'dtpkr-admin-style', plugins_url( '../../assets/css/', __FILE__  ) . 'custom-admin-styles.css', false, '1.0.0' );
			wp_enqueue_style( 'dtpkr-slider-style' );
			wp_enqueue_style( 'dtpkr-admin-style' );

			if( is_admin() ) {

					$pickers = $this->integration->get_date_time_pickers(false);
					$pickers_n_selectors = $this->integration->get_pickers_n_selectors();

					//wp_enqueue_script( 'dtpicker-lite-integratrion', plugins_url( '../../assets/js/', __FILE__  ) . 'integration.js', array( 'wp-color-picker' ), false, true );
					wp_localize_script( 'dtpicker-lite-integratrion', 'intregation_obj_lite',
						array(
							'ajaxurl'           => admin_url( 'admin-ajax.php' ),
							'pickers'           => $pickers,
							'pickers_n_selectors' => $pickers_n_selectors,
						)
					);
			}
		}


		public function get_settings_sections() {
			$sections = array(
				array(
					'id'    => 'dtpicker',
					'title' => __( 'Basic Settings', 'date-time-picker-field' ),
				),

				array(
					'id'    => 'dtpicker_advanced',
					'title' => __( 'Advanced Settings', 'date-time-picker-field' ),
				),
			);
			return $sections;
		}

		public function get_settings_navigation() {
			$sections = array(
				array(
					'id'    => 'dateTimePicker',
					'title' => __( 'Date and Time picker', 'date-time-picker-field' ),
				),
				array(
					'id'    => 'timePicker',
					'title' => __( 'Time picker', 'date-time-picker-field' ),
				),
				array(
					'id'    => 'datePicker',
					'title' => __( 'Date picker', 'date-time-picker-field' ),
				),
				array(
					'id'    => 'dateRange',
					'title' => '<span class="pro-tab">' . __( 'Date range', 'date-time-picker-field' ) . '</span><sup class="red"><small>PRO</small></sup>',
				),
			);
			return $sections;
		}


		/**
		 * Returns all the settings fields
		 *
		 * @return array settings fields
		 */
		public function get_settings_fields() {

			global $wp_locale;

			$tzone = get_option( 'timezone_string' );

			// existing languages in datetime jquery script.
			$available = $this->available_lang_codes();
			$langs     = array_keys( $available );

			$languages         = array();
			$languages['auto'] = __( 'Default - Detect page language', 'date-time-picker-field' );

			require_once ABSPATH . 'wp-admin/includes/translation-install.php';
			$translations = wp_get_available_translations();
			foreach ( $langs as $locale ) {
				if ( isset( $translations[ $locale ] ) ) {
					$translation                        = $translations[ $locale ];
					$languages[ $available[ $locale ] ] = $translation['native_name'];
				} else {
					if ( $locale === 'en_US' ) {
						// we don't translate this string, since we are displaying in native name.
						$languages['en'] = 'English (US)';
					}
				}
			}

			/* translators: %s is a day of the week */
			$allowed_string = __( 'Allowed times', 'date-time-picker-field' );

			$settings_fields = array(
				'dtpicker' => array(

					array(
						'name'              => 'selector',
						'label'             => __( 'CSS Selector', 'date-time-picker-field' ),
						'desc'              => __( 'Selector of the input field you want to target and transform into a picker. You can enter multiple selectors separated by commas.', 'date-time-picker-field' ),
						'placeholder'       => __( 'eg. \'.birthday\'', 'date-time-picker-field' ),
						'type'              => 'hidden',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
						'tab'				=> 'all',
					),
					array(
						'name'    => 'datepicker',
						'label'   => __( 'Display Calendar', 'date-time-picker-field' ),
						'desc'    => __( 'Display date picker calendar.', 'date-time-picker-field' ),
						'type'    => 'hidden',
						'value'   => '1',
						'default' => 'on',
						'tab'	  => 'all',
					),

					array(
						'name'    => 'timepicker',
						'label'   => __( 'Display Time', 'date-time-picker-field' ),
						'desc'    => __( 'Display time picker.', 'date-time-picker-field' ),
						'type'    => 'hidden',
						'value'   => '1',
						'default' => 'on',
						'tab'	  => 'all',
					),

					array(
						'name'    => 'minDate',
						'label'   => __( 'Disable past dates', 'date-time-picker-field' ),
						'desc'    => sprintf(
							// translators: the %s will be a timezone name
							__( 'If enabled, past dates (and times) can\'t be selected. Consider the plugin will use the timezone you have in your general settings to perform this calculation. Your current timezone is %s.', 'date-time-picker-field' ),
							wp_timezone_string()
						),
						'type'    => 'togglebutton',
						'value'   => 'on',
						'default' => 'off',
						'tab'	  => 'datePicker',
					),

					array(
						'name'    => 'disabled_calendar_days',
						'label'   => __( 'Disable specific dates', 'date-time-picker-field' ),
						'type'    => 'text',
						'desc'    => __( 'Add the dates you want to disable divided by commas, in the format you have selected. Useful to disable holidays for example.', 'date-time-picker-field' ),
						'default' => '',
						'data'	  => 'dtpicker_advanced',
						'tab'	  => 'datePicker',
					),

					array(
						'name'    => 'disabled_days',
						'label'   => __( 'Disable week days', 'date-time-picker-field' ),
						'desc'    => __( 'Select days you want to disable.', 'date-time-picker-field' ),
						'type'    => 'multicheck',
						'default' => array(),
						'options' => array(
							'0' => $wp_locale->get_weekday( 0 ),
							'1' => $wp_locale->get_weekday( 1 ),
							'2' => $wp_locale->get_weekday( 2 ),
							'3' => $wp_locale->get_weekday( 3 ),
							'4' => $wp_locale->get_weekday( 4 ),
							'5' => $wp_locale->get_weekday( 5 ),
							'6' => $wp_locale->get_weekday( 6 ),
						),
						'tab'			=> 'datePicker',
						'data' 		=> 'dtpicker_advanced',
					),

					array(
						'name'              => 'min_date',
						'label'             => __( 'Minimum date', 'date-time-picker-field' ),
						'desc'              => __( 'Use the European day-month-year format or an english string that is accepted by the <a target="_blank" href="https://php.net/manual/en/function.strtotime.php">strtotime PHP function</a>. (Ex: "+5 days") Leave empty to set no limit.', 'date-time-picker-field' ),
						'type'              => 'text',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
						'tab'				=> 'datePicker',
					),

					array(
						'name'              => 'max_date',
						'label'             => __( 'Maximum date', 'date-time-picker-field' ),
						'desc'              => __( 'Use the European day-month-year format or an english string that is accepted by the <a target="_blank" href="https://php.net/manual/en/function.strtotime.php">strtotime PHP function</a>. (Ex: "+5 days") Leave empty to set no limit.', 'date-time-picker-field' ),
						'type'              => 'text',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
						'tab'				=> 'datePicker',
					),

					array(
						'name'				=> 'use_max_date_as_default',
						'label'    			=> __( 'Use Maximum Date as Default', 'date-time-picker-field' ),
						'desc'				=> __( 'By default the plugin will consider today or the min date as the default value. You can enable this option to use the Maximum Date as the default value.', 'date-time-picker-field' ),
						'type' 				=> 'checkbox',
						'default'			=> false,
						'dependency'		=> array( 'max_date', '!=', '' ),
						'tab'				=> 'datePicker',
					),

					array(
						'name'              => 'days_offset',
						'label'             => __( 'Days offset ', 'date-time-picker-field' ),
						'desc'              => __( 'Set the next available slot to advance at least X available days. Write the number of days here.', 'date-time-picker-field' ),
						'type'              => 'text',
						'default'           => '0',
						'sanitize_callback' => 'sanitize_text_field',
						'tab'				=> 'datePicker',
					),

					array(
						'name'    => 'dateformat',
						'label'   => __( 'Date format', 'date-time-picker-field' ),
						'desc'    => '',
						'type'    => 'select',
						'options' => array(
							'YYYY-MM-DD'  => __( 'Year-Month-Day', 'date-time-picker-field' ) . ' ' . current_time( 'Y-m-d' ),
							'DD-MM-YYYY'  => __( 'Day-Month-Year', 'date-time-picker-field' ) . ' ' . current_time( 'd-m-Y' ),
							'MM-DD-YYYY'  => __( 'Month-Day-Year', 'date-time-picker-field' ) . ' ' . current_time( 'm-d-Y' ),
							'MMM-DD-YYYY' => __( 'MONTH-Day-Year (english only)', 'date-time-picker-field' ) . ' ' . current_time( 'M-d-Y' ),
							'DD-MMM-YYYY' => __( 'MONTH-Day-Year (english only)', 'date-time-picker-field' ) . ' ' . current_time( 'd-M-Y' ),
							'YYYY/MM/DD'  => __( 'Year/Month/Day', 'date-time-picker-field' ) . ' ' . current_time( 'Y/m/d' ),
							'DD/MM/YYYY'  => __( 'Day/Month/Year', 'date-time-picker-field' ) . ' ' . current_time( 'd/m/Y' ),
							'MM/DD/YYYY'  => __( 'Month/Day/Year', 'date-time-picker-field' ) . ' ' . current_time( 'm/d/Y' ),
							'MMM/DD/YYYY' => __( 'MONTH/Day/Year (english only)', 'date-time-picker-field' ) . ' ' . current_time( 'M/d/Y' ),
							'DD/MMM/YYYY' => __( 'Day/MONTH/Year (english only)', 'date-time-picker-field' ) . ' ' . current_time( 'd/M/Y' ),
							'DD.MM.YYYY'  => __( 'Day.Month.Year', 'date-time-picker-field' ) . ' ' . current_time( 'd.m.Y' ),
							'MM.DD.YYYY'  => __( 'Month.Day.Year', 'date-time-picker-field' ) . ' ' . current_time( 'm.d.Y' ),
							'YYYY.MM.DD'  => __( 'Year.Month.Dat', 'date-time-picker-field' ) . ' ' . current_time( 'Y.m.d' ),
							'MMM.DD.YYYY' => __( 'MONTH.Day.Year (english only)', 'date-time-picker-field' ) . ' ' . current_time( 'M.d.Y' ),
							'DD.MMM.YYYY' => __( 'Day.MONTH.Year (english only)', 'date-time-picker-field' ) . ' ' . current_time( 'd.M.Y' ),
							'YYYYMMDD'    => __( 'YearMonthDay', 'date-time-picker-field' ) . ' ' . current_time( 'Ymd' ),
						),
						'default' => 'YYYY-MM-DD',
						'tab'			=> 'datePicker',
					),

					array(
						'name'    => 'picker_type',
						'label'   => __( 'Type', 'date-time-picker-field' ),
						'desc'    => __( 'Use this optional field to provide a description of your event.', 'date-time-picker-field' ),
						'type'    => 'radiogroup',
						'options' => array(
							'datetimepicker' => 'Date and Time picker (Default)',
							'datepicker' => 'Date picker',
							'timepicker' => 'Time picker',
							'daterange' => 'Date range (PRO Feature)'
						),
						'default' => 'datetimepicker',
						'disabled'=> 'daterange',
						'tab'	  => 'general',
					),

					array(
						'name'    => 'inline',
						'label'   => __( 'Display Inline', 'date-time-picker-field' ),
						'desc'    => __( 'Display calendar and/or time picker inline.', 'date-time-picker-field' ),
						'type'    => 'togglebutton',
						'value'   => '1',
						'default' => 'off',
						'tab'	  => 'general',
					),

					array(
						'name'    => 'placeholder',
						'label'   => __( 'Placeholder', 'date-time-picker-field' ),
						'desc'    => __( 'If enabled, original placeholder will be kept. If disabled it will be replaced with current date or next available time depending on your settings.', 'date-time-picker-field' ),
						'type'    => 'togglebutton',
						'value'   => '1',
						'default' => 'off',
						'tab'	  => 'general',
					),

					array(
						'name'    => 'preventkeyboard',
						'label'   => __( 'Prevent keyboard edit', 'date-time-picker-field' ),
						'desc'    => __( 'If enabled, it wont be possible to edit the text. This will also prevent the keyboard on mobile devices to display when selecting the date.', 'date-time-picker-field' ),
						'type'    => 'togglebutton',
						'value'   => 'on',
						'default' => 'off',
						'tab'	  => 'general',
					),

					array(
						'name'    => 'locale',
						'label'   => __( 'Language', 'date-time-picker-field' ),
						'desc'    => __( 'Language to display the month and day labels.', 'date-time-picker-field' ),
						'type'    => 'select',
						'default' => 'auto',
						'options' => $languages,
						'tab'	  => 'general',
					),

					array(
						'name'    => 'theme',
						'label'   => __( 'Theme', 'date-time-picker-field' ),
						'desc'    => __( 'Calendar visual style.', 'date-time-picker-field' ),
						'type'    => 'select',
						'default' => 'default',
						'options' => array(
							'default' => __( 'Default', 'date-time-picker-field' ),
							'dark'    => __( 'Dark', 'date-time-picker-field' ),
						),
						'tab'	  => 'general',
					),


					array(
						'name'    => 'load',
						'label'   => __( 'When to Load', 'date-time-picker-field' ),
						'desc'    => __( 'Choose to search for the css selector across the website or only when the shortcode [datetimepicker] exists on a page. Use the shortcode to prevent the script from loading across all pages.', 'date-time-picker-field' ),
						'type'    => 'select',
						'options' => array(
							'full'      => __( 'Across the full website', 'date-time-picker-field' ),
							'admin'     => __( 'Admin panel only', 'date-time-picker-field' ),
							'fulladmin' => __( 'Full website including admin panel', 'date-time-picker-field' ),
							'shortcode' => __( 'Only when shortcode [datetimepicker] exists on a page.', 'date-time-picker-field' ),
						),
						'default' => 'full',
						'tab'	  => 'general',
					),

					array(
						'name'              => 'step',
						'label'             => __( 'Time Step', 'date-time-picker-field' ),
						'desc'              => __( 'Time interval in minutes for time picker options.', 'date-time-picker-field' ),
						'type'              => 'text',
						'default'           => '60',
						'sanitize_callback' => 'sanitize_text_field',
						'tab'				=> 'timePicker',
					),

					array(
						'name'              => 'minTime',
						'label'             => __( 'Minimum time', 'date-time-picker-field' ),
						'desc'              => __( 'Time options will start from this. Leave empty for none. Use the format you selected for the time. For example: 08:00 AM.', 'date-time-picker-field' ),
						'type'              => 'text',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
						'tab'				=> 'timePicker',
					),

					array(
						'name'              => 'maxTime',
						'label'             => __( 'Maximum time', 'date-time-picker-field' ),
						'desc'              => __( 'Time options will not be later than this specified time. Leave empty for none. Use the format you selected for the time. For example: 08:00 PM.', 'date-time-picker-field' ),
						'type'              => 'text',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
						'tab'				=> 'timePicker',
					),

					array(
						'name'              => 'offset',
						'label'             => __( 'Time offset', 'date-time-picker-field' ),
						'desc'              => __( 'Time interval in minutes to advance next available time. For example, set "45" if you only want time entries 45m from now to be available. Works better when option to disable past dates is also enabled.', 'date-time-picker-field' ),
						'type'              => 'text',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
						'tab'				=> 'timePicker',
					),

					array(
						'name'    => 'allowed_times',
						'label'   => __( 'Global allowed times', 'date-time-picker-field' ),
						'type'    => 'text',
						'desc'    => __( 'Write the allowed times to override the time step and serve as default if you use the options below.<br> Values still need to be within minimum and maximum times defined in the basic settings.<br> Use the time format separated by commas. <br>Example: 09:00,11:00,12:00,21:00 You need to list all the options.', 'date-time-picker-field' ),
						'default' => '',
						'tab'	  => 'timePicker',
						'data' 	  => 'dtpicker_advanced',
					),

					array(
						'name'    => 'sunday_times',
						'label'   => 'Allowed times for '. $wp_locale->get_weekday( 0 ),
						'type'    => 'text',
						'default' => '',
						'tab'			=> 'timePicker',
						'data' 		=> 'dtpicker_advanced',
					),

					array(
						'name'    => 'monday_times',
						'label'   => 'Allowed times for '. $wp_locale->get_weekday( 1 ),
						'type'    => 'text',
						'default' => '',
						'tab'	  => 'timePicker',
						'data' 	  => 'dtpicker_advanced',
					),

					array(
						'name'    => 'tuesday_times',
						'label'   => 'Allowed times for '. $wp_locale->get_weekday( 2 ),
						'type'    => 'text',
						'default' => '',
						'tab'	  => 'timePicker',
						'data' 	  => 'dtpicker_advanced',
					),

					array(
						'name'    => 'wednesday_times',
						'label'   => 'Allowed times for '. $wp_locale->get_weekday( 3 ),
						'type'    => 'text',
						'default' => '',
						'tab'	  => 'timePicker',
						'data' 	  => 'dtpicker_advanced',
					),
					array(
						'name'    => 'thursday_times',
						'label'   => 'Allowed times for '. $wp_locale->get_weekday( 4 ),
						'type'    => 'text',
						'default' => '',
						'tab'	  => 'timePicker',
						'data' 	  => 'dtpicker_advanced',
					),
					array(
						'name'    => 'friday_times',
						'label'   => 'Allowed times for '. $wp_locale->get_weekday( 5 ),
						'type'    => 'text',
						'default' => '',
						'tab'	  => 'timePicker',
						'data' 	  => 'dtpicker_advanced',
					),
					array(
						'name'    => 'saturday_times',
						'label'   => 'Allowed times for '. $wp_locale->get_weekday( 6 ),
						'type'    => 'text',
						'default' => '',
						'tab'	  => 'timePicker',
						'data' 	  => 'dtpicker_advanced',
					),

					array(
						'name'    => 'hourformat',
						'label'   => __( 'Hour Format', 'date-time-picker-field' ),
						'desc'    => '',
						'type'    => 'select',
						'options' => array(
							'HH:mm'   => 'H:M ' . current_time( 'H:i' ),
							'hh:mm A' => 'H:M AM/PM ' . current_time( 'h:i A' ),
						),
						'default' => 'hh:mm A',
						'tab'	  => 'timePicker',
					),
				),
			);

			return $settings_fields;
		}

		public function plugin_page() {
			echo $this->settings_api->top_bar();
			echo '<div class="dtpkr-wrap settings-page">';
			$this->settings_api->show_forms();
			echo '</div>';
		}

		public function integration_page() {

			$this->integration_api->show_forms();
		}

		/**
		 * Get all the pages
		 *
		 * @return array page names with key value pairs
		 */
		public function get_pages() {
			$pages         = get_pages();
			$pages_options = array();
			if ( $pages ) {
				foreach ( $pages as $page ) {
					$pages_options[ $page->ID ] = $page->post_title;
				}
			}

			return $pages_options;
		}


		/**
		 * Get array with available languages where key is the WordPress lang code and value is the jquery script lang code.
		 *
		 * @return array of language codes
		 */
		public function available_lang_codes() {

			$available = array(
				'ar'    => 'ar',
				'az'    => 'az',
				'bg_BG' => 'bg',
				'bs_BG' => 'bs',
				'ca'    => 'ca',
				'zh_CN' => 'ch',
				'cz_CZ' => 'cs',
				'da_DK' => 'da',
				'de_DE' => 'de',
				'el'    => 'el',
				'en_US' => 'en',
				'en_GB' => 'en-GB',
				'es_ES' => 'es',
				'et'    => 'et',
				'eu'    => 'eu',
				'fa_IR' => 'fa',
				'fi'    => 'fi',
				'fr_FR' => 'fr',
				'gl_ES' => 'gl',
				'he_IL' => 'he',
				'hr'    => 'hr',
				'hu_HU' => 'hu',
				'id_ID' => 'id',
				'it_IT' => 'it',
				'ja   ' => 'ja',
				'ko_KO' => 'ko',
				'kr_KR' => 'kr',
				'lt_LT' => 'lt',
				'lv'    => 'lv',
				'mk_MK' => 'mk',
				'mn'    => 'mn',
				'nl_NL' => 'nl',
				'nb_NO' => 'no',
				'pl_PL' => 'pl',
				'pt_PT' => 'pt',
				'pt_BR' => 'pt-BR',
				'ro_RO' => 'ro',
				'ru_RU' => 'ru',
				'sv_SE' => 'se',
				'sk_SK' => 'sk',
				'sl_SL' => 'sl',
				'sq'    => 'sq',
				'sr_RS' => 'sr',
				'sr_YU' => 'sr-YU',
				'sv_SE' => 'sv',
				'th'    => 'th',
				'tr_TR' => 'tr',
				'uk'    => 'uk',
				'vi'    => 'vi',
				'zh_ZH' => 'zh',
				'zh_TW' => 'zh-TW',
			);

			return $available;

		}

	}
}
