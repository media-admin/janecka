<?php

/**
 * AWS plugin gutenberg integrations init
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!class_exists('AWS_Gutenberg_Init')) :

    /**
     * Class for main plugin functions
     */
    class AWS_Gutenberg_Init {

        /**
         * @var AWS_Gutenberg_Init The single instance of the class
         */
        protected static $_instance = null;

        /**
         * Main AWS_Gutenberg_Init Instance
         *
         * Ensures only one instance of AWS_Gutenberg_Init is loaded or can be loaded.
         *
         * @static
         * @return AWS_Gutenberg_Init - Main instance
         */
        public static function instance()
        {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        /**
         * Constructor
         */
        public function __construct() {

            add_action( 'init', array( $this, 'register_block' ) );

            if ( version_compare( get_bloginfo('version'),'5.8', '>=' ) ) {
                add_filter( 'block_categories_all', array( $this, 'add_block_category' ) );
            } else {
                add_filter( 'block_categories', array( $this, 'add_block_category' ) );
            }

        }

        /*
         * Register gutenberg blocks
         */
        public function register_block() {

            global $pagenow;

            $scripts = array( 'wp-blocks', 'wp-editor' );
            if ( $pagenow && $pagenow === 'widgets.php' && version_compare( get_bloginfo('version'),'5.8', '>=' ) ) {
                $scripts = array( 'wp-blocks', 'wp-edit-widgets' );
            }

            $form_ids = $this->get_form_ids();

            wp_register_script(
                'aws-gutenberg-search-block',
                AWS_PRO_URL . 'includes/modules/gutenberg/aws-gutenberg-search-block.js',
                $scripts,
                AWS_PRO_VERSION
            );

            wp_register_style(
                'aws-gutenberg-styles-editor',
                AWS_PRO_URL . 'assets/css/common.css',
                array( 'wp-edit-blocks' ),
                AWS_PRO_VERSION
            );

            register_block_type( 'advanced-woo-search/search-block', array(
                'apiVersion' => 2,
                'editor_script' => 'aws-gutenberg-search-block',
                'editor_style' => 'aws-gutenberg-styles-editor',
                'render_callback' => array( $this, 'search_block_dynamic_render_callback' ),
                'attributes'      =>  array(
                    'placeholder'   =>  array(
                        'type'    => 'string',
                        'default' => AWS_Helpers::translate( 'search_field_text_1', AWS_PRO()->get_settings( 'search_field_text', 1 ) ),
                    ),
                    'form_ids'   =>  array(
                        'type'    => 'array',
                        'default' => $form_ids,
                    ),
                    'form_id_val'   =>  array(
                        'type'    => 'string',
                        'default' => $form_ids[0]['value'],
                    ),
                ),
            ) );

        }

        /*
         * Render dynamic content
         */
        public function search_block_dynamic_render_callback( $block_attributes, $content ) {

            $placeholder = $block_attributes['placeholder'];
            $form_id = $block_attributes['form_id_val'];
            $search_form = aws_get_search_form( false, array( 'id' => $form_id ) );

            if ( $placeholder ) {
                $search_form = preg_replace( '/placeholder="([\S\s]*?)"/i', 'placeholder="' . esc_attr( $placeholder ) . '"', $search_form );

            }

            return $search_form;

        }

        /*
         * Add new blocks category
         */
        public function add_block_category( $categories ) {
            return array_merge(
                $categories,
                array(
                    array(
                        'slug'  => 'aws',
                        'title' => 'Advanced Woo Search',
                        'icon'  => 'search',
                    ),
                )
            );
        }

        /*
         * Ger available form IDs
         */
        private function get_form_ids() {
            $plugin_options = get_option( 'aws_pro_settings' );
            $form_ids = array();
            foreach ( $plugin_options as $instance_id => $instance_options ) {
                $form_ids[] = array(
                    'label' => $instance_id,
                    'value' => $instance_id
                );
            }
            return $form_ids;
        }

    }

endif;

AWS_Gutenberg_Init::instance();