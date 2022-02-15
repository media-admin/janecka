<?php

/* --- JANECKA CUSTOM POST TYPES --- */

function janecka_post_types() {

	add_post_type_support( 'team', 'thumbnail' );
	add_post_type_support( 'team', 'excerpt' );

	add_filter( 'janecka_gallery_metabox_post_types', function( $types ) {
		$types[] = 'gallery';
		return $types;
	} );



	/* Custom Post Type "FAQ" */

	register_post_type( 'faq', array(
		'show_in_rest' => true,
		'public' => true,
		'show_ui' => true,
		'taxonomies' => array( 'faq-kategorien' ),
		'labels' => array(
			'name' =>  'FAQs',
			'add_new' => 'Neue FAQ erstellen',
			'edit_item' => 'FAQ bearbeiten',
			'singular_name' => 'FAQ',
			'all_items' => 'Alle FAQs',
			'supports' => array('title', 'editor', 'author', 'custom-fields', ),
		),
		'has_archive' => false,
		'exclude_from_search' => false,
		'rewrite' => array('slug' => 'faq', 'with_front' => true, 'pages' => true, 'feeds' => true,),
		'menu_position' => 10,
		'show_in_admin_bar'   => false,
		'show_in_nav_menus'   => false,
		'publicly_queryable'  => true,
		'menu_icon' => 'dashicons-lightbulb'
	));



	/* Custom Post Type "FILIALEN" */

	add_post_type_support( 'stores', 'thumbnail' );

	register_post_type( 'stores', array(
		'show_in_rest' => true,
		'public' => true,
		'show_ui' => true,
		'taxonomies' => array( 'filialen-zahlungsweisen' ),
		'labels' => array(
			'name' =>  'Filialen',
			'add_new' => 'Neue Filiale hinzufügen',
			'edit_item' => 'Filiale bearbeiten',
			'singular_name' => 'Filialen',
			'all_items' => 'Alle Filialen',
			'supports' => array('title', 'editor', 'author', 'custom-fields', ),
		),
		'has_archive' => true,
		'exclude_from_search' => false,
		'rewrite' => array('slug' => 'unsere-filialen', 'with_front' => true, 'pages' => true, 'feeds' => true,),
		'menu_position' => 8,
		'show_in_admin_bar'   => false,
		'show_in_nav_menus'   => false,
		'publicly_queryable'  => true,
		'menu_icon' => 'dashicons-store'
	));







	/* Custom Post Type "SLIDER" */

	add_post_type_support( 'slider', 'thumbnail' );

	register_post_type( 'slider', array(
		'show_in_rest' => true,
		'public' => true,
		'show_ui' => true,
		'labels' => array(
			'name' =>  'Header Slider',
			'add_new' => 'Neues Slider-Bild erstellen',
			'edit_item' => 'Slider-Bild bearbeiten',
			'singular_name' => 'Slider',
			'all_items' => 'Alle Slider',
			'supports' => array('title', 'editor', 'author', 'custom-fields', ),
		),
		'has_archive' => false,
		'exclude_from_search' => false,
		'rewrite' => array('slug' => 'sliders'),
		'menu_position' => 9,
		'show_in_admin_bar'   => false,
		'show_in_nav_menus'   => false,
		'publicly_queryable'  => true,
		'menu_icon' => 'dashicons-superhero'
	));



	/* Custom Post Type "HINWEISE" */

	register_post_type( 'notification', array(
		'show_in_rest' => true,
		'public' => true,
		'show_ui' => true,
		'labels' => array(
			'name' =>  'Hinweise',
			'add_new' => 'Neuen Hinweis erstellen',
			'edit_item' => 'Hinweis bearbeiten',
			'singular_name' => 'Hinweis',
			'all_items' => 'Alle Hinweise',
			'supports' => array('title', 'editor', 'author', 'custom-fields', ),
		),
		'has_archive' => false,
		'exclude_from_search' => false,
		'rewrite' => array('slug' => 'hinweise'),
		'menu_position' => 9,
		'show_in_admin_bar'   => false,
		'show_in_nav_menus'   => false,
		'publicly_queryable'  => true,
		'menu_icon' => 'dashicons-bell'
	));




}



/* --- JANECKA CUSTOM TAXONOMIES --- */

function janecka_taxonomies() {

	/* Custom Taxonomie "FAQ-KATEGORIE" */

	 $labels = array(
		'name' => _x( 'FAQ-Kategorien', 'taxonomy general name' ),
		'singular_name' => _x( 'FAQ-Kategorie', 'taxonomy singular name' ),
		'search_items' =>  __( 'FAQ-Kategorien durchsuchen' ),
		'popular_items' => __( 'Meist benutzte FAQ-Kategorien' ),
		'all_items' => __( 'Alle FAQ-Kategorien' ),
		'parent_item' => __( 'Übergeordnete FAQ-Kategorie' ),
		'parent_item_colon' => __( 'Übergeordnete FAQ-Kategorien:' ),
		'edit_item' => __( 'FAQ-Kategorie bearbeiten' ),
		'update_item' => __( 'FAQ-Kategorie aktualisieren' ),
		'add_new_item' => __( 'Neue FAQ-Kategorien hinzufügen' ),
		'new_item_name' => __( 'Name der neuen FAQ-Kategorien' ),
	);


	register_taxonomy('faq-kategorien', array('faqs'), array(
		'hierarchical' => true,
		'labels' => $labels,
		'show_ui' => true,
		'show_in_rest' => true,
		'query_var' => true,
		'rewrite' => array( 'slug' => 'faqs' ),

	));



	/* Custom Taxonomie "FILIALEN-ZAHLUNGSWEISEN" für Filialen */

	 $labels = array(
		'name' => _x( 'Filialen-Zahlungsweisen', 'taxonomy general name' ),
		'singular_name' => _x( 'Filialen-Zahlungsweise', 'taxonomy singular name' ),
		'search_items' =>  __( 'Filialen-Zahlungsweise durchsuchen' ),
		'popular_items' => __( 'Meist benutzte Filialen-Zahlungsweisen' ),
		'all_items' => __( 'Alle Filialen-Zahlungsweisen' ),

		'parent_item' => __( 'Übergeordnete Filialen-Zahlungsweise' ),
		'parent_item_colon' => __( 'Übergeordnete Filialen-Zahlungsweise:' ),

		'edit_item' => __( 'Filialen-Zahlungsweise bearbeiten' ),
		'update_item' => __( 'Filialen-Zahlungsweise aktualisieren' ),
		'add_new_item' => __( 'Neue Filialen-Zahlungsweise hinzufügen' ),
		'new_item_name' => __( 'Name der neuen Filialen-Zahlungsweise' ),
	);


	register_taxonomy('filialen-zahlungsweisen', array('stores'), array(
		'hierarchical' => true,
		'labels' => $labels,
		'show_ui' => true,
		'show_in_rest' => true,
		'query_var' => true,
		'rewrite' => array( 'slug' => 'filialen-zahlungsweisen' ),

	));








}

add_action('init', 'janecka_post_types');

add_action( 'init', 'janecka_taxonomies', 0 );

?>