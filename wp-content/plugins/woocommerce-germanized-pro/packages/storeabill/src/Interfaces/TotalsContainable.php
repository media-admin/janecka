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
interface TotalsContainable {

	/**
	 * @return \Vendidero\StoreaBill\Total[]
	 */
	public function get_totals();
}