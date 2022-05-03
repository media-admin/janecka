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
class InvoiceController extends DocumentController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'invoices';

	protected function get_data_type() {
		return 'invoice';
	}

	protected function get_type() {
		return 'simple';
	}

	/**
	 * Register the routes for invoices.
	 */
	public function register_routes() {
		parent::register_routes();

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/cancel',
			array(
				'args'   => array(
					'id' => array(
						'description' => _x( 'Unique identifier for the resource.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'cancel_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => array(
						'id'                   => array(
							'description' => _x( 'Unique identifier for the resource.', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
			)
		);
	}

	/**
	 * @param int $id
	 *
	 * @return bool|Invoice|WC_Data|WP_Error
	 */
	public function get_object( $id ) {
		return sab_get_invoice( $id, $this->get_type() );
	}

	/**
	 * Cancel an invoice.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function cancel_item( $request ) {
		$object = $this->get_object( (int) $request['id'] );

		if ( ! $object || 0 === $object->get_id() ) {
			return new WP_Error( "storeabill_rest_{$this->get_data_type()}_invalid_id", _x( 'Invalid ID.', 'storeabill-core', 'woocommerce-germanized-pro' ), array( 'status' => 400 ) );
		}

		$cancellation = $object->cancel();

		if ( is_wp_error( $cancellation ) ) {
			return $cancellation;
		}

		$request->set_param( 'context', 'edit' );

		if ( $controller = Server::instance()->get_controller( 'cancellations', $this->namespace ) ) {
			$response = $controller->prepare_object_for_response( $cancellation, $request );
		} else {
			$response = $this->prepare_object_for_response( $object, $request );
		}

		return rest_ensure_response( $response );
	}

	protected function get_price_fields() {
		return array(
			'discount_total',
			'discount_tax',
			'discount_net',
			'additional_costs_discount_total',
			'additional_costs_discount_tax',
			'additional_costs_discount_net',
			'shipping_total',
			'shipping_tax',
			'shipping_net',
			'product_total',
			'product_tax',
			'product_net',
			'fee_total',
			'fee_tax',
			'fee_net',
			'voucher_total',
			'voucher_net',
			'voucher_tax',
			'shipping_subtotal',
			'shipping_subtotal_tax',
			'shipping_subtotal_net',
			'product_subtotal',
			'product_subtotal_tax',
			'product_subtotal_net',
			'fee_subtotal',
			'fee_subtotal_tax',
			'fee_subtotal_net',
			'total',
			'total_net',
			'total_tax',
			'subtotal',
			'subtotal_net',
			'subtotal_tax',
			'total_paid'
		);
	}

	protected function get_date_fields() {
		return array(
			'date_created',
			'date_modified',
			'date_due',
			'date_paid',
			'date_sent',
			'date_of_service',
			'date_of_service_end'
		);
	}

	protected function get_item_price_fields() {
		return array(
			'line_subtotal',
			'subtotal_tax',
			'line_total',
			'total_tax',
			'total_net',
			'subtotal_net',
			'price',
			'price_net',
			'price_tax',
			'price_subtotal',
			'price_subtotal_net',
			'price_subtotal_tax',
			'total',
			'total_net',
			'subtotal',
			'subtotal_net',
			'discount_total',
			'discount_net',
			'discount_tax',
		);
	}

	protected function get_formatted_item_data( $object ) {
		$data = parent::get_formatted_item_data( $object );

		if ( isset( $data['tax_totals'] ) ) {
			$data['tax_totals'] = $this->get_tax_total_data( $data['tax_totals'], $object );
		}

		if ( isset( $data['totals'] ) ) {
			$data['totals'] = $this->get_totals_data( $data['totals'], $object );
		}

		return $data;
	}

	protected function get_totals_data( $t_totals, $object ) {
		$is_preview = $this->is_preview_request();

		foreach( $t_totals as $key => $totals ) {
			$totals           = $totals->get_data();
			$t_totals[ $key ] = $totals;

			foreach( $totals as $inner_key => $total ) {
				if ( is_numeric( $total ) ) {
					$total = sab_format_decimal( $total, $this->request['dp'] );

					if ( $is_preview ) {
						$t_totals[ $key ][ $inner_key . '_formatted' ] = sab_format_price( $total );
					}
				}

				$t_totals[ $key ][ $inner_key ] = $total;
			}
		}

		return $t_totals;
	}

	protected function get_tax_total_data( $tax_totals, $object ) {
		$is_preview = $this->is_preview_request();

		foreach( $tax_totals as $key => $totals ) {
			$totals             = $totals->get_data();
			$tax_totals[ $key ] = $totals;

			foreach( $totals as $inner_key => $total ) {
				if ( is_numeric( $total ) ) {
					$tax_totals[ $key ][ $inner_key ] = sab_format_decimal( $tax_totals[ $key ][ $inner_key ], $this->request['dp'] );

					if ( $is_preview ) {
						$tax_totals[ $key ][ $inner_key . '_formatted' ] = sab_format_price( $tax_totals[ $key ][ $inner_key ] );
					}
				} elseif( is_a( $total, '\Vendidero\StoreaBill\TaxRate' ) ) {
					$tax_totals[ $key ][ $inner_key ] = $this->get_tax_rate_data( $total, $object );
				} elseif( is_array( $total ) ) {
					foreach( $total as $t_key => $t ) {
						$tax_totals[ $key ][ $inner_key ][ $t_key ] = sab_format_decimal( $tax_totals[ $key ][ $inner_key ][ $t_key ], $this->request['dp'] );

						if ( $is_preview ) {
							$tax_totals[ $key ][ $inner_key ][ $t_key . '_formatted' ] = sab_format_price( $tax_totals[ $key ][ $inner_key ][ $t_key ] );
						}
					}
				}
			}
		}

		return $tax_totals;
	}

	/**
	 * @param Invoice $invoice
	 * @param  WP_REST_Request $request Request object.
	 */
	protected function sync( &$invoice, $request ) {
		if ( isset( $request['order_id'] ) && isset( $request['order_type'] ) ) {
			$ref_id   = absint( $request['order_id'] );
			$ref_type = sab_clean( $request['order_type'] );

			$invoice->set_order_id( $ref_id );
			$invoice->set_order_type( $ref_type );
		}

		if ( $order = $invoice->get_order() ) {
			$order->sync( $invoice );
		}
	}

	protected function is_sync( $request ) {
		if ( isset( $request['sync'] ) && true === $request['sync'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Prepare a single invoice for create or update.
	 *
	 * @throws WC_REST_Exception When fails to set any item.
	 * @param  WP_REST_Request $request Request object.
	 * @param  bool            $creating If is creating a new object.
	 * @return WP_Error|Invoice
	 */
	protected function prepare_object_for_database( $request, $creating = false ) {
		$id        = isset( $request['id'] ) ? absint( $request['id'] ) : 0;
		$invoice   = $this->get_object( $id );
		$schema    = $this->get_item_schema();
		$data_keys = array_keys( array_filter( $schema['properties'], array( $this, 'filter_writable_props' ) ) );

		if ( $this->is_sync( $request ) ) {
			$this->sync( $invoice, $request );
		} else {
			// Handle all writable props.
			foreach ( $data_keys as $key ) {
				$value = $request[ $key ];

				if ( ! is_null( $value ) ) {

					if ( strpos( $key, '_items' ) !== false ) {
						$item_type = str_replace( '_items', '', $key );
						$this->prepare_items_for_database( $invoice, $value, $item_type );
						continue;
					}

					switch ( $key ) {
						case 'status':
							// Change should be done later so transitions have new data.
							break;
						case "address":
							$this->update_address( $invoice, $value );
							break;
						case 'meta_data':
							if ( is_array( $value ) ) {
								foreach ( $value as $meta ) {
									$invoice->update_meta_data( $meta['key'], $meta['value'], isset( $meta['id'] ) ? $meta['id'] : '' );
								}
							}
							break;
						default:
							if ( is_callable( array( $invoice, "set_{$key}" ) ) ) {
								$invoice->{"set_{$key}"}( $value );
							}
							break;
					}
				}
			}
		}

		/**
		 * Filters an object before it is inserted via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->object_type`,
		 * refers to the object type slug.
		 *
		 * @param WC_Data         $object   The object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating If is creating a new object.
		 */
		return apply_filters( "storeabill_rest_pre_insert_{$this->get_data_type()}_object", $invoice, $request, $creating );
	}

	/**
	 * Save an object data.
	 *
	 * @since  3.0.0
	 * @throws WC_REST_Exception But all errors are validated before returning any data.
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

			if ( ! is_null( $request['customer_id'] ) && 0 !== $request['customer_id'] ) {
				// Make sure customer exists.
				if ( false === get_user_by( 'id', $request['customer_id'] ) ) {
					throw new WC_REST_Exception( 'storeabill_rest_invalid_customer_id', _x( 'Customer ID is invalid.', 'storeabill-core', 'woocommerce-germanized-pro' ), 400 );
				}

				// Make sure customer is part of blog.
				if ( is_multisite() && ! is_user_member_of_blog( $request['customer_id'] ) ) {
					add_user_to_blog( get_current_blog_id(), $request['customer_id'], 'customer' );
				}
			}

			if ( $creating ) {
				$object->set_created_via( 'rest-api' );

				if ( ! $this->is_sync( $request ) ) {
					if ( ! isset( $request['prices_include_tax'] ) ) {
						$object->set_prices_include_tax( 'yes' === get_option( 'woocommerce_prices_include_tax' ) );
					}

					$object->calculate_totals();
				}
			} else {
				$item_types    = $object->get_item_types();
				$items_changed = false;

				foreach( $item_types as $item_type ) {

					if ( isset( $request[ $item_type . '_items' ] ) ) {
						$items_changed = true;
						break;
					}
				}

				// Recalculate invoice totals.
				if ( ! $this->is_sync( $request ) && ( isset( $request['address'] ) || $items_changed || isset( $request['prices_include_tax'] ) ) ) {
					$object->calculate_totals( true );
				}
			}

			// Set status.
			if ( ! empty( $request['status'] ) ) {
				$object->set_status( $request['status'] );
			}

			if ( $order = $object->get_order() ) {
				$order->add_document( $object );
				$order->validate();

				$object = $order->get_document( $object->get_id() );

				if ( $object && ( 'closed' === $request['status'] && ! $object->is_finalized() ) ) {
					$object->finalize();
				}

				return $object;
			} else {
				if ( 'closed' === $request['status'] && ! $object->is_finalized() ) {
					$object->finalize();
				} else {
					$object->save();
				}

				return $this->get_object( $object->get_id() );
			}
		} catch ( WC_Data_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		} catch ( \WC_REST_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Get objects.
	 *
	 * @since  3.0.0
	 * @param  array $query_args Query args.
	 * @return array
	 */
	protected function get_objects( $query_args ) {
		$query  = new Query( $query_args );
		$result = $query->get_invoices();
		$total  = $query->get_total();

		if ( $total < 1 ) {
			// Out-of-bounds, run the query again without LIMIT for total count.
			unset( $query_args['page'] );

			$count_query = new Query( $query_args );
			$count_query->get_invoices();

			$total = $count_query->get_total();
		}

		return array(
			'objects' => $result,
			'total'   => (int) $total,
			'pages'   => $query->get_max_num_pages(),
		);
	}

	protected function get_item_data( $item, $object ) {
		$data = parent::get_item_data( $item, $object );

		// Format tax data
		if ( isset( $data['taxes'] ) ) {
			$data['taxes'] = array_values( array_map( function( $item ) use ( $object ) {
				return $this->get_item_data( $item, $object );
			}, $data['taxes'] ) );
		}

		// Format tax rates
		if ( isset( $data['tax_rates'] ) ) {
			$data['tax_rates'] = array_values( array_map( function( $item ) use ( $object ) {
				return $this->get_tax_rate_data( $item, $object );
			}, $data['tax_rates'] ) );
		}

		return $data;
	}

	/**
	 * Expands a document item to get its data.
	 *
	 * @param TaxRate $rate Rate data.
	 *
	 * @return array
	 */
	protected function get_tax_rate_data( $rate, $object ) {
		$data = $rate->get_data();

		return $data;
	}

	protected function get_additional_collection_params() {
		$params = parent::get_additional_collection_params();

		$params['payment_status'] = array(
			'default'           => 'any',
			'description'       => _x( 'Limit result set to invoices which have a specific payment statuses.', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'string',
				'enum' => array_merge( array( 'any' ), array_keys( sab_get_invoice_payment_statuses() ) ),
			),
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}

	/**
	 * Prepare objects query.
	 *
	 * @since  3.0.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args = parent::prepare_objects_query( $request );

		if ( isset( $request['payment_status'] ) ) {
			$args['payment_status'] = $request['payment_status'];
		}

		return $args;
	}

	/**
	 * Get the Invoice's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'invoice',
			'type'       => 'object',
			'properties' => array(
				'id'                   => array(
					'description' => _x( 'Unique identifier for the resource.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'integer',
					'label'       => _x( 'ID', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'parent_id'            => array(
					'description' => _x( 'Parent invoice ID.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'integer',
					'label'       => _x( 'Parent ID', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
				),
				'order_id'             => array(
					'description' => _x( 'The order ID linked to the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'integer',
					'label'       => _x( 'Order ID', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'default'     => 0,
					'context'     => array( 'view', 'edit' ),
				),
				'order_number'    => array(
					'description' => _x( 'The formatted order number linked to the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Order number', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'default'     => '',
					'context'     => array( 'view', 'edit' ),
				),
				'order_type'        => array(
					'description' => _x( 'The order resource type, e.g. woocommerce.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Order type', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'default'     => 'woocommerce',
					'context'     => array( 'view', 'edit' ),
				),
				'sync' => array(
					'default'     => false,
					'type'        => 'boolean',
					'description' => _x( 'Whether to automatically sync the invoice with it\'s order if possible.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				),
				'number'               => array(
					'description' => _x( 'The invoice number.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Number', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'formatted_number'      => array(
					'description' => _x( 'The formatted invoice number.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Formatted number', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'created_via'          => array(
					'description' => _x( 'Shows where the invoice was created.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Created via', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'status'               => array(
					'description' => _x( 'Invoice status.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Status', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'default'     => 'draft',
					'enum'        => $this->get_document_statuses(),
					'context'     => array( 'view', 'edit' ),
				),
				'payment_status'  => array(
					'description' => _x( 'Invoice payment status.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Payment status', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'default'     => 'pending',
					'enum'        => array_keys( sab_get_invoice_payment_statuses() ),
					'context'     => array( 'view', 'edit' ),
				),
				'payment_method_name' => array(
					'description' => _x( 'The payment method slug.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Payment method slug', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
				),
				'payment_method_title' => array(
					'description' => _x( 'The payment method title.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Payment method', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
				),
				'payment_transaction_id' => array(
					'description' => _x( 'The payment transaction id.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Payment transaction id', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
				),
				'currency'             => array(
					'description' => _x( 'Currency the invoice was created with, in ISO format.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Currency', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'default'     => sab_get_default_currency(),
					'enum'        => array_keys( sab_get_currencies() ),
					'context'     => array( 'view', 'edit' ),
				),
				'date_created'         => array(
					'description' => _x( "The date the invoice was created, in the site's timezone.", 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'label'       => _x( 'Date', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created_gmt'     => array(
					'description' => _x( 'The date the invoice was created, as GMT.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'label'       => _x( 'Date (GMT)', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified'        => array(
					'description' => _x( "The date the invoice was last modified, in the site's timezone.", 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'label'       => _x( 'Date modified', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified_gmt'    => array(
					'description' => _x( 'The date the invoice was last modified, as GMT.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'label'       => _x( 'Date modified (GMT)', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_paid'        => array(
					'description' => _x( "The date the invoice was marked as paid, in the site's timezone.", 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'label'       => _x( 'Date paid', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_paid_gmt'    => array(
					'description' => _x( 'The date the invoice was marked as paid, as GMT.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'label'       => _x( 'Date paid (GMT)', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_due'        => array(
					'description' => _x( "The date until which the invoice is due for payment, in the site's timezone.", 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'label'       => _x( 'Date due', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
				),
				'date_due_gmt'    => array(
					'description' => _x( 'The date until which the invoice is due for payment, as GMT.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'label'       => _x( 'Date due (GMT)', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
				),
				'date_of_service' => array(
					'description' => _x( "The date of service, in the site's timezone.", 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'label'       => _x( 'Date of service', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
				),
				'date_of_service_gmt' => array(
					'description' => _x( 'The date of service, as GMT.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'label'       => _x( 'Date of service (GMT)', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
				),
				'date_of_service_end' => array(
					'description' => _x( "The end date of service, in the site's timezone.", 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'label'       => _x( 'Date of service end', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
				),
				'date_of_service_end_gmt' => array(
					'description' => _x( 'The end date of service, as GMT.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'label'       => _x( 'Date of service end (GMT)', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
				),
				'product_total'       => array(
					'description' => _x( 'Product total amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Product total', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'product_subtotal'       => array(
					'description' => _x( 'Product subtotal (before discounts) amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Product subtotal', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'shipping_total'       => array(
					'description' => _x( 'Shipping total amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Shipping total', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'shipping_subtotal'       => array(
					'description' => _x( 'Shipping subtotal (before discounts) amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Shipping subtotal', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'fee_total'         => array(
					'description' => _x( 'Fee total amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Fee total', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'fee_subtotal'         => array(
					'description' => _x( 'Fee subtotal (before discounts) amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Fee subtotal', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'discount_total'         => array(
					'description' => _x( 'Discount total amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Discount total', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'discount_percentage' => array(
					'description' => _x( 'Discount percentage for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Discount percentage', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'discount_notice' => array(
					'description' => _x( 'A notice on discounts e.g. coupon codes used.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Discount Notice', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'default'     => '',
					'context'     => array( 'view', 'edit' ),
				),
				'additional_costs_discount_total' => array(
					'description' => _x( 'Discount on additional costs e.g. shipping and fees.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Additional costs discount', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'voucher_total'  => array(
					'description' => _x( 'Voucher total amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Voucher total', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
				),
				'voucher_net'     => array(
					'description' => _x( 'Voucher net amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Voucher net total', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'voucher_tax'  => array(
					'description' => _x( 'Voucher tax amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Voucher tax', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
				),
				'total_tax'             => array(
					'description' => _x( 'Tax total amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Tax total', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'subtotal_tax'             => array(
					'description' => _x( 'Subtotal tax (before discounts) amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Tax subtotal', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'product_tax'             => array(
					'description' => _x( 'Product tax total amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Product tax total', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'product_subtotal_tax'             => array(
					'description' => _x( 'Product subtotal tax (before discounts) amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Product tax subtotal', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'shipping_tax'             => array(
					'description' => _x( 'Shipping tax total amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Shipping tax total', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'shipping_subtotal_tax'             => array(
					'description' => _x( 'Shipping subtotal tax (before discounts) amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Shipping tax subtotal', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'fee_tax'             => array(
					'description' => _x( 'Fee tax total amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Fee tax total', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'fee_subtotal_tax'             => array(
					'description' => _x( 'Fee subtotal tax (before discounts) amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Fee tax subtotal', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'discount_tax'             => array(
					'description' => _x( 'Discount tax total amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Discount tax total', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'additional_costs_discount_tax'  => array(
					'description' => _x( 'Additional costs discount tax total amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Additional costs discount tax total', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'total'                => array(
					'description' => _x( 'Total amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Total', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'subtotal'                => array(
					'description' => _x( 'Subtotal (before discounts) amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Subtotal', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'total_net'                => array(
					'description' => _x( 'Total net amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Net total', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'subtotal_net'                => array(
					'description' => _x( 'Subtotal net amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Subtotal net', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'product_net'                => array(
					'description' => _x( 'Product net amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Product net total', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'product_subtotal_net'                => array(
					'description' => _x( 'Product subtotal net amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Product subtotal net total', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'shipping_net'                => array(
					'description' => _x( 'Shipping net amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Shipping net total', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'shipping_subtotal_net'                => array(
					'description' => _x( 'Shipping subtotal net amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Shipping subtotal net', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'fee_net'                => array(
					'description' => _x( 'Fee net amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Fee net total', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'fee_subtotal_net'       => array(
					'description' => _x( 'Fee subtotal net amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Fee subtotal net', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'total_paid'      => array(
					'description' => _x( 'Total paid amount of the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Total amount paid', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
				),
				'tax_rate_percentages' => array(
					'description' => _x( 'Tax rate percentages.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'label'       => _x( 'List of tax rate percentages.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type' => 'number',
					),
				),
				'prices_include_tax'   => array(
					'description' => _x( 'True in case prices include tax.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'boolean',
					'label'       => _x( 'Prices include tax', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' )
				),
				'customer_id'          => array(
					'description' => _x( 'User ID linked to the invoice. 0 for guests.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'integer',
					'label'       => _x( 'Customer ID', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'default'     => 0,
					'context'     => array( 'view', 'edit' ),
				),
				'vat_id'    => array(
					'description' => _x( 'VAT ID', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'is_reverse_charge'    => array(
					'description' => _x( 'Is a reverse of charge?', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
				),
				'is_taxable'    => array(
					'description' => _x( 'Is taxable?', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
				),
				'round_tax_at_subtotal' => array(
					'description' => _x( 'Round tax at subtotal?', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
				),
				'relative_path'   => array(
					'description' => _x( 'Relative path to PDF file.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Relative path', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'default'     => '',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'path'            => array(
					'description' => _x( 'Absolute path to PDF file.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Absolute path', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'default'     => '',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'formatted_address' => array(
					'description' => _x( 'Formatted address data.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Formatted address', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'address'              => array(
					'description' => _x( 'Address data.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'first_name' => array(
							'description' => _x( 'First name.', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'last_name'  => array(
							'description' => _x( 'Last name.', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'company'    => array(
							'description' => _x( 'Company name.', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_1'  => array(
							'description' => _x( 'Address line 1', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_2'  => array(
							'description' => _x( 'Address line 2', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'city'       => array(
							'description' => _x( 'City name.', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'state'      => array(
							'description' => _x( 'ISO code or name of the state, province or district.', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'type'        => 'string',
							'label'       => _x( 'State', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'context'     => array( 'view', 'edit' ),
						),
						'postcode'   => array(
							'description' => _x( 'Postal code.', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'country'    => array(
							'description' => _x( 'Country code in ISO 3166-1 alpha-2 format.', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'label'       => _x( 'Country code', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'email'      => array(
							'description' => _x( 'Email address.', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'type'        => 'string',
							'format'      => 'email',
							'context'     => array( 'view', 'edit' ),
						),
						'phone'      => array(
							'description' => _x( 'Phone number.', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'vat_id'    => array(
							'description' => _x( 'Address VAT ID.', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'formatted_shipping_address' => array(
					'description' => _x( 'Formatted shipping address data.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => _x( 'Formatted shipping address', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'shipping_address' => array(
					'description' => _x( 'Shipping Address data.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'first_name' => array(
							'description' => _x( 'Shipping first name.', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'last_name'  => array(
							'description' => _x( 'Shipping last name.', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'company'    => array(
							'description' => _x( 'Shipping company name.', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_1'  => array(
							'description' => _x( 'Shipping address line 1', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_2'  => array(
							'description' => _x( 'Shipping address line 2', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'city'       => array(
							'description' => _x( 'Shipping city name.', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'state'      => array(
							'description' => _x( 'Shipping ISO code or name of the state, province or district.', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'label'       => _x( 'Shipping state', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'postcode'   => array(
							'description' => _x( 'Shipping postal code.', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'country'    => array(
							'description' => _x( 'Shipping country code in ISO 3166-1 alpha-2 format.', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'label'       => _x( 'Shipping country code', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'vat_id'    => array(
							'description' => _x( 'Shipping VAT ID.', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'taxable_country' => array(
					'description' => _x( 'Taxable country code in ISO 3166-1 alpha-2 format.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'label'       => _x( 'Taxable country code', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'readonly'    => true,
					'context'     => array( 'view', 'edit' ),
				),
				'taxable_postcode' => array(
					'description' => _x( 'Taxable postcode.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'readonly'    => true,
					'context'     => array( 'view', 'edit' ),
				),
				'meta_data'        => array(
					'description' => _x( 'Meta data.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => $this->get_meta_property_schema(),
					),
				),
				'product_items'    => array(
					'description' => _x( 'Product items data.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array_merge( $this->get_taxable_item_property_schema(), array(
							'product_id'  => array(
								'description' => _x( 'Product ID.', 'storeabill-core', 'woocommerce-germanized-pro' ),
								'type'        => array( 'string', 'null' ),
								'context'     => array( 'view', 'edit' ),
							),
							'sku'  => array(
								'description' => _x( 'SKU.', 'storeabill-core', 'woocommerce-germanized-pro' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'is_virtual'  => array(
								'description' => _x( 'Whether this is a virtual item.', 'storeabill-core', 'woocommerce-germanized-pro' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'is_service'  => array(
								'description' => _x( 'Whether this item is a service or not.', 'storeabill-core', 'woocommerce-germanized-pro' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'has_differential_taxation'  => array(
								'description' => _x( 'Whether this item differential taxed or not.', 'storeabill-core', 'woocommerce-germanized-pro' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
						) ),
					),
				),
				'tax_items'        => array(
					'description' => _x( 'Tax items.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'items'       => array(
						'type'       => 'object',
						'properties' => $this->get_tax_item_property_schema(),
					),
				),
				'shipping_items'   => array(
					'description' => _x( 'Shipping items.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array_merge( $this->get_taxable_item_property_schema(), array(
							'enable_split_tax'  => array(
								'description' => _x( 'Whether to enable split-tax calculation.', 'storeabill-core', 'woocommerce-germanized-pro' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
						) ),
					),
				),
				'fee_items'        => array(
					'description' => _x( 'Fee lines data.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array_merge( $this->get_taxable_item_property_schema(), array(
							'enable_split_tax'  => array(
								'description' => _x( 'Whether to enable split-tax calculation.', 'storeabill-core', 'woocommerce-germanized-pro' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
						) ),
					),
				),
				'voucher_items'   => array(
					'description' => _x( 'Voucher items.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'           => array(
								'description' => _x( 'Item ID.', 'storeabill-core', 'woocommerce-germanized-pro' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'code'         => array(
								'description' => _x( 'Coupon code.', 'storeabill-core', 'woocommerce-germanized-pro' ),
								'type'        => array( 'string', 'null' ),
								'context'     => array( 'view', 'edit' ),
							),
							'quantity'     => array(
								'description' => _x( 'Quantity billed.', 'storeabill-core', 'woocommerce-germanized-pro' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'line_total'        => array(
								'description' => _x( 'Line total (after discounts).', 'storeabill-core', 'woocommerce-germanized-pro' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'meta_data'    => array(
								'description' => _x( 'Meta data.', 'storeabill-core', 'woocommerce-germanized-pro' ),
								'type'        => 'array',
								'context'     => array( 'view', 'edit' ),
								'items'       => array(
									'type'       => 'object',
									'properties' => $this->get_meta_property_schema(),
								),
							),
							'attributes'      => array(
								'description' => _x( 'Item attributes.', 'storeabill-core', 'woocommerce-germanized-pro' ),
								'type'        => 'array',
								'context'     => array( 'view', 'edit' ),
								'items'       => array(
									'type'       => 'object',
									'properties' => $this->get_item_attributes_property_schema(),
								),
							),
							'price'        => array(
								'description' => _x( 'Product price.', 'storeabill-core', 'woocommerce-germanized-pro' ),
								'type'        => 'number',
								'context'     => array( 'view', 'edit' ),
							),
							'total'    => array(
								'description' => _x( 'Product total.', 'storeabill-core', 'woocommerce-germanized-pro' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
				),
				'totals'           => array(
					'description' => _x( 'Total data.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'properties' => array(
						'type' => array(
							'description' => _x( 'Total type.', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'total' => array(
							'description' => _x( 'Total amount.', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'total_formatted' => array(
							'description' => _x( 'Total formatted amount.', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'placeholders' => array(
							'description' => _x( 'Total placeholders.', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'type'        => 'array',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
				'tax_totals' => $this->get_tax_totals_schema(),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	protected function get_tax_totals_schema() {
		return array(
			'description' => _x( 'Tax total data.', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'type'        => 'array',
			'context'     => array( 'view', 'edit' ),
			'items'       => array(
				'type'       => 'object',
				'properties' => array(
					'total_net' => array(
						'description' => _x( 'Total net.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'total_tax' => array(
						'description' => _x( 'Total tax.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'tax_rate' => array(
						'type'       => 'object',
						'properties' => $this->get_tax_rate_property_schema(),
						'readonly'   => true,
					),
					'net_totals' => array(
						'description' => _x( 'Net totals.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'object',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
						'properties' => array(
							'product' => array(
								'description' => _x( 'Product net total.', 'storeabill-core', 'woocommerce-germanized-pro' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'shipping' => array(
								'description' => _x( 'Shipping net total.', 'storeabill-core', 'woocommerce-germanized-pro' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'fee' => array(
								'description' => _x( 'Fee net total.', 'storeabill-core', 'woocommerce-germanized-pro' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
					'tax_totals' => array(
						'description' => _x( 'Tax totals.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'object',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
						'properties' => array(
							'product' => array(
								'description' => _x( 'Product tax total.', 'storeabill-core', 'woocommerce-germanized-pro' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'shipping' => array(
								'description' => _x( 'Shipping tax total.', 'storeabill-core', 'woocommerce-germanized-pro' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'fee' => array(
								'description' => _x( 'Fee tax total.', 'storeabill-core', 'woocommerce-germanized-pro' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
				),
			),
		);
	}

	protected function get_taxable_item_property_schema() {
		return array(
			'id'           => array(
				'description' => _x( 'Item ID.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'name'         => array(
				'description' => _x( 'Product name.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => array( 'string', 'null' ),
				'context'     => array( 'view', 'edit' ),
			),
			'quantity'     => array(
				'description' => _x( 'Quantity billed.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
			),
			'is_taxable'     => array(
				'description' => _x( 'Whether the item is taxable or not.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
			),
			'line_subtotal'     => array(
				'description' => _x( 'Line subtotal (before discounts).', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'discount_total'     => array(
				'description' => _x( 'Discount total.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'discount_net'     => array(
				'description' => _x( 'Discount net total.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'discount_tax'     => array(
				'description' => _x( 'Discount tax total.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'discount_percentage' => array(
				'description' => _x( 'Discount percentage.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'subtotal_tax' => array(
				'description' => _x( 'Line subtotal tax (before discounts).', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'line_total'        => array(
				'description' => _x( 'Line total (after discounts).', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'total_tax'    => array(
				'description' => _x( 'Line total tax (after discounts).', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'taxes'        => array(
				'description' => _x( 'Product taxes.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'items'       => array(
					'type'        => 'object',
					'properties'  => $this->get_tax_item_property_schema(),
				),
			),
			'meta_data'    => array(
				'description' => _x( 'Meta data.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'items'       => array(
					'type'       => 'object',
					'properties' => $this->get_meta_property_schema(),
				),
			),
			'attributes'      => array(
				'description' => _x( 'Item attributes.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'items'       => array(
					'type'       => 'object',
					'properties' => $this->get_item_attributes_property_schema(),
				),
			),
			'price'        => array(
				'description' => _x( 'Product price.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit' ),
			),
			'price_net'        => array(
				'description' => _x( 'Product net price.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'price_tax'        => array(
				'description' => _x( 'Product price tax.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'price_subtotal'        => array(
				'description' => _x( 'Product price (before discounts).', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit' ),
			),
			'price_subtotal_tax'        => array(
				'description' => _x( 'Product price tax (before discounts).', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'price_subtotal_net'        => array(
				'description' => _x( 'Product net price (before discounts).', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'total'    => array(
				'description' => _x( 'Product total.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'total_net'    => array(
				'description' => _x( 'Product net total.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'subtotal'    => array(
				'description' => _x( 'Product subtotal.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'subtotal_net'    => array(
				'description' => _x( 'Product net subtotal.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
		);
	}

	protected function get_tax_item_property_schema() {
		return array(
			'id'       => array(
				'description' => _x( 'Tax item ID.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
			),
			'tax_type'    => array(
				'description' => _x( 'Tax type e.g. product.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'round_tax_at_subtotal' => array(
				'description' => _x( 'True in case tax is rounded at subtotal.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'is_oss'     => array(
				'description' => _x( 'Is OSS tax?', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'total_tax'    => array(
				'description' => _x( 'Tax total.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'total_net'    => array(
				'description' => _x( 'Tax net total.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'subtotal_tax' => array(
				'description' => _x( 'Tax subtotal.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'subtotal_net' => array(
				'description' => _x( 'Tax net subtotal.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'tax_rate'       => array(
				'type'       => 'object',
				'context'    => array( 'view', 'edit' ),
				'properties' => $this->get_tax_rate_property_schema(),
			),
			'meta_data'    => array(
				'description' => _x( 'Meta data.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'items'       => array(
					'type'       => 'object',
					'properties' => $this->get_meta_property_schema(),
				),
			),
			'attributes'      => array(
				'description' => _x( 'Attributes.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'items'       => array(
					'type'       => 'object',
					'properties' => $this->get_item_attributes_property_schema(),
				),
			),
		);
	}

	protected function get_item_attributes_property_schema() {
		return array(
			'value'    => array(
				'description' => _x( 'Attribute value.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'key'   => array(
				'description' => _x( 'Attribute key.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'label'   => array(
				'description' => _x( 'Attribute label.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'formatted_label'   => array(
				'description' => _x( 'Attribute formatted label.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true
			),
			'formatted_value'   => array(
				'description' => _x( 'Attribute formatted value.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true
			),
		);
	}

	protected function get_meta_property_schema() {
		return array(
			'id'    => array(
				'description' => _x( 'Meta ID.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'key'   => array(
				'description' => _x( 'Meta key.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'value' => array(
				'description' => _x( 'Meta value.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => array( 'string', 'null' ),
				'context'     => array( 'view', 'edit' ),
			)
		);
	}

	protected function get_tax_rate_property_schema() {
		return array(
			'percent'       => array(
				'description' => _x( 'Tax percentage.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'formatted_percentage' => array(
				'description' => _x( 'Formatted tax percentage.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'country'       => array(
				'description' => _x( 'Tax country.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'priority'       => array(
				'description' => _x( 'Tax priority.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
			),
			'is_compound'     => array(
				'description' => _x( 'Is compound?', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
			),
			'is_oss'     => array(
				'description' => _x( 'Is OSS?', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
			),
			'label'         => array(
				'description' => _x( 'Tax rate label.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			)
		);
	}
}