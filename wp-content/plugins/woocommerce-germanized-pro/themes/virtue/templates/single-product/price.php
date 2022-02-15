<?php
/**
 * Single Product Price, including microdata for SEO
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version 	2.7.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $post, $product;
?>
<div class="pricebox">
	<p class="product_price price headerfont"><?php echo $product->get_price_html(); ?></p>

    <?php do_action( 'woocommerce_gzdp_virtue_single_product_price_box' ); ?>
</div>