<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_GZDP_Admin {

	/**
	 * Single instance of WooCommerce Germanized Main Class
	 *
	 * @var object
	 */
	protected static $_instance = null;

	protected $wizard = null;

	public static function instance() {
	    if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-germanized-pro' ), '1.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-germanized-pro' ), '1.0' );
	}
	
	public function __construct() {

		foreach ( $this->get_custom_setting_types() as $type ) {
			if ( method_exists( $this, $type . '_field' ) ) {
				add_action( 'woocommerce_admin_field_gzdp_' . $type, array( $this, $type . '_field' ), 0, 1 );
            }
		}

		// Email template filter
		add_filter( 'woocommerce_template_directory', array( $this, 'set_woocommerce_template_dir' ), 10, 2 );

		add_filter( 'woocommerce_admin_settings_sanitize_option', array( $this, 'save_field' ), 0, 3 );
		add_filter( 'woocommerce_gzd_template_check', array( $this, 'add_template_check' ), 10, 1 );
		add_action( 'woocommerce_gzd_status_after_tools', array( $this, 'generator_cache_clean' ), 10 );
		add_action( 'woocommerce_gzd_status_after_tools', array( $this, 'legacy_invoice_import' ), 10 );
		add_action( 'woocommerce_gzd_status_after_tools', array( $this, 'legacy_template_import' ), 10 );

		add_action( 'admin_init', array( $this, 'check_clear_generator_cache' ) );
		add_action( 'admin_init', array( $this, 'check_import_legacy_templates' ) );

        $this->wizward = require WC_GERMANIZED_PRO_ABSPATH . 'includes/admin/class-wc-gzdp-admin-setup-wizard.php';
	}

	public function generator_cache_clean() {
	    ?>
        <tr>
            <td><?php _e( 'Clear generator cache', 'woocommerce-germanized-pro' ); ?></td>
            <td class="help"><?php echo wc_help_tip( esc_attr( __( 'In case the terms generator does not work as expected you might want to clear the cache manually.', 'woocommerce-germanized-pro' ) ) ); ?></td>
            <td>
                <a href="<?php echo wp_nonce_url( add_query_arg( array( 'clear-generator-cache' => true ) ), 'wc-gzdp-clear-generator-cache' ); ?>" class="button button-secondary"><?php _e( 'Clear cache', 'woocommerce-germanized-pro' ); ?></a></td>
            </td>
        </tr>
        <?php
    }

	public function legacy_invoice_import() {
		$has_invoices     = \Vendidero\Germanized\Pro\Legacy\Importer::has_legacy_invoices();
		$hide_skip_button = true;

		if ( ! $has_invoices ) {
			return;
		}
		?>
        <tr>
            <td><?php _e( 'Import legacy documents', 'woocommerce-germanized-pro' ); ?></td>
            <td class="help"></td>
            <td>
                <p>
	                <?php printf( __( '3.0 is a major update with exciting <a href="%1$s" target="_blank">new features</a>. To make your existing documents (invoices, cancellations, packing slips) available within the latest version we will need to import and convert them. Depending on your number of documents that might take some time. The import will be queued and scheduled (processing 10 documents at a time). You can optionally choose to import documents starting from a certain year only. We will not delete old data to make sure no data is lost.', 'woocommerce-germanized-pro' ), 'https://vendidero.de/germanized-pro-3-0' ); ?>
                </p>
	            <?php include WC_GERMANIZED_PRO_ABSPATH . "includes/admin/views/html-import-legacy-invoices-form.php"; ?>
            </td>
        </tr>
		<?php
	}

	public function legacy_template_import() {
	    if ( ! get_option( 'wc_gzdp_invoice_simple' ) ) {
	        return;
	    }
		?>
        <tr>
            <td><?php _e( 'Import legacy templates', 'woocommerce-germanized-pro' ); ?></td>
            <td class="help"></td>
            <td>
                <a href="<?php echo wp_nonce_url( add_query_arg( array( 'import-legacy-templates' => true ) ), 'wc-gzdp-import-legacy-templates' ); ?>" class="button button-secondary"><?php _e( 'Import legacy templates', 'woocommerce-germanized-pro' ); ?></a></td>
            </td>
        </tr>
		<?php
	}

	public function check_clear_generator_cache() {
		if ( current_user_can( 'manage_woocommerce' ) && isset( $_GET['clear-generator-cache'] ) && isset( $_GET['_wpnonce'] ) && check_admin_referer( 'wc-gzdp-clear-generator-cache' ) ) {
		    WC_GZDP_Admin_Generator::instance()->clear_caches();

			wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=germanized' ) );
		}
	}

	public function check_import_legacy_templates() {
		if ( current_user_can( 'manage_woocommerce' ) && isset( $_GET['import-legacy-templates'] ) && isset( $_GET['_wpnonce'] ) && check_admin_referer( 'wc-gzdp-import-legacy-templates' ) ) {
			WC_GZDP_Install::create_legacy_template( 'invoice', false );
			WC_GZDP_Install::create_legacy_template( 'invoice_cancellation', false );
			WC_GZDP_Install::create_legacy_template( 'packing_slip', false );

			wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=germanized-storeabill' ) );
		}
	}

	public function add_template_check( $check ) {

	    $check['germanized_pro'] = array(
		    'title'             => __( 'Germanized for WooCommerce Pro', 'woocommerce-germanized-pro' ),
		    'path'              => WC_germanized_pro()->plugin_path() . '/templates',
		    'template_path'     => WC_germanized_pro()->template_path(),
		    'outdated_help_url' => '',
		    'files'             => array(),
		    'has_outdated'      => false,
        );

        return $check;
    }

	public function get_custom_setting_types() {
		return array( 'decimal', 'disclaimer', 'editor', 'notice', 'select_page', 'checkbox_multiple', 'attachment', 'margins' );
	}

	public function get_custom_setting_type( $type ) {
		return str_replace( 'gzdp_', '', $type );
	}

	public function is_custom_setting_type( $type ) {
		if ( strpos( $type, 'gzdp_' ) === false )
			return false;
		
		$type = $this->get_custom_setting_type( $type );
		return in_array( $type, $this->get_custom_setting_types() );
	}

	public function set_woocommerce_template_dir( $dir, $template ) {
		if ( file_exists( WC_germanized_pro()->plugin_path() . '/templates/' . $template ) )
			return 'woocommerce-germanized-pro';

		return $dir;
	}

	public function save_field( $value, $option, $raw_value ) {
		if ( $this->is_custom_setting_type( $option[ 'type' ] ) && method_exists( $this, 'sanitize_' . $this->get_custom_setting_type( $option[ 'type' ] ) . '_field' ) )
			$value = call_user_func_array( array( $this, 'sanitize_' . $this->get_custom_setting_type( $option[ 'type' ] ) . '_field' ), array( $option ) );

		return $value;
 	}

	public function sanitize_attachment_field( $value ) {
		$option_value = ( isset( $_POST[ $value['id'] ] ) ? explode( ',', stripslashes_deep( $_POST[ $value['id'] ] ) ) : '' );
		$option_value = array_map( 'absint', $option_value );

		if ( ! empty( $option_value ) && ! empty( $option_value[0] ) ) {
			if ( sizeof( $option_value ) == 1 )
				$option_value = $option_value[0];
		} else {
			$option_value = '';
		}

		return $option_value;
	}

	public function save_multiples( $settings ) {
		foreach ( $settings as $value ) {
			$type = $this->get_custom_setting_type( $value[ 'type' ] );

			if ( $this->is_custom_setting_type( $value[ 'type' ] ) && in_array( $type, array( 'attachment', 'checkbox_multiple', 'margins' ) ) && method_exists( $this, 'sanitize_' . $type . '_field' ) )
				update_option( str_replace( '[]', '', $value['id'] ), call_user_func_array( array( $this, 'sanitize_' . $type . '_field' ), array( $value ) ) );
		}
	}

	public function attachment_field_save( $value ) {
		update_option( str_replace( '[]', '', $value['id'] ), $this->sanitize_attachment_field( $value ) );
	}

	public function attachment_field( $value ) {
		$option_value = WC_Admin_Settings::get_option( $value['id'] );

		// Custom attribute handling.
		$custom_attributes = array();

		if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
			foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}

		if ( empty( $option_value ) ) {
			$option_value = array();
		}
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?>
                    <?php if ( ! empty( $value[ 'desc_tip' ] ) ) : ?>
                        <?php echo wc_help_tip( $value[ 'desc_tip' ] ); ?>
                    <?php endif; ?>
                </label>
			</th>
			<td class="forminp forminp-attachment wc-gzdp-attachment-wrapper">
				<input 
					id="<?php echo esc_attr( $value['id'] ); ?>" 
					name="<?php echo esc_attr( $value['id'] ); ?>" 
					type="hidden"
					style="<?php echo esc_attr( $value['css'] ); ?>"
					value="<?php echo esc_attr( implode( ',', (array) $option_value ) ); ?>"
					class="<?php echo esc_attr( $value['class'] ); ?> wc-gzdp-attachment-id-data"
					<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
				/>
				<input 
					id="<?php echo esc_attr( $value['id'] ); ?>_button" 
					data-input="<?php echo esc_attr( $value['id'] ); ?>" 
					data-type="<?php echo esc_attr( $value[ 'data-type' ] ); ?>"
					data-multiple="<?php echo esc_attr( ( isset( $value[ 'multiple' ] ) ? $value[ 'multiple' ] : false ) ); ?>"
					class="button button-wc-gzdp-attachment-field" 
					name="<?php echo esc_attr( $value['id'] ); ?>_button" 
					type="button" 
					value="<?php echo _x( 'Select or upload attachment', 'admin-field', 'woocommerce-germanized-pro' ); ?>" 
				/>
				<div class="current-attachment">
					<?php if ( ! empty( $option_value ) ) : ?>
						<?php foreach ( (array) $option_value as $option ) : ?>
							<div class="wc-gzdp-unset-attachment-wrap data-unset-<?php echo $option; ?>">
								<a class="unset-attachment" href="#" data-unset="<?php echo esc_attr( $option ); ?>"><span class="dashicons dashicons-no-alt"></span></a>
								<a class="" href="<?php echo get_edit_post_link( $option );?>" target="_blank"><?php echo get_the_title( $option ); ?></a>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
				<div class="clear"></div>
				<?php if ( $value[ 'desc' ] ) : ?>
					<p class="description"><?php echo wp_kses_post( $value[ 'desc' ] ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	public function margins_field( $value ) {

		$value['id']  = str_replace( '[]', '', $value['id'] );
		$option_value = WC_Admin_Settings::get_option( $value['id'] );

		if ( empty( $option_value ) ) {
			$option_value = $value['default'];
		}
		
		$value['class'] .= 'wc_input_decimal';
		
		if ( $value['desc_tip'] ) {
			$value['desc_tip'] = $value['desc'];
		}
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?>
                    <?php if ( ! empty( $value['desc_tip'] ) ) : ?>
                        <?php echo wc_help_tip( $value['desc_tip'] ); ?>
                    <?php endif; ?>
                </label>
			</th>
			<td class="forminp forminp-margins wc-gzdp-margins-wrapper">
				<input 
					id="<?php echo esc_attr( $value['id'] ); ?>" 
					size="3"
					name="<?php echo esc_attr( $value['id'] ); ?>[]" 
					type="text"
					style="<?php echo esc_attr( $value['css'] ); ?>"
					value="<?php echo wc_format_localized_decimal( $option_value[0] ); ?>"
					class="<?php echo esc_attr( $value['class'] ); ?>"
				/>
				<input 
					id="<?php echo esc_attr( $value['id'] ); ?>" 
					size="3"
					name="<?php echo esc_attr( $value['id'] ); ?>[]" 
					type="text"
					style="<?php echo esc_attr( $value['css'] ); ?>"
					value="<?php echo wc_format_localized_decimal( $option_value[1] ); ?>"
					class="<?php echo esc_attr( $value['class'] ); ?>"
				/>
				<input 
					id="<?php echo esc_attr( $value['id'] ); ?>" 
					size="3"
					name="<?php echo esc_attr( $value['id'] ); ?>[]" 
					type="text"
					style="<?php echo esc_attr( $value['css'] ); ?>"
					value="<?php echo wc_format_localized_decimal( $option_value[2] ); ?>"
					class="<?php echo esc_attr( $value['class'] ); ?>"
				/>
				<input 
					id="<?php echo esc_attr( $value['id'] ); ?>" 
					size="3"
					name="<?php echo esc_attr( $value['id'] ); ?>[]" 
					type="text"
					style="<?php echo esc_attr( $value['css'] ); ?>"
					value="<?php echo wc_format_localized_decimal( isset( $option_value[3] ) ? $option_value[3] : $value[ 'default' ][3] ); ?>"
					class="<?php echo esc_attr( $value['class'] ); ?>"
				/>
				<div class="clear"></div>
				<?php if ( $value['desc'] && empty( $value['desc_tip'] ) ) : ?>
					<p class="description"><?php echo wp_kses_post( $value['desc'] ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	public function sanitize_margins_field( $value ) {
		$value['id'] = str_replace( '[]', '', $value['id'] );
		
		$option_value = isset( $_POST[ $value['id'] ] ) ? $_POST[ $value['id'] ] : array( 0, 0, 0 );

		if ( ! empty( $option_value ) ) {
			$option_value = array_map( 'wc_format_decimal', $option_value );
		}

		return $option_value;
	}

	public function sanitize_decimal_field( $value ) {
		$option_value = isset( $_POST[ $value['id'] ] ) ? wc_format_decimal( stripslashes_deep( $_POST[ $value['id'] ] ) ) : '';

		return $option_value;
	}

	public function decimal_field_save( $value ) {
		update_option( $value['id'], $this->sanitize_decimal_field( $value ) );
	}

	public function decimal_field( $value ) {
		$option_value = WC_Admin_Settings::get_option( $value['id'] );

		// Custom attribute handling.
		$custom_attributes = array();

		if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
			foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}

		if ( ! empty( $option_value ) ) {
			update_option( $value['id'], wc_format_decimal( $option_value ) );
		}

		if ( empty( $option_value ) ) {
			$option_value = $value['default'];
		}

		$value['class'] .= 'wc_input_decimal';

		if ( $value['desc_tip'] ) {
			$value['desc_tip'] = $value['desc'];
		}
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?>
                    <?php if ( ! empty( $value['desc_tip'] ) ) : ?>
                        <?php echo wc_help_tip( $value['desc_tip'] ); ?>
                    <?php endif; ?>
                </label>
			</th>
			<td class="forminp forminp-gzdp-decimal wc-gzdp-decimal-wrapper">
				<input 
					id="<?php echo esc_attr( $value['id'] ); ?>" 
					name="<?php echo esc_attr( $value['id'] ); ?>" 
					type="text"
					style="<?php echo esc_attr( $value['css'] ); ?>"
					value="<?php echo wc_format_localized_decimal( $option_value ); ?>"
					class="<?php echo esc_attr( $value['class'] ); ?>"
					<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
				/>
				<div class="clear"></div>
				<?php if ( $value[ 'desc' ] && empty( $value['desc_tip'] ) ) : ?>
					<p class="description"><?php echo wp_kses_post( $value['desc'] ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	public function sanitize_editor_field( $value ) {
		$option_value = ( isset( $_POST[ $value['id'] ] ) ? esc_html( $_POST[ $value['id'] ] ) : '' );
		return $option_value;
	}

	public function editor_field_save( $value ) {
		update_option( $value['id'], $this->sanitize_editor_field( $value ) );
	}

	public function editor_field( $value ) {
		$option_value = WC_Admin_Settings::get_option( $value['id'] );

		// Custom attribute handling.
		$custom_attributes = array();

		if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
			foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}

		if ( empty( $option_value ) )
			$option_value = $value['default'];

		$value['class'] .= 'wc_wp_editor';

		if ( $value['desc_tip'] ) {
			$value['desc_tip'] = $value['desc'];
		}
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?>
					<?php if ( ! empty( $value['desc_tip'] ) ) : ?>
						<?php echo wc_help_tip( $value['desc_tip'] ); ?>
					<?php endif; ?>
                </label>
			</th>
			<td class="forminp forminp-editor wc-gzdp-editor-wrapper">
				<?php wp_editor( htmlspecialchars_decode( $option_value ), $value['id'], array( 'textarea_name' => $value['id'], 'textarea_rows' => 5, 'editor_class' => $value['class'], 'editor_css' => '<style>' . $value['css'] . '</style>', 'media_buttons' => false, 'teeny' => true ) ); ?>

                <div class="clear"></div>

                <?php if ( $value['desc'] && empty( $value['desc_tip'] ) ) : ?>
					<p class="description"><?php echo wp_kses_post( $value['desc'] ); ?></p>
				<?php endif; ?>

                <input type="hidden" name="<?php echo esc_attr( $value['id'] ); ?>-attribute-container" id="<?php echo esc_attr( $value['id'] ); ?>-attribute-container" value="" <?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?> />
			</td>
		</tr>
		<?php
	}

	public function sanitize_checkbox_multiple_field( $value ) {

		$value[ 'id' ] = str_replace( '[]', '', $value[ 'id' ] );

		$option_value = ( isset( $_POST[ $value['id'] ] ) ? $_POST[ $value['id'] ] : '' );
		
		if ( is_array( $option_value ) && ! empty( $option_value ) )
			$option_value = array_map( 'sanitize_text_field', $option_value );
		
		return $option_value;
	}

	public function checkbox_multiple_field_save( $value ) {
		update_option( str_replace( '[]', '', $value[ 'id' ] ), $this->sanitize_checkbox_multiple_field( $value ) );
	}

	public function checkbox_multiple_field( $value ) {
		$value[ 'id' ] = str_replace( '[]', '', $value[ 'id' ] );
		$option_values = (array) WC_Admin_Settings::get_option( $value[ 'id' ] );

		$custom_attributes = array();

		if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
			foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}
		?>
		<tr valign="top">
			<th scope="row" class="titledesc"><label><?php echo esc_html( $value['title'] ) ?></label></th>
			<td class="forminp forminp-checkbox">
				<?php if ( ! empty( $value[ 'options' ] ) ) : ?>
					<?php foreach( $value[ 'options' ] as $key => $title ) : ?>
						<fieldset>
							<label for="<?php echo $value['id'] . '_' . $key; ?>">
								<input
									name="<?php echo esc_attr( $value['id'] ); ?>[]"
									id="<?php echo esc_attr( $value['id'] . '_' . $key ); ?>"
									type="checkbox"
									value="<?php echo esc_attr( $key ); ?>"
									<?php checked( ( ( in_array( $key, $option_values ) || $value[ 'default' ] == $key ) ? true : false ), true ); ?>
									<?php echo implode( ' ', $custom_attributes ); ?>
								/> <?php echo $title ?>
							</label>
						</fieldset>
					<?php endforeach; ?>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	public function sanitize_disclaimer_field( $value ) {
		return false;
	}

	public function disclaimer_field_save( $value ) {
		return false;
	}

	public function disclaimer_field( $value ) {
		?>
		<tr valign="top">
			<td class="forminp forminp-checkbox wc-gzdp-disclaimer notice notice-success">
				<div class="disclaimer-text">
					<h3>Disclaimer</h3>
					<?php echo html_entity_decode( $value[ 'desc_tip' ] ); ?>
				</div>
				<fieldset>
					<label for="<?php echo $value['id'] ?>">
						<input
							name="disclaimer"
							id="<?php echo esc_attr( $value['id'] ); ?>"
							type="checkbox"
							class="wc-gzdp-disclaimer-input"
							value="1"
							<?php echo implode( ' ', $value[ 'custom_attributes' ] ); ?>
						/> <?php echo $value[ 'desc' ] ?>
					</label>
				</fieldset>
			</td>
		</tr>
		<?php
	}

	public function sanitize_select_page_field( $value ) {
		$option_value = ( isset( $_POST[ $value['id'] ] ) ? absint( $_POST[ $value['id'] ] ) : '' );
		return $option_value;
	}

	public function select_page_field_save( $value ) {
		update_option( $value[ 'id' ], $this->sanitize_select_page_field( $value ) );
	}

	public function select_page_field( $value ) {
		$args = array(
			'name'             => $value['id'],
			'id'               => $value['id'],
			'sort_column'      => 'menu_order',
			'sort_order'       => 'ASC',
			'show_option_none' => ' ',
			'class'            => $value['class'],
			'echo'             => false,
			'selected'         => absint( WC_Admin_Settings::get_option( $value['id'] ) )
		);

		if ( isset( $value['args'] ) ) {
			$args = wp_parse_args( $value['args'], $args );
		}

		if ( $value[ 'desc_tip' ] )
			$value[ 'desc_tip' ] = $value[ 'desc' ];

		// Custom attribute handling
		$custom_attributes = array();

		if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
			foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}
		
		?><tr valign="top" class="single_select_page">
			<th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $value['id'] ); ?>">
	                <?php echo esc_html( $value['title'] ) ?>
					<?php if ( ! empty( $value[ 'desc_tip' ] ) ) : ?>
						<?php echo wc_help_tip( $value[ 'desc_tip' ] ); ?>
					<?php endif; ?>
                </label>
			</th>
			<td class="forminp">
				<?php echo str_replace(' id=', " data-placeholder='" . __( 'Select a page&hellip;', 'woocommerce-germanized-pro' ) .  "' " . implode( " ", $custom_attributes ) . " style='" . $value['css'] . "' class='" . $value['class'] . "' id=", wp_dropdown_pages( $args ) ); ?>
			</td>
		</tr>
		<?php
	}

	public function sanitize_notice_field( $value ) {
		return false;
	}

	public function notice_field_save( $value ) {
		return false;
	}

	public function notice_field( $value ) {
		?>
		<tr valign="top">
			<td class="forminp wc-gzdp-notice notice notice-<?php echo ( isset( $value[ 'notice_type' ] ) ? $value[ 'notice_type' ] : "warning" ); ?>">
				<?php echo ( isset( $value[ 'custom_attributes' ][ 'data-custom-desc' ] ) ? html_entity_decode( $value[ 'custom_attributes' ][ 'data-custom-desc' ] ) : '' ); ?>
			</td>
		</tr>
		<?php
	}

}

WC_GZDP_Admin::instance();