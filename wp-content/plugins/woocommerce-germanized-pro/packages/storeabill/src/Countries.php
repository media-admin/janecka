<?php

namespace Vendidero\StoreaBill;

defined( 'ABSPATH' ) || exit;

class Countries {

	/**
	 * @return array|string[]
	 */
	public static function get_base_location() {
		return wc_get_base_location();
	}

	public static function get_base_country() {
		return self::get_base_location()['country'];
	}

	public static function base_country_supports_oss_procedure() {
		$base_country     = self::get_base_country();
		$eu_vat_countries = self::get_eu_vat_countries();

		return apply_filters( 'storeabill_base_country_supports_oss_procedure', in_array( $base_country, $eu_vat_countries ) );
	}

	public static function get_base_state() {
		return self::get_base_location()['state'];
	}

	public static function get_base_address() {
		return WC()->countries->get_base_address();
	}

	public static function get_base_address_2() {
		return WC()->countries->get_base_address_2();
	}

	public static function get_base_city() {
		return WC()->countries->get_base_city();
	}

	public static function get_base_postcode() {
		return WC()->countries->get_base_postcode();
	}

	public static function get_base_company_name() {
		return get_bloginfo( 'name' );
	}

	public static function get_base_email() {
		return get_option( 'admin_email' );
	}

	public static function get_base_bank_account_data() {
		$data = array(
			'holder'    => Package::get_setting( 'bank_account_holder' ),
			'bank_name' => Package::get_setting( 'bank_account_bank_name' ),
			'iban'      => Package::get_setting( 'bank_account_iban' ),
			'bic'       => Package::get_setting( 'bank_account_bic' ),
		);

		$fallback_accounts = get_option( 'woocommerce_bacs_accounts' );

		if ( empty( $data['iban'] ) && ! empty( $fallback_accounts ) ) {
			$default_data = wp_parse_args( $fallback_accounts[0], array(
				'iban'         => '',
				'account_name' => '',
				'bank_name'    => '',
				'bic'          => '',
			) );

			$default_data['holder'] = $default_data['account_name'];

			foreach( $data as $key => $value ) {
				if ( empty( $value ) ) {
					$data[ $key ] = $default_data[ $key ];
				}
			}
		}

		return $data;
	}

	public static function has_base_bank_account() {
		$data = self::get_base_bank_account_data();

		return ! empty( $data['iban'] ) ? true : false;
	}

	public static function get_formatted_base_address( $separator = '<br/>', $include_company = true ) {
		$address_data = array(
			'company'   => $include_company ? self::get_base_company_name() : '',
			'address_1' => self::get_base_address(),
			'address_2' => self::get_base_address_2(),
			'postcode'  => self::get_base_postcode(),
			'city'      => self::get_base_city(),
			'country'   => self::get_base_country(),
			'state'     => self::get_base_state()
		);

		return self::get_formatted_address( $address_data, $separator );
	}

	public static function get_formatted_address( $address = array(), $separator = '<br/>' ) {
		$force = apply_filters( 'storeabill_document_address_force_country_display', false );

		if ( $force ) {
			add_filter( 'woocommerce_formatted_address_force_country_display', '__return_true', 1, 2000 );
		}

		$formatted_address = WC()->countries->get_formatted_address( $address, $separator );

		if ( $force ) {
			remove_filter( 'woocommerce_formatted_address_force_country_display', '__return_true', 2000 );
		}

		return $formatted_address;
	}

	public static function is_third_country( $country, $postcode = '' ) {
		$is_third_country = true;

		/**
		 * In case the base country is within EU consider all non-EU countries as third countries.
		 * In any other case consider every non-base-country as third country.
		 */
		if ( in_array( self::get_base_country(), self::get_eu_vat_countries() ) ) {
			$is_third_country = ! self::is_eu_vat_country( $country, $postcode );
		} else {
			$is_third_country = $country !== self::get_base_country();
		}

		return apply_filters( 'storeabill_is_third_country', $is_third_country, $country, $postcode );
	}

	public static function get_eu_countries() {
		$countries = WC()->countries->get_european_union_countries();

		return $countries;
	}

	public static function get_eu_vat_countries() {
		return apply_filters( 'storeabill_eu_vat_countries', WC()->countries->get_european_union_countries( 'eu_vat' ) );
	}

	public static function is_northern_ireland( $country, $postcode = '' ) {
		if ( 'GB' === $country && 'BT' === strtoupper( substr( trim( $postcode ), 0, 2 ) ) ) {
			return true;
		}

		return false;
	}

	public static function is_eu_vat_postcode_exemption( $country, $postcode = '' ) {
		$country    = sab_strtoupper( $country );
		$postcode   = sab_normalize_postcode( $postcode );
		$exemptions = self::get_eu_vat_postcode_exemptions();
		$is_exempt  = false;

		if ( ! empty( $postcode ) && in_array( $country, self::get_eu_vat_countries() ) ) {
			if ( array_key_exists( $country, $exemptions ) ) {
				$wildcards = sab_get_wildcard_postcodes( $postcode, $country );

				foreach( $exemptions[ $country ] as $exempt_postcode ) {
					if ( in_array( $exempt_postcode, $wildcards, true ) ) {
						$is_exempt = true;
						break;
					}
				}
			}
		}

		return $is_exempt;
	}

	public static function is_eu_vat_country( $country, $postcode = '' ) {
		$country           = sab_strtoupper( $country );
		$postcode          = sab_normalize_postcode( $postcode );
		$is_eu_vat_country = in_array( $country, self::get_eu_vat_countries() );

		if ( self::is_northern_ireland( $country, $postcode ) ) {
			$is_eu_vat_country = true;
		} elseif ( self::is_eu_vat_postcode_exemption( $country, $postcode ) ) {
			$is_eu_vat_country = false;
		}

		return apply_filters( 'storeabill_is_eu_vat_country', $is_eu_vat_country, $country, $postcode );
	}

	public static function get_eu_vat_postcode_exemptions() {
		return apply_filters( 'storeabill_eu_vat_postcode_exemptions', array(
			'DE' => array(
				'27498', // Helgoland
				'78266' // Büsingen am Hochrhein
			),
			'ES' => array(
				'35*', // Canary Islands
				'38*', // Canary Islands
				'51*', // Ceuta
				'52*' // Melilla
			),
			'GR' => array(
				'63086', // Mount Athos
				'63087' // Mount Athos
			),
			'FR' => array(
				'971*', // Guadeloupe
				'972*', // Martinique
				'973*', // French Guiana
				'974*', // Réunion
				'976*', // Mayotte
			),
			'IT' => array(
				'22060', // Livigno, Campione d’Italia
				'23030', // Lake Lugano
			),
			'FI' => array(
				'22*', // Aland islands
			),
		) );
	}

	public static function is_eu_country( $country ) {
		return in_array( $country, self::get_eu_countries() );
	}
}