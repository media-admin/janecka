<?php

namespace Vendidero\Germanized\Pro\StoreaBill\PackingSlip;

use Vendidero\StoreaBill\REST\DocumentController;

defined( 'ABSPATH' ) || exit;

/**
 * Invoice Controller class.
 */
class Controller extends DocumentController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'packing_slips';

	protected function get_data_type() {
		return 'packing_slip';
	}

	protected function get_type() {
		return 'simple';
	}

	/**
	 * Get object.
	 *
	 * @param  int $id Object ID.
	 * @return \WC_Data
	 */
	protected function get_object( $id ) {
		return sab_get_document( $id, 'packing_slip' );
	}

	/**
	 * Prepare objects query.
	 *
	 * @since  3.0.0
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args = parent::prepare_objects_query( $request );

		if ( isset( $request['order_id'] ) && ! empty( $request['order_id'] ) ) {
			$args['order_id'] = $request['order_id'];
		}

		return $args;
	}

	protected function get_additional_collection_params() {
		$params = parent::get_additional_collection_params();

		$params['order_id'] = array(
			'description'       => _x( 'Limit result set to packing slips belonging to a certain order.', 'woocommerce-germanized-pro' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}

	protected function get_objects( $query_args ) {
		$query  = new Query( $query_args );
		$result = $query->get_packing_slips();
		$total  = $query->get_total();

		if ( $total < 1 ) {
			// Out-of-bounds, run the query again without LIMIT for total count.
			unset( $query_args['page'] );

			$count_query = new Query( $query_args );
			$count_query->get_packing_slips();

			$total = $count_query->get_total();
		}

		return array(
			'objects' => $result,
			'total'   => (int) $total,
			'pages'   => $query->get_max_num_pages(),
		);
	}
}