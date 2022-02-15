<?php

namespace Vendidero\Germanized\Pro\StoreaBill\PostDocument;

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
	protected $rest_base = 'posts';

	protected function get_data_type() {
		return 'post';
	}

	protected function get_type() {
		return 'simple';
	}

	protected function get_objects( $query_args ) {
		$query  = new Query( $query_args );
		$result = $query->get_posts();
		$total  = $query->get_total();

		if ( $total < 1 ) {
			// Out-of-bounds, run the query again without LIMIT for total count.
			unset( $query_args['page'] );

			$count_query = new Query( $query_args );
			$count_query->get_posts();

			$total = $count_query->get_total();
		}

		return array(
			'objects' => $result,
			'total'   => (int) $total,
			'pages'   => $query->get_max_num_pages(),
		);
	}
}