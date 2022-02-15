<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'AWS_Search_Filters' ) ) :

    /**
     * AWS search filters generator class
     */
    class AWS_Search_Filters {

        /**
         * @var AWS_Search_Filters Array of filters $conditions
         */
        protected $conditions = null;

        /**
         * @var AWS_Search_Filters Array with current condition rule $rule
         */
        protected $rule = null;

        /**
         * @var AWS_Search_Filters ID of current form instance $form_id
         */
        private $form_id = 0;

        /**
         * @var AWS_Search_Filters ID of current filter $filter_id
         */
        private $filter_id = 0;

        /*
         * Constructor
         */
        public function __construct( $conditions, $form_id = 1, $filter_id = 1 ) {

            /**
             * Filters condition rules
             *
             * @since 2.45
             *
             * @param array $conditions Condition rules
             * @param int $form_id Current form ID
             * @param int $filter_id Current filter ID
             */
            $this->conditions = apply_filters( 'aws_filters_conditions', $conditions, $form_id, $filter_id );

            $this->form_id = $form_id;

            $this->filter_id = $filter_id;

        }

        /*
         * Filter products results and output SQL string
         */
        public function filter() {

            $filters = array();
            $filters_sql = '';

            if ( empty( $this->conditions ) || ! is_array( $this->conditions ) ) {
                return $filters;
            }

            /**
             * Filter condition functions
             * @since 2.45
             * @param array Array of custom condition functions
             */
            $custom_match_functions = apply_filters( 'aws_filters_condition_rules', array() );

            foreach ( $this->conditions as $condition_group ) {

                if ( $condition_group && ! empty( $condition_group ) ) {

                    $group_rules = $this->filter_product_terms_rules( $condition_group );
                    $group_filters = array();

                    foreach( $group_rules as $condition_rule ) {

                        $this->rule = $condition_rule;
                        $condition_name = $condition_rule['param'];
                        $condition_output = '';

                        if ( isset( $custom_match_functions[$condition_name] ) ) {
                            $condition_output = call_user_func( $custom_match_functions[$condition_name], $condition_rule );
                        } elseif ( method_exists( $this, 'match_' . $condition_name ) ) {
                            $condition_output = call_user_func( array( $this, 'match_' . $condition_name ) );
                        }

                        if ( $condition_output ) {
                            $group_filters[] = $condition_output;
                        }

                    }

                    if ( ! empty( $group_filters ) ) {
                        $filters[] = '( ' . implode( ' AND ', $group_filters ) . ' )';
                    }

                }

            }

            if ( ! empty( $filters ) ) {
                $filters_sql =  implode( ' OR ', $filters );
            }

            $filters_sql = trim( preg_replace( '/\s+/', ' ', $filters_sql ) );

            return $filters_sql;

        }

        /*
         * Aggregate product terms rules
         */
        private function filter_product_terms_rules( $group_rules ) {

            global $wpdb;

            $terms_rules = array( 'product_category', 'product_tag', 'product_taxonomy', 'product_attributes', 'product_shipping_class' );
            $new_group_rules = array();

            $terms_equal_array = array();
            $terms_not_equal_array = array();

            $taxonomies_equal_array = array();
            $taxonomies_not_equal_array = array();

            foreach( $group_rules as $condition_rule ) {

                if ( array_search( $condition_rule['param'], $terms_rules ) !== false ) {
                    if ( $condition_rule['operator'] === 'equal' ) {
                        if ( $condition_rule['value'] === 'aws_any' ) {
                            $taxonomies_equal_array[] = $wpdb->prepare( '%s', $condition_rule['suboption'] );
                        } else {
                            $terms_equal_array[] = $condition_rule['value'];
                        }
                    } else {
                        if ( $condition_rule['value'] === 'aws_any' ) {
                            $taxonomies_not_equal_array[] = $wpdb->prepare( '%s', $condition_rule['suboption'] );
                        } else {
                            $terms_not_equal_array[] = $condition_rule['value'];
                        }
                    }
                    continue;
                }

                $new_group_rules[] = $condition_rule;

            }

            if ( $terms_equal_array ) {
                $new_group_rules[] = array( 'param' => 'product_terms', 'operator' => 'equal', 'value' => $terms_equal_array );
            }

            if ( $terms_not_equal_array ) {
                $new_group_rules[] = array( 'param' => 'product_terms', 'operator' => 'not_equal', 'value' => $terms_not_equal_array );
            }

            if ( $taxonomies_equal_array ) {
                $new_group_rules[] = array( 'param' => 'product_taxonomies', 'operator' => 'equal', 'value' => $taxonomies_equal_array );
            }

            if ( $taxonomies_not_equal_array ) {
                $new_group_rules[] = array( 'param' => 'product_taxonomies', 'operator' => 'not_equal', 'value' => $taxonomies_not_equal_array );
            }

            return $new_group_rules;

        }

        /*
         * Product rule
         */
        public function match_product() {

            if ( 'aws_any' === $this->rule['value'] ) {
                if ( $this->rule['operator'] === 'equal' ) {
                    return '';
                } else {
                    return "( 1=2 )";
                }
            }

            $product = wc_get_product( $this->rule['value'] );

            $filter_products = array();
            $filter_products[] = $this->rule['value'];
            $relation = $this->rule['operator'] === 'equal' ? 'IN' : 'NOT IN';

            if ( ! is_a( $product, 'WC_Product' ) ) {
               return '';
            }

            /*
             * Products filter
             */
            $filter_products = apply_filters( 'aws_products_filter', $filter_products, $relation, $this->form_id, $this->filter_id );

            if ( $product->is_type( 'variable' ) && method_exists( $product, 'get_children' ) ) {
                $filter_products = array_merge( $filter_products, $product->get_children() );
            }

            $product_ids = implode( ',', $filter_products );

            $string = "( id {$relation} ({$product_ids}) )";

            return $string;

        }

        /*
         * Product taxonomies terms rule
         */
        private function match_product_terms() {

            global $wpdb;

            $relation = $this->rule['operator'] === 'equal' ? 'IN' : 'NOT IN';

            /*
            * Taxonomies filter
            */
            $terms = apply_filters( 'aws_tax_filter', $this->rule['value'], $relation, $this->form_id, $this->filter_id );
            $terms = implode( ',', $terms );

            /**
             * Include or not child terms for tax filter
             * @since 1.75
             */
            $filter_tax_include_childs = apply_filters( 'aws_tax_filter_include_childs', true, $this->form_id, $this->filter_id );

            $include_childs = '';
            if ( $filter_tax_include_childs ) {
                $include_childs = " OR parent IN ({$terms}) OR parent IN ( SELECT term_id from {$wpdb->term_taxonomy} WHERE parent IN ({$terms}) )";
            }

            $string = "( id {$relation} (
                   SELECT $wpdb->posts.ID
                   FROM $wpdb->term_relationships
                   JOIN $wpdb->posts
                   ON ( $wpdb->term_relationships.object_id = $wpdb->posts.post_parent OR $wpdb->term_relationships.object_id = $wpdb->posts.ID )
                   WHERE $wpdb->term_relationships.term_taxonomy_id IN ( 
                       select term_taxonomy_id from $wpdb->term_taxonomy WHERE term_id IN ({$terms}) {$include_childs}
                   )
                ))";

            return $string;

        }

        /*
         * Product taxonomies rule
         */
        private function match_product_taxonomies() {

            global $wpdb;

            $taxonomies = implode( ',', $this->rule['value'] );
            $relation = $this->rule['operator'] === 'equal' ? 'IN' : 'NOT IN';

            $string = "( id {$relation} (
                   SELECT $wpdb->posts.ID
                   FROM $wpdb->term_relationships
                   JOIN $wpdb->posts
                   ON ( $wpdb->term_relationships.object_id = $wpdb->posts.post_parent OR $wpdb->term_relationships.object_id = $wpdb->posts.ID )
                   WHERE $wpdb->term_relationships.term_taxonomy_id IN ( 
                       select term_taxonomy_id from $wpdb->term_taxonomy WHERE taxonomy IN ({$taxonomies})
                   )
                ))";

            return $string;

        }

        /*
         * Product type rule
         */
        private function match_product_type() {

            global $wpdb;

            $type = $this->rule['value'];
            $relation = $this->rule['operator'] === 'equal' ? '=' : '!=';

            if ( $type === 'simple') {
                $string = "( type {$relation} 'product' )";
            } elseif ( $type === 'variable') {
                $string = "( type {$relation} 'var' )";
            } elseif ( $type === 'variation') {
                $string = "( type {$relation} 'child' )";
            } else {
                $relation = $this->rule['operator'] === 'equal' ? 'IN' : 'NOT IN';
                $string = "( id {$relation} (
                   SELECT $wpdb->posts.ID
                   FROM $wpdb->term_relationships
                   JOIN $wpdb->posts
                   ON ( $wpdb->term_relationships.object_id = $wpdb->posts.post_parent OR $wpdb->term_relationships.object_id = $wpdb->posts.ID )
                   WHERE $wpdb->term_relationships.term_taxonomy_id IN ( 
                       select term_taxonomy_id from $wpdb->term_taxonomy WHERE term_id IN (
                            SELECT term_id
                            FROM $wpdb->terms
                            WHERE slug = '{$type}'
                       )
                   )
                ))";
            }

            return $string;

        }

        /*
         * Product featured rule
         */
        private function match_product_featured() {

            global $wpdb;

            $is_featured = $this->rule['value'] === 'true';
            $is_featured = $this->rule['operator'] === 'equal' ? $is_featured : ! $is_featured;

            $relation = $is_featured ? 'IN' : 'NOT IN';

            $string = "( id {$relation} (
                   SELECT $wpdb->posts.ID
                   FROM $wpdb->term_relationships
                   JOIN $wpdb->posts
                   ON ( $wpdb->term_relationships.object_id = $wpdb->posts.post_parent OR $wpdb->term_relationships.object_id = $wpdb->posts.ID )
                   WHERE $wpdb->term_relationships.term_taxonomy_id IN ( 
                       select term_taxonomy_id from $wpdb->term_taxonomy WHERE term_id IN (
                            SELECT term_id
                            FROM $wpdb->terms
                            WHERE slug = 'featured'
                       )
                   )
                ))";

            return $string;

        }

        /*
         * Product visibility rule
         */
        private function match_product_visibility() {

            $visibility = $this->rule['value'];
            $relation = $this->rule['operator'] === 'equal' ? '=' : '!=';

            $string = "( visibility {$relation} '{$visibility}' )";

            return $string;

        }

        /*
         * Product sale status rule
         */
        private function match_product_sale_status() {

            $sale_val = $this->rule['value'] === 'true' ? '1' : '0';
            $relation = $this->rule['operator'] === 'equal' ? '=' : '!=';

            $string = "( on_sale {$relation} {$sale_val} )";

            return $string;

        }

        /*
         * Product stock status rule
         */
        private function match_product_stock_status() {

            $relation = $this->rule['operator'] === 'equal' ? 'IN' : 'NOT IN';

            switch ( $this->rule['value'] ) {
                case 'instock':
                    $val = 1;
                    break;
                case 'outofstock':
                    $val = 0;
                    break;
                default:
                    $val = 2;
            }

            $string = "( in_stock {$relation} ( {$val} ) )";

            return $string;

        }

        /*
         * Product meta rule
         */
        private function match_product_meta() {

            global $wpdb;

            $relation = $this->rule['operator'] === 'equal' ? 'IN' : 'NOT IN';
            $meta_name = $this->rule['suboption'];
            $meta_value = $this->rule['value'] === 'aws_any' ? '' : $this->rule['value'];

            if ( $meta_value ) {

                $string = "( id {$relation} (
                      SELECT post_id
                      FROM $wpdb->postmeta
                      WHERE meta_key = '{$meta_name}' AND meta_value = {$meta_value}
                ))";

            } else {

                $string = "( id {$relation} (
                      SELECT post_id
                      FROM $wpdb->postmeta
                      WHERE meta_key = '{$meta_name}'
                ))";

            }

            return $string;

        }

        /*
         * Product custom attributes rule
         */
        private function match_product_custom_attributes() {

            global $wpdb;

            $relation = $this->rule['operator'] === 'equal' ? 'IN' : 'NOT IN';
            $meta_name = $this->rule['suboption'];
            $meta_value = $this->rule['value'] === 'aws_any' ? '' : $this->rule['value'];

            if ( $meta_value ) {

                $string = $wpdb->prepare( "( id {$relation} (
                      SELECT post_id
                      FROM $wpdb->postmeta
                      WHERE meta_key = '_product_attributes' AND meta_value LIKE %s AND meta_value LIKE %s
                ))", '%' . $wpdb->esc_like( $meta_name ) . '%', '%' . $wpdb->esc_like( $meta_value ) . '%' );

            } else {

                $string = $wpdb->prepare( "( id {$relation} (
                      SELECT post_id
                      FROM $wpdb->postmeta
                      WHERE meta_key = '_product_attributes' AND meta_value LIKE %s
                ))", '%' . $wpdb->esc_like( $meta_name ) . '%' );

            }

            return $string;

        }

        /*
         * Terms pages term taxonomy rule
         */
        private function match_term_taxonomy() {

            global $wpdb;

            $relation = $this->rule['operator'] === 'equal' ? 'IN' : 'NOT IN';
            $taxonomy = $this->rule['suboption'];
            $term = $this->rule['value'] === 'aws_any' ? '' : $this->rule['value'];

            /**
             * Include or not child terms for tax filter
             * @since 1.75
             */
            $filter_tax_include_childs = apply_filters( 'aws_tax_filter_include_childs', true, $this->form_id, $this->filter_id );

            $include_childs = '';
            if ( $filter_tax_include_childs ) {
                $include_childs_operator = $this->rule['operator'] === 'equal' ? 'OR' : 'AND';
                $include_childs = " {$include_childs_operator} {$wpdb->term_taxonomy}.parent {$relation} ( {$term} ) {$include_childs_operator} {$wpdb->term_taxonomy}.parent {$relation} ( SELECT term_id from {$wpdb->term_taxonomy} WHERE parent IN ({$term}) )";
            }
            
            if ( $term ) {

                $string = "( {$wpdb->terms}.term_id {$relation} ( {$term} ) {$include_childs} )";

            } else {

                $string = "( {$wpdb->term_taxonomy}.taxonomy {$relation} ( '{$taxonomy}' ) )";

            }

            return $string;

        }

        /*
         * Terms pages products count rule
         */
        private function match_term_count() {

            switch ( $this->rule['operator'] ) {
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

            $count = intval( $this->rule['value'] );

            $string = "( count {$operator} {$count} )";

            return $string;

        }

        /*
         * Terms pages hierarchy type rule
         */
        private function match_term_hierarchy() {

            $is_top = $this->rule['value'] === 'top_parent';
            $is_top = $this->rule['operator'] === 'equal' ? $is_top : ! $is_top;

            $string = $is_top ? "( parent = 0 )" : "( parent != 0 )";

            return $string;

        }

        /*
         * Terms pages term has image rule
         */
        private function match_term_has_image() {

            global $wpdb;

            $relation = $this->rule['operator'] === 'equal' ? 'IN' : 'NOT IN';

            $string = "( {$wpdb->terms}.term_id {$relation} (
                      SELECT term_id
                      FROM $wpdb->termmeta
                      WHERE meta_key = 'thumbnail_id'
            ))";

            return $string;

        }

        /*
         * User pages: user rule
         */
        private function match_user_page_user() {

            $user_id = $this->rule['value'];
            $relation = $this->rule['operator'] === 'equal' ? 'IN' : 'NOT IN';

            $string = "(ID {$relation} ( {$user_id} ))";

            return $string;

        }

        /*
         * User pages: user role rule
         */
        private function match_user_page_role() {

            $relation = $this->rule['operator'] === 'equal' ? 'IN' : 'NOT IN';
            $string = '';
            $users_array = array();
            $users_args = array(
                'role__in' => $this->rule['value'],
            );

            $users = get_users( $users_args );

            if ( !is_wp_error( $users ) && $users && !empty( $users ) ) {
                foreach( $users as $user ) {
                    $users_array[] = $user->ID;
                }
            }

            if ( $users_array ) {
                $users_ids = implode( ',', $users_array );
                $string = "( ID {$relation} ( {$users_ids} ) )";
            }

           return $string;

        }

        /*
         * User pages: user products number rule
         */
        private function match_user_page_count() {

            global $wpdb;

            switch ( $this->rule['operator'] ) {
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

            $count = intval( $this->rule['value'] );

            $string = "( (
                SELECT COUNT(*)
                FROM {$wpdb->posts}
                WHERE {$wpdb->posts}.post_author = {$wpdb->users}.ID AND {$wpdb->posts}.post_type = 'product' AND {$wpdb->posts}.post_status = 'publish'
            ) {$operator} {$count} )";

            return $string;

        }

    }

endif;