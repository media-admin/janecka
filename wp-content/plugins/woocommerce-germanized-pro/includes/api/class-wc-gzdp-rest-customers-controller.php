<?php
/**
 * Class WC_GZDP_REST_Customers_Controller
 *
 * @since 1.7.0
 * @author vendidero, Daniel Hüsken
 */
class WC_GZDP_REST_Customers_Controller {

	public function __construct() {
		add_filter( 'woocommerce_rest_prepare_customer', array( $this, 'prepare' ), 10, 3 );
		add_action( 'woocommerce_rest_insert_customer', array( $this, 'insert' ), 10, 3 );
		add_filter( 'woocommerce_rest_customer_schema', array( $this, 'schema' ) );
	}

	/**
	 * Filter customer data returned from the REST API.
	 *
	 * @since 1.0.0
	 * @wp-hook woocommerce_rest_prepare_customer
	 *
	 * @param \WP_REST_Response $response The response object.
	 * @param \WP_User $customer User object used to create response.
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response
	 */
	public function prepare( $response, $customer, $request ) {

		$response_customer_data = $response->get_data();
		$response_customer_data['billing']['vat_id'] = $customer->billing_vat_id;
		$response->set_data( $response_customer_data );

		return $response;
	}

	/**
	 * Prepare a single customer for create or update.
	 *
	 * @since 1.0.0
	 * @wp-hook woocommerce_rest_insert_customer
	 *
	 * @param \WP_User $customer Data used to create the customer.
	 * @param \WP_REST_Request $request Request object.
	 * @param bool $creating True when creating item, false when updating.
	 */
	public function insert( $customer, $request, $creating ) {

		if ( isset( $request['billing']['vat_id'] ) ) {
			update_user_meta( $customer->ID, 'billing_vat_id', sanitize_text_field( $request['billing']['vat_id'] ) );
		}

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

		return $schema_properties;
	}

}
