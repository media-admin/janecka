<?php

namespace Vendidero\StoreaBill\REST;

defined( 'ABSPATH' ) || exit;

use Vendidero\StoreaBill\Document\AsyncExporter;
use Vendidero\StoreaBill\Document\Document;
use Vendidero\StoreaBill\Document\Item;
use Vendidero\StoreaBill\Invoice;
use Vendidero\StoreaBill\Invoice\TaxableItem;
use Vendidero\StoreaBill\References\Product;
use Vendidero\StoreaBill\TaxRate;

use WC_REST_Exception;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;
use WP_REST_Response;

/**
 * Document Controller class.
 */
abstract class DocumentController extends Controller {

	abstract protected function get_type();

	public function get_document_type() {
		return $this->get_data_type() . ( 'simple' === $this->get_type() ? '' : '_' . $this->get_type() );
	}

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/preview',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_preview' ),
					'permission_callback' => array( $this, 'get_preview_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'edit' ) ),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/export',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_export' ),
					'permission_callback' => array( $this, 'create_export_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_public_export_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/export/(?P<id>[\w]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_export' ),
					'permission_callback' => array( $this, 'get_export_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'edit' ) ),
					),
				),
				'schema' => array( $this, 'get_public_export_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/export/(?P<id>[\w]+)/download',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'download_export' ),
					'permission_callback' => array( $this, 'get_export_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'edit' ) ),
					),
				)
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/pdf',
			array(
				'args'   => array(
					'id' => array(
						'description' => _x( 'Unique identifier for the resource.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_pdf' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'id'                   => array(
							'description' => _x( 'Unique identifier for the resource.', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
				'schema' => array( $this, 'get_public_pdf_schema' )
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => _x( 'Unique identifier for the resource.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'force' => array(
							'default'     => false,
							'type'        => 'boolean',
							'description' => _x( 'Whether to bypass archive and force deletion.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/batch',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'batch_items' ),
					'permission_callback' => array( $this, 'batch_items_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				'schema' => array( $this, 'get_public_batch_schema' ),
			)
		);
	}

	public function get_public_export_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'export',
			'type'       => 'object',
			'properties' => array(
				'id'              => array(
					'description' => _x( 'Unique identifier for the resource.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'ID', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'type' => array(
					'description' => _x( 'The export type, e.g. csv.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'start_date'      => array(
					'description' => _x( "The export start date, in the site's timezone.", 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'end_date'      => array(
					'description' => _x( "The export end date, in the site's timezone.", 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'filters' => array(
					'description' => _x( 'Export filters.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type' => 'string',
					),
				),
				'status' => array(
					'description' => _x( 'The export status, e.g. running.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'enum',
					'options'     => array( 'running', 'complete' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'percent_complete' => array(
					'description' => _x( 'Percent completed.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			)
		);
	}

	public function get_public_pdf_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'pdf',
			'type'       => 'object',
			'properties' => array(
				'file' => array(
					'description' => _x( 'The file data (base64 encoded).', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'binary',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'path' => array(
					'description' => _x( 'Local path to file in case it exists.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'filename' => array(
					'description' => _x( 'The filename', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'is_preview' => array(
					'description' => _x( 'Whether it is a preview or the file is persisted.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			)
		);
	}

	/**
	 * Get a single export.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_export( $request ) {
		try {
			$exporter = new AsyncExporter( sab_clean( $request['id'] ) );
		} catch( \Exception $e ) {
			return new WP_Error( "storeabill_rest_{$this->get_data_type()}_export_invalid_id", _x( 'Invalid export ID.', 'storeabill-core', 'woocommerce-germanized-pro' ), array( 'status' => 404 ) );
		}

		if ( ! $exporter->exists() || $exporter->get_document_type() !== $this->get_document_type() ) {
			return new WP_Error( "storeabill_rest_{$this->get_data_type()}_export_invalid_id", _x( 'Invalid export ID.', 'storeabill-core', 'woocommerce-germanized-pro' ), array( 'status' => 404 ) );
		}

		/**
		 * Next step
		 */
		if ( 'complete' !== $exporter->get_status() ) {
			$result = $exporter->next();

			if ( is_wp_error( $result ) ) {
				$exporter->delete();
				$result->add_data( array( 'status' => 400 ) );

				return $result;
			}
		}

		$data     = $this->prepare_export_for_response( $exporter, $request );
		$response = rest_ensure_response( $data );

		$response->add_links( array(
			'self'       => array(
				'href' => rest_url( sprintf( '/%s/%s/export/%s', $this->namespace, $this->rest_base, $exporter->get_id() ) ),
			),
			'download' => array(
				'href' => rest_url( sprintf( '/%s/%s/export/%s/download', $this->namespace, $this->rest_base, $exporter->get_id() ) ),
			),
		) );

		return $response;
	}

	public function download_export( $request ) {
		try {
			$exporter = new AsyncExporter( sab_clean( $request['id'] ) );
		} catch( \Exception $e ) {
			return new WP_Error( "storeabill_rest_{$this->get_data_type()}_export_invalid_id", _x( 'Invalid export ID.', 'storeabill-core', 'woocommerce-germanized-pro' ), array( 'status' => 404 ) );
		}

		if ( ! $exporter->exists() || ! 'complete' === $exporter->get_status() || $exporter->get_document_type() !== $this->get_document_type() ) {
			return new WP_Error( "storeabill_rest_{$this->get_data_type()}_export_invalid_id", _x( 'Invalid export ID.', 'storeabill-core', 'woocommerce-germanized-pro' ), array( 'status' => 404 ) );
		}

		$exporter->export();
	}

	/**
	 * Start a new export.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_export( $request ) {
		$id = sab_get_random_key();

		while( get_transient( "sab_export_{$id}" ) ) {
			$id = sab_get_random_key();
		}

		$args = array(
			'document_type' => $this->get_document_type(),
			'type'          => $request['type'],
			'filters'       => $request['filters'],
			'start_date'    => $request['start_date'],
			'end_date'      => $request['end_date'],
		);

		try {
			$exporter = new AsyncExporter( $id, $args );
			$result   = $exporter->next();

			if ( is_wp_error( $result ) ) {
				$result->add_data( array( 'status' => 400 ) );

				return $result;
			}

			$request->set_param( 'context', 'edit' );

			$response = $this->prepare_export_for_response( $exporter, $request );
			$response = rest_ensure_response( $response );

			$response->set_status( 201 );
			$response->header( 'Location', rest_url( sprintf( '/%s/%s/export/%d', $this->namespace, $this->rest_base, $exporter->get_id() ) ) );
		} catch( \Exception $e ) {
			/* translators: %s: error message */
			return new WP_Error( "storeabill_rest_{$this->get_data_type()}_export_error", sprintf( _x( 'Cannot create export: %1$s.', 'storeabill-core', 'woocommerce-germanized-pro' ), $e->getMessage() ), array( 'status' => 400 ) );
		}

		return $response;
	}

	/**
	 * @param AsyncExporter $export
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	protected function prepare_export_for_response( $export, $request ) {
		$data = array(
			'id'               => $export->get_id(),
			'type'             => $export->get_type(),
			'status'           => $export->get_status(),
			'start_date'       => wc_rest_prepare_date_response( $export->get_start_date(), false ),
			'end_date'         => wc_rest_prepare_date_response( $export->get_end_date(), false ),
			'filters'          => $export->get_filters(),
			'percent_complete' => $export->get_percent_complete(),
		);

		$context  = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$schema   = $this->get_public_export_schema();
		$data     = rest_filter_response_by_context( $data, $schema, $context );
		$response = rest_ensure_response( $data );

		return $response;
	}

	/**
	 * Get a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_pdf( $request ) {
		$object = $this->get_object( (int) $request['id'] );

		if ( ! $object || 0 === $object->get_id() ) {
			return new WP_Error( "storeabill_rest_{$this->get_data_type()}_invalid_id", _x( 'Invalid ID.', 'storeabill-core', 'woocommerce-germanized-pro' ), array( 'status' => 404 ) );
		}

		if ( ! $object->is_editable() && ! $object->has_file() ) {
			$result = $object->render();

			if ( is_wp_error( $result ) ) {
				return rest_ensure_response( $result );
			}
		}

		$result = false;
		$data   = array(
			'file'       => '',
			'is_preview' => false,
			'path'       => '',
			'filename'   => ''
		);

		if ( $object->has_file() ) {
			try {
				$result = file_get_contents( $object->get_path() );
			} catch( \Exception $ex){
				$result = new WP_Error( 'render-error', _x( 'Error while reading document.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
			}
		} else {
			$result             = $object->preview( false );
			$data['is_preview'] = true;
		}

		if ( is_wp_error( $result ) ) {
			return rest_ensure_response( $result );
		}

		try {
			$data['file'] = chunk_split( base64_encode( $result ) );
		} catch( \Exception $ex){
			return rest_ensure_response( new WP_Error( 'render-error', _x( 'Error while reading document.', 'storeabill-core', 'woocommerce-germanized-pro' ) ) );
		}

		$data['filename'] = $object->get_filename();
		$data['path']     = $object->has_file() ? $object->get_path() : '';

		$response = rest_ensure_response( $data );

		return $response;
	}

	protected function get_additional_collection_params() {
		$params = array();

		$params['status'] = array(
			'default'           => 'any',
			'description'       => _x( 'Limit result set to documents which have specific statuses.', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'string',
				'enum' => array_merge( array( 'any' ), array_keys( sab_get_document_statuses( $this->get_document_type() ) ) ),
			),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['reference_id'] = array(
			'default'            => 0,
			'description'        => _x( 'Limit result set to documents which have a specific reference id.', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'type'               => 'number',
			'sanitize_callback'  => 'absint',
			'validate_callback'  => 'rest_validate_request_arg',
		);

		$params['reference_type'] = array(
			'default'            => '',
			'description'        => _x( 'Limit result set to documents which have a specific reference type.', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'type'               => 'string',
			'validate_callback'  => 'rest_validate_request_arg',
		);

		return $params;
	}

	/**
	 * Get a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_preview( $request ) {
		$object = $this->get_preview_object();

		if ( ! $object ) {
			return new WP_Error( "storeabill_rest_{$this->get_data_type()}_invalid_id", _x( 'Invalid ID.', 'storeabill-core', 'woocommerce-germanized-pro' ), array( 'status' => 404 ) );
		}

		$data     = $this->prepare_object_for_response( $object, $request );
		$response = rest_ensure_response( $data );

		return $response;
	}

	/**
	 * Check if a given request has access to read an item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_preview_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_storeabill' ) ) {
			return new \WP_Error( "storeabill_rest_cannot_view", _x( 'Sorry, you cannot view this resource.', 'storeabill-core', 'woocommerce-germanized-pro' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to read an item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_export_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_storeabill' ) ) {
			return new \WP_Error( "storeabill_rest_cannot_view", _x( 'Sorry, you cannot view this resource.', 'storeabill-core', 'woocommerce-germanized-pro' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to read an item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function create_export_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_storeabill' ) ) {
			return new WP_Error( 'storeabill_rest_cannot_create', _x( 'Sorry, you are not allowed to create resources.', 'storeabill-core', 'woocommerce-germanized-pro' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 *
	 * @return bool|Document
	 */
	public function get_preview_object() {
		return sab_get_document_preview( $this->get_document_type(), true );
	}

	/**
	 * Update address.
	 *
	 * @param Document $document document data.
	 * @param array    $posted Posted data.
	 * @param string   $type   Address type.
	 */
	protected function update_address( $document, $posted ) {
		$document->set_address( $posted );

		// Override by explicitly calling existing setters
		foreach ( $posted as $key => $value ) {
			if ( is_callable( array( $document, "set_{$key}" ) ) ) {
				$document->{"set_{$key}"}( $value );
			}
		}
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param Document         $object  Object data.
	 * @param WP_REST_Request $request Request object.
	 * @return array           Links for the given post.
	 */
	protected function prepare_links( $object, $request ) {
		$links = array(
			'self'       => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $object->get_id() ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			),
			'pdf' => array(
				'href' => rest_url( sprintf( '/%s/%s/%d/pdf', $this->namespace, $this->rest_base, $object->get_id() ) ),
			),
		);

		if ( 0 !== (int) $object->get_customer_id() ) {
			$links['customer'] = array(
				'href' => rest_url( sprintf( '/%s/customers/%d', 'wc/v3', $object->get_customer_id() ) ),
			);
		}

		if ( 0 !== (int) $object->get_parent_id() ) {
			$links['up'] = array(
				'href' => rest_url( sprintf( '/%s/orders/%d', $this->namespace, $object->get_parent_id() ) ),
			);
		}

		return $links;
	}

	/**
	 * Helper method to check if the resource ID associated with the provided item is null.
	 * Items can be deleted by setting the resource ID to null.
	 *
	 * @param array $item Item provided in the request body.
	 * @return bool True if the item resource ID is null, false otherwise.
	 */
	protected function item_is_null( $item ) {
		$keys = array( 'product_id', 'reference_id', 'name' );

		foreach ( $keys as $key ) {
			if ( array_key_exists( $key, $item ) && is_null( $item[ $key ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Prepare objects query.
	 *
	 * @since  3.0.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args         = parent::prepare_objects_query( $request );
		$args['type'] = $this->get_document_type();

		if ( isset( $request['reference_id'] ) && ! empty( $request['reference_id'] ) ) {
			$args['reference_id'] = $request['reference_id'];
		}

		if ( isset( $request['reference_type'] ) && ! empty( $request['reference_type'] ) ) {
			$args['reference_type'] = $request['reference_type'];
		}

		return $args;
	}

	/**
	 * Wrapper method to handle items.
	 *
	 * @param Document $document Document object.
	 * @param array    $items Item data.
	 * @param string   $item_type The item type.
	 *
	 * @throws WC_REST_Exception If item ID is not associated with order.
	 */
	protected function prepare_items_for_database( $document, $items, $item_type ) {
		$item_types_supported = $document->get_item_types();

		if ( ! in_array( $item_type, $item_types_supported ) ) {
			throw new WC_REST_Exception( 'storeabill_rest_invalid_item_type', sprintf( _x( 'Document item type %s is not supported by this document.', 'storeabill-core', 'woocommerce-germanized-pro' ), $item_type ), 400 );
		}

		if ( is_array( $items ) ) {
			foreach ( $items as $item ) {
				if ( is_array( $item ) ) {
					if ( $this->item_is_null( $item ) || ( isset( $item['quantity'] ) && 0 === $item['quantity'] ) ) {
						$document->remove_item( $item['id'] );
					} else {
						$this->set_item( $document, $item_type, $item );
					}
				}
			}
		}
	}

	/**
	 * Prepare a single order output for response.
	 *
	 * @since  3.0.0
	 * @param  \WC_Data        $object  Object data.
	 * @param  WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function prepare_object_for_response( $object, $request ) {
		$this->request       = $request;
		$this->request['dp'] = is_null( $this->request['dp'] ) ? sab_get_price_decimals() : absint( $this->request['dp'] );

		$data                = $this->get_formatted_item_data( $object );
		$context             = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data                = $this->add_additional_fields_to_object( $data, $request );
		$data                = $this->filter_response_by_context( $data, $context );
		$response            = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $object, $request ) );

		/**
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->post_type,
		 * refers to object type being prepared for the response.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param \WC_Data         $object   Object data.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( "storeabill_rest_prepare_{$this->get_document_type()}_object", $response, $object, $request );
	}

	/**
	 * Wrapper method to create/update document items.
	 * When updating, the item ID provided is checked to ensure it is associated
	 * with the order.
	 *
	 * @param Document $document Document object.
	 * @param string   $item_type The item type.
	 * @param array    $posted item provided in the request body.
	 *
	 * @throws WC_REST_Exception If item ID is not associated with order.
	 */
	protected function set_item( $document, $item_type, $posted ) {
		global $wpdb;

		if ( ! empty( $posted['id'] ) ) {
			$action = 'update';
		} else {
			$action = 'create';
		}

		$item   = null;

		// Verify provided line item ID is associated with order.
		if ( 'update' === $action ) {
			$item = $document->get_item( absint( $posted['id'] ) );

			if ( ! $item ) {
				throw new WC_REST_Exception( 'storeabill_rest_invalid_item_id', _x( 'Document item ID provided is not associated with document.', 'storeabill-core', 'woocommerce-germanized-pro' ), 400 );
			}
		}

		// Prepare item data.
		$method = 'prepare_items';
		$item   = $this->$method( $posted, $item_type, $action, $item );

		do_action( "storeabill_rest_set_{$this->get_data_type()}_item", $item, $posted );

		// If creating the document, add the item to it.
		if ( 'create' === $action ) {
			$document->add_item( $item );
		} else {
			$item->save();
		}
	}

	/**
	 * Create or update a line item.
	 *
	 * @param array               $posted Line item data.
	 * @param TaxableItem $item Passed item.
	 * @param string              $action 'create' to add line item or 'update' to update it.
	 *
	 * @throws WC_REST_Exception Invalid data, server error.
	 */
	protected function prepare_product_items( $posted, $item, $action = 'create' ) {
		$product_id = $this->get_product_id( $posted );

		if ( ! empty( $product_id ) && ( $document = $item->get_document() ) ) {
			$product = Product::get_product( $product_id, $document->get_reference_type() );

			if ( $product !== $item->get_product() ) {
				$item->set_product( $product );

				if ( 'create' === $action ) {
					$quantity = isset( $posted['quantity'] ) ? $posted['quantity'] : 1;
					$total    = wc_get_price_excluding_tax( $product->get_product(), array( 'qty' => $quantity ) );

					$item->set_line_total( $total );
					$item->set_line_subtotal( $total );
				}
			}
		}
	}

	/**
	 * @param $item_type
	 */
	protected function maybe_prefix_item_type( $item_type ) {
		return sab_maybe_prefix_document_item_type( $item_type, $this->get_document_type() );
	}

	/**
	 * Create or update a line item.
	 *
	 * @param array  $posted Line item data.
	 * @param string $item_type The item type.
	 * @param string $action 'create' to add line item or 'update' to update it.
	 * @param TaxableItem|null $item Passed when updating an item. Null during creation.
	 *
	 * @return Item
	 * @throws WC_REST_Exception Invalid data, server error.
	 */
	protected function prepare_items( $posted, $item_type = 'product', $action = 'create', $item = null ) {
		$item = is_null( $item ) ? sab_get_document_item( ( ! empty( $posted['id'] ) ? $posted['id'] : 0 ), $this->maybe_prefix_item_type( $item_type ) ) : $item;

		if ( ! $item ) {
			throw new WC_REST_Exception( 'storeabill_rest_invalid_item', _x( 'Document item provided is invalid.', 'storeabill-core', 'woocommerce-germanized-pro' ), 400 );
		}

		// Prepare item data.
		$method = 'prepare_' . $item_type . '_items';

		if ( is_callable( array( $this, $method ) ) ) {
			$this->$method( $posted, $item, $action );
		}

		$this->maybe_set_tax_rates( $item, $posted );
		$this->maybe_set_item_props( $item, array( 'name', 'quantity', 'line_total', 'line_subtotal', 'price', 'attributes' ), $posted );
		$this->maybe_set_item_meta_data( $item, $posted );

		return $item;
	}

	/**
	 * Maybe set tax rates if available.
	 *
	 * @param TaxableItem $item Document item data.
	 * @param array               $posted Request data.
	 */
	protected function maybe_set_tax_rates( $item, $posted ) {
		$tax_rates_raw      = array_key_exists( 'tax_rates', $posted ) ? (array) $posted['tax_rates'] : null;
		$posted_tax_rates   = array();
		$existing_tax_rates = $item->get_tax_rates();

		if ( ! is_null( $tax_rates_raw ) ) {

			foreach( $tax_rates_raw as $tax_rate_data ) {
				$tax_rate = new TaxRate( $tax_rate_data );
				$posted_tax_rates[ $tax_rate->get_merge_key() ] = $tax_rate;
			}

			if ( array_keys( $posted_tax_rates ) !== array_keys( $existing_tax_rates ) ) {
				$item->remove_taxes();

				foreach( $posted_tax_rates as $tax_rate ) {
					$item->add_tax_rate( $tax_rate );
				}
			}
		}
	}

	/**
	 * Gets the product ID from the SKU or posted ID.
	 *
	 * @param array $posted Request data.
	 * @return int
	 */
	protected function get_product_id( $posted ) {
		$product_id = 0;

		if ( ! empty( $posted['sku'] ) ) {
			$product_id = (int) wc_get_product_id_by_sku( $posted['sku'] );
		} elseif ( ! empty( $posted['product_id'] ) && empty( $posted['variation_id'] ) ) {
			$product_id = (int) $posted['product_id'];
		} elseif ( ! empty( $posted['variation_id'] ) ) {
			$product_id = (int) $posted['variation_id'];
		}

		return $product_id;
	}

	/**
	 * Maybe set item meta if posted.
	 *
	 * @param Item  $item   Order item data.
	 * @param array         $posted Request data.
	 */
	protected function maybe_set_item_meta_data( $item, $posted ) {
		if ( ! empty( $posted['meta_data'] ) && is_array( $posted['meta_data'] ) ) {
			foreach ( $posted['meta_data'] as $meta ) {
				if ( isset( $meta['key'] ) ) {
					$value = isset( $meta['value'] ) ? $meta['value'] : null;
					$item->update_meta_data( $meta['key'], $value, isset( $meta['id'] ) ? $meta['id'] : '' );
				}
			}
		}
	}

	/**
	 * Maybe set an item prop if the value was posted.
	 *
	 * @param Item  $item   Document item.
	 * @param string        $prop   Order property.
	 * @param array         $posted Request data.
	 */
	protected function maybe_set_item_prop( $item, $prop, $posted ) {
		$setter = "set_$prop";

		if ( isset( $posted[ $prop ] ) && is_callable( array( $item, $setter ) ) ) {
			$item->$setter( $posted[ $prop ] );
		}
	}

	/**
	 * Maybe set item props if the values were posted.
	 *
	 * @param Item  $item   Document item data.
	 * @param string[]      $props  Properties.
	 * @param array         $posted Request data.
	 */
	protected function maybe_set_item_props( $item, $props, $posted ) {
		foreach ( $props as $prop ) {
			$this->maybe_set_item_prop( $item, $prop, $posted );
		}
	}

	/**
	 * Get document statuses.
	 *
	 * @return array
	 */
	protected function get_document_statuses() {
		$statuses = array_keys( sab_get_document_statuses( $this->get_document_type() ) );

		return $statuses;
	}

	protected function get_price_fields() {
		return array( 'discount_total', 'discount_tax', 'shipping_total', 'shipping_tax', 'product_total', 'product_tax', 'fee_total', 'fee_tax', 'total', 'total_tax' );
	}

	protected function get_date_fields() {
		return array( 'date_created', 'date_modified', 'date_sent' );
	}

	protected function get_item_price_fields() {
		return array(
			'line_subtotal',
			'subtotal_tax',
			'line_total',
			'total_tax',
			'discount_total',
			'price',
			'price_subtotal',
			'price_net',
			'price_subtotal_net',
			'price_tax',
			'total_net',
			'subtotal_net',
			'total',
			'subtotal'
		);
	}

	/**
	 * Decides whether the current request is a preview request or not.
	 *
	 * @param WP_REST_Request|Document $request Full details about the request.
	 * @return boolean
	 */
	protected function is_preview_request() {

		if ( $this->request ) {
			$route = '/' . $this->namespace . '/' . $this->rest_base . '/preview';

			if ( $this->request->get_route() === $route ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get formatted item data.
	 *
	 * @since  3.0.0
	 * @param  Document $object document instance.
	 * @return array
	 */
	protected function get_formatted_item_data( $object ) {
		$data                 = $object->get_data();
		$format_price         = $this->get_price_fields();
		$format_date          = $this->get_date_fields();
		$item_types           = $object->get_item_types();
		$is_preview           = $this->is_preview_request();
		$item_types_postfixed = array();

		foreach( $item_types as $type ) {
			$item_types_postfixed[] = $type . '_items';
		}

		// Format decimal values.
		foreach ( $format_price as $key ) {
			if ( ! array_key_exists( $key, $data ) ) {
				continue;
			}

			$data[ $key ] = sab_format_decimal( $data[ $key ], $this->request['dp'] );

			if ( $is_preview ) {
				$data[ $key . '_formatted' ] = ( is_callable( array( $object, 'get_formatted_price' ) ) ) ? $object->get_formatted_price( $data[ $key ], $key ) : sab_format_price( $data[ $key ] );
			}
		}

		// Format date values.
		foreach ( $format_date as $key ) {
			if ( ! array_key_exists( $key, $data ) ) {
				continue;
			}

			$datetime              = $data[ $key ];
			$data[ $key ]          = wc_rest_prepare_date_response( $datetime, false );
			$data[ $key . '_gmt' ] = wc_rest_prepare_date_response( $datetime );
		}

		// Format line items.
		foreach ( $item_types_postfixed as $key ) {
			if ( ! array_key_exists( $key, $data ) ) {
				continue;
			}

			$data[ $key ] = array_values( array_map( function( $item ) use ( $object ) {
				return $this->get_item_data( $item, $object );
			}, $data[ $key ] ) );
		}

		return $data;
	}

	/**
	 * @param Item $item
	 * @param $price
	 */
	protected function format_item_price( $item, $price, $key = '' ) {
		if ( $document = $item->get_document() ) {
			if ( is_callable( array( $document, 'get_formatted_price' ) ) ) {
				return $document->get_formatted_price( $price, $key );
			}
		}

		return sab_format_price( $price );
	}

	/**
	 * Expands a document item to get its data.
	 *
	 * @param Item $item Document item data.
	 * @param Document $object Document item data.
	 *
	 * @return array
	 */
	protected function get_item_data( $item, $object ) {
		$data           = $item->get_data();
		$format_decimal = $this->get_item_price_fields();
		$is_preview     = $this->is_preview_request();

		// Format decimal values.
		foreach ( $format_decimal as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$data[ $key ] = sab_format_decimal( $data[ $key ], $this->request['dp'] );

				if ( $is_preview ) {
					$data[ $key . '_formatted' ] = $this->format_item_price( $item, $data[ $key ], $key );
				}
			}
		}

		if ( $is_preview && isset( $data['discount_percentage'] ) ) {
			$data['discount_percentage_formatted'] = sab_format_percentage( $data['discount_percentage'], array( 'html' => true ) );
		}

		/**
		 * Add formatted attribute data to previews.
		 */
		if ( $is_preview ) {
			$data['attributes_formatted'] = sab_display_item_attributes( $item, array( 'echo' => false ) );
		}

		return $data;
	}

}