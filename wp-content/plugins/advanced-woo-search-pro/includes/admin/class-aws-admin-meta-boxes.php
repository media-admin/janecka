<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'AWS_Admin_Meta_Boxes' ) ) :

    /**
     * Class for plugin admin panel
     */
    class AWS_Admin_Meta_Boxes {

        /*
         * Get content for the global options
         * @return string
         */
        static public function get_general_tab_content() {

            $html = '';

            $html .='<table class="form-table">';

            $html .='<tbody>';

            $html .='<tr>';

                $html .='<th>' . esc_html__( 'Activation', 'advanced-woo-search' ) . '</th>';

                $html .='<td>';
                    $html .='<div class="description activation">';
                        $html .=esc_html__( 'In case you need to add plugin search form on your website, you can do it in several ways:', 'advanced-woo-search' ) . '<br>';
                        $html .='<div class="list">';
                            $html .='1. ' . sprintf(esc_html__( "Enable a %s option ( may not work with some themes )", 'advanced-woo-search' ), '<a href="#seamless">' . __( 'Seamless integration', 'advanced-woo-search' ) . '</a>' ) . '<br>';
                            $html .='2. ' . sprintf( esc_html__( 'Using shortcode %s', 'advanced-woo-search' ), '<code>[aws_search_form id="YOUR_FORM_ID"]</code>' ) . '<br>';
                            $html .='3. ' . sprintf( esc_html__( "Add search form as a widget. Go to %s and drag&drop 'AWS Widget' to one of your widget areas", 'advanced-woo-search' ), '<a href="' . admin_url( 'widgets.php' ) . '" target="_blank">' . __( 'Widgets Screen', 'advanced-woo-search' ) . '</a>' ) . '<br>';
                            $html .='4. ' . sprintf( esc_html__( 'Add PHP code to the necessary files of your theme: %s', 'advanced-woo-search' ), "<code>&lt;?php aws_get_search_form( true, array( 'id' => YOUR_FORM_ID ) ); ?&gt;</code>" ) . '<br>';
                            $html .= sprintf( esc_html__( 'Replace %s with ID of search form that you want to display', 'advanced-woo-search' ), "<code>YOUR_FORM_ID</code>" ) . '<br>';
                        $html .='</div>';
                    $html .='</div>';
                $html .='</td>';

            $html .='</tr>';


            $html .='<tr>';

            $html .='<th>' . esc_html__( 'Reindex table', 'advanced-woo-search' ) . '</th>';

                $html .='<td id="activation">';

                    $html .='<div id="aws-reindex"><input class="button" type="button" value="' . esc_attr__( 'Reindex table', 'advanced-woo-search' ) . '"><span class="loader"></span><span class="reindex-progress">0%</span><span class="reindex-notice">' . __( 'Please do not close the page.', 'advanced-woo-search' ) . '</span></div><br><br>';
                    $html .='<span class="description">' .
                        sprintf( esc_html__( 'This action only need for %s one time %s - after you activate this plugin. After this all products changes will be re-indexed automatically.', 'advanced-woo-search' ), '<strong>', '</strong>' ) . '<br>' .
                        __( 'Update all data in plugins index table. Index table - table with products data where plugin is searching all typed terms.<br>Use this button if you think that plugin not shows last actual data in its search results.<br>' .
                            '<strong>CAUTION:</strong> this can take large amount of time.', 'advanced-woo-search' ) . sprintf( __( 'Index table options can be found inside %s section.', 'advanced-woo-search' ), '<a href="'.esc_url( admin_url('admin.php?page=aws-options&tab=performance') ).'">' . __( 'Performance', 'advanced-woo-search' ) . '</a>' ) . '<br><br>' .
                        esc_html__( 'Products in index:', 'advanced-woo-search' ) . '<span id="aws-reindex-count"> <strong>' . AWS_Helpers::get_indexed_products_count() . '</strong></span>';
                    $html .='</span>';

                $html .='</td>';

            $html .='</tr>';

            $html .='<tr id="seamless">';

                $html .='<th>' . esc_html__( 'Seamless integration' ) . '</th>';

                $html .='<td>';
                    $seamless = get_option( 'aws_pro_seamless' ) ? get_option( 'aws_pro_seamless' ) : 'false';
                    $html .='<input class="radio" type="radio" name="seamless" id="seamlesstrue" value="true" ' . checked( $seamless, 'true', false ) . '> <label for="seamlesstrue">On</label><br>';
                    $html .='<input class="radio" type="radio" name="seamless" id="seamlessfalse" value="false" ' . checked( $seamless, 'false', false ) . '> <label for="seamlessfalse">Off</label><br>';
                    $html .='<br><span class="description">' . esc_html__( 'Replace all the standard search forms on your website ( may not work with some themes ).', 'advanced-woo-search' ) . '</span>';
                $html .='</td>';

            $html .='</tr>';

            $html .='<tr>';

                $html .='<th>' . esc_html__( 'Stop words list' ) . '</th>';

                $html .='<td>';
                    $stopwords = get_option( 'aws_pro_stopwords' ) ? get_option( 'aws_pro_stopwords' ) : '';
                    $html .='<textarea id="stopwords" name="stopwords" cols="85" rows="3">' . $stopwords . '</textarea>';
                    $html .='<br><span class="description">' . esc_html__( 'Comma separated list of words that will be excluded from search.', 'advanced-woo-search' ) . '<br>' . esc_html__( 'Re-index required on change.', 'advanced-woo-search' ) . '</span>';
                $html .='</td>';

            $html .='</tr>';

            $html .='<tr>';

                $html .='<th>' . esc_html__( 'Synonyms' ) . '</th>';

                $html .='<td>';
                    $synonyms = get_option( 'aws_pro_synonyms' ) ? get_option( 'aws_pro_synonyms' ) : '';
                    $html .='<textarea id="synonyms" name="synonyms" cols="85" rows="3">' . $synonyms . '</textarea>';
                    $html .='<br><span class="description">' . esc_html__( 'Comma separated list of synonym words. Each group of synonyms must be on separated text line.', 'advanced-woo-search' ) . '<br>' . esc_html__( 'Re-index required on change.', 'advanced-woo-search' ) . '</span>';
                $html .='</td>';

            $html .='</tr>';


            $html .='</tbody>';

            $html .='</table>';

            $html .='<p class="submit"><input name="Submit" type="submit" class="button-primary" value="' . esc_attr__( 'Save Changes', 'advanced-woo-search' ) . '" /></p>';

            return $html;

        }

        /*
         * Get content for general tabs
         * @return string
         */
        static public function get_general_tabs() {

            $tabs = array(
                'general'     => __( 'General', 'advanced-woo-search' ),
                'performance' => __( 'Performance', 'advanced-woo-search' ),
            );

            $current_tab = empty( $_GET['tab'] ) ? 'general' : sanitize_text_field( $_GET['tab'] );

            if ( strpos( $current_tab, 'index_' ) === 0 ) {
                $current_tab = 'performance';
            }


            $tabs_html = '';

            foreach ( $tabs as $name => $label ) {
                $tabs_html .= '<a href="' . esc_url( admin_url( 'admin.php?page=aws-options&tab=' . $name ) ) . '" class="nav-tab ' . ( $current_tab == $name ? 'nav-tab-active' : '' ) . '">' . esc_html( $label ) . '</a>';
            }

            $tabs_html = '<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">'.$tabs_html.'</h2>';

            return $tabs_html;

        }

        /*
         * Get content for the instances table
         * @return string
         */
        static public function get_instances_table() {
            
            $plugin_options = AWS_Admin_Options::get_settings();

            $html = '';

            $html .='<table class="aws-table aws-form-instances widefat" cellspacing="0">';
    
    
            $html .='<thead>';
    
            $html .='<tr>';
            $html .='<th class="aws-name">' . esc_html__( 'Form Name', 'advanced-woo-search' ) . '</th>';
            $html .='<th class="aws-shortcode">' . esc_html__( 'Shortcode', 'advanced-woo-search' ) . '</th>';
            $html .='<th class="aws-actions"></th>';
            $html .='</tr>';
    
            $html .='</thead>';
    
    
            $html .='<tbody>';
    
            foreach ( $plugin_options as $instance => $instance_options ) {
    
                $instance_page = admin_url( 'admin.php?page=aws-options&aws_id=' . $instance );
    
                $html .='<tr>';
    
                $html .='<td class="aws-name">';
                $html .='<a href="' . $instance_page . '">' . $instance_options['search_instance'] . '</a>';
                $html .='</td>';
    
                $html .='<td class="aws-shortcode">';
                $html .='[aws_search_form id="' . $instance . '"]';
                $html .='</td>';
    
                $html .='<td class="aws-actions">';
                $html .='<a class="button alignright tips delete" title="Delete" data-id="' . esc_attr( $instance ) . '" href="#">' . esc_html__( 'Delete', 'advanced-woo-search' ) . '</a>';
                $html .='<a class="button alignright tips copy" title="Copy" data-id="' . esc_attr( $instance ) . '" href="#">' . esc_html__( 'Copy', 'advanced-woo-search' ) . '</a>';
                $html .='<a class="button alignright tips edit" title="Edit" href="' . $instance_page . '">' . esc_html__( 'Edit', 'advanced-woo-search' ) . '</a>';
                $html .='</td>';
    
                $html .='</tr>';
    
            }
    
            $html .='</tbody>';
    
    
            $html .='</table>';
    
    
            $html .='<div class="aws-insert-instance">';
                $html .='<button class="button aws-insert-instance">' . esc_html__( 'Add New Form', 'advanced-woo-search' ) . '</button>';
            $html .='</div>';

            return $html;
            
        }


         /*
         * Get content for the welcome notice
         * @return string
         */
        static public function get_welcome_notice() {

            $html = '';

            $html .= '<div id="aws-welcome-panel">';
                $html .= '<div class="aws-welcome-notice updated notice is-dismissible" style="background:#f2fbff;">';

                    $html .= '<div class="aws-welcome-panel" style="border:none;box-shadow:none;padding:0;margin:16px 0 0;background:transparent;">';
                        $html .= '<div class="aws-welcome-panel-content">';
                            $html .= '<h2>' . sprintf( __( 'Welcome to %s', 'advanced-woo-search' ), 'Advanced Woo Search PRO' ) . '</h2>';
                            $html .= '<p class="about-description">' . __( 'Powerful search plugin for WooCommerce.', 'advanced-woo-search' ) . '</p>';
                            $html .= '<div class="aws-welcome-panel-column-container">';
                                $html .= '<div class="aws-welcome-panel-column">';
                                    $html .= '<h4>' . __( 'Get Started', 'advanced-woo-search' ) . '</h4>';
                                    $html .= '<p style="margin-bottom:10px;">' . __( 'In order to start using the plugin search form you need to take following steps:', 'advanced-woo-search' ) . '</p>';
                                    $html .= '<ul>';
                                        $html .= '<li><strong>1.</strong> <strong>' . __( 'Index plugin table.', 'advanced-woo-search' ) . '</strong> ' . __( 'Click on the \'Reindex table\' button and wait till the index process is finished.', 'advanced-woo-search' ) . '</li>';
                                        $html .= '<li><strong>2.</strong> <strong>' . __( 'Set plugin settings.', 'advanced-woo-search' ) . '</strong> ' . __( 'Leave it to default values or customize some of them.', 'advanced-woo-search' ) . '</li>';
                                        $html .= '<li><strong>3.</strong> <strong>' . __( 'Add search form.', 'advanced-woo-search' ) . '</strong> ' . sprintf( __( 'There are several ways you can add a search form to your site. Use the \'Seamless integration\' option, shortcode, widget or custom php function. Read more inside %s section or read %s.', 'advanced-woo-search' ), '<a href="#activation">' .  __( 'Activation', 'advanced-woo-search' ) . '</a>', '<a target="_blank" href="https://advanced-woo-search.com/guide/search-form/">' .  __( 'guide article', 'advanced-woo-search' ) . '</a>' ) . '</li>';
                                        $html .= '<li><strong>4.</strong> <strong>' . __( 'Finish!', 'advanced-woo-search' ) . '</strong> ' . __( 'Now all is set and you can check your search form on the pages where you add it.', 'advanced-woo-search' ) . '</li>';
                                    $html .= '</ul>';
                                $html .= '</div>';
                                $html .= '<div class="aws-welcome-panel-column">';
                                    $html .= '<h4>' . __( 'Documentation', 'advanced-woo-search' ) . '</h4>';
                                    $html .= '<ul>';
                                        $html .= '<li><a href="https://advanced-woo-search.com/guide/steps-to-get-started/" class="aws-welcome-icon aws-welcome-edit-page" target="_blank">' . __( 'Steps to Get Started', 'advanced-woo-search' ) . '</a></li>';
                                        $html .= '<li><a href="https://advanced-woo-search.com/guide/search-form/" class="aws-welcome-icon aws-welcome-edit-page" target="_blank">' . __( 'How to Add Search Form', 'advanced-woo-search' ) . '</a></li>';
                                        $html .= '<li><a href="https://advanced-woo-search.com/guide/search-source/" class="aws-welcome-icon aws-welcome-edit-page" target="_blank">' . __( 'Search Sources', 'advanced-woo-search' ) . '</a></li>';
                                        $html .= '<li><a href="https://advanced-woo-search.com/guide/terms-search/" class="aws-welcome-icon aws-welcome-edit-page" target="_blank">' . __( 'Terms Pages Search', 'advanced-woo-search' ) . '</a></li>';
                                    $html .= '</ul>';
                                $html .= '</div>';
                                $html .= '<div class="aws-welcome-panel-column aws-welcome-panel-last">';
                                    $html .= '<h4>' . __( 'Help', 'advanced-woo-search' ) . '</h4>';
                                    $html .= '<ul>';
                                        $html .= '<li><div class="aws-welcome-icon aws-welcome-widgets-menus"><a href="https://wordpress.org/support/plugin/advanced-woo-search/" target="_blank">' . __( 'Support Forums', 'advanced-woo-search' ) . '</a></div></li>';
                                        $html .= '<li><div class="aws-welcome-icon aws-welcome-widgets-menus"><a href="https://advanced-woo-search.com/contact/" target="_blank">' . __( 'Contact Form', 'advanced-woo-search' ) . '</a></div></li>';
                                    $html .= '</ul>';
                                $html .= '</div>';
                            $html .= '</div>';
                        $html .= '</div>';
                    $html .= '</div>';

                $html .= '</div>';
            $html .= '</div>';

            return $html;

        }

        /*
         * Get content for the reindex notice
         * @return string
         */
        static public function get_reindex_notice() {

            $html = '';

            $html .= '<div class="updated notice is-dismissible">';
                $html .= '<p>';
                    $html .= sprintf( esc_html__( 'Advanced Woo Search: In order to apply the changes in the index table you need to reindex. %s', 'advanced-woo-search' ), '<a class="button button-secondary" href="'.esc_url( admin_url('admin.php?page=aws-options') ).'">'.esc_html__( 'Go to Settings Page', 'advanced-woo-search' ).'</a>'  );
                $html .= '</p>';
            $html .= '</div>';

            return $html;

        }

    }

endif;