<?php
/**
 * The template for displaying product content in the single-product.php template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-single-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

/* --- Remove unused Data --- */

// Remove the Price
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 27, 0 );


// Remove the Add to Cart Button
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30, 0);


// Remove the Product Description
remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10, 0);

/**
 * Hook: woocommerce_before_single_product.
 *
 * @hooked woocommerce_output_all_notices - 10
 */
do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
	echo get_the_password_form(); // WPCS: XSS ok.
	return;
}
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( '', $product ); ?>>

	<?php
	/**
	 * Hook: woocommerce_before_single_product_summary.
	 *
	 * @hooked woocommerce_show_product_sale_flash - 10
	 * @hooked woocommerce_show_product_images - 20
	 */
	do_action( 'woocommerce_before_single_product_summary' );
	?>


	<?php

	/* Adding the Material Attributes */
	function janecka_single_product_eheringe_display_material_and_width() {

		global $product;

		$product_attributes = $product->get_attributes(); // Get the product attributes

		$material_id = $product_attributes['pa_material']['options']['0']; // returns the ID of the term
		$material_value = get_term( $material_id )->name; // gets the term name of the term from the ID

		$width_id = $product_attributes['pa_ringbreite']['options']['0']; // returns the ID of the term
		$width_value = get_term( $width_id )->name; // gets the term name of the term from the ID

		// Output

		if ( !empty( $material_id | $width_id ) ) {
			echo '<span class="product__ehering-details">';
			echo $material_value . ', Breite ' . $width_value;
			echo '</span>';
		}
	}

	add_action( 'woocommerce_single_product_summary', 'janecka_single_product_eheringe_display_material_and_width', 9 );


	/* Adding the Material Attributes */
	function janecka_single_product_eheringe_display_ring_pair_details() {

		global $product;

		?>

		<div class="columns">

			<div class="column single-eheringe-damen__wrapper">
				<h3 class="single-eheringe-damen__header-title">Damenring</h3>
				<span class="single-eheringe-damen__sku-label">Artikelnummer</span>
				<p class="single-eheringe-damen__sku-data"><?php the_field('damenring_artikelnummer'); ?></p>

				<p class="single-eheringe-damen__diamond">

					<?php
						$women_diamond_type = get_field('damenring_diamant');
						$woman_diamond_number = get_field('damenring_anzahl_steine');

						if ( !empty( $women_diamond_type ) ) {
							echo $women_diamond_type;
							echo ' / Anzahl: ';
							echo $woman_diamond_number;
							echo '<p/>';
						} else {
							echo 'Kein Diamant vorhanden';
							echo '<p/>';
						}

					?>

				<p class="single-eheringe-damen__price">€ <?php the_field('damenring_basispreis'); ?></p>

			</div>

			<div class="column single-eheringe-herren__wrapper">
				<h3 class="single-eheringe-herren__header-title">Herrenring</h3>
				<span class="single-eheringe-herren__sku-label">Artikelnummer</span>
				<p class="single-eheringe-herren__sku-data"><?php the_field('herrenring_artikelnummer'); ?></p>
				<p class="single-eheringe-herren__diamond"><?php the_field('herrenring_diamant'); ?>&nbsp;</p>
				<p class="single-eheringe-herren__price">€ <?php the_field('herrenring_basispreis'); ?></p>
			</div>

		</div>

		<p class="product__ehering-price-details">Die Preise verstehen sich inklusive 20% MwSt.</p>

		<?php echo do_shortcode('[content_terminwunschbutton]'); ?>

	<?php
	}

	add_action( 'woocommerce_single_product_summary', 'janecka_single_product_eheringe_display_ring_pair_details', 11 );

	?>


	<div class="summary entry-summary">
		<?php
		/**
		 * Hook: woocommerce_single_product_summary.
		 *
		 * @hooked woocommerce_template_single_title - 5
		 * @hooked woocommerce_template_single_rating - 10
		 * @hooked woocommerce_template_single_price - 10
		 * @hooked woocommerce_template_single_excerpt - 20
		 * @hooked woocommerce_template_single_add_to_cart - 30
		 * @hooked woocommerce_template_single_meta - 40
		 * @hooked woocommerce_template_single_sharing - 50
		 * @hooked WC_Structured_Data::generate_product_data() - 60
		 */
		do_action( 'woocommerce_single_product_summary' );
		?>
	</div>

	<?php
	/**
	 * Hook: woocommerce_after_single_product_summary.
	 *
	 * @hooked woocommerce_output_product_data_tabs - 10
	 * @hooked woocommerce_upsell_display - 15
	 * @hooked woocommerce_output_related_products - 20
	 */
	do_action( 'woocommerce_after_single_product_summary' );
	?>
</div>

<?php do_action( 'woocommerce_after_single_product' ); ?>
