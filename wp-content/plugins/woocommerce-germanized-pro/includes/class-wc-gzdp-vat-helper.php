<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_GZDP_VAT_Helper {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {
		
		if ( ! is_admin() || defined( 'DOING_AJAX' ) ) {
			$this->frontend_hooks();
        }
		
		add_filter( 'woocommerce_localisation_address_formats', array( $this, 'set_vat_field' ), 0, 1 );
		add_filter( 'woocommerce_default_address_fields', array( $this, 'add_vat_field' ), 0, 1 );

		add_filter( 'woocommerce_order_formatted_billing_address', array( $this, 'set_formatted_billing_address' ), 0, 2 );
		add_filter( 'woocommerce_order_formatted_shipping_address', array( $this, 'set_formatted_shipping_address' ), 0, 2 );

		add_filter( 'woocommerce_admin_billing_fields', array( $this, 'set_admin_billing_address' ), 0, 1 );
		add_filter( 'woocommerce_admin_shipping_fields', array( $this, 'set_admin_shipping_address' ), 0, 1 );

		add_filter( 'woocommerce_get_country_locale', array( $this, 'hide_vat_field' ), 0, 1 );
		add_filter( 'woocommerce_country_locale_field_selectors', array( $this, 'hide_vat_field_js' ), 0, 1 );
		add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'add_vat_address' ), 0, 2 );

		add_filter( 'woocommerce_customer_meta_fields', array( $this, 'add_vat_field_profile' ), 0, 1 );

		add_action( 'edit_user_profile_update', array( $this, 'save_billing_vat_id_field_profile' ), 5 );
		add_action( 'edit_user_profile_update', array( $this, 'save_shipping_vat_id_field_profile' ), 5 );

		add_action( 'personal_options_update', array( $this, 'save_billing_vat_id_field_profile' ), 5 );
		add_action( 'personal_options_update', array( $this, 'save_shipping_vat_id_field_profile' ), 5 );

		// Save VAT result data from transient to corresponding order
        add_action( 'woocommerce_checkout_create_order', array( $this, 'save_vat_result_data' ), 10, 1 );
        /*
         * Some payment gateways (e.g. Amazon pay) retrieve order data within process_payment method.
         * Use this Germanized hook which fires right before confirming the order to make sure we process the order again after the data might be available.
         */
        add_action( 'woocommerce_gzd_checkout_order_before_confirmation', array( $this, 'save_vat_result_data_after_payment' ), 10, 1 );

        add_filter( 'woocommerce_ajax_get_customer_details', array( $this, 'customer_details_load_vat_id' ), 10, 3 );

		add_filter( 'woocommerce_ajax_calc_line_taxes', array( $this, 'calc_order_taxes' ), 0, 4 );
		add_action( 'woocommerce_before_order_object_save', array( $this, 'calc_order_taxes_v3' ), 10, 2 );
		add_filter( 'woocommerce_ajax_get_customer_details', array( $this, 'load_customer_vat_id' ), 10, 3 );

		add_filter( 'woocommerce_checkout_fields', array( $this, 'checkout_field_required' ), 10, 1 );

		// Add VAT result meta box
        add_action( 'add_meta_boxes', array( $this, 'add_vat_result_meta_box' ), 30 );

		// New Woo Order vat exempt filter
		add_filter( 'woocommerce_order_is_vat_exempt', array( $this, 'order_has_vat_exempt_filter' ), 10, 2 );

		add_action( 'woocommerce_checkout_update_customer', array( $this, 'maybe_prevent_customer_data_update' ), 10, 2 );

		// Add VAT ID to order address data
		add_filter( 'woocommerce_get_order_address', array( $this, 'add_order_address_data' ), 10, 3 );
	}

	public function add_order_address_data( $data, $type, $order ) {
        if ( 'shipping' === $type && ( $vat_id = $this->get_order_shipping_vat_id( $order ) ) ) {
            $data['vat_id'] = $vat_id;
        } elseif ( 'billing' === $type && ( $vat_id = $this->get_order_billing_vat_id( $order ) ) ) {
			$data['vat_id'] = $vat_id;
		}

		return $data;
	}

	/**
     * By default Woo updates the customer with the new address data.
     * In case shipping to a different address is activated, the billing_vat_id is removed (and vice-versa with shipping_vat_id).
     * This leads to removal of customer data (which is not expected behaviour). Instead load the old vat id and refresh the customer.
     *
	 * @param WC_Customer $customer
	 * @param array $data
	 */
	public function maybe_prevent_customer_data_update( $customer, $data ) {
	    if ( isset( $data['ship_to_different_address'] ) ) {

	        if ( false === $data['ship_to_different_address'] ) {
		        $old_shipping_vat_id = get_user_meta( $customer->get_id(), 'shipping_vat_id', true );

		        if ( ! empty( $old_shipping_vat_id ) ) {
			        $customer->update_meta_data( 'shipping_vat_id', $old_shipping_vat_id );
		        }
            } else {
		        $old_billing_vat_id = get_user_meta( $customer->get_id(), 'billing_vat_id', true );

		        if ( ! empty( $old_billing_vat_id ) ) {
			        $customer->update_meta_data( 'billing_vat_id', $old_billing_vat_id );
		        }
            }
        }
    }

	/**
     * Make sure to check whether shipping to a different address is activated and
     * un-require the field which is not checked (e.g. billing_vat_id).
     *
	 * @param $fields
	 *
	 * @return mixed
	 */
	public function checkout_field_required( $fields ) {
	    if ( WC()->checkout()->get_value( 'ship_to_different_address' ) ) {
	        if ( isset( $fields['billing']['billing_vat_id'] ) ) {
		        $fields['billing']['billing_vat_id']['required'] = false;
            }
        } else {
	        if ( isset( $fields['shipping']['shipping_vat_id'] ) ) {
		        $fields['shipping']['shipping_vat_id']['required'] = false;
            }
        }

	    return $fields;
    }

	public function add_vat_result_meta_box() {
	    global $post;

        if ( $post && is_a( $post, 'WP_Post' ) && 'shop_order' === $post->post_type ) {
            $order = wc_get_order( $post );

            if ( $order && $this->get_order_vat_id( $order ) && $this->get_order_vat_result_data( $order ) ) {
                add_meta_box( 'woocommerce-gzdp-vat-result', __( 'VAT ID validation result', 'woocommerce-germanized-pro' ), array( $this, 'vat_result_meta_box' ), 'shop_order', 'side' );
            }
        }
	}

    public function vat_result_meta_box() {
	    global $post;

	    if ( $order = wc_get_order( $post ) ) {

	        $result           = $this->get_order_vat_result_data( $order );
            $qualified        = isset( $result['qualified'] ) ? (array) $result['qualified'] : array();
		    $qualified_titles = array(
			    'company'  => __( 'Company', 'woocommerce-germanized-pro' ),
			    'postcode' => __( 'Postcode', 'woocommerce-germanized-pro' ),
			    'city'     => __( 'City', 'woocommerce-germanized-pro' )
            );
		    $qualified_current = array();

            foreach( $qualified as $q ) {
                $qualified_current[] = isset( $qualified_titles[ $q ] ) ? $qualified_titles[ $q ] : '';
            }
            ?>
            <ul class="vat-data">
                <li><strong><?php _e( 'VAT ID', 'woocommerce-germanized-pro' ); ?></strong> <?php echo esc_attr( $this->get_order_vat_id( $order ) ); ?></li>
                <?php if ( ! empty( $result['name'] ) ) : ?>
                    <li><strong><?php _e( 'Name', 'woocommerce-germanized-pro' ); ?></strong> <?php echo esc_attr( $result['name'] ); ?></li>
                <?php endif; ?>
                <?php if ( ! empty( $result['identifier'] ) ) : ?>
                    <li><strong><?php _e( 'Request ID', 'woocommerce-germanized-pro' ); ?></strong> <?php echo esc_attr( $result['identifier'] ); ?></li>
                <?php endif; ?>
                <?php if ( ! empty( $result['company'] ) ) : ?>
                    <li><strong><?php _e( 'Company', 'woocommerce-germanized-pro' ); ?></strong> <?php echo esc_attr( $result['company'] ); ?></li>
                <?php endif; ?>
                <?php if ( ! empty( $result['address'] ) ) : ?>
                    <li><strong><?php _e( 'Address', 'woocommerce-germanized-pro' ); ?></strong> <?php echo esc_attr( $result['address'] ); ?></li>
                <?php endif; ?>
                <?php if ( ! empty( $result['date'] ) ) :
                    if ( class_exists( 'WC_DateTime' ) ) {
                        $date = new WC_DateTime( $result['date'] );
                        $result['date'] = $date->date_i18n( 'd.m.Y H:i' );
                    }
                    ?>
                    <li><strong><?php _e( 'Date', 'woocommerce-germanized-pro' ); ?></strong> <?php echo esc_attr( $result['date'] ); ?></li>
                <?php endif; ?>
	            <?php if ( isset( $result['is_qualified'] ) && $result['is_qualified'] ) : ?>
                    <li><strong><?php _e( 'Qualified', 'woocommerce-germanized-pro' ); ?> <span class="woocommerce-help-tip" style="margin-left: 3px; top: 2px;" data-tip="<?php printf( __( 'Successfully checked fields: %s', 'woocommerce-germanized-pro' ), implode( ', ', $qualified_current ) ); ?>"></span></strong> <span class="dashicons dashicons-yes"></span></li>
	            <?php endif; ?>
            </ul>
            <?php
        }
    }

	/**
	 * @param WC_Order $order
	 */
	public function save_vat_result_data( $order ) {
	    if ( ( $vat_id = $this->get_order_vat_id( $order ) ) && ! $order->get_meta( '_vat_id_validation_result', true ) ) {
	        $order->update_meta_data( '_vat_id_validation_result', $this->get_vat_result_data( $vat_id, $order ) );
	    }
    }

	/**
	 * @param WC_Order $order
	 */
    public function save_vat_result_data_after_payment( $order ) {
	    if ( ( $vat_id = $this->get_order_vat_id( $order ) ) && ! $order->get_meta( '_vat_id_validation_result', true ) ) {
		    $order->update_meta_data( '_vat_id_validation_result', $this->get_vat_result_data( $vat_id, $order ) );
		    $order->save();
	    }
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return bool|array
	 */
    public function get_order_vat_result_data( $order ) {
	    $meta = $order->get_meta( '_vat_id_validation_result' );

	    if ( $meta && ! empty( $meta ) ) {
	        return $meta;
        }

        return false;
    }

	public function load_customer_vat_id( $data, $customer, $user_id ) {
		$types = array( 'shipping', 'billing' );

		foreach( $types as $type ) {

			if ( ! isset( $data[ $type ] ) ) {
				continue;
			}

			$data[ $type ]['vat_id'] = get_user_meta( $user_id,  $type . '_vat_id', true );
		}

		return $data;
	}

	public function frontend_hooks() {

		// Make sure that taxes for fees are removed
		add_action( 'woocommerce_after_calculate_totals', array( $this, 'maybe_remove_fee_taxes' ), 10, 1 );
		
		add_filter( 'woocommerce_process_myaccount_field_billing_vat_id', array( $this, 'user_save_billing_vat_id' ), 0, 1 );
		add_filter( 'woocommerce_process_myaccount_field_shipping_vat_id', array( $this, 'user_save_shipping_vat_id' ), 0, 1 );

		// If is VAT exempt (and net prices are used) set tax rounding precision to 2
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'set_tax_rounding' ) );

		// If prices do not include tax, set taxes to zero if vat exempt
		add_filter( 'woocommerce_calc_tax', array( $this, 'set_price_excluding_tax' ), 0, 5 );

		// Set min max prices for variable products to exclude tax if is vat exempt (pre 1.4.8)
		add_filter( 'woocommerce_variation_prices', array( $this, 'set_variable_exempt' ), 10, 3 );

		// Validate checkout and maybe set VAT exempt before re-calculating totals
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'set_vat_prices_process_checkout' ), 0 );

		// Vat ID check = 0
		add_filter( 'woocommerce_checkout_posted_data', array( $this, 'maybe_remove_vat_id_data' ), 10, 1 );

		add_filter( 'woocommerce_process_checkout_field_billing_vat_id', array( $this, 'set_billing_vat_id_format' ), 10, 1 );
		add_filter( 'woocommerce_process_checkout_field_shipping_vat_id', array( $this, 'set_shipping_vat_id_format' ), 10, 1 );

		add_filter( 'default_checkout_billing_vat_id', array( $this, 'get_checkout_vat_id_from_session' ), 10, 2 );
		add_filter( 'default_checkout_shipping_vat_id', array( $this, 'get_checkout_vat_id_from_session' ), 10, 2 );

		add_action( 'wp_head', array( $this, 'maybe_set_customer_vat_exempt' ), 10 );

		// Register Form
		if ( 'yes' === get_option( 'woocommerce_gzdp_enable_vat_check_register' ) ) {
			add_action( 'woocommerce_register_form', array( $this, 'register_form_input' ), 10 );
			add_filter( 'woocommerce_process_registration_errors', array( $this, 'validate_register_vat_id' ), 10, 4 );
			add_action( 'woocommerce_created_customer', array( $this, 'register_vat_id_customer' ), 10, 3 );
		}
	}

	public function register_vat_id_customer( $customer_id, $new_customer_data, $password_generated ) {
		$vat_id = isset( $_POST['vat_id'] ) ? wc_clean( $_POST['vat_id'] ) : '';

		if ( ! empty( $vat_id ) ) {
			$vat_id_fragments = $this->get_vat_id_from_string( $vat_id );

			if ( $this->validate( $vat_id_fragments['country'], $vat_id_fragments['number'] ) === true ) {
			    $country = $this->get_country_by_vat_id_prefix( $vat_id_fragments['country'] );

				add_user_meta( $customer_id, 'billing_vat_id', $this->set_vat_id_format( $vat_id_fragments['number'], $vat_id_fragments['country'] ) );
				add_user_meta( $customer_id, 'billing_country', $country );

				// Make sure customer is a vat exempt
				$this->set_vat_exempt( $country, ( 'XI' === $vat_id_fragments['country'] ? 'BT1234' : '' ) );
			}
		}
	}

	public function validate_register_vat_id( $validation_error, $username, $password, $email ) {
		$vat_id = isset( $_POST['vat_id'] ) ? wc_clean( $_POST['vat_id'] ) : '';

		if ( empty( $vat_id ) && $this->vat_field_is_required( true ) ) {
			$validation_error->add( 'wc_gzdp_registration_vat_id_missing', __( 'A valid VAT ID is required to register.', 'woocommerce-germanized-pro' ) );
		}

		if ( ! empty( $vat_id ) ) {
			$vat_id_fragments = $this->get_vat_id_from_string( $vat_id );

			if ( ! $this->country_supports_vat_id( $vat_id_fragments['country'] ) ) {
				$country_name = isset( WC()->countries->countries[ $vat_id_fragments['country'] ] ) ? WC()->countries->countries[ $vat_id_fragments['country'] ] : '';

				if ( ! empty( $country_name ) ) {
					$validation_error->add( 'wc_gzdp_registration_vat_id_country_not_supported', sprintf( __( 'Sorry but we do not support VAT IDs within %s.', 'woocommerce-germanized-pro' ), $country_name ) );
				} else {
					$validation_error->add( 'wc_gzdp_registration_vat_id_country_not_supported', __( 'Sorry but we do not support VAT IDs within your country.', 'woocommerce-germanized-pro' ) );
				}
			}

			$address_data = apply_filters( 'woocommerce_gzdp_register_vat_validation_address_data', array() );

			if ( ! isset( $address_data['company'] ) ) {
				add_filter( 'woocommerce_gzdp_vat_validation_company_required', '__return_false', 10 );
			}

			$result = $this->validate( $vat_id_fragments['country'], $vat_id_fragments['number'], $address_data );

			if ( ! isset( $address_data['company'] ) ) {
				remove_filter( 'woocommerce_gzdp_vat_validation_company_required', '__return_false', 10 );
			}

			if ( is_wp_error( $result ) ) {
			    foreach( $result->get_error_messages() as $message ) {
				    $validation_error->add( 'wc_gzdp_registration_vat_id_invalid', $message );
			    }
			}
		}

		return $validation_error;
	}

	public function register_form_input() {
		wc_get_template( 'myaccount/form-register-vat-id.php', array( 'required' => $this->vat_field_is_required( true ) ) );
	}

	public function get_checkout_vat_id_from_session( $value, $input ) {
		if ( WC()->session && WC()->session->get( $input ) ) {
			return WC()->session->get( $input );
		}

		return $value;
	}

	public function maybe_set_customer_vat_exempt() {

		if ( get_option( 'woocommerce_gzdp_enable_vat_check_login' ) === 'no' ) {
			return;
		}

		if ( ! function_exists( 'is_woocommerce' ) || ! function_exists( 'is_cart' ) || ! function_exists( 'is_checkout' ) || ! function_exists( 'is_account_page' ) ) {
			return;
		}

		if ( ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() ) && WC()->customer ) {
			WC()->customer->set_is_vat_exempt( false );

			if ( $vat_id = $this->get_user_vat_id() ) {
				$elements     = $this->get_vat_id_from_string( $vat_id );
				$address_data = $this->get_user_address_data();

				if ( $this->validate( $elements['country'], $elements['number'], $address_data ) === true ) {
					$this->set_vat_exempt();
				}
			}
		}
	}

	public function maybe_remove_fee_taxes( $cart ) {
		if ( WC()->customer && WC()->customer->is_vat_exempt() ) {
			foreach( $cart->get_fees() as $fee ) {
				$fee->tax = 0;
				$fee->tax_data = array();
			}
		}
	}

	public function get_vat_address_type_by_checkout_data( $posted ) {
    	// Use billing_country as fallback if no shipping_country was supplied (e.g. Amazon Pay)
    	$shipping_default_country = isset( $posted['shipping_country'] ) ? $posted['shipping_country'] : $posted['billing_country'];

    	$billing_country  		  = isset( $posted['billing_country'] ) ? $posted['billing_country'] : WC()->countries->get_base_country();
		$shipping_country		  = ( isset( $posted['ship_to_different_address'] ) && $posted['ship_to_different_address'] ) ? $shipping_default_country : '';
		$type             		  = $this->get_vat_address_type_by_countries( $billing_country, $shipping_country );

		return $type;
	}

	public function maybe_remove_vat_id_data( $data ) {

	    // Remove one of the two VAT IDs
        if ( isset( $data['ship_to_different_address'] ) && $data['ship_to_different_address'] ) {
            $data['billing_vat_id'] = '';
        } else {
	        $data['shipping_vat_id'] = '';
        }

        return $data;
    }

	public function customer_details_load_vat_id( $customer_data, $customer, $user_id ) {
		$customer_data["shipping"]['vat_id'] = get_user_meta( $user_id, "shipping_vat_id", true );
		$customer_data["billing"]['vat_id']  = get_user_meta( $user_id, "billing_vat_id", true );
		return $customer_data;
	}

	public function get_vat_id_prefix_by_country( $country ) {
		$country = strtoupper( $country );

		// Treat Isle of Man as UK and Monaco as FR
		$map = array(
			'GR' => 'EL',
			'MC' => 'FR',
			'IM' => 'UK',
		);

		if ( isset( $map[ $country ] ) ) {
			return $map[ $country ];
		}

		return $country;
	}

	public function get_country_by_vat_id_prefix( $vat_id_prefix ) {
		$country = strtoupper( $vat_id_prefix );

		// Northern Ireland belongs to the UK
		$map = array(
			'XI'  => 'GB',
            'UK'  => 'GB',
		);

		if ( isset( $map[ $vat_id_prefix ] ) ) {
			$country = $map[ $vat_id_prefix ];
		}

		return $country;
	}

	public function get_vat_id_from_string( $number, $expected_country = '' ) {
	    // Make sure all letters are uppercase
	    $number = strtoupper( $number );
		$number = trim( preg_replace( "/[^a-z0-9]+/i", "", sanitize_text_field( $number ) ) );
        $original_expected_country = $expected_country;

		if ( ! empty( $expected_country ) ) {
			$expected_country = $this->get_vat_id_prefix_by_country( $expected_country );
		}

		$maybe_country = substr( $number, 0, 2 );

		// Explicitly whitelist Norther Ireland prefix although it is not a country
		if ( 'XI' === $maybe_country ) {
			$expected_country = 'XI';
		}

		if ( empty( $expected_country ) ) {
			preg_match( "/^([A-Z]+)/", $maybe_country, $matches );
			
			if ( ! empty( $matches ) ) {
				$expected_country = $maybe_country;
				$number           = substr( $number, 2 );
			} else {
				$expected_country = $this->get_vat_id_prefix_by_country( WC()->countries->get_base_country() );
			}
		} elseif ( $maybe_country == $this->get_vat_id_prefix_by_country( $expected_country ) ) {
			$number = substr( $number, 2 );
		}

		return apply_filters( "woocommerce_gzdp_vat_id_fragments", array(
			"number" 	=> $number,
			"country" 	=> $expected_country,
		), $number, $original_expected_country );
	}

	public function set_vat_id_format( $number, $country = '' ) {
		$elements = $this->get_vat_id_from_string( $number, $country );

		return apply_filters( 'woocommerce_gzdp_vat_id_format', $elements['country'] . $elements['number'], $number, $country );
	}

	public function set_billing_vat_id_format( $data = '' ) {
		if ( empty( $data ) ) {
			return $data;
		}

		return $this->set_vat_id_format( $data, ( isset( $_POST['billing_country'] ) ? wc_clean( $_POST['billing_country'] ) : '' ) );
	}

	public function set_shipping_vat_id_format( $data = '' ) {
		if ( empty( $data ) ) {
			return $data;
		}

		return $this->set_vat_id_format( $data, ( isset( $_POST['shipping_country'] ) ? wc_clean( $_POST['shipping_country'] ) : '' ) );
	}

	public function set_variable_exempt( $prices_array, $product, $display ) {
		if ( WC()->customer && WC()->customer->is_vat_exempt() && 'incl' === get_option( 'woocommerce_tax_display_shop' ) ) {
			foreach ( $prices_array as $type => $variations ) {
				foreach ( $variations as $variation_id => $price ) {
					$variation = wc_get_product( $variation_id );
					$prices_array[ $type ][ $variation_id ] = wc_get_price_excluding_tax( $variation, array( 'qty' => 1, 'price' => $price ) );
				}
				asort( $prices_array[ $type ] );
			}

			// Prevent multiple vat removal
			remove_filter( 'woocommerce_variation_prices', array( $this, 'set_variable_exempt' ), 10 );
		}
		return $prices_array;
	}

	public function set_price_excluding_tax( $taxes, $price, $rates, $price_includes_tax, $suppress_rounding ) {
		if ( ! wc_prices_include_tax() && ! $price_includes_tax && is_object( WC()->customer ) && WC()->customer->is_vat_exempt() )
			$taxes = array();

		return $taxes;
	}

	public function set_tax_rounding() {
		if ( is_object( WC()->customer ) && WC()->customer->is_vat_exempt() && WC()->cart->prices_include_tax ) {
			add_filter( 'woocommerce_calc_tax', array( $this, 'tax_round' ), 0, 5 );
		} else {
			remove_filter( 'woocommerce_calc_tax', array( $this, 'tax_round' ), 0 );
		}
	}

	public function tax_round( $taxes, $price, $rates, $price_includes_tax, $suppress_rounding ) {

		if ( apply_filters( 'woocommerce_gzdp_vat_id_disable_inc_tax_rounding', false ) ) {
			return $taxes;
		}

		foreach( $taxes as $key => $tax ) {
			$taxes[ $key ] = WC_Tax::round( $tax );
		}

		return $taxes;
	}

	protected function user_save_vat_id( $vat_id, $country, $address_type = 'billing' ) {
		$number       = wc_clean( $vat_id );
		$elements     = $this->get_vat_id_from_string( $number, $country );
		$post_data    = $_POST;
		$address_data = $this->get_address_data( $post_data, $address_type );
		$result       = $this->validate( $elements['country'], $elements['number'], $address_data );

		if ( is_wp_error( $result ) ) {
		    foreach( $result->get_error_messages() as $message ) {
			    wc_add_notice( $message, 'error', array( 'id' => "{$address_type}_vat_id" ) );
		    }

			return '';
		}

		return $vat_id;
	}

	public function user_save_shipping_vat_id( $vat_id = '' ) {
		if ( ! empty( $vat_id ) ) {
			$shipping_country = ( isset( $_POST['shipping_country'] ) ? wc_clean( $_POST['shipping_country'] ) : '' );

			if ( empty( $shipping_country ) ) {
				wc_add_notice( __( 'Please choose a shipping country before saving your VAT ID.', 'woocommerce-germanized-pro' ), 'error' );
				return '';
			}

			return $this->user_save_vat_id( $vat_id, $shipping_country, 'shipping' );
		}
		return $vat_id;
	}

	public function user_save_billing_vat_id( $vat_id = '' ) {
		if ( ! empty( $vat_id ) ) {
			$billing_country = ( isset( $_POST['billing_country'] ) ? wc_clean( $_POST['billing_country'] ) : WC()->countries->get_base_country() );

			return $this->user_save_vat_id( $vat_id, $billing_country, 'billing' );
		}
		return $vat_id;
	}

	public function get_vat_address_type_by_countries( $billing_country, $shipping_country = '' ) {
		$type = 'shipping';

		$shipping_country_exists = ( ! empty( $shipping_country ) ? true : false );

		if ( empty( $shipping_country ) ) {
			$shipping_country = $billing_country;
		}

		if ( $billing_country === $shipping_country && ! $shipping_country_exists ) {
			$type = 'billing';
		}

		return apply_filters( 'woocommerce_gzdp_vat_address_type_by_countries', $type, $billing_country, $shipping_country );
	}

	public function get_address_differing_fields() {
		return wc_gzdp_get_order_address_differing_fields();
	}

	public function order_has_differing_shipping_address( $order ) {
	    return wc_gzdp_order_has_differing_shipping_address( $order );
	}

	public function get_vat_address_type_by_order( $order ) {

		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
        }

		$billing_country  = $order->get_billing_country();
		$shipping_country = '';

		if ( $this->order_has_differing_shipping_address( $order ) ) {
			$shipping_country = $order->get_shipping_country();

			// Fallback to billing country
			if ( empty( $shipping_country ) ) {
				$shipping_country = $billing_country;
			}
		}

		return $this->get_vat_address_type_by_countries( $billing_country, $shipping_country );
	}

	public function order_supports_vat_id( $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
        }

		// Order type does not allow checking billing country e.g. POS
		if ( ! is_callable( array( $order, 'get_billing_country' ) ) ) {
		    return false;
        }

		$eu            = wc_gzdp_get_eu_vat_countries();
		$type          = $this->get_vat_address_type_by_order( $order );
		$vat_id        = $this->get_order_vat_id( $order );
		$user_country  = 'shipping' === $type ? $order->get_shipping_country() : $order->get_billing_country();
		$user_postcode = 'shipping' === $type ? $order->get_shipping_postcode() : $order->get_billing_postcode();

		return ( $vat_id && in_array( WC()->countries->get_base_country(), $eu ) && $this->country_supports_vat_id( $user_country, $user_postcode ) );
	}

	public function order_has_vat_exempt_filter( $is_exempt, $order ) {
		if ( ! $is_exempt ) {
			$is_exempt = $this->order_has_vat_exempt( $order );
		}

		return $is_exempt;
	}

	public function order_has_vat_exempt( $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
        }

		// Order does not support billing_country e.g. POS
		if ( ! is_callable( array( $order, 'get_billing_country' ) ) ) {
		    return false;
        }

		$type          = $this->get_vat_address_type_by_order( $order );
		$user_country  = 'shipping' === $type ? $order->get_shipping_country() : $order->get_billing_country();
		$user_postcode = 'shipping' === $type ? $order->get_shipping_postcode() : $order->get_billing_postcode();

		return ( $this->order_supports_vat_id( $order ) && $this->country_supports_vat_exempt( $user_country, $user_postcode ) );
	}

	public function get_order_billing_vat_id( $order ) {
		$vat_id = $order->get_meta( "_billing_vat_id" );

		return ! empty( $vat_id ) ? $vat_id : false;
	}

	public function get_order_shipping_vat_id( $order ) {
		$vat_id = $order->get_meta( "_shipping_vat_id" );

		return ! empty( $vat_id ) ? $vat_id : false;
	}

	public function get_order_vat_id( $order ) {

	    if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
        }

	    // Order does not support country selection e.g. POS
		if ( ! is_callable( array( $order, 'get_billing_country' ) ) ) {
			return false;
		}

		$type   = $this->get_vat_address_type_by_order( $order );
		$vat_id = $order->get_meta( "_{$type}_vat_id" );

		return ! empty( $vat_id ) ? $vat_id : false;
	}

	public function user_has_differing_shipping_address( $user_id = 0 ) {
        $customer = false;

        if ( empty( $user_id ) && WC()->customer ) {
            $customer = WC()->customer;
        } elseif ( ! empty( $user_id ) ) {
            $customer = new WC_Customer( $user_id );
        }

        if ( ! $customer ) {
            return false;
        }

		$shipping_vat_id = $customer->get_meta( 'shipping_vat_id' );
		$billing_vat_id  = $customer->get_meta( 'billing_vat_id' );

		if ( empty( $user_id ) && WC()->session ) {
			if ( ! is_null( WC()->session->get( 'shipping_vat_id' ) ) ) {
				$shipping_vat_id = WC()->session->get( 'shipping_vat_id' );
			}

			if ( ! is_null( WC()->session->get( 'billing_vat_id' ) ) ) {
				$billing_vat_id = WC()->session->get( 'billing_vat_id' );
			}
		}

		// We do not need to check for differing addresses if the user has no shipping_vat_id
		if ( empty( $shipping_vat_id ) ) {
			return false;
		} else {
		    if ( $shipping_vat_id !== $billing_vat_id ) {
		        return true;
            }
         }

		foreach( $this->get_address_differing_fields() as $field ) {

		    $b_getter = "get_billing_{$field}";
		    $s_getter = "get_shipping_{$field}";
			$b_data   = '';
			$s_data   = '';

		    if ( is_callable( array( $customer, $b_getter ) ) && is_callable( array( $customer, $s_getter ) ) ) {
		        $b_data = $customer->$b_getter();
			    $s_data = $customer->$s_getter();
            }

			if ( ! empty( $s_data ) && $b_data !== $s_data ) {
				return true;
			}
		}

		return false;
	}

	protected function get_user_vat_address_type( $user_id = false ) {
		$customer = false;
		$type     = 'billing';

		if ( ! $user_id ) {
			$customer = WC()->customer;
		} else {
			$customer = new WC_Customer( $user_id );
		}

		if ( $customer ) {
			$billing_country  = $customer->get_billing_country();
			$shipping_country = ( $this->user_has_differing_shipping_address( $user_id ) ? $customer->get_shipping_country() : '' );
			$type             = $this->get_vat_address_type_by_countries( $billing_country, $shipping_country );
		}

		return $type;
	}

	public function get_user_vat_id( $user_id = false ) {
        $customer = false;

        if ( ! $user_id ) {
            $customer = WC()->customer;
        } else {
            $customer = new WC_Customer( $user_id );
        }

        if ( $customer ) {
	        $type     = $this->get_user_vat_address_type( $user_id );
	        $country  = 'shipping' === $type ? $customer->get_shipping_country() : $this->get_customer_billing_country();
	        $postcode = 'shipping' === $type ? $customer->get_shipping_postcode() : $this->get_customer_billing_postcode();

	        if ( ! $this->country_supports_vat_id( $country, $postcode ) ) {
		        return false;
	        }

	        if ( ! $user_id && WC()->session ) {
                if ( is_checkout() && WC()->checkout()->get_value( "{$type}_vat_id" ) ) {
                    return WC()->checkout()->get_value( "{$type}_vat_id" );
                } elseif ( is_null( WC()->session->get( "{$type}_vat_id" ) ) ) {
			        return $customer->get_meta( "{$type}_vat_id", true );
		        } else {
			        return WC()->session->get( "{$type}_vat_id" );
		        }
            } else {
		        return $customer->get_meta( "{$type}_vat_id", true );
            }
        }

		return false;
	}

	public function disable_base_rates() {
	    return false;
    }

	public function set_vat_exempt( $country = '', $postcode = '' ) {
		if ( empty( $country ) ) {
		    add_filter( 'woocommerce_apply_base_tax_for_local_pickup', array( $this, 'disable_base_rates' ), 10 );
			$address = WC()->customer->get_taxable_address();
			remove_filter( 'woocommerce_apply_base_tax_for_local_pickup', array( $this, 'disable_base_rates' ), 10 );

			$country  = $address[0];
			$postcode = $address[2];
		}

		if ( $this->country_supports_vat_exempt( $country, $postcode ) ) {
			WC()->customer->set_is_vat_exempt( true );

			do_action( 'woocommerce_gzdp_customer_is_vat_exempt' );

			/**
			 * Newer versions (>= 4.4) support customer vat exempt status checking. No need to set manually
			 */
			if ( WC()->cart && ! is_callable( array( WC()->cart, 'get_tax_price_display_mode' ) ) ) {
			    WC()->cart->tax_display_cart = 'excl';
            }
		}
	}

	public function save_billing_vat_id_field_profile() {
		if ( isset( $_POST['billing_vat_id'] ) && ! empty( $_POST['billing_vat_id'] ) ) {

			$vat_id       = sanitize_text_field( $_POST['billing_vat_id'] );
			$country      = ( isset( $_POST['billing_country'] ) ? $_POST['billing_country'] : '' );
			$elements     = $this->get_vat_id_from_string( $vat_id, $country );
			$post_data    = $_POST;
			$address_data = $this->get_address_data( $post_data, 'billing' );

			if ( apply_filters( 'woocommerce_gzdp_validate_admin_profile_vat_id', true, $elements ) && $this->validate( $elements['country'], $elements['number'], $address_data ) !== true ) {
				add_action( 'user_profile_update_errors', array( $this, 'save_vat_field_profile_error' ), 5, 3 );
			}
		}
	}

	public function save_shipping_vat_id_field_profile() {
		if ( isset( $_POST['shipping_vat_id'] ) && ! empty( $_POST['shipping_vat_id'] ) ) {

			$vat_id       = sanitize_text_field( $_POST['shipping_vat_id'] );
			$country      = ( isset( $_POST['shipping_country'] ) ? $_POST['shipping_country'] : '' );
			$elements     = $this->get_vat_id_from_string( $vat_id, $country );
			$post_data    = $_POST;
			$address_data = $this->get_address_data( $post_data, 'shipping' );

			if ( apply_filters( 'woocommerce_gzdp_validate_admin_profile_vat_id', true, $elements ) && $this->validate( $elements['country'], $elements['number'], $address_data ) !== true ) {
				add_action( 'user_profile_update_errors', array( $this, 'save_vat_field_profile_error' ), 5, 3 );
			}
		}
	}

	public function save_vat_field_profile_error( $errors, $update, $user ) {
		$errors->add( 'billing_vat_id', __( 'VAT ID seems to be invalid but was still saved. Please check the ID again.', 'woocommerce-germanized-pro' ) );
	}

	public function add_vat_field_profile( $fields ) {

		$fields['billing']['fields']['billing_vat_id'] = array(
			'label'       => __( 'VAT ID', 'woocommerce-germanized-pro' ),
			'description' => '',
		);

		$fields['shipping']['fields']['shipping_vat_id'] = array(
			'label'       => __( 'VAT ID', 'woocommerce-germanized-pro' ),
			'description' => '',
		);

		return $fields;

	}

	public function set_admin_billing_address( $fields ) {
		
		$fields['vat_id'] = array(
			'label' => __( 'VAT ID', 'woocommerce-germanized-pro' ),
			'show'  => false
		);

		return $fields;  
	}

	public function set_admin_shipping_address( $fields ) {

		$fields['vat_id'] = array(
			'label' => __( 'VAT ID', 'woocommerce-germanized-pro' ),
			'show'  => false
		);

		return $fields;
	}

	public function set_formatted_billing_address( $fields, $order ) {
		if ( empty( $fields ) || ! is_array( $fields ) ) {
			return $fields;
		}

		$fields['vat_id'] = '';
		$type             = $this->get_vat_address_type_by_order( $order );
		
		if ( ( 'billing' === $type ) && $order->get_meta( '_billing_vat_id' ) ) {
			$fields['vat_id'] = apply_filters( 'woocommerce_gzdp_address_vat_id', $order->get_meta( '_billing_vat_id' ) );
        }
		
		return $fields;
	}

	/**
	 * @param $fields
	 * @param WC_Order $order
	 *
	 * @return array
	 */
	public function set_formatted_shipping_address( $fields, $order ) {
	    if ( empty( $fields ) || ! is_array( $fields ) ) {
	        return $fields;
        }

		$fields['vat_id'] = '';

		remove_filter( 'woocommerce_order_formatted_shipping_address', array( $this, 'set_formatted_shipping_address' ), 0 );
		$type = $this->get_vat_address_type_by_order( $order );
		add_filter( 'woocommerce_order_formatted_shipping_address', array( $this, 'set_formatted_shipping_address' ), 0, 2 );

		if ( ( 'shipping' === $type ) && $order->get_meta( '_shipping_vat_id' ) ) {
			$fields['vat_id'] = apply_filters( 'woocommerce_gzdp_address_vat_id', $order->get_meta( '_shipping_vat_id' ) );
        }

		return $fields;
	}

	public function set_vat_prices_process_checkout() {
		if ( is_checkout() && ! is_cart() ) {
		    $data           = array();
		    $prevent_errors = false;

		    if ( isset( $_POST['post_data'] ) ) {
			    // Parse Array
			    parse_str( $_POST['post_data'], $data );
		    } else {
			    $data = WC()->checkout()->get_posted_data();
            }

			/**
			 * In case data is empty (e.g. while first-time visiting checkout)
             * use WC()->checkout data as fallback (which uses customer/session data).
			 */
		    if ( empty( $data['billing_country'] ) ) {
		        $address_type                        = $this->get_user_vat_address_type();
		        $data[ $address_type . '_country' ]  = WC()->checkout()->get_value( $address_type . '_country' );
			    $data[ $address_type . '_company' ]  = WC()->checkout()->get_value( $address_type . '_company' );
			    $data[ $address_type . '_city' ]     = WC()->checkout()->get_value( $address_type . '_city' );
			    $data[ $address_type . '_postcode' ] = WC()->checkout()->get_value( $address_type . '_postcode' );
		        $data[ $address_type . '_vat_id' ]   = $this->get_user_vat_id();

			    $prevent_errors = true;
            } else {
			    $address_type = $this->get_vat_address_type_by_checkout_data( $data );
            }

			if ( isset( $data["{$address_type}_vat_id"] ) ) {
				$this->check_vat_exemption( $data, false, $prevent_errors );
			}
        }
	}

	public function get_customer_billing_country() {
		if ( is_callable( array( WC()->customer, "get_billing_country" ) ) ) {
			return WC()->customer->get_billing_country();
		} else {
			return WC()->customer->get_country();
		}
	}

	public function get_customer_billing_postcode() {
		if ( is_callable( array( WC()->customer, "get_billing_postcode" ) ) ) {
			return WC()->customer->get_billing_postcode();
		} else {
			return WC()->customer->get_postcode();
		}
	}

	/**
	 * @param array    $posted
	 * @param WP_Error $errors
	 */
	public function validate_number_after_submit( $posted, $errors ) {
		$this->check_vat_exemption( $posted, $errors );
	}

	protected function get_address_data( $data, $address_type = 'billing' ) {
		$address_data = array(
			'company'  => isset( $data["{$address_type}_company"] ) ? wc_clean( $data["{$address_type}_company"] ) : '',
			'city'     => isset( $data["{$address_type}_city"] ) ? wc_clean( $data["{$address_type}_city"] ) : '',
			'postcode' => isset( $data["{$address_type}_postcode"] ) ? wc_clean( $data["{$address_type}_postcode"] ) : '',
		);

		return $address_data;
	}

	public function check_vat_exemption( $post_data = array(), $errors = false, $prevent_errors = false ) {
		WC()->customer->set_is_vat_exempt( false );

		// Refresh tax display as Woo no checks the customer vat exempt status on load
		if ( WC()->cart && ! is_callable( array( WC()->cart, 'get_tax_price_display_mode' ) ) ) {
			WC()->cart->tax_display_cart = get_option( 'woocommerce_tax_display_cart' );
        }

		if ( WC()->session ) {
			WC()->session->set( "billing_vat_id", '' );
			WC()->session->set( "shipping_vat_id", '' );
		}

		$customer_country  = $this->get_customer_billing_country();
		$customer_postcode = $this->get_customer_billing_postcode();

		if ( ! empty( $post_data ) && isset( $post_data['billing_country'] ) && ! empty( $post_data['billing_country'] ) ) {
		    $customer_country = $post_data['billing_country'];
        }

		if ( ! empty( $post_data ) && isset( $post_data['billing_postcode'] ) && ! empty( $post_data['billing_postcode'] ) ) {
			$customer_postcode = $post_data['billing_postcode'];
		}

		$address_type = $this->get_vat_address_type_by_checkout_data( $post_data );

		if ( 'shipping' === $address_type ) {
			$customer_country  = WC()->customer->get_shipping_country();
			$customer_postcode = WC()->customer->get_shipping_postcode();

			if ( ! empty( $post_data ) && isset( $post_data['shipping_country'] ) && ! empty( $post_data['shipping_country'] ) ) {
			    $customer_country = $post_data['shipping_country'];
			}

			if ( ! empty( $post_data ) && isset( $post_data['shipping_postcode'] ) && ! empty( $post_data['shipping_postcode'] ) ) {
				$customer_postcode = $post_data['shipping_postcode'];
			}
		}

		if ( isset( $post_data["{$address_type}_vat_id"] ) && ! empty( $customer_country ) && ! empty( $post_data["{$address_type}_vat_id"] ) && $this->country_supports_vat_id( $customer_country, $customer_postcode ) ) {
			$address_data    = $this->get_address_data( $post_data, $address_type );
			$vat_id_elements = $this->get_vat_id_from_string( $post_data[ "{$address_type}_vat_id" ], $customer_country );
			$result          = $this->validate( $vat_id_elements['country'], $vat_id_elements['number'], $address_data );

			if ( ! is_wp_error( $result ) ) {

				if ( WC()->session ) {
					WC()->session->set( "{$address_type}_vat_id", $vat_id_elements['country'] . $vat_id_elements['number'] );
				}

				$this->set_vat_exempt( $customer_country, $customer_postcode );
			} elseif ( ! $prevent_errors ) {
			    if ( $errors ) {
			        foreach( $result->get_error_messages() as $message ) {
				        $errors->add( 'validation', $message, 'error', array( 'id' => "{$address_type}_vat_id" ) );
			        }
			    } else {
				    foreach( $result->get_error_messages() as $message ) {
					    /**
					     * Prevent double-showing message
					     */
				        if ( wc_has_notice( $message, 'error' ) ) {
				            continue;
				        }

					    wc_add_notice( $message, 'error', array( 'id' => "{$address_type}_vat_id" ) );
				    }
                }
			}
		}
	}

	public function add_vat_address( $replacements, $args ) {
		extract( $args );
		
		if ( isset( $vat_id ) ) {
			$replacements['{vat_id}'] = $vat_id;
		} else {
			$replacements['{vat_id}'] = '';
		}
 		
 		return $replacements;
	}

	public function hide_vat_field_js( $fields ) {
		$fields['vat_id'] = '#billing_vat_id_field, #shipping_vat_id_field';
		return $fields;
	}

	protected function is_northern_ireland( $country, $postcode = '' ) {
	    return Vendidero\StoreaBill\Countries::is_northern_ireland( $country, $postcode );
	}

	public function country_supports_vat_id( $country, $postcode = '' ) {
		$supports_vat = false;

		if ( WC()->countries->get_base_country() !== $country && ( in_array( $country, wc_gzdp_get_eu_vat_countries() ) || $this->is_northern_ireland( $country, $postcode ) ) ) {
			$supports_vat = true;
		}

		/**
		 * Exclude certain postcodes from allowing VAT exemptions
		 */
		if ( Vendidero\StoreaBill\Countries::is_eu_vat_postcode_exemption( $country, $postcode ) ) {
		    $supports_vat = false;
		}

		if ( 'yes' === get_option( 'woocommerce_gzdp_vat_id_base_country_included' ) && WC()->countries->get_base_country() === $country ) {
			$supports_vat = true;
		}

		if ( 'CH' === $country ) {
		    $supports_vat = true;
		}
		
		return apply_filters( 'woocommerce_gzdp_country_supports_vat_id', $supports_vat, $country, $postcode );
	}

	public function country_supports_vat_exempt( $country, $postcode = '' ) {
		$supports_vat_exempt = false;

		if ( WC()->countries->get_base_country() !== $country && ( in_array( $country, wc_gzdp_get_eu_vat_countries() ) || $this->is_northern_ireland( $country, $postcode ) ) ) {
			$supports_vat_exempt = true;
		}

		/**
		 * Exclude certain postcodes from allowing VAT exemptions
		 */
		if ( Vendidero\StoreaBill\Countries::is_eu_vat_postcode_exemption( $country, $postcode ) ) {
			$supports_vat_exempt = false;
		}

		if ( 'CH' === $country ) {
		    $supports_vat_exempt = true;
		}

		return apply_filters( 'woocommerce_gzdp_country_supports_vat_exempt', $supports_vat_exempt, $country, $postcode );
	}

	public function hide_vat_field( $locale ) {
		$applyable = array_merge( WC()->countries->get_allowed_countries(), WC()->countries->get_shipping_countries() );

		foreach ( $applyable as $country => $name ) {
			if ( ! $this->country_supports_vat_id( $country ) && 'GB' !== $country ) {
				if ( ! isset( $locale[ $country ] ) ) {
					$locale[ $country ] = array();
				}
				
				$locale[ $country ]['vat_id'] = array( 'required' => false, 'hidden' => true );
			}
		}

		return $locale;
	}

	public function set_vat_field( $countries ) {
		foreach ( $countries as $country => $value ) {
			$countries[ $country ] .= "\n{vat_id}";
		}
		
		return $countries;
	}

	public function add_vat_field( $fields ) {
		$fields['vat_id'] = array(
			'label'       => __( 'VAT ID', 'woocommerce-germanized-pro' ),
			'placeholder' => __( 'VAT ID', 'woocommerce-germanized-pro' ),
			'required'    => $this->vat_field_is_required(),
			'clear'       => true,
			'class'       => array( 'form-row-wide' ),
			'priority'    => 100,
		);

		return $fields;
	}

	public function vat_field_is_required( $for_registration = false ) {
		$is_required = false;

		// Check if VAT ID is not forced
		if ( 'yes' === get_option( 'woocommerce_gzdp_force_virtual_product_business' ) ) {
			// If it is forced check whether current cart contains virtual/downloadable product
			$items = ( WC()->cart ? WC()->cart->get_cart() : array() );

			if ( ! empty( $items ) ) {
				foreach ( $items as $cart_item_key => $values ) {
                    $_product = $values['data'];

                    if ( ! is_callable( array( $_product, 'is_downloadable' ) ) ) {
                        $_product = wc_get_product( $_product );
                    }

					if ( $_product->is_downloadable() || $_product->is_virtual() ) {
						$is_required = true;
						break;
					}
				}
			}
		}

		if ( 'yes' === get_option( 'woocommerce_gzdp_vat_id_required' ) ) {
			$is_required = true;
		}

		if ( $for_registration ) {
		    $is_required = ( 'yes' === get_option( 'woocommerce_gzdp_vat_id_registration_required' ) ) ? true : false;
        }

		return apply_filters( 'woocommerce_gzdp_vat_id_field_is_required', $is_required, $for_registration );
	}

	protected function get_user_address_data( $user_id = false ) {
		$customer = false;

		if ( empty( $user_id ) && WC()->customer ) {
			$customer = WC()->customer;
		} elseif ( ! empty( $user_id ) ) {
			$customer = new WC_Customer( $user_id );
		}

		if ( ! $customer ) {
			return false;
		}

		$type = $this->get_user_vat_address_type( $user_id );

		$address_data = array(
			'company'  => 'shipping' === $type ? $customer->get_shipping_company() : $customer->get_billing_company(),
			'city'     => 'shipping' === $type ? $customer->get_shipping_city() : $customer->get_billing_city(),
			'postcode' => 'shipping' === $type ? $customer->get_shipping_postcode() : $customer->get_billing_postcode(),
		);

		return $address_data;
	}

	protected function get_order_address_data( $order ) {
	    $address_type    = $this->get_vat_address_type_by_order( $order );
	    $getter          = 'get_' . $address_type . '_';
	    $getter_company  = $getter . 'company';
	    $getter_city     = $getter . 'city';
	    $getter_postcode = $getter . 'postcode';

	    return array(
            'company'  => is_callable( array( $order, $getter_company) ) ? $order->$getter_company() : '',
            'city'     => is_callable( array( $order, $getter_city) ) ? $order->$getter_city() : '',
            'postcode' => is_callable( array( $order, $getter_postcode ) ) ? $order->$getter_postcode() : '',
        );
	}

	public function get_vat_result_data( $vat_id, $order ) {
	    $vat_id_parts = $this->get_vat_id_from_string( $vat_id );
	    $address_data = $this->get_order_address_data( $order );
	    $postfix      = $this->get_transient_postfix( $vat_id_parts['country'], $vat_id_parts['number'], $address_data );

        if ( $result_data = get_transient( 'vat_id_validation_result_' . $postfix ) ) {
            return (array) $result_data;
        }

        return false;
    }

    protected function get_transient_postfix( $country, $number, $address_expected = array() ) {
	    $country          = $this->get_vat_id_prefix_by_country( $country );
	    $address_expected = wp_parse_args( $address_expected, array(
		    'company'  => '',
		    'postcode' => '',
		    'city'     => ''
	    ) );

	    $address_expected = array_map( 'wc_clean', $address_expected );
	    $address_expected = array_filter( $address_expected );
	    $address          = strtolower( implode( '_', $address_expected ) );

	    return ( $country . $number . $address );
    }

	public function validate( $country, $number, $address_expected = array() ) {
		$country           = $this->get_vat_id_prefix_by_country( $country );
		$transient_postfix = $this->get_transient_postfix( $country, $number, $address_expected );
		$transient_name    = 'vat_id_validated_' . $transient_postfix;
		$company           = isset( $address_expected['company'] ) ? trim( strtolower( $address_expected['company'] ) ) : '';
		$company_required  = apply_filters( 'woocommerce_gzdp_vat_validation_company_required', ( 'yes' === get_option( 'woocommerce_gzdp_vat_id_company_required' ) ) );

		/**
		 * Force company existence.
		 */
		if ( $company_required && empty( $company ) ) {
			return new WP_Error( 'vat-id-company-missing', __( 'Please provide your company name to check your VAT ID.', 'woocommerce-germanized-pro' ) );
		}

        $vat_validator = false;

		if ( 'CH' === $country ) {
			$vat_validator = new WC_GZDP_VAT_Validation_Switzerland();
		} elseif ( in_array( $country, wc_gzdp_get_eu_vat_countries() ) || 'XI' === $country ) {
			$vat_validator = new WC_GZDP_VAT_Validation( array(
				'requester_vat_id' => get_option( 'woocommerce_gzdp_vat_requester_vat_id' ),
				'address_expected' => $address_expected
			) );
		}

        if ( $vat_validator ) {
	        /**
	         * Cache VAT validation results
	         */
	        if ( get_transient( $transient_name ) ) {
		        return true;
	        }

	        if ( $vat_validator->check( $country, $number ) ) {
		        $vat_result = $vat_validator->get_data();

		        // Save the result as transient
		        set_transient( 'vat_id_validation_result_' . $transient_postfix, $vat_result, 5 * DAY_IN_SECONDS );

		        if ( get_option( 'woocommerce_gzdp_vat_check_cache' ) ) {
			        $days = (int) get_option( 'woocommerce_gzdp_vat_check_cache', 7 );

			        set_transient( $transient_name, 'yes', $days * DAY_IN_SECONDS );
		        }

		        return true;
	        } elseif ( $errors = $vat_validator->get_error_messages() ) {
		        return $errors;
	        }

	        return new WP_Error( 'vat-id-invalid', __( 'The VAT ID you\'ve provided is invalid.', 'woocommerce-germanized-pro' ) );
        } else {
            return apply_filters( 'woocommerce_gzdp_vat_id_missing_validator_is_valid', true, $country, $number, $address_expected );
        }
	}

	/**
	 * @param WC_Order $order
	 * @param $data_store
	 */
	public function calc_order_taxes_v3( $order, $data_store ) {
		if ( ! empty( $_POST['action'] ) && 'woocommerce_calc_line_taxes' === $_POST[ 'action' ] ) {

			$country  = ! empty( $_POST['country'] ) ? wc_clean( $_POST['country'] ) : WC()->countries->get_base_country();
			$postcode = ! empty( $_POST['postcode'] ) ? wc_clean( $_POST['postcode'] ) : WC()->countries->get_base_postcode();
			$vat_id   = ! empty( $_POST['vat_id'] ) ? wc_clean( $_POST['vat_id'] ) : '';

			if ( ! empty( $vat_id ) && $this->order_supports_vat_id( $order ) && $this->country_supports_vat_exempt( $country, $postcode ) ) {
				$vat_id_elements = $this->get_vat_id_from_string( $vat_id, $country );
				$validated       = WC_GZDP_VAT_Helper::instance()->validate( $vat_id_elements['country'], $vat_id_elements['number'] );

				// Is VAT exempt
				if ( $validated ) {
					$order->remove_order_items( 'tax' );
					$order->set_shipping_tax(  0 );
					$order->set_cart_tax( 0 );

					$this->save_vat_result_data( $order );
				}
			}
		}
	}

	/**
	 * Item filter when manually recalculating order taxes to check for vat id - WC pre 3.X only
	 *  
	 * @param  array $items    
	 * @param  int $order_id 
	 * @param  string $country  
	 * @param  array $data post data
	 * @return array
	 */
	public function calc_order_taxes( $items, $order_id, $country, $data ) {

		remove_filter( 'get_post_metadata', array( $this, 'product_vat_exempt' ), 0 );

		if ( isset( $data['vat_id'] ) && $this->country_supports_vat_id( $country ) ) {

			$vat_id_elements = $this->get_vat_id_from_string( sanitize_text_field( $data['vat_id'] ), $country );

			// Is VAT exempt
			if ( WC_GZDP_VAT_Helper::instance()->validate( $vat_id_elements['country'], $vat_id_elements['number'] ) ) {
				// Remove product taxable status
				add_filter( 'get_post_metadata', array( $this, 'product_vat_exempt' ), 0, 4 );
				// Remove order taxes
				add_action( 'woocommerce_saved_order_items', array( $this, 'remove_order_vat' ), 0, 2 );
			}
		}
		return $items;
	}

	public function remove_order_vat( $order_id, $items ) {
		$order = wc_get_order( $order_id );
		$order->remove_order_items( 'tax' );
	}

	/**
	 * Temporarily adds a filter to stop products from being taxable - for admin order tax calculation only
	 */
	public function product_vat_exempt( $metadata, $object_id, $meta_key, $single ) {
		if ( '_tax_status' === $meta_key )
			return 'none';
	}

}
return WC_GZDP_VAT_Helper::instance();