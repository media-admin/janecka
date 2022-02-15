<?php
/**
 * StoreaBill Journal Functions
 *
 * Journal related functions available on both the front-end and admin.
 *
 * @version 1.0.0
 */

use Vendidero\StoreaBill\Document\Journal;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param $journal_data
 *
 * @return bool|Journal
 * @throws Exception
 */
function sab_get_journal( $journal_data ) {
	if ( ! is_numeric( $journal_data ) ) {
		$journal_id = sab_load_data_store( 'journal' )->get_id( $journal_data );
	}

	/**
	 * The journal for a valid document type seems to not exist yet. Let's lazily create it.
	 */
	if ( ! $journal_id && sab_get_document_type( $journal_data ) ) {
		$journal_id = sab_create_journal( $journal_data );
	}

	if ( ! $journal_id ) {
		return false;
	}

	try {
		return new Journal( $journal_id );
	} catch ( Exception $e ) {
		wc_caught_exception( $e, __FUNCTION__, array( $journal_id ) );
		return false;
	}
}

function sab_create_journal( $document_type, $args = array() ) {
	$args = wp_parse_args( $args, array(
		'reset_interval'  => '',
		'number_format'   => '{number}',
		'number_min_size' => 0,
		'last_number'     => 0,
	) );

	if ( ! $document_type_object = sab_get_document_type( $document_type ) ) {
		return false;
	}

	$journal_id = sab_load_data_store( 'journal' )->get_id( $document_type );

	/**
	 * Do not create journals twice.
	 */
	if ( ! empty( $journal_id ) ) {
		return $journal_id;
	} else {
		$journal = new Journal();
		$journal->set_name( sab_get_document_type_label( $document_type, 'plural' ) );
		$journal->set_type( $document_type );
		$journal->set_props( $args );

		return $journal->save();
	}
}

function sab_get_journal_reset_intervals() {
	$intervals = array(
		'yearly'  => _x( 'Yearly', 'storeabill-core', 'woocommerce-germanized-pro' ),
		'monthly' => _x( 'Monthly', 'storeabill-core', 'woocommerce-germanized-pro' ),
		'weekly'  => _x( 'Weekly', 'storeabill-core', 'woocommerce-germanized-pro' ),
		'daily'   => _x( 'Daily', 'storeabill-core', 'woocommerce-germanized-pro' ),
	);

	return apply_filters( 'storeabill_journal_reset_intervals', $intervals );
}