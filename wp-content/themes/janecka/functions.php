<?php
/**
* Theme Funktionen und allgemeine Definitionen für die Website "janecka.at"
*/


/* Allgemeine Theme Funktionen */

/* Theme Features */
function medialab_features() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'post-formats', array( 'gallery' ) );
}

add_action('initafter_setup_theme', 'medialab_features');


/* Display Admin Bar */
function medialab_admin_bar(){

	if(is_user_logged_in()){
		add_filter( 'show_admin_bar', '__return_true' , 1000 );
	}
}

add_action('init', 'medialab_admin_bar' );





/* Styles and Scripts */

function medialab_register_styles() {

	// Import Normalizer
	wp_register_style( 'normalize', get_template_directory_uri() . '/assets/css/normalize.css' );
	wp_enqueue_style( 'normalize' );


	// Import FontAwesome Styles
	wp_register_style( 'fontawesome', get_template_directory_uri() . '/assets/css/fontawesome-all.css' );
	wp_enqueue_style( 'fontawesome' );


	// Import Bulma Framework
	wp_register_style( 'bulma', get_template_directory_uri() . '/vendor/bulma-0.9.0/css/bulma.min.css' );
	wp_enqueue_style( 'bulma' );


	// Import Bulma Radio Checkboxes Style
	wp_register_style( 'bulma-radio-checkboxes', get_template_directory_uri() . '/vendor/bulma-checkradio/bulma-checkradio.min.css' );
	wp_enqueue_style( 'bulma-radio-checkboxes' );


	// Import Slick Styles
	wp_register_style( 'slick', get_template_directory_uri() . '/assets/css/slick.css' );
	wp_enqueue_style( 'slick' );

	wp_register_style( 'slick-theme', get_template_directory_uri() . '/assets/css/slick-theme.css' );
	wp_enqueue_style( 'slick-theme' );


	// Import Cookie Script Stylesheets
	wp_register_style( 'cookie-style', get_template_directory_uri() . '/vendor/dywc_1.1/dywc.css' );
	wp_enqueue_style( 'cookie-style' );


	// Import Theme Styles via style.css
	wp_register_style( 'style', get_stylesheet_uri() );
	wp_enqueue_style( 'style' );

}

add_action( 'wp_enqueue_scripts', 'medialab_register_styles' );





function medialab_register_scripts() {

	// Import JQery 1.4.3
	wp_register_script( 'jquery-1-4-3', get_template_directory_uri() . '/vendor/jquery-1.4.3/jquery.min.js', '', null, true );
	wp_enqueue_script( 'jquery-1-4-3' );


	// Import JQery 1.11.0
	wp_register_script( 'jquery-1-11-0', '//code.jquery.com/jquery-1.11.0.min.js', '', null, true );
	wp_enqueue_script( 'jquery-1-11-0' );


	// Import JQery Migrate 1.2.1
	wp_register_script( 'jquery--migrate-1-2-1', '//code.jquery.com/jquery-migrate-1.2.1.min.js', '', null, true );
	wp_enqueue_script( 'jquery--migrate-1-2-1' );


	// Import Import Bulma Extensions
	wp_register_script( 'jquery--migrate-1-2-1', '//code.jquery.com/jquery-migrate-1.2.1.min.js', '', null, true );
	wp_enqueue_script( 'jquery--migrate-1-2-1' );



	// Import Button Back-to-Top
	wp_register_script( 'button-back-to-top', get_template_directory_uri() . '/assets/js/button-back-to-top.js', '', null, true );
	wp_enqueue_script( 'button-back-to-top' );


	// Import Cookie Notice Scripts
	wp_register_script( 'cookie-notice', get_template_directory_uri() . '/assets/js/dywc.js', '', null, true );
	wp_enqueue_script( 'cookie-notice' );


	// Import Slick Scripts
	wp_register_script( 'slick', get_template_directory_uri() . '/assets/js/slick.js', '', null, true );
	wp_enqueue_script( 'slick' );

}

add_action( 'wp_enqueue_scripts', 'medialab_register_scripts' );








/* Beitragsbild aktivieren  */

if ( ! function_exists( 'theme_slug_setup' ) ) :
 function theme_slug_setup() {
	add_theme_support( 'post-thumbnails' );
}
endif;
add_action( 'after_setup_theme', 'theme_slug_setup' );



/* Support des Dateityps SVG */

function medialab_add_upload_ext($checked, $file, $filename, $mimes){

	if(!$checked['type']){
		$wp_filetype = wp_check_filetype( $filename, $mimes );
		$ext = $wp_filetype['ext'];
		$type = $wp_filetype['type'];
		$proper_filename = $filename;

		if($type && 0 === strpos($type, 'image/') && $ext !== 'svg'){
			$ext = $type = false;
		}
		$checked = compact('ext','type','proper_filename');
	}
	return $checked;
}

add_filter('wp_check_filetype_and_ext', 'medialab_add_upload_ext', 10, 4);




/* Include der Custom Shortcode Bibliothek des aktuellen Themes */

include('classes/custom-shortcodes.php');




/* Support einer jeweils eigenen single.php nach Kategorie */

add_filter('single_template', 'check_for_category_single_template');

function check_for_category_single_template( $t ) {
	foreach( (array) get_the_category() as $cat ) {
		if ( file_exists(get_stylesheet_directory() . "/single-category-{$cat->slug}.php") ) return get_stylesheet_directory() . "/single-category-{$cat->slug}.php";
		if($cat->parent) {
			$cat = get_the_category_by_ID( $cat->parent );
			if ( file_exists(get_stylesheet_directory() . "/single-category-{$cat->slug}.php") ) return get_stylesheet_directory() . "/single-category-{$cat->slug}.php";
		}
	}
	return $t;
}



/* Fügt das Slug-Adds the Slug to the body tag's class  */

function medialab_add_slug_body_class( $classes ) {
	 global $post;
	if ( isset( $post ) ) {
	 $classes[] = $post->post_name;
	}
	return $classes;
}

add_filter( 'body_class', 'medialab_add_slug_body_class' );




/* Fügt den Support von Widgets hinzu */

function medialab_widgets_init() {
	register_sidebar( array(
		'name'          => 'Home right sidebar',
		'id'            => 'home_right_1',
		'before_widget' => '<div>',
		'after_widget'  => '</div>',
		'before_title'  => '<h2 class="rounded">',
		'after_title'   => '</h2>',
	) );
}

add_action( 'widgets_init', 'medialab_widgets_init' );






/* Styles für Plugin "wordpress-notification-bar" importieren

wp_dequeue_style('wnb_style');

*/






/* Menü Support */

function medialab_register_menu() {
	register_nav_menu( 'nav-menu-main', 'Hauptnavigation', 'janecka' );
	register_nav_menu( 'footer-navigation', 'Footernavigation', 'janecka' );
	register_nav_menu( 'footer-menu', 'Footermenü', 'janecka' );
}

add_action( 'init', 'medialab_register_menu' );



/* Navigation Walker für Hauptnavigation */

require_once('classes/bulma-navwalker.php');










/* Nav Walker for Mega Menu */

class MegaMenu_Nav_Walker extends Walker_Nav_Menu
{
		function start_el(&$output, $item, $depth = 0, $args, $id = 0) {
				global $wp_query;
				$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

				$class_names = $value = '';

				$classes = empty( $item->classes ) ? array() : (array) $item->classes;

				$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item ) );
				$class_names = ' class="' . esc_attr( $class_names ) . '"';

				$output .= $indent . '<li id="menu-item-'. $item->ID . '"' . $value . $class_names .'>';

				$attributes  = ! empty( $item->attr_title ) ? ' title="'  . esc_attr( $item->attr_title ) .'"' : '';
				$attributes .= ! empty( $item->target )     ? ' target="' . esc_attr( $item->target     ) .'"' : '';
				$attributes .= ! empty( $item->xfn )        ? ' rel="'    . esc_attr( $item->xfn        ) .'"' : '';
				$attributes .= ! empty( $item->url )        ? ' href="'   . esc_attr( $item->url        ) .'"' : '';

				$item_output = $args->before;
				$item_output .= '<a'. $attributes .'>';
				$item_output .= $args->link_before . apply_filters( 'the_title', $item->title, $item->ID ) . $args->link_after;
				$item_output .= '</a>';
				$item_output .= $item->subtitle;
				$item_output .= $args->after;

				$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args, $id );
		}
}


function my_wp_nav_menu_args( $args = '' ) {
	$args['walker'] = new MegaMenu_Nav_Walker();
	return $args;
}
add_filter( 'wp_nav_menu_args', 'my_wp_nav_menu_args' );







/* Navigation Walker für Footermenü */

class Footer_Walker extends Walker_Nav_Menu {

	function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0) {

	$classes = empty($item->classes) ? array () : (array) $item->classes;

	$class_names = join(' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item ) );

		!empty ( $class_names ) and $class_names = ' class="'. esc_attr( $class_names ) . '"';

		$output .= "<li>";
		$attributes  = 'class="footer-menu__link"';

		!empty( $item->attr_title ) and $attributes .= ' title="'  . esc_attr( $item->attr_title ) .'"';
		!empty( $item->target ) and $attributes .= ' target="' . esc_attr( $item->target     ) .'"';
		!empty( $item->xfn ) and $attributes .= ' rel="'    . esc_attr( $item->xfn        ) .'"';
		!empty( $item->url ) and $attributes .= ' href="'   . esc_attr( $item->url        ) .'"';
		$title = apply_filters( 'the_title', $item->title, $item->ID );
		$item_output = $args->before
			. "<a  $attributes>"
			. $args->link_before
			. $title
			. '</a></li>'
			. $args->link_after
			. $args->after;
		$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
	}
}












/* Fügt das Slug-Adds the Slug to the body tag's class  */

function add_slug_body_class( $classes ) {
	 global $post;
	if ( isset( $post ) ) {
	 $classes[] = $post->post_name;
	}
	return $classes;
}

add_filter( 'body_class', 'add_slug_body_class' );



/* Stellt sicher, dass XML-Files vom Plugin WP All Import korrekt importiert werden */

function wpai_is_xml_preprocess_enabled( $is_enabled ) {
	return false;
}

add_filter( 'is_xml_preprocess_enabled', 'wpai_is_xml_preprocess_enabled', 10, 1 );





/* Ersetzt die IP bei Kommentaren (IP-Anonymisierung lt. DSGVO) */

function medialab_replace_comment_ip() {
	 return "127.0.0.1";
}

add_filter( 'pre_comment_user_ip', 'medialab_replace_comment_ip', 50);




/* Setzt den Standardwert für den "Read More"-Link  */

function medialab_read_more_text( $text, $post_id ) {
	return '<a class="more-link" href="' . get_permalink() . '">' . __( 'Mehr erfahren' , 'janecka' ) . '</a>';
}

add_filter( 'the_content_more_link', 'medialab_read_more_text', 10, 2 );





/* Anzeige der Vorschaubilder in der Galerie */

function medialab_get_backend_preview_thumb($post_ID) {
	$post_thumbnail_id = get_post_thumbnail_id($post_ID);
	if ($post_thumbnail_id) {
		$post_thumbnail_img = wp_get_attachment_image_src($post_thumbnail_id, 'thumbnail');
		return $post_thumbnail_img[0];
	}
}

function medialab_preview_thumb_column_head($defaults) {
	$defaults['featured_image'] = 'Image';
	return $defaults;
}
add_filter('manage_posts_columns', 'medialab_preview_thumb_column_head');


function medialab_preview_thumb_column($column_name, $post_ID) {
	if ($column_name == 'featured_image') {
		$post_featured_image = medialab_get_backend_preview_thumb($post_ID);
			if ($post_featured_image) {
				echo '<img src="' . $post_featured_image . '" />';
			}
	}
}
add_action('manage_posts_custom_column', 'medialab_preview_thumb_column', 10, 2);





/* Registrierung Google Maps */

// Method 1: Filter.
	function my_acf_google_map_api( $api ){
			$api['key'] = 'AIzaSyC6BCon3WAqzUZpBlrCzG-ZuCFOqDPNvRM';
			return $api;
	}
	add_filter('acf/fields/google_map/api', 'my_acf_google_map_api');

	// Method 2: Setting.
	function my_acf_init() {
			acf_update_setting('google_api_key', 'AIzaSyC6BCon3WAqzUZpBlrCzG-ZuCFOqDPNvRM');
	}
	add_action('acf/init', 'my_acf_init');







/* Redirects the Store Detail Pages to the Store Posting */
function janecka_stores_template_redirect(){
	if ( function_exists( 'is_page' ) && is_category( 'unsere-filialen' ) ){
		$redirect_page_id = 440; // adjust to ID of page you are redirecting to
		wp_redirect( get_permalink( $redirect_page_id ) );
		exit();
	}

	elseif ( function_exists( 'is_page' ) && is_page( 'janecka-1140' ) ){
		$redirect_page_id = 205; // adjust to ID of page you are redirecting to
		wp_redirect( get_permalink( $redirect_page_id ) );
		exit();
	}

	elseif ( function_exists( 'is_page' ) && is_page( 'janecka-1010' ) ){
		$redirect_page_id = 206; // adjust to ID of page you are redirecting to
		wp_redirect( get_permalink( $redirect_page_id ) );
		exit();
	}

	elseif ( function_exists( 'is_page' ) && is_page( 'janecka-1060' ) ){
		$redirect_page_id = 211; // adjust to ID of page you are redirecting to
		wp_redirect( get_permalink( $redirect_page_id ) );
		exit();
	}

	elseif ( function_exists( 'is_page' ) && is_page( 'janecka-1100' ) ){
		$redirect_page_id = 209; // adjust to ID of page you are redirecting to
		wp_redirect( get_permalink( $redirect_page_id ) );
		exit();
	}

}

add_action( 'template_redirect', 'janecka_stores_template_redirect' );









/* === WooCommerce === */

/* General Features */

function janecka_add_woocommerce_support() {
	add_theme_support( 'woocommerce', array(
		'thumbnail_image_width' => 350,
		'single_image_width'    => 350,

		'product_grid'          => array(
			// 'default_rows'    => 3,
			// 'min_rows'        => 2,
			// 'max_rows'        => 8,
			'default_columns' => 3,
			'min_columns'     => 1,
			'max_columns'     => 3,
		),
	) );
}
add_action( 'after_setup_theme', 'janecka_add_woocommerce_support' );


/* Remove Product gallery Features (zoom, swipe, lightbox) */
remove_theme_support( 'wc-product-gallery-zoom' );
remove_theme_support( 'wc-product-gallery-lightbox' );
remove_theme_support( 'wc-product-gallery-slider' );


/* Adding Product gallery Features (zoom, swipe, lightbox) */
add_theme_support( 'wc-product-gallery-lightbox' );





/* Editing the Shop's Title
function wc_custom_shop_archive_title( $title ) {
		if ( is_shop() && isset( $title['title'] ) ) {
				$title['title'] = 'My Title';
		}

		return $title;
}
add_filter( 'document_title_parts', 'wc_custom_shop_archive_title' );

*/








/* Adding Taxononmy Terms to Body Class */
function janecka_custom_taxonomy_in_body_class( $classes ){
	if( is_singular( 'product' ) )
	{
		$custom_terms = get_the_terms(0, 'product_cat');
		if ($custom_terms) {
			foreach ($custom_terms as $custom_term) {
				$classes[] = 'product_cat_' . $custom_term->slug;
			}
		}
	}

	if( is_singular( 'product' ) )
	{
		$custom_terms = get_the_terms(0, 'product_tag');
		if ($custom_terms) {
			foreach ($custom_terms as $custom_term) {
				$classes[] = 'product_tag_' . $custom_term->slug;
			}
		}
	}
	return $classes;
}

add_filter( 'body_class', 'janecka_custom_taxonomy_in_body_class' );




/* Redirects the Category Page to Static Page */
function janecka_category_template_redirect(){
	if ( function_exists( 'is_product_category' ) && is_product_category( 'eheringe' ) ){
		$redirect_page_id = 558; // adjust to ID of page you are redirecting to
		wp_redirect( get_permalink( $redirect_page_id ) );
		exit();
	}

	elseif ( function_exists( 'is_product_category' ) && is_product_category( 'verlobungsringe-liebe-hochzeit' ) ){
		$redirect_page_id = 556; // adjust to ID of page you are redirecting to
		wp_redirect( get_permalink( $redirect_page_id ) );
		exit();
	}

	elseif ( function_exists( 'is_product_category' ) && is_product_category( 'uhren' ) ){
		$redirect_page_id = 428; // adjust to ID of page you are redirecting to
		wp_redirect( get_permalink( $redirect_page_id ) );
		exit();
	}

	elseif ( function_exists( 'is_product_category' ) && is_product_category( 'schmuck' ) ){
		$redirect_page_id = 430; // adjust to ID of page you are redirecting to
		wp_redirect( get_permalink( $redirect_page_id ) );
		exit();
	}

}

add_action( 'template_redirect', 'janecka_category_template_redirect' );





/* Adding Parent Categories to the Breadcrumb Menu */
add_filter('woocommerce_breadcrumb_main_term', function($main, $terms) {
	$url_cat = get_query_var( 'product_cat', false );

	if ($url_cat) {
		foreach($terms as $term) {
			if (preg_match('/'.$term->slug.'$/', $url_cat)) {
				return $term;
			}
		}
	}

	return $main;

}, 100, 2);



/* Adding Parent Tags to the Breadcrumb Menu */
function janecka_custom_product_tag_crumb( $crumbs, $breadcrumb ){
		// Targetting product tags
		$current_taxonomy  = 'product_tag';
		$current_term      = $GLOBALS['wp_query']->get_queried_object();
		$current_key_index = sizeof($crumbs) - 1;

		// Only product tags
		if( is_a($current_term, 'WP_Term') && term_exists( $current_term->term_id, $current_taxonomy ) ) {
				// The label term name
				$crumbs[$current_key_index][0] = sprintf( __( 'Marken > ' . '%s', 'janecka' ), $current_term->name );
				// The term link (not really necessary as we are already on the page)
				$crumbs[$current_key_index][1] = get_term_link( $current_term, $current_taxonomy );
		}
		return $crumbs;
}

add_filter( 'woocommerce_get_breadcrumb', 'janecka_custom_product_tag_crumb', 20, 2 );



/* Changing the Breadcrumb Separator */
function janecka_change_breadcrumb_delimiter( $defaults ) {
	// Change the breadcrumb delimeter from '/' to '>'
	$defaults['delimiter'] = ' &gt; ';
	return $defaults;
}

add_filter( 'woocommerce_breadcrumb_defaults', 'janecka_change_breadcrumb_delimiter' );





















/* --- Adding Different Delivery Time if not on stock --- */

/* Creating an additional element for extra delivery time */
function wdt_adjust_delivery_time_html( $text, $product ) {

	// this must be a variation if we can find a parent_id
	if ( $product->get_parent_id() ) {
		$id = $product->get_parent_id();
	} else {
		$id = $product->get_id();
	}

	// let's try to find the parent delivery time in postmeta
	if ( metadata_exists( 'post', $id, '_delivery_time_fallback' ) ) {
		$delivery_times = get_terms( array(
			'taxonomy' => 'product_delivery_time',
			'hide_empty' => false,
			)
		);

		$dtf_id = get_post_meta( $id, '_delivery_time_fallback', true );

		foreach ($delivery_times as $dtf) {
			if ( $dtf->term_id == $dtf_id) {
				$delivery_time = $dtf->name;
				break;
			}
		}

	} else {
		$delivery_time = 'auf Anfrage';
	}
	// return 'voraussichtlich '.$delivery_time;
	if ( $delivery_time == 'auf Anfrage') {
		return $delivery_time;
	} else {
		return $delivery_time . '<span class="delivery-time-info-after"> (Ausland abweichend)</span>';
	}

}

add_filter( 'woocommerce_germanized_delivery_time_backorder_html', 'wdt_adjust_delivery_time_html', 10, 4 );



/* Making sure that the extra delivery time gets saved to the product */
function wdt_add_deliver_time_fallback() {

	// Lieferzeiten aus den Terms generieren
	$delivery_times = get_terms( array(
		'taxonomy' => 'product_delivery_time',
		'hide_empty' => false,
	) );

	$options[''] = __( 'Keine', 'woocommerce');

	foreach ($delivery_times as $key => $term) {
		$options[$term->term_id] = $term->name;
	}

	// gewählte Lieferzeit aufbereiten
	$dtf_id = get_post_meta( get_the_ID(), '_delivery_time_fallback', true );

	// Element generieren
	woocommerce_wp_select( array(
		'id'          => 'delivery_time_fallback',
		'label'       => __('Lieferzeit bei Lieferrückstand', 'woocommerce'),
		'options'     => $options,
		'value'       => $dtf_id,
		'desc_tip' => true,
		'description' => __( 'Lieferzeit die angezeigt wird, wenn sich der Artikel im Lieferrückstand befindet.' ),
	) );

}

add_action( 'woocommerce_product_options_stock_status', 'wdt_add_deliver_time_fallback' );






/* --- Shop Page --- */

/* Remove unused Data */

// remove Archive Description
remove_action('woocommerce_archive_description','woocommerce_taxonomy_archive_description', 10, 0);

// remove Button Add to Cart
remove_action('woocommerce_after_shop_loop_item','woocommerce_template_loop_add_to_cart', 10, 0);




/* Adding customized Text */

function janecka_change_archive_description() {
	return '<mark>Jo geht jo eh!</mark>';
}

add_action( 'woocommerce_archive_description', 'janecka_change_archive_description', 27 );




/* Custom On Sale Badge Text */

function janecka_change_sale_text() {
	return '<span class="onsale">Sale</span>';
}

add_filter('woocommerce_sale_flash', 'janecka_change_sale_text');


/* Adding the Brand */

function janecka_shop_display_brand() {

	global $product;

	$brand_tag = wc_get_product_tag_list( $product->get_id(), '' );

	// Output
	echo '<div class="product-meta product-brand">';
	if ( ! empty( $brand_tag ) ){
		echo $brand_tag;
		} else {
			echo 'Noch keine Marke hinterlegt';
		}
	echo '</div>';

}

add_action( 'woocommerce_shop_loop_item_title', 'janecka_shop_display_brand', 9 );



/* Adding the SKU

function janecka_shop_display_skus() {

	global $product;

	if ( $product->get_sku() ) {
		echo '<div class="product-meta product-sku">Art.-Nr.: ' . $product->get_sku() . '</div>';
	}
}

add_action( 'woocommerce_after_shop_loop_item', 'janecka_shop_display_skus', 8 );

*/
















/* --- Single Product Page --- */


/* Use different Single Product Template in case of cat=Eheringe or cat=Verlobungsringe */
function custom_single_product_template_include( $template ) {
		if ( is_singular('product') && (has_term( 'eheringe', 'product_cat')) ) {
				$template = get_stylesheet_directory() . '/woocommerce/single-product-eheringe.php';
		} elseif ( is_singular('product') && (has_term( 'verlobungsringe-liebe-hochzeit', 'product_cat')) ) {
			$template = get_stylesheet_directory() . '/woocommerce/single-product-verlobungsringe.php';
		}
		return $template;
}

add_filter( 'template_include', 'custom_single_product_template_include', 50, 1 );




/* Remove unused Data */

// remove Breadcrumb
remove_action(
	'woocommerce_before_main_content','woocommerce_breadcrumb', 20, 0);


// remove Price
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );


// remove Price
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );


// remove Product Meta
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );


// remove Rating Stars
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );


// remove Additional Information Tabs
remove_action('woocommerce_after_single_product_summary','woocommerce_output_product_data_tabs', 10);


// remove Related Products
remove_action('woocommerce_after_single_product_summary','woocommerce_output_related_products', 20);


/* Adding the Brand */

function janecka_single_product_display_brand() {

	global $product;

	$brand_tag = wc_get_product_tag_list( $product->get_id(), '' );

	echo '<div class="single-product-meta single-product-brand product-tags">';
	if ( ! empty( $brand_tag ) ){
		echo $brand_tag;
		} else {
			echo 'Noch keine Marke hinterlegt';
		}
	echo '</div>';

}

add_action( 'woocommerce_single_product_summary', 'janecka_single_product_display_brand', 4 );


/* Adding the SKU */

function janecka_single_product_show_sku(){
	global $product;
	$sku = $product->get_sku();
	if ($sku != null) {
		echo '<span class="single-product-sku">';
		echo '<span class="single-product-label">Artikelnummer</span>' . $product->get_sku();
		echo '</span>';
	}
}

add_action( 'woocommerce_single_product_summary', 'janecka_single_product_show_sku', 11 );



/* Adding the Delivery Time ---------------------------> Backup after Initializing Additional Delivery time if not on stock

function janecka_single_product_display_delivery_time() {
	echo '<span class="single-product-delivery-time">';
	echo '<span class="single-product-label">Lieferzeit</span>' . $product->get_term('woocommerce_catalog_ordering');
	echo '</span>';
}

add_action( 'woocommerce_catalog_ordering', 'janecka_single-product_display_delivery_time', 10 );

<--------------------------- */




/* Adding Engraving

function janecka_single_product_display_engraving() {

	global $product;

	$product_attributes = $product->get_attributes(); // Get the product attributes

	// Output

	if ( !empty( $engraving_id ) ) {
		$engraving_id = $product_attributes['pa_gravur']['options']['0']; // returns the ID of the term
		$engraving_value = get_term( $engraving_id )->name; // gets the term name of the term from the ID

		echo '<span class="single-product-engraving">';
		echo $engraving_value;
		echo '</span>';
	} else {
		echo '<span class="single-product-engraving">';
		echo 'Nein';
		echo '</span>';
	}

}

add_action( 'woocommerce_single_product_summary', 'janecka_single_product_display_engraving', 20 );

*/


/* Adding the Price */

add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 27 );




/* Editing the In Stock Text */

function janecka_custom_get_availability_text( $availability, WC_Product $product ) {

		$stock = $product->get_stock_quantity();
		error_log( $stock );

		if ( $product->is_in_stock() ) {
				if ( $stock > 0 ) {
						$availability = 'Nur noch ' . $stock . ' St&uuml;ck auf Lager';
				}
		} else {
				$availability = __( 'Nur auf Bestellung', 'janecka' );
		}

		return $availability;
}

add_filter( 'woocommerce_get_availability_text', 'janecka_custom_get_availability_text', 99, 2 );




/* Adding the Order Stuff

woocommerce_before_variations_form
woocommerce_before_single_variation
woocommerce_single_variation
woocommerce_after_single_variation

*/



add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );

add_filter( "woocommerce_is_sold_individually" , "woocommerce_is_sold_individually_callback", 20, 2 );
function woocommerce_is_sold_individually_callback( $status, $product ){
	if ( $product->get_sold_individually() ){
		return true;
	}
	return false;
}



/* Adding the Quantity Field

function janecka_quantity_minus_sign() {
	echo '<div class="quantity-section">';
	echo '<div class="quantity-wrapper">';
	echo '<button type="button" class="minus" >-</button>';
}

add_action( 'woocommerce_before_add_to_cart_quantity', 'janecka_quantity_minus_sign' );



function janecka_quantity_plus_sign() {
	echo '<button type="button" class="plus" >+</button>';
	echo '</div>';
	echo '</div>';
}

add_action( 'woocommerce_after_add_to_cart_quantity', 'janecka_quantity_plus_sign' );






function janecka_quantity_inputs_for_woocommerce_loop_add_to_cart_link( $html, $product ) {
	if ( $product && $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() && ! $product->is_sold_individually() ) {
		$html = '<form action="' . esc_url( $product->add_to_cart_url() ) . '" class="cart stuff" method="post" enctype="multipart/form-data">';
			$html .= woocommerce_quantity_input( array(), $product, false );
			$html .= '<button type="submit" class="button alt">' . esc_html( $product->add_to_cart_text() ) . '</button>';
			$html .= '</form>';
	}
	return $html;
}

add_filter( 'woocommerce_loop_add_to_cart_link', 'janecka_quantity_inputs_for_woocommerce_loop_add_to_cart_link', 10, 3 );



/* Adding Quantity Buttons "Plus" and "Minus"

function janecka_quantity_plus_minus() {
	 // To run this on the single product page

		if ( ! is_product() ) return;

	 ?>
	 <script type="text/javascript">

			jQuery(document).ready(function(jQuery){

						jQuery('form.cart').on( 'click', 'button.plus, button.minus', function() {

						// Get current quantity values
						var qty = jQuery( this ).closest( 'form.cart' ).find( '.qty' );
						var val = parseFloat(qty.val());
						var max = parseFloat(qty.attr( 'max' ));
						var min = parseFloat(qty.attr( 'min' ));
						var step = parseFloat(qty.attr( 'step' ));


						// Change the value if plus or minus
						if ( jQuery( this ).is( '.plus' ) ) {
							 if ( max && ( max <= val ) ) {
									qty.val( max );
							 }
						else {
							 qty.val( val + step );
								 }
						}
						else {
							 if ( min && ( min >= val ) ) {
									qty.val( min );
							 }
							 else if ( val > 1 ) {
									qty.val( val - step );
							 }
						}

				 });

			});

	 </script>
	 <?php
}

add_action( 'wp_footer', 'janecka_quantity_plus_minus' );

*/









/* Changing the WooCommerce Shop Button's Text "Read More" */

function janecka_change_readmore_text( $translated_text, $text, $domain ) {

	if ( ! is_admin() && $domain === 'woocommerce' && $translated_text === 'Weiterlesen') {
		$translated_text = 'Zu den Details';
	}

	return $translated_text;
}

add_filter( 'gettext', 'janecka_change_readmore_text', 20, 3 );






/* ????????????????????????????

function janecka_out_of_stock_button( $args ){

	global $product;

	if( $product && !$product->is_in_stock() ){
		return '<a href="' . home_url( 'contact' ) . '">Contact us</a>';
	}
	return $args;
}


add_filter( 'woocommerce_loop_add_to_cart_link', 'janecka_out_of_stock_button' );


*/



/* CHANGING THE URL AND PAGE TITLE


function jpb_custom_meta_permalink( $link, $post ){

$post_meta = get_post_meta( $post->ID, '<insert your meta key here>', true );

if( empty( $post_meta ) || !is_string( $post_meta ) )

	 $post_meta = '<insert your default value (could be an empty string) here>';

$link = str_replace( '!!custom_field_placeholder!!', $post_meta, $link );

return $link;

}



add_filter( 'post_link', 'jpb_custom_meta_permalink', 10, 2 );



function append_sku_string( $link, $post ) {

$post_meta = get_post_meta( $post->ID, '_sku', true );

		 if ( 'product' == get_post_type( $post ) ) {

			 $link = $link . '#' .$post_meta;

			 return $link;

		 }

}

add_filter( 'post_type_link', 'append_sku_string', 1, 2 );

*/


/*

function append_sku_to_titles() {

 $all_ids = get_posts( array(
		'post_type' => 'product',
		'numberposts' => -1,
		'post_status' => 'publish',
		'fields' => 'ids'
));

foreach ( $all_ids as $id ) {
				$_product = wc_get_product( $id );
				$_sku = $_product->get_sku();
				$_title = $_product->get_title();

				$new_title = $_title . " " . $_sku;

				/*
				*   Tested.
				*   echo $_title + $_sku;
				*   echo("<script>console.log('Old: ".$_title. " - ". $_sku."');</script>");
				*   echo("<script>console.log('New: ".$new_title."');</script>");
				*/


				/*
				$updated = array();
				$updated['ID'] = $id;
				$updated['post_title'] = $new_title;

				wp_update_post( $updated );
}}

// Call the function with footer (*Attention)
add_action( 'wp_footer', 'append_sku_to_titles' );










/* Adding the Description */

remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );



function janecka_output_long_description() {

	global $product;
	?>

	 <?php
			echo '<div class="single-product-description">';
			echo '<h2 class=single-product-description-header>Produktbeschreibung</h2>';

			$output = wpautop($product->get_description());

			echo $output;
			echo '</div>';
	 ?>

<?php
}

add_action( 'woocommerce_after_single_product_summary', 'janecka_output_long_description', 15 );





/* Adding related Products  */

add_action( 'woocommerce_after_single_product', 'woocommerce_output_related_products', 50 );









/* --- Single Product Page EHERINGE --- */




















/* --- Shopping Cart --- */

function janecka_cart_info_shipping_costs( $args ){
	echo '<p>Hinweis: Versand für Österreich ab 50 € kostenlos - darunter 4,99€ , Ausland bis 80€ 9,99€</p>';
}

add_filter( 'woocommerce_after_cart_contents', 'janecka_cart_info_shipping_costs',99,1 );

add_filter( 'yith_wcan_suppress_cache', '__return_true' );



/* Repairing the Ajax Product Filter Functionality */
add_filter ('yith_wcan_use_wp_the_query_object', '__return_true');


/* Making the Ajac Product Filter Auto Completion work again  */

if ( ! function_exists( 'yith_wcan_set_query_vars' ) ) {
 function yith_wcan_set_query_vars() {
 if ( ! function_exists( 'YITH_WCAN_Query' ) ) {
 return;
			}

			$qo = get_queried_object();

			$query_vars = YITH_WCAN_Query()->get_query_vars();

 if ( is_product_taxonomy() && $qo instanceof WP_Term && ! isset( $query_vars[ $qo->taxonomy ] ) ) {
 YITH_WCAN_Query()->set( $qo->taxonomy, $qo->slug );
			} elseif ( is_page() && $qo instanceof WP_Post && ! isset( $query_vars['product_cat'] ) ) {
				 $page_slug    = $qo->post_name;
				 $related_term = get_term_by( 'slug', $page_slug, 'product_cat' );

 if ( $related_term && ! is_wp_error( $related_term ) ) {
 YITH_WCAN_Query()->set( 'product_cat', $related_term->slug );
				 }
			}
	 }

 add_action( 'wp', 'yith_wcan_set_query_vars' );
}