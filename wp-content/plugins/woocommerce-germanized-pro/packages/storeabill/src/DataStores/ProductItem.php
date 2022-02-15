<?php

namespace Vendidero\StoreaBill\DataStores;

defined( 'ABSPATH' ) || exit;

/**
 * Invoice data store.
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
		'_is_virtual',
		'_is_service',
		'_has_differential_taxation',
		'_sku'
	);

	protected function format_update_value( $document, $prop ) {
		$value = parent::format_update_value( $document, $prop );

		switch( $prop ) {
			case "prices_include_tax":
			case "is_taxable":
			case "is_virtual":
			case "is_service":
			case "has_differential_taxation":
				$value = sab_bool_to_string( $value );
				break;
		}

		/**
		 * Round tax at subtotal prop may only be overridden for additional costs (e.g. shipping, fee).
		 */
		if ( 'round_tax_at_subtotal' === $prop ) {
			$value = '';
		}

		return $value;
	}
}