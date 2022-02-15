<?php

namespace Vendidero\StoreaBill\Editor\Templates;

use Vendidero\StoreaBill\Countries;use Vendidero\StoreaBill\Package;

defined( 'ABSPATH' ) || exit;

abstract class Template {

	public static function get_template_data() {
	    return array();
    }

	public static function get_screenshot_url() {
	    return '';
    }

	public static function get_tags() {
	    return array();
    }

	public static function get_title() {
	    return '';
    }

	public static function get_document_type() {
	    return 'invoice';
    }

	public static function get_name() {
	    return '';
    }

	/**
	 * This method should return the HTML result produced by the current template.
	 * It is not possible to server-side render the HTML result that is why the result must be supplied by the template.
	 * Using the HTML is necessary to make sure templates created on install or by default are usable right away.
	 *
	 * @return string
	 */
	public static function get_html() {
	    return '';
    }

	protected static function get_hook_prefix() {
		return 'storeabill_' . static::get_document_type() . '_' . static::get_name() . '_template_';
	}

	public static function clean_html_whitespaces( $html ) {
		$html = preg_replace( '/^\s+|\n|\r|\s+$/m', '', $html );
		$html = str_replace( array( '<br><br></p>', '<br/><br/></p>', '<br></p>', '<br/></p>' ), '</p>', $html );

		return $html;
	}

	protected static function get_light_color() {
		return '#a9a9a9';
	}

	public static function has_dense_layout() {
	    return apply_filters( self::get_hook_prefix() . 'has_dense_layout', false );
	}

	public static function get_default_header() {
	    if ( ! apply_filters( self::get_hook_prefix() . 'show_header', true ) ) {
	        return '';
        }
		ob_start();
		?>
		<!-- wp:storeabill/header -->
		<div class="wp-block-storeabill-header">
			<div class="wp-block-group__inner-container sab-header-container">
				<!-- wp:columns -->
				<div class="wp-block-columns">
					<!-- wp:column {"width":66.66} -->
					<div class="wp-block-column" style="flex-basis:66.66%">
                        <?php if ( apply_filters( self::get_hook_prefix() . 'show_logo', true ) ) : ?>
                            <!-- wp:storeabill/logo {"width":215,"height":null,"sizeSlug":"large"} -->
                            <figure class="wp-block-storeabill-logo size-large is-resized"><img src="<?php echo esc_url( apply_filters( self::get_hook_prefix() . 'logo', trailingslashit( Package::get_assets_url() ) . 'images/logo.svg' ) ); ?>" alt="" width="<?php echo apply_filters( self::get_hook_prefix() . 'logo_width', 215 ); ?>"/></figure>
                            <!-- /wp:storeabill/logo -->
                        <?php else: ?>
                            <!-- wp:paragraph {"style":{"typography":{"fontSize":30},"color":{"text":"<?php echo esc_attr( self::get_light_color() ); ?>"}}} -->
                            <p class="has-text-color" style="font-size:30px;color:<?php echo esc_attr( self::get_light_color() ); ?>"><strong><?php echo Countries::get_base_company_name(); ?></strong></p>
                            <!-- /wp:paragraph -->
                        <?php endif; ?>
					</div>
					<!-- /wp:column -->

					<!-- wp:column {"width":33.33} -->
					<div class="wp-block-column" style="flex-basis:33.33%">
						<!-- wp:paragraph {"align":"right","fontSize":"small","style":{"color":{"text":"<?php echo esc_attr( self::get_light_color() ); ?>"}}} -->
						<p class="has-text-align-right has-text-color has-small-font-size" style="color:<?php echo esc_attr( self::get_light_color() ); ?>">
							<?php echo apply_filters( self::get_hook_prefix() . 'company_formatted_address', Countries::get_formatted_base_address() ); ?><br>
							<?php do_action( self::get_hook_prefix() . 'after_company_address_header' ); ?><br>
						</p>
						<!-- /wp:paragraph -->
						<!-- wp:paragraph {"align":"right","fontSize":"small","style":{"color":{"text":"<?php echo esc_attr( self::get_light_color() ); ?>"}}} -->
						<p class="has-text-align-right has-text-color has-small-font-size" style="color:<?php echo esc_attr( self::get_light_color() ); ?>">
							<?php echo sprintf( esc_html_x( 'Email: %s', 'storeabill-core', 'woocommerce-germanized-pro' ), Countries::get_base_email() ); ?><br>
							<?php do_action( self::get_hook_prefix() . 'after_company_contact_header' ); ?><br>
						</p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:column -->
				</div>
				<!-- /wp:columns -->
			</div>
		</div>
		<!-- /wp:storeabill/header -->
		<?php do_action( self::get_hook_prefix() . 'after_header' ); ?>
		<?php
		return ob_get_clean();
	}

	public static function get_default_footer() {
		if ( ! apply_filters( self::get_hook_prefix() . 'show_footer', true ) ) {
			return '';
		}
		ob_start();
		?>
		<?php do_action( self::get_hook_prefix() . 'before_footer' ); ?>
		<!-- wp:storeabill/footer -->
		<div class="wp-block-storeabill-footer">
			<div class="wp-block-group__inner-container sab-footer-container">
				<!-- wp:storeabill/page-number {"align":"right"} -->
				<p class="wp-block-storeabill-page-number sab-page-number has-text-align-right"><?php printf( esc_html_x( 'Page %1$s of %2$s', 'storeabill-core', 'woocommerce-germanized-pro' ), '<span class="current-page-no-placeholder-content sab-tooltip" contenteditable="false" data-tooltip="' . esc_html_x( 'Page number', 'storeabill-core', 'woocommerce-germanized-pro' ) . '"><span class="editor-placeholder"></span>{current_page_no}</span>', '<span class="total-pages-placeholder-content sab-tooltip" contenteditable="false" data-tooltip="' . esc_html_x( 'Total pages', 'storeabill-core', 'woocommerce-germanized-pro' ) . '"><span class="editor-placeholder"></span>{total_pages}</span>' ); ?></p>
				<!-- /wp:storeabill/page-number -->

				<!-- wp:spacer {"height":20} -->
				<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
				<!-- /wp:spacer -->

				<!-- wp:separator {"customColor":"<?php echo esc_attr( self::get_light_color() ); ?>","className":"is-style-wide"} -->
				<hr class="wp-block-separator has-text-color has-background is-style-wide" style="background-color:<?php echo esc_attr( self::get_light_color() ); ?>;color:<?php echo esc_attr( self::get_light_color() ); ?>"/>
				<!-- /wp:separator -->

				<!-- wp:columns -->
				<div class="wp-block-columns">
					<!-- wp:column {"width":42.4} -->
					<div class="wp-block-column" style="flex-basis:42.4%">
						<!-- wp:paragraph {"fontSize":"small","style":{"color":{"text":"<?php echo esc_attr( self::get_light_color() ); ?>"}}} -->
						<p class="has-text-color has-small-font-size" style="color:<?php echo esc_attr( self::get_light_color() ); ?>"><strong><?php echo esc_html_x( 'Bank Account', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></strong></p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"fontSize":"small","style":{"color":{"text":"<?php echo esc_attr( self::get_light_color() ); ?>"}}} -->
						<p class="has-text-color has-small-font-size" style="color:<?php echo esc_attr( self::get_light_color() ); ?>">
							<?php printf( esc_html_x( 'Holder:', 'storeabill-bank-account', 'woocommerce-germanized-pro' ) ); ?> <span class="document-shortcode sab-tooltip" contenteditable="false" data-tooltip="<?php echo esc_attr_x( 'Bank account holder', 'storeabill-core', 'woocommerce-germanized-pro' ); ?>" data-shortcode="setting?data=bank_account_holder"><span class="editor-placeholder"></span><?php echo sab_get_base_bank_account_data( 'holder' ); ?></span><br>
							<?php printf( esc_html_x( 'IBAN:', 'storeabill-bank-account', 'woocommerce-germanized-pro' ) ); ?> <span class="document-shortcode sab-tooltip" contenteditable="false" data-tooltip="<?php echo esc_attr_x( 'IBAN', 'storeabill-core', 'woocommerce-germanized-pro' ); ?>" data-shortcode="setting?data=bank_account_iban"><span class="editor-placeholder"></span><?php echo sab_get_base_bank_account_data( 'iban' ); ?></span><br>
							<?php printf( esc_html_x( 'BIC:', 'storeabill-bank-account', 'woocommerce-germanized-pro' ) ); ?> <span class="document-shortcode sab-tooltip" contenteditable="false" data-tooltip="<?php echo esc_attr_x( 'BIC', 'storeabill-core', 'woocommerce-germanized-pro' ); ?>" data-shortcode="setting?data=bank_account_bic"><span class="editor-placeholder"></span><?php echo sab_get_base_bank_account_data( 'bic' ); ?></span><br>
						</p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:column -->

					<!-- wp:column {"width":27.3} -->
					<div class="wp-block-column" style="flex-basis:27.3%">
						<!-- wp:paragraph {"fontSize":"small","style":{"color":{"text":"<?php echo esc_attr( self::get_light_color() ); ?>"}}} -->
						<p class="has-text-color has-small-font-size" style="color:<?php echo esc_attr( self::get_light_color() ); ?>"><strong><?php echo esc_html_x( 'Contact Us', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></strong></p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"align":"left","fontSize":"small","style":{"color":{"text":"<?php echo esc_attr( self::get_light_color() ); ?>"}}} -->
						<p class="has-text-align-left has-text-color has-small-font-size" style="color:<?php echo esc_attr( self::get_light_color() ); ?>">
							<?php echo sprintf( esc_html_x( 'Email: %s', 'storeabill-core', 'woocommerce-germanized-pro' ), Countries::get_base_email() ); ?><br>

							<?php do_action( self::get_hook_prefix() . 'after_company_contact_footer' ); ?><br>
						</p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:column -->

					<!-- wp:column -->
					<div class="wp-block-column">
						<!-- wp:paragraph {"align":"right","fontSize":"small","style":{"color":{"text":"<?php echo esc_attr( self::get_light_color() ); ?>"}}} -->
						<p class="has-text-align-right has-text-color has-small-font-size" style="color:<?php echo esc_attr( self::get_light_color() ); ?>"><strong><?php echo Countries::get_base_company_name(); ?></strong></p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"align":"right","fontSize":"small","style":{"color":{"text":"<?php echo esc_attr( self::get_light_color() ); ?>"}}} -->
						<p class="has-text-align-right has-text-color has-small-font-size" style="color:<?php echo esc_attr( self::get_light_color() ); ?>">
							<?php echo Countries::get_formatted_base_address( '<br/>', false ); ?><br>

							<?php do_action( self::get_hook_prefix() . 'after_company_address_footer' ); ?><br>
						</p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:column -->
				</div>
				<!-- /wp:columns -->
			</div>
		</div>
		<!-- /wp:storeabill/footer -->
		<?php
		return ob_get_clean();
	}
}