<?php

namespace Vendidero\StoreaBill\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Discountable Interface
 *
 * This interface makes sure that an object may be discountable.
 */
interface Discountable {

	/**
	 * Returns total discount.
	 *
	 * @param string $context
	 *
	 * @return string
	 */
	public function get_discount_total( $context = '' );

	/**
	 * Returns total discount percentage.
	 *
	 * @return string
	 */
	public function get_discount_percentage();

	public function get_discount_tax( $context = '' );

	public function get_discount_net( $context = '' );

	public function get_total_before_discount();

	public function has_discount();
}