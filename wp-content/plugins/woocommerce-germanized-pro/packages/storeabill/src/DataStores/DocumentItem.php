<?php

namespace Vendidero\StoreaBill\DataStores;
use WC_Data;
use Exception;
use WC_Data_Store_WP;
use WC_Object_Data_Store_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Document Item Data Store
 *
 * @version 1.0.0
 */
class DocumentItem extends WC_Data_Store_WP implements WC_Object_Data_Store_Interface {

	/**
	 * Data stored in meta keys, but not considered "meta" for an order.
	 *
	 * @since 3.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array();

	protected $core_meta_keys = array(
		'_attributes',
	);

	protected $core_props = array(
		'document_id',
		'reference_id',
		'name',
		'parent_id',
		'quantity'
	);

	/**
	 * Meta type. This should match up with
	 * the types available at https://developer.wordpress.org/reference/functions/add_metadata/.
	 * WP defines 'post', 'user', 'comment', and 'term'.
	 *
	 * @var string
	 */
	protected $meta_type = 'storeabill_document_item';

	/**
	 * Create a new document item in the database.
	 *
	 * @param \Vendidero\StoreaBill\Document\Item $item Document item object.
	 *
	 *@since 3.0.0
	 */
	public function create( &$item ) {
		global $wpdb;

		$wpdb->insert(
			$wpdb->storeabill_document_items, array(
				'document_id'                => $item->get_document_id( 'edit' ),
				'document_item_reference_id' => $item->get_reference_id( 'edit' ),
				'document_item_parent_id'    => $item->get_parent_id( 'edit' ),
				'document_item_name'         => $item->get_name( 'edit' ),
				'document_item_type'         => $item->get_type(),
				'document_item_quantity'     => $item->get_quantity( 'edit' ),
			)
		);

		$item->set_id( $wpdb->insert_id );
		$this->save_item_data( $item );
		$item->save_meta_data();
		$item->apply_changes();
		$this->clear_cache( $item );

		/**
		 * Action that indicates that a new document item has been created in the DB.
		 *
		 * The dynamic portion of this hook, `$item->get_type()` refers to the
		 * item type e.g. invoice.
		 *
		 * @param integer                                       $document_item_id The document item id.
		 * @param \Vendidero\StoreaBill\Item $item The document item object.
		 * @param integer                                       $document_id The document id.
		 *
		 * @since 1.0.0
		 * @package Vendidero/StoreaBill
		 */
		do_action( "storeabill_new_{$item->get_type()}_item", $item->get_id(), $item, $item->get_document_id() );
	}

	/**
	 * Update a document item in the database.
	 *
	 * @param \Vendidero\StoreaBill\Document\Item $item Document item object.
	 *
	 *@since 3.0.0
	 */
	public function update( &$item ) {
		global $wpdb;

		$changes = $item->get_changes();

		if ( array_intersect( $this->core_props, array_keys( $changes ) ) ) {
			$wpdb->update(
				$wpdb->storeabill_document_items, array(
				'document_id'                => $item->get_document_id( 'edit' ),
				'document_item_reference_id' => $item->get_reference_id( 'edit' ),
				'document_item_parent_id'    => $item->get_parent_id('edit' ),
				'document_item_name'         => $item->get_name( 'edit' ),
				'document_item_quantity'     => $item->get_quantity( 'edit' ),
			), array( 'document_item_id' => $item->get_id() )
			);
		}

		$this->save_item_data( $item );
		$item->save_meta_data();
		$item->apply_changes();
		$this->clear_cache( $item );

		/**
		 * Action that indicates that a document item has been updated in the DB.
		 *
		 * The dynamic portion of this hook, `$item->get_type()` refers to the
		 * item type e.g. invoice.
		 *
		 * @param integer                                       $document_item_id The document item id.
		 * @param \Vendidero\StoreaBill\Item $item The document item object.
		 * @param integer                                       $document_id The document id.
		 *
		 * @since 1.0.0
		 * @package Vendidero/StoreaBill
		 */
		do_action( "storeabill_{$item->get_type()}_item_updated", $item->get_id(), $item, $item->get_document_id() );
	}

	/**
	 * Remove a document item from the database.
	 *
	 * @param \Vendidero\StoreaBill\Document\Item $item Document item object.
	 * @param array                                         $args Array of args to pass to the delete method.
	 *
	 *@since 1.0.0
	 */
	public function delete( &$item, $args = array() ) {
		if ( $item->get_id() ) {
			global $wpdb;

			/**
			 * Action that fires before deleting a document item from the DB.
			 *
			 * @param integer $document_item_id The document item id.
			 *
			 * @since 1.0.0
			 * @package Vendidero/StoreaBill
			 */
			do_action( 'storeabill_before_delete_document_item', $item->get_id() );

			$wpdb->delete( $wpdb->storeabill_document_items, array( 'document_item_id' => $item->get_id() ) );
			$wpdb->delete( $wpdb->storeabill_document_itemmeta, array( 'storeabill_document_item_id' => $item->get_id() ) );

			/**
			 * Action that indicates that a ShipmentItem has been deleted from the DB.
			 *
			 * @param integer                                       $document_item_id The document item id.
			 * @param \Vendidero\StoreaBill\Item $item The document item object.
			 *
			 * @since 1.0.0
			 * @package Vendidero/StoreaBill
			 */
			do_action( 'storeabill_delete_document_item', $item->get_id(), $item );
			$this->clear_cache( $item );
		}
	}

	/**
	 * Read a document item from the database.
	 *
	 * @param \Vendidero\StoreaBill\Document\Item $item Document item object.
	 *
	 * @throws Exception If invalid document item.
	 * @since 1.0.0
	 */
	public function read( &$item ) {
		global $wpdb;

		$item->set_defaults();

		// Get from cache if available.
		$data = wp_cache_get( 'item-' . $item->get_id(), 'document-items' );

		if ( false === $data ) {
			$data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->storeabill_document_items} WHERE document_item_id = %d LIMIT 1;", $item->get_id() ) );
			wp_cache_set( 'item-' . $item->get_id(), $data, 'document-items' );
		}

		if ( ! $data ) {
			throw new Exception( _x( 'Invalid document item.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}

		$item->set_props(
			array(
				'document_id'  => $data->document_id,
				'reference_id' => $data->document_item_reference_id,
				'parent_id'    => $data->document_item_parent_id,
				'name'         => $data->document_item_name,
				'quantity'     => $data->document_item_quantity
			)
		);

		$this->read_item_data( $item );
		$item->read_meta_data();
		$item->set_object_read( true );
	}

	/**
	 * Read extra data associated with the document item.
	 *
	 * @param \Vendidero\StoreaBill\Document\Item $item Document item object.
	 *
	 * @since 3.0.0
	 */
	protected function read_item_data( &$item ) {
		$props = array();

		foreach( array_merge( $this->internal_meta_keys, $this->core_meta_keys ) as $meta_key ) {
			$props[ substr( $meta_key, 1 ) ] = get_metadata( 'storeabill_document_item', $item->get_id(), $meta_key, true );
		}

		$item->set_props( $props );
	}

	/**
	 * Update meta data in, or delete it from, the database.
	 *
	 * Avoids storing meta when it's either an empty string or empty array.
	 * Other empty values such as numeric 0 and null should still be stored.
	 * Data-stores can force meta to exist using `must_exist_meta_keys`.
	 *
	 * Note: WordPress `get_metadata` function returns an empty string when meta data does not exist.
	 *
	 * @param WC_Data $object The WP_Data object (WC_Coupon for coupons, etc).
	 * @param string   $meta_key Meta key to update.
	 * @param mixed    $meta_value Value to save.
	 *
	 * @since 3.6.0 Added to prevent empty meta being stored unless required.
	 *
	 * @return bool True if updated/deleted.
	 */
	protected function update_or_delete_meta( $object, $meta_key, $meta_value ) {
		if ( in_array( $meta_value, array( array(), '' ), true ) && ! in_array( $meta_key, $this->must_exist_meta_keys, true ) ) {
			$updated = delete_metadata( 'storeabill_document_item', $object->get_id(), $meta_key );
		} else {
			$updated = update_metadata( 'storeabill_document_item', $object->get_id(), $meta_key, $meta_value );
		}

		return (bool) $updated;
	}

	/**
	 * Saves an item's data to the database / item meta.
	 * Ran after both create and update, so $item->get_id() will be set.
	 *
	 * @param \Vendidero\StoreaBill\Document\Item $item Document item object.
	 *
	 * @since 1.0.0
	 */
	public function save_item_data( &$item ) {
		$updated_props     = array();
		$meta_key_to_props = array();

		foreach( array_merge( $this->internal_meta_keys, $this->core_meta_keys ) as $meta_key ) {

			$prop_name = substr( $meta_key, 1 );

			if ( in_array( $prop_name, $this->core_props ) ) {
				continue;
			}

			$meta_key_to_props[ $meta_key ] = $prop_name;
		}

		$props_to_update = $this->get_props_to_update( $item, $meta_key_to_props, 'storeabill_document_item' );

		foreach ( $props_to_update as $meta_key => $prop ) {

			$getter = "get_$prop";

			if ( ! is_callable( array( $item, $getter ) ) ) {
				continue;
			}

			$value   = $this->format_update_value( $item, $prop );
			$updated = $this->update_or_delete_meta( $item, $meta_key, $value );

			if ( $updated ) {
				$updated_props[] = $prop;
			}
		}

		/**
		 * Action that fires after updating a document item's properties.
		 *
		 * @param \Vendidero\StoreaBill\Document\Item $item The document item object.
		 * @param array                                         $changed_props The updated properties.
		 *
		 * @since 1.0.0
		 * @package Vendidero/StoreaBill
		 */
		do_action( 'storeabill_document_item_object_updated_props', $item, $updated_props );
	}

	protected function format_update_value( $item, $prop ) {
		$value = $item->{"get_$prop"}( 'edit' );
		$value = is_string( $value ) ? wp_slash( $value ) : $value;

		/**
		 * Convert attributes to their array rep
		 */
		if ( 'attributes' === $prop ) {
			$value = array_map( function( $attribute ) {
				return $attribute->toArray();
			}, (array) $value );
		}

		return $value;
	}

	/**
	 * Read document item's children from database.
	 *
	 * @param \Vendidero\StoreaBill\Item $item The item.
	 *
	 * @return array|DocumentItem[]
	 */
	public function read_children( &$item ) {

		// Get from cache if available.
		$items = wp_cache_get( 'item-children-' . $item->get_id(), 'document-items' );

		if ( false === $items ) {
			global $wpdb;

			$get_items_sql = $wpdb->prepare( "SELECT * FROM {$wpdb->storeabill_document_items} WHERE document_item_parent_id = %d ORDER BY document_item_id;", $item->get_id() );
			$items         = $wpdb->get_results( $get_items_sql );

			foreach ( $items as $child ) {
				wp_cache_set( 'item-' . $child->document_item_id, $child, 'document-items' );
			}

			wp_cache_set( 'item-children-' . $item->get_id(), $items, 'document-items' );
		}

		if ( ! empty( $items ) ) {
			$items = array_map( function( $item ) {
				return sab_get_document_item( $item->document_item_id, $item->document_item_type );
			}, $items );
		} else {
			$items = array();
		}

		return $items;
	}

	/**
	 * @param \Vendidero\StoreaBill\Document\Item $item
	 * @param bool $force
	 */
	public function remove_children( &$item, $force = false ) {

		foreach ( $item->get_children() as $child ) {
			$child->delete( $force );
		}

		$this->clear_cache( $item );
	}

	/**
	 * Clear meta cache.
	 *
	 * @param \Vendidero\StoreaBill\Document\Item $item Document item object.
	 */
	public function clear_cache( &$item ) {
		wp_cache_delete( 'item-' . $item->get_id(), 'document-items' );
		wp_cache_delete( 'item-children-' . $item->get_id(), 'document-items' );
		wp_cache_delete( 'document-items-' . $item->get_document_id(), 'documents' );
		wp_cache_delete( $item->get_id(), $this->meta_type . '_meta' );
	}

	/**
	 * Table structure is slightly different between meta types, this function will return what we need to know.
	 *
	 * @since  3.0.0
	 * @return array Array elements: table, object_id_field, meta_id_field
	 */
	protected function get_db_info() {
		global $wpdb;

		$meta_id_field   = 'meta_id'; // for some reason users calls this umeta_id so we need to track this as well.
		$table           = $wpdb->storeabill_document_itemmeta;
		$object_id_field = $this->meta_type . '_id';

		if ( ! empty( $this->object_id_field_for_meta ) ) {
			$object_id_field = $this->object_id_field_for_meta;
		}

		return array(
			'table'           => $table,
			'object_id_field' => $object_id_field,
			'meta_id_field'   => $meta_id_field,
		);
	}
}
