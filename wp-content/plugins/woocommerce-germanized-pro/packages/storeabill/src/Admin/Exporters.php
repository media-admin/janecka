<?php

namespace Vendidero\StoreaBill\Admin;

use Vendidero\StoreaBill\Interfaces\Exporter;
use Vendidero\StoreaBill\Package;

defined( 'ABSPATH' ) || exit;

class Exporters {

	protected static $exporters = array();

	/**
	 * Constructor.
	 */
	public static function init() {
		if ( ! self::export_allowed() ) {
			return;
		}

		add_action( 'admin_menu', array( __CLASS__, 'add_to_menus' ) );
		add_action( 'admin_head', array( __CLASS__, 'hide_from_menus' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_scripts' ), 20 );
		add_action( 'load-woocommerce_page_sab-accounting-export', array( __CLASS__, 'setup_export' ), 0 );
		add_action( 'admin_init', array( __CLASS__, 'download_export_file' ) );
	}

	public static function export_allowed( $document_type = '' ) {
		$can_export = current_user_can( 'export' );

		if ( $can_export && ! empty( $document_type ) ) {
			$can_export = current_user_can( 'edit_' . $document_type . 's' );
		}

		return $can_export;
	}

	public static function setup_export() {
		global $current_document_type, $current_document_type_object, $exporter;

		$current_document_type = isset( $_GET['document_type'] ) ? sab_clean( wp_unslash( $_GET['document_type'] ) ) : 'invoice';
		$export_type           = isset( $_GET['export_type'] ) ? sab_clean( wp_unslash( $_GET['export_type'] ) ) : 'csv';

		if ( ! in_array( $export_type, array_keys( sab_get_export_types() ) ) ) {
			$export_type = 'csv';
		}

		if ( ! $document_type_object = sab_get_document_type( $current_document_type ) ) {
			$current_document_type = 'invoice';
		}

		$current_document_type_object = sab_get_document_type( $current_document_type );

		if ( ! $exporter = sab_get_document_type_exporter( $current_document_type, $export_type ) ) {
			wp_die( _x( 'This exporter does not exist', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}
	}

	/**
	 * Add menu items for our custom exporters.
	 */
	public static function add_to_menus() {
		add_submenu_page( 'woocommerce', _x( 'Export documents', 'storeabill-core', 'woocommerce-germanized-pro' ), _x( 'Export documents', 'storeabill-core', 'woocommerce-germanized-pro' ), 'manage_storeabill', 'sab-accounting-export', array( __CLASS__, 'render_page' ) );
	}

	public static function render_page() {
		global $exporter, $current_document_type;

		include_once( Package::get_path() . '/includes/admin/views/html-export.php' );
	}

	/**
	 * Hide menu items from view so the pages exist, but the menu items do not.
	 */
	public static function hide_from_menus() {
		global $submenu;

		if ( isset( $submenu['woocommerce'] ) ) {
			foreach ( $submenu['woocommerce'] as $key => $menu ) {
				if ( 'sab-accounting-export' === $menu[2] ) {
					unset( $submenu['woocommerce'][ $key ] );
				}
			}
		}
	}

	/**
	 * Serve the generated file.
	 */
	public static function download_export_file() {
		$document_type = isset( $_GET['document_type'] ) ? sab_clean( wp_unslash( $_GET['document_type'] ) ) : 'invoice';
		$export_type   = isset( $_GET['export_type'] ) ? sab_clean( wp_unslash( $_GET['export_type'] ) ) : 'csv';

		if ( isset( $_GET['action'], $_GET['nonce'] ) && 'sab-download-export' === wp_unslash( $_GET['action'] ) && ( $exporter = sab_get_document_type_exporter( $document_type, $export_type ) ) ) {
			if ( wp_verify_nonce( wp_unslash( $_GET['nonce'] ), $exporter->get_nonce_download_action() ) ) {

				if ( ! empty( $_GET['filename'] ) ) { // WPCS: input var ok.
					$exporter->set_filename( wp_unslash( $_GET['filename'] ) ); // WPCS: input var ok, sanitization ok.
				}

				$exporter->export();
			}
		}
	}

	/**
	 * Enqueue scripts.
	 */
	public static function admin_scripts() {
		/**
		 * @var Exporter $exporter
		 */
		global $current_document_type, $exporter;

		wp_register_script( 'storeabill_admin_export', Package::get_build_url() . '/admin/export.js', array( 'storeabill_admin_global', 'jquery-ui-datepicker' ), Package::get_version() );

		if ( $exporter ) {
			wp_localize_script(
				'storeabill_admin_export',
				'storeabill_admin_export_params',
				array(
					'export_nonce'  => wp_create_nonce( $exporter->get_nonce_action() ),
					'ajax_url'      => admin_url( 'admin-ajax.php' ),
					'document_type' => $exporter->get_document_type(),
					'type'          => $exporter->get_type(),
					'extension'     => $exporter->get_file_extension(),
				)
			);

			/**
			 * Import UI style for range datepicker.
			 */
			wp_enqueue_style( 'jquery-ui-style' );
		}
	}
}