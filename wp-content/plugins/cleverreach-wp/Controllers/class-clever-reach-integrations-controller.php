<?php

namespace CleverReach\WordPress\Controllers;

use CleverReach\WordPress\Components\Utility\Helper;

/**
 * Class Clever_Reach_Integrations_Controller
 *
 * @package CleverReach\WordPress\Controllers
 */
class Clever_Reach_Integrations_Controller extends Clever_Reach_Base_Controller {
	/**
	 * Returns all integrations in JSON format.
	 */
	public function get_all_integrations() {
		$is_plugin_installed = Helper::is_plugin_enabled( 'contact-form-7/wp-contact-form-7.php' );
		$link                = 'https://wordpress.org/plugins/contact-form-7/';
		if ( $is_plugin_installed ) {
			$link = admin_url() . 'admin.php?page=wpcf7';
		}

		$integrations = array(
			array(
				'name'        => __( 'Contact Form 7', 'contact-form-7' ),
				'description' => __( 'Subscribe people that submit CF7 form to newsletter', 'cleverreach-wp' ),
				'link'        => $link,
				'installed'   => $is_plugin_installed,
				'manual'      => __( 'https://support.cleverreach.de/hc/en-us/articles/360010498020', 'cleverreach-wp' ),
			)
		);

		$this->die_json( $integrations );
	}
}
