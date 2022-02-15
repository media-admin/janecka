<?php

namespace Vendidero\Germanized\Pro\StoreaBill\PackingSlip;

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
		return 'packing_slip';
	}

	public static function get_name() {
		return 'default';
	}

	protected static function get_light_color() {
		return '#a9a9a9';
	}

	protected static function has_price_column() {
	    return apply_filters( self::get_hook_prefix() . 'has_price_column', true );
    }

	public static function get_html() {
		$heading_bg_color = sab_hex_lighter( self::get_light_color(), 70 );
		$preview          = sab_get_document_preview( self::get_document_type(), true );
		ob_start();
		?>
		<!-- wp:storeabill/document-styles /-->
		<?php echo self::get_default_header(); ?>

		<!-- wp:storeabill/address -->
		<div class="wp-block-storeabill-address sab-document-address has-text-align-left">
			<p class="address-heading">
				<span style="font-size:10px" class="has-inline-text-size"><span style="color:<?php echo esc_attr( self::get_light_color() ); ?>" class="has-inline-color"><?php echo apply_filters( self::get_hook_prefix() . 'address_header', Countries::get_formatted_base_address( ' - ' ) ); ?></span></span>
			</p>
			<p class="address-content">
				<span class="placeholder-content" contenteditable="false"><span class="editor-placeholder"></span>{content}</span>
			</p>
		</div>
		<!-- /wp:storeabill/address -->

		<!-- wp:spacer {"height":28} -->
		<div style="height:28px" aria-hidden="true" class="wp-block-spacer"></div>
		<!-- /wp:spacer -->

		<!-- wp:storeabill/document-title {"customFontSize":"25"} -->
		<p class="has-text-align-left" style="font-size:25px"><strong><?php echo apply_filters( self::get_hook_prefix() . 'document_title', strtoupper( __( 'Packing Slip', 'woocommerce-germanized-pro' ) ) ); ?></strong></p>
		<!-- /wp:storeabill/document-title -->

		<!-- wp:columns -->
		<div class="wp-block-columns">
			<!-- wp:column -->
			<div class="wp-block-column">
				<!-- wp:paragraph -->
				<p>
					<?php echo esc_html__( 'Shipment number', 'woocommerce-germanized-pro' ); ?>: <span class="document-shortcode sab-tooltip" contenteditable="false" data-tooltip="<?php echo esc_attr__( 'Formatted shipment number', 'woocommerce-germanized-pro' ); ?>" data-shortcode="document?data=shipment_number"><span class="editor-placeholder"></span><?php echo $preview->get_shipment_number(); ?></span><br>
					<?php echo esc_html__( 'Order number', 'woocommerce-germanized-pro' ); ?>: <span class="document-shortcode sab-tooltip" contenteditable="false" data-tooltip="<?php echo esc_attr__( 'Order number', 'woocommerce-germanized-pro' ); ?>" data-shortcode="document?data=order_number"><span class="editor-placeholder"></span><?php echo $preview->get_order_number(); ?></span><br>
					<?php do_action( self::get_hook_prefix() . 'after_document_details' ); ?><br>
				</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column -->
			<div class="wp-block-column">
				<!-- wp:storeabill/document-date {"align":"right"} -->
				<p class="has-text-align-right"><?php echo esc_html__( 'Date', 'woocommerce-germanized-pro' ); ?>: <span class="placeholder-content sab-tooltip" contenteditable="false" data-tooltip="<?php echo esc_attr__( 'Document date', 'woocommerce-germanized-pro' ); ?>" ><span class="editor-placeholder"></span>{content}</span></p>
				<!-- /wp:storeabill/document-date -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->

		<?php do_action( self::get_hook_prefix() . 'before_item_table' ); ?>

		<!-- wp:spacer {"height":20} -->
		<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
		<!-- /wp:spacer -->

		<!-- wp:storeabill/item-table {"className":"is-style-even","customBorderColor":"<?php echo esc_attr( self::get_light_color() ); ?>","borders":["horizontal"],"headingTextColor":"black","customHeadingBackgroundColor":"<?php echo esc_attr( $heading_bg_color ); ?>","hasDenseLayout":<?php echo self::has_dense_layout() ? 'true' : 'false'; ?>} -->
		<div class="wp-block-storeabill-item-table is-style-even has-border-horizontal">
			<!-- wp:storeabill/item-table-column {"width":45,"headingTextColor":"#000000","headingBackgroundColor":"<?php echo esc_attr( $heading_bg_color ); ?>"} -->
			<div class="wp-block-storeabill-item-table-column is-horizontally-aligned-left" style="flex-basis:45%">
				<span class="item-column-heading-text"><strong><?php echo esc_html_x( 'Name', 'item-table-column', 'woocommerce-germanized-pro' ); ?></strong></span>
				<!-- wp:storeabill/item-name -->
				<p class="wp-block-storeabill-item-name sab-block-item-content"><span class="placeholder-content sab-tooltip" contenteditable="false" data-tooltip="<?php echo esc_attr_x( 'Name', 'item-table-name', 'woocommerce-germanized-pro' ); ?>"><span class="editor-placeholder"></span>{content}</span> (<span class="document-shortcode sab-tooltip" contenteditable="false" data-tooltip="<?php echo esc_attr__( 'SKU', 'woocommerce-germanized-pro' ); ?>" data-shortcode="document_item?data=sku"><span class="editor-placeholder"></span>123</span>)</p>
				<!-- /wp:storeabill/item-name -->

				<?php do_action( self::get_hook_prefix() . 'after_item_name' ); ?>

				<!-- wp:storeabill/item-attributes {"customTextColor":"<?php echo esc_attr( self::get_light_color() ); ?>","fontSize":"small"} /-->

				<?php do_action( self::get_hook_prefix() . 'after_item_attributes' ); ?>
			</div>
			<!-- /wp:storeabill/item-table-column -->

			<!-- wp:storeabill/item-table-column {"align":"<?php echo ( self::has_price_column() ? 'center' : 'right' ); ?>","headingTextColor":"#000000","headingBackgroundColor":"<?php echo esc_attr( $heading_bg_color ); ?>"} -->
			<div class="wp-block-storeabill-item-table-column is-horizontally-aligned-<?php echo ( self::has_price_column() ? 'center' : 'right' ); ?>">
				<span class="item-column-heading-text"><strong><?php echo esc_html_x( 'Quantity', 'item-table-column', 'woocommerce-germanized-pro' ); ?></strong></span>
				<!-- wp:storeabill/item-quantity -->
				<p class="wp-block-storeabill-item-quantity sab-block-item-content">{content}</p>
				<!-- /wp:storeabill/item-quantity -->

				<?php do_action( self::get_hook_prefix() . 'after_item_quantity' ); ?>
			</div>
			<!-- /wp:storeabill/item-table-column -->

            <?php if ( self::has_price_column() ) : ?>
                <!-- wp:storeabill/item-table-column {"align":"right","headingTextColor":"#000000","headingBackgroundColor":"<?php echo esc_attr( $heading_bg_color ); ?>"} -->
                <div class="wp-block-storeabill-item-table-column is-horizontally-aligned-right">
                    <span class="item-column-heading-text"><strong><?php echo esc_html_x( 'Total', 'item-table-column', 'woocommerce-germanized-pro' ); ?></strong></span>
                    <!-- wp:storeabill/item-line-total {"showPricesIncludingTax":true} -->
                    <p class="wp-block-storeabill-item-line-total sab-block-item-content">{content}</p>
                    <!-- /wp:storeabill/item-line-total -->

                    <?php do_action( self::get_hook_prefix() . 'after_item_line_total' ); ?>
                </div>
                <!-- /wp:storeabill/item-table-column -->
            <?php endif; ?>
		</div>
		<!-- /wp:storeabill/item-table -->

		<?php do_action( self::get_hook_prefix() . 'after_item_table' ); ?>

		<?php echo self::get_default_footer(); ?>
		<?php
		$html = ob_get_clean();

		return apply_filters( self::get_hook_prefix() . 'html', self::clean_html_whitespaces( $html ) );
	}
}