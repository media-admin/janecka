<?php

namespace Vendidero\StoreaBill\Interfaces;

/**
 * Invoice
 *
 * @package  Germanized/StoreaBill/Interfaces
 * @version  1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Invoice class.
 */
interface Invoice extends Summable {

	public function get_item_types_for_totals();

	public function get_item_types_for_tax_totals();

	public function get_total_tax( $context = '' );

	public function get_subtotal_tax();

	public function prices_include_tax();

	public function round_tax_at_subtotal();

	public function update_taxes();

	public function update_total();

	public function update_tax_totals();

	public function get_formatted_price( $price, $type = '' );

	public function is_paid();

	public function is_oss();

	public function is_eu_cross_border_taxable();
}
