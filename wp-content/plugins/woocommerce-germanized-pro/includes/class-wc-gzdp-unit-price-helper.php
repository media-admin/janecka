<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_GZDP_Unit_Price_Helper {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	}

	private function __construct() {
		// Unit auto calculation
		add_action( 'woocommerce_before_product_object_save', array( $this, 'before_product_save' ), 10 );
		add_filter( 'woocommerce_gzd_product_saveable_data', array( $this, 'calculate_unit_price' ), 10, 2 );
		
		add_action( 'woocommerce_bulk_edit_variations', array( $this, 'bulk_save_variations_unit_price' ), 0, 4 );
		add_action( 'woocommerce_product_quick_edit_save', array( $this, 'quick_edit_save_unit_price' ), 0, 1 );
		add_action( 'woocommerce_product_bulk_edit_save', array( $this, 'bulk_edit_save_unit_price' ), 0, 1 );

		// Hook into the product saving (WC > 3.0) and manipulate price after saving
		add_filter( 'woocommerce_gzd_save_display_unit_price_data', array( $this, 'save_display_price' ), 10, 2 );
	}

	public function before_product_save( $product ) {
		$gzd_product = wc_gzd_get_product( $product );

		if ( $gzd_product->get_unit_price_auto() ) {
			$gzd_product->recalculate_unit_price();
		}
	}

	public function save_display_price( $data, $product ) {
		$data = array_merge( $data, $this->get_product_unit_price_data( $product ) );

		return $this->calculate_unit_price( $data, $product );
	}

	public function calculate_unit_price( $data, $post_id ) {
		$product     = ( is_numeric( $post_id ) ? wc_get_product( $post_id ) : $post_id );
		$gzd_product = wc_gzd_get_product( $product );
		$parent      = false;

		$data = wp_parse_args( $data, array(
			'is_rest' => false,
		) );

		$data_replaceable = $data;

		// If it is a REST request, let's insert default product data (if it is missing) before calculation
		if ( $data['is_rest'] ) {
			$insert_defaults = array(
				'unit',
				'unit_base',
				'unit_product',
				'unit_price_auto'
			);

			foreach( $insert_defaults as $default ) {
				if ( ! isset( $data_replaceable["_{$default}"] ) ) {
					$getter = "get_{$default}";

					if ( is_callable( array( $gzd_product, $getter ) ) ) {
						$data_replaceable["_{$default}"] = $gzd_product->$getter();
					}
				}
			}
		}

		// Set inherited values
		if ( $product->is_type( 'variation' ) ) {
			if ( $parent = wc_get_product( $product->get_parent_id() ) ) {
				$gzd_parent_product = wc_gzd_get_product( $parent );

                $inherited = array(
                    'unit',
                    'unit_base',
                    'unit_product',
                );

                foreach ( $inherited as $inherit ) {
                	$getter = "get_{$inherit}";

                    if ( ! isset( $data[ '_' . $inherit ] ) || empty( $data[ '_' . $inherit ] ) ) {
                        $data_replaceable[ '_' . $inherit ] = isset( $data[ '_parent_' . $inherit ] ) ? $data[ '_parent_' . $inherit ] : $gzd_parent_product->$getter();
                    }
                }
            }
		}

		$mandatory = array(
			'_unit_price_auto',
			'_unit',
			'_unit_base',
		);

		foreach ( $mandatory as $mand ) {
			if ( '_unit_price_auto' === $mand ) {
				if ( apply_filters( 'woocommerce_gzdp_force_unit_price_auto_calculation', false, $product ) ) {
					continue;
				}
			}

			if ( ! isset( $data_replaceable[ $mand ] ) || empty( $data_replaceable[ $mand ] ) ) {
				return $data;
			}
		}

		$unit_price_data = wc_gzd_recalculate_unit_price( array(
			'base'     => $data_replaceable['_unit_base'],
			'products' => isset( $data_replaceable['_unit_product'] ) && ! empty( $data_replaceable['_unit_product'] ) ? $data_replaceable['_unit_product'] : 1,
		), $gzd_product );

		$data['_unit_price_regular'] = $unit_price_data['regular'];
		$data['_unit_price_sale']    = '';

		if ( $product->get_sale_price() ) {
			$data['_unit_price_sale'] = $unit_price_data['sale'];
		}

		return $data;
	}

	/**
	 * @param WC_Product $product
	 *
	 * @return array
	 */
	public function get_product_unit_price_data( $product ) {
		$gzd_product = wc_gzd_get_product( $product );

		$unit_data = array(
			'_unit_price_auto'       => $gzd_product->get_unit_price_auto(),
			'_unit_base'             => $gzd_product->get_unit_base(),
			'_unit_product'          => $gzd_product->get_unit_product(),
			'_unit'                  => $gzd_product->get_unit(),
			'_sale_price'            => $product->get_sale_price(),
			'_sale_price_dates_from' => $product->get_date_on_sale_from(),
			'_sale_price_dates_to'   => $product->get_date_on_sale_to(),
			'product-type'           => $product->get_type(),
		);

		return $unit_data;
	}

	public function save_unit_price( $product ) {
		if ( ! $product ) {
			return false;
		}

		if ( method_exists( 'WC_Germanized_Meta_Box_Product_Data', 'save_unit_price' ) ) {
			$id        = $product->get_id();
			$unit_data = $this->get_product_unit_price_data( $product );
			$unit_data = apply_filters( 'woocommerce_gzd_product_saveable_data', $unit_data, $id );

			WC_Germanized_Meta_Box_Product_Data::save_unit_price( $product, $unit_data, $product->is_type( 'variation' ) );
		}
	}

	public function bulk_save_variations_unit_price( $bulk_action, $data, $product_id, $variations ) {
		foreach ( $variations as $variation_id ) {
			$product = wc_get_product( $variation_id );

			if ( $product ) {
				$this->save_unit_price( $product );
			}
		}
	}

	public function quick_edit_save_unit_price( $product ) {
		$this->save_unit_price( $product );
	}

	public function bulk_edit_save_unit_price( $product ) {
		$this->save_unit_price( $product );
	}
}

WC_GZDP_Unit_Price_Helper::instance();