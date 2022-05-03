<?php
/**
 * The Template for displaying nutri score for a certain product.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized-pro/loop/food/nutri-score.php.
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
<?php if ( wc_gzd_get_gzd_product( $product )->get_nutri_score() ) : ?>
	<div class="wc-gzd-nutri-score wc-gzd-additional-info-loop wc-gzd-product-food-information">
		<?php echo wc_gzd_get_gzd_product( $product )->get_formatted_nutri_score(); ?>
	</div>
<?php elseif ( $product->is_type( 'variable' ) ) : ?>
	<p class="wc-gzd-nutri-score wc-gzd-additional-info-loop wc-gzd-product-food-information wc-gzd-additional-info-placeholder"></p>
<?php endif; ?>
