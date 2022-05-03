<?php

namespace Vendidero\StoreaBill\Document;

use Vendidero\StoreaBill\Admin\Admin;
use Vendidero\StoreaBill\Utilities\CacheHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Shipment Order
 *
 * @class 		WC_GZD_Shipment_Order
 * @version		1.0.0
 * @author 		Vendidero
 */
class BulkMerge extends BulkActionHandler {

	public function get_title() {
		return _x( 'Merge and download PDFs', 'storeabill-core', 'woocommerce-germanized-pro' );
	}

	public function handle() {
		$current = $this->get_current_ids();

		/**
		 * Reset file.
		 */
		if ( 1 === $this->get_step() ) {
			delete_user_meta( get_current_user_id(), $this->get_file_option_name() );
			delete_user_meta( get_current_user_id(), $this->get_files_option_name() );
		}

		if ( ! empty( $current ) ) {
			foreach ( $current as $document_id ) {
				CacheHelper::prevent_caching();

				if ( $document = sab_get_document( $document_id ) ) {
					if ( $document->has_file() ) {
						$this->add_file( $document->get_relative_path() );
					}
				}
			}
		}

		if ( $this->is_last_step() ) {
			$this->merge();
		}
	}

	protected function get_download_button() {
		$download_button = '';

		if ( ( $path = $this->get_file() ) && file_exists( $path ) ) {

			$download_url = add_query_arg( array(
				'action'      => 'sab-download-bulk-documents',
				'object_type' => $this->get_object_type(),
				'bulk_action' => $this->get_action_name(),
				'force'       => 'no'
			), wp_nonce_url( admin_url(), 'sab-download-bulk-documents' ) );

			$download_button = '<a class="button button-primary bulk-download-button" style="margin-left: 1em;" href="' . $download_url . '" target="_blank">' . _x( 'Download', 'storeabill-core', 'woocommerce-germanized-pro' ) . '</a>';
		}

		return $download_button;
	}

	public function can_download() {
		return current_user_can( 'read_' . $this->get_object_type() );
	}

	public function get_success_message() {
		$download_button = $this->get_download_button();

		if ( empty( $download_button ) ) {
			return _x( 'No PDF files were available for merging.', 'storeabill-core', 'woocommerce-germanized-pro' );
		} else {
			return sprintf( _x( 'Successfully merged documents. %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $download_button );
		}
	}

	public function get_file() {
		$file = get_user_meta( get_current_user_id(), $this->get_file_option_name(), true );

		if ( $file && ! empty( $file ) ) {
			$file = sab_get_absolute_file_path( $file );
		}

		return $file;
	}

	protected function get_filename() {
		return "bulk-{$this->get_object_type()}.pdf";
	}

	protected function get_file_option_name() {
		return "_sab_{$this->get_object_type()}_{$this->get_action()}_path";
	}

	protected function get_files_option_name() {
		return "_sab_{$this->get_object_type()}_{$this->get_action()}_paths";
	}

	protected function get_files() {
		$files = (array) get_user_meta( get_current_user_id(), $this->get_files_option_name(), true );

		return array_filter( $files );
	}

	protected function merge() {
		try {
			$merger = sab_get_pdf_merger();

			foreach( $this->get_files() as $file_to_merge ) {
				$file_to_merge = sab_get_absolute_file_path( $file_to_merge );

				if ( file_exists( $file_to_merge ) ) {
					$merger->add( $file_to_merge );
				}
			}

			$new_file_stream = $merger->stream();

			if ( $new_file_path = sab_upload_document( $this->get_filename(), $new_file_stream, true, true ) ) {
				update_user_meta( get_current_user_id(), $this->get_file_option_name(), $new_file_path );
			}
		} catch( \Exception $e ) {}
	}

	protected function add_file( $path ) {
		$files    = $this->get_files();
		$abs_path = sab_get_absolute_file_path( $path );

		if ( $abs_path && file_exists( $abs_path ) ) {
			$files[] = $path;
		}

		update_user_meta( get_current_user_id(), $this->get_files_option_name(), $files );
	}

	public function get_limit() {
		return 15;
	}

	public function get_action_name() {
		return 'merge';
	}
}