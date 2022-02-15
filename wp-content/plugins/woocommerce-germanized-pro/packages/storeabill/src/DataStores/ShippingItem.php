<?php

namespace Vendidero\StoreaBill\DataStores;

defined( 'ABSPATH' ) || exit;

/**
 * Invoice data store.
 *
 * @version 1.0.0
 */
class ShippingItem extends DocumentItem {

	/**
	 * Data stored in meta keys, but not considered "meta" for an item.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_product_id',
		'_prices_include_tax',
		'_round_tax_at_subtotal',
		'_price',
		'_price_subtotal',
		'_price_tax',
		'_price_subtotal_tax',
		'_line_total',
		'_total_tax',
		'_line_subtotal',
		'_subtotal_tax',
		'_is_taxable',
		'_enable_split_tax'
	);

	protected function format_update_value( $document, $prop ) {
		$value = parent::format_update_value( $document, $prop );

		switch( $prop ) {
			case "prices_include_tax":
			case "is_taxable":
			case "round_tax_at_subtotal":
			case "enable_split_tax":
				$value = sab_bool_to_string( $value );
				break;
		}

		return $value;
	}
}