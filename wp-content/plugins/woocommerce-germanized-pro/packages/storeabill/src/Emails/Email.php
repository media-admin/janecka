<?php

namespace Vendidero\StoreaBill\Emails;

/**
 * Include dependencies.
 */
if ( ! class_exists( 'WC_Email', false ) ) {
	require_once WC_ABSPATH . 'includes/emails/class-wc-email.php';
}

use Vendidero\StoreaBill\Package;

defined( 'ABSPATH' ) || exit;

class Email extends \WC_Email {

	public function __construct() {
		$this->template_base  = Package::get_path() . '/templates/';

		parent::__construct();
	}

	public function is_admin() {
		return $this->is_customer_email() ? false : true;
	}

	protected function get_default_placeholders() {
		return array();
	}

	/**
	 * Switch Woo and Germanized to site locale
	 */
	public function setup_locale() {
		parent::setup_locale();

		if ( $this->is_customer_email() && apply_filters( 'storeabill_email_setup_locale', true ) ) {
			sab_switch_to_site_locale();
		}
	}

	/**
	 * Restore Woo and Germanized locale
	 */
	public function restore_locale() {
		parent::restore_locale();

		if ( $this->is_customer_email() && apply_filters( 'storeabill_email_restore_locale', true ) ) {
			sab_restore_locale();
		}
	}

	/**
	 * Adds better compatibility to multi-language-plugins such as WPML.
	 * Should be called during trigger method after setting up the email object
	 * so that e.g. order data is available.
	 */
	public function setup_email_locale( $lang = false ) {
		if ( $this->is_customer_email() && apply_filters( 'storeabill_email_setup_user_locale', true ) ) {
			sab_switch_to_email_locale( $this, $lang );
		}
	}

	public function restore_email_locale() {
		if ( $this->is_customer_email() && apply_filters( 'storeabill_email_setup_user_locale', true ) ) {
			sab_restore_email_locale( $this );
		}
	}
}