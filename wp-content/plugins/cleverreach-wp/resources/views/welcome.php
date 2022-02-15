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
$config = $this->get_welcome_config();

?>
<div class="cr-container">
	<input type="hidden" id="cr-auth-url" value="<?php echo esc_url( $config[ 'authUrl' ] ); ?>">
	<input type="hidden" id="cr-check-status-url" value="<?php echo esc_url( $config[ 'checkStatusUrl' ] ); ?>">

	<div class="cr-loader-big">
		<span class="cr-loader"></span>
	</div>

	<div class="cr-connecting">
	<span>
		<?php echo esc_html( __( 'Connecting...', 'cleverreach-wp' ) ); ?>
	</span>
	</div>

	<div class="cr-content-window-wrapper wpcontent">
		<div class="cr-welcome-content-window cr-iframe">
			<iframe id="cr-iframe" scrolling="no" src="<?php echo esc_url( $config[ 'authUrl' ] ); ?>">
			</iframe>
		</div>
	</div>

	<a href="#" id="cr-open-form"></a>

	<script>
		window.onload = function () {
			let triggerType = '<?php echo esc_attr( $config[ 'triggerType' ] ); ?>',
				surveyUrl = '<?php echo esc_url( $config[ 'surveyUrl' ] ); ?>',
				ignoreSurveyUrl = '<?php echo esc_url( $config[ 'ignoreSurveyUrl' ] ); ?>',
				notificationFlagUrl = '<?php echo esc_url( $config[ 'notificationFlagUrl' ] ); ?>',
				surveyController = new CleverReach.Survey.SurveyController();

			surveyController.init(triggerType, surveyUrl, ignoreSurveyUrl, notificationFlagUrl);
		};
	</script>
