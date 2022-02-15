<?php

namespace Vendidero\StoreaBill\Interfaces;

use Vendidero\StoreaBill\TaxRate;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Taxable Interface
 *
 * This interface makes sure that the object supports taxes.
 */
interface Taxable {

	/**
	 * Returns total tax amount.
	 *
	 * @param string $context
	 *
	 * @return string
	 */
	public function get_total_tax( $context = '' );

	/**
	 * Set total tax amount.
	 *
	 * @param $value
	 */
	public function set_total_tax( $value );

	/**
	 * Adds a tax rate for tax calculations.
	 *
	 * @param TaxRate $rate
	 */
	public function add_tax_rate( $rate );

	/**
	 * Returns the tax rate.
	 *
	 * @return TaxRate[] $rate
	 */
	public function get_tax_rates();

	public function prices_include_tax();

	public function set_prices_include_tax( $include_tax );

	public function calculate_tax_totals();

	public function get_total_net();

	public function round_tax_at_subtotal();

	public function is_taxable();

	public function set_is_taxable( $is_taxable );

	public function has_taxes();
}