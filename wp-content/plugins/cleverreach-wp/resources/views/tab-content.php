<?php
?>

<div class="cr-tab-wrapper">
	<div class="cr-dashboard-tab-wrapper">
		<button class="cr-dashboard-tab" tabindex="0" role="button" id="cr-tab-dashboard-button">
			<?php echo esc_html( __( 'Dashboard', 'cleverreach-wp' ) ); ?>
		</button>
	</div>
	<div class="cr-pull-right">
		<a href="<?php echo esc_url( __($config['helpUrl'], 'cleverreach-wp' ) ); ?>" target="_blank">
			<?php echo esc_html( __( 'Help & Support', 'cleverreach-wp' ) ); ?></a>
		<p class="cr-id"><?php echo esc_html( __( 'CleverReach® ID', 'cleverreach-wp' ) ); ?>: <?php echo esc_html( $config['recipientId'] ); ?></p>
	</div>
</div>
