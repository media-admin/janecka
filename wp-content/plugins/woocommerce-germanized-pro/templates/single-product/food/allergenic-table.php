<?php
/**
 * The Template for displaying allergenic for a certain product.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized-pro/single-product/food/allergenic-table.php.
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
<?php if ( wc_gzd_get_gzd_product( $product )->is_food() && wc_gzd_get_gzd_product( $product )->get_allergenic() ) : ?>
    <div class="wc-gzd-allergenic-table wc-gzdp-product-food-information wc-gzdp-product-food-allergenic">
        <table class="wc-gzdp-food-table shop_food_table wc-gzdp-food-table-nutrients">
            <tbody>
			<?php foreach( wc_gzd_get_gzd_product( $product )->get_allergenic() as $allergen ) : ?>
                <tr class="wc-gzdp-allergenic-table-item wc-gzdp-allergenic-table-item-<?php echo esc_attr( $allergen->get_slug() ); ?>">
                    <th class="wc-gzdp-allergenic-table-item-label">
                        <span class="wc-gzdp-allergen-name"><?php echo esc_html( $allergen->get_name() ); ?></span>
                    </th>
                    <td class="wc-gzdp-allergenic-table-item-value">
                        <span class="wc-gzdp-allergen-value"><?php echo $allergen->get_included_value(); ?></span>
                    </td>
                </tr>
			<?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
