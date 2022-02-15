<?php

namespace Vendidero\StoreaBill\DataStores;

defined( 'ABSPATH' ) || exit;

/**
 * TaxItem data store.
 *
 * @version 1.0.0
 */
class TaxItem extends DocumentItem {

	/**
	 * Data stored in meta keys, but not considered "meta" for an item.
	 * Make sure that round_tax_at_subtotal is loaded before other totals so that the amount
	 * is formatted correctly.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_round_tax_at_subtotal',
		'_tax_type',
		'_rate',
		'_total_net',
		'_subtotal_net',
		'_total_tax',
		'_subtotal_tax',
	);

	protected function format_update_value( $document, $prop ) {
		$value = parent::format_update_value( $document, $prop );

		switch( $prop ) {
			case "round_tax_at_subtotal":
				$value = sab_bool_to_string( $value );
				break;
		}

		return $value;
	}
}