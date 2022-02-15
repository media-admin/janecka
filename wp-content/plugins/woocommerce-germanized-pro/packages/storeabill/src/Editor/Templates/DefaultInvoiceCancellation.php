<?php

namespace Vendidero\StoreaBill\Editor\Templates;

use Vendidero\StoreaBill\Countries;
use Vendidero\StoreaBill\Package;

defined( 'ABSPATH' ) || exit;

class DefaultInvoiceCancellation extends Template {

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
		return _x( 'Default', 'storeabill-core', 'woocommerce-germanized-pro' );
	}

	public static function get_document_type() {
		return 'invoice_cancellation';
	}

	public static function get_name() {
		return 'default';
	}

	protected static function get_light_color() {
		return '#a9a9a9';
	}

	protected static function prices_include_tax() {
		return apply_filters( self::get_hook_prefix() . 'prices_include_tax', true );
	}

	protected static function show_net_totals_per_tax_rate() {
		return apply_filters( self::get_hook_prefix() . 'show_net_totals_per_tax_rate', false );
	}

	protected static function show_row_based_discounts() {
		return apply_filters( self::get_hook_prefix() . 'show_row_based_discounts', false );
	}

	protected static function show_row_based_tax_rates() {
		return apply_filters( self::get_hook_prefix() . 'show_row_based_tax_rates', false );
	}

	protected static function show_differential_taxation_notice() {
		return apply_filters( self::get_hook_prefix() . 'show_differential_taxation_notice', false );
	}

	protected static function show_item_sku() {
		return apply_filters( self::get_hook_prefix() . 'show_item_sku', false );
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

		<!-- wp:storeabill/document-title -->
		<p class="has-text-align-left">
            <strong><span style="font-size:25px" class="has-inline-text-size"><?php echo apply_filters( self::get_hook_prefix() . 'document_title', strtoupper( _x( 'Cancellation', 'storeabill-core', 'woocommerce-germanized-pro' ) ) ); ?></span></strong><br>
			<?php printf( esc_html_x( 'to Invoice %s', 'storeabill-core', 'woocommerce-germanized-pro' ), '<span class="document-shortcode sab-tooltip" contenteditable="false" data-tooltip="' . esc_attr_x( 'Parent invoice formatted number', 'storeabill-core', 'woocommerce-germanized-pro' ) . '" data-shortcode="document?data=parent_formatted_number"><span class="editor-placeholder"></span>' . $preview->get_parent_formatted_number() . '</span>' ); ?>
        </p>
		<!-- /wp:storeabill/document-title -->

        <!-- wp:columns -->
		<div class="wp-block-columns">
			<!-- wp:column -->
			<div class="wp-block-column">
				<!-- wp:paragraph -->
				<p>
                    <?php echo esc_html_x( 'Cancellation number', 'storeabill-core', 'woocommerce-germanized-pro' ); ?>: <span class="document-shortcode sab-tooltip" contenteditable="false" data-tooltip="<?php echo esc_attr_x( 'Formatted document number', 'storeabill-core', 'woocommerce-germanized-pro' ); ?>" data-shortcode="document?data=formatted_number"><span class="editor-placeholder"></span><?php echo $preview->get_formatted_number(); ?></span><br>

					<?php do_action( self::get_hook_prefix() . 'after_document_details' ); ?><br>
				</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column -->
			<div class="wp-block-column">
				<!-- wp:storeabill/document-date {"align":"right"} -->
				<p class="has-text-align-right"><?php echo esc_html_x( 'Date', 'storeabill-core', 'woocommerce-germanized-pro' ); ?>: <span class="placeholder-content sab-tooltip" contenteditable="false" data-tooltip="<?php echo esc_attr_x( 'Document Date', 'storeabill-core', 'woocommerce-germanized-pro' ); ?>" ><span class="editor-placeholder"></span>{content}</span></p>
				<!-- /wp:storeabill/document-date -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->

		<?php do_action( self::get_hook_prefix() . 'before_item_table' ); ?>

		<!-- wp:spacer {"height":20} -->
		<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
		<!-- /wp:spacer -->

        <!-- wp:storeabill/item-table {"className":"is-style-even","customBorderColor":"<?php echo esc_attr( self::get_light_color() ); ?>","borders":["horizontal"],"headingTextColor":"black","customHeadingBackgroundColor":"<?php echo esc_attr( $heading_bg_color ); ?>","showPricesIncludingTax":<?php echo self::prices_include_tax() ? 'true' : 'false'; ?>,"hasDenseLayout":<?php echo self::has_dense_layout() ? 'true' : 'false'; ?>} -->
        <div class="wp-block-storeabill-item-table is-style-even has-border-horizontal">
            <!-- wp:storeabill/item-table-column {"width":45,"headingTextColor":"#000000","headingBackgroundColor":"<?php echo esc_attr( $heading_bg_color ); ?>"} -->
            <div class="wp-block-storeabill-item-table-column is-horizontally-aligned-left" style="flex-basis:45%">
                <span class="item-column-heading-text"><strong><?php echo esc_html_x( 'Name', 'storeabill-item-table-column', 'woocommerce-germanized-pro' ); ?></strong></span>
                <!-- wp:storeabill/item-name -->
                <p class="wp-block-storeabill-item-name sab-block-item-content"><span class="placeholder-content sab-tooltip" contenteditable="false" data-tooltip="<?php echo esc_attr_x( 'Name', 'storeabill-item-table-column', 'woocommerce-germanized-pro' ); ?>"><span class="editor-placeholder"></span>{content}</span><?php echo ( self::show_item_sku() ? ' (<span class="document-shortcode sab-tooltip" contenteditable="false" data-tooltip="' . esc_attr__( 'SKU', 'woocommerce-germanized-pro' ). '" data-shortcode="document_item?data=sku"><span class="editor-placeholder"></span>123</span>)' : '' ); ?></p>
                <!-- /wp:storeabill/item-name -->

				<?php do_action( self::get_hook_prefix() . 'after_item_name' ); ?>

                <!-- wp:storeabill/item-attributes {"customTextColor":"<?php echo esc_attr( self::get_light_color() ); ?>","fontSize":"small"} /-->

				<?php do_action( self::get_hook_prefix() . 'after_item_attributes' ); ?>

	            <?php if ( self::show_differential_taxation_notice() ) : ?>
                    <!-- wp:storeabill/item-differential-taxation-notice {"customTextColor":"<?php echo esc_attr( self::get_light_color() ); ?>","fontSize":"small"} -->
                    <p class="wp-block-storeabill-item-differential-taxation-notice sab-block-item-content has-text-color has-small-font-size" style="color:<?php echo esc_attr( self::get_light_color() ); ?>"><?php echo apply_filters( self::get_hook_prefix() . 'differential_taxation_notice', esc_html_x( 'Subject to differential taxation under §25a UStG.', 'storeabill-core', 'woocommerce-germanized-pro' ) ); ?></p>
                    <!-- /wp:storeabill/item-differential-taxation-notice -->
	            <?php endif; ?>
            </div>
            <!-- /wp:storeabill/item-table-column -->

            <!-- wp:storeabill/item-table-column {"align":"center","headingTextColor":"#000000","headingBackgroundColor":"<?php echo esc_attr( $heading_bg_color ); ?>"} -->
            <div class="wp-block-storeabill-item-table-column is-horizontally-aligned-center">
                <span class="item-column-heading-text"><strong><?php echo esc_html_x( 'Quantity', 'storeabill-item-table-column', 'woocommerce-germanized-pro' ); ?></strong></span>
                <!-- wp:storeabill/item-quantity -->
                <p class="wp-block-storeabill-item-quantity sab-block-item-content">{content}</p>
                <!-- /wp:storeabill/item-quantity -->

				<?php do_action( self::get_hook_prefix() . 'after_item_quantity' ); ?>
            </div>
            <!-- /wp:storeabill/item-table-column -->

            <!-- wp:storeabill/item-table-column {"align":"center","headingTextColor":"#000000","headingBackgroundColor":"<?php echo esc_attr( $heading_bg_color ); ?>"} -->
            <div class="wp-block-storeabill-item-table-column is-horizontally-aligned-center">
                <span class="item-column-heading-text"><strong><?php echo esc_html_x( 'Price', 'storeabill-item-table-column', 'woocommerce-germanized-pro' ); ?></strong></span>
                <!-- wp:storeabill/item-price {"showPricesIncludingTax":<?php echo self::prices_include_tax() ? 'true' : 'false'; ?>} -->
                <p class="wp-block-storeabill-item-price sab-block-item-content">{content}</p>
                <!-- /wp:storeabill/item-price -->

				<?php do_action( self::get_hook_prefix() . 'after_item_price' ); ?>
            </div>
            <!-- /wp:storeabill/item-table-column -->

			<?php if ( self::show_row_based_discounts() ) : ?>
                <!-- wp:storeabill/item-table-column {"align":"center","headingTextColor":"#000000","headingBackgroundColor":"<?php echo esc_attr( $heading_bg_color ); ?>"} -->
                <div class="wp-block-storeabill-item-table-column is-horizontally-aligned-center">
                    <span class="item-column-heading-text"><strong><?php echo esc_html_x( 'Discount', 'storeabill-item-table-column', 'woocommerce-germanized-pro' ); ?></strong></span>
                    <!-- wp:storeabill/item-discount {"showPricesIncludingTax":<?php echo self::prices_include_tax() ? 'true' : 'false'; ?>} -->
                    <p class="wp-block-storeabill-item-discount sab-block-item-content">{content}</p>
                    <!-- /wp:storeabill/item-discount -->

					<?php do_action( self::get_hook_prefix() . 'after_item_discount' ); ?>
                </div>
                <!-- /wp:storeabill/item-table-column -->
			<?php endif; ?>

			<?php if ( self::show_row_based_tax_rates() ) : ?>
                <!-- wp:storeabill/item-table-column {"align":"center","headingTextColor":"#000000","headingBackgroundColor":"<?php echo esc_attr( $heading_bg_color ); ?>"} -->
                <div class="wp-block-storeabill-item-table-column is-horizontally-aligned-center">
                    <span class="item-column-heading-text"><strong><?php echo esc_html_x( 'Tax Rate', 'storeabill-item-table-column', 'woocommerce-germanized-pro' ); ?></strong></span>
                    <!-- wp:storeabill/item-tax-rate {"showPricesIncludingTax":<?php echo self::prices_include_tax() ? 'true' : 'false'; ?>} -->
                    <p class="wp-block-storeabill-item-tax-rate sab-block-item-content">{content}</p>
                    <!-- /wp:storeabill/item-tax-rate -->

					<?php do_action( self::get_hook_prefix() . 'after_item_tax_rate' ); ?>
                </div>
                <!-- /wp:storeabill/item-table-column -->
			<?php endif; ?>

            <!-- wp:storeabill/item-table-column {"align":"right","headingTextColor":"#000000","headingBackgroundColor":"<?php echo esc_attr( $heading_bg_color ); ?>"} -->
            <div class="wp-block-storeabill-item-table-column is-horizontally-aligned-right">
                <span class="item-column-heading-text"><strong><?php echo esc_html_x( 'Total', 'storeabill-item-table-column', 'woocommerce-germanized-pro' ); ?></strong></span>
                <!-- wp:storeabill/item-line-total {"discountTotalType":"<?php echo ( self::show_row_based_discounts() ? 'after_discounts' : 'before_discounts' ); ?>","showPricesIncludingTax":<?php echo self::prices_include_tax() ? 'true' : 'false'; ?>} -->
                <p class="wp-block-storeabill-item-line-total sab-block-item-content">{content}</p>
                <!-- /wp:storeabill/item-line-total -->

				<?php do_action( self::get_hook_prefix() . 'after_item_line_total' ); ?>
            </div>
            <!-- /wp:storeabill/item-table-column -->
        </div>
        <!-- /wp:storeabill/item-table -->

		<?php do_action( self::get_hook_prefix() . 'after_item_table' ); ?>

        <!-- wp:storeabill/item-totals {"hasDenseLayout":<?php echo self::has_dense_layout() ? 'true' : 'false'; ?>} -->
        <div class="wp-block-storeabill-item-totals">
            <!-- wp:storeabill/item-total-row {"totalType":"line_subtotal<?php echo ( self::show_row_based_discounts() ? '_after' : '' ); ?><?php echo ( ! self::prices_include_tax() ? '_net' : '' ); ?>","heading":"<?php echo esc_attr_x( 'Subtotal', 'storeabill-item-total', 'woocommerce-germanized-pro' ); ?>"} /-->

	        <?php if ( ! self::show_row_based_discounts() ) : ?>
                <!-- wp:storeabill/item-total-row {"totalType":"discount<?php echo ( ! self::prices_include_tax() ? '_net' : '' ); ?>","hideIfEmpty":true,"heading":"<?php printf( esc_attr_x( 'Discount %s', 'storeabill-item-total', 'woocommerce-germanized-pro' ), '\u003cspan class=\u0022document-shortcode sab-tooltip\u0022 contenteditable=\u0022false\u0022 data-tooltip=\u0022' . esc_attr_x( 'Discount Notice', 'storeabill-core', 'woocommerce-germanized-pro' ) . '\u0022 data-shortcode=\u0022document_total?data=notice&total_type=discount\u0022\u003e\u003cspan class=\u0022editor-placeholder\u0022\u003e\u003c/span\u003e' . esc_attr_x( 'XYZ123 (Single-purpose)', 'storeabill-core', 'woocommerce-germanized-pro' ) . '\u003c/span\u003e' ); ?>"} /-->
	        <?php else: ?>
                <!-- wp:storeabill/item-total-row {"totalType":"additional_costs_discount<?php echo ( ! self::prices_include_tax() ? '_net' : '' ); ?>","hideIfEmpty":true,"heading":"<?php echo esc_attr_x( 'Discount', 'storeabill-item-total', 'woocommerce-germanized-pro' ); ?>"} /-->
	        <?php endif; ?>

            <!-- wp:storeabill/item-total-row {"totalType":"fee<?php echo ( ! self::prices_include_tax() ? '_net' : '' ); ?>","heading":"<?php echo esc_attr_x( 'Fees', 'storeabill-item-total', 'woocommerce-germanized-pro' ); ?>","hideIfEmpty":true} /-->

            <!-- wp:storeabill/item-total-row {"totalType":"shipping<?php echo ( ! self::prices_include_tax() ? '_net' : '' ); ?>","heading":"<?php echo esc_attr_x( 'Shipping', 'storeabill-item-total', 'woocommerce-germanized-pro' ); ?>","hideIfEmpty":true} /-->

			<?php if ( self::prices_include_tax() ) : ?>
                <!-- wp:storeabill/item-total-row {"content":"\u003cstrong\u003e{total}\u003c/strong\u003e","totalType":"total","borders":["top","bottom"],"customBorderColor":"<?php echo esc_attr( self::get_light_color() ); ?>","customFontSize":"16","heading":"\u003cstrong\u003e<?php echo esc_attr_x( 'Total', 'storeabill-item-total', 'woocommerce-germanized-pro' ); ?>\u003c/strong\u003e"} /-->

				<?php if ( self::show_net_totals_per_tax_rate() ) : ?>
                    <!-- wp:storeabill/item-total-row {"totalType":"nets","customTextColor":"<?php echo esc_attr( self::get_light_color() ); ?>","customFontSize":"11","heading":"<?php printf( esc_attr_x( '%s %% Net', 'storeabill-item-total', 'woocommerce-germanized-pro' ), '\u003cspan class=\u0022document-shortcode sab-tooltip\u0022 contenteditable=\u0022false\u0022 data-tooltip=\u0022' . esc_attr_x( 'Tax Rate', 'storeabill-core', 'woocommerce-germanized-pro' ) . '\u0022 data-shortcode=\u0022document_total?data=rate&total_type=nets\u0022\u003e\u003cspan class=\u0022editor-placeholder\u0022\u003e\u003c/span\u003e19\u003c/span\u003e' ); ?>"} /-->
				<?php else: ?>
                    <!-- wp:storeabill/item-total-row {"totalType":"net","customTextColor":"<?php echo esc_attr( self::get_light_color() ); ?>","customFontSize":"11","heading":"<?php echo esc_attr_x( 'Net', 'storeabill-item-total', 'woocommerce-germanized-pro' ); ?>"} /-->
				<?php endif; ?>

                <!-- wp:storeabill/item-total-row {"totalType":"taxes","customTextColor":"<?php echo esc_attr( self::get_light_color() ); ?>","customFontSize":"11","heading":"<?php printf( esc_attr_x( '%s %% Tax', 'storeabill-item-total', 'woocommerce-germanized-pro' ), '\u003cspan class=\u0022document-shortcode sab-tooltip\u0022 contenteditable=\u0022false\u0022 data-tooltip=\u0022' . esc_attr_x( 'Tax Rate', 'storeabill-core', 'woocommerce-germanized-pro' ) . '\u0022 data-shortcode=\u0022document_total?data=rate&total_type=taxes\u0022\u003e\u003cspan class=\u0022editor-placeholder\u0022\u003e\u003c/span\u003e19\u003c/span\u003e' ); ?>","hideIfEmpty":true} /-->
			<?php else: ?>
				<?php if ( self::show_net_totals_per_tax_rate() ) : ?>
                    <!-- wp:storeabill/item-total-row {"totalType":"nets","heading":"<?php printf( esc_attr_x( '%s %% Net', 'storeabill-item-total', 'woocommerce-germanized-pro' ), '\u003cspan class=\u0022document-shortcode sab-tooltip\u0022 contenteditable=\u0022false\u0022 data-tooltip=\u0022' . esc_attr_x( 'Tax Rate', 'storeabill-core', 'woocommerce-germanized-pro' ) . '\u0022 data-shortcode=\u0022document_total?data=rate&total_type=nets\u0022\u003e\u003cspan class=\u0022editor-placeholder\u0022\u003e\u003c/span\u003e19\u003c/span\u003e' ); ?>"} /-->
				<?php else: ?>
                    <!-- wp:storeabill/item-total-row {"totalType":"net","heading":"<?php echo esc_attr_x( 'Net', 'storeabill-item-total', 'woocommerce-germanized-pro' ); ?>"} /-->
				<?php endif; ?>

                <!-- wp:storeabill/item-total-row {"totalType":"taxes","heading":"<?php printf( esc_attr_x( '%s %% Tax', 'storeabill-item-total', 'woocommerce-germanized-pro' ), '\u003cspan class=\u0022document-shortcode sab-tooltip\u0022 contenteditable=\u0022false\u0022 data-tooltip=\u0022' . esc_attr_x( 'Tax Rate', 'storeabill-core', 'woocommerce-germanized-pro' ) . '\u0022 data-shortcode=\u0022document_total?data=rate&total_type=taxes\u0022\u003e\u003cspan class=\u0022editor-placeholder\u0022\u003e\u003c/span\u003e19\u003c/span\u003e' ); ?>","hideIfEmpty":true} /-->

                <!-- wp:storeabill/item-total-row {"content":"\u003cstrong\u003e{total}\u003c/strong\u003e","totalType":"total","borders":["top","bottom"],"customBorderColor":"<?php echo esc_attr( self::get_light_color() ); ?>","customFontSize":"16","heading":"\u003cstrong\u003e<?php echo esc_attr_x( 'Total', 'storeabill-item-total', 'woocommerce-germanized-pro' ); ?>\u003c/strong\u003e"} /-->
			<?php endif; ?>
        </div>
        <!-- /wp:storeabill/item-totals -->

		<?php do_action( self::get_hook_prefix() . 'after_totals' ); ?>

		<?php echo self::get_default_footer(); ?>
		<?php
		$html = ob_get_clean();

		return apply_filters( self::get_hook_prefix() . 'html', self::clean_html_whitespaces( $html ) );
	}
}