<?php

namespace Vendidero\StoreaBill\WooCommerce\Admin;

use Vendidero\StoreaBill\Admin\Settings;
use Vendidero\StoreaBill\Document\BulkActionHandler;
use Vendidero\StoreaBill\Package;
use Vendidero\StoreaBill\WooCommerce\Automation;
use Vendidero\StoreaBill\WooCommerce\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin class.
 */
class Admin {

    protected static $bulk_handlers = null;

	/**
	 * Constructor.
	 */
	public static function init() {
		Fields::init();
		Ajax::init();

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_styles' ), 15 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_scripts' ), 15 );

		if ( ! Package::enable_accounting() ) {
		    return;
        }

		add_action( 'admin_notices', array( __CLASS__, 'setting_warning' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_adjust_setting' ), 10 );

		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ), 35 );
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ), 15 );
		add_action( 'load-woocommerce_page_sab-accounting', array( __CLASS__, 'setup_table' ), 0 );

		/**
		 * Render bulk progress
		 */
		add_action( 'manage_posts_extra_tablenav', array( __CLASS__, 'render_bulk_actions' ), 150, 1 );
		add_filter( 'bulk_actions-edit-shop_order', array( __CLASS__, 'add_bulk_actions' ) );
		add_filter( 'storeabill_shop_order_bulk_action_handlers', array( __CLASS__, 'register_bulk_actions' ), 10 );

		/**
		 * Handle settings saving for sync handlers
		 */
        add_action( 'woocommerce_update_options_germanized-storeabill', array( __CLASS__, 'update_sync_handlers' ), 10 );

		/**
		 * Add download actions to Woo order table
		 */
		add_filter( 'woocommerce_admin_order_actions', array( __CLASS__, 'order_download_actions' ), 5, 2 );
	}

	public static function maybe_adjust_setting() {
	    if ( current_user_can( 'manage_woocommerce' ) && isset( $_GET['action'], $_GET['_wpnonce'] ) && 'sab-woo-round-tax-at-subtotal' === $_GET['action'] && wp_verify_nonce( $_GET['_wpnonce'], 'sab-woo-round-tax-at-subtotal' ) ) {
            $redirect = remove_query_arg( array( '_wpnonce', 'action' ) );
		    $incl_tax = 'yes' === get_option( 'woocommerce_prices_include_tax' );

            if ( wc_tax_enabled() ) {
                if ( $incl_tax ) {
                    update_option( 'woocommerce_tax_round_at_subtotal', 'yes' );
                } else {
	                update_option( 'woocommerce_tax_round_at_subtotal', 'no' );
                }
            }

            wp_safe_redirect( $redirect );
        }
    }

	public static function setting_warning() {
	    $calc_tax            = wc_tax_enabled();
	    $incl_tax            = 'yes' === get_option( 'woocommerce_prices_include_tax' );
	    $notice              = false;
	    $adjust_setting_link = wp_nonce_url( add_query_arg( array( 'action' => 'sab-woo-round-tax-at-subtotal' ) ), 'sab-woo-round-tax-at-subtotal' );

	    if ( current_user_can( 'manage_woocommerce' ) && $calc_tax ) {
	        if ( $incl_tax && 'yes' !== get_option( 'woocommerce_tax_round_at_subtotal' ) ) {
	            $notice = sprintf( _x( 'Your WooCommerce tax settings are inconsistent. Please make sure to <a href="%s">enable rounding tax at subtotal</a> to avoid rounding differences.', 'storeabill-core', 'woocommerce-germanized-pro' ), $adjust_setting_link );
	        } elseif ( ! $incl_tax && 'no' !== get_option( 'woocommerce_tax_round_at_subtotal' ) ) {
		        $notice = sprintf( _x( 'Your WooCommerce tax settings are inconsistent. Please make sure to <a href="%s">disable rounding tax at subtotal</a> to avoid rounding differences.', 'storeabill-core', 'woocommerce-germanized-pro' ), $adjust_setting_link );
	        }

	        if ( false !== $notice ) {
	            ?>
                <div class="notice notice-error error">
                    <p><?php echo $notice; ?></p>
                </div>
                <?php
            }
        }
    }

	/**
	 * @param $actions
	 * @param \WC_Order $order
	 */
	public static function order_download_actions( $actions, $order ) {
	    $invoices = Helper::get_invoices( $order->get_id() );

	    foreach ( $invoices as $invoice ) {
		    $actions["download-invoice-{$invoice->get_id()}"] = array(
			    'url'       => $invoice->get_download_url(),
			    'name'      => sprintf( _x( 'Download %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $invoice->get_title() ),
			    'action'    => "download"
		    );
        }

	    return $actions;
    }

	public static function register_bulk_actions( $handlers ) {
		$handlers = array_merge( array(
			'sync_invoices'  => '\Vendidero\StoreaBill\WooCommerce\BulkSync',
			'merge_invoices' => '\Vendidero\StoreaBill\WooCommerce\BulkDownload'
		), $handlers );

		return $handlers;
    }

	public static function update_sync_handlers() {
	    global $current_section;

	    if ( $current_section && 'sync_handlers' === $current_section ) {
	        if ( $sync_handler = Settings::get_current_sync_handler_name() ) {
		        if ( $handler = \Vendidero\StoreaBill\ExternalSync\Helper::get_sync_handler( $sync_handler ) ) {
		            $handler->process_admin_settings();

		            if ( $handler->get_setting_errors() ) {
		                foreach( $handler->get_setting_errors() as $error ) {
		                    \WC_Admin_Settings::add_error( $error );
                        }
                    }
		        }
            }
        }
    }

	public static function add_bulk_actions( $actions ) {
	    foreach( \Vendidero\StoreaBill\Admin\Admin::get_bulk_actions_handlers( 'shop_order' ) as $handler ) {
	        $actions[ $handler->get_action() ] = $handler->get_title();
        }

	    return $actions;
    }

	public static function render_bulk_actions( $which ) {
		$screen = get_current_screen();

		if ( 'top' === $which && $screen && 'shop_order' === $screen->post_type ) {
			$finished    = ( isset( $_GET['bulk_action_handling'] ) && 'finished' === $_GET['bulk_action_handling'] ) ? true : false;
			$bulk_action = ( isset( $_GET['current_bulk_action'] ) ) ? sab_clean( $_GET['current_bulk_action'] ) : '';

			if ( $finished && ( $handler = \Vendidero\StoreaBill\Admin\Admin::get_bulk_action_handler( $bulk_action, 'shop_order' ) ) && check_admin_referer( $handler->get_done_nonce_action() ) ) {
			    $handler->finish();
			}
			?>
			<div class="sab-bulk-action-wrapper">
				<h4 class="bulk-title"><?php _ex(  'Processing bulk actions...', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></h4>
				<div class="sab-bulk-notice-wrapper"></div>
				<progress class="sab-bulk-progress sab-progress-bar" max="100" value="0"></progress>
			</div>
			<?php
		}
	}

	public static function setup_table() {
		global $wp_list_table, $current_document_type, $current_document_type_object;

		$current_document_type = isset( $_GET['document_type'] ) ? sab_clean( wp_unslash( $_GET['document_type'] ) ) : 'invoice';

		if ( ! $document_type_object = sab_get_document_type( $current_document_type ) ) {
			$current_document_type = 'invoice';
		}

		$current_document_type_object = sab_get_document_type( $current_document_type );

		$wp_list_table = new $current_document_type_object->admin_table_class_name();
		$doaction      = $wp_list_table->current_action();

		if ( $doaction ) {
			/**
			 * This nonce is dynamically constructed by WP_List_Table and uses
			 * the normalized plural argument.
			 */
			check_admin_referer( 'bulk-' . sanitize_key( sab_get_document_type_label( $document_type_object, 'plural' ) ) );

			$pagenum       = $wp_list_table->get_pagenum();
			$parent_file   = $wp_list_table->get_main_page();
			$sendback      = remove_query_arg( array( 'deleted', 'ids', 'changed', 'bulk_action' ), wp_get_referer() );

			if ( ! $sendback ) {
				$sendback = admin_url( $parent_file );
			}

			$sendback       = add_query_arg( 'paged', $pagenum, $sendback );
			$document_ids   = array();

			if ( isset( $_REQUEST['ids'] ) ) {
				$document_ids = explode( ',', $_REQUEST['ids'] );
			} elseif ( ! empty( $_REQUEST['document'] ) ) {
				$document_ids = array_map( 'intval', $_REQUEST['document'] );
			}

			if ( ! empty( $document_ids ) ) {
				$sendback = $wp_list_table->handle_bulk_actions( $doaction, $document_ids, $sendback );
			}

			$sendback = remove_query_arg( array( 'action', 'action2', '_status', 'bulk_edit', 'document' ), $sendback );

			wp_redirect( $sendback );
			exit();

		} elseif ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
			wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
			exit;
		}

		$wp_list_table->set_bulk_notice();
		$wp_list_table->prepare_items();

		add_screen_option( 'per_page' );
	}

	public static function get_screen_ids() {
		$screen_ids = array();

		foreach ( wc_get_order_types() as $type ) {
			$screen_ids[] = $type;
			$screen_ids[] = 'edit-' . $type;
		}

		return $screen_ids;
	}

	public static function admin_styles() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		wp_register_style( 'storeabill_orders_admin', Package::get_build_url() . '/admin/order-styles.css', array( 'storeabill_admin' ), Package::get_version() );

		// Admin styles for WC pages only.
		if ( in_array( $screen_id, self::get_screen_ids() ) ) {
			if ( strpos( $screen_id, 'order' ) !== false ) {
				wp_enqueue_style( 'storeabill_orders_admin' );
			}
		}
	}

	public static function admin_scripts() {
		global $post;

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		wp_register_script( 'storeabill_woo_admin_edit_order', Package::get_build_url() . '/admin/edit-order.js', array( 'woocommerce_admin', 'storeabill_admin_global', 'wc-admin-order-meta-boxes' ), Package::get_version() );
		wp_register_script( 'storeabill_woo_admin_bulk_actions', Package::get_build_url() . '/admin/bulk-actions.js', array( 'woocommerce_admin', 'storeabill_admin_global' ), Package::get_version() );

		// Orders.
		if ( in_array( str_replace( 'edit-', '', $screen_id ), wc_get_order_types( 'order-meta-boxes' ) ) ) {

			wp_enqueue_script( 'storeabill_woo_admin_edit_order' );

			wp_localize_script(
				'storeabill_woo_admin_edit_order',
				'storeabill_admin_edit_order_params',
				array(
					'ajax_url'                    => admin_url( 'admin-ajax.php' ),
					'edit_documents_nonce'        => wp_create_nonce( 'sab-edit-woo-documents' ),
					'order_id'                    => isset( $post->ID ) ? $post->ID : '',
					'i18n_delete_document_notice' => _x( 'Do you really want to delete the document?', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'i18n_cancel_invoice_notice'  => _x( 'Do you really want to cancel the invoice?', 'storeabill-core', 'woocommerce-germanized-pro' ),
				)
			);
		}

		if ( 'edit-shop_order' === $screen_id ) {
			wp_enqueue_script( 'storeabill_woo_admin_bulk_actions' );

			$bulk_actions = array();

			foreach( \Vendidero\StoreaBill\Admin\Admin::get_bulk_actions_handlers( 'shop_order' ) as $handler ) {
				$bulk_actions[ sanitize_key( $handler->get_action() ) ] = array(
					'title' => $handler->get_title(),
					'nonce' => wp_create_nonce( $handler->get_nonce_action() ),
					'parse_ids_ascending' => $handler->parse_ids_ascending(),
					'id_order_by_column'  => $handler->get_id_order_by_column()
				);
			}

			wp_localize_script(
				'storeabill_woo_admin_bulk_actions',
				'storeabill_admin_bulk_actions_params',
				array(
					'ajax_url'               => admin_url( 'admin-ajax.php' ),
					'bulk_actions'           => $bulk_actions,
					'table_type'             => 'post',
					'object_input_type_name' => 'post_type',
				)
			);
		}
	}

	public static function add_meta_boxes() {
	    global $pagenow;

		/**
		 * Hide invoice panel from new orders.
		 */
	    if ( 'post-new.php' === $pagenow ) {
	        return;
	    }

		foreach ( wc_get_order_types( 'order-meta-boxes' ) as $type ) {
		    if ( apply_filters( "storeabill_woo_order_type_{$type}_add_invoice_meta_box", true ) ) {
			    add_meta_box( 'storeabill-invoices', _x( 'Invoices', 'storeabill-core', 'woocommerce-germanized-pro' ), array( InvoiceMetaBox::class, 'output' ), $type, 'normal', 'high' );
		    }
		}
	}

	public static function add_menu() {
		add_submenu_page( 'woocommerce', _x( 'Accounting', 'storeabill-core', 'woocommerce-germanized-pro' ), _x( 'Accounting', 'storeabill-core', 'woocommerce-germanized-pro' ), 'manage_storeabill', 'sab-accounting', array( 'Vendidero\StoreaBill\Admin\Admin', 'render_accounting_page' ) );
	}
}