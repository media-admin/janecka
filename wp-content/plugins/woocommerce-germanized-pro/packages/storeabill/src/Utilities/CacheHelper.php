<?php
namespace Vendidero\StoreaBill\Utilities;

defined( 'ABSPATH' ) || exit;

class CacheHelper {

	/**
	 * Hook in methods.
	 */
	public static function init() {

	}

	/**
	 * Get prefix for use with wp_cache_set. Allows all cache in a group to be invalidated at once.
	 *
	 * @param  string $group Group of cache to get.
	 * @return string
	 */
	public static function get_cache_prefix( $group ) {
		// Get cache key - uses cache key wc_orders_cache_prefix to invalidate when needed.
		$prefix = wp_cache_get( 'sab_' . $group . '_cache_prefix', $group );

		if ( false === $prefix ) {
			$prefix = microtime();
			wp_cache_set( 'sab_' . $group . '_cache_prefix', $prefix, $group );
		}

		return 'sab_cache_' . $prefix . '_';
	}

	/**
	 * Invalidate cache group.
	 *
	 * @param string $group Group of cache to clear.
	 * @since 3.9.0
	 */
	public static function invalidate_cache_group( $group ) {
		wp_cache_set( 'sab_' . $group . '_cache_prefix', microtime(), $group );
	}

	/**
	 * Prevent caching on certain pages
	 */
	public static function prevent_caching() {
		if ( ! is_blog_installed() ) {
			return;
		}

		if ( function_exists( 'w3tc_objectcache_flush' ) ) {
			w3tc_objectcache_flush();
		}

		if ( function_exists( 'w3tc_dbcache_flush' ) ) {
			w3tc_dbcache_flush();
		}

		self::set_nocache_constants();
		nocache_headers();
	}

	/**
	 * Set constants to prevent caching by some plugins.
	 *
	 * @param  mixed $return Value to return. Previously hooked into a filter.
	 * @return mixed
	 */
	public static function set_nocache_constants( $return = true ) {
		sab_maybe_define_constant( 'DONOTCACHEPAGE', true );
		sab_maybe_define_constant( 'DONOTCACHEOBJECT', true );
		sab_maybe_define_constant( 'DONOTCACHEDB', true );

		return $return;
	}
}
