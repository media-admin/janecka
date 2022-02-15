<?php
/**
 * ACF plugin support
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( 'AWS_ACF' ) ) :

    /**
     * Class
     */
    class AWS_ACF {

        private $data = array();

        private $acf_supported_types = array( 'user', 'post_object', 'taxonomy', 'relationship' );

        private $acf_fields_allowed = array();

        private $custom_fields = array();

        /**
         * Main AWS_ACF Instance
         *
         * Ensures only one instance of AWS_ACF is loaded or can be loaded.
         *
         * @static
         * @return AWS_ACF - Main instance
         */
        protected static $_instance = null;

        /**
         * Main AWS_ACF Instance
         *
         * Ensures only one instance of AWS_ACF is loaded or can be loaded.
         *
         * @static
         * @return AWS_ACF - Main instance
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
            add_filter( 'aws_meta_keys_unfiltered', array( $this, 'aws_meta_keys_unfiltered' ) );
            add_filter( 'aws_meta_keys', array( $this, 'aws_meta_keys' ) );
            add_filter( 'aws_indexed_custom_fields', array( $this, 'aws_indexed_custom_fields' ), 10, 3 );
        }

        /*
         * Add description for ACF fields in admin page
         */
        public function aws_meta_keys_unfiltered( $meta_keys ) {

            $this->data['acf_fields'] = array();

            if ( isset( $_GET['page'] ) && $_GET['page'] == 'aws-options' ) {

                if ( is_array( $meta_keys ) && ! empty( $meta_keys ) ) {
                    foreach ( $meta_keys as $field ) {
                        if ( strpos(  $field->meta_value, 'field_' ) === 0 && strpos( $field->meta_key, '_') === 0 ) {
                            $this->data['acf_fields'][ $field->meta_value] = ltrim( $field->meta_key, '_' );
                        }
                    }
                }

            }

            return $meta_keys;

        }

        /*
         * Add description for ACF fields in admin page
         */
        public function aws_meta_keys( $meta_keys ) {

            if ( isset( $_GET['page'] ) && $_GET['page'] == 'aws-options' && isset( $this->data['acf_fields'] ) && ! empty( $this->data['acf_fields'] ) ) {

                if ( is_array( $meta_keys ) && ! empty( $meta_keys ) ) {

                    $acf_fields = $this->get_available_acf_fields();

                    foreach ( $meta_keys as $meta_slug => $meta_name ) {
                        if ( $index = array_search( $meta_name, $this->data['acf_fields'] ) ) {
                            $field_title = isset( $acf_fields[$index] ) ? $acf_fields[$index]['title'] : false;
                            if ( $field_title ) {
                                $meta_keys[$meta_slug] = $field_title . ' ( ' . $meta_name . ' ) ( ACF )';
                            } else {
                                $meta_keys[$meta_slug] = $meta_name . ' ( ACF )';
                            }
                        }
                    }

                }

            }

            return $meta_keys;

        }

        /*
         * Extract data from ACF advanced fields
         */
        public function aws_indexed_custom_fields( $custom_fields, $id, $product ) {

            if ( is_array( $custom_fields ) && ! empty( $custom_fields ) ) {

                $acf_fields = $this->get_available_acf_fields();

                foreach ( $custom_fields as $custom_field_key => $custom_field_val ) {
                    if ( strpos( $custom_field_val[0], 'field_' ) === 0 && strpos( $custom_field_key, '_') === 0 ) {
                        $field_key = $custom_field_val[0];
                        $field_name = ltrim( $custom_field_key, '_' );
                        if ( isset( $acf_fields[$field_key] ) && in_array( $acf_fields[$field_key]['data']['type'], $this->acf_supported_types )  ) {
                            $this->acf_fields_allowed[$field_name] = $acf_fields[$field_key]['data']['type'];
                        }
                    }
                }

                $this->custom_fields = $custom_fields;

                return $this->get_fields();

            }

            return $custom_fields;

        }

        /*
         * Get ACF fields data in exists
         */
        private function get_fields() {

            foreach ( $this->custom_fields as $custom_field_key => $custom_field_val ) {

                if ( isset( $this->acf_fields_allowed[$custom_field_key] ) ) {

                    $fiels_new_val = '';

                    $field_vals = maybe_unserialize( $custom_field_val[0] );
                    if ( ! is_array( $field_vals ) && $field_vals ) {
                        $field_vals = array( $field_vals );
                    }

                    if ( $field_vals && is_array( $field_vals ) && ! empty( $field_vals ) ) {

                        switch( $this->acf_fields_allowed[$custom_field_key] ) {

                            case 'relationship';
                                $fiels_new_val .= $this->get_relationship_field( $field_vals );
                                break;

                            case 'taxonomy';
                                $fiels_new_val .= $this->get_taxonomy_field( $field_vals );
                                break;

                            case 'user';
                                $fiels_new_val .= $this->get_user_field( $field_vals );
                                break;

                            case 'post_object';
                                $fiels_new_val .= $this->get_post_object_field( $field_vals );
                                break;

                        }

                        $this->custom_fields[$custom_field_key] = array( $fiels_new_val );

                    }

                }

            }

            return $this->custom_fields;

        }

        /*
         * Relationship field type
         */
        private function get_relationship_field( $field_vals ) {

            $fiels_new_val = '';

            $posts = get_posts( array(
                'posts_per_page'      => -1,
                'post_type'           => 'any',
                'post_status'         => 'any',
                'ignore_sticky_posts' => true,
                'suppress_filters'    => true,
                'no_found_rows'       => 1,
                'lang'                => '',
                'include'             => $field_vals,
            ) );

            if ( $posts ) {
                foreach( $posts as $post ) {
                    $fiels_new_val .= $post->ID . ' ' . $post->post_title . ' ';
                }
            }

            return $fiels_new_val;

        }

        /*
         * Taxonomy field type
         */
        private function get_taxonomy_field( $taxes ) {

            $fiels_new_val = '';

            foreach( $taxes as $tax_id ) {
                $term = get_term_by('term_taxonomy_id', $tax_id );
                if ( ! is_wp_error( $term ) && $term ) {
                    $fiels_new_val .= $tax_id . ' ' . $term->name . ' ' . $term->description . ' ';
                }
            }

            return $fiels_new_val;

        }

        /*
         * User field type
         */
        private function get_user_field( $users ) {

            $fiels_new_val = '';

            foreach( $users as $user_id ) {
                $user_meta = get_userdata( $user_id );
                if ( $user_meta && is_object( $user_meta ) ) {
                    $fiels_new_val .= $user_id . ' ' . $user_meta->data->user_nicename . ' ' . $user_meta->data->display_name . ' ';
                }
            }

            return $fiels_new_val;

        }

        /*
         * Post object field type
         */
        private function get_post_object_field( $posts ) {

            $fiels_new_val = '';

            foreach( $posts as $post_id ) {
                $post_title = get_the_title( $post_id );
                if ( $post_title ) {
                    $fiels_new_val .= $post_id . ' ' . $post_title . ' ';
                }
            }

            return $fiels_new_val;

        }

        /**
         * Get available ACF plugin fields
         * @param array $args Array of query arguments
         * @return array $fields
         */
        private function get_available_acf_fields( $args = array() ) {

            if ( isset( $this->data['acf_all_fields'] ) ) {
                return $this->data['acf_all_fields'];
            }

            $fields = array();

            $defaults = array(
                'posts_per_page' => -1,
                'post_type' => 'acf-field',
                'orderby' => 'menu_order',
                'order' => 'ASC',
                'suppress_filters' => true,
                'cache_results' => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
                'post_parent' => false,
                'post_status' => array( 'publish' ),
            );

            $r = wp_parse_args($args, $defaults);

            $posts = get_posts($r);

            if ( $posts && is_array( $posts ) ) {
                foreach ( $posts as $post ) {
                    $fields[$post->post_name] = array(
                        'type' => $post->post_excerpt,
                        'title' => $post->post_title,
                        'data' => maybe_unserialize( $post->post_content )
                    );
                }
            }

            $this->data['acf_all_fields'] = $fields;

            return $fields;

        }

    }

endif;