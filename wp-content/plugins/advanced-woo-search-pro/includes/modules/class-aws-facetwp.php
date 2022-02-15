<?php

/**
 * FacetWP integration
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!class_exists('AWS_FacetWP')) :

    /**
     * Class for main plugin functions
     */
    class AWS_FacetWP {

        /**
         * @var AWS_FacetWP The single instance of the class
         */
        protected static $_instance = null;

        private $data = array();

        /**
         * Main AWS_FacetWP Instance
         *
         * Ensures only one instance of AWS_FacetWP is loaded or can be loaded.
         *
         * @static
         * @return AWS_FacetWP - Main instance
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

            add_filter( 'facetwp_pre_filtered_post_ids', array( $this, 'facetwp_pre_filtered_post_ids' ), 10, 2 );
            add_filter( 'facetwp_filtered_post_ids', array( $this, 'facetwp_filtered_post_ids' ), 1 );
            add_filter( 'aws_searchpage_enabled', array( $this, 'aws_searchpage_enabled' ), 1 );
            add_filter( 'aws_search_page_custom_data', array( $this, 'aws_search_page_custom_data' ), 1 );
            add_filter( 'posts_pre_query', array( $this, 'posts_pre_query' ), 9999, 2 );

        }

        /*
         * FacetWP add unfiltered products IDs
         */
        public function facetwp_pre_filtered_post_ids( $post_ids, $obj ) {
            if ( class_exists( 'AWS_Search_Page' ) && isset( $_GET['type_aws'] ) && isset( $_GET['s'] ) ) {
                $search_res = AWS_Search_Page::factory()->search( $obj->query, $obj->query_args['posts_per_page'], $obj->query_args['paged'] );
                if ( $search_res ) {
                    $products_ids = array();
                    foreach ( $search_res['all'] as $product ) {
                        $products_ids[] = $product['id'];
                    }
                    $post_ids = $products_ids;
                }
            }
            return $post_ids;
        }

        /*
         * FacetWP check for active filters
         */
        public function facetwp_filtered_post_ids( $post_ids ) {
            if ( isset( $_GET['type_aws'] ) && isset( $_GET['s'] ) && ! empty( $post_ids ) ) {
                $this->data['facetwp'] = true;
                $this->data['filtered_post_ids'] = $post_ids;
            }
            return $post_ids;
        }

        /*
         * Disable AWS search if FacetWP is active
         */
        public function aws_searchpage_enabled( $enabled ) {
            if ( isset( $this->data['facetwp'] ) && $this->data['facetwp'] ) {
                $enabled = false;
            }
            return $enabled;
        }

        /*
         * FacetWP - Update search page query
         */
        public function aws_search_page_custom_data( $data ) {
            if ( isset( $this->data['facetwp'] ) && $this->data['facetwp'] ) {
                $data['force_ids'] = true;
            }
            return $data;
        }

        /*
         * Update posts query
         */
        public function posts_pre_query( $posts, $query ) {
            if ( ( $query->is_main_query() || $query->is_search() ) && isset( $this->data['filtered_post_ids'] ) && ! empty( $this->data['filtered_post_ids'] ) ) {
                $posts = $this->data['filtered_post_ids'];
            }
            return $posts;
        }

    }

endif;

AWS_FacetWP::instance();