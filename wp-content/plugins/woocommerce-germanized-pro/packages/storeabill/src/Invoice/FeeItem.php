<?php

namespace Vendidero\StoreaBill\Invoice;

use Vendidero\StoreaBill\Interfaces\Discountable;
use Vendidero\StoreaBill\Interfaces\SplitTaxable;
use Vendidero\StoreaBill\Interfaces\Summable;
use Vendidero\StoreaBill\Interfaces\Taxable;

defined( 'ABSPATH' ) || exit;

/**
 * FeeItem class
 */
class FeeItem extends TaxableItem implements Taxable, Summable, SplitTaxable, Discountable {

	protected $extra_data = array(
		'line_total'            => 0,
		'total_tax'             => 0,
		'prices_include_tax'    => false,
		'round_tax_at_subtotal' => null,
		'line_subtotal'         => 0,
		'subtotal_tax'          => 0,
		'price'                 => 0,
		'price_subtotal'        => 0,
		'is_taxable'            => true,
		'enable_split_tax'      => false,
	);

	protected $data_store_name = 'invoice_fee_item';

	public function get_item_type() {
		return 'fee';
	}

	public function get_document_group() {
		return 'accounting';
	}

	public function get_data() {
		$data = parent::get_data();

		$data['discount_total']      = $this->get_discount_total();
		$data['discount_net']        = $this->get_discount_net();
		$data['discount_tax']        = $this->get_discount_tax();
		$data['discount_percentage'] = $this->get_discount_percentage();

		return $data;
	}

	public function get_enable_split_tax( $context = 'view' ) {
		return $this->get_prop( 'enable_split_tax', $context );
	}

	public function enable_split_tax() {
		return true === $this->get_enable_split_tax();
	}

	public function set_enable_split_tax( $enable ) {
		$this->set_prop( 'enable_split_tax', sab_string_to_bool( $enable ) );
	}

	public function get_discount_total( $context = '' ) {
		$discount_total = $this->get_total_before_discount() - $this->get_total();

		return sab_format_decimal( $discount_total );
	}

	public function get_discount_net( $context = '' ) {
		return sab_format_decimal( $this->get_discount_total( $context ) - $this->get_discount_tax( $context ) );
	}

	public function get_discount_tax( $context = '' ) {
		return sab_format_decimal( $this->get_subtotal_tax() - $this->get_total_tax() );
	}

	public function get_discount_percentage() {
		return sab_calculate_discount_percentage( $this->get_total_before_discount(), $this->get_discount_total() );
	}

	public function has_discount() {
		return $this->get_discount_total() > 0;
	}

	public function get_total_before_discount() {
		return $this->get_subtotal();
	}
}