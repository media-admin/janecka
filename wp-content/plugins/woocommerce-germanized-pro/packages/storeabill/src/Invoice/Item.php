<?php

namespace Vendidero\StoreaBill\Invoice;

use Vendidero\StoreaBill\Interfaces\SyncableReferenceItem;

defined( 'ABSPATH' ) || exit;

abstract class Item extends \Vendidero\StoreaBill\Document\Item {

	/**
	 * @return bool|SyncableReferenceItem
	 */
	public function get_reference() {
		if ( is_null( $this->reference ) ) {
			if ( $document = $this->get_document() ) {
				if ( $reference = $document->get_reference() ) {
					if ( is_a( $reference, '\Vendidero\StoreaBill\Interfaces\Order' ) ) {
						$this->reference = $reference->get_order_item( $this->get_reference_id() );
					}
				}
			}

			if ( is_null( $this->reference ) ) {
				$this->reference = false;
			}
		}

		return $this->reference;
	}
}