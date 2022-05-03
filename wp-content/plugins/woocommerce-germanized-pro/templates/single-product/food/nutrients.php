<?php
/**
 * The Template for displaying nutrients for a certain product.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized-pro/single-product/food/nutrients.php.
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
$has_vitamin = false;
?>
<?php if ( wc_gzd_get_gzd_product( $product )->get_nutrients() ) : ?>
	<?php if ( $print_title ) : ?>
        <h2 class="wc-gzd-nutrients-heading"><?php echo esc_html( apply_filters( 'woocommerce_gzd_product_nutrients_heading', __( 'Nutrients', 'woocommerce-germanized-pro' ) ) ); ?></h2>
	<?php endif; ?>

    <div class="wc-gzd-nutrients wc-gzd-product-food-information">
        <p class="wc-gzd-nutrient-reference-value"><?php echo \Vendidero\Germanized\Pro\Food\Helper::get_nutrient_reference_value_title( wc_gzd_get_gzd_product( $product )->get_nutrient_reference_value() ); ?></p>

        <table class="wc-gzd-food-table shop_food_table wc-gzd-food-table-nutrients">
            <tbody>
			<?php foreach( wc_gzd_get_gzd_product( $product )->get_nutrients() as $nutrient ) : ?>
                <tr class="wc-gzd-nutrient-table-item wc-gzd-nutrient-table-item-<?php echo esc_attr( $nutrient->get_id() ); ?> wc-gzd-nutrient-table-item-<?php echo esc_attr( $nutrient->get_slug() ); ?> wc-gzd-nutrient-table-item-<?php echo esc_attr( $nutrient->get_slug() ); ?> <?php echo ( $nutrient->is_parent() ? 'wc-gzd-nutrient-table-item-is-parent' : 'wc-gzd-nutrient-table-item-is-child wc-gzd-nutrient-table-item-child-of-' . $nutrient->get_parent_id() ); ?>">
                    <th class="wc-gzd-nutrient-table-item-label" <?php echo ( 'title' === $nutrient->get_type() ? 'colspan="2"' : '' ); ?>>
						<?php echo ( $nutrient->has_name_prefix() ) ? '<span class="wc-gzd-nutrient-prefix">' . esc_html( $nutrient->get_name_prefix() ) . '</span>' : ''; ?>
                        <span class="wc-gzd-nutrient-name"><?php echo esc_html( $nutrient->get_name() ); ?></span>
                    </th>
                    <?php if ( 'title' !== $nutrient->get_type() ) : ?>
                        <td class="wc-gzd-nutrient-table-item-value">
                            <span class="wc-gzd-nutrient-value"><?php echo wc_format_localized_decimal( wc_gzd_get_gzd_product( $product )->get_nutrient_value( $nutrient->get_id() ) ); ?></span>
                            <span class="wc-gzd-nutrient-unit"><?php echo esc_html( $nutrient->get_unit() ); ?></span>

                            <?php if ( $nutrient->is_vitamin() ) :
                                $has_vitamin = true;
                                ?>
                                <span class="wc-gzd-nutrient-vitamin-reference-value">(<?php echo wc_format_localized_decimal( wc_gzd_get_gzd_product( $product )->get_nutrient_reference( $nutrient->get_id() ) ); ?> <span class="wc-gzd-nutrient-vitamin-reference-unit"><?php echo \Vendidero\Germanized\Pro\Food\Helper::get_nutrient_vitamin_reference_unit(); ?></span> <span class="wc-gzd-nutrient-vitamin-reference-mark"><?php echo esc_html( apply_filters( 'woocommerce_gzdp_nutrient_vitamin_reference_mark', '**' ) ); ?></span>)</span
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
			<?php endforeach; ?>
            </tbody>
        </table>

        <?php if ( $has_vitamin ) : ?>
            <p class="wc-gzd-nutrient-vitamin-reference-notice"><?php echo esc_html( apply_filters( 'woocommerce_gzdp_nutrient_vitamin_reference_mark', '**' ) ); ?> <?php _e( 'Percent of the reference amount for daily intake', 'woocommerce-germanized-pro' ); ?></p>
        <?php endif; ?>
    </div>
<?php elseif ( $product->is_type( 'variable' ) ) : ?>
    <h2 class="wc-gzd-nutrients-heading wc-gzd-additional-info-placeholder"></h2>
    <div class="wc-gzd-nutrients wc-gzd-product-food-information wc-gzd-additional-info-placeholder"></div>
<?php endif; ?>
