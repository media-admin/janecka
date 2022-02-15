<?php

namespace Vendidero\StoreaBill\DataStores;

use Vendidero\StoreaBill\Package;
use Vendidero\StoreaBill\Utilities\CacheHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Invoice data store.
 *
 * @version 1.0.0
 */
class Invoice extends Document {

	protected $internal_date_props_to_keys = array(
		'date_created'        => 'date_created',
		'date_modified'       => 'date_modified',
		'date_sent'           => 'date_sent',
		'date_custom'         => 'date_custom',
		'date_custom_extra'   => 'date_custom_extra',
		'date_of_service'     => '_date_of_service',
		'date_of_service_end' => '_date_of_service_end'
	);

	/**
	 * Data stored in meta keys, but not considered "meta" for an invoice.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_address',
		'_shipping_address',
		'_created_via',
		'_version',
		'_reference_number',
		'_currency',
		'_prices_include_tax',
		'_is_reverse_charge',
		'_is_oss',
		'_vat_id',
		'_is_taxable',
		'_round_tax_at_subtotal',
		'_tax_display_mode',
		'_total',
		'_subtotal',
		'_total_paid',
		'_product_total',
		'_shipping_total',
		'_fee_total',
		'_product_subtotal',
		'_shipping_subtotal',
		'_fee_subtotal',
		'_discount_total',
		'_total_tax',
		'_product_tax',
		'_shipping_tax',
		'_fee_tax',
		'_subtotal_tax',
		'_product_subtotal_tax',
		'_shipping_subtotal_tax',
		'_fee_subtotal_tax',
		'_voucher_total',
		'_voucher_tax',
		'_discount_tax',
		'_payment_status',
		'_external_sync_handlers',
		'_payment_method_title',
		'_payment_method_name',
		'_payment_transaction_id',
		'_discount_notice',
		'_date_of_service',
		'_date_of_service_gmt',
		'_date_of_service_end',
		'_date_of_service_end_gmt'
	);

	/**
	 * @param \Vendidero\StoreaBill\Invoice\Invoice $document
	 */
	public function create( &$document ) {
		$round_at_subtotal = $document->get_round_tax_at_subtotal( 'edit' );

		if ( is_null( $round_at_subtotal ) ) {
			$document->set_round_tax_at_subtotal( $document->prices_include_tax() ? true : false );
		}

		parent::create( $document );
	}

	protected function format_update_value( $document, $prop ) {
		$value = parent::format_update_value( $document, $prop );

		switch( $prop ) {
			case "prices_include_tax":
			case "round_tax_at_subtotal":
			case "is_reverse_charge":
			case "is_oss":
			case "is_taxable":
				$value = sab_bool_to_string( $value );
				break;
			case "payment_status":
				$value = $this->get_payment_status( $document );
				break;
		}

		return $value;
	}

	/**
	 * Make sure to skip date_paid and date_due meta (which are stored as core extra date data).
	 *
	 * @param mixed[] $props
	 * @param \Vendidero\StoreaBill\Document\Document $document
	 */
	protected function filter_props_to_update( $props, &$document ) {
		if ( array_key_exists( '_date_paid', $props ) ) {
			unset( $props['_date_paid'] );
		}

		if ( array_key_exists( '_date_due', $props ) ) {
			unset( $props['_date_due'] );
		}

		if ( array_key_exists( '_date_of_service_gmt', $props ) ) {
			unset( $props['_date_of_service_gmt'] );
		}

		if ( array_key_exists( '_date_of_service_end_gmt', $props ) ) {
			unset( $props['_date_of_service_end_gmt'] );
		}

		return $props;
	}

	/**
	 * Get the payment status to save to the object.
	 *
	 * @since 3.6.0
	 * @param \Vendidero\StoreaBill\Invoice\Invoice $invoice Invoice object.
	 * @return string
	 */
	protected function get_payment_status( $invoice ) {
		$status = $invoice->get_payment_status( 'edit' );

		if ( ! $status ) {
			/** This filter is documented in src/Invoice.php */
			$status = apply_filters( "storeabill_{$invoice->get_type()}_get_default_payment_status", 'pending' );
		}

		if ( ! in_array( $status, array_keys( sab_get_invoice_payment_statuses() ) ) ) {
			$status = 'pending';
		}

		return $status;
	}

	public function get_query_args( $query_vars ) {
		// Map query vars to ones that our document implementation knows.
		$key_mapping = array(
			'date_paid'    => 'date_custom',
			'date_due'     => 'date_custom_extra',
			'order_id'     => 'reference_id',
			'order_number' => 'reference_number'
		);

		foreach ( $key_mapping as $query_key => $db_key ) {

			if ( isset( $query_vars[ $query_key ] ) ) {
				$query_vars[ $db_key ] = $query_vars[ $query_key ];
				unset( $query_vars[ $query_key ] );
			}

			/**
			 * Support orderby clause
			 */
			if ( isset( $query_vars['orderby'] ) && ! empty( $query_vars['orderby'] ) ) {
				if ( is_array( $query_vars['orderby'] ) ) {
					if ( in_array( $query_key, $query_vars['orderby'] ) ) {
						$query_vars['orderby'] = array_replace ( $query_vars['orderby'], array_fill_keys(
							array_keys( $query_vars['orderby'], $query_key ),
							$db_key
						) );
					}
				} else {
					$query_vars['orderby'] = str_replace( $query_key, $db_key, $query_vars['orderby'] );
				}
			}

			/**
			 * Support searching columns
			 */
			if ( isset( $query_vars['search_columns'] ) && ! empty( $query_vars['search_columns'] ) ) {
				if ( in_array( $query_key, $query_vars['search_columns'] ) ) {
					$query_vars['search_columns'] = array_replace ( $query_vars['search_columns'], array_fill_keys(
						array_keys( $query_vars['search_columns'], $query_key ),
						$db_key
					) );
				}
			}
		}

		if ( isset( $query_vars['payment_status'] ) ) {
			$query_vars['payment_status'] = (array) $query_vars['payment_status'];
			$query_vars['payment_status'] = array_map( 'sanitize_key', $query_vars['payment_status'] );

			if ( in_array( 'all', $query_vars['payment_status'] ) || in_array( 'any', $query_vars['payment_status'] ) ) {
				unset( $query_vars['payment_status'] );
			}
		}

		return parent::get_query_args( $query_vars );
	}

	/**
	 * Clear any caches.
	 *
	 * @param \Vendidero\StoreaBill\Document\Document $document Document object.
	 * @since 1.0.0
	 */
	protected function clear_caches( &$document ) {
		parent::clear_caches( $document );

		CacheHelper::invalidate_cache_group( 'invoices' );
	}

	public function get_payment_status_count( $status, $type = '' ) {
		global $wpdb;

		$type = ( ! empty( $type ) && 'simple' === $type ? 'invoice' : $type );

		if ( empty( $type ) ) {
			$query = $wpdb->prepare( "SELECT COUNT( * ) FROM {$wpdb->storeabill_documents} AS documents INNER JOIN {$wpdb->storeabill_documentmeta} AS meta ON ( documents.document_id = meta.storeabill_document_id AND meta.meta_key = '_payment_status' ) WHERE meta.meta_value = %s", $status );
		} else {
			$query = $wpdb->prepare( "SELECT COUNT( * ) FROM {$wpdb->storeabill_documents} AS documents INNER JOIN {$wpdb->storeabill_documentmeta} AS meta ON ( documents.document_id = meta.storeabill_document_id AND meta.meta_key = '_payment_status' ) WHERE meta.meta_value = %s AND documents.document_type = %s", $status, $type );
		}

		return absint( $wpdb->get_var( $query ) );
	}
}