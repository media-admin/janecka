<?php

namespace Vendidero\Germanized\DPD\Api;

use Vendidero\Germanized\DPD\Label\Retoure;
use Vendidero\Germanized\DPD\Label\Simple;
use Vendidero\Germanized\Shipments\Shipment;

defined( 'ABSPATH' ) || exit;

abstract class Api {
	const DEV_ENVIRONMENT = 0;
	const PROD_ENVIRONMENT = 1;

	/** @var Api */
	private static $instance;

	/** @var int */
	protected static $environment = self::DEV_ENVIRONMENT;

	/**
	 * @return Api
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new static;
		}

		return self::$instance;
	}

	/**
	 * Set API environment to development version
	 */
	public static function dev() {
		self::$environment = self::DEV_ENVIRONMENT;
	}

	/**
	 * Set API environment to production version
	 */
	public static function prod() {
		self::$environment = self::PROD_ENVIRONMENT;
	}

	public static function is_sandbox() {
		return self::$environment === self::DEV_ENVIRONMENT;
	}

	abstract public function get_domestic_products( $is_return = false );

	abstract public function get_international_products( $is_return = false );

	abstract public function get_eu_products( $is_return = false );

	public function get_page_formats() {
		return array(
			'A4' => _x( 'A4', 'dpd', 'woocommerce-germanized-pro' ),
			'A6' => _x( 'A6', 'dpd', 'woocommerce-germanized-pro' ),
			'A7' => _x( 'A7', 'dpd', 'woocommerce-germanized-pro' ),
		);
	}

	public function get_international_customs_terms() {
		return array(
			'01' => _x( 'DAP, cleared', 'dpd', 'woocommerce-germanized-pro' ),
			'02' => _x( 'DDP (incl. duties, excl. taxes)', 'dpd', 'woocommerce-germanized-pro' ),
			'03' => _x( 'DDP (incl. duties and taxes)', 'dpd', 'woocommerce-germanized-pro' ),
			'05' => _x( 'Ex works (EXW)', 'dpd', 'woocommerce-germanized-pro' ),
			'06' => _x( 'DAP', 'dpd', 'woocommerce-germanized-pro' ),
			'07' => _x( 'DAP (duty and taxes pre-paid by receiver)', 'dpd', 'woocommerce-germanized-pro' ),
		);
	}

	public function get_international_customs_paper() {
		return array(
			'A' => _x( 'Commercial invoice', 'dpd', 'woocommerce-germanized-pro' ),
			'B' => _x( 'Proforma invoice', 'dpd', 'woocommerce-germanized-pro' ),
			'G' => _x( 'Delivery note', 'dpd', 'woocommerce-germanized-pro' ),
			'H' => _x( 'Third party billing', 'dpd', 'woocommerce-germanized-pro' ),
			'C' => _x( 'Export declaration', 'dpd', 'woocommerce-germanized-pro' ),
			'D' => _x( 'EUR1', 'dpd', 'woocommerce-germanized-pro' ),
			'E' => _x( 'EUR2', 'dpd', 'woocommerce-germanized-pro' ),
			'F' => _x( 'ATR', 'dpd', 'woocommerce-germanized-pro' ),
			'I' => _x( 'T1 document', 'dpd', 'woocommerce-germanized-pro' ),
		);
	}

	/**
	 * @param string $ref_text
	 * @param Shipment $shipment
	 * @param int $max_length
	 *
	 * @return string
	 */
	protected function get_reference( $ref_text, $shipment, $max_length = 50 ) {
		return mb_substr( str_replace( array( '{shipment_id}', '{order_id}' ), array( $shipment->get_shipment_number(), $shipment->get_order_number() ), $ref_text ), 0, $max_length );
	}

	protected function is_valid_address( $address, $address_type = 'recipient', $additional_mandatory = array() ) {
		$errors    = new \WP_Error();
		$fields = array(
			'name1'   => _x( 'First Name', 'dpd', 'woocommerce-germanized-pro' ),
			'name2'   => _x( 'Last Name', 'dpd', 'woocommerce-germanized-pro' ),
			'street'  => _x( 'Street', 'dpd', 'woocommerce-germanized-pro' ),
			'houseNo' => _x( 'House number', 'dpd', 'woocommerce-germanized-pro' ),
			'country' => _x( 'Country', 'dpd', 'woocommerce-germanized-pro' ),
			'zipCode' => _x( 'Postcode', 'dpd', 'woocommerce-germanized-pro' ),
			'city'    => _x( 'City', 'dpd', 'woocommerce-germanized-pro' ),
			'email'   => _x( 'Email', 'dpd', 'woocommerce-germanized-pro' ),
			'phone'   => _x( 'Phone', 'dpd', 'woocommerce-germanized-pro' ),
		);

		$mandatory = array(
			'name1',
			'street',
			'houseNo',
			'country',
			'zipCode',
			'city',
		);

		$address_labels = array(
			'recipient' => _x( 'Recipient Address', 'dpd', 'woocommerce-germanized-pro' ),
			'sender'    => _x( 'Sender Address', 'dpd', 'woocommerce-germanized-pro' ),
		);

		$address_label = array_key_exists( $address_type, $address_labels ) ? $address_labels[ $address_type ] : '';
		$mandatory     = array_unique( array_merge( $mandatory, $additional_mandatory ) );

		foreach( $mandatory as $mandatory_field_name ) {
			if ( ! array_key_exists( $mandatory_field_name, $address ) || '' === $address[ $mandatory_field_name ] ) {
				$errors->add( $address_type . '_' . $mandatory_field_name, sprintf( _x( '%1$s: %2$s is missing or empty.', 'dpd', 'woocommerce-germanized-pro' ), $address_label, array_key_exists( $mandatory_field_name, $fields ) ? $fields[ $mandatory_field_name ] : $mandatory_field_name ) );
			}
		}

		if ( wc_gzd_shipment_wp_error_has_errors( $errors ) ) {
			return $errors;
		} else {
			return true;
		}
	}

	/**
	 * @param Simple|Retoure $label
	 *
	 * @return \WP_Error|true
	 */
	abstract public function get_label( $label );

	protected function clean_request( $array ) {
		foreach ( $array as $k => $v ) {

			if ( is_array( $v ) ) {
				$array[ $k ] = $this->clean_request( $v );
			}

			if ( '' === $v ) {
				unset( $array[ $k ] );
			}
		}

		return $array;
	}

	/** Disabled Api constructor. Use Api::instance() as singleton */
	protected function __construct() {}
}
