<?php

namespace Vendidero\StoreaBill\WooCommerce;
use Vendidero\StoreaBill\ExternalSync\ExternalSyncable;
use Vendidero\StoreaBill\ExternalSync\SyncData;
use WC_Customer;

defined( 'ABSPATH' ) || exit;

/**
 * WooProduct class
 */
class Customer implements \Vendidero\StoreaBill\Interfaces\Customer {

	use ExternalSyncable;

	/**
	 * The actual customer object
	 *
	 * @var WC_Customer
	 */
	protected $customer;

	/**
	 * @param WC_Customer|integer $customer
	 */
	public function __construct( $customer ) {
		if ( is_numeric( $customer ) ) {
			$customer = new WC_Customer( $customer );

			/**
			 * In case the customer does not exist or the customer ID does not exist (e.g. user was deleted)
			 * throw an exception.
			 */
			if ( ! is_a( $customer, 'WC_Customer' ) || $customer->get_id() <= 0 ) {
				throw new \Exception( _x( 'Invalid customer.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
			}
		}

		$this->customer = $customer;
	}

	public function get_reference_type() {
		return 'woocommerce';
	}

	/**
	 * Returns the Woo WC_Customer original object
	 *
	 * @return WC_Customer
	 */
	public function get_customer() {
		return $this->customer;
	}

	public function get_object() {
		return $this->get_customer();
	}

	public function get_id() {
		return $this->customer->get_id();
	}

	public function get_formatted_identifier() {
		return sprintf( _x( 'Customer %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $this->get_id() );
	}

	public function get_first_name() {
		return $this->customer->get_billing_first_name();
	}

	public function get_last_name() {
		return $this->customer->get_billing_last_name();
	}

	public function get_title() {
		$title = $this->customer->get_meta( "billing_title", true );

		return apply_filters( 'storeabill_woo_customer_title', $title, $this->customer, $this );
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

		return apply_filters( 'storeabill_woo_customer_formatted_title', $title_formatted, $this->customer, $this );
	}

	public function get_email() {
		$email = $this->customer->get_billing_email();

		if ( empty( $email ) ) {
			$email = $this->customer->get_email();
		}

		return $email;
	}

	public function get_phone() {
		return $this->customer->get_billing_phone();
	}

	public function get_company() {
		$company = $this->customer->get_billing_company();

		return $company;
	}

	public function get_company_name() {
		$company = $this->get_company();

		if ( $this->is_business() && empty( $company ) ) {
			/* translators: 1: first name 2: last name */
			$company = sprintf( _x( '%1$s %2$s', 'storeabill-company-customer-name', 'woocommerce-germanized-pro' ), $this->get_first_name(), $this->get_last_name() );
		}

		return apply_filters( 'storeabill_woo_customer_company_name', $company, $this->customer, $this );
	}

	public function is_vat_exempt() {
		return $this->customer->is_vat_exempt();
	}

	public function get_vat_id() {
		$vat_id_formatted = '';

		if ( $vat_id = $this->customer->get_meta( "shipping_vat_id", true ) ) {
			$vat_id_formatted = $vat_id;
		}

		if ( empty( $vat_id ) && ( $billing_vat_id = $this->customer->get_meta( "billing_vat_id", true ) ) ) {
			$vat_id_formatted = $billing_vat_id;
		}

		return apply_filters( 'storeabill_woo_customer_vat_id', $vat_id_formatted, $this->customer, $this );
	}

	public function is_business() {
		$company     = $this->get_company();
		$vat_id      = $this->get_vat_id();
		$is_business = ! empty( $company ) || ! empty( $vat_id );

		return apply_filters( 'storeabill_woo_customer_is_business', $is_business, $this->customer, $this );
	}

	public function get_billing_address() {
		return $this->customer->get_billing_address();
	}

	public function get_billing_address_2() {
		return $this->customer->get_billing_address_2();
	}

	public function get_billing_postcode() {
		return $this->customer->get_billing_postcode();
	}

	public function get_billing_country() {
		return $this->customer->get_billing_country();
	}

	public function get_billing_city() {
		return $this->customer->get_billing_city();
	}

	public function get_shipping_address() {
		return $this->customer->get_shipping_address();
	}

	public function get_shipping_address_2() {
		return $this->customer->get_shipping_address_2();
	}

	public function get_shipping_postcode() {
		return $this->customer->get_shipping_postcode();
	}

	public function get_shipping_country() {
		return $this->customer->get_shipping_country();
	}

	public function get_shipping_city() {
		return $this->customer->get_shipping_city();
	}

	public function has_shipping_address() {
		if ( is_callable( array( $this->customer, 'has_shipping_address' ) ) ) {
			return $this->customer->has_shipping_address();
		} else {
			foreach ( $this->customer->get_shipping() as $address_field ) {
				// Trim guards against a case where a subset of saved shipping address fields contain whitespace.
				if ( strlen( trim( $address_field ) ) > 0 ) {
					return true;
				}
			}

			return false;
		}
	}

	public function get_meta( $key, $single = true, $context = 'view' ) {
		$meta = $this->customer->get_meta( $key, $single, $context );

		/**
		 * Support user meta data as fallback as Woo does explicitly remove
		 * user meta data (such as nickname) from the customer meta data.
		 */
		if ( empty( $meta ) && $this->customer->get_id() > 0 ) {
			$meta = get_user_meta( $this->customer->get_id(), $key, $single );

			if ( ! $meta ) {
				$user = get_user_by( 'id', $this->customer->get_id() );

				if ( $user && isset( $user->{$key} ) ) {
					$meta = $user->{$key};
				}
			}
		}

		return $meta;
	}

	public function save() {
		return $this->customer->save();
	}

	/**
	 * Call child methods if the method does not exist.
	 *
	 * @param $method
	 * @param $args
	 *
	 * @return bool|mixed
	 */
	public function __call( $method, $args ) {
		if ( method_exists( $this->customer, $method ) ) {
			return call_user_func_array( array( $this->customer, $method ), $args );
		}

		return false;
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
		} elseif( is_callable( array( $this->get_customer(), $method ) ) ) {
			return true;
		}

		return false;
	}

	public function get_type() {
		return 'customer';
	}

	public function get_external_sync_handlers() {
		$sync_handlers = (array) $this->customer->get_meta( 'external_sync_handlers', true );

		return $sync_handlers;
	}

	public function set_external_sync_handlers( $sync_handlers ) {
		$this->customer->update_meta_data( 'external_sync_handlers', (array) $sync_handlers );
	}
}