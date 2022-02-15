<?php

namespace Vendidero\StoreaBill;

use WC_Tax;

/**
 * Tax calculation.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Performs tax calculations.
 *
 * @class Tax
 */
class Tax {

	/**
	 * Calculate tax for a line.
	 *
	 * @param  float   $price              Price to calc tax on.
	 * @param  array   $rates              Rates to apply.
	 * @param  boolean $price_includes_tax Whether the passed price has taxes included.
	 *
	 * @return array                       Array of rates + prices after tax.
	 */
	public static function calc_tax( $price, $rates, $price_includes_tax = false ) {
		if ( $price_includes_tax ) {
			$taxes = self::calc_inclusive_tax( $price, $rates );
		} else {
			$taxes = self::calc_exclusive_tax( $price, $rates );
		}

		return apply_filters( 'storeabill_calc_tax', $taxes, $price, $rates, $price_includes_tax );
	}

	/**
	 * Calc tax from inclusive price.
	 *
	 * @param  float $price Price to calculate tax for.
	 * @param  array $rates Array of tax rates.
	 * @return array
	 */
	public static function calc_inclusive_tax( $price, $rates ) {
		$woo_rates = self::convert_tax_rates_to_woo( $rates );

		return WC_Tax::calc_inclusive_tax( $price, $woo_rates );
	}

	/**
	 * Calc tax from exclusive price.
	 *
	 * @param  float $price Price to calculate tax for.
	 * @param  array $rates Array of tax rates.
	 * @return array
	 */
	public static function calc_exclusive_tax( $price, $rates ) {
		$woo_rates = self::convert_tax_rates_to_woo( $rates );

		return WC_Tax::calc_exclusive_tax( $price, $woo_rates );
	}

	/**
	 * @param TaxRate[] $rates
	 */
	protected static function convert_tax_rates_to_woo( $rates ) {
		$woo_rates = array();

		foreach( $rates as $rate ) {
			$woo_rate = array(
				'rate'     => $rate->get_percent(),
				'country'  => $rate->get_country(),
				'compound' => sab_bool_to_string( $rate->is_compound() ),
			);

			$woo_rates[ $rate->get_merge_key() ] = $woo_rate;
		}

		return $woo_rates;
	}

	public static function get_base_tax_rates( $class = '' ) {
		return WC_Tax::get_base_tax_rates( $class );
	}

	public static function _sort_tax_rates_callback( $rate1, $rate2 ) {
		if ( $rate1->get_country() !== $rate2->get_country() ) {
			if ( '' === $rate1->get_country() ) {
				return 1;
			}

			if ( '' === $rate2->get_country() ) {
				return -1;
			}

			return strcmp( $rate1->get_country(), $rate2->get_country() ) > 0 ? 1 : -1;
		}

		return $rate1->get_priority() < $rate2->get_priority() ? -1 : 1;
	}

	public static function get_rate_percent_value( $tax_rate ) {
		return WC_Tax::get_rate_percent_value( $tax_rate );
	}

	public static function get_tax_rate_merge_key( $args ) {
		$args = wp_parse_args( $args, array(
			'is_compound' => false,
			'is_moss'     => false,
			'is_oss'      => false,
			'percent'     => 0,
		) );

		/**
		 * MOSS turned into OSS
		 */
		$is_oss = $args['is_moss'] || $args['is_oss'];

		return $args['percent'] . "_compound_" . sab_bool_to_string( $args['is_compound'] ) . "_oss_" . sab_bool_to_string( $is_oss );
	}
}
