<?php

namespace Vendidero\StoreaBill\Admin;

use Vendidero\StoreaBill\Package;

defined( 'ABSPATH' ) || exit;

class Fields {

	public static function sanitize_toggle_input_field( $value ) {
		return wc_bool_to_string( $value );
	}

	public static function render_oauth_connect_field( $args ) {
		$args = wp_parse_args( $args, array(
			'is_manual'                => false,
			'url'                      => '',
			'handler_label'            => '',
			'description'              => '',
			'authorization_input_name' => ''
		) );

		extract( $args );

		include( Package::get_path() . '/includes/admin/views/html-oauth-connect.php' );
	}

	public static function render_oauth_connected_field( $args ) {
		$args = wp_parse_args( $args, array(
			'description'              => '',
			'disconnect_input_name'    => '',
			'authorization_input_name' => '',
			'refresh_url'              => '',
			'is_manual'                => false
		) );

		extract( $args );

		include( Package::get_path() . '/includes/admin/views/html-oauth-connected.php' );
	}

	public static function render_document_templates_field( $args ) {
		$args = wp_parse_args( $args, array(
			'document_type'       => 'invoice',
			'templates'           => array(),
			'default_template_id' => '',
		) );

		$default_template = sab_get_default_document_template( $args['document_type'] );

		$args['templates']           = sab_get_document_templates( $args['document_type'] );
		$args['default_template_id'] = $default_template ? $default_template->get_id() : 0;
		$args['editor_templates']    = \Vendidero\StoreaBill\Editor\Helper::get_editor_templates( $args['document_type'] );

		extract( $args );

		include( Package::get_path() . '/includes/admin/views/html-document-templates.php' );
	}

	public static function render_document_journal_field( $args ) {
		$args = wp_parse_args( $args, array(
			'document_type'     => 'invoice',
			'field'             => '',
			'description'       => '',
            'custom_attributes' => array(),
		) );

		$option_name = 'journal_' . $args['document_type'] . '_' . $args['field'];

		if ( ! $journal = sab_get_journal( $args['document_type'] ) ) {
			return;
		}

		// Custom attribute handling.
		$custom_attributes = array();

		if ( ! empty( $args['custom_attributes'] ) && is_array( $args['custom_attributes'] ) ) {
			foreach ( $args['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}

		echo '<input type="text" name="sab_settings_hider" style="display: none" ' . implode( ' ', $custom_attributes ) . ' />';

		if ( 'last_number' === $args['field'] ) {
			?>
            <div class="sab-input-unblock-wrapper">
                <input type="number" class="sab-number-preview-trigger sab-number-preview-last-number sab-input-to-unblock sab-journal-last-number" name="<?php echo esc_attr( $option_name ); ?>" value="<?php echo $journal->get_last_number(); ?>" disabled placeholder="0" step="1" style="max-width: 100px" />
                <span class="sab-input-unblock">
                    <input type="checkbox" class="sab-input-unblock-checkbox" name="<?php echo esc_attr( $option_name ); ?>_unblock" id="<?php echo esc_attr( $option_name ); ?>_unblock" value="yes" />
				    <label for="<?php echo esc_attr( $option_name ); ?>_unblock"><?php _ex( 'Manually adjust last number', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></label>
                </span>
                <div class="sab-additional-desc">
		            <?php printf( _x( 'This option keeps track of the last sequential number of your %s. Adjusting this number might lead to inconsistencies (e.g. duplicate numbers) so make sure to double-check before adjusting it. You can enable edit mode by ticking the checkbox next to the field.', 'storeabill-core', 'woocommerce-germanized-pro' ), sab_get_document_type_label( $args['document_type'], 'plural' ) ); ?>
                </div>
            </div>
			<?php
		} elseif( 'number_format' === $args['field'] ) {
			$placeholder_html    = '<ul class="sab-document-number-placeholders">';
			$placeholders        = sab_get_document_number_placeholders( $args['document_type'] );
			$preview_number_html = '';

			if ( $preview = sab_get_document_preview( $args['document_type'] ) ) {
			    $preview->set_number( $journal->get_last_number() + 1 );
				$preview->set_formatted_number( $preview->format_number( $preview->get_number() ) );

			    $preview_number_html = '<code>' . $preview->get_formatted_number() . '</code>';
            }

			foreach( $placeholders as $placeholder => $title ) {
				$placeholder_html .= sprintf( '<li><code>%s</code>: %s</li>', esc_attr( $placeholder ), esc_html( $title ) );
			}

			$placeholder_html .= '</ul>';
		    ?>
            <input type="text" class="sab-number-preview-trigger sab-number-preview-number-format" name="<?php echo esc_attr( $option_name ); ?>" value="<?php echo $journal->get_number_format(); ?>" />
            <span class="sab-number-preview" data-document-type="<?php echo esc_attr( $args['document_type'] ); ?>"><?php _ex( 'Preview:', 'storeabill-core', 'woocommerce-germanized-pro' ); ?> <span class="sab-number"><?php echo $preview_number_html; ?></span><span class="spinner"></span></span>
            <div class="sab-additional-desc">
				<?php printf( _x( 'The number format determines the formatted sequential number. You can use static prefixes, postfixes and/or placeholders which are replaced dynamically. At least the <code>{number}</code> placeholder <strong>must</strong> exist. Choose from the following placeholders to format your number: %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $placeholder_html ); ?>
            </div>
            <?php
		} elseif( 'number_min_size' === $args['field'] ) {
			?>
            <input type="number" class="sab-number-preview-trigger sab-number-preview-number-min-size" name="<?php echo esc_attr( $option_name ); ?>" value="<?php echo $journal->get_number_min_size(); ?>" step="1" placeholder="0" />
            <div class="sab-additional-desc">
				<?php printf( _x( 'You can optionally force your numbers (the plain, sequential number) to have a certain minimum size. By increasing this option the number will be prefixed with zeros to reach the minimum size. Example: <ul><li>Minimum size: <code>3</code></li><li>Number: <code>5</code></li><li>Formatted number: <code>005</code></li></ul>', 'storeabill-core', 'woocommerce-germanized-pro' ) ); ?>
            </div>
			<?php
		} elseif( 'reset_interval' === $args['field'] ) {
			?>
            <select name="<?php echo esc_attr( $option_name ); ?>">
                <option value=""><?php _ex( 'None', 'storeabill-reset-interval', 'woocommerce-germanized-pro' );?></option>
                <?php foreach( sab_get_journal_reset_intervals() as $interval => $title ) : ?>
                    <option value="<?php echo esc_attr( $interval ); ?>" <?php selected( $interval, $journal->get_reset_interval() ); ?>><?php echo esc_html( $title ); ?></option>
                <?php endforeach; ?>
            </select>
            <div class="sab-additional-desc">
				<?php printf( _x( 'Optionally choose an interval to reset your sequential number to zero. This option might be useful if you are using date placeholders within your formatted number and want to reset the number periodically.', 'storeabill-core', 'woocommerce-germanized-pro' ) ); ?>
            </div>
			<?php
		}
	}

	public static function render_toggle_field( $args ) {
		$args = wp_parse_args( $args, array(
			'value'             => '',
			'id'                => '',
			'description'       => '',
			'custom_attributes' => array(),
			'css'               => '',
			'class'             => '',
			'suffix'            => '',
		) );

		// Custom attribute handling.
		$custom_attributes = array();

		if ( ! empty( $args['custom_attributes'] ) && is_array( $args['custom_attributes'] ) ) {
			foreach ( $args['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}

		?>
		<fieldset class="sab-toggle-wrapper">
			<a class="sab-toggle" href="#">
	            <span class="sab-input-toggle <?php echo ( 'yes' === $args['value'] ? 'sab-input-toggle--enabled' : 'sab-input-toggle--disabled' ); ?>">
	                <?php echo( 'yes' === $args['value'] ? _x( 'Yes', 'storeabill-core', 'woocommerce-germanized-pro' ) : _x( 'No', 'storeabill-core', 'woocommerce-germanized-pro' ) ); ?>
	            </span>
			</a>
			<input
			name="<?php echo esc_attr( $args['id'] ); ?>"
			id="<?php echo esc_attr( $args['id'] ); ?>"
			type="checkbox"
			style="display: none; <?php echo esc_attr( $args['css'] ); ?>"
			value="1"
			class="<?php echo esc_attr( $args['class'] ); ?>"
			<?php checked( $args['value'], 'yes' ); ?>
			<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
			/><?php echo esc_html( $args['suffix'] ); ?><?php echo $args['description']; // WPCS: XSS ok. ?>
		</fieldset>
		<?php
	}
}