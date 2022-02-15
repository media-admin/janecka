<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

/**
 * Frontend controller.
 *
 * @var CleverReach\WordPress\Controllers\Clever_Reach_Frontend_Controller $this
 */
$config = $this->get_initial_sync_settings_config();

?>
<input type="hidden" id="cr-configuration" value="<?php echo esc_url( $config['configurationUrl'] ); ?>">

<div class="cr-content-window-wrapper">
	<div class="cr-content-window">
		<img class="cr-welcome-icon" src="<?php echo esc_url( $config['helloUrl'] ); ?>" />
		<div class="cr-configuration">
			<h3>
				<?php echo esc_html( __( 'Configuration', 'cleverreach-wp' ) ); ?>
			</h3>
			<div>
				<label for="cr-none">
					<input type="radio" id="cr-none" name="cr-newsletterStatus" value="none" checked/>
					<b><?php echo esc_html( __( 'Current users should NOT be synced as Subscribers', 'cleverreach-wp' ) ); ?></b>
				</label><br>
				<label for="cr-all">
					<input type="radio" id="cr-all" name="cr-newsletterStatus" value="all" />
					<b><?php echo esc_html( __( 'Current users should be synced as Subscribers', 'cleverreach-wp' ) ); ?></b>
				</label><br>
				<?php if ( $config['hasSubscriberRole'] ) : ?>
					<label for="cr-onlyRoleSubscriber">
						<input type="radio" id="cr-onlyRoleSubscriber" name="cr-newsletterStatus" value="role_subscriber_only" />
						<b><?php echo esc_html( __( 'Current users should be synced as Subscribers only if they have Subscriber role', 'cleverreach-wp' ) ); ?></b>
					</label>
				<?php endif; ?>
				<br><br>
				<i><?php echo esc_html( __( 'CleverReach module adds newsletter subscription status so you can later set this per user.', 'cleverreach-wp' ) ); ?></i>
			</div>
		</div>

		<a data-success-panel-start-initial-sync class="cr-action-buttons-wrapper cr-primary" id="cr-startSync" tabindex="0" role="button">
			<?php echo esc_html( __( 'Start synchronization now', 'cleverreach-wp' ) ); ?> →
		</a>
	</div>
</div>
