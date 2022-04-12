<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'AWS_Admin_Fields' ) ) :

    /**
     * Class for plugin admin ajax hooks
     */
    class AWS_Admin_Fields {

        /**
         * @var AWS_Admin_Fields Options section name
         */
        private $section_name;

        /**
         * @var AWS_Admin_Fields The array of options that is need to be generated
         */
        private $options_array;

        /**
         * @var AWS_Admin_Fields Current plugin instance options
         */
        private $plugin_options;

        /*
         * Constructor
         */
        public function __construct( $tab_name, $plugin_options ) {

            $this->section_name = isset( $_GET['section'] ) ? sanitize_text_field( $_GET['section'] ) : 'none';

            $options = AWS_Admin_Options::options_array( $tab_name, $this->section_name );

            $this->options_array = $options[$tab_name];
            $this->plugin_options = $plugin_options;

            $this->generate_fields();

        }

        /*
         * Generate options fields
         */
        private function generate_fields() {

            if ( empty( $this->options_array ) ) {
                return;
            }

            $plugin_options = $this->plugin_options;

            echo '<table class="form-table">';
            echo '<tbody>';

            foreach ( $this->options_array as $k => $value ) {

                if ( isset( $value['depends'] ) && ! $value['depends'] ) {
                    continue;
                }

                if ( $this->section_name !== 'none' ) {
                    echo '<a class="button aws-back-to-filters" href="' . esc_url( AWS_Helpers::get_settings_instance_page_url() ) . '" title="' . esc_attr__( 'Back', 'advanced-woo-search' ) . '">' . esc_html__( 'Back', 'advanced-woo-search' ) . '</a>';
                }

                switch ( $value['type'] ) {

                    case 'text': ?>
                        <tr valign="top">
                            <th scope="row"><?php echo esc_html( $value['name'] ); ?></th>
                            <td>
                                <input type="text" name="<?php echo esc_attr( $value['id'] ); ?>" class="regular-text" value="<?php echo esc_attr( stripslashes( $plugin_options[ $value['id'] ] ) ); ?>">
                                <br><span class="description"><?php echo wp_kses_post( $value['desc'] ); ?></span>
                            </td>
                        </tr>
                        <?php break;

                    case 'image': ?>

                        <tr valign="top">
                            <th scope="row"><?php echo esc_html( $value['name'] ); ?></th>
                            <td>
                                <img class="image-preview" src="<?php echo esc_url( stripslashes( $plugin_options[ $value['id'] ] ) ); ?>"  />
                                <input type="hidden" size="40" name="<?php echo esc_attr( $value['id'] ); ?>" class="image-hidden-input" value="<?php echo esc_attr( stripslashes( $plugin_options[ $value['id'] ] ) ); ?>" />
                                <input class="button image-upload-btn" type="button" value="Upload Image" data-size="<?php echo esc_attr( $value['size'] ); ?>" />
                                <input class="button image-remove-btn" type="button" value="Remove Image" />
                            </td>
                        </tr>

                        <?php

                        break;

                    case 'number': ?>

                        <?php
                        $params = '';
                        $params .= isset( $value['step'] ) ? ' step="' . $value['step'] . '"' : '';
                        $params .= isset( $value['min'] ) ? ' min="' . $value['min'] . '"' : '';
                        $params .= isset( $value['max'] ) ? ' max="' . $value['max'] . '"' : '';
                        ?>


                        <tr valign="top">
                            <th scope="row"><?php echo esc_html( $value['name'] ); ?></th>
                            <td>
                                <input type="number" <?php echo $params; ?> name="<?php echo esc_attr( $value['id'] ); ?>" class="regular-text" value="<?php echo esc_attr( stripslashes( $plugin_options[ $value['id'] ] ) ); ?>">
                                <br><span class="description"><?php echo wp_kses_post( $value['desc'] ); ?></span>
                            </td>
                        </tr>
                        <?php break;

                    case 'textarea': ?>
                        <tr valign="top">
                            <th scope="row"><?php echo esc_html( $value['name'] ); ?></th>
                            <td>
                                <?php $textarea_cols = isset( $value['cols'] ) ? $value['cols'] : "55"; ?>
                                <?php $textarea_rows = isset( $value['rows'] ) ? $value['rows'] : "3"; ?>
                                <?php $textarea_output = isset( $value['allow_tags'] ) ? wp_kses( $plugin_options[ $value['id'] ], AWS_Admin_Helpers::get_kses( $value['allow_tags'] ) ) : esc_html( stripslashes( $plugin_options[ $value['id'] ] ) ); ?>
                                <textarea id="<?php echo esc_attr( $value['id'] ); ?>" name="<?php echo esc_attr( $value['id'] ); ?>" cols="<?php echo $textarea_cols; ?>" rows="<?php echo $textarea_rows; ?>"><?php print $textarea_output; ?></textarea>
                                <br><span class="description"><?php echo wp_kses_post( $value['desc'] ); ?></span>
                            </td>
                        </tr>
                        <?php break;

                    case 'checkbox': ?>
                        <tr valign="top">
                            <th scope="row"><?php echo esc_html( $value['name'] ); ?></th>
                            <td>
                                <?php $checkbox_options = $plugin_options[ $value['id'] ]; ?>
                                <?php foreach ( $value['choices'] as $val => $label ) { ?>
                                    <input type="checkbox" name="<?php echo esc_attr( $value['id'] . '[' . $val . ']' ); ?>" id="<?php echo esc_attr( $value['id'] . '_' . $val ); ?>" value="1" <?php checked( $checkbox_options[$val], '1' ); ?>> <label for="<?php echo esc_attr( $value['id'] . '_' . $val ); ?>"><?php echo esc_html( $label ); ?></label><br>
                                <?php } ?>
                                <?php if ( isset( $value['desc'] ) && $value['desc'] ): ?>
                                    <br><span class="description"><?php echo wp_kses_post( $value['desc'] ); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php break;

                    case 'radio': ?>
                        <tr valign="top">
                            <th scope="row"><?php echo esc_html( $value['name'] ); ?></th>
                            <td>
                                <?php foreach ( $value['choices'] as $val => $label ) { ?>
                                    <?php $option_val = isset( $plugin_options[ $value['id'] ] ) ? $plugin_options[ $value['id'] ] : ''; ?>
                                    <input class="radio" type="radio" name="<?php echo esc_attr( $value['id'] ); ?>" id="<?php echo esc_attr( $value['id'].$val ); ?>" value="<?php echo esc_attr( $val ); ?>" <?php checked( $option_val, $val ); ?>> <label for="<?php echo esc_attr( $value['id'].$val ); ?>"><?php echo $label; ?></label><br>
                                <?php } ?>
                                <br><span class="description"><?php echo wp_kses_post( $value['desc'] ); ?></span>
                            </td>
                        </tr>
                        <?php break;

                    case 'radio-image': ?>
                        <tr valign="top">
                            <th scope="row"><?php echo esc_html( $value['name'] ); ?></th>
                            <td>
                                <ul class="img-select">
                                    <?php foreach ( $value['choices'] as $val => $img ) { ?>
                                        <li class="option">
                                            <input class="radio" type="radio" name="<?php echo esc_attr( $value['id'] ); ?>" id="<?php echo esc_attr( $value['id'].$val ); ?>" value="<?php echo esc_attr( $val ); ?>" <?php checked( $plugin_options[ $value['id'] ], $val ); ?>>
                                            <span class="ico" style="background: url('<?php echo esc_url( AWS_PRO_URL . 'assets/img/' . $img ); ?>') no-repeat 50% 50%;"></span>
                                        </li>
                                    <?php } ?>
                                </ul>
                                <br><span class="description"><?php echo wp_kses_post( $value['desc'] ); ?></span>
                            </td>
                        </tr>
                        <?php break;

                    case 'select': ?>
                        <tr valign="top">
                            <th scope="row"><?php echo esc_html( $value['name'] ); ?></th>
                            <td>
                                <select name="<?php echo $value['id']; ?>">
                                    <?php foreach ( $value['choices'] as $val => $label ) { ?>
                                        <?php $option_val = isset( $plugin_options[ $value['id'] ] ) ? $plugin_options[ $value['id'] ] : ''; ?>
                                        <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $option_val, $val ); ?>><?php echo esc_html( $label ); ?></option>
                                    <?php } ?>
                                </select>
                                <br><span class="description"><?php echo wp_kses_post( $value['desc'] ); ?></span>
                            </td>
                        </tr>
                        <?php break;

                    case 'sortable': ?>
                        <tr valign="top">
                            <th scope="row"><?php echo esc_html( $value['name'] ); ?></th>
                            <td>

                                <script>
                                    jQuery(document).ready(function() {

                                        jQuery( "#<?php echo esc_attr( $value['id'] ); ?>1, #<?php echo esc_attr( $value['id'] ); ?>2" ).sortable({
                                            connectWith: ".connectedSortable",
                                            placeholder: "highlight",
                                            update: function(event, ui){
                                                var serviceList = '';
                                                jQuery("#<?php echo esc_attr( $value['id'] ); ?>2 li").each(function(){

                                                    serviceList = serviceList + ',' + jQuery(this).attr('id');

                                                });
                                                var serviceListOut = serviceList.substring(1);
                                                jQuery('#<?php echo esc_attr( $value['id'] ); ?>').attr('value', serviceListOut);
                                            }
                                        }).disableSelection();

                                    })
                                </script>

                                <span class="description"><?php echo wp_kses_post( $value['desc'] ); ?></span><br><br>

                                <?php
                                $all_buttons = $value['choices'];
                                $active_buttons = explode( ',', $plugin_options[ $value['id'] ] );
                                $active_buttons_array = array();

                                if ( count( $active_buttons ) > 0 ) {
                                    foreach ($active_buttons as $button) {
                                        $active_buttons_array[$button] = $all_buttons[$button];
                                    }
                                }

                                $inactive_buttons = array_diff($all_buttons, $active_buttons_array);
                                ?>

                                <div class="sortable-container">

                                    <div class="sortable-title">
                                        <?php esc_html_e( 'Active sources', 'advanced-woo-search' ) ?><br>
                                        <?php esc_html_e( 'Change order by drag&drop', 'advanced-woo-search' ) ?>
                                    </div>

                                    <ul id="<?php echo esc_attr( $value['id'] ); ?>2" class="sti-sortable enabled connectedSortable">
                                        <?php
                                        if ( count( $active_buttons_array ) > 0 ) {
                                            foreach ($active_buttons_array as $button_value => $button) {
                                                if ( ! $button ) continue;
                                                echo '<li id="' . esc_attr( $button_value ) . '" class="sti-btn sti-' . esc_attr( $button_value ) . '-btn">' . $button . '</li>';
                                            }
                                        }
                                        ?>
                                    </ul>

                                </div>

                                <div class="sortable-container">

                                    <div class="sortable-title">
                                        <?php esc_html_e( 'Deactivated sources', 'advanced-woo-search' ) ?><br>
                                        <?php esc_html_e( 'Excluded from search results', 'advanced-woo-search' ) ?>
                                    </div>

                                    <ul id="<?php echo esc_attr( $value['id'] ); ?>1" class="sti-sortable disabled connectedSortable">
                                        <?php
                                        if ( count( $inactive_buttons ) > 0 ) {
                                            foreach ($inactive_buttons as $button_value => $button) {
                                                echo '<li id="' . esc_attr( $button_value ) . '" class="sti-btn sti-' . esc_attr( $button_value ) . '-btn">' . $button . '</li>';
                                            }
                                        }
                                        ?>
                                    </ul>

                                </div>

                                <input type="hidden" id="<?php echo esc_attr( $value['id'] ); ?>" name="<?php echo esc_attr( $value['id'] ); ?>" value="<?php echo esc_attr( $plugin_options[ $value['id'] ] ); ?>" />

                            </td>
                        </tr>
                        <?php break;

                    case 'table': ?>

                        <?php
                        $table_head = isset( $value['table_head'] ) && $value['table_head'] ? $value['table_head'] : __( 'Search Source', 'advanced-woo-search' );
                        ?>

                        <tr valign="top">

                            <th scope="row"><?php echo esc_html( $value['name'] ); ?></th>

                            <td>

                                <span class="description">
                                    <?php echo wp_kses_post( $value['desc'] ); ?>
                                    <?php echo AWS_Admin_Helpers::meta_fields_pagination(); ?>
                                </span><br><br>

                                <table class="aws-table aws-table-sources widefat" cellspacing="0">

                                    <thead>
                                        <tr>
                                            <th class="aws-name"><?php echo esc_html( $table_head ); ?></th>
                                            <th class="aws-actions"></th>
                                            <th class="aws-active"></th>
                                        </tr>
                                    </thead>

                                    <tbody>

                                        <?php $table_options = isset( $plugin_options[ $value['id'] ] ) ? $plugin_options[ $value['id'] ] : array(); ?>

                                        <?php if ( is_array( $table_options ) ) { ?>

                                            <?php foreach ( $value['choices'] as $val => $fchoices ) { ?>

                                                <?php
                                                $active_class = isset( $table_options[$val] ) && $table_options[$val] ? 'active' : '';
                                                $label = is_array( $fchoices ) ? $fchoices['label'] : $fchoices;
                                                if ( ! $active_class && ! isset( $table_options[$val] ) && $value['id'] === 'index_sources' ) {
                                                    $active_class = 'active';
                                                }
                                                if ( strpos( $label, 'index disabled' ) !== false ) {
                                                    $active_class = 'disabled';
                                                }
                                                $setting = is_array( $fchoices ) ? $fchoices['option'] : false;
                                                ?>

                                                <tr>
                                                    <td class="aws-name"><?php echo $label; ?></td>
                                                    <td class="aws-actions">
                                                        <?php if ( $setting ): ?>
                                                            <a class="button alignright tips edit" title="<?php echo esc_attr__( 'Edit', 'advanced-woo-search' ); ?>" href="<?php echo esc_url( AWS_Helpers::get_settings_instance_page_url('&section=' . $val) ); ?>"><?php echo esc_attr__( 'Edit', 'advanced-woo-search' ); ?></a>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="aws-active <?php echo $active_class; ?>">
                                                        <span data-change-state="1" data-setting="<?php echo esc_attr( $value['id'] ); ?>" data-name="<?php echo esc_attr( $val ); ?>" class="aws-yes" title="<?php echo esc_attr__( 'Disable this source', 'advanced-woo-search' ); ?>"><?php echo esc_html__( 'Yes', 'advanced-woo-search' ); ?></span>
                                                        <span data-change-state="0" data-setting="<?php echo esc_attr( $value['id'] ); ?>" data-name="<?php echo esc_attr( $val ); ?>" class="aws-no" title="<?php echo esc_attr__( 'Enable this source', 'advanced-woo-search' ); ?>"><?php echo esc_html__( 'No', 'advanced-woo-search' ); ?></span>
                                                        <span style="display: none;" class="aws-disabled" title="<?php echo esc_attr__( 'Source index disabled', 'advanced-woo-search' ); ?>"><?php echo esc_html__( 'No', 'advanced-woo-search' ); ?></span>
                                                    </td>
                                                </tr>
                                            <?php } ?>

                                        <?php } ?>

                                    </tbody>

                                </table>

                            </td>

                        </tr>

                        <?php break;

                    case 'filter_rules': ?>

                        <?php
                        $filter_section = isset( $value['filter'] ) ? $value['filter'] : 'product';
                        ?>

                        <tr valign="top" data-option-id="<?php echo esc_attr( $value['id'] ); ?>">
                            <th scope="row"><?php echo esc_html( $value['name'] ); ?></th>
                            <td>

                                <span class="description"><?php echo wp_kses_post( $value['desc'] ); ?></span>

                                <?php
                                $filters = isset( $plugin_options[ $value['id'] ] ) ? $plugin_options[ $value['id'] ] : false;
                                $rules = AWS_Admin_Options::include_filters();
                                $default_rule = new AWS_Admin_Filters( $rules[$filter_section][0], $filter_section );
                                $rules_container_class = $filters && ! empty($filters) && isset( $filters[$filter_section] ) && ! empty( $filters[$filter_section] ) ? '' : ' aws-rules-empty';

                                $html = '';

                                $html .= '<div class="aws-rules' . $rules_container_class . '">';

                                $html .= '<script id="awsRulesTemplate" type="text/html">';

                                $html .= $default_rule->get_rule();

                                $html .= '</script>';

                                if ( $filters && ! empty( $filters ) && isset( $filters[$filter_section] ) ) {

                                    foreach( $filters[$filter_section] as $group_id => $group_rules ) {

                                        $group_id = is_string( $group_id ) ? str_replace( 'group_', '', $group_id ) : $group_id;

                                        $html .= '<table class="aws-rules-table" data-aws-group="' . esc_attr( $group_id ) . '">';
                                            $html .= '<tbody>';

                                            foreach( $group_rules as $rule_id => $rule_values ) {

                                                $rule_id = is_string( $rule_id ) ? str_replace( 'rule_', '', $rule_id ) : $rule_id;

                                                if ( isset( $rule_values['param'] ) ) {
                                                    $current_rule = new AWS_Admin_Filters( AWS_Admin_Filters_Helpers::include_filter_rule_by_id( $rule_values['param'] ), $filter_section, $group_id, $rule_id, $rule_values );
                                                    $html .= $current_rule->get_rule();
                                                }

                                            }

                                            $html .= '</tbody>';
                                        $html .= '</table>';

                                    }

                                }

                                $html .= '<a href="#" class="button add-first-filter" data-aws-add-first-filter>' . esc_html( $value['button'] ) . '</a>';

                                $html .= '<a href="#" class="button add-rule-group" data-aws-add-group>' . __( "Add 'or' group", "advanced-woo-search" ) . '</a>';

                                $html .= '</div>';

                                echo $html;

                                ?>

                            </td>
                        </tr>

                        <?php break;

                    case 'heading':

                        $heading_tag = isset( $value['heading_type'] ) && $value['heading_type'] === 'text' ? 'span' : 'h3';
                        $heading_description = isset( $value['desc'] ) ? $value['desc'] : '';
                        ?>

                        <?php $id = isset( $value['id'] ) && $value['id'] ? 'id="' . $value['id'] . '"' : ''; ?>
                        <tr valign="top">

                            <th scope="row"><<?php echo $heading_tag; ?> <?php echo $id; ?> class="aws-heading"><?php echo esc_html( $value['name'] ); ?></<?php echo $heading_tag; ?>></th>

                            <?php if ( $heading_description ): ?>
                                <td>
                                    <span class="description"><?php echo $heading_description; ?></span>
                                </td>
                            <?php endif; ?>

                        </tr>
                        <?php break;

                    case 'html':
                        $custom_html = isset( $value['html'] ) ? $value['html'] : '';
                        $description = isset( $value['desc'] ) ? $value['desc'] : '';
                        ?>
                        <tr valign="top">
                            <th scope="row"><?php echo esc_html( $value['name'] ); ?></th>
                            <td>
                                <?php echo $value['html']; ?>
                                <?php if ( $description ): ?>
                                    <span class="description"><?php echo $description; ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <?php break;

                }

            }

            echo '</tbody>';
            echo '</table>';

            if ( $this->section_name === 'none' ) {
                echo '<p class="submit"><input name="Submit" type="submit" class="button-primary" value="' . esc_attr__( 'Save Changes', 'advanced-woo-search' ) . '" /></p>';
            }

        }



    }

endif;
