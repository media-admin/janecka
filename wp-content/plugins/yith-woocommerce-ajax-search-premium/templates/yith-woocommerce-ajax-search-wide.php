<?php
/**
 * YITH WooCommerce Ajax Search template
 *
 * @author  YITH
 * @package YITH WooCommerce Ajax Search Premium
 * @version 1.2.3
 */

if ( ! defined( 'YITH_WCAS' ) ) {
	exit;
} // Exit if accessed directly

wp_enqueue_script( 'yith_wcas_frontend' );
$research_post_type = ( get_option( 'yith_wcas_default_research' ) ) ? get_option( 'yith_wcas_default_research' ) : 'product';
?>
<div class="yith-ajaxsearchform-container yith-ajaxsearchform-wide <?php echo esc_attr( $class ); ?> ">
	<form role="search" method="get" id="yith-ajaxsearchform" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<div class="yith-ajaxsearch-filters">
			<div class="yith-ajaxsearchform-select yith-ajaxsearchform-select-list">
				<?php
				if ( get_option( 'yith_wcas_show_search_list' ) === 'yes' ) :
					$selected_search = ( isset( $_REQUEST['post_type'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['post_type'] ) ) : $research_post_type; //phpcs:ignore ?>

					<select class="yit_wcas_post_type selectbox" id="yit_wcas_post_type" name="post_type">
						<option value="product" <?php selected( 'product', $selected_search ); ?>><?php esc_html_e( 'Products', 'yith-woocommerce-ajax-search' ); ?></option>
						<option value="any" <?php selected( 'any', $selected_search ); ?>><?php esc_html_e( 'All', 'yith-woocommerce-ajax-search' ); ?></option>
					</select>

				<?php else : ?>
					<input type="hidden" name="post_type" class="yit_wcas_post_type" id="yit_wcas_post_type" value="<?php echo esc_attr( $research_post_type ); ?>" />
				<?php endif; ?>
			</div>
			<div class="yith-ajaxsearchform-select yith-ajaxsearchform-select-category">
				<?php
				if ( get_option( 'yith_wcas_show_category_list' ) === 'yes' ) :

					$product_categories = yith_wcas_get_shop_categories( get_option( 'yith_wcas_show_category_list_all' ) === 'all' );

					$selected_category = ( isset( $_REQUEST['product_cat'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['product_cat'] ) ) : ''; //phpcs:ignore

					if ( ! empty( $product_categories ) ) :
						?>
						<select class="search_categories selectbox" id="search_categories" name="product_cat">
							<option value="" <?php selected( '', $selected_category ); ?>><?php esc_html_e( 'All', 'yith-woocommerce-ajax-search' ); ?></option>
							<?php
							foreach ( $product_categories as $category ) :
								if ( empty( $category ) ) :
									continue;
								endif;
								?>
								<option value="<?php echo esc_attr( $category->slug ); ?>" <?php selected( $category->slug, $selected_category ); ?>><?php echo esc_html( $category->name ); ?></option>
							<?php endforeach; ?>
						</select>
					<?php endif ?>
				<?php endif ?>
			</div>
		</div>
		<div class="search-input-container">
			<input type="search" value="<?php echo esc_attr( get_search_query() ); ?>" name="s" id="yith-s" class="yith-s" placeholder="<?php echo esc_attr( get_option( 'yith_wcas_search_input_label' ) ); ?>" data-append-to=".search-input-container" data-loader-icon="<?php echo esc_attr( str_replace( '"', '', apply_filters( 'yith_wcas_ajax_search_icon', '' ) ) ); ?>" data-min-chars="<?php echo esc_attr( get_option( 'yith_wcas_min_chars' ) ); ?>" />
		</div>

		<div class="search-submit-container">
			<?php if ( apply_filters( 'yith_wcas_submit_as_input', true ) ) : ?>
				<input type="submit" id="yith-searchsubmit" value="<?php echo esc_attr( apply_filters( 'yith_wcas_submit_label', get_option( 'yith_wcas_search_submit_label' ) ) ); ?>" />
			<?php else : ?>
				<button type="submit" id="yith-searchsubmit"><?php echo wp_kses_post( apply_filters( 'yith_wcas_submit_label', get_option( 'yith_wcas_search_submit_label' ) ) ); ?></button>
			<?php endif; ?>
		</div>

		<?php if ( defined( 'ICL_LANGUAGE_CODE' ) ) : ?>
			<input type="hidden" name="lang" value="<?php echo esc_attr( ICL_LANGUAGE_CODE ); ?>" />
		<?php endif ?>

	</form>
</div>

