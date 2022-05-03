<?php

namespace Vendidero\StoreaBill\Admin;

use Vendidero\StoreaBill\Document\BulkActionHandler;
use Vendidero\StoreaBill\Document\DefaultTemplate;
use Vendidero\StoreaBill\Document\Document;
use Vendidero\StoreaBill\Document\FirstPageTemplate;
use Vendidero\StoreaBill\ExternalSync\Helper;
use Vendidero\StoreaBill\Interfaces\Exporter;

defined( 'ABSPATH' ) || exit;

/**
 *
 */
class Ajax {

	/**
	 * Constructor.
	 */
	public static function init() {
		self::add_ajax_events();
	}

	/**
	 * Hook in methods - uses WordPress ajax handlers (admin-ajax).
	 */
	public static function add_ajax_events() {

		$ajax_events = array(
			'delete_document',
			'cancel_invoice',
			'finalize_invoice',
			'refresh_document',
			'update_invoice_payment_status',
			'handle_bulk_action',
			'export',
			'external_sync',
			'send_document',
			'delete_document_template',
			'copy_document_template',
			'create_document_template_first_page',
			'update_default_document_template',
			'create_document_template',
			'preview_formatted_document_number',
			'json_search_external_customers'
		);

		foreach ( $ajax_events as $ajax_event ) {
			add_action( 'wp_ajax_storeabill_admin_' . $ajax_event, array( __CLASS__, 'suppress_errors' ), 5 );
			add_action( 'wp_ajax_storeabill_admin_' . $ajax_event, array( __CLASS__, $ajax_event ) );
		}
	}

	public static function suppress_errors() {
		if ( ! WP_DEBUG || ( WP_DEBUG && ! WP_DEBUG_DISPLAY ) ) {
			@ini_set( 'display_errors', 0 ); // Turn off display_errors during AJAX events to prevent malformed JSON.
		}

		$GLOBALS['wpdb']->hide_errors();
	}

	protected static function is_ajax_request() {
		return ( ! isset( $_GET['_wpnonce'] ) || isset( $_GET['do_ajax'] ) );
	}

	protected static function get_request_data( $key, $default = null ) {
		return ( isset( $_REQUEST[ $key ] ) ? self::clean_data( $_REQUEST[ $key ] ) : $default );
	}

	protected static function clean_data( $data ) {
		if ( is_array( $data ) ) {
			$data = array_map( 'wp_unslash', $data );
			$data = array_map( 'sab_clean', $data );
			$data = array_filter( $data );
		} else {
			$data = sab_clean( wp_unslash( $data ) );
		}

		return $data;
	}

	protected static function get_screen_id() {
		$screen_id = self::get_request_data( 'screen_id' );

		if ( ! $screen_id ) {
			$screen_id = 'sab-accounting';
		}

		return $screen_id;
	}

	protected static function error( $error = array() ) {
		if ( self::is_ajax_request() ) {
			$error['success'] = false;
			wp_send_json( $error );
		} else {
			self::add_notices( $error, 'error' );

			wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=sab-accounting' ) );
			exit;
		}
	}

	protected static function add_notices( $data, $type = 'success' ) {
		$data = wp_parse_args( $data, array(
			'messages' => array(),
		) );

		if ( ! empty( $data['messages'] ) ) {
			foreach( $data['messages'] as $message ) {
				Notices::add( $message, $type, self::get_screen_id() );
			}
		}
	}

	protected static function success( $data = array() ) {
		if ( self::is_ajax_request() ) {
			$data['success'] = true;
			wp_send_json( $data );
		} else {
			self::add_notices( $data );

			wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=sab-accounting' ) );
			exit;
		}
	}

	public static function json_search_external_customers() {
		ob_start();

		check_ajax_referer( 'sab-search-external-customers', 'security' );

		if ( ! current_user_can( 'manage_storeabill' ) ) {
			wp_die( -1 );
		}

		$limit        = 20;
		$term         = isset( $_GET['term'] ) ? (string) sab_clean( wp_unslash( $_GET['term'] ) ) : '';
		$handler_name = isset( $_GET['handler'] ) ? (string) sab_clean( wp_unslash( $_GET['handler'] ) ) : '';

		if ( empty( $term ) || ! ( $handler = Helper::get_sync_handler( $handler_name ) ) ) {
			wp_die();
		}

		$found_customers = $handler->search_customers( $term );
		$found_customers = array_slice( $found_customers, 0, $limit, true );

		wp_send_json( $found_customers );
	}

	public static function preview_formatted_document_number() {
		check_ajax_referer( 'sab-preview-formatted-document-number', 'security' );

		if ( ! self::get_request_data( 'document_type' ) || ! self::get_request_data( 'number_format' ) || ! current_user_can( 'manage_storeabill' ) ) {
			wp_die( -1 );
		}

		$document_type   = self::get_request_data( 'document_type' );
		$number_format   = self::get_request_data( 'number_format' );
		$number_min_size = self::get_request_data( 'number_min_size', 0 );
		$last_number     = self::get_request_data( 'last_number', 1 );

		$response = array(
			'preview' => '',
		);

		if ( $preview = sab_get_document_preview( $document_type ) ) {
			$preview->get_journal()->set_number_format( $number_format );
			$preview->get_journal()->set_number_min_size( absint( $number_min_size ) );

			$preview->set_number( absint( $last_number ) + 1 );
			$preview->set_formatted_number( $preview->format_number( $preview->get_number() ) );

			$response['preview'] = '<code>' . $preview->get_formatted_number() . '</code>';
		}

		self::success( $response );
	}

	public static function create_document_template() {
		check_ajax_referer( 'sab-edit-document-template', 'security' );

		if ( ! self::get_request_data( 'document_type' ) || ! current_user_can( 'manage_storeabill' ) ) {
			wp_die( -1 );
		}

		$document_type  = sab_clean( self::get_request_data( 'document_type' ) );
		$template_name  = sab_clean( self::get_request_data( 'template', '' ) );

		$response_error = array(
			'messages' => array( _x( 'Error while creating the template.', 'storeabill-core', 'woocommerce-germanized-pro' ) ),
		);

		if ( ! $template = sab_create_document_template( $document_type, $template_name, true ) ) {
			self::error( $response_error );
		}

		self::template_success( array(
			'messages'        => array( sprintf( _x( 'New template added successfully. <a href="%s">Edit template</a>', 'storeabill-core', 'woocommerce-germanized-pro' ), $template->get_edit_url() ) ),
			'new_template_id' => $template->get_id(),
			'edit_url'        => $template->get_edit_url()
		), $template->get_document_type() );
	}

	public static function update_default_document_template() {
		check_ajax_referer( 'sab-edit-document-template', 'security' );

		if ( ! self::get_request_data( 'id' ) || ! self::get_request_data( 'document_type' ) || ! current_user_can( 'manage_storeabill' ) ) {
			wp_die( -1 );
		}

		$id             = absint( self::get_request_data( 'id' ) );
		$document_type  = sab_clean( self::get_request_data( 'document_type' ) );
		$response_error = array(
			'messages' => array( _x( 'Error while updating the template.', 'storeabill-core', 'woocommerce-germanized-pro' ) ),
		);

		if ( ! $template = sab_get_document_template( $id ) ) {
			self::error( $response_error );
		}

		if ( ! $document_type_data = sab_get_document_type( $document_type ) ) {
			self::error( $response_error );
		}

		update_option( 'storeabill_' . $document_type . '_default_template', $template->get_id() );

		self::template_success( array(
			'messages' => array( _x( 'Default template updated successfully', 'storeabill-core', 'woocommerce-germanized-pro' ) ),
		), $template->get_document_type() );
	}

	public static function create_document_template_first_page() {
		check_ajax_referer( 'sab-edit-document-template', 'security' );

		if ( ! self::get_request_data( 'id' ) || ! current_user_can( 'manage_storeabill' ) ) {
			wp_die( -1 );
		}

		$id             = absint( self::get_request_data( 'id' ) );
		$response_error = array(
			'messages' => array( _x( 'Error while creating the template.', 'storeabill-core', 'woocommerce-germanized-pro' ) ),
		);

		if ( ( ! $template = sab_get_document_template( $id ) ) || $template->is_first_page() ) {
			self::error( $response_error );
		}

		$content          = $template->get_content();
		$header_content   = \Vendidero\StoreaBill\Editor\Helper::get_block_content( 'storeabill/header', $content, true );
		$footer_content   = \Vendidero\StoreaBill\Editor\Helper::get_block_content( 'storeabill/footer', $content, true );

		$tpl = new FirstPageTemplate();
		$tpl->set_parent_id( $template->get_id() );
		$tpl->set_status( 'publish' );
		$tpl->set_content(
			"<!-- wp:storeabill/document-styles /-->" . $header_content . $footer_content
		);
		$tpl->save();

		self::template_success( array(
			'messages'        => array( sprintf( _x( 'First page template added successfully. <a href="%s">Edit template</a>', 'storeabill-core', 'woocommerce-germanized-pro' ), $tpl->get_edit_url() ) ),
			'new_template_id' => $tpl->get_id(),
		), $template->get_document_type() );
	}

	public static function copy_document_template() {
		check_ajax_referer( 'sab-edit-document-template', 'security' );

		if ( ! self::get_request_data( 'id' ) || ! current_user_can( 'manage_storeabill' ) ) {
			wp_die( -1 );
		}

		$id             = absint( self::get_request_data( 'id' ) );
		$response_error = array(
			'messages' => array( _x( 'Error while copying the template.', 'storeabill-core', 'woocommerce-germanized-pro' ) ),
		);

		if ( ! $template = sab_get_document_template( $id ) ) {
			self::error( $response_error );
		}

		if ( $new_template = sab_duplicate_document_template( $id ) ) {
			self::template_success( array(
				'messages'        => array( _x( 'Template duplicated successfully.', 'storeabill-core', 'woocommerce-germanized-pro' ) ),
				'new_template_id' => $new_template->get_id(),
			), $template->get_document_type() );
		} else {
			self::template_error( $response_error, $template->get_document_type() );
		}
	}

	public static function delete_document_template() {
		check_ajax_referer( 'sab-edit-document-template', 'security' );

		if ( ! self::get_request_data( 'id' ) || ! current_user_can( 'manage_storeabill' ) ) {
			wp_die( -1 );
		}

		$id             = absint( self::get_request_data( 'id' ) );
		$response_error = array(
			'messages' => array( _x( 'Error while deleting the template.', 'storeabill-core', 'woocommerce-germanized-pro' ) ),
		);

		if ( ! $template = sab_get_document_template( $id, true ) ) {
			self::error( $response_error );
		}

		$document_type = $template->get_document_type();

		if ( $template->delete( true ) ) {
			self::template_success( array(
				'messages' => array( _x( 'Template deleted successfully.', 'storeabill-core', 'woocommerce-germanized-pro' ) ),
			), $document_type );
		} else {
			self::template_error( $response_error, $document_type );
		}
 	}

 	protected static function get_template_html( $document_type ) {
	    ob_start();
	    Fields::render_document_templates_field( array(
		    'document_type' => $document_type,
	    ) );
	    $html = ob_get_clean();

	    return $html;
    }

 	protected static function template_success( $data, $document_type = 'invoice' ) {
		$data['fragments'] = array(
			'.sab-document-templates' => self::get_template_html( $document_type ),
		);

		self::success( $data );
    }

    protected static function template_error( $data, $document_type = 'invoice' ) {
	    $data['fragments'] = array(
		    '.sab-document-templates' => self::get_template_html( $document_type ),
	    );

	    self::error( $data );
    }

	public static function delete_document() {
		if ( self::is_ajax_request() ) {
			check_ajax_referer( 'sab-delete-document', 'security' );
		} else {
			check_admin_referer( 'sab-delete-document' );
		}

		if ( ! self::get_request_data( 'document_id' ) ) {
			wp_die( -1 );
		}

		$document_id = absint( self::get_request_data( 'document_id' ) );

		$response_error = array(
			'messages' => array( _x( 'Error while deleting the document.', 'storeabill-core', 'woocommerce-germanized-pro' ) ),
		);

		if ( ! $document = sab_get_document( $document_id ) ) {
			self::error( $response_error );
		}

		if ( ! current_user_can( 'delete_' . $document->get_type(), $document->get_id() ) ) {
			wp_die( -1 );
		}

		if ( ! $document->is_editable() ) {
			self::error( array(
				'messages' => array( _x( 'This document cannot be deleted.', 'storeabill-core', 'woocommerce-germanized-pro' ) )
			) );
		}

		if ( $document->delete( true ) ) {
			self::success( array(
				'messages' => array( _x( 'Document deleted successfully.', 'storeabill-core', 'woocommerce-germanized-pro' ) )
			) );
		} else {
			self::error( $response_error );
		}
	}

	public static function send_document() {
		if ( self::is_ajax_request() ) {
			check_ajax_referer( 'sab-send-document', 'security' );
		} else {
			check_admin_referer( 'sab-send-document' );
		}

		if ( ! self::get_request_data( 'document_id' ) ) {
			wp_die( -1 );
		}

		$document_id  = absint( self::get_request_data( 'document_id' ) );
		$display_type = sab_clean( self::get_request_data( 'display_type', 'table' ) );

		$response_error = array(
			'messages' => array( _x( 'Error while sending the document.', 'storeabill-core', 'woocommerce-germanized-pro' ) ),
		);

		if ( ! $document = sab_get_document( $document_id ) ) {
			self::error( $response_error );
		}

		if ( ! current_user_can( 'edit_' . $document->get_type(), $document->get_id() ) ) {
			wp_die( -1 );
		}

		$result = $document->send_to_customer();

		if ( ! is_wp_error( $result ) ) {
			self::success( array(
				'messages' => array( _x( 'Document sent successfully.', 'storeabill-core', 'woocommerce-germanized-pro' ) ),
				'fragments'   => array(
					'.sab-document-actions' => self::get_document_actions_html( $document, $display_type )
				),
			) );
		} else {
			$response_error = array( 'messages' => $result->get_error_messages() );

			self::error( $response_error );
		}
	}

	public static function refresh_document() {
		if ( self::is_ajax_request() ) {
			check_ajax_referer( 'sab-refresh-document', 'security' );
		} else {
			check_admin_referer( 'sab-refresh-document' );
		}

		if ( ! self::get_request_data( 'document_id' ) ) {
			wp_die( -1 );
		}

		$document_id = absint( self::get_request_data( 'document_id' ) );

		$response_error = array(
			'messages' => array( _x( 'Error while refreshing the document.', 'storeabill-core', 'woocommerce-germanized-pro' ) ),
		);

		if ( ! $document = sab_get_document( $document_id ) ) {
			self::error( $response_error );
		}

		if ( ! current_user_can( 'edit_' . $document->get_type(), $document->get_id() ) ) {
			wp_die( -1 );
		}

		$result = $document->render();

		if ( ! is_wp_error( $result ) ) {
			self::success( array(
				'messages' => array( _x( 'Document refreshed successfully.', 'storeabill-core', 'woocommerce-germanized-pro' ) )
			) );
		} else {
			$response_error = array( 'messages' => $result->get_error_messages() );
			self::error( $response_error );
		}
	}

	public static function external_sync() {
		if ( self::is_ajax_request() ) {
			check_ajax_referer( 'sab-external-sync' );
		} else {
			check_admin_referer( 'sab-external-sync' );
		}

		if ( ! self::get_request_data( 'object_id' ) || ! self::get_request_data( 'object_type' ) || ! self::get_request_data( 'handler' ) ) {
			wp_die( -1 );
		}

		$object_id      = absint( self::get_request_data( 'object_id' ) );
		$object_type    = sab_clean( self::get_request_data( 'object_type' ) );
		$reference_type = sab_clean( self::get_request_data( 'reference_type', '' ) );
		$handler_name   = sab_clean( self::get_request_data( 'handler' ) );
		$display_type   = sab_clean( self::get_request_data( 'display_type', 'table' ) );

		$response_error = array(
			'messages' => array( _x( 'Sync failed.', 'storeabill-core', 'woocommerce-germanized-pro' ) ),
		);

		if ( ! $object = Helper::get_object( $object_id, $object_type, $reference_type ) ) {
			self::error( $response_error );
		}

		if ( ! $handler = Helper::get_sync_handler( $handler_name ) ) {
			self::error( $response_error );
		}

		if ( ! current_user_can( 'edit_' . $object->get_type(), $object->get_id() ) ) {
			wp_die( -1 );
		}

		/**
		 * Cancel outstanding events.
		 */
		Helper::cancel_deferred_sync( $object, $handler );

		$result = $handler->sync( $object );

		if ( ! is_wp_error( $result ) ) {
			self::success( array(
				'messages'    => array( _x( 'Synced successfully.', 'storeabill-core', 'woocommerce-germanized-pro' ) ),
				'fragments'   => array(
					'.sab-document-actions' => self::get_document_actions_html( $object, $display_type )
				),
			) );
		} else {
			$response_error = array( 'messages' => $result->get_error_messages() );
			self::error( $response_error );
		}
	}

	/**
	 * @param Document $document
	 */
	protected static function get_document_actions_html( $document, $for = 'table' ) {
		return Admin::get_document_actions_html( Admin::get_document_actions( $document, $for ) );
	}

	public static function update_invoice_payment_status() {
		if ( self::is_ajax_request() ) {
			check_ajax_referer( 'sab-update-invoice-payment-status', 'security' );
		} else {
			check_admin_referer( 'sab-update-invoice-payment-status' );
		}

		if ( ! current_user_can( 'edit_invoice' ) || ! self::get_request_data( 'document_id' ) || ! self::get_request_data( 'status' ) ) {
			wp_die( -1 );
		}

		$response_error = array(
			'messages' => array( _x( 'Error while updating the payment status.', 'storeabill-core', 'woocommerce-germanized-pro' ) ),
		);

		$status      = self::get_request_data( 'status' );
		$statuses    = array_keys( sab_get_invoice_payment_statuses() );
		$document_id = absint( self::get_request_data( 'document_id' ) );

		if ( ! $document = sab_get_document( $document_id ) ) {
			self::error( $response_error );
		}

		if ( ! is_a( $document, '\Vendidero\StoreaBill\Invoice\Invoice' ) ) {
			wp_send_json( $response_error );
		}

		if ( ! in_array( $status, $statuses ) ) {
			wp_send_json( $response_error );
		}

		if ( $document->update_payment_status( $status ) ) {
			self::success( array(
				'messages' => array( _x( 'Invoice payment status updated successfully.', 'storeabill-core', 'woocommerce-germanized-pro' ) )
			) );
		} else {
			self::error( $response_error );
		}
	}

	public static function cancel_invoice() {
		if ( self::is_ajax_request() ) {
			check_ajax_referer( 'sab-cancel-invoice', 'security' );
		} else {
			check_admin_referer( 'sab-cancel-invoice' );
		}

		if ( ! current_user_can( 'edit_invoice' ) || ! self::get_request_data( 'document_id' ) ) {
			wp_die( -1 );
		}

		$response_error = array(
			'messages' => array( _x( 'Error while cancelling the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ) ),
		);

		$document_id = absint( self::get_request_data( 'document_id' ) );

		if ( ! $document = sab_get_document( $document_id ) ) {
			self::error( $response_error );
		}

		if ( ! is_a( $document, '\Vendidero\StoreaBill\Invoice\Simple' ) ) {
			wp_send_json( $response_error );
		}

		if ( ! $document->is_cancelable() ) {
			self::error( array(
				'messages' => array( _x( 'This invoice cannot be cancelled.', 'storeabill-core', 'woocommerce-germanized-pro' ) )
			) );
		}

		$result = $document->cancel();

		if ( ! is_wp_error( $result ) ) {
			self::success( array(
				'messages' => array( _x( 'Invoice cancelled successfully.', 'storeabill-core', 'woocommerce-germanized-pro' ) )
			) );
		} else {
			$response_error = array( 'messages' => $result->get_error_messages() );
			self::error( $response_error );
		}
	}

	public static function finalize_invoice() {
		if ( self::is_ajax_request() ) {
			check_ajax_referer( 'sab-finalize-invoice', 'security' );
		} else {
			check_admin_referer( 'sab-finalize-invoice' );
		}

		if ( ! current_user_can( 'edit_invoice' ) || ! self::get_request_data( 'document_id' ) ) {
			wp_die( -1 );
		}

		$response_error = array(
			'messages' => array( _x( 'Error while finalizeing the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ) ),
		);

		$document_id = absint( self::get_request_data( 'document_id' ) );

		if ( ! $document = sab_get_document( $document_id ) ) {
			self::error( $response_error );
		}

		if ( ! is_a( $document, '\Vendidero\StoreaBill\Invoice\Invoice' ) ) {
			wp_send_json( $response_error );
		}

		if ( $document->is_finalized() ) {
			self::error( array(
				'messages' => array( _x( 'This invoice cannot be finalized.', 'storeabill-core', 'woocommerce-germanized-pro' ) )
			) );
		}

		/**
		 * Sync before finalizing.
		 */
		if ( $order = $document->get_order() ) {
			$order->sync( $document );
		}

		$result = $document->finalize();

		if ( ! is_wp_error( $result ) ) {
			self::success( array(
				'messages' => array( _x( 'Invoice finalized successfully.', 'storeabill-core', 'woocommerce-germanized-pro' ) )
			) );
		} else {
			$response_error = array( 'messages' => $result->get_error_messages() );
			self::error( $response_error );
		}
	}

	/**
	 * @param BulkActionHandler $handler
	 * @param $type
	 * @param $ids
	 * @param $step
	 */
	protected static function do_bulk_action( $handler, $type, $ids, $step, $ref_type = '' ) {
		if ( 1 === $step ) {
			$handler->reset();
		}

		$handler->set_step( $step );
		$handler->set_ids( $ids );
		$handler->set_object_type( $type );
		$handler->set_reference_type( $ref_type );

		try {
			$handler->handle();
		} catch( \Exception $e ) {
			self::error( array(
				'messages' => array(
					_x( 'Error while bulk processing objects', 'storeabill-core', 'woocommerce-germanized-pro' )
				),
			) );
		}

		if ( $handler->get_percent_complete() >= 100 ) {
			$errors = $handler->get_notices( 'error' );

			if ( empty( $errors ) ) {
				$handler->add_notice( $handler->get_success_message(), 'success' );
			}

			self::success(
				array(
					'step'       => 'done',
					'percentage' => 100,
					'url'        => $handler->get_done_redirect_url(),
					'type'       => $handler->get_object_type(),
				)
			);
		} else {
			self::success(
				array(
					'step'       => ++$step,
					'percentage' => $handler->get_percent_complete(),
					'ids'        => $handler->get_ids(),
					'type'       => $handler->get_object_type(),
				)
			);
		}
	}

	public static function handle_bulk_action() {
		$action   = self::get_request_data( 'bulk_action', '' );
		$type     = self::get_request_data( 'type', 'invoice' );
		$ref_type = self::get_request_data( 'reference_type', '' );

		if ( ! current_user_can( 'manage_storeabill' ) || ! self::get_request_data( 'step' ) || ! self::get_request_data( 'ids' ) ) {
			wp_die( -1 );
		}

		if ( ! $handler = Admin::get_bulk_action_handler( $action, $type ) ) {
			wp_die( -1 );
		}

		if ( self::is_ajax_request() ) {
			check_ajax_referer( $handler->get_nonce_action(), 'security' );
		} else {
			check_admin_referer( $handler->get_nonce_action() );
		}

		$ids  = self::get_request_data( 'ids', array() );
		$step = self::get_request_data( 'step', 1 );

		self::do_bulk_action( $handler, $type, $ids, $step, $ref_type );
	}

	public static function export() {

		if ( ! self::get_request_data( 'step' ) || ! self::get_request_data( 'filename' ) ) {
			wp_die( -1 );
		}

		$document_type = self::get_request_data( 'document_type', 'invoice' );
		$type          = self::get_request_data( 'type', 'csv' );
		$step          = absint( self::get_request_data( 'step', 1 ) );
		$filename      = self::get_request_data( 'filename','' );
		$filters       = self::get_request_data( 'filters', array() );

		if ( ! $exporter = sab_get_document_type_exporter( $document_type, $type ) ) {
			self::error( array(
				'messages' => array(
					_x( 'No applicable exporter found.', 'storeabill-core', 'woocommerce-germanized-pro' )
				),
			) );
		}

		check_ajax_referer( $exporter->get_nonce_action(), 'security' );

		if ( ! Exporters::export_allowed( $document_type ) ) {
			self::error( array(
				'messages' => array(
					_x( 'Insufficient privileges to export documents.', 'storeabill-core', 'woocommerce-germanized-pro' )
				),
			) );
		}

		if ( ! empty( $filters['start_date'] ) && ! \DateTime::createFromFormat( 'Y-m-d', $filters['start_date'] ) ) {
			self::error( array(
				'messages' => array(
					_x( 'Please make sure to provide a valid start date.', 'storeabill-core', 'woocommerce-germanized-pro' )
				),
			) );
		}

		if ( ! empty( $filters['end_date'] ) && ! \DateTime::createFromFormat( 'Y-m-d', $filters['end_date'] ) ) {
			self::error( array(
				'messages' => array(
					_x( 'Please make sure to provide a valid end date.', 'storeabill-core', 'woocommerce-germanized-pro' )
				),
			) );
		}

		$start_date = ! empty( $filters['start_date'] ) ? sab_string_to_datetime( $filters['start_date'] ) : false;
		$end_date   = ! empty( $filters['end_date'] ) ? sab_string_to_datetime( $filters['end_date'] ) : false;
		$today      = sab_string_to_datetime( 'now' );

		if ( $start_date && $start_date > $today ) {
			self::error( array(
				'messages' => array(
					_x( 'Please choose a start date from the past.', 'storeabill-core', 'woocommerce-germanized-pro' )
				),
			) );
		}

		if ( $start_date && $end_date && $start_date > $end_date ) {
			self::error( array(
				'messages' => array(
					_x( 'The end date must be after the start date.', 'storeabill-core', 'woocommerce-germanized-pro' )
				),
			) );
		}

		$exporter->set_filename( $filename );

		if ( is_a( $exporter, 'Vendidero\StoreaBill\Document\CsvExporter' ) ) {
			if ( ! empty( $_POST['columns'] ) ) { // WPCS: input var ok.
				$exporter->set_column_names( wp_unslash( $_POST['columns'] ) ); // WPCS: input var ok, sanitization ok.
			}
		}

		if ( $start_date ) {
			$exporter->set_start_date( $start_date );
		}

		if ( $end_date ) {
			$exporter->set_end_date( $end_date );
		}

		$org_filters = $filters;
		$filters     = array_diff_key( $filters, array_flip( array( 'start_date', 'end_date' ) ) );

		if ( ! empty( $filters ) ) {
			$exporter->set_filters( $filters );
		}

		$exporter->set_page( $step );
		$exporter->generate_file();

		if ( $exporter->has_errors() ) {
			self::error( array(
				'messages' => $exporter->get_errors()->get_error_messages(),
			) );
		} else {
			if ( $exporter->get_percent_complete() >= 100 ) {
				$query_args = array(
					'nonce'         => wp_create_nonce( $exporter->get_nonce_download_action() ),
					'action'        => 'sab-download-export',
					'export_type'   => $exporter->get_type(),
					'document_type' => $exporter->get_document_type(),
					'filename'      => $exporter->get_filename(),
				);

				$step_args = array(
					'step'       => 'done',
					'percentage' => 100,
					'url'        => add_query_arg( $query_args, admin_url( 'admin.php?page=sab-accounting-export' ) ),
				);
			} else {
				$step_args = array(
					'step'       => ++$step,
					'percentage' => $exporter->get_percent_complete(),
					'filters'    => $org_filters,
				);
			}

			if ( is_a( $exporter, 'Vendidero\StoreaBill\Document\CsvExporter' ) ) {
				$step_args['columns'] = $exporter->get_column_names();
			}

			self::success( $step_args );
		}
	}
}