<?php

namespace Vendidero\StoreaBill\Invoice;

use Vendidero\StoreaBill\Document\Document;

defined( 'ABSPATH' ) || exit;

/**
 * Invoice Query Class
 *
 * Extended by classes to provide a query abstraction layer for safe object searching.
 *
 * @version  1.0.0
 * @package  StoreaBill/Abstracts
 */
class Query extends \Vendidero\StoreaBill\Document\Query {

	/**
	 * Get the default allowed query vars.
	 *
	 * @return array
	 */
	protected function get_default_query_vars() {
		$args = parent::get_default_query_vars();

		$args = array_replace_recursive( $args, array(
			'payment_status' => array(),
		) );

		return $args;
	}

	public function get_document_type() {
		return 'invoice';
	}

	/**
	 * Retrieve invoices.
	 *
	 * @return Invoice[]|Document[]
	 */
	public function get_invoices() {
		return $this->get_documents();
	}
}
