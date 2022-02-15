<?php

namespace Vendidero\StoreaBill\REST;

defined( 'ABSPATH' ) || exit;

use Vendidero\StoreaBill\Document\Template;
use Vendidero\StoreaBill\Package;
use WP_Error;
use WP_REST_Server;
use WP_REST_Request;

/**
 * REST API Products controller class.
 */
class PreviewShortcodesController extends Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'preview_shortcodes';

	protected function get_data_type() {
		return '';
	}

	/**
	 * @param int $id
	 *
	 * @return bool|Template
	 */
	public function get_object( $id ) {
		return false;
	}

	/**
	 * Register the routes for invoices.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_shortcode' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => array(
						'query' => array(
							"type"              => "string",
							"default"           => "",
							"validate_callback" => "rest_validate_request_arg",
						),
						'document_type' => array(
							"type"              => "string",
							"default"           => 'invoice',
							"validate_callback" => "rest_validate_request_arg",
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * @param WP_REST_Request $request
	 */
	public function get_shortcode( $request ) {
		$query         = $request['query'];
		$document_type = $request['document_type'];
		$result        = array(
			'content'   => '',
			'shortcode' => '',
		);

		if ( $preview = sab_get_document_preview( $document_type, true ) ) {
			Package::setup_document( $preview );

			// Setup document
			$document = $GLOBALS['document'];

			// Setup first document_item.
			if ( ! isset( $GLOBALS['document_item'] ) ) {
				$items = $document->get_items( $document->get_line_item_types() );

				if ( ! empty( $items ) ) {
					Package::setup_document_item( array_values( $items )[0] );
				}
			}

			// Setup total e.g. first tax rate for total taxes.
			if ( ! isset( $GLOBALS['document_total'] ) && is_callable( array( $document, 'get_totals' ) ) ) {
				$url = parse_url( $query );

				parse_str( ( isset( $url['query'] ) ? $url['query'] : '' ), $query_result );

				$total_type = isset( $query_result['total_type'] ) ? $query_result['total_type'] : 'total';
				$totals     = $document->get_totals( $total_type );

				if ( ! empty( $totals ) ) {
					Package::setup_document_total( $totals[0] );
				}
			}

			$shortcode_str = sab_query_to_shortcode( $query );

			if ( ! empty( $shortcode_str ) ) {
				$result['shortcode'] = $shortcode_str;

				$query               = add_query_arg( array( 'is_editor_preview' => 'yes' ), $query );
				$execute_shortcode   = sab_query_to_shortcode( $query );
				$result['content']   = apply_filters( "storeabill_{$document_type}_editor_preview_shortcode_result", do_shortcode( $execute_shortcode ), $shortcode_str, $query );
			}
		}

		return $result;
	}

	/**
	 * Check if a given request has access to read an item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_storeabill' ) ) {
			return new WP_Error( 'storeabill_rest_cannot_view', _x( 'Sorry, you cannot view this resource.', 'storeabill-core', 'woocommerce-germanized-pro' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

	/**
	 * @inheritDoc
	 */
	protected function get_objects( $query_args ) {
		return false;
	}
}
