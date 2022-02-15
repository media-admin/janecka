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

<div id="message" class="error woocommerce-gzd-message wc-connect">
	<h3><?php _e( 'Germanized basic missing, outdated or not supported', 'woocommerce-germanized-pro' ); ?></h3>
	<p>
		<?php if ( ! $dependencies->is_woocommerce_gzd_activated() ) :
			$install_url  = wp_nonce_url( add_query_arg( array( 'action' => 'install-plugin', 'plugin' => 'woocommerce-germanized' ), admin_url( 'update.php' ) ), 'install-plugin_woocommerce_germanized' );
			$plugins      = array_keys( get_plugins() );
			// translators: 1$-2$: opening and closing <strong> tags, 3$-4$: link tags, takes to woocommerce plugin on wp.org, 5$-6$: opening and closing link tags, leads to plugins.php in admin
			$text         = sprintf( esc_html__( '%1$sGermanized Pro is inactive.%2$s The %3$sGermanized basic plugin%4$s must be active for Germanized Pro to work. Please %5$sinstall Germanized basic &raquo;%6$s',  'woocommerce-germanized-pro' ), '<strong>', '</strong>', '<a href="http://wordpress.org/extend/plugins/woocommerce-germanized/">', '</a>', '<a href="' .  esc_url( $install_url ) . '">', '</a>' );

			if ( in_array( 'woocommerce-germanized/woocommerce-germanized.php', $plugins ) ) {
				$install_url = wp_nonce_url( add_query_arg( array( 'action' => 'activate', 'plugin' => urlencode( 'woocommerce-germanized/woocommerce-germanized.php' ) ), admin_url( 'plugins.php' ) ), 'activate-plugin_woocommerce-germanized/woocommerce-germanized.php' );
				// translators: 1$-2$: opening and closing <strong> tags, 3$-4$: link tags, takes to woocommerce plugin on wp.org, 5$-6$: opening and closing link tags, leads to plugins.php in admin
				$text        = sprintf( esc_html__( '%1$sGermanized Pro is inactive.%2$s The %3$sGermanized basic plugin%4$s must be active for Germanized Pro to work. Please %5$sactivate Germanized basic &raquo;%6$s',  'woocommerce-germanized-pro' ), '<strong>', '</strong>', '<a href="http://wordpress.org/extend/plugins/woocommerce-germanized/">', '</a>', '<a href="' .  esc_url( $install_url ) . '">', '</a>' );
			}

			echo $text;
		elseif ( $dependencies->is_woocommerce_gzd_outdated() ) :
			// translators: 1$-2$: opening and closing <strong> tags, 3$: minimum supported WooCommerce version, 4$-5$: opening and closing link tags, leads to plugin admin
			printf( esc_html__( '%1$sGermanized Pro is inactive.%2$s This version of Germanized Pro requires Germanized basic %3$s or newer. Please %4$supdate Germanized to version %3$s or newer &raquo;%5$s', 'woocommerce-germanized-pro' ), '<strong>', '</strong>', $dependencies->get_wc_gzd_min_version_required(), '<a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">', '</a>' );
		elseif ( $dependencies->is_woocommerce_gzd_unsupported() ) :
			// translators: 1$-2$: opening and closing <strong> tags, 3$: minimum supported WooCommerce version, 4$-5$: opening and closing link tags, leads to plugin admin
			printf( esc_html__( '%1$sGermanized Pro is inactive.%2$s This version of Germanized Pro seems outdated and requires an older version of Germanized basic (< %3$s) to work. Please %4$supdate Germanized Pro to the latest version%5$s or %6$sdowngrade your Germanized basic version%7$s.', 'woocommerce-germanized-pro' ), '<strong>', '</strong>', $dependencies->get_wc_gzd_max_version_supported(), '<a href="https://vendidero.de/dokument/germanized-pro-aktualisieren" target="_blank">', '</a>', '<a href="https://vendidero.de/dokument/downgrade-germanized" target="_blank">', '</a>' );
		endif; ?>
	</p>
</div>