<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<table class="wc-gzdp-legal-checkboxes">

	<thead>
		<tr>
			<th><?php _e( 'Checkbox', 'woocommerce-germanized-pro' ); ?></th>
			<th><?php _e( 'Value', 'woocommerce-germanized-pro' ); ?></th>
		</tr>
	</thead>

	<tbody>
		<?php foreach( $checkboxes as $checkbox ) :
			$cb_value = WC_GZDP_Legal_Checkbox_Helper::instance()->checkbox_is_checked( $checkbox, $checkbox_object );
			?>
			<tr>
				<td>
					<?php echo $checkbox->get_admin_name(); ?>
				</td>
				<td>
                    <?php echo $cb_value ? '<span class="dashicons dashicons-yes"></span>' : '<span class="dashicons dashicons-no-alt"></span>'; ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>

</table>