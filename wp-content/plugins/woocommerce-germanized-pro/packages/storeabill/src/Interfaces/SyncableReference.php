<?php

namespace Vendidero\StoreaBill\Interfaces;

/**
 * Order Interface
 *
 * @package  Germanized/StoreaBill/Interfaces
 * @version  1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order class.
 */
interface SyncableReference extends Reference {

	/**
	 * Syncs an object with the current reference.
	 *
	 * @param \WC_Data $object
	 */
	public function sync( &$object, $args = array() );
}
