<?php
/**
 * YITH WooCommerce Wishlist plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( 'AWS_YITH_WISHLIST' ) ) :

    /**
     * Class
     */
    class AWS_YITH_WISHLIST {

        /**
         * Main AWS_YITH_WISHLIST Instance
         *
         * Ensures only one instance of AWS_YITH_WISHLIST is loaded or can be loaded.
         *
         * @static
         * @return AWS_YITH_WISHLIST - Main instance
         */
        protected static $_instance = null;

        public $form_id = 1;
        public $filter_id = 1;
        public $is_ajax = true;

        /**
         * Main AWS_YITH_WISHLIST Instance
         *
         * Ensures only one instance of AWS_YITH_WISHLIST is loaded or can be loaded.
         *
         * @static
         * @return AWS_YITH_WISHLIST - Main instance
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
            add_filter( 'aws_admin_filter_rules', array( $this, 'filter_rules' ), 1 );
            add_filter( 'aws_filters_condition_rules', array( $this, 'condition_rules' ), 1 );
            add_action( 'aws_search_start', array( $this, 'search_start' ), 10, 3  );
            add_filter( 'aws_admin_page_options', array( $this, 'add_admin_options' ) );
            add_filter( 'aws_search_pre_filter_products', array( $this, 'add_wishlist' ) );
            add_action( 'wp_footer', array( $this, 'wishlist_ajax' ) );
        }

        /*
         * Add new label conditions for admin
         */
        public function filter_rules( $options ) {

            $options['product'][] = array(
                "name" => __( "YITH Wishlist: Is product in the current user wishlist", "advanced-woo-search" ),
                "id"   => "product_yith_is_in_list",
                "type" => "bool",
                "operators" => "equals",
            );

            $options['product'][] = array(
                "name" => __( "YITH Wishlist: Times product was added to all wishlists", "advanced-woo-search" ),
                "id"   => "product_yith_times_added",
                "type" => "number",
                "operators" => "equals_compare",
            );

            return $options;

        }

        /*
         * Add custom condition rule method
         */
        public function condition_rules( $rules ) {
            $rules['product_yith_is_in_list'] = array( $this, 'yith_is_in_list' );
            $rules['product_yith_times_added'] = array( $this, 'yith_times_added' );
            return $rules;
        }

        /*
         * Condition: Is product in users wishlist
         */
        public function yith_is_in_list( $condition_rule ) {

            $is_in_wishlist = $condition_rule['value'] === 'true';
            $is_in_wishlist = $condition_rule['operator'] === 'equal' ? $is_in_wishlist : ! $is_in_wishlist;

            $relation = $is_in_wishlist ? 'IN' : 'NOT IN';

            $ids = array();
            $string = '';

            if ( function_exists( 'YITH_WCWL' ) ) {

                $products = YITH_WCWL()->get_products( array(
                    'wishlist_id' => 'all',
                    'wishlist_visibility' => 'all'
                ));

                foreach ( $products as $product ) {
                    $ids[] = $product['prod_id'];
                }

            }

            if ( ! empty( $ids ) ) {
                $product_ids = implode( ',', $ids );
                $string = "( id {$relation} ({$product_ids}) )";
            }

            return $string;

        }

        /*
         * Condition: Times product added to the wishlists
         */
        public function yith_times_added( $condition_rule ) {

            $operator = $condition_rule['operator'];
            $times_added = intval( $condition_rule['value'] );
            $ids = array();
            $string = '';

            if ( 'equal' == $operator ) {
                $compare_operator = '==';
            } elseif ( 'not_equal' == $operator ) {
                $compare_operator = '!=';
            } elseif ( 'greater' == $operator ) {
                $compare_operator = '>=';
            } elseif ( 'less' == $operator ) {
                $compare_operator = '<=';
            }

            try {
                $result = WC_Data_Store::load( 'wishlist-item' )->query_products();
                if ( $result ) {
                    foreach ( $result as $result_item ) {
                        $wishlist_count = intval( $result_item['wishlist_count'] );
                        if (version_compare($wishlist_count, $times_added, $compare_operator )) {
                            $ids[] = $result_item['id'];
                        }
                    }
                }
            } catch( Exception $e ){
                return 0;
            }

            if ( ! empty( $ids ) ) {
                $product_ids = implode( ',', $ids );
                $string = "( id IN ({$product_ids}) )";
            }

            return $string;

        }

        /*
         * On search start
         */
        public function search_start( $s, $form_id, $filter_id  ) {
            $this->form_id = $form_id;
            $this->filter_id = $filter_id;
            $this->is_ajax = isset( $_GET['type_aws'] ) ? false : true;
        }

        public function add_wishlist( $products_array ) {

            if ( $this->is_ajax && $products_array ) {

                $show_wishlist = AWS_PRO()->get_settings( 'show_yith_wishlist', $this->form_id,  $this->filter_id );
                if ( ! $show_wishlist ) {
                    $show_wishlist = 'excerpt';
                }

                if ( $show_wishlist !== 'no' ) {
                    foreach( $products_array as $key => $product_item ) {
                        $wishlist = '<div class="woocommerce aws-wishlist">' . do_shortcode( '[yith_wcwl_add_to_wishlist parent_product_id="' . $product_item['parent_id'] . '" product_id="' . $product_item['id'] . '"]' ) . '</div>';
                        $wishlist = preg_replace( '/<a( href="[?|#]add_to_wishlist[\s\S]*?)<\/a>/', '<button style="z-index:2;" $1</button>', $wishlist );
                        $products_array[$key][$show_wishlist] .= $wishlist;
                    }
                }

            }

            return $products_array;

        }

        /*
         * Add wishlist admin options
         */
        public function add_admin_options( $options ) {

            $new_options = array();

            if ( $options ) {
                foreach ( $options as $section_name => $section ) {
                    foreach ( $section as $values ) {

                        $new_options[$section_name][] = $values;

                        if ( isset( $values['id'] ) && $values['id'] === 'show_stock' ) {

                            $new_options[$section_name][] = array(
                                "name"  => __( "Show YITH Wishlist?", "advanced-woo-search" ),
                                "desc"  => __( "Show or not YITH Wishlist for all products inside search results.", "advanced-woo-search" ),
                                "id"    => "show_yith_wishlist",
                                "inherit" => "true",
                                "value" => 'no',
                                "type"  => "radio",
                                'choices' => array(
                                    'excerpt' => __( 'Show after content', 'advanced-woo-search' ),
                                    'price'   => __( 'Show after price', 'advanced-woo-search' ),
                                    'title'   => __( 'Show after title', 'advanced-woo-search' ),
                                    'no'      => __( 'Not show', 'advanced-woo-search' )
                                )
                            );

                        }

                    }
                }

                return $new_options;

            }

            return $options;

        }

        /*
         * Fix for wishlist AJAX load
         */
        public function wishlist_ajax() {

            if ( 'no' == get_option( 'yith_wcwl_ajax_enable', 'no' ) ) {
                return;
            }

            ?>

            <script>
                window.addEventListener( "awsShowingResults", function(e) {
                    jQuery('.aws-search-result').trigger('yith_wcwl_reload_fragments');
                }, false);
            </script>

        <?php }

    }

endif;
