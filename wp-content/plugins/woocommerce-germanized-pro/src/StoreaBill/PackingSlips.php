<?php

namespace Vendidero\Germanized\Pro\StoreaBill;

use Vendidero\Germanized\Pro\StoreaBill\PackingSlip\Preview;
use Vendidero\Germanized\Pro\StoreaBill\PackingSlip\ProductItem;
use Vendidero\StoreaBill\Admin\Settings;
use Vendidero\StoreaBill\Compatibility\WPML;
use Vendidero\StoreaBill\Utilities\CacheHelper;

defined( 'ABSPATH' ) || exit;

class PackingSlips {

	public static function init() {
		// Register document types
		add_action( 'storeabill_registered_core_document_types', array( __CLASS__, 'register_document_type' ), 10 );
		add_filter( 'storeabill_document_item_classname', array( __CLASS__, 'register_document_items' ), 10, 3 );
		add_filter( 'storeabill_data_stores', array( __CLASS__, 'register_data_store' ), 10 );

		add_action( 'storeabill_woo_gzd_shipment_item_synced', array( __CLASS__, 'sync_shipment_item_product' ), 10, 2 );

		add_filter( 'storeabill_packing_slip_editor_templates', array( __CLASS__, 'register_template' ) );
		add_filter( 'storeabill_packing_slip_shortcode_handlers', array( __CLASS__, 'register_shortcode_handler' ), 10 );

		if ( self::is_enabled() ) {
			add_filter( 'storeabill_rest_api_get_rest_namespaces', array( __CLASS__, 'register_rest_controllers' ) );
			add_filter( 'storeabill_default_template_path', array( __CLASS__, 'register_default_template_path' ), 10, 2 );

			add_action( 'init', array( __CLASS__, 'setup_automation' ), 50 );
			add_action( "storeabill_packing_slip_rendered", array( __CLASS__, 'maybe_send_mail' ), 10 );

			/**
			 * Sync Packing Slips
			 */
			add_action( 'woocommerce_after_shipment_object_save', array( __CLASS__, 'maybe_sync_packing_slip' ) );
			add_action( 'woocommerce_gzd_shipment_deleted', array( __CLASS__, 'delete_packing_slip' ), 10, 2 );
			add_action( 'woocommerce_gzdp_packing_slip_auto_sync_callback', array( __CLASS__, 'auto_sync_callback' ), 10 );

			add_filter( 'storeabill_bundles_compatibility_document_types', array( __CLASS__, 'register_bundles_compatibility' ), 10 );

			/**
			 * WPML compatibility
			 */
			if ( WPML::is_active() ) {
				add_action( 'woocommerce_gzdp_before_sync_packing_slip', array( __CLASS__, 'maybe_switch_order_lang' ), 10, 1 );
				add_action( 'woocommerce_gzdp_after_sync_packing_slip', array( __CLASS__, 'maybe_restore_order_language' ), 10, 2 );
				add_action( 'woocommerce_gzdp_synced_packing_slip', array( __CLASS__, 'sync_packing_slip_language' ), 10, 2 );
			}
		}

		if ( is_admin() ) {
			add_filter( 'storeabill_admin_settings_sections', array( __CLASS__, 'register_setting_sections' ) );
			add_filter( 'storeabill_admin_settings', array( __CLASS__, 'register_settings' ), 10, 2 );
			add_action( 'admin_init', array( __CLASS__, 'download_packing_slips' ), 0 );

			add_filter( 'storeabill_available_document_number_placeholders', array( __CLASS__, 'register_number_placeholder' ), 10, 2 );

			if ( self::is_enabled() ) {
				add_action( 'woocommerce_gzd_shipments_meta_box_shipment_after_right_column', array( __CLASS__, 'packing_slip_meta_box' ), 20, 1 );

				add_filter( 'woocommerce_gzd_shipments_table_actions', array( __CLASS__, 'packing_slip_download_action' ), 10, 2 );
				add_filter( 'woocommerce_gzd_shipments_table_bulk_actions', array( __CLASS__, 'packing_slip_bulk_action' ), 10, 1 );
				add_filter( 'woocommerce_gzd_shipments_table_bulk_action_handlers', array( __CLASS__, 'register_packing_slip_bulk_handler' ) );
				add_filter( 'woocommerce_admin_order_actions', array( __CLASS__, 'order_download_actions' ), 10, 2 );

				Ajax::init();
			}
		}
	}

	/**
	 * Mark packing slips as being suitable for the bundles compatibility script.
	 *
	 * @param $document_types
	 *
	 * @return mixed
	 */
	public static function register_bundles_compatibility( $document_types ) {
		$document_types[] = 'packing_slip';

		return $document_types;
	}

	/**
	 * @param ShipmentItem $item
	 * @param ProductItem $document_item
	 */
	public static function sync_shipment_item_product( $item, $document_item ) {
		if ( $order_item = wc_gzd_get_order_item( $item->get_item()->get_order_item() ) ) {
			$document_item->update_meta_data( '_unit_price', $order_item->get_formatted_unit_price() );
			$document_item->update_meta_data( '_unit_price_excl', $order_item->get_formatted_unit_price( false ) );
			$document_item->update_meta_data( '_cart_desc', $order_item->get_cart_description() );
			$document_item->update_meta_data( '_delivery_time', $order_item->get_delivery_time() );
			$document_item->update_meta_data( '_product_units', $order_item->get_formatted_product_units() );
			$document_item->update_meta_data( '_unit', $order_item->get_formatted_unit() );
		}
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 */
	public static function maybe_switch_order_lang( $shipment ) {
		if ( ! WPML::translate_documents( 'packing_slip' ) ) {
			return;
		}

		if ( $order = $shipment->get_order() ) {
			if ( $order->get_meta( 'wpml_language', true ) ) {
				$lang = $order->get_meta( 'wpml_language', true );

				if ( ! empty( $lang ) ) {
					WPML::switch_language( $lang );
				}
			}
		}
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 */
	public static function maybe_restore_order_language( $shipment ) {
		if ( ! WPML::translate_documents( 'packing_slip' ) ) {
			return;
		}

		if ( $order = $shipment->get_order() ) {
			if ( $order->get_meta( 'wpml_language', true ) ) {
				$lang = $order->get_meta( 'wpml_language', true );

				if ( ! empty( $lang ) ) {
					WPML::restore_language();
				}
			}
		}
	}

	/**
	 * @param PackingSlip $packing_slip
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 */
	public static function sync_packing_slip_language( $packing_slip, $shipment ) {
		if ( ! WPML::translate_documents( 'packing_slip' ) ) {
			return;
		}

		if ( $order = $shipment->get_order() ) {
			if ( $order->get_meta( 'wpml_language', true ) ) {
				$lang = $order->get_meta( 'wpml_language', true );

				if ( ! empty( $lang ) ) {
					$packing_slip->update_meta_data( '_wpml_language', $lang );
				}
			}
		}
	}

	public static function download_packing_slips() {
		if ( isset( $_GET['action'] ) && 'wc-gzdp-download-packing-slip-export' === $_GET['action'] && wp_verify_nonce( $_REQUEST['_wpnonce'], 'wc-gzdp-download-packing-slips' ) ) {
			if ( current_user_can( 'read_packing_slip' ) ) {
				$handler = new \WC_GZDP_Admin_Packing_Slip_Bulk_Handler();

				if ( ( $file = $handler->get_file() ) && file_exists( $file ) ) {
					if ( ! isset( $_GET['force'] ) || 'no' === $_GET['force'] ) {
						$download_method = 'inline';
					} else {
						$download_method = 'force';
					}

					// Trigger download via one of the methods.
					do_action( 'storeabill_download_file_' . $download_method, $file, 'bulk.pdf' );
				}
			}
		}
	}

	public static function register_number_placeholder( $placeholder, $document_type ) {
		if ( 'packing_slip' === $document_type ) {
			$placeholder = array_merge( $placeholder, array(
				'{shipment_number}' => __( 'Shipment number (e.g. 123)', 'woocommerce-germanized-pro' ),
				'{order_number}'    => __( 'Order number (e.g. 1234)', 'woocommerce-germanized-pro' ),
			) );
		}

		return $placeholder;
	}

	public static function register_shortcode_handler( $handler ) {
		$handler[] = '\Vendidero\Germanized\Pro\StoreaBill\PackingSlip\Shortcodes';

		return $handler;
	}

	/**
	 * @param PackingSlip $packing_slip
	 */
	public static function maybe_send_mail( $packing_slip ) {
		if ( 'yes' !== get_option( 'woocommerce_gzdp_send_packing_slip_to_admin' ) ) {
			return;
		}

		if ( 'yes' === $packing_slip->get_meta( '_sent_to_admin' ) && apply_filters( 'woocommerce_gzdp_skip_resend_packing_slip_after_render', true, $packing_slip ) ) {
			return;
		}

		$result = $packing_slip->send_to_admin();

		if ( ! is_wp_error( $result ) ) {
			$packing_slip->update_meta_data( '_sent_to_admin', 'yes' );
			$packing_slip->save();
		}
	}

	public static function cancel_deferred_sync( $shipment_id ) {
		$queue = WC()->queue();

		/**
		 * Cancel outstanding events.
		 */
		$queue->cancel_all( 'woocommerce_gzdp_packing_slip_auto_sync_callback', array( $shipment_id ), 'woocommerce-gzdp-packing-slip-sync' );
	}

	public static function auto_sync_callback( $shipment_id ) {
		self::cancel_deferred_sync( $shipment_id );

		CacheHelper::prevent_caching();

		if ( $shipment = self::get_shipment( $shipment_id ) ) {
			self::sync_packing_slip( $shipment->get_shipment(), true, true );
		}
	}

	/**
	 * @param integer $shipment_id
	 */
	public static function queue_auto_sync_packing_slip( $shipment_id ) {
		$defer = sab_allow_deferring( 'auto' );

		if ( ! $shipment = self::get_shipment( $shipment_id ) ) {
			return;
		}

		/**
		 * Cancel outstanding events and queue new.
		 */
		self::cancel_deferred_sync( $shipment_id );

		if ( $defer ) {
			$queue = WC()->queue();

			$defer_args = array(
				'shipment_id' => $shipment_id,
			);

			$queue->schedule_single(
				time() + 50,
				'woocommerce_gzdp_packing_slip_auto_sync_callback',
				$defer_args,
				'woocommerce-gzdp-packing-slip-sync'
			);
		} else {
			self::sync_packing_slip( $shipment->get_shipment() );
		}
	}

	protected static function get_auto_statuses() {
		$statuses = array();

		if ( 'yes' === get_option( 'woocommerce_gzdp_packing_slip_auto' ) ) {
			$statuses = get_option( 'woocommerce_gzdp_packing_slip_auto_statuses' );

			if ( ! is_array( $statuses ) ) {
				$statuses = array( $statuses );
			}

			$statuses = array_filter( $statuses );

			foreach ( $statuses as $key => $status ) {
				$statuses[ $key ] = str_replace( 'gzd-', '', $status );
			}
		}

		return $statuses;
	}

	public static function setup_automation() {
		$statuses = self::get_auto_statuses();

		if ( 'yes' === get_option( 'woocommerce_gzdp_packing_slip_auto' ) && ! empty( $statuses ) ) {
			foreach( $statuses as $status ) {
				add_action( 'woocommerce_gzd_shipment_status_' . $status, array( __CLASS__, 'queue_auto_sync_packing_slip' ), 10, 1 );
			}
		}
	}

	/**
	 * This is being checked on load - do not call the main plugin here.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return 'yes' === get_option( 'woocommerce_gzdp_invoice_enable' );
	}

	public static function register_template( $templates ) {
		$templates['default'] = '\Vendidero\Germanized\Pro\StoreaBill\PackingSlip\DefaultTemplate';

		return $templates;
	}

	public static function register_default_template_path( $default_path, $template_name ) {
		/**
		 * Add default packing slip templates from plugin template path.
		 */
		if ( strpos( $template_name, 'packing-slip/' ) !== false ) {
			$default_path = trailingslashit( WC_germanized_pro()->plugin_path() ) . 'templates/';
		}

		return $default_path;
	}

	public static function register_rest_controllers( $controllers ) {
		$controllers['sab/v1']['packing_slips'] = '\Vendidero\Germanized\Pro\StoreaBill\PackingSlip\Controller';

		return $controllers;
	}

	/**
	 * @param $shipment
	 *
	 * @return bool|PackingSlip
	 */
	public static function get_packing_slip( $shipment ) {
		if ( $syncable_shipment = self::get_shipment( $shipment ) ) {
			return $syncable_shipment->get_packing_slip();
		}

		return false;
	}

	public static function delete_packing_slip( $shipment_id ) {
		if ( $syncable_shipment = self::get_shipment( $shipment_id ) ) {
			if ( $packing_slip = $syncable_shipment->get_packing_slip() ) {
				$packing_slip->delete( true );
			}
		}
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 */
	public static function maybe_sync_packing_slip( $shipment ) {
		/**
		 * Woo multilingual seems to have a weird way of
		 * saving/updating order item languages when opening the orders screen.
		 * This might lead to (unnecessary) sync calls within admin.
		 */
		if ( ( WPML::is_active() && is_admin() ) || apply_filters( 'woocommerce_gzdp_disable_auto_packing_slip_sync', false ) ) {
			return false;
		}

		if ( $syncable_shipment = self::get_shipment( $shipment ) ) {
			$packing_slip = $syncable_shipment->get_packing_slip();

			if ( ! $packing_slip ) {
				return false;
			} else {
				return self::sync_packing_slip( $shipment, true, false, true );
			}
		}

		return false;
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 */
	public static function sync_packing_slip( $shipment, $render = true, $force_render = false, $only_render_for_changes = false ) {
		$result = new \WP_Error( 'packing-slip-error', __( 'Error while generating packing slip.', 'woocommerce-germanized-pro' ) );

		if ( $syncable_shipment = self::get_shipment( $shipment ) ) {
			$packing_slip = $syncable_shipment->get_packing_slip();

			if ( ! $packing_slip ) {
				$packing_slip = new PackingSlip();
			}

			$syncable_shipment->sync( $packing_slip );

			/**
			 * Do only re-render in case the packing slip has changed.
			 */
			if ( $only_render_for_changes && ! $packing_slip->has_changed() ) {
				return true;
			}

			if ( $render ) {
				$packing_slip->save();

				if ( ! $force_render && sab_allow_deferring( 'render' ) ) {
					$result = $packing_slip->render_deferred();
				} else {
					$result = $packing_slip->render();
				}
			} else {
				$result = $packing_slip->save();
			}
		}

		return $result;
	}

	public static function get_shipment( $shipment ) {
		try {
			$syncable_shipment = new Shipment( $shipment );
		} catch( \Exception $e ) {
			$syncable_shipment = false;
		}

		return $syncable_shipment;
	}

	public static function register_data_store( $stores ) {
		return array_merge( $stores, array(
			'packing_slip'          => '\Vendidero\Germanized\Pro\StoreaBill\DataStores\PackingSlip',
			'shipment_product_item' => '\Vendidero\Germanized\Pro\StoreaBill\DataStores\ProductItem'
		) );
	}

	public static function register_document_items( $classname, $item_type, $item_id ) {
		if ( 'shipments_product' === $item_type ) {
			$classname = '\Vendidero\Germanized\Pro\StoreaBill\PackingSlip\ProductItem';
		}

		return $classname;
	}

	public static function register_document_type() {
		sab_register_document_type( 'packing_slip', array(
			'group'                     => 'shipments',
			'api_endpoint'              => 'packing_slips',
			'labels'                    => array(
				'singular' => __( 'Packing Slip', 'woocommerce-germanized-pro' ),
				'plural'   => __( 'Packing Slips', 'woocommerce-germanized-pro' ),
			),
			'class_name'                => '\Vendidero\Germanized\Pro\StoreaBill\PackingSlip',
			'admin_email_class_name'    => '\Vendidero\Germanized\Pro\StoreaBill\PackingSlip\Email',
			'preview_class_name'        => '\Vendidero\Germanized\Pro\StoreaBill\PackingSlip\Preview',
			'default_line_item_types'   => array( 'product' ),
			'default_status'            => 'closed',
			'available_line_item_types' => array( 'product' ),
			'supports'                  => array( 'items' ),
			'barcode_code_types'        => array(
				'document?data=order_number' => __( 'Order number', 'woocommerce-germanized-pro' ),
			),
			'shortcodes'                => array(
				'document' => array(
					array(
						'shortcode' => 'document?data=order_number',
						'title'     => __( 'Order number', 'woocommerce-germanized-pro' ),
					),
					array(
						'shortcode' => 'document?data=shipment_number',
						'title'     => __( 'Shipment number', 'woocommerce-germanized-pro' ),
					),
					array(
						'shortcode' => 'return_reasons?format=plain',
						'title'     => __( 'Return reasons', 'woocommerce-germanized-pro' ),
					),
				),
			),
			'additional_blocks'         => array(
				'storeabill/item-price',
				'storeabill/item-line-total',
			),
		) );
	}

	/**
	 * @param $actions
	 * @param \WC_Order $order
	 */
	public static function order_download_actions( $actions, $order ) {
		$shipments = wc_gzd_get_shipments_by_order( $order );

		foreach( $shipments as $shipment ) {
			if ( $packing_slip = wc_gzdp_get_packing_slip_by_shipment( $shipment ) ) {
				$actions["download-packing-slip-{$packing_slip->get_id()}"] = array(
					'url'       => $packing_slip->get_download_url(),
					'name'      => sprintf( _x( 'Download %s', 'woocommerce-germanized-pro' ), $packing_slip->get_title() ),
					'action'    => 'download'
				);
			}
		}

		return $actions;
	}

	public static function packing_slip_bulk_action( $actions ) {
		$actions['packing_slips'] = __( 'Generate and download packing slips', 'woocommerce-germanized-pro' );

		return $actions;
	}

	public static function packing_slip_download_action( $actions, $shipment ) {
		if ( $packing_slip = wc_gzdp_get_packing_slip_by_shipment( $shipment ) ) {
			$actions['download_packing_slip'] = array(
				'url'    => $packing_slip->get_download_url(),
				'name'   => sprintf( _x( 'Download %s', 'invoices', 'woocommerce-germanized-pro' ), $packing_slip->get_title() ),
				'action' => 'download-packing-slip download',
				'target' => '_blank'
			);
		} else {
			$actions['generate_packing_slip'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_gzdp_create_packing_slip&shipment_id=' . $shipment->get_id() ), 'wc-gzdp-create-packing-slip' ),
				'name'   => __( 'Generate Packing Slip', 'woocommerce-germanized-pro' ),
				'action' => 'generate-packing-slip generate',
			);
		}

		return $actions;
	}

	public static function register_packing_slip_bulk_handler( $handlers ) {
		$handlers['packing_slips'] = 'WC_GZDP_Admin_Packing_Slip_Bulk_Handler';

		return $handlers;
	}

	public static function packing_slip_meta_box( $the_shipment ) {
		$shipment     = $the_shipment;
		$packing_slip = wc_gzdp_get_packing_slip_by_shipment( $the_shipment );

		include WC_Germanized_pro()->plugin_path() . '/includes/admin/views/html-shipment-packing-slip.php';
	}

	public static function register_settings( $settings, $section = '' ) {
		if ( 'packing_slips' === $section ) {
			$settings = self::get_packing_slips_settings();
		}

		return $settings;
	}

	protected static function get_packing_slips_settings() {
		$settings = array(
			array( 'title' => '', 'type' => 'title', 'id' => 'packing_slip_settings' ),

			array(
				'title' 	     => __( 'Automation', 'woocommerce-germanized-pro' ),
				'desc' 		     => __( 'Automatically create packing slips to shipments.', 'woocommerce-germanized-pro' ),
				'id' 		     => 'woocommerce_gzdp_packing_slip_auto',
				'default'	     => 'yes',
				'type' 		     => 'sab_toggle',
			),

			array(
				'title' 	     => __( 'Shipment status(es)', 'woocommerce-germanized-pro' ),
				'desc' 		     => '<div class="sab-additional-desc">' . sprintf( __( 'Select one or more shipment statuses. A packing slip is generated as soon as a shipment reaches one of the statuses selected.', 'woocommerce-germanized-pro' ) ) . '</div>',
				'id' 		     => 'woocommerce_gzdp_packing_slip_auto_statuses',
				'default'	     => array( 'gzd-processing', 'gzd-shipped' ),
				'type'           => 'multiselect',
				'class'          => 'sab-enhanced-select',
				'options'        => wc_gzd_get_shipment_statuses(),
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzdp_packing_slip_auto' => ''
				),
			),

			array(
				'title' 	     => __( 'Send to admin', 'woocommerce-germanized-pro' ),
				'desc' 		     => sprintf( __( 'Send the packing slip via <a href="%s">email</a> after rendering.', 'woocommerce-germanized-pro' ), admin_url( 'admin.php?page=wc-settings&tab=email&section=storeabill_vendiderogermanizedprostoreabillpackingslipemail' ) ),
				'id'             => 'woocommerce_gzdp_send_packing_slip_to_admin',
				'default'	     => 'no',
				'type' 		     => 'sab_toggle',
			),

			array( 'type' => 'sectionend', 'id' => 'packing_slip_settings' ),

			array( 'title' => _x( 'Layout', 'woocommerce-germanized-pro' ), 'desc' => sprintf( _x( 'Manage your %1$s templates by using the visual editor <a href="%2$s" class="button button-secondary">Learn more</a>', 'woocommerce-germanized-pro' ), sab_get_document_type_label( 'packing_slip' ), AccountingHelper::template_help_link() ), 'type' => 'title', 'id' => 'packing_slip_layout_settings' ),

			array(
				'type'          => 'sab_document_templates',
				'document_type' => 'packing_slip',
				'title'         => __( 'Manage template', 'woocommerce-germanized-pro' )
			),

			array( 'type' => 'sectionend', 'id' => 'packing_slip_layout_settings' ),
		);

		$settings = array_merge( $settings, Settings::get_numbering_options( 'packing_slip' ) );

		return $settings;
	}

	public static function register_setting_sections( $sections ) {
		$sections['packing_slips'] = __( 'Packing Slips', 'woocommerce-germanized-pro' );

		return $sections;
	}
}