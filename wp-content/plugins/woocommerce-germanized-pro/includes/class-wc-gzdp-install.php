<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use \Vendidero\Germanized\Pro\Packages;
use Vendidero\StoreaBill\Document\FirstPageTemplate;
use Vendidero\StoreaBill\TaxRate;

if ( ! class_exists( 'WC_GZDP_Install' ) ) :

/**
 * Installation related functions and hooks
 *
 * @class 		WC_GZD_Install
 * @version		1.0.0
 * @author 		Vendidero
 */
class WC_GZDP_Install {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		add_action( 'admin_init', array( __CLASS__, 'check_version' ), 5 );
		add_action( 'admin_init', array( __CLASS__, 'setup_redirect' ), 10 );

		add_action( 'admin_notices', array( __CLASS__, 'legacy_invoice_import_notice' ), 20 );
		add_action( 'admin_post_wc_gzdp_legacy_invoice_import_start', array( __CLASS__, 'legacy_invoice_import_submit' ), 10 );
		add_action( 'admin_post_wc_gzdp_legacy_invoice_import_cancel', array( __CLASS__, 'legacy_invoice_import_cancel_submit' ), 10 );

		/**
		 * Listen to import queues
		 */
		add_action( 'woocommerce_gzdp_legacy_invoice_import', array( __CLASS__, 'legacy_invoice_import' ), 10, 2 );
	}

	public static function setup_redirect() {
        if ( get_option( '_wc_gzdp_setup_wizard_redirect' ) ) {

	        // Bail if activating from network, or bulk, or within an iFrame, or AJAX (e.g. plugins screen)
	        if ( is_network_admin() || isset( $_GET['activate-multi'] ) || defined( 'IFRAME_REQUEST' ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
		        return;
	        }

	        if ( ( isset( $_REQUEST['action'] ) && 'upgrade-plugin' == $_REQUEST['action'] ) && ( isset( $_REQUEST['plugin'] ) && strstr( $_REQUEST['plugin'], 'woocommerce-germanized-pro.php' ) ) ) {
		        return;
	        }

            delete_option( '_wc_gzdp_setup_wizard_redirect' );
            wp_safe_redirect( admin_url( 'admin.php?page=wc-gzdp-setup' ) );
            exit();
        }
    }

	/**
	 * check_version function.
	 *
	 * @access public
	 * @return void
	 */
	public static function check_version() {
		if ( ! defined( 'IFRAME_REQUEST' ) && ( get_option( 'woocommerce_gzdp_version' ) != WC_germanized_pro()->version ) ) {
			self::install();
		}
	}

	public static function create_capabilities() {
		global $wp_roles;

		$capabilities = self::get_core_capabilities();

		foreach ( $capabilities as $cap_group ) {
			foreach ( $cap_group as $cap ) {
				$wp_roles->add_cap( 'shop_manager', $cap );
				$wp_roles->add_cap( 'administrator', $cap );
			}
		}
	}

	/**
	 * Get capabilities for WooCommerce - these are assigned to admin/shop manager during installation or reset.
	 *
	 * @return array
	 */
	private static function get_core_capabilities() {
		$capabilities = array();

		$capability_types = array(
			'packing_slip',
			'post_document'
		);

		foreach ( $capability_types as $capability_type ) {
			$capabilities[ $capability_type ] = \Vendidero\StoreaBill\Install::get_capabilities( $capability_type );
		}

		return $capabilities;
	}

	/**
	 * Install WC_Germanized
	 */
	public static function install() {
		if ( ! function_exists( 'WC' ) ) {
			wp_die( sprintf( __( 'Please install and activate <a href="%s" target="_blank">WooCommerce</a> before installing Germanized Pro. Thank you!', 'woocommerce-germanized-pro' ), 'http://wordpress.org/plugins/woocommerce/' ) );
		}

		self::create_capabilities();
		self::create_cron_jobs();
		self::install_packages();
		self::create_options();
		
		// Queue upgrades
		$current_version    = get_option( 'woocommerce_gzdp_version', null );
		$current_db_version = get_option( 'woocommerce_gzdp_db_version', null );
		$is_install         = ( ! $current_version ) ? true : false;

		if ( ! $current_version ) {
            update_option( '_wc_gzdp_setup_wizard_redirect', 1 );
        } else {
			self::update();
        }

        self::update_db_version();

		// Update version
		update_option( 'woocommerce_gzdp_version', WC_germanized_pro()->version );

		// Update activation date
		update_option( 'woocommerce_gzdp_activation_date', date( 'Y-m-d' ) );

		// Unregister installation
		delete_option( '_wc_gzdp_do_install' );

		if ( $is_install ) {
			do_action( 'woocommerce_gzdp_installed' );
		} else {
			do_action( 'woocommerce_gzdp_updated' );
		}
	}

	protected static function create_cron_jobs() {
	    if ( ! did_action( 'init' ) ) {
	        add_action( 'init', array( __CLASS__, 'register_cron_jobs' ), 15 );
        } else {
	        self::register_cron_jobs();
        }
    }

    public static function register_cron_jobs() {
	    if ( function_exists( 'as_next_scheduled_action' ) && false === as_next_scheduled_action( 'woocommerce_gzdp_check_generator_versions' ) ) {
		    as_schedule_recurring_action( strtotime( 'midnight tonight' ), DAY_IN_SECONDS, 'woocommerce_gzdp_check_generator_versions' );
	    }
    }

	protected static function memory_exceeded() {
		$memory_limit   = self::get_memory_limit() * 0.9; // 90% of max memory
		$current_memory = memory_get_usage( true );
		$return         = false;

		if ( $current_memory >= $memory_limit ) {
			$return = true;
		}

		return $return;
	}

	protected static function get_memory_limit() {
		if ( function_exists( 'ini_get' ) ) {
			$memory_limit = ini_get( 'memory_limit' );
		} else {
			// Sensible default.
			$memory_limit = '128M';
		}

		if ( ! $memory_limit || -1 === $memory_limit ) {
			// Unlimited, set to 32GB.
			$memory_limit = '32000M';
		}

		$memory_limit = trim( $memory_limit );

		switch ( substr( $memory_limit, -1 ) ) {
			case 'M': case 'm': return intval( $memory_limit ) * 1048576;
			case 'K': case 'k': return intval( $memory_limit ) * 1024;
			case 'G': case 'g': return intval( $memory_limit ) * 1073741824;
			default: return intval( $memory_limit ) * 1048576;
		}
	}

	public static function legacy_invoice_import_notice() {

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( 'yes' === get_option( '_wc_gzdp_legacy_invoice_import_running' ) ) {
		    $errors   = self::get_legacy_invoice_import_errors();
		    $has_next = WC()->queue()->get_next( 'woocommerce_gzdp_legacy_invoice_import' );
			?>
			<div id="message" class="notice">
				<p>
					<?php printf( __( 'Germanized Pro legacy invoice import is running. Check the <a href="%1$s">logs</a> for further information. You can <a href="%2$s" onclick="%3$s">cancel</a> the import at any time.', 'woocommerce-germanized-pro' ), admin_url( 'admin.php?page=wc-status&tab=logs' ), wp_nonce_url( admin_url( 'admin-post.php?action=wc_gzdp_legacy_invoice_import_cancel' ), 'wc-gzdp-legacy-invoice-import-cancel' ), 'return confirm(\'' . __( 'Do you really want to cancel the import?', 'woocommerce-germanized-pro' ) . '\')' ); ?>

                    <?php if ( get_option( '_wc_gzdp_show_invoice_automation_upgrade_disabled_notice' ) ) : ?>
						<?php printf( __( 'We\'ve temporarily disabled automatic invoice creation to prevent duplicates. After the import is finished (you\'ll be notified) you might want to <a href="%s">re-enable the setting</a>.', 'woocommerce-germanized-pro' ), admin_url( 'admin.php?page=wc-settings&tab=germanized-storeabill' ) ); ?>
				    <?php endif; ?>
                </p>
                <?php if ( ! $has_next ) : ?>
                    <p>
	                    <?php printf( __( 'Seems like the importer has failed or stop. You\'ll might need to <a href="%s">restart</a> the importer.', 'woocommerce-germanized-pro' ), admin_url( 'admin.php?page=wc-status&tab=germanized' ) ); ?>
                    </p>
                <?php endif; ?>
                <?php if ( ! empty( $errors ) ) : ?>
                    <?php include WC_GERMANIZED_PRO_ABSPATH . "includes/admin/views/html-import-legacy-invoices-errors.php"; ?>
                <?php endif; ?>
			</div>
			<?php
		} elseif ( get_option( '_wc_gzdp_needs_legacy_invoice_import' ) ) {
			?>
			<div id="message" class="notice">
				<h3><?php _e( 'Germanized Pro document import', 'woocommerce-germanized-pro' ); ?></h3>
				<p>
					<?php printf( __( '3.0 is a major update with exciting <a href="%1$s" target="_blank">new features</a>. To make your existing documents (invoices, cancellations, packing slips) available within the latest version we will need to import and convert them. Depending on your number of documents that might take some time. The import will be queued and scheduled (processing 10 documents at a time). You can optionally choose to import documents starting from a certain year only. We will not delete old data to make sure no data is lost.', 'woocommerce-germanized-pro' ), 'https://vendidero.de/germanized-pro-3-0' ); ?>
				</p>
				<?php include WC_GERMANIZED_PRO_ABSPATH . "includes/admin/views/html-import-legacy-invoices-form.php"; ?>
			</div>
			<?php
		} elseif ( get_option( '_wc_gzdp_legacy_invoice_import_finished' ) ) {
			$errors = self::get_legacy_invoice_import_errors();
			?>
            <div id="message" class="notice updated">
                <h3><?php _e( 'Germanized Pro document import ready', 'woocommerce-germanized-pro' ); ?></h3>
                <p>
	                <?php printf( __( 'Your documents have been imported successfully. Check out the new <a href="%s">accounting page</a>.', 'woocommerce-germanized-pro' ), admin_url( 'admin.php?page=sab-accounting' ) ); ?>
                </p>
                <?php if ( get_option( '_wc_gzdp_show_invoice_automation_upgrade_disabled_notice' ) ) : ?>
                    <p>
		                <?php printf( __( 'During import we\'ve disabled automatic invoice creation to prevent duplicates. After you\'ve reviewed your document templates you might want to <a href="%s">re-enable the setting</a>.', 'woocommerce-germanized-pro' ), admin_url( 'admin.php?page=wc-settings&tab=germanized-storeabill' ) ); ?>
                    </p>
                <?php endif; ?>
	            <?php if ( ! empty( $errors ) ) : ?>
		            <?php include WC_GERMANIZED_PRO_ABSPATH . "includes/admin/views/html-import-legacy-invoices-errors.php"; ?>
	            <?php endif; ?>
                <p class="button-wrapper">
                    <a class="button button-secondary" href="<?php echo wp_nonce_url( admin_url( 'admin-post.php?action=wc_gzdp_legacy_invoice_import_cancel' ), 'wc-gzdp-legacy-invoice-import-cancel' ); ?>"><?php _e( 'Remove notice', 'woocommerce-germanized-pro' ); ?></a>
                </p>
            </div>
			<?php
		}
	}

	protected static function get_legacy_invoice_import_errors() {
		$errors = get_option( '_wc_gzdp_legacy_invoice_import_errors' );

		if ( empty( $errors ) || ! is_array( $errors ) ) {
			$errors = array();
		}

		return $errors;
	}

	public static function legacy_invoice_import_cancel_submit() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'wc-gzdp-legacy-invoice-import-cancel' ) ) {
			wp_die();
		}

		self::cancel_legacy_invoice_import();
		delete_option( '_wc_gzdp_legacy_invoice_import_finished' );

		if ( wp_get_referer() ) {
			wp_safe_redirect( wp_get_referer() );
		} else {
			wp_safe_redirect( admin_url( 'admin.php?page=sab-accounting' ) );
		}
	}

	public static function legacy_invoice_import_submit() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'wc-gzdp-legacy-invoice-import-start' ) ) {
			wp_die();
		}

		if ( isset( $_POST['skip'] ) ) {
			self::cancel_legacy_invoice_import();

			if ( wp_get_referer() ) {
				wp_safe_redirect( wp_get_referer() );
			} else {
				wp_safe_redirect( admin_url( 'admin.php?page=sab-accounting' ) );
			}

			return;
		}

		$import_after = isset( $_POST['import_after'] ) ? absint( $_POST['import_after'] ) : date_i18n( 'Y' );

		self::start_legacy_invoice_import( $import_after );

		if ( wp_get_referer() ) {
			wp_safe_redirect( wp_get_referer() );
		} else {
			wp_safe_redirect( admin_url( 'admin.php?page=sab-accounting' ) );
		}
	}

	public static function legacy_invoice_import( $limit, $after ) {
		$invoices = \Vendidero\Germanized\Pro\Legacy\Importer::get_legacy_invoices( $limit, $after );
		$logger   = wc_get_logger();

		if ( empty( $invoices ) ) {
			self::legacy_invoice_import_complete();
		} else {
			$errors        = new WP_Error();
			$last_post_ids = get_option( '_wc_gzdp_legacy_invoice_import_current_ids' );
			$current_ids   = array();

			if ( empty( $last_post_ids ) || ! is_array( $last_post_ids ) ) {
				$last_post_ids = array();
			}

			$logger->log( 'notice', 'Starting new batch legacy invoice import', 'wc-gzdp-legacy-invoice-import' );

			foreach( $invoices as $invoice ) {
				$importer = new \Vendidero\Germanized\Pro\Legacy\Importer( $invoice );
				$result   = $importer->import();

				if ( is_wp_error( $result ) ) {
					foreach( $result->get_error_messages() as $error ) {
						$errors->add( 'import-error', $error );
					}
				}

				foreach( $importer->get_logs() as $log ) {
					$logger->info( $log, array( 'source' => 'wc-gzdp-legacy-invoice-import' ) );
				}

				$current_ids[] = $invoice->ID;

				/*
				 * Stop in case memory exceeds
				 */
				if ( self::memory_exceeded() ) {
					$logger->info( 'PHP Memory has exceeded.', array( 'source' => 'wc-gzdp-legacy-invoice-import' ) );

					break;
				}
			}

			if ( wc_gzd_wp_error_has_errors( $errors ) ) {
				$import_errors = self::get_legacy_invoice_import_errors();

				foreach( $errors->get_error_messages() as $message ) {
					$import_errors[] = $message;
				}

				update_option( '_wc_gzdp_legacy_invoice_import_errors', $import_errors );
			}

			$logger->info( 'Processed invoices posts: ' . wc_print_r( $current_ids, true ), array( 'source' => 'wc-gzdp-legacy-invoice-import' ) );

			if ( $current_ids == $last_post_ids ) {
				/**
				 * Seem to be stuck in a loop
				 */
				$logger->info( 'Cancelling legacy invoice import due to loop detection. Posts: ' . wc_print_r( $last_post_ids, true ), array( 'source' => 'wc-gzdp-legacy-invoice-import' ) );

			    self::cancel_legacy_invoice_import();
			} else {
			    update_option( '_wc_gzdp_legacy_invoice_import_current_ids', $current_ids );
				/**
				 * Queue next event
				 */
				self::queue_legacy_invoice_import( $limit, $after );
			}
		}
	}

	protected static function legacy_invoice_import_complete() {
		self::cancel_legacy_invoice_import();

		update_option( '_wc_gzdp_legacy_invoice_import_finished', 'yes' );

		$logger = wc_get_logger();
		$logger->info( 'Legacy import completed successfully', array( 'source' => 'wc-gzdp-legacy-invoice-import' ) );
	}

	public static function cancel_legacy_invoice_import() {
	    self::cancel_legacy_invoice_import_queue();

		delete_option( '_wc_gzdp_needs_legacy_invoice_import' );
		delete_option( '_wc_gzdp_legacy_invoice_import_running' );
		delete_option( '_wc_gzdp_legacy_invoice_import_current_ids' );
	}

	public static function cancel_legacy_invoice_import_queue() {
		$queue = WC()->queue();

		/**
		 * Cancel outstanding events and queue new.
		 */
		$queue->cancel_all( 'woocommerce_gzdp_legacy_invoice_import' );
	}

	public static function start_legacy_invoice_import( $after = '' ) {
		$limit        = apply_filters( 'woocommerce_gzdp_legacy_invoice_import_bulk_limit', 15 );
		$has_invoices = \Vendidero\Germanized\Pro\Legacy\Importer::has_legacy_invoices( $after );

		if ( $has_invoices ) {
			update_option( '_wc_gzdp_legacy_invoice_import_running', 'yes' );
			delete_option( '_wc_gzdp_legacy_invoice_import_errors' );
			delete_option( '_wc_gzdp_legacy_invoice_import_current_ids' );

			/**
			 * Temporarily disable automation to prevent issues during import.
			 */
			if ( 'yes' === get_option( 'storeabill_invoice_woo_order_auto_create' ) ) {
				update_option( 'storeabill_invoice_woo_order_auto_create', 'no' );
				update_option( '_wc_gzdp_show_invoice_automation_upgrade_disabled_notice', 1 );
			}

			$logger = wc_get_logger();
			$logger->info( sprintf( '#### Starting new legacy invoice import at %1$s ####', date_i18n( 'Y-m-d H:i' ) ), array( 'source' => 'wc-gzdp-legacy-invoice-import' ) );

			self::queue_legacy_invoice_import( $limit, $after );
		} else {
			self::legacy_invoice_import_complete();
		}
	}

	protected static function queue_legacy_invoice_import( $limit, $after ) {
		$args = array(
			'limit' => $limit,
			'after' => $after,
		);

		$queue = WC()->queue();

		self::cancel_legacy_invoice_import_queue();

		$queue->schedule_single(
			time() + 10,
			'woocommerce_gzdp_legacy_invoice_import',
			$args,
			'woocommerce-gzdp-update'
		);
	}

	public static function update_db_version() {
		update_option( 'woocommerce_gzdp_db_version', WC_germanized_pro()->version );
	}

	protected static function install_packages() {
		foreach ( Packages::get_packages() as $package_slug => $namespace ) {
			if ( is_callable( array( $namespace, 'install_integration' ) ) ) {
				$namespace::install_integration();
			}
		}
	}

	/**
	 * Handle updates
	 */
	public static function update() {
		$current_db_version = get_option( 'woocommerce_gzdp_db_version' );

		if ( version_compare( $current_db_version, '1.2.0', '<' ) ) {
			self::upgrade_invoice_path();
		} elseif ( version_compare( $current_db_version, '1.4.0', '<' ) ) {
			self::upgrade_pdf_options();
		} elseif ( version_compare( $current_db_version, '1.4.3', '<' ) ) {
			self::upgrade_1_4_2();
		} elseif ( version_compare( $current_db_version, '1.7.0', '<' ) ) {
			self::upgrade_1_6_3();
		} elseif ( version_compare( $current_db_version, '1.8.0', '<' ) ) {
			self::upgrade_invoice_path_suffix();
		} elseif ( version_compare( $current_db_version, '1.8.6', '<' ) ) {
			self::upgrade_fonts_path_suffix();
		} elseif ( version_compare( $current_db_version, '1.9.5', '<' ) ) {
			self::upgrade_1_9_5();
		} elseif ( version_compare( $current_db_version, '1.9.6', '<' ) ) {
			self::upgrade_1_9_6();
		} elseif( version_compare( $current_db_version, '2.0.0', '<' ) ) {
			self::upgrade_2_0_0();
		} elseif( version_compare( $current_db_version, '3.0.0', '<' ) ) {
			self::upgrade_3_0_0();
		} elseif( version_compare( $current_db_version, '3.2.2', '<' ) ) {
			self::upgrade_3_2_2();
		} elseif( version_compare( $current_db_version, '3.4.0', '<' ) ) {
			self::upgrade_3_4_0();
		}
	}

	protected static function upgrade_3_2_2() {
	    update_option( 'woocommerce_gzdp_checkout_layout_style', 'navigation' );
	}

	protected static function upgrade_3_4_0() {
		if ( \Vendidero\Germanized\DPD\Package::has_dependencies() && \Vendidero\Germanized\DPD\Package::is_dpd_enabled() && ( $provider = \Vendidero\Germanized\DPD\Package::get_dpd_shipping_provider() ) ) {
            $username = \Vendidero\Germanized\DPD\Package::get_api_username();
            $password = \Vendidero\Germanized\DPD\Package::get_api_password();

			/**
			 * If the DPD web connect API has already been in use make sure to switch the default API type back to web_connect.
			 */
            if ( ! empty( $username ) && ! empty( $password ) ) {
                $provider->update_setting( 'api_type', 'web_connect' );
                $provider->save();
            }
		}
	}

	protected static function upgrade_3_0_0() {
		$has_legacy_invoice = \Vendidero\Germanized\Pro\Legacy\Importer::has_legacy_invoices();

		if ( $has_legacy_invoice ) {
			update_option( '_wc_gzdp_needs_legacy_invoice_import', 1 );
		}

        if ( 'yes' === get_option( 'woocommerce_gzdp_invoice_enable' ) && get_option( 'wc_gzdp_invoice_simple' ) ) {
            /**
             * Numbering
             */
            $number_format_invoice      = trim( str_replace( '{type}', '', get_option( 'woocommerce_gzdp_invoice_number_format' ) ) );
            $number_format_cancellation = trim( str_replace( '{type}', '', get_option( 'woocommerce_gzdp_invoice_cancellation_number_format' ) ) );
            $number_format_packing_slip = trim( str_replace( '{type}', '', get_option( 'woocommerce_gzdp_invoice_packing_slip_number_format' ) ) );

            if ( strpos( $number_format_invoice, '{number}' ) === false ) {
                $number_format_invoice = '{number}';
            }

            if ( strpos( $number_format_packing_slip, '{number}' ) === false ) {
                $number_format_packing_slip = '{number}';
            }

            if ( empty( $number_format_cancellation ) || strpos( $number_format_invoice, '{number}' ) === false ) {
                $number_format_cancellation = trim( str_replace( '{order_number}', '', $number_format_invoice ) );
            }

            $leading_zeros              = get_option( 'woocommerce_gzdp_invoice_number_leading_zeros' );
            $last_invoice_number        = absint( get_option( 'wc_gzdp_invoice_simple' ) );
            $last_cancellation_number   = absint( get_option( 'wc_gzdp_invoice_cancellation' ) );
            $last_packing_slip_number   = absint( get_option( 'wc_gzdp_invoice_packing_slip' ) );

            if ( 'no' === get_option( 'woocommerce_gzdp_invoice_cancellation_numbering' ) ) {
                update_option( 'storeabill_invoice_cancellation_separate_numbers', 'no' );
            } else {
	            update_option( 'storeabill_invoice_cancellation_separate_numbers', 'yes' );
            }

            /**
             * Seems like older version have interpreted {y} as 2020 instead of 20.
             */
            $number_format_invoice      = str_replace( '{y}', '{Y}', $number_format_invoice );
            $number_format_cancellation = str_replace( '{y}', '{Y}', $number_format_cancellation );

            if ( $journal = sab_get_journal( 'invoice' ) ) {
                $journal->set_number_format( $number_format_invoice );

                if ( ! empty( $leading_zeros ) ) {
                    $journal->set_number_min_size( absint( $leading_zeros ) );
                }

                $journal->save();
                $journal->update_last_number( $last_invoice_number );
            }

            if ( $journal = sab_get_journal( 'invoice_cancellation' ) ) {
                $journal->set_number_format( $number_format_cancellation );

                if ( ! empty( $leading_zeros ) ) {
                    $journal->set_number_min_size( absint( $leading_zeros ) );
                }

                $journal->save();
                $journal->update_last_number( $last_cancellation_number );
            }

            if ( $journal = sab_get_journal( 'packing_slip' ) ) {
                $journal->set_number_format( $number_format_packing_slip );

                if ( ! empty( $leading_zeros ) ) {
                    $journal->set_number_min_size( absint( $leading_zeros ) );
                }

                $journal->save();
                $journal->update_last_number( $last_packing_slip_number );
            }

            /**
             * Invoice Automation
             */
            if ( 'yes' === get_option( 'woocommerce_gzdp_invoice_auto' ) ) {
                update_option( 'storeabill_invoice_woo_order_auto_create', 'yes' );

                $order_status     = get_option( 'woocommerce_gzdp_invoice_auto_status' );
                $gateway_specific = get_option( 'woocommerce_gzdp_invoice_auto_gateway_specific' );
                $gateways_enabled = get_option( 'woocommerce_gzdp_invoice_auto_gateways' );
                $timing           = 'status';

                if ( empty( $order_status ) ) {
                    $timing = 'checkout';
                }

                if ( 'yes' === $gateway_specific ) {
                    $new_gateway_option = array();

                    foreach( \Vendidero\StoreaBill\WooCommerce\Helper::get_available_payment_methods() as $method ) {
                        $method_id = $method->id;

                        $option_data = get_option( "woocommerce_gzdp_invoice_{$method_id}_auto_status" );
                        $option_data = is_array( $option_data ) ? array_filter( $option_data ) : array( $option_data );

                        if ( ! empty( $option_data ) && is_array( $option_data ) ) {
                            $new_gateway_option[ $method_id ] = $option_data;
                        }
                    }

                    if ( ! empty( $new_gateway_option ) ) {
                        update_option( 'storeabill_invoice_woo_order_payment_method_statuses', $new_gateway_option );

                        $timing = 'status_payment_method';
                    }
                }

                update_option( 'storeabill_invoice_woo_order_auto_create_timing', $timing );

                if ( 'status' === $timing && ! empty( $order_status ) ) {
                    update_option( 'storeabill_invoice_woo_order_auto_create_statuses', array( $order_status ) );
                }

                $gateways_enabled = is_array( $gateways_enabled ) ? array_filter( $gateways_enabled ) : array();

                if ( ! empty( $gateways_enabled ) ) {
                    $new_gateways_enabled = array();

                    foreach( \Vendidero\StoreaBill\WooCommerce\Helper::get_available_payment_methods() as $method ) {
                        $method_id = $method->id;

                        if ( in_array( $method_id, $gateways_enabled ) ) {
                            $new_gateways_enabled[] = $method_id;
                        }
                    }

                    if ( ! empty( $new_gateways_enabled ) ) {
                        update_option( 'storeabill_invoice_woo_order_auto_payment_gateway_specific', 'yes' );
                        update_option( 'storeabill_invoice_woo_order_auto_payment_gateways', $new_gateways_enabled );
                    }
                }

                if ( 'yes' === get_option( 'woocommerce_gzdp_invoice_auto_email' ) ) {
                    update_option( 'storeabill_invoice_woo_order_auto_finalize', 'yes' );
                    update_option( 'storeabill_invoice_send_to_customer', 'yes' );
                }
            } else {
                update_option( 'storeabill_invoice_woo_order_auto_create', 'no' );
            }

            /**
             * Invoice General
             */
            if ( 'yes' === get_option( 'woocommerce_gzdp_invoice_auto_except_free' ) ) {
                update_option( 'storeabill_invoice_woo_order_free', 'no' );
            }

            /**
             * Packing Slips
             */
            if ( 'yes' === get_option( 'woocommerce_gzdp_invoice_packing_slip_auto' ) ) {
                update_option( 'woocommerce_gzdp_packing_slip_auto', 'yes' );

                $status = get_option( 'woocommerce_gzdp_invoice_packing_slip_auto_shipment_status' );

                if ( ! empty( $status ) ) {
                    update_option( 'woocommerce_gzdp_packing_slip_auto_statuses', array( $status ) );
                }
            } else {
                update_option( 'woocommerce_gzdp_packing_slip_auto', 'no' );
            }

            /**
             * Download
             */
            $frontend_types = get_option( 'woocommerce_gzdp_invoice_download_frontend_types' );

            if ( ! empty( $frontend_types ) && is_array( $frontend_types ) ) {
                if ( in_array( 'simple', $frontend_types ) ) {
                    update_option( 'storeabill_invoice_woo_order_invoice_customer_download', 'yes' );
                }

                if ( in_array( 'cancellation', $frontend_types ) ) {
                    update_option( 'storeabill_invoice_woo_order_invoice_cancellation_customer_download', 'yes' );
                }
            }

            self::create_legacy_template( 'invoice' );
            self::create_legacy_template( 'invoice_cancellation' );
            self::create_legacy_template( 'packing_slip' );
        }

        self::import_legal_pages();

		if ( $has_legacy_invoice ) {
			/**
			 * Temporarily disable automation to prevent issues during import.
			 */
			if ( 'yes' === get_option( 'storeabill_invoice_woo_order_auto_create' ) ) {
				update_option( 'storeabill_invoice_woo_order_auto_create', 'no' );
				update_option( '_wc_gzdp_show_invoice_automation_upgrade_disabled_notice', 1 );
			}

			update_option( '_wc_gzdp_setup_wizard_redirect', 1 );
		}
	}

	public static function import_legal_pages() {
		$legal_pages = \Vendidero\Germanized\Pro\StoreaBill\LegalPages::get_legal_page_ids();

		foreach( $legal_pages as $legal_page_id ) {

			// Legal page does already exist.
			if ( $existing_legal_page = \Vendidero\Germanized\Pro\StoreaBill\LegalPages::get_legal_page( $legal_page_id ) ) {
				continue;
			}

			$attachment_id = get_post_meta( $legal_page_id, '_legal_page_attachment', true );

			if ( ! empty( $attachment_id ) ) {
				$filename = get_post_meta( $legal_page_id, '_legal_page_filename', true );

				if ( ! empty( $attachment_id ) && ( $attachment = get_post( $attachment_id ) ) ) {
					WC_germanized_pro()->set_upload_dir_filter();
					$path = get_attached_file( $attachment_id );
					WC_germanized_pro()->unset_upload_dir_filter();

					if ( file_exists( $path ) ) {
						$legal_page = new \Vendidero\Germanized\Pro\StoreaBill\PostDocument();
						$legal_page->set_post_id( $legal_page_id );

						try {
							$relative_path  = WC_germanized_pro()->get_relative_upload_path( $path );
							$filename       = ! empty( $filename ) ? $filename : basename( $path );

							$new_upload_dir  = \Vendidero\StoreaBill\UploadManager::get_upload_dir();
							$new_upload_path = trailingslashit( $new_upload_dir['basedir'] ) . $relative_path;

							if ( wp_mkdir_p( dirname( $new_upload_path ) ) ) {
								if ( file_exists( $new_upload_path ) ) {
									$new_upload_path = str_replace( $filename, 'legacy-' . $filename, $new_upload_path );
									$relative_path   = \Vendidero\StoreaBill\UploadManager::get_relative_upload_dir( $new_upload_path );
								}

								if ( @copy( $path, $new_upload_path ) ) {
									$legal_page->set_relative_path( $relative_path );
									$legal_page->save();

									\Vendidero\StoreaBill\Package::log( sprintf( 'Successfully imported legacy legal page %s', $legal_page_id ), 'info', 'legacy-import' );
								} else {
									\Vendidero\StoreaBill\Package::log( sprintf( 'Unable to copy legacy legal page %1$s to new dir %2$s.', $path, $new_upload_path ), 'info', 'legacy-import' );
								}
							} else {
								\Vendidero\StoreaBill\Package::log( sprintf( 'Unable to create upload dir %s while importing legal pages.', $new_upload_path ), 'info', 'legacy-import' );
							}
						} catch ( \Exception $e ) {}
					}
				}
			}
		}
	}

	public static function create_legacy_template( $document_type = 'invoice', $make_default = true ) {
	    $incl_tax      = true;
	    $is_invoice    = strpos( $document_type, 'invoice' ) !== false;
	    $option_prefix = ! $is_invoice ? 'invoice_' . $document_type : $document_type;

		/**
		 * Enable dense layout
		 */
		add_filter( "storeabill_{$document_type}_default_template_has_dense_layout", '__return_true', 30 );

		/**
		 * By default - hide price column from packing slips.
		 */
		add_filter( "storeabill_{$document_type}_default_template_has_price_column", '__return_false', 30 );

		if ( $is_invoice ) {
		    if ( 'yes' !== get_option( 'woocommerce_gzdp_invoice_table_gross' ) ) {
			    add_filter( "storeabill_{$document_type}_default_template_prices_include_tax", '__return_false', 30 );
			    $incl_tax = false;
		    }

		    if ( in_array( get_option( 'woocommerce_gzdp_invoice_net_totals' ), array( 'always', 'greater_250' ) ) ) {
			    add_filter( "storeabill_{$document_type}_default_template_show_net_totals_per_tax_rate", '__return_true', 30 );
		    }

		    if ( 'yes' === get_option( 'woocommerce_gzdp_invoice_show_tax_rate' ) ) {
			    add_filter( "storeabill_{$document_type}_default_template_show_row_based_tax_rates", '__return_true', 30 );
		    }

		    if ( 'yes' === get_option( 'woocommerce_gzdp_invoice_column_based_discounts' ) ) {
			    add_filter( "storeabill_{$document_type}_default_template_show_row_based_discounts", '__return_true', 30 );
		    }

		    if ( 'yes' === get_option( 'woocommerce_gzdp_invoice_show_differential_taxation_notice' ) ) {
			    add_filter( "storeabill_{$document_type}_default_template_show_differential_taxation_notice", '__return_true', 30 );
		    }

			if ( 'yes' === get_option( 'woocommerce_gzdp_invoice_show_sku' ) ) {
				add_filter( "storeabill_{$document_type}_default_template_show_item_sku", '__return_true', 30 );
			}
	    }

		$reverse_charge_text_callback = function( $content ) {
	        $data = get_option( 'woocommerce_gzdp_invoice_reverse_charge_text' );

			return ! empty( $data ) ? $data : $content;
        };

		add_filter( "storeabill_{$document_type}_default_template_reverse_charge_notice", $reverse_charge_text_callback, 30 );

		$third_country_text_callback = function( $content ) {
		    $data = get_option( 'woocommerce_gzdp_invoice_third_party_country_text' );

			return ! empty( $data ) ? $data : $content;
		};

		add_filter( "storeabill_{$document_type}_default_template_third_country_notice", $third_country_text_callback, 30 );

		$differential_taxed_text_callback = function( $content ) {
			$data = get_option( 'woocommerce_gzdp_invoice_differential_taxation_notice_text' );

			return ! empty( $data ) ? $data : $content;
		};

		add_filter( "storeabill_{$document_type}_default_template_differential_taxation_notice", $differential_taxed_text_callback, 30 );

		$unit_price_callback = function() use ( $incl_tax ) {
			?>
            <!-- wp:storeabill/item-meta {"metaType":"unit_price<?php echo ( $incl_tax ? '' : '_excl' ); ?>","customTextColor":"#a9a9a9","fontSize":"small"} -->
            <p class="wp-block-storeabill-item-meta sab-block-item-content has-text-color has-small-font-size" style="color:#a9a9a9">{content}</p>
            <!-- /wp:storeabill/item-meta -->
			<?php
		};

		if ( $is_invoice && 'yes' === get_option( 'woocommerce_gzdp_invoice_show_unit_price' ) ) {
			add_action( "storeabill_{$document_type}_default_template_after_item_price", $unit_price_callback, 30 );
		}

		$delivery_time_callback = function() {
			?>
            <!-- wp:storeabill/item-meta {"metaType":"delivery_time","customTextColor":"#a9a9a9","fontSize":"small"} -->
            <p class="wp-block-storeabill-item-meta sab-block-item-content has-text-color has-small-font-size" style="color:#a9a9a9">{content}</p>
            <!-- /wp:storeabill/item-meta -->
			<?php
		};

		if ( $is_invoice && 'yes' === get_option( 'woocommerce_gzdp_invoice_show_delivery_time' ) ) {
			add_action( "storeabill_{$document_type}_default_template_after_item_name", $delivery_time_callback, 30 );
		}

		$cart_desc_callback = function() {
			?>
            <!-- wp:storeabill/item-meta {"metaType":"cart_desc","customTextColor":"#a9a9a9","fontSize":"small"} -->
            <p class="wp-block-storeabill-item-meta sab-block-item-content has-text-color has-small-font-size" style="color:#a9a9a9">{content}</p>
            <!-- /wp:storeabill/item-meta -->
			<?php
		};

		if ( $is_invoice && 'yes' === get_option( 'woocommerce_gzdp_invoice_show_item_desc' ) ) {
			add_action( "storeabill_{$is_invoice}_default_template_after_item_name", $cart_desc_callback, 50 );
		}

        $before_table = get_option( 'woocommerce_gzdp_invoice_text_before_table' );
        $after_table  = get_option( 'woocommerce_gzdp_invoice_text_after_table' );

        if ( 'invoice' !== $document_type && 'yes' === get_option( "woocommerce_gzdp_{$option_prefix}_table_content" ) ) {
	        $before_table = get_option( "woocommerce_gzdp_{$option_prefix}_text_before_table" );
	        $after_table  = get_option( "woocommerce_gzdp_{$option_prefix}_text_after_table" );
        }

        if ( ! empty( $before_table ) ) {
	        $before_table = wpautop( self::strip_tags( htmlspecialchars_decode( wp_unslash( $before_table ) ) ) );
        }

        if ( ! empty( $after_table ) ) {
	        $after_table = wpautop( self::strip_tags( htmlspecialchars_decode( wp_unslash( $after_table ) ) ) );
        }

		$before_table_callback = function() use ( $before_table ) {
			?>
            <!-- wp:paragraph -->
            <?php echo self::convert_legacy_shortcodes( $before_table ); ?>
            <!-- /wp:paragraph -->
			<?php
		};

        if ( ! empty( $before_table ) ) {
            add_action( "storeabill_{$document_type}_default_template_before_item_table", $before_table_callback, 30 );
        }

		$after_table_callback = function() use ( $after_table ) {
			?>
            <!-- wp:paragraph -->
			<?php echo self::convert_legacy_shortcodes( $after_table ); ?>
            <!-- /wp:paragraph -->
			<?php
		};

        if ( ! empty( $after_table ) ) {
	        add_action( "storeabill_{$document_type}_default_template_after_" . ( $is_invoice ? 'totals' : 'item_table' ), $after_table_callback, 30 );
        }

        $default_logo = '';

		if ( $email_logo = get_option( 'woocommerce_email_header_image' ) ) {
			$default_logo = $email_logo;
		}

		if ( empty( $default_logo ) ) {
			add_filter( "storeabill_{$document_type}_default_template_show_logo", '__return_false', 30 );
		}

		$logo_callback = function( $logo ) use ( $default_logo ) {
			return $default_logo;
		};

		add_filter( "storeabill_{$document_type}_default_template_logo", $logo_callback, 30 );

		$has_custom_first_page = false;

        $margins            = self::get_legacy_margins( get_option( 'woocommerce_gzdp_invoice_margins' ) );
        $margins_first_page = self::get_legacy_margins( get_option( 'woocommerce_gzdp_invoice_first_page_margins' ) );

        if ( $margins_first_page != $margins ) {
            $has_custom_first_page = true;
        }

        $template            = self::get_legacy_template( get_option( 'woocommerce_gzdp_invoice_template_attachment' ) );
        $template_first_page = self::get_legacy_template( get_option( 'woocommerce_gzdp_invoice_template_attachment_first' ) );

        if ( ! empty( $template ) ) {
            add_filter( "storeabill_{$document_type}_default_template_show_header", '__return_false', 30 );
	        add_filter( "storeabill_{$document_type}_default_template_show_footer", '__return_false', 30 );

	        add_filter( "storeabill_{$document_type}_default_template_address_header", '__return_empty_string', 30 );
        }

		$template_data_callback = function( $data ) use ( $margins, $template ) {
			$data['margins'] = array(
				'left'   => $margins[0],
				'top'    => $margins[1],
				'right'  => $margins[2],
				'bottom' => $margins[3]
			);

			if ( ! empty( $template ) ) {
                $data['pdf_template_id'] = $template;
			}

			$data['font_size'] = '12';

			return $data;
		};

		add_filter( "storeabill_{$document_type}_default_template_data", $template_data_callback, 30 );

        if ( ! empty( $template_first_page ) && $template_first_page != $template ) {
            $has_custom_first_page = true;
        }

        $template = self::create_template( $document_type, array(
            'has_custom_first_page'  => $has_custom_first_page,
            'first_page_margins'     => $margins_first_page,
            'first_page_template_id' => $template_first_page,
        ) );

		/**
		 * Turn the newly created template into the default template.
		 */
        if ( $make_default && $template && $template->get_id() > 0 ) {
	        update_option( 'storeabill_' . $document_type . '_default_template', $template->get_id() );
        }

		remove_filter( "storeabill_{$document_type}_default_template_show_item_sku", '__return_true', 30 );
		remove_filter( "storeabill_{$document_type}_default_template_has_price_column", '__return_false', 30 );
		remove_filter( "storeabill_{$document_type}_default_template_has_dense_layout", '__return_true', 30 );
		remove_filter( "storeabill_{$document_type}_default_template_prices_include_tax", '__return_false', 30 );
		remove_filter( "storeabill_{$document_type}_default_template_show_net_totals_per_tax_rate", '__return_true', 30 );
		remove_filter( "storeabill_{$document_type}_default_template_show_row_based_tax_rates", '__return_true', 30 );
		remove_filter( "storeabill_{$document_type}_default_template_show_row_based_discounts", '__return_true', 30 );
		remove_filter( "storeabill_{$document_type}_default_template_show_differential_taxation_notice", '__return_true', 30 );

		remove_filter( "storeabill_{$document_type}_default_template_reverse_charge_notice", $reverse_charge_text_callback, 30 );
		remove_filter( "storeabill_{$document_type}_default_template_third_country_notice", $third_country_text_callback, 30 );
		remove_filter( "storeabill_{$document_type}_default_template_differential_taxation_notice", $differential_taxed_text_callback, 30 );

		remove_filter( "storeabill_{$document_type}_default_template_show_logo", '__return_false', 30 );
		remove_filter( "storeabill_{$document_type}_default_template_logo", $logo_callback, 30 );

		remove_action( "storeabill_{$document_type}_default_template_after_item_price", $unit_price_callback, 30 );
		remove_action( "storeabill_{$document_type}_default_template_after_item_name", $delivery_time_callback, 30 );
		remove_action( "storeabill_{$document_type}_default_template_after_item_name", $cart_desc_callback, 50 );
		remove_action( "storeabill_{$document_type}_default_template_before_item_table", $before_table_callback, 30 );
		remove_action( "storeabill_{$document_type}_default_template_after_totals", $after_table_callback, 30 );
		remove_filter( "storeabill_{$document_type}_default_template_show_header", '__return_false', 30 );
		remove_filter( "storeabill_{$document_type}_default_template_show_footer", '__return_false', 30 );
		remove_filter( "storeabill_{$document_type}_default_template_data", $template_data_callback, 30 );
		remove_filter( "storeabill_{$document_type}_default_template_address_header", '__return_empty_string', 30 );
	}

	protected static function strip_tags( $html ) {
	    return strip_tags( $html, '<br><strong><em><br/>' );
	}

	protected static function get_legacy_template( $template ) {
	    $template = absint( $template );

	    if ( ! empty( $template ) ) {
	        $file = get_attached_file( $template );

	        if ( @file_exists( $file ) ) {
	            return $template;
	        }
	    }

	    return 0;
	}

	protected static function create_template( $document_type, $args = array() ) {
	    $args = wp_parse_args( $args, array(
            'template_name'          => 'default',
            'has_custom_first_page'  => false,
            'first_page_margins'     => array(),
            'first_page_template_id' => 0,
        ) );

	    $template_name = $args['template_name'];

		if ( ! $editor_tpl = \Vendidero\StoreaBill\Editor\Helper::get_editor_template( $document_type, $template_name ) ) {
		    return false;
        }

	    $template = sab_create_document_template( $document_type, $template_name, true );
	    $template->set_title( __( 'Germanized Pro import', 'woocommerce-germanized-pro' ) );
	    $template->save();

	    if ( $args['has_custom_first_page'] ) {

		    remove_filter( "storeabill_{$document_type}_{$template_name}_template_show_header", '__return_false', 30 );
		    remove_filter( "storeabill_{$document_type}_{$template_name}_template_show_footer", '__return_false', 30 );

		    $tpl = new FirstPageTemplate();
		    $tpl->set_parent_id( $template->get_id() );

		    if ( ! empty( $args['first_page_template_id'] ) ) {
		        $tpl->set_pdf_template_id( $args['first_page_template_id'] );

			    /**
			     * Add a little additional margin to prevent address overlapping.
			     */
			    $args['first_page_margins'][1] = $args['first_page_margins'][1] + 0.5;
		    }

		    $tpl->set_margins( array(
                'top'    => $args['first_page_margins'][1],
                'bottom' => $args['first_page_margins'][3],
            ) );

		    ob_start();
		    ?>
            <!-- wp:storeabill/document-styles /-->
            <?php if ( empty( $args['first_page_template_id'] ) ) : ?>
		        <?php echo $editor_tpl::get_default_header(); ?>
            <?php endif; ?>
		    <?php if ( empty( $args['first_page_template_id'] ) ) : ?>
			    <?php echo $editor_tpl::get_default_footer(); ?>
		    <?php endif; ?>
		    <?php
		    $content = $editor_tpl::clean_html_whitespaces( ob_get_clean() );

		    $tpl->set_content( $content );
		    $tpl->set_status( 'publish' );
		    $tpl->save();
	    }

	    return $template;
	}

	protected static function get_legacy_margins( $margins ) {
		if ( empty( $margins ) || ! is_array( $margins ) || sizeof( $margins ) < 4 ) {
			$margins = array( 1, 1, 1, 1 );
		} else {
		    $conversion = 0.1;

		    $margins = array(
			    wc_format_decimal( $margins[0] * $conversion, 2, true ),
			    wc_format_decimal( $margins[1] * $conversion, 2, true ),
			    wc_format_decimal( $margins[2] * $conversion, 2, true ),
			    wc_format_decimal( $margins[3] * $conversion, 2, true )
            );
		}

		/**
		 * @var [] 0: left, 1: top, 2: right: 3: bottom
		 */
		return $margins;
	}

	protected static function convert_legacy_shortcodes( $content ) {
		$content = str_replace( '[if_invoice_shipping_vat_id]', '[if_document data="shipping_vat_id" compare="nempty"]', $content );
		$content = str_replace( '[/if_invoice_shipping_vat_id]', '[/if_document]', $content );
		$content = str_replace( array( '[if_order_data', '[/if_order_data]' ), array( '[if_order', '[/if_order]' ), $content );
		$content = str_replace( '[order_data', '[order', $content );
		$content = str_replace( '[order_user_data', '[customer', $content );
		$content = str_replace( array( '[if_invoice_data', '[/if_invoice_data]' ), array( '[if_document', '[/if_document]' ), $content );
		$content = str_replace( '[invoice_data', '[document', $content );
		$content = str_replace( '[reverse_charge]', '', $content );
		$content = str_replace( '[third_party_country]', '', $content );
		$content = str_replace( 'meta="', 'data="', $content );
		$content = str_replace( 'data="coupons"', 'data="coupon_codes"', $content );
		$content = str_replace( 'data="shipping_address"', 'data="formatted_shipping_address"', $content );
		$content = str_replace( '[order data="id"]', '[document data="order_number"]', $content );
		$content = str_replace( 'data="number_formatted"', 'data="formatted_number"', $content );

		return $content;
	}

	public static function upgrade_fonts_path_suffix() {
		$upload_dir = wp_upload_dir();
		$gzdp_dir   = WC_germanized_pro()->filter_upload_dir( $upload_dir );

		// Cut off the suffix
		$path         = substr( $gzdp_dir[ 'basedir' ], 0, -11 ) . '/fonts';
		$new_gzdp_dir = $gzdp_dir[ 'basedir' ] . '/fonts';

		if ( file_exists( $path ) && file_exists( $new_gzdp_dir ) ) {

			$files = @glob( $path . '/*.*' );

			foreach ( $files as $file ) {
				$file_to_go = str_replace( $path, $new_gzdp_dir, $file );
				@rename( $file, $file_to_go );
			}
		}
	}

	public static function upgrade_invoice_path_suffix() {

		$upload_dir = wp_upload_dir();
		$gzdp_dir = WC_germanized_pro()->filter_upload_dir( $upload_dir );

		// Cut off the suffix
		$path = substr( $gzdp_dir[ 'basedir' ], 0, -11 );
		$new_gzdp_dir = $gzdp_dir[ 'basedir' ];

		if ( file_exists( $path ) && ! file_exists( $new_gzdp_dir ) ) {
			// Now try to rename the folder
			if ( ! rename( $path, $new_gzdp_dir ) ) {
				update_option( '_wc_gzdp_invoice_dir_rename_failed', 'yes' );
			}
		}
	}

	public static function upgrade_1_9_6() {
		if ( 'yes' === get_option( 'woocommerce_gzdp_contract_after_confirmation' ) ) {
			update_option( 'woocommerce_gzd_email_order_confirmation_text', __( 'Your order has been processed. We are glad to confirm the order to you. Your order details are shown below for your reference.', 'woocommerce-germanized-pro' ) );
		}
	}

	public static function upgrade_2_0_0() {
		$packing_slip_format = get_option( 'woocommerce_gzdp_invoice_packing_slip_number_format' );

		// Replace {order_number} with {shipment_number} for packing slips
		if ( strpos( $packing_slip_format, '{order_number}' ) !== false ) {
			$packing_slip_format = str_replace( '{order_number}', '{shipment_number}', $packing_slip_format );

			update_option( 'woocommerce_gzdp_invoice_packing_slip_number_format', $packing_slip_format );
		}
	}

	public static function upgrade_1_9_5() {
		delete_transient( 'woocommerce_gzdp_generator_success_widerruf' );
		delete_transient( 'woocommerce_gzdp_generator_success_agbs' );

		delete_option( 'woocommerce_gzdp_generator_settings_widerruf' );
		delete_option( 'woocommerce_gzdp_generator_settings_agbs' );

		delete_option( 'woocommerce_gzdp_generator_widerruf' );
		delete_option( 'woocommerce_gzdp_generator_agbs' );
	}

	public static function upgrade_1_6_3() {

        // Do not allow cancellation auto generation on wc-refunded status (using partial cancellations instead)
	    if ( 'wc-refunded' === get_option( 'woocommerce_gzdp_invoice_cancellation_auto_status' ) ) {
	        update_option( 'woocommerce_gzdp_invoice_cancellation_auto_status', 'wc-cancelled' );
        }
    }

	public static function upgrade_1_4_2() {

		$options = array(
			'margins' => 'first_page_margins',
			'page_numbers_bottom' => 'first_page_page_numbers_bottom',
		);

		$types = array( 'invoice', 'legal_page' );

		foreach ( $options as $org => $option ) {

			foreach ( $types as $type ) {

				// Set Bottom Margin
				if ( $org === 'margins' ) {
					$margins = get_option( 'woocommerce_gzdp_' . $type . '_' . $org );
					if ( ! is_array( $margins ) )
						$margins = array( 15, 15, 15 );
					$margins[3] = 25;

					update_option( 'woocommerce_gzdp_' . $type . '_' . $org, $margins );
				}

				update_option( 'woocommerce_gzdp_' . $type . '_' . $option, get_option( 'woocommerce_gzdp_' . $type . '_' . $org ) );
			}

		}

		$invoices = array(
			'invoice' => _x( 'Invoice', 'invoices', 'woocommerce-germanized-pro' ),
			'invoice_cancellation' => _x( 'Cancellation', 'invoices', 'woocommerce-germanized-pro' ),
		);

		foreach ( $invoices as $invoice_type => $title ) {

			// Invoice email heading
			$invoice_settings = get_option( 'woocommerce_customer_' . $invoice_type . '_settings' );
			
			if ( $invoice_settings && is_array( $invoice_settings ) ) {

				$types = array( 'subject', 'heading' );

				foreach ( $types as $type ) {
					
					if ( isset( $invoice_settings[ $type ] ) ) {
						
						$invoice_settings[ $type ] = str_replace( $title . ' {invoice_number}', '{invoice_number}', $invoice_settings[ $type ] );
						
						if ( $invoice_type == 'invoice_cancellation' ) {
							$invoice_settings[ $type ] = str_replace( 'zur Rechnung {invoice_number_parent}', 'zu {invoice_number_parent}', $invoice_settings[ $type ] );
						}

					}
				}

				update_option( 'woocommerce_customer_' . $invoice_type . '_settings', $invoice_settings );

	 		}

 		}

	}

	public static function upgrade_pdf_options() {

		$rename = array(
			'woocommerce_gzdp_invoice_custom_font_names' => 'woocommerce_gzdp_pdf_custom_font_names',
			'woocommerce_gzdp_invoice_custom_fonts' => 'woocommerce_gzdp_invoice_custom_fonts',
			'woocommerce_gzdp_invoice_text_cancellation_after_table' => 'woocommerce_gzdp_invoice_cancellation_text_after_table',
			'woocommerce_gzdp_invoice_text_cancellation_before_table' => 'woocommerce_gzdp_invoice_cancellation_text_before_table',
			'woocommerce_gzdp_invoice_text_packing_slip_after_table' => 'woocommerce_gzdp_invoice_packing_slip_text_after_table',
			'woocommerce_gzdp_invoice_text_packing_slip_before_table' => 'woocommerce_gzdp_invoice_packing_slip_text_before_table',
		);

		foreach( $rename as $old => $new ) {
			if ( get_option( $old ) )
				update_option( $new, get_option( $old ) );
			delete_option( $old );
		}

	}

	public static function upgrade_invoice_path() {
		
		// Go through invoices
		$invoices = get_posts( array( 'post_type' => 'invoice', 'posts_per_page' => -1, 'post_status' => 'any' ) );
		
		if ( ! empty( $invoices ) ) {
		
			foreach ( $invoices as $invoice ) {
				
				if ( $attachment = get_post_meta( $invoice->ID, '_invoice_attachment', true ) ) {

					$file = get_attached_file( $attachment );

					if ( $file ) {

						$upload_dir = WC_germanized_pro()->get_upload_dir();
						
						WC_germanized_pro()->set_upload_dir_filter();
						$path = str_replace( array( WC_germanized_pro()->plugin_path() . '/uploads', $upload_dir[ 'basedir' ] ), '', get_attached_file( $attachment ) );
						WC_germanized_pro()->unset_upload_dir_filter();
						
						$path = ltrim( $path, '/' );

						update_post_meta( $attachment, '_wp_attached_file', $path );
					}

				}

			}

		}

	}

	/**
	 * Default options
	 *
	 * Sets up the default options used on the settings page
	 *
	 * @access public
	 */
	public static function create_options() {
		include_once( WC()->plugin_path() . '/includes/admin/settings/class-wc-settings-page.php' );
		include_once( WC_germanized_pro()->plugin_path() . '/includes/admin/settings/class-wc-gzdp-settings.php' );
		include_once( WC_germanized()->plugin_path() . '/includes/admin/settings/class-wc-gzd-settings-germanized.php' );
		
		$settings = new WC_GZD_Settings_Germanized();
		$options  = is_callable( array( $settings, 'get_settings_for_section_core' ) ) ? $settings->get_settings_for_section_core( '' ) : $settings->get_settings();

		foreach ( $options as $value ) {
			if ( isset( $value['id'] ) && self::is_pro_option( $value['id'] ) && isset( $value['default'] ) ) {
				$autoload = isset( $value['autoload'] ) ? (bool) $value['autoload'] : true;
				add_option( str_replace( '[]', '', $value['id'] ), $value['default'], '', ( $autoload ? 'yes' : 'no' ) );
			}
		}
	}

	protected static function is_pro_option( $id ) {
	    return ( strpos( $id, 'gzdp' ) !== false || strpos( $id, 'storeabill' ) !== false );
	}
}

endif;

return new WC_GZDP_Install();
