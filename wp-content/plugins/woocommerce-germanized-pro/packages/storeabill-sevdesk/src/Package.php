<?php

namespace Vendidero\StoreaBill\sevDesk;

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
	const VERSION = '1.1.3';

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
		$handlers[] = '\Vendidero\StoreaBill\sevDesk\Sync';

		return $handlers;
	}

	public static function install() {}

	private static function includes() {}

	public static function get_api_url() {
		return 'https://my.sevdesk.de/api/v1/';
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

	public static function get_app_url() {
		return 'https://my.sevdesk.de/#/';
	}
}