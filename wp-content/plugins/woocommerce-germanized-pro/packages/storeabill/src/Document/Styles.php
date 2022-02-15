<?php

namespace Vendidero\StoreaBill\Document;

defined( 'ABSPATH' ) || exit;

/**
 * Core class used to register styles.
 *
 * @package WordPress
 * @uses WP_Dependencies
 * @since 2.6.0
 */
class Styles extends \WP_Styles {

	public function __construct() {
		/**
		 * Fires when the WP_Styles instance is initialized.
		 *
		 * @since 2.6.0
		 *
		 * @param \WP_Styles &$this WP_Styles instance, passed by reference.
		 */
		do_action_ref_array( 'storeabill_default_document_styles', array( &$this ) );
	}

	/**
	 * Use relative paths in PDF mode to make sure that mPDF doesn't need
	 * to look up via file_get_contents.
	 *
	 * @param string $src    The source of the enqueued style.
	 * @param string $ver    The version of the enqueued style.
	 * @param string $handle The style's registered handle.
	 *
	 * @return string Style's fully-qualified URL.
	 */
	public function _css_href( $src, $ver, $handle ) {
		$src = parent::_css_href( $src, $ver, $handle );

		return sab_get_asset_path_by_url( $src );
 	}
}