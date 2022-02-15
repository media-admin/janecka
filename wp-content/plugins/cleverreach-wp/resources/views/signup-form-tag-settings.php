<div class="control-box" id="cr-signup-form-tag-settings">
	<fieldset>
		<legend>
			<?php echo sprintf( esc_html( __( 'Generate a form-tag for CleverReach® Sign-Up checkbox.', 'cleverreach-wp' ) ) ); ?>
		</legend>
		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'contact-form-7' ) ); ?></label></th>
				<td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" value="cleverreach-sign-up" readonly/></td>
			</tr>
			<tr>
				<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-values' ); ?>"><?php echo esc_html( __( 'Label', 'contact-form-7' ) ); ?></label></th>
				<td><input type="text" name="values" class="oneline" id="<?php echo esc_attr( $args['content'] . '-values' ); ?>" value="<?php echo esc_html( __( 'Subscribe to newsletter', 'cleverreach-wp' ) ) ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-preselect' ); ?>"><?php echo esc_html( __( 'Pre-select checkbox', 'cleverreach-wp' ) ); ?></label></th>
				<td>
					<input type="radio" name="preselect-checkbox" class="option" id="<?php echo esc_attr( $args['content'] . '-preselect-yes' ); ?>" value="yes"/>
					<label for="<?php echo esc_attr( $args['content'] . '-preselect-yes' ); ?>"><?php echo esc_html( __( 'Yes' ) ); ?></label>
					<input type="radio" name="preselect-checkbox" class="option default" id="<?php echo esc_attr( $args['content'] . '-preselect-no' ); ?>" value="no" checked />
					<label for="<?php echo esc_attr( $args['content'] . '-preselect-no' ); ?>"><?php echo esc_html( __( 'No' ) ); ?></label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-required' ); ?>"><?php echo esc_html( __( 'Make this checkbox required', 'cleverreach-wp' ) ); ?></label></th>
				<td><input type="checkbox" name="required-checkbox" id="<?php echo esc_attr( $args['content'] . '-required' ); ?>" class="option" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-id' ); ?>"><?php echo esc_html( __( 'Id attribute', 'contact-form-7' ) ); ?></label></th>
				<td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-id' ); ?>"/></td>
			</tr>
			<tr>
				<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php echo esc_html( __( 'Class attribute', 'contact-form-7' ) ); ?></label></th>
				<td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-class' ); ?>"/></td>
			</tr>
			</tbody>
		</table>
	</fieldset>
</div>

<div class="insert-box">
	<input type="text" name="cleverreach" id="<?php echo esc_attr( $args['content'] . '-tag' ); ?>" class="tag code" readonly="readonly" onfocus="this.select()" />

	<div class="submitbox">
		<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'contact-form-7' ) ); ?>" />
	</div>

	<br class="clear" />
</div>
