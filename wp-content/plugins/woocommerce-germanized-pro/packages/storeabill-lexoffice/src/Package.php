<?php

namespace Vendidero\StoreaBill\Lexoffice;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class Package {

	/**
	 * Version.
	 *
	 * @var string
	 */
	const VERSION = '1.2.1';

	/**
	 * Init the package.
	 */
	public static function init() {

		if ( ! self::has_dependencies() ) {
			return;
		}

		self::init_hooks();
		self::includes();
	}

	public static function has_dependencies() {
		return class_exists( '\Vendidero\StoreaBill\Package' );
	}

	protected static function init_hooks() {
		add_filter( 'storeabill_external_sync_handlers', array( __CLASS__, 'register_handler' ), 10 );
	}

	public static function register_handler( $handlers ) {
		$handlers[] = '\Vendidero\StoreaBill\Lexoffice\Sync';

		return $handlers;
	}

	public static function install() {}

	private static function includes() {}

	public static function get_client_id() {
		return defined( 'SAB_LEXOFFICE_API_CLIENT_ID' ) ? SAB_LEXOFFICE_API_CLIENT_ID : 'c3475683-2715-4070-8581-8af631899199';
	}

	public static function get_client_secret() {
		return defined( 'SAB_LEXOFFICE_API_CLIENT_SECRET' ) ? SAB_LEXOFFICE_API_CLIENT_SECRET : '3IlL8=B*#.8xFbb2pZ49anf[0';
	}

	public static function get_auth_url() {
		return defined( 'SAB_LEXOFFICE_AUTH_URL' ) ? SAB_LEXOFFICE_AUTH_URL : 'https://app.lexoffice.de/api/oauth2/';
	}

	public static function get_api_url() {
		return defined( 'SAB_LEXOFFICE_API_URL' ) ? SAB_LEXOFFICE_API_URL : 'https://api.lexoffice.io/v1/';
	}

	public static function get_app_url() {
		return defined( 'SAB_LEXOFFICE_APP_URL' ) ? SAB_LEXOFFICE_APP_URL : 'https://app.lexoffice.de/';
	}

	public static function log( $message, $type = 'info' ) {
		\Vendidero\StoreaBill\Package::log( $message, $type, 'lexoffice' );
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
	public static function get_url() {
		return plugins_url( '', __DIR__ );
	}
}