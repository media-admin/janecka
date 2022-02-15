<?php

use CleverReach\WordPress\Controllers\Clever_Reach_CF7_Controller;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\RecipientAttribute;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Forms\Models\Form;

?>

<div class="contact-form-editor-box-cleverreach" id="<?php echo esc_attr( $args['id'] ); ?>">
	<table class="form-table">
		<tbody>
		<tr>
			<th scope="row" class="cr-settings-label">
				<label for="<?php echo esc_attr( $args['id'] ); ?>-enabled-synchronization">
					<?php echo esc_html( __( 'Enabled synchronization', 'cleverreach-wp' ) ); ?>
				</label>
			</th>
			<td>

				<input
						type="radio"
						name="<?php echo esc_attr( $args['id'] ); ?>-enabled-synchronization"
						class="option"
						value="1"
					<?php if ( $args[ 'recipient_sync_enabled' ] ) {
						?>
						checked
					<?php } ?>
				/>
				<label for="<?php echo esc_attr( $args['id'] . '-enabled-synchronization-yes' ); ?>">
					<?php echo esc_html( __( 'Yes' ) ); ?>
				</label>
				<input
						type="radio"
						name="<?php echo esc_attr( $args['id'] ); ?>-enabled-synchronization"
						class="option default" value="0"
					<?php if ( ! $args[ 'recipient_sync_enabled' ] ) {
						?> checked
					<?php } ?>
				/>
				<label for="<?php echo esc_attr( $args['id'] . '-enabled-synchronization-no' ); ?>">
					<?php echo esc_html( __( 'No' ) ); ?>
				</label>
			</td>
		</tr>
		<?php if ( $args[ 'recipient_sync_enabled' ] ) { ?>
			<tr>
				<th scope="row">
					<label for="<?php echo esc_attr( $args['id'] ); ?>-double-opt-in-form">
						<?php echo esc_html( __( 'Double opt-in form', 'cleverreach-wp' ) ); ?>
					</label>
				</th>
				<td>
					<select
							<?php if ( $args['is_user_info_incomplete'] ) { ?> disabled <?php } ?>
							id="<?php echo esc_attr( $args['id'] ); ?>-double-opt-in-form"
							class="cr-form-settings-input"
							name="<?php echo esc_attr( $args['id'] ); ?>-double-opt-in-form"
					>
						<option value="none"><?php echo esc_html( __( 'None', 'cleverreach-wp' ) ); ?></option>
						<?php
						/** @var Form $form */
						foreach ( $args[ 'forms' ] as $form ) {
							$selected = '';
							if ( array_key_exists( 'double_opt_in_form', $args )
							     && $args [ 'double_opt_in_form' ] === $form->getFormId()
							) {
								$selected = 'selected';
							}
							?>
							<option value="<?php echo esc_attr( $form->getFormId() ) ?>" <?php echo esc_attr( $selected )?>>
								<?php echo esc_html( $form->getName() ) ?>
							</option>
							<?php
						}
						?>
					</select>
					<span class="cr-field-desc">
						<?php if ( $args['is_user_info_incomplete'] ) {
							echo esc_html( __( 'Double opt-in is disabled due to missing user data on your CleverReach account.', 'cleverreach-wp' ) ); ?>
							<a href="<?php echo esc_url( $args['cr_data_page'] ) ?>" target="_blank">
								<?php echo esc_html( __( 'Update data', 'cleverreach-wp' ) ); ?>
							</a>
						<?php } else {
							echo esc_html( __( 'If “None” is selected, recipients will not receive a confirmation email before subscribing to the newsletter. Please note the legal situation in your country, whether you want to use Single-Opt-In or Double-Opt-In. We suggest the use of Double-Opt-In to be GDPR compliant.', 'cleverreach-wp' ) );
						} ?>
					</span>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="<?php echo esc_attr( $args['id'] ); ?>-recipient-tags">
						<?php echo esc_html( __( 'Recipient tags', 'cleverreach-wp' ) ); ?>
					</label>
				</th>
				<td>
					<input
							type="text"
							id="<?php echo esc_attr( $args['id'] ); ?>-recipient-tags"
							class="large-text code cr-form-settings-input"
							name="<?php echo esc_attr( $args['id'] ); ?>-recipient-tags"
							size="70"
						<?php if ( array_key_exists( 'recipient_tags', $args ) ) {
							?>
							value="<?php echo esc_attr( $args[ 'recipient_tags' ] ) ?>"
							<?php
						} ?>
							placeholder="<?php echo esc_html( __( 'Examples: visitor,applicant...', 'cleverreach-wp' ) ); ?>"
					/>
					<span class="cr-field-desc">
						<?php echo esc_html( __( 'The listed tags will be applied to all recipients added by this form. Please enter comma-separated tags.', 'cleverreach-wp' ) ); ?>
					</span>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="<?php echo esc_attr( $args['id'] ); ?>-attribute-mappings">
						<?php echo esc_html( __( 'Attribute mappings', 'cleverreach-wp' ) ); ?>
					</label>
				</th>
				<td class="cr-attribute-mappings">
					<table>
						<thead>
							<th><?php echo esc_html( __( 'CF7 form field', 'cleverreach-wp' ) ); ?></th>
							<th><?php echo esc_html( __( 'CleverReach® Attribute', 'cleverreach-wp' ) ); ?></th>
						</thead>
						<tbody>
						<?php
						/** @var \WPCF7_FormTag $cf_tag */
						foreach ( $args[ 'cf7_tags' ] as $cf_tag ) {
							?>
							<tr>
								<td class="cr-attribute">
									[<?php echo esc_html( $cf_tag->name ); ?>]
								</td>
								<td>
									<select id="<?php echo esc_attr( $args[ 'id' ] ); ?>-<?php echo esc_html( $cf_tag->name ); ?>-attribute"
											name="<?php echo esc_attr( $args[ 'id' ] ); ?>-<?php echo esc_html( $cf_tag->name ); ?>-attribute">
										<option value="empty">
											<?php echo esc_html( __( 'Empty', 'cleverreach-wp' ) ); ?>
										</option>
										<?php
										/** @var RecipientAttribute $attribute */
										foreach ( $args[ 'cr_attributes' ] as $attribute ) {
											if ( ! in_array( $attribute->getName(), Clever_Reach_CF7_Controller::$cleverreach_system_attributes, true ) ) {
												$selected = '';
												if ( array_key_exists( 'cleverreach-wp-' . $cf_tag->name . '-attribute', $args[ 'attributes' ] )
												     && $args[ 'attributes' ][ 'cleverreach-wp-' . $cf_tag->name . '-attribute' ] === $attribute->getName()
												) {
													$selected = 'selected';
												}
												?>
												<option value="<?php echo esc_attr( $attribute->getName() ) ?>" <?php echo esc_attr( $selected ) ?>>
													<?php echo esc_html( $attribute->getDescription() ) ?>
												</option>
												<?php
											}
										}
										?>
									</select>
								</td>
							</tr>
							<?php
						}
						?>
						</tbody>
					</table>
				</td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
</div>
