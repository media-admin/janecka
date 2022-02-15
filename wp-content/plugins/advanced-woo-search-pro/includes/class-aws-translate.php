<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'AWS_Translate' ) ) :

    /**
     * Class for WPML strings translations
     */
    class AWS_Translate {

        /**
         * Return a singleton instance of the current class
         *
         * @return object
         */
        public static function factory() {
            static $instance = false;

            if ( ! $instance ) {
                $instance = new self();
                $instance->setup();
            }

            return $instance;
        }

        /**
         * Constructor
         */
        public function __construct() {}

        /**
         * Setup actions and filters for all things settings
         */
        public function setup() {

            add_action( 'aws_settings_saved', array( $this, 'settings_saved' ) );
            add_action( 'aws_form_changed', array( $this, 'form_changed' ), 10, 3 );
            add_action( 'aws_filters_changed', array( $this, 'filters_changed' ), 10, 4 );

        }

       /*
        * Register the WPML translations
        */
        public function settings_saved( $params = false ) {

            // No WPML
            if ( ! function_exists( 'icl_register_string' ) ) {
                return;
            }

            $this->register_wpml_translations( $params );

        }

       /*
        * Register the WPML translations
        */
        public function form_changed( $params, $type, $instance_id ) {

            // No WPML
            if ( ! function_exists( 'icl_register_string' ) ) {
                return;
            }

            if ( $type === 'copy_form' || $type === 'add_form' ) {
                $this->register_wpml_translations( $params );
            }

            if ( $type === 'delete_form' ) {
                $this->unregister_wpml_translations( $params, $instance_id );
            }

        }

        /*
        * Register the WPML translations
        */
        public function filters_changed( $params, $type, $instance_id, $filter ) {

            // No WPML
            if ( ! function_exists( 'icl_register_string' ) ) {
                return;
            }

            if ( $type === 'add_filter' || $type === 'copy_filter' ) {
                $this->register_wpml_translations( $params );
            }

            if ( $type === 'delete_filter' ) {
                $this->unregister_wpml_translations( $params, $instance_id, $filter );
            }

        }

        /*
         * Register the WPML translations
         */
        private function register_wpml_translations( $params ) {

            // These options are registered
            $options_to_reg = array(
                "search_field_text" => "Search",
                "not_found_text"    => "Nothing found",
                "show_more_text"    => "View all results",
            );

            if ( ! $params ) {
                $params = $options_to_reg;
            }

            foreach( $params as $search_instance_num => $search_instance_settings ) {

                foreach ( $options_to_reg as $key => $option ) {
                    icl_register_string( 'aws', $key . '_' . $search_instance_num, $search_instance_settings[$key] );
                }

                if ( isset( $search_instance_settings['filters'] ) ) {
                    foreach( $search_instance_settings['filters'] as $filter_num => $filter_settings ) {
                        if ( isset( $filter_settings['filter_name'] ) ) {
                            icl_register_string( 'aws', 'filter_name' . '_' . $search_instance_num . '_' . $filter_num, $filter_settings['filter_name'] );
                        }
                    }
                }

            }

        }

        /*
         * Unregister the WPML translations
         */
        private function unregister_wpml_translations( $params = false, $instance_id = false, $filter = false ) {

            if ( ! function_exists( 'icl_unregister_string' ) ) {
                return;
            }

            // These options are registered
            $options_to_reg = array(
                "search_field_text" => "Search",
                "not_found_text"    => "Nothing found",
                "show_more_text"    => "View all results",
            );

            if ( $instance_id && ! $filter ) {

                foreach ( $options_to_reg as $key => $option ) {
                    icl_unregister_string( 'aws', $key . '_' . $instance_id );
                }

                for ($i = 1; $i <= 10; $i++) {
                    icl_unregister_string( 'aws', 'filter_name' . '_' . $instance_id . '_' . $i );
                }

            }

            if ( $instance_id && $filter ) {
                icl_unregister_string( 'aws', 'filter_name' . '_' . $instance_id . '_' . $filter );
            }

        }

    }

endif;

AWS_Translate::factory();