<?php
/**
 * Versions capability
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( 'AWS_PRO_Versions' ) ) :

/**
 * Class for plugin search
 */
class AWS_PRO_Versions {

    /**
     * Return a singleton instance of the current class
     *
     * @return object
     */
    public static function factory() {
        static $instance = false;

        if ( ! $instance ) {
            $instance = new self();
            $instance->setup();
        }

        return $instance;
    }

    /**
     * Placeholder
     */
    public function __construct() {}

    /**
     * Setup actions and filters for all things settings
     */
    public function setup() {

        $current_version = get_option( 'aws_pro_plugin_ver' );
        $reindex_version = get_option( 'aws_pro_reindex_version' );

        if ( ! ( $reindex_version ) && current_user_can( 'manage_options' ) ) {
            add_action( 'admin_notices', array( $this, 'admin_notice_no_index' ) );
        }

        if ( $reindex_version && version_compare( $reindex_version, '1.28', '<' ) && current_user_can( 'manage_options' ) ) {
            add_action( 'admin_notices', array( $this, 'admin_notice_reindex' ) );
        }

        if ( $reindex_version && version_compare( $reindex_version, '2.26', '<' ) && ( ! isset( $_REQUEST['action'] ) || ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] !== 'aws-reindex' ) ) ) {
            add_filter( 'aws_indexed_data', array( $this, 'indexed_data_change_stock_value' ), 1 );
        }

        if ( $reindex_version && version_compare( $reindex_version, '2.26', '<' ) ) {
            add_filter( 'aws_admin_page_options_current', array( $this, 'admin_page_change_stock_value' ), 2 );
        }

        $stopwords = get_option( 'aws_pro_stopwords' );

        if ( $stopwords === false ) {
            $stopwords = 'a, also, am, an, and, are, as, at, be, but, by, call, can, co, con, de, do, due, eg, eight, etc, even, ever, every, for, from, full, go, had, has, hasnt, have, he, hence, her, here, his, how, ie, if, in, inc, into, is, it, its, ltd, me, my, no, none, nor, not, now, of, off, on, once, one, only, onto, or, our, ours, out, over, own, part, per, put, re, see, so, some, ten, than, that, the, their, there, these, they, this, three, thru, thus, to, too, top, un, up, us, very, via, was, we, well, were, what, when, where, who, why, will';
            $free_settings = get_option( 'aws_settings' );
            if ( $free_settings && isset( $free_settings['stopwords'] ) ) {
                $stopwords = $free_settings['stopwords'];
            }
            update_option( 'aws_pro_stopwords', $stopwords, 'no' );
        }

        if ( $current_version ) {

            if ( version_compare( $current_version, '1.29', '<' ) ) {

                $settings = get_option( 'aws_pro_settings' );
                $options_array = AWS_Admin_Options::options_array();

                if ( $settings ) {
                    foreach( $settings as $search_instance_num => $search_instance_settings ) {
                        if ( isset( $search_instance_settings['filters'] ) ) {
                            foreach( $search_instance_settings['filters'] as $filter_num => $filter_settings ) {

                                if ( isset( $filter_settings['search_in'] ) && is_string( $filter_settings['search_in'] ) ) {
                                    $current_search_in = explode( ',', $filter_settings['search_in'] );
                                    $new_search_in = array();
                                    foreach( $options_array['results'] as $def_option ) {
                                        if ( isset( $def_option['id'] ) && $def_option['id'] === 'search_in' && isset( $def_option['choices'] ) ) {
                                            foreach( $def_option['choices'] as $choice_key => $choice_label ) {
                                                $new_search_in[$choice_key] = in_array( $choice_key, $current_search_in ) ? 1 : 0;
                                            }
                                            $settings[$search_instance_num]['filters'][$filter_num]['search_in'] = $new_search_in;
                                            break;
                                        }
                                    }
                                }

                                if ( ! isset( $filter_settings['search_in_attr'] ) ) {
                                    foreach( $options_array['results'] as $def_option ) {
                                        if ( isset( $def_option['id'] ) && $def_option['id'] === 'search_in_attr' && isset( $def_option['value'] ) ) {
                                            $settings[$search_instance_num]['filters'][$filter_num]['search_in_attr'] = $def_option['value'];
                                            break;
                                        }
                                    }
                                }

                                if ( ! isset( $filter_settings['search_in_tax'] ) ) {
                                    foreach( $options_array['results'] as $def_option ) {
                                        if ( isset( $def_option['id'] ) && $def_option['id'] === 'search_in_tax' && isset( $def_option['value'] ) ) {
                                            $settings[$search_instance_num]['filters'][$filter_num]['search_in_tax'] = $def_option['value'];
                                            break;
                                        }
                                    }
                                }

                            }
                        }
                    }

                    update_option( 'aws_pro_settings', $settings );

                }

                AWS_Helpers::add_term_id_column();

            }

            if ( version_compare( $current_version, '1.30', '<' ) ) {

                $settings = get_option( 'aws_pro_settings' );

                if ( $settings ) {

                    foreach( $settings as $search_instance_num => $search_instance_settings ) {
                        if ( ! isset( $search_instance_settings['show_more'] ) ) {
                            $settings[$search_instance_num]['show_more'] = 'false';
                        }
                    }

                    update_option( 'aws_pro_settings', $settings );

                }

            }

            if ( version_compare( $current_version, '1.32', '<' ) ) {

                if ( ! AWS_Helpers::is_table_not_exist() ) {

                    global $wpdb;
                    $table_name =  $wpdb->prefix . AWS_INDEX_TABLE_NAME;

                    $wpdb->query("
                        ALTER TABLE {$table_name}
                        MODIFY term_source varchar(50);
                    ");

                }

                $settings = get_option( 'aws_pro_settings' );
                $options_array = AWS_Admin_Options::options_array();

                if ( $settings ) {
                    foreach( $settings as $search_instance_num => $search_instance_settings ) {
                        if ( isset( $search_instance_settings['filters'] ) ) {
                            foreach( $search_instance_settings['filters'] as $filter_num => $filter_settings ) {

                                if ( isset( $filter_settings['search_in'] ) && is_string( $filter_settings['search_in'] ) ) {
                                    $current_search_in = explode( ',', $filter_settings['search_in'] );
                                    $new_search_in = array();
                                    foreach( $options_array['results'] as $def_option ) {
                                        if ( isset( $def_option['id'] ) && $def_option['id'] === 'search_in' && isset( $def_option['choices'] ) ) {
                                            foreach( $def_option['choices'] as $choice_key => $choice_label ) {
                                                $new_search_in[$choice_key] = in_array( $choice_key, $current_search_in ) ? 1 : 0;
                                            }
                                            $settings[$search_instance_num]['filters'][$filter_num]['search_in'] = $new_search_in;
                                            break;
                                        }
                                    }
                                }

                                if ( ! isset( $filter_settings['search_in_meta'] ) ) {
                                    foreach( $options_array['results'] as $def_option ) {
                                        if ( isset( $def_option['id'] ) && $def_option['id'] === 'search_in_meta' && isset( $def_option['value'] ) ) {
                                            $settings[$search_instance_num]['filters'][$filter_num]['search_in_meta'] = $def_option['value'];
                                            break;
                                        }
                                    }
                                }

                            }
                        }
                    }

                    update_option( 'aws_pro_settings', $settings );

                }

            }

            if ( version_compare( $current_version, '1.36', '<' ) ) {
                $seamless = get_option( 'aws_pro_seamless' );

                if ( $seamless === false ) {
                    $seamless = 'false';
                    update_option( 'aws_pro_seamless', $seamless );
                }
            }

            if ( version_compare( $current_version, '1.37', '<' ) ) {

                $settings = get_option( 'aws_pro_settings' );

                if ( $settings ) {

                    foreach( $settings as $search_instance_num => $search_instance_settings ) {
                        if ( ! isset( $search_instance_settings['show_clear'] ) ) {
                            $settings[$search_instance_num]['show_clear'] = 'false';
                        }
                    }

                    update_option( 'aws_pro_settings', $settings );

                }

            }

            if ( version_compare( $current_version, '1.38', '<' ) ) {

                $settings = get_option( 'aws_pro_settings' );

                if ( $settings ) {

                    foreach( $settings as $search_instance_num => $search_instance_settings ) {
                        if ( ! isset( $search_instance_settings['show_more_text'] ) ) {
                            $settings[$search_instance_num]['show_more_text'] = __('View all results', 'advanced-woo-search');
                        }
                    }

                    update_option( 'aws_pro_settings', $settings );

                }

            }

            if ( version_compare( $current_version, '1.41', '<' ) ) {

                $settings = get_option( 'aws_pro_settings' );

                if ( $settings ) {

                    foreach( $settings as $search_instance_num => $search_instance_settings ) {
                        if ( isset( $search_instance_settings['filters'] ) ) {
                            foreach ($search_instance_settings['filters'] as $filter_num => $filter_settings) {
                                if ( ! isset( $filter_settings['show_cart'] ) ) {
                                    $settings[$search_instance_num]['filters'][$filter_num]['show_cart'] = 'false';
                                }
                            }
                        }
                    }

                    update_option( 'aws_pro_settings', $settings );

                }

            }

            if ( version_compare( $current_version, '1.44', '<' ) ) {

                $settings = get_option( 'aws_pro_settings' );

                if ( $settings ) {

                    foreach( $settings as $search_instance_num => $search_instance_settings ) {
                        if ( isset( $search_instance_settings['filters'] ) ) {
                            foreach ($search_instance_settings['filters'] as $filter_num => $filter_settings) {
                                if ( ! isset( $filter_settings['on_sale'] ) ) {
                                    $settings[$search_instance_num]['filters'][$filter_num]['on_sale'] = 'true';
                                }
                            }
                        }
                    }

                    update_option( 'aws_pro_settings', $settings );

                }

                AWS_Helpers::add_on_sale_column();

            }

            if ( version_compare( $current_version, '1.45', '<' ) ) {

                $settings = get_option( 'aws_pro_settings' );

                if ( $settings ) {

                    foreach( $settings as $search_instance_num => $search_instance_settings ) {
                        if ( isset( $search_instance_settings['filters'] ) ) {
                            foreach ($search_instance_settings['filters'] as $filter_num => $filter_settings) {

                                if ( isset( $filter_settings['exclude_cats'] ) && $filter_settings['exclude_cats'] ) {
                                    if ( ! preg_match( "/[^\\d^,^\\s]/i", $filter_settings['exclude_cats'] ) ) {
                                        $settings[$search_instance_num]['filters'][$filter_num]['adv_filters']['group_a1']['source'] = 'tax:product_cat';
                                        $settings[$search_instance_num]['filters'][$filter_num]['adv_filters']['group_a1']['term'] = explode( ',', $filter_settings['exclude_cats'] );
                                        $settings[$search_instance_num]['filters'][$filter_num]['exclude_cats'] = '';
                                    }
                                }

                                if ( isset( $filter_settings['exclude_tags'] ) && $filter_settings['exclude_tags'] ) {
                                    if ( ! preg_match( "/[^\\d^,^\\s]/i", $filter_settings['exclude_tags'] ) ) {
                                        $settings[$search_instance_num]['filters'][$filter_num]['adv_filters']['group_a2']['source'] = 'tax:product_tag';
                                        $settings[$search_instance_num]['filters'][$filter_num]['adv_filters']['group_a2']['term'] = explode( ',', $filter_settings['exclude_tags'] );
                                        $settings[$search_instance_num]['filters'][$filter_num]['exclude_tags'] = '';
                                    }
                                }

                                if ( isset( $filter_settings['exclude_products'] ) && $filter_settings['exclude_products'] ) {
                                    if ( ! preg_match( "/[^\\d^,^\\s]/i", $filter_settings['exclude_products'] ) ) {
                                        $settings[$search_instance_num]['filters'][$filter_num]['adv_filters']['group_a3']['source'] = 'product';
                                        $settings[$search_instance_num]['filters'][$filter_num]['adv_filters']['group_a3']['term'] = explode( ',', $filter_settings['exclude_products'] );
                                        $settings[$search_instance_num]['filters'][$filter_num]['exclude_products'] = '';
                                    }
                                }

                            }
                        }
                    }

                    update_option( 'aws_pro_settings', $settings );

                }

            }

            if ( version_compare( $current_version, '1.50', '<' ) ) {

                $settings = get_option( 'aws_pro_settings' );

                if ( $settings ) {

                    foreach( $settings as $search_instance_num => $search_instance_settings ) {
                        if ( isset( $search_instance_settings['filters'] ) ) {
                            foreach ($search_instance_settings['filters'] as $filter_num => $filter_settings) {
                                if ( ! isset( $filter_settings['show_outofstock_price'] ) ) {
                                    $settings[$search_instance_num]['filters'][$filter_num]['show_outofstock_price'] = 'true';
                                }
                            }
                        }
                    }

                    update_option( 'aws_pro_settings', $settings );

                }

            }

            if ( version_compare( $current_version, '1.51', '<' ) ) {
                $sync = get_option( 'aws_pro_autoupdates' );

                if ( $sync === false ) {
                    $sync = 'true';
                    update_option( 'aws_pro_autoupdates', $sync, 'no' );
                }
            }

            if ( version_compare( $current_version, '1.58', '<' ) ) {

                $settings = get_option( 'aws_pro_settings' );

                if ( $settings ) {

                    foreach( $settings as $search_instance_num => $search_instance_settings ) {
                        if ( ! isset( $search_instance_settings['search_exact'] ) ) {
                            $settings[$search_instance_num]['search_exact'] = 'false';
                        }
                    }

                    foreach( $settings as $search_instance_num => $search_instance_settings ) {
                        if ( isset( $search_instance_settings['filters'] ) ) {
                            foreach ($search_instance_settings['filters'] as $filter_num => $filter_settings) {
                                if ( ! isset( $filter_settings['var_rules'] ) ) {
                                    $settings[$search_instance_num]['filters'][$filter_num]['var_rules'] = 'parent';
                                }
                            }
                        }
                    }

                    update_option( 'aws_pro_settings', $settings );

                }

            }

            if ( version_compare( $current_version, '1.60', '<' ) ) {

                $settings = get_option( 'aws_pro_settings' );
                $options_array = AWS_Admin_Options::options_array();

                if ( $settings ) {

                    foreach( $settings as $search_instance_num => $search_instance_settings ) {

                        if ( isset( $search_instance_settings['filters'] ) ) {
                            foreach( $search_instance_settings['filters'] as $filter_num => $filter_settings ) {

                                if ( ! isset( $filter_settings['search_archives'] ) ) {

                                    $show_cats = $settings[$search_instance_num]['filters'][$filter_num]['show_cats'] === 'true' ? 1 : 0;
                                    $show_tags = $settings[$search_instance_num]['filters'][$filter_num]['show_tags'] === 'true' ? 1 : 0;

                                    $settings[$search_instance_num]['filters'][$filter_num]['search_archives']['archive_category'] = $show_cats;
                                    $settings[$search_instance_num]['filters'][$filter_num]['search_archives']['archive_tag'] = $show_tags;
                                    $settings[$search_instance_num]['filters'][$filter_num]['search_archives']['archive_tax'] = 0;

                                }

                                if ( ! isset( $filter_settings['search_archives_tax'] ) ) {
                                    foreach( $options_array['results'] as $def_option ) {
                                        if ( isset( $def_option['id'] ) && $def_option['id'] === 'search_archives_tax' && isset( $def_option['value'] ) ) {
                                            $settings[$search_instance_num]['filters'][$filter_num]['search_archives_tax'] = $def_option['value'];
                                            break;
                                        }
                                    }
                                }

                                if ( ! isset( $filter_settings['search_archives_attr'] ) ) {
                                    foreach( $options_array['results'] as $def_option ) {
                                        if ( isset( $def_option['id'] ) && $def_option['id'] === 'search_archives_attr' && isset( $def_option['value'] ) ) {
                                            $settings[$search_instance_num]['filters'][$filter_num]['search_archives_attr'] = $def_option['value'];
                                            break;
                                        }
                                    }
                                }

                            }
                        }
                    }

                    update_option( 'aws_pro_settings', $settings );

                }

            }

            if ( version_compare( $current_version, '1.62', '<' ) ) {

                $settings = get_option( 'aws_pro_settings' );

                if ( $settings ) {

                    foreach( $settings as $search_instance_num => $search_instance_settings ) {
                        if ( ! isset( $search_instance_settings['disable_smooth'] ) ) {
                            $settings[$search_instance_num]['disable_smooth'] = 'false';
                        }
                    }

                    update_option( 'aws_pro_settings', $settings );

                }

            }

            if ( version_compare( $current_version, '1.70', '<' ) ) {

                $synonyms = get_option( 'aws_pro_synonyms' );

                if ( $synonyms === false ) {
                    $synonyms = 'buy, pay, purchase, acquire&#13;&#10;box, housing, unit, package';
                    update_option( 'aws_pro_synonyms', $synonyms, 'no' );
                }

            }

            if ( version_compare( $current_version, '1.80', '<' ) ) {

                $settings = get_option( 'aws_pro_settings' );

                if ( $settings ) {

                    foreach( $settings as $search_instance_num => $search_instance_settings ) {
                        if ( isset( $search_instance_settings['filters'] ) ) {
                            foreach ($search_instance_settings['filters'] as $filter_num => $filter_settings) {
                                if ( ! isset( $filter_settings['highlight'] ) && isset( $filter_settings['mark_words'] ) ) {
                                    $mark_words = $filter_settings['mark_words'];
                                    $settings[$search_instance_num]['filters'][$filter_num]['highlight'] = $mark_words;
                                }
                            }
                        }
                    }

                    update_option( 'aws_pro_settings', $settings );

                }

            }

            if ( version_compare( $current_version, '1.87', '<' ) ) {

                $settings = get_option( 'aws_pro_settings' );

                if ( $settings ) {

                    foreach( $settings as $search_instance_num => $search_instance_settings ) {
                        if ( ! isset( $search_instance_settings['mobile_overlay'] ) ) {
                            $settings[$search_instance_num]['mobile_overlay'] = 'false';
                        }
                    }

                    update_option( 'aws_pro_settings', $settings );

                }

            }

            if ( version_compare( $current_version, '2.04', '<' ) ) {

                $settings = get_option( 'aws_pro_settings' );
                $options_array = AWS_Admin_Options::options_array();

                if ( $settings ) {

                    foreach( $settings as $search_instance_num => $search_instance_settings ) {

                        if ( isset( $search_instance_settings['filters'] ) ) {
                            foreach( $search_instance_settings['filters'] as $filter_num => $filter_settings ) {

                                if ( isset( $filter_settings['search_archives'] ) && ! isset( $filter_settings['search_archives']['archive_users'] ) ) {
                                    $settings[$search_instance_num]['filters'][$filter_num]['search_archives']['archive_users'] = 0;
                                }

                                if ( ! isset( $filter_settings['search_archives_users'] ) ) {
                                    foreach( $options_array['results'] as $def_option ) {
                                        if ( isset( $def_option['id'] ) && $def_option['id'] === 'search_archives_users' && isset( $def_option['value'] ) ) {
                                            $settings[$search_instance_num]['filters'][$filter_num]['search_archives_users'] = $def_option['value'];
                                            break;
                                        }
                                    }
                                }

                            }
                        }
                    }

                    update_option( 'aws_pro_settings', $settings );

                }

            }

            if ( version_compare( $current_version, '2.26', '<' ) ) {

                $settings = get_option( 'aws_pro_settings' );

                if ( $settings ) {

                    foreach( $settings as $search_instance_num => $search_instance_settings ) {
                        if ( isset( $search_instance_settings['filters'] ) ) {
                            foreach ($search_instance_settings['filters'] as $filter_num => $filter_settings) {
                                if ( ! isset( $filter_settings['product_stock_status'] ) && isset( $filter_settings['outofstock'] ) ) {
                                    $old_value = $filter_settings['outofstock'];

                                    $in_stock = $old_value === 'out' ? 0 : 1;
                                    $on_backorder = $old_value === 'out' ? 0 : 1;
                                    $out_of_stock = $old_value === 'false' ? 0 : 1;

                                    $new_value = array(
                                        'in_stock'     => $in_stock,
                                        'out_of_stock' => $out_of_stock,
                                        'on_backorder' => $on_backorder,
                                    );

                                    $settings[$search_instance_num]['filters'][$filter_num]['product_stock_status'] = $new_value;

                                }
                            }
                        }
                    }

                    update_option( 'aws_pro_settings', $settings );

                }

            }

            if ( version_compare( $current_version, '2.34', '<' ) ) {

                $settings = get_option( 'aws_pro_settings' );

                if ( $settings ) {

                    foreach( $settings as $search_instance_num => $search_instance_settings ) {

                        if ( isset( $search_instance_settings['show_page'] ) && ! isset( $search_instance_settings['search_page'] ) ) {
                            $search_page_val = $search_instance_settings['show_page'] === 'false' ? 'false' : 'true';
                            $settings[$search_instance_num]['search_page'] = $search_page_val;
                        }

                        if ( isset( $search_instance_settings['show_page'] ) && ! isset( $search_instance_settings['enable_ajax'] ) ) {
                            $search_page_val = $search_instance_settings['show_page'] === 'ajax_off' ? 'false' : 'true';
                            $settings[$search_instance_num]['enable_ajax'] = $search_page_val;
                        }

                        if ( ! isset( $search_instance_settings['search_page_res_num'] ) ) {
                            $settings[$search_instance_num]['search_page_res_num'] = '100';
                        }

                        if ( ! isset( $search_instance_settings['search_page_res_per_page'] ) ) {
                            $settings[$search_instance_num]['search_page_res_per_page'] = '';
                        }

                        if ( ! isset( $search_instance_settings['search_page_query'] ) ) {
                            $settings[$search_instance_num]['search_page_query'] = 'default';
                        }

                    }

                    update_option( 'aws_pro_settings', $settings );

                }

            }

            if ( version_compare( $current_version, '2.35', '<' ) ) {

                $common_opts = get_option( 'aws_pro_common_opts' );
                $settings = get_option( 'aws_pro_settings' );

                if ( $common_opts ) {

                    if ( ! isset( $common_opts['autoupdates'] ) ) {
                        $sync_option = get_option( 'aws_pro_autoupdates' );
                        $sync = $sync_option && $sync_option === 'false' ? 'false' : 'true';
                        $common_opts['autoupdates'] = $sync;
                    }

                    if ( ! isset( $common_opts['cache'] ) && $settings ) {
                        $cache_option = 'false';
                        foreach( $settings as $search_instance_num => $search_instance_settings ) {
                            if ( isset( $search_instance_settings['cache'] ) && $search_instance_settings['cache'] === 'true' ) {
                                $cache_option = 'true';
                                break;
                            }
                        }
                        $common_opts['cache'] = $cache_option;
                    }

                    update_option( 'aws_pro_common_opts', $common_opts );

                }

            }

            if ( version_compare( $current_version, '2.45', '<' ) ) {

                $settings = get_option( 'aws_pro_settings' );

                if ( $settings ) {

                    foreach( $settings as $search_instance_num => $search_instance_settings ) {
                        if ( isset( $search_instance_settings['filters'] ) ) {
                            foreach ($search_instance_settings['filters'] as $filter_num => $filter_settings) {

                                if ( isset( $filter_settings['adv_filters'] ) && is_array( $filter_settings['adv_filters'] ) && ! empty( $filter_settings['adv_filters'] ) ) {

                                    $relation_option = isset( $filter_settings['exclude_rel'] ) ? $filter_settings['exclude_rel'] : 'exclude';
                                    $new_relation_option = $relation_option === 'exclude' ? 'not_equal' : 'equal';
                                    $new_adv_filters = array();

                                    foreach ( $filter_settings['adv_filters'] as $filter_group_id => $filter_params ) {
                                        if ( ! isset( $filter_params['source'] ) ) {
                                            continue;
                                        }
                                        $filter_source = str_replace('tax:', '', $filter_params['source'] );
                                        if ( $filter_source === 'product_cat' ) {
                                            $filter_source = 'product_category';
                                        }
                                        $filter_terms = isset( $filter_params['term'] ) && ! empty( $filter_params['term'] ) ? $filter_params['term'] : false;
                                        if ( $filter_terms ) {
                                            foreach ( $filter_terms as $term_id ) {

                                                $rule_id = uniqid($term_id);
                                                if ( $term_id === 'all' ) {
                                                    $term_id = 'aws_any';
                                                }

                                                if ( strpos( $filter_source, 'pa_' ) === 0 ) {
                                                    $new_adv_filters['product']['group_1'][$rule_id] = array(
                                                        'param' => 'product_attributes',
                                                        'suboption' => $filter_source,
                                                        'operator' => $new_relation_option,
                                                        'value' => $term_id
                                                    );
                                                } elseif( in_array( $filter_source, array( 'product', 'product_category', 'product_tag', 'product_shipping_class' ) ) ) {
                                                    $new_adv_filters['product']['group_1'][$rule_id] = array(
                                                        'param' => $filter_source,
                                                        'operator' => $new_relation_option,
                                                        'value' => $term_id
                                                    );
                                                } else {
                                                    $new_adv_filters['product']['group_1'][$rule_id] = array(
                                                        'param' => 'product_taxonomy',
                                                        'suboption' => $filter_source,
                                                        'operator' => $new_relation_option,
                                                        'value' => $term_id
                                                    );
                                                }


                                            }
                                        }
                                    }

                                    if ( !empty( $new_adv_filters ) ) {
                                        $settings[$search_instance_num]['filters'][$filter_num]['adv_filters'] = $new_adv_filters;
                                    }

                                }

                            }
                        }
                    }

                    update_option( 'aws_pro_settings', $settings );

                }

            }

        }

        if ( ! $current_version ) {

            AWS_Helpers::add_term_id_column();
            AWS_Helpers::add_on_sale_column();

        }

        update_option( 'aws_pro_plugin_ver', AWS_PRO_VERSION );

    }

    /**
     * Change in stock value for index table for old reindex table versions
     */
    public function indexed_data_change_stock_value( $data ) {
        if ( $data && is_array( $data ) && isset( $data['in_stock'] ) && $data['in_stock'] === 2 ) {
            $data['in_stock'] = 1;
        }
        return $data;
    }

    /**
     * Hide stock status options values for old reindex table versions
     */
    public function admin_page_change_stock_value( $options ) {
        if ( $options ) {
            foreach( $options as $options_key => $options_tab ) {
                foreach ($options_tab as $key => $option) {
                    if ( isset( $option['id'] ) && $option['id'] === 'product_stock_status' ) {
                        unset( $options[$options_key][$key]['choices']['on_backorder'] );
                    }
                }
            }
        }
        return $options;
    }

    /**
     * Admin notice for table first re-index
     */
    public function admin_notice_no_index() { ?>
        <div class="updated notice is-dismissible">
            <p><?php printf( esc_html__( 'Advanced Woo Search: Please go to the plugin setting page and start indexing your products. %s', 'advanced-woo-search' ), '<a class="button button-secondary" href="'.esc_url( admin_url('admin.php?page=aws-options') ).'">'.esc_html__( 'Go to Settings Page', 'advanced-woo-search' ).'</a>'  ); ?></p>
        </div>
    <?php }

    /**
     * Admin notice for table reindex
     */
    public function admin_notice_reindex() { ?>
        <div class="updated notice is-dismissible">
            <p><?php printf( esc_html__( 'Advanced Woo Search: Please reindex table for proper work of new plugin features. %s', 'advanced-woo-search' ), '<a class="button button-secondary" href="'.esc_url( admin_url('admin.php?page=aws-options') ).'">'.esc_html__( 'Go to Settings Page', 'advanced-woo-search' ).'</a>'  ); ?></p>
        </div>
    <?php }

}


endif;

add_action( 'admin_init', 'AWS_PRO_Versions::factory' );