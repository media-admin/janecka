<?php

add_action('et_builder_ready', 'aws_divi_register_modules');
function aws_divi_register_modules() {

    if ( class_exists( 'ET_Builder_Module' ) ):

        class Divi_AWS_Module extends ET_Builder_Module {

            public $slug       = 'aws';
            public $vb_support = 'partial';

            public function init() {
                $this->name = esc_html__( 'Advanced Woo Search', 'advanced-woo-search' );
            }

            public function get_fields() {

                $plugin_options = get_option( 'aws_pro_settings' );
                $form_ids = array();
                foreach ( $plugin_options as $instance_id => $instance_options ) {
                    $form_ids[strval($instance_id)] = strval( $instance_id );
                }

                wp_enqueue_style(
                    'aws-divi',
                    AWS_PRO_URL . 'includes/modules/divi/divi.css', array(), AWS_PRO_VERSION
                );

                return array(
                    'placeholder'     => array(
                        'label'           => esc_html__( 'Placeholder', 'advanced-woo-search' ),
                        'type'            => 'text',
                        'option_category' => 'basic_option',
                        'description'     => esc_html__( 'Add placeholder text or leave empty to use default.', 'advanced-woo-search' ),
                        'toggle_slug'     => 'main_content',
                    ),
                    'form_id' => array(
                        'label'             => esc_html__( 'Form ID:', 'advanced-woo-search' ),
                        'type'              => 'select',
                        'option_category'   => 'basic_option',
                        'options'           => $form_ids,
                        'default'           => '1',
                        'toggle_slug'       => 'main_content',
                        'description'       => esc_html__( 'Choose form id', 'advanced-woo-search' ),
                        'mobile_options'    => true,
                    ),
                );
            }

            public function render( $unprocessed_props, $content = null, $render_slug = null ) {
                if ( function_exists( 'aws_get_search_form' ) ) {
                    $form_id = ( isset( $this->props['form_id'] ) && $this->props['form_id'] ) ? $this->props['form_id'] : 1;
                    $search_form = aws_get_search_form( false, array( 'id' => $form_id ) );
                    if ( $this->props['placeholder'] ) {
                        $search_form = preg_replace( '/placeholder="([\S\s]*?)"/i', 'placeholder="' . $this->props['placeholder'] . '"', $search_form );
                    }
                    return $search_form;
                }
                return '';
            }

        }

        new Divi_AWS_Module;

    endif;

}