<?php

namespace Vendidero\StoreaBill\REST;

defined( 'ABSPATH' ) || exit;

use WP_Error;
use WC_REST_Controller;
use WP_REST_Request;
use WC_Data;
use WP_REST_Response;
use WC_Data_Exception;
use WC_REST_Exception;

/**
 * Controller class.
 */
abstract class Controller extends WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'sab/v1';

	/**
	 * Stores the request.
	 *
	 * @var array
	 */
	protected $request = array();

	abstract protected function get_data_type();

	public function get_endpoint() {
		return $this->namespace . '/' . $this->rest_base;
	}

	/**
	 * Get object.
	 *
	 * @param  int $id Object ID.
	 * @return WC_Data|WP_Error object or WP_Error object.
	 */
	protected function get_object( $id ) {
		// translators: %s: Class method name.
		return new WP_Error( 'invalid-method', sprintf( _x( "Method '%s' not implemented. Must be overridden in subclass.", 'storeabill-core', 'woocommerce-germanized-pro' ), __METHOD__ ), array( 'status' => 405 ) );
	}

	/**
	 * Only return writable props from schema.
	 *
	 * @param  array $schema Schema.
	 * @return bool
	 */
	protected function filter_writable_props( $schema ) {
		return empty( $schema['readonly'] );
	}

	/**
	 * Check if a given request has access to read an item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		$object = $this->get_object( (int) $request['id'] );

		if ( $object && 0 !== $object->get_id() && ! sab_rest_check_permissions( $this->get_data_type(), 'read', $object->get_id() ) ) {
			return new WP_Error( 'storeabill_rest_cannot_view', _x( 'Sorry, you cannot view this resource.', 'storeabill-core', 'woocommerce-germanized-pro' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to create an item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {

		if ( ! sab_rest_check_permissions( $this->get_data_type(), 'create' ) ) {
			return new WP_Error( 'storeabill_rest_cannot_create', _x( 'Sorry, you are not allowed to create resources.', 'storeabill-core', 'woocommerce-germanized-pro' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to read items.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {

		if ( ! sab_rest_check_permissions( $this->get_data_type(), 'read' ) ) {
			return new WP_Error( 'storeabill_rest_cannot_view', _x( 'Sorry, you cannot list resources.', 'storeabill-core', 'woocommerce-germanized-pro' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to update an item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {
		$object = $this->get_object( (int) $request['id'] );

		if ( $object && 0 !== $object->get_id() && ! sab_rest_check_permissions( $this->get_data_type(), 'edit', $object->get_id() ) ) {
			return new WP_Error( 'storeabill_rest_cannot_edit', _x( 'Sorry, you are not allowed to edit this resource.', 'storeabill-core', 'woocommerce-germanized-pro' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access batch create, update and delete items.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return boolean|WP_Error
	 */
	public function batch_items_permissions_check( $request ) {

		if ( ! sab_rest_check_permissions( $this->get_data_type(), 'batch' ) ) {
			return new WP_Error( 'storeabill_rest_cannot_batch', _x( 'Sorry, you are not allowed to batch manipulate this resource.', 'storeabill-core', 'woocommerce-germanized-pro' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to delete an item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		$object = $this->get_object( (int) $request['id'] );

		if ( $object && 0 !== $object->get_id() && ! sab_rest_check_permissions( $this->get_data_type(), 'delete', $object->get_id() ) ) {
			return new WP_Error( 'storeabill_rest_cannot_delete', _x( 'Sorry, you are not allowed to delete this resource.', 'storeabill-core', 'woocommerce-germanized-pro' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Prepares the object for the REST response.
	 *
	 * @since  3.0.0
	 * @param  WC_Data         $object  Object data.
	 * @param  WP_REST_Request $request Request object.
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	protected function prepare_object_for_response( $object, $request ) {
		// translators: %s: Class method name.
		return new WP_Error( 'invalid-method', sprintf( _x( "Method '%s' not implemented. Must be overridden in subclass.", 'storeabill-core', 'woocommerce-germanized-pro' ), __METHOD__ ), array( 'status' => 405 ) );
	}

	/**
	 * Prepares one object for create or update operation.
	 *
	 * @since  3.0.0
	 * @param  WP_REST_Request $request Request object.
	 * @param  bool            $creating If is creating a new object.
	 * @return WP_Error|WC_Data The prepared item, or WP_Error object on failure.
	 */
	protected function prepare_object_for_database( $request, $creating = false ) {
		// translators: %s: Class method name.
		return new WP_Error( 'invalid-method', sprintf( _x( "Method '%s' not implemented. Must be overridden in subclass.", 'storeabill-core', 'woocommerce-germanized-pro' ), __METHOD__ ), array( 'status' => 405 ) );
	}

	/**
	 * Get a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$object = $this->get_object( (int) $request['id'] );

		if ( ! $object || 0 === $object->get_id() ) {
			return new WP_Error( "storeabill_rest_{$this->get_data_type()}_invalid_id", _x( 'Invalid ID.', 'storeabill-core', 'woocommerce-germanized-pro' ), array( 'status' => 404 ) );
		}

		$data     = $this->prepare_object_for_response( $object, $request );
		$response = rest_ensure_response( $data );

		return $response;
	}

	/**
	 * Save an object data.
	 *
	 * @since  3.0.0
	 * @param  WP_REST_Request $request  Full details about the request.
	 * @param  bool            $creating If is creating a new object.
	 * @return WC_Data|WP_Error
	 */
	protected function save_object( $request, $creating = false ) {
		try {
			$object = $this->prepare_object_for_database( $request, $creating );

			if ( is_wp_error( $object ) ) {
				return $object;
			}

			$object->save();

			return $this->get_object( $object->get_id() );
		} catch ( WC_Data_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		} catch ( WC_REST_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Create a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		if ( ! empty( $request['id'] ) ) {
			/* translators: %s: object type */
			return new WP_Error( "storeabill_rest_{$this->get_data_type()}_exists", sprintf( _x( 'Cannot create existing %s.', 'storeabill-core', 'woocommerce-germanized-pro' ), $this->get_data_type() ), array( 'status' => 400 ) );
		}

		$object = $this->save_object( $request, true );

		if ( is_wp_error( $object ) ) {
			return $object;
		}

		try {
			$this->update_additional_fields_for_object( $object, $request );

			/**
			 * Fires after a single object is created or updated via the REST API.
			 *
			 * @param WC_Data         $object    Inserted object.
			 * @param WP_REST_Request $request   Request object.
			 * @param boolean         $creating  True when creating object, false when updating.
			 */
			do_action( "storeabill_rest_insert_{$this->get_data_type()}_object", $object, $request, true );
		} catch ( WC_Data_Exception $e ) {
			$object->delete();
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		} catch ( WC_REST_Exception $e ) {
			$object->delete();
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_object_for_response( $object, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $object->get_id() ) ) );

		return $response;
	}

	/**
	 * Update a single post.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		$object = $this->get_object( (int) $request['id'] );

		if ( ! $object || 0 === $object->get_id() ) {
			return new WP_Error( "storeabill_rest_{$this->get_data_type()}_invalid_id", _x( 'Invalid ID.', 'storeabill-core', 'woocommerce-germanized-pro' ), array( 'status' => 400 ) );
		}

		$object = $this->save_object( $request, false );

		if ( is_wp_error( $object ) ) {
			return $object;
		}

		try {
			$this->update_additional_fields_for_object( $object, $request );

			/**
			 * Fires after a single object is created or updated via the REST API.
			 *
			 * @param WC_Data         $object    Inserted object.
			 * @param WP_REST_Request $request   Request object.
			 * @param boolean         $creating  True when creating object, false when updating.
			 */
			do_action( "storeabill_rest_insert_{$this->get_data_type()}_object", $object, $request, false );
		} catch ( WC_Data_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		} catch ( \WC_REST_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_object_for_response( $object, $request );
		return rest_ensure_response( $response );
	}

	/**
	 * Get objects.
	 *
	 * @since  3.0.0
	 * @param  array $query_args Query args.
	 * @return array
	 */
	abstract protected function get_objects( $query_args );

	/**
	 * Prepare objects query.
	 *
	 * @since  3.0.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args                        = array();
		$args['offset']              = $request['offset'];
		$args['order']               = $request['order'];
		$args['orderby']             = $request['orderby'];
		$args['page']                = $request['page'];
		$args['include']             = $request['include'];
		$args['exclude']             = $request['exclude'];
		$args['limit']               = $request['per_page'];
		$args['parent_id']           = $request['parent'];
		$args['search']              = $request['search'];

		if( 'id' === $args['orderby'] ) {
			$args['orderby'] = 'ID';
		}

		// Set before into date query. Date query must be specified as an array of an array.
		if ( isset( $request['before'] ) && isset( $request['after'] ) ) {
			$args['date_created'] = $request['after'] . '...' . $request['before'];
		} elseif( isset( $request['after'] ) ) {
			$args['date_created'] = '>' . $request['after'];
		} elseif( isset( $request['before'] ) ) {
			$args['date_created'] = '<' . $request['before'];
		}

		if ( isset( $request['status'] ) ) {
			$args['status'] = $request['status'];
		}

		// Force pagination
		$args['paginate'] = true;

		/**
		 * Filter the query arguments for a request.
		 *
		 * Enables adding extra arguments or setting defaults for a post
		 * collection request.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request used.
		 */
		$args = apply_filters( "storeabill_rest_{$this->get_data_type()}_object_query", $args, $request );

		return $args;
	}

	/**
	 * Get a collection of posts.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$query_args    = $this->prepare_objects_query( $request );
		$query_results = $this->get_objects( $query_args );
		$objects       = array();

		foreach ( $query_results['objects'] as $object ) {
			if ( ! sab_rest_check_permissions( $this->get_data_type(), 'read', $object->get_id() ) ) {
				continue;
			}

			$data      = $this->prepare_object_for_response( $object, $request );
			$objects[] = $this->prepare_response_for_collection( $data );
		}

		$page      = (int) $query_args['page'];
		$max_pages = $query_results['pages'];

		$response = rest_ensure_response( $objects );
		$response->header( 'X-WP-Total', $query_results['total'] );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		$base          = $this->rest_base;
		$attrib_prefix = '(?P<';

		if ( strpos( $base, $attrib_prefix ) !== false ) {
			$attrib_names = array();
			preg_match( '/\(\?P<[^>]+>.*\)/', $base, $attrib_names, PREG_OFFSET_CAPTURE );

			foreach ( $attrib_names as $attrib_name_match ) {
				$beginning_offset = strlen( $attrib_prefix );
				$attrib_name_end  = strpos( $attrib_name_match[0], '>', $attrib_name_match[1] );
				$attrib_name      = substr( $attrib_name_match[0], $beginning_offset, $attrib_name_end - $beginning_offset );

				if ( isset( $request[ $attrib_name ] ) ) {
					$base  = str_replace( "(?P<$attrib_name>[\d]+)", $request[ $attrib_name ], $base );
				}
			}
		}

		$base = add_query_arg( $request->get_query_params(), rest_url( sprintf( '/%s/%s', $this->namespace, $base ) ) );

		if ( $page > 1 ) {
			$prev_page = $page - 1;

			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}

			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}
		if ( $max_pages > $page ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );
			$response->link_header( 'next', $next_link );
		}

		return $response;
	}

	/**
	 * Delete a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$force  = (bool) $request['force'];
		$object = $this->get_object( (int) $request['id'] );
		$result = false;

		if ( ! $object || 0 === $object->get_id() ) {
			return new WP_Error( "storeabill_rest_{$this->get_data_type()}_invalid_id", _x( 'Invalid ID.', 'storeabill-core', 'woocommerce-germanized-pro' ), array( 'status' => 404 ) );
		}

		if ( ! sab_rest_check_permissions( $this->get_data_type(), 'delete', $object->get_id() ) ) {
			/* translators: %s: post type */
			return new WP_Error( "storeabill_rest_user_cannot_delete_{$this->get_data_type()}", sprintf( _x( 'Sorry, you are not allowed to delete %s.', 'storeabill-core', 'woocommerce-germanized-pro' ), $this->get_data_type() ), array( 'status' => rest_authorization_required_code() ) );
		}

		$supports_archive = is_a( $object, '\Vendidero\StoreaBill\Document\Document' ) ? true : false;

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_object_for_response( $object, $request );

		// If we're forcing, then delete permanently.
		if ( $force ) {
			$object->delete( true );
			$result = 0 === $object->get_id();
		} else {
			// If we don't support archiving for this type, error out.
			if ( ! $supports_archive ) {
				/* translators: %s: object type */
				return new WP_Error( 'storeabill_rest_archive_not_supported', sprintf( _x( 'The %s does not support archiving.', 'storeabill-core', 'woocommerce-germanized-pro' ), $this->get_data_type() ), array( 'status' => 501 ) );
			}

			// Otherwise, only trash if we haven't already.
			if ( is_callable( array( $object, 'get_status' ) ) ) {
				if ( 'archived' === $object->get_status() ) {
					/* translators: %s: post type */
					return new WP_Error( 'storeabill_rest_already_archived', sprintf( _x( 'The %s has already been archived.', 'storeabill-core', 'woocommerce-germanized-pro' ), $this->get_data_type() ), array( 'status' => 410 ) );
				}

				$object->delete();
				$result = 'archived' === $object->get_status();
			}
		}

		if ( ! $result ) {
			/* translators: %s: object type */
			return new WP_Error( 'storeabill_rest_cannot_delete', sprintf( _x( 'The %s cannot be deleted.', 'storeabill-core', 'woocommerce-germanized-pro' ), $this->get_data_type() ), array( 'status' => 500 ) );
		}

		/**
		 * Fires after a single object is deleted or trashed via the REST API.
		 *
		 * @param WC_Data          $object   The deleted or trashed object.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( "storeabill_rest_delete_{$this->get_data_type()}_object", $object, $response, $request );

		return $response;
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param WC_Data         $object  Object data.
	 * @param WP_REST_Request $request Request object.
	 * @return array                   Links for the given post.
	 */
	protected function prepare_links( $object, $request ) {
		$links = array(
			'self' => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $object->get_id() ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);

		return $links;
	}

	protected function get_additional_collection_params() {
		return array();
	}

	/**
	 * Get the query params for collections of attachments.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                       = array();
		$params['context']            = $this->get_context_param();
		$params['context']['default'] = 'view';

		$params['page'] = array(
			'description'        => _x( 'Current page of the collection.', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'type'               => 'integer',
			'default'            => 1,
			'sanitize_callback'  => 'absint',
			'validate_callback'  => 'rest_validate_request_arg',
			'minimum'            => 1,
		);
		$params['per_page'] = array(
			'description'        => _x( 'Maximum number of items to be returned in result set.', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'type'               => 'integer',
			'default'            => 10,
			'minimum'            => 1,
			'maximum'            => 100,
			'sanitize_callback'  => 'absint',
			'validate_callback'  => 'rest_validate_request_arg',
		);
		$params['search'] = array(
			'description'        => _x( 'Limit results to those matching a string.', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'type'               => 'string',
			'sanitize_callback'  => 'sanitize_text_field',
			'validate_callback'  => 'rest_validate_request_arg',
		);
		$params['after'] = array(
			'description'        => _x( 'Limit response to resources published after a given ISO8601 compliant date.', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'type'               => 'string',
			'format'             => 'date-time',
			'validate_callback'  => 'rest_validate_request_arg',
		);
		$params['before'] = array(
			'description'        => _x( 'Limit response to resources published before a given ISO8601 compliant date.', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'type'               => 'string',
			'format'             => 'date-time',
			'validate_callback'  => 'rest_validate_request_arg',
		);
		$params['exclude'] = array(
			'description'       => _x( 'Ensure result set excludes specific IDs.', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'type'              => 'array',
			'items'             => array(
				'type'          => 'integer',
			),
			'default'           => array(),
			'sanitize_callback' => 'wp_parse_id_list',
		);
		$params['include'] = array(
			'description'       => _x( 'Limit result set to specific ids.', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'type'              => 'array',
			'items'             => array(
				'type'          => 'integer',
			),
			'default'           => array(),
			'sanitize_callback' => 'wp_parse_id_list',
		);
		$params['offset'] = array(
			'description'        => _x( 'Offset the result set by a specific number of items.', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'type'               => 'integer',
			'sanitize_callback'  => 'absint',
			'validate_callback'  => 'rest_validate_request_arg',
		);
		$params['order'] = array(
			'description'        => _x( 'Order sort attribute ascending or descending.', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'type'               => 'string',
			'default'            => 'desc',
			'enum'               => array( 'asc', 'desc' ),
			'validate_callback'  => 'rest_validate_request_arg',
		);
		$params['orderby'] = array(
			'description'        => _x( 'Sort collection by object attribute.', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'type'               => 'string',
			'default'            => 'date',
			'enum'               => array(
				'date',
				'id',
			),
			'validate_callback'  => 'rest_validate_request_arg',
		);

		/**
		 * Filter collection parameters for the object controller.
		 *
		 * The dynamic part of the filter `$this->get_data_type()` refers to the object
		 * type slug for the controller e.g. invoice.
		 *
		 * This filter registers the collection parameter, but does not map the
		 * collection parameter to an internal WP_Query parameter.
		 *
		 * @param array        $query_params JSON Schema-formatted collection parameters.
		 */
		return apply_filters( "storeabill_rest_{$this->get_data_type()}_collection_params", array_replace_recursive( $params, $this->get_additional_collection_params() ) );
	}
}
