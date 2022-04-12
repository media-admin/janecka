<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'AWS_Table' ) ) :

    /**
     * Class for plugin index table
     */
    class AWS_Table {

        /**
         * @var AWS_Table Index table name
         */
        private $table_name;

        /**
         * @var AWS_Table Data
         */
        private $data;

        /**
         * Constructor
         */
        public function __construct() {

            global $wpdb;

            $this->table_name = $wpdb->prefix . AWS_INDEX_TABLE_NAME;

            // Create, update product
            add_action( 'woocommerce_after_product_object_save', array( $this, 'woocommerce_after_product_object_save' ) );

            // Change product status
            add_action( 'wp_insert_post', array( $this, 'product_changed' ), 10, 3 );

            // Delete product
            add_action( 'delete_post', array( $this, 'product_deleted' ), 10, 2 );

            add_action( 'create_term', array( &$this, 'term_changed' ), 10, 3 );
            add_action( 'delete_term', array( &$this, 'term_changed' ), 10, 3 );
            add_action( 'edit_term', array( &$this, 'term_changed' ), 10, 3 );

            add_action( 'delete_term', array( $this, 'term_deleted' ), 10, 4 );

            add_action( 'added_post_meta', array( $this, 'meta_changed' ), 10, 4 );
            add_action( 'updated_postmeta', array( $this, 'meta_changed' ), 10, 4 );
            add_action( 'deleted_post_meta', array( $this, 'meta_changed' ), 10, 4 );

            add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'attributes_changed' ), 10, 3 );

            add_action( 'woocommerce_delete_product_variation', array( $this, 'variation_deleted' ) );
            add_action( 'woocommerce_trash_product_variation', array( $this, 'variation_deleted' ) );

            add_action( 'woocommerce_product_set_stock_status', array( $this, 'stock_status_changes' ), 10, 3 );
            add_action( 'woocommerce_variation_set_stock_status', array( $this, 'stock_status_changes' ), 10, 3 );

            add_action( 'updated_postmeta', array( $this, 'updated_custom_tabs' ), 10, 4 );

            add_action( 'wp_ajax_aws-reindex', array( $this, 'reindex_table_ajax' ) );

            add_action( 'aws_reindex_table', array( $this, 'reindex_table_job' ) );

            add_action( 'aws_reindex_product', array( $this, 'reindex_product_action' ) );

            add_filter( 'aws_meta_keys_include', array( $this, 'meta_keys_include' ), 1 );

        }

        /*
         * Reindex plugin table ajax hook
         */
        public function reindex_table_ajax() {

            check_ajax_referer( 'aws_pro_admin_ajax_nonce' );

            if ( function_exists( 'wp_raise_memory_limit' ) ) {
                wp_raise_memory_limit( 'admin' );
            }

            @set_time_limit( 600 );

            $this->reindex_table();

        }

        /*
         * Reindex plugin table
         */
        public function reindex_table( $data = false ) {

            ob_start();

            global $wpdb;

            $index_meta = $data ? $data : $_POST['data'];
            $status = false;

            // If something goes wrong during last index start from latest indexed product
            if ( 'start' === $index_meta ) {
                $aws_index_processed = get_transient( 'aws_index_processed' );

                if ( $aws_index_processed ) {
                    $index_meta = $aws_index_processed;
                }
            }

            // No current index going on. Let's start over
            if ( 'start' === $index_meta ) {

                $status = 'start';
                $index_meta = array(
                    'offset' => 0,
                    'start' => true,
                );

                $wpdb->query("DROP TABLE IF EXISTS {$this->table_name}");

                $this->create_table();

                if ( AWS_PRO()->cache->is_cache_table_not_exist() ) {
                    AWS_PRO()->cache->create_cache_table();
                }

                $index_meta['found_posts'] = $this->get_number_of_products();

            } else if ( ! empty( $index_meta['site_stack'] ) && $index_meta['offset'] >= $index_meta['found_posts'] ) {
                $status = 'start';

                $index_meta['start'] = true;
                $index_meta['offset'] = 0;
                $index_meta['current_site'] = array_shift( $index_meta['site_stack'] );
            } else {
                $index_meta['start'] = false;
            }


            $index_meta = apply_filters( 'aws_pro_index_meta', $index_meta );
            $posts_per_page = apply_filters( 'aws_index_posts_per_page', 20 );


            if ( $status !== 'start' ) {

                $posts = array();

                $queued_posts = get_posts( array(
                    'posts_per_page'      => $posts_per_page,
                    'fields'              => 'ids',
                    'post_type'           => 'product',
                    'post_status'         => 'publish',
                    'offset'              => $index_meta['offset'],
                    'ignore_sticky_posts' => true,
                    'suppress_filters'    => true,
                    'has_password'        => false,
                    'no_found_rows'       => 1,
                    'orderby'             => 'ID',
                    'order'               => 'DESC',
                    'lang'                => ''
                ) );

                if ( $queued_posts && count( $queued_posts ) ) {
                    foreach( $queued_posts as $post_id ) {
                        $posts[] = absint( $post_id );
                    }
                }

                if ( $posts && count( $posts ) > 0 ) {

                    $this->fill_table( $posts );

                    $index_meta['offset'] = absint( $index_meta['offset'] + $posts_per_page );

                    if ( $index_meta['offset'] >= $index_meta['found_posts'] ) {
                        $index_meta['offset'] = $index_meta['found_posts'];
                    }

                    set_transient( 'aws_index_processed', $index_meta, 60*60 );

                } else {

                    // We are done (with this site)

                    $index_meta['offset'] = (int) count( $posts );

                    do_action('aws_cache_clear');

                    update_option( 'aws_pro_reindex_version', AWS_PRO_VERSION );

                    delete_transient( 'aws_index_processed' );

                    do_action( 'aws_index_complete', $index_meta );

                }

            }

            ob_end_clean();

            if ( $data ) {
                return $index_meta;
            } else {
                wp_send_json_success( $index_meta );
            }

        }

        /*
         * Cron job function
         */
        public function reindex_table_job() {

            /*
             * Added in WordPress v4.6.0
             */
            if ( function_exists( 'wp_raise_memory_limit' ) ) {
                wp_raise_memory_limit( 'admin' );
            }

            /**
             * Max execution time for script
             * @since 1.50
             * @param integer
             */
            @set_time_limit( apply_filters( 'aws_index_cron_runner_time_limit', 600 ) );

            $meta = get_option( 'aws_cron_job' );

            if ( ! $meta || ! is_array( $meta ) ) {
                $meta = 'start';
            } else {
                $meta['attemps'] = (int) isset( $meta['attemps'] ) ? $meta['attemps'] + 1 : 1;
            }

            /**
             * Max number of script repeats
             * @since 1.50
             * @param integer
             */
            $max_cron_attemps = apply_filters( 'aws_index_max_cron_attemps', 10 );

            try {

                do {

                    wp_clear_scheduled_hook( 'aws_reindex_table', array( 'inner' ) );

                    // Fallback if re-index failed by timeout in this iteration
                    if ( ! isset( $meta['attemps'] ) || ( isset( $meta['attemps'] ) && $meta['attemps'] < $max_cron_attemps ) ) {
                        if ( ! wp_next_scheduled( 'aws_reindex_table', array( 'inner' ) ) ) {
                            wp_schedule_single_event( time() + 60, 'aws_reindex_table', array( 'inner' ) );
                        }
                    }

                    $meta = $this->reindex_table( $meta );
                    $offset = (int) isset( $meta['offset'] ) ? $meta['offset'] : 0;
                    $start = (int) isset( $meta['start'] ) ? $meta['start'] : 0;

                    // No more attemps
                    if ( isset( $meta['attemps'] ) && $meta['attemps'] >= $max_cron_attemps ) {
                        delete_option( 'aws_cron_job' );
                    } else {
                        update_option( 'aws_cron_job', $meta );
                    }

                } while ( !( $offset === 0 && ! $start ) );

            } catch ( Exception $e ) {

            }

            // Its no longer needs
            wp_clear_scheduled_hook( 'aws_reindex_table', array( 'inner' ) );

            delete_option( 'aws_cron_job' );

        }

        /*
         * Get total number of products
         */
        private function get_number_of_products() {

            $args = array(
                'posts_per_page'      => -1,
                'fields'              => 'ids',
                'post_type'           => 'product',
                'post_status'         => 'publish',
                'ignore_sticky_posts' => true,
                'suppress_filters'    => true,
                'has_password'        => false,
                'no_found_rows'       => 1,
                'orderby'             => 'ID',
                'order'               => 'DESC',
                'lang'                => ''
            );


            $posts = get_posts( $args );

            if ( $posts && count( $posts ) > 0 ) {
                $count = count( $posts );
            } else {
                $count = 0;
            }

            return $count;

        }

        /*
         * Create index table
         */
        private function create_table() {

            global $wpdb;

            $charset_collate = $wpdb->get_charset_collate();
            $terms_key = '';

            $search_rule = AWS_PRO()->get_common_settings( 'search_rule' );
            if ( $search_rule === 'begins' ) {
                $terms_key = 'KEY term (term),';
            }

            $sql = "CREATE TABLE {$this->table_name} (
                      id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
                      term VARCHAR(50) NOT NULL DEFAULT 0,
                      term_source VARCHAR(50) NOT NULL DEFAULT 0,
                      type VARCHAR(50) NOT NULL DEFAULT 0,
                      count BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
                      in_stock INT(11) NOT NULL DEFAULT 0,
                      on_sale INT(11) NOT NULL DEFAULT 0,
                      term_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
                      visibility VARCHAR(20) NOT NULL DEFAULT 0,
                      lang VARCHAR(20) NOT NULL DEFAULT 0,
                      KEY id (id),
                      {$terms_key}
                      KEY term_id (term_id),
                      UNIQUE KEY source_term (id,term,term_source,lang)
                ) $charset_collate;";

            /**
             * SQL query that used to create index table
             * @since 2.31
             * @param string $sql SQL query string
             */
            $sql = apply_filters( 'aws_create_index_table_sql', $sql );

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );

            if ( $wpdb->last_error ) {

                if ( strpos( $wpdb->last_error, 'COLLATION' ) !== false ) {
                    $sql = str_replace( " COLLATE $wpdb->collate", '', $sql );
                    dbDelta( $sql );
                }

            }

            do_action( 'aws_create_index_table' );

        }

        /*
        * Insert data into the index table
        */
        private function fill_table( $posts ) {

            /**
             * Products that will be indexed
             * @since 1.70
             * @param array $posts Array of products IDs or product objects
             */
            $posts = apply_filters( 'aws_index_product_ids', $posts );

            $options = AWS_Helpers::get_index_options();

            foreach ( $posts as $post_item ) {

                if ( ! is_object( $post_item ) ) {
                    $product = wc_get_product( $post_item );
                } else {
                    $product = $post_item;
                }

                if( ! is_a( $product, 'WC_Product' ) ) {
                    continue;
                }

                $id = method_exists( $product, 'get_id' ) ? $product->get_id() : $post_item;

                $table_data = new AWS_Table_Data( $product, $id, $options );

                $scraped_data = $table_data->scrap_data();

                if ( ! empty( $scraped_data ) ) {
                    foreach ( $scraped_data as $product_data ) {

                        //Insert data into table
                        $this->insert_into_table( $product_data );

                    }
                }

            }

        }

        /*
         * Scrap all product data and insert to table
         */
        private function insert_into_table( $data ) {
            global $wpdb;

            /**
             * Filters product data array before it will be added to index table.
             *
             * @since 1.54
             *
             * @param array $data Product data array.
             * @param int $data['id'] Product id.
             * @param null ( since 1.73 )
             */
            $data = apply_filters( 'aws_indexed_data', $data, $data['id'], null );

            $values = array();

            if ( $data && is_array( $data ) && isset( $data['terms'] ) ) {

                foreach( $data['terms'] as $source => $all_terms ) {

                    $term_id = 0;

                    if ( preg_match( '/\%(\d+)\%/', $source, $matches ) ) {
                        if ( isset( $matches[1] ) ) {
                            $term_id = $matches[1];
                            $source = preg_replace( '/\%(\d+)\%/', '', $source );
                        }
                    }

                    if ( is_array( $all_terms ) && ! empty( $all_terms ) ) {
                        foreach ( $all_terms as $term => $count ) {

                            if ( ! $term ) {
                                continue;
                            }

                            $value = $wpdb->prepare(
                                "(%d, %s, %s, %s, %d, %d, %d, %d, %s, %s)",
                                $data['id'], $term, $source, $data['type'], $count, $data['in_stock'], $data['on_sale'], $term_id, $data['visibility'], $data['lang']
                            );

                            $values[] = $value;

                        }
                    }

                }

            }

            if ( count( $values ) > 0 ) {

                $values = implode( ', ', $values );

                $query  = "INSERT IGNORE INTO {$this->table_name}
				              (`id`, `term`, `term_source`, `type`, `count`, `in_stock`, `on_sale`, `term_id`, `visibility`, `lang`)
				              VALUES $values
                    ";

                $wpdb->query( $query );

            }

        }

        /*
         * Fires when products terms are changed
         */
        public function term_changed( $term_id, $tt_id, $taxonomy ) {

            if ( $taxonomy === 'product_cat' || $taxonomy === 'product_tag' ) {
                do_action( 'aws_cache_clear' );
            }

        }

        /*
         * Fires when product term is deleted
         */
        public function term_deleted( $term_id, $tt_id, $taxonomy, $deleted_term ) {

            $source_name = AWS_Helpers::get_source_name( $taxonomy );

            if ( $source_name ) {

                if ( AWS_Helpers::is_index_table_has_terms() == 'has_terms' ) {

                    global $wpdb;

                    $sql = "DELETE FROM {$this->table_name}
                            WHERE term_source = '{$source_name}'
                            AND term_id = {$term_id}";

                    $wpdb->query( $sql );

                    do_action( 'aws_cache_clear' );

                }

            }

        }

        /*
         * Update index table
         */
        public function product_changed( $post_id, $post, $update ) {

            $slug = 'product';

            // Not run for newly created products
            if ( ! $update ) {
                return;
            }

            if ( $slug != $post->post_type ) {
                return;
            }

            if ( wp_is_post_revision( $post_id ) ) {
                return;
            }

            if ( $post->post_status === 'publish' ) {
                return;
            }

            $this->update_table( $post_id );

        }

        /*
         * Update index table
         */
        public function woocommerce_after_product_object_save( $product ) {

            $post_id = $product->get_id();

            if ( 'variation' === $product->get_type() ) {
                return;
            }

            if ( wp_is_post_revision( $post_id ) ) {
                return;
            }

            $this->update_table( $post_id );

        }

        /*
         * Product removed hook
         */
        public function product_deleted( $post_id, $post = false ) {
            
            $slug = 'product';

            if ( $post && $slug != $post->post_type ) {
                return;
            }

            if ( ! $post ) {
                $post_type = get_post_type( $post_id );
                if ( $post_type && $slug != $post_type ) {
                    return;
                }
            }

            $this->update_table( $post_id );

        }

        /*
         * Update index table on custom fields change
         */
        public function meta_changed( $meta_id, $object_id, $meta_key, $meta_value ) {

            if ( AWS_Helpers::filter_custom_fields( $meta_key ) ) {
                return;
            }

            $post_type = get_post_type( $object_id );

            if ( ! $post_type || $post_type !== 'product' ) {
                return;
            }

            $this->update_table( $object_id );

        }

        /*
         * Hook that fires when product attributes was changed
         */
        public function attributes_changed( $loop, $variation_data, $variation ) {

            if ( $variation->post_parent && ! isset( $this->data['attributes_change'] ) ) {

                $this->data['attributes_change'] = true;

                $this->update_table( $variation->post_parent );

            }

        }

        /*
         * Product variation was deleted
         */
        public function variation_deleted( $id ) {
            $this->update_table( $id, false );
        }

        /*
         * Product stock status changed
         */
        public function stock_status_changes( $product_id, $stock_status, $product ) {
            global $wp_current_filter, $wpdb;
            if ( ! in_array( 'save_post', $wp_current_filter ) || in_array( 'woocommerce_process_shop_order_meta', $wp_current_filter ) ) {
                $sync = AWS_PRO()->get_common_settings('autoupdates');
                if ( AWS_Helpers::is_table_not_exist() ) {
                    $this->create_table();
                }
                if ( $sync !== 'false' ) {
                    $in_stock = $stock_status === 'instock' ? 1 : 0;
                    $wpdb->update( $this->table_name, array( 'in_stock' => $in_stock ), array( 'id' => $product_id ) );
                    do_action('aws_cache_clear');
                }
            }
        }

        /*
         * Custom Tabs was updated
         */
        public function updated_custom_tabs( $meta_id, $object_id, $meta_key, $meta_value ) {

            if ( $meta_key === 'yikes_woo_products_tabs' && apply_filters( 'aws_filter_yikes_woo_products_tabs_sync', true ) ) {

                $this->update_table( $object_id );

            }

        }

        /*
         * Re-index single product action
         */
        public function reindex_product_action( $product_id ) {
            $this->update_table( $product_id );
        }

        /*
         * Update index table
         */
        private function update_table( $product_id, $with_childs = true ) {

            global $wpdb;

            $sync = AWS_PRO()->get_common_settings('autoupdates');

            if ( AWS_Helpers::is_table_not_exist() ) {
                $this->create_table();
            }

            if ( $sync === 'false' ) {
                return;
            }

            $childs = array();

            if ( $with_childs ) {

                $childs = wp_parse_id_list(
                    get_posts(
                        array(
                            'post_parent' => $product_id,
                            'post_type'   => 'product_variation',
                            'fields'      => 'ids',
                            'post_status' => array( 'any', 'trash', 'auto-draft' ),
                            'numberposts' => -1,
                        )
                    )
                );

            }

            $wpdb->delete( $this->table_name, array( 'id' => $product_id ) );

            if ( $childs && ! empty( $childs ) ) {
                $ids = implode( ',', array_map( 'absint', $childs ) );
                $wpdb->query( "DELETE FROM {$this->table_name} WHERE id IN($ids)" );
            }

            $posts = get_posts( array(
                'posts_per_page'   => -1,
                'fields'           => 'ids',
                'post_type'        => 'product',
                'post_status'      => 'publish',
                'has_password'     => false,
                'no_found_rows'    => 1,
                'include'          => $product_id,
                'lang'             => ''
            ) );

            if ( $posts ) {
                $this->fill_table( $posts );
            }

            do_action('aws_cache_clear');

        }

        /*
         * Add enabled meta fields
         */
        public function meta_keys_include( $include_meta ) {
            $index_options = AWS_Helpers::get_index_options();
            if ( $index_options ) {
                $is_enabled = $index_options['index']['meta'];
                if ( $is_enabled ) {
                    $meta_sources = $index_options['index']['meta_sources'];
                    if ( $meta_sources && is_array( $meta_sources ) ) {
                        foreach ( $meta_sources as $meta_sources_name => $meta_sources_val ) {
                            if ( $meta_sources_val ) {
                                $meta_sources_name = str_replace( 'meta_', '', $meta_sources_name );
                                $include_meta[] = $meta_sources_name;
                            }
                        }
                    }
                }
            }
            return $include_meta;
        }

    }

endif;


new AWS_Table();