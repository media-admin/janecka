<?php

namespace Vendidero\StoreaBill\Interfaces;

/**
 * Customer Interface
 *
 * @package  Germanized/StoreaBill/Interfaces
 * @version  1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface Customer extends Reference, ExternalSyncable {

	public function get_id();

	public function get_title();

	public function get_formatted_title();

	public function get_first_name();

	public function get_last_name();

	public function get_email();

	public function get_phone();

	public function is_business();

	public function get_company_name();

	public function is_vat_exempt();

	public function get_vat_id();

	public function get_billing_address();

	public function get_billing_address_2();

	public function get_billing_postcode();

	public function get_billing_country();

	public function get_billing_city();

	public function get_billing();

	public function get_shipping_address();

	public function get_shipping_address_2();

	public function get_shipping_postcode();

	public function get_shipping_country();

	public function get_shipping_city();

	public function get_shipping();

	public function has_shipping_address();

	public function save();
}
