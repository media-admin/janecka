<?php

use Vendidero\Germanized\Shipments\Admin\BulkActionHandler;

defined( 'ABSPATH' ) || exit;

/**
 * Shipment Order
 *
 * @class 		WC_GZD_Shipment_Order
 * @version		1.0.0
 * @author 		Vendidero
 */
class WC_GZDP_Admin_Packing_Slip_Bulk_Handler extends BulkActionHandler {

	protected $path = '';

	public function get_action() {
		return 'packing_slips';
	}

	public function get_limit() {
		return 5;
	}

	public function get_title() {
		return __( 'Generating packing slips...', 'woocommerce-germanized-pro' );
	}

	public function get_file() {
		$file = get_user_meta( get_current_user_id(), $this->get_file_option_name(), true );

		if ( $file && ! empty( $file ) ) {
			return sab_get_absolute_file_path( $file );
		}

		return '';
	}

	protected function update_file( $path ) {
		update_user_meta( get_current_user_id(), $this->get_file_option_name(), $path );
	}

	protected function get_file_option_name() {
		return "_sab_packing_slip_bulk_merge_path";
	}

	protected function get_files_option_name() {
		$action = sanitize_key( $this->get_action() );

		return "woocommerce_gzd_shipments_{$action}_bulk_files";
	}

	protected function get_files() {
		$files = get_user_meta( get_current_user_id(), $this->get_files_option_name(), true );

		if ( empty( $files ) || ! is_array( $files ) ) {
			$files = array();
		}

		return $files;
	}

	protected function add_file( $path ) {
		$files   = $this->get_files();
		$files[] = $path;

		update_user_meta( get_current_user_id(), $this->get_files_option_name(), $files );
	}

	public function reset( $is_new = false ) {
		parent::reset( $is_new );

		if ( $is_new ) {
			delete_user_meta( get_current_user_id(), $this->get_file_option_name() );
			delete_user_meta( get_current_user_id(), $this->get_files_option_name() );
		}
	}

	public function get_filename() {
		if ( $file = $this->get_file() ) {
			return basename( $file );
		}

		return '';
	}

	protected function get_download_button() {
		$download_button = '';

		if ( ( $path = $this->get_file() ) && file_exists( $path ) ) {

			$download_url = add_query_arg( array(
				'action'        => 'wc-gzdp-download-packing-slip-export',
				'force'         => 'no'
			), wp_nonce_url( admin_url(), 'wc-gzdp-download-packing-slips' ) );

			$download_button = '<a class="button button-primary bulk-download-button" style="margin-left: 1em;" href="' . $download_url . '" target="_blank">' . __( 'Download packing slips', 'woocommerce-germanized-pro' ) . '</a>';
		}

		return $download_button;
	}

	public function get_success_message() {
		$download_button = $this->get_download_button();

		return sprintf( __( 'Successfully generated packing slips. %s', 'woocommerce-germanized-pro' ), $download_button );
	}

	public function admin_after_error() {
		$download_button = $this->get_download_button();

		if ( ! empty( $download_button ) ) {
			echo '<div class="notice"><p>' . sprintf( __( 'Packing slips partially generated. %s', 'woocommerce-germanized-pro' ), $download_button ) . '</p></div>';
		}
	}

	public function is_last_step() {
		$current_step = (int) $this->get_step();
		$max_step     = (int) $this->get_max_step();

		if ( $max_step === $current_step ) {
			return true;
		}

		return false;
	}

	public function handle() {
		$current = $this->get_current_ids();

		if ( ! empty( $current ) ) {
			foreach( $current as $shipment_id ) {
				$packing_slip = wc_gzdp_get_packing_slip_by_shipment( $shipment_id );

				if ( ! $packing_slip ) {
					if ( $shipment = wc_gzd_get_shipment( $shipment_id ) ) {

						try {
							$result = \Vendidero\Germanized\Pro\StoreaBill\PackingSlips::sync_packing_slip( $shipment, true, true );

							if ( ! is_wp_error( $result ) ) {
								$packing_slip = \Vendidero\Germanized\Pro\StoreaBill\PackingSlips::get_packing_slip( $shipment );
							} else {
								foreach( $result->get_error_messages() as $message ) {
									$this->add_notice( sprintf( __( 'An error occurred while creating packing slip for %1$s: %2$s.', 'woocommerce-germanized-pro' ), '<a href="' . $shipment->get_edit_shipment_url() .'" target="_blank">' . sprintf( __( 'shipment #%d', 'woocommerce-germanized-pro' ), $shipment_id ) . '</a>', $message ), 'error' );
								}
							}
						} catch( Exception $e ) {
							$this->add_notice( sprintf( __( 'Error while creating packing slip for %s.', 'woocommerce-germanized-pro' ), '<a href="' . $shipment->get_edit_shipment_url() .'" target="_blank">' . sprintf( __( 'shipment #%d', 'woocommerce-germanized-pro' ), $shipment_id ) . '</a>' ), 'error' );
						}
					}
				}

				// Merge to bulk print/download
				if ( $packing_slip ) {
					$this->add_file( $packing_slip->get_path() );
				}
			}
		}

		if ( $this->is_last_step() ) {
			try {
				$merger   = sab_get_pdf_merger();
				$filename = apply_filters( 'woocommerce_gzdp_packing_slip_bulk_filename', 'packing-slip-export.pdf', $this );

				foreach( $this->get_files() as $file ) {
					if ( ! file_exists( $file ) ) {
						continue;
					}

					$merger->add( $file );
				}

				$new_file_stream = $merger->stream();

				if ( $new_file_path = sab_upload_document( $filename, $new_file_stream, true, true ) ) {
					$this->update_file( $new_file_path );
				}
			} catch( Exception $e ) {}
		}

		$this->update_notices();
	}
}