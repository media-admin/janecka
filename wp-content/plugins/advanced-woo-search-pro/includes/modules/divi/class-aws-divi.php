<?php
/**
 * Divi builder integration
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( 'AWS_Divi' ) ) :

    /**
     * Class
     */
    class AWS_Divi {

        /**
         * Main AWS_Divi Instance
         *
         * Ensures only one instance of AWS_Divi is loaded or can be loaded.
         *
         * @static
         * @return AWS_Divi - Main instance
         */
        protected static $_instance = null;
        
        /**
         * Main AWS_Divi Instance
         *
         * Ensures only one instance of AWS_Divi is loaded or can be loaded.
         *
         * @static
         * @return AWS_Divi - Main instance
         */
        public static function instance() {
            if ( is_null( self::$_instance ) ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        /**
         * Constructor
         */
        public function __construct() {

            add_filter( 'aws_before_strip_shortcodes', array( $this, 'divi_builder_strip_shortcodes' ) );
            add_filter( 'aws_index_do_shortcodes', array( $this, 'divi_builder_index_do_shortcodes' ) );
            add_filter( 'aws_indexed_content', array( $this, 'aws_indexed_content' ), 10, 3 );

        }

        /*
         * Divi builder remove dynamic text shortcodes
         */
        public function divi_builder_strip_shortcodes( $str ) {
            $str = preg_replace( '#\[et_pb_text.[^\]]*?_dynamic_attributes.*?\]@ET-.*?\[\/et_pb_text\]#', '', $str );
            return $str;
        }

        /*
         * Disable shortcodes exucution inside product content when runing Divi visual builder
         */
        public function divi_builder_index_do_shortcodes( $do_shortcodes ) {
            if ( isset( $_POST['action'] ) && $_POST['action'] === 'et_fb_ajax_save' ) {
                return false;
            }
            return $do_shortcodes;
        }

        /*
         * Add to index content from 'long description' field
         */
        public function aws_indexed_content( $content, $post_id, $product ) {

            if ( function_exists('et_pb_is_pagebuilder_used') && defined('ET_BUILDER_WC_PRODUCT_LONG_DESC_META_KEY') && et_pb_is_pagebuilder_used( $post_id ) ) {
                $description = get_post_meta( $post_id, ET_BUILDER_WC_PRODUCT_LONG_DESC_META_KEY, true );
                if ( $description ) {
                    $description = AWS_Helpers::strip_shortcodes( $description );
                    $content .= ' ' . $description;
                }
            }

            return $content;

        }

    }

endif;

AWS_Divi::instance();