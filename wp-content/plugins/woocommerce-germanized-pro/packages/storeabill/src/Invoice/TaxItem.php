<?php

namespace Vendidero\StoreaBill\Invoice;

use Vendidero\StoreaBill\Interfaces\TaxContainable;
use Vendidero\StoreaBill\TaxRate;

defined( 'ABSPATH' ) || exit;

/**
 * TaxItem class
 */
class TaxItem extends Item implements TaxContainable {

	/**
	 * @var TaxRate
	 */
	protected $tax_rate = null;

	protected $extra_data = array(
		'round_tax_at_subtotal' => false,
		'tax_type'              => '',
		'rate'                  => array(),
		'total_net'             => 0,
		'subtotal_net'          => 0,
		'total_tax'             => 0,
		'subtotal_tax'          => 0,
	);

	protected $data_store_name = 'invoice_tax_item';

	public function get_data() {
		$data            = parent::get_data();
		$data['is_oss']  = $this->is_oss();
		$data['is_moss'] = $this->is_oss();

		return $data;
	}

	public function get_item_type() {
		return 'tax';
	}

	public function get_document_group() {
		return 'accounting';
	}

	public function get_tax_type( $context = 'view' ) {
		return $this->get_prop( 'tax_type', $context );
	}

	public function set_tax_type( $tax_type ) {
		$this->set_prop( 'tax_type', $tax_type );
	}

	public function get_round_tax_at_subtotal( $context = 'view' ) {
		return $this->get_prop( 'round_tax_at_subtotal', $context );
	}

	public function set_round_tax_at_subtotal( $round_tax ) {
		$this->set_prop( 'round_tax_at_subtotal', sab_string_to_bool( $round_tax ) );
	}

	public function round_tax_at_subtotal() {
		return true === $this->get_round_tax_at_subtotal();
	}

	/**
	 * For legacy purposes: MOSS turned into OSS.
	 *
	 * @return bool
	 */
	public function is_moss() {
		return $this->is_oss();
	}

	/**
	 * Whether this tax item contains OSS tax or not.
	 *
	 * @return bool
	 */
	public function is_oss() {
		return $this->get_tax_rate() ? $this->get_tax_rate()->is_oss() : false;
	}

	/**
	 * @return TaxRate|boolean
	 */
	public function get_tax_rate() {
		if ( is_null( $this->tax_rate ) ) {
			$rate = $this->get_rate();

			if ( ! empty( $rate ) ) {
				$this->tax_rate = new TaxRate( $rate );
			}
		}

		return ( $this->tax_rate ) ? $this->tax_rate : false;
 	}

	public function set_tax_rate( $rate ) {
		$this->tax_rate = new TaxRate( $rate );

		$this->set_rate( $this->tax_rate->get_data() );
	}

	public function get_tax_rate_key() {
		if ( $rate = $this->get_tax_rate() ) {
			return $rate->get_merge_key();
		} else {
			return '';
		}
	}

	public function get_rate( $context = 'view' ) {
		return $this->get_prop( 'rate', $context );
	}

	public function set_rate( $rate ) {
		$this->set_prop( 'rate', (array) $rate );
	}

	public function get_total_tax( $context = '' ) {
		return $this->get_prop( 'total_tax', $context );
	}

	public function get_total_net( $context = '' ) {
		return $this->get_prop( 'total_net', $context );
	}

	/**
	 * Sets total net amount.
	 *
	 * @param $value float the amount to be set.
	 */
	public function set_total_net( $value ) {
		$this->set_prop( 'total_net', sab_format_decimal( $value ) );
	}

	public function get_subtotal_net( $context = '' ) {
		return $this->get_prop( 'subtotal_net', $context );
	}

	/**
	 * Sets subtotal net amount.
	 *
	 * @param $value float the amount to be set.
	 */
	public function set_subtotal_net( $value ) {
		$this->set_prop( 'subtotal_net', sab_format_decimal( $value ) );
	}

	/**
	 * Sets total tax amount.
	 *
	 * @param $value float the amount to be set.
	 */
	public function set_total_tax( $value ) {
		if ( ! $this->round_tax_at_subtotal() ) {
			$value = wc_round_tax_total( $value );
		}

		$this->set_prop( 'total_tax', sab_format_decimal( $value ) );
	}

	public function get_subtotal_tax( $context = '' ) {
		return $this->get_prop( 'subtotal_tax', $context );
	}

	/**
	 * Sets total tax amount.
	 *
	 * @param $value float the amount to be set.
	 */
	public function set_subtotal_tax( $value ) {
		if ( ! $this->round_tax_at_subtotal() ) {
			$value = wc_round_tax_total( $value );
		}

		$this->set_prop( 'subtotal_tax', sab_format_decimal( $value ) );
	}
}