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
            add_action( 'aws_search_start', array( $this, 'search_start' ), 10, 3  );
            add_filter( 'aws_admin_page_options', array( $this, 'add_admin_options' ) );
            add_filter( 'aws_search_pre_filter_products', array( $this, 'add_wishlist' ) );
            add_action( 'wp_footer', array( $this, 'wishlist_ajax' ) );
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
                        $wishlist = preg_replace( '/<a([\s\S]*?add_to_wishlist[\s\S]*?)<\/a>/', '<button$1</button>', $wishlist );
                        $wishlist = preg_replace( '/<a([\s\S]*?)<\/a>/', '<span style="text-decoration: underline;"$1</span>', $wishlist );
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
