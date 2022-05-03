<?php

namespace Vendidero\StoreaBill\DataStores;

defined( 'ABSPATH' ) || exit;

/**
 * VoucherItem data store.
 *
 * @version 1.0.0
 */
class VoucherItem extends DocumentItem {

	/**
	 * Data stored in meta keys, but not considered "meta" for an item.
	 * Make sure that round_tax_at_subtotal is loaded before other totals so that the amount
	 * is formatted correctly.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_price',
		'_line_total',
		'_code'
	);
}