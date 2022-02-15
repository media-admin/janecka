<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/wc-gzdp-order-functions.php';

function wc_gzdp_get_privacy_policy_text() {
	$plain_text = apply_filters( 'woocommerce_germanized_pro_checkout_privacy_policy_text', get_option( 'woocommerce_gzdp_checkout_privacy_policy_text' ) );

	if ( ! empty( $plain_text ) ) {
		$plain_text = str_replace(
			array( '{data_security_link}', '{/data_security_link}' ),
			array(
				'<a href="' . esc_url( wc_gzd_get_page_permalink( 'data_security' ) ) . '" target="_blank">',
				'</a>',
			),
			$plain_text
		);
	}

	return $plain_text;
}

function wc_gzdp_get_eu_vat_countries() {
	$countries     = WC()->countries;
	$vat_countries = array();
	$woo_version   = WC_GZDP_Dependencies::instance()->get_plugin_version( 'woocommerce' );

	/**
	 * in Woo 4.0 there the $type parameter for get_european_union_countries was deprecated.
	 * This was reverted in 4.1.
	 */
	if ( version_compare( $woo_version, '4.1', '>=' ) ) {
		$vat_countries = $countries->get_european_union_countries( 'eu_vat' );
	} elseif ( is_callable( array( $countries, 'get_vat_countries' ) ) ) {
		$vat_countries = $countries->get_vat_countries();
		$eu_countries  = $countries->get_european_union_countries();

		// Include EU VAT countries only
		$vat_countries = array_intersect( $vat_countries, $eu_countries );
	} else {
		$vat_countries = $countries->get_european_union_countries( 'eu_vat' );
	}

	return $vat_countries;
}

function wc_gzdp_upload_file( $filename, $stream, $filename_exists = false, $relative = false ) {
	$path = false;

	WC_germanized_pro()->set_upload_dir_filter();

	if ( $filename_exists ) {
		$GLOBALS['wc_gzdp_unique_filename'] = $filename;

		// Make sure that WP overrides file if it does already exist
		add_filter( 'wp_unique_filename', '_wc_gzdp_upload_file_keep_filename', 10, 1 );
	}

	$tmp = wp_upload_bits( $filename,null, $stream );

	if ( isset( $tmp['file'] ) ) {
		$path = $tmp['file'];

		if ( $relative ) {
			$path = WC_germanized_pro()->get_relative_upload_path( $path );
		}
	}

	if ( $filename_exists ) {
		remove_filter( 'wp_unique_filename', '_wc_gzdp_upload_file_keep_filename', 10 );
	}

	WC_germanized_pro()->unset_upload_dir_filter();

	return $path;
}

function _wc_gzdp_upload_file_keep_filename( $new_filename ) {
	return isset( $GLOBALS['wc_gzdp_unique_filename'] ) ? $GLOBALS['wc_gzdp_unique_filename'] : $new_filename;
}

/**
 * Remove Class Filter Without Access to Class Object
 *
 * In order to use the core WordPress remove_filter() on a filter added with the callback
 * to a class, you either have to have access to that class object, or it has to be a call
 * to a static method.  This method allows you to remove filters with a callback to a class
 * you don't have access to.
 *
 * Works with WordPress 1.2+ (4.7+ support added 9-19-2016)
 * Updated 2-27-2017 to use internal WordPress removal for 4.7+ (to prevent PHP warnings output)
 *
 * @param string $tag         Filter to remove
 * @param string $class_name  Class name for the filter's callback
 * @param string $method_name Method name for the filter's callback
 * @param int    $priority    Priority of the filter (default 10)
 *
 * @return bool Whether the function is removed.
 */
function wc_gzdp_remove_class_filter( $tag, $class_name = '', $method_name = '', $priority = 10 ) {
	global $wp_filter;

	// Check that filter actually exists first
	if ( ! isset( $wp_filter[ $tag ] ) ) return FALSE;

	/**
	 * If filter config is an object, means we're using WordPress 4.7+ and the config is no longer
	 * a simple array, rather it is an object that implements the ArrayAccess interface.
	 *
	 * To be backwards compatible, we set $callbacks equal to the correct array as a reference (so $wp_filter is updated)
	 *
	 * @see https://make.wordpress.org/core/2016/09/08/wp_hook-next-generation-actions-and-filters/
	 */
	if ( is_object( $wp_filter[ $tag ] ) && isset( $wp_filter[ $tag ]->callbacks ) ) {
		// Create $fob object from filter tag, to use below
		$fob = $wp_filter[ $tag ];
		$callbacks = &$wp_filter[ $tag ]->callbacks;
	} else {
		$callbacks = &$wp_filter[ $tag ];
	}

	// Exit if there aren't any callbacks for specified priority
	if ( ! isset( $callbacks[ $priority ] ) || empty( $callbacks[ $priority ] ) ) return FALSE;

	// Loop through each filter for the specified priority, looking for our class & method
	foreach( (array) $callbacks[ $priority ] as $filter_id => $filter ) {

		// Filter should always be an array - array( $this, 'method' ), if not goto next
		if ( ! isset( $filter[ 'function' ] ) || ! is_array( $filter[ 'function' ] ) ) continue;

		// If first value in array is not an object, it can't be a class
		if ( ! is_object( $filter[ 'function' ][ 0 ] ) ) continue;

		// Method doesn't match the one we're looking for, goto next
		if ( $filter[ 'function' ][ 1 ] !== $method_name ) continue;

		// Method matched, now let's check the Class
		if ( get_class( $filter[ 'function' ][ 0 ] ) === $class_name ) {

			// WordPress 4.7+ use core remove_filter() since we found the class object
			if( isset( $fob ) ){
				// Handles removing filter, reseting callback priority keys mid-iteration, etc.
				$fob->remove_filter( $tag, $filter['function'], $priority );

			} else {
				// Use legacy removal process (pre 4.7)
				unset( $callbacks[ $priority ][ $filter_id ] );
				// and if it was the only filter in that priority, unset that priority
				if ( empty( $callbacks[ $priority ] ) ) {
					unset( $callbacks[ $priority ] );
				}
				// and if the only filter for that tag, set the tag to an empty array
				if ( empty( $callbacks ) ) {
					$callbacks = array();
				}
				// Remove this filter from merged_filters, which specifies if filters have been sorted
				unset( $GLOBALS['merged_filters'][ $tag ] );
			}

			return TRUE;
		}
	}

	return FALSE;
}

/**
 * Remove Class Action Without Access to Class Object
 *
 * In order to use the core WordPress remove_action() on an action added with the callback
 * to a class, you either have to have access to that class object, or it has to be a call
 * to a static method.  This method allows you to remove actions with a callback to a class
 * you don't have access to.
 *
 * Works with WordPress 1.2+ (4.7+ support added 9-19-2016)
 *
 * @param string $tag         Action to remove
 * @param string $class_name  Class name for the action's callback
 * @param string $method_name Method name for the action's callback
 * @param int    $priority    Priority of the action (default 10)
 *
 * @return bool               Whether the function is removed.
 */
function wc_gzdp_remove_class_action( $tag, $class_name = '', $method_name = '', $priority = 10 ) {
	wc_gzdp_remove_class_filter( $tag, $class_name, $method_name, $priority );
}

function wc_gzdp_legal_checkbox_is_checked( $checkbox_id, $object ) {
	if ( ! $checkbox = wc_gzd_get_legal_checkbox( $checkbox_id ) ) {
		return false;
	}

	$checked = WC_GZDP_Legal_Checkbox_Helper::instance()->checkbox_is_checked( $checkbox, $object );

	return $checked;
}

function wc_gzdp_get_email_helper( $email ) {
	if ( function_exists( 'wc_gzd_get_email_helper' ) ) {
		return wc_gzd_get_email_helper( $email );
	}

	return new WC_GZDP_Email_Helper( $email );
}