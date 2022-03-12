<?php
namespace CMoreira\Plugins\DateTimePicker\Integration;

use \WP_Query as WP_Query;

/**
 * Intgegration helper class with forms
 */
class IntegrationHelper {


  public function __construct() {

    $this->options = $this->get_dtp_options();

    $this->plugins = array(
       'manual' => __( 'Manual', 'date-time-picker-field-pro' ),
    );
  }


  public function get_date_time_pickers($picker_ID) {

    $html = '';
    if (is_array($this->options)) {
      $html .= '<option value="0" ' . selected($picker_ID, 0, false) . '>' . (array_key_exists('selector', $this->options) ? $this->options['selector'] : __('New Picker', 'date-time-picker-field-pro' )) . '</option>';
    }

    return $html;
  }


  public function get_pickers_n_selectors() {

    $meta = get_option( 'dtpicker' );
    if (is_array($meta) && array_key_exists('selector', $meta)) {
      $selector = $meta['selector'];
    }

    if (empty($selector)) {
      $selector = '.input' . rand(100,999);

      $manual = new ManualIntegration();
      $set = $manual->set_class($selector);
    }

    $output[0] = array(
      'selector' => $selector
    );

    return $output;
  }


  public function get_dtp_options() {

    $opts    = get_option( 'dtpicker' );
    $optsadv = get_option( 'dtpicker_advanced' );
    if ( is_array( $opts ) && is_array( $optsadv ) ) {
      $opts = array_merge( $opts, $optsadv );
    }

    return $opts;
  }
}
