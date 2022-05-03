<?php

namespace Vendidero\StoreaBill\Lexoffice;
use Vendidero\StoreaBill\ExternalSync\ExternalSyncable;
use Vendidero\StoreaBill\ExternalSync\SyncData;
use Vendidero\StoreaBill\Invoice\Invoice;

defined( 'ABSPATH' ) || exit;

class Customer implements \Vendidero\StoreaBill\Interfaces\Customer {

	use ExternalSyncable;

	/**
	 * The object
	 *
	 * @var Invoice|\Vendidero\StoreaBill\Interfaces\Customer
	 */
	protected $object;

	protected $data = array();

	/**
	 * Customer constructor.
	 *
	 * @param Invoice|\Vendidero\StoreaBill\Interfaces\Customer $object
	 * @param array $customer_data
	 */
	public function __construct( $object, $customer_data = array() ) {
		if ( is_a( $object, '\Vendidero\StoreaBill\Invoice\Invoice' ) ) {
			$this->populate_by_invoice( $object );
		} elseif ( is_a( $object, '\Vendidero\StoreaBill\Interfaces\Customer' ) ) {
			$this->populate_by_customer( $object );
		}

		$this->object = $object;
	}

	/**
	 * @param Invoice $invoice
	 * @param array $customer_data
	 *
	 * @return void
	 */
	public function populate_by_invoice( $invoice, $customer_data = array() ) {
		$this->data = wp_parse_args( $customer_data, array(
			'is_business'         => false,
			'id'                  => '',
			'is_vat_exempt'       => $invoice->is_reverse_charge(),
			'billing_address'     => $invoice->get_address(),
			'shipping_address'    => $invoice->get_shipping_address(),
			'vat_id'              => $invoice->get_vat_id(),
			'email'               => $invoice->get_email(),
			'phone'               => $invoice->get_phone(),
			'first_name'          => $invoice->get_first_name(),
			'last_name'           => $invoice->get_last_name()
		) );

		$this->update_address_data( $customer_data );
	}

	/**
	 * @param \Vendidero\StoreaBill\Interfaces\Customer $customer
	 * @param array $customer_data
	 *
	 * @return void
	 */
	public function populate_by_customer( $customer, $customer_data = array() ) {
		$this->data = wp_parse_args( $customer_data, array(
			'is_business'         => $customer->is_business(),
			'id'                  => '',
			'is_vat_exempt'       => $customer->is_vat_exempt(),
			'billing_address'     => $customer->get_billing(),
			'shipping_address'    => $customer->get_shipping(),
			'vat_id'              => $customer->get_vat_id(),
			'email'               => $customer->get_email(),
			'phone'               => $customer->get_phone(),
			'first_name'          => $customer->get_first_name(),
			'last_name'           => $customer->get_last_name()
		) );

		$this->update_address_data( $customer_data );
	}

	protected function update_address_data( $customer_data = array() ) {
		$this->data['billing_address'] = wp_parse_args( $this->data['billing_address'], array(
			'title'       => isset( $customer_data['billing_address']['title'] ) ? $customer_data['billing_address']['title'] : '',
			'first_name'  => isset( $customer_data['billing_address']['first_name'] ) ? $customer_data['billing_address']['first_name'] : '',
			'last_name'   => isset( $customer_data['billing_address']['last_name'] ) ? $customer_data['billing_address']['last_name'] : '',
			'address_1'   => isset( $customer_data['billing_address']['address_1'] ) ? $customer_data['billing_address']['address_1'] : '',
			'address_2'   => isset( $customer_data['billing_address']['address_2'] ) ? $customer_data['billing_address']['address_2'] : '',
			'city'        => isset( $customer_data['billing_address']['city'] ) ? $customer_data['billing_address']['city'] : '',
			'postcode'    => isset( $customer_data['billing_address']['postcode'] ) ? $customer_data['billing_address']['postcode'] : '',
			'country'     => isset( $customer_data['billing_address']['country'] ) ? $customer_data['billing_address']['country'] : '',
		) );

		$this->data['shipping_address'] = wp_parse_args( $this->data['shipping_address'], array(
			'title'       => isset( $customer_data['shipping_address']['title'] ) ? $customer_data['shipping_address']['title'] : '',
			'first_name'  => isset( $customer_data['shipping_address']['first_name'] ) ? $customer_data['shipping_address']['first_name'] : '',
			'last_name'   => isset( $customer_data['shipping_address']['last_name'] ) ? $customer_data['shipping_address']['last_name'] : '',
			'address_1'   => isset( $customer_data['shipping_address']['address_1'] ) ? $customer_data['shipping_address']['address_1'] : '',
			'address_2'   => isset( $customer_data['shipping_address']['address_2'] ) ? $customer_data['shipping_address']['address_2'] : '',
			'city'        => isset( $customer_data['shipping_address']['city'] ) ? $customer_data['shipping_address']['city'] : '',
			'postcode'    => isset( $customer_data['shipping_address']['postcode'] ) ? $customer_data['shipping_address']['postcode'] : '',
			'country'     => isset( $customer_data['shipping_address']['country'] ) ? $customer_data['shipping_address']['country'] : '',
		) );
	}

	public function get_reference_type() {
		return 'lexoffice';
	}

	/**
	 * @return \Vendidero\StoreaBill\Interfaces\Customer|Invoice
	 */
	public function get_object() {
		return $this->object;
	}

	public function get_id() {
		return $this->get( 'id' );
	}

	public function get_formatted_identifier() {
		return sprintf( _x( 'Customer %s', 'storeabill-core', 'storeabill' ), $this->get_id() );
	}

	protected function get( $key ) {
		return isset( $this->data[ $key ] ) ? $this->data[ $key ] : false;
	}

	public function get_first_name() {
		return $this->get( 'first_name' );
	}

	protected function get_address_data( $prop, $type = 'billing' ) {
		$address_data = $this->get( "{$type}_address" );

		return isset( $address_data[ $prop ] ) ? $address_data[ $prop ] : '';
	}

	public function get_last_name() {
		return $this->get( 'last_name' );
	}

	public function get_title() {
		return $this->get_address_data( "title" );
	}

	public function get_formatted_title() {
		$title_formatted = '';

		if ( function_exists( 'wc_gzd_get_customer_title' ) && ( $title = $this->get_title() ) ) {
			if ( is_numeric( $title ) ) {
				$title_formatted = wc_gzd_get_customer_title( $title );
			} else {
				$title_formatted = $title;
			}
		}

		return $title_formatted;
	}

	public function get_email() {
		return $this->get( 'email' );
	}

	public function get_phone() {
		return $this->get( 'phone' );
	}

	public function get_company() {
		$company = $this->get_address_data( 'company' );

		return $company;
	}

	public function get_company_name() {
		$company = $this->get_company();

		if ( $this->is_business() && empty( $company ) ) {
			/* translators: 1: first name 2: last name */
			$company = sprintf( _x( '%1$s %2$s', 'storeabill-company-customer-name', 'storeabill' ), $this->get_first_name(), $this->get_last_name() );
		}

		return $company;
	}

	public function is_vat_exempt() {
		return $this->get( 'is_vat_exempt' );
	}

	public function get_vat_id() {
		return $this->get( 'vat_id' );
	}

	public function is_business() {
		$company        = $this->get_company();
		$force_business = $this->get( 'is_business' );
		$vat_id         = $this->get_vat_id();
		$is_business    = ! empty( $company ) || ! empty( $vat_id ) || $force_business;

		return $is_business;
	}

	public function get_billing_address() {
		return $this->get_address_data( 'address_1' );
	}

	public function get_billing_address_2() {
		return $this->get_address_data( 'address_2' );
	}

	public function get_billing_postcode() {
		return $this->get_address_data( 'postcode' );
	}

	public function get_billing_country() {
		return $this->get_address_data( 'country' );
	}

	public function get_billing_city() {
		return $this->get_address_data( 'city' );
	}

	public function get_billing() {
		return $this->data['billing_address'];
	}

	public function get_shipping_address() {
		return $this->get_address_data( 'address_1', 'shipping' );
	}

	public function get_shipping_address_2() {
		return $this->get_address_data( 'address_2', 'shipping' );
	}

	public function get_shipping_postcode() {
		return $this->get_address_data( 'postcode', 'shipping' );
	}

	public function get_shipping_country() {
		return $this->get_address_data( 'country', 'shipping' );
	}

	public function get_shipping_city() {
		return $this->get_address_data( 'city', 'shipping' );
	}

	public function get_shipping() {
		return $this->data['shipping_address'];
	}

	public function has_shipping_address() {
		/**
		 * Some fields (e.g. shipping email) may have a value although there is no address available
		 */
		$fields_to_check            = array( 'address_1', 'country', 'postcode' );
		$has_valid_shipping_address = true;

		foreach( $fields_to_check as $field_name ) {
			$value = array_key_exists( $field_name, $this->data['shipping_address'] ) ? trim( $this->data['shipping_address'][ $field_name ] ) : '';

			if ( strlen( $value ) <= 0 ) {
				$has_valid_shipping_address = false;
			}
		}

		return $has_valid_shipping_address;
	}

	public function get_meta( $key, $single = true, $context = 'view' ) {
		return false;
	}

	public function save() {
		return $this->object->save();
	}

	/**
	 * Check if a method is callable by checking the underlying order object.
	 * Necessary because is_callable checks will always return true for this object
	 * due to overloading __call.
	 *
	 * @param $method
	 *
	 * @return bool
	 */
	public function is_callable( $method ) {
		if ( method_exists( $this, $method ) ) {
			return true;
		}

		return false;
	}

	public function get_type() {
		return 'customer';
	}

	public function get_external_sync_handler_data( $handler_name ) {
		if ( is_a( $this->object, '\Vendidero\StoreaBill\Invoice\Invoice' ) ) {
			$data = $this->object->get_external_sync_handler_data( $handler_name );

			$data->set( 'id', $data->get( 'customer_id' ) );
			$data->set( 'version', $data->get( 'customer_version' ) );
		} else {
			$data = $this->object->get_external_sync_handler_data( $handler_name );
		}

		return $data;
	}

	public function get_external_sync_handlers() {
		return $this->object->get_external_sync_handlers();
	}

	public function set_external_sync_handlers( $sync_handlers ) {
		$this->object->set_external_sync_handlers( $sync_handlers );
	}

	public function has_been_externally_synced( $handler_name ) {
		if ( is_a( $this->object, '\Vendidero\StoreaBill\Invoice\Invoice' ) ) {
			$external_sync_handlers = $this->get_external_sync_handlers();

			if ( array_key_exists( $handler_name, $external_sync_handlers ) && ! empty( $external_sync_handlers[ $handler_name ]['customer_id'] ) ) {
				return true;
			}

			return false;
		} else {
			return $this->object->has_been_externally_synced( $handler_name );
		}
	}

	/**
	 * @param $handler_name
	 * @param array|SyncData $args
	 */
	public function update_external_sync_handler( $handler_name, $args = array() ) {
		if ( is_a( $this->object, '\Vendidero\StoreaBill\Invoice\Invoice' ) ) {
			if ( isset( $args['id'] ) ) {
				$args['customer_id'] = $args['id'];
				unset( $args['id'] );
			}

			if ( isset( $args['version'] ) ) {
				$args['customer_version'] = $args['version'];
				unset( $args['version'] );
			}
		}

		$this->object->update_external_sync_handler( $handler_name, $args );
	}
}