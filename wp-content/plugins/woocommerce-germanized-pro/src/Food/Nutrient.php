<?php

namespace Vendidero\Germanized\Pro\Food;

defined( 'ABSPATH' ) || exit;

class Nutrient {

	/**
	 * @var \WP_Term
	 */
	private $term = null;

	/**
	 * @var Nutrient[]
	 */
	private $children = null;

	/**
	 * @var \WP_Term
	 */
	private $unit = null;

	/**
	 * @param \WP_Term $term
	 */
	public function __construct( $term, $children = array() ) {
		if ( ! is_a( $term, 'WP_Term' ) ) {
			$term = get_term_by( is_numeric( $term ) ? 'id' : 'slug', $term, 'product_nutrient' );

			if ( ! is_a( $term, 'WP_Term' ) ) {
				throw new \Exception( __( 'This nutrient does not exist', 'woocommerce-germanized-pro' ) );
			}
		}

		$this->term = $term;

		if ( ! empty( $children ) ) {
			$this->set_children( $children );
		}
	}

	public function has_name_prefix() {
		return ! empty( $this->get_name_prefix() );
	}

	public function get_name_prefix() {
		$prefix = '';

		if ( $this->has_parent() && ! $this->is_vitamin() ) {
			$prefix = _x( 'of which', 'nutrient-prefix', 'woocommerce-germanized-pro' );
		}

		return apply_filters( 'woocommerce_gzdp_nutrient_name_prefix', $prefix, $this );
	}

	public function get_name() {
		return apply_filters( 'woocommerce_gzdp_nutrient_name', $this->term->name, $this );
	}

	public function get_id() {
		return $this->term->term_id;
	}

	public function get_slug() {
		return $this->term->slug;
	}

	public function get_meta( $key, $single = true ) {
		return get_term_meta( $this->get_id(), $key, $single );
	}

	public function update_meta_data( $key, $value ) {
		return update_term_meta( $this->get_id(), $key, $value );
	}

	public function get_unit_term( $context = 'view' ) {
		if ( is_null( $this->unit ) ) {
			$this->unit = WC_germanized()->units->get_unit_term( $this->get_unit_id(), 'id' );
		}

		if ( is_wp_error( $this->unit ) ) {
			$this->unit = false;
		}

		$unit = $this->unit;

		/**
		 * Fallback to parent unit
		 */
		if ( 'view' === $context && ! $unit && ( $parent = $this->get_parent() ) ) {
			$unit = $parent->get_unit_term();
		}

		return $unit;
	}

	public function has_parent() {
		return $this->term->parent > 0;
	}

	public function get_parent() {
		$parent = false;

		if ( $this->has_parent() ) {
			$parent = Helper::get_nutrient( $this->term->parent );
		}

		return $parent;
	}

	public function is_parent() {
		return 0 === $this->get_parent_id();
	}

	public function get_unit() {
		$name = Helper::get_default_nutrient_unit();

		if ( $unit = $this->get_unit_term() ) {
			$name = $unit->name;
		}

		return apply_filters( 'woocommerce_gzdp_nutrient_unit', $name, $unit, $this );
	}

	public function get_type( $context = 'view' ) {
		$type = $this->get_meta( '_nutrient_type' );

		if ( ! $type || ! array_key_exists( $type, Helper::get_nutrient_types() ) ) {
			$type = 'numeric';
		}

		if ( 'view' === $context && 'vitamins' === $type && $this->is_parent() ) {
			$type = 'title';
		}

		return apply_filters( 'woocommerce_gzdp_nutrient_type', $type, $this );
	}

	public function get_parent_id() {
		$parent_id = 0;

		if ( $this->term->parent > 0 ) {
			$parent_id = absint( $this->term->parent );
		}

		return $parent_id;
	}

	public function is_vitamin() {
		$is_vitamin = 'vitamins' === $this->get_type( 'edit' );

		/**
		 * Fallback to parent type
		 */
		if ( $parent = $this->get_parent() ) {
			$is_vitamin = $parent->is_vitamin();
		}

		return apply_filters( 'woocommerce_gzdp_is_vitamin', $is_vitamin, $this );
	}

	public function get_unit_id() {
		$unit = $this->get_meta( '_unit' );
		$unit = '' === $unit ? 0 : absint( $unit );

		return $unit;
	}

	public function get_rounding_rule_slug( $context = 'view' ) {
		$rounding_rule = $this->get_meta( '_rounding_rule' );

		/**
		 * Fallback to parent rule
		 */
		if ( 'view' === $context && ! $rounding_rule && ( $parent = $this->get_parent() ) ) {
			$rounding_rule = $parent->get_rounding_rule_slug();
		}

		return $rounding_rule;
	}

	public function set_unit_id( $unit ) {
		$this->update_meta_data( '_unit', absint( $unit ) );
	}

	public function set_order( $order ) {
		Helper::set_nutrient_order( $this->get_id(), (int) $order );
		$this->term->order = (int) $order;
	}

	/**
	 * @param \WP_Term[]|Nutrient[] $children
	 */
	public function set_children( $children ) {
		if ( ! empty( $children ) && ! is_wp_error( $children ) ) {
			if ( is_a( $children[0], 'WP_Term' ) ) {
				foreach( $children as $key => $child ) {
					try {
						$children[ $key ] = new Nutrient( $child );
					} catch( \Exception $e ) {
						continue;
					}
				}
			}

			$this->children = $children;
		}
	}

	/**
	 * @param \WP_Term|Nutrient $child
	 */
	public function add_child( $child ) {
		if ( is_a( $child, 'WP_Term' ) ) {
			try {
				$child = new Nutrient( $child );
			} catch( \Exception $e ) {
				return false;
			}
		}

		$this->children[] = $child;

		return true;
	}

	public function set_rounding_rule_slug( $slug ) {
		if ( '' === $slug || ! array_key_exists( $slug, Helper::get_nutrient_rounding_rules() ) ) {
			delete_term_meta( $this->get_id(), '_rounding_rule' );
		} else {
			$this->update_meta_data( '_rounding_rule', $slug );
		}
	}

	public function set_type( $type ) {
		if ( '' === $type || ! array_key_exists( $type, Helper::get_nutrient_types() ) ) {
			$this->update_meta_data( '_nutrient_type', 'numeric' );
		} else {
			$this->update_meta_data( '_nutrient_type', $type );
		}
	}

	public function get_rounding_rule() {
		$slug = $this->get_rounding_rule_slug();
		$rule = array();

		if ( ! empty( $slug ) ) {
			$nutrient_rules = Helper::get_nutrient_rounding_rules();

			if ( array_key_exists( $slug, $nutrient_rules ) ) {
				$rule = $nutrient_rules[ $slug ];
			}
		}

		$rule = (array) apply_filters( 'woocommerce_gzdp_nutrient_rounding_rule', $rule, $slug, $this );

		return wp_parse_args( $rule, array(
			'title'       => '',
			'description' => '',
			'rules'       => array(),
		) );
	}

	public function get_rounding_rules() {
		return (array) apply_filters( 'woocommerce_gzdp_nutrient_rounding_rules', $this->get_rounding_rule()['rules'], $this );
	}

	/**
	 * @param float $value
	 *
	 * @return string
	 */
	public function round( $value ) {
		$value = (float) wc_format_decimal( $value, 4 );

		if ( $value <= 0 ) {
			return $value;
		}

		foreach( $this->get_rounding_rules() as $rule ) {
			$is_valid = true;

			$rule = wp_parse_args( $rule, array(
				'min'      => 0,
				'max'      => -1,
				'decimals' => 0,
				'prefix'   => '',
			) );

			if ( $rule['min'] != 0 && $value <= $rule['min'] ) {
				$is_valid = false;
			}

			if ( $rule['max'] != -1 && $value > $rule['max'] ) {
				$is_valid = false;
			}

			if ( $is_valid ) {
				/**
				 * Some rules include a prefix, e.g. < which indicates that
				 * the current value has a too-high precision.
				 */
				if ( ! empty( $rule['prefix'] ) && '<' === $rule['prefix'] ) {
					$value = wc_format_decimal( $value, $rule['decimals'] );

					if ( $value < $rule['max'] ) {
						$value = $rule['max'];
					}
				}

				$value = ( ! empty( $rule['prefix'] ) ? esc_html( $rule['prefix'] ) . ' ' : '' ) . wc_format_localized_decimal( wc_format_decimal( $value, $rule['decimals'], apply_filters( "woocommerce_gzdp_nutrient_round_trim_zeros", true ) ) );
				break;
			}
		}

		return $value;
	}

	/**
	 * @return Nutrient[]
	 */
	public function get_children() {
		$children = array();

		if ( is_null( $this->children ) && $this->is_parent() ) {
			$children_terms = get_terms( 'product_nutrient', array(
				'orderby'    => 'order',
				'depth'      => 1,
				'parent'     => $this->get_parent_id(),
				'order'      => 'ASC',
				'hide_empty' => false,
			) );

			if ( ! is_wp_error( $children_terms ) ) {
				$this->set_children( $children_terms );
			}
		}

		if ( ! is_null( $this->children ) ) {
			$children = $this->children;
		}

		return $children;
	}

	public function __toString() {
		return sprintf( '%1$s', ( $this->has_name_prefix() ? $this->get_name_prefix() . ' ' : '' ) . $this->get_name() );
	}
}