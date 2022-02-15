<?php

namespace Vendidero\StoreaBill\REST;

defined( 'ABSPATH' ) || exit;

use Vendidero\StoreaBill\Fonts\Embed;
use WP_Error;
use WP_REST_Server;
use WP_REST_Request;

/**
 * REST API Products controller class.
 */
class PreviewFontsController extends Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'preview_fonts';

	protected function get_data_type() {
		return '';
	}

	/**
	 * @param int $id
	 *
	 * @return bool
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
			'/' . $this->rest_base . '/css',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_css' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => array(
						'fonts' => array(
							"type"              => "object",
							"default"           => array(),
							"sanitize_callback" => array( $this, 'sanitize_font_items' ),
							"validate_callback" => "rest_validate_request_arg",
							'items'             => array(
								'type'          => 'array'
							),
						),
						'display_types' => array(
							"type"              => "object",
							"default"           => array(),
							"sanitize_callback" => array( $this, 'sanitize_font_display_types' ),
							"validate_callback" => "rest_validate_request_arg",
							'items'             => array(
								'type'          => 'array'
							),
						),
						'type' => array(
							"type"              => "string",
							"default"           => 'html',
							"validate_callback" => "rest_validate_request_arg",
							'enum'             => array(
								'html',
								'pdf'
							),
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	public function sanitize_font_items( $items ) {
		foreach( $items as $font_display_type => $font ) {
			$font = wp_parse_args( $font, array(
				'name'     => '',
				'variants' => array(),
			) );

			$items[ $font_display_type ] = array(
				'name'     => sab_clean( $font['name'] ),
				'variants' => sab_clean( $font['variants'] ),
			);
		}

		return $items;
	}

	public function sanitize_font_display_types( $types ) {
		foreach( $types as $display_type_name => $type ) {
			$type = wp_parse_args( $type, array(
				'title'     => '',
				'name'      => '',
				'selectors' => array(),
			) );

			$types[ $display_type_name ] = array(
				'title'     => sab_clean( $type['title'] ),
				'name'      => sab_clean( $type['name'] ),
				'selectors' => sab_clean( $type['selectors'] ),
			);
		}

		return $types;
	}

	/**
	 * @param WP_REST_Request $request
	 */
	public function get_css( $request ) {
		$fonts         = $request['fonts'];
		$display_types = $request['display_types'];
		$type          = $request['type'];
		$embed         = new Embed( $fonts, $display_types, $type );

		return array(
			'facets' => $embed->get_font_facets_css(),
			'inline' => $embed->get_inline_css()
		);
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

	protected function get_objects( $query_args ) {
		return array();
	}
}
