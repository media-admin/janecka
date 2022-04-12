<?php
/**
 * WCFM - WooCommerce Multivendor Marketplace plugin support
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( 'AWS_WCFM' ) ) :

    /**
     * Class
     */
    class AWS_WCFM {
        
        /**
         * Main AWS_WCFM Instance
         *
         * Ensures only one instance of AWS_WCFM is loaded or can be loaded.
         *
         * @static
         * @return AWS_WCFM - Main instance
         */
        protected static $_instance = null;

        /**
         * Main AWS_WCFM Instance
         *
         * Ensures only one instance of AWS_WCFM is loaded or can be loaded.
         *
         * @static
         * @return AWS_WCFM - Main instance
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
            add_filter( 'aws_admin_filter_rules', array( $this, 'label_rules' ), 1 );
            add_filter( 'aws_filters_condition_rules', array( $this, 'condition_rules' ), 1 );
            add_filter( 'aws_excerpt_search_result', array( $this, 'wcfm_excerpt_search_result' ), 1, 3 );
            add_filter( 'aws_search_users_results', array( $this, 'wcfm_search_users_results' ), 1, 3 );
            add_filter( 'aws_searchbox_markup', array( $this, 'wcfm_searchbox_markup' ), 1, 2 );
            add_filter( 'aws_front_data_parameters', array( $this, 'wcfm_front_data_parameters' ), 1 );
            add_filter( 'aws_search_query_array', array( $this, 'wcfm_search_query_array' ), 1 );
            add_filter( 'aws_terms_search_query', array( $this, 'wcfm_terms_search_query' ), 1, 2 );
            add_filter( 'aws_search_tax_results', array( $this, 'wcfm_search_tax_results' ), 1 );
        }

        /*
         * Add new label conditions for admin
         */
        public function label_rules( $options ) {

            $options['product'][] = array(
                "name" => __( "WCFM: Is product sold by any vendor", "advanced-woo-search" ),
                "id"   => "product_wcfm_is_sold_by_vendor",
                "type" => "bool",
                "operators" => "equals",
            );

            $options['product'][] = array(
                "name" => __( "WCFM: Product sold by", "advanced-woo-search" ),
                "id"   => "product_wcfm_sold_by",
                "type" => "callback",
                "operators" => "equals",
                "choices" => array(
                    'callback' => array($this, 'get_all_vendors'),
                    'params'   => array()
                ),
            );

            $options['product'][] = array(
                "name" => __( "WCFM: Store rating", "advanced-woo-search" ),
                "id"   => "product_wcfm_store_rating",
                "type" => "number",
                "step" => "0.01",
                "operators" => "equals_compare",
            );

            $options['product'][] = array(
                "name" => __( "WCFM: Store reviews count", "advanced-woo-search" ),
                "id"   => "product_wcfm_store_reviews",
                "type" => "number",
                "operators" => "equals_compare",
            );

            $options['product'][] = array(
                "name" => __( "WCFM: Product views", "advanced-woo-search" ),
                "id"   => "product_wcfm_views",
                "type" => "number",
                "operators" => "equals_compare",
            );

            $options['user'][] = array(
                "name" => __( "WCFM: User is vendor", "advanced-woo-search" ),
                "id"   => "user_wcfm_is_vendor",
                "type" => "bool",
                "operators" => "equals",
            );

            $options['user'][] = array(
                "name" => __( "WCFM: Store rating", "advanced-woo-search" ),
                "id"   => "user_wcfm_store_rating",
                "type" => "number",
                "step" => "0.01",
                "operators" => "equals_compare",
            );

            $options['user'][] = array(
                "name" => __( "WCFM: Store reviews count", "advanced-woo-search" ),
                "id"   => "user_wcfm_store_reviews",
                "type" => "number",
                "operators" => "equals_compare",
            );

            return $options;

        }

        /*
         * Add custom condition rule method
         */
        public function condition_rules( $rules ) {
            $rules['product_wcfm_sold_by'] = array( $this, 'wcfm_sold_by' );
            $rules['product_wcfm_is_sold_by_vendor'] = array( $this, 'wcfm_is_sold_by_vendor' );
            $rules['product_wcfm_store_rating'] = array( $this, 'wcfm_product_store_rating' );
            $rules['product_wcfm_store_reviews'] = array( $this, 'wcfm_product_store_reviews' );
            $rules['product_wcfm_views'] = array( $this, 'wcfm_product_views' );
            $rules['user_wcfm_is_vendor'] = array( $this, 'wcfm_is_vendor' );
            $rules['user_wcfm_store_rating'] = array( $this, 'wcfm_store_rating' );
            $rules['user_wcfm_store_reviews'] = array( $this, 'wcfm_store_reviews' );
            return $rules;
        }

        /*
         * Condition: Is product sold by vendor
         */
        public function wcfm_sold_by( $condition_rule ) {
            global $wpdb;

            $value = $condition_rule['value'];

            $relation = $condition_rule['operator'] === 'equal' ? 'IN' : 'NOT IN';

            $string = "( id {$relation} (
                   SELECT $wpdb->posts.ID
                   FROM $wpdb->posts
                   WHERE $wpdb->posts.post_author = {$value}
                ))";

            return $string;

        }

        /*
         * Condition: Is product sold by any available vendor
         */
        public function wcfm_is_sold_by_vendor( $condition_rule ) {
            global $wpdb;

            $relation = $condition_rule['operator'] === 'equal' ? 'IN' : 'NOT IN';
            $value_relation = $condition_rule['value'] === 'true' ? 'IN' : 'NOT IN';

            $vendors = array( 0 );
            $vendors_list = $this->get_all_vendors();
            if ( $vendors_list ) {
                $vendors = array_keys( $vendors_list );
            }

            $vendors_string = implode( ',', $vendors );

            $string = "( id {$relation} (
                   SELECT $wpdb->posts.ID
                   FROM $wpdb->posts
                   WHERE $wpdb->posts.post_author {$value_relation} ({$vendors_string})
                ))";

            return $string;

        }

        /*
         * Condition: Store rating for products
         */
        public function wcfm_product_store_rating( $condition_rule ) {
            global $wpdb;

            switch ( $condition_rule['operator'] ) {
                case 'equal':
                    $operator = '=';
                    break;
                case 'not_equal':
                    $operator = '!=';
                    break;
                case 'greater':
                    $operator = '>=';
                    break;
                default:
                    $operator = '<=';
            }

            $rating = intval( $condition_rule['value'] );

            $string = "( id IN (
                   SELECT $wpdb->posts.ID
                   FROM $wpdb->posts
                   WHERE $wpdb->posts.post_author IN (
                    SELECT distinct user_id
                    FROM {$wpdb->usermeta}
                    WHERE $wpdb->usermeta.meta_key = '_wcfmmp_avg_review_rating' AND $wpdb->usermeta.meta_value {$operator} {$rating} 
                   )
                ))";

            return $string;

        }

        /*
         * Condition: Store reviews count for products
         */
        public function wcfm_product_store_reviews( $condition_rule ) {
            global $wpdb;

            switch ( $condition_rule['operator'] ) {
                case 'equal':
                    $operator = '=';
                    break;
                case 'not_equal':
                    $operator = '!=';
                    break;
                case 'greater':
                    $operator = '>=';
                    break;
                default:
                    $operator = '<=';
            }

            $reviews = intval( $condition_rule['value'] );

            $string = "( id IN (
                   SELECT $wpdb->posts.ID
                   FROM $wpdb->posts
                   WHERE $wpdb->posts.post_author IN (
                    SELECT distinct user_id
                    FROM {$wpdb->usermeta}
                    WHERE $wpdb->usermeta.meta_key = '_wcfmmp_total_review_count' AND $wpdb->usermeta.meta_value {$operator} {$reviews} 
                   )
                ))";

            return $string;

        }

        /*
         * Condition: Product views count
         */
        public function wcfm_product_views( $condition_rule ) {
            global $wpdb;

            switch ( $condition_rule['operator'] ) {
                case 'equal':
                    $operator = '=';
                    break;
                case 'not_equal':
                    $operator = '!=';
                    break;
                case 'greater':
                    $operator = '>=';
                    break;
                default:
                    $operator = '<=';
            }

            $views = intval( $condition_rule['value'] );

            $string = "( id IN (
                   SELECT $wpdb->postmeta.post_id
                   FROM $wpdb->postmeta
                   WHERE $wpdb->postmeta.meta_key = '_wcfm_product_views' AND $wpdb->postmeta.meta_value {$operator} {$views} 
                ))";

            return $string;

        }

        /*
         * Condition: Is WCFM vendor
         */
        public function wcfm_is_vendor( $condition_rule ) {
            global $wpdb;

            $relation = $condition_rule['value'] === 'true' ? 'IN' : 'NOT IN';
            if ( $condition_rule['operator'] !== 'equal' ) {
                $relation = $relation === 'IN' ? 'NOT IN' : 'IN';
            }

            $vendors = array( 0 );
            $vendors_list = $this->get_all_vendors();
            if ( $vendors_list ) {
                $vendors = array_keys( $vendors_list );
            }

            $vendors_string = implode( ',', $vendors );

            $string = "( ID {$relation} ( {$vendors_string} ) )";

            return $string;

        }

        /*
         * Condition: Store rating
         */
        public function wcfm_store_rating( $condition_rule ) {
            global $wpdb;

            switch ( $condition_rule['operator'] ) {
                case 'equal':
                    $operator = '=';
                    break;
                case 'not_equal':
                    $operator = '!=';
                    break;
                case 'greater':
                    $operator = '>=';
                    break;
                default:
                    $operator = '<=';
            }

            $rating = intval( $condition_rule['value'] );

            $string = "( ID IN (
                SELECT user_id
                FROM {$wpdb->usermeta}
                WHERE $wpdb->usermeta.meta_key = '_wcfmmp_avg_review_rating' AND $wpdb->usermeta.meta_value {$operator} {$rating} 
            ) )";

            return $string;

        }

        /*
         * Condition:Store reviews count
         */
        public function wcfm_store_reviews( $condition_rule ) {
            global $wpdb;

            switch ( $condition_rule['operator'] ) {
                case 'equal':
                    $operator = '=';
                    break;
                case 'not_equal':
                    $operator = '!=';
                    break;
                case 'greater':
                    $operator = '>=';
                    break;
                default:
                    $operator = '<=';
            }

            $reviews = intval( $condition_rule['value'] );

            $string = "( ID IN (
                SELECT user_id
                FROM {$wpdb->usermeta}
                WHERE $wpdb->usermeta.meta_key = '_wcfmmp_total_review_count' AND $wpdb->usermeta.meta_value {$operator} {$reviews} 
            ) )";

            return $string;

        }

        /*
         * Condition callback: get all available vendors
         */
        public function get_all_vendors() {

            global $WCFMmp;

            $options = $WCFMmp ? $WCFMmp->wcfmmp_vendor->wcfmmp_search_vendor_list( true ) : array();

            return $options;

        }

        /*
         * Add store name and logo inside search results
         */
        function wcfm_excerpt_search_result( $excerpt, $post_id, $product ) {

            if ( function_exists( 'wcfm_get_vendor_id_by_post' ) ) {

                $vendor_id = wcfm_get_vendor_id_by_post( $post_id );

                if ( $vendor_id ) {
                    if ( apply_filters( 'wcfmmp_is_allow_sold_by', true, $vendor_id ) && wcfm_vendor_has_capability( $vendor_id, 'sold_by' ) ) {

                        global $WCFM, $WCFMmp;

                        $is_store_offline = get_user_meta( $vendor_id, '_wcfm_store_offline', true );

                        if ( ! $is_store_offline ) {

                            $store_name = wcfm_get_vendor_store_name( absint( $vendor_id ) );
                            $store_url = function_exists('wcfmmp_get_store_url') && $vendor_id ? wcfmmp_get_store_url( $vendor_id ) : '';

                            $logo = '';

                            if ( apply_filters( 'wcfmmp_is_allow_sold_by_logo', true ) ) {
                                $store_logo = wcfm_get_vendor_store_logo_by_vendor( $vendor_id );
                                if ( ! $store_logo ) {
                                    $store_logo = apply_filters( 'wcfmmp_store_default_logo', $WCFM->plugin_url . 'assets/images/wcfmmp-blue.png' );
                                }
                                $logo = '<img style="margin-right:4px;" width="24px" src="' . $store_logo . '" />';
                            }

                            $excerpt .= '<br><a style="margin-top:4px;display:block;" href="' . $store_url . '">' . $logo . $store_name . '</a>';

                        }

                    }
                }

            }

            return $excerpt;

        }

        /*
         * Update users results
         */
        public function wcfm_search_users_results( $result_array, $roles, $search_string ) {

            if ( array_search( 'dc_vendor', $roles ) !== false || array_search( 'wcfm_vendor', $roles ) !== false  ) {
                foreach( $result_array as $user_id => $user_params ) {
                    $user_meta = get_userdata( $user_id );
                    $user_roles = $user_meta->roles;
                    if ( in_array( 'dc_vendor', $user_roles ) || in_array( 'wcfm_vendor', $user_roles ) ) {
                        global $wpdb, $blog_id;
                        $wp_user_avatar_id = get_user_meta( $user_id, $wpdb->get_blog_prefix($blog_id).'user_avatar', true );
                        $wp_user_avatar = function_exists('wcfm_get_vendor_store_logo_by_vendor') ? wcfm_get_vendor_store_logo_by_vendor( $user_id ) : '';
                        $result_array[$user_id][0]['link'] = function_exists('wcfmmp_get_store_url') ? wcfmmp_get_store_url( $user_id ) : '/store/' . $user_meta->data->user_nicename;
                        if ( $wp_user_avatar ) {
                            $result_array[$user_id][0]['image'] = $wp_user_avatar;
                        } else {
                            $store = function_exists( 'wcfmmp_get_store' ) ? wcfmmp_get_store( $user_id ) : false;
                            if ( $store ) {
                                $result_array[$user_id][0]['image'] = $store->get_avatar();
                            }
                        }
                        $rating = do_shortcode('[wcfm_store_info id=' . $user_id . ' data="store_rating"]');
                        $rating = apply_filters( 'aws_wcfm_users_rating', $rating );
                        if ( $rating ) {
                            $rating = '
                            <style>.wcfmmp-store-rating {
                                overflow: hidden;
                                position: relative;
                                height: 1.618em;
                                line-height: 1.618;
                                font-size: 1em;
                                width: 6em!important;
                                font-family: \'Font Awesome 5 Free\'!important;
                                font-weight: 900;
                            }.wcfmmp-store-rating::before {
                                content:"" "" "" "" "";
                                opacity: 0.25;
                                float: left;
                                top: 0px;
                                left: 0px;
                                position: absolute;
                                color: rgb(173, 181, 182);
                            }.wcfmmp-store-rating span {
                                overflow: hidden;
                                float: left;
                                top: 0;
                                left: 0;
                                position: absolute;
                                padding-top: 1.5em;
                            }.wcfmmp-store-rating span::before {
                                content: "" "" "" "" "";
                                top: 0px;
                                position: absolute;
                                left: 0px;
                                color: rgb(255, 145, 44);
                            }
                            </style>' . $rating;
                            $result_array[$user_id][0]['excerpt'] .= $rating;
                        }
                    }
                }
            }

            return $result_array;

        }

        /*
        * WCFM - WooCommerce Multivendor Marketplace update search page url for vendors shops
        */
        public function wcfm_searchbox_markup( $markup, $params ) {

            $store = $this->get_current_store();

            if ( $store ) {
                $markup = preg_replace( '/action="(.+?)"/i', 'action="' . $store->get_shop_url() . '"', $markup );
            }

            return $markup;

        }

        /*
         * WCFM - WooCommerce Multivendor Marketplace limit search inside vendors shop
         */
        public function wcfm_front_data_parameters( $params ) {

            $store = $this->get_current_store();

            if ( $store ) {
                $params['data-tax'] = 'store:' . $store->get_id();
            }

            return $params;

        }

        /*
         * WCFM - WooCommerce Multivendor Marketplace limit search inside vendoes shop
         */
        public function wcfm_search_query_array( $query ) {

            $vendor_id = false;

            if ( isset( $_REQUEST['aws_tax'] ) && $_REQUEST['aws_tax'] && strpos( $_REQUEST['aws_tax'], 'store:' ) !== false ) {
                $vendor_id = intval( str_replace( 'store:', '', $_REQUEST['aws_tax'] ) );
            } else {
                $store = $this->get_current_store();
                if ( $store ) {
                    $vendor_id = $store->get_id();
                }
            }

            if ( $vendor_id ) {

                $store_products = get_posts( array(
                    'posts_per_page'      => -1,
                    'fields'              => 'ids',
                    'post_type'           => 'product',
                    'post_status'         => 'publish',
                    'ignore_sticky_posts' => true,
                    'suppress_filters'    => true,
                    'no_found_rows'       => 1,
                    'orderby'             => 'ID',
                    'order'               => 'DESC',
                    'lang'                => '',
                    'author'              => $vendor_id
                ) );

                if ( $store_products ) {
                    $query['search'] .= " AND ( id IN ( " . implode( ',', $store_products ) . " ) )";
                }

            }

            return $query;

        }

        /*
         * WCFM - WooCommerce Multivendor Marketplace limit search inside vendoes shop for taxonomies
         */
        public function wcfm_terms_search_query( $sql, $taxonomy ) {

            global $wpdb;

            $store = false;

            if ( isset( $_REQUEST['aws_tax'] ) && $_REQUEST['aws_tax'] && strpos( $_REQUEST['aws_tax'], 'store:' ) !== false ) {
                $vendor_id = intval( str_replace( 'store:', '', $_REQUEST['aws_tax'] ) );
                $store = function_exists( 'wcfmmp_get_store' ) ? wcfmmp_get_store( $vendor_id ) : false;
            } else {
                $store = $this->get_current_store();
            }

            if ( $store ) {
                $all_vendor_tax = array();
                foreach ( $taxonomy as $taxonomy_slug ) {
                    $vendor_tax = $store->get_store_taxonomies( $taxonomy_slug );
                    if ( ! empty( $vendor_tax) ) {
                        $all_vendor_tax = array_merge( $all_vendor_tax, $vendor_tax );
                    }
                }

                if ( ! empty( $all_vendor_tax ) ) {
                    $sql_terms = "AND $wpdb->term_taxonomy.term_id IN ( " . implode( ',', $all_vendor_tax ) . " )";
                    $sql = str_replace( 'WHERE 1 = 1', 'WHERE 1 = 1 ' . $sql_terms, $sql );
                } else {
                    $sql = '';
                }

            }

            return $sql;

        }

        /*
         * WCFM - Update links for taxonomies inside vendors store
         */
        public function wcfm_search_tax_results( $result_array ) {

            $store = false;
            if ( isset( $_REQUEST['aws_tax'] ) && $_REQUEST['aws_tax'] && strpos( $_REQUEST['aws_tax'], 'store:' ) !== false ) {
                $vendor_id = intval( str_replace( 'store:', '', $_REQUEST['aws_tax'] ) );
                $store = function_exists( 'wcfmmp_get_store' ) ? wcfmmp_get_store( $vendor_id ) : false;
            } else {
                $store = $this->get_current_store();
            }

            if ( $store && $result_array ) {
                foreach ( $result_array as $tax_name => $items ) {
                    $url_base = ( $tax_name === 'product_cat' ) ? 'category' : 'tax-' . $tax_name;
                    foreach ( $items as $item_key => $item ) {
                        $result_array[$tax_name][$item_key]['link'] = $store->get_shop_url() . $url_base . '/' . $item['slug'];
                        $result_array[$tax_name][$item_key]['count'] = '';
                    }
                }
            }

            return $result_array;

        }

        /*
         * Get current store object
         */
        private function get_current_store() {

            $store = false;

            if ( function_exists('wcfmmp_is_store_page') && function_exists('wcfm_get_option') && wcfmmp_is_store_page() ) {

                $wcfm_store_url  = wcfm_get_option( 'wcfm_store_url', 'store' );
                $wcfm_store_name = apply_filters( 'wcfmmp_store_query_var', get_query_var( $wcfm_store_url ) );

                if ( $wcfm_store_name ) {
                    $seller_info = get_user_by( 'slug', $wcfm_store_name );
                    if ( $seller_info && function_exists( 'wcfmmp_get_store' ) ) {
                        $store_user = wcfmmp_get_store( $seller_info->ID );
                        if ( $store_user ) {
                            $store = $store_user;
                        }
                    }
                }

            }

            return $store;

        }

    }

endif;