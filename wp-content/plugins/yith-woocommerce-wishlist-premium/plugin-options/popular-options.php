<?php
/**
 * Lists options page
 *
 * @author YITH
 * @package YITH\Wishlist\Options
 * @version 3.0.0
 */

if ( ! defined( 'YITH_WCWL' ) ) {
	exit;
} // Exit if accessed directly

// phpcs:disable WordPress.Security.NonceVerification.Recommended
$current_action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : false;
$product_id     = isset( $_GET['product_id'] ) ? intval( $_GET['product_id'] ) : false;
// phpcs:enable WordPress.Security.NonceVerification.Recommended

$options = array(
	'popular_section_start' => array(
		'type' => 'title',
		'desc' => '',
		'id'   => 'yith_wcwl_popular_settings',
	),

	'popular_section_end'   => array(
		'type' => 'sectionend',
		'id'   => 'yith_wcwl_popular_settings',
	),
);

if ( ! $current_action || 'show_users' !== $current_action ) {
	$options = yith_wcwl_merge_in_array(
		$options,
		array(
			'wishlists' => array(
				'name'                 => __( 'Popular products', 'yith-woocommerce-wishlist' ),
				'type'                 => 'yith-field',
				'yith-type'            => 'list-table',

				'class'                => '',
				'list_table_class'     => 'YITH_WCWL_Popular_Table',
				'list_table_class_dir' => YITH_WCWL_INC . 'tables/class-yith-wcwl-popular-table.php',
				'id'                   => 'popular-filter',
			),
		),
		'popular_section_start'
	);
} else {
	$product = wc_get_product( $product_id );

	$options = yith_wcwl_merge_in_array(
		$options,
		array(
			'wishlists' => array(
				// translators: 1. Product name.
				'name'                 => $product ? sprintf( __( 'Users that added "%s" to wishlist', 'yith-woocommerce-wishlist' ), $product->get_name() ) : __( 'Users that added product to wishlist', 'yith-woocommerce-wishlist' ),
				'desc'                 => sprintf( '<small><a href="%s">%s</a></small>', remove_query_arg( array( 'action', 'product_id' ) ), __( '< Back to popular', 'yith-woocommerce-wishlist' ) ),
				'type'                 => 'yith-field',
				'yith-type'            => 'list-table',

				'class'                => '',
				'list_table_class'     => 'YITH_WCWL_Users_Popular_Table',
				'list_table_class_dir' => YITH_WCWL_INC . 'tables/class-yith-wcwl-users-popular-table.php',
				'id'                   => 'popular-filter',
			),
		),
		'popular_section_start'
	);
}

return apply_filters(
	'yith_wcwl_popular_options',
	array(
		'popular' => $options,
	)
);
