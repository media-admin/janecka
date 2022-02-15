<?php

namespace Vendidero\StoreaBill;

use Vendidero\StoreaBill\Invoice\Invoice;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class Updater {

	/**
	 * @param int $offset
	 * @param int $limit
	 *
	 * @return Invoice[]
	 */
	public static function get_net_invoices( $offset = 0, $limit = 50 ) {
		global $wpdb;

		$versions = array(
			'0.0.1',
			'1.0.0',
			'1.0.1',
			'1.0.2',
			'1.0.3',
			'1.0.4',
			'1.0.5',
			'1.0.6',
			'1.0.7',
			'1.0.8',
			'1.0.9',
			'1.1.0',
			'1.1.1',
			'1.1.2',
			'1.1.3',
		);

		$query = "SELECT {$wpdb->prefix}storeabill_documents.* FROM {$wpdb->prefix}storeabill_documents 
			LEFT JOIN {$wpdb->prefix}storeabill_documentmeta AS mt1 ON ( {$wpdb->prefix}storeabill_documents.document_id = mt1.storeabill_document_id ) 
			LEFT JOIN {$wpdb->prefix}storeabill_documentmeta AS mt2 ON ( {$wpdb->prefix}storeabill_documents.document_id = mt2.storeabill_document_id ) 
			LEFT JOIN {$wpdb->prefix}storeabill_documentmeta AS mt3 ON ( {$wpdb->prefix}storeabill_documents.document_id = mt3.storeabill_document_id AND mt3.meta_key = '_legacy_version' ) 
			WHERE 1=1 
			AND ( document_type = 'invoice' OR document_type = 'invoice_cancellation' ) 
			AND ( document_status = 'draft' OR document_status = 'closed' OR document_status = 'archived' OR document_status = 'cancelled' ) 
			AND ( mt1.meta_key = '_prices_include_tax' AND mt1.meta_value = 'no' ) 
			AND ( mt2.meta_key = '_version' AND mt2.meta_value IN ( '" . implode( "','", $versions ) . "' ) )
			AND ( mt3.meta_value IS NULL OR mt3.meta_value = '0.0.1-legacy-incomplete' OR mt3.meta_value = '0.0.1-legacy' )  
			ORDER BY document_date_created DESC 
			LIMIT %d, %d
		";

		$results  = $wpdb->get_results( $wpdb->prepare( $query, $offset, $limit ) );
		$invoices = array();

		if ( ! empty( $results ) ) {
			foreach( $results as $result ) {
				if ( $invoice = sab_get_invoice( $result->document_id ) ) {
					$invoices[] = $invoice;
				}
			}
		}

		return $invoices;
	}

	/**
	 * @param $offset
	 * @param $limit
	 */
	public static function update_120_net_invoices( $offset, $limit ) {
		$invoices = self::get_net_invoices( $offset, $limit );

		if ( ! empty( $invoices ) ) {
			foreach( $invoices as $invoice ) {
				$created_via    = $invoice->get_created_via();
				$invoice_type   = $invoice->get_invoice_type();
				$legacy_version = $invoice->get_meta( '_legacy_version' );

				if ( '0.0.1-legacy-incomplete' === $legacy_version || ( '0.0.1-legacy' === $legacy_version && 'cancellation' === $invoice_type ) ) {
					$has_adjusted = false;

					foreach( $invoice->get_items( array( 'product', 'shipping', 'fee' ) ) as $item ) {
						$document_type_total_getter = 'get_' . $item->get_item_type() . '_total';
						$document_type_total        = is_callable( array( $invoice, $document_type_total_getter ) ) ? $invoice->{$document_type_total_getter}() : 0;

						if ( is_a( $invoice, 'Vendidero\StoreaBill\Invoice\Cancellation' ) ) {
							if ( $parent = $invoice->get_parent() ) {
								$document_type_total = is_callable( array( $parent, $document_type_total_getter ) ) ? $parent->{$document_type_total_getter}() : $document_type_total;
							}
						}

						$item_total          = sab_format_decimal( $item->get_line_total() + $item->get_total_tax(), '' );
						$document_type_total = sab_format_decimal( $document_type_total, '' );

						/**
						 * During legacy import of net invoices, some invoice items may wrongly
						 * have a gross total as line total which leads to wrong item totals.
						 *
						 * Cancellations may already have wrong line totals. Adjust them based on it's tax net totals instead.
						 */
						if ( $document_type_total > 0 && $item_total > $document_type_total ) {
							$tax_item = false;
							$taxes    = $item->get_taxes();

							if ( sizeof( $taxes ) === 1 && 'cancellation' === $invoice->get_invoice_type() ) {
								foreach( $item->get_taxes() as $tax ) {
									$tax_item = $tax;
								}
							}

							if ( $tax_item ) {
								Package::log( sprintf( 'Setting line total based on tax net total: %s', $tax_item->get_total_net() ), 'info', 'update' );
								$item->set_line_total( $tax_item->get_total_net() );
							} else {
								$item->set_line_total( $item->get_line_total() - $item->get_total_tax() );
							}

							$item->set_line_subtotal( $item->get_line_total() );
							$item->set_subtotal_tax( $item->get_total_tax() );
							$item->save();

							$has_adjusted = true;

							Package::log( sprintf( 'Updated %1$s (%2$s) %3$s item net line total during update.', $invoice->get_title(), $invoice->get_id(), $item->get_item_type() ), 'info', 'update' );
						}
					}

					if ( $has_adjusted && 'cancellation' === $invoice->get_invoice_type() ) {
						add_filter( "storeabill_{$invoice->get_type()}_is_editable", "__return_true", 100 );
						$invoice->calculate_totals( false );
						$invoice->save();
						remove_filter( "storeabill_{$invoice->get_type()}_is_editable", "__return_true", 100 );
					}
				} elseif ( empty( $created_via ) ) {
					foreach( $invoice->get_items( 'tax' ) as $tax_item ) {
						if ( $parent_item = $invoice->get_item( $tax_item->get_parent_id() ) ) {
							$parent_item_net          = $parent_item->get_total_net();
							$parent_item_subtotal_net = $parent_item->get_subtotal_net();

							/**
							 * Fix wrong tax amount in inner tax items for net invoices.
							 */
							if ( $parent_item_net < $tax_item->get_total_net() ) {
								$tax_item->set_total_net( $tax_item->get_total_net() - $tax_item->get_total_tax() );

								if ( $parent_item_subtotal_net < $tax_item->get_subtotal_net() ) {
									$tax_item->set_subtotal_net( $tax_item->get_subtotal_net() - $tax_item->get_subtotal_tax() );
								}

								$tax_item->save();
								Package::log( sprintf( 'Updated %1$s (%2$s) tax item net total during update.', $invoice->get_title(), $invoice->get_id() ), 'info', 'update' );
							}
						}
					}
				}
			}

			/**
			 * Schedule updating
			 */
			WC()->queue()->schedule_single(
				time() + 1,
				'storeabill_update_120_net_invoices',
				array(
					'offset' => $offset + $limit,
					'limit'  => $limit,
				),
				'storeabill-db-updates'
			);
		}
	}

	public static function update_120_net_tax_item_totals() {
		$invoices = self::get_net_invoices( 0, 1 );

		if ( ! empty( $invoices ) ) {
			/**
			 * Schedule updating
			 */
			WC()->queue()->schedule_single(
				time() + 1,
				'storeabill_update_120_net_invoices',
				array(
					'offset' => 0,
					'limit'  => 50,
				),
				'storeabill-db-updates'
			);
		}
	}

	public static function update_120_db_version() {
		Install::update_db_version( '1.2.0' );
	}

	public static function update_121_default_hidden_columns() {
		$default_hidden = get_user_option( 'managewoocommerce_page_sab-accountingcolumnshidden' );

		if ( is_array( $default_hidden ) ) {
			/**
			 * By default mark new column address as hidden after updating
			 */
			$default_hidden[] = 'address';

			update_user_option( get_current_user_id(), 'managewoocommerce_page_sab-accountingcolumnshidden', $default_hidden );
		}
	}

	public static function update_121_db_version() {
		Install::update_db_version( '1.2.1' );
	}
}