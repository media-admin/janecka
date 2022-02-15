<?php

namespace Vendidero\StoreaBill\REST;

defined( 'ABSPATH' ) || exit;

use Vendidero\StoreaBill\Document\Document;
use Vendidero\StoreaBill\Document\Item;
use Vendidero\StoreaBill\Invoice\Invoice;
use Vendidero\StoreaBill\Invoice\Query;
use Vendidero\StoreaBill\TaxRate;

use WP_Error;
use WC_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WC_Data;
use WP_REST_Response;
use WC_Data_Exception;
use WC_REST_Exception;

/**
 * Invoice Controller class.
 */
class CancellationController extends InvoiceController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'cancellations';

	protected function get_type() {
		return 'cancellation';
	}

	/**
	 * Register the routes for invoices.
	 */
	public function register_routes() {
		DocumentController::register_routes();
	}

	/**
	 * Create a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		return new WP_Error( "storeabill_rest_{$this->get_data_type()}_cannot_create", sprintf( _x( 'Cancellations cannot be created. Please cancel a specific invoice instead.', 'storeabill-core', 'woocommerce-germanized-pro' ), $this->get_data_type() ), array( 'status' => 400 ) );
	}

	public function get_item_schema() {
		$schema = parent::get_item_schema();

		unset( $schema['properties']['sync'] );

		$schema['properties'] = array_merge( $schema['properties'], array(
			'parent_number'   => array(
				'description' => _x( 'Parent invoice number.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'label'       => _x( 'Parent number', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'parent_formatted_number' => array(
				'description' => _x( 'Parent formatted invoice number.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'label'       => _x( 'Parent formatted number', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'refund_order_id' => array(
				'description' => _x( 'Refund order id if available.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'integer',
				'label'       => _x( 'Order refund ID', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'default'     => 0,
				'context'     => array( 'view', 'edit' ),
			),
			'refund_order_number' => array(
				'description' => _x( 'Refund order number if available.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'label'       => _x( 'Order refund number', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'default'     => '',
				'context'     => array( 'view', 'edit' ),
			),
		) );

		return $schema;
	}
}