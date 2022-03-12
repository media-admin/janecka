<?php
namespace CMoreira\Plugins\DateTimePicker\Integration;

use \WP_Query as WP_Query;

/**
 * Manual Intgegration helper class with forms
 */
class ManualIntegration {

  public function set_class($selector) {

    $meta = get_option( 'dtpicker' );
    if (is_array($meta)) {
      $meta['selector'] = $selector;
    }

    update_option( 'dtpicker', $meta );
  }
}
