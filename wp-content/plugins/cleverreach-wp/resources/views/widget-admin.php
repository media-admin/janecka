<?php

use CleverReach\WordPress\IntegrationCore\BusinessLogic\Forms\Models\Form;

$view_parameters = $this->get_admin_view_parameters();
/** @var Form[] $forms */
$forms = $view_parameters['forms'];

?>

<script type="text/javascript">
	/**
	 * Change form url when other form is selected
	 *
	 * @param selectObject html node
	 */
	function cleverreachChangeFormUrl(selectObject) {
		let editFormUrl = document.getElementById('cr-edit-form-url').value;
		let parentElement = selectObject.parentElement;
		let childNodes = parentElement.childNodes;
		for (let i = 0; i < childNodes.length; i++) {
			if (childNodes[i].nodeName.toLocaleLowerCase() === 'a') {
				let link = childNodes[i];
				link.setAttribute('href', editFormUrl + '&form_id=' + selectObject.value);
			}
		}
	}
</script>

<style>
	.cr-forms-dropdown-widget select{
		width: 100%;
		max-width: 100%;
	}
</style>


<input type="hidden" id="cr-edit-form-url" value="<?php echo esc_url( $view_parameters[ 'edit_form_url' ] ); ?>">
<p>
<div>
	<label for="<?php echo esc_attr( $this->get_field_id( 'form' ) ); ?>">
		<?php echo esc_html( __( 'Choose an integration form:', 'cleverreach-wp' ) ); ?>
	</label><br>
	<div class="cr-forms-dropdown-widget">
		<select id="<?php echo esc_attr( $this->get_field_id( 'form' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'form' ) ); ?>"
				onchange="cleverreachChangeFormUrl(this)">
			<?php
			foreach ( $forms as $form ) { ?>
				<option <?php echo esc_attr( selected( $view_parameters[ 'form_id' ], $form->getFormId() ) ); ?>
						title="<?php echo esc_attr( $form->getName() ); ?>"
						value="<?php echo esc_attr( $form->getFormId() ); ?>">
					<?php echo substr($form->getName(), 0, 50); ?>
				</option>
			<?php } ?>
		</select>
		<br>
		<a id="<?php echo esc_attr( $this->get_field_id( 'link' ) ); ?>"
		   href="<?php echo esc_url( $view_parameters[ 'edit_form_url' ] . '&form_id=' . $view_parameters[ 'form_id' ] ); ?>"
		   target="_blank">
			<?php echo esc_html( __( 'Edit in CleverReach®', 'cleverreach-wp' ) ); ?>
		</a>
	</div>
</div>
</p>