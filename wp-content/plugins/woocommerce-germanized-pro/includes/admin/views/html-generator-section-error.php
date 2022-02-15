<?php
/**
 * Admin View: Generator Section
 */

if ( ! defined( 'ABSPATH' ) )
	exit;
?>
<div class="wc-gzd-admin-settings wc-gzd-admin-settings-generator wc-gzd-admin-settings-generator-<?php echo $generator_id; ?>">
	<div class="notice error inline">
		<p><?php printf( _x( 'There seems to be a problem receiving the newest generator. Is your <a href="%s">update flatrate</a> still active?', 'generator', 'woocommerce-germanized-pro' ), admin_url( 'index.php?page=vendidero' ) ); ?></p>
	</div>
</div>