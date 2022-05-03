<?php

namespace Vendidero\Germanized\Pro\Food\Deposits;

use Automattic\WooCommerce\Utilities\NumberUtil;
use Vendidero\StoreaBill\Document\Attribute;
use Vendidero\StoreaBill\Invoice\FeeItem;
use Vendidero\StoreaBill\WooCommerce\OrderItemFee;

defined( 'ABSPATH' ) || exit;

class Helper {

	public static function init() {
		add_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'register_cart_deposits_as_fees' ), 10000 );

        add_filter( 'woocommerce_gzd_skip_order_item_split_tax_calculation', function( $skip_split_tax, $item ) {
            if ( is_a( $item, 'WC_Order_Item_Fee' ) && self::order_fee_is_deposit( $item ) ) {
	            $skip_split_tax = true;
            }

            return $skip_split_tax;
        }, 10, 2 );

        add_filter( 'woocommerce_gzd_skip_fee_split_tax_calculation', function( $skip_split_tax, $fee ) {
	        if ( self::cart_fee_is_deposit( $fee ) ) {
		        $skip_split_tax = true;
	        }

	        return $skip_split_tax;
        }, 10, 2 );

        add_filter( 'woocommerce_order_item_after_calculate_taxes', function( $item, $calculate_tax_for ) {
            if ( is_a( $item, 'WC_Order_Item_Fee' ) && self::order_fee_is_deposit( $item ) ) {
	            self::calculate_order_deposit_prices( $item, $calculate_tax_for );
            }
        }, 10, 2 );

        add_filter( 'woocommerce_cart_totals_get_fees_from_cart_taxes', array( __CLASS__,  'convert_deposit_to_incl_taxes' ), 10, 2 );

        add_action( 'woocommerce_checkout_create_order_fee_item', array( __CLASS__, 'fee_item_save' ), 10, 4 );
        add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'on_checkout_create_order' ), 1500, 2 );

		/**
		 * Make sure to force order deposits recalculation in case items
         * are saved within WC Admin to make sure deposits are adjusted before saving the order.
         *
         * @see wc_save_order_items()
		 */
        add_action( 'woocommerce_before_save_order_items', function( $order_id ) {
            add_action( 'woocommerce_before_order_object_save', function( $order ) {
	            self::recalculate_order_deposits( $order );
            } );
        }, 5 );

        // After saving an order, maybe update the deposit item map to reflect current item ids
        add_action( 'woocommerce_after_order_object_save', array( __CLASS__, 'maybe_update_deposit_item_map' ), 10 );

		add_action( 'woocommerce_after_order_fee_item_name', array( __CLASS__, 'admin_order_deposit_postions' ), 10, 2 );

		/**
		 * Update deposits before (re)calculating order taxes to make sure
         * deposit taxes (net price) is not being mixed during WC_Order::calculate_totals().
		 */
		add_action( 'woocommerce_order_before_calculate_totals', function( $and_taxes, $order ) {
            self::recalculate_order_deposits( $order );
        }, 10, 2 );

        add_action( 'woocommerce_order_after_calculate_totals', array( __CLASS__, 'update_order_deposit_totals' ), 10, 2 );

		add_action( 'storeabill_woo_order_item_fee_synced', array( __CLASS__, 'mark_invoice_fee_as_deposit' ), 10, 3 );

		add_filter( 'woocommerce_gzd_voucher_cart_allow_fee_reduction', array( __CLASS__, 'prevent_deposit_voucher' ), 10, 2 );
		add_filter( 'woocommerce_gzd_voucher_order_allow_fee_reduction', array( __CLASS__, 'prevent_deposit_voucher' ), 10, 2 );
	}

	/**
	 * @param boolean $allow_reduction
	 * @param $fee
	 *
	 * @return boolean
	 */
    public static function prevent_deposit_voucher( $allow_reduction, $fee ) {
        if ( is_a( $fee, 'WC_Order_Item_Fee' ) ) {
            if ( self::order_fee_is_deposit( $fee ) ) {
	            $allow_reduction = false;
            }
        } elseif ( self::cart_fee_is_deposit( $fee ) ) {
	        $allow_reduction = false;
        }

        return $allow_reduction;
    }

	/**
	 * @param \WC_Order $order
	 *
	 * @return void
	 */
	public static function on_checkout_create_order( $order ) {
        self::recalculate_order_deposits( $order );
        self::update_order_deposit_totals( true, $order );
	}

	/**
	 * @param \WC_Order $order
	 *
	 * @return void
	 */
    public static function maybe_update_deposit_item_map( $order ) {
        $deposit_by_tax_classes = self::get_deposit_data_from_order( $order );

        foreach( $order->get_fees() as $fee ) {
            if ( self::order_fee_is_deposit( $fee ) ) {
                if ( array_key_exists( $fee->get_tax_class(), $deposit_by_tax_classes ) ) {
                    $deposit_data = $deposit_by_tax_classes[ $fee->get_tax_class() ];
                    $item_ids     = $deposit_data['item_ids'];

                    if ( $fee->get_meta( '_item_ids' ) !== $item_ids ) {
                        $fee->update_meta_data( '_item_ids', $item_ids );
	                    $fee->update_meta_data( '_positions', self::get_order_fee_item_positions( $fee ) );
                        $fee->save();
                    }
                }
            }
        }
    }

	/**
	 * @param \WC_Order $order
	 *
	 * @return array
	 */
    protected static function get_deposit_data_from_order( $order ) {
	    $deposit_by_tax_classes = array();

	    foreach( $order->get_items( 'line_item' ) as $item_id => $item ) {
            $deposit      = 0;
		    $tax_class    = $item->get_tax_class();
		    $quantity     = $item->get_quantity() > 0 ? $item->get_quantity() : 1;
            $includes_tax = $order->get_prices_include_tax();

		    if ( $gzd_item = wc_gzd_get_order_item( $item ) ) {
                if ( $gzd_item->has_deposit() ) {
	                $deposit = $gzd_item->get_deposit_amount( $includes_tax );
                }
		    } elseif ( $product = $item->get_product() ) {
                $gzd_product = wc_gzd_get_gzd_product( $product );

                if ( $gzd_product->has_deposit() ) {
	                $deposit = $gzd_product->get_deposit_amount( 'view', $includes_tax ? 'incl' : 'excl' );
                }
            }

            if ( $deposit > 0 ) {
	            if ( ! isset( $deposit_by_tax_classes[ $tax_class ] ) ) {
		            $deposit_by_tax_classes[ $tax_class ] = array(
			            'amount'   => 0,
			            'item_ids' => array(),
		            );
	            }

	            $deposit_by_tax_classes[ $tax_class ]['amount']     += ( floatval( $deposit ) * $quantity );
	            $deposit_by_tax_classes[ $tax_class ]['item_ids'][] = $item_id;
	            $deposit_by_tax_classes[ $tax_class ]['item_ids']   = array_unique( $deposit_by_tax_classes[ $tax_class ]['item_ids'] );
            }
	    }

        return $deposit_by_tax_classes;
    }

	/**
	 * @param \WC_Order $order
	 *
	 * @return void
	 */
    public static function recalculate_order_deposits( $order, $and_taxes = true ) {
	    /**
	     * Do not calculate deposits for refunds
	     */
        if ( ! is_a( $order, 'WC_Order' ) ) {
            return;
        }

	    $deposit_by_tax_classes    = self::get_deposit_data_from_order( $order );
	    $fee_items                 = $order->get_fees();
	    $is_vat_exempt             = apply_filters( 'woocommerce_order_is_vat_exempt', 'yes' === $order->get_meta( 'is_vat_exempt' ), $order );
	    $deposit_fees_by_tax_class = array();

	    foreach( $fee_items as $id => $fee_item ) {
		    if ( self::order_fee_is_deposit( $fee_item ) ) {
                // Duplicate detected
                if ( array_key_exists( $fee_item->get_tax_class(), $deposit_fees_by_tax_class ) ) {
                    $order->remove_item( $id );
                    continue;
                }

			    $deposit_fees_by_tax_class[ $fee_item->get_tax_class() ] = array(
				    'item' => $fee_item,
				    'id'   => $id,
			    );
		    }
	    }

	    /**
	     * Remove deposits which no longer exist
	     */
	    foreach( $deposit_fees_by_tax_class as $fee_tax_class => $fee_data ) {
		    if ( ! isset( $deposit_by_tax_classes[ $fee_tax_class ] ) ) {
			    $order->remove_item( $fee_data['id'] );
		    }
	    }

	    /**
	     * Update deposit fees
	     */
	    foreach( $deposit_by_tax_classes as $tax_class => $deposit_data ) {
		    $is_new  = false;
		    $deposit = $deposit_data['amount'];

		    if ( ! array_key_exists( $tax_class, $deposit_fees_by_tax_class ) ) {
			    $is_new = true;

			    $fee_item = new \WC_Order_Item_Fee();
			    $fee_item->update_meta_data( '_is_deposit', 'yes' );
		    } else {
			    $fee_item = $deposit_fees_by_tax_class[ $tax_class ]['item'];
		    }

            $fee_item->set_name( apply_filters( 'woocommerce_gzdp_order_item_deposit_name', __( 'Deposit', 'woocommerce-germanized-pro' ) ) );
		    $fee_item->set_amount( $deposit );
            $fee_item->set_total( $deposit );
		    $fee_item->set_tax_status( ( wc_tax_enabled() && ! $is_vat_exempt ) ? 'taxable' : 'none' );
		    $fee_item->set_tax_class( $tax_class );
		    $fee_item->update_meta_data( '_item_ids', $deposit_data['item_ids'] );
		    $fee_item->update_meta_data( '_positions', self::get_order_fee_item_positions( $fee_item ) );

		    if ( $is_new ) {
			    $order->add_item( $fee_item );
		    }

		    /**
		     * Maybe treat deposit as incl tax
		     */
		    if ( $and_taxes || $fee_item->get_id() <= 0 ) {
			    self::calculate_order_deposit_prices( $fee_item, array(), $order );
		    }
	    }
    }

	/**
	 * @param $item_id
	 * @param \WC_Order_Item_Fee $item
	 *
	 * @return void
	 */
	public static function admin_order_deposit_postions( $item_id, $item ) {
		if ( self::order_fee_is_deposit( $item ) && $item->get_meta( '_positions', true ) ) {
			?>
			<div class="view">
				<table class="display_meta" cellspacing="0">
					<tbody>
						<tr>
							<th><?php _e( 'Positions:', 'woocommerce-germanized-pro' ); ?></th>
							<td><?php echo wp_kses_post( force_balance_tags( $item->get_meta( '_positions', true ) ) ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
			<?php
		}
	}

	/**
	 * @param OrderItemFee $order_item_fee
	 * @param FeeItem $document_item
	 * @param $args
	 *
	 * @return void
	 */
	public static function mark_invoice_fee_as_deposit( $order_item_fee, $document_item, $args ) {
		if ( 'yes' === $order_item_fee->get_meta( '_is_deposit' ) ) {
			$document_item->set_enable_split_tax( false );
			$document_item->update_meta_data( '_is_deposit', 'yes' );

            if ( $order_item_fee->get_meta( '_item_ids' ) ) {
                $item_ids             = array_filter( (array) $order_item_fee->get_meta( '_item_ids' ) );
                $document_items_names = array();

                foreach( $item_ids as $item_id ) {
                    if ( $document = $document_item->get_document() ) {
                        if ( $ref_item = $document->get_item_by_reference_id( $item_id ) ) {
	                        $document_items_names[] = $ref_item->get_name() . ' &times; ' . $ref_item->get_quantity();
                        }
                    }
                }

                if ( ! empty( $document_items_names ) ) {
	                $attribute_props = array(
		                'key'   => 'position',
		                'value' => implode( ', ', $document_items_names ),
		                'label' => __( 'Positions', 'woocommerce-germanized-pro' ),
		                'order' => 1,
	                );

	                if ( ! $attribute = $document_item->get_attribute( 'positions' ) ) {
		                $document_item->add_attribute( new Attribute( $attribute_props ) );
	                } else {
		                $attribute->set_props( $attribute_props );
	                }
                } else {
                    $document_item->remove_attribute( 'positions' );
                }
            }
		}
	}

	/**
	 * @param boolean $and_taxes
	 * @param \WC_Order $order
	 *
	 * @return void
	 */
    public static function update_order_deposit_totals( $and_taxes, $order ) {
        $deposit_total = 0;
        $deposit_tax   = 0;

	    foreach ( $order->get_fees() as $item ) {
		    if ( self::order_fee_is_deposit( $item ) ) {
			    $deposit_total += $item->get_total();

			    foreach( $item->get_taxes()['total'] as $tax ) {
				    $deposit_tax += (float) $tax;
			    }
		    }
	    }

        if ( $deposit_total > 0 ) {
	        $order->update_meta_data( '_deposit_total', NumberUtil::round( $deposit_total, wc_get_price_decimals() ) );
	        $order->update_meta_data( '_deposit_tax', wc_round_tax_total( $deposit_tax ) );
        } else {
	        $order->delete_meta_data( '_deposit_total' );
	        $order->delete_meta_data( '_deposit_tax' );
        }
    }

	/**
	 * @param \WC_Order_Item_Fee $fee_item
	 *
	 * @return string
	 */
	public static function get_order_fee_item_positions( $fee_item ) {
		$item_descriptions = array();

		if ( $fee_item->get_meta( '_item_ids' ) && ( $order = $fee_item->get_order() ) ) {
			$item_ids = array_filter( (array) $fee_item->get_meta( '_item_ids' ) );

			foreach( $item_ids as $item_id ) {
				if ( $item = $order->get_item( $item_id ) ) {
					$item_descriptions[] = $item->get_name() . ' &times; ' . $item->get_quantity();
				}
			}
		}

		return implode( ', ', $item_descriptions );
	}

	/**
	 * Mark order item as deposit.
	 *
	 * @param \WC_Order_Item_Fee $item
	 * @param $fee_key
	 * @param object $fee
	 * @param \WC_Order $order
	 */
	public static function fee_item_save( $item, $fee_key, $fee, $order ) {
		if ( self::cart_fee_is_deposit( $fee ) ) {
			$item->update_meta_data( '_is_deposit', 'yes' );
			$item->update_meta_data( '_prices_include_tax', ( isset( $fee->prices_include_tax ) ? wc_bool_to_string( $fee->prices_include_tax ) : 'no' ) );
        }
	}

	public static function cart_fee_is_deposit( $fee ) {
		$id = isset( $fee->object ) ? $fee->object->id : $fee->id;

		return 'deposit_' === substr( $id, 0, 8 );
	}

	/**
	 * @param \WC_Order_Item_Fee $fee
	 *
	 * @return bool
	 */
	public static function order_fee_is_deposit( $fee ) {
		return 'yes' === $fee->get_meta( '_is_deposit' );
	}

	/**
	 * @param \WC_Order_Item_Fee $item
	 *
	 * @return void
	 */
    public static function calculate_order_deposit_prices( $item, $calculate_tax_for = array(), $order = false ) {
        if ( self::order_fee_is_deposit( $item ) ) {
            $order             = ! $order ? $item->get_order() : $order;
            $calculate_tax_for = ( empty( $calculate_tax_for ) && $order ) ? \WC_GZD_Admin_Order::instance()->get_order_taxable_location( $order ) : $calculate_tax_for;

            if ( $order && wc_tax_enabled() && $order->get_prices_include_tax() && '0' !== $item->get_tax_class() && 'taxable' === $item->get_tax_status() ) {
	            $is_vat_exempt = apply_filters( 'woocommerce_order_is_vat_exempt', 'yes' === $order->get_meta( 'is_vat_exempt' ), $order );
	            $item_total    = (float) $item->get_amount();

                if ( isset( $calculate_tax_for['country'] ) ) {
                    $calculate_tax_for['tax_class'] = $item->get_tax_class();

	                $tax_rates = \WC_Tax::find_rates( $calculate_tax_for );
                } else {
	                $tax_rates = \WC_Tax::get_rates_from_location( $item->get_tax_class(), $calculate_tax_for );
                }

	            /**
	             * In case the customer is a VAT exempt - use customer's tax rates to find the fee net price.
	             */
	            if ( $is_vat_exempt ) {
		            $tax_rates = \WC_Tax::get_rates( $item->get_tax_class() );
	            }

	            $tax_class_taxes = \WC_Tax::calc_tax( $item_total, $tax_rates, true );

	            $item->set_taxes( array( 'total' => $tax_class_taxes ) );

	            /**
	             * Adjust the net total amount based on current tax calculation
	             */
	            $item->set_total( $item_total - $item->get_total_tax() );

	            /**
	             * Remove taxes in case of a VAT exempt
	             */
	            if ( $is_vat_exempt ) {
		            $item->set_taxes( false );
	            }
            }
        }
    }

    public static function convert_deposit_to_incl_taxes( $taxes, $fee ) {
        if ( self::cart_fee_is_deposit( $fee ) ) {
            if ( wc_prices_include_tax() ) {
                $rates = \WC_Tax::get_rates( $fee->tax_class, WC()->cart->get_customer() );

	            /**
	             * In case the customer is a VAT exempt - use customer's tax rates
	             * to find the fee net price.
	             */
	            if ( WC()->customer->is_vat_exempt() ) {
		            $rates = \WC_Tax::get_rates( $fee->tax_class );
	            }

	            $taxes = \WC_Tax::calc_tax( $fee->total, $rates, true );

	            $total_tax = array_sum( array_map( function( $value ) {
		            if ( 'yes' !== get_option( 'woocommerce_tax_round_at_subtotal' ) ) {
			            $value = wc_round_tax_total( $value, 0 );
		            }

		            return $value;
	            }, $taxes ) );

	            $fee->total = $fee->total - $total_tax;
            }
        }

        return $taxes;
    }

	public static function register_cart_deposits_as_fees() {
		$deposit_by_tax_classes = array();

		foreach ( WC()->cart->get_cart_contents() as $cart_item_key => $values ) {
			if ( $values['data'] && ( $deposit = self::get_product_deposit( $values['data'] ) ) ) {
				$tax_class = $values['data']->get_tax_class();
				$quantity  = $values['quantity'] > 0 ? $values['quantity'] : 1;

				if ( ! isset( $deposit_by_tax_classes[ $tax_class ] ) ) {
					$deposit_by_tax_classes[ $tax_class ] = 0;
				}

				$deposit_by_tax_classes[ $tax_class ] += ( floatval( $deposit ) * $quantity );
			}
		}

		foreach( $deposit_by_tax_classes as $tax_class => $deposit_total ) {
			WC()->cart->fees_api()->add_fee( array(
				'name'               => apply_filters( 'woocommerce_gzdp_cart_deposit_name', __( 'Deposit', 'woocommerce-germanized-pro' ) ),
				'amount'             => $deposit_total,
				'taxable'            => true,
				'id'                 => "deposit_{$tax_class}",
				'tax_class'          => $tax_class,
			) );
		}
	}

	/**
	 * @param \WC_Product|\WC_GZD_Product|integer $product
	 *
	 * @return string
	 */
	public static function get_product_deposit( $product, $incl_tax = null ) {
		$amount   =  wc_format_decimal( 0 );
        $incl_tax = is_null( $incl_tax ) ? wc_prices_include_tax() : (bool) $incl_tax;

		if ( $product = wc_gzd_get_gzd_product( $product ) ) {
			$amount = $product->get_deposit_amount( 'view', true === $incl_tax ? 'incl' : 'excl' );
		}

		return $amount;
	}

	public static function create_default_deposit_types( $delete_first = false ) {
		if ( $delete_first ) {
			$terms = get_terms( array(
				'taxonomy'   => 'product_deposit_type',
				'hide_empty' => false
			) );

			foreach ( $terms as $term ) {
				wp_delete_term( $term->term_id, 'product_deposit_type' );
			}
		}

		foreach ( self::get_default_deposit_types() as $deposit_type_data ) {
			$deposit_type_data = wp_parse_args( $deposit_type_data, array(
				'title'          => '',
				'deposit'        => 0,
                'slug'           => '',
				'packaging_type' => 'reusable',
            ) );

			$slug = empty( $deposit_type_data['slug'] ) ? sanitize_title( $deposit_type_data['title'] ) : sanitize_title( $deposit_type_data['slug'] );

			if ( isset( WC_germanized()->deposit_types ) && ! WC_germanized()->deposit_types->get_deposit_type_term( $slug ) ) {
				$term = wp_insert_term( $deposit_type_data['title'], 'product_deposit_type', array( 'slug' => $slug ) );

                if ( ! is_wp_error( $term ) ) {
                    update_term_meta( $term['term_id'], 'deposit', wc_format_decimal( $deposit_type_data['deposit'], '' ) );
	                update_term_meta( $term['term_id'], 'deposit_packaging_type', $deposit_type_data['packaging_type'] );
                }
			}
		}
	}

	public static function get_default_deposit_types() {
        return array(
            array(
                'title'          => _x( 'Beer bottle', 'deposit-type', 'woocommerce-germanized-pro' ),
                'slug'           => 'beer-bottle',
                'deposit'        => '0.08',
                'packaging_type' => 'reusable',
            ),
	        array(
		        'title'          => _x( 'Beer bottle with swing stopper', 'deposit-type', 'woocommerce-germanized-pro' ),
                'slug'           => 'beer-bottle-with-swing-stopper',
		        'deposit'        => '0.15',
		        'packaging_type' => 'reusable',
	        ),
	        array(
		        'title'          => _x( 'Water bottle', 'deposit-type', 'woocommerce-germanized-pro' ),
		        'slug'           => 'water-bottle',
		        'deposit'        => '0.15',
		        'packaging_type' => 'reusable',
	        ),
            array(
		        'title'          => _x( 'Bottle juice & soft drinks', 'deposit-type', 'woocommerce-germanized-pro' ),
                'slug'           => 'bottle-juice-soft-drinks',
		        'deposit'        => '0.15',
		        'packaging_type' => 'reusable',
	        ),
	        array(
		        'title'          => _x( 'Bottle', 'deposit-type', 'woocommerce-germanized-pro' ),
                'slug'           => 'bottle',
		        'deposit'        => '0.25',
		        'packaging_type' => 'disposable',
	        ),
	        array(
		        'title'          => _x( 'Can', 'deposit-type', 'woocommerce-germanized-pro' ),
                'slug'           => 'can',
		        'deposit'        => '0.25',
		        'packaging_type' => 'disposable',
	        ),
        );
	}
}