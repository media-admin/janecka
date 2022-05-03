<?php
/**
 * The Template for displaying allergenic for a certain product.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized-pro/single-product/food/allergenic.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @version 3.9.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

global $product;
?>
<?php if ( wc_gzd_get_gzd_product( $product )->get_formatted_allergenic() ) : ?>
	<?php if ( $print_title ) : ?>
        <h2 class="wc-gzd-allergenic-heading"><?php echo esc_html( apply_filters( 'woocommerce_gzd_product_allergenic_heading', __( 'Allergenic', 'woocommerce-germanized-pro' ) ) ); ?></h2>
	<?php endif; ?>

    <div class="wc-gzd-allergenic wc-gzd-product-food-information">
        <?php echo wpautop( wc_gzd_get_gzd_product( $product )->get_formatted_allergenic() ); ?>
    </div>
<?php elseif ( $product->is_type( 'variable' ) ) : ?>
    <h2 class="wc-gzd-allergenic-heading wc-gzd-additional-info-placeholder"></h2>
    <div class="wc-gzd-allergenic wc-gzd-product-food-information wc-gzd-additional-info-placeholder"></div>
<?php endif; ?>
