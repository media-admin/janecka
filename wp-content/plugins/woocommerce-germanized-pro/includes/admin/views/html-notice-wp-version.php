<?php
/**
 * Admin View: Dep notice
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @var WC_GZDP_Dependencies $dependencies
 */
?>

<div id="message" class="error woocommerce-gzd-message">
	<h3><?php _e( 'WordPress is outdated', 'woocommerce-germanized-pro' ); ?></h3>
	<p>
		<?php
			// translators: 1$-2$: opening and closing <strong> tags, 3$: minimum supported WooCommerce version, 4$-5$: opening and closing link tags, leads to plugin admin
			printf( esc_html__( '%1$sGermanized Pro is inactive.%2$s This version of Germanized Pro requires WordPress %3$s or newer. Please %4$supdate WordPress to version %3$s or newer &raquo;%5$s', 'woocommerce-germanized-pro' ), '<strong>', '</strong>', $dependencies->get_wp_min_version_required(), '<a href="' . esc_url( admin_url( 'update-core.php' ) ) . '">', '</a>' );
		?>
	</p>
</div>