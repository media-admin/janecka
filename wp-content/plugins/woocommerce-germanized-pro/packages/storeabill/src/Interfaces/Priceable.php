<?php

namespace Vendidero\StoreaBill\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Priceable Interface
 *
 * This interface makes sure that an object may contain prices.
 */
interface Priceable {

	/**
	 * Returns price (after discounts).
	 *
	 * @param string $context
	 *
	 * @return string
	 */
	public function get_price( $context = '' );

	/**
	 * Returns price (before discounts).
	 *
	 * @param string $context
	 *
	 * @return string
	 */
	public function get_price_subtotal( $context = '' );
}