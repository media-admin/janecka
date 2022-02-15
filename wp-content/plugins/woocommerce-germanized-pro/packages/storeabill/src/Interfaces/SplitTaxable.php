<?php

namespace Vendidero\StoreaBill\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SplitTaxable Interface
 *
 * Makes sure that an object supports split tax calculation.
 */
interface SplitTaxable {

	public function enable_split_tax();
}