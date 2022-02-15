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
interface TaxContainable {

	public function get_tax_type( $context = '' );

	public function set_tax_type( $type );

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
	 * Returns total tax amount.
	 *
	 * @param string $context
	 *
	 * @return string
	 */
	public function get_subtotal_tax( $context = '' );

	/**
	 * Set total tax amount.
	 *
	 * @param $value
	 */
	public function set_subtotal_tax( $value );


	/**
	 * Adds a tax rate for tax calculations.
	 *
	 * @param TaxRate $rate
	 */
	public function set_tax_rate( $rate );

	/**
	 * Returns the tax rate.
	 *
	 * @return TaxRate $rate
	 */
	public function get_tax_rate();

	public function get_tax_rate_key();
	
	public function round_tax_at_subtotal();
}