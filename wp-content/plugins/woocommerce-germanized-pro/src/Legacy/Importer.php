<?php

namespace Vendidero\Germanized\Pro\Legacy;

use Vendidero\Germanized\Pro\StoreaBill\PackingSlip;
use Vendidero\Germanized\Pro\StoreaBill\PackingSlips;
use Vendidero\StoreaBill\Document\Document;
use Vendidero\StoreaBill\Tax;
use Vendidero\StoreaBill\TaxRate;
use Vendidero\StoreaBill\WooCommerce\Order;

defined( 'ABSPATH' ) || exit;

class Importer {

	/**
	 * @var null|\WP_Error
	 */
	protected $error = null;

	/**
	 * @var string[]
	 */
	protected $logs = array();

	protected $post = null;

	/**
	 * @var null|Document
	 */
	protected $document = null;

	protected $formatted_number = '';

	protected $reference_id = 0;

	protected $refund_order_id = 0;

	protected $reference = null;

	protected $refund_order = null;

	protected $item_data = array();

	protected $parent = null;

	protected $status_after_save = 'closed';

	protected $items_to_cancel = array();

	protected $invoice_type = '';

	protected $version_after_save = '0.0.1-legacy';

	public function __construct( $post_id ) {
		$this->error = new \WP_Error();
		$this->logs  = array();
		$this->post  = is_numeric( $post_id ) ? get_post( $post_id ) : $post_id;

		if ( ! $this->post || 'invoice' !== $this->post->post_type ) {
			$this->error( sprintf( __( 'Invoice Post %s does not exist or is not of post type invoice.', 'woocommerce-germanized-pro' ), $post_id ) );
		}
	}

	protected function is_importing() {
		if ( get_transient( 'wc-gzdp-invoice-import-' . $this->post->ID ) ) {
			return true;
		}

		return false;
	}

	protected function start() {
		$this->log( sprintf( 'Starting import for %s.', $this->formatted_number ) );

		delete_post_meta( $this->post->ID, '_import_skipped_due_error' );
		delete_post_meta( $this->post->ID, '_is_imported' );

		set_transient( 'wc-gzdp-invoice-import-' . $this->post->ID, $this->post->ID, MINUTE_IN_SECONDS );

		$document_type = $this->get_document_type();

		/**
		 * Remove actions to prevent automatic adjustments
		 */
		add_action( "storeabill_before_{$document_type}_object_save", array( $this, 'remove_all_actions' ), 0, 1 );
	}

	public function remove_all_actions( $document ) {
		$document_type = $document->get_type();

		remove_all_actions( "storeabill_before_{$document_type}_object_save" );
		remove_all_actions( "storeabill_after_{$document_type}_object_save" );

		remove_all_actions( "storeabill_{$document_type}_rendered" );
		remove_all_actions( "storeabill_{$document_type}_payment_status_complete" );
		remove_all_actions( "storeabill_{$document_type}_payment_status_pending" );

		remove_all_actions( 'storeabill_document_before_status_change' );
		remove_all_actions( 'storeabill_document_status_changed' );

		remove_all_actions( "storeabill_{$document_type}_status_draft" );
		remove_all_actions( "storeabill_{$document_type}_status_closed" );
		remove_all_actions( "storeabill_{$document_type}_status_cancelled" );

		remove_all_actions( "storeabill_{$document_type}_status_from_draft_to_closed" );
		remove_all_actions( "storeabill_{$document_type}_status_from_closed_to_cancelled" );
		remove_all_actions( "storeabill_{$document_type}_status_from_draft_to_cancelled" );
	}

	protected function get_document_type() {
		if ( 'simple' === $this->invoice_type ) {
			return 'invoice';
		} elseif( 'cancellation' === $this->invoice_type ) {
			return 'invoice_cancellation';
		} elseif( 'packing_slip' === $this->invoice_type ) {
			return 'packing_slip';
		}

		return 'invoice';
	}

	protected function end() {
		if ( ! $this->has_errors() ) {
			$this->log( sprintf( 'Finished import for %s.', $this->formatted_number ) );
		} else {
			$this->log( sprintf( 'Aborted import for %s due to errors.', $this->formatted_number ) );
		}

		delete_transient( 'wc-gzdp-invoice-import-' . $this->post->ID );

		$document_type = $this->get_document_type();

		remove_action( "storeabill_before_{$document_type}_object_save", array( $this, 'remove_all_actions' ), 0 );
	}

	public function import( $and_children = true ) {

		if ( $this->has_errors() ) {
			return $this->error;
		}

		$this->invoice_type     = get_post_meta( $this->post->ID, '_type', true );
		$this->formatted_number = get_post_meta( $this->post->ID, '_invoice_number_formatted', true );

		if ( self::has_been_imported( $this->post->ID ) && self::document_exists( $this->post->ID )  ) {
			if ( $and_children ) {
				$this->maybe_import_children();
			}

			$this->error( sprintf( __( '%s has already been imported.', 'woocommerce-germanized-pro' ), $this->formatted_number ) );

			return $this->error;
		}

		/**
		 * This invoice is already being processed.
		 */
		if ( $this->is_importing() ) {
			$this->error( sprintf( __( '%s is currently being imported. Please wait a few seconds.', 'woocommerce-germanized-pro' ), $this->formatted_number ) );

			return $this->error;
		}

		$this->start();

		if ( 'simple' === $this->invoice_type ) {
			$this->document     = new \Vendidero\StoreaBill\Invoice\Simple();
			$this->reference_id = get_post_meta( $this->post->ID, '_invoice_order', true );

			$this->import_invoice_simple();
		} elseif( 'cancellation' === $this->invoice_type ) {
			$this->document     = new \Vendidero\StoreaBill\Invoice\Cancellation();
			$invoice_subtype    = get_post_meta( $this->post->ID, '_subtype', true );

			if ( 'refund' === $invoice_subtype ) {
				$this->refund_order_id = get_post_meta( $this->post->ID, '_invoice_order', true );

				if ( $refund = $this->get_refund_order() ) {
					$this->reference_id = $refund->get_parent_id();
				}

				$this->document->set_refund_order_id( $this->refund_order_id );
				$this->document->set_refund_order_number( $this->refund_order_id );
			} else {
				$this->reference_id = get_post_meta( $this->post->ID, '_invoice_order', true );
			}

			$this->reference_id = get_post_meta( $this->post->ID, '_invoice_order', true );

			$this->import_invoice_cancellation();
		} elseif ( 'packing_slip' === $this->invoice_type ) {
			$this->import_packing_slip();
		}

		if ( $this->document && ! $this->has_errors() ) {
			$this->document->set_created_via( 'wc_gzdp_legacy_import' );
			$this->document->set_date_created( strtotime( $this->post->post_date_gmt ) );
			$this->document->update_meta_data( '_imported_post_id', $this->post->ID );

			if ( get_post_meta( $this->post->ID, '_invoice_delivery_date', true ) ) {
				$this->document->set_date_sent( get_post_meta( $this->post->ID, '_invoice_delivery_date', true ) );
			}

			$this->import_pdf();

			if ( ! $this->has_errors() ) {
				$id = $this->document->save();

				if ( $id > 0 ) {
					/**
					 * Update the document status and version at the very end to prevent locking problems.
					 */
					$this->document->set_status( $this->status_after_save );
					$this->document->update_meta_data( '_legacy_version', $this->version_after_save );
					$this->document->save();

					update_post_meta( $this->post->ID, '_is_imported', $id );

					if ( $and_children ) {
						$this->maybe_import_children();
					}

					/**
					 * Maybe update parent status to cancelled after importing a cancellation
					 */
					if ( 'cancellation' === $this->invoice_type ) {
						$this->get_parent()->add_cancellation( $this->document );
						$items_left = $this->get_parent()->get_items_left_to_cancel();

						if ( empty( $items_left ) ) {
							$this->get_parent()->set_payment_status( 'complete' );
							$this->get_parent()->update_status( 'cancelled' );
						}
					}

					$this->log( sprintf( 'Successfully imported %1$s as document %2$s.', $this->formatted_number, $id ) );
				} else {
					$this->error( sprintf( __( 'An error occurred while saving the newly created document for %s' ), $this->formatted_number ) );
				}
			}
		}

		/**
		 * In case an error was found which leads to skipping import for this document
		 * mark the document to be skipped (e.g. PDF attachment not found).
		 */
		if ( $this->has_errors( true ) ) {
			update_post_meta( $this->post->ID, '_import_skipped_due_error', 'yes' );
		}

		$this->end();

		return $this->has_errors() ? $this->error : true;
	}

	protected function maybe_import_children() {
		/**
		 * In case this is a simple invoice - check if cancellations exist and import them too
		 */
		if ( 'simple' === $this->invoice_type ) {
			$cancellations = self::get_legacy_invoices( -1, '', $this->post->ID );

			if ( ! empty( $cancellations ) ) {
				$this->log( sprintf( 'Importing cancellations linked to %s', $this->formatted_number ) );

				foreach( $cancellations as $cancellation ) {
					$importer            = new Importer( $cancellation->ID );
					$cancellation_result = $importer->import();

					foreach( $importer->get_logs() as $log ) {
						$this->log( $log );
					}

					if ( is_wp_error( $cancellation_result ) ) {
						foreach( $cancellation_result->get_error_messages() as $error ) {
							$this->error( $error );
						}
					}
				}
			}
		}
	}

	protected function has_attachment() {
		$attachment_id = get_post_meta( $this->post->ID, '_invoice_attachment', true );

		if ( ! empty( $attachment_id ) && ( $attachment = get_post( $attachment_id ) ) ) {
			WC_germanized_pro()->set_upload_dir_filter();
			$path = get_attached_file( $attachment_id );
			WC_germanized_pro()->unset_upload_dir_filter();

			if ( file_exists( $path ) ) {
				return true;
			}
		}

		return false;
	}

	protected function import_pdf() {
		$attachment_id = get_post_meta( $this->post->ID, '_invoice_attachment', true );

		if ( ! empty( $attachment_id ) && ( $attachment = get_post( $attachment_id ) ) ) {
			WC_germanized_pro()->set_upload_dir_filter();
			$path = get_attached_file( $attachment_id );
			WC_germanized_pro()->unset_upload_dir_filter();

			/**
			 * Copy file to StoreaBill folder
			 */
			if ( file_exists( $path ) ) {
				try {
					$relative_path  = WC_germanized_pro()->get_relative_upload_path( $path );
					$filename       = basename( $path );

					$new_upload_dir  = \Vendidero\StoreaBill\UploadManager::get_upload_dir();
					$new_upload_path = trailingslashit( $new_upload_dir['basedir'] ) . $relative_path;

					if ( ! wp_mkdir_p( dirname( $new_upload_path ) ) ) {
						$this->error( sprintf( __( 'Error creating invoice PDF path %1$s for %2$s.', 'woocommerce-germanized-pro' ), $new_upload_path, $this->formatted_number ) );
					} else {
						if ( file_exists( $new_upload_path ) ) {
							$new_upload_path = str_replace( $filename, 'legacy-' . $filename, $new_upload_path );
							$relative_path   = \Vendidero\StoreaBill\UploadManager::get_relative_upload_dir( $new_upload_path );
						}

						if ( ! @copy( $path, $new_upload_path ) ) {
							$this->error( sprintf( __( 'Error copying %1$s PDF file %2$s to new folder.', 'woocommerce-germanized-pro' ), $this->formatted_number, $new_upload_path ) );
						} else {
							$this->log( sprintf( 'Successfully copied PDF for %s.', $this->formatted_number ) );

							$this->document->set_relative_path( $relative_path );
						}
					}
				} catch ( \Exception $e ) {
					$this->error( sprintf( __( 'Error while copying %1$s PDF: %2$s.', 'woocommerce-germanized-pro' ), $this->formatted_number, $e->getMessage() ) );
				}
			} else {
				/**
				 * Skip import
				 */
				$this->error( sprintf( __( '%s does not have a PDF linked to it - skipping.', 'woocommerce-germanized-pro' ), $this->formatted_number ), true );
			}
		} else {
			/**
			 * Skip import
			 */
			$this->error( sprintf( __( '%s does not have a PDF linked to it - skipping.', 'woocommerce-germanized-pro' ), $this->formatted_number ), true );
		}
	}

	public static function has_been_imported( $post_id ) {
		$id = get_post_meta( $post_id, '_is_imported', true );

		if ( ! empty( $id ) ) {
			return true;
		}

		return false;
	}

	public static function document_exists( $post_id ) {
		$id = get_post_meta( $post_id, '_is_imported', true );

		if ( ! empty( $id ) && ( $document = sab_get_document( $id ) ) ) {
			return true;
		}

		return false;
	}

	public static function delete( $id, $by_document = false ) {
		if ( ! $by_document ) {
			$document_id = get_post_meta( $id, '_is_imported', true );

			if ( ! $document_id || ! ( $document = sab_get_document( $document_id ) ) ) {
				return false;
			}

			return self::delete_imported_document( $document, $id );
		} else {
			if ( $document = sab_get_document( $id ) ) {
				$post_id = $document->get_meta( '_imported_post_id', true );

				if ( empty( $post_id ) || ! get_post( $post_id ) ) {
					return false;
				}

				return self::delete_imported_document( $document, $post_id );
			}
		}

		return false;
	}

	public static function get_legacy_invoices( $limit = 50, $after = '', $parent_id = 0 ) {
		$query = self::get_legacy_invoice_query( $limit, $after, $parent_id );

		return $query->posts;
	}

	/**
	 * @param $post_id
	 *
	 * @return bool|\Vendidero\StoreaBill\Document\Document
	 */
	public static function get_imported_document_by_post_id( $post_id ) {
		if ( $post = get_post( $post_id ) ) {
			$document_id = get_post_meta( $post_id, '_is_imported', true );

			if ( ! empty( $document_id ) && ( $document = sab_get_document( $document_id ) ) ) {
				return $document;
			}
		}

		return false;
	}

	protected static function get_legacy_invoice_query( $limit = 50, $after = '', $parent_id = 0 ) {
		$args = array(
			'posts_per_page'   => $limit,
			'order'            => 'ASC',
			'post_type'        => 'invoice',
			'suppress_filters' => true,
			'post_status'      => array( 'wc-gzdp-pending', 'wc-gzdp-cancelled', 'wc-gzdp-paid' ),
			'meta_query'       => array(
				array(
					'key'     => '_is_imported',
					'compare' => 'NOT EXISTS'
				),
				array(
					'key'     => '_import_skipped_due_error',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_invoice_number',
					'compare' => 'EXISTS'
				),
			)
		);

		if ( ! empty( $parent_id ) ) {
			$args['meta_query'][] = array(
				'key'   => '_invoice_parent_id',
				'value' => $parent_id
			);

			$args['numberposts'] = -1;
		} else {
			/**
			 * By default do not query children (e.g. cancellations)
			 */
			$args['meta_query'][] = array(
				'key'     => '_invoice_parent_id',
				'compare' => 'NOT EXISTS'
			);
		}

		if ( ! empty( $after ) ) {
			$args['date_query'] = array(
				'column'    => 'post_date',
				'after'     => $after,
				'inclusive' => true
			);
		}

		return new \WP_Query( $args );
	}

	public static function get_legacy_invoice_count( $after = '' ) {
		$query = self::get_legacy_invoice_query( -1, $after );

		return $query->post_count;
	}

	public static function has_legacy_invoices( $after = '' ) {
		$query = self::get_legacy_invoice_query( 1, $after );

		return $query->post_count > 0 ? true : false;
	}

	protected static function delete_imported_document( $document, $post_id ) {
		$document_id = $document->get_id();

		if ( $document->delete( true ) ) {
			if ( strpos( $document->get_type(), 'invoice' ) !== false ) {
				$children = sab_get_invoices( array(
					'parent_id' => $document_id,
					'limit'     => -1,
				) );

				foreach( $children as $child ) {
					$child_post_id = $child->get_meta( '_imported_post_id', true );

					if ( ! empty( $child_post_id ) )  {
						delete_post_meta( $child_post_id, '_is_imported' );
					}

					$child->delete( true );
				}
			}

			delete_post_meta( $post_id, '_is_imported' );

			return true;
		}

		return false;
	}

	protected function get_reference() {
		if ( is_null( $this->reference ) || ! $this->reference ) {
			if ( is_a( $this->document, '\Vendidero\StoreaBill\Invoice\Invoice' ) ) {
				$this->reference = wc_get_order( $this->reference_id );
			}
		}

		return $this->reference;
	}

	/**
	 * @return bool|\Vendidero\StoreaBill\Document\Document|\Vendidero\StoreaBill\Invoice\Simple
	 */
	protected function get_parent() {
		if ( is_null( $this->parent ) || ! $this->parent ) {
			$parent_id = get_post_meta( $this->post->ID, '_invoice_parent_id', true );

			if ( ! empty( $parent_id ) ) {
				$this->parent = self::get_imported_document_by_post_id( $parent_id );
			}
		}

		if ( ! $this->parent ) {
			$this->parent = false;
		}

		return $this->parent;
	}

	protected function get_refund_order() {
		if ( is_null( $this->refund_order ) && $this->refund_order_id > 0 ) {
			$this->refund_order = wc_get_order( $this->refund_order_id );
		} else {
			$this->refund_order = false;
		}

		return $this->refund_order;
	}

	protected function has_non_legacy_documents() {
		$existing_documents = array();

		if ( is_a( $this->document, '\Vendidero\StoreaBill\Invoice\Invoice' ) ) {
			$existing_documents = sab_get_invoices( array(
				'type'           => $this->document->get_invoice_type(),
				'limit'          => -1,
				'reference_id'   => $this->reference_id,
				'reference_type' => 'woocommerce'
			) );
		}

		/**
		 * Check whether this order does already include invoices generated by StoreaBill
		 */
		foreach( $existing_documents as $existing_document ) {
			if ( 'wc_gzdp_legacy_import' !== $existing_document->get_created_via() ) {
				return true;
			}
		}

		return false;
	}

	public function has_errors( $skip_import = false ) {
		if ( $skip_import ) {
			foreach( $this->error->get_error_codes() as $code ) {
				if ( 'skip-import-error' === $code ) {
					return true;
					break;
				}
			}

			return false;
		} else {
			return sab_wp_error_has_errors( $this->error );
		}
	}

	public function get_logs() {
		return $this->logs;
	}

	protected function log( $message ) {
		$this->logs[] = $message;
	}

	protected function error( $message, $skip_import = false ) {
		if ( $skip_import ) {
			$this->error->add( 'skip-import-error', $message );
		} else {
			$this->error->add( 'import-error', $message );
		}
	}

	protected function has_item_data() {
		$invoice_item_data  = get_post_meta( $this->post->ID, '_invoice_item_data', true );

		return ! empty( $invoice_item_data ) ? true : false;
	}

	protected function import_invoice_simple() {
		if ( $this->has_non_legacy_documents() ) {
			$this->error( sprintf( __( '%s cannot be imported as other, non-legacy invoices already exist for that order.', 'woocommerce-germanized-pro' ), $this->formatted_number ), true );

			return;
		}

		$invoice_totals     = get_post_meta( $this->post->ID, '_invoice_totals', true );
		$reference          = $this->get_reference();

		$this->document->set_order_id( $this->reference_id );
		$this->document->set_total( $invoice_totals['total'] );
		$this->document->set_total_tax( $invoice_totals['tax'] );
		$this->document->set_prices_include_tax( $reference ? $reference->get_prices_include_tax() : wc_prices_include_tax() );

		if ( $reference ) {
			$address_data          = $reference->get_address( 'billing' );
			$shipping_address_data = $reference->get_address( 'shipping' );
		} else {
			$address_data          = $this->parse_address();
			$shipping_address_data = array();
		}

		$order_total     = 0;
		$order_tax_total = 0;

		if ( $reference ) {
			$order_total     = $reference->get_total() - $reference->get_total_refunded();
			$order_tax_total = $reference->get_total_tax() - $reference->get_total_tax_refunded();
		}

		if ( $this->has_item_data() ) {
			$this->import_invoice_simple_item_data();
		} elseif ( $reference && ( $order_total == $this->document->get_total() && $order_tax_total == $this->document->get_total_tax() ) ) {
			$this->import_invoice_simple_order_sync();
		} else {
			$this->import_invoice_simple_incomplete();
		}

		/**
		 * Override document data after syncing.
		 */
		if ( $reference ) {
			if ( \WC_GZDP_VAT_Helper::instance()->order_has_vat_exempt( $this->reference_id ) && empty( $this->document->get_total_tax() ) ) {
				$vat_id = \WC_GZDP_VAT_Helper::instance()->get_order_vat_id( $this->reference_id );

				$this->document->set_is_reverse_charge( true );
				$this->document->set_vat_id( $vat_id );

				$vat_type = \WC_GZDP_VAT_Helper::instance()->get_vat_address_type_by_order( $this->reference_id );

				if ( 'billing' === $vat_type ) {
					$address_data['vat_id'] = $vat_id;
				} elseif ( 'shipping' === $vat_type ) {
					$shipping_address_data['vat_id'] = $vat_id;
				}
			}

			$this->document->set_customer_id( $reference->get_customer_id() );
		}

		$this->document->set_address( $address_data );
		$this->document->set_shipping_address( $shipping_address_data );
		$this->document->set_order_number( get_post_meta( $this->post->ID, '_invoice_order_number', true ) ? get_post_meta( $this->post->ID, '_invoice_order_number', true ) : $this->reference_id );
		$this->document->set_reference_type( 'woocommerce' );
		$this->document->set_currency( get_post_meta( $this->post->ID, '_invoice_currency', true ) );
		$this->document->set_payment_method_name( get_post_meta( $this->post->ID, '_invoice_payment_method', true ) );
		$this->document->set_payment_method_title( get_post_meta( $this->post->ID, '_invoice_payment_method_title', true ) );
		$this->document->set_number( get_post_meta( $this->post->ID, '_invoice_number', true ) );
		$this->document->set_formatted_number( $this->parse_formatted_number() );
		$this->document->set_date_of_service( strtotime( $this->post->post_date_gmt ) );

		/**
		 * Check document totals again
		 */
		$document_total = wc_format_decimal( abs( $this->document->get_total() ), 2 );
		$post_total     = wc_format_decimal( abs( $invoice_totals['total'] ), 2 );

		if ( $document_total != $post_total ) {
			$this->version_after_save = '0.0.1-legacy-incomplete';
		}

		if ( 'wc-gzdp-cancelled' === $this->post->post_status ) {
			$this->document->set_payment_status( 'complete' );
			$this->status_after_save = 'cancelled';
		} else {
			if ( 'wc-gzdp-paid' === $this->post->post_status ) {
				$this->document->set_payment_status( 'complete' );
			}
		}

		if ( 'complete' === $this->document->get_payment_status() ) {
			if ( $reference && $reference->get_date_paid() ) {
				$this->document->set_date_paid( $reference->get_date_paid() );
			} else {
				$this->document->set_date_paid( strtotime( $this->post->post_date_gmt ) );
			}
		}
	}

	protected function parse_formatted_number() {
		$number = $this->formatted_number;

		$legacy_invoice_types = array(
			get_option( 'woocommerce_gzdp_invoice_cancellation_number_format' ),
			get_option( 'woocommerce_gzdp_invoice_packing_slip_number_format' ),
			get_option( 'woocommerce_gzdp_invoice_number_format' ),
			_x( 'Cancellation', 'invoices', 'woocommerce-germanized-pro' ),
			_x( 'Packing Slip', 'invoices', 'woocommerce-germanized-pro' ),
			_x( 'Invoice', 'invoices', 'woocommerce-germanized-pro' )
		);

		foreach( $legacy_invoice_types as $type ) {
			$number = trim( str_replace( $type, '', $number ) );
		}

		return $number;
	}

	protected function parse_address() {
		$invoice_address      = get_post_meta( $this->post->ID, '_invoice_address', true );
		$invoice_recipient    = get_post_meta( $this->post->ID, '_invoice_recipient', true );
		$title_options        = wc_gzd_get_customer_title_options();
		$address_data         = array(
			'first_name' => $invoice_recipient['firstname'],
			'last_name'  => $invoice_recipient['lastname'],
			'email'      => $invoice_recipient['mail']
		);

		foreach( $title_options as $title_option_num => $title_option ) {
			if ( strpos( $invoice_address, $title_option ) !== false ) {
				$address_data['title'] = $title_option_num;
				/**
				 * Remove title from address
				 */
				$invoice_address = str_replace( $title_option, '', $invoice_address );
				break;
			}
		}

		$invoice_address_data = explode( '<br/>', $invoice_address );

		if ( ! empty( $invoice_address_data ) ) {
			$countries   = WC()->countries->get_countries();
			$country     = WC()->countries->get_base_country();
			$last_part   = $invoice_address_data[ sizeof( $invoice_address_data ) - 1 ];
			$has_country = false;
			$has_company = false;

			if ( in_array( $last_part, $countries ) ) {
				$country     = array_search( $last_part, $countries );
				$has_country = true;
			}

			$address_data['country'] = $country;
			$name_data               = explode( " ", trim( $invoice_address_data[0] ) );
			$street_key              = 1;
			$offset                  = 1;
			$customer_name_key       = 0;
			$min_size_address_2      = $has_country ? 5 : 4;

			// Find customer name
			foreach( $invoice_address_data as $k => $address_data_part ) {
				if ( strpos( $address_data_part, $address_data['last_name'] ) !== false ) {
					$customer_name_key = $k;
					break;
				}
			}

			/**
			 * Has company
			 */
			if ( 1 === $customer_name_key ) {
				$name_data               = explode( " ", trim( $invoice_address_data[1] ) );
				$street_key              = 2;
				$address_data['company'] = trim( $invoice_address_data[0] );
				$has_company             = true;
				$offset                  = 2;
				$min_size_address_2      = $has_country ? 6 : 5;
			}

			/**
			 * Customer name
			 */
			$last_name  = trim( $name_data[ sizeof( $name_data ) - 1 ] );
			$first_name = trim( implode( " ", array_slice( $name_data, 0, sizeof( $name_data ) - 1 ) ) );

			if ( ! empty( $last_name ) ) {
				$address_data['last_name']  = $last_name;
				$address_data['first_name'] = $first_name;
			}

			/**
			 * Address data
			 */
			if ( ! empty( $street_data ) ) {
				$address_data['address_1'] = $invoice_address_data[ $street_key ];
			}

			/**
			 * Seems to have address 2
			 */
			if ( sizeof( $invoice_address_data ) >= $min_size_address_2 ) {
				$address_data['address_2'] = $invoice_address_data[ $street_key + 1 ];

				$offset += 1;
			}

			$remaining_address = array_slice( $invoice_address_data, $offset );

			foreach( $remaining_address as $address_part ) {
				/**
				 * Find postcode with city
				 */
				if ( preg_match("/^(.)*[0-9]{4,}(.)*$/", $address_part ) ) {
					$postcode_data = explode( " ", $address_part );

					if ( ! empty( $postcode_data ) ) {
						foreach( $postcode_data as $k => $postcode_d ) {
							if ( is_numeric( $postcode_d ) ) {
								unset( $postcode_data[ $k ] );
								$address_data['postcode'] = $postcode_d;
								break;
							}
						}

						if ( ! empty( $address_data['postcode'] ) ) {
							$address_data['city'] = implode( " ", $postcode_data );
						}
					}
				}
			}
		}

		return $address_data;
	}

	protected function parse_item_data() {
		$invoice_item_data  = get_post_meta( $this->post->ID, '_invoice_item_data', true );
		$item_data          = array(
			'product'  => array(),
			'shipping' => array(),
			'fee'      => array(),
			'tax'      => array(),
			'rates'    => array(),
		);

		foreach( $invoice_item_data as $item_id => $item ) {
			if ( isset( $item['product_id'] ) ) {
				$item_data['product'][] = $item;
			} elseif ( isset( $item['method_id'] ) ) {
				$item_data['shipping'][] = $item;
			} elseif ( isset( $item['rate_id'] ) ) {
				$item_data['tax'][] = $item;
			} else {
				$item_data['fee'][] = $item;
			}
		}

		foreach( $item_data['tax'] as $tax ) {
			$tax_rate = new TaxRate( array(
				'reference_id' => $tax['rate_id'],
				'is_compound'  => $tax['compound'],
				'percent'      => ! empty( $tax['rate_percent'] ) ? $tax['rate_percent'] : \Vendidero\StoreaBill\Tax::get_rate_percent_value( $tax['rate_id'] ),
			) );

			$item_data['rates'][ $tax['rate_id'] ] = $tax_rate;
		}

		$this->item_data = $item_data;
	}

	protected function get_item_data( $type ) {
		if ( empty( $this->item_data ) ) {
			$this->parse_item_data();
		}

		return array_key_exists( $type, $this->item_data ) ? $this->item_data[ $type ] : array();
	}

	protected function get_tax_rate_by_rate_id( $rate_id ) {
		if ( empty( $this->item_data ) ) {
			$this->parse_item_data();
		}

		return array_key_exists( $rate_id, $this->item_data['rates'] ) ? $this->item_data['rates'][ $rate_id ] : false;
	}

	protected function import_invoice_simple_item_data() {
		$this->log( sprintf( 'Importing %s based on item data.', $this->formatted_number ) );

		/**
		 * Indicate version.
		 */
		$this->version_after_save = '0.0.1-legacy-item-data';

		foreach( $this->get_item_data( 'product' ) as $line_item ) {
			$item = new \Vendidero\StoreaBill\Invoice\ProductItem();

			$total    = $this->document->prices_include_tax() ? ( $line_item['total'] + $line_item['total_tax'] ) : $line_item['total'];
			$subtotal = $total;

			if ( isset( $line_item['subtotal'] ) ) {
				$subtotal = $this->document->prices_include_tax() ? ( $line_item['subtotal'] + $line_item['subtotal_tax'] ) : $line_item['subtotal'];
			}

			$item->set_line_total( $total );
			$item->set_reference_id( $line_item['id'] );
			$item->set_total_tax( $line_item['total_tax'] );
			$item->set_subtotal_tax( isset( $line_item['subtotal_tax'] ) ? $line_item['subtotal_tax'] : $line_item['total_tax'] );
			$item->set_name( $line_item['name'] );
			$item->set_line_subtotal( $subtotal );
			$item->set_quantity( isset( $line_item['quantity'] ) ? $line_item['quantity'] : 1 );
			$item->set_product_id( ! empty( $line_item['variation_id'] ) ? $line_item['variation_id'] : $line_item['product_id'] );

			if ( ! empty( $line_item['taxes']['total'] ) ) {
				foreach( $line_item['taxes']['total'] as $rate_id => $tax_total ) {
					if ( $rate = $this->get_tax_rate_by_rate_id( $rate_id ) ) {
						$item->add_tax_rate( $rate );
					}
				}
			}

			$this->document->add_item( $item );
		}

		foreach( $this->get_item_data( 'shipping' ) as $line_item ) {
			$item = new \Vendidero\StoreaBill\Invoice\ShippingItem();

			$total    = $this->document->prices_include_tax() ? ( $line_item['total'] + $line_item['total_tax'] ) : $line_item['total'];
			$subtotal = $total;

			if ( isset( $line_item['subtotal'] ) ) {
				$subtotal = $this->document->prices_include_tax() ? ( $line_item['subtotal'] + $line_item['subtotal_tax'] ) : $line_item['subtotal'];
			}

			$item->set_line_total( $total );
			$item->set_reference_id( $line_item['id'] );
			$item->set_total_tax( $line_item['total_tax'] );
			$item->set_subtotal_tax( isset( $line_item['subtotal_tax'] ) ? $line_item['subtotal_tax'] : $line_item['total_tax'] );
			$item->set_name( $line_item['name'] );
			$item->set_line_subtotal( $subtotal );
			$item->set_quantity( isset( $line_item['quantity'] ) ? $line_item['quantity'] : 1 );

			if ( ! empty( $line_item['taxes']['total'] ) ) {
				foreach( $line_item['taxes']['total'] as $rate_id => $tax_total ) {
					if ( $rate = $this->get_tax_rate_by_rate_id( $rate_id ) ) {
						$item->add_tax_rate( $rate );
					}
				}
			}

			$this->document->add_item( $item );
		}

		foreach( $this->get_item_data( 'fee' ) as $line_item ) {
			$item = new \Vendidero\StoreaBill\Invoice\FeeItem();

			$total    = $this->document->prices_include_tax() ? ( $line_item['total'] + $line_item['total_tax'] ) : $line_item['total'];
			$subtotal = $total;

			if ( isset( $line_item['subtotal'] ) ) {
				$subtotal = $this->document->prices_include_tax() ? ( $line_item['subtotal'] + $line_item['subtotal_tax'] ) : $line_item['subtotal'];
			}

			$item->set_line_total( $total );
			$item->set_reference_id( $line_item['id'] );
			$item->set_total_tax( $line_item['total_tax'] );
			$item->set_subtotal_tax( isset( $line_item['subtotal_tax'] ) ? $line_item['subtotal_tax'] : $line_item['total_tax'] );
			$item->set_name( $line_item['name'] );
			$item->set_line_subtotal( $subtotal );
			$item->set_quantity( isset( $line_item['quantity'] ) ? $line_item['quantity'] : 1 );

			if ( ! empty( $line_item['taxes']['total'] ) ) {
				foreach( $line_item['taxes']['total'] as $rate_id => $tax_total ) {
					if ( $rate = $this->get_tax_rate_by_rate_id( $rate_id ) ) {
						$item->add_tax_rate( $rate );
					}
				}
			}

			$this->document->add_item( $item );
		}

		if ( ! get_post_meta( $this->post->ID, '_invoice_net_split_taxes' ) ) {
			add_filter( 'storeabill_invoice_calculate_tax_shares_net_based', '__return_false', 100 );
		}

		$this->document->calculate_totals( true );

		if ( ! get_post_meta( $this->post->ID, '_invoice_net_split_taxes' ) ) {
			remove_filter( 'storeabill_invoice_calculate_tax_shares_net_based', '__return_false', 100 );
		}
	}

	protected function import_invoice_simple_order_sync() {
		$this->log( sprintf( 'Importing %s based on order sync.', $this->formatted_number ) );

		/**
		 * Indicate version.
		 */
		$this->version_after_save = '0.0.1-legacy-order-sync';

		/**
		 * Use order data for syncing
		 */
		$sab_order = \Vendidero\StoreaBill\WooCommerce\Helper::get_order( $this->reference_id );

		if ( ! get_post_meta( $this->post->ID, '_invoice_net_split_taxes' ) ) {
			add_filter( 'storeabill_invoice_calculate_tax_shares_net_based', '__return_false', 100 );
		}

		$sab_order->sync( $this->document, array( 'validate_total' => false ) );

		if ( ! get_post_meta( $this->post->ID, '_invoice_net_split_taxes' ) ) {
			remove_filter( 'storeabill_invoice_calculate_tax_shares_net_based', '__return_false', 100 );
		}
	}

	protected function import_invoice_simple_incomplete() {
		$this->log( sprintf( 'Importing %s based on incomplete legacy data.', $this->formatted_number ) );

		/**
		 * Indicate that data might be missing.
		 */
		$this->version_after_save = '0.0.1-legacy-incomplete';

		$invoice_totals     = get_post_meta( $this->post->ID, '_invoice_totals', true );
		$invoice_tax_totals = get_post_meta( $this->post->ID, '_invoice_tax_totals', true );
		$invoice_items      = get_post_meta( $this->post->ID, '_invoice_items', true );

		$shipping_tax = isset( $invoice_totals['shipping_tax'] ) ? abs( $invoice_totals['shipping_tax'] ) : 0;

		$this->document->set_product_total( $invoice_totals['subtotal_gross'] );
		$this->document->set_product_tax( $invoice_totals['subtotal_gross'] - $invoice_totals['subtotal'] );
		$this->document->set_shipping_total( $invoice_totals['shipping'] + $shipping_tax );
		$this->document->set_shipping_tax( $shipping_tax );
		$this->document->set_fee_total( $invoice_totals['fee'] );
		$this->document->set_discount_total( $invoice_totals['discount'] );
		$this->document->set_total_tax( $invoice_totals['tax'] );

		if ( sizeof( $invoice_tax_totals ) === 1 ) {
			if ( $this->document->get_product_total() > 0 ) {
				$item = new \Vendidero\StoreaBill\Invoice\ProductItem();

				if ( $this->document->prices_include_tax() ) {
					$item->set_line_total( $this->document->get_product_total() );
				} else {
					$item->set_line_total( $this->document->get_product_total() - $this->document->get_product_tax() );
				}

				$item->set_total_tax( $this->document->get_product_tax() );
				$item->set_subtotal_tax( $this->document->get_product_tax() );

				$item_name = __( 'Unknown product or service', 'woocommerce-germanized-pro' );

				if ( $this->get_reference() && is_array( $invoice_items ) ) {
					$order_item_names = '';

					foreach( $invoice_items as $invoice_line_item ) {
						if ( is_a( $invoice_line_item, 'WC_Order_Item' ) ) {
							if ( $invoice_line_item->get_id() > 0 ) {
								$order_item_names .= empty( $order_item_names ) ? $invoice_line_item->get_name() : ' | ' . $invoice_line_item->get_name();
							}
						}
					}

					if ( ! empty( $order_item_names ) ) {
						$item_name = $order_item_names;
					}
				}

				$item->set_name( $item_name );
				$this->document->add_item( $item );

				$item->calculate_totals();
			}

			if ( $this->document->get_shipping_total() > 0 ) {
				$item = new \Vendidero\StoreaBill\Invoice\ShippingItem();

				if ( $this->document->prices_include_tax() ) {
					$item->set_line_total( $this->document->get_shipping_total() );
				} else {
					$item->set_line_total( $this->document->get_shipping_total() - $this->document->get_shipping_tax() );
				}

				$item->set_total_tax( $this->document->get_shipping_tax() );
				$item->set_subtotal_tax( $this->document->get_shipping_tax() );

				$item->set_name( __( 'Shipping', 'woocommerce-germanized-pro' ) );
				$this->document->add_item( $item );
			}

			$total_diff = wc_format_decimal( $this->document->get_total() - ( $this->document->get_product_total() + $this->document->get_shipping_total() ), '' );

			if ( $total_diff > 0 ) {
				$tax_diff = wc_format_decimal( $this->document->get_total_tax() - ( $this->document->get_product_tax() + $this->document->get_shipping_tax() ), '' );

				$this->document->set_fee_total( $total_diff );
				$this->document->set_fee_tax( $tax_diff );

				$item = new \Vendidero\StoreaBill\Invoice\FeeItem();

				if ( $this->document->prices_include_tax() ) {
					$item->set_line_total( $this->document->get_fee_total() );
				} else {
					$item->set_line_total( $this->document->get_fee_total() - $this->document->get_fee_tax() );
				}

				$item->set_total_tax( $this->document->get_fee_tax() );
				$item->set_subtotal_tax( $this->document->get_fee_tax() );

				$item->set_name( __( 'Fee', 'woocommerce-germanized-pro' ) );

				$this->document->add_item( $item );
			}
		} else {
			/**
			 * There is no way to figure out how much of the tax total
			 * belongs to which item (or shipping). Instead we fairly distribute
			 * tax totals based on item total share.
			 */
			$this->version_after_save = '0.0.1-legacy-incomplete-placeholder';
		}

		$rates = array();

		if ( ! empty( $invoice_tax_totals ) ) {
			foreach( $invoice_tax_totals as $tax_total ) {
				$rate_id    = $tax_total->rate_id;
				$percentage = Tax::get_rate_percent_value( $rate_id );

				if ( $order = $this->get_reference() ) {
					if ( $sab_order = \Vendidero\StoreaBill\WooCommerce\Helper::get_order( $order ) ) {
						$percentage = $sab_order->get_tax_rate_percent( $rate_id );
					}
				}

				$merge_key = $percentage . "_" . wc_bool_to_string( $tax_total->is_compound );

				if ( ! array_key_exists( $merge_key, $rates ) ) {
					$tax_data = array(
						'percent'      => $percentage,
						'is_compound'  => $tax_total->is_compound,
						'reference_id' => $rate_id,
					);

					$rates[ $merge_key ] = new TaxRate( $tax_data );
				}

				$total_tax = $tax_total->amount;
				$total_net = $this->document->get_total_net();

				/**
				 * Multiple tax rates cause problems as we do not know the tax rate
				 * of each item.
				 */
				if ( sizeof( $invoice_tax_totals ) === 1 ) {
					if ( $this->document->get_product_tax() > 0 ) {
						$tax_item = new \Vendidero\StoreaBill\Invoice\TaxItem();
						$product_tax_total = $this->document->get_product_tax();

						$total_tax -= $product_tax_total;
						$total_net -= $this->document->get_product_net();

						$tax_item->set_total_net( $this->document->get_product_net() );
						$tax_item->set_subtotal_net( $this->document->get_product_net() );
						$tax_item->set_total_tax( $product_tax_total );
						$tax_item->set_subtotal_tax( $product_tax_total );

						$tax_item->set_tax_rate( $rates[ $merge_key ] );
						$tax_item->set_tax_type( 'product' );

						$items = $this->document->get_items( 'product' );

						if ( ! empty( $items ) ) {
							foreach( $items as $item ) {
								$item->add_tax( $tax_item );
							}
						} else {
							$this->document->add_item( $tax_item );
						}
					}

					if ( $this->document->get_shipping_tax() > 0 ) {
						$tax_item = new \Vendidero\StoreaBill\Invoice\TaxItem();
						$shipping_tax_total = $this->document->get_shipping_tax();

						$total_tax -= $shipping_tax_total;
						$total_net -= $this->document->get_shipping_net();

						$tax_item->set_total_net( $this->document->get_shipping_net() );
						$tax_item->set_subtotal_net( $this->document->get_shipping_net() );
						$tax_item->set_total_tax( $shipping_tax_total );
						$tax_item->set_subtotal_tax( $shipping_tax_total );

						$tax_item->set_tax_rate( $rates[ $merge_key ] );
						$tax_item->set_tax_type( 'shipping' );

						$items = $this->document->get_items( 'shipping' );

						if ( ! empty( $items ) ) {
							foreach( $items as $item ) {
								$item->add_tax( $tax_item );
							}
						} else {
							$this->document->add_item( $tax_item );
						}
					}

					if ( $this->document->get_fee_tax() > 0 ) {
						$tax_item = new \Vendidero\StoreaBill\Invoice\TaxItem();
						$fee_tax_total = $this->document->get_fee_tax();

						$total_tax -= $fee_tax_total;
						$total_net -= $this->document->get_fee_net();

						$tax_item->set_total_net( $this->document->get_fee_net() );
						$tax_item->set_subtotal_net( $this->document->get_fee_net() );
						$tax_item->set_total_tax( $fee_tax_total );
						$tax_item->set_subtotal_tax( $fee_tax_total );

						$tax_item->set_tax_rate( $rates[ $merge_key ] );
						$tax_item->set_tax_type( 'fee' );

						$items = $this->document->get_items( 'fee' );

						if ( ! empty( $items ) ) {
							foreach( $items as $item ) {
								$item->add_tax( $tax_item );
							}
						} else {
							$this->document->add_item( $tax_item );
						}
					}

					$total_tax = wc_format_decimal( $total_tax, '' );
					$total_net = wc_format_decimal( $total_net, '' );

					if ( $total_tax > 0 ) {
						$tax_item = new \Vendidero\StoreaBill\Invoice\TaxItem();

						$tax_item->set_total_net( $total_net );
						$tax_item->set_subtotal_net( $total_net );
						$tax_item->set_total_tax( $total_tax );
						$tax_item->set_subtotal_tax( $total_tax );

						$tax_item->set_tax_rate( $rates[ $merge_key ] );
						$tax_item->set_tax_type( 'legacy' );

						$this->document->add_item( $tax_item );
					}
				} else {
					$product_tax_share    = ( $this->document->get_product_total() / $this->document->get_total() );
					$shipping_tax_share   = ( $this->document->get_shipping_total() / $this->document->get_total() );
					$fee_tax_share        = ( $this->document->get_fee_total() / $this->document->get_total() );

					if ( ! empty( $product_tax_share ) ) {
						$tax_item = new \Vendidero\StoreaBill\Invoice\TaxItem();
						$item_total_tax = $total_tax * $product_tax_share;

						$tax_item->set_total_tax( $item_total_tax );
						$tax_item->set_subtotal_tax( $item_total_tax );

						$tax_item->set_tax_rate( $rates[ $merge_key ] );
						$tax_item->set_tax_type( 'product' );

						$this->document->add_item( $tax_item );
					}

					if ( ! empty( $shipping_tax_share ) ) {
						$tax_item = new \Vendidero\StoreaBill\Invoice\TaxItem();
						$item_total_tax = $total_tax * $shipping_tax_share;

						$tax_item->set_total_tax( $item_total_tax );
						$tax_item->set_subtotal_tax( $item_total_tax );

						$tax_item->set_tax_rate( $rates[ $merge_key ] );
						$tax_item->set_tax_type( 'shipping' );

						$this->document->add_item( $tax_item );
					}

					if ( ! empty( $fee_tax_share ) ) {
						$tax_item = new \Vendidero\StoreaBill\Invoice\TaxItem();
						$item_total_tax = $total_tax * $fee_tax_share;

						$tax_item->set_total_tax( $item_total_tax );
						$tax_item->set_subtotal_tax( $item_total_tax );

						$tax_item->set_tax_rate( $rates[ $merge_key ] );
						$tax_item->set_tax_type( 'fee' );

						$this->document->add_item( $tax_item );
					}
				}
			}
		}
	}

	protected function import_invoice_cancellation() {
		if ( ! $this->get_parent() ) {
			$parent_id = get_post_meta( $this->post->ID, '_invoice_parent_id', true );

			// Import parent first
			$importer      = new Importer( $parent_id );
			$parent_result = $importer->import( false );

			foreach( $importer->get_logs() as $log ) {
				$this->log( $log );
			}

			if ( is_wp_error( $parent_result ) ) {
				if ( $importer->has_errors( true ) ) {
					$this->error( sprintf( __( 'Error(s) while importing %1$s parent %2$s - skipping.', 'woocommerce-germanized-pro' ), $this->formatted_number, $parent_id ), true );

					return;
				} else {
					foreach( $parent_result->get_error_messages() as $error ) {
						$this->error( $error );
					}

					$this->error( sprintf( __( 'Error(s) while importing %1$s parent %2$s.', 'woocommerce-germanized-pro' ), $this->formatted_number, $parent_id ) );
				}
			}
		}

		if ( ! $this->get_parent() ) {
			$this->error( sprintf( __( 'Missing parent for %s', 'woocommerce-germanized-pro' ), $this->formatted_number ), true );
			return;
		}

		$this->document->set_prices_include_tax( $this->get_parent()->get_prices_include_tax() );
		$this->document->set_round_tax_at_subtotal( $this->get_parent()->get_round_tax_at_subtotal() );
		$this->document->set_parent_id( $this->get_parent()->get_id() );
		$this->document->set_parent_number( $this->get_parent()->get_number() );
		$this->document->set_parent_formatted_number( $this->get_parent()->get_formatted_number() );
		$this->document->set_number( get_post_meta( $this->post->ID, '_invoice_number', true ) );
		$this->document->set_formatted_number( $this->parse_formatted_number() );

		$invoice_totals     = get_post_meta( $this->post->ID, '_invoice_totals', true );
		$invoice_tax_totals = get_post_meta( $this->post->ID, '_invoice_tax_totals', true );
		$invoice_items      = get_post_meta( $this->post->ID, '_invoice_items', true );

		$this->document->set_order_id( $this->reference_id );
		$this->document->set_total( abs( $invoice_totals['total'] ) );
		$this->document->set_total_tax( abs( $invoice_totals['tax'] ) );

		$this->items_to_cancel = array();

		if ( $this->refund_order_id > 0 ) {
			if ( $this->has_item_data() ) {
				$this->import_invoice_cancellation_refund_item_data();
			} else {
				$this->import_invoice_cancellation_refund();
			}

			if ( empty( $this->items_to_cancel ) ) {
				/**
				 * Try to cancel based on total amounts
				 */
				$this->log( sprintf( 'No cancellable items found for %s. Cancelling based on refund totals instead.', $this->formatted_number ) );

				$total              = abs( $invoice_totals['total'] );
				$total_tax          = abs( $invoice_totals['tax'] );
				$shipping_net_total = abs( $invoice_totals['shipping'] );
				$product_total      = abs( $invoice_totals['subtotal_gross'] );
				$product_net_total  = abs( $invoice_totals['subtotal'] );
				// Fee total in net
				$fee_net_total     = $total - $total_tax - $shipping_net_total - $product_net_total;
				$left_to_cancel    = $this->get_parent()->get_items_left_to_cancel();

				$cancel_amounts = array(
					'product'  => $product_total,
					'shipping' => $shipping_net_total,
					'fee'      => $fee_net_total,
				);

				foreach( $this->get_parent()->get_items( $this->get_parent()->get_item_types_cancelable() ) as $item ) {
					if ( array_key_exists( $item->get_id(), $left_to_cancel ) ) {
						$total_left    = $left_to_cancel[ $item->get_id() ]['total'];
						$quantity_left = $left_to_cancel[ $item->get_id() ]['quantity'];

						if ( array_key_exists( $item->get_item_type(), $cancel_amounts ) ) {
							$to_cancel_amount = $cancel_amounts[ $item->get_item_type() ];

							if ( $to_cancel_amount > 0 ) {
								$cancel_amount = $total_left;

								if ( $cancel_amount > $to_cancel_amount ) {
									$cancel_amount = $to_cancel_amount;
								}

								/**
								 * $shipping_total and $fee_total is missing tax amount.
								 */
								if ( 'shipping' === $item->get_item_type() ) {
									$cancel_amount += $item->get_total_tax();

									// Rounding issues
									if ( abs( $cancel_amount - $item->get_total() ) <= 0.01 ) {
										$cancel_amount = $item->get_total();
									}
								} elseif ( 'fee' === $item->get_item_type() ) {
									$cancel_amount += $item->get_total_tax();

									// Rounding issues
									if ( abs( $cancel_amount - $item->get_total() ) <= 0.01 ) {
										$cancel_amount = $item->get_total();
									}
								}

								$this->items_to_cancel[ $item->get_id() ] = array(
									'quantity' => $quantity_left,
									'total'    => $cancel_amount,
								);

								$cancel_amounts[ $item->get_item_type() ] -= $cancel_amount;
							}
						}
					}
				}
			}
		}

		if ( empty( $this->items_to_cancel ) ) {
			$this->log( sprintf( 'No cancellable items found for %s. Cancelling what is left in parent invoice (full cancellation).', $this->formatted_number ) );

			$this->items_to_cancel = $this->get_parent()->get_items_left_to_cancel();
		}

		$this->document->set_reference_id( $this->get_parent()->get_reference_id() );
		$this->document->set_reference_type( $this->get_parent()->get_reference_type() );
		$this->document->set_reference_number( $this->get_parent()->get_reference_number() );
		$this->document->set_address( $this->get_parent()->get_address() );
		$this->document->set_shipping_address( $this->get_parent()->get_shipping_address() );
		$this->document->set_prices_include_tax( $this->get_parent()->get_prices_include_tax() );
		$this->document->set_round_tax_at_subtotal( $this->get_parent()->get_round_tax_at_subtotal() );
		$this->document->set_customer_id( $this->get_parent()->get_customer_id() );
		$this->document->set_currency( $this->get_parent()->get_currency() );
		$this->document->set_is_reverse_charge( $this->get_parent()->is_reverse_charge() );
		$this->document->set_payment_method_name( $this->get_parent()->get_payment_method_name() );
		$this->document->set_payment_method_title( $this->get_parent()->get_payment_method_title() );
		$this->document->set_date_of_service( $this->get_parent()->get_date_of_service() );

		/**
		 * In case no items are left to cancel - create placeholder cancellations.
		 */
		if ( empty( $this->items_to_cancel ) ) {
			$this->version_after_save = '0.0.1-legacy-incomplete-placeholder';
		} else {
			foreach( $this->items_to_cancel as $item_id => $item_data ) {
				if ( $parent_item = $this->get_parent()->get_item( $item_id ) ) {

					$new_item = sab_get_document_item( 0, $parent_item->get_type() );
					$props    = array_diff_key( $parent_item->get_data(), array_flip( array( 'id', 'document_id', 'parent_id', 'taxes', 'quantity', 'total_tax', 'subtotal_tax', 'price', 'price_subtotal', 'line_subtotal', 'line_total' ) ) );

					$this->document->add_item( $new_item );

					$new_item->set_parent_id( $item_id );
					$new_item->set_props( $props );

					/**
					 * Calculate the percentage of the parent item
					 * being cancelled.
					 */
					$total_percentage = $item_data['total'] / $parent_item->get_total();

					if ( $total_percentage >= 1 ) {
						$total_percentage = 1;
					}

					if ( is_callable( array( $new_item, 'set_quantity' ) ) ) {
						$new_item->set_quantity( $item_data['quantity'] );
					}

					if ( is_callable( array( $new_item, 'set_line_total' ) ) ) {
						$new_item->set_line_total( $item_data['total'] );

						if ( is_callable( array( $new_item, 'set_line_subtotal' ) ) ) {
							$new_item->set_line_subtotal( $item_data['total'] );
						}
					}

					if ( is_a( $new_item, '\Vendidero\StoreaBill\Invoice\TaxableItem' ) ) {
						$item_total_tax    = 0;
						$item_subtotal_tax = 0;

						foreach( $parent_item->get_taxes() as $tax_item ) {
							$new_tax_item = sab_get_document_item( 0, $tax_item->get_type() );
							$props        = array_diff_key( $tax_item->get_data(), array_flip( array( 'id', 'document_id', 'parent_id', 'taxes', 'total_net', 'subtotal_net', 'total_tax', 'subtotal_tax' ) ) );

							$new_tax_item->set_props( $props );

							$tax_total    = $tax_item->get_total_tax() * $total_percentage;
							$subtotal_tax = $tax_item->get_subtotal_tax() * $total_percentage;

							$item_total_tax += $tax_total;
							$item_subtotal_tax += $subtotal_tax;

							// Need to calculate taxes for adjusted totals.
							$new_tax_item->set_total_tax( $tax_total );
							$new_tax_item->set_subtotal_tax( $subtotal_tax );
							$new_tax_item->set_total_net( $tax_item->get_total_net() * $total_percentage );
							$new_tax_item->set_subtotal_net( $tax_item->get_subtotal_net() * $total_percentage );

							$new_item->add_tax( $new_tax_item );
						}

						if ( ! $this->document->prices_include_tax() ) {
							if ( is_callable( array( $new_item, 'set_line_total' ) ) ) {
								$new_item->set_line_total( $item_data['total'] - $item_total_tax );

								if ( is_callable( array( $new_item, 'set_line_subtotal' ) ) ) {
									$new_item->set_line_subtotal( $item_data['total'] - $item_subtotal_tax );
								}
							}
						}

						$new_item->set_total_tax( $item_total_tax );
						$new_item->set_subtotal_tax( $item_subtotal_tax );
					}
				}
			}

			$this->document->calculate_totals( false );
		}

		// Force total and total tax to equal imported invoice.
		$this->document->set_total( abs( $invoice_totals['total'] ) );
		$this->document->set_total_tax( abs( $invoice_totals['tax'] ) );

		$invoice_paid_total      = $this->get_parent()->get_total_paid();
		$cancellation_total_paid = $this->document->get_total() > $invoice_paid_total ? $invoice_paid_total : $this->document->get_total();

		$this->document->set_payment_status( $cancellation_total_paid < $this->document->get_total() ? 'partial' : 'complete' );
		$this->document->set_total_paid( $cancellation_total_paid );
	}

	protected function import_invoice_cancellation_refund() {
		$this->log( sprintf( 'Importing %s based on refund data.', $this->formatted_number ) );

		/**
		 * Indicate version.
		 */
		$this->version_after_save = '0.0.1-legacy-refund-order';

		$sync_by_refund_order = false;

		if ( $refund_order = $this->get_refund_order() ) {
			$total     = abs( $refund_order->get_total() );
			$total_tax = abs( $refund_order->get_total_tax() );

			if ( $this->document->get_total() == $total && $this->document->get_total_tax() == $total_tax ) {
				$sync_by_refund_order = true;

				foreach( $refund_order->get_items( array( 'line_item', 'shipping', 'fee' ) ) as $item ) {
					$quantity = $item->get_quantity();
					$total    = abs( $item->get_total() );
					$tax      = abs( $item->get_total_tax() );
					$item_id  = $item->get_id();

					/**
					 * Find the parent item id based on refund order
					 */
					if ( $refund_order ) {
						if ( $refund_item = $refund_order->get_item( $item_id ) ) {
							$item_id = $refund_item->get_meta( '_refunded_item_id' );
						}
					}

					$gross_total = $total + $tax;

					// Find by other attributes (e.g. product id)
					$product_id = is_callable( array( $item, 'get_product_id' ) ) ? $item->get_product_id() : 0;
					$product_id = is_callable( array( $item, 'get_variation_id' ) && ! empty( $item->get_variation_id() ) ) ? $item->get_variation_id() : $product_id;
					$item_name  = $item->get_name();

					if ( ! $parent_item = $this->get_parent()->get_item_by_reference_id( $item_id ) ) {
						$parent_item = $this->find_parent_item_by_product_id( $product_id );

						if ( ! $parent_item ) {
							$parent_item = $this->find_parent_item_by_name( $item_name );
						}

						if ( ! $parent_item ) {
							$parent_item = $this->find_parent_item_by_total( $gross_total );
						}
					}

					if ( ! $parent_item ) {
						$this->items_to_cancel = array();
						break;
					} else {
						$this->items_to_cancel[ $parent_item->get_id() ] = array(
							'quantity' => $quantity,
							'total'    => $gross_total,
						);
					}
				}
			}
		}

		/**
		 * Syncing by refund order data has failed.
		 */
		if ( ! $sync_by_refund_order ) {
			$this->items_to_cancel = array();
		}
	}

	protected function import_invoice_cancellation_refund_item_data() {
		$this->log( sprintf( 'Importing %s based on refund item data.', $this->formatted_number ) );

		/**
		 * Indicate version.
		 */
		$this->version_after_save = '0.0.1-legacy-refund-item-data';

		$items = array_merge( $this->get_item_data( 'product' ), $this->get_item_data( 'shipping' ), $this->get_item_data( 'fee' ) );

		foreach( $items as $item ) {
			$quantity = isset( $item['quantity'] ) ? abs( $item['quantity'] ) : 1;
			$total    = isset( $item['total'] ) ? abs( $item['total'] ) : 0;
			$tax      = isset( $item['total_tax'] ) ? abs( $item['total_tax'] ) : 0;
			$item_id  = $item['id'];

			/**
			 * Find the parent item id based on refund order
			 */
			if ( $refund_order = $this->get_refund_order() ) {
				if ( $refund_item = $refund_order->get_item( $item_id ) ) {
					$item_id = $refund_item->get_meta( '_refunded_item_id' );
				}
			}

			$gross_total = wc_format_decimal( $total + $tax, '' );

			// Find by other attributes (e.g. product id)
			$product_id = isset( $item['product_id'] ) ? absint( $item['product_id'] ) : 0;
			$product_id = isset( $item['variation_id'] ) && ! empty( $item['variation_id'] ) ? absint( $item['variation_id'] ) : $product_id;
			$item_name  = $item['name'];

			if ( ! $parent_item = $this->get_parent()->get_item_by_reference_id( $item_id ) ) {
				$parent_item = $this->find_parent_item_by_product_id( $product_id );

				if ( ! $parent_item ) {
					$parent_item = $this->find_parent_item_by_name( $item_name );
				}

				if ( ! $parent_item ) {
					$parent_item = $this->find_parent_item_by_total( $gross_total );
				}
			}

			if ( ! $parent_item ) {
				$this->items_to_cancel = array();
				break;
			} else {
				$this->items_to_cancel[ $parent_item->get_id() ] = array(
					'quantity' => $quantity,
					'total'    => wc_format_decimal( $gross_total ),
				);
			}
		}
	}

	protected function import_packing_slip() {
		$shipment_id = get_post_meta( $this->post->ID,'_invoice_shipment_id', true );

		if ( empty( $shipment_id ) || ! ( $shipment = wc_gzd_get_shipment( $shipment_id ) ) ) {
			$this->import_packing_slip_legacy();
		} else {
			$this->import_packing_slip_by_shipment();
		}

		if ( $this->document ) {
			$this->document->set_number( get_post_meta( $this->post->ID, '_invoice_number', true ) );
			$this->document->set_formatted_number( $this->parse_formatted_number() );
			$this->document->set_order_id( get_post_meta( $this->post->ID, '_invoice_order', true ) );
			$this->document->set_order_number( get_post_meta( $this->post->ID, '_invoice_order_number', true ) );
		} else {
			$this->error( sprintf( __( 'Error while creating document for %s - skipping', 'woocommerce-germanized-pro' ), $this->formatted_number ), true );
		}
	}

	protected function import_packing_slip_by_shipment() {
		$shipment_id = get_post_meta( $this->post->ID,'_invoice_shipment_id', true );

		$this->document = new PackingSlip();
		$this->document->set_shipment_id( $shipment_id );
		$this->version_after_save = '0.0.1-legacy-shipment-sync';

		if ( $syncable_shipment = PackingSlips::get_shipment( $shipment_id ) ) {
			$syncable_shipment->sync( $this->document );
		}
	}

	protected function import_packing_slip_legacy() {
		$shipment_id = get_post_meta( $this->post->ID,'_invoice_shipment_id', true );

		$this->document = new PackingSlip();

		if ( ! empty( $shipment_id ) ) {
			$this->document->set_shipment_id( $shipment_id );
		}

		$this->version_after_save = '0.0.1-legacy-incomplete';
	}

	protected function find_parent_item_by_product_id( $product_id ) {
		foreach( $this->get_parent()->get_items( 'product' ) as $item ) {
			if ( $item->get_product_id() == $product_id ) {
				$this->log( sprintf( 'Found parent item for %1$s based on product id %2$s.', $this->formatted_number, $product_id ) );

				return $item;
			}
		}

		return false;
	}

	protected function find_parent_item_by_name( $name ) {
		foreach( $this->get_parent()->get_items() as $item ) {
			if ( $item->get_name() == $name ) {
				$this->log( sprintf( 'Found parent item for %1$s based on name "%2$s".', $this->formatted_number, $name ) );

				return $item;
			}
		}

		foreach( $this->get_parent()->get_items() as $item ) {
			if ( strpos( $item->get_name(), $name ) !== false ) {
				$this->log( sprintf( 'Found parent item for %1$s based on name substring "%2$s" matching "%3$s".', $this->formatted_number, $name, $item->get_name() ) );

				return $item;
			}
		}

		return false;
	}

	protected function find_parent_item_by_total( $total ) {
		foreach( $this->get_parent()->get_items() as $item ) {
			if ( is_callable( array( $item, 'get_total' ) ) ) {
				if ( $item->get_total() == $total ) {
					$this->log( sprintf( 'Found parent item for %1$s based on total "%3$s".', $this->formatted_number, $total ) );

					return $item;
				}
			}
		}

		return false;
	}
}