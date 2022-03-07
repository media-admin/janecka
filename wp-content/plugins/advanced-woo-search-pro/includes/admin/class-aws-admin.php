<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


if ( ! class_exists( 'AWS_Admin' ) ) :

/**
 * Class for plugin admin panel
 */
class AWS_Admin {

    /*
     * Name of the plugin settings page
     */
    var $page_name = 'aws-options';

    /**
     * @var AWS_Admin The single instance of the class
     */
    protected static $_instance = null;

    /**
     * Main AWS_Admin Instance
     *
     * Ensures only one instance of AWS_Admin is loaded or can be loaded.
     *
     * @static
     * @return AWS_Admin - Main instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /*
    * Constructor
    */
    public function __construct() {

        add_action( 'admin_menu', array( &$this, 'add_admin_page' ) );
        add_action( 'admin_init', array( &$this, 'register_settings' ) );

        add_filter( 'aws_admin_page_options_current', array( $this, 'check_sources_in_index' ), 1 );

        if ( ! AWS_Admin_Options::get_settings() ) {
            $default_settings = AWS_Admin_Options::get_default_settings();
            update_option( 'aws_pro_settings', array( '1' => $default_settings ) );
        }

        if ( ! AWS_Admin_Options::get_common_settings() ) {
            $default_common_settings = AWS_Admin_Options::get_default_settings( 'performance', false );
            update_option( 'aws_pro_common_opts', $default_common_settings );
        }

        if ( ! get_option( 'aws_instances' ) ) {
            add_option( 'aws_instances', '1', '', 'no' );
        }

        add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ) );

        add_action( 'admin_notices', array( $this, 'display_welcome_header' ), 1 );

        add_action( 'admin_notices', array( $this, 'display_reindex_message' ), 1 );

        add_action( 'aws_admin_change_state', array( $this, 'disable_not_indexed_sources' ), 1, 3 );

    }

    /*
     * Add options page
     */
    public function add_admin_page() {
        add_menu_page( esc_html__( 'Adv. Woo Search', 'advanced-woo-search' ), esc_html__( 'Adv. Woo Search', 'advanced-woo-search' ), 'manage_options', 'aws-options', array( &$this, 'display_admin_page' ), 'dashicons-search', 70 );
    }

    /**
     * Generate and display options page
     */
    public function display_admin_page() {

        $nonce = wp_create_nonce( 'plugin-settings' );

        $instance_id = isset( $_GET['aws_id'] ) ? (int) sanitize_text_field( $_GET['aws_id'] ) : 0;
        $filter_id   = isset( $_GET['filter'] ) ? (int) sanitize_text_field( $_GET['filter'] ) : 1;

        $settings = AWS_Admin_Options::get_settings();

        $tabs = array(
            'general' => __( 'General', 'advanced-woo-search' ),
            'form'    => __( 'Search Form', 'advanced-woo-search' ),
            'results' => __( 'Search Results', 'advanced-woo-search' )
        );

        $current_tab = empty( $_GET['tab'] ) ? 'general' : sanitize_text_field( $_GET['tab'] );

        $tabs_html = '';

        foreach ( $tabs as $name => $label ) {
            $tabs_html .= '<a href="' . esc_url( admin_url( 'admin.php?page=aws-options&tab=' . $name . '&aws_id=' . $instance_id ) ) . '" class="nav-tab ' . ( $current_tab == $name ? 'nav-tab-active' : '' ) . '">' . esc_html( $label ) . '</a>';
        }

        $tabs_html = '<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">'.$tabs_html.'</h2>';


        echo '<div class="wrap">';

            echo '<h1></h1>';

            echo '<form action="" name="aws_form" id="aws_form" method="post">';

                if ( ! $instance_id ) {

                    if ( isset( $_POST["stopwords"] ) ) {
                        update_option( 'aws_pro_stopwords', $_POST["stopwords"], 'no' );
                    }

                    if ( isset( $_POST["synonyms"] ) ) {
                        update_option( 'aws_pro_synonyms', $_POST["synonyms"], 'no' );
                    }

                    if ( isset( $_POST["seamless"] ) ) {
                        update_option( 'aws_pro_seamless', $_POST["seamless"] );
                    }

                    if ( isset( $_POST["Submit"] ) && current_user_can( 'manage_options' ) && isset( $_POST["_wpnonce"] ) && wp_verify_nonce( $_POST["_wpnonce"], 'plugin-settings' ) ) {
                        AWS_Admin_Options::update_common_settings();
                    }

                    $common_settings = AWS_Admin_Options::get_common_settings();

                    echo '<h1>Advanced Woo Search</h1>';

                    echo AWS_Admin_Meta_Boxes::get_general_tabs();

                    switch ( $current_tab ) {
                        case( 'performance'  ):
                            new AWS_Admin_Fields( 'performance', $common_settings );
                            break;
                        default:
                            echo AWS_Admin_Meta_Boxes::get_instances_table();
                            echo AWS_Admin_Meta_Boxes::get_general_tab_content();
                    }

                } else {

                    $instance_settings = $settings[$instance_id];
                    $instance_name = $instance_settings['search_instance'];

                    if ( empty( $instance_settings ) ) {
                        echo esc_html__( 'No such instance!', 'advanced-woo-search' );
                        return;
                    }

                    if ( isset( $_POST["Submit"] ) && current_user_can( 'manage_options' ) && isset( $_POST["_wpnonce"] ) && wp_verify_nonce( $_POST["_wpnonce"], 'plugin-settings' ) ) {
                        AWS_Admin_Options::update_settings();
                    }


                    $plugin_options = AWS_Admin_Options::get_settings();
                    $plugin_options = $plugin_options[$instance_id];


                    echo '<a class="button aws-back" href="' . admin_url( 'admin.php?page=aws-options' ) . '" title="' . esc_attr__( 'Back', 'advanced-woo-search' ) . '">' . esc_html__( 'Back', 'advanced-woo-search' ) . '</a>';

                    echo '<h1 data-id="' . esc_attr( $instance_id ) . '" class="aws-instance-name">' . esc_html( $instance_name ) . '</h1>';

                    if ( $current_tab === 'results' && $filter_id ) {
                        $filters = $plugin_options['filters'];
                        echo '<h2 class="aws-instance-filter">"' . esc_html( $filters[$filter_id]['filter_name'] ) . '" filter</h2>';
                    }

                    echo '<div class="aws-instance-shortcode">[aws_search_form id="' . $instance_id . '"]</div>';

                    echo $tabs_html;

                    echo '<input type="hidden" name="aws_instance" value="' . esc_attr( $instance_id ) . '" />';

                    switch ($current_tab) {
                        case('results'):
                            $this->filters_tabs( $plugin_options['filters'] );
                            new AWS_Admin_Fields( 'results', $plugin_options['filters'][$filter_id] );
                            break;
                        case('form'):
                            new AWS_Admin_Fields( 'form', $plugin_options );
                            break;
                        default:
                            new AWS_Admin_Fields( 'general', $plugin_options );
                    }

                }

                echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $nonce ) . '">';

            echo '</form>';

        echo '</div>';

    }

    /*
     * Generate filters tabs html
     */
    private function filters_tabs( $filters ) {

        if ( isset( $_GET['section'] ) ) {
            return;
        }

        $instance_id       = isset( $_GET['aws_id'] ) ? (int) sanitize_text_field( $_GET['aws_id'] ) : 0;
        $current_filter_id = isset( $_GET['filter'] ) ? (int) sanitize_text_field( $_GET['filter'] ) : 1;

        echo '<table class="aws-table aws-form-filters widefat" cellspacing="0">';

            echo '<thead>';

                echo '<tr>';
                    echo '<th class="aws-sort">&nbsp;</th>';
                    echo '<th class="aws-name">' . esc_html__( 'Filter Name', 'advanced-woo-search' ) . '</th>';
                    echo '<th class="aws-actions"></th>';
                echo '</tr>';

            echo '</thead>';

            echo '<tbody>';

            foreach ( $filters as $filter_id => $filter_opts ) {

                $instance_page = admin_url( 'admin.php?page=aws-options&tab=results&aws_id=' . $instance_id . '&filter=' . $filter_id );

                echo '<tr class="aws-filter-item ' . ( 1 == $filter_id ? 'disabled' : '' ) . '" data-instance="' . esc_attr( $instance_id ) . '" data-id="' . esc_attr( $filter_id ) . '">';

                    if ( $filter_id != 1 ) {
                        echo '<td class="aws-sort"></td>';
                        //echo '<input type="hidden" name="filter_order[]" value="' . $filter_id . '">';
                    } else {
                        echo '<td></td>';
                        //echo '<input type="hidden" name="filter_order[]" value="1">';
                    }

                    echo '<td class="aws-name">';
                        echo '<a href="' . $instance_page . '" class="' . ( $current_filter_id == $filter_id ? 'active' : '' ) . '">' . $filter_opts['filter_name'] . '</a>';
                        if ( $current_filter_id == $filter_id ) {
                            echo '<span class="aws-current-filter">(' . esc_html__( 'editing', 'advanced-woo-search' ) . ')</span>';
                        }
                    echo '</td>';

                    echo '<td class="aws-actions">';

                        if ( $filter_id != 1 ) {
                            echo '<a class="button alignright tips delete" title="Delete" data-instance="' . esc_attr( $instance_id ) . '" data-id="' . esc_attr( $filter_id ) . '" href="#">' . esc_html__('Delete', 'advanced-woo-search') . '</a>';
                        }

                        echo '<a class="button alignright tips copy" title="Copy" data-instance="' . esc_attr( $instance_id ) . '" data-id="' . esc_attr( $filter_id ) . '" href="#">' . esc_html__( 'Copy', 'advanced-woo-search' ) . '</a>';

                    echo '</td>';

                echo '</tr>';

            }

            echo '</tbody>';

        echo '</table>';

        echo '<div class="aws-insert-filter-box">';
            echo '<button class="button aws-insert-filter" data-instance="' . esc_attr( $instance_id ) . '">' . esc_html__( 'Add New Filter', 'advanced-woo-search' ) . '</button>';
        echo '</div>';

        echo '<ul class="aws-sub">';
            echo '<li><a href="#general">' . __( "General", "advanced-woo-search" ) . '</a> | </li>';
            echo '<li><a href="#sources">' . __( "Search Sources", "advanced-woo-search" ) . '</a> | </li>';
            echo '<li><a href="#view">' . __( "View", "advanced-woo-search" ) . '</a> | </li>';
            echo '<li><a href="#excludeinclude">' . __( "Filter Results", "advanced-woo-search" ) . '</a></li>';
        echo '</ul>';

    }

    /*
	 * Register plugin settings
	 */
    public function register_settings() {
        register_setting( 'aws_pro_settings', 'aws_pro_settings' );
    }

    /*
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts() {

        if ( isset( $_GET['page'] ) && $_GET['page'] == 'aws-options' ) {

            $instance_id = isset( $_GET['aws_id'] ) ? (int) sanitize_text_field( $_GET['aws_id'] ) : 0;
            $filter_id   = isset( $_GET['filter'] ) ? (int) sanitize_text_field( $_GET['filter'] ) : 1;

            wp_enqueue_style( 'plugin-admin-style', AWS_PRO_URL . 'assets/css/admin.css', array(), 'pro' . AWS_PRO_VERSION );
            wp_enqueue_style( 'aws-select2', AWS_PRO_URL . '/assets/css/select2.min.css' );

            wp_enqueue_script( 'jquery' );
            wp_enqueue_script( 'jquery-ui-sortable' );
            wp_enqueue_script( 'select2' );
            wp_enqueue_media();

            wp_enqueue_script( 'aws-admin', AWS_PRO_URL . 'assets/js/admin.js', array('jquery', 'jquery-ui-sortable', 'select2'), 'pro' . AWS_PRO_VERSION );

            wp_localize_script( 'aws-admin', 'aws_vars', array(
                'ajaxurl'    => admin_url( 'admin-ajax.php', 'relative' ),
                'ajax_nonce' => wp_create_nonce( 'aws_pro_admin_ajax_nonce' ),
                'instance'   => $instance_id,
                'filter'     => $filter_id
            ) );

        }

    }

    /*
     * Add welcome notice
     */
    public function display_welcome_header() {

        if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'aws-options' ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $hide_notice = get_option( 'aws_hide_welcome_notice' );

        if ( ! $hide_notice || $hide_notice === 'true' ) {
            return;
        }

        echo AWS_Admin_Meta_Boxes::get_welcome_notice();

    }

    /*
     * Check if some sources for disabled from index
     */
    public function check_sources_in_index( $options ) {

        if ( $options ) {

            $index_options = AWS_Helpers::get_index_options();
            $common_settings = AWS_Admin_Options::get_common_settings();
            $reindex_version = get_option( 'aws_pro_reindex_version' );

            foreach( $options as $options_key => $options_tab ) {
                foreach( $options_tab as $key => $option ) {

                    if ( isset( $option['id'] ) && $option['id'] === 'search_in' && isset( $option['choices'] ) ) {
                        foreach( $option['choices'] as $choice_key => $choice_label ) {
                            if ( isset( $index_options['index'][$choice_key] ) && ! $index_options['index'][$choice_key] ) {
                                $text = '<span style="color:#dc3232;">' . __( '(index disabled)', 'advanced-woo-search' ) . '</span>' . ' <a href="'.esc_url( admin_url('admin.php?page=aws-options&tab=performance#index_sources') ).'">' . __( '(enable)', 'advanced-woo-search' ) . '</a>';
                                if ( is_array( $choice_label ) ) {
                                    $options[$options_key][$key]['choices'][$choice_key] = $choice_label['label'] . ' ' . $text;
                                } else {
                                    $options[$options_key][$key]['choices'][$choice_key] = $choice_label . ' ' . $text;
                                }
                            }
                        }
                    }

                    if ( isset( $option['id'] ) && $option['id'] === 'search_in_attr' && isset( $option['choices'] ) ) {
                        foreach( $option['choices'] as $choice_key => $choice_label ) {
                            if ( ( isset( $index_options['index']['attr_sources'][$choice_key] ) && ! $index_options['index']['attr_sources'][$choice_key] ) || ! isset( $index_options['index']['attr_sources'][$choice_key] ) ) {
                                $text = '<span style="color:#dc3232;">' . __( '(index disabled)', 'advanced-woo-search' ) . '</span>' . ' <a href="'.esc_url( admin_url('admin.php?page=aws-options&tab=performance&section=attr') ).'">' . __( '(enable)', 'advanced-woo-search' ) . '</a>';
                                $options[$options_key][$key]['choices'][$choice_key] = $choice_label . ' ' . $text;
                            }
                        }
                    }

                    if ( isset( $option['id'] ) && $option['id'] === 'search_in_tax' && isset( $option['choices'] ) ) {
                        foreach( $option['choices'] as $choice_key => $choice_label ) {
                            if ( ( isset( $index_options['index']['tax_sources'][$choice_key] ) && ! $index_options['index']['tax_sources'][$choice_key] ) || ! isset( $index_options['index']['tax_sources'][$choice_key] ) ) {
                                $text = '<span style="color:#dc3232;">' . __( '(index disabled)', 'advanced-woo-search' ) . '</span>' . ' <a href="'.esc_url( admin_url('admin.php?page=aws-options&tab=performance&section=tax') ).'">' . __( '(enable)', 'advanced-woo-search' ) . '</a>';
                                $options[$options_key][$key]['choices'][$choice_key] = $choice_label . ' ' . $text;
                            }
                        }
                    }

                    if ( isset( $option['id'] ) && $option['id'] === 'search_in_meta' && isset( $option['choices'] ) ) {
                        foreach( $option['choices'] as $choice_key => $choice_label ) {
                            if ( ( isset( $index_options['index']['meta_sources'][$choice_key] ) && ! $index_options['index']['meta_sources'][$choice_key] ) || ! isset( $index_options['index']['meta_sources'][$choice_key] ) ) {
                                $text = '<span style="color:#dc3232;">' . __( '(index disabled)', 'advanced-woo-search' ) . '</span>' . ' <a href="'.esc_url( admin_url('admin.php?page=aws-options&tab=performance&section=meta') ).'">' . __( '(enable)', 'advanced-woo-search' ) . '</a>';
                                $options[$options_key][$key]['choices'][$choice_key] = $choice_label . ' ' . $text;
                            }
                        }
                    }

                    // If PRO version update after adding new index sources feature
                    if ( $reindex_version && ! $common_settings ) {
                        $options = AWS_Admin_Helpers::filter_index_sources_on_update( $options, $options_key, $key, $option );
                    }

                }
            }

        }

        return $options;

    }

    /*
     * Disable sources that was excluded from index
     */
    public function disable_not_indexed_sources( $setting, $option, $state ) {

        $allowed_options = array(
            'index_sources'      => 'search_in',
            'index_sources_attr' => 'search_in_attr',
            'index_sources_tax'  => 'search_in_tax',
            'index_sources_meta' => 'search_in_meta'
        );

        if ( isset( $allowed_options[$setting] ) && $state ) {
            $settings = AWS_Admin_Options::get_settings();
            $option_to_update = $allowed_options[$setting];

            if ( $settings ) {

                foreach ($settings as $search_instance_num => $search_instance_settings) {
                    if ( isset( $search_instance_settings['filters'] ) ) {
                        foreach( $search_instance_settings['filters'] as $filter_num => $filter_settings ) {
                            $settings[$search_instance_num]['filters'][$filter_num][$option_to_update][$option] = 0;
                        }
                    }
                }

                update_option( 'aws_pro_settings', $settings );

            }

        }

    }

    /*
     * Add reindex notice after index options change
     */
    public function display_reindex_message() {

        if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'aws-options' ) {
            return;
        }

        if ( ! isset( $_POST["Submit"] ) || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( isset( $_POST["index_variations"] ) || isset( $_POST["search_rule"] ) ) {
            echo AWS_Admin_Meta_Boxes::get_reindex_notice();
        }

    }

}

endif;


add_action( 'init', 'AWS_Admin::instance' );