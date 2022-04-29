<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'AWS_Search' ) ) :

/**
 * Class for plugin search action
 */
class AWS_Search {

    /**
     * @var AWS_Search Array of all plugin data $data
     */
    private $data = array();

    /**
     * @var AWS_Search ID of current form instance $form_id
     */
    private $form_id = 0;

    /**
     * @var AWS_Search ID of current filter $filter_id
     */
    private $filter_id = 0;

    /**
     * @var AWS_Search Current language $lang
     */
    private $lang = 0;

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

        $this->data['settings'] = get_option( 'aws_pro_settings' );

        if ( isset( $_REQUEST['wc-ajax'] ) ) {
            add_action( 'wc_ajax_aws_action', array( $this, 'action_callback' ) );
        } else {
            add_action( 'wp_ajax_aws_action', array( $this, 'action_callback' ) );
            add_action( 'wp_ajax_nopriv_aws_action', array( $this, 'action_callback' ) );
        }

    }

    /*
     * AJAX call action callback
     */
    public function action_callback() {

        if ( ! defined( 'DOING_AJAX' ) ) {
            define( 'DOING_AJAX', true );
        }

        if ( ! headers_sent() && isset( $_REQUEST['typedata'] ) ) {
            header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        }

        ob_start();

        $search_results = $this->search();

        ob_end_clean();

        echo json_encode( $search_results );

        die;

    }

    /*
     * AJAX call action callback
     */
    public function search( $keyword = '' ) {

        global $wpdb;

        $this->form_id   = isset( $_REQUEST['aws_id'] ) ? sanitize_text_field( $_REQUEST['aws_id'] ) : ( isset( $_REQUEST['id'] ) ? sanitize_text_field( $_REQUEST['id'] ) : 1 );
        $this->filter_id = isset( $_REQUEST['aws_filter'] ) ? sanitize_text_field( $_REQUEST['aws_filter'] ) : ( isset( $_REQUEST['filter'] ) ? sanitize_text_field( $_REQUEST['filter'] ) : 1 );
        $this->lang      = isset( $_REQUEST['lang'] ) ? sanitize_text_field( $_REQUEST['lang'] ) : '';

        if ( $this->lang ) {
            do_action( 'wpml_switch_language', $this->lang );
        }

        $cache = AWS_PRO()->get_common_settings( 'cache' );

        $s = $keyword ? esc_attr( $keyword ) : esc_attr( $_REQUEST['keyword'] );
        $s = htmlspecialchars_decode( $s );

        $this->data['s_nonormalize'] = $s;

        $s = AWS_Helpers::normalize_string( $s );


        /**
         * Fires each time when performing the search
         * @since 1.50
         * @param string $s Search query
         * @param integer $this->form_id Search form id
         * @param integer $this->filter_id Search form filter id ( @since 2.11 )
         */
        do_action( 'aws_search_start', $s, $this->form_id, $this->filter_id );


        $cache_option_name = '';
            
        if ( $cache === 'true' && ! $keyword ) {
            $cache_option_name = AWS_PRO()->cache->get_cache_name( $s, $this->form_id, $this->filter_id );
            $res = AWS_PRO()->cache->get_from_cache_table( $cache_option_name );
            if ( $res ) {
                $cached_value = json_decode( $res );
                if ( $cached_value && ! empty( $cached_value ) ) {
                    return $cached_value;
                }
            }
        }

        $products_array = array();
        $custom_tax_array = array();
        $users_array = array();

        $search_logic          = AWS_PRO()->get_settings( 'search_logic', $this->form_id );
        $search_exact          = AWS_PRO()->get_settings( 'search_exact', $this->form_id );
        $style                 = AWS_PRO()->get_settings( 'style', $this->form_id,  $this->filter_id );
        $product_stock_status  = AWS_PRO()->get_settings( 'product_stock_status', $this->form_id,  $this->filter_id );
        $var_rules             = AWS_PRO()->get_settings( 'var_rules', $this->form_id,  $this->filter_id );
        $onsale                = AWS_PRO()->get_settings( 'on_sale', $this->form_id,  $this->filter_id );
        $product_visibility    = AWS_PRO()->get_settings( 'product_visibility', $this->form_id,  $this->filter_id );
        $results_num           = $keyword ? apply_filters( 'aws_page_results', 100 ) : AWS_PRO()->get_settings( 'results_num', $this->form_id,  $this->filter_id );
        $exclude_rel           = AWS_PRO()->get_settings( 'exclude_rel', $this->form_id,  $this->filter_id );
        $exclude_cats          = AWS_PRO()->get_settings( 'exclude_cats', $this->form_id,  $this->filter_id );
        $exclude_tags          = AWS_PRO()->get_settings( 'exclude_tags', $this->form_id,  $this->filter_id );
        $exclude_products      = AWS_PRO()->get_settings( 'exclude_products', $this->form_id,  $this->filter_id );
        $adv_filters           = AWS_PRO()->get_settings( 'adv_filters', $this->form_id,  $this->filter_id );
        $search_archives       = AWS_PRO()->get_settings( 'search_archives', $this->form_id,  $this->filter_id );
        $search_archives_tax   = AWS_PRO()->get_settings( 'search_archives_tax', $this->form_id,  $this->filter_id );
        $search_archives_attr  = AWS_PRO()->get_settings( 'search_archives_attr', $this->form_id,  $this->filter_id );
        $search_archives_users = AWS_PRO()->get_settings( 'search_archives_users', $this->form_id,  $this->filter_id );

        $search_rule           = AWS_PRO()->get_common_settings( 'search_rule' );


        $this->data['s']                  = $s;
        $this->data['search_terms']       = array();
        $this->data['search_in']          = $this->set_search_in();
        $this->data['results_num']        = $results_num ? $results_num : 10;
        $this->data['search_logic']       = $search_logic ? $search_logic : 'or';
        $this->data['search_exact']       = $search_exact ? $search_exact : 'false';
        $this->data['exclude_rel']        = $exclude_rel;
        $this->data['exclude_cats']       = $exclude_cats;
        $this->data['exclude_tags']       = $exclude_tags;
        $this->data['exclude_products']   = $exclude_products;
        $this->data['adv_filters']        = $adv_filters;
        $this->data['product_stock_status'] = $product_stock_status;
        $this->data['var_rules']          = $var_rules;
        $this->data['onsale']             = $onsale;
        $this->data['product_visibility'] = $product_visibility;
        $this->data['search_rule']        = $search_rule;
        $this->data['form_id']            = $this->form_id;
        $this->data['filter_id']          = $this->filter_id;

        $search_array = array_unique( explode( ' ', $s ) );
        $search_array = AWS_Helpers::filter_stopwords( $search_array );

        $tax_to_display = AWS_Helpers::get_tax_to_display( $search_archives, $search_archives_tax, $search_archives_attr );


        if ( is_array( $search_array ) && ! empty( $search_array ) ) {
            foreach ( $search_array as $search_term ) {
                $search_term = trim( $search_term );
                if ( $search_term ) {
                    $this->data['search_terms'][] = $search_term;
                }
            }
        }

//        if ( empty( $this->data['search_terms'] ) ) {
//            $this->data['search_terms'][] = '';
//        }

        /**
         * Filter search data parameters
         * @since 2.50
         * @param array $this->data Array of data parameters
         */
        $this->data = apply_filters( 'aws_search_data_parameters', $this->data );

        if ( ! empty( $this->data['search_terms'] ) ) {

            if ( ! empty( $this->data['search_in'] ) ) {

                $posts_ids = $this->query_index_table();

                /**
                 * Filters array of products ids
                 *
                 * @since 1.42
                 *
                 * @param array $posts_ids Array of products ids
                 * @param string $s Search query
                 */
                $posts_ids = apply_filters( 'aws_search_results_products_ids', $posts_ids, $s );

                $products_array = $this->get_products( $posts_ids );

                /**
                 * Filters array of products before they displayed in search results
                 *
                 * @since 1.31
                 *
                 * @param array $products_array Array of products results
                 * @param string $s Search query
                 */
                $products_array = apply_filters( 'aws_search_results_products', $products_array, $s );

            }

            /**
             * Filters array of custom taxonomies that must be displayed in search results
             *
             * @since 1.54
             *
             * @param array $taxonomies_archives Array of custom taxonomies
             * @param string $s Search query
             */
            $taxonomies_archives = apply_filters( 'aws_search_results_tax_archives', $tax_to_display, $s );

            if ( $taxonomies_archives && is_array( $taxonomies_archives ) && ! empty( $taxonomies_archives ) ) {

                $tax_search = new AWS_Tax_Search( $taxonomies_archives, $this->data );
                $custom_tax_array = $tax_search->get_results();

            }

            if ( $search_archives && isset( $search_archives['archive_users'] ) && $search_archives['archive_users'] ) {

                $users_search = new AWS_Users_Search( $search_archives_users, $this->data );
                $users_array = $users_search->get_results();

            }

        }

        $result_array = array(
            'tax'      => $custom_tax_array,
            'users'    => $users_array,
            'products' => $products_array,
            'style'    => $style,
        );

        /**
         * Filters array of all results data before they displayed in search results
         *
         * @since 1.32
         *
         * @param array $brands_array Array of products data
         * @param string $s Search query
         */
        $result_array = apply_filters( 'aws_search_results_all', $result_array, $s );

        if ( $cache === 'true' && ! $keyword ) {
            AWS_PRO()->cache->insert_into_cache_table( $cache_option_name, $result_array );
        }

        return $result_array;

    }

    /*
     * Query in index table
     */
    private function query_index_table() {

        global $wpdb;

        $table_name = $wpdb->prefix . AWS_INDEX_TABLE_NAME;

        $search_logic       = $this->data['search_logic'];
        $search_exact       = $this->data['search_exact'];
        $search_in_arr      = $this->data['search_in'];
        $results_num        = $this->data['results_num'];
        $exclude_rel        = $this->data['exclude_rel'];
        $exclude_cats       = $this->data['exclude_cats'];
        $exclude_tags       = $this->data['exclude_tags'];
        $exclude_products   = $this->data['exclude_products'];
        $adv_filters        = $this->data['adv_filters'];
        $product_stock_status = $this->data['product_stock_status'];
        $var_rules          = $this->data['var_rules'];
        $onsale             = $this->data['onsale'];
        $product_visibility = $this->data['product_visibility'];
        $search_rule        = $this->data['search_rule'];

        $reindex_version = get_option( 'aws_pro_reindex_version' );

        $query = array();

        $query['select']           = '';
        $query['search']           = '';
        $query['relevance']        = '';
        $query['exclude_terms']    = '';
        $query['exclude_products'] = '';
        $query['adv_filters']      = '';
        $query['stock']            = '';
        $query['sale']             = '';
        $query['visibility']       = '';
        $query['lang']             = '';
        $query['type']             = '';
        $query['having']           = '';

        $search_array = array();
        $search_terms = array();
        $relevance_array = array();
        $new_relevance_array = array();


        /**
         * Filters array of search terms before generating SQL query
         *
         * @since 1.38
         *
         * @param array $this->data['search_terms'] Array of search terms
         */
        $this->data['search_terms'] = apply_filters( 'aws_search_terms', $this->data['search_terms'] );

        $relevance_scores = AWS_Helpers::get_relevance_scores( $this->data );

        foreach ( $this->data['search_terms'] as $search_term ) {

            $search_term_len = strlen( $search_term );

            $relevance_title        = $relevance_scores['title'] + 20 * $search_term_len;
            $relevance_title_like   = $relevance_scores['title'] / 5 + 2 * $search_term_len;

            $relevance_content      = $relevance_scores['content'] + 4 * $search_term_len;
            $relevance_content_like = $relevance_scores['content'] + 1 * $search_term_len;

            $relevance_id = $relevance_scores['id'];
            $relevance_id_like = $relevance_scores['id'] / 10;

            $relevance_sku = $relevance_scores['sku'];
            $relevance_sku_like = $relevance_scores['sku'] / 5;

            $relevance_other = $relevance_scores['other'];
            $relevance_other_like = $relevance_scores['other'] / 5;

            $search_term_norm = AWS_Plurals::singularize( $search_term );

            if ( $search_term_norm && $search_term_len > 3 && strlen( $search_term_norm ) > 2 ) {
                $search_term = $search_term_norm;
            }

            if ( $search_rule === 'begins' ) {
                $like = $wpdb->esc_like( $search_term ) . '%';
            } else {
                $like = '%' . $wpdb->esc_like( $search_term ) . '%';
            }

            $search_terms[] = $search_term;

            if ( $search_term_len > 1 || ! $search_term_len ) {

                if ( $search_exact === 'true' ) {
                    $search_array[] = $wpdb->prepare( '( term = "%s" )', $search_term );
                } else {
                    $search_array[] = $wpdb->prepare( '( term LIKE %s )', $like );
                }

            } else {
                $search_array[] = $wpdb->prepare( '( term = "%s" )', $search_term );
            }


            $addition_relevance_sources = array();

            foreach ( $search_in_arr as $search_in_term ) {

                switch ( $search_in_term ) {

                    case 'title':
                        $relevance_array['title'][] = $wpdb->prepare( "( case when ( term_source = 'title' AND term = '%s' ) then {$relevance_title} * count else 0 end )", $search_term );
                        $relevance_array['title'][] = $wpdb->prepare( "( case when ( term_source = 'title' AND term LIKE %s ) then {$relevance_title_like} * count else 0 end )", $like );
                        break;

                    case 'content':
                        $relevance_array['content'][] = $wpdb->prepare( "( case when ( term_source = 'content' AND term = '%s' ) then {$relevance_content} * count else 0 end )", $search_term );
                        $relevance_array['content'][] = $wpdb->prepare( "( case when ( term_source = 'content' AND term LIKE %s ) then {$relevance_content_like} * count else 0 end )", $like );
                        break;

                    case 'excerpt':
                        $relevance_array['excerpt'][] = $wpdb->prepare( "( case when ( term_source = 'excerpt' AND term = '%s' ) then {$relevance_content} * count else 0 end )", $search_term );
                        $relevance_array['excerpt'][] = $wpdb->prepare( "( case when ( term_source = 'excerpt' AND term LIKE %s ) then {$relevance_content_like} * count else 0 end )", $like );
                        break;

                    case 'sku':
                        $relevance_array['sku'][] = $wpdb->prepare( "( case when ( term_source = 'sku' AND term = '%s' ) then {$relevance_sku} else 0 end )", $search_term );
                        $relevance_array['sku'][] = $wpdb->prepare( "( case when ( term_source = 'sku' AND term LIKE %s ) then {$relevance_sku_like} else 0 end )", $like );
                        break;

                    case 'id':
                        $relevance_array['id'][] = $wpdb->prepare( "( case when ( term_source = 'id' AND term = '%s' ) then {$relevance_id} else 0 end )", $search_term );
                        $relevance_array['id'][] = $wpdb->prepare( "( case when ( term_source = 'id' AND term LIKE %s ) then {$relevance_id_like} else 0 end )", $like );
                        break;

                    default:
                        $addition_relevance_sources[] = $search_in_term;

                }

            }

            if ( ! empty( $addition_relevance_sources ) ) {
                $addition_relevance_sources_string = '';
                foreach ( $addition_relevance_sources as $addition_relevance_source ) {
                    $addition_relevance_sources_string .= "'" . $addition_relevance_source . "',";
                }
                $addition_relevance_sources_string = rtrim( $addition_relevance_sources_string, "," );
                $new_relevance_array[] = $wpdb->prepare( "( case when ( term_source IN ( {$addition_relevance_sources_string} ) AND term = '%s' ) then {$relevance_other} else 0 end ) + ( case when ( term_source IN ( {$addition_relevance_sources_string} ) AND term LIKE %s ) then {$relevance_other_like} else 0 end )",  $search_term, $like );
            }

        }


        // Sort 'relevance' queries in the array by search priority
        foreach ( $search_in_arr as $search_in_item ) {
            if ( isset( $relevance_array[$search_in_item] ) ) {
                $new_relevance_array[] = implode( ' + ', $relevance_array[$search_in_item] );
            }
        }


        $query['select'] = ' distinct ID';
        $query['relevance'] = sprintf( ' (SUM( %s )) ', implode( ' + ', $new_relevance_array ) );
        $query['search'] = sprintf( ' AND ( %s )', implode( ' OR ', $search_array ) );


        $stock_status_in = AWS_Helpers::get_query_stock_status( $product_stock_status, $reindex_version );
        if ( $stock_status_in && ! empty( $stock_status_in ) ) {
            $query['stock'] = sprintf( ' AND in_stock IN ( %s )', implode( ',', $stock_status_in ) );
        }


        if ( $onsale === 'false' ) {
            $query['sale'] = " AND on_sale = 1";
        } elseif ( $onsale === 'not' ) {
            $query['sale'] = " AND on_sale = 0";
        }


        if ( $product_visibility ) {

            $visibility_array = array();

            foreach( $product_visibility as $visibility => $is_active ) {
                if ( $is_active ) {
                    $like = '%' . $wpdb->esc_like( $visibility ) . '%';
                    $visibility_array[] = $wpdb->prepare( '( visibility LIKE %s )', $like );
                }
            }

            $query['visibility'] = sprintf( ' AND ( %s )', implode( ' OR ', $visibility_array ) );

        }

        // Advanced filters
        if ( isset( $adv_filters['product'] ) && ! empty( $adv_filters['product'] ) ) {

            $adv_filters_arr_obj = new AWS_Search_Filters( $adv_filters['product'], $this->form_id, $this->filter_id );
            $adv_filters_string = $adv_filters_arr_obj->filter();

            if ( $adv_filters_string ) {
                $query['adv_filters'] .= sprintf( ' AND ( %s )', $adv_filters_string );
            }

        }

        /*
         * Exclude certain products from search
         * @deprecated
         * @param array
         */
        $exclude_products_filter = apply_filters( 'aws_exclude_products', array(), $this->form_id, $this->filter_id );

        if ( $exclude_products_filter && is_array( $exclude_products_filter ) && ! empty( $exclude_products_filter ) ) {
            $query['exclude_products'] = sprintf( ' AND ( id NOT IN ( %s ) )', implode( ',', $exclude_products_filter ) );
        }

        // Language option

        if ( $this->lang ) {
            $current_lang = $this->lang;
        } else {
            $current_lang = AWS_Helpers::get_lang();
        }

        /**
         * Filter current language code
         * @since 1.50
         * @param string $current_lang Lang code
         */
        $current_lang = apply_filters( 'aws_search_current_lang', $current_lang, $this->form_id, $this->filter_id );

        if ( $current_lang && $reindex_version && version_compare( $reindex_version, '1.02', '>=' ) ) {
            $query['lang'] = $wpdb->prepare( " AND ( lang LIKE %s OR lang = '' )", '%' . $wpdb->esc_like( $current_lang ) . '%' );
        }


        // Type query
        $query['type'] = " AND ( type = 'product' OR type = 'var' )";

        if ( $reindex_version && version_compare( $reindex_version, '1.58', '>=' ) ) {
            if ( $var_rules === 'both' ) {
                $query['type'] = " AND ( type = 'product' OR type = 'var' OR type = 'child' )";
            } elseif ( $var_rules === 'child' ) {
                $query['type'] = " AND ( type = 'product' OR type = 'child' )";
            }
        }


        // Search logic
        if ( $search_logic === 'and' ) {

            if ( $search_exact === 'true' ) {
                $terms_number = count( $this->data['search_terms'] );
                if ( $terms_number ) {
                    $having_count = $terms_number - 1;
                    if ( $having_count < 0 ) {
                        $having_count = 0;
                    }
                    $query['having'] = sprintf( ' AND count(distinct term) > %s', $having_count );
                }

            } else {
                $and_search_array = array();
                $and_search_sources = array();
                foreach( $search_terms as $and_search_term ) {
                    if ( $and_search_term && strlen( $and_search_term ) > 1 ) {
                        $and_like = '%' . $wpdb->esc_like( $and_search_term ) . '%';
                        $and_search_array[] = $wpdb->prepare( '( term_string LIKE %s )', $and_like );
                    }
                }
                foreach( $search_in_arr as $and_search_source) {
                    $and_search_sources[] = 'term_source="' . $and_search_source . '"';
                }
                if ( $and_search_array ) {
                    $having_string = implode( ' AND ', $and_search_array );
                    $query['search'] .= sprintf( ' AND ( %s )', implode( ' OR ', $and_search_sources ) );
                    $query['select'] = ' distinct ID, GROUP_CONCAT(distinct term) as term_string';
                    $query['having'] = sprintf( ' AND ( %s )', $having_string );
                }
            }

        }

        /**
         * Filter search query parameters
         * @since 1.59
         * @param array $query Query parameters
         * @param int $this->form_id Form id
         * @param int $this->filter_id Filter id
         */
        $query = apply_filters( 'aws_search_query_array', $query, $this->form_id, $this->filter_id );

        $sql = "SELECT
                    {$query['select']},
                    {$query['relevance']} as relevance
                FROM
                    {$table_name}
                WHERE
                    1=1
                {$query['search']}
                {$query['adv_filters']}
                {$query['exclude_terms']}
                {$query['exclude_products']}
                {$query['stock']}
                {$query['sale']}
                {$query['visibility']}
                {$query['lang']}
                {$query['type']}
                GROUP BY ID
                  having relevance > 0 {$query['having']}
                ORDER BY 
                    relevance DESC, id DESC
		LIMIT 0, {$results_num}
		";

        /**
         * Filter search query string
         * @since 2.06
         * @param array $query Query string
         * @param int $this->form_id Form id
         * @param int $this->filter_id Filter id
         */
        $sql = apply_filters( 'aws_search_query_string', $sql, $this->form_id, $this->filter_id );

        $this->data['sql'] = $sql;

        $posts_ids = $this->get_posts_ids( $sql );

        return $posts_ids;

    }

    /*
     * Set sources to search_in option
     */
    private function set_search_in() {

        $search_in      = AWS_PRO()->get_settings( 'search_in', $this->form_id,  $this->filter_id );
        $search_in_attr = AWS_PRO()->get_settings( 'search_in_attr', $this->form_id,  $this->filter_id );
        $search_in_tax  = AWS_PRO()->get_settings( 'search_in_tax', $this->form_id,  $this->filter_id );
        $search_in_meta = AWS_PRO()->get_settings( 'search_in_meta', $this->form_id,  $this->filter_id );

        $search_in_arr = array();
        $search_in_temp = array();

        if ( is_array( $search_in ) ) {
            foreach( $search_in as $search_in_source => $search_in_active ) {
                if ( $search_in_active ) {
                    $search_in_arr[] = $search_in_source;
                }
            }
        } else {
            $search_in_arr = explode( ',',  $search_in );
        }

        if ( $search_in_arr && is_array( $search_in_arr ) && ! empty( $search_in_arr ) ) {
            foreach ( $search_in_arr as $search_source ) {
                switch ( $search_source ) {

                    case 'attr':

                        if ( $search_in_attr && is_array( $search_in_attr ) && ! empty( $search_in_attr ) ) {
                            foreach( $search_in_attr as $available_attribute_slug => $available_attribute_val ) {
                                if ( $available_attribute_val ) {
                                    $search_in_temp[] = $available_attribute_slug;
                                }
                            }
                        }

                        break;

                    case 'tax':

                        if ( $search_in_tax && is_array( $search_in_tax ) && ! empty( $search_in_tax ) ) {
                            foreach( $search_in_tax as $available_tax_slug => $available_tax_val ) {
                                if ( $available_tax_val ) {
                                    $search_in_temp[] = $available_tax_slug;
                                }
                            }
                        }

                        break;

                    case 'meta':

                        if ( $search_in_meta && is_array( $search_in_meta ) && ! empty( $search_in_meta ) ) {
                            foreach( $search_in_meta as $available_meta_slug => $available_meta_val ) {
                                if ( $available_meta_val ) {
                                    $search_in_temp[] = $available_meta_slug;
                                }
                            }
                        }

                        break;

                    default:
                        $search_in_temp[] = $search_source;

                }
            }
        }

        return $search_in_temp;

    }

    /*
     * Get array of included to search result posts ids
     */
    private function get_posts_ids( $sql ) {

        global $wpdb;

        $posts_ids = array();

        $search_results = $wpdb->get_results( $sql );


        if ( !empty( $search_results ) && !is_wp_error( $search_results ) && is_array( $search_results ) ) {
            foreach ( $search_results as $search_result ) {
                $post_id = intval( $search_result->ID );
                if ( ! in_array( $post_id, $posts_ids ) ) {
                    $posts_ids[] = $post_id;
                }
            }
        }

        unset( $search_results );

        return $posts_ids;

    }

    /*
     * Get products info
     */
    public function get_products( $posts_ids ) {

        $products_array = array();

        if ( count( $posts_ids ) > 0 ) {

            $excerpt_source       = AWS_PRO()->get_settings( 'desc_source', $this->form_id );

            $desc_scrap_words     = AWS_PRO()->get_settings( 'mark_words', $this->form_id, $this->filter_id );
            $highlight_words      = AWS_PRO()->get_settings( 'highlight', $this->form_id, $this->filter_id );
            $style                = AWS_PRO()->get_settings( 'style', $this->form_id, $this->filter_id );
            $show_excerpt         = AWS_PRO()->get_settings( 'show_excerpt', $this->form_id, $this->filter_id );
            $excerpt_length       = AWS_PRO()->get_settings( 'excerpt_length', $this->form_id, $this->filter_id );
            $show_price           = AWS_PRO()->get_settings( 'show_price', $this->form_id, $this->filter_id );
            $show_outofstockprice = AWS_PRO()->get_settings( 'show_outofstock_price', $this->form_id, $this->filter_id );
            $show_sale            = AWS_PRO()->get_settings( 'show_sale', $this->form_id, $this->filter_id );
            $show_sku             = AWS_PRO()->get_settings( 'show_sku', $this->form_id, $this->filter_id );
            $show_stock_status    = AWS_PRO()->get_settings( 'show_stock', $this->form_id, $this->filter_id );
            $show_image           = AWS_PRO()->get_settings( 'show_image', $this->form_id, $this->filter_id );
            $show_cats            = AWS_PRO()->get_settings( 'show_result_cats', $this->form_id, $this->filter_id );
            $show_brands          = AWS_PRO()->get_settings( 'show_result_brands', $this->form_id, $this->filter_id, 'woocommerce-brands/woocommerce-brands.php' );
            $show_rating          = AWS_PRO()->get_settings( 'show_rating', $this->form_id, $this->filter_id );
            $show_featured        = AWS_PRO()->get_settings( 'show_featured', $this->form_id, $this->filter_id );
            $show_variations      = AWS_PRO()->get_settings( 'show_variations', $this->form_id, $this->filter_id );
            $show_add_to_cart     = AWS_PRO()->get_settings( 'show_cart', $this->form_id, $this->filter_id );


            $posts_items = $posts_ids;


            foreach ( $posts_items as $post_item ) {

                if ( ! is_object( $post_item ) ) {
                    $product = wc_get_product( $post_item );
                } else {
                    $product = $post_item;
                }

                if ( ! is_a( $product, 'WC_Product' ) ) {
                    continue;
                }

                setup_postdata( $post_item );

                $post_id = method_exists( $product, 'get_id' ) ? $product->get_id() : $post_item;
                $parent_id = $product->is_type( 'variation' ) && method_exists( $product, 'get_parent_id' ) ? $product->get_parent_id() : $post_id;

                /**
                 * Filter additional product data
                 * @since 1.51
                 * @param array $this->data Additional data
                 * @param int $post_id Product id
                 * @param object $product Product
                 */
                $this->data = apply_filters( 'aws_search_data_params', $this->data, $post_id, $product );

                $post_data = get_post( $post_id );

                $title = isset( $post_data->post_title ) ? $post_data->post_title : '';
                $title = apply_filters( 'the_title', $title, $post_id );

                // Add custom variation attributes to product title
                if ( $product->is_type( 'variation' ) && class_exists( 'WC_Product_Variation' ) ) {
                    $variation_product = new WC_Product_Variation( $post_id );
                    if ( method_exists( $variation_product, 'get_attributes' ) ) {
                        $variation_attr = $variation_product->get_attributes();
                        $attr_to_title = array();

                        if ( $variation_attr && is_array( $variation_attr ) ) {
                            foreach( $variation_attr as $variation_p_att => $variation_p_text ) {

                                if ( strpos( $variation_p_att, 'pa_' ) === 0 ) {
                                    $attr_term = get_term_by( 'slug', $variation_p_text, $variation_p_att );
                                    if ( ! is_wp_error( $attr_term ) && $attr_term && $attr_term->name ) {
                                        $variation_p_text = $attr_term->name;
                                    }
                                }

                                if ( $variation_p_text ) {
                                    $attr_to_title[] = $variation_p_text;
                                }

                            }
                        }

                        if ( $attr_to_title && strpos( $title, '&#8211;' ) === false ) {
                            $title = get_the_title( $parent_id );
                            $title = $title . ' &#8211; ' . implode( ', ', $attr_to_title );
                        }

                    }
                }


                $title = AWS_Helpers::html2txt( $title );


                $excerpt = '';
                $price   = '';
                $on_sale = '';
                $sku = '';
                $stock_status = '';
                $image = '';
                $categories = '';
                $brands = '';
                $rating = '';
                $is_featured = '';
                $reviews = '';
                $variations = '';
                $add_to_cart = '';

                if ( $show_excerpt === 'true' ) {

                    $excerpt = ( $excerpt_source === 'excerpt' && $post_data->post_excerpt ) ? $post_data->post_excerpt : $post_data->post_content;
                    $excerpt = AWS_Helpers::html2txt( $excerpt );
                    $excerpt = str_replace('"', "'", $excerpt);
                    $excerpt = strip_shortcodes( $excerpt );
                    $excerpt = AWS_Helpers::strip_shortcodes( $excerpt );

                    if ( $desc_scrap_words === 'true'  ) {

                        $marked_content = $this->scrap_content( $excerpt );

                        if ( $marked_content ) {
                            $excerpt = $marked_content;
                        } else {
                            $excerpt = wp_trim_words( $excerpt, $excerpt_length, '...' );
                        }

                    } else {
                        $excerpt = wp_trim_words( $excerpt, $excerpt_length, '...' );
                    }

                }

                if ( $show_price === 'true' && ( $product->is_in_stock() || ( ! $product->is_in_stock() && $show_outofstockprice === 'true' ) ) ) {
                    $price = $product->get_price_html();
                    $price = preg_replace("/<a\s(.+?)>(.+?)<\/a>/is", "<span>$2</span>", $price);
                }

                if ( $show_sale === 'true' && ( $product->is_in_stock() || ( ! $product->is_in_stock() && $show_outofstockprice === 'true' ) ) ) {
                    $on_sale = $product->is_on_sale();
                }

                if ( $show_sku === 'true' ) {
                    $sku = $product->get_sku();
                }

                if ( method_exists( $product, 'get_stock_status' ) ) {
                    $product_stock_status = $product->get_stock_status();
                } else {
                    $product_stock_status = false;
                }

                if ( $show_stock_status === 'true' || $show_stock_status === 'quantity' ) {
                    if ( $product->is_in_stock() && $product_stock_status !== 'onbackorder' ) {
                        
                        if ( $show_stock_status === 'quantity' && $product->get_stock_quantity() ) {
                            $stock_status = array(
                                'status' => true,
                                'text'   => $product->get_stock_quantity() . ' ' . esc_html__( 'In stock', 'advanced-woo-search' )
                            );
                        } else {
                            $stock_status = array(
                                'status' => true,
                                'text'   => esc_html__( 'In stock', 'advanced-woo-search' )
                            );
                        }
                       
                    } else {
                        $stock_status = array(
                            'status' => false,
                            'text'   => $product_stock_status === 'onbackorder' ? esc_html__( 'On Backorder', 'advanced-woo-search' ) : esc_html__( 'Out of stock', 'advanced-woo-search' )
                        );
                    }
                }
              
                if ( $show_image === 'true' ) {
                    $image_size = ( $style === 'style-big-grid' ) ? 'normal' : 'thumbnail';
                    $image = $this->get_image( $product, $image_size, $post_data );
                }

                if ( $show_cats === 'true' ) {
                    $categories = $this->get_terms_list( $parent_id, 'product_cat' );
                }

                if ( $show_brands === 'true' ) {
                    $brands = AWS_Helpers::get_product_brands( $post_id );
                }

                if ( $show_rating === 'true' && method_exists( $product, 'get_average_rating' ) ) {
                    $rating = $product->get_average_rating();
                    $rating = $rating ? $rating * 20 : '';
                    if ( method_exists( $product, 'get_review_count' ) ) {
                        $reviews = sprintf( _nx( '1 Review', '%1$s Reviews', $product->get_review_count(), 'product reviews', 'advanced-woo-search' ), number_format_i18n( $product->get_review_count() ) );
                    }
                }

                if ( $show_featured === 'true' ) {
                    $is_featured = $product->is_featured();
                }

                if ( $show_variations === 'true' ) {
                    if ( $product->is_type( 'variable' ) && method_exists( $product, 'get_variation_attributes' ) ) {

                        $variations_array = $product->get_variation_attributes();
                        $variations = array();

                        foreach ( $variations_array as $variation_key => $variation_value ) {
                            if ( strpos( $variation_key, 'pa_' ) === 0 ) {
                                $attr_tax = get_taxonomy( $variation_key );
                                if ( ! is_wp_error( $attr_tax ) && $attr_tax ) {
                                    $attr_terms = get_the_terms( $post_id, $variation_key );
                                    if ( ! is_wp_error( $attr_terms ) && ! empty( $attr_terms ) ) {
                                        $attr_terms_array = array();
                                        foreach ( $attr_terms as $attr_term ) {
                                            if ( in_array( $attr_term->slug, $variation_value ) ) {
                                                $attr_terms_array[] = $attr_term->name;
                                            }
                                        }

                                        if ( ! empty( $attr_terms_array ) ) {
                                            $variations[$attr_tax->labels->singular_name] = $attr_terms_array;
                                        }

                                    }
                                }
                            } else {
                                $variations[$variation_key] = $variation_value;
                            }
                        }

                    }
                }

                if ( $show_add_to_cart !== 'false' && class_exists( 'WC_AJAX' ) ) {
                    $add_to_cart = AWS_Helpers::get_product_cart_args( $product, $show_add_to_cart );
                }

                $tags = $this->get_terms_list( $post_id, 'product_tag' );

                if ( method_exists( $product, 'get_price' ) ) {
                    $f_price = $product->get_price();
                }

                if ( method_exists( $product, 'get_average_rating' ) ) {
                    $f_rating  = $product->get_average_rating();
                }

                if ( method_exists( $product,'get_review_count' ) ) {
                    $f_reviews = $product->get_review_count();
                }

                $f_stock = $product->is_in_stock();
                $f_sale  = $product->is_on_sale();

                if ( $highlight_words === 'true'  ) {
                    $title      = $this->highlight_words( $title );
                    $excerpt    = $this->highlight_words( $excerpt );
                    $sku        = $this->highlight_words( $sku );
                    $categories = $this->highlight_words( $categories );
                }

                $title   = apply_filters( 'aws_title_search_result', $title, $post_id, $product );
                $excerpt = apply_filters( 'aws_excerpt_search_result', $excerpt, $post_id, $product );

                $new_result = array(
                    'id'           => $post_id,
                    'parent_id'    => $parent_id,
                    'title'        => $title,
                    'excerpt'      => $excerpt,
                    'link'         => get_permalink( $post_id ),
                    'image'        => $image,
                    'price'        => $price,
                    'categories'   => $categories,
                    'tags'         => $tags,
                    'brands'       => $brands,
                    'on_sale'      => $on_sale,
                    'sku'          => $sku,
                    'stock_status' => $stock_status,
                    'featured'     => $is_featured,
                    'rating'       => $rating,
                    'reviews'      => $reviews,
                    'variations'   => $variations,
                    'add_to_cart'  => $add_to_cart,
                    'f_price'      => $f_price,
                    'f_rating'     => $f_rating,
                    'f_reviews'    => $f_reviews,
                    'f_stock'      => $f_stock,
                    'f_sale'       => $f_sale,
                    'post_data'    => $post_data
                );

                /**
                 * Filter single product search result
                 * @since 2.49
                 * @param array $new_result Product data array
                 * @param int $post_id Product id
                 * @param object $product Product
                 */
                $new_result = apply_filters( 'aws_search_pre_filter_single_product', $new_result, $post_id, $product );

                $products_array[] = $new_result;

                wp_reset_postdata();

            }

        }

        /**
         * Filter products array before output
         * @since 1.51
         * @param array $products_array Products array
         * @param array $this->data Additional data
         */
        $products_array = apply_filters( 'aws_search_pre_filter_products', $products_array, $this->data );

        return $products_array;

    }

    /*
	 * Get string with current product terms
	 *
	 * @return string List of terms
	 */
    private function get_terms_list( $id, $taxonomy ) {

        $terms = get_the_terms( $id, $taxonomy );

        if ( is_wp_error( $terms ) ) {
            return '';
        }

        if ( empty( $terms ) ) {
            return '';
        }

        $cats_array_temp = array();

        foreach ( $terms as $term ) {
            if ( is_object( $term ) && property_exists( $term, 'name' ) ) {
                $cats_array_temp[] = $term->name;
            }
        }

        return implode( ', ', $cats_array_temp );

    }

    /*
     * Get product image
     *
     * @return string Image url
     */
    private function get_image( $product, $image_size, $post_data ) {

        $image_sources = AWS_PRO()->get_settings( 'image_source', $this->form_id );
        $default_img   = AWS_PRO()->get_settings( 'default_img', $this->form_id );

        /**
         * Filter products images size
         * @since 2.06
         * @param string $image_size Image size
         */
        $image_size = apply_filters( 'aws_image_size', $image_size );

        $post_id = $post_data->ID;

        $image_sources_array = explode( ',', $image_sources );

        $image_src = '';

        if ( empty( $image_sources_array ) ) {
            return '';
        }

        foreach ( $image_sources_array as $image_source ) {

            switch( $image_source ) {

                case 'featured';

                    $post_thumbnail_id = get_post_thumbnail_id( $post_id );

                    if ( ! $post_thumbnail_id && method_exists( $product, 'get_image_id' ) ) {
                        $post_thumbnail_id = $product->get_image_id();
                    }

                    if ( $post_thumbnail_id ) {
                        $thumb_url_array = wp_get_attachment_image_src( $post_thumbnail_id, $image_size );
                        $image_src = $thumb_url_array ? $thumb_url_array[0] : '';
                    }

                    break;

                case 'gallery';

                    $gallery_images_array = method_exists( $product, 'get_gallery_image_ids' ) ? $product->get_gallery_image_ids() : ( method_exists( $product, 'get_gallery_attachment_ids' ) ? $product->get_gallery_attachment_ids() : array() );

                    if ( ! empty( $gallery_images_array ) ) {
                        $gallery_url_array = wp_get_attachment_image_src( $gallery_images_array[0], $image_size );
                        $image_src = $gallery_url_array ? $gallery_url_array[0] : '';
                    }

                    break;

                case 'content';

                    $image_src = $this->scrap_img( $post_data->post_content );

                    break;

                case 'description';

                    $image_src = $this->scrap_img( $post_data->post_excerpt );

                    break;

                case 'default';

                    $image_src = $default_img;

                    break;

            }

            if ( $image_src ) {
                break;
            }

        }

        return $image_src;

    }

    /*
     * Scrap img src from string
     *
     * @return string Image src url
     */
    private function scrap_img( $content ) {
        preg_match( '|<img.*?src=[\'"](.*?)[\'"].*?>|i', $content, $matches );
        $result = ( isset( $matches[1] ) && $matches[1] ) ? $matches[1] : '';
        return $result;
    }

    /*
     * Scrap content excerpt
     */
    private function scrap_content( $content ) {

        $exact_words = array();
        $words = array();
        $excerpt_length = AWS_PRO()->get_settings( 'excerpt_length', $this->form_id, $this->filter_id );

        foreach( $this->data['search_terms'] as $search_in ) {

            $search_in = preg_quote( $search_in, '/' );
            $exact_words[] = '\b' . $search_in . '\b';

            if ( strlen( $search_in ) > 1 ) {
                $words[] = $search_in;
            } else {
                $words[] = '\b' . $search_in . '\b';
            }

        }

        usort( $exact_words, array( $this, 'sort_by_length' ) );
        $exact_words = implode( '|', $exact_words );

        usort( $words, array( $this, 'sort_by_length' ) );
        $words = implode( '|', $words );

        preg_match( '/([^.?!]*?)(' . $exact_words . '){1}(.*?[.!?])/i', $content, $matches );

        if ( ! isset( $matches[0] ) ) {
            preg_match( '/([^.?!]*?)(' . $words . '){1}(.*?[.!?])/i', $content, $matches );
        }

        if ( isset( $matches[0] ) ) {

            $content = $matches[0];

            // Trim to long content
            if (str_word_count(strip_tags($content)) > 34) {

                if (str_word_count(strip_tags($matches[3])) > 34) {
                    $matches[3] = wp_trim_words($matches[3], 30, '...');
                }

                $content = '...' . $matches[2] . $matches[3];

            }

        } else {

            // Get first N sentences
            if ( str_word_count( strip_tags( $content ) ) > $excerpt_length ) {

                $sentences_array = preg_split( "/(?<=[.!?])/", $content, 10, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
                $sentences_string = '';
                $str_word_count = 0;

                if ( ! empty( $sentences_array ) ) {
                    foreach ( $sentences_array as $sentence ) {
                        $str_word_count = $str_word_count + str_word_count( strip_tags( $sentence ) );
                        if ( $str_word_count <= $excerpt_length ) {
                            $sentences_string .= $sentence;
                        } else {
                            break;
                        }
                    }
                }

                if ( $sentences_string ) {
                    $content = $sentences_string;
                }

            }

        }

        return $content;

    }

    /*
     * Highlight search words
     */
    private function highlight_words( $text ) {

        if ( ! $text ) {
            return $text;
        }

        $pattern = array();

        foreach( $this->data['search_terms'] as $search_in ) {

            $search_in = preg_quote( $search_in, '/' );

            if ( strlen( $search_in ) > 1 ) {
                $pattern[] = '(' . $search_in . ')+';
            } else {
                $pattern[] = '\b[' . $search_in . ']{1}\b';
            }

        }

        usort( $pattern, array( $this, 'sort_by_length' ) );
        $pattern = implode( '|', $pattern );
        $pattern = sprintf( '/%s/i', $pattern );

        /**
         * Tag to use for highlighting search words inside content
         * @since 1.79
         * @param string Tag for highlighting
         */
        $highlight_tag = apply_filters( 'aws_highlight_tag', 'strong' );

        $highlight_tag_pattern = '<' . $highlight_tag . '>${0}</' . $highlight_tag . '>';

        $text = preg_replace($pattern, $highlight_tag_pattern, $text );

        return $text;

    }

    /*
     * Sort array by its values length
     */
    private function sort_by_length( $a, $b ) {
        return strlen( $b ) - strlen( $a );
    }

}

endif;


AWS_Search::factory();

function aws_search( $keyword = '' ) {

    ob_start();

    $search_results = AWS_Search::factory()->search( $keyword );

    ob_end_clean();

    return $search_results;

}