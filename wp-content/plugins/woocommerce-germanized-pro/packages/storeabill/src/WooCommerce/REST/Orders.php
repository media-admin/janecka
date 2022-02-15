<?php
namespace Vendidero\StoreaBill\WooCommerce\REST;

use Vendidero\StoreaBill\WooCommerce\Helper;

defined( 'ABSPATH' ) || exit;

class Orders extends \WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'orders';

	/**
	 * Stores the request.
	 *
	 * @var array
	 */
	protected $request = array();

	/**
	 * Register the routes for orders.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)' . '/invoices',
			array(
				'args'   => array(
					'id' => array(
						'description' => _x( 'Unique identifier for the resource.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => array(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)' . '/invoices/sync',
			array(
				'args'   => array(
					'id' => array(
						'description' => _x( 'Unique identifier for the resource.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'sync' ),
					'permission_callback' => array( $this, 'batch_items_permissions_check' ),
					'args'                => array(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)' . '/invoices/finalize',
			array(
				'args'   => array(
					'id' => array(
						'description' => _x( 'Unique identifier for the resource.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'finalize' ),
					'permission_callback' => array( $this, 'batch_items_permissions_check' ),
					'args'                => array(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Check if a given request has access to read items.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! sab_rest_check_permissions( 'invoice', 'read' ) ) {
			return new \WP_Error( 'storeabill_rest_cannot_view', _x( 'Sorry, you cannot list resources.', 'storeabill-core', 'woocommerce-germanized-pro' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to read items.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function batch_items_permissions_check( $request ) {
		if ( ! sab_rest_check_permissions( 'invoice', 'batch' ) ) {
			return new \WP_Error( 'storeabill_rest_cannot_batch', _x( 'Sorry, you are not allowed to batch manipulate this resource.', 'storeabill-core', 'woocommerce-germanized-pro' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	public function sync( $request ) {
		$id = $request->get_param( 'id' );

		if ( ! $id || empty( $id ) || ! ( $order = Helper::get_order( $id ) ) ) {
			return new \WP_Error( 404, _x( 'Order ID not found', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}

		$order->sync_order();

		return $this->get_items( $request );
	}

	public function finalize( $request ) {
		$id = $request->get_param( 'id' );

		if ( ! $id || empty( $id ) || ! ( $order = Helper::get_order( $id ) ) ) {
			return new \WP_Error( 404, _x( 'Order ID not found', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}

		$order->finalize();

		return $this->get_items( $request );
	}

	/**
	 * Get a collection of posts.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_items( $request ) {
		$id = $request->get_param( 'id' );

		if ( ! $id || empty( $id ) || ! ( $order = Helper::get_order( $id ) ) ) {
			return new \WP_Error( 404, _x( 'Order ID not found', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}

		$objects = array();

		foreach( $order->get_documents() as $document ) {
			if ( ! sab_rest_check_permissions( $document->get_type(), 'read', $document->get_id() ) ) {
				continue;
			}

			$document_type = sab_get_document_type( $document->get_type() );
			$data          = false;

			if ( $controller = \Vendidero\StoreaBill\REST\Server::instance()->get_controller( $document_type->api_endpoint ) ) {
				$data = $controller->prepare_object_for_response( $document, $request );
			}

			if ( ! $data ) {
				continue;
			}

			$objects[] = $this->prepare_response_for_collection( $data );
		}

		$response = rest_ensure_response( $objects );

		return $response;
	}
}
