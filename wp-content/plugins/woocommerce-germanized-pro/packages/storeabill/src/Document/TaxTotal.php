<?php

namespace Vendidero\StoreaBill\Document;

use Vendidero\StoreaBill\Interfaces\TaxContainable;
use Vendidero\StoreaBill\Invoice\TaxItem;
use Vendidero\StoreaBill\TaxRate;

defined( 'ABSPATH' ) || exit;

/**
 * TaxTotal class
 */
class TaxTotal {

	/**
	 * Merged tax items.
	 *
	 * @var TaxItem[]
	 */
	private $taxes = array();

	protected $item_type_totals = null;

	protected $item_type_net_totals = null;

	protected $item_type_subtotals = null;

	protected $item_type_net_subtotals = null;

	protected $total = 0;

	protected $total_net = 0;

	protected $subtotal = 0;

	protected $subtotal_net = 0;

	/**
	 * @var null|TaxRate
	 */
	protected $rate = null;

	public function __construct() {}

	public function get_total_tax( $round = true ) {
		return $round ? wc_round_tax_total( $this->total ) : sab_format_decimal( $this->total );
	}

	public function get_total_net( $round = true ) {
		return $round ? sab_format_decimal( $this->total_net, '' ) : sab_format_decimal( $this->total_net );
	}

	public function get_subtotal_tax( $round = true ) {
		return $round ? wc_round_tax_total( $this->subtotal ) : sab_format_decimal( $this->subtotal );
	}

	public function get_subtotal_net( $round = true ) {
		return $round ? sab_format_decimal( $this->subtotal_net, '' ) : sab_format_decimal( $this->subtotal_net );
	}

	public function set_total_tax( $total ) {
		$this->total = $total;
	}

	public function set_subtotal_tax( $total ) {
		$this->subtotal = $total;
	}

	public function set_total_net( $total ) {
		$this->total_net = $total;
	}

	public function set_subtotal_net( $total ) {
		$this->subtotal_net = $total;
	}

	public function get_data() {
		return array(
			'total_net'     => $this->get_total_net(),
			'total_tax'     => $this->get_total_tax(),
			'subtotal_net'  => $this->get_subtotal_net(),
			'subtotal_tax'  => $this->get_subtotal_tax(),
			'rate'          => $this->get_tax_rate(),
			'net_totals'    => $this->get_item_type_net_totals(),
			'tax_totals'    => $this->get_item_type_totals(),
			'net_subtotals' => $this->get_item_type_net_subtotals(),
			'tax_subtotals' => $this->get_item_type_subtotals()
		);
	}

	/**
	 * @return bool|TaxRate
	 */
	public function get_tax_rate() {
		return $this->rate ? $this->rate : false;
	}

	/**
	 * @param TaxRate $rate
	 */
	public function set_tax_rate( $rate ) {
		$this->rate = $rate;
	}

	/**
	 * Set item type (e.g. shipping) tax totals for the specific rate.
	 *
	 * @param float[] $totals Array containing item_type => value tax totals.
	 */
	public function set_item_type_totals( $totals ) {
		$this->item_type_totals = array_map( 'sab_format_decimal', $totals );
	}

	/**
	 * Set item type (e.g. shipping) tax totals for the specific rate.
	 *
	 * @param float[] $totals Array containing item_type => value tax totals.
	 */
	public function set_item_type_net_totals( $totals ) {
		$this->item_type_net_totals = array_map( 'sab_format_decimal', $totals );
	}

	/**
	 * Set item type (e.g. shipping) tax totals for the specific rate.
	 *
	 * @param float[] $totals Array containing item_type => value tax totals.
	 */
	public function set_item_type_subtotals( $totals ) {
		$this->item_type_subtotals = array_map( 'sab_format_decimal', $totals );
	}

	/**
	 * Set item type (e.g. shipping) tax totals for the specific rate.
	 *
	 * @param float[] $totals Array containing item_type => value tax totals.
	 */
	public function set_item_type_net_subtotals( $totals ) {
		$this->item_type_net_subtotals = array_map( 'sab_format_decimal', $totals );
	}

	/**
	 * Get item type tax totals in item_type => value pairs.
	 *
	 * @return float[]
	 */
	public function get_item_type_totals() {
		if ( is_null( $this->item_type_totals ) ) {
			$this->calculate_item_type_totals();
		}

		return $this->item_type_totals;
	}

	/**
	 * Get item type tax totals in item_type => value pairs.
	 *
	 * @return float[]
	 */
	public function get_item_type_net_totals() {
		if ( is_null( $this->item_type_net_totals ) ) {
			$this->calculate_item_type_totals();
		}

		return $this->item_type_net_totals;
	}

	/**
	 * Get item type tax totals in item_type => value pairs.
	 *
	 * @return float[]
	 */
	public function get_item_type_subtotals() {
		if ( is_null( $this->item_type_subtotals ) ) {
			$this->calculate_item_type_totals();
		}

		return $this->item_type_subtotals;
	}

	/**
	 * Get item type tax totals in item_type => value pairs.
	 *
	 * @return float[]
	 */
	public function get_item_type_net_subtotals() {
		if ( is_null( $this->item_type_net_subtotals ) ) {
			$this->calculate_item_type_totals();
		}

		return $this->item_type_net_subtotals;
	}

	/**
	 * Add a merged tax item. Merged tax items will be used to recalculate item type totals - will not be stored persistently.
	 *
	 * @param TaxContainable $tax
	 *
	 * @return bool;
	 */
	public function add_tax( $tax ) {

		if ( ! is_a( $tax, '\Vendidero\StoreaBill\Interfaces\TaxContainable' ) ) {
			return false;
		}

		array_push( $this->taxes, $tax );
		$this->calculate_item_type_totals();

		return true;
	}

	/**
	 * Returns item type tax total for a specific item type e.g. shipping.
	 *
	 * @param $type
	 * @return float|int
	 */
	public function get_item_type_total( $type ) {
		$totals = $this->get_item_type_totals();

		return isset( $totals[ $type ] ) ? ( $totals[ $type ] ) : 0;
	}

	/**
	 * Returns item type tax total for a specific item type e.g. shipping.
	 *
	 * @param $type
	 * @return float|int
	 */
	public function get_item_type_net_total( $type ) {
		$totals = $this->get_item_type_net_totals();

		return isset( $totals[ $type ] ) ? ( $totals[ $type ] ) : 0;
	}

	/**
	 * Returns item type tax total for a specific item type e.g. shipping.
	 *
	 * @param $type
	 * @return float|int
	 */
	public function get_item_type_subtotal( $type ) {
		$totals = $this->get_item_type_subtotals();

		return isset( $totals[ $type ] ) ? ( $totals[ $type ] ) : 0;
	}

	/**
	 * Returns item type tax total for a specific item type e.g. shipping.
	 *
	 * @param $type
	 * @return float|int
	 */
	public function get_item_type_net_subtotal( $type ) {
		$totals = $this->get_item_type_net_subtotals();

		return isset( $totals[ $type ] ) ? ( $totals[ $type ] ) : 0;
	}

	/**
	 * Recalculates item type tax totals.
	 */
	private function calculate_item_type_totals() {
		$totals        = array();
		$subtotals     = array();
		$net_totals    = array();
		$net_subtotals = array();

		foreach( $this->taxes as $child ) {

			if ( ! isset( $totals[ $child->get_tax_type() ] ) ) {
				$totals[ $child->get_tax_type() ]     = 0;
				$net_totals[ $child->get_tax_type() ] = 0;
			}

			if ( ! isset( $subtotals[ $child->get_tax_type() ] ) ) {
				$subtotals[ $child->get_tax_type() ]     = 0;
				$net_subtotals[ $child->get_tax_type() ] = 0;
			}

			$totals[ $child->get_tax_type() ]        += (float) $child->get_total_tax();
			$net_totals[ $child->get_tax_type() ]    += (float) $child->get_total_net();

			$subtotals[ $child->get_tax_type() ]     += (float) $child->get_subtotal_tax();
			$net_subtotals[ $child->get_tax_type() ] += (float) $child->get_subtotal_net();
		}

		$this->set_total_tax( array_sum( $totals ) );
		$this->set_total_net( array_sum( $net_totals ) );

		$this->set_subtotal_tax( array_sum( $subtotals ) );
		$this->set_subtotal_net( array_sum( $net_subtotals ) );

		$this->set_item_type_totals( $totals );
		$this->set_item_type_net_totals( $net_totals );

		$this->set_item_type_subtotals( $subtotals );
		$this->set_item_type_net_subtotals( $net_subtotals );
	}

	public function get_taxes() {
		return $this->taxes;
	}
}