<?php

namespace Vendidero\StoreaBill\DataStores;

use Vendidero\StoreaBill\Document\Factory;
use Vendidero\StoreaBill\Utilities\CacheHelper;
use WC_Data_Store_WP;
use WC_Object_Data_Store_Interface;
use Exception;
use WC_Data;

defined( 'ABSPATH' ) || exit;

/**
 * Document data store.
 *
 * @version 1.0.0
 */
abstract class Document extends WC_Data_Store_WP implements WC_Object_Data_Store_Interface {

	/**
	 * Internal meta type used to store document data.
	 *
	 * @var string
	 */
	protected $meta_type = 'storeabill_document';

	/**
	 * Data stored in meta keys, but not considered "meta" for a document.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_address',
		'_created_via',
		'_reference_number',
		'_external_sync_handlers',
		'_version'
	);

	protected $internal_date_props_to_keys = array(
		'date_created'      => 'date_created',
		'date_modified'     => 'date_modified',
		'date_sent'         => 'date_sent',
		'date_custom'       => 'date_custom',
		'date_custom_extra' => 'date_custom_extra'
	);

	protected $core_props = array(
		'date_created',
		'date_created_gmt',
		'date_modified',
		'date_modified_gmt',
		'date_sent',
		'date_sent_gmt',
		'date_custom',
		'date_custom_gmt',
		'date_custom_extra',
		'date_custom_extra_gmt',
		'parent_id',
		'reference_id',
		'reference_type',
		'customer_id',
		'author_id',
		'journal_type',
		'country',
		'status',
		'number',
		'formatted_number',
		'relative_path',
	);

	/**
	 * If we have already saved our extra data, don't do automatic / default handling.
	 *
	 * @var bool
	 */
	protected $extra_data_saved = false;

	/*
	|--------------------------------------------------------------------------
	| CRUD Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Method to create a new document in the database.
	 *
	 * @param \Vendidero\StoreaBill\Document\Document $document Document object.
	 */
	public function create( &$document ) {
		global $wpdb;

		$document->set_version( SAB_VERSION );

		if ( ! $document->get_date_created() ) {
			$document->set_date_created( time() );
		}

		if ( 0 >= $document->get_author_id() ) {
			$document->set_author_id( 1 );
		}

		$this->maybe_set_number( $document );

		$data = array(
			'document_country'           => $document->get_country( 'edit' ),
			'document_reference_id'      => $document->get_reference_id( 'edit' ),
			'document_parent_id'         => $document->get_parent_id( 'edit' ),
			'document_customer_id'       => $document->get_customer_id( 'edit' ),
			'document_author_id'         => $document->get_author_id( 'edit' ),
			'document_number'            => $document->get_number( 'edit' ),
			'document_formatted_number'  => $document->get_formatted_number( 'edit' ),
			'document_status'            => $this->get_status( $document ),
			'document_type'              => $document->get_type(),
			'document_reference_type'    => $document->get_reference_type( 'edit' ),
			'document_journal_type'      => $document->get_journal_type( 'edit' ),
			'document_relative_path'     => $document->get_relative_path( 'edit' ),
			'document_date_created'      => gmdate( 'Y-m-d H:i:s', $document->get_date_created( 'edit' )->getOffsetTimestamp() ),
			'document_date_created_gmt'  => gmdate( 'Y-m-d H:i:s', $document->get_date_created( 'edit' )->getTimestamp() ),
		);

		if ( $document->get_date_sent() ) {
			$data['document_date_sent']     = gmdate( 'Y-m-d H:i:s', $document->get_date_sent( 'edit' )->getOffsetTimestamp() );
			$data['document_date_sent_gmt'] = gmdate( 'Y-m-d H:i:s', $document->get_date_sent( 'edit' )->getTimestamp() );
		}

		if ( $document->get_date_custom() ) {
			$data['document_date_custom']     = gmdate( 'Y-m-d H:i:s', $document->get_date_custom( 'edit' )->getOffsetTimestamp() );
			$data['document_date_custom_gmt'] = gmdate( 'Y-m-d H:i:s', $document->get_date_custom( 'edit' )->getTimestamp() );
		}

		if ( $document->get_date_custom_extra() ) {
			$data['document_date_custom_extra']     = gmdate( 'Y-m-d H:i:s', $document->get_date_custom_extra( 'edit' )->getOffsetTimestamp() );
			$data['document_date_custom_extra_gmt'] = gmdate( 'Y-m-d H:i:s', $document->get_date_custom_extra( 'edit' )->getTimestamp() );
		}

		$wpdb->insert(
			$wpdb->storeabill_documents,
			$data
		);

		$document_id = $wpdb->insert_id;

		if ( $document_id ) {
			$document->set_id( $document_id );
			$this->save_document_data( $document );

			$document->save_meta_data();
			$document->apply_changes();

			$this->clear_caches( $document );

			/**
			 * Action that indicates that a new document has been created in the DB.
			 *
			 * The dynamic portion of this hook, `$document->get_type()` refers to the
			 * document type e.g. invoice.
			 *
			 * @param integer                                   $document_id The document id.
			 * @param \Vendidero\StoreaBill\Document\Document $document The document instance.
			 *
			 * @since 3.0.0
			 * @package Vendidero/Germanized/Shipments
			 */
			do_action( "storeabill_new_{$document->get_type()}", $document_id, $document );
		}
	}

	/**
	 * Get the status to save to the object.
	 *
	 * @since 3.6.0
	 * @param \Vendidero\StoreaBill\Document\Document $document Document object.
	 * @return string
	 */
	protected function get_status( $document ) {
		$status = $document->get_status( 'edit' );

		if ( ! $status ) {
			$default_status = 'draft';

			if ( $document_type = sab_get_document_type( $document->get_type() ) ) {
				$default_status = $document_type->default_status;
			}

			/** This filter is documented in src/Document.php */
			$status = apply_filters( "storeabill_{$document->get_type()}_get_default_status", $default_status );
		}

		if ( ! in_array( $status, array_keys( sab_get_document_statuses( $document->get_type() ) ) ) ) {
			$status = 'draft';
		}

		return $status;
	}

	/**
	 * Method to update a document in the database.
	 *
	 * @param \Vendidero\StoreaBill\Document\Document $document Document object.
	 */
	public function update( &$document ) {
		global $wpdb;

		$this->maybe_set_number( $document );

		$core_props    = $this->core_props;
		$changed_props = array_keys( $document->get_changes() );
		$document_data = array();

		// Make sure country in core props is updated as soon as the address changes
		if ( in_array( 'address', $changed_props ) ) {
			$changed_props[] = 'country';
		}

		if ( ! empty( $changed_props ) ) {
			$document->set_version( SAB_VERSION );
		}

		foreach ( $changed_props as $prop ) {
			if ( ! in_array( $prop, $core_props, true ) ) {
				continue;
			}

			switch( $prop ) {
				case "status":
					$document_data[ 'document_' . $prop ] = $this->get_status( $document );
					break;
				case "date_created":
				case "date_modified":
				case "date_sent":
				case "date_custom":
				case "date_custom_extra":
					if ( is_callable( array( $document, 'get_' . $prop ) ) ) {
						$document_data[ 'document_' . $prop ]          = $document->{'get_' . $prop}( 'edit' ) ? gmdate( 'Y-m-d H:i:s', $document->{'get_' . $prop}( 'edit' )->getOffsetTimestamp() ) : '0000-00-00 00:00:00';
						$document_data[ 'document_' . $prop . '_gmt' ] = $document->{'get_' . $prop}( 'edit' ) ? gmdate( 'Y-m-d H:i:s', $document->{'get_' . $prop}( 'edit' )->getTimestamp() ) : '0000-00-00 00:00:00';
					}
					break;
				default:
					if ( is_callable( array( $document, 'get_' . $prop ) ) ) {
						$document_data[ 'document_' . $prop ] = $document->{'get_' . $prop}( 'edit' );
					}
					break;
			}
		}

		if ( ! empty( $document_data ) ) {
			$wpdb->update(
				$wpdb->storeabill_documents,
				$document_data,
				array( 'document_id' => $document->get_id() )
			);
		}

		$this->save_document_data( $document );

		$document->save_meta_data();
		$document->apply_changes();

		$this->clear_caches( $document );

		/**
		 * Action that indicates that a document has been updated in the DB.
		 *
		 * The dynamic portion of this hook, `$document->get_type()` refers to the
		 * document type e.g. invoice.
		 *
		 * @param integer  $document_id The document id.
		 * @param Document $document The document instance.
		 *
		 * @since 1.0.0
		 * @package Vendidero/StoreaBill
		 */
		do_action( "storeabill_{$document->get_type()}_updated", $document->get_id(), $document );
	}

	/**
	 * Remove a document from the database.
	 *
	 * @param \Vendidero\StoreaBill\Document\Document $document Document object.
	 * @param bool                                      $force_delete Whether to force deletion or not.
	 *
	 * @since 1.0.0
	 */
	public function delete( &$document, $force_delete = false ) {
		global $wpdb;

		if ( ! $force_delete ) {
			$document->update_status( 'archive' );

			/**
			 * Action that indicates that a document has been archived.
			 *
			 * The dynamic portion of this hook, `$document->get_type()` refers to the
			 * document type e.g. invoice.
			 *
			 * @param integer                                   $document_id The document id.
			 * @param \Vendidero\StoreaBill\Document\Document $document The document object.
			 *
			 * @since 1.0.0
			 * @package Vendidero/StoreaBill
			 */
			do_action( "storeabill_{$document->get_type()}_archived", $document->get_id(), $document );
		} else {
			$wpdb->delete( $wpdb->storeabill_documents, array( 'document_id' => $document->get_id() ), array( '%d' ) );
			$wpdb->delete( $wpdb->storeabill_documentmeta, array( 'storeabill_document_id' => $document->get_id() ), array( '%d' ) );

			$this->delete_items( $document );
			$this->delete_notices( $document );
			$this->delete_file( $document );

			$this->clear_caches( $document );

			/**
			 * Action that indicates that a document has been deleted from the DB.
			 *
			 * The dynamic portion of this hook, `$document->get_type()` refers to the
			 * document type e.g. invoice.
			 *
			 * @param integer                                   $document_id The document id.
			 * @param \Vendidero\StoreaBill\Document\Document $document The document object.
			 *
			 * @since 1.0.0
			 * @package Vendidero/StoreaBill
			 */
			do_action( "storeabill_{$document->get_type()}_deleted", $document->get_id(), $document );
		}
	}

	/**
	 * Read a document from the database.
	 *
	 * @param \Vendidero\StoreaBill\Document\Document $document Document object.
	 *
	 * @throws Exception Throw exception if invalid document.
	 *
	 * @since 1.0.0
	 */
	public function read( &$document ) {
		global $wpdb;

		$data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->storeabill_documents} WHERE document_id = %d LIMIT 1",
				$document->get_id()
			)
		);

		if ( $data ) {
			$document->set_props(
				array(
					'reference_id'      => $data->document_reference_id,
					'reference_type'    => $data->document_reference_type,
					'country'           => $data->document_country,
					'parent_id'         => $data->document_parent_id,
					'customer_id'       => $data->document_customer_id,
					'author_id'         => $data->document_author_id,
					'number'            => $data->document_number,
					'formatted_number'  => $data->document_formatted_number,
					'journal_type'      => $data->document_journal_type,
					'relative_path'     => $data->document_relative_path,
					'date_created'      => '0000-00-00 00:00:00' !== $data->document_date_created_gmt ? wc_string_to_timestamp( $data->document_date_created_gmt ) : null,
					'date_modified'     => '0000-00-00 00:00:00' !== $data->document_date_modified_gmt ? wc_string_to_timestamp( $data->document_date_modified_gmt ) : null,
					'date_sent'         => '0000-00-00 00:00:00' !== $data->document_date_sent_gmt ? wc_string_to_timestamp( $data->document_date_sent_gmt ) : null,
					'date_custom'       => '0000-00-00 00:00:00' !== $data->document_date_custom_gmt ? wc_string_to_timestamp( $data->document_date_custom_gmt ) : null,
					'date_custom_extra' => '0000-00-00 00:00:00' !== $data->document_date_custom_extra_gmt ? wc_string_to_timestamp( $data->document_date_custom_extra_gmt ) : null,
					'status'            => $data->document_status,
				)
			);

			$this->read_document_data( $document );
			$this->read_extra_data( $document );

			$document->read_meta_data();
			$document->set_object_read( true );

			/**
			 * Action that indicates that a document has been loaded from DB.
			 *
			 * The dynamic portion of this hook, `$document->get_type()` refers to the
			 * document type e.g. invoice.
			 *
			 * @param \Vendidero\StoreaBill\Document\Document $document The document object.
			 *
			 * @since 1.0.0
			 * @package Vendidero/StoreaBill
			 */
			do_action( "storeabill_{$document->get_type()}_loaded", $document );
		} else {
			throw new Exception( _x( 'Invalid document.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}
	}

	/**
	 * Maybe generate a sequential number for the current invoice.
	 *
	 * @param \Vendidero\StoreaBill\Document\Document $document Document object.
	 */
	protected function maybe_set_number( &$document ) {
		if ( $document->number_upon_save() ) {
			$success = false;

			if ( $journal = $document->get_journal() ) {
				$number = $journal->next_number();

				if ( ! is_wp_error( $number ) ) {
					$document->set_number( $number );
					$document->set_formatted_number( $document->format_number( $number ) );

					$success = true;

					do_action( "storeabill_{$document->get_type()}_numbered", $document->get_id(), $document );
				}
			}

			if ( ! $success ) {
				/**
				 * Make sure that the document does not transitions to closed
				 * in case the numbering failed.
				 */
				$document->set_status( 'draft' );
			}
		}
	}

	/**
	 * Clear any caches.
	 *
	 * @param \Vendidero\StoreaBill\Document\Document $document Document object.
	 * @since 1.0.0
	 */
	protected function clear_caches( &$document ) {
		wp_cache_delete( 'document-items-' . $document->get_id(), 'documents' );
		wp_cache_delete( $document->get_id(), $this->meta_type . '_meta' );
		CacheHelper::invalidate_cache_group( 'documents' );
	}

	/*
	|--------------------------------------------------------------------------
	| Additional Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get the document type based on document ID.
	 *
	 * @param int $document_id Document id.
	 * @return string
	 */
	public function get_document_type( $document_id ) {
		return Factory::get_document_type( $document_id );
	}

	/**
	 * Read extra data associated with the document.
	 *
	 * @param \Vendidero\StoreaBill\Document\Document $document Document object.
	 * @since 1.0.0
	 */
	protected function read_extra_data( &$document ) {
		foreach ( $document->get_extra_data_keys() as $key ) {
			$function = 'set_' . $key;

			if ( is_callable( array( $document, $function ) ) ) {
				$document->{$function}( get_metadata( 'storeabill_document', $document->get_id(), '_' . $key, true ) );
			}
		}
	}

	/**
	 * Read extra data associated with the document.
	 *
	 * @param \Vendidero\StoreaBill\Document\Document $document Document object.
	 * @since 1.0.0
	 */
	protected function read_document_data( &$document ) {
		$props = array();

		foreach( $this->internal_meta_keys as $meta_key ) {
			$props[ substr( $meta_key, 1 ) ] = $this->format_read_value( $meta_key, $document );
		}

		$document->set_props( $props );
	}

	/**
	 * @param $meta_key
	 * @param \Vendidero\StoreaBill\Document\Document $document
	 *
	 * @return mixed
	 */
	protected function format_read_value( $meta_key, $document ) {
		$value = get_metadata( 'storeabill_document', $document->get_id(), $meta_key, true );

		if ( strpos( $meta_key, 'date_' ) !== false ) {
			if ( metadata_exists( 'storeabill_document', $document->get_id(), $meta_key . '_gmt' ) ) {
				$value = get_metadata( 'storeabill_document', $document->get_id(), $meta_key . '_gmt', true );
			}
		}

		return $value;
	}

	/**
	 * @param mixed[] $props
	 * @param \Vendidero\StoreaBill\Document\Document $document
	 */
	protected function filter_props_to_update( $props, &$document ) {
		return $props;
	}

	/**
	 * @param \Vendidero\StoreaBill\Document\Document $document
	 */
	protected function save_document_data( &$document ) {
		$updated_props     = array();
		$meta_key_to_props = array();

		foreach( $this->internal_meta_keys as $meta_key ) {
			$prop_name = substr( $meta_key, 1 );

			if ( in_array( $prop_name, $this->core_props ) ) {
				continue;
			}

			$meta_key_to_props[ $meta_key ] = $prop_name;
		}

		// Make sure to take extra data into account.
		$extra_data_keys = $document->get_extra_data_keys();

		foreach ( $extra_data_keys as $key ) {
			$meta_key_to_props[ '_' . $key ] = $key;
		}

		$props_to_update = $this->get_props_to_update( $document, $meta_key_to_props, 'storeabill_document' );
		$props_to_update = $this->filter_props_to_update( $props_to_update, $document );

		foreach ( $props_to_update as $meta_key => $prop ) {

			if ( ! is_callable( array( $document, "get_$prop" ) ) ) {
				continue;
			}

			$value   = $this->format_update_value( $document, $prop );
			$updated = $this->update_or_delete_meta( $document, $meta_key, $value );

			if ( $updated ) {
				$updated_props[] = $prop;
			}
		}

		/**
		 * Action that fires after updating a document's properties.
		 *
		 * @param \Vendidero\StoreaBill\Document\Document $document The document object.
		 * @param array                                     $changed_props The updated properties.
		 *
		 * @since 1.0.0
		 * @package Vendidero/StoreaBill
		 */
		do_action( 'storeabill_document_updated_props', $document, $updated_props );
	}

	protected function format_update_value( $document, $prop ) {
		$value = $document->{"get_$prop"}( 'edit' );
		$value = is_string( $value ) ? wp_slash( $value ) : $value;

		return $value;
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
	 * @param string  $meta_key Meta key to update.
	 * @param mixed   $meta_value Value to save.
	 *
	 * @since 3.6.0 Added to prevent empty meta being stored unless required.
	 *
	 * @return bool True if updated/deleted.
	 */
	protected function update_or_delete_meta( $object, $meta_key, $meta_value ) {
		$updated = false;

		if ( strpos( $meta_key, 'date_' ) !== false ) {
			if ( is_null( $meta_value ) ) {
				$updated = delete_metadata( 'storeabill_document', $object->get_id(), $meta_key );
			} elseif ( is_a( $meta_value, 'WC_DateTime' ) ) {
				$updated = update_metadata( 'storeabill_document', $object->get_id(), $meta_key, $meta_value->getOffsetTimestamp() );
				$updated = update_metadata( 'storeabill_document', $object->get_id(), ( $meta_key . '_gmt' ), $meta_value->getTimestamp() );
			}
		} elseif ( ! $updated ) {
			if ( in_array( $meta_value, array( array(), '' ), true ) && ! in_array( $meta_key, $this->must_exist_meta_keys, true ) ) {
				$updated = delete_metadata( 'storeabill_document', $object->get_id(), $meta_key );
			} else {
				$updated = update_metadata( 'storeabill_document', $object->get_id(), $meta_key, $meta_value );
			}
		}

		return (bool) $updated;
	}

	/**
	 * Read items from the database for this document.
	 *
	 * @param \Vendidero\StoreaBill\Document\Document $document Document object.
	 * @param string $type The document item type.
	 *
	 * @return array
	 */
	public function read_items( $document, $type = '' ) {
		global $wpdb;

		// Get from cache if available.
		$items = 0 < $document->get_id() ? wp_cache_get( 'document-items-' . $document->get_id(), 'documents' ) : false;

		if ( false === $items ) {

			if ( $document->get_id() > 0 ) {
				$items = $wpdb->get_results(
					$wpdb->prepare( "SELECT * FROM {$wpdb->storeabill_document_items} WHERE document_id = %d ORDER BY document_item_id;", $document->get_id() )
				);
			} else {
				$items = array();
			}

			foreach ( $items as $item ) {
				wp_cache_set( 'item-' . $item->document_item_id, $item, 'document-items' );
			}

			if ( 0 < $document->get_id() ) {
				wp_cache_set( 'document-items-' . $document->get_id(), $items, 'documents' );
			}
		}

		if ( ! empty( $type ) ) {
			$type  = sab_maybe_prefix_document_item_type( $type, $document->get_type() );
			$items = wp_list_filter( $items, array( 'document_item_type' => $type ) );
		}

		if ( ! empty( $items ) ) {
			$items = array_map( 'sab_get_document_item', array_combine( wp_list_pluck( $items, 'document_item_id' ), $items ) );
		} else {
			$items = array();
		}

		return $items;
	}

	/**
	 * Remove all items from the document.
	 *
	 * @param \Vendidero\StoreaBill\Document\Document $document Document object.
	 */
	public function delete_items( $document, $type = '' ) {
		global $wpdb;

		if ( ! empty( $type ) ) {
			$type = sab_maybe_prefix_document_item_type( $type, $document->get_type() );

			$wpdb->query( $wpdb->prepare( "DELETE FROM itemmeta USING {$wpdb->storeabill_document_itemmeta} itemmeta INNER JOIN {$wpdb->storeabill_document_items} items WHERE itemmeta.storeabill_document_item_id = items.document_item_id AND items.document_id = %d AND items.document_item_type = %s", $document->get_id(), $type ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->storeabill_document_items} WHERE document_id = %d AND document_item_type = %s", $document->get_id(), $type ) );
		} else {
			$wpdb->query( $wpdb->prepare( "DELETE FROM itemmeta USING {$wpdb->storeabill_document_itemmeta} itemmeta INNER JOIN {$wpdb->storeabill_document_items} items WHERE itemmeta.storeabill_document_item_id = items.document_item_id and items.document_id = %d", $document->get_id() ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->storeabill_document_items} WHERE document_id = %d", $document->get_id() ) );
		}

		$this->clear_caches( $document );
	}

	/**
	 * Read notices from the database for this document.
	 *
	 * @param \Vendidero\StoreaBill\Document\Document $document Document object.
	 *
	 * @return array
	 */
	public function read_notices( $document, $type = '' ) {
		global $wpdb;

		// Get from cache if available.
		$notices = 0 < $document->get_id() ? wp_cache_get( 'document-notices-' . $document->get_id(), 'documents' ) : false;

		if ( false === $notices ) {

			if ( $document->get_id() > 0 ) {
				$notices = $wpdb->get_results(
					$wpdb->prepare( "SELECT * FROM {$wpdb->storeabill_document_notices} WHERE document_id = %d ORDER BY document_notice_id;", $document->get_id() )
				);
			} else {
				$notices = array();
			}

			foreach ( $notices as $notice ) {
				wp_cache_set( 'notice-' . $notice->document_notice_id, $notice, 'document-notices' );
			}

			if ( 0 < $document->get_id() ) {
				wp_cache_set( 'document-notices-' . $document->get_id(), $notices, 'documents' );
			}
		}

		if ( ! empty( $type ) ) {
			$notices = wp_list_filter( $notices, array( 'document_notice_type' => $type ) );
		}

		if ( ! empty( $notices ) ) {
			$notices = array_map( function( $item ) {
				return sab_get_document_notice( $item->document_notice_id, $item->document_notice_type );
			}, $notices );
		} else {
			$notices = array();
		}

		return $notices;
	}

	/**
	 * Remove all notices from the document.
	 *
	 * @param \Vendidero\StoreaBill\Document\Document $document Document object.
	 */
	public function delete_notices( $document) {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( "DELETE FROM noticemeta USING {$wpdb->storeabill_document_noticemeta} noticemeta INNER JOIN {$wpdb->storeabill_document_notices} notices WHERE noticemeta.storeabill_document_notice_id = notices.document_notice_id and notices.document_id = %d", $document->get_id() ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->storeabill_document_notices} WHERE document_id = %d", $document->get_id() ) );

		$this->clear_caches( $document );
	}

	/**
	 * Remove the document file.
	 *
	 * @param \Vendidero\StoreaBill\Document\Document $document Document object.
	 */
	public function delete_file( $document ) {
		if ( $document->has_file() ) {
			wp_delete_file( $document->get_path() );
		}
	}

	/**
	 * Get valid WP_Query args from a WC_Order_Query's query variables.
	 *
	 * @since 3.0.6
	 * @param array $query_vars query vars from a WC_Order_Query.
	 * @return array
	 */
	protected function get_wp_query_args( $query_vars ) {
		global $wpdb;

		$skipped_values = array( '', array(), null );
		$wp_query_args  = array(
			'errors'     => array(),
			'meta_query' => isset( $query_vars['meta_query'] ) ? $query_vars['meta_query'] : array(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		);

		foreach ( $query_vars as $key => $value ) {
			if ( in_array( $value, $skipped_values, true ) || 'meta_query' === $key ) {
				continue;
			}

			// Build meta queries out of vars that are stored in internal meta keys.
			if ( in_array( '_' . $key, $this->internal_meta_keys, true ) && ! in_array( '_' . $key, array_values( $this->internal_date_props_to_keys ), true ) ) {
				// Check for existing values if wildcard is used.
				if ( '*' === $value ) {
					$wp_query_args['meta_query'][] = array(
						array(
							'key'     => '_' . $key,
							'compare' => 'EXISTS',
						),
						array(
							'key'     => '_' . $key,
							'value'   => '',
							'compare' => '!=',
						),
					);
				} else {
					$wp_query_args['meta_query'][] = array(
						'key'     => '_' . $key,
						'value'   => $value,
						'compare' => is_array( $value ) ? 'IN' : '=',
					);
				}
			} else { // Other vars get mapped to wp_query args or just left alone.
				$wp_query_args[ $key ] = $value;
			}
		}

		// Force type to be existent
		if ( isset( $query_vars['type'] ) ) {
			$wp_query_args['type'] = $query_vars['type'];
		}

		if ( ! isset( $wp_query_args['date_query'] ) ) {
			$wp_query_args['date_query'] = array();
		}

		if ( ! isset( $wp_query_args['meta_query'] ) ) {
			$wp_query_args['meta_query'] = array();
		}

		$date_queries = $this->internal_date_props_to_keys;

		foreach ( $date_queries as $query_var_key => $db_key ) {

			$is_meta_date_prop = substr( $db_key, 0, 1 ) === '_' ? true : false;

			if ( isset( $query_vars[ $query_var_key ] ) && '' !== $query_vars[ $query_var_key ] ) {

				if ( ! $is_meta_date_prop ) {
					// Pretend like this is a post_date e.g. core db prop
					$date_query_args = $this->parse_date_for_wp_query( $query_vars[ $query_var_key ], 'post_date', $wp_query_args );
				} else {
					$date_query_args = $this->parse_date_for_wp_query( $query_vars[ $query_var_key ], $db_key, array( 'meta_query' => array() ) );

					// Merge meta date results
					if ( ! empty( $date_query_args['meta_query'] ) ) {
						$wp_query_args['meta_query'] = array_merge( $wp_query_args['meta_query'], $date_query_args['meta_query'] );
					}
				}

				/**
				 * Replace date query columns after Woo parsed dates (for non-meta dates).
				 * Include table name because otherwise WP_Date_Query won't accept our custom column.
				 */
				if ( ! $is_meta_date_prop && isset( $date_query_args['date_query'] ) && ! empty( $date_query_args['date_query'] ) ) {
					$date_query = $date_query_args['date_query'][0];

					if ( 'post_date' === $date_query['column'] ) {
						$date_query['column'] = $wpdb->storeabill_documents . '.document_' . $db_key;
					} elseif ( 'post_date_gmt' === $date_query['column'] ) {
						$date_query['column'] = $wpdb->storeabill_documents . '.document_' . $db_key . '_gmt';
					}

					$wp_query_args['date_query'][] = $date_query;
				}
			}

			// Order by
			if ( isset( $query_vars['orderby'] ) && $query_var_key === $query_vars['orderby'] ) {
				// Order by meta date
				if ( $is_meta_date_prop ) {
					$wp_query_args['meta_key']  = $db_key;
					$wp_query_args['meta_type'] = 'TIME';
					$wp_query_args['orderby']   = 'meta_value_num';
				} else {
					// Orderby original db fields
					$wp_query_args['orderby'] = $db_key;
				}
			}
		}

		/**
		 * Filter to adjust document's query arguments after parsing.
		 *
		 * @param array    $wp_query_args Array containing parsed query arguments.
		 * @param array    $query_vars The original query arguments.
		 * @param Document $data_store The document data store object.
		 *
		 * @since 1.0.0
		 * @package Vendidero/Germanized/Storeabill
		 */
		return apply_filters( 'storeabill_document_data_store_get_documents_query', $wp_query_args, $query_vars, $this );
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
		$table           = $wpdb->storeabill_documentmeta;
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

	public function get_query_args( $query_vars ) {
		return $this->get_wp_query_args( $query_vars );
	}

	public function get_document_count( $status, $type = '' ) {
		global $wpdb;

		if ( empty( $type ) ) {
			$query = $wpdb->prepare( "SELECT COUNT( * ) FROM {$wpdb->storeabill_documents} WHERE document_status = %s", $status );
		} else {
			$query = $wpdb->prepare( "SELECT COUNT( * ) FROM {$wpdb->storeabill_documents} WHERE document_status = %s and document_type = %s", $status, $type );
		}

		return absint( $wpdb->get_var( $query ) );
	}
}
