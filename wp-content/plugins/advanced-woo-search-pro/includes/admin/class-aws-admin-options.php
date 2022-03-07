<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


if ( ! class_exists( 'AWS_Admin_Options' ) ) :

    /**
     * Class for plugin admin options methods
     */
    class AWS_Admin_Options {

        /*
         * Get default settings values
         * @param string $tab Tab name
		 * @return array
         */
        static public function get_default_settings( $tab = false, $sec = 'none' ) {

            $options = self::options_array( $tab, $sec );
            $default_settings = array();

            foreach ( $options as $section_name => $section ) {

                foreach ($section as $values) {

                    if ( isset( $values['type'] ) && $values['type'] === 'heading' ) {
                        continue;
                    }

                    if ( isset( $values['type'] ) && $values['type'] === 'table' && empty( $values['value'] ) ) {
                        continue;
                    }

                    if ( isset( $values['type'] ) && ( $values['type'] === 'checkbox' || $values['type'] === 'table' ) ) {
                        foreach ( $values['choices'] as $key => $val ) {

                            if ( ! isset( $values['value'][$key] ) ) {
                                continue;
                            }

                            if ( $section_name === 'results' ) {
                                $default_settings['filters']['1'][$values['id']][$key] = sanitize_text_field( $values['value'][$key] );
                            } else {
                                $default_settings[$values['id']][$key] = sanitize_text_field( $values['value'][$key] );
                            }

                        }
                        continue;
                    }

                    if ( $section_name === 'results' ) {
                        $val = $values['value'];
                        if ( $values['type'] !== 'filter_rules' ) {
                            if ( $values['type'] === 'textarea' && isset( $values['allow_tags'] ) ) {
                                $val = (string) addslashes( wp_kses( stripslashes( $val ), AWS_Admin_Helpers::get_kses( $values['allow_tags'] ) ) );
                            }
                            elseif ( $values['type'] === 'textarea' ) {
                                if ( function_exists('sanitize_textarea_field') ) {
                                    $val = (string) sanitize_textarea_field( $val );
                                } else {
                                    $val = (string) str_replace( "<\n", "&lt;\n", wp_strip_all_tags( $val ) );
                                }
                            }
                            else {
                                $val = (string) sanitize_text_field( $val );
                            }
                        }
                        $default_settings['filters']['1'][$values['id']] = $val;
                        continue;
                    }


                    if ( isset( $values['type'] ) && $values['type'] === 'textarea' && isset( $values['allow_tags'] ) ) {
                        $default_settings[$values['id']] = (string) addslashes( wp_kses( stripslashes( $values['value'] ), AWS_Admin_Helpers::get_kses( $values['allow_tags'] ) ) );
                    }
                    elseif ( isset( $values['type'] ) && $values['type'] === 'textarea' ) {
                        if ( function_exists('sanitize_textarea_field') ) {
                            $default_settings[$values['id']] = (string) sanitize_textarea_field( $values['value'] );
                        } else {
                            $default_settings[$values['id']] = (string) str_replace( "<\n", "&lt;\n", wp_strip_all_tags( $values['value'] ) );
                        }
                    }
                    else {
                        $default_settings[$values['id']] = (string) sanitize_text_field( $values['value'] );
                    }

                    if (isset( $values['sub_option'])) {
                        $default_settings[$values['sub_option']['id']] = (string) sanitize_text_field( $values['sub_option']['value'] );
                    }

                }
            }

            return $default_settings;

        }

        /*
         * Update plugin settings
         */
        static public function update_settings() {

            $options = self::options_array( false, 'none' );
            $settings = self::get_settings();
            $current_tab = empty( $_GET['tab'] ) ? 'general' : sanitize_text_field( $_GET['tab'] );

            $instance_id = isset( $_GET['aws_id'] ) ? (int) sanitize_text_field( $_GET['aws_id'] ) : 0;
            $filter_id   = isset( $_GET['filter'] ) ? (int) sanitize_text_field( $_GET['filter'] ) : 1;

            $instance_settings = $settings[$instance_id];

            foreach ( $options[$current_tab] as $values ) {

                if ( $values['type'] === 'heading' || $values['type'] === 'table' ) {
                    continue;
                }

                if ( $values['type'] === 'checkbox' ) {

                    $checkbox_array = array();

                    foreach ( $values['choices'] as $key => $value ) {
                        $new_value = isset( $_POST[ $values['id'] ][$key] ) ? '1' : '0';
                        $checkbox_array[$key] = sanitize_text_field( $new_value );
                    }

                    if ( $current_tab === 'results' ) {
                        $instance_settings['filters'][$filter_id][$values['id']] = $checkbox_array;
                    } else {
                        $instance_settings[ $values['id'] ] = $checkbox_array;
                    }

                    continue;
                }

                $new_value = isset( $_POST[ $values['id'] ] ) ? $_POST[ $values['id'] ] : '';

                if ( $current_tab === 'results' ) {
                    if ( $values['type'] !== 'filter_rules'  ) {
                        if ( $values['type'] === 'textarea' && isset( $values['allow_tags'] ) ) {
                            $new_value = (string) addslashes( wp_kses( stripslashes( $new_value ), AWS_Admin_Helpers::get_kses( $values['allow_tags'] ) ) );
                        }
                        elseif ( $values['type'] === 'textarea' ) {
                            if ( function_exists('sanitize_textarea_field') ) {
                                $new_value = (string) sanitize_textarea_field( $new_value );
                            } else {
                                $new_value = (string) str_replace( "<\n", "&lt;\n", wp_strip_all_tags( $new_value ) );
                            }
                        }
                        else {
                            $new_value = (string) sanitize_text_field( $new_value );
                        }
                    }
                    $instance_settings['filters'][$filter_id][$values['id']] = $new_value;
                    continue;
                }

                if ( $values['type'] === 'textarea' && isset( $values['allow_tags'] ) ) {
                    $instance_settings[ $values['id'] ] = (string) addslashes( wp_kses( stripslashes( $new_value ), AWS_Admin_Helpers::get_kses( $values['allow_tags'] ) ) );
                }
                elseif ( $values['type'] === 'textarea' ) {
                    if ( function_exists('sanitize_textarea_field') ) {
                        $instance_settings[ $values['id'] ] = (string) sanitize_textarea_field( $new_value );
                    } else {
                        $instance_settings[ $values['id'] ] = (string) str_replace( "<\n", "&lt;\n", wp_strip_all_tags( $new_value ) );
                    }
                }
                else {
                    $instance_settings[ $values['id'] ] = (string) sanitize_text_field( $new_value );
                }

                if ( isset( $values['sub_option'] ) ) {
                    $new_value = isset( $_POST[ $values['sub_option']['id'] ] ) ? $_POST[ $values['sub_option']['id'] ] : '';
                    $instance_settings[ $values['sub_option']['id'] ] = (string) sanitize_text_field( $new_value );
                }

            }

            $settings[$instance_id] = $instance_settings;

            update_option( 'aws_pro_settings', $settings );

            do_action( 'aws_settings_saved', $settings );

            do_action( 'aws_cache_clear' );

        }

        /*
         * Update common plugin settings
         */
        static public function update_common_settings() {

            $options = self::options_array( false );
            $settings = self::get_common_settings();
            $current_tab = empty( $_GET['tab'] ) ? 'general' : sanitize_text_field( $_GET['tab'] );

            foreach ( $options[$current_tab] as $values ) {

                if ( ! isset( $values['type'] ) ) {
                    continue;
                }

                if ( $values['type'] === 'heading' || $values['type'] === 'table' ) {
                    continue;
                }

                if ( $values['type'] === 'checkbox' ) {

                    $checkbox_array = array();

                    foreach ( $values['choices'] as $key => $value ) {
                        $new_value = isset( $_POST[ $values['id'] ][$key] ) ? '1' : '0';
                        $checkbox_array[$key] = sanitize_text_field( $new_value );
                    }

                    $settings[ $values['id'] ] = $checkbox_array;

                    continue;

                }

                $new_value = isset( $_POST[ $values['id'] ] ) ? $_POST[ $values['id'] ] : '';

                $settings[ $values['id'] ] = (string) sanitize_text_field( $new_value );

            }

            update_option( 'aws_pro_common_opts', $settings );

            do_action( 'aws_settings_saved', $settings );

            do_action( 'aws_cache_clear' );

        }

        /*
         * Get plugin settings
         * @return array
         */
        static public function get_settings() {
            $plugin_options = get_option( 'aws_pro_settings' );
            return $plugin_options;
        }

        /*
         * Get plugin settings
         * @return array
         */
        static public function get_common_settings() {
            $plugin_options = get_option( 'aws_pro_common_opts' );
            return $plugin_options;
        }

        /*
         * Get options array
         *
         * @param string $tab Tab name
         * @param string $section Section name
         * @return array
         */
        static public function options_array( $tab = false, $section = false ) {

            $options = self::include_options();
            $options_arr = array();

            foreach ( $options as $tab_name => $tab_options ) {

                if ( $tab && $tab !== $tab_name ) {
                    continue;
                }

                foreach ( $tab_options as $option ) {

                    if ( $section ) {

                        if ( ( isset( $option['section'] ) && $option['section'] !== $section ) || ( !isset( $option['section'] ) && $section !== 'none' ) ) {
                            continue;
                        }

                    }

                    if ( isset( $option['value'] ) && isset( $option['value']['callback'] ) ) {
                        $option['value'] = call_user_func_array( $option['value']['callback'], $option['value']['params'] );
                    }

                    if ( isset( $option['choices'] ) && isset( $option['choices']['callback'] ) ) {
                        $option['choices'] = call_user_func_array( $option['choices']['callback'], $option['choices']['params'] );
                    }

                    $options_arr[$tab_name][] = $option;

                }


            }

            /**
             * Filter admin page options for current page
             * @since 2.23
             * @param array $options_arr Array of options
             * @param bool|string $tab Current settings page tab
             * @param bool|string $section Current settings page section
             */
            $options_arr = apply_filters( 'aws_admin_page_options_current', $options_arr, $tab, $section );

            return $options_arr;

        }

        /*
         * Include options array
         * @return array
         */
        static public function include_options() {

            $product_stock_status = 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ? array( 'in_stock' => 1, 'out_of_stock' => 0, 'on_backorder' => 0 ) : array( 'in_stock' => 1, 'out_of_stock' => 1, 'on_backorder' => 1 );

            $options = array();

            $options['common'][] = array(
                "id"    => "search_instance",
                "value" => "Search Form"
            );

            $options['common'][] = array(
                "id"    => "filter_num",
                "value" => "1"
            );

            $options['performance'][] = array(
                "name"    => __( "Search options", "advanced-woo-search" ),
                "type"    => "heading"
            );

            $options['performance'][] = array(
                "name"  => __( "Search rule", "advanced-woo-search" ),
                "desc"  => __( "Search rule that will be used for terms search.", "advanced-woo-search" ),
                "id"    => "search_rule",
                "inherit" => "true",
                "value" => 'contains',
                "type"  => "radio",
                'choices' => array(
                    'contains' => '%s% ' . __( "( contains ). Search query can be inside any part of the product words ( beginning, end, middle ). Slow.", "advanced-woo-search" ),
                    'begins'   => 's% ' . __( "( begins ). Search query can be only at the beginning of the product words. Fast.", "advanced-woo-search" ),
                )
            );

            $options['performance'][] = array(
                "name"  => __( "AJAX timeout", "advanced-woo-search" ),
                "desc"  => __( "Time after user input that script is waiting before sending a search event to the server, ms.", "advanced-woo-search" ),
                "id"    => "search_timeout",
                "inherit" => "true",
                "value" => 300,
                'min'   => 100,
                "type"  => "number"
            );

            $options['performance'][] = array(
                "name"    => __( "Cache options", "advanced-woo-search" ),
                "type"    => "heading"
            );

            $options['performance'][] = array(
                "name"  => __( "Cache results", "advanced-woo-search" ),
                "desc"  => __( "Cache search results to increase search speed.", "advanced-woo-search" ) . '<br>' .
                    __( "Turn off if you have old data in the search results after the content of products was changed.", "advanced-woo-search" ),
                "id"    => "cache",
                "inherit" => "true",
                "value" => 'true',
                "type"  => "radio",
                'choices' => array(
                    'true'  => __( 'On', 'advanced-woo-search' ),
                    'false'  => __( 'Off', 'advanced-woo-search' ),
                )
            );

            $options['performance'][] = array(
                "name"    => __( "Clear cache", "advanced-woo-search" ),
                "type"    => "html",
                "desc"    =>__( "Clear cache for all search results.", "advanced-woo-search" ),
                "html"    => '<div id="aws-clear-cache"><input class="button" type="button" value="' . esc_attr__( 'Clear cache', 'advanced-woo-search' ) . '"><span class="loader"></span></div><br>',
            );

            $options['performance'][] = array(
                "name"    => __( "Index table options", "advanced-woo-search" ),
                "id"      => "index_sources",
                "type"    => "heading"
            );

            $options['performance'][] = array(
                "name"         => __( "Overview", "advanced-woo-search" ),
                'heading_type' => 'text',
                'desc'         => __( 'To perform the search plugin use a special index table. This table contains normalized words of all your products from all available sources.', "advanced-woo-search" ) . '<br>' .
                    __( 'Sometimes when there are too many products in your store index table can be very large and that can reflect on search speed.', "advanced-woo-search" ) . '<br>' .
                    __( 'In this section you can use several options to change the table size by disabling some unused product data.', "advanced-woo-search" ) . '<br>' .
                    '<b>' . __( "Note:", "advanced-woo-search" ) . '</b> ' . __( "Reindex is required after options changes.", "advanced-woo-search" ),
                "type"         => "heading"
            );

            $options['performance'][] = array(
                "name"       => __( "Data to index", "advanced-woo-search" ),
                "desc"       => __( "Choose what products data to add inside the plugin index table.", "advanced-woo-search" ),
                "table_head" => __( 'What to index', 'advanced-woo-search' ),
                "id"         => "index_sources",
                "inherit"    => "true",
                "value" => array(
                    'title'    => 1,
                    'content'  => 1,
                    'sku'      => 1,
                    'excerpt'  => 1,
                    'category' => 1,
                    'tag'      => 1,
                    'id'       => 1,
                    'attr'     => 0,
                    'tax'      => 0,
                    'meta'     => 0,
                ),
                "choices" => array(
                    "title"    => __( "Title", "advanced-woo-search" ),
                    "content"  => __( "Content", "advanced-woo-search" ),
                    "sku"      => __( "SKU", "advanced-woo-search" ),
                    "excerpt"  => __( "Short description", "advanced-woo-search" ),
                    "category" => __( "Category", "advanced-woo-search" ),
                    "tag"      => __( "Tag", "advanced-woo-search" ),
                    "id"       => __( "ID", "advanced-woo-search" ),
                    "attr"     => array( 'label' => __( "Attributes", "advanced-woo-search" ), 'option' => true ),
                    "tax"      => array( 'label' => __( "Taxonomies", "advanced-woo-search" ), 'option' => true ),
                    "meta"     => array( 'label' => __( "Custom Fields", "advanced-woo-search" ), 'option' => true ),
                ),
                "type"    => "table"
            );

            $options['performance'][] = array(
                "name"    => __( "Attributes index", "advanced-woo-search" ),
                "desc"    => __( "Choose product attributes that must be indexed.", "advanced-woo-search" ),
                "table_head" => __( 'Data to index', 'advanced-woo-search' ),
                "id"      => "index_sources_attr",
                "section" => "attr",
                "value"   => array(),
                "choices" => array(
                    'callback' => 'AWS_Helpers::get_attributes',
                    'params'   => array()
                ),
                "type"    => "table"
            );

            $options['performance'][] = array(
                "name"    => __( "Taxonomies index", "advanced-woo-search" ),
                "desc"    => __( "Choose product taxonomies that must be indexed.", "advanced-woo-search" ),
                "table_head" => __( 'Data to index', 'advanced-woo-search' ),
                "id"      => "index_sources_tax",
                "section" => "tax",
                "value"   => array(),
                "choices" => array(
                    'callback' => 'AWS_Helpers::get_taxonomies',
                    'params'   => array()
                ),
                "type"    => "table"
            );

            $options['performance'][] = array(
                "name"    => __( "Custom fields index", "advanced-woo-search" ),
                "desc"    => sprintf( __( "Choose product custom fields that must be indexed. %s", "advanced-woo-search" ), ! isset( $_GET['show_inner'] ) ? '<a href="' . esc_url( AWS_Helpers::get_settings_instance_page_url('&section=meta&show_inner=true' ) ) . '">' . __( "Include inner fields.", "advanced-woo-search" ) . '</a>' : '<a href="' . esc_url( AWS_Helpers::get_settings_instance_page_url('&section=meta' ) ) . '">' . __( "Hide inner fields.", "advanced-woo-search" ) . '</a>' ),
                "table_head" => __( 'Data to index', 'advanced-woo-search' ),
                "id"      => "index_sources_meta",
                "section" => "meta",
                "value"   => array(),
                "choices" => array(
                    'callback' => 'AWS_Helpers::get_custom_fields',
                    'params'   => array()
                ),
                "type"    => "table"
            );

            $options['performance'][] = array(
                "name"  => __( "Index variations", "advanced-woo-search" ),
                "desc"  => __( "Index or not content of product variations.", "advanced-woo-search" ),
                "id"    => "index_variations",
                "inherit" => "true",
                "value" => 'true',
                "type"  => "radio",
                'choices' => array(
                    'true'  => __( 'On', 'advanced-woo-search' ),
                    'false'  => __( 'Off', 'advanced-woo-search' ),
                )
            );

            $options['performance'][] = array(
                "name"  => __( "Sync index table", "advanced-woo-search" ),
                "desc"  => __( "Automatically update plugin index table when product content was changed. This means that in search there will be always latest product data.", "advanced-woo-search" ) . '<br>' .
                    __( "Turn this off if you have any problems with performance.", "advanced-woo-search" ),
                "id"    => "autoupdates",
                "value" => 'true',
                "inherit" => "true",
                "type"  => "radio",
                'choices' => array(
                    'true'  => __( 'On', 'advanced-woo-search' ),
                    'false'  => __( 'Off', 'advanced-woo-search' ),
                )
            );

            $options['form'][] = array(
                "name"  => __( "Text for search field", "advanced-woo-search" ),
                "desc"  => __( "Text for search field placeholder.", "advanced-woo-search" ),
                "id"    => "search_field_text",
                "inherit" => "true",
                "value" => __( "Search", "advanced-woo-search" ),
                "type"  => "text"
            );

            $options['form'][] = array(
                "name"  => __( "Text for show more button", "advanced-woo-search" ),
                "desc"  => __( "Text for link to search results page at the bottom of search results block.", "advanced-woo-search" ),
                "id"    => "show_more_text",
                "inherit" => "true",
                "value" => __( "View all results", "advanced-woo-search" ),
                "type"  => "text"
            );

            $options['form'][] = array(
                "name"  => __( "Nothing found field", "advanced-woo-search" ),
                "desc"  => __( "Text when there is no search results.", "advanced-woo-search" ),
                "id"    => "not_found_text",
                "inherit" => "true",
                "value" => __( "Nothing found", "advanced-woo-search" ),
                "type"  => "textarea",
                'allow_tags' => array( 'a', 'br', 'em', 'strong', 'b', 'code', 'blockquote', 'p', 'i' )
            );

            $options['form'][] = array(
                "name"  => __( "Minimum number of characters", "advanced-woo-search" ),
                "desc"  => __( "Minimum number of characters required to run ajax search.", "advanced-woo-search" ),
                "id"    => "min_chars",
                "inherit" => "true",
                "value" => 1,
                "type"  => "number"
            );

            $options['form'][] = array(
                "name"  => __( "AJAX search", "advanced-woo-search" ),
                "desc"  => __( "Use or not live search feature.", "advanced-woo-search" ),
                "id"    => "enable_ajax",
                "inherit" => "true",
                "value" => 'true',
                "type"  => "radio",
                'choices' => array(
                    'true'  => __( 'On', 'advanced-woo-search' ),
                    'false' => __( 'Off', 'advanced-woo-search' ),
                )
            );

            $options['form'][] = array(
                "name"  => __( "Show loader", "advanced-woo-search" ),
                "desc"  => __( "Show loader animation while searching.", "advanced-woo-search" ),
                "id"    => "show_loader",
                "inherit" => "true",
                "value" => 'true',
                "type"  => "radio",
                'choices' => array(
                    'true'  => __( 'On', 'advanced-woo-search' ),
                    'false' => __( 'Off', 'advanced-woo-search' )
                )
            );

            $options['form'][] = array(
                "name"  => __( "Show clear button", "advanced-woo-search" ),
                "desc"  => __( "Show 'Clear search string' button for desktop devices ( for mobile it is always visible ).", "advanced-woo-search" ),
                "id"    => "show_clear",
                "inherit" => "true",
                "value" => 'true',
                "type"  => "radio",
                'choices' => array(
                    'true'  => __( 'On', 'advanced-woo-search' ),
                    'false' => __( 'Off', 'advanced-woo-search' ),
                )
            );

            $options['form'][] = array(
                "name"  => __( "Show 'View All Results'", "advanced-woo-search" ),
                "desc"  => __( "Show link to search results page at the bottom of search results block.", "advanced-woo-search" ),
                "id"    => "show_more",
                "inherit" => "true",
                "value" => 'true',
                "type"  => "radio",
                'choices' => array(
                    'true'  => __( 'On', 'advanced-woo-search' ),
                    'false' => __( 'Off', 'advanced-woo-search' )
                )
            );

            $options['form'][] = array(
                "name"  => __( "Mobile full screen", "advanced-woo-search" ),
                "desc"  => __( "Full screen search on focus. Will not work if the search form is inside the block with position: fixed.", "advanced-woo-search" ),
                "id"    => "mobile_overlay",
                "inherit" => "true",
                "value" => 'false',
                "type"  => "radio",
                'choices' => array(
                    'true'  => __( 'On', 'advanced-woo-search' ),
                    'false' => __( 'Off', 'advanced-woo-search' )
                )
            );

            $options['form'][] = array(
                "name"  => __( "Show title in input", "advanced-woo-search" ),
                "desc"  => __( "Show title of hovered search result in the search input field.", "advanced-woo-search" ),
                "id"    => "show_addon",
                "value" => 'false',
                "type"  => "radio",
                'choices' => array(
                    'true'  => __( 'On', 'advanced-woo-search' ),
                    'false' => __( 'Off', 'advanced-woo-search' )
                )
            );

            $options['form'][] = array(
                "name"  => __( "Form Styling", "advanced-woo-search" ),
                "desc"  => __( "Choose search form layout", "advanced-woo-search" ) . '<br>' . __( "Filter button will be visible only if you have more than one active filter for current search form instance.", "advanced-woo-search" ),
                "id"    => "buttons_order",
                "inherit" => "true",
                "value" => '2',
                "type"  => "radio-image",
                'choices' => array(
                    '1' => 'btn-layout1.png',
                    '2' => 'btn-layout2.png',
                    '3' => 'btn-layout3.png',
                    '4' => 'btn-layout4.png',
                    '5' => 'btn-layout5.png',
                    '6' => 'btn-layout6.png',
                )
            );

            $options['results'][] = array(
                "name"    => __( "General", "advanced-woo-search" ),
                "id"      => "general",
                "type"    => "heading"
            );

            $options['results'][] = array(
                "name"  => __( "Filter name", "advanced-woo-search" ),
                "desc"  => __( "Name for current filter.", "advanced-woo-search" ),
                "id"    => "filter_name",
                "value" => "All",
                "type"  => "text"
            );

            $options['results'][] = array(
                "name"  => __( "Style", "advanced-woo-search" ),
                "desc"  => __( "Set style for search results output.", "advanced-woo-search" ),
                "id"    => "style",
                "value" => 'style-inline',
                "type"  => "radio",
                'choices' => array(
                    'style-inline'   => __( "Inline Style", "advanced-woo-search" ),
                    'style-grid'     => __( "Grid Style", "advanced-woo-search" ),
                    'style-big-grid' => __( "Big Grid Style", "advanced-woo-search" ),
                )
            );

            $options['results'][] = array(
                "name"  => __( "Description content", "advanced-woo-search" ),
                "desc"  => __( "What to show in product description?", "advanced-woo-search" ),
                "id"    => "mark_words",
                "inherit" => "true",
                "value" => 'true',
                "type"  => "radio",
                'choices' => array(
                    'true'  => __( "Smart scraping sentences with searching terms from product description.", "advanced-woo-search" ),
                    'false' => __( "First N words of product description ( number of words that you choose below. )", "advanced-woo-search" ),
                )
            );

            $options['results'][] = array(
                "name"  => __( "Description length", "advanced-woo-search" ),
                "desc"  => __( "Maximal allowed number of words for product description.", "advanced-woo-search" ),
                "id"    => "excerpt_length",
                "inherit" => "true",
                "value" => 20,
                "type"  => "number"
            );

            $options['results'][] = array(
                "name"  => __( "Max number of results", "advanced-woo-search" ),
                "desc"  => __( "Maximum number of displayed search results.", "advanced-woo-search" ),
                "id"    => "results_num",
                "inherit" => "true",
                "value" => 10,
                "type"  => "number"
            );

            $options['results'][] = array(
                "name"  => __( "Variable products", "advanced-woo-search" ),
                "desc"  => __( "How to show variable products.", "advanced-woo-search" ),
                "id"    => "var_rules",
                "value" => 'parent',
                "type"  => "radio",
                'choices' => array(
                    'parent' => __( 'Show only parent products', 'advanced-woo-search' ),
                    'both'   => __( 'Show parent and child products', 'advanced-woo-search' ),
                    'child'  => __( 'Show only child products', 'advanced-woo-search' ),
                )
            );

            $options['results'][] = array(
                "name"  => __( "Products sale status", "advanced-woo-search" ),
                "desc"  => __( "Search only for products with selected sale status", "advanced-woo-search" ),
                "id"    => "on_sale",
                "value" => 'true',
                "type"  => "radio",
                'choices' => array(
                    'true'  => __( "Show on-sale and not on-sale products", "advanced-woo-search" ),
                    'false' => __( "Show only on-sale products", "advanced-woo-search" ),
                    'not'   => __( "Show only not on-sale products", "advanced-woo-search" ),
                )
            );

            $options['results'][] = array(
                "name"  => __( "Products stock status", "advanced-woo-search" ),
                "desc"  => __( "Search only for products with selected stock status", "advanced-woo-search" ),
                "id"    => "product_stock_status",
                "value" => $product_stock_status,
                "type"  => "checkbox",
                'choices' => array(
                    'in_stock'     => __( 'In stock', 'advanced-woo-search' ),
                    'out_of_stock' => __( 'Out of stock', 'advanced-woo-search' ),
                    'on_backorder' => __( 'On backorder', 'advanced-woo-search' ),
                )
            );

            $options['results'][] = array(
                "name"  => __( "Products visibility", "advanced-woo-search" ),
                "desc"  => __( "Search only products with this visibilities.", "advanced-woo-search" ),
                "id"    => "product_visibility",
                "value" => array(
                    'visible'  => 1,
                    'catalog'  => 1,
                    'search'   => 1,
                    'hidden'   => 0,
                ),
                "type"  => "checkbox",
                'choices' => array(
                    'visible'  => __( 'Catalog/search', 'advanced-woo-search' ),
                    'catalog'  => __( 'Catalog', 'advanced-woo-search' ),
                    'search'   => __( 'Search', 'advanced-woo-search' ),
                    'hidden'   => __( 'Hidden', 'advanced-woo-search' ),
                )
            );

            $options['results'][] = array(
                "name"    => __( "Search Sources", "advanced-woo-search" ),
                "id"      => "sources",
                "type"    => "heading"
            );

            $options['results'][] = array(
                "name"    => __( "Search in", "advanced-woo-search" ),
                "desc"    => __( "Click on status icon to enable or disable search source.", "advanced-woo-search" ),
                "id"      => "search_in",
                "inherit" => "true",
                "value" => array(
                    'title'    => 1,
                    'content'  => 1,
                    'sku'      => 1,
                    'excerpt'  => 1,
                    'category' => 0,
                    'tag'      => 0,
                    'id'       => 0,
                    'attr'     => 0,
                    'tax'      => 0,
                    'meta'     => 0,
                ),
                "choices" => array(
                    "title"    => __( "Title", "advanced-woo-search" ),
                    "content"  => __( "Content", "advanced-woo-search" ),
                    "sku"      => __( "SKU", "advanced-woo-search" ),
                    "excerpt"  => __( "Short description", "advanced-woo-search" ),
                    "category" => __( "Category", "advanced-woo-search" ),
                    "tag"      => __( "Tag", "advanced-woo-search" ),
                    "id"       => __( "ID", "advanced-woo-search" ),
                    "attr"     => array( 'label' => __( "Attributes", "advanced-woo-search" ), 'option' => true ),
                    "tax"      => array( 'label' => __( "Taxonomies", "advanced-woo-search" ), 'option' => true ),
                    "meta"     => array( 'label' => __( "Custom Fields", "advanced-woo-search" ), 'option' => true ),
                ),
                "type"    => "table"
            );

            $options['results'][] = array(
                "name"    => __( "Archive pages", "advanced-woo-search" ),
                "desc"    => __( "Search for taxonomies and displayed their archive pages in search results.", "advanced-woo-search" ),
                'table_head' => __( 'Archive Pages', 'advanced-woo-search' ),
                "id"      => "search_archives",
                "inherit" => "true",
                "value" => array(
                    'archive_category' => 0,
                    'archive_tag'      => 0,
                    'archive_tax'      => 0,
                    'archive_attr'     => 0,
                    'archive_users'    => 0,
                ),
                "choices" => array(
                    "archive_category" => __( "Category", "advanced-woo-search" ),
                    "archive_tag"      => __( "Tag", "advanced-woo-search" ),
                    "archive_tax"      => array( 'label' => __( "Taxonomies", "advanced-woo-search" ), 'option' => true ),
                    "archive_attr"     => array( 'label' => __( "Attributes", "advanced-woo-search" ), 'option' => true ),
                    "archive_users"    => array( 'label' => __( "Users", "advanced-woo-search" ), 'option' => true ),
                ),
                "type"    => "table"
            );

            $options['results'][] = array(
                "name"    => __( "Attributes search", "advanced-woo-search" ),
                "desc"    => __( "Choose product attributes that must be searchable.", "advanced-woo-search" ),
                "id"      => "search_in_attr",
                "section" => "attr",
                "value"   => array(),
                "choices" => array(
                    'callback' => 'AWS_Helpers::get_attributes',
                    'params'   => array()
                ),
                "type"    => "table"
            );

            $options['results'][] = array(
                "name"    => __( "Taxonomies search", "advanced-woo-search" ),
                "desc"    => __( "Choose product taxonomies that must be searchable.", "advanced-woo-search" ),
                "id"      => "search_in_tax",
                "section" => "tax",
                "value"   => array(),
                "choices" => array(
                    'callback' => 'AWS_Helpers::get_taxonomies',
                    'params'   => array()
                ),
                "type"    => "table"
            );

            $options['results'][] = array(
                "name"    => __( "Custom fields search", "advanced-woo-search" ),
                "desc"    => sprintf( __( "Choose product custom fields that must be searchable. %s", "advanced-woo-search" ), ! isset( $_GET['show_inner'] ) ? '<a href="' . esc_url( AWS_Helpers::get_settings_instance_page_url('&section=meta&show_inner=true' ) ) . '">' . __( "Include inner fields.", "advanced-woo-search" ) . '</a>' : '<a href="' . esc_url( AWS_Helpers::get_settings_instance_page_url('&section=meta' ) ) . '">' . __( "Hide inner fields.", "advanced-woo-search" ) . '</a>' ),
                "id"      => "search_in_meta",
                "section" => "meta",
                "value"   => array(),
                "choices" => array(
                    'callback' => 'AWS_Helpers::get_custom_fields',
                    'params'   => array()
                ),
                "type"    => "table"
            );

            $options['results'][] = array(
                "name"    => __( "Taxonomies archives", "advanced-woo-search" ),
                "desc"    => __( "Choose taxonomies archive pages that must be searchable.", "advanced-woo-search" ),
                'table_head' => __( 'Archive Pages', 'advanced-woo-search' ),
                "id"      => "search_archives_tax",
                "section" => "archive_tax",
                "value"   => array(),
                "choices" => array(
                    'callback' => 'AWS_Helpers::get_taxonomies',
                    'params'   => array( false, false )
                ),
                "type"    => "table"
            );

            $options['results'][] = array(
                "name"    => __( "Attributes archives", "advanced-woo-search" ),
                "desc"    => __( "Choose attributes archive pages that must be searchable.", "advanced-woo-search" ),
                'table_head' => __( 'Archive Pages', 'advanced-woo-search' ),
                "id"      => "search_archives_attr",
                "section" => "archive_attr",
                "value"   => array(),
                "choices" => array(
                    'callback' => 'AWS_Helpers::get_attribute_archives',
                    'params'   => array()
                ),
                "type"    => "table"
            );

            $options['results'][] = array(
                "name"    => __( "User Roles", "advanced-woo-search" ),
                "desc"    => __( "Choose user roles that will be available for search.", "advanced-woo-search" ),
                'table_head' => __( 'User Roles', 'advanced-woo-search' ),
                "id"      => "search_archives_users",
                "section" => "archive_users",
                "value"   => array(),
                "choices" => array(
                    'callback' => 'AWS_Helpers::get_user_roles',
                    'params'   => array()
                ),
                "type"    => "table"
            );

            $options['results'][] = array(
                "name"    => __( "View", "advanced-woo-search" ),
                "id"      => "view",
                "type"    => "heading"
            );

            $options['results'][] = array(
                "name"  => __( "Highlight words", "advanced-woo-search" ),
                "desc"  => __( "Highlight search words inside products content.", "advanced-woo-search" ),
                "id"    => "highlight",
                "inherit" => "true",
                "value" => 'true',
                "type"  => "radio",
                'choices' => array(
                    'true'  => __( 'On', 'advanced-woo-search' ),
                    'false' => __( 'Off', 'advanced-woo-search' )
                )
            );

            $options['results'][] = array(
                "name"  => __( "Show image", "advanced-woo-search" ),
                "desc"  => __( "Show product image for each search result.", "advanced-woo-search" ),
                "id"    => "show_image",
                "inherit" => "true",
                "value" => 'true',
                "type"  => "radio",
                'choices' => array(
                    'true'  => __( 'On', 'advanced-woo-search' ),
                    'false' => __( 'Off', 'advanced-woo-search' )
                )
            );

            $options['results'][] = array(
                "name"  => __( "Show description", "advanced-woo-search" ),
                "desc"  => __( "Show product description for each search result.", "advanced-woo-search" ),
                "id"    => "show_excerpt",
                "inherit" => "true",
                "value" => 'true',
                "type"  => "radio",
                'choices' => array(
                    'true'  => __( 'On', 'advanced-woo-search' ),
                    'false' => __( 'Off', 'advanced-woo-search' )
                )
            );

            $options['results'][] = array(
                "name"  => __( "Show categories for results", "advanced-woo-search" ),
                "desc"  => __( "Include categories in products search results.", "advanced-woo-search" ),
                "id"    => "show_result_cats",
                "inherit" => "true",
                "value" => 'true',
                "type"  => "radio",
                'choices' => array(
                    'true'  => __( 'On', 'advanced-woo-search' ),
                    'false' => __( 'Off', 'advanced-woo-search' )
                )
            );

            $options['results'][] = array(
                "name"  => __( "Show brands in products", "advanced-woo-search" ),
                "desc"  => __( "Show brands with all products in search results.", "advanced-woo-search" ),
                "id"    => "show_result_brands",
                "inherit" => "true",
                "value" => 'false',
                "type"  => "radio",
                "depends" => AWS_Helpers::is_plugin_active( 'woocommerce-brands/woocommerce-brands.php' ),
                'choices' => array(
                    'true'  => __( 'On', 'advanced-woo-search' ),
                    'false' => __( 'Off', 'advanced-woo-search' )
                )
            );

            $options['results'][] = array(
                "name"  => __( "Show rating", "advanced-woo-search" ),
                "desc"  => __( "Show product rating.", "advanced-woo-search" ),
                "id"    => "show_rating",
                "inherit" => "true",
                "value" => 'true',
                "type"  => "radio",
                'choices' => array(
                    'true'  => __( 'On', 'advanced-woo-search' ),
                    'false' => __( 'Off', 'advanced-woo-search' )
                )
            );

            $options['results'][] = array(
                "name"  => __( "Show featured", "advanced-woo-search" ),
                "desc"  => __( "Show featured badge near product title.", "advanced-woo-search" ),
                "id"    => "show_featured",
                "inherit" => "true",
                "value" => 'false',
                "type"  => "radio",
                'choices' => array(
                    'true'  => __( 'On', 'advanced-woo-search' ),
                    'false' => __( 'Off', 'advanced-woo-search' )
                )
            );

            $options['results'][] = array(
                "name"  => __( "Show variations attributes", "advanced-woo-search" ),
                "desc"  => __( "Show attributes for parent variable products.", "advanced-woo-search" ),
                "id"    => "show_variations",
                "inherit" => "true",
                "value" => 'false',
                "type"  => "radio",
                'choices' => array(
                    'true'  => __( 'On', 'advanced-woo-search' ),
                    'false' => __( 'Off', 'advanced-woo-search' )
                )
            );

            $options['results'][] = array(
                "name"  => __( "Show price", "advanced-woo-search" ),
                "desc"  => __( "Show product price for each search result.", "advanced-woo-search" ),
                "id"    => "show_price",
                "inherit" => "true",
                "value" => 'true',
                "type"  => "radio",
                'choices' => array(
                    'true'  => __( 'On', 'advanced-woo-search' ),
                    'false' => __( 'Off', 'advanced-woo-search' )
                )
            );

            $options['results'][] = array(
                "name"  => __( "Show price for out of stock", "advanced-woo-search" ),
                "desc"  => __( "Show product price for out of stock products.", "advanced-woo-search" ),
                "id"    => "show_outofstock_price",
                "value" => 'true',
                "type"  => "radio",
                'choices' => array(
                    'true'  => __( 'On', 'advanced-woo-search' ),
                    'false' => __( 'Off', 'advanced-woo-search' ),
                )
            );

            $options['results'][] = array(
                "name"  => __( "Show sale badge", "advanced-woo-search" ),
                "desc"  => __( "Show sale badge for products in search results.", "advanced-woo-search" ),
                "id"    => "show_sale",
                "inherit" => "true",
                "value" => 'true',
                "type"  => "radio",
                'choices' => array(
                    'true'  => __( 'On', 'advanced-woo-search' ),
                    'false' => __( 'Off', 'advanced-woo-search' )
                )
            );

            $options['results'][] = array(
                "name"  => __( "Show product SKU", "advanced-woo-search" ),
                "desc"  => __( "Show product SKU in search results.", "advanced-woo-search" ),
                "id"    => "show_sku",
                "inherit" => "true",
                "value" => 'false',
                "type"  => "radio",
                'choices' => array(
                    'true'  => __( 'On', 'advanced-woo-search' ),
                    'false' => __( 'Off', 'advanced-woo-search' )
                )
            );

            $options['results'][] = array(
                "name"  => __( "Show 'Add to cart'", "advanced-woo-search" ),
                "desc"  => __( "Show 'Add to cart' button for each search result.", "advanced-woo-search" ),
                "id"    => "show_cart",
                "value" => 'false',
                "type"  => "radio",
                'choices' => array(
                    'true'     => __( 'Show', 'advanced-woo-search' ),
                    'quantity' => __( 'Show with quantity box', 'advanced-woo-search' ),
                    'false'    => __( 'Hide', 'advanced-woo-search' )
                )
            );

            $options['results'][] = array(
                "name"  => __( "Show stock status", "advanced-woo-search" ),
                "desc"  => __( "Show stock status for every product in search results.", "advanced-woo-search" ),
                "id"    => "show_stock",
                "inherit" => "true",
                "value" => 'false',
                "type"  => "radio",
                'choices' => array(
                    'true'      => __( 'Show', 'advanced-woo-search' ),
                    'quantity'  => __( 'Show with product quantity', 'advanced-woo-search' ),
                    'false'     => __( 'Hide', 'advanced-woo-search' )
                )
            );

            $options['results'][] = array(
                "name"    => __( "Filter Results", "advanced-woo-search" ),
                "id"      => "excludeinclude",
                "type"    => "heading",
            );

            $options['results'][] = array(
                "name"         => __( "Overview", "advanced-woo-search" ),
                'heading_type' => 'text',
                "desc"         => __( "Filter search results. You can include/exclude search results based on different rules.", "advanced-woo-search" ) . '<br>' .
                                  __( "Combine filter rules to AND or OR logical blocks to create advanced filter logic.", "advanced-woo-search" ) . '<br>' .
                                  __( "Please try not to use too many filters overwise this can impact on search speed.", "advanced-woo-search" ),
                "type"         => "heading"
            );

            $options['results'][] = array(
                "name"    => __( "Products results", "advanced-woo-search" ),
                "desc"    => '',
                "button"  => __( "Filter products search results", "advanced-woo-search" ),
                "id"      => "adv_filters",
                "filter"  => "product",
                "value"   => '',
                "type"    => "filter_rules"
            );

            $options['results'][] = array(
                "name"    => __( "Terms results", "advanced-woo-search" ),
                "desc"    => '',
                "button"  => __( "Filter taxonomies archive pages results", "advanced-woo-search" ),
                "id"      => "adv_filters",
                "filter"  => "term",
                "value"   => '',
                "type"    => "filter_rules"
            );

            $options['results'][] = array(
                "name"    => __( "Users results", "advanced-woo-search" ),
                "desc"    => '',
                "button"  => __( "Filter users archive pages search results", "advanced-woo-search" ),
                "id"      => "adv_filters",
                "filter"  => "user",
                "value"   => '',
                "type"    => "filter_rules"
            );

            $options['general'][] = array(
                "name"  => __( "Search logic", "advanced-woo-search" ),
                "desc"  => __( "Search rules.", "advanced-woo-search" ),
                "id"    => "search_logic",
                "value" => 'or',
                "type"  => "radio",
                'choices' => array(
                    'or'  => __( 'OR. Show result if at least one word exists in product.', 'advanced-woo-search' ),
                    'and'  => __( 'AND. Show result if only all words exists in product.', 'advanced-woo-search' ),
                    //'exact'  => __( 'EXACT MATCH', 'Show result if product contains exact same phrase.' )
                )
            );

            $options['general'][] = array(
                "name"  => __( "Exact match", "advanced-woo-search" ),
                "desc"  => __( "Search only for full word matching or display results even if they match only part of word.", "advanced-woo-search" ),
                "id"    => "search_exact",
                "value" => 'false',
                "type"  => "radio",
                'choices' => array(
                    'true'  => __( 'Yes. Search only for full words matching.', 'advanced-woo-search' ),
                    'false'  => __( 'No. Partial words match search.', 'advanced-woo-search' ),
                )
            );

            $options['general'][] = array(
                "name"  => __( "Description source", "advanced-woo-search" ),
                "desc"  => __( "From where to take product description.<br>If first source is empty data will be taken from other sources.", "advanced-woo-search" ),
                "id"    => "desc_source",
                "inherit" => "true",
                "value" => 'content',
                "type"  => "radio",
                'choices' => array(
                    'content'  => __( 'Content', 'advanced-woo-search' ),
                    'excerpt'  => __( 'Short description', 'advanced-woo-search' ),
                )
            );

            $options['general'][] = array(
                "name"  => __( "Open product in new tab", "advanced-woo-search" ),
                "desc"  => __( "When user clicks on one of the search result new window will be opened.", "advanced-woo-search" ),
                "id"    => "target_blank",
                "inherit" => "true",
                "value" => 'false',
                "type"  => "radio",
                'choices' => array(
                    'true'  => __( 'On', 'advanced-woo-search' ),
                    'false' => __( 'Off', 'advanced-woo-search' )
                )
            );

            $options['general'][] = array(
                "name"  => __( "Image source", "advanced-woo-search" ),
                "desc"  => __( "Source of image that will be shown with search results. Position of fields show the priority of each source.", "advanced-woo-search" ),
                "id"    => "image_source",
                "value" => "featured,gallery,content,description,default",
                "choices" => array(
                    "featured"    => __( "Featured image", "advanced-woo-search" ),
                    "gallery"     => __( "Gallery first image", "advanced-woo-search" ),
                    "content"     => __( "Content first image", "advanced-woo-search" ),
                    "description" => __( "Description first image", "advanced-woo-search" ),
                    "default"     => __( "Default image", "advanced-woo-search" )
                ),
                "type"  => "sortable"
            );

            $options['general'][] = array(
                "name"  => __( "Default image", "advanced-woo-search" ),
                "desc"  => __( "Default image for search results.", "advanced-woo-search" ),
                "id"    => "default_img",
                "value" => "",
                "type"  => "image",
                'size'  => "thumbnail"
            );

            $options['general'][] = array(
                "name"  => __( "Use Google Analytics", "advanced-woo-search" ),
                "desc"  => __( "Use google analytics to track searches. You need google analytics to be installed on your site.", "advanced-woo-search" ) .
                    '<br>' . sprintf( __( "Data will be visible inside Google Analytics 'Site Search' report. Need to activate 'Site Search' feature inside GA. %s", "advanced-woo-search" ), '<a href="https://advanced-woo-search.com/guide/google-analytics/" target="_blank">' . __( 'More info', 'advanced-woo-search' ) . '</a>' ) .
                    '<br>' . __( "Also will send event with category - 'AWS search', action - 'AWS Search Form {form_id}' and label of value of search term.", "advanced-woo-search" ),
                "id"    => "use_analytics",
                "inherit" => "true",
                "value" => 'false',
                "type"  => "radio",
                'choices' => array(
                    'true'  => __( 'On', 'advanced-woo-search' ),
                    'false'  => __( 'Off', 'advanced-woo-search' ),
                )
            );

            $options['general'][] = array(
                "name"    => __( "Search results page", "advanced-woo-search" ),
                "type"    => "heading"
            );

            $options['general'][] = array(
                "name"  => __( "Enable results page", "advanced-woo-search" ),
                "desc"  => __( "Show plugin search results on a separated search results page. Will use your current theme products search results page template.", "advanced-woo-search" ),
                "id"    => "search_page",
                "inherit" => "true",
                "value" => 'true',
                "type"  => "radio",
                'choices' => array(
                    'true'  => __( 'On', 'advanced-woo-search' ),
                    'false'  => __( 'Off', 'advanced-woo-search' ),
                )
            );

            $options['general'][] = array(
                "name"  => __( "Max number of results", "advanced-woo-search" ),
                "desc"  => __( "Maximal total number of search results. Larger values can lead to slower search speed.", "advanced-woo-search" ),
                "id"    => "search_page_res_num",
                "inherit" => "true",
                "value" => 100,
                "type"  => "number"
            );

            $options['general'][] = array(
                "name"  => __( "Results per page", "advanced-woo-search" ),
                "desc"  => __( "Number of search results per page. Empty or 0 - use theme default value.", "advanced-woo-search" ),
                "id"    => "search_page_res_per_page",
                "inherit" => "true",
                "value" => '',
                "type"  => "number"
            );

            $options['general'][] = array(
                "name"  => __( "Change query hook", "advanced-woo-search" ),
                "desc"  => __( "If you have any problems with correct products results on the search results page - try to change this option.", "advanced-woo-search" ),
                "id"    => "search_page_query",
                "inherit" => "true",
                "value" => 'default',
                "type"  => "radio",
                'choices' => array(
                    'default' => __( 'Default', 'advanced-woo-search' ),
                    'posts_pre_query' => __( 'posts_pre_query', 'advanced-woo-search' ),
                )
            );

            $options = self::inherit_free_options( $options );

            /**
             * Filter admin page options
             * @since 2.15
             * @param array $options Array of options
             */
            $options = apply_filters( 'aws_admin_page_options', $options );

            return $options;

        }

        /*
         * Include filter conditions
         * @return array
         */
        static public function include_filters() {

            $options = array();

            $options['product'][] = array(
                "name" => __( "Product", "advanced-woo-search" ),
                "id"   => "product",
                "type" => "callback_ajax",
                "ajax" => "aws-searchForProducts",
                "placeholder" => __( "Search for a product...", "advanced-woo-search" ),
                "operators" => "equals",
                "choices" => array(
                    'callback' => 'AWS_Admin_Filters_Helpers::get_product',
                    'params'   => array()
                ),
            );

            $options['product'][] = array(
                "name" => __( "Product is featured", "advanced-woo-search" ),
                "id"   => "product_featured",
                "type" => "bool",
                "operators" => "equals",
            );

            $options['product'][] = array(
                "name" => __( "Product category", "advanced-woo-search" ),
                "id"   => "product_category",
                "type" => "callback",
                "operators" => "equals",
                "choices" => array(
                    'callback' => 'AWS_Admin_Filters_Helpers::get_tax_terms',
                    'params'   => array( 'product_cat' )
                ),
            );

            $options['product'][] = array(
                "name" => __( "Product tag", "advanced-woo-search" ),
                "id"   => "product_tag",
                "type" => "callback",
                "operators" => "equals",
                "choices" => array(
                    'callback' => 'AWS_Admin_Filters_Helpers::get_tax_terms',
                    'params'   => array( 'product_tag' )
                ),
            );

            $options['product'][] = array(
                "name" => __( "Product taxonomy", "advanced-woo-search" ),
                "id"   => "product_taxonomy",
                "type" => "callback",
                "operators" => "equals",
                "choices" => array(
                    'callback' => 'AWS_Admin_Filters_Helpers::get_tax_terms',
                    'params'   => array()
                ),
                "suboption" => array(
                    'callback' => 'AWS_Admin_Filters_Helpers::get_tax',
                    'params'   => array()
                ),
            );

            $options['product'][] = array(
                "name" => __( "Product attributes", "advanced-woo-search" ),
                "id"   => "product_attributes",
                "type" => "callback",
                "operators" => "equals",
                "choices" => array(
                    'callback' => 'AWS_Admin_Filters_Helpers::get_tax_terms',
                    'params'   => array()
                ),
                "suboption" => array(
                    'callback' => 'AWS_Admin_Filters_Helpers::get_attributes',
                    'params'   => array()
                ),
            );

            $options['product'][] = array(
                "name" => __( "Product custom attributes", "advanced-woo-search" ),
                "id"   => "product_custom_attributes",
                "type" => "callback",
                "operators" => "equals",
                "choices" => array(
                    'callback' => 'AWS_Admin_Filters_Helpers::get_custom_attributes',
                    'params'   => array()
                ),
                "suboption" => array(
                    'callback' => 'AWS_Admin_Filters_Helpers::get_custom_attributes',
                    'params'   => array()
                ),
            );

            $options['product'][] = array(
                "name" => __( "Product custom fields", "advanced-woo-search" ),
                "id"   => "product_meta",
                "type" => "callback",
                "operators" => "equals",
                "choices" => array(
                    'callback' => 'AWS_Admin_Filters_Helpers::get_custom_fields',
                    'params'   => array()
                ),
                "suboption" => array(
                    'callback' => 'AWS_Admin_Filters_Helpers::get_custom_fields',
                    'params'   => array()
                ),
            );

            $options['product'][] = array(
                "name" => __( "Product shipping class", "advanced-woo-search" ),
                "id"   => "product_shipping_class",
                "type" => "callback",
                "operators" => "equals",
                "choices" => array(
                    'callback' => 'AWS_Admin_Filters_Helpers::get_tax_terms',
                    'params'   => array( 'product_shipping_class' )
                ),
            );

            $options['term'][] = array(
                "name" => __( "Term page taxonomy", "advanced-woo-search" ),
                "id"   => "term_taxonomy",
                "type" => "callback",
                "operators" => "equals",
                "choices" => array(
                    'callback' => 'AWS_Admin_Filters_Helpers::get_tax_terms',
                    'params'   => array()
                ),
                "suboption" => array(
                    'callback' => 'AWS_Admin_Filters_Helpers::get_all_tax',
                    'params'   => array()
                ),
            );

            $options['term'][] = array(
                "name" => __( "Term products count", "advanced-woo-search" ),
                "id"   => "term_count",
                "type" => "number",
                "operators" => "equals_compare",
            );

            $options['term'][] = array(
                "name" => __( "Term hierarchy type", "advanced-woo-search" ),
                "id"   => "term_hierarchy",
                "type" => "callback",
                "operators" => "equals",
                "choices" => array(
                    'callback' => 'AWS_Admin_Filters_Helpers::get_terms_hierarchy',
                    'params'   => array()
                ),
            );

            $options['term'][] = array(
                "name" => __( "Term has image", "advanced-woo-search" ),
                "id"   => "term_has_image",
                "type" => "bool",
                "operators" => "equals",
            );

            $options['user'][] = array(
                "name" => __( "User", "advanced-woo-search" ),
                "id"   => "user_page_user",
                "type" => "callback",
                "operators" => "equals",
                "choices" => array(
                    'callback' => 'AWS_Admin_Filters_Helpers::get_users',
                    'params'   => array()
                ),
            );

            $options['user'][] = array(
                "name" => __( "User role", "advanced-woo-search" ),
                "id"   => "user_page_role",
                "type" => "callback",
                "operators" => "equals",
                "choices" => array(
                    'callback' => 'AWS_Admin_Filters_Helpers::get_user_roles',
                    'params'   => array()
                ),
            );

            $options['user'][] = array(
                "name" => __( "User products count", "advanced-woo-search" ),
                "id"   => "user_page_count",
                "type" => "number",
                "operators" => "equals_compare",
            );

            /**
             * Filter filter rules
             * @since 2.45
             * @param array $options Array of filter rules
             */
            $options = apply_filters( 'aws_admin_filter_rules', $options );

            return $options;

        }

        /*
         * Inherit some options from the free plugin version if exists
         * @return array
         */
        static public function inherit_free_options( $options ) {

            $current_version = get_option( 'aws_pro_plugin_ver' );
            $settings = self::get_settings();
            $common_settings = self::get_common_settings();
            $free_settings = get_option( 'aws_settings' );

            // First activation
            if ( ! $current_version ) {

                if ( $free_settings && ( ! $settings || ! $common_settings ) ) {

                    $outofstock = isset( $free_settings['outofstock'] ) ? $free_settings['outofstock'] : false;

                    foreach ( $options as $tab_name => $tab_options ) {
                        foreach( $tab_options as $option_key => $tab_option ) {

                            if ( isset( $tab_option['id'] ) && isset( $tab_option['inherit'] ) ) {
                                $option_id = ( $tab_option['inherit'] && $tab_option['inherit'] !== 'true' ) ? $tab_option['inherit'] : $tab_option['id'];
                                if ( isset( $free_settings[$option_id] ) ) {
                                    $options[$tab_name][$option_key]['value'] = $free_settings[$option_id];
                                }
                            }

                            if ( isset( $tab_option['id'] ) && $tab_option['id'] === 'product_stock_status' && $outofstock && $outofstock === 'false' ) {
                                $options[$tab_name][$option_key]['value']['out_of_stock'] = 0;
                            }

                        }
                    }

                    $synonyms = get_option( 'aws_pro_synonyms' );
                    if ( $synonyms === false && isset( $free_settings['synonyms'] ) && $free_settings['synonyms'] ) {
                        update_option( 'aws_pro_synonyms', $free_settings['synonyms'], 'no' );
                    }

                    $seamless = get_option( 'aws_pro_seamless' );
                    if ( $seamless === false && isset( $free_settings['seamless'] ) && $free_settings['seamless'] ) {
                        update_option( 'aws_pro_seamless', $free_settings['seamless'] );
                    }

                }

            }

            return $options;

        }

    }

endif;