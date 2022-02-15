<?php

namespace Vendidero\StoreaBill\Invoice;

use Vendidero\StoreaBill\Interfaces\Discountable;
use Vendidero\StoreaBill\Interfaces\Product;
use Vendidero\StoreaBill\Interfaces\Summable;
use Vendidero\StoreaBill\Interfaces\Taxable;

defined( 'ABSPATH' ) || exit;

/**
 * ProductItem class
 */
class ProductItem extends TaxableItem implements Discountable {

	protected $product = null;

	protected $extra_data = array(
		'product_id'                => 0,
		'line_total'                => 0,
		'total_tax'                 => 0,
		'prices_include_tax'        => false,
		'round_tax_at_subtotal'     => null,
		'line_subtotal'             => 0,
		'subtotal_tax'              => 0,
		'price'                     => 0,
		'price_subtotal'            => 0,
		'sku'                       => '',
		'is_taxable'                => true,
		'is_virtual'                => false,
		'is_service'                => false,
		'has_differential_taxation' => false,
	);

	protected $data_store_name = 'invoice_product_item';

	public function get_item_type() {
		return 'product';
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

	/**
	 * @return bool|\Vendidero\StoreaBill\Interfaces\Product
	 */
	public function get_product() {
		if ( is_null( $this->product ) ) {

			if ( $this->get_product_id() > 0 ) {
				$this->product = \Vendidero\StoreaBill\References\Product::get_product( $this->get_product_id(), $this->get_document()->get_reference_type() );
			}

			if ( is_null( $this->product ) ) {
				$this->product = false;
			}
		}

		return $this->product;
	}

	public function get_image_url( $size = '', $placeholder = false ) {
		if ( $product = $this->get_product() ) {
			return $product->get_image_url( $size, $placeholder );
		}

		return parent::get_image_url( $size, $placeholder );
	}

	public function get_is_virtual( $context = 'view' ) {
		return $this->get_prop( 'is_virtual', $context );
	}

	public function is_virtual() {
		return true === $this->get_is_virtual();
	}

	public function set_is_virtual( $is_virtual ) {
		$this->set_prop( 'is_virtual', sab_string_to_bool( $is_virtual ) );
	}

	public function get_sku( $context = 'view' ) {
		return $this->get_prop( 'sku', $context );
	}

	public function set_sku( $sku ) {
		$this->set_prop( 'sku', $sku );
	}

	public function get_is_service( $context = 'view' ) {
		return $this->get_prop( 'is_service', $context );
	}

	public function is_service() {
		return true === $this->get_is_service();
	}

	public function set_is_service( $is_service ) {
		$this->set_prop( 'is_service', sab_string_to_bool( $is_service ) );
	}

	public function get_has_differential_taxation( $context = 'view' ) {
		return $this->get_prop( 'has_differential_taxation', $context );
	}

	public function has_differential_taxation() {
		return true === $this->get_has_differential_taxation();
	}

	public function set_has_differential_taxation( $differential_taxation ) {
		$this->set_prop( 'has_differential_taxation', sab_string_to_bool( $differential_taxation ) );
	}

	/**
	 * Get product id.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return integer
	 */
	public function get_product_id( $context = 'view' ) {
		return $this->get_prop( 'product_id', $context );
	}

	/**
	 * Sets the product id
	 *
	 * @param integer $product_id
	 */
	public function set_product_id( $product_id ) {
		$this->set_prop( 'product_id', absint( $product_id ) );

		$this->product = null;
	}

	/**
	 * Set properties based on passed in product object.
	 *
	 * @param Product $product Product instance.
	 */
	public function set_product( $product ) {

		if ( ! is_a( $product, '\Vendidero\StoreaBill\Interfaces\Product' ) ) {
			return;
		}

		$this->set_product_id( $product->get_id() );
		$this->set_name( $product->get_name() );
		$this->set_sku( $product->get_sku() );
		$this->set_is_virtual( $product->is_virtual() );
		$this->set_is_service( $product->is_service() );
	}

	public function get_discount_total( $context = '' ) {
		$discount_total = $this->get_total_before_discount() - $this->get_total();

		return sab_format_decimal( $discount_total );
	}

	public function get_discount_net( $context = '' ) {
		$discount_total = sab_format_decimal( $this->get_discount_total( $context ) - $this->get_discount_tax( $context ) );
		$net_total      = $this->get_subtotal_net();

		/**
		 * Discount net amount cannot exceed item net total
		 */
		if ( $discount_total > $net_total ) {
			$discount_total = $net_total;
		}

		return $discount_total;
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