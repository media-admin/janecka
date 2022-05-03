<?php

namespace Vendidero\StoreaBill\Invoice;

use Vendidero\StoreaBill\Interfaces\Priceable;
use Vendidero\StoreaBill\Interfaces\Summable;
use Vendidero\StoreaBill\Interfaces\Taxable;
use Vendidero\StoreaBill\Tax;
use Vendidero\StoreaBill\TaxRate;

defined( 'ABSPATH' ) || exit;

/**
 * TaxableDocumentItem class
 */
abstract class TaxableItem extends Item implements Taxable, Summable, Priceable {

	protected $tax_rates = null;

	public function get_data() {
		$data                       = parent::get_data();
		$data['tax_rates']          = $this->get_tax_rates();
		$data['taxes']              = $this->get_taxes();
		$data['total']              = $this->get_total();
		$data['total_net']          = $this->get_total_net();
		$data['subtotal']           = $this->get_subtotal();
		$data['subtotal_net']       = $this->get_subtotal_net();
		$data['price_net']          = $this->get_price_net();
		$data['price_subtotal_net'] = $this->get_price_subtotal_net();
		$data['price_tax']          = $this->get_price_tax();
		$data['price_subtotal_tax'] = $this->get_price_subtotal_tax();

		return $data;
	}

	public function get_total( $context = 'view' ) {
		return ( $this->prices_include_tax() ? $this->get_line_total() : sab_format_decimal( (float) $this->get_line_total( $context ) + (float) $this->get_total_tax( $context ) ) );
	}

	public function get_subtotal( $context = 'view' ) {
		return ( $this->prices_include_tax() ? $this->get_line_subtotal() : sab_format_decimal( (float) $this->get_line_subtotal( $context ) + (float) $this->get_subtotal_tax( $context ) ) );
	}

	/**
	 * Line total (after discounts).
	 *
	 * @param string $value
	 */
	public function get_line_total( $context = 'view' ) {
		return $this->get_prop( 'line_total', $context );
	}

	/**
	 * Line subtotal (before discounts).
	 *
	 * @param string $value
	 */
	public function get_line_subtotal( $context = 'view' ) {
		return $this->get_prop( 'line_subtotal', $context );
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
		return $this->get_quantity() > 0 ? sab_format_decimal( $this->get_total_tax( $context ) / $this->get_quantity( $context ) ) : 0;
	}

	public function get_price_net() {
		return sab_format_decimal( $this->get_price() - $this->get_price_tax() );
	}

	/**
	 * Unit price (before discounts).
	 *
	 * @param string $value
	 */
	public function get_price_subtotal( $context = 'view' ) {
		return $this->get_prop( 'price_subtotal', $context );
	}

	public function get_price_subtotal_tax( $context = 'view' ) {
		return $this->get_quantity() > 0 ? sab_format_decimal( $this->get_subtotal_tax() / $this->get_quantity() ) : 0;
	}

	public function get_price_subtotal_net() {
		return sab_format_decimal( $this->get_price_subtotal() - $this->get_price_subtotal_tax() );
	}

	/**
	 * Unit price.
	 *
	 * @param string $value
	 */
	public function set_price( $value ) {
		$this->set_prop( 'price', sab_format_decimal( $value ) );
	}

	/**
	 * Unit price.
	 *
	 * @param string $value
	 */
	public function set_price_subtotal( $value ) {
		$this->set_prop( 'price_subtotal', sab_format_decimal( $value ) );
	}

	/**
	 * Set total amount.
	 *
	 * @param $value
	 */
	public function set_line_total( $value ) {
		$this->set_prop( 'line_total', sab_format_decimal( $value ) );

		// Subtotal cannot be less than total (or greater in case total is smaller than 0)
		if ( '' === $this->get_line_subtotal() || ( ( $this->get_line_total() > 0 && $this->get_line_subtotal() < $this->get_line_total() ) ) || ( $this->get_line_total() < 0 && $this->get_line_subtotal() > $this->get_line_total() ) ) {
			$this->set_line_subtotal( $value );
		}
	}

	/**
	 * Sets subtotal amount.
	 *
	 * @param $value
	 */
	public function set_line_subtotal( $value ) {
		$this->set_prop( 'line_subtotal', sab_format_decimal( $value ) );
	}

	public function get_is_taxable( $context = 'view' ) {
		return $this->get_prop( 'is_taxable', $context );
	}

	public function set_is_taxable( $is_taxable ) {
		$this->set_prop( 'is_taxable', sab_string_to_bool( $is_taxable ) );
	}

	public function has_taxes() {
		$tax_rates = $this->get_tax_rates();

		return ( sizeof( $tax_rates ) > 0 && $this->is_taxable() );
	}

	public function is_taxable() {
		return true === $this->get_is_taxable();
	}

	/**
	 * Returns tax amount for subtotal.
	 *
	 * @param string $context
	 *
	 * @return string
	 */
	public function get_subtotal_tax( $context = 'view' ) {
		return $this->get_prop( 'subtotal_tax', $context );
	}

	/**
	 * Sets subtotal tax amount.
	 *
	 * @param $value
	 */
	public function set_subtotal_tax( $value ) {
		$this->set_prop( 'subtotal_tax', sab_format_decimal( $value ) );
	}

	/**
	 * @return TaxRate[]
	 */
	public function get_tax_rates() {
		if ( is_null( $this->tax_rates ) ) {
			$this->tax_rates = array();

			foreach( $this->get_taxes() as $tax ) {
				if ( $rate = $tax->get_tax_rate() ) {
					if ( ! array_key_exists( $rate->get_merge_key(), $this->tax_rates ) ) {
						$this->tax_rates[ $rate->get_merge_key() ] = $rate;
					}
				}
			}

			uasort( $this->tax_rates, array( '\Vendidero\StoreaBill\Tax', '_sort_tax_rates_callback' ) );
		}

		return $this->tax_rates;
	}

	/**
	 * @param array|TaxRate $rate
	 */
	public function add_tax_rate( $rate ) {
		$this->get_tax_rates();
		$tax_rate = new TaxRate( $rate );

		if ( $this->contains_tax_rate( $tax_rate ) ) {
			return false;
		}

		$tax_item = new TaxItem();
		$tax_item->set_tax_rate( $tax_rate );

		$this->add_tax( $tax_item );

		return true;
	}

	/**
	 * @param TaxRate $tax_rate
	 */
	public function contains_tax_rate( $tax_rate ) {
		$rates = $this->get_tax_rates();

		return array_key_exists( $tax_rate->get_merge_key(), $rates );
	}

	public function remove_tax_rate( $merge_code ) {
		$tax_rates = $this->get_tax_rates();

		// Remove the tax child item
		if ( $tax_item = $this->get_tax_item_by_rate_key( $merge_code ) ) {
			$this->remove_child( $tax_item->get_key() );
		}

		if ( array_key_exists( $merge_code, $tax_rates ) ) {
			unset( $this->tax_rates[ $merge_code ] );
		}
	}

	public function get_total_tax( $context = '' ) {
		return $this->get_prop( 'total_tax', $context );
	}

	/**
	 * Sets total tax amount.
	 *
	 * @param $value float the amount to be set.
	 */
	public function set_total_tax( $value ) {
		$this->set_prop( 'total_tax', sab_format_decimal( $value ) );
	}

	/**
	 * Returns whether prices of this invoice include tax or not.
	 *
	 * @param string $context
	 *
	 * @return bool True if prices include tax.
	 */
	public function get_prices_include_tax( $context = 'view' ) {
		return $this->get_prop( 'prices_include_tax', $context );
	}

	/**
	 * Set whether invoice prices include tax or not.
	 *
	 * @param bool|string $value Either bool or string (yes/no).
	 */
	public function set_prices_include_tax( $value ) {
		$this->set_prop( 'prices_include_tax', sab_string_to_bool( $value ) );
	}

	public function get_total_net() {
		$total_net = ( $this->prices_include_tax() ) ? sab_format_decimal( $this->get_line_total() - $this->get_total_tax() ) : $this->get_line_total();
		$rounded      = sab_format_decimal($total_net, '' );

		if ( $rounded == 0 ) {
			$total_net = 0;
		}

		return $total_net;
	}

	public function get_subtotal_net() {
		$subtotal_net = ( $this->prices_include_tax() ) ? sab_format_decimal( $this->get_line_subtotal() - $this->get_subtotal_tax() ) : $this->get_line_subtotal();
		$rounded      = sab_format_decimal( $subtotal_net, '' );

		if ( $rounded == 0 ) {
			$subtotal_net = 0;
		}

		return $subtotal_net;
	}

	/**
	 * Adds a tax item. Can be manually called to store pre defined tax items.
	 *
	 * @param TaxItem $item
	 * @return bool True if the tax item has been added successfully.
	 */
	public function add_tax( $item ) {

		if ( ! is_a( $item, '\Vendidero\StoreaBill\Interfaces\TaxContainable' ) ) {
			return false;
		}

		$item->set_tax_type( $this->get_item_type() );
		$this->add_child( $item );

		// Make sure tax rates are loaded again
		$this->tax_rates = null;

		return true;
	}

	/**
	 * @return TaxItem[] $taxes
	 */
	public function get_taxes() {
		return parent::get_children();
	}

	public function remove_taxes() {
		parent::remove_children( true );
	}

	protected function get_tax_item_by_rate_key( $key ) {
		foreach( $this->get_taxes() as $tax ) {
			if ( $key === $tax->get_tax_rate_key() ) {
				return $tax;
			}
		}

		return false;
	}

	/**
	 * Recalculate totals (based on subtotal).
	 */
	public function calculate_totals() {
		/**
		 * Calculate price before (subtotal) and after discounts.
		 */
		if ( $this->get_quantity() > 0 ) {
			$this->set_price( $this->get_total() / $this->get_quantity() );
			$this->set_price_subtotal( $this->get_subtotal() / $this->get_quantity() );
		}
	}

	protected function has_voucher() {
		$has_voucher = false;

		if ( ( $document = $this->get_document() ) && $document->has_voucher() ) {
			$has_voucher = true;
		}

		return $has_voucher;
	}

	/**
	 * In case the parent document includes a voucher
	 * calculate item tax totals based on the pre-discount amount (e.g. subtotal).
	 *
	 * @return mixed|null
	 */
	protected function get_line_total_taxable() {
		$line_total = $this->get_line_total();

		if ( $this->has_voucher() ) {
			if ( ( $document = $this->get_document() ) && $document->stores_vouchers_as_discount() ) {
				$line_total = $this->get_line_subtotal();
			}
		}

		return $line_total;
	}

	protected function get_tax_shares() {
		$tax_shares = array();

		if ( ( $document = $this->get_document() ) && is_a( $document, '\Vendidero\StoreaBill\Interfaces\Invoice' ) ) {
			$tax_shares = $document->get_tax_shares( $this->get_item_type() );
		}

		return $tax_shares;
	}

	protected function calculate_split_tax_totals() {
		$total_tax     = 0;
		$subtotal_tax  = 0;
		$rates         = $this->get_tax_rates();

		if ( $document = $this->get_document() ) {
			if ( is_a( $document, '\Vendidero\StoreaBill\Interfaces\Invoice' ) ) {
				$tax_shares = $this->get_tax_shares();

				foreach( $tax_shares as $tax_rate_key => $share ) {
					if ( isset( $rates[ $tax_rate_key ] ) ) {

						$total_amount    = sab_format_decimal( $this->get_line_total_taxable() * $share );
						$subtotal_amount = sab_format_decimal( $this->get_line_subtotal() * $share );
						$share_rates     = array( $rates[ $tax_rate_key ] );

						$taxes           = Tax::calc_tax( $total_amount, $share_rates, $this->prices_include_tax() );
						$subtotal_taxes  = Tax::calc_tax( $subtotal_amount, $share_rates, $this->prices_include_tax() );

						foreach( $taxes as $rate_key => $tax ) {

							$rate         = $rates[ $rate_key ];
							$subtotal     = isset( $subtotal_taxes[ $rate_key ] ) ? $subtotal_taxes[ $rate_key ] : 0;
							$round_taxes  = apply_filters( 'storeabill_round_tax_at_subtotal_split_tax_calculation', $this->round_tax_at_subtotal(), $this );

							// Item does already exist - lets update
							if ( $item = $this->get_tax_item_by_rate_key( $rate->get_merge_key() ) ) {
								$item->set_tax_rate( $rate );
								$item->set_round_tax_at_subtotal( $round_taxes );

								$item->set_total_tax( $tax );
								$item->set_subtotal_tax( $subtotal );
							} else {
								$item = new TaxItem();
								$item->set_tax_rate( $rate );
								$item->set_round_tax_at_subtotal( $round_taxes );

								$item->set_total_tax( $tax );
								$item->set_subtotal_tax( $subtotal );

								$this->add_tax( $item );
							}

							$item->set_total_net( $this->prices_include_tax() ? ( $total_amount - $item->get_total_tax() ) : $total_amount );
							$item->set_subtotal_net( $this->prices_include_tax() ? ( $subtotal_amount - $item->get_subtotal_tax() ) : $subtotal_amount );

							if ( $document = $this->get_document() ) {
								if ( ! $document->get_item( $item->get_key() ) ) {
									$document->add_item( $item );
								}
							}

							$total_tax    += $item->get_total_tax();
							$subtotal_tax += $item->get_subtotal_tax();
						}
					}
				}

				// Delete unused tax items
				foreach( $this->get_taxes() as $tax ) {

					if ( ! array_key_exists( $tax->get_tax_rate_key(), $tax_shares ) ) {
						if ( $document = $this->get_document() ) {
							$document->remove_item( $tax->get_id() );
						} else {
							$tax->delete( true );
						}
					}
				}

				$this->set_total_tax( $total_tax );
				$this->set_subtotal_tax( $subtotal_tax );
			}
		}
	}

	/**
	 * Returns whether taxes are round at subtotal or per line.
	 *
	 * @param string $context
	 *
	 * @return bool True if taxes are round at subtotal.
	 */
	public function get_round_tax_at_subtotal( $context = 'view' ) {
		$round_tax = $this->get_prop( 'round_tax_at_subtotal', $context );

		if ( 'view' === $context && is_null( $round_tax ) ) {
			if ( $document = $this->get_document() ) {
				if ( is_a( $document, '\Vendidero\StoreaBill\Interfaces\Invoice' ) ) {
					return $document->round_tax_at_subtotal();
				}
			}

			return sab_string_to_bool( get_option( 'woocommerce_tax_round_at_subtotal' ) );
		}

		return $round_tax;
	}

	/**
	 * Set whether invoice taxes are round at subtotal or not.
	 *
	 * @param bool|string $value Either bool or string (yes/no).
	 */
	public function set_round_tax_at_subtotal( $value ) {
		$this->set_prop( 'round_tax_at_subtotal', '' === $value ? null : sab_string_to_bool( $value ) );
	}

	public function round_tax_at_subtotal() {
		return $this->get_round_tax_at_subtotal();
	}

	public function calculate_tax_totals() {

		if ( ! $this->is_taxable() ) {
			$this->set_total_tax( 0 );
			$this->set_subtotal_tax( 0 );
			$this->remove_taxes();

			return;
		}

		/*
		 * Maybe calculate split tax totals based on tax shares included within the document.
		 */
		if ( is_a( $this, '\Vendidero\StoreaBill\Interfaces\SplitTaxable' ) ) {
			if ( ( $document = $this->get_document() ) && $this->enable_split_tax() ) {
				if ( is_a( $document, '\Vendidero\StoreaBill\Interfaces\Invoice' ) ) {
					$tax_shares = $this->get_tax_shares();

					if ( sizeof( $tax_shares ) > 1 ) {
						$this->calculate_split_tax_totals();
						return;
					}
				}
			}
		}

		$total_tax      = 0;
		$subtotal_tax   = 0;
		$rates          = $this->get_tax_rates();

		$taxes          = Tax::calc_tax( $this->get_line_total_taxable(), $rates, $this->prices_include_tax() );
		$subtotal_taxes = Tax::calc_tax( $this->get_line_subtotal(), $rates, $this->prices_include_tax() );

		foreach( $taxes as $rate_key => $tax ) {
			$rate         = $rates[ $rate_key ];
			$subtotal     = isset( $subtotal_taxes[ $rate_key ] ) ? $subtotal_taxes[ $rate_key ] : 0;

			// Item does already exist - lets update
			if ( $item = $this->get_tax_item_by_rate_key( $rate->get_merge_key() ) ) {
				$item->set_tax_rate( $rate );
				$item->set_round_tax_at_subtotal( $this->round_tax_at_subtotal() );

				$item->set_total_tax( $tax );
				$item->set_subtotal_tax( $subtotal );
			} else {
				$item = new TaxItem();
				$item->set_tax_rate( $rate );
				$item->set_round_tax_at_subtotal( $this->round_tax_at_subtotal() );

				$item->set_total_tax( $tax );
				$item->set_subtotal_tax( $subtotal );

				$this->add_tax( $item );
			}

			$item->set_total_net( $this->prices_include_tax() ? ( $this->get_line_total_taxable() - $item->get_total_tax() ) : $this->get_line_total_taxable() );
			$item->set_subtotal_net( $this->prices_include_tax() ? ( $this->get_line_subtotal() - $item->get_subtotal_tax() ) : $this->get_line_subtotal() );

			if ( $document = $this->get_document() ) {
				if ( ! $document->get_item( $item->get_key() ) ) {
					$document->add_item( $item );
				}
			}

			$total_tax    += $item->get_total_tax();
			$subtotal_tax += $item->get_subtotal_tax();
		}

		// Delete unused tax items
		foreach( $this->get_taxes() as $tax ) {

			if ( ! array_key_exists( $tax->get_tax_rate_key(), $taxes ) ) {
				if ( $document = $this->get_document() ) {
					$document->remove_item( $tax->get_id() );
				} else {
					$tax->delete( true );
				}
			}
		}

		$this->set_total_tax( $total_tax );
		$this->set_subtotal_tax( $subtotal_tax );
	}

	public function prices_include_tax() {
		return $this->get_prices_include_tax() === true;
	}
}