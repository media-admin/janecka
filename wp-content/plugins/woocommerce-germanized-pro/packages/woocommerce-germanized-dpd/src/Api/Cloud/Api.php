<?php

namespace Vendidero\Germanized\DPD\Api\Cloud;

use Vendidero\Germanized\DPD\Label\Retoure;
use Vendidero\Germanized\DPD\Label\Simple;
use Vendidero\Germanized\DPD\Package;

defined( 'ABSPATH' ) || exit;

class Api extends \Vendidero\Germanized\DPD\Api\Api {

	/** @var string[] */
	private $endpoint = [
		self::DEV_ENVIRONMENT  => 'https://cloud-stage.dpd.com/api/v1/',
		self::PROD_ENVIRONMENT => 'https://cloud.dpd.com/api/v1/'
	];

	/**
	 * @return string
	 */
	protected function getEndpoint() {
		return $this->endpoint[ static::$environment ];
	}

	protected function get( $endpoint, $args = array() ) {
		$url = add_query_arg( $args, $this->getEndpoint() . $endpoint );

		$response = wp_remote_get( $url, array(
			'headers' => array(
				'Version'                     => '100',
				'Language'                    => Package::get_api_language(),
				'PartnerCredentials-Name'     => Package::get_cloud_api_partner_name(),
				'PartnerCredentials-Token'    => Package::get_cloud_api_partner_token(),
				'UserCredentials-cloudUserID' => Package::get_cloud_api_username(),
				'UserCredentials-Token'       => Package::get_cloud_api_password(),
				'Content-Type'                => 'application/json'
			),
			'timeout' => 100,
		) );

		return $response;
	}

	protected function refresh_pickup_details() {
		$response      = $this->get( 'ZipCodeRules' );
		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = json_decode( wp_remote_retrieve_body( $response ) );

		if ( 200 === $response_code && isset( $response_body->ZipCodeRules ) ) {
			$pickup_details = array(
				'no_pickup_days' => explode( ',', trim( $response_body->ZipCodeRules->NoPickupDays ) ),
				'express_cutoff' => $response_body->ZipCodeRules->ExpressCutOff,
				'classic_cutoff' => $response_body->ZipCodeRules->ClassicCutOff
			);

			set_transient( 'dpd_pickup_details', $pickup_details, DAY_IN_SECONDS * 30 );

			return true;
		} else {
			delete_transient( 'dpd_pickup_details' );

			return false;
		}
	}

	public function get_pickup_details( $force_refresh = false ) {
		if ( $force_refresh ) {
			$this->refresh_pickup_details();
		}

		if ( ! get_transient( 'dpd_pickup_details' ) ) {
			$this->refresh_pickup_details();
		}

		$pickup_details = get_transient( 'dpd_pickup_details' );

		if ( ! $pickup_details || ! isset( $pickup_details['no_pickup_days'] ) ) {
			$pickup_details = array();
		}

		return wp_parse_args( $pickup_details, array(
			'no_pickup_days' => array(),
			'express_cutoff' => '12:00',
			'classic_cutoff' => '08:00',
		) );
	}

	protected function is_working_day( $datetime ) {
		$pickup_details = $this->get_pickup_details();
		$is_working_day = ( in_array( $datetime->format( 'd.m.Y' ), $pickup_details['no_pickup_days'] ) ) ? false : true;

		if ( $is_working_day ) {
			$is_working_day = $datetime->format( 'N' ) > 5 ? false : true;
		}

		return $is_working_day;
	}

	/**
	 * @param $product_id
	 *
	 * @return \DateTime|false
	 */
	public function get_next_available_pickup_date( $product_id = 'Classic' ) {
		$product_id = strtolower( $product_id );
		$is_express = strstr( $product_id, 'express' ) ? true : false;

		try {
			$tz_obj         = new \DateTimeZone(  'Europe/Berlin' );
			$starting_date  = new \DateTime( "now", $tz_obj );
			$pickup_details = $this->get_pickup_details();

			// In case current date greater cutoff time -> add one working day
			if ( $starting_date->format( 'Hi' ) > str_replace( ':', '', ( $is_express ? $pickup_details['express_cutoff'] : $pickup_details['classic_cutoff'] ) ) ) {
				$starting_date->add( new \DateInterval('P1D' ) );
			}

			while ( ! $this->is_working_day( $starting_date ) ) {
				$starting_date->add( new \DateInterval('P1D' ) );
			}

			return $starting_date;
		} catch( \Exception $e ) {
			return false;
		}
	}

	public function get_domestic_products( $is_return = false ) {
		if ( $is_return ) {
			return array(
				'Classic_Return'      => _x( 'DPD Classic Return', 'dpd', 'woocommerce-germanized-pro' ),
				'Shop_Return'         => _x( 'DPD Shop Return', 'dpd', 'woocommerce-germanized-pro' ),
			);
		} else {
			return array(
				'Classic'             => _x( 'DPD Classic', 'dpd', 'woocommerce-germanized-pro' ),
				'Classic_Predict'     => _x( 'DPD Classic Predict', 'dpd', 'woocommerce-germanized-pro' ),
				'Express_830'         => _x( 'DPD Express 8:30', 'dpd', 'woocommerce-germanized-pro' ),
				'Express_10'          => _x( 'DPD Express 10:00', 'dpd', 'woocommerce-germanized-pro' ),
				'Express_12'          => _x( 'DPD Express 12:00', 'dpd', 'woocommerce-germanized-pro' ),
				'Express_18'          => _x( 'DPD Express 18:00', 'dpd', 'woocommerce-germanized-pro' ),
				'Express_12_Saturday' => _x( 'DPD Express 12:00 (Saturday)', 'dpd', 'woocommerce-germanized-pro' ),
			);
		}
	}

	public function get_international_products( $is_return = false ) {
		if ( $is_return ) {
			return array(
				'Classic_Return'      => _x( 'DPD Classic Return', 'dpd', 'woocommerce-germanized-pro' ),
				'Shop_Return'         => _x( 'DPD Shop Return', 'dpd', 'woocommerce-germanized-pro' ),
			);
		} else {
			return array(
				'Express_International' => _x( 'DPD Express', 'dpd', 'woocommerce-germanized-pro' ),
			);
		}
	}

	public function get_eu_products( $is_return = false ) {
		if ( $is_return ) {
			return array(
				'Classic_Return'      => _x( 'DPD Classic Return', 'dpd', 'woocommerce-germanized-pro' ),
				'Shop_Return'         => _x( 'DPD Shop Return', 'dpd', 'woocommerce-germanized-pro' ),
			);
		} else {
			return array(
				'Classic'               => _x( 'DPD Classic', 'dpd', 'woocommerce-germanized-pro' ),
				'Express_International' => _x( 'DPD Express', 'dpd', 'woocommerce-germanized-pro' ),
			);
		}
	}

	public function get_page_formats() {
		return array(
			'PDF_A4' => _x( 'A4', 'dpd', 'woocommerce-germanized-pro' ),
			'PDF_A6' => _x( 'A6', 'dpd', 'woocommerce-germanized-pro' ),
		);
	}

	public function get_international_customs_terms() {
		return array();
	}

	public function get_international_customs_paper() {
		return array();
	}

	protected function post( $endpoint, $request ) {
		$response = wp_remote_post( $this->getEndpoint() . $endpoint, array(
			'headers' => array(
				'Version'                     => '100',
				'Language'                    => Package::get_api_language(),
				'PartnerCredentials-Name'     => Package::get_cloud_api_partner_name(),
				'PartnerCredentials-Token'    => Package::get_cloud_api_partner_token(),
				'UserCredentials-cloudUserID' => Package::get_cloud_api_username(),
				'UserCredentials-Token'       => Package::get_cloud_api_password(),
				'Content-Type'                => 'application/json'
			),
			'timeout' => 100,
			'body'    => json_encode( $request ),
		) );

		return $response;
	}

	/**
	 * @param Simple|Retoure $label
	 *
	 * @return \WP_Error|true
	 */
	public function get_label( $label ) {
		$shipment       = $label->get_shipment();
		$shipment_ref   = $this->get_reference( apply_filters( 'woocommerce_gzd_dpd_label_api_reference', _x( 'Shipment {shipment_id}', 'dpd', 'woocommerce-germanized-pro' ), $label ), $shipment, 35 );
		$shipment_ref_2 = $this->get_reference( apply_filters( 'woocommerce_gzd_dpd_label_api_reference_2', _x( 'Order {order_id}', 'dpd', 'woocommerce-germanized-pro' ), $label ), $shipment, 35 );
		$is_return      = 'return' === $label->get_type();

		$provider       = $shipment->get_shipping_provider_instance();
		$error          = new \WP_Error();
		$label_supports_email_transmit = ( $label->supports_third_party_email_notification() || apply_filters( 'woocommerce_gzd_dpd_label_force_email_notification', wc_string_to_bool( $provider->get_setting( 'label_force_email_transfer', 'no' ) ), $label ) );
		$house_number   = $is_return ? $shipment->get_sender_address_street_number() : $shipment->get_address_street_number();
		$country        = $is_return ? $shipment->get_sender_country() : $shipment->get_country();
		$state          = in_array( $country, array( 'US', 'CA' ) ) ? ( $is_return ? $shipment->get_sender_state() : $shipment->get_state() ) : '';
		$item_names     = array();

		foreach( $shipment->get_items() as $item ) {
			$item_names[] = $item->get_name();
		}

		$address = array(
			'Gender'     => 'none',
			'Company'    => mb_substr( $is_return ? $shipment->get_sender_company() : $shipment->get_company(), 0, 50 ),
			'Salutation' => '',
			'FirstName'  => mb_substr( $is_return ? $shipment->get_sender_first_name() : $shipment->get_first_name(), 0, 50 ),
			'LastName'   => mb_substr( $is_return ? $shipment->get_sender_last_name() : $shipment->get_last_name(), 0, 50 ),
			'Street'     => mb_substr( $is_return ? $shipment->get_sender_address_street() : $shipment->get_address_street(), 0, 50 ),
			'HouseNo'    => mb_substr( empty( $house_number ) ? '0' : $house_number, 0, 8 ),
			'Country'    => mb_substr( $country, 0, 2 ),
			'ZipCode'    => $is_return ? $shipment->get_sender_postcode() : $shipment->get_postcode(),
			'City'       => mb_substr( $is_return ? $shipment->get_sender_city() : $shipment->get_city(), 0, 50 ),
			'State'      => mb_substr( $state, 0, 2 ),
			'Phone'      => mb_substr( $is_return ? $shipment->get_sender_phone() : $shipment->get_phone(), 0, 20 ),
			'Mail'       => mb_substr( $is_return ? $shipment->get_sender_email() : $shipment->get_email(), 0, 50 ),
		);

		/**
		 * Force email, phone transmission for predict, returns and international shipments
		 */
		if ( ! $label_supports_email_transmit && ! $is_return && ! strstr( strtolower( $label->get_product_id() ), 'predict' ) && ! $shipment->is_shipping_inner_eu() && ! $shipment->is_shipping_international() ) {
			unset( $address['Phone'] );
			unset( $address['Mail'] );
		}

		$request = array(
			'OrderAction'   => 'startOrder',
			'OrderSettings' => array(
				'LabelSize'          => $label->get_page_format(),
				'LabelStartPosition' => apply_filters( 'woocommerce_gzd_dpd_label_start_position', 'UpperLeft', $label ),
				'ShipDate'           => $label->get_pickup_date() ? $label->get_pickup_date() : date_i18n( 'Y-m-d' ),
			),
			'OrderDataList' => array(
				array(
					'ShipAddress' => $address,
					'ParcelData'  => array(
						'ShipService'    => $label->get_product_id(),
						'Weight'         => $label->get_weight(),
						'Content'        => mb_substr( apply_filters( "woocommerce_gzd_dpd_label_api_content_desc", implode( ', ', $item_names ), $label ), 0, 35 ),
						'YourInternalID' => mb_substr( $shipment->get_shipment_number(), 0, 35 ),
						'Reference1'     => $shipment_ref,
						'Reference2'     => $shipment_ref_2,
					),
				),
			)
		);

		$clean_request = $this->clean_request( $request );
		$response      = $this->post( 'setOrder', apply_filters( 'woocommerce_gzd_dpd_label_api_request', $clean_request, $label ) );

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = json_decode( wp_remote_retrieve_body( $response ) );

		if ( 200 !== $response_code ) {
			$error->add( 'api_error', sprintf( _x( 'DPD API error: %1$s', 'dpd', 'woocommerce-germanized-pro' ), $response_code ) );
		} else {
			$error = $this->get_request_errors( $response_body );
		}

		if ( ! wc_gzd_shipment_wp_error_has_errors( $error ) ) {
			$label_response = $response_body->LabelResponse;
			$pdf            = base64_decode( $label_response->LabelPDF );

			$label->set_number( $label_response->LabelDataList[0]->ParcelNo );

			if ( ! empty( $label_response->LabelPDF ) ) {
				if ( $path = $label->upload_label_file( $pdf ) ) {
					$label->set_path( $path );
				} else {
					$error->add( 'upload', _x( 'Error while uploading DPD label.', 'dpd', 'woocommerce-germanized-pro' ) );
				}
			}

			$label->save();
		}

		return wc_gzd_shipment_wp_error_has_errors( $error ) ? $error : true;
	}

	protected function get_request_errors( $response ) {
		$error = new \WP_Error();

		if ( isset( $response->Ack ) && true === $response->Ack ) {
			return $error;
		} else {
			if ( isset( $response->ErrorDataList ) && is_array( $response->ErrorDataList ) ) {
				foreach( $response->ErrorDataList as $api_error ) {
					$error->add( $api_error->ErrorCode, isset( $api_error->ErrorMsgLong ) ? $api_error->ErrorMsgLong : $api_error->ErrorMsgShort );
				}
			}
		}

		return $error;
	}
}