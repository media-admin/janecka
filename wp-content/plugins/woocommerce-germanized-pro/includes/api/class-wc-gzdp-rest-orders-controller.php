<?php
/**
 * Class WC_GZDP_REST_Orders_Controller
 *
 * @since 1.7.0
 * @author vendidero, Daniel Hüsken
 */
class WC_GZDP_REST_Orders_Controller {

	public function __construct() {
		add_filter( 'woocommerce_rest_prepare_shop_order_object', array( $this, 'prepare' ), 10, 3 );
		add_filter( 'woocommerce_rest_pre_insert_shop_order_object', array( $this, 'insert' ), 10, 3 );

		add_filter( 'woocommerce_rest_shop_order_schema', array( $this, 'schema' ) );
	}

	/**
	 * Filter customer data returned from the REST API.
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param WP_User $customer User object used to create response.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 *@since 1.0.0
	 * @wp-hook woocommerce_rest_prepare_order
	 *
	 */
	public function prepare( $response, $post, $request ) {
		$order = wc_get_order( $post );

		$response_order_data = $response->get_data();
		$response_order_data['billing']['vat_id']  = $order->get_meta( '_billing_vat_id' );
		$response_order_data['shipping']['vat_id'] = $order->get_meta( '_shipping_vat_id' );
		$response_order_data['needs_confirmation'] = wc_gzdp_order_needs_confirmation( $order->get_id() );

		$response->set_data( $response_order_data );

		return $response;
	}

	/**
	 * @param WC_Order $order
	 * @param $request
	 * @param $creating
	 *
	 * @return mixed
	 */
	public function insert( $order, $request, $creating ) {

		if ( isset( $request['billing']['vat_id'] ) ) {
			$order->update_meta_data( '_billing_vat_id', wc_clean( $request['billing']['vat_id'] ) );
		}

		if ( isset( $request['shipping']['vat_id'] ) ) {
			$order->update_meta_data( '_shipping_vat_id', wc_clean( $request['shipping']['vat_id'] ) );
		}

		if ( isset( $request['needs_confirmation'] ) ) {

			if ( $request['needs_confirmation'] ) {
				$order->update_meta_data( '_order_needs_confirmation', true );
			} elseif ( ! $creating && ! $request['needs_confirmation'] ) {

				// Check if current order needs confirmation and do only confirm once
				if ( wc_gzdp_order_needs_confirmation( $order->get_id() ) ) {
					WC_GZDP_Contract_Helper::instance()->confirm_order( $order->get_id() );
				}

				$order->delete_meta_data( '_order_needs_confirmation' );
			}
		}

		return $order;
	}

	/**
	 * Extend schema.
	 *
	 * @since 1.0.0
	 * @wp-hook woocommerce_rest_customer_schema
	 *
	 * @param array $schema_properties Data used to create the customer.
	 *
	 * @return array
	 */
	public function schema( $schema_properties ) {

		$schema_properties['billing']['properties']['vat_id'] = array(
			'description' => __( 'VAT ID', 'woocommerce-germanized-pro' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' )
		);

		$schema_properties['shipping']['properties']['vat_id'] = array(
			'description' => __( 'VAT ID', 'woocommerce-germanized-pro' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' )
		);

		$schema_properties['needs_confirmation'] = array(
			'description' => __( 'Whether an order needs confirmation or not.', 'woocommerce-germanized-pro' ),
			'type'        => 'boolean',
			'context'     => array( 'view', 'edit' ),
		);

		return $schema_properties;
	}

}
