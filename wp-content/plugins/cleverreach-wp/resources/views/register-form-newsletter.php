<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

use CleverReach\WordPress\Components\Utility\Database;

$cr_checked = false;
$cr_user_id = false;
if ( isset( $GLOBALS['profileuser'] ) && isset( $GLOBALS['profileuser']->data->ID ) ) {
	// This will be set if backend profile page is opened.
	$cr_user_id = $GLOBALS['profileuser']->data->ID;
} elseif ( isset( $GLOBALS['userdata'] ) && isset( $GLOBALS['userdata']->data->ID ) ) {
	// This will be set if frontend profile page is opened.
	$cr_user_id = $GLOBALS['userdata']->data->ID;
}

if ( null !== $cr_user_id ) {
	$cr_newsletter_column = Database::get_newsletter_column();
	$cr_checked           = get_user_meta( $cr_user_id, $cr_newsletter_column, true ) === '1';
}
?>
<table class="form-table">
	<tbody>
		<tr>
			<th scope="row">
				<?php echo esc_html( __( 'Subscribe to newsletter', 'cleverreach-wp' ) ); ?>
			</th>
			<td>
				<label for="cr_newsletter_status">
					<input type="checkbox" class="" name="cr_newsletter_status" id="cr_newsletter_status" value="1"
						<?php echo esc_attr( $cr_checked ? ' checked="checked"' : '' ); ?> />
				</label>
			</td>
		</tr>
	</tbody>
</table>
<div class="clear"></div>
