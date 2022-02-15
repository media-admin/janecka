<?php

namespace Vendidero\StoreaBill\Document;

use Vendidero\StoreaBill\Data;
use Vendidero\StoreaBill\Interfaces\SyncableReferenceItem;
use WC_Data_Store;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Document item class.
 */
abstract class Item extends Data {

	protected $document = null;

	protected $children = null;

	protected $parent = null;

	protected $children_to_delete = array();

	protected $reference = null;

	protected $attributes = null;

	/**
	 * Document item data array.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $data = array(
		'document_id'  => 0,
		'reference_id' => 0,
		'parent_id'    => 0,
		'name'         => '',
		'quantity'     => 1,
		'attributes'   => array(),
	);

	/**
	 * Stores meta in cache for future reads.
	 * A group must be set to to enable caching.
	 *
	 * @var string
	 */
	protected $cache_group = 'document-items';

	/**
	 * Meta type. This should match up with
	 * the types available at https://developer.wordpress.org/reference/functions/add_metadata/.
	 * WP defines 'post', 'user', 'comment', and 'term'.
	 *
	 * @var string
	 */
	protected $meta_type = 'document_item';

	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'document_item';

	protected $data_store_name = 'document_item';

	protected $key = '';

	protected $parent_key = '';

	protected $current_position = 1;

	/**
	 * Constructor.
	 *
	 * @param int|object|array $item ID to load from the DB, or WC_Order_Item object.
	 */
	public function __construct( $item = 0 ) {
		parent::__construct( $item );

		if ( $item instanceof Item ) {
			$this->set_id( $item->get_id() );
		} elseif ( is_numeric( $item ) && $item > 0 ) {
			$this->set_id( $item );
		} else {
			$this->set_object_read( true );
		}

		$this->data_store = sab_load_data_store( $this->data_store_name );

		// If we have an ID, load the user from the DB.
		if ( $this->get_id() ) {
			try {
				$this->data_store->read( $this );
			} catch ( Exception $e ) {
				$this->set_id( 0 );
				$this->set_object_read( true );
			}
		} else {
			$this->set_object_read( true );
		}
	}

	public function apply_changes() {
		if ( function_exists( 'array_replace' ) ) {
			$this->data = array_replace( $this->data, $this->changes ); // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.array_replaceFound
		} else { // PHP 5.2 compatibility.
			foreach ( $this->changes as $key => $change ) {
				$this->data[ $key ] = $change;
			}
		}

		$this->changes = array();
	}

	/**
	 * Get all class data in array format.
	 *
	 * @since 3.0.0
	 * @return array
	 */
	public function get_data() {
		$data = array_merge(
			array(
				'id' => $this->get_id(),
			),
			$this->data,
			array(
				'meta_data' => $this->get_meta_data(),
			)
		);

		$data['attributes'] = array();

		foreach( $this->get_attributes() as $attribute ) {
			$data['attributes'][] = $attribute->get_data();
		}

		return $data;
	}

	public function get_key() {
		return ( $this->get_id() > 0 ) ? $this->get_id() : $this->key;
	}

	public function set_key( $key ) {
		$this->key = $key;
	}

	/**
	 * Gets the current position of an item
	 * within a table.
	 *
	 * @return int
	 */
	public function get_current_position() {
		return $this->current_position;
	}

	/**
	 * Sets the current position (count) within a
	 * table output.
	 */
	public function set_current_position( $current_position ) {
		$this->current_position = (int) $current_position;
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	abstract public function get_item_type();

	public function get_document_group() {
		return 'others';
	}

	public function get_type() {
		return "{$this->get_document_group()}_{$this->get_item_type()}";
	}

	/**
	 * Get order ID this meta belongs to.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int
	 */
	public function get_document_id( $context = 'view' ) {
		return $this->get_prop( 'document_id', $context );
	}

	/**
	 * Get order ID this meta belongs to.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int
	 */
	public function get_reference_id( $context = 'view' ) {
		return $this->get_prop( 'reference_id', $context );
	}

	/**
	 * @param string $context
	 *
	 * @return Attribute[]|null
	 */
	public function get_attributes( $context = 'view' ) {
		if ( is_null( $this->attributes ) ) {
			$this->attributes = array();

			foreach( ( array) $this->get_prop( 'attributes' ) as $attribute_data ) {
				$this->attributes[] = new Attribute( $attribute_data );
			}
		}

		uasort( $this->attributes, array( $this, '_sort_attributes_callback' ) );

		return apply_filters( "{$this->get_hook_prefix()}attributes", array_values( $this->attributes ), $this );
	}

	public function get_attribute( $key ) {
		$attributes         = $this->get_attributes();
		$matching_attribute = false;
		$key                = strtolower( $key );

		foreach( $attributes as $attribute ) {
			if ( $key == strtolower( $attribute->get_key() ) ) {
				$matching_attribute = $attribute;
				break;
			}
		}

		return $matching_attribute;
	}

	public function get_attribute_value( $key, $auto_p = false ) {
		$value = '';

		if ( $attribute = $this->get_attribute( $key ) ) {
			$value = $attribute->get_formatted_value( $auto_p );
		}

		return $value;
	}

	/**
	 * @param Attribute $attribute1
	 * @param Attribute $attribute2
	 *
	 * @return int
	 */
	public function _sort_attributes_callback( $attribute1, $attribute2 ) {
		return $attribute1->get_order() < $attribute2->get_order() ? -1 : 1;
	}

	/**
	 * @param Attribute $attribute
	 *
	 * @return void
	 */
	public function add_attribute( $attribute ) {
		$this->get_attributes();

		$this->attributes[] = $attribute;
	}

	/**
	 * @return bool|SyncableReferenceItem
	 */
	public function get_reference() {
		if ( is_null( $this->reference ) ) {
			$this->reference = false;
		}

		return $this->reference;
	}

	/**
	 * Get item parent id.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int
	 */
	public function get_parent_id( $context = 'view' ) {
		return $this->get_prop( 'parent_id', $context );
	}

	public function get_parent_key() {
		return ( $this->get_parent_id() > 0 ) ? $this->get_parent_id() : $this->parent_key;
	}

	public function set_parent_key( $key ) {
		$this->parent_key = $key;
	}

	public function get_name( $context = 'view' ) {
		return $this->get_prop( 'name', $context );
	}

	public function get_quantity( $context = 'view' ) {
		return $this->get_prop( 'quantity', $context );
	}

	/**
	 * @return bool|Item
	 */
	public function get_parent() {
		if ( is_null( $this->parent ) && 0 < $this->get_parent_id() ) {
			$this->parent = sab_get_document_item( $this->get_parent_id() );
		}

		$parent = ( $this->parent ) ? $this->parent : false;

		return $parent;
	}

	/**
	 * Get parent document object.
	 *
	 * @return Document|boolean
	 */
	public function get_document() {
		if ( is_null( $this->document ) && 0 < $this->get_document_id() ) {
			$this->document = sab_get_document( $this->get_document_id() );
		}

		$document = ( $this->document ) ? $this->document : false;

		return $document;
	}

	/**
	 * Check whether the item is really connected to a document
	 * which means it is registered as an item within the document.
	 *
	 * @return bool
	 */
	protected function has_document() {
		$has_document = false;

		if ( $document = $this->get_document() ) {
			if ( $document->get_item( $this->get_key() ) ) {
				$has_document = true;
			}
		}

		return $has_document;
 	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * @param Document $document
	 */
	public function set_document( $document ) {
		$this->set_document_id( $document->get_id() );

		$this->reload_children();
		$this->document = $document;
	}

	public function reload_children() {
		$this->children = null;
	}

	/**
	 * Set document id.
	 *
	 * @param int $value document id.
	 */
	public function set_document_id( $value ) {
		$this->document = null;

		$this->set_prop( 'document_id', absint( $value ) );
	}

	/**
	 * Set order ID.
	 *
	 * @param int $value Order ID.
	 */
	public function set_reference_id( $value ) {
		$this->set_prop( 'reference_id', absint( $value ) );

		$this->reference = null;
	}

	/**
	 * Set attributes.
	 *
	 * @param [] $attributes The attributes.
	 */
	public function set_attributes( $attributes ) {
		$this->attributes = null;
		$attributes = (array) $attributes;

		$this->set_prop( 'attributes', array_filter( $attributes ) );
	}

	/**
	 * Set quantity.
	 *
	 * @param int|float $value quantity.
	 */
	public function set_quantity( $value ) {
		$this->set_prop( 'quantity', sab_format_item_quantity( $value ) );
	}

	/**
	 * Set parent id.
	 *
	 * @param int $value parent id.
	 */
	public function set_parent_id( $value ) {
		$this->set_prop( 'parent_id', absint( $value ) );
	}

	/**
	 * @param Item $item
	 */
	public function set_parent( $item ) {
		$this->parent = $item;

		$this->set_parent_id( $item->get_id() );
		$this->set_parent_key( $item->get_key() );
	}

	public function set_name( $name ) {
		$this->set_prop( 'name', $name );
	}

	/**
	 * Returns a list of stored children.
	 *
	 * @return array|Item[]
	 */
	public function get_children() {
		/**
		 * If we are connected to a document - load children from document
		 * to make sure that we are working on the same instances.
		 *
		 * @TODO improve item parent key search
		 */
		if ( $this->has_document() ) {
			$items    = $this->get_document()->get_items();
			$children = array();

			foreach( $items as $item ) {

				if ( empty( $item->get_parent_key() ) ) {
					continue;
				}

				if ( $item->get_parent_key() === $this->get_key() ) {
					$children[ $item->get_key() ] = $item;
				}
			}

			return $children;

		} elseif ( is_null( $this->children ) ) {

			// Load children
			$this->children = array();

			if ( $this->get_id() > 0 ) {
				$this->children = $this->data_store->read_children( $this );
			}
		}

		return $this->children;
	}

	/**
	 * Adds a child to the current item.
	 *
	 * @param Item $item
	 */
	public function add_child( $item ) {
		$this->get_children();

		$item->set_parent( $this );
		$item->set_document_id( $this->get_document_id() );

		if ( $this->has_document() ) {
			$item->set_document( $this->get_document() );
			$this->get_document()->add_item( $item );
		} else {
			$key = $item->get_id();

			if ( $key > 0 ) {
				$this->children[ $key ] = $item;
			} else {
				$key = 'new_' . $item->get_item_type() . ':' . sizeof( $this->children ) . uniqid();
				$item->set_key( $key );

				$this->children[ $key ] = $item;
			}
		}
	}

	public function get_child( $key ) {

		if ( $this->has_document() ) {
			return $this->get_document()->get_item( $key );
		}

		$children = $this->get_children();

		// Search for item key.
		if ( $children ) {
			if ( isset( $children[ $key ] ) ) {
				$children[ $key ]->set_parent( $this );
				return $children[ $key ];
			}
		}

		return false;
	}

	/**
	 * Deletes children and removes them from the item.
	 */
	public function remove_children( $force = true ) {
		if ( $this->has_document() ) {
			foreach( $this->get_children() as $child ) {
				$this->remove_child( $child->get_key() );
			}
		} else {
			$this->data_store->remove_children( $this, $force );
		}

		$this->children = array();
	}

	/**
	 * Deletes children and removes them from the item.
	 */
	public function remove_child( $key, $force = true ) {
		$child = $this->get_child( $key );

		if ( ! $child ) {
			return false;
		}

		if ( $this->has_document() ) {
			$this->get_document()->remove_item( $key );
		} elseif ( is_numeric( $key ) ) {
			$this->children_to_delete[] = $child;
		}

		if ( isset( $this->children[ $key ] ) ) {
			unset( $this->children[ $key ] );
		}
	}

	public function get_image_url( $size = '', $placeholder = false ) {
		$image_url = '';

		if ( $placeholder ) {
			$image_url = sab_placeholder_img( $size );
		}

		return $image_url;
	}

	/*
	|--------------------------------------------------------------------------
	| Other Methods
	|--------------------------------------------------------------------------
	*/
	public function save() {

		if ( $parent = $this->get_parent() ) {
			$this->set_parent_id( $parent->get_id() );
		}

		$result = parent::save();

		if ( ! $this->get_document() ) {
			$this->save_children();
		}

		return $result;
	}

	/**
	 * Saves the children item states in the database.
	 */
	protected function save_children() {
		foreach ( $this->children_to_delete as $child ) {
			$child->delete( true );
		}

		foreach ( $this->get_children() as $child ) {
			$child->set_document_id( $this->get_document_id() );
			$child->set_parent_id( $this->get_id() );
			$child->save();
		}
	}
}
