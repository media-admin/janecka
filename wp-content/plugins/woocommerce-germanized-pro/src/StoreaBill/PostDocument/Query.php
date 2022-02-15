<?php

namespace Vendidero\Germanized\Pro\StoreaBill\PostDocument;

use Vendidero\Germanized\Pro\StoreaBill\Post;
use Vendidero\Germanized\Pro\StoreaBill\PostDocument;
use Vendidero\StoreaBill\Document\Document;

defined( 'ABSPATH' ) || exit;

/**
 * Packing Slip Query Class
 *
 * Extended by classes to provide a query abstraction layer for safe object searching.
 *
 * @version  1.0.0
 * @package  StoreaBill/Abstracts
 */
class Query extends \Vendidero\StoreaBill\Document\Query {

	public function get_document_type() {
		return 'post_document';
	}

	/**
	 * Retrieve packing slips.
	 *
	 * @return PostDocument[]|Document[]
	 */
	public function get_posts() {
		return $this->get_documents();
	}
}
