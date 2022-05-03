<?php
/**
 * The Template for displaying ingredients & nutrition tab for a certain product.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized-pro/single-product/tabs/ingredients.php.
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
<?php do_action( 'woocommerce_gzdp_product_ingredients', true ); ?>

<?php do_action( 'woocommerce_gzdp_product_allergenic', true ); ?>

<?php do_action( 'woocommerce_gzdp_product_nutrients', true ); ?>