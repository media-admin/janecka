<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

function wc_gzdp_order_needs_confirmation( $order_id ) {
	$order = ( is_object( $order_id ) ? $order_id : wc_get_order( $order_id ) );

	if ( ! $order ) {
		$needs_confirmation = false;
	} else {
		$needs_confirmation = $order->get_meta( '_order_needs_confirmation' ) ? true : false;
	}

	/**
	 * This filter allows adjusting whether a specific order needs confirmation or not.
	 * Order confirmation is only available when the manual order confirmation feature is activated.
	 *
	 * @param boolean  $needs_confirmation Whether the order needs confirmation or not.
	 * @param WC_Order $order The order instance.
	 *
	 * @since 2.0.0
	 */
	return apply_filters( 'woocommerce_gzdp_order_needs_confirmation', $needs_confirmation, $order );
}

function wc_gzdp_get_order_address_differing_fields() {
    return apply_filters( 'woocommerce_gzdp_order_address_differing_fields', array(
        'company',
        'first_name',
        'last_name',
        'address_1',
        'address_2',
        'city',
        'country',
        'postcode'
    ) );
}

function wc_gzdp_order_has_differing_shipping_address( $order ) {
    $order = ( is_numeric( $order ) ? wc_get_order( $order ) : $order );

    if ( is_callable( array( $order, 'has_shipping_address' ) ) ) {
        if ( ! $order->has_shipping_address() ) {
            return false;
        }
    }

	/**
	 * If this method returns false - indicates non-existing shipping address.
	 * See wc-meta-box-order-data.
	 */
    if ( ! $order->get_formatted_shipping_address() ) {
    	return false;
    }

    // In case the order has a shipping_vat_id - make sure that the order has a different shipping address
    $shipping_vat_id = $order->get_meta( '_shipping_vat_id', true );
	$billing_vat_id  = $order->get_meta( '_billing_vat_id', true );

    if ( ! empty( $shipping_vat_id ) && empty( $billing_vat_id ) ) {
    	return true;
    }

    foreach( wc_gzdp_get_order_address_differing_fields() as $field ) {
    	$b_getter = "get_billing_{$field}";
    	$s_getter = "get_shipping_{$field}";

        $b_data = $order->$b_getter();
        $s_data = $order->$s_getter();

        if ( $b_data !== $s_data ) {
            return true;
        }
    }

    return false;
}