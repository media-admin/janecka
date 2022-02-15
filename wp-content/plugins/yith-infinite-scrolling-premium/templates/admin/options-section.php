<?php
/**
 * Section option template
 *
 * @author  YITH
 * @package YITH Infinite Scrolling
 * @version 1.0.0
 */

defined( 'YITH_INFS' ) || exit; // Exit if accessed directly.

?>
<div>
	<input id="yith-infs-add-section" type="text" class="section-title" value=""/>
	<a href="" id="yith-infs-add-section-button" class="button-primary" data-section_id="<?php echo esc_attr( $id ); ?>"
		data-section_name="<?php echo esc_attr( $name ); ?>">
		<?php esc_html_e( 'Add section', 'yit' ); ?>
	</a>
	<span class="error-input-section"></span>
</div>

<div id="<?php echo esc_attr( $id ); ?>-container" class="infs-sections-group">

	<?php if ( is_array( $db_value ) ) : ?>

		<?php foreach ( $db_value as $key => $value ) : ?>

			<div class="infs-section <?php echo esc_attr( $key ); ?>">

				<div class="section-head">
					<?php echo esc_html( __( 'Options for ', 'yith-infinite-scrolling' ) . $key ); ?>
					<span class="remove" data-section="<?php echo esc_attr( $key ); ?>"></span>
				</div>

				<div class="section-body">
					<table>
						<?php
						foreach ( $fields as $field_key => $field_opts ) :
							$field_id   = $id . '[' . $key . '][' . $field_key . ']';
							$field_name = $name . '[' . $key . '][' . $field_key . ']';
							$type       = isset( $field_opts['type'] ) ? $field_opts['type'] : 'text'; // phpcs:ignore
							$value      = isset( $db_value[ $key ][ $field_key ] ) ? $db_value[ $key ][ $field_key ] : ( isset( $field_opts['value'] ) ? $field_opts['value'] : '' );
							$class      = isset( $field_opts['class'] ) ? $field_opts['class'] : '';
							?>
						<tr>
							<th>
								<label for="<?php echo esc_attr( $field_id ); ?>">
									<?php echo esc_html( $field_opts['label'] ); ?>
								</label>
							</th>
							<td>
								<?php
								switch ( $type ) :
									case 'select':
										?>
										<select name="<?php echo esc_attr( $field_name ); ?>" id="<?php echo esc_attr( $field_id ); ?>"
											class="<?php echo esc_attr( $class ); ?>">
											<?php foreach ( $field_opts['options'] as $option_key => $option_label ) : ?>
												<option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( $option_key, $value ); ?>>
													<?php echo esc_html( $option_label ); ?>
												</option>
											<?php endforeach; ?>
										</select>
										<?php
										break;
									case 'loader':
										?>
										<img src=""/><br>
										<select name="<?php echo esc_attr( $field_name ); ?>" id="<?php echo esc_attr( $field_id ); ?>"
											class="<?php echo esc_attr( $class ); ?>">
											<?php foreach ( $field_opts['options'] as $preset => $url ) : ?>
												<option value="<?php echo esc_attr( $preset ); ?>" data-loader_url="<?php echo esc_url( $url ); ?>" >
													<?php echo esc_html( $preset ); ?>
												</option>
											<?php endforeach; ?>
										</select>
										<?php
										break;
									case 'upload':
										?>
										<input type="text" name="<?php echo esc_attr( $field_name ); ?>"
											id="<?php echo esc_attr( $field_id ); ?>"
											value="<?php echo esc_attr( $value ); ?>" class="upload_img_url">
										<input type="button" value="<?php esc_html_e( 'Upload', 'yith-infinite-scrolling' ); ?>"
											id="<?php echo esc_attr( $field_id ); ?>-button"
											class="upload_img_button button-primary"/>
										<?php
										break;
									default:
										?>
										<input type="<?php echo esc_attr( $type ); ?>" name="<?php echo esc_attr( $field_name ); ?>"
											id="<?php echo esc_attr( $field_id ); ?>" class="<?php echo esc_attr( $class ); ?>"
											value="<?php echo esc_attr( $value ); ?>">
										<?php
										break;

								endswitch;
								?>
								<p class="desc">
									<?php echo wp_kses_post( $field_opts['desc'] ); ?>
								</p>
							</td>

							<?php endforeach; ?>
						</tr>
					</table>
				</div>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
