<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Frontend class
 *
 * @author YITH
 * @package YITH\ZoomMagnifier\Classes
 * @version 1.1.2
 */

if ( ! defined( 'YITH_WCMG' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCMG_Frontend_Premium' ) ) {
	/**
	 * Admin class.
	 * The class manage all the Frontend behaviors.
	 *
	 * @since 1.0.0
	 */
	class YITH_WCMG_Frontend_Premium extends YITH_WCMG_Frontend {

		/**
		 * Constructor
		 *
		 * @access public
		 * @since 1.0.0
		 */
		public function __construct() { // phpcs:ignore
			parent::__construct();
		}

		/**
		 * Enqueue styles and scripts
		 *
		 * @access public
		 * @return void
		 * @since 1.0.0
		 */
		public function enqueue_styles_scripts() {

			/**
			 * YITH_WooCommerce_Zoom_Magnifier_Premium variable.
			 *
			 *  @var YITH_WooCommerce_Zoom_Magnifier_Premium $yith_wcmg
			 */
			global $yith_wcmg;

			if ( $yith_wcmg->is_product_excluded() ) {
				return;
			}

			parent::enqueue_styles_scripts();

		}

		/**
		 * Render zoom.
		 */
		public function render() {

			/**
			 * YITH_WooCommerce_Zoom_Magnifier_Premium variable.
			 *
			 *  @var YITH_WooCommerce_Zoom_Magnifier_Premium $yith_wcmg
			 */
			global $yith_wcmg;

			// Check if the plugin have to interact with current product.
			if ( $yith_wcmg->is_product_excluded() ) {
				return;
			}

			// Call the parent method.
			parent::render();

		}
	}
}
