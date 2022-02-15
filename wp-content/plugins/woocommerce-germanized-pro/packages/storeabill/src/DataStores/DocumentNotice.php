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
class DocumentNotice extends WC_Data_Store_WP implements WC_Object_Data_Store_Interface {

	/**
	 * Data stored in meta keys, but not considered "meta" for an order.
	 *
	 * @since 3.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array();

	protected $core_props = array(
		'document_id',
		'text',
		'date_created',
		'type',
	);

	/**
	 * Meta type. This should match up with
	 * the types available at https://developer.wordpress.org/reference/functions/add_metadata/.
	 * WP defines 'post', 'user', 'comment', and 'term'.
	 *
	 * @var string
	 */
	protected $meta_type = 'storeabill_document_notice';

	/**
	 * Create a new document item in the database.
	 *
	 * @param \Vendidero\StoreaBill\Document\Notice $notice Document notice object.
	 *
	 *@since 3.0.0
	 */
	public function create( &$notice ) {
		global $wpdb;

		$notice->set_date_created( time() );

		$wpdb->insert(
			$wpdb->storeabill_document_notices, array(
				'document_id'                      => $notice->get_document_id(),
				'document_notice_text'             => $this->get_text( $notice),
				'document_notice_type'             => $this->get_type( $notice ),
				'document_notice_date_created'     => gmdate( 'Y-m-d H:i:s', $notice->get_date_created( 'edit' )->getOffsetTimestamp() ),
				'document_notice_date_created_gmt' => gmdate( 'Y-m-d H:i:s', $notice->get_date_created( 'edit' )->getTimestamp() ),
			)
		);

		$notice->set_id( $wpdb->insert_id );
		$this->save_notice_data( $notice );
		$notice->save_meta_data();
		$notice->apply_changes();
		$this->clear_cache( $notice );

		/**
		 * Action that indicates that a new document notice has been created in the DB.
		 *
		 * @param integer                                         $document_notice_id The document notice id.
		 * @param \Vendidero\StoreaBill\Document\Notice $notice The document notice object.
		 * @param integer                                         $document_id The document id.
		 *
		 * @since 1.0.0
		 * @package Vendidero/StoreaBill
		 */
		do_action( "storeabill_new_document_notice", $notice->get_id(), $notice, $notice->get_document_id() );
	}

	/**
	 * Figure out the notice text.
	 *
	 * @param \Vendidero\StoreaBill\Document\Notice $notice Document notice object
	 */
	protected function get_text( $notice ) {
		$text = wp_filter_post_kses( $notice->get_text() );

		return $text;
	}

	/**
	 * Figure out the notice type.
	 *
	 * @param \Vendidero\StoreaBill\Document\Notice $notice Document notice object
	 */
	protected function get_type( $notice ) {
		$type         = $notice->get_type( 'edit' );
		$default_type = apply_filters( 'storeabill_default_document_notice_type', 'info' );

		if ( empty( $type ) || ! in_array( $type, array_keys( sab_get_document_notice_types() ) ) ) {
			$type = $default_type;
		}

		return $type;
	}

	/**
	 * Update a document notice in the database.
	 *
	 * @param \Vendidero\StoreaBill\Document\Notice $notice Document notice object.
	 *
	 *@since 3.0.0
	 */
	public function update( &$notice ) {
		global $wpdb;

		$core_props    = $this->core_props;
		$changed_props = array_keys( $notice->get_changes() );
		$notice_data   = array();

		foreach ( $changed_props as $prop ) {

			if ( ! in_array( $prop, $core_props, true ) ) {
				continue;
			}

			switch( $prop ) {
				case "document_id":
					$notice_data['document_id'] = absint( $notice->get_document_id() );
					break;
				case "text":
					$notice_data[ 'document_notice_' . $prop ] = $this->get_text( $notice );
					break;
				case "type":
					$notice_data[ 'document_notice_' . $prop ] = $this->get_type( $notice );
					break;
				case "date_created":
					if ( is_callable( array( $notice, 'get_' . $prop ) ) ) {
						$notice_data[ 'document_notice_' . $prop ]          = gmdate( 'Y-m-d H:i:s', $notice->{'get_' . $prop}( 'edit' )->getOffsetTimestamp() );
						$notice_data[ 'document_notice_' . $prop . '_gmt' ] = gmdate( 'Y-m-d H:i:s', $notice->{'get_' . $prop}( 'edit' )->getTimestamp() );
					}
					break;
				default:
					if ( is_callable( array( $notice, 'get_' . $prop ) ) ) {
						$notice_data[ 'document_notice_' . $prop ] = $notice->{'get_' . $prop}( 'edit' );
					}
					break;
			}
		}

		if ( ! empty( $notice_data ) ) {
			$wpdb->update(
				$wpdb->storeabill_document_notices,
				$notice_data,
				array( 'document_notice_id' => $notice->get_id() )
			);
		}

		$this->save_notice_data( $notice );
		$notice->save_meta_data();
		$notice->apply_changes();
		$this->clear_cache( $notice );

		/**
		 * Action that indicates that a document notice has been updated in the DB.
		 *
		 * @param integer                                         $document_notice_id The document notice id.
		 * @param \Vendidero\StoreaBill\Document\Notice $notice The document notice object.
		 * @param integer                                         $document_id The document id.
		 *
		 * @since 1.0.0
		 * @package Vendidero/StoreaBill
		 */
		do_action( "storeabill_document_notice_updated", $notice->get_id(), $notice, $notice->get_document_id() );
	}

	/**
	 * Remove a document notice from the database.
	 *
	 * @param \Vendidero\StoreaBill\Document\Notice $notice Document notice object.
	 * @param array                                           $args Array of args to pass to the delete method.
	 *
	 *@since 1.0.0
	 */
	public function delete( &$notice, $args = array() ) {
		if ( $notice->get_id() ) {
			global $wpdb;

			/**
			 * Action that fires before deleting a document notice from the DB.
			 *
			 * @param integer $document_notice_id The document notice id.
			 *
			 * @since 1.0.0
			 * @package Vendidero/StoreaBill
			 */
			do_action( 'storeabill_before_delete_document_notice', $notice->get_id() );

			$wpdb->delete( $wpdb->storeabill_document_notices, array( 'document_notice_id' => $notice->get_id() ) );
			$wpdb->delete( $wpdb->storeabill_document_noticemeta, array( 'storeabill_document_notice_id' => $notice->get_id() ) );

			/**
			 * Action that indicates that a document notice has been deleted from the DB.
			 *
			 * @param integer                                         $document_notice_id The document notice id.
			 * @param \Vendidero\StoreaBill\Document\Notice $notice The document notice object.
			 *
			 * @since 1.0.0
			 * @package Vendidero/StoreaBill
			 */
			do_action( 'storeabill_delete_document_notice', $notice->get_id(), $notice );
			$this->clear_cache( $notice );
		}
	}

	/**
	 * Read a document notice from the database.
	 *
	 * @param \Vendidero\StoreaBill\Document\Notice $notice Document notice object.
	 *
	 * @throws Exception If invalid document notice.
	 * @since 1.0.0
	 */
	public function read( &$notice ) {
		global $wpdb;

		$notice->set_defaults();

		// Get from cache if available.
		$data = wp_cache_get( 'notice-' . $notice->get_id(), 'document-notices' );

		if ( false === $data ) {
			$data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->storeabill_document_notices} WHERE document_notice_id = %d LIMIT 1;", $notice->get_id() ) );
			wp_cache_set( 'notice-' . $notice->get_id(), $data, 'document-notices' );
		}

		if ( ! $data ) {
			throw new Exception( _x( 'Invalid document notice.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}

		$notice->set_props(
			array(
				'document_id'       => $data->document_id,
				'text'              => wp_unslash( $data->document_notice_text ),
				'type'              => $data->document_notice_type,
				'date_created'      => '0000-00-00 00:00:00' !== $data->document_notice_date_created_gmt ? wc_string_to_timestamp( $data->document_notice_date_created_gmt ) : null,
			)
		);

		$this->read_notice_data( $notice );
		$notice->read_meta_data();
		$notice->set_object_read( true );
	}

	/**
	 * Read extra data associated with the document notice.
	 *
	 * @param \Vendidero\StoreaBill\Document\Notice $notice Document notice object.
	 *
	 * @since 3.0.0
	 */
	protected function read_notice_data( &$notice ) {
		$props = array();

		foreach( $this->internal_meta_keys as $meta_key ) {
			$props[ substr( $meta_key, 1 ) ] = get_metadata( 'storeabill_document_notice', $notice->get_id(), $meta_key, true );
		}

		$notice->set_props( $props );
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
			$updated = delete_metadata( 'storeabill_document_notice', $object->get_id(), $meta_key );
		} else {
			$updated = update_metadata( 'storeabill_document_notice', $object->get_id(), $meta_key, $meta_value );
		}

		return (bool) $updated;
	}

	/**
	 * Saves a notice's data to the database / meta.
	 * Ran after both create and update, so $notice->get_id() will be set.
	 *
	 * @param \Vendidero\StoreaBill\Document\Notice $notice Document notice object.
	 *
	 * @since 1.0.0
	 */
	public function save_notice_data( &$notice ) {
		$updated_props     = array();
		$meta_key_to_props = array();

		foreach( $this->internal_meta_keys as $meta_key ) {

			$prop_name = substr( $meta_key, 1 );

			if ( in_array( $prop_name, $this->core_props ) ) {
				continue;
			}

			$meta_key_to_props[ $meta_key ] = $prop_name;
		}

		$props_to_update = $this->get_props_to_update( $notice, $meta_key_to_props, 'storeabill_document_notice' );

		foreach ( $props_to_update as $meta_key => $prop ) {

			$getter = "get_$prop";

			if ( ! is_callable( array( $notice, $getter ) ) ) {
				continue;
			}

			$value = $notice->{"get_$prop"}( 'edit' );
			$value = is_string( $value ) ? wp_slash( $value ) : $value;

			$updated = $this->update_or_delete_meta( $notice, $meta_key, $value );

			if ( $updated ) {
				$updated_props[] = $prop;
			}
		}

		/**
		 * Action that fires after updating a document notice's properties.
		 *
		 * @param \Vendidero\StoreaBill\Document\Notice $notice The document notice object.
		 * @param array                                            $changed_props The updated properties.
		 *
		 * @since 1.0.0
		 * @package Vendidero/StoreaBill
		 */
		do_action( 'storeabill_document_notice_object_updated_props', $notice, $updated_props );
	}

	/**
	 * Clear meta cache.
	 *
	 * @param \Vendidero\StoreaBill\Document\Notice $notice Document notice object.
	 */
	public function clear_cache( &$notice ) {
		wp_cache_delete( 'notice-' . $notice->get_id(), 'document-notices' );
		wp_cache_delete( 'document-notices-' . $notice->get_document_id(), 'documents' );
		wp_cache_delete( $notice->get_id(), $this->meta_type . '_meta' );
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
		$table           = $wpdb->storeabill_document_noticemeta;
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
