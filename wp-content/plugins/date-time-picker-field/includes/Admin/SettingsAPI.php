<?php
/**
 * weDevs Settings API wrapper class
 *
 * @version 1.3 (27-Sep-2016)
 *
 * @author Tareq Hasan <tareq@weDevs.com>
 * @link https://tareq.co Tareq Hasan
 * @example example/oop-example.php How to use the class
 */
namespace CMoreira\Plugins\DateTimePicker\Admin;

if ( ! class_exists( 'SettingsAPI' ) ) :
	class SettingsAPI {

		/**
		 * settings sections array
		 *
		 * @var array
		 */
		protected $settings_sections = array();

		protected $date_range_picker_image;
		protected $date_picker_image;
		protected $time_picker_image;
		protected $date_time_picker_image;

		/**
		 * Settings fields array
		 *
		 * @var array
		 */
		protected $settings_fields = array();

		public function __construct() {

			$this->date_range_picker_image = plugins_url( '../../assets/img/', __FILE__  ) . 'date-range.png';
			$this->date_picker_image = plugins_url( '../../assets/img/', __FILE__  ) . 'date-picker.png';
			$this->time_picker_image = plugins_url( '../../assets/img/', __FILE__  ) . 'time-picker.png';
			$this->date_time_picker_image = plugins_url( '../../assets/img/', __FILE__  ) . 'date-and-time-picker.png';
		}


		/**
		 * Set settings sections
		 *
		 * @param array $sections setting sections array
		 */
		public function set_sections( $sections ) {
			$this->settings_sections = $sections;

			return $this;
		}


		/**
		 * Set settings navigation tabs
		 *
		 * @param array $sections setting sections array
		 */
		public function set_navigation( $sections ) {
			$this->settings_navigation = $sections;

			return $this;
		}



		/**
		 * Add a single section
		 *
		 * @param array $section
		 */
		public function add_section( $section ) {
			$this->settings_sections[] = $section;

			return $this;
		}

		/**
		 * Set settings fields
		 *
		 * @param array $fields settings fields array
		 */
		public function set_fields( $fields ) {
			$this->settings_fields = $fields;

			return $this;
		}

		public function add_field( $section, $field ) {
			$defaults = array(
				'name'  => '',
				'label' => '',
				'desc'  => '',
				'type'  => 'text',
			);

			$arg                                 = wp_parse_args( $field, $defaults );
			$this->settings_fields[ $section ][] = $arg;

			return $this;
		}

		/**
		 * Initialize and registers the settings sections and fileds to WordPress
		 *
		 * Usually this should be called at `admin_init` hook.
		 *
		 * This function gets the initiated settings sections and fields. Then
		 * registers them to WordPress and ready for use.
		 */
		public function admin_init() {
			// register settings sections
			foreach ( $this->settings_sections as $section ) {
				if ( false == get_option( $section['id'] ) ) {
					add_option( $section['id'] );
				}

				if ( isset( $section['desc'] ) && ! empty( $section['desc'] ) ) {
					$section['desc'] = '<div class="inside">' . $section['desc'] . '</div>';
					$callback        = function() use ( $section ) {
						echo str_replace( '"', '\"', $section['desc'] );
					};
				} elseif ( isset( $section['callback'] ) ) {
					$callback = $section['callback'];
				} else {
					$callback = null;
				}

				add_settings_section( $section['id'], $section['title'], $callback, $section['id'] );
			}

			// register settings fields
			foreach ( $this->settings_fields as $section => $field ) {
				foreach ( $field as $option ) {

					$data_type = (isset($option['data']) ? $option['data'] : $section);
					$name     = $option['name'];
					$type     = isset( $option['type'] ) ? $option['type'] : 'text';
					$label    = isset( $option['label'] ) ? $option['label'] : '';
					$callback = isset( $option['callback'] ) ? $option['callback'] : array( $this, 'callback_' . $type );

					$args = array(
						'id'                => $name,
						'class'             => isset( $option['class'] ) ? $option['class'] : $name,
						'label_for'         => "{$data_type}[{$name}]",
						'desc'              => isset( $option['desc'] ) ? $option['desc'] : '',
						'name'              => $label,
						'section'           => $data_type,
						'size'              => isset( $option['size'] ) ? $option['size'] : null,
						'options'           => isset( $option['options'] ) ? $option['options'] : '',
						'std'               => isset( $option['default'] ) ? $option['default'] : '',
						'sanitize_callback' => isset( $option['sanitize_callback'] ) ? $option['sanitize_callback'] : '',
						'type'              => $type,
						'placeholder'       => isset( $option['placeholder'] ) ? $option['placeholder'] : '',
						'min'               => isset( $option['min'] ) ? $option['min'] : '',
						'max'               => isset( $option['max'] ) ? $option['max'] : '',
						'step'              => isset( $option['step'] ) ? $option['step'] : '',
					);

					add_settings_field( "{$data_type}[{$name}]", $label, $callback, $section, $section, $args );
				}
			}

			// creates our settings in the options table
			#foreach ( $this->settings_sections as $section ) {
				register_setting( 'dtpicker', 'dtpicker', array( $this, 'sanitize_options' ) );
				register_setting( 'dtpicker', 'dtpicker_advanced', array( $this, 'sanitize_options' ) );
			#}
		}

		/**
		 * Get field description for display
		 *
		 * @param array $args settings field args
		 */
		public function get_field_description( $args ) {
			if ( ! empty( $args['desc'] ) ) {
				$desc = sprintf( '<div class="description-text"><p>%s</p></div>', $args['desc'] );
			} else {
				$desc = '';
			}

			return $desc;
		}

		/**
		 * Displays a text field for a settings field
		 *
		 * @param array $args settings field args
		 */
		public function callback_text( $args ) {

			$value       = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size        = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
			$type        = isset( $args['type'] ) ? $args['type'] : 'text';
			$placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';

			$html = '<div class="field-group-wrap"><div class="field-group picker-field ' . $args['tab'] . '" id="' . $args['tab'] . '">';
			if ($args['label'] != '') {
				$html .= sprintf( '<label class="input-label">%1$s</label>', $args['label'] );
			}
			$html .= $this->get_field_description( $args );
			$html .= sprintf( '<div class="field-input"><input type="%1$s" class="%2$s-text" id="%3$s[%4$s]" name="%3$s[%4$s]" value="%5$s"%6$s/></div>%7$s', $type, $size, $args['section'], $args['id'], $value, $placeholder, ($args['name'] == 'selector' ? ' <a href="javascript:void(0);" id="copy-field">' . __('Copy', 'date-time-picker-field') . '</a>' : false) );
			$html .= '</div></div>';

			echo $html;
		}

		/**
		 * Displays a url field for a settings field
		 *
		 * @param array $args settings field args
		 */
		public function callback_url( $args ) {
			$this->callback_text( $args );
		}

		/**
		 * Displays a number field for a settings field
		 *
		 * @param array $args settings field args
		 */
		public function callback_number( $args ) {
			$value       = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size        = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
			$type        = isset( $args['type'] ) ? $args['type'] : 'number';
			$placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';
			$min         = ( $args['min'] == '' ) ? '' : ' min="' . $args['min'] . '"';
			$max         = ( $args['max'] == '' ) ? '' : ' max="' . $args['max'] . '"';
			$step        = ( $args['step'] == '' ) ? '' : ' step="' . $args['step'] . '"';

			$html  = sprintf( '<input type="%1$s" class="%2$s-number" id="%3$s[%4$s]" name="%3$s[%4$s]" value="%5$s"%6$s%7$s%8$s%9$s/>', $type, $size, $args['section'], $args['id'], $value, $placeholder, $min, $max, $step );
			$html .= $this->get_field_description( $args );

			echo $html;
		}

		/**
		 * Displays a checkbox for a settings field
		 *
		 * @param array $args settings field args
		 */
		public function callback_checkbox( $args ) {
			$value =  esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$html = '<div class="field-group-wrap"><div class="field-group picker-field ' . $args['tab'] . '" id="' . $args['tab'] . '"><div class="field-input">';
			$html .= sprintf( '<label class="check-label"><input type="checkbox" id="" class="regular-text" name="%1$s[%2$s]" value="1" %3$s />%4$s</label>', $args['section'], $args['id'], checked($value, '1', false), $args['label'] );
			$html .= sprintf( '<div class="description-text mt-10"><p>%1$s</p></div></label>', $args['desc'] );
			$html .= '</div></div></div>';

			echo $html;
		}

		/**
		 * Displays a toggle button for a settings field
		 *
		 * @param array $args settings field args
		 */
		public function callback_togglebutton( $args ) {

			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );

			$html  = '<div class="field-group-wrap"><div class="field-group picker-field ' . $args['tab'] . '" id="' . $args['tab'] . '"><fieldset class="mt-0"><div class="switch-container"><div class="switch-desc">';
			$html .= sprintf( '<label class="check-label">%1$s</label></div><div class="switch-item right">', $args['label'] );
			$html .= sprintf( '<label class="switch" for="wpuf-%1$s[%2$s]">', $args['section'], $args['id'] );
			$html .= sprintf( '<input type="hidden" name="%1$s[%2$s]" value="%3$s" />', $args['section'], $args['id'], $value );
			$html .= sprintf( '<span class="slider round"></span><input type="checkbox" id="wpuf-%1$s[%2$s]" class="checkbox" name="%1$s[%2$s]" value="on" %3$s /></label></div></div>', $args['section'], $args['id'], checked( $value, 'on', false ) );
			$html .= sprintf('<div class="description-text"><p>%1$s</p></div>', $args['desc']);
			$html .= '</fieldset></div></div>';
			echo $html;
		}

		/**
		 * Displays a radio button group for settings field
		 *
		 * @param array $args settings field args
		 */
		public function callback_radiogroup ($args) {

			$value =  esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$html = '<div class="radio-group-wrap ' . $args['tab'] . '" id="' . $args['tab'] . '"><div class="field-group">';
			$html .= sprintf( '<label class="radiogroup-label">%1$s</label>', $args['label'] );
			$html .= $this->get_field_description( $args );
			$html .= '<div class="field-input">';
			foreach ( $args['options'] as $key => $label ) {
				if(isset($args['disabled']) && !empty($args['disabled']) && $args['disabled'] == $key){
					$html .= '<div class="custom-radio-grid"><span class="disable-radio-look"></span><span class="disable-radio-label">' . $label . '</span></div>';
				}else{
					$html   .= '<div class="radio-selectors"><label class="radio-label ' . $args['tab'] . '" for="'. $args['id'] .'">';
					$html   .= '<input type="radio" class="'. $args['id'] .'" name="'. $args['section'].'['.$args['id'].']'.'" value="'. $key.'" '.  checked( $value, $key, false )  .' />';
					$html   .= $label . '</label></div>';
				}
			}

			$html .= '</div></div></div>';

			echo $html;
		}

		/**
		 * Displays a checkbox for a settings field
		 *
		 * @param array $args settings field args
		 */
		public function callback_hidden( $args ) {

			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );

			$html  = '<div class="hidden-wrap picker-field ' . $args['tab'] . '" id="' . $args['tab'] . '">';
			$html .= sprintf( '<input type="hidden" name="%1$s[%2$s]" value="%3$s" />', $args['section'], $args['id'], $value );
			$html .= '</div>';

			echo $html;
		}

		/**
		 * Displays a multicheckbox for a settings field
		 *
		 * @param array $args settings field args
		 */
		public function callback_multicheck( $args ) {

			$value = $this->get_option( $args['id'], $args['section'], $args['std'] );
			$html = '<div class="field-group-wrap"><div class="field-group picker-field ' . $args['tab'] . '" id="' . $args['tab'] . '">';
			$html .= sprintf( '<label class="multicheck-label">%1$s</label>', $args['label'] );
			$html .= '<fieldset class="mt-0">';
			$html .= $this->get_field_description( $args );
			$html .= sprintf( '<input type="hidden" name="%1$s[%2$s]" value="" />', $args['section'], $args['id'] );
			foreach ( $args['options'] as $key => $label ) {
				$checked = isset( $value[ $key ] ) ? $value[ $key ] : '';
				$html   .= sprintf( '<div class="field-input"><div class="picker-input"><label class="checkbox-multi" for="wpuf-%1$s[%2$s][%3$s]">', $args['section'], $args['id'], $key );
				$html   .= sprintf( '<input type="checkbox" class="checkbox" id="wpuf-%1$s[%2$s][%3$s]" name="%1$s[%2$s][%3$s]" value="%3$s" %4$s />', $args['section'], $args['id'], $key, checked( $checked, $key, false ) );
				$html   .= sprintf( '%1$s</label></div></div>', $label );
			}

			$html .= '</fieldset></div></div>';

			echo $html;
		}

		/**
		 * Displays a radio button for a settings field
		 *
		 * @param array $args settings field args
		 */
		public function callback_radio( $args ) {

			$value = $this->get_option( $args['id'], $args['section'], $args['std'] );
			$html  = '<fieldset>';

			foreach ( $args['options'] as $key => $label ) {
				$html .= sprintf( '<label for="wpuf-%1$s[%2$s][%3$s]">', $args['section'], $args['id'], $key );
				$html .= sprintf( '<input type="radio" class="radio" id="wpuf-%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s" %4$s />', $args['section'], $args['id'], $key, checked( $value, $key, false ) );
				$html .= sprintf( '%1$s</label><br>', $label );
			}

			$html .= $this->get_field_description( $args );
			$html .= '</fieldset>';

			echo $html;
		}

		/**
		 * Displays a selectbox for a settings field
		 *
		 * @param array $args settings field args
		 */
		public function callback_select( $args ) {

			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';

			$html = '<div class="field-group-wrap"><div class="field-group picker-field ' . $args['tab'] . '" id="' . $args['tab'] . '">';
			$html .= sprintf( '<label class="select-label">%1$s</label>', $args['label'] );
			$html .= $this->get_field_description( $args );
			$html .= sprintf( '<div class="field-input"><select class="%1$s-text" name="%2$s[%3$s]" id="%2$s[%3$s]">', $size, $args['section'], $args['id'] );

			foreach ( $args['options'] as $key => $label ) {
				$html .= sprintf( '<option value="%s"%s>%s</option>', $key, selected( $value, $key, false ), $label );
			}

			$html .= sprintf( '</select></div>' );
			$html .= '</div></div>';

			echo $html;
		}

		/**
		 * Displays a textarea for a settings field
		 *
		 * @param array $args settings field args
		 */
		public function callback_textarea( $args ) {

			$value       = esc_textarea( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size        = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
			$placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';

			$html  = sprintf( '<textarea rows="5" cols="55" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]"%4$s>%5$s</textarea>', $size, $args['section'], $args['id'], $placeholder, $value );
			$html .= $this->get_field_description( $args );

			echo $html;
		}

		/**
		 * Displays the html for a settings field
		 *
		 * @param array $args settings field args
		 * @return string
		 */
		public function callback_html( $args ) {
			echo $this->get_field_description( $args );
		}

		/**
		 * Displays a rich text textarea for a settings field
		 *
		 * @param array $args settings field args
		 */
		public function callback_wysiwyg( $args ) {

			$value = $this->get_option( $args['id'], $args['section'], $args['std'] );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : '500px';

			echo '<div style="max-width: ' . $size . ';">';

			$editor_settings = array(
				'teeny'         => true,
				'textarea_name' => $args['section'] . '[' . $args['id'] . ']',
				'textarea_rows' => 10,
			);

			if ( isset( $args['options'] ) && is_array( $args['options'] ) ) {
				$editor_settings = array_merge( $editor_settings, $args['options'] );
			}

			wp_editor( $value, $args['section'] . '-' . $args['id'], $editor_settings );

			echo '</div>';

			echo $this->get_field_description( $args );
		}

		/**
		 * Displays a file upload field for a settings field
		 *
		 * @param array $args settings field args
		 */
		function callback_file( $args ) {

			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
			$id    = $args['section'] . '[' . $args['id'] . ']';
			$label = isset( $args['options']['button_label'] ) ? $args['options']['button_label'] : __( 'Choose File' );

			$html  = sprintf( '<input type="text" class="%1$s-text wpsa-url" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value );
			$html .= '<input type="button" class="button wpsa-browse" value="' . $label . '" />';
			$html .= $this->get_field_description( $args );

			echo $html;
		}

		/**
		 * Displays a password field for a settings field
		 *
		 * @param array $args settings field args
		 */
		function callback_password( $args ) {

			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';

			$html  = sprintf( '<input type="password" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value );
			$html .= $this->get_field_description( $args );

			echo $html;
		}

		/**
		 * Displays a color picker field for a settings field
		 *
		 * @param array $args settings field args
		 */
		function callback_color( $args ) {

			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';

			$html  = sprintf( '<input type="text" class="%1$s-text wp-color-picker-field" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s" data-default-color="%5$s" />', $size, $args['section'], $args['id'], $value, $args['std'] );
			$html .= $this->get_field_description( $args );

			echo $html;
		}


		/**
		 * Displays a select box for creating the pages select box
		 *
		 * @param array $args settings field args
		 */
		function callback_pages( $args ) {

			$dropdown_args = array(
				'selected' => esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) ),
				'name'     => $args['section'] . '[' . $args['id'] . ']',
				'id'       => $args['section'] . '[' . $args['id'] . ']',
				'echo'     => 0,
			);
			$html          = wp_dropdown_pages( $dropdown_args );
			echo $html;
		}

		/**
		 * Sanitize callback for Settings API
		 *
		 * @return mixed
		 */
		function sanitize_options( $options ) {

			if ( ! $options || !is_array($options) ) {
				return $options;
			}

			foreach ( $options as $option_slug => $option_value ) {
				$sanitize_callback = $this->get_sanitize_callback( $option_slug );

				// If callback is set, call it.
				if ( $sanitize_callback ) {
					$options[ $option_slug ] = call_user_func( $sanitize_callback, $option_value );
					continue;
				}
			}

			return $options;
		}

		/**
		 * Get sanitization callback for given option slug
		 *
		 * @param string $slug option slug
		 *
		 * @return mixed string or bool false
		 */
		function get_sanitize_callback( $slug = '' ) {
			if ( empty( $slug ) ) {
				return false;
			}

			// Iterate over registered fields and see if we can find proper callback.
			foreach ( $this->settings_fields as $section => $options ) {
				foreach ( $options as $option ) {
					if ( $option['name'] != $slug ) {
						continue;
					}

					// Return the callback name.
					return isset( $option['sanitize_callback'] ) && is_callable( $option['sanitize_callback'] ) ? $option['sanitize_callback'] : false;
				}
			}

			return false;
		}

		/**
		 * Get the value of a settings field
		 *
		 * @param string $option  settings field name
		 * @param string $section the section name this field belongs to
		 * @param string $default default text if it's not found
		 * @return string
		 */
		public function get_option( $option, $section, $default = '' ) {

			$options = get_option( $section );

			if ( isset( $options[ $option ] ) ) {
				return $options[ $option ];
			}

			return $default;
		}

		/**
		 * Show navigations as tab
		 *
		 * Shows all the settings section labels as tab
		 */
		public function show_navigation() {
			$html = '<h2 class="nav-tab-wrapper">';

			$count = count( $this->settings_navigation );

			// don't show the navigation if only one section exists.
			if ( $count === 1 ) {
				return;
			}

			foreach ( $this->settings_navigation as $tab ) {
				if ($tab['id'] == 'dateRange') {
					$html .= sprintf( '<span class="nav-tab">%1$s</span>', $tab['title'] );
				} else {
					$html .= sprintf( '<a href="#%1$s" class="tab nav-tab" id="%1$s-tab">%2$s</a>', $tab['id'], $tab['title'] );
				}
			}

			$html .= '</h2>';

			echo $html;
		}

		/**
		 * Show the section settings forms
		 *
		 * This function displays every sections in a different form
		 */
		public function show_forms() {
			$this->style_fix();
		?>
		<form method="post" action="options.php" novalidate="novalidate">
			<?php
				do_action( 'wsa_form_top_dtpicker' );
				do_action( 'wsa_form_top_dtpicker_advanced' );

				// echo '<div class="advertisement-wrap">
				// 	<a href="https://www.inputwp.com/pro" target="_blank">
				// 		<img src="https://www.inputwp.com/wp-content/uploads/upgrade-pro-inputwp.png" width="" height="" class="advertisement" />
				// 	</a>
				// </div>';

				$datepicker = $timepicker = $general = array();
				foreach ($this->settings_fields as $key => $field) {
					settings_fields( $key );

					foreach ($field as $item) {

						$item['section'] = (isset($item['data']) ? $item['data'] : $key);
						$item['id'] = $item['name'];
						$item['std'] = $item['default'];
						$tab = $item['tab'];

						if ($tab == 'all') {
							$this->display_Settings_fields($item);
						} elseif ($tab == 'datePicker') {
							$datepicker[] = $item;
						} elseif ($tab == 'timePicker') {
							$timePicker[] = $item;
						} elseif ($tab == 'general') {
							$general[] = $item;
						}
					}

					echo '<div class="metabox-holder pt-0 mt-30">
						<div class="tab-group">
							<div class="main-custom-switch-container">
								<h4 class="tab-main-heading">' . __('Details', 'date-time-picker-field') . '</h4>
							</div>';

				}

				foreach ($general as $field) {
					$this->display_Settings_fields($field);
				}

					echo '<div class="field-group-wrap">
							<div class="field-group">';
								submit_button(__( 'Save', 'date-time-picker-field' ), 'primary', 'submit', false);
							echo '</div>
						</div>
						</div></div>
						<div class="mt-30">
							<a href="https://www.inputwp.com/pro" target="_blank" class="pro-poster">
								<img src="https://www.inputwp.com/wp-content/uploads/upgrade-pro-inputwp.png" width="" height="" class="advertisement" />
							</a>
						</div>
						<div class="documentation-wrap">
							<label>Documentation</label>
							<ul>
								<li>
									<a target="_blank" href="https://www.inputwp.com/documentation/how-to-integrate-with-contact-form-7/">How to integrate with Contact Form 7</a>
								</li>
								<li>
									<a target="_blank" href="https://www.inputwp.com/documentation/how-to-integrate-with-divi/">How to integrate with Divi</a>
								</li>
								<li>
									<a target="_blank" href="https://www.inputwp.com/documentation/how-to-use-manual-integration-css-selector">How to use Manual integration</a>
								</li>
							</ul>
						</div>
						<div class="metabox-holder picker-box datePicker pt-0 mt-30">
							<div class="tab-group">
								<div class="main-custom-switch-container">
									<h4 class="tab-main-heading">' . __('Date picker', 'date-time-picker-field') . '</h4>
								</div>';

				foreach ($datepicker as $field) {
					$this->display_Settings_fields($field);
				}

				echo '</div></div>
					<div class="metabox-holder picker-box timePicker pt-0 mt-30">
						<div class="tab-group">
							<div class="main-custom-switch-container">
								<h4 class="tab-main-heading">' . __('Time picker', 'date-time-picker-field') . '</h4>
							</div>';

				foreach ($timePicker as $field) {
					$this->display_Settings_fields($field);
				}

				echo '</div></div>';
				do_action( 'wsa_form_bottom_dtpicker' );
				do_action( 'wsa_form_bottom_dtpicker_advanced' );
			?>
		</form>

			<?php $this->script();
		}


		public function display_Settings_fields($item) {

			switch ($item['type']) {
				case 'text':
					$this->callback_text($item);
					break;

				case 'select':
					$this->callback_select($item);
					break;

				case 'hidden':
					$this->callback_hidden($item);
					break;

				case 'checkbox':
					$this->callback_checkbox($item);
					break;

				case 'togglebutton':
					$this->callback_togglebutton($item);
					break;

				case 'multiselect':
					$this->callback_multiselect($item);
					break;

				case 'multicheck':
					$this->callback_multicheck($item);
					break;

				case 'radiogroup':
					$this->callback_radiogroup($item);
					break;
			}
		}


		/**
		 * Tabbable JavaScript codes & Initiate Color Picker
		 *
		 * This code uses localstorage for displaying active tabs
		 */
		public function script() {
		?>
			<script>
				jQuery(document).ready(function($) {

					function sliderCheck(element) {
						var checked = $(element).find('input[type="checkbox"]').attr('checked');
						if (checked != undefined) {
							$(element).find('input[type="checkbox"]').removeAttr('checked').end().find('.slider').removeClass('checked').end().find('input[type="checkbox"], input[type="hidden"]').val('off').end();
						} else {
							$(element).find('input[type="checkbox"]').attr('checked', 'checked').end().find('.slider').addClass('checked').end().find('input[type="checkbox"], input[type="hidden"]').val('on').end();
						}
					}

					function sliderState(element) {
						var checked = $(element).find('input[type="checkbox"]').attr('checked');
						if (checked != undefined) {
							$(element).find('.slider').addClass('checked').end().find('input[type="checkbox"], input[type="hidden"]').val('on').end();
						} else {
							$(element).find('.slider').removeClass('checked').end().find('input[type="checkbox"], input[type="hidden"]').val('off').end();
						}
					}
					function toggle_tabs(item) {
						if (item == '#datePicker') {
							$('input[name="dtpicker[datepicker]"]').val('on');
							$('input[name="dtpicker[timepicker]"]').val('off');
						} else if (item == '#timePicker') {
							$('input[name="dtpicker[datepicker]"]').val('off');
							$('input[name="dtpicker[timepicker]"]').val('on');
						} else if (item == '#dateTimePicker') {
							$('input[name="dtpicker[datepicker]"]').val('on');
							$('input[name="dtpicker[timepicker]"]').val('on');
						}

						jQuery('.picker-box').hide();
						if (item == '#dateTimePicker') {
							$('.picker-box').show();
						} else {
							if (item != undefined) {
								$('.' + item.slice(1) + ', .all, .general, .global').show();
							}
						}

						if (item == '#datePicker') {
							$('.general-gap').hide();
						} else {
							$('.general-gap').show();
						}
					}

					var picker_type  = jQuery('input[name="dtpicker[picker_type]"]:checked').val();
					toggle_option_tabs(picker_type);
					jQuery('.radio-group-wrap .radio-label').on('click', function(){
						jQuery(this).find('input[type="radio"]').prop('checked', true);
						picker_type = jQuery(this).find('.picker_type').val();
						toggle_option_tabs(picker_type);
					});

					function toggle_option_tabs(picker_type) {
						jQuery('.picker-box').hide();
						if(picker_type == 'datetimepicker') {
							jQuery('input[name="dtpicker[datepicker]"]').val('on');
							jQuery('input[name="dtpicker[timepicker]"]').val('on');
							jQuery('.picker-box.datePicker').show();
							jQuery('.picker-box.timePicker').show();
						} else if(picker_type == 'datepicker') {
							jQuery('input[name="dtpicker[datepicker]"]').val('on');
							jQuery('input[name="dtpicker[timepicker]"]').val('off');
							jQuery('.picker-box.datePicker').show();
						} else if(picker_type == 'timepicker') {
							jQuery('input[name="dtpicker[timepicker]"]').val('on');
							jQuery('input[name="dtpicker[datepicker]"]').val('off');
							jQuery('.picker-box.timePicker').show();
						}
					}

					$('#copy-field').on('click', function() {
						var copyText = document.getElementById("dtpicker[selector]");
						copyText.select();
						copyText.setSelectionRange(0, 99999);
						document.execCommand("copy");
						jQuery(this).text('<?php _e('Copied', 'date-time-picker-field'); ?>');
					});

					//Initiate Color Picker
					$('.wp-color-picker-field').wpColorPicker();

					// Switches option sections
					//$('.group').hide();
					// $('.preview-image').hide();
					// var activetab = '';
					// if (typeof(localStorage) != 'undefined' ) {
					// 	activetab = localStorage.getItem("activetab");
					// } else {
					// 	activetab = '#dateTimePicker';
					// 	$('#dateTimePicker').show();
					// }

					//if url has section id as hash then set it as active or override the current local storage value
					// if(window.location.hash){
					// 	activetab = window.location.hash;
					// 	if (typeof(localStorage) != 'undefined' ) {
					// 		localStorage.setItem("activetab", activetab);
					// 	}
					// }

					// toggle_tabs(localStorage.activetab);

					// if (activetab != '' && $(activetab).length ) {
					// 	$(activetab).fadeIn();
					// } else {
					// 	$('.group:first').fadeIn();
					// }
					// $('.group .collapsed').each(function(){
					// 	$(this).find('input:checked').parent().parent().parent().nextAll().each(
					// 	function(){
					// 		if ($(this).hasClass('last')) {
					// 			$(this).removeClass('hidden');
					// 			return false;
					// 		}
					// 		$(this).filter('.hidden').removeClass('hidden');
					// 	});
					// });

					// if (activetab != '' && $(activetab + '-tab').length ) {
					// 	$(activetab + '-tab').addClass('nav-tab-active');
					// 	toggle_tabs(activetab);
					// }
					// else {
					// 	$('.nav-tab-wrapper a:first').addClass('nav-tab-active');
					// }
					// $('.nav-tab-wrapper a').click(function(evt) {
					// 	$('.nav-tab-wrapper a').removeClass('nav-tab-active');
					// 	$(this).addClass('nav-tab-active').blur();
					// 	var clicked_group = $(this).attr('href');
					// 	if (typeof(localStorage) != 'undefined' ) {
					// 		localStorage.setItem("activetab", $(this).attr('href'));
					// 	}
					// 	$('.group').hide();
					// 	$(clicked_group).fadeIn();
					// 	$('.preview-image').hide();
					// 	$(clicked_group).show();

					// 	toggle_tabs(clicked_group);
					// 	evt.preventDefault();
					// });

					$('.switch').each(function(i, v) {sliderState(v);});
					$('.switch').on('click', function (event) {
						event.preventDefault();
						sliderCheck(this);
					});

					$('.wpsa-browse').on('click', function (event) {
						event.preventDefault();

						var self = $(this);

						// Create the media frame.
						var file_frame = wp.media.frames.file_frame = wp.media({
							title: self.data('uploader_title'),
							button: {
								text: self.data('uploader_button_text'),
							},
							multiple: false
						});

						file_frame.on('select', function () {
							attachment = file_frame.state().get('selection').first().toJSON();
							self.prev('.wpsa-url').val(attachment.url).change();
						});

						// Finally, open the modal.
						file_frame.open();
					});
				});
			</script>
		<?php
		}

		public function top_bar(){
			return '<div class="plugin-page-header d-flex align-items-center justify-content-between">
				<div class="plugin-page-left">
					<p>Date and Time Picker by Input WP <small>v2.1</small></p>
				</div>
				<div class="plugin-page-right">
					<ul>
						<li>
							<a target="_blank" href="https://www.inputwp.com/documentation" >Documentation</a>
						</li>
						<li>
							<a target="_blank" href="https://wordpress.org/support/plugin/date-time-picker-field/" >Support</a>
						</li>
						<li>
							<a target="_blank" href="https://www.inputwp.com/pro" >Upgrade for $39</a>
						</li>
					</ul>
				</div>
			</div>';
		}

		public function style_fix() {
			global $wp_version;

			if ( version_compare( $wp_version, '3.8', '<=' ) ) : ?>
				<style type="text/css">
					/** WordPress 3.8 Fix **/
					.form-table th { padding: 20px 10px; }
					#wpbody-content .metabox-holder { padding-top: 5px; }
				</style>
			<?php endif; ?>
			<style type="text/css">
				.dtpkr-wrap {width:95%; max-width: 660px; float: none; margin: 50px auto; border-radius: 4px; background: #f1f1f1; border: none;}
				.heading {padding: 30px 20px; font-family: sans-serif; font-size: 16px; font-weight: normal;}
				.red {color: #e74c3c;}
				.right {text-align: right;}
				.dtpkr-wrap h2.nav-tab-wrapper {border: none; margin-top: -15px; padding-bottom: 0; margin-bottom: 0px;}
				.nav-tab, .nav-tab:hover, .nav-tab:focus {background: #fff; border: none;}
				.nav-tab {padding: 12px 1%; margin-left: 0px; width: 23%; text-align: center; font-size: 12px;}
				.nav-tab-active, .nav-tab-active:hover, .nav-tab-active:focus {border-bottom: 4px solid #007cba;}
				.pro-tab {color: #757575; font-size: 12px;}
				.metabox-preview {background: #f1f1f1; max-width: 660px; margin: 0; padding: 80px 0; text-align: center;}
				.metabox-holder, .metabox-holder-form{ border-radius: 4px; -webkit-border-radius: 4px; -moz-border-radius: 4px;}
				.selector-wrap{ border-radius: 0px 0px 4px 4px; -webkit-border-radius: 0px 0px 4px 4px; -moz-border-radius: 0px 0px 4px 4px;}
				.heading-wrap, .metabox-holder, .selector-wrap{border: solid 1px #e2e4e7; background: #fff; }
				.metabox-preview {border-left: solid 1px #e2e4e7; border-right: solid 1px #e2e4e7;}
				.heading-wrap {border-radius: 4px 4px 0px 0px; -webkit-border-radius: 4px 4px 0px 0px; -moz-border-radius: 4px 4px 0px 0px;}
				.pkr-heading, .general-heading, .global-heading {margin: 20px 0px 40px 20px; font-family: sans-serif; font-size: 16px; font-weight: normal;}
				.input-wrap, .select-wrap, .multicheck-wrap, .check-wrap, .submit-wrap {margin: 20px 20px;}
				.description small {font-size: 12px; color: #757575;}
				.input-wrap p.description, .select-wrap p.description{margin-top: 10px;}
				.input-label, .select-label, .multicheck-label, .check-label, .check-wrap {font-size: 14px;}
				.check-label, .checkbox-multi {line-height: 2.25em;}
				input[type=date], input[type=datetime-local], input[type=datetime], input[type=email], input[type=month], input[type=number], input[type=password], input[type=search], input[type=tel], input[type=text], input[type=time], input[type=url], input[type=week], select, fieldset {margin-top: 10px;}
				.regular-text {width: 200px;}
				#copy-field, #copy-field:hover, #copy-field:focus {margin-left: 20px; text-decoration: none;}
				.advertisement-wrap {background: #f1f1f1;}
				.advertisement {background: transparent; max-width: 100%;}
				.switch-container, .selector-container {display: grid; grid-template-columns: 520px 100px;}
				@media only screen and (min-device-width: 320px) and (max-device-width: 480px) {
					.switch-container, .selector-container {display: grid; grid-template-columns: 250px 50px;}
				}
				.switch-desc, .selector-desc {grid-column: 1;}
				.switch-item, .selector-btn {grid-column: 2;}
				.selector-btn {padding-top: 18px;}
				.button-primary {min-width: 90px;}
			</style>
			<?php
		}

	}

endif;
