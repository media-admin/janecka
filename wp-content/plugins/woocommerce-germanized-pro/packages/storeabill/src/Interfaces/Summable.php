<?php

namespace Vendidero\StoreaBill\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Summable Interface
 *
 * This interface makes sure that an object is summable.
 */
interface Summable {

	/**
	 * Recalculate total amounts.
	 */
	public function calculate_totals();

	/**
	 * Returns total amount.
	 *
	 * @param string $context
	 *
	 * @return string
	 */
	public function get_total( $context = '' );

	/**
	 * Returns subtotal amount.
	 *
	 * @param string $context
	 *
	 * @return string
	 */
	public function get_subtotal();
}