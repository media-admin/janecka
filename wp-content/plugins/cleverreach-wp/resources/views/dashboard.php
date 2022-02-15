<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

use CleverReach\WordPress\Components\Utility\Helper;

/**
 * Frontend controller.
 *
 * @var CleverReach\WordPress\Controllers\Clever_Reach_Frontend_Controller $this
 */
$config = $this->get_dashboard_config();

if ( ! $config[ 'isInitialSyncTaskFailed' ] ) {
	$dashboard_title   = __( 'Targeted email marketing for more revenue!', 'cleverreach-wp' );
	$dashboard_message = str_replace(
		'%s',
		$config[ 'integrationName' ],
		/* translators: %s is replaced with name of the shop*/
		__( 'Create appealing emails to showcase your articles to your readers.', 'cleverreach-wp' )
	);
	$cr_segments       = $this->build_segments( $config[ 'tags' ] );
	if ( ! $config[ 'isFirstEmailBuilt' ] ) {
		$dashboard_button_text = __( 'Create your first newsletter →', 'cleverreach-wp' );
	} else {
		$dashboard_button_text = __( 'Create your next newsletter now! →', 'cleverreach-wp' );
	}

	$import_class = ! $config[ 'importStatisticsDisplayed' ] ? 'cr-has-import' : '';
	$cr_logo      = Helper::get_clever_reach_base_url( '/resources/images/cr_logo_transparent_107px.png' );
} else {
	$dashboard_title       = __( 'An error occurred!', 'cleverreach-wp' );
	$dashboard_message     = str_replace(
		'%s',
		$config[ 'initialSyncTaskFailureMessage' ],
		/* translators: %s is replaced with error description */
		__( 'Error description: %s. For more details please contact Help & Support.', 'cleverreach-wp' )
	);
	$dashboard_button_text = __( 'Retry synchronization now', 'cleverreach-wp' );
	$cr_segments           = '';
}
?>

<input type="hidden" id="cr-build-email-url" value="<?php echo esc_url( $config['buildEmailUrl'] ); ?>">
<input type="hidden" id="cr-build-first-email-url" value="<?php echo esc_url( $config['buildFirstEmailUrl'] ); ?>">
<input type="hidden" id="cr-retry-sync-url" value="<?php echo esc_url( $config['retrySyncUrl'] ); ?>">

<?php include __DIR__ . '/tab-content.php'; ?>

<?php if ( $config['isInitialSyncTaskFailed'] ) : ?>
	<div class="cr-content-window-wrapper">
		<div class="cr-content-window">
			<img class="cr-icon" src="<?php echo esc_url( $config['logoUrl'] ); ?>"/>
			<h3>
				<?php echo esc_html( $dashboard_title ); ?>
			</h3>
			<div class="cr-dashboard-text-wrapper cr-main-text">
				<?php echo esc_html( $dashboard_message ); ?>
			</div>
			<button class="cr-action-buttons-wrapper cr-primary" id="cr-retrySync">
				<?php echo esc_html( $dashboard_button_text ); ?>
			</button>
		</div>
	</div>

<?php else : ?>

	<div class="cr-dashboard-block">
		<div class="cr-dashboard-container <?php echo esc_html( $import_class ); ?>">
			<?php if ( ! $config[ 'importStatisticsDisplayed' ] && $config[ 'recipientSyncEnabled' ] ) : ?>
				<!-- INITIAL SYNC IMPORT -->
				<div class="cr-import">
					<svg viewBox="0 0 400 150" preserveAspectRatio="xMinYMin meet" style="stroke: none; fill: url('#cr-gradient')">
						<path d="M0,130 C150,180 200,60 400,120 L400,00 L0,0 Z" style="stroke: none;"></path>
						<defs>
							<linearGradient id="cr-gradient" x1="100%" y1="0%" x2="0%" y2="100%">
								<stop offset="0%" stop-color="#0AE355"></stop>
								<stop offset="72%" stop-color="#00C526"></stop>
							</linearGradient>
						</defs>
					</svg>
					<div class="cr-import-successful">
						<?php echo esc_html( __( 'Import was successful!', 'cleverreach-wp' ) ); ?>
					</div>
					<div class="cr-success-circle">
						<i class="fa fa-check"></i>
					</div>
					<div class="cr-report-content">
						<div class="cr-recipients">
							<div class="cr-dashboard-report-icon-large">
								<i class="fa fa-users"></i>
							</div>
							<div class="cr-dashboard-concrete-report">
								<div class="title">
									<?php echo esc_html( __( 'Recipients', 'cleverreach-wp' ) ); ?>
								</div>
								<div class="value">
									<?php
									echo esc_html( number_format_i18n( $config['numberOfSyncedRecipients'] ) );
									?>
								</div>
							</div>
						</div>
						<div class="cr-recipient-list">
							<div class="cr-dashboard-report-icon-large">
								<i class="fa fa-clipboard-list"></i>
							</div>
							<div class="cr-dashboard-concrete-report">
								<div class="title">
									<?php echo esc_html( __( 'Recipient list', 'cleverreach-wp' ) ); ?>
								</div>
								<div class="value cr-recipient-list-value" title="<?php echo esc_html( $config['integrationName'] ); ?>">
									<?php echo esc_html( $config['integrationName'] ); ?>
								</div>
							</div>
						</div>
						<div class="cr-segments">
							<div class="cr-dashboard-report-icon-small">
								<i class="fa fa-tag"></i>
							</div>
							<div class="cr-dashboard-concrete-report">
								<div class="title">
									<?php echo esc_html( __( 'Segments', 'cleverreach-wp' ) ); ?>
								</div>
								<?php echo wp_kses($cr_segments, array(
										'div' => array( 'class' => array(), 'title' => array() ),
										'span' => array( 'class' => array() ) ) ); ?>
							</div>
						</div>
					</div>
					<div class="cr-gdpr">
						<a href="<?php echo esc_url( __( $config['gdprUrl'], 'cleverreach-wp' ) ); ?>" target="_blank" class="cr-link">
							<i>
								<?php echo esc_html( __( 'Observe notes on GDPR!', 'cleverreach-wp' ) ); ?>
							</i>
						</a>
					</div>
				</div>
			<?php endif; ?>
			<!-- /INITIAL SYNC REPORT -->
			<!-- DASHBOARD CARD -->
			<div class="cr-create">
				<img class="cr-dashboard-logo" src="<?php echo esc_url( $cr_logo ); ?>" alt="Logo">
				<h3>
					<?php echo esc_html( $dashboard_title ); ?>
				</h3>
				<div class="cr-dashboard-text-wrapper cr-main-text">
					<?php echo esc_html( $dashboard_message ); ?>
				</div>
				<div class="cr-button-container">
					<button class="cr-action-buttons-wrapper cr-primary" id="cr-buildEmail">
						<?php echo esc_html( $dashboard_button_text ); ?>
					</button>
				</div>
			</div>
			<!-- /DASHBOARD CARD -->

			<div class="cr-cards-container">
				<!-- FORMS CARD -->
				<div class="cr-forms">
					<?php include __DIR__ . '/forms.php'; ?>
				</div>
				<!-- /FORMS CARD -->

				<!-- INTEGRATIONS CARD -->
				<div class="cr-integrations">
					<?php include __DIR__ . '/integrations.php'; ?>
				</div>
				<!-- /INTEGRATIONS CARD -->
			</div>
		</div>
	</div>
<?php endif; ?>

<a href="#" id="cr-open-form"></a>

<script>
	window.onload = function() {
		let triggerType = '<?php echo esc_attr( $config[ 'triggerType' ] ); ?>',
			surveyUrl = '<?php echo esc_url( $config[ 'surveyUrl' ] ); ?>',
			ignoreSurveyUrl = '<?php echo esc_url( $config[ 'ignoreSurveyUrl' ] ); ?>',
			notificationFlagUrl = '<?php echo esc_url( $config[ 'notificationFlagUrl' ] ); ?>',
			surveyController = new CleverReach.Survey.SurveyController();

		surveyController.init(triggerType, surveyUrl, ignoreSurveyUrl, notificationFlagUrl);
	};
</script>
