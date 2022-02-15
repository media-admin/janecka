<?php

namespace Vendidero\Germanized\DPD\Label;

use Vendidero\Germanized\DPD\Package;
use Vendidero\Germanized\Shipments\Labels\Label;

defined( 'ABSPATH' ) || exit;

/**
 * DPD ReturnLabel class.
 */
class Simple extends Label {

	/**
	 * Stores product data.
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'mps_id'        => '',
		'page_format'   => '',
		'customs_terms' => '',
		'customs_paper' => '',
		'pickup_date'   => '',
	);

	public function get_type() {
		return 'simple';
	}

	public function get_page_format( $context = 'view' ) {
		return $this->get_prop( 'page_format', $context );
	}

	public function get_customs_terms( $context = 'view' ) {
		return $this->get_prop( 'customs_terms', $context );
	}

	public function get_pickup_date( $context = 'view' ) {
		return $this->get_prop( 'pickup_date', $context );
	}

	public function get_customs_paper( $context = 'view' ) {
		return $this->get_prop( 'customs_paper', $context );
	}

	public function set_page_format( $value ) {
		$this->set_prop( 'page_format', $value );
	}

	public function set_customs_terms( $value ) {
		$this->set_prop( 'customs_terms', $value );
	}

	public function set_customs_paper( $value ) {
		$this->set_prop( 'customs_paper', $value );
	}

	public function set_pickup_date( $date ) {
		$this->set_prop( 'pickup_date', $date );
	}

	public function get_shipping_provider( $context = 'view' ) {
		return 'dpd';
	}

	public function get_mps_id( $context = 'view' ) {
		return $this->get_prop( 'mps_id', $context );
	}

	public function set_mps_id( $mpn ) {
		$this->set_prop( 'mps_id', $mpn );
	}

	/**
	 * @return \WP_Error|true
	 */
	public function fetch() {
		$result = Package::get_api()->get_label( $this );

		return $result;
	}

	public function delete( $force_delete = false ) {
		return parent::delete( $force_delete );
	}
}