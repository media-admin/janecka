<?php
/**
 * BeRocket WooCommerce Advanced Product Labels plugin integration
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( 'AWS_Berocket_Labels' ) ) :

    /**
     * Class
     */
    class AWS_Berocket_Labels {

        /**
         * Main AWS_Berocket_Labels Instance
         *
         * Ensures only one instance of AWS_Berocket_Labels is loaded or can be loaded.
         *
         * @static
         * @return AWS_Berocket_Labels - Main instance
         */
        protected static $_instance = null;

        public $form_id = 1;
        public $filter_id = 1;
        public $is_ajax = true;

        /**
         * Main AWS_Berocket_Labels Instance
         *
         * Ensures only one instance of AWS_Berocket_Labels is loaded or can be loaded.
         *
         * @static
         * @return AWS_Berocket_Labels - Main instance
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
            add_filter( 'aws_admin_page_options', array( $this, 'add_admin_options' ), 1 );
            add_filter( 'aws_search_pre_filter_products', array( $this, 'add_labels' ) );
        }

        /*
         * On search start
         */
        public function search_start( $s, $form_id, $filter_id  ) {
            $this->form_id = $form_id;
            $this->filter_id = $filter_id;
            $this->is_ajax = isset( $_GET['type_aws'] ) ? false : true;
        }

        public function add_labels( $products_array ) {

            if ( $this->is_ajax && $products_array ) {

                $show_labels = AWS_PRO()->get_settings( 'show_berocket_labels', $this->form_id,  $this->filter_id );
                if ( ! $show_labels ) {
                    $show_labels = 'excerpt';
                }

                if ( $show_labels !== 'no' ) {
                    foreach( $products_array as $key => $product_item ) {

                        ob_start();
                        do_action('berocket_apl_set_label', true, $product_item['id'] );
                        $label_markup = ob_get_contents();
                        ob_end_clean();

                        $labels = '<div class="aws-berocket-labels aws-berocket-labels-pos-' . $show_labels . '">' . $label_markup . '</div>';
                        $products_array[$key][$show_labels] .= $labels;

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
                                "name"  => __( "Show BeRocket Labels?", "advanced-woo-search" ),
                                "desc"  => __( "Show or not BeRocket plugin product labels for all products inside search results.", "advanced-woo-search" ),
                                "id"    => "show_berocket_labels",
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

    }

endif;

AWS_Berocket_Labels::instance();