<?php

namespace Vendidero\StoreaBill\Admin;

use Vendidero\StoreaBill\Document\BulkActionHandler;
use Vendidero\StoreaBill\Document\Document;
use Vendidero\StoreaBill\ExternalSync\Helper;
use Vendidero\StoreaBill\Package;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin class.
 */
class Admin {

    protected static $bulk_handlers = array();

	/**
	 * Constructor.
	 */
	public static function init() {
		if ( isset( $_GET['action'] ) && ( 'sab-preview-document' === $_GET['action'] ) && isset( $_GET['document_id'], $_GET['_wpnonce'] ) ) { // WPCS: input var ok, CSRF ok.
			add_action( 'init', array( __CLASS__, 'preview_document' ) );
		}

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_scripts' ) );

		add_filter( 'set-screen-option', array( __CLASS__, 'set_screen_option' ), 10, 3 );
		add_filter( 'set_screen_option_woocommerce_page_sab_accounting_per_page', array( __CLASS__, 'set_screen_option' ), 10, 3 );

		Ajax::init();
		Exporters::init();
		Users::init();
	}

	public static function set_screen_option( $new_value, $option, $value ) {

		if ( in_array( $option, array( 'woocommerce_page_sab_accounting_per_page' ) ) ) {
			return absint( $value );
		}

		return $new_value;
	}

	public static function get_screen_ids() {
		$screen_ids = array(
			'woocommerce_page_sab-accounting',
			'woocommerce_page_sab-accounting-export',
            'users',
            'profile',
            'user-edit'
		);

		return $screen_ids;
	}

	public static function admin_scripts() {
		global $post;

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		wp_register_script( 'jquery-blockui', Package::get_assets_url() . '/libs/jquery-blockui/jquery.blockUI.min.js', array( 'jquery' ), '2.70', true );

		wp_register_script( 'storeabill_admin_enhanced_select', Package::get_build_url() . '/admin/enhanced-select.js', array( 'selectWoo' ), Package::get_version() );
		wp_register_script( 'storeabill_admin_global', Package::get_build_url() . '/admin/global.js', array( 'jquery', 'jquery-tiptip', 'jquery-blockui', 'storeabill_admin_enhanced_select' ), Package::get_version() );
		wp_register_script( 'storeabill_admin_bulk_actions', Package::get_build_url() . '/admin/bulk-actions.js', array( 'storeabill_admin_global' ), Package::get_version() );
		wp_register_script( 'storeabill_admin_settings', Package::get_build_url() . '/admin/settings.js', array( 'storeabill_admin_global' ), Package::get_version() );
		wp_register_script( 'storeabill_admin_table', Package::get_build_url() . '/admin/table.js', array( 'storeabill_admin_global' ), Package::get_version() );
		wp_register_script( 'storeabill_admin_templates', Package::get_build_url() . '/admin/templates.js', array( 'storeabill_admin_global' ), Package::get_version() );

		wp_localize_script(
			'storeabill_admin_global',
			'storeabill_admin_global_params',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			)
		);

		wp_localize_script(
			'storeabill_admin_enhanced_select',
			'storeabill_admin_enhanced_select_params',
			array(
				'i18n_no_matches'                 => _x( 'No matches found', 'enhanced select', 'storeabill' ),
				'i18n_ajax_error'                 => _x( 'Loading failed', 'enhanced select', 'storeabill' ),
				'i18n_input_too_short_1'          => _x( 'Please enter 1 or more characters', 'enhanced select', 'storeabill' ),
				'i18n_input_too_short_n'          => _x( 'Please enter %qty% or more characters', 'enhanced select', 'storeabill' ),
				'i18n_input_too_long_1'           => _x( 'Please delete 1 character', 'enhanced select', 'storeabill' ),
				'i18n_input_too_long_n'           => _x( 'Please delete %qty% characters', 'enhanced select', 'storeabill' ),
				'i18n_selection_too_long_1'       => _x( 'You can only select 1 item', 'enhanced select', 'storeabill' ),
				'i18n_selection_too_long_n'       => _x( 'You can only select %qty% items', 'enhanced select', 'storeabill' ),
				'i18n_load_more'                  => _x( 'Loading more results&hellip;', 'enhanced select', 'storeabill' ),
				'i18n_searching'                  => _x( 'Searching&hellip;', 'enhanced select', 'storeabill' ),
				'ajax_url'                        => admin_url( 'admin-ajax.php' ),
                'search_external_customers_nonce' => wp_create_nonce( 'sab-search-external-customers' ),
			)
		);

		if ( in_array( $screen_id, self::get_screen_ids() ) || self::is_settings_page() ) {
		    wp_enqueue_script( 'storeabill_admin_global' );

		    if ( 'woocommerce_page_sab-accounting' === $screen_id || 'users' === $screen_id ) {
                $object_type       = false;
			    $localization_args = array(
				    'ajax_url' => admin_url( 'admin-ajax.php' ),
				    'action'   => 'storeabill_admin_handle_bulk_action',
			    );

                if ( 'users' === $screen_id ) {
                    $object_type = 'customer';
                    $table_type  = 'users';

	                $localization_args['object_type']    = 'customer';
	                $localization_args['reference_type'] = 'woocommerce';
                } elseif( 'woocommerce_page_sab-accounting' === $screen_id ) {
	                global $current_document_type;

	                $object_type = $current_document_type;
	                $table_type  = 'document';

	                $localization_args['object_input_type_name'] = 'document_type';
                }

                if ( $object_type ) {
	                $bulk_actions           = array();
	                $available_bulk_actions = self::get_bulk_actions_handlers( $object_type );

	                if ( ! empty( $available_bulk_actions ) ) {
		                foreach( $available_bulk_actions as $handler ) {
			                $bulk_actions[ sanitize_key( $handler->get_action() ) ] = array(
				                'title' => $handler->get_title(),
				                'nonce' => wp_create_nonce( $handler->get_nonce_action() ),
                                'parse_ids_ascending' => $handler->parse_ids_ascending(),
				                'id_order_by_column'  => $handler->get_id_order_by_column()
			                );
		                }

		                $localization_args['bulk_actions'] = $bulk_actions;
		                $localization_args['table_type']   = $table_type;

		                wp_localize_script(
			                'storeabill_admin_bulk_actions',
			                'storeabill_admin_bulk_actions_params',
			                $localization_args
		                );

		                wp_enqueue_script( 'storeabill_admin_bulk_actions' );
                    }
                }

			    wp_localize_script(
				    'storeabill_admin_table',
				    'storeabill_admin_table_params',
				    array(
					    'ajax_url' => admin_url( 'admin-ajax.php' )
				    )
			    );

			    wp_enqueue_script( 'storeabill_admin_table' );
            }
		}

		if ( self::is_settings_page() ) {
			wp_localize_script(
				'storeabill_admin_settings',
				'storeabill_admin_settings_params',
				array(
					'ajax_url'                     => admin_url( 'admin-ajax.php' ),
					'preview_number_nonce'         => wp_create_nonce( 'sab-preview-formatted-document-number' ),
					'i18n_oauth_disconnect_notice' => _x( 'Do you really want to disconnect?', 'storeabill-core', 'woocommerce-germanized-pro' ),
				)
			);

			wp_enqueue_script( 'storeabill_admin_settings' );

			wp_localize_script(
				'storeabill_admin_templates',
				'storeabill_admin_templates_params',
				array(
					'ajax_url'                    => admin_url( 'admin-ajax.php' ),
                    'edit_templates_nonce'        => wp_create_nonce( 'sab-edit-document-template' ),
                    'i18n_delete_template_notice' => _x( 'Do you really want to delete the template?', 'storeabill-core', 'woocommerce-germanized-pro' )
				)
			);

			wp_enqueue_script( 'storeabill_admin_templates' );
        }
	}

	protected static function is_settings_page() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

	    return ( 'woocommerce_page_wc-settings' === $screen_id && isset( $_GET['tab'] ) && strpos( $_GET['tab'], 'storeabill' ) !== false );
    }

	public static function admin_styles() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		// Register admin styles.
		wp_register_style( 'storeabill_admin', Package::get_build_url() . '/admin/global-styles.css', array(), Package::get_version() );
		wp_register_style( 'storeabill_admin_accounting', Package::get_build_url() . '/admin/accounting-styles.css', array( 'storeabill_admin' ), Package::get_version() );

		// Admin styles.
		if ( in_array( $screen_id, self::get_screen_ids() ) || self::is_settings_page() ) {
			wp_enqueue_style( 'storeabill_admin' );
			wp_enqueue_style( 'storeabill_admin_accounting' );
		}
	}

	/**
	 * @param $object_type
	 *
	 * @return BulkActionHandler[]
	 */
	public static function get_bulk_actions_handlers( $object_type ) {
		if ( ! array_key_exists( $object_type, self::$bulk_handlers ) ) {

			self::$bulk_handlers[ $object_type ] = array();

			$handlers = array();

			if ( $document_type = sab_get_document_type( $object_type ) ) {
				$handlers = array(
					'merge' => '\Vendidero\StoreaBill\Document\BulkMerge'
				);
            }

            if ( 'invoice' === $object_type ) {
                $handlers = array_replace( $handlers, array(
                    'finalize' => '\Vendidero\StoreaBill\Invoice\BulkFinalize',
                    'cancel'   => '\Vendidero\StoreaBill\Invoice\BulkCancel'
                ) );
            }

            foreach( Helper::get_available_sync_handlers() as $handler ) {
                if ( $handler::is_object_type_supported( $object_type ) ) {
	                $handlers[ 'sync_' . $object_type . '_handler_' . $handler::get_name() ] = '\Vendidero\StoreaBill\ExternalSync\BulkSync';
                }
            }

			/**
			 * Filter to register new BulkActionHandler.
			 *
			 * @param array $handlers Array containing key => classname.
			 *
			 * @since 3.0.0
			 * @package Vendidero/Germanized/Shipments
			 */
			$handlers = apply_filters( "storeabill_{$object_type}_bulk_action_handlers", $handlers, $object_type );

			foreach( $handlers as $key => $handler ) {
			    if ( ! class_exists( $handler ) ) {
			        continue;
                }

				self::$bulk_handlers[ $object_type ][ $key ] = new $handler( array(
                    'id'          => $key,
                    'object_type' => $object_type
                ) );
			}
		}

		return self::$bulk_handlers[ $object_type ];
    }

	/**
	 * @param $action
	 * @param $document_type
	 *
	 * @return bool|BulkActionHandler
	 */
	public static function get_bulk_action_handler( $action, $object_type ) {
	    $action   = substr( $action, 0, 5 ) === 'bulk_' ? substr( $action, 5 ) : $action;
        $handlers = self::get_bulk_actions_handlers( $object_type );

        if ( array_key_exists( $action, $handlers ) ) {
            return $handlers[ $action ];
        }

        return false;
	}

	public static function get_document_preview_url( $document_id, $output_type = 'pdf' ) {
		return wp_nonce_url( add_query_arg( array( 'document_id' => $document_id, 'action' => 'sab-preview-document', 'output_type' => $output_type ), admin_url( 'index.php' ) ), 'sab-preview-document' );
	}

	public static function preview_document() {
		if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'sab-preview-document' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_storeabill' ) ) {
			wp_die( _x( 'Sorry, but you are not allowed to preview documents', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}

		$document_id = isset( $_GET['document_id'] ) ? absint( $_GET['document_id'] ) : 0;
		$output_type = isset( $_GET['output_type'] ) ? sab_clean( $_GET['output_type'] ) : 'pdf';

		if ( empty( $document_id ) ) {
			wp_die( _x( 'The document id is missing', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}

		if ( ! $document = sab_get_document( $document_id ) ) {
			wp_die( _x( 'The requested document does not exist', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}

		if ( 'html' === $output_type ) {
			echo $document->get_html();
			exit();
		} else {
			$result = $document->preview();

			if ( is_wp_error( $result ) ) {
				wp_die( $result->get_error_message() );
			}
		}
	}

	public static function render_accounting_page() {
		global $wp_list_table, $current_document_type, $current_document_type_object;
		?>
		<div class="wrap storeabill-accounting">
			<h1 class="wp-heading-inline"><?php echo _x( 'Accounting', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></h1>
            <?php if ( Exporters::export_allowed( $current_document_type ) ) : ?>
                <?php foreach( sab_get_export_types() as $export_type => $export_title ) : ?>
                    <?php if ( $exporter = sab_get_document_type_exporter( $current_document_type, $export_type ) ) : ?>
                        <a href="<?php echo esc_url( $exporter->get_admin_url() ); ?>" class="page-title-action"><?php printf( _x( 'Export %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $export_title ); ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
			<hr class="wp-header-end" />

            <nav class="nav-tab-wrapper sab-nav-tab-wrapper">
	            <?php foreach( sab_get_document_types( '', 'accounting' ) as $document_type ) :
		            $document_type_object = sab_get_document_type( $document_type );
		            ?>
                    <a class="nav-tab <?php echo ( $document_type === $current_document_type ? 'nav-tab-active' : '' ); ?>" href="<?php echo esc_url( add_query_arg( array( 'document_type' => $document_type ), admin_url( 'admin.php?page=sab-accounting' ) ) ); ?>"><?php echo sab_get_document_type_label( $document_type, 'plural' ); ?></a>
	            <?php endforeach; ?>
            </nav>

			<?php
			    $finished    = ( isset( $_GET['bulk_action_handling'] ) && 'finished' === $_GET['bulk_action_handling'] ) ? true : false;
			    $bulk_action = ( isset( $_GET['current_bulk_action'] ) ) ? sab_clean( $_GET['current_bulk_action'] ) : '';

			    if ( $finished && ( $handler = Admin::get_bulk_action_handler( $bulk_action, $current_document_type ) ) && check_admin_referer( $handler->get_done_nonce_action() ) ) {
				    $handler->finish();
                }

				$wp_list_table->output_notices();
				$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'updated', 'changed', 'deleted', 'trashed', 'untrashed' ), $_SERVER['REQUEST_URI'] );
			?>

            <div class="sab-bulk-action-wrapper">
                <h4 class="bulk-title"><?php _ex(  'Processing bulk actions...', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></h4>
                <div class="sab-bulk-notice-wrapper"></div>
                <progress class="sab-bulk-progress sab-progress-bar" max="100" value="0"></progress>
            </div>

			<?php $wp_list_table->views(); ?>

			<form id="posts-filter" method="get">

				<?php $wp_list_table->search_box( sprintf( _x( 'Search %s', 'storeabill-core', 'woocommerce-germanized-pro' ), sab_get_document_type_label( $current_document_type, 'plural' ) ), 'document' ); ?>

				<input type="hidden" name="document_status" class="document_status_page" value="<?php echo ! empty( $_GET['document_status'] ) ? esc_attr( sab_clean( $_GET['document_status'] ) ) : 'all'; ?>" />
				<input type="hidden" name="document_type" class="document_type" value="<?php echo esc_attr( $current_document_type ); ?>" />

				<input type="hidden" name="page" value="sab-accounting" />

				<?php $wp_list_table->display(); ?>
			</form>

			<div id="ajax-response"></div>
			<br class="clear" />
		</div>
		<?php
	}

	/**
	 * @param Document $document
	 * @param string $for
	 */
	public static function get_document_actions( $document, $for = 'table' ) {
	    $actions = array();

		if ( $document->has_file() ) {
			$actions['send'] = array(
				'url'     => wp_nonce_url( admin_url( 'admin-ajax.php?action=storeabill_admin_send_document&document_id=' . $document->get_id() ), 'sab-send-document' ),
				'name'    => $document->get_date_sent() ? sprintf( _x( 'Last sent on %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $document->get_date_sent()->date_i18n( sab_date_format() ) ) : _x( 'Send to customer', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'action'  => 'send',
                'classes' => $document->get_date_sent() ? 'inactive' : '',
			);
		}

		if ( is_a( $document, '\Vendidero\StoreaBill\Invoice\Invoice' ) ) {
			if ( ! $document->is_finalized() ) {
				$actions['preview'] = array(
					'url'    => esc_url( Admin::get_document_preview_url( $document->get_id() ) ),
					'name'   => _x( 'Preview', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'action' => 'preview',
					'target' => '_blank'
				);

				if ( current_user_can( "delete_{$document->get_type()}s" ) ) {
					$actions['delete'] = array(
						'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=storeabill_admin_delete_document&document_id=' . $document->get_id() ), 'sab-delete-document' ),
						'name'   => _x( 'Delete', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'action' => 'delete'
					);
				}

				if ( 'table' === $for ) {
					$actions['finalize'] = array(
						'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=storeabill_admin_finalize_invoice&document_id=' . $document->get_id() ), 'sab-finalize-invoice' ),
						'name'   => _x( 'Finalize', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'action' => 'finalize'
					);
				}
			} elseif ( $document->has_file() ) {
				$actions['download'] = array(
					'url'    => $document->get_download_url(),
					'name'   => _x( 'Download', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'action' => 'download',
					'target' => '_blank'
				);
			}

			if ( $document->is_finalized() && ! $document->has_file() ) {
				$actions['refresh'] = array(
					'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=storeabill_admin_refresh_document&document_id=' . $document->get_id() ), 'sab-refresh-document' ),
					'name'   => _x( 'Refresh', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'action' => 'refresh'
				);
			}

			if ( 'simple' === $document->get_invoice_type() ) {
				if ( $document->is_finalized() && $document->is_cancelable() ) {
					$actions['cancel'] = array(
						'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=storeabill_admin_cancel_invoice&document_id=' . $document->get_id() ), 'sab-cancel-invoice' ),
						'name'   => _x( 'Cancel', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'action' => 'cancel'
					);
				}

				if ( 'table' === $for && ( $document->is_finalized() && ! $document->is_paid() ) ) {
					$actions['mark_as_paid'] = array(
						'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=storeabill_admin_update_invoice_payment_status&status=complete&document_id=' . $document->get_id() ), 'sab-update-invoice-payment-status' ),
						'name'   => _x( 'Mark as paid', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'action' => 'mark_as_paid'
					);
				}
			}
		}

		return apply_filters( "storeabill_admin_{$document->get_type()}_actions", $actions, $document, $for );
	}

	public static function get_document_actions_html( $actions ) {
		$actions_html = '';

		foreach ( $actions as $action ) {
			if ( isset( $action['group'] ) ) {
				$actions_html .= '<div class="sab-action-button-group"><label>' . $action['group'] . '</label> <span class="sab-action-button-group__items">' . self::get_document_actions_html( $action['actions'] ) . '</span></div>';
			} elseif ( isset( $action['action'], $action['url'], $action['name'] ) ) {
				$target  = isset( $action['target'] ) ? $action['target'] : '_self';
				$content = '<span class="btn-content">' . esc_html( $action['name'] ) . '</span>';
				$classes = 'button sab-action-button sab-document-action-button';

				if ( isset( $action['icon'] ) && ! empty( $action['icon'] ) ) {
					$content  = '<img class="sab-action-icon" src="' . esc_url( $action['icon'] ) . '" />';
					$classes .= ' sab-action-button-has-icon';
				}

				if ( isset( $action['classes'] ) ) {
					$classes .= ' ' . $action['classes'];
				}

				$actions_html .= sprintf( '<a class="' . esc_attr( $classes ) . ' sab-tip sab-document-action-button-%1$s document-%1$s" href="%2$s" aria-label="%3$s" data-tip="%3$s" target="%4$s">%5$s</a>', esc_attr( $action['action'] ), esc_url( $action['url'] ), esc_attr( isset( $action['title'] ) ? $action['title'] : $action['name'] ), $target, $content );
			}
		}

		return $actions_html;
	}
}
