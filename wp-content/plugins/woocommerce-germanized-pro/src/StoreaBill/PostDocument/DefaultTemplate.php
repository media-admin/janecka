<?php

namespace Vendidero\Germanized\Pro\StoreaBill\PostDocument;

use Vendidero\StoreaBill\Countries;
use Vendidero\StoreaBill\Editor\Templates\Template;
use Vendidero\StoreaBill\Package;

defined( 'ABSPATH' ) || exit;

class DefaultTemplate extends Template {

	public static function get_template_data() {
		return apply_filters( self::get_hook_prefix() . 'data', array(
			'margins' => array(
				'top'    => '1',
				'left'   => '1',
				'right'  => '1',
				'bottom' => '1',
			)
		) );
	}

	public static function get_screenshot_url() {
		return '';
	}

	public static function get_tags() {
		return array();
	}

	public static function get_title() {
		return __( 'Default', 'woocommerce-germanized-pro' );
	}

	public static function get_document_type() {
		return 'post_document';
	}

	public static function get_name() {
		return 'default';
	}

	protected static function get_light_color() {
		return '#a9a9a9';
	}

	public static function get_html() {
		ob_start();
		?>
		<!-- wp:storeabill/document-styles /-->
		<?php echo self::get_default_header(); ?>

        <!-- wp:storeabill/post-title {"fontSize":"large"} /-->

        <!-- wp:storeabill/post-content /-->

		<?php echo self::get_default_footer(); ?>
		<?php
		$html = ob_get_clean();

		return apply_filters( self::get_hook_prefix() . 'html', self::clean_html_whitespaces( $html ) );
	}
}