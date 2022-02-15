<?php

namespace Vendidero\Germanized\Pro\StoreaBill\PackingSlip;

use Vendidero\Germanized\Pro\StoreaBill\PackingSlip;
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
		return 'packing_slip';
	}

	/**
	 * Retrieve packing slips.
	 *
	 * @return PackingSlip[]|Document[]
	 */
	public function get_packing_slips() {
		return $this->get_documents();
	}
}
