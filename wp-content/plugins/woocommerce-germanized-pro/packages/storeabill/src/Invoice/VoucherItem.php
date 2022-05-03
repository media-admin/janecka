<?php

namespace Vendidero\StoreaBill\Invoice;

use Vendidero\StoreaBill\Interfaces\Summable;
use Vendidero\StoreaBill\Interfaces\Taxable;

defined( 'ABSPATH' ) || exit;

/**
 * VoucherItem class
 */
class VoucherItem extends TaxableItem implements Summable, Taxable {

	protected $extra_data = array(
		'code'                      => '',
		'line_total'                => 0,
		'total_tax'                 => 0,
		'prices_include_tax'        => false,
		'round_tax_at_subtotal'     => null,
		'price'                     => 0,
		'is_taxable'                => false,
	);

	protected $data_store_name = 'invoice_voucher_item';

	public function get_item_type() {
		return 'voucher';
	}

	public function get_document_group() {
		return 'accounting';
	}

	public function get_data() {
		$data                   = parent::get_data();
		$data['total']          = $this->get_total();
		$data['subtotal']       = $this->get_subtotal();
		$data['subtotal_tax']   = $this->get_subtotal_tax();
		$data['line_subtotal']  = $this->get_line_subtotal();
		$data['price_subtotal'] = $this->get_price_subtotal();

		return $data;
	}

	public function get_name( $context = 'view' ) {
		$name = $this->get_prop( 'name', $context );

		if ( 'view' === $context && empty( $name ) ) {
			$name = sprintf( _x( 'Voucher: %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $this->get_code( $context ) );
		}

		return $name;
	}

	public function get_line_subtotal( $context = 'view' ) {
		return $this->get_line_total( $context );
	}

	public function get_total_tax( $context = '' ) {
		return 0;
	}

	public function get_subtotal_tax( $context = 'view' ) {
		return 0;
	}

	public function get_total_net( $context = '' ) {
		return $this->get_total( $context );
	}

	public function get_tax_rates() {
		return array();
	}

	public function set_line_subtotal( $value ) {}

	public function set_total_tax( $value ) {}

	public function get_taxes() {
		return array();
	}

	/**
	 * Line total
	 *
	 * @param string $value
	 */
	public function get_line_total( $context = 'view' ) {
		return $this->get_prop( 'line_total', $context );
	}

	/**
	 * Voucher Code
	 *
	 * @param string $value
	 */
	public function get_code( $context = 'view' ) {
		return $this->get_prop( 'code', $context );
	}

	/**
	 * Set total amount.
	 *
	 * @param $value
	 */
	public function set_line_total( $value ) {
		$this->set_prop( 'line_total', sab_format_decimal( $value ) );
	}

	/**
	 * Set voucher code.
	 *
	 * @param $value
	 */
	public function set_code( $value ) {
		$this->set_prop( 'code', $value );

		if ( '' === $this->get_name( 'edit' ) ) {
			$this->set_name( sprintf( _x( 'Voucher: %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $value ) );
		}
 	}

	public function get_total( $context = 'view' ) {
		return sab_format_decimal( (float) $this->get_line_total( $context ) );
	}

	public function get_subtotal( $context = 'view' ) {
		return sab_format_decimal( (float) $this->get_line_total( $context ) );
	}

	/**
	 * Unit price (after discounts).
	 *
	 * @param string $value
	 */
	public function get_price( $context = 'view' ) {
		return $this->get_prop( 'price', $context );
	}

	public function get_price_tax( $context = 'view' ) {
		return 0;
	}

	public function get_price_subtotal( $context = 'view' ) {
		return $this->get_price( $context );
	}

	public function get_price_subtotal_tax( $context = 'view' ) {
		return 0;
	}

	public function get_price_net( $context = 'view' ) {
		return $this->get_price( $context );
	}

	public function get_price_subtotal_net( $context = 'view' ) {
		return $this->get_price( $context );
	}

	public function set_price( $value ) {
		$this->set_prop( 'price', sab_format_decimal( $value ) );
	}

	public function calculate_totals() {
		/**
		 * Calculate unit price.
		 */
		if ( $this->get_quantity() > 0 ) {
			$this->set_price( $this->get_total() / $this->get_quantity() );
		}
	}

	public function add_tax_rate( $rate ) {
		return false;
	}

	public function prices_include_tax() {
		return true;
	}

	public function add_tax( $item ) {
		return false;
	}

	public function set_prices_include_tax( $include_tax ) {}

	public function calculate_tax_totals() {}

	public function round_tax_at_subtotal() {}

	public function is_taxable() {
		return false;
	}

	public function set_is_taxable( $is_taxable ) {}

	public function has_taxes() {
		return false;
	}
}