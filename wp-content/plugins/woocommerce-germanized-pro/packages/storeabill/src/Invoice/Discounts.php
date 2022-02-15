<?php
namespace Vendidero\StoreaBill\Invoice;

defined( 'ABSPATH' ) || exit;

class Discounts extends \WC_Discounts {

	/**
	 * @var Invoice
	 */
	protected $object;

	protected $is_voucher = false;

	/**
	 * Discounts constructor.
	 *
	 * @param Invoice $object
	 */
	public function __construct( $object = null ) {
		$this->set_items_from_invoice( $object );
	}

	/**
	 * Get all discount totals.
	 *
	 * @since  3.2.0
	 * @param  bool $in_cents Should the totals be returned in cents, or without precision.
	 * @return mixed
	 */
	public function get_total_discount( $in_cents = false ) {
		$discounts = parent::get_discounts_by_coupon( $in_cents );

		return array_key_exists( '', $discounts ) ? $discounts[''] : 0;
	}

	/**
	 * @param Invoice $object
	 */
	protected function set_items_from_invoice( $object, $item_types = array( 'product' ) ) {
		$this->items     = array();
		$this->discounts = array();
		$this->object    = $object;
		$tmp_items       = array();

		foreach( $item_types as $item_type ) {
			$tmp_items[ $item_type ] = array();
		}

		foreach ( $object->get_items( $item_types ) as $invoice_item ) {
			if ( ! is_a( $invoice_item, 'Vendidero\StoreaBill\Invoice\TaxableItem' ) ) {
				continue;
			}

			$item           = new \stdClass();
			$item->key      = $invoice_item->get_key();
			$item->object   = $invoice_item;
			$item->product  = false;
			$item->quantity = $invoice_item->get_quantity();
			$item->price    = wc_add_number_precision_deep( $invoice_item->get_line_total() );

			$tmp_items[ $invoice_item->get_item_type() ][ $invoice_item->get_key() ] = $item;
		}

		foreach( $tmp_items as $item_type => $items ) {
			if ( ! empty( $items ) ) {
				uasort( $tmp_items[ $item_type ], array( $this, 'sort_by_price' ) );

				$this->items = $this->items + $tmp_items[ $item_type ];
			}
		}
	}

	public function is_voucher() {
		return $this->is_voucher;
	}

	public function apply_coupon( $coupon, $validate = true ) {

	}

	public function get_discounts( $in_cents = false ) {
		$discounts = parent::get_discounts( $in_cents );

		return ( ! empty( $discounts ) && array_key_exists( '', $discounts ) ) ? $discounts[''] : array();
	}

	public function apply_discount( $amount, $type = 'fixed', $args = array() ) {
		$args = wp_parse_args( $args, array(
			'is_voucher' => false,
			'item_types' => array( 'product' )
		) );

		$this->set_items_from_invoice( $this->object, $args['item_types'] );
		$this->is_voucher = $args['is_voucher'];

		if ( ( $this->object->has_voucher() ) && ! $this->is_voucher() || ( $this->is_voucher() && $this->object->has_discount() && ! $this->object->has_voucher() ) ) {
			return new \WP_Error( 'mixed_types', _x( 'Vouchers and normal discounts may not be mixed', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}

		$coupon_type = 'fixed' === $type ? 'fixed_cart' : 'percent';
		$coupon      = new \WC_Coupon();

		$coupon->read_manual_coupon( '', array(
			'amount'        => sab_format_decimal( $amount ),
			'discount_type' => $coupon_type,
		) );

		$result = parent::apply_coupon( $coupon, false );

		return $result;
	}

	/**
	 * Get items which the coupon should be applied to.
	 *
	 * @since  3.2.0
	 * @param  object $coupon Coupon object.
	 * @return array
	 */
	protected function get_items_to_apply_coupon( $coupon ) {
		$items_to_apply = array();

		foreach ( $this->get_items_to_validate() as $item ) {
			$item_to_apply = clone $item; // Clone the item so changes to this item do not affect the originals.

			if ( 0 === $this->get_discounted_price_in_cents( $item_to_apply ) || 0 >= $item_to_apply->quantity ) {
				continue;
			}

			$items_to_apply[] = $item_to_apply;
		}
		return $items_to_apply;
	}
}