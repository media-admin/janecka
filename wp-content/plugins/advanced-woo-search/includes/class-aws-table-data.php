<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'AWS_Table_Data' ) ) :

    /**
     * Class for admin condition rules
     */
    class AWS_Table_Data {

        /**
         * @var object AWS_Table_Data Product object
         */
        private $product;

        /**
         * @var int AWS_Table_Data Product id
         */
        private $id;

        /**
         * @var array AWS_Table_Data Index options
         */
        private $options;

        /**
         * @var string AWS_Table_Data Current language
         */
        private $lang = '';

        /**
         * @var array AWS_Table_Data Product data
         */
        private $scraped_data = array();

        /*
         * Constructor
         */
        public function __construct( $product, $id, $options ) {

            $this->product = $product;

            $this->id = $id;

            $this->options = $options;

            $this->lang = $this->get_lang();

        }

        /*
         * Scrap data from product
         *
         * @return array
         */
        public function scrap_data() {

            $product = $this->product;

            $data = array();

            $data['id'] = $this->id;

            $data['terms'] = array();

            $data['in_stock'] = $this->get_stock_status();
            $data['on_sale'] = $product->is_on_sale();
            $data['visibility'] = $this->get_visibility();
            $data['lang'] = $this->lang ? $this->lang : '';

            $ids = $data['id'];

            $sku = $product->get_sku();
            $title = get_the_title( $data['id'] );
            $content = get_post_field( 'post_content', $data['id'] );
            $excerpt = get_post_field( 'post_excerpt', $data['id'] );

            $cat_array = $this->options['index']['category'] ? AWS_Helpers::get_terms_array( $data['id'], 'product_cat' ) : false;
            $tag_array = $this->options['index']['tag'] ? AWS_Helpers::get_terms_array( $data['id'], 'product_tag' ) : false;

            if ( $this->options['apply_filters'] ) {
                $content = apply_filters( 'the_content', $content, $data['id'] );
            } elseif( isset( $this->options['do_shortcodes'] ) && $this->options['do_shortcodes'] ) {
                $content = do_shortcode( $content );
            }

            // Get all child products if exists
            if ( $product->is_type( 'variable' ) && class_exists( 'WC_Product_Variation' ) && $this->options['index']['variations'] ) {

                if ( sizeof( $product->get_children() ) > 0 ) {

                    foreach ( $product->get_children() as $child_id ) {

                        $variation_product = new WC_Product_Variation( $child_id );

                        if ( method_exists( $variation_product, 'get_status' ) && $variation_product->get_status() === 'private' ) {
                            continue;
                        }

                        $variation_sku = $variation_product->get_sku();

                        $variation_desc = '';
                        if ( method_exists( $variation_product, 'get_description' ) ) {
                            $variation_desc = $variation_product->get_description();
                        }

                        if ( $variation_sku ) {
                            $sku = $sku . ' ' . $variation_sku;
                        }

                        $ids = $ids . ' ' . $child_id;

                        if ( $variation_desc ) {
                            $content = $content . ' ' . $variation_desc;
                        }

                    }

                }

            }

            // Get content from Custom Product Tabs
            if ( $custom_tabs = get_post_meta( $data['id'], 'yikes_woo_products_tabs' ) ) {
                if ( $custom_tabs && ! empty( $custom_tabs ) ) {
                    foreach( $custom_tabs as $custom_tab_array ) {
                        if ( $custom_tab_array && ! empty( $custom_tab_array ) ) {
                            foreach( $custom_tab_array as $custom_tab ) {
                                if ( isset( $custom_tab['content'] ) && $custom_tab['content'] ) {
                                    $content = $content . ' ' . $custom_tab['content'];
                                }
                            }
                        }
                    }
                }
            }

            // WP 4.2 emoji strip
            if ( function_exists( 'wp_encode_emoji' ) ) {
                $content = wp_encode_emoji( $content );
            }

            $content = AWS_Helpers::strip_shortcodes( $content );
            $excerpt = AWS_Helpers::strip_shortcodes( $excerpt );

            /**
             * Filters product title before it will be indexed.
             *
             * @since 1.37
             *
             * @param string $title Product title.
             * @param int $data['id'] Product id.
             * @param object $product Current product object.
             */
            $title = apply_filters( 'aws_indexed_title', $title, $data['id'], $product );

            /**
             * Filters product content before it will be indexed.
             *
             * @since 1.37
             *
             * @param string $content Product content.
             * @param int $data['id'] Product id.
             * @param object $product Current product object.
             */
            $content = apply_filters( 'aws_indexed_content', $content, $data['id'], $product );

            /**
             * Filters product excerpt before it will be indexed.
             *
             * @since 1.37
             *
             * @param string $excerpt Product excerpt.
             * @param int $data['id'] Product id.
             * @param object $product Current product object.
             */
            $excerpt = apply_filters( 'aws_indexed_excerpt', $excerpt, $data['id'], $product );

            $data['terms']['title']    = $this->options['index']['title'] ? $this->extract_terms( $title, 'title' ) : '';
            $data['terms']['content']  = $this->options['index']['content'] ? $this->extract_terms( $content, 'content' ) : '';
            $data['terms']['excerpt']  = $this->options['index']['excerpt'] ? $this->extract_terms( $excerpt, 'excerpt' ) : '';
            $data['terms']['sku']      = $this->options['index']['sku'] ? $this->extract_terms( $sku, 'sku' ) : '';
            $data['terms']['id']       = $this->options['index']['id'] ? $this->extract_terms( $ids, 'id' ) : '';


            if ( $cat_array && ! empty( $cat_array ) ) {
                foreach( $cat_array as $cat_source => $cat_terms ) {
                    $data['terms'][$cat_source] = $this->extract_terms( $cat_terms, 'cat' );
                }
            }

            if ( $tag_array && ! empty( $tag_array ) ) {
                foreach( $tag_array as $tag_source => $tag_terms ) {
                    $data['terms'][$tag_source] = $this->extract_terms( $tag_terms, 'tag' );
                }
            }

            // Get translations if exists ( WPML )
            if ( defined( 'ICL_SITEPRESS_VERSION' ) && has_filter('wpml_element_has_translations') && has_filter('wpml_get_element_translations') ) {

                $is_translated = apply_filters( 'wpml_element_has_translations', NULL, $data['id'], 'post_product' );

                if ( $is_translated ) {

                    $translations = apply_filters( 'wpml_get_element_translations', NULL, $data['id'], 'post_product');

                    foreach( $translations as $language => $lang_obj ) {
                        if ( ! $lang_obj->original && $lang_obj->post_status === 'publish' ) {
                            $translated_post = get_post( $lang_obj->element_id );
                            if ( $translated_post && !empty( $translated_post ) ) {

                                $translated_post_data = array();
                                $translated_post_data['id'] = $translated_post->ID;
                                $translated_post_data['in_stock'] = $data['in_stock'];
                                $translated_post_data['on_sale'] = $data['on_sale'];
                                $translated_post_data['visibility'] = $data['visibility'];
                                $translated_post_data['lang'] = $lang_obj->language_code;
                                $translated_post_data['terms'] = array();

                                $translated_title = get_the_title( $translated_post->ID );
                                $translated_content = get_post_field( 'post_content', $translated_post->ID );
                                $translated_excerpt = get_post_field( 'post_excerpt', $translated_post->ID );

                                if ( $this->options['apply_filters'] ) {
                                    $translated_content = apply_filters( 'the_content', $translated_content, $translated_post->ID );
                                }

                                $translated_content = AWS_Helpers::strip_shortcodes( $translated_content );
                                $translated_excerpt = AWS_Helpers::strip_shortcodes( $translated_excerpt );

                                $translated_post_data['terms']['title'] = $this->options['index']['title'] ? $this->extract_terms( $translated_title, 'title' ) : '';
                                $translated_post_data['terms']['content'] = $this->options['index']['content'] ? $this->extract_terms( $translated_content, 'content' ) : '';
                                $translated_post_data['terms']['excerpt'] = $this->options['index']['excerpt'] ? $this->extract_terms( $translated_excerpt, 'excerpt' ) : '';
                                $translated_post_data['terms']['sku'] = $this->options['index']['sku'] ? $this->extract_terms( $sku, 'sku' ) : '';
                                $translated_post_data['terms']['id'] = $this->options['index']['id'] ? $this->extract_terms( $translated_post->ID, 'id' ) : '';

                                $this->scraped_data[] = $translated_post_data;

                            }
                        }
                    }

                }

            }
            elseif ( function_exists( 'qtranxf_use' ) ) {

                $enabled_languages = get_option( 'qtranslate_enabled_languages' );

                if ( $enabled_languages ) {

                    foreach( $enabled_languages as $current_lang ) {

                        if ( $current_lang == $this->lang ) {
                            $default_lang_title = qtranxf_use( $current_lang, $product->get_name(), true, true );
                            $data['terms']['title'] = $this->extract_terms( $default_lang_title, 'title' );
                            continue;
                        }

                        if ( function_exists( 'qtranxf_getAvailableLanguages' ) ) {

                            global $wpdb;

                            $qtrans_content = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE ID = %d", $data['id'] ) );

                            if ( $qtrans_content ) {

                                $languages_title = qtranxf_getAvailableLanguages( $qtrans_content->post_title );
                                $languages_content = qtranxf_getAvailableLanguages( $qtrans_content->post_content );

                                if ( ( $languages_title && in_array( $current_lang, $languages_title ) ) || ( $languages_content && in_array( $current_lang, $languages_content ) ) ) {

                                    if ( method_exists( $product, 'get_description' ) && method_exists( $product, 'get_name' ) && method_exists( $product, 'get_short_description' ) ) {

                                        $translated_post_data = array();
                                        $translated_post_data['id'] = $data['id'];
                                        $translated_post_data['in_stock'] = $data['in_stock'];
                                        $translated_post_data['on_sale'] = $data['on_sale'];
                                        $translated_post_data['visibility'] = $data['visibility'];
                                        $translated_post_data['lang'] = $current_lang;
                                        $translated_post_data['terms'] = array();

                                        $translated_title = qtranxf_use( $current_lang, $product->get_name(), true, true );
                                        $translated_content = qtranxf_use( $current_lang, $product->get_description(), true, true );
                                        $translated_excerpt = qtranxf_use( $current_lang, $product->get_short_description(), true, true );

                                        $translated_content = AWS_Helpers::strip_shortcodes( $translated_content );
                                        $translated_excerpt = AWS_Helpers::strip_shortcodes( $translated_excerpt );

                                        $translated_post_data['terms']['title'] = $this->options['index']['title'] ? $this->extract_terms( $translated_title, 'title' ) : '';
                                        $translated_post_data['terms']['content'] = $this->options['index']['content'] ? $this->extract_terms( $translated_content, 'content' ) : '';
                                        $translated_post_data['terms']['excerpt'] = $this->options['index']['excerpt'] ? $this->extract_terms( $translated_excerpt, 'excerpt' ) : '';
                                        $translated_post_data['terms']['sku'] = $this->options['index']['sku'] ? $this->extract_terms( $sku, 'sku' ) : '';
                                        $translated_post_data['terms']['id'] = $this->options['index']['id'] ?  $this->extract_terms( $ids, 'id' ) : '';

                                        $this->scraped_data[] = $translated_post_data;

                                    }

                                }

                            }

                        }

                    }

                }

            }  elseif ( defined( 'FALANG_VERSION' ) ) {
                $falang_post = new \Falang\Core\Post($data['id']);
                $is_translated = $falang_post->is_post_type_translatable($falang_post->post_type );

                if ($is_translated){
                    $languages = Falang()->get_model()->get_languages_list( array( 'hide_default' => true )  );

                    foreach ($languages as $language) {
                        $translated_post_data = array();
                        $translated_post_data['id'] = $data['id'];
                        $translated_post_data['in_stock'] = $data['in_stock'];
                        $translated_post_data['on_sale'] = $data['on_sale'];
                        $translated_post_data['visibility'] = $data['visibility'];
                        $translated_post_data['lang'] = $language->slug;
                        $translated_post_data['terms'] = array();

                        $post = get_post($data['id']);
                        $translated_title = $falang_post->translate_post_field($post, 'post_title', $language);
                        $translated_content = $falang_post->translate_post_field($post, 'post_content', $language);
                        $translated_excerpt = $falang_post->translate_post_field($post, 'post_excerpt', $language);

                        $translated_post_data['terms']['title'] = $this->options['index']['title'] ? $this->extract_terms( $translated_title, 'title' ) : '';
                        $translated_post_data['terms']['content'] = $this->options['index']['content'] ? $this->extract_terms( $translated_content, 'content' ) : '';
                        $translated_post_data['terms']['excerpt'] = $this->options['index']['excerpt'] ? $this->extract_terms( $translated_excerpt, 'excerpt' ) : '';
                        $translated_post_data['terms']['sku'] = $this->options['index']['sku'] ? $this->extract_terms( $sku, 'sku' ) : '';
                        $translated_post_data['terms']['id'] = $this->options['index']['id'] ? $this->extract_terms( $data['id'], 'id' ) : '';

                        $this->scraped_data[] = $translated_post_data;

                    }
                }
            }

            $this->scraped_data[] = $data;

            return $this->scraped_data;

        }

        /*
         * Get current language
         *
         * @return string
         */
        private function get_lang() {

            $lang = '';

            if ( defined( 'ICL_SITEPRESS_VERSION' ) && has_filter( 'wpml_post_language_details' ) ) {
                $lang = apply_filters( 'wpml_post_language_details', NULL, $this->id );
                $lang = $lang['language_code'];
            } elseif ( function_exists( 'pll_default_language' ) && function_exists( 'pll_get_post_language' ) ) {
                $lang = pll_get_post_language( $this->id ) ? pll_get_post_language( $this->id ) : pll_default_language();
            } elseif ( function_exists( 'qtranxf_getLanguageDefault' ) ) {
                $lang = qtranxf_getLanguageDefault();
            } elseif ( defined( 'FALANG_VERSION' ) ) {
                $lang = Falang()->get_current_language()->slug;
            }

            return $lang;

        }

        /*
         * Extract terms from content
         */
        private function extract_terms( $str, $source = '' ) {

            // Avoid single A-Z.
            //$str = preg_replace( '/\b\w{1}\b/i', " ", $str );
            //if ( ! $term || ( 1 === strlen( $term ) && preg_match( '/^[a-z]$/i', $term ) ) )

            $str = AWS_Helpers::normalize_string( $str );

            $str = str_replace( array(
                "Ă‹â€ˇ",
                "Ă‚Â°",
                "Ă‹â€ş",
                "Ă‹ĹĄ",
                "Ă‚Â¸",
                "Ă‚Â§",
                "=",
                "Ă‚Â¨",
                "â€™",
                "â€",
                "â€ť",
                "â€ś",
                "â€ž",
                "Â´",
                "â€”",
                "â€“",
                "Ă—",
                '&#8217;',
                "&nbsp;",
                chr( 194 ) . chr( 160 )
            ), " ", $str );

            $str = str_replace( 'Ăź', 'ss', $str );

            $str = preg_replace( '/^[a-z]$/i', "", $str );

            $str = preg_replace( '/\s+/', ' ', $str );

            /**
             * Filters extracted string
             *
             * @since 1.44
             *
             * @param string $str String of product content
             * @param @since 1.97 string $source Terms source
             */
            $str = apply_filters( 'aws_extracted_string', $str, $source );

            $str_array = explode( ' ', $str );
            $str_array = AWS_Helpers::filter_stopwords( $str_array );
            $str_array = array_count_values( $str_array );

            /**
             * Filters extracted terms before adding to index table
             *
             * @since 1.44
             *
             * @param string $str_array Array of terms
             * @param @since 1.97 string $source Terms source
             */
            $str_array = apply_filters( 'aws_extracted_terms', $str_array, $source );

            $str_new_array = array();

            // Remove e, es, ies from the end of the string
            if ( ! empty( $str_array ) && $str_array ) {
                foreach( $str_array as $str_item_term => $str_item_num ) {
                    if ( $str_item_term  ) {

                        if ( ! isset( $str_new_array[$str_item_term] ) && preg_match("/es$/", $str_item_term ) ) {
                            $str_new_array[$str_item_term] = $str_item_num;
                        }

                        $new_array_key = AWS_Plurals::singularize( $str_item_term );

                        if ( $new_array_key && strlen( $str_item_term ) > 3 && strlen( $new_array_key ) > 2 ) {
                            if ( ! isset( $str_new_array[$new_array_key] ) ) {
                                $str_new_array[$new_array_key] = $str_item_num;
                            }
                            if ( $source === 'sku' ) {
                                $str_new_array[$str_item_term] = $str_item_num;
                            }
                        } else {
                            if ( ! isset( $str_new_array[$str_item_term] ) ) {
                                $str_new_array[$str_item_term] = $str_item_num;
                            }
                        }

                    }
                }
            }

            $str_new_array = AWS_Helpers::get_synonyms( $str_new_array );

            return $str_new_array;

        }

        /*
         * Get product stock status
         *
         * @return string
         */
        private function get_stock_status() {

            $stock_status = 1;

            if ( method_exists( $this->product, 'get_stock_status' ) ) {
                $stock_status = $this->product->get_stock_status() === 'outofstock' ? 0 : 1;
            } elseif ( method_exists( $this->product, 'is_in_stock' ) ) {
                $stock_status = $this->product->is_in_stock();
            }

            return $stock_status;

        }

        /*
         * Get product visibility
         *
         * @return string
         */
        private function get_visibility() {

            $visibility = 'visible';

            if ( method_exists( $this->product, 'get_catalog_visibility' ) ) {
                $visibility = $this->product->get_catalog_visibility();
            } elseif ( method_exists( $this->product, 'get_visibility' ) ) {
                $visibility = $this->product->get_visibility();
            } else  {
                $visibility = $this->product->visibility;
            }

            return $visibility;

        }

    }

endif;