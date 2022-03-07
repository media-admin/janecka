<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'AWS_Admin_Ajax' ) ) :

    /**
     * Class for plugin admin ajax hooks
     */
    class AWS_Admin_Ajax {

        /*
         * Constructor
         */
        public function __construct() {

            add_action( 'wp_ajax_aws-renameForm', array( &$this, 'rename_form' ) );

            add_action( 'wp_ajax_aws-copyForm', array( &$this, 'copy_form' ) );

            add_action( 'wp_ajax_aws-deleteForm', array( &$this, 'delete_form' ) );

            add_action( 'wp_ajax_aws-addForm', array( &$this, 'add_form' ) );

            add_action( 'wp_ajax_aws-addFilter', array( &$this, 'add_filter' ) );

            add_action( 'wp_ajax_aws-copyFilter', array( &$this, 'copy_filter' ) );

            add_action( 'wp_ajax_aws-deleteFilter', array( &$this, 'delete_filter' ) );

            add_action( 'wp_ajax_aws-orderFilter', array( &$this, 'order_filter' ) );

            add_action( 'wp_ajax_aws-changeState', array( &$this, 'change_state' ) );

            add_action( 'wp_ajax_aws-hideWelcomeNotice', array( $this, 'hide_welcome_notice' ) );

            add_action( 'wp_ajax_aws-getRuleGroup', array( $this, 'get_rule_group' ) );

            add_action( 'wp_ajax_aws-getSuboptionValues', array( $this, 'get_suboption_values' ) );

            add_action( 'wp_ajax_aws-searchForProducts', array( $this, 'search_for_products' ) );

        }

        /*
         * Ajax hook for form renaming
         */
        public function rename_form() {

            check_ajax_referer( 'aws_pro_admin_ajax_nonce' );

            $instance_id = sanitize_text_field( $_POST['id'] );
            $form_name   = sanitize_text_field( $_POST['name'] );

            $settings = $this->get_settings();

            $settings[$instance_id]['search_instance'] = $form_name;

            update_option( 'aws_pro_settings', $settings );

            wp_send_json_success( '1' );

        }

        /*
         * Ajax hook for form coping
         */
        public function copy_form() {

            check_ajax_referer( 'aws_pro_admin_ajax_nonce' );

            $instance_id = sanitize_text_field( $_POST['id'] );

            $instances_number = get_option( 'aws_instances' );
            $instances_number++;

            $settings = $this->get_settings();
            $instance_settings = $settings[$instance_id];

            $instance_settings['search_instance'] = $instance_settings['search_instance'] . ' (copy)';

            $settings[$instances_number] = $instance_settings;

            update_option( 'aws_instances', $instances_number, 'no' );
            update_option( 'aws_pro_settings', $settings );

            /**
             * Fires after search form instance was create/copy/delete
             *
             * @since 1.33
             *
             * @param array $settings Array of plugin settings
             * @param string $ Action type
             * @param string $instance_id Form instance id
             */
            do_action( 'aws_form_changed', $settings, 'copy_form', $instance_id );

            wp_send_json_success( '1' );

        }

        /*
         * Ajax hook for form deleting
         */
        public function delete_form() {

            check_ajax_referer( 'aws_pro_admin_ajax_nonce' );

            $instance_id = sanitize_text_field( $_POST['id'] );

            $settings = $this->get_settings();

            unset( $settings[$instance_id] );

            update_option( 'aws_pro_settings', $settings );

            /**
             * Fires after search form instance was create/copy/delete
             *
             * @since 1.33
             *
             * @param array $settings Array of plugin settings
             * @param string $ Action type
             * @param string $instance_id Form instance id
             */
            do_action( 'aws_form_changed', $settings, 'delete_form', $instance_id );

            do_action( 'aws_cache_clear', $instance_id );

            wp_send_json_success( '1' );

        }

        /*
         * Ajax hook for form adding
         */
        public function add_form() {

            check_ajax_referer( 'aws_pro_admin_ajax_nonce' );

            $instances_number = get_option( 'aws_instances' );
            $instances_number++;

            $settings = $this->get_settings();

            $default_settings = AWS_Admin_Options::get_default_settings();

            $settings[$instances_number] = $default_settings;

            update_option( 'aws_instances', $instances_number, 'no' );
            update_option( 'aws_pro_settings', $settings );

            /**
             * Fires after search form instance was create/copy/delete
             *
             * @since 1.33
             *
             * @param array $settings Array of plugin settings
             * @param string $ Action type
             * @param string $instance_id Form instance id
             */
            do_action( 'aws_form_changed', $settings, 'add_form', $instances_number );

            wp_send_json_success( '1' );

        }

        /*
         * Ajax hook for filter adding
         */
        public function add_filter() {

            check_ajax_referer( 'aws_pro_admin_ajax_nonce' );

            $instance_id = sanitize_text_field( $_POST['instanceId'] );

            $settings = $this->get_settings();
            $filter_id = ++$settings[$instance_id]['filter_num'];

            $default_settings = AWS_Admin_Options::get_default_settings( 'results' );

            if ( isset( $default_settings['filters'] ) ) {
                foreach ( $default_settings['filters']['1'] as $setting_name => $setting_value ) {
                    $settings[$instance_id]['filters'][$filter_id][$setting_name] = $setting_value;
                }
            }

            $settings[$instance_id]['filters'][$filter_id]['filter_name'] = __( 'New Filter', 'advanced-woo-search' );

            update_option( 'aws_pro_settings', $settings );

            /**
             * Fires after search form filter was create/copy/delete
             *
             * @since 1.33
             *
             * @param array $settings Array of plugin settings
             * @param string $ Action type
             * @param string $instance_id Form instance id
             * @param string $filter_id Filter id
             */
            do_action( 'aws_filters_changed', $settings, 'add_filter', $instance_id, $filter_id );

            wp_send_json_success( '1' );

        }

        /*
         * Ajax hook for filter coping
         */
        public function copy_filter() {

            check_ajax_referer( 'aws_pro_admin_ajax_nonce' );

            $instance_id = sanitize_text_field( $_POST['instanceId'] );
            $filter      = sanitize_text_field( $_POST['filterId'] );

            $settings = $this->get_settings();
            $filter_id = ++$settings[$instance_id]['filter_num'];

            $filter_settings = $settings[$instance_id]['filters'][$filter];

            $filter_settings['filter_name'] = $filter_settings['filter_name'] . ' (copy)';

            $settings[$instance_id]['filters'][$filter_id] = $filter_settings;

            update_option( 'aws_pro_settings', $settings );

            /**
             * Fires after search form filter was create/copy/delete
             *
             * @since 1.33
             *
             * @param array $settings Array of plugin settings
             * @param string $ Action type
             * @param string $instance_id Form instance id
             * @param string $filter_id Filter id
             */
            do_action( 'aws_filters_changed', $settings, 'copy_filter', $instance_id, $filter );

            wp_send_json_success( '1' );

        }

        /*
         * Ajax hook for filter deleting
         */
        public function delete_filter() {

            check_ajax_referer( 'aws_pro_admin_ajax_nonce' );

            $instance_id = sanitize_text_field( $_POST['instanceId'] );
            $filter      = sanitize_text_field( $_POST['filterId'] );

            $settings = $this->get_settings();

            unset( $settings[$instance_id]['filters'][$filter] );

            update_option( 'aws_pro_settings', $settings );

            /**
             * Fires after search form filter was create/copy/delete
             *
             * @since 1.33
             *
             * @param array $settings Array of plugin settings
             * @param string $ Action type
             * @param string $instance_id Form instance id
             * @param string $filter_id Filter id
             */
            do_action( 'aws_filters_changed', $settings, 'delete_filter', $instance_id, $filter );

            do_action( 'aws_cache_clear', $instance_id, $filter );

            wp_send_json_success( '1' );

        }

        /*
         * Ajax hook for filter deleting
         */
        public function order_filter() {

            check_ajax_referer( 'aws_pro_admin_ajax_nonce' );

            $instance_id = sanitize_text_field( $_POST['instanceId'] );
            $order       = sanitize_text_field( $_POST['order'] );

            $order = json_decode( $order );

            $settings = $this->get_settings();

            $filters = $settings[$instance_id]['filters'];

            $new_filters_array = array();

            foreach ( $order as $filter_id ) {
                $new_filters_array[$filter_id] = $filters[$filter_id];
            }

            $settings[$instance_id]['filters'] = $new_filters_array;

            update_option( 'aws_pro_settings', $settings );

            wp_send_json_success( '1' );

        }

        /*
         * Change option state
         */
        public function change_state() {

            check_ajax_referer( 'aws_pro_admin_ajax_nonce' );

            $instance_id = isset( $_POST['instanceId'] ) ? sanitize_text_field( $_POST['instanceId'] ) : 0;
            $filter      = isset( $_POST['filterId'] ) ? sanitize_text_field( $_POST['filterId'] ) : 0;
            $setting     = sanitize_text_field( $_POST['setting'] );
            $option      = sanitize_text_field( $_POST['option'] );
            $state       = sanitize_text_field( $_POST['state'] );

            if ( $instance_id && $filter ) {
                $settings = $this->get_settings();
                $settings[$instance_id]['filters'][$filter][$setting][$option] = $state ? 0 : 1;
                update_option( 'aws_pro_settings', $settings );
            } else {
                $common_settings = AWS_PRO()->get_common_settings();
                $common_settings[$setting][$option] = $state ? 0 : 1;
                update_option( 'aws_pro_common_opts', $common_settings );
            }

            do_action( 'aws_cache_clear', $instance_id, $filter );

            do_action( 'aws_admin_change_state', $setting, $option, $state );

            wp_send_json_success( '1' );

        }

        /*
         * Hide plugin welcome notice
         */
        public function hide_welcome_notice() {

            check_ajax_referer( 'aws_pro_admin_ajax_nonce' );

            update_option( 'aws_hide_welcome_notice', 'true', false );

            wp_send_json_success( '1' );

        }

        /*
         * Ajax hook for rule groups
         */
        public function get_rule_group() {

            check_ajax_referer( 'aws_pro_admin_ajax_nonce' );

            $name = sanitize_text_field( $_POST['name'] );
            $section = sanitize_text_field( $_POST['section'] );
            $group_id = sanitize_text_field( $_POST['groupID'] );
            $rule_id = sanitize_text_field( $_POST['ruleID'] );

            $rules = AWS_Admin_Options::include_filters();
            $html = array();

            foreach ( $rules as $rule_section => $section_rules ) {
                foreach ( $section_rules as $rule ) {
                    if ( $rule['id'] === $name ) {

                        $rule_obj = new AWS_Admin_Filters( $rule, $section, $group_id, $rule_id );

                        $html['aoperators'] = $rule_obj->get_field( 'operator' );

                        if ( isset( $rule['suboption'] ) ) {
                            $html['asuboptions'] = $rule_obj->get_field( 'suboption' );
                        }

                        $html['avalues'] = $rule_obj->get_field( 'value' );

                        break;

                    }
                }
            }

            wp_send_json_success( $html );

        }

        /*
         * Ajax hook for suboption values
         */
        public function get_suboption_values() {

            check_ajax_referer( 'aws_pro_admin_ajax_nonce' );

            $param = sanitize_text_field( $_POST['param'] );
            $section = sanitize_text_field( $_POST['section'] );
            $suboption = sanitize_text_field( $_POST['suboption'] );
            $group_id = sanitize_text_field( $_POST['groupID'] );
            $rule_id = sanitize_text_field( $_POST['ruleID'] );

            $rules = AWS_Admin_Options::include_filters();
            $html = array();

            foreach ( $rules as $rule_section => $section_rules ) {
                foreach ( $section_rules as $rule ) {
                    if ( $rule['id'] === $param ) {

                        $rule['choices']['params'] = array( $suboption );

                        $rule_obj = new AWS_Admin_Filters( $rule, $section, $group_id, $rule_id );

                        $html = $rule_obj->get_field( 'value' );

                        break;

                    }
                }
            }

            wp_send_json_success( $html );

        }

        /*
         * Ajax hook to search for products
         */
        public function search_for_products() {

            check_ajax_referer( 'aws_pro_admin_ajax_nonce' );

            $term = sanitize_text_field( $_POST['search'] );
            $term = (string) wc_clean( wp_unslash( $term ) );

            $products = array();
            $products[] = array(
                'id' => 'aws_any',
                'text' => __( "Any product", "advanced-woo-search" )
            );

            $include_variations = false;
            $limit = 30;

            if ( class_exists('WC_Data_Store') ) {

                $data_store = WC_Data_Store::load( 'product' );
                $ids        = $data_store->search_products( $term, '', (bool) $include_variations, false, $limit, array(), array() );

                foreach ( $ids as $id ) {

                    $product_object = wc_get_product( $id );

                    if ( ! wc_products_array_filter_readable( $product_object ) ) {
                        continue;
                    }

                    $formatted_name = $product_object->get_formatted_name();
                    $products[] = array(
                        'id' => $product_object->get_id(),
                        'text' => rawurldecode( wp_strip_all_tags( $formatted_name ) )
                    );

                }

            }

            wp_send_json( array( 'results' => $products ) );

        }

        /*
         * Get plugin settings
         */
        private function get_settings() {
            $plugin_options = AWS_PRO()->get_settings();
            return $plugin_options;
        }

    }

endif;


new AWS_Admin_Ajax();