<?php
/*
Plugin Name: Responsive Lightbox & Gallery
Description: Responsive Lightbox & Gallery allows users to create galleries and view larger versions of images, galleries and videos in a lightbox (overlay) effect optimized for mobile devices.
Version: 2.4.1
Author: dFactory
Author URI: http://www.dfactory.eu/
Plugin URI: http://www.dfactory.eu/plugins/responsive-lightbox/
License: MIT License
License URI: http://opensource.org/licenses/MIT
Text Domain: responsive-lightbox
Domain Path: /languages

Responsive Lightbox & Gallery
Copyright (C) 2013-2022, Digital Factory - info@digitalfactory.pl

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

define( 'RESPONSIVE_LIGHTBOX_URL', plugins_url( '', __FILE__ ) );
define( 'RESPONSIVE_LIGHTBOX_PATH', plugin_dir_path( __FILE__ ) );
define( 'RESPONSIVE_LIGHTBOX_REL_PATH', dirname( plugin_basename( __FILE__ ) ) . DIRECTORY_SEPARATOR );

include_once( RESPONSIVE_LIGHTBOX_PATH . 'includes' . DIRECTORY_SEPARATOR . 'class-fast-image.php' );
include_once( RESPONSIVE_LIGHTBOX_PATH . 'includes' . DIRECTORY_SEPARATOR . 'class-galleries.php' );
include_once( RESPONSIVE_LIGHTBOX_PATH . 'includes' . DIRECTORY_SEPARATOR . 'class-folders.php' );
include_once( RESPONSIVE_LIGHTBOX_PATH . 'includes' . DIRECTORY_SEPARATOR . 'class-frontend.php' );
include_once( RESPONSIVE_LIGHTBOX_PATH . 'includes' . DIRECTORY_SEPARATOR . 'class-settings.php' );
include_once( RESPONSIVE_LIGHTBOX_PATH . 'includes' . DIRECTORY_SEPARATOR . 'class-tour.php' );
include_once( RESPONSIVE_LIGHTBOX_PATH . 'includes' . DIRECTORY_SEPARATOR . 'class-welcome.php' );
include_once( RESPONSIVE_LIGHTBOX_PATH . 'includes' . DIRECTORY_SEPARATOR . 'class-widgets.php' );
include_once( RESPONSIVE_LIGHTBOX_PATH . 'includes' . DIRECTORY_SEPARATOR . 'functions.php' );

/**
 * Responsive Lightbox class.
 *
 * @class Responsive_Lightbox
 * @version	2.4.1
 */
class Responsive_Lightbox {

	public $defaults = [
		'settings' => [
			'tour'							=> true,
			'script'						=> 'swipebox',
			'selector'						=> 'lightbox',
			'default_gallery'				=> 'default',
			'builder_gallery'				=> 'basicgrid',
			'default_woocommerce_gallery'	=> 'default',
			'galleries'						=> true,
			'gallery_image_size'			=> 'full',
			'gallery_image_title'			=> 'default',
			'gallery_image_caption'			=> 'default',
			'force_custom_gallery'			=> false,
			'woocommerce_gallery_lightbox'	=> false,
			'videos'						=> true,
			'widgets'						=> false,
			'comments'						=> false,
			'image_links'					=> true,
			'image_title'					=> 'default',
			'image_caption'					=> 'default',
			'images_as_gallery'				=> false,
			'deactivation_delete'			=> false,
			'loading_place'					=> 'header',
			'conditional_loading'			=> false,
			'enable_custom_events'			=> false,
			'custom_events'					=> 'ajaxComplete',
			'update_version'				=> 1,
			'update_notice'					=> true,
			'update_delay_date'				=> 0
		],
		'builder' => [
			'gallery_builder'		=> true,
			'categories'			=> true,
			'tags'					=> true,
			'permalink'				=> 'rl_gallery',
			'permalink_categories'	=> 'rl_category',
			'permalink_tags'		=> 'rl_tag',
			'archives'				=> true,
			'archives_category'		=> 'all'
		],
		'configuration' => [
			'swipebox' => [
				'animation'					=> 'css',
				'force_png_icons'			=> false,
				'hide_close_mobile'			=> false,
				'remove_bars_mobile'		=> false,
				'hide_bars'					=> true,
				'hide_bars_delay'			=> 5000,
				'video_max_width'			=> 1080,
				'loop_at_end'				=> false
			],
			'prettyphoto' => [
				'animation_speed'			=> 'normal',
				'slideshow'					=> false,
				'slideshow_delay'			=> 5000,
				'slideshow_autoplay'		=> false,
				'opacity'					=> 75,
				'show_title'				=> true,
				'allow_resize'				=> true,
				'allow_expand'				=> true,
				'width'						=> 1080,
				'height'					=> 720,
				'separator'					=> '/',
				'theme'						=> 'pp_default',
				'horizontal_padding'		=> 20,
				'hide_flash'				=> false,
				'wmode'						=> 'opaque',
				'video_autoplay'			=> false,
				'modal'						=> false,
				'deeplinking'				=> false,
				'overlay_gallery'			=> true,
				'keyboard_shortcuts'		=> true,
				'social'					=> false
			],
			'fancybox' => [
				'modal'						=> false,
				'show_overlay'				=> true,
				'show_close_button'			=> true,
				'enable_escape_button'		=> true,
				'hide_on_overlay_click'		=> true,
				'hide_on_content_click'		=> false,
				'cyclic'					=> false,
				'show_nav_arrows'			=> true,
				'auto_scale'				=> true,
				'scrolling'					=> 'yes',
				'center_on_scroll'			=> true,
				'opacity'					=> true,
				'overlay_opacity'			=> 70,
				'overlay_color'				=> '#666',
				'title_show'				=> true,
				'title_position'			=> 'outside',
				'transitions'				=> 'fade',
				'easings'					=> 'swing',
				'speeds'					=> 300,
				'change_speed'				=> 300,
				'change_fade'				=> 100,
				'padding'					=> 5,
				'margin'					=> 5,
				'video_width'				=> 1080,
				'video_height'				=> 720
			],
			'nivo' => [
				'effect'					=> 'fade',
				'click_overlay_to_close'	=> true,
				'keyboard_nav'				=> true,
				'error_message'				=> 'The requested content cannot be loaded. Please try again later.'
			],
			'imagelightbox' => [
				'animation_speed'			=> 250,
				'preload_next'				=> true,
				'enable_keyboard'			=> true,
				'quit_on_end'				=> false,
				'quit_on_image_click'		=> false,
				'quit_on_document_click'	=> true
			],
			'tosrus' => [
				'effect'					=> 'slide',
				'infinite'					=> true,
				'keys'						=> false,
				'autoplay'					=> true,
				'pause_on_hover'			=> false,
				'timeout'					=> 4000,
				'pagination'				=> true,
				'pagination_type'			=> 'thumbnails',
				'close_on_click'			=> false
			],
			'featherlight' => [
				'open_speed'				=> 250,
				'close_speed'				=> 250,
				'close_on_click'			=> 'background',
				'close_on_esc'				=> true,
				'gallery_fade_in'			=> 100,
				'gallery_fade_out'			=> 300
			],
			'magnific' => [
				'disable_on'				=> 0,
				'mid_click'					=> true,
				'preloader'					=> true,
				'close_on_content_click'	=> true,
				'close_on_background_click'	=> true,
				'close_button_inside'		=> true,
				'show_close_button'			=> true,
				'enable_escape_key'			=> true,
				'align_top'					=> false,
				'fixed_content_position'	=> 'auto',
				'fixed_background_position'	=> 'auto',
				'auto_focus_last'			=> true
			]
		],
		'folders' => [
			'active'			=> true,
			'media_taxonomy'	=> 'rl_media_folder',
			'media_tags'		=> false,
			'jstree_wholerow'	=> true,
			'show_in_menu'		=> false,
			'folders_removal'	=> true
		],
		'remote_library' => [
			'active'		=> true,
			'caching'		=> false,
			'cache_expiry'	=> 1,
			'flickr'		=> [
				'active'	=> false,
				'api_key'	=> ''
			],
			'unsplash'		=> [
				'active'	=> false,
				'api_key'	=> ''
			],
			'wikimedia'		=> [
				'active'	=> true
			]
		],
		'capabilities' => [
			'active' => false,
			'roles' => [
				'administrator'	=> [
					'publish_galleries',
					'edit_galleries',
					'edit_published_galleries',
					'edit_others_galleries',
					'edit_private_galleries',
					'delete_galleries',
					'delete_published_galleries',
					'delete_others_galleries',
					'delete_private_galleries',
					'read_private_galleries',
					'manage_gallery_categories',
					'manage_gallery_tags',
					'edit_lightbox_settings'
				],
				'editor' => [
					'publish_galleries',
					'edit_galleries',
					'edit_published_galleries',
					'edit_others_galleries',
					'edit_private_galleries',
					'delete_galleries',
					'delete_published_galleries',
					'delete_others_galleries',
					'delete_private_galleries',
					'read_private_galleries',
					'manage_gallery_categories',
					'manage_gallery_tags'
				],
				'author' => [
					'publish_galleries',
					'edit_galleries',
					'edit_published_galleries',
					'delete_galleries',
					'delete_published_galleries'
				]
			]
		],
		'basicgrid_gallery'	=> [
			'columns_lg'		=> 4,
			'columns_md'		=> 3,
			'columns_sm'		=> 2,
			'columns_xs'		=> 1,
			'gutter'			=> 2,
			'force_height'		=> false,
			'row_height'		=> 150
		],
		'basicslider_gallery' => [
			'adaptive_height'		=> true,
			'loop'					=> false,
			'captions'				=> 'overlay',
			'init_single'			=> true,
			'responsive'			=> true,
			'preload'				=> 'visible',
			'pager'					=> true,
			'controls'				=> true,
			'hide_on_end'			=> true,
			'slide_margin'			=> 0,
			'transition'			=> 'fade',
			'kenburns_zoom'			=> 120,
			'speed'					=> 800,
			'easing'				=> 'swing',
			'continuous'			=> true,
			'use_css'				=> true,
			'slideshow'				=> true,
			'slideshow_direction'	=> 'next',
			'slideshow_hover'		=> true,
			'slideshow_hover_delay'	=> 100,
			'slideshow_delay'		=> 500,
			'slideshow_pause'		=> 3000
		],
		'basicmasonry_gallery' => [
			'columns_lg'		=> 4,
			'columns_md'		=> 3,
			'columns_sm'		=> 2,
			'columns_xs'		=> 2,
			'gutter'			=> 20,
			'margin'			=> 20,
			'origin_left'		=> true,
			'origin_top'		=> true
		],
		'version' => '2.4.1',
		'activation_date' => ''
	];
	public $options = [];
	public $providers = [];
	public $capabilities = [
		'publish_galleries'				=> '',
		'edit_galleries'				=> '',
		'edit_published_galleries'		=> '',
		'edit_others_galleries'			=> '',
		'edit_private_galleries'		=> '',
		'delete_galleries'				=> '',
		'delete_published_galleries'	=> '',
		'delete_others_galleries'		=> '',
		'delete_private_galleries'		=> '',
		'read_private_galleries'		=> '',
		'manage_gallery_categories'		=> '',
		'manage_gallery_tags'			=> '',
		'edit_lightbox_settings'		=> ''
	];
	public $gallery_types = [];
	private $deactivaion_url = '';
	private $version = false;
	private $notices = [];
	private $current_script = '';
	private static $_instance;

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		register_activation_hook( __FILE__, [ $this, 'activate_multisite' ] );
		register_deactivation_hook( __FILE__, [ $this, 'deactivate_multisite' ] );

		// change from older versions
		$this->version = $db_version = get_option( 'responsive_lightbox_version' );

		// no version?
		if ( $db_version === false )
			$this->version = $db_version = '1.0.0';

		// 1.0.5 update
		if ( version_compare( $db_version, '1.0.5', '<' ) ) {
			if ( ( $array = get_option( 'rl_settings' ) ) !== false ) {
				update_option( 'responsive_lightbox_settings', $array );
				delete_option( 'rl_settings' );
			}

			if ( ( $array = get_option( 'rl_configuration' ) ) !== false ) {
				update_option( 'responsive_lightbox_configuration', $array );
				delete_option( 'rl_configuration' );
			}
		}

		$capabilities = get_option( 'responsive_lightbox_capabilities' );

		if ( empty( $capabilities ) )
			$capabilities = [];

		$this->options['settings'] = array_merge( $this->defaults['settings'], ( ( $array = get_option( 'responsive_lightbox_settings' ) ) === false ? [] : $array ) );
		$this->options['folders'] = array_merge( $this->defaults['folders'], ( ( $array = get_option( 'responsive_lightbox_folders' ) ) === false ? [] : $array ) );
		$this->options['builder'] = array_merge( $this->defaults['builder'], ( ( $array = get_option( 'responsive_lightbox_builder' ) ) === false ? [] : $array ) );
		$this->options['capabilities'] = array_merge( $this->defaults['capabilities'], $capabilities );
		$this->options['remote_library'] = array_merge( $this->defaults['remote_library'], ( ( $array = get_option( 'responsive_lightbox_remote_library' ) ) === false ? [] : $array ) );

		// for multi arrays we have to merge them separately
		$db_conf_opts = ( ( $base = get_option( 'responsive_lightbox_configuration' ) ) === false ? [] : $base );

		foreach ( $this->defaults['configuration'] as $script => $settings ) {
			$this->options['configuration'][$script] = array_merge( $settings, ( isset( $db_conf_opts[$script] ) ? $db_conf_opts[$script] : [] ) );
		}

		// add default galleries options
		$this->options['basicgrid_gallery'] = array_merge( $this->defaults['basicgrid_gallery'], ( ( $array = get_option( 'responsive_lightbox_basicgrid_gallery', $this->defaults['basicgrid_gallery'] ) ) == false ? [] : $array ) );
		$this->options['basicslider_gallery'] = array_merge( $this->defaults['basicslider_gallery'], ( ( $array = get_option( 'responsive_lightbox_basicslider_gallery', $this->defaults['basicslider_gallery'] ) ) == false ? [] : $array ) );
		$this->options['basicmasonry_gallery'] = array_merge( $this->defaults['basicmasonry_gallery'], ( ( $array = get_option( 'responsive_lightbox_basicmasonry_gallery', $this->defaults['basicmasonry_gallery'] ) ) == false ? [] : $array ) );

		// set current lightbox script
		$this->current_script = $this->options['settings']['script'];

		// actions
		add_action( 'upgrader_process_complete', [ $this, 'update_plugin' ], 10, 2 );
		add_action( 'plugins_loaded', [ $this, 'plugins_loaded_init' ] );
		add_action( 'in_admin_header', [ $this, 'display_breadcrumbs' ] );
		add_action( 'after_setup_theme', [ $this, 'init_remote_libraries' ], 11 );
		add_action( 'init', [ $this, 'init_galleries' ] );
		add_action( 'init', [ $this, 'init_folders' ] );
		add_action( 'init', [ $this, 'init_gutenberg' ] );
		add_action( 'admin_init', [ $this, 'update_notice' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'front_scripts_styles' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts_styles' ] );
		add_action( 'sidebar_admin_setup', [ $this, 'sidebar_admin_setup' ] );
		add_action( 'admin_footer', [ $this, 'modal_deactivation_template' ] );
		add_action( 'wp_ajax_rl_dismiss_notice', [ $this, 'dismiss_notice' ] );
		add_action( 'wp_ajax_rl-deactivate-plugin', [ $this, 'deactivate_plugin' ] );

		// filters
		add_filter( 'plugin_action_links', [ $this, 'plugin_settings_link' ], 10, 2 );
		add_filter( 'plugin_row_meta', [ $this, 'plugin_extend_links' ], 10, 2 );
	}

	/**
	 * Disable object cloning.
	 */
	public function __clone() {}

	/**
	 * Disable unserializing of the class.
	 */
	public function __wakeup() {}

	/**
	 * Main Responsive Lightbox instance.
	 *
	 * @return object
	 */
	public static function instance() {
		if ( self::$_instance === null )
			self::$_instance = new self();

		return self::$_instance;
	}

	/**
	 * Single site activation function.
	 *
	 * @return void
	 */
	public function activate_single() {
		// transient for welcome screen
		if ( get_option( 'responsive_lightbox_activation_date', false ) === false )
			set_transient( 'rl_activation_redirect', 1, 3600 );
		elseif ( $this->version !== false ) {
			// activated from old version
			if ( version_compare( $this->version, '2.0.0', '<' ) )
				set_transient( 'rl_activation_redirect', 1, 3600 );
		} else
			set_transient( 'rl_activation_redirect', 1, 3600 );

		// grant new capabilities
		$this->grant_capabilities();

		// add options if needed
		add_option( 'responsive_lightbox_settings', $this->defaults['settings'], '', false );
		add_option( 'responsive_lightbox_configuration', $this->defaults['configuration'], '', false );
		add_option( 'responsive_lightbox_folders', $this->defaults['folders'], '', false );
		add_option( 'responsive_lightbox_builder', $this->defaults['builder'], '', false );
		add_option( 'responsive_lightbox_capabilities', $this->defaults['capabilities'], '', false );
		add_option( 'responsive_lightbox_remote_library', $this->defaults['remote_library'], '', false );
		add_option( 'responsive_lightbox_version', $this->defaults['version'], '', false );

		// permalinks
		flush_rewrite_rules();
	}

	/**
	 * Single site deactivation function.
	 *
	 * @param bool $multi Multisite?
	 * @return void
	 */
	public function deactivate_single( $multi = false ) {
		if ( $multi === true ) {
			$options = get_option( 'responsive_lightbox_settings' );
			$check = $options['deactivation_delete'];
		} else
			$check = $this->options['settings']['deactivation_delete'];

		// delete options if needed
		if ( $check ) {
			global $wp_roles;

			// remove all capabilities
			foreach ( $wp_roles->roles as $role_name => $label ) {
				$role = $wp_roles->get_role( $role_name );

				foreach ( $this->capabilities as $capability => $label ) {
					$role->remove_cap( $capability );
				}
			}

			delete_option( 'responsive_lightbox_settings' );
			delete_option( 'responsive_lightbox_configuration' );
			delete_option( 'responsive_lightbox_folders' );
			delete_option( 'responsive_lightbox_builder' );
			delete_option( 'responsive_lightbox_capabilities' );
			delete_option( 'responsive_lightbox_remote_library' );
			delete_option( 'responsive_lightbox_version' );
		}

		// permalinks
		flush_rewrite_rules();
	}

	/**
	 * Activation function.
	 *
	 * @param bool $networkwide Multisite?
	 * @return void
	 */
	public function activate_multisite( $networkwide ) {
		if ( is_multisite() && $networkwide ) {
			global $wpdb;

			$activated_blogs = [];
			$current_blog_id = $wpdb->blogid;
			$blogs_ids = $wpdb->get_col( $wpdb->prepare( 'SELECT blog_id FROM ' . $wpdb->blogs, '' ) );

			foreach ( $blogs_ids as $blog_id ) {
				switch_to_blog( $blog_id );
				$this->activate_single();
				$activated_blogs[] = (int) $blog_id;
			}

			switch_to_blog( $current_blog_id );
			update_site_option( 'responsive_lightbox_activated_blogs', $activated_blogs, [] );
		} else
			$this->activate_single();
	}

	/**
	 * Dectivation function.
	 *
	 * @param bool $networkwide Multisite?
	 * @return void
	 */
	public function deactivate_multisite( $networkwide ) {
		if ( is_multisite() && $networkwide ) {
			global $wpdb;

			$current_blog_id = $wpdb->blogid;
			$blogs_ids = $wpdb->get_col( $wpdb->prepare( 'SELECT blog_id FROM ' . $wpdb->blogs, '' ) );

			if ( ($activated_blogs = get_site_option( 'responsive_lightbox_activated_blogs', false, false )) === false )
				$activated_blogs = [];

			foreach ( $blogs_ids as $blog_id ) {
				switch_to_blog( $blog_id );
				$this->deactivate_single( true );

				if ( in_array( (int) $blog_id, $activated_blogs, true ) )
					unset( $activated_blogs[array_search( $blog_id, $activated_blogs )] );
			}

			switch_to_blog( $current_blog_id );
			update_site_option( 'responsive_lightbox_activated_blogs', $activated_blogs );
		} else
			$this->deactivate_single();
	}

	/**
	 * Update plugin hook.
	 *
	 * @return void
	 */
	public function update_plugin( $upgrader_object, $options ) {
		// plugin update?
		if ( $options['action'] === 'update' && $options['type'] === 'plugin' && array_key_exists( 'plugins', $options ) && is_array( $options['plugins'] ) ) {
			// get current plugin name
			$current_plugin = plugin_basename( __FILE__ );

			// search for a plugin
			foreach ( $options['plugins'] as $plugin ) {
				// found?
				if ( $plugin === $current_plugin ) {
					// 2.3.1
					if ( version_compare( $this->version, '2.3.1', '<' ) ) {
						// grant new capabilities just before the version update
						$this->grant_capabilities();
					}
				}
			}
		}
	}

	/**
	 * Grant new capabilities to user roles.
	 *
	 * @return void
	 */
	public function grant_capabilities() {
		// no capabilities?
		if ( ! $this->options['capabilities']['active'] )
			return;

		global $wp_roles;

		$user = wp_get_current_user();

		// add new capabilities to roles
		foreach ( $wp_roles->roles as $role_name => $label ) {
			// get user role
			$role = $wp_roles->get_role( $role_name );

			// treat such role as administrator
			if ( $role->has_cap( 'install_plugins' ) ) {
				// add every capability
				foreach ( $this->capabilities as $capability => $label ) {
					$role->add_cap( $capability );
				}

				// force capability on the current user
				if ( is_a( $user, 'WP_User' ) && ! $user->has_cap( 'edit_lightbox_settings' ) )
					$user->add_cap( 'edit_lightbox_settings' );
			}

			// role exists?
			if ( array_key_exists( $role_name, $this->defaults['capabilities']['roles'] ) ) {
				// add new capabilities to the current roles nevertheless
				foreach ( $this->capabilities as $capability => $label ) {
					// capability exists?
					if ( in_array( $capability, $this->defaults['capabilities']['roles'][$role_name], true ) ) {
						$role->add_cap( $capability );

						// force capability on the current user
						if ( $capability === 'edit_lightbox_settings' && is_a( $user, 'WP_User' ) && ! $user->has_cap( 'edit_lightbox_settings' ) && in_array( $role_name, $user->roles, true ) )
							$user->add_cap( 'edit_lightbox_settings' );
					}
				}
			}
		}
	}

	/**
	 * Early plugin initialization.
	 *
	 * @return void
	 */
	public function plugins_loaded_init() {
		// load textdomain
		load_plugin_textdomain( 'responsive-lightbox', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		// set gallery types
		$this->gallery_types = [
			'default'		=> __( 'Default', 'responsive-lightbox' ),
			'basicgrid'		=> __( 'Basic Grid', 'responsive-lightbox' ),
			'basicslider'	=> __( 'Basic Slider', 'responsive-lightbox' ),
			'basicmasonry'	=> __( 'Basic Masonry', 'responsive-lightbox' )
		];

		// only for backend
		if ( is_admin() ) {
			$galleries = apply_filters( 'rl_gallery_types', $this->gallery_types );

			$update_settings = false;

			// lightbox scipt does not exist?
			if ( ! array_key_exists( $this->options['settings']['script'], $this->options['configuration'] ) ) {
				// set default script
				$this->options['settings']['script'] = $this->defaults['settings']['script'];

				$update_settings = true;
			}

			// check default WordPress gallery
			if ( ! array_key_exists( $this->options['settings']['default_gallery'], $galleries ) ) {
				// set default gallery
				$this->options['settings']['default_gallery'] = $this->defaults['settings']['default_gallery'];

				$update_settings = true;
			}

			// check default builder gallery
			if ( ! array_key_exists( $this->options['settings']['builder_gallery'], $galleries ) ) {
				// set default gallery
				$this->options['settings']['builder_gallery'] = $this->defaults['settings']['builder_gallery'];

				$update_settings = true;
			}

			// check default WooCommerce gallery
			if ( ! array_key_exists( $this->options['settings']['default_woocommerce_gallery'], $galleries ) ) {
				// set default gallery
				$this->options['settings']['default_woocommerce_gallery'] = $this->defaults['settings']['default_woocommerce_gallery'];

				$update_settings = true;
			}

			// update settings
			if ( $update_settings )
				update_option( 'responsive_lightbox_settings', $this->options['settings'], false );

			// set capabilities with labels
			$this->capabilities = [
				'publish_galleries'				=> __( 'Publish Galleries', 'responsive-lightbox' ),
				'edit_galleries'				=> __( 'Edit Galleries', 'responsive-lightbox' ),
				'edit_published_galleries'		=> __( 'Edit Published Galleries', 'responsive-lightbox' ),
				'edit_others_galleries'			=> __( 'Edit Others Galleries', 'responsive-lightbox' ),
				'edit_private_galleries'		=> __( 'Edit Private Galleries', 'responsive-lightbox' ),
				'delete_galleries'				=> __( 'Delete Galleries', 'responsive-lightbox' ),
				'delete_published_galleries'	=> __( 'Delete Published Galleries', 'responsive-lightbox' ),
				'delete_others_galleries'		=> __( 'Delete Others Galleries', 'responsive-lightbox' ),
				'delete_private_galleries'		=> __( 'Delete Private Galleries', 'responsive-lightbox' ),
				'read_private_galleries'		=> __( 'Read Private Galleries', 'responsive-lightbox' ),
				'manage_gallery_categories'		=> __( 'Manage Gallery Categories', 'responsive-lightbox' ),
				'manage_gallery_tags'			=> __( 'Manage Gallery Tags', 'responsive-lightbox' ),
				'edit_lightbox_settings'		=> __( 'Manage Settings', 'responsive-lightbox' )
			];

			// 2.3.0 update
			if ( version_compare( $this->version, '2.3.0', '<' ) ) {
				// grant new capabilities just before the version update
				$this->grant_capabilities();
			}

			// plugin version update
			if ( version_compare( $this->version, $this->defaults['version'], '<' ) )
				update_option( 'responsive_lightbox_version', $this->defaults['version'], false );
		}

		include_once( RESPONSIVE_LIGHTBOX_PATH . 'includes' . DIRECTORY_SEPARATOR . 'class-multilang.php' );
	}

	/**
	 * Update notice.
	 *
	 * @return void
	 */
	public function update_notice() {
		if ( ! current_user_can( 'install_plugins' ) )
			return;

		$current_update = 2;

		// get current time
		$current_time = time();

		if ( $this->options['settings']['update_version'] < $current_update ) {
			// check version, if update ver is lower than plugin ver, set update notice to true
			$this->options['settings'] = array_merge( $this->options['settings'], [ 'update_version' => $current_update, 'update_notice' => true ] );

			update_option( 'responsive_lightbox_settings', $this->options['settings'] );

			// set activation date
			$activation_date = get_option( 'responsive_lightbox_activation_date' );

			if ( $activation_date === false )
				update_option( 'responsive_lightbox_activation_date', $current_time );
		}

		// display current version notice
		if ( $this->options['settings']['update_notice'] === true ) {
			// include notice js, only if needed
			add_action( 'admin_print_scripts', [ $this, 'admin_inline_js' ], 999 );

			// get activation date
			$activation_date = get_option( 'responsive_lightbox_activation_date' );

			if ( (int) $this->options['settings']['update_delay_date'] === 0 ) {
				if ( $activation_date + 1209600 > $current_time )
					$this->options['settings']['update_delay_date'] = $activation_date + 1209600;
				else
					$this->options['settings']['update_delay_date'] = $current_time;

				update_option( 'responsive_lightbox_settings', $this->options['settings'] );
			}

			if ( ( ! empty( $this->options['settings']['update_delay_date'] ) ? (int) $this->options['settings']['update_delay_date'] : $current_time ) <= $current_time )
				$this->add_notice( sprintf( __( "Hey, you've been using <strong>Responsive Lightbox & Gallery</strong> for more than %s", 'responsive-lightbox' ), human_time_diff( $activation_date, $current_time ) ) . '<br />' . __( 'Could you please do me a BIG favor and give it a 5-star rating on WordPress to help us spread the word and boost our motivation.', 'responsive-lightbox' ) . '<br /><br />' . __( 'Your help is much appreciated. Thank you very much', 'responsive-lightbox' ) . ' ~ <strong>Bartosz Arendt</strong>, ' . sprintf( __( 'founder of <a href="%s" target="_blank">dFactory</a> plugins.', 'responsive-lightbox' ), 'https://dfactory.eu/' ) . '<br /><br />' . sprintf( __( '<a href="%s" class="rl-dismissible-notice" target="_blank" rel="noopener">Ok, you deserve it</a><br /><a href="javascript:void(0);" class="rl-dismissible-notice rl-delay-notice" rel="noopener">Nope, maybe later</a><br /><a href="javascript:void(0);" class="rl-dismissible-notice" rel="noopener">I already did</a>', 'responsive-lightbox' ), 'https://wordpress.org/support/plugin/responsive-lightbox/reviews/?filter=5#new-post' ), 'notice notice-info is-dismissible rl-notice' );
		}
	}

	/**
	 * Dismiss notice.
	 *
	 * @return void
	 */
	public function dismiss_notice() {
		if ( ! current_user_can( 'install_plugins' ) )
			return;

		if ( wp_verify_nonce( esc_attr( $_REQUEST['nonce'] ), 'rl_dismiss_notice' ) ) {
			$notice_action = empty( $_REQUEST['notice_action'] ) || $_REQUEST['notice_action'] === 'hide' ? 'hide' : esc_attr( $_REQUEST['notice_action'] );

			switch ( $notice_action ) {
				// delay notice
				case 'delay':
					// set delay period to 1 week from now
					$this->options['settings'] = array_merge( $this->options['settings'], [ 'update_delay_date' => time() + 1209600 ] );
					update_option( 'responsive_lightbox_settings', $this->options['settings'] );
					break;

				// hide notice
				default:
					$this->options['settings'] = array_merge( $this->options['settings'], [ 'update_notice' => false ] );
					$this->options['settings'] = array_merge( $this->options['settings'], [ 'update_delay_date' => 0 ] );

					update_option( 'responsive_lightbox_settings', $this->options['settings'] );
			}
		}

		exit;
	}

	/**
	 * Add admin notices.
	 *
	 * @param string $html Notice HTML
	 * @param string $status Notice status
	 * @param bool $paragraph Whether to use paragraph
	 * @param bool $network
	 * @return void
	 */
	public function add_notice( $html = '', $status = 'error', $paragraph = true, $network = false ) {
		$this->notices[] = [
			'html' 		=> $html,
			'status'	=> $status,
			'paragraph'	=> $paragraph
		];

		add_action( 'admin_notices', [ $this, 'display_notice' ] );

		if ( $network )
			add_action( 'network_admin_notices', [ $this, 'display_notice' ] );
	}

	/**
	 * Print admin notices.
	 *
	 * @return void
	 */
	public function display_notice() {
		foreach( $this->notices as $notice ) {
			echo '
			<div class="' . $notice['status'] . '">
				' . ( $notice['paragraph'] ? '<p>' : '' ) . '
				' . $notice['html'] . '
				' . ( $notice['paragraph'] ? '</p>' : '' ) . '
			</div>';
		}
	}

	/**
	 * Print admin scripts.
	 *
	 * @return void
	 */
	public function admin_inline_js() {
		if ( ! current_user_can( 'install_plugins' ) )
			return;
		?>
		<script type="text/javascript">
			( function( $ ) {
				// ready event
				$( function() {
					// save dismiss state
					$( '.rl-notice.is-dismissible' ).on( 'click', '.notice-dismiss, .rl-dismissible-notice', function( e ) {
						var notice_action = 'hide';

						if ( $( e.currentTarget ).hasClass( 'rl-delay-notice' ) ) {
							notice_action = 'delay'
						}

						$.post( ajaxurl, {
							action: 'rl_dismiss_notice',
							notice_action: notice_action,
							url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
							nonce: '<?php echo wp_create_nonce( 'rl_dismiss_notice' ); ?>'
						} );

						$( e.delegateTarget ).slideUp( 'fast' );
					} );
				} );
			} )( jQuery );
		</script>
		<?php
	}

	/**
	 * Add links to Support Forum.
	 *
	 * @param array $links
	 * @param string $file
	 * @return array
	 */
	public function plugin_extend_links( $links, $file ) {
		if ( ! current_user_can( 'install_plugins' ) )
			return $links;

		$plugin = plugin_basename( __FILE__ );

		if ( $file == $plugin ) {
			return array_merge(
				$links, [ sprintf( '<a href="https://dfactory.eu/support/forum/responsive-lightbox/" target="_blank">%s</a>', __( 'Support', 'responsive-lightbox' ) ) ]
			);
		}

		return $links;
	}

	/**
	 * Add links to Settings page.
	 *
	 * @param array $links
	 * @param string $file
	 * @return array
	 */
	public function plugin_settings_link( $links, $file ) {
		if ( ! is_admin() || ! current_user_can( apply_filters( 'rl_lightbox_settings_capability', 'manage_options' ) ) )
			return $links;

		static $plugin;

		$plugin = plugin_basename( __FILE__ );

		if ( $file == $plugin ) {
			if ( ! empty( $links['deactivate'] ) ) {
				// link already contains class attribute?
				if ( preg_match( '/<a.*?class=(\'|")(.*?)(\'|").*?>/is', $links['deactivate'], $result ) === 1 )
					$links['deactivate'] = preg_replace( '/(<a.*?class=(?:\'|").*?)((?:\'|").*?>)/s', '$1 rl-deactivate-plugin-modal$2', $links['deactivate'] );
				else
					$links['deactivate'] = preg_replace( '/(<a.*?)>/s', '$1 class="rl-deactivate-plugin-modal">', $links['deactivate'] );

				// link already contains href attribute?
				if ( preg_match( '/<a.*?href=(\'|")(.*?)(\'|").*?>/is', $links['deactivate'], $result ) === 1 ) {
					if ( ! empty( $result[2] ) )
						$this->deactivaion_url = $result[2];
				}
			}

			// put settings link at start
			array_unshift( $links, sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php' ) . '?page=responsive-lightbox-settings', __( 'Settings', 'responsive-lightbox' ) ) );

			// add add-ons link
			$links[] = sprintf( '<a href="%s" style="color: green;">%s</a>', admin_url( 'admin.php' ) . '?page=responsive-lightbox-addons', __( 'Add-ons', 'responsive-lightbox' ) );
		}

		return $links;
	}

	/**
	 * Deactivation modal HTML template.
	 *
	 * @return void
	 */
	public function modal_deactivation_template() {
		global $pagenow;

		// display only for plugins page
		if ( $pagenow !== 'plugins.php' )
			return;

		echo '
		<div id="rl-deactivation-modal" style="display: none;">
			<div id="rl-deactivation-container">
				<div id="rl-deactivation-body">
					<div class="rl-deactivation-options">
						<p><em>' . __( "We're sorry to see you go. Could you please tell us what happened?", 'responsive-lightbox' ) . '</em></p>
						<ul>';

			foreach ( [
				'1'	=> __( "I couldn't figure out how to make it work.", 'responsive-lightbox' ),
				'2'	=> __( 'I found another plugin to use for the same task.', 'responsive-lightbox' ),
				'3'	=> __( 'The User Interface is not clear to me.', 'responsive-lightbox' ),
				'4'	=> __( 'The plugin is not what I was looking for.', 'responsive-lightbox' ),
				'5'	=> __( "Support isn't timely.", 'responsive-lightbox' ),
				'6'	=> __( 'Other', 'responsive-lightbox' )
			] as $option => $text ) {
				echo '
							<li><label><input type="radio" name="rl_deactivation_option" value="' . $option . '" ' . checked( '6', $option, false ) . ' />' . esc_html( $text ) . '</label></li>';
			}

			echo '
						</ul>
					</div>
					<div class="rl-deactivation-textarea">
						<textarea name="rl_deactivation_other"></textarea>
					</div>
				</div>
				<div id="rl-deactivation-footer">
					<a href="" class="button rl-deactivate-plugin-cancel">' . __( 'Cancel', 'responsive-lightbox' ) . '</a>
					<a href="' . $this->deactivaion_url . '" class="button button-secondary rl-deactivate-plugin-simple">' . __( 'Deactivate', 'responsive-lightbox' ) . '</a>
					<a href="' . $this->deactivaion_url . '" class="button button-primary right rl-deactivate-plugin-data">' . __( 'Deactivate & Submit', 'responsive-lightbox' ) . '</a>
					<span class="spinner"></span>
				</div>
			</div>
		</div>';
	}

	/**
	 * Send data about deactivation of the plugin.
	 *
	 * @return void
	 */
	public function deactivate_plugin() {
		// check permissions
		if ( ! current_user_can( 'install_plugins' ) || wp_verify_nonce( $_POST['nonce'], 'rl-deactivate-plugin' ) === false )
			return;

		if ( isset( $_POST['option_id'] ) ) {
			$option_id = (int) $_POST['option_id'];
			$other = esc_html( $_POST['other'] );

			// avoid fake submissions
			if ( $option_id == 6 && $other == '' )
				wp_send_json_success();

			wp_remote_post(
				'https://dfactory.eu/wp-json/api/v1/forms/',
				[
					'timeout'	=> 5,
					'blocking'	=> true,
					'headers'	=> [],
					'body'		=> [
						'id'		=> 13,
						'option'	=> $option_id,
						'other'		=> $other
					]
				]
			);

			wp_send_json_success();
		}

		wp_send_json_error();
	}

	/**
	 * Get current lightbox script.
	 *
	 * @return string
	 */
	public function get_lightbox_script() {
		// get current script
		return $this->current_script;
	}

	/**
	 * Set current lightbox script.
	 *
	 * @return bool
	 */
	public function set_lightbox_script( $script ) {
		if ( array_key_exists( $script, $this->settings->settings['settings']['fields']['script']['options'] ) ) {
			// set new lightbox script
			$this->current_script = $script;

			return true;
		}

		return false;
	}

	/**
	 * Initialize remote libraries.
	 *
	 * @return void
	 */
	public function init_remote_libraries() {
		// include classes
		include_once( RESPONSIVE_LIGHTBOX_PATH . 'includes/class-remote-library.php' );
		include_once( RESPONSIVE_LIGHTBOX_PATH . 'includes/class-remote-library-api.php' );

		$this->remote_library = new Responsive_Lightbox_Remote_Library();

		// simple html dom
		if ( ! function_exists( 'file_get_html' ) )
			include_once( RESPONSIVE_LIGHTBOX_PATH . 'library/simplehtmldom/simple_html_dom.php' );

		// include providers
		include_once( RESPONSIVE_LIGHTBOX_PATH . 'includes/providers/class-flickr.php' );
		include_once( RESPONSIVE_LIGHTBOX_PATH . 'includes/providers/class-unsplash.php' );
		include_once( RESPONSIVE_LIGHTBOX_PATH . 'includes/providers/class-wikimedia.php' );
	}

	/**
	 * Initialize galleries.
	 *
	 * @return void
	 */
	public function init_galleries() {
		// initialize gallery class
		new Responsive_Lightbox_Galleries( ! $this->options['builder']['gallery_builder'] );

		// end if in read only mode
		if ( ! $this->options['builder']['gallery_builder'] )
			return;

		$taxonomies = [];

		if ( $this->options['builder']['categories'] ) {
			$taxonomies[] = 'rl_category';

			$data = [
				'public'				=> true,
				'hierarchical'			=> true,
				'labels'				=> [
					'name'				=> _x( 'Gallery Categories', 'taxonomy general name', 'responsive-lightbox' ),
					'singular_name'		=> _x( 'Gallery Category', 'taxonomy singular name', 'responsive-lightbox' ),
					'search_items'		=> __( 'Search Gallery Categories', 'responsive-lightbox' ),
					'all_items'			=> __( 'All Gallery Categories', 'responsive-lightbox' ),
					'parent_item'		=> __( 'Parent Gallery Category', 'responsive-lightbox' ),
					'parent_item_colon'	=> __( 'Parent Gallery Category:', 'responsive-lightbox' ),
					'edit_item'			=> __( 'Edit Gallery Category', 'responsive-lightbox' ),
					'view_item'			=> __( 'View Gallery Category', 'responsive-lightbox' ),
					'update_item'		=> __( 'Update Gallery Category', 'responsive-lightbox' ),
					'add_new_item'		=> __( 'Add New Gallery Category', 'responsive-lightbox' ),
					'new_item_name'		=> __( 'New Gallery Category Name', 'responsive-lightbox' ),
					'menu_name'			=> __( 'Categories', 'responsive-lightbox' )
				],
				'show_ui'				=> true,
				'show_admin_column'		=> true,
				'update_count_callback'	=> '_update_post_term_count',
				'query_var'				=> true,
				'rewrite'				=> [
					'slug'			=> $this->options['builder']['permalink_categories'],
					'with_front'	=> false,
					'hierarchical'	=> false
				]
			];

			if ( $this->options['capabilities']['active'] ) {
				$data['capabilities'] = [
					'manage_terms'	=> 'manage_gallery_categories',
					'edit_terms'	=> 'manage_gallery_categories',
					'delete_terms'	=> 'manage_gallery_categories',
					'assign_terms'	=> 'edit_galleries'
				];
			}

			register_taxonomy( 'rl_category', 'rl_gallery', $data );
		}

		if ( $this->options['builder']['tags'] ) {
			$taxonomies[] = 'rl_tag';

			$data = [
				'public'				=> true,
				'hierarchical'			=> false,
				'labels'				=> [
					'name'							=> _x( 'Gallery Tags', 'taxonomy general name', 'responsive-lightbox' ),
					'singular_name'					=> _x( 'Gallery Tag', 'taxonomy singular name', 'responsive-lightbox' ),
					'search_items'					=> __( 'Search Gallery Tags', 'responsive-lightbox' ),
					'popular_items'					=> __( 'Popular Gallery Tags', 'responsive-lightbox' ),
					'all_items'						=> __( 'All Gallery Tags', 'responsive-lightbox' ),
					'parent_item'					=> null,
					'parent_item_colon'				=> null,
					'edit_item'						=> __( 'Edit Gallery Tag', 'responsive-lightbox' ),
					'update_item'					=> __( 'Update Gallery Tag', 'responsive-lightbox' ),
					'add_new_item'					=> __( 'Add New Gallery Tag', 'responsive-lightbox' ),
					'new_item_name'					=> __( 'New Gallery Tag Name', 'responsive-lightbox' ),
					'separate_items_with_commas'	=> __( 'Separate gallery tags with commas', 'responsive-lightbox' ),
					'add_or_remove_items'			=> __( 'Add or remove gallery tags', 'responsive-lightbox' ),
					'choose_from_most_used'			=> __( 'Choose from the most used gallery tags', 'responsive-lightbox' ),
					'menu_name'						=> __( 'Tags', 'responsive-lightbox' )
				],
				'show_ui'				=> true,
				'show_admin_column'		=> true,
				'update_count_callback'	=> '_update_post_term_count',
				'query_var'				=> true,
				'rewrite'				=> [
					'slug'			=> $this->options['builder']['permalink_tags'],
					'with_front'	=> false,
					'hierarchical'	=> false
				]
			];

			if ( $this->options['capabilities']['active'] ) {
				$data['capabilities'] = [
					'manage_terms'	=> 'manage_gallery_tags',
					'edit_terms'	=> 'manage_gallery_tags',
					'delete_terms'	=> 'manage_gallery_tags',
					'assign_terms'	=> 'edit_galleries'
				];
			}

			register_taxonomy( 'rl_tag', 'rl_gallery', $data );
		}

		$data = [
			'labels'				=> [
				'name'					=> _x( 'Galleries', 'post type general name', 'responsive-lightbox' ),
				'singular_name'			=> _x( 'Gallery', 'post type singular name', 'responsive-lightbox' ),
				'add_new'				=> __( 'Add New', 'responsive-lightbox' ),
				'add_new_item'			=> __( 'Add New Gallery', 'responsive-lightbox' ),
				'edit_item'				=> __( 'Edit Gallery', 'responsive-lightbox' ),
				'new_item'				=> __( 'New Gallery', 'responsive-lightbox' ),
				'view_item'				=> __( 'View Gallery', 'responsive-lightbox' ),
				'view_items'			=> __( 'View Galleries', 'responsive-lightbox' ),
				'search_items'			=> __( 'Search Galleries', 'responsive-lightbox' ),
				'not_found'				=> __( 'No galleries found', 'responsive-lightbox' ),
				'not_found_in_trash'	=> __( 'No galleries found in trash', 'responsive-lightbox' ),
				'all_items'				=> __( 'All Galleries', 'responsive-lightbox' ),
				'menu_name'				=> __( 'Gallery', 'responsive-lightbox' )
			],
			'description'			=> '',
			'public'				=> true,
			'exclude_from_search'	=> false,
			'publicly_queryable'	=> true,
			'show_ui'				=> true,
			'show_in_menu'			=> true,
			'show_in_admin_bar'		=> true,
			'show_in_nav_menus'		=> true,
			'menu_position'			=> 57,
			'menu_icon'				=> 'dashicons-format-gallery',
			'map_meta_cap'			=> true,
			'hierarchical'			=> false,
			'supports'				=> [ 'title', 'author', 'thumbnail' ],
			'has_archive'			=> $this->options['builder']['archives'],
			'query_var'				=> true,
			'can_export'			=> true,
			'taxonomies'			=> $taxonomies,
			'rewrite'				=> [
				'slug'			=> $this->options['builder']['permalink'],
				'with_front'	=> false,
				'feed'			=> true,
				'pages'			=> true
			]
		];

		if ( $this->options['capabilities']['active'] ) {
			$data['capability_type'] = [ 'gallery', 'galleries' ];
			$data['capabilities'] = [
				'publish_posts'				=> 'publish_galleries',
				'edit_posts'				=> 'edit_galleries',
				'edit_published_posts'		=> 'edit_published_galleries',
				'edit_others_posts'			=> 'edit_others_galleries',
				'edit_private_posts'		=> 'edit_private_galleries',
				'delete_posts'				=> 'delete_galleries',
				'delete_published_posts'	=> 'delete_published_galleries',
				'delete_others_posts'		=> 'delete_others_galleries',
				'delete_private_posts'		=> 'delete_private_galleries',
				'read_private_posts'		=> 'read_private_galleries',
				'edit_post'					=> 'edit_gallery',
				'delete_post'				=> 'delete_gallery',
				'read_post'					=> 'read_gallery'
			];
		}

		// register rl_gallery
		register_post_type( 'rl_gallery', $data );

		if ( $this->options['builder']['archives'] && $this->options['builder']['archives_category'] !== 'all' && ! is_admin() )
			add_action( 'pre_get_posts', [ $this, 'gallery_archives' ] );

		add_filter( 'post_updated_messages', [ $this, 'post_updated_messages' ] );
	}

	/**
	 * Create breadcrumbs.
	 *
	 * @return void
	 */
	public function display_breadcrumbs() {
		global $pagenow, $typenow;

		$breadcrumbs = [];

		// get settings
		$settings = Responsive_Lightbox()->settings;

		// settings?
		if ( isset( $_GET['page'] ) && preg_match( '/^responsive-lightbox-(' . implode( '|', array_keys( $settings->tabs ) ) . ')$/', $_GET['page'], $tabs ) === 1 ) {
			$tab_key = isset( $tabs[1] ) ? $tabs[1] : 'settings';
			$section_key = isset( $_GET['section'] ) ? esc_attr( $_GET['section'] ) : ( ! empty( $settings->tabs[$tab_key]['default_section'] ) ? $settings->tabs[$tab_key]['default_section'] : '' );

			$breadcrumbs[] = [
				'url'	=> admin_url( 'admin.php?page=responsive-lightbox-settings' ),
				'name'	=> __( 'Settings', 'responsive-lightbox' )
			];

			if ( $tab_key === 'configuration' ) {
				$breadcrumbs[] = [
					'url'	=> admin_url( 'admin.php?page=responsive-lightbox-configuration' ),
					'name'	=> $settings->tabs[$tab_key]['name']
				];

				// valid lightbox script?
				if ( array_key_exists( $section_key, $settings->scripts ) ) {
					$breadcrumbs[] = [
						'url'	=> '',
						'name'	=> $settings->scripts[$section_key]['name']
					];
				}
			} elseif ( $tab_key === 'gallery' ) {
				$breadcrumbs[] = [
					'url'	=> admin_url( 'admin.php?page=responsive-lightbox-gallery' ),
					'name'	=> $settings->tabs[$tab_key]['name']
				];

				// valid gallery?
				if ( array_key_exists( $section_key, $settings->tabs['gallery']['sections'] ) ) {
					$breadcrumbs[] = [
						'url'	=> '',
						'name'	=> $settings->tabs['gallery']['sections'][$section_key]
					];
				}
			} else {
				$breadcrumbs[] = [
					'url'	=> '',
					'name'	=> $settings->tabs[$tab_key]['name']
				];
			}
		// gallery listing
		} elseif ( $pagenow === 'edit.php' && $typenow === 'rl_gallery' ) {
			$breadcrumbs[] = [
				'url'	=> '',
				'name'	=> __( 'Galleries', 'responsive-lightbox' )
			];
		// single gallery
		} elseif ( $pagenow === 'post.php' && get_post_type() === 'rl_gallery' ) {
			$breadcrumbs[] = [
				'url'	=> admin_url( 'edit.php?post_type=rl_gallery' ),
				'name'	=> __( 'Galleries', 'responsive-lightbox' )
			];

			$breadcrumbs[] = [
				'url'	=> '',
				'name'	=> __( 'Edit gallery', 'responsive-lightbox' )
			];
		// new gallery
		} elseif ( $pagenow === 'post-new.php' && get_post_type() === 'rl_gallery' ) {
			$breadcrumbs[] = [
				'url'	=> admin_url( 'edit.php?post_type=rl_gallery' ),
				'name'	=> __( 'Galleries', 'responsive-lightbox' )
			];

			$breadcrumbs[] = [
				'url'	=> '',
				'name'	=> __( 'New gallery', 'responsive-lightbox' )
			];
		// gallery taxonomies
		} elseif ( in_array( $pagenow, [ 'edit-tags.php', 'term.php' ], true ) && isset( $_GET['taxonomy'], $_GET['post_type'] ) && $_GET['post_type'] === 'rl_gallery' ) {
			$breadcrumbs[] = [
				'url'	=> admin_url( 'edit.php?post_type=rl_gallery' ),
				'name'	=> __( 'Galleries', 'responsive-lightbox' )
			];

			// categories
			if ( $_GET['taxonomy'] === 'rl_category' ) {
				// new category
				if ( $pagenow === 'term.php' ) {
					$breadcrumbs[] = [
						'url'	=> admin_url( 'edit-tags.php?taxonomy=rl_category&post_type=rl_gallery' ),
						'name'	=> __( 'Categories', 'responsive-lightbox' )
					];

					$breadcrumbs[] = [
						'url'	=> '',
						'name'	=> __( 'Edit category', 'responsive-lightbox' )
					];
				// categories listing
				} else {
					$breadcrumbs[] = [
						'url'	=> '',
						'name'	=> __( 'Categories', 'responsive-lightbox' )
					];
				}
			// tags
			} elseif ( $_GET['taxonomy'] === 'rl_tag' ) {
				// new tag
				if ( $pagenow === 'term.php' ) {
					$breadcrumbs[] = [
						'url'	=> admin_url( 'edit-tags.php?taxonomy=rl_category&post_type=rl_gallery' ),
						'name'	=> __( 'Tags', 'responsive-lightbox' )
					];

					$breadcrumbs[] = [
						'url'	=> '',
						'name'	=> __( 'Edit tag', 'responsive-lightbox' )
					];
				// tags listing
				} else {
					$breadcrumbs[] = [
						'url'	=> '',
						'name'	=> __( 'Tags', 'responsive-lightbox' )
					];
				}
			}
		}

		// any breadcrumbs?
		if ( ! empty( $breadcrumbs ) ) {
			array_unshift( $breadcrumbs,
				[
					'url'	=> '',
					'name'	=> __( 'Responsive Lightbox & Gallery', 'responsive-lightbox' )
				]
			);

			$html = [];

			foreach ( $breadcrumbs as $breadcrumb ) {
				if ( ! empty( $breadcrumb['url'] ) )
					$html[] = '<a href="' . esc_url( $breadcrumb['url'] ) . '">' . esc_html( $breadcrumb['name'] ) . '</a>';
				else
					$html[] = '<span>' . esc_html( $breadcrumb['name'] ) . '</span>';
			}

			echo '
			<div class="responsive-lightbox-breadcrumbs-container">
				<div class="responsive-lightbox-breadcrumbs">
					<p>' . implode( ' / ', $html ) . '</p>
				</div>
			</div>';
		}
	}

	/**
	 * Gallery update messages.
	 *
	 * @param array $messages
	 * @return array
	 */
	public function gallery_archives( $query ) {
		if ( is_post_type_archive( 'rl_gallery' ) ) {
			$query->set(
				'tax_query',
				[
					'relation' => 'OR',
					[
						'taxonomy' => 'rl_category',
						'field'    => 'slug',
						'terms'    => $this->options['builder']['archives_category']
					]
				]
			);
		}
	}

	/**
	 * Gallery update messages.
	 *
	 * @param array $messages
	 * @return array
	 */
	public function post_updated_messages( $messages ) {
		$post = get_post();
		$post_type = get_post_type( $post );
		$post_type_object = get_post_type_object( $post_type );

		$messages['rl_gallery'] = [
			1	 => __( 'Gallery updated.', 'responsive-lightbox' ),
			4	 => __( 'Gallery updated.', 'responsive-lightbox' ),
			5	 => isset( $_GET['revision'] ) ? sprintf( __( 'Gallery restored to revision from %s', 'responsive-lightbox' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6	 => __( 'Gallery published.', 'responsive-lightbox' ),
			7	 => __( 'Gallery saved.', 'responsive-lightbox' ),
			8	 => __( 'Gallery submitted.', 'responsive-lightbox' ),
			9	 => sprintf( __( 'Gallery scheduled for: <strong>%1$s</strong>.', 'responsive-lightbox' ), date_i18n( __( 'M j, Y @ G:i', 'responsive-lightbox' ), strtotime( $post->post_date ) ) ),
			10	 => __( 'Gallery draft updated.', 'responsive-lightbox' )
		];

		if ( $post_type_object->publicly_queryable && 'rl_gallery' === $post_type ) {
			$permalink = get_permalink( $post->ID );

			$view_link = sprintf( ' <a href="%s">%s</a>', esc_url( $permalink ), __( 'View gallery', 'responsive-lightbox' ) );
			$messages[$post_type][1] .= $view_link;
			$messages[$post_type][6] .= $view_link;
			$messages[$post_type][9] .= $view_link;

			$preview_permalink = add_query_arg( 'preview', 'true', $permalink );
			$preview_link = sprintf( ' <a target="_blank" href="%s">%s</a>', esc_url( $preview_permalink ), __( 'Preview gallery', 'responsive-lightbox' ) );
			$messages[$post_type][8] .= $preview_link;
			$messages[$post_type][10] .= $preview_link;
		}

		return $messages;
	}

	/**
	 * Initialize folders.
	 *
	 * @return void
	 */
	public function init_folders() {
		// initialize folder class
		new Responsive_Lightbox_Folders( ! $this->options['folders']['active'] );

		// end if in read only mode
		if ( ! $this->options['folders']['active'] )
			return;

		// register media taxonomy
		$this->register_media_taxonomy( 'rl_media_folder' );

		// register media tags
		if ( $this->options['folders']['media_tags'] ) {
			register_taxonomy(
				'rl_media_tag',
				'attachment',
				[
					'public'				=> true,
					'hierarchical'			=> false,
					'labels'				=> [
						'name'				=> _x( 'Media Tags', 'taxonomy general name', 'responsive-lightbox' ),
						'singular_name'	=> _x( 'Media Tag', 'taxonomy singular name', 'responsive-lightbox' ),
						'search_items'	=> __( 'Search Tags', 'responsive-lightbox' ),
						'all_items'		=> __( 'All Tags', 'responsive-lightbox' ),
						'edit_item'		=> __( 'Edit Tag', 'responsive-lightbox' ),
						'update_item'	=> __( 'Update Tag', 'responsive-lightbox' ),
						'add_new_item'	=> __( 'Add New Tag', 'responsive-lightbox' ),
						'new_item_name'	=> __( 'New Tag Name', 'responsive-lightbox' ),
						'not_found'		=> __( 'No tags found.', 'responsive-lightbox' ),
						'menu_name'		=> _x( 'Tags', 'taxonomy general name', 'responsive-lightbox' ),
					],
					'show_ui'				=> true,
					'show_in_menu'			=> $this->options['folders']['show_in_menu'],
					'show_in_nav_menus'		=> false,
					'show_in_quick_edit'	=> true,
					'show_tagcloud'			=> false,
					'show_admin_column'		=> $this->options['folders']['show_in_menu'],
					'update_count_callback'	=> '_update_generic_term_count',
					'query_var'				=> false,
					'rewrite'				=> false
				]
			);
		}

		// get non-builtin hierarchical taxonomies
		$taxonomies = get_taxonomies(
			[
				'object_type'	=> [ 'attachment' ],
				'hierarchical'	=> true,
				'_builtin'		=> false
			],
			'objects',
			'and'
		);

		$media_taxonomies = [];

		foreach ( $taxonomies as $taxonomy => $object ) {
			$media_taxonomies[$taxonomy] = $taxonomy . ' (' . $object->labels->menu_name . ')';
		}

		$tax = $this->options['folders']['media_taxonomy'];

		// selected hierarchical taxonomy does not exists?
		if ( ! in_array( $tax, $media_taxonomies, true ) ) {
			// check taxonomy existence
			if ( ( $taxonomy = get_taxonomy( $tax ) ) !== false ) {
				// update
				$media_taxonomies[$tax] = $tax . ' (' . $taxonomy->labels->menu_name . ')';
			// is it really old taxonomy?
			} elseif ( in_array( $tax, Responsive_Lightbox()->folders->get_taxonomies(), true ) ) {
				$this->register_media_taxonomy( $tax );

				$media_taxonomies[$tax] = $tax;
			// use default taxonomy
			} else {
				$media_taxonomies[$tax] = $tax;
				$this->options['folders']['media_taxonomy'] = $this->defaults['folders']['media_taxonomy'];

				update_option( 'responsive_lightbox_folders', $this->options['folders'] );
			}
		}

		$this->settings->settings['folders']['fields']['media_taxonomy']['options'] = $media_taxonomies;
	}

	/**
	 * Register media taxonomy.
	 *
	 * @return void
	 */
	public function register_media_taxonomy( $taxonomy ) {
		$show_in_menu = ( $this->options['folders']['show_in_menu'] && ( ( $taxonomy === 'rl_media_folder' && $this->options['folders']['media_taxonomy'] === 'rl_media_folder' ) || ( $taxonomy !== 'rl_media_folder' && $this->options['folders']['media_taxonomy'] !== 'rl_media_folder' ) ) );

		register_taxonomy(
			$taxonomy,
			'attachment',
			[
				'public'				=> true,
				'hierarchical'			=> true,
				'labels'				=> [
					'name'				=> _x( 'Media Folders', 'taxonomy general name', 'responsive-lightbox' ),
					'singular_name'		=> _x( 'Media Folder', 'taxonomy singular name', 'responsive-lightbox' ),
					'search_items'		=> __( 'Search Folders', 'responsive-lightbox' ),
					'all_items'			=> __( 'All Files', 'responsive-lightbox' ),
					'parent_item'		=> __( 'Parent Folder', 'responsive-lightbox' ),
					'parent_item_colon'	=> __( 'Parent Folder:', 'responsive-lightbox' ),
					'edit_item'			=> __( 'Edit Folder', 'responsive-lightbox' ),
					'update_item'		=> __( 'Update Folder', 'responsive-lightbox' ),
					'add_new_item'		=> __( 'Add New Folder', 'responsive-lightbox' ),
					'new_item_name'		=> __( 'New Folder Name', 'responsive-lightbox' ),
					'not_found'			=> __( 'No folders found.', 'responsive-lightbox' ),
					'menu_name'			=> _x( 'Folders', 'taxonomy general name', 'responsive-lightbox' ),
				],
				'show_ui'				=> ! ( $taxonomy === 'rl_media_folder' && $this->options['folders']['media_taxonomy'] !== 'rl_media_folder' ),
				'show_in_menu'			=> $show_in_menu,
				'show_in_nav_menus'		=> false,
				'show_in_quick_edit'	=> true,
				'show_tagcloud'			=> false,
				'show_admin_column'		=> $show_in_menu,
				'update_count_callback'	=> '_update_generic_term_count',
				'query_var'				=> false,
				'rewrite'				=> false
			]
		);
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $page
	 * @return void
	 */
	public function admin_scripts_styles( $page ) {
		global $typenow;

		// settings?
		if ( preg_match( '/^(toplevel|lightbox)_page_responsive-lightbox-(' . implode( '|', array_keys( Responsive_Lightbox()->settings->tabs ) ) . ')$/', $page ) === 1 ) {
			wp_enqueue_script( 'responsive-lightbox-admin', RESPONSIVE_LIGHTBOX_URL . '/js/admin.js', [ 'jquery', 'wp-color-picker' ], $this->defaults['version'] );

			wp_localize_script(
				'responsive-lightbox-admin',
				'rlArgsAdmin',
				[
					'resetSettingsToDefaults'	=> __( 'Are you sure you want to reset these settings to defaults?', 'responsive-lightbox' ),
					'tax_nonce'					=> wp_create_nonce( 'rl-folders-ajax-taxonomies-nonce' )
				]
			);

			wp_enqueue_style( 'wp-color-picker' );

			wp_enqueue_style( 'responsive-lightbox-admin', RESPONSIVE_LIGHTBOX_URL . '/css/admin.css', [], $this->defaults['version'] );
		// galleries?
		} elseif ( in_array( $page, [ 'post.php', 'post-new.php' ], true ) && get_post_type() === 'rl_gallery' || ( $page === 'edit.php' && $typenow === 'rl_gallery' ) ) {
			wp_enqueue_media();

			wp_enqueue_script( 'responsive-lightbox-admin-select2', RESPONSIVE_LIGHTBOX_URL . '/assets/select2/select2.full' . ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', [ 'jquery' ], $this->defaults['version'] );

			wp_enqueue_script( 'responsive-lightbox-admin-galleries', RESPONSIVE_LIGHTBOX_URL . '/js/admin-galleries.js', [ 'jquery', 'underscore', 'wp-color-picker', 'jquery-ui-sortable' ], $this->defaults['version'] );

			wp_localize_script(
				'responsive-lightbox-admin-galleries',
				'rlArgsGalleries',
				[
					'mediaItemTemplate'		=> $this->galleries->get_media_item_template( $this->galleries->fields['images']['media']['attachments']['preview'] ),
					'mediaEmbedTemplate'	=> $this->galleries->get_media_embed_template( true ),
					'mediaExcludeTemplate'	=> $this->galleries->get_media_exclude_input_template(),
					'textSelectImages'		=> __( 'Select gallery items', 'responsive-lightbox' ),
					'textUseImages'			=> __( 'Use these items', 'responsive-lightbox' ),
					'clearSelection'		=> __( 'Clear selected items', 'responsive-lightbox' ),
					'selectedImages'		=> __( 'Selected gallery items', 'responsive-lightbox' ),
					'editAttachment'		=> __( 'Edit attachment', 'responsive-lightbox' ),
					'editEmbed'				=> __( 'Edit embed video', 'responsive-lightbox' ),
					'videoDetails'			=> __( 'Video details', 'responsive-lightbox' ),
					'saveChanges'			=> __( 'Save changes', 'responsive-lightbox' ),
					'embedVideo'			=> __( 'Embed Video', 'responsive-lightbox' ),
					'onlyEmbedProviders'	=> __( 'Videos can be embedded only from the following providers: %s.', 'responsive-lightbox' ),
					'nonce'					=> wp_create_nonce( 'rl-gallery' ),
					'post_id'				=> get_the_ID(),
					'thumbnail'				=> wp_get_attachment_image_src( $this->galleries->maybe_generate_thumbnail(), 'thumbnail', false ),
					'supports'				=> [
						'default'	=> rl_current_lightbox_supports( 'video' ) ? [ 'image', 'video' ] : 'image',
						'youtube'	=> rl_current_lightbox_supports( 'youtube' ),
						'vimeo'		=> rl_current_lightbox_supports( 'vimeo' )
					],
					'videoIcon'				=> RESPONSIVE_LIGHTBOX_URL . '/images/responsive-lightbox-video-thumbnail.png'
				]
			);

			wp_enqueue_style( 'wp-color-picker' );

			wp_enqueue_style( 'responsive-lightbox-admin', RESPONSIVE_LIGHTBOX_URL . '/css/admin.css', [], $this->defaults['version'] );

			wp_enqueue_style( 'responsive-lightbox-admin-select2', RESPONSIVE_LIGHTBOX_URL . '/assets/select2/select2' . ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '' ) . '.css', [], $this->defaults['version'] );

			wp_enqueue_style( 'responsive-lightbox-admin-galleries', RESPONSIVE_LIGHTBOX_URL . '/css/admin-galleries.css', [], $this->defaults['version'] );
		// plugins?
		} elseif ( $page === 'plugins.php' ) {
			add_thickbox();

			wp_enqueue_script( 'responsive-lightbox-admin-plugins', RESPONSIVE_LIGHTBOX_URL . '/js/admin-plugins.js', [ 'jquery' ], $this->defaults['version'] );

			wp_enqueue_style( 'responsive-lightbox-admin-plugins', RESPONSIVE_LIGHTBOX_URL . '/css/admin-plugins.css', [], $this->defaults['version'] );

			wp_localize_script(
				'responsive-lightbox-admin-plugins',
				'rlArgsPlugins',
				[
					'deactivate'	=> __( 'Responsive Lightbox & Gallery - Deactivation survey', 'responsive-lightbox' ),
					'nonce'			=> wp_create_nonce( 'rl-deactivate-plugin' )
				]
			);
		// taxonomies?
		} elseif ( in_array( $page, [ 'edit-tags.php', 'term.php' ], true ) && isset( $_GET['taxonomy'], $_GET['post_type'] ) && $_GET['post_type'] === 'rl_gallery' )
			wp_enqueue_style( 'responsive-lightbox-admin', RESPONSIVE_LIGHTBOX_URL . '/css/admin.css', [], $this->defaults['version'] );
	}

	/**
	 * Init Gutenberg.
	 *
	 * @return void
	 */
	public function init_gutenberg() {
		global $wp_version;

		// actions
		add_action( 'enqueue_block_editor_assets', [ $this, 'gutenberg_enqueue_scripts' ] );

		// filters
		if ( version_compare( $wp_version, '5.8', '>=' ) )
			add_filter( 'block_categories_all', [ $this, 'block_category' ] );
		else
			add_filter( 'block_categories', [ $this, 'block_category' ] );
	}

	/**
	 * Create block category.
	 *
	 * @return array
	 */
	function block_category( $categories ) {
		return array_merge(
			$categories,
			[
				[
					'slug' => 'responsive-lightbox',
					'title' => 'Responsive Lightbox'
				]
			]
		);
	}

	/**
	 * Extend Gutenberg.
	 *
	 * @return void
	 */
	public function gutenberg_enqueue_scripts() {
		global $pagenow;

		// get main instance
		$rl = Responsive_Lightbox();

		// block editor dependencies
		$dependencies = [ 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-components' ];

		// widgets page?
		if ( $pagenow === 'widgets.php' )
			$dependencies[] = 'wp-edit-widgets';
		// customizer?
		elseif ( $pagenow === 'customize.php' )
			$dependencies[] = 'wp-customize-widgets';
		// post page?
		else
			$dependencies[] = 'wp-editor';

		// enqueue script
		wp_enqueue_script( 'responsive-lightbox-block-editor-script', RESPONSIVE_LIGHTBOX_URL . '/js/gutenberg.min.js', $dependencies, $rl->defaults['version'] );

		// enqueue styles
		wp_enqueue_style( 'responsive-lightbox-block-editor-styles', RESPONSIVE_LIGHTBOX_URL . '/css/gutenberg.min.css', '', $rl->defaults['version'] );

		wp_localize_script(
			'responsive-lightbox-block-editor-script',
			'rlBlockEditor',
			[
				'active' => true
			]
		);

		// enqueue gallery
		$rl->galleries->enqueue_gallery_scripts_styles();

		// register gallery block
		register_block_type(
			'responsive-lightbox/gallery',
			[
				'editor_script' => 'block-editor-script'
			]
		);

		// register remote library image block
		register_block_type(
			'responsive-lightbox/remote-library-image',
			[
				'editor_script' => 'block-editor-script'
			]
		);
	}

	/**
	 * Enqueue admin widget scripts.
	 *
	 * @return void
	 */
	public function sidebar_admin_setup() {
		wp_enqueue_media();

		wp_enqueue_script( 'responsive-lightbox-admin-widgets', RESPONSIVE_LIGHTBOX_URL . '/js/admin-widgets.js', [ 'jquery', 'underscore' ], $this->defaults['version'] );

		wp_localize_script(
			'responsive-lightbox-admin-widgets',
			'rlArgsWidgets',
			[
				'textRemoveImage'		=> __( 'Remove image', 'responsive-lightbox' ),
				'textSelectImages'		=> __( 'Select images', 'responsive-lightbox' ),
				'textSelectImage'		=> __( 'Select image', 'responsive-lightbox' ),
				'textUseImages'			=> __( 'Use these images', 'responsive-lightbox' ),
				'textUseImage'			=> __( 'Use this image', 'responsive-lightbox' )
			]
		);

		wp_register_style( 'responsive-lightbox-admin', RESPONSIVE_LIGHTBOX_URL . '/css/admin.css', [], $this->defaults['version'] );
		wp_enqueue_style( 'responsive-lightbox-admin' );
	}

	/**
	 * Enqueue frontend scripts and styles.
	 *
	 * @return void
	 */
	public function front_scripts_styles() {
		$args = apply_filters(
			'rl_lightbox_args',
			[
				'script'			=> $this->get_lightbox_script(),
				'selector'			=> $this->options['settings']['selector'],
				'customEvents'		=> ( $this->options['settings']['enable_custom_events'] === true ? $this->options['settings']['custom_events'] : '' ),
				'activeGalleries'	=> $this->get_boolean_value( $this->options['settings']['galleries'] )
			]
		);

		$scripts = [];
		$styles = [];

		switch ( $args['script'] ) {
			case 'prettyphoto':
				wp_register_script(
					'responsive-lightbox-prettyphoto', plugins_url( 'assets/prettyphoto/jquery.prettyPhoto' . ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', __FILE__ ), [ 'jquery' ], $this->defaults['version'], ($this->options['settings']['loading_place'] === 'header' ? false : true )
				);

				wp_register_style(
					'responsive-lightbox-prettyphoto', plugins_url( 'assets/prettyphoto/prettyPhoto' . ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '' ) . '.css', __FILE__ ), [], $this->defaults['version']
				);

				$scripts[] = 'responsive-lightbox-prettyphoto';
				$styles[] = 'responsive-lightbox-prettyphoto';

				$args = array_merge(
					$args,
					[
						'animationSpeed'	 => $this->options['configuration']['prettyphoto']['animation_speed'],
						'slideshow'			 => $this->get_boolean_value( $this->options['configuration']['prettyphoto']['slideshow'] ),
						'slideshowDelay'	 => $this->options['configuration']['prettyphoto']['slideshow_delay'],
						'slideshowAutoplay'	 => $this->get_boolean_value( $this->options['configuration']['prettyphoto']['slideshow_autoplay'] ),
						'opacity'			 => sprintf( '%.2f', ($this->options['configuration']['prettyphoto']['opacity'] / 100 ) ),
						'showTitle'			 => $this->get_boolean_value( $this->options['configuration']['prettyphoto']['show_title'] ),
						'allowResize'		 => $this->get_boolean_value( $this->options['configuration']['prettyphoto']['allow_resize'] ),
						'allowExpand'		 => $this->get_boolean_value( $this->options['configuration']['prettyphoto']['allow_expand'] ),
						'width'				 => $this->options['configuration']['prettyphoto']['width'],
						'height'			 => $this->options['configuration']['prettyphoto']['height'],
						'separator'			 => $this->options['configuration']['prettyphoto']['separator'],
						'theme'				 => $this->options['configuration']['prettyphoto']['theme'],
						'horizontalPadding'	 => $this->options['configuration']['prettyphoto']['horizontal_padding'],
						'hideFlash'			 => $this->get_boolean_value( $this->options['configuration']['prettyphoto']['hide_flash'] ),
						'wmode'				 => $this->options['configuration']['prettyphoto']['wmode'],
						'videoAutoplay'		 => $this->get_boolean_value( $this->options['configuration']['prettyphoto']['video_autoplay'] ),
						'modal'				 => $this->get_boolean_value( $this->options['configuration']['prettyphoto']['modal'] ),
						'deeplinking'		 => $this->get_boolean_value( $this->options['configuration']['prettyphoto']['deeplinking'] ),
						'overlayGallery'	 => $this->get_boolean_value( $this->options['configuration']['prettyphoto']['overlay_gallery'] ),
						'keyboardShortcuts'	 => $this->get_boolean_value( $this->options['configuration']['prettyphoto']['keyboard_shortcuts'] ),
						'social'			 => $this->get_boolean_value( $this->options['configuration']['prettyphoto']['social'] )
					]
				);
				break;

			case 'swipebox':
				wp_register_script(
					'responsive-lightbox-swipebox', plugins_url( 'assets/swipebox/jquery.swipebox' . ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', __FILE__ ), [ 'jquery' ], $this->defaults['version'], ($this->options['settings']['loading_place'] === 'header' ? false : true )
				);

				wp_register_style(
					'responsive-lightbox-swipebox', plugins_url( 'assets/swipebox/swipebox' . ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '' ) . '.css', __FILE__ ), [], $this->defaults['version']
				);

				$scripts[] = 'responsive-lightbox-swipebox';
				$styles[] = 'responsive-lightbox-swipebox';

				$args = array_merge(
					$args,
					[
						'animation'					=> $this->get_boolean_value( ($this->options['configuration']['swipebox']['animation'] === 'css' ? true : false ) ),
						'hideCloseButtonOnMobile'	=> $this->get_boolean_value( $this->options['configuration']['swipebox']['hide_close_mobile'] ),
						'removeBarsOnMobile'		=> $this->get_boolean_value( $this->options['configuration']['swipebox']['remove_bars_mobile'] ),
						'hideBars'					=> $this->get_boolean_value( $this->options['configuration']['swipebox']['hide_bars'] ),
						'hideBarsDelay'				=> $this->options['configuration']['swipebox']['hide_bars_delay'],
						'videoMaxWidth'				=> $this->options['configuration']['swipebox']['video_max_width'],
						'useSVG'					=> ! $this->options['configuration']['swipebox']['force_png_icons'],
						'loopAtEnd'					=> $this->get_boolean_value( $this->options['configuration']['swipebox']['loop_at_end'] )
					]
				);
				break;

			case 'fancybox':
				wp_register_script(
					'responsive-lightbox-fancybox', plugins_url( 'assets/fancybox/jquery.fancybox' . ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', __FILE__ ), [ 'jquery' ], $this->defaults['version'], ($this->options['settings']['loading_place'] === 'header' ? false : true )
				);

				wp_register_style(
					'responsive-lightbox-fancybox', plugins_url( 'assets/fancybox/jquery.fancybox' . ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '' ) . '.css', __FILE__ ), [], $this->defaults['version']
				);

				$scripts[] = 'responsive-lightbox-fancybox';
				$styles[] = 'responsive-lightbox-fancybox';

				$args = array_merge(
					$args,
					[
						'modal'					=> $this->get_boolean_value( $this->options['configuration']['fancybox']['modal'] ),
						'showOverlay'			=> $this->get_boolean_value( $this->options['configuration']['fancybox']['show_overlay'] ),
						'showCloseButton'		=> $this->get_boolean_value( $this->options['configuration']['fancybox']['show_close_button'] ),
						'enableEscapeButton'	=> $this->get_boolean_value( $this->options['configuration']['fancybox']['enable_escape_button'] ),
						'hideOnOverlayClick'	=> $this->get_boolean_value( $this->options['configuration']['fancybox']['hide_on_overlay_click'] ),
						'hideOnContentClick'	=> $this->get_boolean_value( $this->options['configuration']['fancybox']['hide_on_content_click'] ),
						'cyclic'				=> $this->get_boolean_value( $this->options['configuration']['fancybox']['cyclic'] ),
						'showNavArrows'			=> $this->get_boolean_value( $this->options['configuration']['fancybox']['show_nav_arrows'] ),
						'autoScale'				=> $this->get_boolean_value( $this->options['configuration']['fancybox']['auto_scale'] ),
						'scrolling'				=> $this->options['configuration']['fancybox']['scrolling'],
						'centerOnScroll'		=> $this->get_boolean_value( $this->options['configuration']['fancybox']['center_on_scroll'] ),
						'opacity'				=> $this->get_boolean_value( $this->options['configuration']['fancybox']['opacity'] ),
						'overlayOpacity'		=> $this->options['configuration']['fancybox']['overlay_opacity'],
						'overlayColor'			=> $this->options['configuration']['fancybox']['overlay_color'],
						'titleShow'				=> $this->get_boolean_value( $this->options['configuration']['fancybox']['title_show'] ),
						'titlePosition'			=> $this->options['configuration']['fancybox']['title_position'],
						'transitions'			=> $this->options['configuration']['fancybox']['transitions'],
						'easings'				=> $this->options['configuration']['fancybox']['easings'],
						'speeds'				=> $this->options['configuration']['fancybox']['speeds'],
						'changeSpeed'			=> $this->options['configuration']['fancybox']['change_speed'],
						'changeFade'			=> $this->options['configuration']['fancybox']['change_fade'],
						'padding'				=> $this->options['configuration']['fancybox']['padding'],
						'margin'				=> $this->options['configuration']['fancybox']['margin'],
						'videoWidth'			=> $this->options['configuration']['fancybox']['video_width'],
						'videoHeight'			=> $this->options['configuration']['fancybox']['video_height']
					]
				);
				break;

			case 'nivo':
				wp_register_script(
					'responsive-lightbox-nivo', plugins_url( 'assets/nivo/nivo-lightbox' . ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', __FILE__ ), [ 'jquery' ], $this->defaults['version'], ($this->options['settings']['loading_place'] === 'header' ? false : true ), $this->defaults['version']
				);

				wp_register_style(
					'responsive-lightbox-nivo', plugins_url( 'assets/nivo/nivo-lightbox' . ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '' ) . '.css', __FILE__ ), [], $this->defaults['version']
				);

				wp_register_style(
					'responsive-lightbox-nivo-default', plugins_url( 'assets/nivo/themes/default/default.css', __FILE__ ), [], $this->defaults['version']
				);

				$scripts[] = 'responsive-lightbox-nivo';
				$styles[] = 'responsive-lightbox-nivo';
				$styles[] = 'responsive-lightbox-nivo-default';

				$args = array_merge(
					$args,
					[
						'effect'				=> $this->options['configuration']['nivo']['effect'],
						'clickOverlayToClose'	=> $this->get_boolean_value( $this->options['configuration']['nivo']['click_overlay_to_close'] ),
						'keyboardNav'			=> $this->get_boolean_value( $this->options['configuration']['nivo']['keyboard_nav'] ),
						'errorMessage'			=> esc_attr( $this->options['configuration']['nivo']['error_message'] )
					]
				);
				break;

			case 'imagelightbox':
				wp_register_script(
					'responsive-lightbox-imagelightbox', plugins_url( 'assets/imagelightbox/imagelightbox' . ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', __FILE__ ), [ 'jquery' ], $this->defaults['version'], ($this->options['settings']['loading_place'] === 'header' ? false : true )
				);

				wp_register_style(
					'responsive-lightbox-imagelightbox', plugins_url( 'assets/imagelightbox/imagelightbox' . ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '' ) . '.css', __FILE__ ), [], $this->defaults['version']
				);

				$scripts[] = 'responsive-lightbox-imagelightbox';
				$styles[] = 'responsive-lightbox-imagelightbox';

				$args = array_merge(
					$args,
					[
						'animationSpeed'		=> $this->options['configuration']['imagelightbox']['animation_speed'],
						'preloadNext'			=> $this->get_boolean_value( $this->options['configuration']['imagelightbox']['preload_next'] ),
						'enableKeyboard'		=> $this->get_boolean_value( $this->options['configuration']['imagelightbox']['enable_keyboard'] ),
						'quitOnEnd'				=> $this->get_boolean_value( $this->options['configuration']['imagelightbox']['quit_on_end'] ),
						'quitOnImageClick'		=> $this->get_boolean_value( $this->options['configuration']['imagelightbox']['quit_on_image_click'] ),
						'quitOnDocumentClick'	=> $this->get_boolean_value( $this->options['configuration']['imagelightbox']['quit_on_document_click'] ),
					]
				);
				break;

			case 'tosrus':
				// swipe support, enqueue Hammer.js on mobile devices only
				if ( wp_is_mobile() ) {
					wp_register_script(
						'responsive-lightbox-hammer-js', plugins_url( 'assets/tosrus/hammer' . ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', __FILE__ ), [], $this->defaults['version'], ($this->options['settings']['loading_place'] === 'header' ? false : true )
					);
					$scripts[] = 'responsive-lightbox-hammer-js';
				}

				wp_register_script(
					'responsive-lightbox-tosrus', plugins_url( 'assets/tosrus/jquery.tosrus' . ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', __FILE__ ), [ 'jquery' ], $this->defaults['version'], ($this->options['settings']['loading_place'] === 'header' ? false : true )
				);

				wp_register_style(
					'responsive-lightbox-tosrus', plugins_url( 'assets/tosrus/jquery.tosrus' . ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '' ) . '.css', __FILE__ ), [], $this->defaults['version']
				);

				$scripts[] = 'responsive-lightbox-tosrus';
				$styles[] = 'responsive-lightbox-tosrus';

				$args = array_merge(
					$args,
					[
						'effect'			=> $this->options['configuration']['tosrus']['effect'],
						'infinite'			=> $this->get_boolean_value( $this->options['configuration']['tosrus']['infinite'] ),
						'keys'				=> $this->get_boolean_value( $this->options['configuration']['tosrus']['keys'] ),
						'autoplay'			=> $this->get_boolean_value( $this->options['configuration']['tosrus']['autoplay'] ),
						'pauseOnHover'		=> $this->get_boolean_value( $this->options['configuration']['tosrus']['pause_on_hover'] ),
						'timeout'			=> $this->options['configuration']['tosrus']['timeout'],
						'pagination'		=> $this->get_boolean_value( $this->options['configuration']['tosrus']['pagination'] ),
						'paginationType'	=> $this->options['configuration']['tosrus']['pagination_type'],
						'closeOnClick'		=> $this->get_boolean_value( $this->options['configuration']['tosrus']['close_on_click'] )
					]
				);
				break;

			case 'featherlight':
				wp_register_script(
					'responsive-lightbox-featherlight', plugins_url( 'assets/featherlight/featherlight' . ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', __FILE__ ), [ 'jquery' ], $this->defaults['version'], ($this->options['settings']['loading_place'] === 'header' ? false : true )
				);

				wp_register_style(
					'responsive-lightbox-featherlight', plugins_url( 'assets/featherlight/featherlight' . ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '' ) . '.css', __FILE__ ), [], $this->defaults['version']
				);

				wp_register_script(
					'responsive-lightbox-featherlight-gallery', plugins_url( 'assets/featherlight/featherlight.gallery' . ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', __FILE__ ), [ 'jquery' ], $this->defaults['version'], ($this->options['settings']['loading_place'] === 'header' ? false : true )
				);

				wp_register_style(
					'responsive-lightbox-featherlight-gallery', plugins_url( 'assets/featherlight/featherlight.gallery' . ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '' ) . '.css', __FILE__ ), [], $this->defaults['version']
				);

				$scripts[] = 'responsive-lightbox-featherlight';
				$styles[] = 'responsive-lightbox-featherlight';
				$scripts[] = 'responsive-lightbox-featherlight-gallery';
				$styles[] = 'responsive-lightbox-featherlight-gallery';

				$args = array_merge(
					$args,
					[
						'openSpeed'			=> $this->options['configuration']['featherlight']['open_speed'],
						'closeSpeed'		=> $this->options['configuration']['featherlight']['close_speed'],
						'closeOnClick'		=> $this->options['configuration']['featherlight']['close_on_click'],
						'closeOnEsc'		=> $this->get_boolean_value( $this->options['configuration']['featherlight']['close_on_esc'] ),
						'galleryFadeIn'		=> $this->options['configuration']['featherlight']['gallery_fade_in'],
						'galleryFadeOut'	=> $this->options['configuration']['featherlight']['gallery_fade_out']
					]
				);
				break;

			case 'magnific':
				wp_register_script( 'responsive-lightbox-magnific', plugins_url( 'assets/magnific/jquery.magnific-popup' . ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', __FILE__ ), [ 'jquery' ], $this->defaults['version'], ( $this->options['settings']['loading_place'] === 'header' ? false : true ) );

				wp_register_style( 'responsive-lightbox-magnific', plugins_url( 'assets/magnific/magnific-popup' . ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '' ) . '.css', __FILE__ ), [], $this->defaults['version'] );

				$scripts[] = 'responsive-lightbox-magnific';
				$styles[] = 'responsive-lightbox-magnific';

				$args = array_merge(
					$args,
					[
						'disableOn'				=> $this->options['configuration']['magnific']['disable_on'],
						'midClick'				=> $this->options['configuration']['magnific']['mid_click'],
						'preloader'				=> $this->options['configuration']['magnific']['preloader'],
						'closeOnContentClick'	=> $this->options['configuration']['magnific']['close_on_content_click'],
						'closeOnBgClick'		=> $this->options['configuration']['magnific']['close_on_background_click'],
						'closeBtnInside'		=> $this->options['configuration']['magnific']['close_button_inside'],
						'showCloseBtn'			=> $this->options['configuration']['magnific']['show_close_button'],
						'enableEscapeKey'		=> $this->options['configuration']['magnific']['enable_escape_key'],
						'alignTop'				=> $this->options['configuration']['magnific']['align_top'],
						'fixedContentPos'		=> $this->options['configuration']['magnific']['fixed_content_position'],
						'fixedBgPos'			=> $this->options['configuration']['magnific']['fixed_background_position'],
						'autoFocusLast'			=> $this->options['configuration']['magnific']['auto_focus_last']
					]
				);
				break;

			default:
				do_action( 'rl_lightbox_enqueue_scripts' );

				$scripts = apply_filters( 'rl_lightbox_scripts', $scripts );
				$styles = apply_filters( 'rl_lightbox_styles', $styles );
		}

		// run scripts by default
		$contitional_scripts = true;

		if ( $this->options['settings']['conditional_loading'] === true ) {
			global $post;

			if ( is_object( $post ) ) {
				// is gallery present in content
				$has_gallery = has_shortcode( $post->post_content, 'gallery' ) || has_shortcode( $post->post_content, 'rl_gallery' );

				// are images present in content
				preg_match_all( '/<a(.*?)href=(?:\'|")([^<]*?).(bmp|gif|jpeg|jpg|png|webp)(?:\'|")(.*?)>/i', $post->post_content, $links );

				$has_images = (bool) $links[0];

				if ( $has_gallery === false && $has_images === false )
					$contitional_scripts = false;
			}
		}

		if ( ! empty( $args['script'] ) && ! empty( $args['selector'] ) && apply_filters( 'rl_lightbox_conditional_loading', $contitional_scripts ) != false ) {
			wp_register_script( 'responsive-lightbox-infinite-scroll', RESPONSIVE_LIGHTBOX_URL . '/assets/infinitescroll/infinite-scroll.pkgd' . ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', [ 'jquery' ] );
			wp_register_script( 'responsive-lightbox-images-loaded', RESPONSIVE_LIGHTBOX_URL . '/assets/imagesloaded/imagesloaded.pkgd' . ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', [ 'jquery' ] );
			wp_register_script( 'responsive-lightbox-masonry', RESPONSIVE_LIGHTBOX_URL . '/assets/masonry/masonry.pkgd' . ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', [ 'jquery' ], Responsive_Lightbox()->defaults['version'], ( Responsive_Lightbox()->options['settings']['loading_place'] === 'footer' ) );

			wp_register_script( 'responsive-lightbox', plugins_url( 'js/front.js', __FILE__ ), [ 'jquery', 'underscore', 'responsive-lightbox-infinite-scroll' ], $this->defaults['version'], ( $this->options['settings']['loading_place'] === 'header' ? false : true ) );

			$args['woocommerce_gallery'] = 0;

			if ( class_exists( 'WooCommerce' ) ) {
				global $woocommerce;

				if ( Responsive_Lightbox()->options['settings']['woocommerce_gallery_lightbox'] === true ) {
					if ( version_compare( $woocommerce->version, '3.0', ">=" ) )
						$args['woocommerce_gallery'] = 1;
				}
			}

			$scripts[] = 'responsive-lightbox';

			$args['ajaxurl'] = admin_url( 'admin-ajax.php' );
			$args['nonce'] = wp_create_nonce( 'rl_nonce' );
			$args['preview'] = ( isset( $_GET['rl_gallery_revision_id'], $_GET['preview'] ) && $_GET['preview'] === 'true' ) ? 'true' : 'false';
			$args['postId'] = (int) get_the_ID();
			$args['scriptExtension'] = array_key_exists( $args['script'], apply_filters( 'rl_settings_licenses', [] ) );

			// enqueue scripts
			if ( $scripts && is_array( $scripts ) ) {
				foreach ( $scripts as $script ) {
					wp_enqueue_script( $script );
				}

				wp_localize_script(	'responsive-lightbox', 'rlArgs', $args );
			}

			// enqueue styles
			if ( $styles && is_array( $styles ) ) {
				foreach ( $styles as $style ) {
					wp_enqueue_style( $style );
				}
			}
		}

		// gallery style
		wp_register_style( 'responsive-lightbox-gallery',  plugins_url( 'css/gallery.css', __FILE__ ), [], Responsive_Lightbox()->defaults['version'] );
	}

	/**
	 * Helper: convert value to boolean
	 *
	 * @param int $option
	 * @return bool
	 */
	private function get_boolean_value( $option ) {
		return ( $option == true ? 1 : 0 );
	}

	/**
	 * Helper: convert hex color to rgb color.
	 *
	 * @param type $color
	 * @return array
	 */
	public function hex2rgb( $color ) {
		if ( $color[0] == '#' )
			$color = substr( $color, 1 );

		if ( strlen( $color ) == 6 )
			list( $r, $g, $b ) = [ $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] ];
		elseif ( strlen( $color ) == 3 )
			list( $r, $g, $b ) = [ $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] ];
		else
			return false;

		$r = hexdec( $r );
		$g = hexdec( $g );
		$b = hexdec( $b );

		return [ $r, $g, $b ];
	}
}

/**
 * Initialize Responsive Lightbox.
 */
function Responsive_Lightbox() {
	static $instance;

	// first call to instance() initializes the plugin
	if ( $instance === null || ! ( $instance instanceof Responsive_Lightbox ) )
		$instance = Responsive_Lightbox::instance();

	return $instance;
}

$responsive_lightbox = Responsive_Lightbox();