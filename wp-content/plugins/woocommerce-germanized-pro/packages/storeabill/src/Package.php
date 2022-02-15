<?php

namespace Vendidero\StoreaBill;

use Vendidero\StoreaBill\Admin\Admin;
use Vendidero\StoreaBill\Document\Document;
use Vendidero\StoreaBill\Document\Item;
use Vendidero\StoreaBill\Document\ShortcodeManager;
use Vendidero\StoreaBill\Document\Total;
use Vendidero\StoreaBill\Emails\Mailer;

defined( 'ABSPATH' ) || exit;

/**
 * StoreaBill class
 */
class Package {

	/**
	 * Version.
	 *
	 * @var string
	 */
	const VERSION = '1.8.5';

	/**
	 * Init the package.
	 */
	public static function init() {
		if ( ! self::has_dependencies() ) {
			return;
		}

		self::define_constants();
		self::load_plugin_textdomain();
		self::define_tables();
		self::init_hooks();
		self::includes();

		REST\Server::instance()->init();

		self::load_packages();
		self::load_compatibilities();
	}

	/**
	 * Load Localisation files.
	 */
	public static function load_plugin_textdomain() {
		if ( self::is_feature_plugin() ) {
			if ( function_exists( 'determine_locale' ) ) {
				$locale = determine_locale();
			} else {
				// @todo Remove when start supporting WP 5.0 or later.
				$locale = is_admin() ? get_user_locale() : get_locale();
			}

			$locale = apply_filters( 'plugin_locale', $locale, 'storeabill' );

			unload_textdomain( 'storeabill' );
			load_textdomain( 'storeabill', WP_LANG_DIR . '/storeabill/storeabill-' . $locale . '.mo' );
			load_plugin_textdomain( 'storeabill', false, self::get_path() . '/i18n/languages' );
		}

		do_action( 'storeabill_load_plugin_textdomain' );
	}

	public static function is_feature_plugin() {
		return defined( 'STOREABILL_IS_FEATURE_PLUGIN' ) && STOREABILL_IS_FEATURE_PLUGIN;
	}

	public static function get_packages() {
		return array();
	}

	public static function load_packages() {
		foreach ( self::get_packages() as $package_name => $package_class ) {
			if ( self::has_package( $package_name ) ) {
				call_user_func( [ $package_class, 'init' ] );
			}
		}
	}

	public static function load_compatibilities() {
		$compatibilities = apply_filters( 'storeabill_compatibilities', array(
			'wpml'          => '\Vendidero\StoreaBill\Compatibility\WPML',
			'subscriptions' => '\Vendidero\StoreaBill\Compatibility\Subscriptions',
			'bookings'      => '\Vendidero\StoreaBill\Compatibility\Bookings',
			'bundles'       => '\Vendidero\StoreaBill\Compatibility\Bundles'
		) );

		foreach( $compatibilities as $compatibility ) {
			if ( is_a( $compatibility, '\Vendidero\StoreaBill\Interfaces\Compatibility', true ) ) {
				if ( $compatibility::is_active() ) {
					$compatibility::init();
				}
			}
		}
	}

	public static function has_package( $package ) {
		return file_exists( trailingslashit( self::get_path() ) . 'packages/' . $package );
	}

	public static function has_dependencies() {
		global $wp_version;

		$woo_version = get_option( 'woocommerce_db_version' );

		if ( empty( $woo_version ) ) {
			$woo_version = get_option( 'woocommerce_version' );
        }

		return class_exists( 'WooCommerce' ) && version_compare( $woo_version, '3.9', '>=' ) && version_compare( $wp_version, '5.4', '>=' );
	}

	protected static function define_constants() {
		self::define( 'SAB_PLUGIN_FILE', dirname( __DIR__ ) . '/storeabill.php' );
		self::define( 'SAB_ABSPATH',  dirname( SAB_PLUGIN_FILE ) . '/' );
		self::define( 'SAB_VERSION', self::get_version() );
		self::define( 'SAB_TEMPLATE_DEBUG_MODE', false );
		self::define( 'SAB_PDF_DEBUG_MODE', false );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name  Constant name.
	 * @param string|bool $value Constant value.
	 */
	private static function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	protected static function init_hooks() {
		/**
		 * Update Action Queue callbacks
		 */
		add_action( 'storeabill_run_update_callback', array( '\Vendidero\StoreaBill\Install', 'run_update_callback' ) );
		add_action( 'storeabill_update_120_net_invoices', array( '\Vendidero\StoreaBill\Updater', 'update_120_net_invoices' ), 10, 2 );

		add_filter( 'woocommerce_data_stores', array( __CLASS__, 'register_data_stores' ), 10, 1 );

		add_action( 'init', array( __CLASS__, 'register_document_types' ), 5 );
		add_action( 'admin_init', array( __CLASS__, 'check_version' ), 10 );

		add_action( 'storeabill_document_render_callback', array( __CLASS__, 'render_callback' ) );
		add_filter( 'woocommerce_locate_template', array( __CLASS__, 'filter_templates' ), 50, 3 );

		add_action( 'admin_notices', array( __CLASS__, 'upload_dir_warning' ) );

		// Force using Gutenberg to prevent issues for document_template post type.
		add_filter( 'use_block_editor_for_post', array( __CLASS__, 'force_gutenberg' ), 1500, 2 );
	}

	public static function force_gutenberg( $use_gutenberg, $post ) {
		if ( 'document_template' === $post->post_type ) {
			return true;
		}

		return $use_gutenberg;
    }

	public static function upload_dir_warning() {
		$dir     = UploadManager::get_upload_dir();
		$path    = $dir['basedir'];
		$dirname = basename( $path );

		if ( @is_dir( $dir['basedir'] ) ) {
			return;
		}
		?>
		<div class="notice notice-error error">
			<p><?php printf( _x( 'Your document upload directory is missing. Please manually create the folder %s and make sure that it is writeable.', 'storeabill-core', 'woocommerce-germanized-pro' ), '<i>wp-content/uploads/' . $dirname . '</i>' ); ?></p>
		</div>
		<?php
	}

	public static function check_version() {
		if ( self::is_feature_plugin() && self::has_dependencies() && ! defined( 'IFRAME_REQUEST' ) && ( get_option( 'storeabill_version' ) != self::get_version() ) ) {
			Install::install();

			do_action( 'storeabill_updated' );
		}
	}

	/**
	 * Filter WooCommerce Templates to look into /templates before looking within theme folder
	 *
	 * @param string $template
	 * @param string $template_name
	 * @param string $template_path
	 *
	 * @return string
	 */
	public static function filter_templates( $template, $template_name, $template_path ) {
		$template_path = self::get_template_path();

		// Check for Theme overrides
		$theme_template = locate_template( array(
			trailingslashit( $template_path ) . $template_name,
		) );

		if ( ! $theme_template && file_exists( self::get_path() . '/templates/' . $template_name ) ) {
			$template = self::get_path() . '/templates/' . $template_name;
		} elseif ( $theme_template ) {
			$template = $theme_template;
		}

		return $template;
	}

	/**
	 * This callback is used for async rendering.
	 *
	 * @param $document_id
	 * @param string $success_callback
	 */
	public static function render_callback( $document_id, $success_callback = '' ) {
		if ( $document = sab_get_document( $document_id ) ) {
			$result  = $document->render();
			$success = is_wp_error( $result ) ? false : true;

			if ( $success && ! empty( $success_callback ) ) {
				if ( is_callable( array( $document, $success_callback ) ) ) {
					$document->$success_callback();
				} elseif( function_exists( $success_callback ) ) {
					call_user_func( $success_callback, $document_id );
				}
			}
		}
	}

	public static function enable_accounting() {
		return apply_filters( 'storeabill_enable_accounting', true );
	}

	public static function register_document_types() {
		sab_register_document_type( 'invoice', array(
			'group'           => 'accounting',
			'api_endpoint'    => 'invoices',
			'labels'          => array(
				'singular' => _x( 'Invoice', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'plural'   => _x( 'Invoices', 'storeabill-core', 'woocommerce-germanized-pro' ),
			),
			'class_name'             => '\Vendidero\StoreaBill\Invoice\Simple',
			'admin_table_class_name' => '\Vendidero\StoreaBill\Invoice\SimpleTable',
			'email_class_name'       => '\Vendidero\StoreaBill\Emails\SimpleInvoice',
			'exporters'              => array(
				'csv' => '\Vendidero\StoreaBill\Invoice\SimpleCsvExporter'
			),
			'statuses'               => array_replace_recursive( sab_get_document_statuses(), array(
				'cancelled'   => _x( 'Cancelled', 'storeabill-core', 'woocommerce-germanized-pro' ),
			) ),
			'statuses_hidden'        => array(
				'cancelled'
			),
			'date_types' => array(
				'date_paid'              => _x( 'Payment date', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'date_due'               => _x( 'Due date', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'date_of_service_period' => _x( 'Date of service', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'date_of_service_end'    => _x( 'End date of service', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'date_of_service'        => _x( 'Start date of service', 'storeabill-core', 'woocommerce-germanized-pro' )
			),
			'barcode_code_types' => array(
				'document?data=order_number' => _x( 'Order number', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'epc'                        => _x( 'EPC (Girocode)', 'storeabill-core', 'woocommerce-germanized-pro' ),
			),
			'shortcodes'         => array(
				'document' => array(
					array(
						'shortcode' => 'document?data=order_number',
						'title'     => _x( 'Order number', 'storeabill-core', 'woocommerce-germanized-pro' ),
					),
					array(
						'shortcode' => 'document?data=date_paid',
						'title'     => _x( 'Date paid', 'storeabill-core', 'woocommerce-germanized-pro' ),
					),
					array(
						'shortcode' => 'document?data=date_due',
						'title'     => _x( 'Date due', 'storeabill-core', 'woocommerce-germanized-pro' ),
					),
					array(
						'shortcode' => 'document?data=payment_method_title',
						'title'     => _x( 'Payment method', 'storeabill-core', 'woocommerce-germanized-pro' ),
					),
					array(
						'shortcode' => 'document?data=vat_id',
						'title'     => _x( 'VAT ID', 'storeabill-core', 'woocommerce-germanized-pro' ),
					),
					array(
						'shortcode' => 'document?data=date_of_service_period',
						'title'     => _x( 'Date of service', 'storeabill-core', 'woocommerce-germanized-pro' ),
					),
				),
			),
		) );

		sab_register_document_type( 'invoice_cancellation', array(
			'group'        => 'accounting',
			'api_endpoint' => 'cancellations',
			'labels'       => array(
				'singular' => _x( 'Cancellation', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'plural'   => _x( 'Cancellations', 'storeabill-core', 'woocommerce-germanized-pro' ),
			),
			'class_name'             => '\Vendidero\StoreaBill\Invoice\Cancellation',
			'preview_class_name'     => '\Vendidero\StoreaBill\Invoice\CancellationPreview',
			'admin_table_class_name' => '\Vendidero\StoreaBill\Invoice\CancellationTable',
			'email_class_name'       => '\Vendidero\StoreaBill\Emails\CancellationInvoice',
			'exporters'              => array(
				'csv' => '\Vendidero\StoreaBill\Invoice\CancellationCsvExporter'
			),
			'shortcodes'         => array(
				'document' => array(
					array(
						'shortcode' => 'document?data=parent_number',
						'title'     => _x( 'Parent invoice number', 'storeabill-core', 'woocommerce-germanized-pro' ),
					),
					array(
						'shortcode' => 'document?data=parent_formatted_number',
						'title'     => _x( 'Parent invoice formatted number', 'storeabill-core', 'woocommerce-germanized-pro' ),
					),
					array(
						'shortcode' => 'document?data=refund_formatted_number',
						'title'     => _x( 'Refund order formatted number', 'storeabill-core', 'woocommerce-germanized-pro' ),
					),
					array(
						'shortcode' => 'document?data=reason',
						'title'     => _x( 'Refund reason', 'storeabill-core', 'woocommerce-germanized-pro' ),
					)
				),
			),
		) );

		do_action( 'storeabill_registered_core_document_types' );
	}

	public static function install() {
	    if ( ! self::has_dependencies() ) {
	        return;
	    }

		self::define_constants();
		self::includes();

		Install::install();
	}

	/**
	 * @param Document $document
	 */
	public static function setup_document_rendering( $document, $is_preview = false ) {
		sab_maybe_define_constant( 'SAB_DOING_RENDERING', true );
		sab_maybe_define_constant( 'SAB_IS_DOCUMENT_PREVIEW', $is_preview );

		$is_editor_preview = false;

		if ( is_a( $document, '\Vendidero\StoreaBill\Interfaces\Previewable' ) ) {
			$is_editor_preview = true;
		}

		sab_maybe_define_constant( 'SAB_IS_EDITOR_PREVIEW', $is_editor_preview );

		do_action( 'storeabill_before_render_document', $document, $is_preview );

		self::setup_document( $document );
	}

	/**
	 * @param Document $document
	 */
	public static function clear_document_rendering( $document, $is_preview = false ) {
		do_action( "storeabill_after_render_document", $document, $is_preview );

		unset( $GLOBALS['document'] );
		unset( $GLOBALS[ $document->get_type() ] );

		/**
		 * Reset original post data.
		 */
		if ( $template = $document->get_template() ) {
			wp_reset_postdata();
		}

		// Destroy Shortcodes
		ShortcodeManager::instance()->remove( $document->get_type() );
	}

	/**
	 * @param Document $document
	 */
	public static function setup_document( $document ) {
		$GLOBALS['document']              = $document;
		$GLOBALS[ $document->get_type() ] = $document;

		/**
		 * Setup global post data for the template file.
		 */
		if ( $template = $document->get_template() ) {
			$GLOBALS['post'] = get_post( $template->get_id() );
		}

		// Setup Shortcodes
		ShortcodeManager::instance()->setup( $document->get_type() );

		include_once SAB_ABSPATH . 'includes/sab-document-template-functions.php';

		/**
		 * Reset asset data to prevent missing assets
		 * while rendering multiple documents per request.
		 */
		sab_document_styles()->done = array();
		sab_document_styles()->reset();

		do_action( 'storeabill_after_setup_document', $document );
	}

	/**
	 * @param Item $item
	 */
	public static function setup_document_item( $item ) {
		$GLOBALS['document_item'] = $item;
	}

	/**
	 * @param Total $total
	 */
	public static function setup_document_total( $total ) {
		$GLOBALS['document_total'] = $total;
	}

	public static function uninstall() {
		/*
		 * Only remove ALL data if SAB_REMOVE_ALL_DATA constant is set to true in user's
		 * wp-config.php. This is to prevent data loss when deleting the plugin from the backend
		 * and to ensure only the site owner can perform this action.
		 */
		if ( defined( 'SAB_REMOVE_ALL_DATA' ) && true === SAB_REMOVE_ALL_DATA ) {
			self::define_constants();
			self::includes();

			// Tables.
			Install::drop_tables();

			// Clear any cached data that has been removed.
			wp_cache_flush();
		}
	}

	public static function install_integration() {
		self::init();
		self::install();
	}

	private static function includes() {
		include_once SAB_ABSPATH . 'includes/sab-core-functions.php';
		include_once SAB_ABSPATH . 'includes/sab-document-template-hooks.php';

		if ( is_admin() ) {
			Admin::init();
		}

		UploadManager::init();
		PostTypes::init();
		WooCommerce\Helper::init();
		Editor\Helper::init();
		DownloadManager::init();
		Mailer::init();
		ExternalSync\Helper::init();
	}

	/**
	 * Register custom tables within $wpdb object.
	 */
	private static function define_tables() {
		global $wpdb;

		// List of tables without prefixes.
		$tables = array(
			'storeabill_document_itemmeta',
			'storeabill_documentmeta',
			'storeabill_document_noticemeta',
			'storeabill_journals',
			'storeabill_documents',
			'storeabill_document_items',
			'storeabill_document_notices',
		);

		foreach ( $tables as $table ) {
			$wpdb->$table   = $wpdb->prefix . $table;
			$wpdb->tables[] = $table;
		}
	}

	public static function register_data_stores( $stores ) {
		$data_stores = apply_filters( 'storeabill_data_stores', array(
			'invoice'               => 'Vendidero\StoreaBill\DataStores\Invoice',
			'invoice_product_item'  => 'Vendidero\StoreaBill\DataStores\ProductItem',
			'invoice_shipping_item' => 'Vendidero\StoreaBill\DataStores\ShippingItem',
			'invoice_fee_item'      => 'Vendidero\StoreaBill\DataStores\FeeItem',
			'invoice_tax_item'      => 'Vendidero\StoreaBill\DataStores\TaxItem',
			'document_item'         => 'Vendidero\StoreaBill\DataStores\DocumentItem',
			'document_notice'       => 'Vendidero\StoreaBill\DataStores\DocumentNotice',
			'document_template'     => 'Vendidero\StoreaBill\DataStores\DocumentTemplate',
			'journal'               => 'Vendidero\StoreaBill\DataStores\Journal'
		) );

		$data_stores_prefixed = array();

		foreach( $data_stores as $key => $class ) {
			$data_stores_prefixed[ 'sab_' . $key ] = $class;
		}

		return array_merge( $stores, $data_stores_prefixed );
	}

	/**
	 * Return the version of the package.
	 *
	 * @return string
	 */
	public static function get_version() {
		return self::VERSION;
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_path() {
		return dirname( __DIR__ );
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_template_path() {
		return apply_filters( 'storeabill_template_path', 'storeabill/' );
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_url() {
		return plugins_url( '', __DIR__ );
	}

	public static function get_assets_url() {
		return self::get_url() . '/assets';
	}

	public static function get_build_url() {
		return self::get_url() . '/build';
	}

	public static function get_setting( $name ) {
		$option_name = "storeabill_{$name}";

		return get_option( $option_name );
	}

	public static function extended_log( $message ) {
	    if ( apply_filters( 'storeabill_enabled_extended_log', false ) ) {
	        self::log( $message, 'info', 'extended-log' );
	    }
	}

	public static function log( $message, $type = 'info', $source = 'core' ) {
		$logger = sab_get_logger();

		if ( ! $logger ) {
			return;
		}

		if ( ! is_callable( array( $logger, $type ) ) ) {
			$type = 'info';
		}

		$logger->{$type}( $message, array( 'source' => 'storeabill-' . $source ) );
	}
}