<?php

/**
 * @package date-time-picker-field
 */

namespace CMoreira\Plugins\DateTimePicker;

use \Datetime;
use \DateTimeZone;

if ( ! class_exists( 'DateTimePicker' ) ) {
	class DateTimePicker {

		public function __construct() {

			// plugin load language domain.
			add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

			// enqueue scripts.
			add_action( 'init', array( $this, 'enqueue_scripts' ) );

			// Adds link to settings page.
			add_filter( 'plugin_action_links_' . dirname( plugin_basename( __FILE__ ) ), array( $this, 'add_action_links' ) );
		}

		/**
		 * Load plugin text domain
		 *
		 * @return void
		 */
		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'date-time-picker-field', '', basename( dirname( dirname( __FILE__ ) ) ) . '/lang/' );
		}

		/**
		 * Function to load necessary files
		 *
		 * @return void
		 */
		public function scripts() {

			$tzone = $this->get_timezone_name();

			$version = $this->get_version();

			global $wp_version;
			if ( version_compare( $wp_version, '5.6', '>=' ) ) {
				$jquery_slug = 'jquery-core';
			} else {
				$jquery_slug = 'jquery';
			}

			wp_enqueue_script( 'dtp-moment', plugins_url( 'assets/js/vendor/moment/moment.js', dirname( __FILE__ ) ), array( $jquery_slug ), $version, true );
			wp_enqueue_style( 'dtpicker', plugins_url( 'assets/js/vendor/datetimepicker/jquery.datetimepicker.min.css', dirname( __FILE__ ) ), array(), $version, 'all' );
			wp_enqueue_script( 'dtpicker', plugins_url( 'assets/js/vendor/datetimepicker/jquery.datetimepicker.full.min.js', dirname( __FILE__ ) ), array( $jquery_slug ), $version, true );
			wp_enqueue_script( 'dtpicker-build', plugins_url( 'assets/js/dtpicker.js', dirname( __FILE__ ) ), array( 'dtpicker', 'dtp-moment' ), $version, true );

			$opts    = get_option( 'dtpicker' );
			$optsadv = get_option( 'dtpicker_advanced' );
			// merge advanced options.
			if ( is_array( $opts ) && is_array( $optsadv ) ) {
				$opts = array_merge( $opts, $optsadv );
			}

			// day of start of week.
			$opts['dayOfWeekStart'] = get_option( 'start_of_week' );

			// sanitize disabled days.
			$opts['disabled_days']          = isset( $opts['disabled_days'] ) && is_array( $opts['disabled_days'] ) ? array_values( array_map( 'intval', $opts['disabled_days'] ) ) : '';
			$opts['disabled_calendar_days'] = isset( $opts['disabled_calendar_days'] ) && '' !== $opts['disabled_calendar_days'] ? explode( ',', $opts['disabled_calendar_days'] ) : '';
			$opts['allowed_times']          = isset( $opts['allowed_times'] ) && '' !== $opts['allowed_times'] ? array_map( array( $this, 'time_24' ), explode( ',', $opts['allowed_times'] ) ) : '';
			$opts['sunday_times']           = isset( $opts['sunday_times'] ) && '' !== $opts['sunday_times'] ? array_map( array( $this, 'time_24' ), explode( ',', $opts['sunday_times'] ) ) : '';
			$opts['monday_times']           = isset( $opts['monday_times'] ) && '' !== $opts['monday_times'] ? array_map( array( $this, 'time_24' ), explode( ',', $opts['monday_times'] ) ) : '';
			$opts['tuesday_times']          = isset( $opts['tuesday_times'] ) && '' !== $opts['tuesday_times'] ? array_map( array( $this, 'time_24' ), explode( ',', $opts['tuesday_times'] ) ) : '';
			$opts['wednesday_times']        = isset( $opts['wednesday_times'] ) && '' !== $opts['wednesday_times'] ? array_map( array( $this, 'time_24' ), explode( ',', $opts['wednesday_times'] ) ) : '';
			$opts['thursday_times']         = isset( $opts['thursday_times'] ) && '' !== $opts['thursday_times'] ? array_map( array( $this, 'time_24' ), explode( ',', $opts['thursday_times'] ) ) : '';
			$opts['friday_times']           = isset( $opts['friday_times'] ) && '' !== $opts['friday_times'] ? array_map( array( $this, 'time_24' ), explode( ',', $opts['friday_times'] ) ) : '';
			$opts['saturday_times']         = isset( $opts['saturday_times'] ) && '' !== $opts['saturday_times'] ? array_map( array( $this, 'time_24' ), explode( ',', $opts['saturday_times'] ) ) : '';

			// offset.
			$opts['offset']      = isset( $opts['offset'] ) ? intval( $opts['offset'] ) : 0;
			$opts['days_offset'] = isset( $opts['days_offset'] ) ? intval( $opts['days_offset'] ) : 0;

			// step.
			$opts['step'] = isset( $opts['step'] ) && intval( $opts['step'] ) > 0 ? intval( $opts['step'] ) : 60;

			// locale.
			if ( isset($opts['locale']) && $opts['locale'] === 'auto' ) {

				global $wp_locale;
				$opts['locale'] = 'en';

				// i18n - the datetime script needs the locale code to exist,
				// we can't create new ones, so we just overwrite the english one.
				$opts['i18n']['en'] = array(
					'months'         => array(
						$wp_locale->month['01'],
						$wp_locale->month['02'],
						$wp_locale->month['03'],
						$wp_locale->month['04'],
						$wp_locale->month['05'],
						$wp_locale->month['06'],
						$wp_locale->month['07'],
						$wp_locale->month['08'],
						$wp_locale->month['09'],
						$wp_locale->month['10'],
						$wp_locale->month['11'],
						$wp_locale->month['12'],
					),
					'dayOfWeekShort' => array(
						$wp_locale->weekday_abbrev[ $wp_locale->weekday[0] ],
						$wp_locale->weekday_abbrev[ $wp_locale->weekday[1] ],
						$wp_locale->weekday_abbrev[ $wp_locale->weekday[2] ],
						$wp_locale->weekday_abbrev[ $wp_locale->weekday[3] ],
						$wp_locale->weekday_abbrev[ $wp_locale->weekday[4] ],
						$wp_locale->weekday_abbrev[ $wp_locale->weekday[5] ],
						$wp_locale->weekday_abbrev[ $wp_locale->weekday[6] ],
					),
					'dayOfWeek'      => array(
						$wp_locale->weekday[0],
						$wp_locale->weekday[1],
						$wp_locale->weekday[2],
						$wp_locale->weekday[3],
						$wp_locale->weekday[4],
						$wp_locale->weekday[5],
						$wp_locale->weekday[6],
					),
				);
			}

			// other variables.
			$format       = '';
			$clean_format = '';
			$value        = '';

			$opts['minTime'] = isset( $opts['minTime'] ) && $opts['minTime'] !== '' ? $opts['minTime'] : '00:00';
			$opts['maxTime'] = isset( $opts['maxTime'] ) && $opts['maxTime'] !== '' ? $opts['maxTime'] : '23:59';

			// workaround AM/PM because of offset issues.
			$opts['minTime'] = $this->time_24( $opts['minTime'] );
			$opts['maxTime'] = $this->time_24( $opts['maxTime'] );

			if ( isset( $opts['datepicker'] ) && 'on' === $opts['datepicker'] ) {
				$format       .= $opts['dateformat'];
				$clean_format .= $this->format( $opts['dateformat'] );

				// max date.
				if ( isset( $opts['max_date'] ) && $opts['max_date'] !== '' ) {
					$temp_date = strtotime( $opts['max_date'] );

					if ( $temp_date ) {
						$opts['max_date'] = date( $clean_format, $temp_date );
						$opts['max_year'] = date( 'Y', $temp_date );
					}
				}

				// min date.
				if ( isset( $opts['min_date'] ) && $opts['min_date'] !== '' ) {
					$temp_date = strtotime( $opts['min_date'] );

					if ( $temp_date ) {
						$opts['min_date'] = date( $clean_format, $temp_date );
						$opts['min_year'] = date( 'Y', $temp_date );
					} else {
						$opts['min_date'] = '';
					}
				}
			}

			if ( isset( $opts['timepicker'] ) && 'on' === $opts['timepicker'] ) {
				$hformat       = $opts['hourformat'];
				$format       .= ' ' . $hformat;
				$clean_format .= ' H:i';
			}

			$opts['format']       = $format;
			$opts['clean_format'] = $clean_format;

			$next_available = $this->get_next_available_time( $opts );
			#if( ! $opts['min_date'] ) {
				#$opts['min_date'] = $next_available;
			#}

			#if ( 'off' === $opts['minDate'] ) {
				#$opts['min_date'] = '';
			#}

			if ( isset( $opts['placeholder'] ) && 'on' === $opts['placeholder'] ) {
				$opts['value'] = '';
			} else {
				if( isset( $opts['use_max_date_as_default'] ) && $opts['use_max_date_as_default'] ) {
					$temp_date = strtotime( $opts['max_date'] );
					if ( $temp_date ) {
						$opts['value'] = date( $clean_format, $temp_date );
					}
				}else{
					$opts['value'] = $next_available;
				}
			}

			$tzone              = get_option( 'timezone_string' );
			$opts['timezone']   = $tzone;
			$tzoffset           = get_option( 'gmt_offset' );
            $tzoffset           = (strpos($tzoffset, '-') !== false ? '-' : '+') . abs($tzoffset);
            $opts['utc_offset'] = $tzoffset;
			$now                = new DateTime( 'now', new DateTimeZone($tzoffset) );
			$opts['now']        = $now->format( $opts['clean_format'] );

			wp_localize_script( 'dtpicker-build', 'datepickeropts', $opts );
		}

		/**
		 * Enqueue scripts according to options
		 *
		 * @return void
		 */
		public function enqueue_scripts() {
			$opts = get_option( 'dtpicker' );
			if ( isset( $opts['load'] ) && 'full' === $opts['load'] ) {
				add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
			} elseif ( isset( $opts['load'] ) && 'admin' === $opts['load'] ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
			} elseif ( isset( $opts['load'] ) && 'fulladmin' === $opts['load'] ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
				add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
			} else {
				add_shortcode( 'datetimepicker', array( $this, 'scripts' ) );
			}
		}



		public function add_action_links( $links ) {
			$mylinks = array(
				'<a href="' . admin_url( 'options-general.php?page=dtp_settings' ) . '">' . __( 'Settings', 'dtpicker' ) . '</a>',
			);

			return array_merge( $mylinks, $links );
		}

		public function get_version() {

			$plugin_version = '1.7.8';

			if ( function_exists( 'get_file_data' ) ) {

				$plugin_data = get_file_data(
					__FILE__,
					array(
						'Version' => 'Version',
					)
				);

				if ( $plugin_data ) {
					$plugin_version = $plugin_data['Version'];
				}
			}

			return $plugin_version;
		}

		/**
		 * Format javascript date time format to PHP format. Needed for backwards compatibility.
		 *
		 * @param [string] $string
		 * @return string converted datetime format
		 */
		public function format( $string ) {
			$replace   = array(
				'hh',
				'HH',
				'mm',
				'A',
				'YYYY',
				'MMM',
				'MM',
				'DD',
			);
			$replaceby = array(
				'h',
				'H',
				'i',
				'A',
				'Y',
				'M',
				'm',
				'd',
			);

			return str_replace( $replace, $replaceby, $string );
		}

		/**
		 * Get next available time based on provided data
		 *
		 * @param array $opts - get_options('dtpicker') and get_options('dtpicker_advanced')
		 * @return string timespamp
		 */
		public function get_next_available_time( $opts ) {

			$tzoffset = get_option( 'gmt_offset' );
            $tzoffset = (strpos($tzoffset, '-') !== false ? '-' : '+') . abs($tzoffset);

			// setup variables
			$min_time      = isset( $opts['minTime'] ) ? $opts['minTime'] : '';
			$max_time      = isset( $opts['maxTime'] ) ? $opts['maxTime'] : '';
			$min_date      = isset( $opts['min_date'] ) ? $opts['min_date'] : '';
			$step          = isset( $opts['step'] ) ? $opts['step'] : '';
			$allowed       = isset( $opts['allowed_times'] ) ? $opts['allowed_times'] : '';
			$offset        = isset( $opts['offset'] ) ? intval( $opts['offset'] ) : 0;
			$disabled      = isset( $opts['disabled_calendar_days'] ) ? $opts['disabled_calendar_days'] : '';
			$days_offset   = isset( $opts['days_offset'] ) ? intval( $opts['days_offset'] ) : 0;
			$disabled_days = isset( $opts['disabled_days'] ) && is_array( $opts['disabled_days'] ) ? $opts['disabled_days'] : [];

			$value = '';
			$now   = new DateTime( 'now', new DateTimeZone($tzoffset) );
			$next  = new DateTime( 'now', new DateTimeZone($tzoffset) );

			// add offset minutes.
			$now->modify( '+' . $offset . 'minutes' );
			$next->modify( '+' . $offset . 'minutes' );

			// add offset days
			// we add one at a time, to make sure we don't consider disabled days
			$a = 0;
			while ($a < $days_offset) {

				$next->modify( '+1 day' );
				$now->modify( '+1 day' );

				// reset time for next day
				$next->setTime(0, 0, 0);
				$now->setTime(0, 0, 0);

				$wday = intval( $next->format( 'w' ) );

				if ( ! in_array( $wday, $disabled_days, true ) ) {
					$a++;
				}
			}

			if ( '' !== $min_date ) {

				// temp hack to allow strtotime to work.
				$min_date = str_replace( '/', '-', $min_date );

				$min = strtotime( $min_date );

				if ( $min > $now->getTimestamp() ) {
					$now->setTimestamp( $min );
					$next->setTimestamp( $min );
				}
			}

			// if it's included in the excluded dates.
			if( is_array( $disabled ) ) {

				// map to trim function
				$disabled = array_map('trim', $disabled );
				$proceed = false;

				while ( ! $proceed ) {
					$next_string = $next->format( $this->format( $opts['dateformat'] ) );
					if ( ! in_array( $next_string, $disabled ) ) {
						$proceed = true;
					} else {
						$next->modify( '+1 day' );
					}
				}
			}

			// use allowed dates
			if ( is_array( $opts['allowed_times'] ) && count( $opts['allowed_times'] ) > 0 ) {

				$found = false;

				while ( ! $found ) {

					// if weekday is disabled, skip
					$wday = intval( $next->format( 'w' ) );
					if ( is_array( $opts['disabled_days'] ) && in_array( $wday, $opts['disabled_days'] ) ) {
						$next->modify( '+1 day' );
						continue;
					}

					$week_day = strtolower( $next->format( 'l' ) );

					// if there's a defined number of allowed hours for this day
					if ( is_array( $opts[ $week_day . '_times' ] ) ) {

						foreach ( $opts[ $week_day . '_times' ] as $hour ) {

							$dtime = DateTime::createFromFormat( 'H:i', trim( $hour ) );

							if ( ! $dtime ) {
								return '';
							}

							$hour   = intval( $dtime->format( 'H' ) );
							$minute = intval( $dtime->format( 'i' ) );

							$next->setTime( $hour, $minute );

							if ( $next > $now ) {
								$found = true;
								$value = $next->format( $opts['clean_format'] );
								break;
							}
						}
					}
					// use default allowed times
					else {
						foreach ( $opts['allowed_times'] as $hour ) {

							$dtime  = DateTime::createFromFormat( 'H:i', trim( $hour ) );
							$hour   = intval( $dtime->format( 'H' ) );
							$minute = intval( $dtime->format( 'i' ) );

							$next->setTime( $hour, $minute );

							if ( $next > $now ) {
								$found = true;
								$value = $next->format( $opts['clean_format'] );
								break;
							}
						}
					}

					$next->modify( '+1 day' );

				}

				return $value;

			}

			// if there's no default allowed times, we calculate them with min/max and step values
			$min = isset( $opts['minTime'] ) && $opts['minTime'] !== '' ? $opts['minTime'] : '00:00';
			$max = isset( $opts['maxTime'] ) && $opts['maxTime'] !== '' ? $opts['maxTime'] : '23:59';

			$range = $this->hours_range( $min, $max, $opts['step'], $opts['hourformat'] );

			// if weekday is disabled, skip to next enabled day
			$included = false;

			while ( ! $included ) {

				$wday = intval( $next->format( 'w' ) );

				if ( is_array( $opts['disabled_days'] ) && in_array( $wday, $opts['disabled_days'] ) ) {
					$next->modify( '+1 day' );
					$next->setTime( 0, 0 );
					continue;
				}

				$included = true;
			}

			$found = false;

			while ( ! $found ) {

				foreach ( $range as $hour ) {

					$dtime  = DateTime::createFromFormat( 'H:i', trim( $hour ) );
					$hour   = intval( $dtime->format( 'H' ) );
					$minute = intval( $dtime->format( 'i' ) );

					$next->setTime( $hour, $minute );

					if ( $next > $now ) {
						$found = true;
						$value = $next->format( $opts['clean_format'] );
						break;
					}
				}

				$next->modify( '+1 day' );

			}

			return $value;

		}

		/**
		 * Calculate hours range
		 *
		 * @param string $min
		 * @param string $max
		 * @param string $step
		 * @param string $format
		 * @return array of times
		 */
		public function hours_range( $min = '00:00', $max = '23:59', $step = '60', $format = 'H:i' ) {

			$times    = array();
			$step     = intval( $step ) <= 60 ? intval( $step ) : 60;
			$date     = DateTime::createFromFormat( 'H:i', $min );
			$max_hour = DateTime::createFromFormat( 'H:i', $max );

			if ( ! $date ) {
				return $times;
			}

			while ( $date <= $max_hour ) {

				array_push( $times, $date->format( 'H:i' ) );
				// increment date - only if it doesn't jump to next hour - we do this because that's what the jquery datetime plugin does
				$minutes = intval( $date->format( 'i' ) );

				if ( ( $minutes + $step ) > 60 ) {
					$date->modify( '+ 1 hour' );
					$date->setTime( $date->format( 'H' ), 0 );
				} else {
					$date->modify( '+' . $step . ' minutes' );
				}
			}

			return $times;
		}

		/**
		 * Convert hour to 24h format
		 *
		 * @param string $hour
		 * @return string 24h formatted hour
		 */
		public function time_24( $hour = '' ) {
			return date( 'H:i', strtotime( $hour ) );
		}

		/**
		 * Get timezone name
		 *
		 * @return string timezone name
		 */
		public function get_timezone_name() {

			$tzone = get_option( 'timezone_string' );
			if ( ! $tzone ) {
				$offset = get_option( 'gmt_offset' );
				$tzone  = timezone_name_from_abbr( '', $offset * 3600, false );
			}

			return $tzone;
		}
	}
}
