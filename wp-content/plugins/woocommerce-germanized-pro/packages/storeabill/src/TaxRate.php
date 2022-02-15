<?php

namespace Vendidero\StoreaBill;

defined( 'ABSPATH' ) || exit;

/**
 * TaxItem class
 */
class TaxRate {

	protected $data = array();

	protected $defaults = array(
		'percent'       => 0,
		'country'       => '',
		'priority'      => 0,
		'is_compound'   => false,
		'is_oss'        => false,
		'label'         => '',
		'reference_id'  => 0,
		'reference_ids' => array(),
	);

	/**
	 * TaxRate constructor.
	 *
	 * @param array|TaxRate $args
	 */
	public function __construct( $args ) {
		$class_args = array();

		if ( is_array( $args ) ) {
			$class_args = $args;
		} elseif( is_a( $args, 'Vendidero\StoreaBill\TaxRate' ) ) {
			$class_args = $args->get_data();
		}

		$class_args = wp_parse_args( $class_args, $this->defaults );

		foreach( $class_args as $key => $data ) {
			$setter = "set_{$key}";

			if ( is_callable( array( $this, $setter ) ) ) {
				$this->$setter( $data );
			}
		}
	}

	public function get_percent() {
		return $this->data['percent'];
	}

	public function set_percent( $percent ) {
		$this->data['percent'] = floatval( $percent );
	}

	public function get_formatted_percentage() {
		return sab_format_tax_rate_percentage( $this->get_percent() );
	}

	public function get_formatted_percentage_html() {
		return sab_format_tax_rate_percentage( $this->get_percent(), array( 'html' => true ) );
	}

	public function get_label() {
		$label = $this->data['label'];

		if ( empty( $label ) ) {
			$label = sprintf( _x( '%s tax', 'storeabill-core', 'woocommerce-germanized-pro' ), $this->get_formatted_percentage() );
		}

		return $label;
	}

	public function set_label( $label ) {
		$this->data['label'] = $label;
	}

	public function get_country() {
		return $this->data['country'];
	}

	public function set_country( $country ) {
		$this->data['country'] = $country;
	}

	protected function get_reference_id() {
		return $this->data['reference_id'];
	}

	public function set_reference_id( $id ) {
		$this->data['reference_id'] = $id;
	}

	/**
	 * A tax rate might be linked to multiple references
	 * e.g. different order tax classes containing the same percentage.
	 *
	 * @return array
	 */
	public function get_reference_ids() {
		$ids = $this->data['reference_ids'];

		if ( empty( $ids ) && ! empty( $this->data['reference_id'] ) ) {
			$ids = array( $this->data['reference_id'] );
		}

		return $ids;
	}

	public function set_reference_ids( $ids ) {
		$this->data['reference_ids'] = (array) $ids;
	}

	public function get_is_compound() {
		return $this->data['is_compound'];
	}

	public function is_compound() {
		return true === $this->data['is_compound'];
	}

	public function set_is_compound( $compound ) {
		$this->data['is_compound'] = sab_string_to_bool( $compound );
	}

	/**
	 * For legacy purposes: MOSS now turns into OSS
	 *
	 * @return bool
	 */
	public function get_is_moss() {
		return $this->get_is_oss();
	}

	public function get_is_oss() {
		return $this->data['is_oss'];
	}

	public function is_moss() {
		return true === $this->get_is_moss();
	}

	public function is_oss() {
		return true === $this->get_is_oss();
	}

	public function set_is_moss( $is_moss ) {
		$this->set_is_oss( $is_moss );
	}

	public function set_is_oss( $is_oss ) {
		$this->data['is_oss'] = sab_string_to_bool( $is_oss );
	}

	public function get_priority() {
		return $this->data['priority'];
	}

	public function set_priority( $priority ) {
		$this->data['priority'] = absint( $priority );
	}

	public function get_data() {
		$data = $this->data;
		$data['formatted_percentage']      = $this->get_formatted_percentage();
		$data['formatted_percentage_html'] = $this->get_formatted_percentage_html();
		$data['label']                     = $this->get_label();

		return $data;
	}

	public function get_merge_key() {
		return Tax::get_tax_rate_merge_key( array(
			'percent'     => $this->get_percent(),
			'is_compound' => $this->get_is_compound(),
			'is_oss'      => $this->get_is_oss(),
		) );
	}

	/**
	 * @param TaxRate $tax_rate
	 */
	public function equals( $tax_rate ) {
		return $tax_rate->get_merge_key() === $this->get_merge_key();
	}
}