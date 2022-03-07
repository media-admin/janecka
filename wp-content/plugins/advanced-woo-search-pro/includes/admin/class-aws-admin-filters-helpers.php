<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


if ( ! class_exists( 'AWS_Admin_Filters_Helpers' ) ) :

    /**
     * Class for plugin help methods
     */
    class AWS_Admin_Filters_Helpers {

        /*
         * Get available price formats
         * @return array
         */
        static public function get_price() {

            $options = array();

            $values = array(
                'current' => __( 'Current', 'advanced-woo-search' ),
                'sale'    => __( 'Sale', 'advanced-woo-search' ),
                'regular' => __( 'Regular', 'advanced-woo-search' ),
            );

            foreach ( $values as $value_val => $value_name ) {
                $options[$value_val] = $value_name;
            }

            return $options;

        }

        /*
         * Get available stock statuses
         * @return array
         */
        static public function get_stock_statuses() {

            $options = array();

            if ( function_exists( 'wc_get_product_stock_status_options' ) ) {
                $values = wc_get_product_stock_status_options();
            } else {
                $values = apply_filters(
                    'woocommerce_product_stock_status_options',
                    array(
                        'instock'     => __( 'In stock', 'woocommerce' ),
                        'outofstock'  => __( 'Out of stock', 'woocommerce' ),
                        'onbackorder' => __( 'On backorder', 'woocommerce' ),
                    )
                );
            }

            foreach ( $values as $value_val => $value_name ) {
                $options[$value_val] = $value_name;
            }

            return $options;

        }

        /*
         * Get available product visibilities
         * @return array
         */
        static public function get_visibilities() {

            $options = array();

            if ( function_exists( 'wc_get_product_visibility_options' ) ) {
                $values = wc_get_product_visibility_options();
            } else {
                $values = apply_filters(
                    'woocommerce_product_visibility_options',
                    array(
                        'visible' => __( 'Shop and search results', 'woocommerce' ),
                        'catalog' => __( 'Shop only', 'woocommerce' ),
                        'search'  => __( 'Search results only', 'woocommerce' ),
                        'hidden'  => __( 'Hidden', 'woocommerce' ),
                    )
                );
            }

            foreach ( $values as $value_val => $value_name ) {
                $options[$value_val] = $value_name;
            }

            return $options;

        }

        /*
         * Get available product types
         * @return array
         */
        static public function get_product_types() {

            $options = array();

            if ( function_exists( 'wc_get_product_types' ) ) {
                $values = wc_get_product_types();
            } else {
                $values = apply_filters(
                    'product_type_selector',
                    array(
                        'simple'   => __( 'Simple product', 'woocommerce' ),
                        'grouped'  => __( 'Grouped product', 'woocommerce' ),
                        'external' => __( 'External/Affiliate product', 'woocommerce' ),
                        'variable' => __( 'Variable product', 'woocommerce' ),
                    )
                );
            }

            $values['variation']  = __( 'Product variation', 'advanced-woo-search' );

            foreach ( $values as $value_val => $value_name ) {
                $options[$value_val] = $value_name;
            }

            return $options;

        }

        /*
         * Get available products
         * @return array
         */
        static public function get_products() {

            $options = array();

            $options['aws_any'] = __( "Any product", "advanced-woo-search" );

            $args = array(
                'posts_per_page' => -1,
                'post_type'      => 'product'
            );

            $products = get_posts( $args );

            if ( ! empty( $products ) ) {
                foreach ( $products as $product ) {
                    $options[$product->ID] = $product->post_title;
                }
            }

            return $options;

        }

        /*
         * Get specific product
         * @return array
         */
        static public function get_product( $id = 0 ) {

            $options = array();

            if ( $id === 'aws_any' ) {
                $options['aws_any'] = __( "Any product", "advanced-woo-search" );
                return $options;
            }

            if ( $id ) {
                $product_object = wc_get_product( $id );
                if ( $product_object ) {
                    $formatted_name = $product_object->get_formatted_name();
                    $options[$id] = rawurldecode( wp_strip_all_tags( $formatted_name ) );
                }
            }

            return $options;

        }

        /*
         * Get available taxonomies
         * @return array
         */
        static public function get_tax() {

            $taxonomy_objects = get_object_taxonomies( 'product', 'objects' );
            $options = array();

            foreach( $taxonomy_objects as $taxonomy_object ) {
                if ( in_array( $taxonomy_object->name, array( 'product_cat', 'product_tag', 'product_type', 'product_visibility', 'product_shipping_class' ) ) ) {
                    continue;
                }

                if ( strpos( $taxonomy_object->name, 'pa_' ) === 0 ) {
                    continue;
                }

                $options[] = array(
                    'name'  => $taxonomy_object->label,
                    'value' => $taxonomy_object->name
                );

            }

            return $options;

        }

        /*
        * Get all available taxonomies
        * @return array
        */
        static public function get_all_tax() {

            $taxonomy_objects = get_object_taxonomies( 'product', 'objects' );
            $options = array();

            foreach( $taxonomy_objects as $taxonomy_object ) {
                if ( in_array( $taxonomy_object->name, array( 'product_type', 'product_visibility', 'product_shipping_class' ) ) ) {
                    continue;
                }

                $options[] = array(
                    'name'  => $taxonomy_object->label,
                    'value' => $taxonomy_object->name
                );

            }

            return $options;

        }

        /*
        * Get available taxonomies_terms
        * @param $name string Tax name
        * @return array
        */
        static public function get_tax_terms( $name = false ) {

            if ( ! $name ) {
                return false;
            }

            $tax = get_terms( array(
                'taxonomy'   => $name,
                'hide_empty' => false,
            ) );

            $options = array();

            if ( ! empty( $tax ) ) {
                foreach ( $tax as $tax_item ) {
                    $options[$tax_item->term_id] = $tax_item->name;
                }
            }

            return $options;

        }

        /*
         * Get available product attributes
         * @return array
         */
        static public function get_attributes() {

            $options = array();

            if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
                $attributes = wc_get_attribute_taxonomies();
                if ( $attributes && ! empty( $attributes ) ) {
                    foreach( $attributes as $attribute ) {
                        $attribute_name = wc_attribute_taxonomy_name( $attribute->attribute_name );
                        $options[] = array(
                            'name'  => $attribute->attribute_label,
                            'value' => $attribute_name
                        );
                    }
                }

            }

            return $options;

        }

        /*
         * Get available product custom attributes
         * @return array
         */
        static public function get_custom_attributes( $name = '' ) {

            global $wpdb;

            $options = array();
            $attributes = array();
            $custom_attributes = $wpdb->get_results( "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = '_product_attributes'" );

            if ( ! empty( $custom_attributes ) && !is_wp_error( $custom_attributes ) ) {
                foreach ( $custom_attributes as $custom_attribute ) {
                    if ( $custom_attribute->meta_value ) {
                        $custom_attribute_array = maybe_unserialize( $custom_attribute->meta_value );

                        if ( $custom_attribute_array && is_array( $custom_attribute_array ) && ! empty( $custom_attribute_array ) ) {

                            foreach ($custom_attribute_array as $custom_attribute_key => $custom_attribute_val) {

                                if ( isset( $custom_attribute_val['is_taxonomy'] ) && $custom_attribute_val['is_taxonomy'] ) {
                                    continue;
                                }

                                $attributes[$custom_attribute_key]['name'] = $custom_attribute_val['name'];

                                $val_array = array_map( 'trim', explode( '|', $custom_attribute_val['value'] ) );

                                if ( $val_array && ! empty( $val_array ) ) {
                                    foreach( $val_array as $val_array_attr ) {
                                        $val_array_attr_key = sanitize_key( strval( $val_array_attr ) );
                                        $attributes[$custom_attribute_key]['val'][$val_array_attr_key] = $val_array_attr;
                                    }
                                }

                            }

                        }

                    }
                }
            }

            if ( ! empty( $attributes ) ) {

                foreach( $attributes as $attribute_slug => $attribute ) {

                    if ( $name ) {
                        if ( $name === $attribute_slug && isset( $attribute['val'] ) ) {
                            foreach( $attribute['val'] as $val_key => $val ) {
                                $options[] = array(
                                    'name'  => $val,
                                    'value' => $val_key
                                );
                            }
                        }
                    } else {
                        $options[] = array(
                            'name'  => $attribute['name'],
                            'value' => $attribute_slug
                        );
                    }

                }

            }

            return $options;

        }

        /*
         * Get available product custom fields
         * @return array
         */
        static public function get_custom_fields( $name = '' ) {

            global $wpdb;

            $query = "
                SELECT DISTINCT meta_key as val
                FROM $wpdb->postmeta
                WHERE meta_key NOT LIKE 'attribute_%'
                ORDER BY val ASC
            ";

            if ( $name ) {

                $query = "
                    SELECT DISTINCT meta_value as val
                    FROM $wpdb->postmeta
                    WHERE meta_key = '{$name}'
                    ORDER BY val ASC
                ";

            }

            $wp_es_fields = $wpdb->get_results( $query );
            $options = array();

            if ( is_array( $wp_es_fields ) && ! empty( $wp_es_fields ) ) {
                foreach ( $wp_es_fields as $field ) {
                    if ( isset( $field->val ) ) {
                        $options[] = array(
                            'name'  => $field->val,
                            'value' => $field->val
                        );
                    }
                }
            }

            return $options;

        }

        /*
         * Get all available users
         * @return array
         */
        static public function get_users() {

            $users = get_users();
            $options = array();

            if ( $users && ! empty( $users ) ) {
                foreach( $users as $user ) {
                    $options[$user->ID] = $user->display_name . ' (' . $user->user_nicename . ')';
                }
            }

            return $options;

        }

        /*
         * Get all available user roles
         * @return array
         */
        static public function get_user_roles() {

            global $wp_roles;

            $roles = $wp_roles->roles;
            $options = array();

            if ( $roles && ! empty( $roles ) ) {

                if ( is_multisite() ) {
                    $options['super_admin'] = __( 'Super Admin', 'advanced-woo-search' );
                }

                foreach( $roles as $role_slug => $role ) {
                    $options[$role_slug] = $role['name'];
                }

                $options['non-logged'] = __( 'Visitor ( not logged-in )', 'advanced-woo-search' );

            }

            return $options;

        }

        /*
         * Get all available user countries
         * @return array
         */
        static public function get_user_countries() {

            $options = array();

            $values = WC()->countries->get_allowed_countries() + WC()->countries->get_shipping_countries();

            foreach ( $values as $value_val => $value_name ) {
                $options[$value_val] = $value_name;
            }

            return $options;

        }

        /*
         * Get all available user languages
         * @return array
         */
        static public function get_user_languages() {

            $options = array();

            $values = include AWS_PRO_DIR . '/includes/admin/languages.php';

            foreach ( $values as $value_val => $value_name ) {
                $options[$value_val] = $value_name;
            }

            return $options;

        }

        /*
         * Get user devices
         * @return array
         */
        static public function get_user_devices() {

            $options = array();

            $values = array(
                'desktop' => __( 'Desktop', 'advanced-woo-search' ),
                'mobile'  => __( 'Mobile', 'advanced-woo-search' ),
            );

            foreach ( $values as $value_val => $value_name ) {
                $options[$value_val] = $value_name;
            }

            return $options;

        }

        /*
         * Get user cart
         * @return array
         */
        static public function get_user_cart() {

            $options = array();

            $values = array(
                'number'  => __( 'Number of items', 'advanced-woo-search' ),
                'average' => __( 'Average items cost', 'advanced-woo-search' ),
                'sum'     => __( 'Total sum of items', 'advanced-woo-search' ),
            );

            foreach ( $values as $value_val => $value_name ) {
                $options[$value_val] = $value_name;
            }

            return $options;

        }

        /*
         * Get available price formats
         * @return array
         */
        static public function get_shop_stats() {

            $options = array();

            $values = array(
                'orders_number' => __( 'Orders number', 'advanced-woo-search' ),
                'aov'           => __( 'Average order value', 'advanced-woo-search' ),
                'total_spend'   => __( 'Total spend', 'advanced-woo-search' ),
            );

            foreach ( $values as $value_val => $value_name ) {
                $options[$value_val] = $value_name;
            }

            return $options;

        }

        /*
         * Get terms pages hierarchy types
         * @return array
         */
        static public function get_terms_hierarchy() {

            $options = array();

            $values = array(
                'top_parent' => __( 'Top parent', 'advanced-woo-search' ),
                'child'  => __( 'Child', 'advanced-woo-search' ),
            );

            foreach ( $values as $value_val => $value_name ) {
                $options[$value_val] = $value_name;
            }

            return $options;

        }

        /*
         * Get available sales number periods
         * @return array
         */
        static public function get_time_periods() {

            $options = array();

            $values = array(
                'all'   => __( 'all time', 'advanced-woo-search' ),
                'hour'  => __( 'last 24 hours', 'advanced-woo-search' ),
                'week'  => __( 'last 7 days', 'advanced-woo-search' ),
                'month' => __( 'last month', 'advanced-woo-search' ),
                'year'  => __( 'last year', 'advanced-woo-search' ),
            );

            foreach ( $values as $value_val => $value_name ) {
                $options[$value_val] = $value_name;
            }

            return $options;

        }

        /*
         * Get filter section name
         * @param $name string Section id
         * @return string
         */
        static public function get_filter_section( $name ) {

            $label = $name;

            $sections = array(
                'product'      => __( "Product", "advanced-woo-search" ),
                'current_user' => __( "Current user", "advanced-woo-search" ),
                'term'         => __( "Terms pages", "advanced-woo-search" ),
                'user'         => __( "Users pages", "advanced-woo-search" ),
            );

            if ( isset( $sections[$name] ) ) {
                $label = $sections[$name];
            }

            return $label;

        }

        /*
         * Filter operators
         * @param $name string Operator name
         * @return array
         */
        static public function get_filter_operators( $name ) {

            $operators = array();

            $operators['equals'] = array(
                array(
                    "name" => __( "equal to", "advanced-woo-search" ),
                    "id"   => "equal",
                ),
                array(
                    "name" => __( "not equal to", "advanced-woo-search" ),
                    "id"   => "not_equal",
                ),
            );

            $operators['equals_compare'] = array(
                array(
                    "name" => __( "equal to", "advanced-woo-search" ),
                    "id"   => "equal",
                ),
                array(
                    "name" => __( "not equal to", "advanced-woo-search" ),
                    "id"   => "not_equal",
                ),
                array(
                    "name" => __( "greater or equal to", "advanced-woo-search" ),
                    "id"   => "greater",
                ),
                array(
                    "name" => __( "less or equal to", "advanced-woo-search" ),
                    "id"   => "less",
                ),
            );

            return $operators[$name];

        }

        /*
         * Include rule array by filter rule id
         * @return array
         */
        static public function include_filter_rule_by_id( $id ) {

            $rules = AWS_Admin_Options::include_filters();
            $rule = array();

            if ( $rules ) {
                foreach ( $rules as $rule_section => $section_rules ) {
                    foreach ( $section_rules as $section_rule ) {
                        if ( $section_rule['id'] === $id ) {
                            $rule = $section_rule;
                            break;
                        }
                    }
                }
            }

            if ( empty( $rule ) ) {
                $rule = $rules['product'][0];
            }

            return $rule;

        }

        /*
         * Get filter parameters that must be excluded for the current section
         * @return array
         */
        static public function get_filter_section_excluded_params( $section_name ) {

            $disabled_sections = array( 'term', 'user' );
            if ( $section_name === 'term' ) {
                $disabled_sections = array( 'product', 'user' );
            } elseif ( $section_name === 'user' ) {
                $disabled_sections = array( 'product', 'term' );
            }

            return $disabled_sections;

        }

    }

endif;