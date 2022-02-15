<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

use CleverReach\WordPress\Components\Utility\Helper;

$logo_url = Helper::get_clever_reach_base_url( '/resources/images/icon_quickstartmailing.svg' );
?>
<div class="cr-content-window-wrapper wpcontent">
	<div class="cr-content-window">
		<img class="cr-icon" src="<?php echo esc_url( $logo_url ); ?>">
		<h3>
			<?php echo esc_html( __( 'An error occurred!', 'cleverreach-wp' ) ); ?>
		</h3>
		<div class="cr-dashboard-text-wrapper cr-main-text">
			<?php
			echo esc_html(
				__(
					'cURL is not installed or enabled in your PHP installation. This is required for background task to work. Please install it and then refresh this page.',
					'cleverreach-wp'
				)
			);
			?>
		</div>
	</div>
</div>
