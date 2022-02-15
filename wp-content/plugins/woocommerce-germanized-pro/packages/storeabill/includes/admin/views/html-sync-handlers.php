<?php
/**
 * Admin View: Settings Tabs
 */
defined( 'ABSPATH' ) || exit;
?>

<p class="tab-description"><?php _ex( 'Transfer invoices and/or customer data to specific external services', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></p>

<table class="sab-sync-handlers widefat striped">
	<thead>
	<tr>
		<th class="sab-sync-handler-title"><?php echo esc_html_x( 'Name', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></th>
		<th class="sab-sync-handler-enabled"><?php echo esc_html_x( 'Enabled', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></th>
		<th class="sab-sync-handler-description"><?php echo esc_html_x( 'Description', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></th>
		<th class="sab-sync-handler-actions"></th>
	</tr>
	</thead>
	<tbody class="wc-gzd-setting-tab-rows">
	<?php foreach ( \Vendidero\StoreaBill\ExternalSync\Helper::get_sync_handlers() as $handler_name => $handler ) : ?>
		<tr>
			<td class="sab-sync-handler-title" id="sab-sync-handler-title-<?php echo esc_attr( $handler_name ); ?>">
                <a href="<?php echo $handler::get_admin_url() ?>">
                    <img class="icon" src="<?php echo esc_url( $handler::get_icon() ); ?>" alt="<?php echo esc_attr( $handler::get_title() ); ?>" /><?php echo $handler::get_title(); ?>
                </a>
            </td>
			<td class="sab-sync-handler-enabled" id="sab-sync-handler-enabled-<?php echo esc_attr( $handler_name ); ?>">
				<span class="<?php echo( $handler->is_enabled() ? 'status-enabled' : 'status-disabled' ); ?>"><?php echo( $handler->is_enabled() ? esc_attr_x( 'Yes', 'storeabill-core', 'woocommerce-germanized-pro' ) : esc_attr_x( 'No', 'storeabill-core', 'woocommerce-germanized-pro' ) ); ?></span>
			</td>
			<td class="sab-sync-handler-description"><?php echo $handler::get_description(); ?></td>
			<td class="sab-sync-handler-actions">
				<?php if ( $help_link = $handler::get_help_link() ) : ?>
					<a class="button button-secondary wc-gzd-dash-button help-link"
					   title="<?php echo esc_attr_x( 'Find out more', 'storeabill-core', 'woocommerce-germanized-pro' ); ?>"
					   aria-label="<?php echo esc_attr_x( 'Find out more', 'storeabill-core', 'woocommerce-germanized-pro' ); ?>"
					   href="<?php echo $help_link; ?>"><?php echo _x( 'How to', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></a>
				<?php endif; ?>

				<a class="button button-secondary wc-gzd-dash-button"
				   aria-label="<?php echo esc_attr_x( 'Manage settings', 'storeabill-core', 'woocommerce-germanized-pro' ); ?>"
				   title="<?php echo esc_attr_x( 'Manage settings', 'storeabill-core', 'woocommerce-germanized-pro' ); ?>"
				   href="<?php echo $handler::get_admin_url(); ?>"><?php _ex( 'Manage', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></a>
			</td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>
