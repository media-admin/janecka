<?php

namespace Vendidero\StoreaBill;

/**
 * Post Types
 *
 * Registers post types and taxonomies.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Post types Class.
 */
class PostTypes {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_types' ), 5 );
	}

	/**
	 * Register core post types.
	 */
	public static function register_post_types() {
		if ( ! is_blog_installed() || post_type_exists( 'document_template' ) ) {
			return;
		}

		do_action( 'storeabill_register_post_type' );

		$supports = array( 'title', 'editor', 'custom-fields' );

		register_post_type(
			'document_template',
			apply_filters(
				'storeabill_register_post_type_document_template',
				array(
					'labels'              => array(
						'name'                  => _x( 'Templates', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'singular_name'         => _x( 'Template', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'all_items'             => _x( 'All Templates', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'menu_name'             => _x( 'Templates', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'add_new'               => _x( 'Add new', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'add_new_item'          => _x( 'Add new template', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'edit'                  => _x( 'Edit', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'edit_item'             => _x( 'Edit template', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'new_item'              => _x( 'New template', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'view_item'             => _x( 'View template', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'view_items'            => _x( 'View templates', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'search_items'          => _x( 'Search templates', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'not_found'             => _x( 'No templates found', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'not_found_in_trash'    => _x( 'No templates found in trash', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'parent'                => _x( 'Parent template', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'insert_into_item'      => _x( 'Insert into template', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'uploaded_to_this_item' => _x( 'Upload to this template', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'filter_items_list'     => _x( 'Filter templates', 'storeabill-core', 'woocommerce-germanized-pro' ),
					),
					'description'         => _x( 'This is where you can add new document templates.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'public'              => false,
					'show_ui'             => true,
					'publicly_queryable'  => current_user_can( 'manage_storeabill' ),
					'exclude_from_search' => true,
					'hierarchical'        => false,
					'rewrite'             => false,
					'query_var'           => true,
					'delete_with_user'    => false,
					'supports'            => $supports,
					'show_in_menu'        => false,
					'has_archive'         => false,
					'show_in_nav_menus'   => false,
					'show_in_rest'        => current_user_can( 'manage_storeabill' ),
				)
			)
		);

		do_action( 'storeabill_after_register_post_type' );
	}
}
