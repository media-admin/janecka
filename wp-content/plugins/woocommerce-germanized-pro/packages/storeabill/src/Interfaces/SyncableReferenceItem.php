<?php

namespace Vendidero\StoreaBill\Interfaces;

/**
 * OrderItem Interface
 *
 * @package  Germanized/StoreaBill/Interfaces
 * @version  1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * OrderItem class.
 */
interface SyncableReferenceItem extends SyncableReference {

	/**
	 * Return the unique identifier for the order
	 *
	 * @return mixed
	 */
	public function get_id();

	/**
	 * Return the quantity.
	 *
	 * @return mixed
	 */
	public function get_quantity();

	/**
	 * Return the item name.
	 *
	 * @return mixed
	 */
	public function get_name();

	/**
	 * Return the order item type.
	 *
	 * @return mixed
	 */
	public function get_type();

	/**
	 * Return the document item type.
	 *
	 * @return mixed
	 */
	public function get_document_item_type();
}
