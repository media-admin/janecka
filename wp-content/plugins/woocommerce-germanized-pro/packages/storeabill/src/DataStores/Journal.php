<?php

namespace Vendidero\StoreaBill\DataStores;

use Vendidero\StoreaBill\Utilities\CacheHelper;
use WC_Data_Store_WP;
use WC_Object_Data_Store_Interface;
use Exception;
use WC_Data;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Document data store.
 *
 * @version 1.0.0
 */
class Journal extends WC_Data_Store_WP implements WC_Object_Data_Store_Interface {

	/**
	 * Data stored in meta keys, but not considered "meta" for a document.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array();

	protected $core_props = array(
		'name',
		'type',
		'number_format',
		'is_archived',
		'number_min_size',
		'date_last_reset',
		'reset_interval'
	);

	/*
	|--------------------------------------------------------------------------
	| CRUD Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Method to create a new journal in the database.
	 *
	 * @param \Vendidero\StoreaBill\Document\Journal $journal Journal object.
	 */
	public function create( &$journal ) {
		global $wpdb;

		$journal->set_date_last_reset( time() );

		$data = array(
			'journal_name'                => $journal->get_name( 'edit' ),
			'journal_number_format'       => $journal->get_number_format( 'edit' ),
			'journal_number_min_size'     => $journal->get_number_min_size( 'edit' ),
			'journal_type'                => $journal->get_type( 'edit' ),
			'journal_is_archived'         => sab_bool_to_string( $journal->get_is_archived( 'edit' ) ),
			'journal_last_number'         => 0,
			'journal_date_last_reset'     => gmdate( 'Y-m-d H:i:s', $journal->get_date_last_reset( 'edit' )->getOffsetTimestamp() ),
			'journal_date_last_reset_gmt' => gmdate( 'Y-m-d H:i:s', $journal->get_date_last_reset( 'edit' )->getTimestamp() ),
			'journal_reset_interval'      => $journal->get_reset_interval( 'edit' )
		);

		$wpdb->insert(
			$wpdb->storeabill_journals,
			$data
		);

		$journal_id = $wpdb->insert_id;

		if ( $journal_id ) {
			$journal->set_id( $journal_id );
			$journal->apply_changes();

			$this->clear_caches( $journal );

			/**
			 * Action that indicates that a new journal has been created in the DB.
			 *
			 * @param integer                                  $journal_id The journal id.
			 * @param \Vendidero\StoreaBill\Document\Journal $journal The journal instance.
			 *
			 * @since 1.0.0
			 * @package Vendidero/Germanized/Shipments
			 */
			do_action( "storeabill_new_journal", $journal_id, $journal );
		}
	}

	/**
	 * Method to update a journal in the database.
	 *
	 * @param \Vendidero\StoreaBill\Document\Journal $journal Journal object.
	 */
	public function update( &$journal ) {
		global $wpdb;

		$updated_props = array();
		$core_props    = $this->core_props;
		$changed_props = array_keys( $journal->get_changes() );
		$journal_data  = array();

		if ( in_array( 'reset_interval', $changed_props ) ) {
			$journal->set_date_last_reset( time() );
			$changed_props[] = 'date_last_reset';
		}

		foreach ( $changed_props as $prop ) {

			if ( ! in_array( $prop, $core_props, true ) ) {
				continue;
			}

			switch( $prop ) {
				case "is_archived":
					$journal_data[ 'journal_' . $prop ] = sab_bool_to_string( $journal->get_is_archived() );
					break;
				case "date_last_reset":
					if ( is_callable( array( $journal, 'get_' . $prop ) ) ) {
						$journal_data[ 'journal_' . $prop ]          = $journal->{'get_' . $prop}( 'edit' ) ? gmdate( 'Y-m-d H:i:s', $journal->{'get_' . $prop}( 'edit' )->getOffsetTimestamp() ) : '0000-00-00 00:00:00';
						$journal_data[ 'journal_' . $prop . '_gmt' ] = $journal->{'get_' . $prop}( 'edit' ) ? gmdate( 'Y-m-d H:i:s', $journal->{'get_' . $prop}( 'edit' )->getTimestamp() ) : '0000-00-00 00:00:00';
					}
					break;
				default:
					if ( is_callable( array( $journal, 'get_' . $prop ) ) ) {
						$journal_data[ 'journal_' . $prop ] = $journal->{'get_' . $prop}( 'edit' );
					}
					break;
			}
		}

		if ( ! empty( $journal_data ) ) {
			$wpdb->update(
				$wpdb->storeabill_journals,
				$journal_data,
				array( 'journal_id' => $journal->get_id() )
			);
		}

		$journal->apply_changes();
		$this->clear_caches( $journal );

		/**
		 * Action that indicates that a journal has been updated in the DB.
		 *
		 * @param integer                                  $journal_id The journal id.
		 * @param \Vendidero\StoreaBill\Document\Journal $journal The journal instance.
		 *
		 * @since 1.0.0
		 * @package Vendidero/StoreaBill
		 */
		do_action( "storeabill_journal_updated", $journal->get_id(), $journal );
	}

	/**
	 * Remove a journal from the database.
	 *
	 * @param \Vendidero\StoreaBill\Document\Journal $journal Journal object.
	 * @param bool                                     $force_delete Whether to force deletion or not.
	 *
	 * @since 1.0.0
	 */
	public function delete( &$journal, $force_delete = false ) {
		global $wpdb;

		if ( ! $force_delete ) {
			$journal->set_is_archived( true );

			/**
			 * Action that indicates that a journal has been archived.
			 *
			 * @param integer                                  $journal_id The journal id.
			 * @param \Vendidero\StoreaBill\Document\Journal $journal The journal instance.
			 *
			 * @since 1.0.0
			 * @package Vendidero/StoreaBill
			 */
			do_action( "storeabill_journal_archived", $journal->get_id(), $journal );
		} else {
			$wpdb->delete( $wpdb->storeabill_journals, array( 'journal_id' => $journal->get_id() ), array( '%d' ) );
			$this->clear_caches( $journal );

			/**
			 * Action that indicates that a journal has been deleted from the DB.
			 *
			 * @param integer                                  $journal_id The journal id.
			 * @param \Vendidero\StoreaBill\Document\Journal $journal The journal instance.
			 *
			 * @since 1.0.0
			 * @package Vendidero/StoreaBill
			 */
			do_action( "storeabill_journal_deleted", $journal->get_id(), $journal );
		}
	}

	/**
	 * Read a journal from the database.
	 *
	 * @param \Vendidero\StoreaBill\Document\Journal $journal Journal object.
	 *
	 * @throws Exception Throw exception if invalid journal.
	 *
	 * @since 1.0.0
	 */
	public function read( &$journal ) {
		global $wpdb;

		$data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->storeabill_journals} WHERE journal_id = %d LIMIT 1",
				$journal->get_id()
			)
		);

		if ( $data ) {
			$journal->set_props(
				array(
					'name'            => $data->journal_name,
					'is_archived'     => $data->journal_is_archived,
					'number_format'   => $data->journal_number_format,
					'number_min_size' => $data->journal_number_min_size,
					'type'            => $data->journal_type,
					'date_last_reset' => '0000-00-00 00:00:00' !== $data->journal_date_last_reset_gmt ? wc_string_to_timestamp( $data->journal_date_last_reset_gmt ) : null,
					'reset_interval'  => $data->journal_reset_interval
				)
			);

			$journal->set_object_read( true );

			/**
			 * Action that indicates that a journal has been loaded from DB.
			 *
			 * @param \Vendidero\StoreaBill\Document\Journal $journal The journal object.
			 *
			 * @since 1.0.0
			 * @package Vendidero/StoreaBill
			 */
			do_action( "storeabill_journal_loaded", $journal );
		} else {
			throw new Exception( _x( 'Invalid journal.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}
	}

	/**
	 * Clear any caches.
	 *
	 * @param \Vendidero\StoreaBill\Document\Journal $journal Journal object.
	 * @since 1.0.0
	 */
	protected function clear_caches( &$journal ) {

	}

	/*
	|--------------------------------------------------------------------------
	| Additional Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * @param \Vendidero\StoreaBill\Document\Journal $journal
	 *
	 * @return int|WP_Error
	 */
	public function next_number( $journal ) {
		$next = 0;

		try {

			if ( ! $journal->get_id() ) {
				throw new Exception( _x( 'Journal has to be saved in DB before updating numbers.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
			}

			CacheHelper::prevent_caching();

			global $wpdb;

			sab_transaction_query( 'start' );

			if ( $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}storeabill_journals SET journal_last_number=last_insert_id(journal_last_number+1) WHERE journal_id = %d", $journal->get_id() ) ) === false ) {
				throw new Exception( _x( 'Error updating journal sequential number.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
			}

			$next = $wpdb->get_var( 'SELECT LAST_INSERT_ID()' );

			sab_transaction_query( 'commit' );

		} catch( Exception $e ) {
			sab_transaction_query( 'rollback' );

			return new WP_Error( 'numbering-error', $e->getMessage() );
		}

		return absint( $next );
	}

	/**
	 * @param \Vendidero\StoreaBill\Document\Journal $journal
	 * @param integer $number
	 *
	 * @return bool|WP_Error
	 */
	public function set_last_number( $journal, $number ) {
		try {
			if ( ! $journal->get_id() ) {
				throw new Exception( _x( 'Journal has to be saved in DB before updating numbers.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
			}

			CacheHelper::prevent_caching();

			global $wpdb;

			sab_transaction_query( 'start' );

			if ( $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}storeabill_journals SET journal_last_number=%d WHERE journal_id = %d", $number, $journal->get_id() ) ) === false ) {
				throw new Exception( _x( 'Error updating journal sequential number.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
			}

			sab_transaction_query( 'commit' );

		} catch( Exception $e ) {
			sab_transaction_query( 'rollback' );

			return new WP_Error( 'numbering-error', $e->getMessage() );
		}

		return true;
	}

	/**
	 * @param \Vendidero\StoreaBill\Document\Journal $journal
	 *
	 * @return int
	 * @throws Exception
	 */
	public function get_last_number( $journal ) {
		global $wpdb;

		CacheHelper::prevent_caching();

		$data = $wpdb->get_var( $wpdb->prepare( "SELECT journal_last_number FROM {$wpdb->prefix}storeabill_journals WHERE journal_id = %d LIMIT 1;", $journal->get_id() ) );

		if ( is_null( $data ) ) {
			throw new Exception( _x( 'Invalid journal.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}

		return absint( $data );
	}

	public function get_type( $journal_id ) {
		global $wpdb;

		$type = $wpdb->get_var( $wpdb->prepare( "SELECT journal_type FROM {$wpdb->prefix}storeabill_journals WHERE journal_id = %d LIMIT 1;", $journal_id ) );

		return $type;
	}

	public function get_id( $journal_type ) {
		global $wpdb;

		$id = $wpdb->get_var( $wpdb->prepare( "SELECT journal_id FROM {$wpdb->prefix}storeabill_journals WHERE journal_type = %s ORDER BY journal_id ASC LIMIT 1;", $journal_type ) );

		return $id;
	}
}
