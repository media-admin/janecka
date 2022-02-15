<?php

/**
 * AWS plugin integration for WPML
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!class_exists('AWS_WPML')) :

    /**
     * Class for main plugin functions
     */
    class AWS_WPML {

        /**
         * @var AWS_WPML The single instance of the class
         */
        protected static $_instance = null;

        private $data = array();

        /**
         * Main AWS_WPML Instance
         *
         * Ensures only one instance of AWS_WPML is loaded or can be loaded.
         *
         * @static
         * @return AWS_WPML - Main instance
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

            add_filter( 'aws_indexed_data', array( $this, 'indexed_data_trans_fallback' ), 1, 2 );

        }

        /*
         * Use translation if available or fallback to default language ( if enabled )
         */
        public function indexed_data_trans_fallback( $data, $id ) {

            global $sitepress;

            $product_sync = false;
            $default_lang = '';
            $all_languages = array();
            $translations = array();

            if ( function_exists( 'wpml_get_setting' ) ) {
                $custom_posts_sync = wpml_get_setting('custom_posts_sync_option', false );
                if ( $custom_posts_sync ) {
                    $product_sync = isset( $custom_posts_sync['product'] ) && $custom_posts_sync['product'] == 2;
                }
            }

            if ( has_filter( 'wpml_default_language' ) ) {
                $default_lang = apply_filters('wpml_default_language', NULL );
            }

            if ( has_filter( 'wpml_post_language_details' ) ) {
                $current_lang_details = apply_filters( 'wpml_post_language_details', NULL, $id );
            }

            if ( has_filter( 'wpml_active_languages' ) ) {
                $all_languages_a = apply_filters( 'wpml_active_languages', NULL );
                if ( ! empty( $all_languages_a ) ) {
                    foreach ( $all_languages_a as $lang_item ) {
                        $lang_item_code = $lang_item['language_code'];
                        $all_languages[$lang_item_code] = $lang_item_code;
                    }
                }
            }

            if ( ! empty( $all_languages ) && $data['lang'] === $default_lang && $sitepress && method_exists( $sitepress, 'get_element_trid' ) && method_exists( $sitepress, 'get_element_translations' ) ) {

                if ( $product_sync ) {

                    $trid = $sitepress->get_element_trid( $id, 'post_product' );
                    if ( $trid ) {
                        $translations = $sitepress->get_element_translations( $trid ) ;
                    }

                    foreach( $all_languages as $lang_code ) {
                        if ( ! empty( $translations ) && isset( $translations[$lang_code] ) ) {
                            continue;
                        }
                        $data['lang'] .= ' ' . $lang_code;
                    }

                }

            }

            return $data;

        }

    }

endif;

AWS_WPML::instance();