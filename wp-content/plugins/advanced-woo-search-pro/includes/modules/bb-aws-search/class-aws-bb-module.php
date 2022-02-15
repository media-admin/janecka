<?php

class AwsSearchModule extends FLBuilderModule {

    public function __construct() {

        parent::__construct(array(
            'name'            => __( 'Advanced Woo Search', 'advanced-woo-search' ),
            'description'     => __( 'WooCommerce search form', 'advanced-woo-search' ),
            'category'        => __( 'WooCommerce', 'fl-builder' ),
            'dir'             => AWS_PRO_DIR . '/includes/modules/bb-aws-search/',
            'url'             => AWS_PRO_URL . 'includes/modules/bb-aws-search/',
            'icon'            => 'search.svg',
            'partial_refresh' => true,
        ));

    }

}

$placeholder = AWS_Helpers::translate( 'search_field_text_1', AWS_PRO()->get_settings( 'search_field_text', 1 ) );
$plugin_options = get_option( 'aws_pro_settings' );

$plugin_forms = array();
foreach ( $plugin_options as $instance_id => $instance_options ) {
    $plugin_forms[$instance_id] = $instance_id;
}


FLBuilder::register_module( 'AwsSearchModule', array(
    'general' => array(
        'title'    => __( 'General', 'advanced-woo-search' ),
        'sections' => array(
            'general' => array(
                'title'  => '',
                'fields' => array(
                    'placeholder' => array(
                        'type'        => 'text',
                        'label'       => __( 'Placeholder', 'advanced-woo-search' ),
                        'default'     => $placeholder,
                        'preview'     => array(
                            'type'     => 'text',
                            'selector' => '.fl-heading-text',
                        ),
                        'connections' => array( 'string' ),
                    ),
                    'form_id' => array(
                        'type'          => 'select',
                        'label'         => __( 'Form ID', 'advanced-woo-search' ),
                        'default'       => '1',
                        'options'       => $plugin_forms,
                    ),
                ),
            ),
        ),
        'description' => sprintf( esc_html__( 'To configure your Advanced Woo Search form please visit %s.', 'advanced-woo-search' ), '<a target="_blank" href="'.esc_url( admin_url('admin.php?page=aws-options') ).'">'.esc_html__( 'plugin settings page', 'advanced-woo-search' ).'</a>'  ),
    ),
) );