<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'AWS_Tax_Search' ) ) :

    /**
     * Class for admin condition rules
     */
    class AWS_Tax_Search {

        /**
         * @var array AWS_Tax_Search Taxonomy name
         */
        private $taxonomy;

        /**
         * @var string AWS_Tax_Search Search string
         */
        private $search_string;

        /**
         * @var string AWS_Tax_Search Unfiltered search string
         */
        private $search_string_unfiltered;

        /**
         * @var array AWS_Tax_Search Search terms array
         */
        private $search_terms;

        /*
         * Constructor
         */
        public function __construct( $taxonomy, $data ) {

            /**
             * Filters array taxonomies search data
             * @since 1.93
             * @param array $data Array of search data
             * @param string $taxonomy Taxonomy name
             */
            $data = apply_filters( 'aws_tax_search_data', $data, $taxonomy );

            $this->taxonomy = $taxonomy;
            $this->search_string = isset( $data['s'] ) ? $data['s'] : '';
            $this->search_string_unfiltered = isset( $data['s_nonormalize'] ) ? $data['s_nonormalize'] : $this->search_string ;
            $this->search_terms = isset( $data['search_terms'] ) ? $data['search_terms'] : array();

        }

        /**
         * Get search results
         * @return array Results array
         */
        public function get_results() {

            if ( ! $this->search_terms || empty( $this->search_terms ) ) {
                return array();
            }

            global $wpdb;

            $search_query = '';
            $search_string_unfiltered = '';

            $filtered_terms_full = $wpdb->prepare( '( name LIKE %s )',  '%' . $wpdb->esc_like( $this->search_string_unfiltered ) . '%' );

            $search_array = array_map( array( 'AWS_Helpers', 'singularize' ), $this->search_terms  );
            $search_array = $this->synonyms( $search_array );
            $search_array = $this->get_search_array( $search_array );

            $search_array_chars = $this->get_unfiltered_search_array();

            if ( $search_array_chars ) {
                $search_string_unfiltered =  sprintf( 'OR ( %s )', implode( ' OR ', $this->get_search_array( $search_array_chars ) ) );
            }

            $search_query .= sprintf( ' AND ( ( %s ) OR %s %s )', implode( ' OR ', $search_array ), $filtered_terms_full, $search_string_unfiltered );

            $search_results = $this->query( $search_query );
            $result_array = $this->output( $search_results );

            return $result_array;

        }

        /**
         * Search query
         * @param string $search_query SQL query
         * @return array SQL query results
         */
        private function query( $search_query ) {

            global $wpdb;

            $excludes = '';
            $taxonomies_array = array_map( array( $this, 'prepare_tax_names' ), $this->taxonomy );
            $taxonomies_names = implode( ',', $taxonomies_array );

            /**
             * Max number of terms to show
             * @since 1.73
             * @param int
             */
            $terms_number = apply_filters( 'aws_search_terms_number', 10 );

            $excludes_array = $this->get_excluded_terms();
            if ( $excludes_array && ! empty( $excludes_array ) ) {
                $excludes = sprintf( " AND ( " . $wpdb->terms . ".term_id NOT IN ( %s ) )", implode( ',', array_map( array( $this, 'prepare_tax_names' ), $excludes_array ) ) );
            }

            $relevance_array = $this->get_relevance_array();

            if ( $relevance_array && ! empty( $relevance_array ) ) {
                $relevance_query = sprintf( ' (SUM( %s )) ', implode( ' + ', $relevance_array ) );
            } else {
                $relevance_query = '0';
            }

            $lang = isset( $_REQUEST['lang'] ) ? sanitize_text_field( $_REQUEST['lang'] ) : '';
            if ( $lang ) {
                $terms = get_terms( array(
                    'taxonomy'   => $this->taxonomy,
                    'hide_empty' => true,
                    'fields'     => 'ids',
                    'lang'       => $lang
                ) );
                if ( $terms ) {
                    $search_query .= sprintf( " AND ( " . $wpdb->terms . ".term_id IN ( %s ) )", implode( ',', $terms ) );
                }
            }

            $sql = "
			SELECT
				distinct($wpdb->terms.name),
				$wpdb->terms.term_id,
				$wpdb->term_taxonomy.taxonomy,
				$wpdb->term_taxonomy.count,
				{$relevance_query} as relevance
			FROM
				$wpdb->terms
				, $wpdb->term_taxonomy
			WHERE 1 = 1
				{$search_query}
				AND $wpdb->term_taxonomy.taxonomy IN ( {$taxonomies_names} )
				AND $wpdb->term_taxonomy.term_id = $wpdb->terms.term_id
				AND count > 0
			    {$excludes}
			    GROUP BY term_id
			    ORDER BY relevance DESC, term_id DESC
			LIMIT 0, {$terms_number}";


            $sql = trim( preg_replace( '/\s+/', ' ', $sql ) );


            /**
             * Filter terms search query
             * @since 1.91
             * @param string $sql Sql query
             * @param string $taxonomy Taxonomy name
             * @param string $search_query Search query
             */
            $sql = apply_filters( 'aws_terms_search_query', $sql, $this->taxonomy, $search_query );

            $search_results = $wpdb->get_results( $sql );

            return $search_results;

        }

        /**
         * Order and output search results
         * @param array SQL query results
         * @return array Array of taxonomies results
         */
        private function output( $search_results ) {

            $result_array = array();

            if ( ! empty( $search_results ) && !is_wp_error( $search_results ) ) {

                foreach ( $search_results as $result ) {

                    $parent = '';
                    $slug = '';

                    $term = get_term( $result->term_id, $result->taxonomy );

                    if ( $term != null && !is_wp_error( $term ) ) {
                        $term_link = get_term_link( $term );
                        $parent    = is_object( $term ) && property_exists( $term, 'parent' ) ? $term->parent : '';
                        $slug      = $term->slug;
                    } else {
                        continue;
                    }

                    $new_result = array(
                        'name'     => $result->name,
                        'id'       => $result->term_id,
                        'slug'     => $slug,
                        'count'    => ( $result->count > 0 ) ? $result->count : '',
                        'link'     => $term_link,
                        'excerpt'  => '',
                        'parent'   => $parent
                    );

                    $result_array[$result->taxonomy][] = $new_result;

                }

                /**
                 * Filters array of custom taxonomies that must be displayed in search results
                 *
                 * @since 1.63
                 *
                 * @param array $result_array Array of custom taxonomies
                 * @param string $taxonomy Name of taxonomy
                 * @param string $s Search query
                 */
                $result_array = apply_filters( 'aws_search_tax_results', $result_array, $this->taxonomy, $this->search_string );

            }

            // Deprecated filters support
            if ( is_array( $this->taxonomy ) ) {

                if ( in_array( 'product_cat', $this->taxonomy ) ) {
                    $result_array = apply_filters( 'aws_search_results_categories', $result_array, $this->search_string );
                }

                if ( in_array( 'product_tag', $this->taxonomy ) ) {
                    $result_array = apply_filters( 'aws_search_results_tags', $result_array, $this->search_string );
                }

            }

            return $result_array;

        }

        /**
         * Get taxonomies relevance array
         *
         * @return array Relevance array
         */
        private function get_relevance_array() {

            global $wpdb;

            $relevance_array = array();

            foreach ( $this->search_terms as $search_term ) {

                $search_term_len = strlen( $search_term );

                $relevance = 40 + 2 * $search_term_len;

                $search_term_norm = AWS_Plurals::singularize( $search_term );

                if ( $search_term_norm && $search_term_len > 3 && strlen( $search_term_norm ) > 2 ) {
                    $search_term = $search_term_norm;
                }

                $like = '%' . $wpdb->esc_like($search_term) . '%';

                $relevance_array[] = $wpdb->prepare( "( case when ( name LIKE %s ) then {$relevance} else 0 end )", $like );

                if ( $terms_desc_search = apply_filters( 'aws_search_terms_description', false ) ) {
                    $relevance_desc = 10 + 2 * $search_term_len;
                    $relevance_array[] = $wpdb->prepare( "( case when ( description LIKE %s ) then {$relevance_desc} else 0 end )", $like );
                    $relevance_array[] = $wpdb->prepare( "( case when ( description LIKE %s ) then {$relevance_desc} else 0 end )", '%' . $wpdb->esc_like( $this->search_string_unfiltered ) . '%' );
                }

            }

            return $relevance_array;

        }

        /**
         * Get taxonomies search array
         * @param array Search terms array
         * @return array Terms
         */
        private function get_search_array( $search_terms ) {

            global $wpdb;

            $search_array = array();

            foreach ( $search_terms as $search_term ) {

                $like = '%' . $wpdb->esc_like($search_term) . '%';

                $search_array[] = $wpdb->prepare('( name LIKE %s )', $like);

                if ( $terms_desc_search = apply_filters( 'aws_search_terms_description', false ) ) {
                    $search_array[] = $wpdb->prepare('( description LIKE %s )', $like);
                    $search_array[] = $wpdb->prepare('( description LIKE %s )', '%' . $wpdb->esc_like( $this->search_string_unfiltered ) . '%' );
                }

            }

            return $search_array;

        }

        /**
         * Get taxonomies search array with special chars
         *
         * @return array Terms
         */
        private function get_unfiltered_search_array() {

            $no_normalized_str = $this->search_string_unfiltered;

            $no_normalized_str = AWS_Helpers::html2txt( $no_normalized_str );
            $no_normalized_str = trim( $no_normalized_str );

            $no_normalized_str = strtr( $no_normalized_str, AWS_Helpers::get_diacritic_chars() );

            if ( function_exists( 'mb_strtolower' ) ) {
                $no_normalized_str = mb_strtolower( $no_normalized_str );
            }

            $search_array_chars = array_unique( explode( ' ', $no_normalized_str ) );
            $search_array_chars = AWS_Helpers::filter_stopwords( $search_array_chars );

            if ( $search_array_chars ) {
                foreach ( $search_array_chars as $search_array_chars_index => $search_array_chars_term ) {
                    if ( array_search( $search_array_chars_term, $this->search_terms ) ) {
                        unset( $search_array_chars[$search_array_chars_index] );
                    }
                }
            }

            if ( count( $search_array_chars ) === 1 && $search_array_chars[0] === $this->search_string_unfiltered ) {
                $search_array_chars = array();
            }

            return $search_array_chars;

        }

        /**
         * Get array of terms that must be excluded
         * @return array Terms ids
         */
        private function get_excluded_terms() {

            $excludes_array = array();

            /**
             * Exclude certain taxonomies terms from search
             * @since 1.92
             * @param array $excludes_array Array of terms Ids
             */
            $excludes_array = apply_filters( 'aws_search_tax_exclude', $excludes_array, $this->taxonomy, $this->search_string );

            foreach( $this->taxonomy as $taxonomy_name ) {

                /**
                 * Exclude certain terms from search ( deprecated )
                 * @since 1.58
                 * @param array
                 */
                $exclude_terms = apply_filters( 'aws_terms_exclude_' . $taxonomy_name, array() );

                if ( $exclude_terms && is_array( $exclude_terms ) && ! empty( $exclude_terms ) ) {
                    $excludes_array = array_merge( $excludes_array, $exclude_terms );
                }

            }

            return $excludes_array;

        }

        /*
         * Prepare taxonomy names for query
         * @param string $name Taxonomy name
         * @return string Prepared string
         */
        private function prepare_tax_names( $name ) {
            global $wpdb;
            return $wpdb->prepare('%s', $name);
        }

        /*
         * Add synonyms
         * @param array $search_terms Search term
         * @return array Search term with synonyms
         */
        private function synonyms( $search_terms ) {

            if ( $search_terms && ! empty( $search_terms ) ) {

                $new_search_terms = array();

                foreach( $search_terms as $search_term ) {
                    $new_search_terms[$search_term] = 1;
                }

                $new_search_terms = AWS_Helpers::get_synonyms( $new_search_terms, true );

                return array_keys( $new_search_terms );

            }

            return $search_terms;

        }

    }

endif;