<?php

namespace Vendidero\Germanized\Pro\StoreaBill\DataStores;

use Vendidero\StoreaBill\DataStores\DocumentItem;

defined( 'ABSPATH' ) || exit;

/**
 * Shipping Item data store.
 *
 * @version 1.0.0
 */
class ProductItem extends DocumentItem {

	/**
	 * Data stored in meta keys, but not considered "meta" for an item.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_sku',
		'_price',
		'_total'
	);
}