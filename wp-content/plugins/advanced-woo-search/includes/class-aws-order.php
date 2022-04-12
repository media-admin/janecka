<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'AWS_Order' ) ) :

    /**
     * Class for products sorting
     */
    class AWS_Order {

        /**
         * @var AWS_Order Array of products
         */
        private $products = array();

        /**
         * Constructor
         */
        public function __construct( $products, $query ) {

            $this->products = $products;

            // Filter
            $this->filter_results( $query );

            // Order
            if ( $query->query && ( isset( $query->query['orderby'] ) || isset( $query->query_vars['orderby'] ) ) ) {
                $this->order( $query );
            }

        }

        /*
         * Filter search results
         */
        private function filter_results( $query ) {

            $new_products = array();
            $filters = array();
            $attr_filter = array();

            if ( isset( $query->query_vars['meta_query'] ) ) {
                $meta_query = $query->query_vars['meta_query'];

                if ( isset( $meta_query['price_filter'] ) && isset( $meta_query['price_filter']['value'] ) ) {
                    $price_values = $meta_query['price_filter']['value'];
                    if ( isset( $price_values[0] ) && isset( $price_values[1] ) ) {
                        $filters['price_min'] = $price_values[0];
                        $filters['price_max'] = $price_values[1];
                    }
                }

            }

            if ( ! isset( $filters['price_min'] ) && isset( $_GET['min_price'] ) ) {
                $filters['price_min'] = sanitize_text_field( $_GET['min_price'] );
            }

            if ( ! isset( $filters['price_max'] ) && isset( $_GET['max_price'] ) ) {
                $filters['price_max'] = sanitize_text_field( $_GET['max_price'] );
            }

            if ( isset( $_GET['rating_filter'] ) && $_GET['rating_filter'] ) {
                $filters['rating'] = explode( ',', sanitize_text_field( $_GET['rating_filter'] ) );
            }

            if ( isset( $_GET['filtering'] ) && $_GET['filtering'] && isset( $_GET['filter_product_brand'] ) ) {
                $filters['brand'] = explode( ',', sanitize_text_field( $_GET['filter_product_brand'] ) );
            }

            if ( isset( $query->query_vars['tax_query'] ) ) {
                $tax_query = $query->query_vars['tax_query'];

                if ( $tax_query && is_array( $tax_query ) && ! empty( $tax_query ) ) {
                    foreach( $tax_query as $taxonomy_query ) {
                        if ( is_array( $taxonomy_query ) ) {
                            if ( isset( $taxonomy_query['taxonomy'] ) && strpos( $taxonomy_query['taxonomy'], 'pa_' ) === 0 ) {
                                $tax_name = $taxonomy_query['taxonomy'];
                                $attr_filter[$tax_name] = $taxonomy_query;
                            }
                        }
                    }
                }

            }

            if ( empty( $attr_filter ) && class_exists('WC_Query') && method_exists( 'WC_Query', 'get_layered_nav_chosen_attributes' ) && count( WC_Query::get_layered_nav_chosen_attributes() ) > 0  ) {
                foreach ( WC_Query::get_layered_nav_chosen_attributes() as $taxonomy => $data ) {
                    $attr_filter[$taxonomy] = $data;
                }
            }


            /**
             * Filter available search page filters before apply
             * @since 2.04
             * @param array $filters Filters
             */
            $filters = apply_filters( 'aws_search_page_filters', $filters );


            foreach( $this->products as $post_array ) {

                if ( isset( $filters['in_status'] ) ) {
                    if ( $post_array['f_stock'] !== $filters['in_status'] ) {
                        continue;
                    }
                }

                if ( isset( $filters['on_sale'] ) ) {
                    if ( $post_array['f_sale'] !== $filters['on_sale'] ) {
                        continue;
                    }
                }

                if ( isset( $filters['price_min'] ) && isset( $filters['price_max'] ) ) {
                    if ( isset( $post_array['f_price'] ) && $post_array['f_price'] ) {
                        if ( $post_array['f_price'] > $filters['price_max'] || $post_array['f_price'] < $filters['price_min'] ) {
                            continue;
                        }
                    }
                }

                if ( isset( $filters['rating'] ) && is_array( $filters['rating'] ) ) {
                    if ( isset( $post_array['f_rating'] ) ) {
                        if ( array_search( floor( $post_array['f_rating'] ), $filters['rating'] ) === false ) {
                            continue;
                        }
                    }
                }

                if ( isset( $filters['brand'] ) && is_array( $filters['brand'] ) ) {

                    $skip = true;
                    $p_brands = get_the_terms( $post_array['id'], 'product_brand' );

                    if ( ! is_wp_error( $p_brands ) && ! empty( $p_brands ) ) {
                        foreach ( $p_brands as $p_brand ) {
                            if ( in_array( $p_brand->term_id,  $filters['brand'] ) ) {
                                $skip = false;
                                break;
                            }
                        }
                    }

                    if ( $skip ) {
                        continue;
                    }

                }

                if ( isset( $filters['tax'] ) && is_array( $filters['tax'] ) ) {

                    $skip = true;

                    foreach( $filters['tax'] as $taxonomy => $taxonomy_terms ) {

                        $terms = get_the_terms( $post_array['id'], $taxonomy );
                        $operator = isset( $taxonomy_terms['operator'] ) ? $taxonomy_terms['operator'] : 'OR';
                        $include_parent = isset( $taxonomy_terms['include_parent'] ) ? $taxonomy_terms['include_parent'] : false;
                        $term_arr = array();

                        if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                            foreach ( $terms as $term ) {
                                $term_arr[] = $term->term_id;
                                if ( $include_parent && $term->parent ) {
                                    $term_arr[] = $term->parent;
                                    $term_parent = get_term( $term->parent, $taxonomy );
                                    while ( ! is_wp_error( $term_parent ) && ! empty( $term_parent->parent ) && ! in_array( $term_parent->parent, $term_arr, true ) ) {
                                        $term_arr[]  = (int) $term_parent->parent;
                                        $term_parent = get_term( $term_parent->parent, $taxonomy );
                                    }
                                }
                            }
                        } elseif( strpos( $taxonomy, 'pa_' ) !== 0 ) {
                            $terms = get_the_terms( $post_array['id'], 'pa_' . $taxonomy );
                            if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                                foreach ( $terms as $term ) {
                                    $term_arr[] = $term->term_id;
                                    if ( $include_parent && $term->parent ) {
                                        $term_arr[] = $term->parent;
                                        $term_parent = get_term( $term->parent, $taxonomy );
                                        while ( ! is_wp_error( $term_parent ) && ! empty( $term_parent->parent ) && ! in_array( $term_parent->parent, $term_arr, true ) ) {
                                            $term_arr[]  = (int) $term_parent->parent;
                                            $term_parent = get_term( $term_parent->parent, $taxonomy );
                                        }
                                    }
                                }
                            }
                        }

                        $skip = AWS_Helpers::page_filter_tax( $term_arr, $taxonomy_terms['terms'], $operator );

                        if ( $skip ) {
                            break;
                        }

                    }

                    if ( $skip ) {
                        continue;
                    }

                }

                if ( $attr_filter && ! empty( $attr_filter ) && is_array( $attr_filter ) ) {

                    $product = wc_get_product( $post_array['id'] );
                    $attributes = $product->get_attributes();
                    $product_terms_array = array();
                    $skip = true;

                    if ( $attributes && ! empty( $attributes ) ) {

                        foreach( $attributes as $attr_name => $attribute_object ) {
                            if ( $attribute_object ) {
                                if ( ( is_object( $attribute_object ) && method_exists( $attribute_object, 'is_taxonomy' ) && $attribute_object->is_taxonomy() ) ||
                                    ( is_array( $attribute_object ) && isset( $attribute_object['is_taxonomy'] ) && $attribute_object['is_taxonomy'] )
                                ) {
                                    if ( isset( $attr_filter[$attr_name] ) ) {
                                        $product_terms = wp_get_object_terms( $post_array['id'], $attr_name );

                                        if ( ! is_wp_error( $product_terms ) && ! empty( $product_terms ) ) {
                                            foreach ( $product_terms as $product_term ) {
                                                $product_terms_array[] = $product_term->slug;
                                            }
                                        }

                                    }
                                }
                            }
                        }

                        if ( $product_terms_array ) {


                            foreach( $attr_filter as $attr_filter_name => $attr_filter_object ) {

                                $operator = isset( $attr_filter_object['operator'] ) ? $attr_filter_object['operator'] : ( isset( $attr_filter_object['query_type'] ) ? $attr_filter_object['query_type'] : 'OR' ) ;
                                $attr_filter_terms = $attr_filter_object['terms'];

                                $skip = AWS_Helpers::page_filter_tax( $product_terms_array, $attr_filter_terms, $operator );

                                if ( $skip ) {
                                    break;
                                }

                            }

                        }

                    }

                    if ( $skip ) {
                        continue;
                    }

                }

                $new_products[] = $post_array;

            }

            /**
             * Filter search results after search page filters applied
             * @since 2.04
             * @param array $new_products Products
             */
            $this->products = apply_filters( 'aws_products_search_page_filtered', $new_products );

        }

        /*
         * Sort products
         */
        private function order( $query ) {

            if ( isset( $query->query['orderby'] ) ) {

                $order_by = $query->query['orderby'];

            } else {

                $order_by = $query->query_vars['orderby'];

                if ( $order_by === 'meta_value_num' ) {
                    $order_by = 'price';
                }

                if ( isset( $query->query_vars['order'] ) ) {
                    $order_by = $order_by . '-' . strtolower( $query->query_vars['order'] );
                }

            }

            /**
             * Filter order by value
             * @since 2.13
             * @param string $order_by Order by value
             * @param object $query Page query
             */
            $order_by = apply_filters( 'aws_products_order_by', $order_by, $query );

            switch( $order_by ) {

                case 'price':
                case 'price-asc':

                    if ( isset( $this->products[0]['f_price'] ) ) {
                        usort( $this->products, array( $this, 'compare_price_asc' ) );
                    }

                    break;

                case 'price-desc':

                    if ( isset( $this->products[0]['f_price'] ) ) {
                        usort( $this->products, array( $this, 'compare_price_desc' ) );
                    }

                    break;

                case 'date':
                case 'date-desc':

                    if ( isset( $this->products[0]['post_data'] ) ) {
                        usort( $this->products, array( $this, 'compare_date' ) );
                    }

                    break;

                case 'date-asc':

                    if ( isset( $this->products[0]['post_data'] ) ) {
                        usort( $this->products, array( $this, 'compare_date_asc' ) );
                    }

                    break;

                case 'rating':
                case 'rating-desc':

                    if ( isset( $this->products[0]['f_rating'] ) ) {
                        usort( $this->products, array( $this, 'compare_rating' ) );
                    }

                    break;

                case 'rating-asc':

                    if ( isset( $this->products[0]['f_rating'] ) ) {
                        usort( $this->products, array( $this, 'compare_rating_asc' ) );
                    }

                    break;

                case 'popularity':
                case 'popularity-desc':

                    if ( isset( $this->products[0]['f_reviews'] ) ) {
                        usort( $this->products, array( $this, 'compare_reviews' ) );
                    }

                    break;

                case 'popularity-asc':

                    if ( isset( $this->products[0]['f_reviews'] ) ) {
                        usort( $this->products, array( $this, 'compare_reviews_asc' ) );
                    }

                    break;

                case 'title':
                case 'title-desc':

                    if ( isset( $this->products[0]['title'] ) ) {
                        usort( $this->products, array( $this, 'compare_title' ) );
                    }

                    break;

                case 'title-asc':

                    if ( isset( $this->products[0]['title'] ) ) {
                        usort( $this->products, array( $this, 'compare_title' ) );
                        $this->products = array_reverse($this->products);
                    }

                    break;

                case 'stock_quantity-asc':

                    if ( isset( $this->products[0]['id'] ) ) {
                        usort( $this->products, array( $this, 'compare_f_quantity_asc' ) );
                    }

                    break;

                case 'stock_quantity-desc':

                    if ( isset( $this->products[0]['id'] ) ) {
                        usort( $this->products, array( $this, 'compare_f_quantity_desc' ) );
                    }

                    break;

            }

            /**
             * Filter search results after ordering
             * @since 2.00
             * @param array $this->products Products
             * @param string $order_by Order by value
             */
            $this->products = apply_filters( 'aws_products_order', $this->products, $order_by );

        }

        /*
         * Compare price values asc
         */
        private function compare_price_asc( $a, $b ) {
            if ( ! is_numeric( $a['f_price'] ) || ! is_numeric( $b['f_price'] ) ) {
                return 0;
            }
            $a = intval( $a['f_price'] * 100 );
            $b = intval( $b['f_price'] * 100 );
            if ($a == $b) {
                return 0;
            }
            return ($a < $b) ? -1 : 1;
        }

        /*
         * Compare price values desc
         */
        private function compare_price_desc( $a, $b ) {
            if ( ! is_numeric( $a['f_price'] ) || ! is_numeric( $b['f_price'] ) ) {
                return 0;
            }
            $a = intval( $a['f_price'] * 100 );
            $b = intval( $b['f_price'] * 100 );
            if ($a == $b) {
                return 0;
            }
            return ($a < $b) ? 1 : -1;
        }

        /*
         * Compare date
         */
        private function compare_date( $a, $b ) {
            $a = strtotime( $a['post_data']->post_date );
            $b = strtotime( $b['post_data']->post_date );
            if ($a == $b) {
                return 0;
            }
            return ($a < $b) ? 1 : -1;
        }

        /*
         * Compare date desc
         */
        private function compare_date_asc( $a, $b ) {
            $a = strtotime( $a['post_data']->post_date );
            $b = strtotime( $b['post_data']->post_date );
            if ($a == $b) {
                return 0;
            }
            return ($a < $b) ? -1 : 1;
        }

        /*
         * Compare rating
         */
        private function compare_rating( $a, $b ) {
            $a = intval( $a['f_rating'] * 100 );
            $b = intval( $b['f_rating'] * 100 );
            if ($a == $b) {
                return 0;
            }
            return ($a < $b) ? 1 : -1;
        }

        /*
         * Compare rating asc
         */
        private function compare_rating_asc( $a, $b ) {
            $a = intval( $a['f_rating'] * 100 );
            $b = intval( $b['f_rating'] * 100 );
            if ($a == $b) {
                return 0;
            }
            return ($a < $b) ? -1 : 1;
        }

        /*
         * Compare popularity
         */
        private function compare_reviews( $a, $b ) {
            $a = intval( $a['f_reviews'] * 100 );
            $b = intval( $b['f_reviews'] * 100 );
            if ($a == $b) {
                return 0;
            }
            return ($a < $b) ? 1 : -1;
        }

        /*
         * Compare rating asc
         */
        private function compare_reviews_asc( $a, $b ) {
            $a = intval( $a['f_reviews'] * 100 );
            $b = intval( $b['f_reviews'] * 100 );
            if ($a == $b) {
                return 0;
            }
            return ($a < $b) ? -1 : 1;
        }

        /*
         * Compare title desc
         */
        private function compare_title( $a, $b ) {
            $res = strcasecmp( $a["title"], $b["title"] );
            return $res;
        }

        /*
         * Compare quantity values asc
         */
        private function compare_f_quantity_asc( $a, $b ) {

            $product_a = wc_get_product( $a['id'] );
            $product_b = wc_get_product( $b['id'] );

            if ( ! is_a( $product_a, 'WC_Product' ) || ! is_a( $product_b, 'WC_Product' ) ) {
                return 0;
            }

            $a_val = AWS_Helpers::get_quantity( $product_a );
            $b_val = AWS_Helpers::get_quantity( $product_b );

            if ($a_val == $b_val) {
                return 0;
            }

            return ($a_val < $b_val) ? -1 : 1;

        }

        /*
         * Compare quantity values desc
         */
        private function compare_f_quantity_desc( $a, $b ) {

            $product_a = wc_get_product( $a['id'] );
            $product_b = wc_get_product( $b['id'] );

            if ( ! is_a( $product_a, 'WC_Product' ) || ! is_a( $product_b, 'WC_Product' ) ) {
                return 0;
            }

            $a_val = AWS_Helpers::get_quantity( $product_a );
            $b_val = AWS_Helpers::get_quantity( $product_b );

            if ($a_val == $b_val) {
                return 0;
            }

            return ($a_val > $b_val) ? -1 : 1;

        }

        /*
         * Return array of sorted products
         */
        public function result() {

            return $this->products;

        }

    }

endif;