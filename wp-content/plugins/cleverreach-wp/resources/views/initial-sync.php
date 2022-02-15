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
$config = $this->get_initial_sync_config();

$cr_progress_items = array(
	'subscriber_list' => __( 'Create recipient list and form in CleverReach®', 'cleverreach-wp' ),
);

if ( $config[ 'recipientSyncEnabled' ] ) {
	$cr_progress_items[ 'add_fields' ] = __( 'Add data fields, segments and tags to recipient list', 'cleverreach-wp' );
	$cr_progress_items[ 'recipient_sync' ] = str_replace(
		'%s',
		$config[ 'integrationName' ],
		/* translators: %s is replaced with site name. */
		__( 'Import recipients from %s to CleverReach®', 'cleverreach-wp' )
	);
}
?>
<input type="hidden" id="cr-admin-status-check-url" value="<?php echo esc_url( $config['statusCheckUrl'] ); ?>">

<div data-task-list-panel>
	<ul class="cr-list-group">
		<?php foreach ( $cr_progress_items as $cr_key => $cr_title ) : ?>
		<li class="cr-list-group-item disabled" data-task="<?php echo esc_attr( $cr_key ); ?>">
			<div class="cr-content-window-wrapper">
				<div class="cr-content-window">
					<i class="cr-icofont cr-icofont-circle">
						<i class="cr-icofont cr-icofont-2x" aria-hidden="true" data-status="<?php echo esc_attr( $cr_key ); ?>"></i>
					</i>
					<div class="cr-item">
						<div class="cr-item-text"><?php echo esc_html( $cr_title ); ?></div>
						<div class="cr-item-badge">
							<span class="cr-progress-text" data-progress="<?php echo esc_attr( $cr_key ); ?>">0%</span>
						</div>
					</div>
				</div>
			</div>
		</li>
		<?php endforeach; ?>
	</ul>
</div>


