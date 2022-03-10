<?php
/**
 * The Template for displaying product archives, including the main shop page which is a post type archive
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.4.0
 */

defined( 'ABSPATH' ) || exit;

?>

<?php
	get_header();

	/* --- Remove unused Data --- */

	// Remove the SKU
	remove_action('woocommerce_after_shop_loop_item', 'janecka_shop_display_skus', 8, 0);

	// Remove the Add to Cart Button
	remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10, 0);

	/* --- Adding additional Data --- */

	/* Adding the Diamond
	function janecka_shop_display_diamonds() {

		global $product;

		$product_attributes = $product->get_attributes(); // Get the product attributes

		if ( !empty( $product_attributes['pa_diamant']['options']['0'] ) ) {
			$diamonds_id = $product_attributes['pa_diamant']['options']['0']; // returns the ID of the term
			$diamonds_value = get_term( $diamonds_id )->name; // gets the term name of the term from the ID
		}

		// Output
		if ( !empty( $diamonds_id ) ) {
			echo '<span class="products__ring-diamonds">';
			echo $diamonds_value;
			echo '<br/>';
			echo '</span>';
		} else {
			echo '<span class="products__ring-diamonds">';
			echo 'Kein Diamant vorhanden';
			echo '<br/>';
			echo '</span>';
		}

	}

	add_action( 'woocommerce_shop_loop_item_title', 'janecka_shop_display_diamonds', 10 );
	*/

	/* Adding the Material Attributes
	function janecka_shop_display_material_and_width() {

		global $product;

		$product_attributes = $product->get_attributes(); // Get the product attributes

		$material_id = $product_attributes['pa_material']['options']['0']; // returns the ID of the term
		$material_value = get_term( $material_id )->name; // gets the term name of the term from the ID

		$width_id = $product_attributes['pa_ringbreite']['options']['0']; // returns the ID of the term
		$width_value = get_term( $width_id )->name; // gets the term name of the term from the ID

		// Output

		if ( !empty( $material_id | $width_id ) ) {
			echo '<span class="products__ring-material">';
			echo $material_value;
			echo '</span><br/>';
			echo '<span class="products__ring-width">';
			echo 'Breite ' . $width_value;
			echo '</span>';
		}

	}

	add_action( 'woocommerce_after_shop_loop_item_title', 'janecka_shop_display_material_and_width', 12 );
	*/

	// Query Variables
	$brand_tag = get_terms(array('taxonomy' => 'product_tag', 'hide_empty' => false));
	$brand_name = $brand_tag[0]->name;
	$brand_slug = $brand_tag[0]->slug;

	// Output Variables
	$brand_banner = get_field('brand-banner', $brand_tag[0]);
	$brand_description = get_field('brand-description', $brand_tag[0]);

	?>

	<?php echo do_shortcode ('[products limit="60" columns="4" category="eheringe" paginate="true"]') ?>

		<section class="service-notice">
			<?php echo do_shortcode('[content_schmuckservice]'); ?>
		</section>

	</main>

<?php get_footer(); ?>