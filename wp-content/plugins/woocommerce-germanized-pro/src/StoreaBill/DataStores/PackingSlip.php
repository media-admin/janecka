<?php

namespace Vendidero\Germanized\Pro\StoreaBill\DataStores;

use Vendidero\StoreaBill\DataStores\Document;

defined( 'ABSPATH' ) || exit;

/**
 * Packing Slip data store.
 *
 * @version 1.0.0
 */
class PackingSlip extends Document {

	/**
	 * Data stored in meta keys, but not considered "meta" for an invoice.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_address',
		'_created_via',
		'_version',
		'_reference_number',
		'_external_sync_handlers',
		'_order_id',
		'_order_number'
	);
}