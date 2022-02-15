<?php

namespace Vendidero\StoreaBill;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class Install {

	/**
	 * DB updates and callbacks that need to be run per version.
	 *
	 * @var array
	 */
	private static $db_updates = array(
		'1.2.0' => array(
			array( '\Vendidero\StoreaBill\Updater', 'update_120_net_tax_item_totals' ),
			array( '\Vendidero\StoreaBill\Updater', 'update_120_db_version' ),
		),
		'1.2.1' => array(
			array( '\Vendidero\StoreaBill\Updater', 'update_121_default_hidden_columns' ),
			array( '\Vendidero\StoreaBill\Updater', 'update_121_db_version' ),
		),
	);

	public static function install() {
		$current_version = get_option( 'storeabill_version', null );

		if ( ! is_null( $current_version ) ) {
			$current_db_version = get_option( 'storeabill_db_version', null );

			/**
			 * Versions < 1.2.0 did not yet support db versions. Add the db version once.
			 */
			if ( is_null( $current_db_version ) && version_compare( $current_version, '1.2.0', '<' ) ) {
				add_option( 'storeabill_db_version', $current_version );
			}
		}

		self::create_upload_dir();
		self::create_tables();
		self::create_capabilities();
		self::setup_environment();
		self::create_default_templates();
		self::create_journals();
		self::update_version();
		self::maybe_update_db_version();
	}

	protected static function update_version() {
		delete_option( 'storeabill_version' );
		add_option( 'storeabill_version', Package::get_version() );
	}

	private static function setup_environment() {
		Package::init();

		PostTypes::register_post_types();
		Package::register_document_types();
	}

	public static function create_default_templates() {
		foreach( sab_get_document_types() as $document_type ) {
			$existing = Package::get_setting( $document_type . '_default_template' );

			if ( ! $existing || ! sab_get_document_template( $existing ) ) {
				$template = sab_create_document_template( $document_type, 'default', true );

				if ( $template && $template->get_id() > 0 ) {
					update_option( 'storeabill_' . $document_type . '_default_template', $template->get_id() );
				}
 			}
		}
	}

	public static function create_journals() {
		foreach( sab_get_document_types() as $document_type ) {
			if ( ! $journal = sab_get_journal( $document_type ) ) {
				sab_create_journal( $document_type );
			}
		}
	}

	private static function create_tables() {
		global $wpdb;

		$wpdb->hide_errors();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( self::get_schema() );
	}

	/**
	 * Get capabilities for StoreaBill - these are assigned to admin/shop manager during installation or reset.
	 *
	 * @return array
	 */
	private static function get_core_capabilities() {
		$capabilities = array();

		$capabilities['core'] = array(
			'manage_storeabill',
		);

		$capability_types = array(
			'invoice',
			'invoice_cancellation',
			'document_template'
		);

		foreach ( $capability_types as $capability_type ) {
			$capabilities[ $capability_type ] = self::get_capabilities( $capability_type );
		}

		return $capabilities;
	}

	public static function get_capabilities( $document_type ) {
		return array(
			"edit_{$document_type}",
			"edit_{$document_type}s",
			"read_{$document_type}",
			"delete_{$document_type}",
			"create_{$document_type}s",
			"edit_others_{$document_type}s",
			"delete_{$document_type}s",
			"delete_others_{$document_type}s",
		);
	}

	/**
	 * Create roles and capabilities.
	 */
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

	private static function create_upload_dir() {
		UploadManager::maybe_set_upload_dir();

		$dir      = UploadManager::get_upload_dir();
		$font_dir = UploadManager::get_font_path();

		if ( ! @is_dir( $dir['basedir'] ) ) {
			@mkdir( $dir['basedir'] );
		}

		if ( ! @file_exists( trailingslashit( $dir['basedir'] ) . '.htaccess' ) ) {
			$content  = 'deny from all' . "\n";
			$content .= '<FilesMatch "\.(?:ttf|woff)$">' . "\n";
			$content .= 'Order deny,allow' . "\n";
			$content .= 'Allow from all' . "\n";
			$content .= '</FilesMatch>';

			@file_put_contents( trailingslashit( $dir['basedir'] ) . '.htaccess', $content );
		}

		if ( ! @file_exists( trailingslashit( $dir['basedir'] ) . 'index.php' ) ) {
			@touch( trailingslashit( $dir['basedir'] ) . 'index.php' );
		}

		/**
		 * Fonts
		 */
		if ( ! @is_dir( $font_dir ) ) {
			@mkdir( $font_dir );
		}

		/**
		 * Copy default PublicSans fonts into font dir
		 */
		if ( ! @file_exists( trailingslashit( $font_dir ) . 'PublicSans.ttf' ) ) {
			$library_font_path = Package::get_path() . '/assets/fonts';
			$files             = @glob($library_font_path . '/*.ttf' );
			$files             = array_merge( $files, @glob($library_font_path . '/*.woff' ) );

			foreach( $files as $file ) {
				$file_to_go = str_replace( trailingslashit( $library_font_path ), trailingslashit( $font_dir ), $file );

				if ( ! @file_exists( trailingslashit( $font_dir ) . basename( $file ) ) ) {
					@copy( $file, $file_to_go );
				}
			}
		}
	}

	/**
	 * Return a list of StoreaBill tables. Used to make sure all SAB tables are dropped when uninstalling the plugin
	 * in a single site or multi site environment.
	 *
	 * @return array SAB tables.
	 */
	public static function get_tables() {
		global $wpdb;

		$tables = array(
			"{$wpdb->prefix}storeabill_document_items",
			"{$wpdb->prefix}storeabill_document_itemmeta",
			"{$wpdb->prefix}storeabill_journals",
			"{$wpdb->prefix}storeabill_document_notices",
			"{$wpdb->prefix}storeabill_document_noticemeta",
			"{$wpdb->prefix}storeabill_documents",
			"{$wpdb->prefix}storeabill_documentmeta",
		);

		/**
		 * Filter the list of known StoreaBill tables.
		 *
		 * If StoreaBill plugins need to add new tables, they can inject them here.
		 *
		 * @param array $tables An array of StoreaBill-specific database table names.
		 */
		$tables = apply_filters( 'storeabill_install_get_tables', $tables );

		return $tables;
	}

	/**
	 * Drop StoreaBill tables.
	 *
	 * @return void
	 */
	public static function drop_tables() {
		global $wpdb;

		$tables = self::get_tables();

		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
	}

	private static function get_schema() {
		global $wpdb;

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		$tables = "
CREATE TABLE {$wpdb->prefix}storeabill_document_items (
  document_item_id BIGINT UNSIGNED NOT NULL auto_increment,
  document_item_name TEXT NOT NULL,
  document_item_type varchar(200) NOT NULL DEFAULT '',
  document_item_parent_id BIGINT UNSIGNED NOT NULL,
  document_item_reference_id BIGINT UNSIGNED NOT NULL,
  document_item_quantity DECIMAL (8,3) UNSIGNED NOT NULL,
  document_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY  (document_item_id),
  KEY document_id (document_id),
  KEY document_item_reference_id (document_item_reference_id),
  KEY document_item_parent_id (document_item_parent_id)
) $collate;
CREATE TABLE {$wpdb->prefix}storeabill_document_itemmeta (
  meta_id BIGINT UNSIGNED NOT NULL auto_increment,
  storeabill_document_item_id BIGINT UNSIGNED NOT NULL,
  meta_key varchar(255) default NULL,
  meta_value longtext NULL,
  PRIMARY KEY  (meta_id),
  KEY storeabill_document_item_id (storeabill_document_item_id),
  KEY meta_key (meta_key(32))
) $collate;
CREATE TABLE {$wpdb->prefix}storeabill_journals (
  journal_id BIGINT UNSIGNED NOT NULL auto_increment,
  journal_type varchar(70) NOT NULL DEFAULT '',
  journal_name varchar(200) NOT NULL,
  journal_is_archived varchar(10) NOT NULL DEFAULT '',
  journal_number_format varchar(200) NOT NULL,
  journal_number_min_size TINYINT NOT NULL DEFAULT 0,
  journal_last_number bigint unsigned NOT NULL,
  journal_date_last_reset datetime NOT NULL default '0000-00-00 00:00:00',
  journal_date_last_reset_gmt datetime NOT NULL default '0000-00-00 00:00:00',
  journal_reset_interval varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY  (journal_id),
  UNIQUE KEY journal_type (journal_type)
) $collate;
CREATE TABLE {$wpdb->prefix}storeabill_document_notices (
  document_notice_id BIGINT UNSIGNED NOT NULL auto_increment,
  document_notice_type varchar(200) NOT NULL DEFAULT '',
  document_notice_date_created datetime NOT NULL default '0000-00-00 00:00:00',
  document_notice_date_created_gmt datetime NOT NULL default '0000-00-00 00:00:00',
  document_notice_text longtext NULL,
  document_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY  (document_notice_id),
  KEY document_id (document_id)
) $collate;
CREATE TABLE {$wpdb->prefix}storeabill_document_noticemeta (
  meta_id BIGINT UNSIGNED NOT NULL auto_increment,
  storeabill_document_notice_id BIGINT UNSIGNED NOT NULL,
  meta_key varchar(255) default NULL,
  meta_value longtext NULL,
  PRIMARY KEY  (meta_id),
  KEY storeabill_document_notice_id (storeabill_document_notice_id),
  KEY meta_key (meta_key(32))
) $collate;
CREATE TABLE {$wpdb->prefix}storeabill_documents (
  document_id BIGINT UNSIGNED NOT NULL auto_increment,
  document_date_created datetime NOT NULL default '0000-00-00 00:00:00',
  document_date_created_gmt datetime NOT NULL default '0000-00-00 00:00:00',
  document_date_modified datetime NOT NULL default '0000-00-00 00:00:00',
  document_date_modified_gmt datetime NOT NULL default '0000-00-00 00:00:00',
  document_date_sent datetime NOT NULL default '0000-00-00 00:00:00',
  document_date_sent_gmt datetime NOT NULL default '0000-00-00 00:00:00',
  document_date_custom datetime NOT NULL default '0000-00-00 00:00:00',
  document_date_custom_gmt datetime NOT NULL default '0000-00-00 00:00:00',
  document_date_custom_extra datetime NOT NULL default '0000-00-00 00:00:00',
  document_date_custom_extra_gmt datetime NOT NULL default '0000-00-00 00:00:00',
  document_status varchar(20) NOT NULL default 'draft',
  document_number varchar(200) NOT NULL DEFAULT '',
  document_formatted_number varchar(200) NOT NULL DEFAULT '',
  document_customer_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  document_author_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  document_country varchar(2) NOT NULL DEFAULT '',
  document_index longtext NOT NULL DEFAULT '',
  document_relative_path varchar(260) NOT NULL DEFAULT '',
  document_reference_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  document_reference_type varchar(200) NOT NULL DEFAULT '',
  document_parent_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  document_type varchar(200) NOT NULL DEFAULT '',
  document_journal_type varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY  (document_id),
  KEY document_reference_id (document_reference_id),
  KEY document_customer_id (document_customer_id),
  KEY document_author_id (document_author_id),
  KEY document_parent_id (document_parent_id)
) $collate;
CREATE TABLE {$wpdb->prefix}storeabill_documentmeta (
  meta_id BIGINT UNSIGNED NOT NULL auto_increment,
  storeabill_document_id BIGINT UNSIGNED NOT NULL,
  meta_key varchar(255) default NULL,
  meta_value longtext NULL,
  PRIMARY KEY  (meta_id),
  KEY storeabill_document_id (storeabill_document_id),
  KEY meta_key (meta_key(32))
) $collate;";

		return $tables;
	}

	/**
	 * Is a DB update needed?
	 *
	 * @since  3.2.0
	 * @return boolean
	 */
	public static function needs_db_update() {
		$current_db_version = get_option( 'storeabill_db_version', null );
		$updates            = self::get_db_update_callbacks();
		$update_versions    = array_keys( $updates );
		usort( $update_versions, 'version_compare' );

		return ! is_null( $current_db_version ) && version_compare( $current_db_version, end( $update_versions ), '<' );
	}

	/**
	 * See if we need to show or run database updates during install.
	 *
	 * @since 3.2.0
	 */
	private static function maybe_update_db_version() {
		if ( self::needs_db_update() ) {
			if ( apply_filters( 'storeabill_enable_auto_update_db', true ) ) {
				self::update();
			}
		} else {
			self::update_db_version();
		}
	}

	/**
	 * Get list of DB update callbacks.
	 *
	 * @since  3.0.0
	 * @return array
	 */
	public static function get_db_update_callbacks() {
		return self::$db_updates;
	}

	/**
	 * Update DB version to current.
	 *
	 * @param string|null $version New StoreaBill DB version or null.
	 */
	public static function update_db_version( $version = null ) {
		delete_option( 'storeabill_db_version' );

		add_option( 'storeabill_db_version', is_null( $version ) ? Package::get_version() : $version );
	}

	/**
	 * Push all needed DB updates to the queue for processing.
	 */
	private static function update() {
		$current_db_version = get_option( 'storeabill_db_version' );
		$loop               = 0;

		foreach ( self::get_db_update_callbacks() as $version => $update_callbacks ) {
			if ( version_compare( $current_db_version, $version, '<' ) ) {
				foreach ( $update_callbacks as $update_callback ) {
					WC()->queue()->schedule_single(
						time() + $loop,
						'storeabill_run_update_callback',
						array(
							'update_callback' => $update_callback,
						),
						'storeabill-db-updates'
					);
					$loop++;
				}
			}
		}
	}

	/**
	 * Run an update callback when triggered by ActionScheduler.
	 *
	 * @since 3.6.0
	 * @param string $callback Callback name.
	 */
	public static function run_update_callback( $callback ) {
		if ( is_callable( $callback ) ) {
			self::run_update_callback_start( $callback );
			$result = (bool) call_user_func( $callback );
			self::run_update_callback_end( $callback, $result );
		}
	}

	/**
	 * Triggered when a callback will run.
	 *
	 * @since 3.6.0
	 * @param string $callback Callback name.
	 */
	protected static function run_update_callback_start( $callback ) {
		sab_maybe_define_constant( 'SAB_UPDATING', true );
	}

	/**
	 * Triggered when a callback has ran.
	 *
	 * @since 3.6.0
	 * @param string $callback Callback name.
	 * @param bool   $result Return value from callback. Non-false need to run again.
	 */
	protected static function run_update_callback_end( $callback, $result ) {
		if ( $result ) {
			WC()->queue()->add(
				'storeabill_run_update_callback',
				array(
					'update_callback' => $callback,
				),
				'storeabill-db-updates'
			);
		}
	}
}