<?php

namespace Vendidero\Germanized\Pro\StoreaBill;

use Vendidero\Germanized\Pro\StoreaBill\PostDocument\Query;
use Vendidero\StoreaBill\Document\Document;
use Vendidero\StoreaBill\Document\FirstPageTemplate;
use Vendidero\StoreaBill\Editor\Blocks\DynamicContent;

defined( 'ABSPATH' ) || exit;

class LegalPages {

	public static function init() {
		add_action( 'storeabill_registered_core_document_types', array( __CLASS__, 'register_document_type' ), 10 );
		add_filter( 'storeabill_data_stores', array( __CLASS__, 'register_data_store' ), 10 );
		add_filter( 'woocommerce_email_attachments', array( __CLASS__, 'attach_legal_pdf' ), 100, 2 );
		add_filter( 'woocommerce_gzd_attach_email_footer', array( __CLASS__, 'maybe_remove_plain_attachment' ), 10, 3 );

		add_action( 'woocommerce_gzd_admin_settings_before_emails_attachments', array( __CLASS__, 'template_button' ) );
		add_filter( 'storeabill_post_document_editor_templates', array( __CLASS__, 'register_template' ) );

		/**
		 * Dynamic Blocks
		 */
		add_filter( 'storeabill_document_template_editor_dynamic_content_blocks', array( __CLASS__, 'register_dynamic_blocks' ), 10 );
		add_filter( 'storeabill_default_template_path', array( __CLASS__, 'register_default_template_path' ), 10, 2 );

		add_filter( 'storeabill_document_template_editor_available_blocks', array( __CLASS__, 'remove_blocks' ), 10, 3 );
		add_filter( 'storeabill_register_document_type_parsed_args', array( __CLASS__, 'force_shortcodes' ), 10, 2 );

		/**
		 * Listen to legal page changes and refresh PDF if necessary.
		 */
		add_action( 'save_post', array( __CLASS__, 'on_save_page' ), 10, 3 );

		/**
		 * Listen to template updates to refresh PDFs after update.
		 */
		add_action( 'save_post', array( __CLASS__, 'on_save_template' ), 10, 3 );

		/**
		 * Custom first pages
		 */
		add_action( 'admin_post_wc_gzdp_add_legal_first_page', array( __CLASS__, 'add_custom_first_page' ), 10 );
		add_action( 'admin_post_wc_gzdp_remove_legal_first_page', array( __CLASS__, 'remove_custom_first_page' ), 10 );

		add_filter( 'storeabill_admin_post_document_templates_archive_url', array( __CLASS__, 'adjust_admin_archive_url' ), 10 );

		/**
		 * Make sure to allow shortcodes like [gzd_complaints] and others within PDF legal pages.
		 */
		add_action( 'storeabill_before_render_document', array( __CLASS__, 'force_allow_shortcodes' ), 0, 2 );

		add_action( 'init', array( __CLASS__, 'maybe_register_visitor_download' ), 30 );
	}

	public static function maybe_register_visitor_download() {
		if ( apply_filters( 'woocommerce_gzdp_allow_visitor_legal_page_download', false ) ) {
			add_shortcode( "gzdp_download_legal_page", array( __CLASS__, 'download_shortcode' ) );
			add_filter( 'user_has_cap', array( __CLASS__, 'visitor_has_capability' ), 10, 3 );
		}
	}

	public static function download_shortcode( $atts = array(), $content = '' ) {
		$atts = wp_parse_args( $atts, array(
			'force'   => 'no',
			'type'    => 'terms',
			'classes' => 'link',
			'target'  => '_blank'
		) );

		$id = self::get_legal_page_id_by_type( $atts['type'] );

		if ( $id && ( $legal_page = self::get_legal_page( $id ) ) ) {
			$content       = empty( $content ) ? sprintf( _x( "Download %s", 'legal-page', 'woocommerce-germanized-pro' ), $legal_page->get_title( false ) ) : $content;
			$download_link = $legal_page->get_download_url( wc_string_to_bool( $atts['force'] ) );

			return '<a target="' . esc_attr( $atts['target'] ) . '" class="' . esc_attr( $atts['classes'] ) . '" href="' . esc_url( $download_link ) . '">' . $content . '</a>';
		}

		return '';
	}

	/**
	 * Checks if a user has a certain capability.
	 *
	 * @param array $allcaps All capabilities.
	 * @param array $caps    Capabilities.
	 * @param array $args    Arguments.
	 *
	 * @return array The filtered array of all capabilities.
	 */
	public static function visitor_has_capability( $allcaps, $caps, $args ) {
		if ( isset( $caps[0] ) ) {
			if ( 'view_post_document' === $caps[0] ) {
				$document = sab_get_document( $args[2], 'post_document' );

				if ( $document && ! $document->is_editable() ) {
					$allcaps["view_post_document"] = true;
				}
			}
		}

		return $allcaps;
	}

	/**
	 * @param Document $document
	 * @param $is_preview
	 */
	public static function force_allow_shortcodes( $document, $is_preview ) {
		if ( 'post_document' === $document->get_type() ) {
			remove_action( 'storeabill_before_render_document', '_sab_filter_render_shortcodes_start', 5 );
		}
	}

	public static function adjust_admin_archive_url( $url ) {
		return admin_url( 'admin.php?page=wc-settings&tab=germanized-emails&section=attachments' );
	}

	public static function remove_custom_first_page() {
		if ( ! current_user_can( 'manage_storeabill' ) ) {
			wp_die();
		}

		if ( ! wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '', 'wc-gzdp-remove-legal-first-page' ) ) {
			wp_die();
		}

		$template = sab_get_default_document_template( 'post_document' );

		if ( $first_page = $template->get_first_page() ) {
			$first_page->delete( true );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=germanized-emails&section=attachments' ) );
		exit();
	}

	public static function add_custom_first_page() {
		if ( ! current_user_can( 'manage_storeabill' ) ) {
			wp_die();
		}

		if ( ! wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '', 'wc-gzdp-add-legal-first-page' ) ) {
			wp_die();
		}

		$template = sab_get_default_document_template( 'post_document' );

		if ( ! $template->has_custom_first_page() ) {
			$tpl = new FirstPageTemplate();
			$tpl->set_parent_id( $template->get_id() );
			$tpl->save();

			wp_safe_redirect( $tpl->get_edit_url() );
			exit();
		} else {
			$tpl = $template->get_first_page();

			wp_safe_redirect( $tpl->get_edit_url() );
			exit();
		}
	}

	public static function on_save_page( $post_id, $post, $update ) {
		$pages = self::get_legal_page_ids();

		if ( ! $post || wp_is_post_revision( $post_id ) || 'page' !== $post->post_type || ! in_array( $post_id, $pages ) ) {
			return;
		}

		self::refresh_legal_page( $post_id, true );
	}

	public static function on_save_template( $post_id, $post, $update ) {
		if ( $update && 'document_template' === $post->post_type ) {
			if ( $template = sab_get_document_template( $post_id ) ) {
				if ( 'post_document' === $template->get_document_type() ) {
					self::regenerate_legal_pages( true );
				}
			}
		}
	}

	public static function force_shortcodes( $args, $document_type ) {
		if ( 'post_document' === $document_type ) {
			$args['shortcodes'] = array(
				'document' => array(
					array(
						'shortcode'        => 'document?data=current_page_no',
						'title'            => __( 'Current page number', 'woocommerce-germanized-pro' ),
						'headerFooterOnly' => true,
					),
					array(
						'shortcode'        => 'document?data=total_pages',
						'title'            => __( 'Total pages', 'woocommerce-germanized-pro' ),
						'headerFooterOnly' => true,
					)
				),
			);
		}

		return $args;
	}

	public static function remove_blocks( $block_types, $document_type, $template ) {
		if ( 'post_document' === $document_type ) {
			$block_types = array_values( array_diff( $block_types, array(
				'storeabill/address',
				'storeabill/document-title',
			) ) );
		}

		return $block_types;
	}

	public static function register_template( $templates ) {
		$templates['default'] = '\Vendidero\Germanized\Pro\StoreaBill\PostDocument\DefaultTemplate';

		return $templates;
	}

	public static function register_data_store( $stores ) {
		return array_merge( $stores, array(
			'post_document' => '\Vendidero\Germanized\Pro\StoreaBill\DataStores\PostDocument'
		) );
	}

	public static function register_document_type() {
		sab_register_document_type( 'post_document', array(
			'group'                     => 'posts',
			'api_endpoint'              => 'posts',
			'labels'                    => array(
				'singular' => __( 'Post', 'woocommerce-germanized-pro' ),
				'plural'   => __( 'Posts', 'woocommerce-germanized-pro' ),
			),
			'class_name'                => '\Vendidero\Germanized\Pro\StoreaBill\PostDocument',
			'preview_class_name'        => '\Vendidero\Germanized\Pro\StoreaBill\PostDocument\Preview',
			'default_line_item_types'   => array(),
			'available_line_item_types' => array(),
			'supports'                  => array(),
			'default_status'            => 'closed',
			'additional_blocks'         => array(
				'storeabill/post-content',
				'storeabill/post-title'
			)
		) );
	}

	public static function register_default_template_path( $default_path, $template_name ) {
		/**
		 * Add default packing slip templates from plugin template path.
		 */
		if ( strpos( $template_name, 'post-document/' ) !== false ) {
			$default_path = trailingslashit( WC_germanized_pro()->plugin_path() ) . 'templates/';
		}

		return $default_path;
	}

	/**
	 * @param DynamicContent $block
	 */
	public static function render_post_content_block( $block ) {
		/**
		 * @var PostDocument $document
		 */
		global $document;

		return $document->get_content();
	}

	/**
	 * @param DynamicContent $block
	 */
	public static function render_post_title_block( $block ) {
		/**
		 * @var PostDocument $document
		 */
		global $document;

		return '<h1>' . $document->get_title() . '</h1>';
	}

	public static function register_dynamic_blocks( $blocks ) {
		$blocks['post-content'] = array(
			'title'           => __( 'Post Content', 'woocommerce-germanized-pro' ),
			'render_callback' => array( __CLASS__, 'render_post_content_block' )
		);

		$blocks['post-title'] = array(
			'title'           => __( 'Post Title', 'woocommerce-germanized-pro' ),
			'render_callback' => array( __CLASS__, 'render_post_title_block' )
		);

		return $blocks;
	}

	public static function template_button() {
		include WC_GERMANIZED_PRO_ABSPATH . '/includes/admin/views/html-pdf-settings-before.php';
	}

	public static function get_settings() {
		$pages       = self::get_legal_page_ids( false );
		$legal_pages = wc_gzd_get_legal_pages();

		$settings = array(
			array( 'title' => '', 'type' => 'title', 'id' => 'email_attachment_options' ),
		);

		foreach ( $pages as $legal_page_type => $post_id ) {

			if ( ! get_post( $post_id ) ) {
				continue;
			}

			$title    = array_key_exists( $legal_page_type, $legal_pages ) ? $legal_pages[ $legal_page_type ] : '';
			$url      = self::get_legal_page_pdf_url( $legal_page_type );
			$settings = array_merge( $settings, array(

				array(
					'title' 	=> $title,
					'desc' 		=> $url ? sprintf( __( 'Send <a href="%1$s" target="_blank">%2$s</a> as PDF attachment instead of plain text', 'woocommerce-germanized-pro' ), $url, $title ) : sprintf( __( 'Send %s as PDF attachment instead of plain text', 'woocommerce-germanized-pro' ), $title ),
					'id' 		=> 'woocommerce_gzdp_legal_page_' . $legal_page_type . '_enabled',
					'default'	=> 'no',
					'type' 		=> 'gzd_toggle',
				),

				array(
					'title' 	=> _x( 'Attachment', 'legal-page', 'woocommerce-germanized-pro' ),
					'desc' 		=> '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'You might want to manually upload a pre-created PDF file as attachment for your %s. Leave empty to use the automatically generated document.', 'legal-page', 'woocommerce-germanized-pro' ), $title ) . '</div>',
					'id' 		=> 'woocommerce_gzdp_legal_page_' . $legal_page_type . '_pdf',
					'default'	=> '',
					'custom_attributes' => array(
						'data-show_if_woocommerce_gzdp_legal_page_' . $legal_page_type . '_enabled' => '',
					),
					'data-type' => 'application/pdf',
					'type' 		=> 'gzdp_attachment',
				)
			) );
		}

		$settings = array_merge( $settings, array( array( 'type' => 'sectionend', 'id' => 'email_attachment_options' ) ) );

		return $settings;
	}

	protected static function regenerate_legal_pages( $defer = false ) {
		foreach( self::get_legal_page_ids( false ) as $legal_page_type => $page_id ) {

			if ( ! get_post( $page_id ) ) {
				continue;
			}

			if ( ! self::has_legal_page_manual_attachment( $legal_page_type ) ) {
				if ( 'yes' === get_option( "woocommerce_gzdp_legal_page_{$legal_page_type}_enabled" ) ) {
					self::refresh_legal_page( $legal_page_type, $defer );
				}
			}
		}
	}

	public static function on_save_settings() {
		self::regenerate_legal_pages( true );
	}

	public static function get_legal_page_ids( $ignore_empty = true ) {
		try {
			$func = new \ReflectionFunction( 'wc_gzd_get_email_attachment_order' );
			$num  = $func->getNumberOfParameters();
		} catch( \Exception $e ) {
			$num = 0;
		}

		$pages = $num >= 1 ? wc_gzd_get_email_attachment_order( true ) : wc_gzd_get_email_attachment_order();
		$ids   = array();

		foreach ( $pages as $page => $title ) {
			$id = wc_get_page_id( $page );

			if ( $id == -1 && $ignore_empty ) {
				continue;
			}

			$ids[ $page ] = wc_get_page_id( $page );
		}

		return $ids;
	}

	public static function get_legal_page_pdf_url( $type ) {
		$url = '';
		$id  = self::get_legal_page_id_by_type( $type );

		if ( self::has_legal_page_manual_attachment( $type ) ) {
			$url = self::get_legal_page_manual_attachment_url( $type );
		} elseif ( ! empty( $id ) ) {
			if ( ( $page = self::get_legal_page( $id ) ) && $page->has_file() ) {
				$url = $page->get_download_url();
			}
		}

		return $url;
 	}

	public static function refresh_legal_page( $id, $defer = false ) {
		if ( ! is_numeric( $id ) ) {
			$id = self::get_legal_page_id_by_type( $id );
		}

		$current_page = self::get_legal_page( $id );
		$result       = false;

		if ( ! $current_page ) {
			$current_page = self::create_legal_page( $id );
		} else {
			$new_filename = sanitize_file_name( sanitize_title( $current_page->get_title() ) . '.pdf' );

			/**
			 * Check whether the filename should change and remove relative path.
			 */
			if ( $new_filename !== $current_page->get_filename() ) {
				if ( $current_page->has_file() ) {
					wp_delete_file( $current_page->get_path() );
				}

				$current_page->set_relative_path( '' );
				$current_page->save();
			}
		}

		if ( $current_page ) {
			if ( $defer ) {
				$result = $current_page->render_deferred();
			} else {
				$result = $current_page->render();
			}
		}

		return $result;
	}

	public static function create_legal_page( $id ) {
		if ( ! is_numeric( $id ) ) {
			$id = self::get_legal_page_id_by_type( $id );
		}

		if ( ! get_post( $id ) ) {
			return false;
		}

		$new_page = new PostDocument();
		$new_page->set_post_id( $id );
		$new_page->save();

		if ( $new_page->get_id() >= 0 ) {
			return $new_page;
		}

		return false;
	}

	public static function get_legal_page_types( $id ) {
		$legal_page_types = array();

		if ( ! is_numeric( $id ) ) {
			return $id;
		}

		foreach( self::get_legal_page_ids() as $legal_type => $legal_page_id ) {
			if ( $id == $legal_page_id ) {
				$legal_page_types[] = $legal_type;
			}
		}

		return $legal_page_types;
	}

	public static function get_legal_page_id_by_type( $type ) {
		$id = 0;

		foreach( self::get_legal_page_ids() as $legal_type => $legal_page_id ) {
			if ( $legal_type == $type ) {
				$id = $legal_page_id;
				break;
			}
		}

		return $id;
	}

	public static function attach_legal_pdf( $attachments, $mail_id ) {
		foreach ( self::get_legal_page_ids() as $page => $id ) {
			$templates = array();

			if ( get_option( "woocommerce_gzd_mail_attach_{$page}" ) ) {
				$templates = get_option( "woocommerce_gzd_mail_attach_{$page}" );
			}

			if ( 'yes' === get_option( "woocommerce_gzdp_legal_page_{$page}_enabled" ) && in_array( $mail_id, $templates ) ) {

				if ( self::has_legal_page_manual_attachment( $page ) ) {
					$attachments[] = self::get_legal_page_manual_attachment_path( $page );
				} else {
					$current_page = self::get_legal_page( $id );

					if ( ! $current_page ) {
						$current_page = self::create_legal_page( $id );
					}

					if ( $current_page && $current_page->has_file() ) {
						$attachments[] = $current_page->get_path();
					}
				}
			}
		}

		return $attachments;
	}

	public static function has_legal_page_manual_attachment( $type ) {
		return ( false !== self::get_legal_page_manual_attachment_path( $type ) );
	}

	public static function get_legal_page_manual_attachment_path( $type ) {
		$attachment_id = get_option( "woocommerce_gzdp_legal_page_{$type}_pdf" );

		if ( $attachment_id && ! empty( $attachment_id ) ) {
			$path = get_attached_file( $attachment_id );

			if ( $path && file_exists( $path ) ) {
				return $path;
			}
		}

		return false;
	}

	public static function get_legal_page_manual_attachment_url( $type ) {
		$attachment_id = get_option( "woocommerce_gzdp_legal_page_{$type}_pdf" );
		$url           = '';

		if ( $attachment_id && ! empty( $attachment_id ) ) {
			$url = wp_get_attachment_url( $attachment_id );
		}

		return $url;
	}

	public static function has_legal_page_attachment( $id ) {
		$legal_page_types = array( $id );

		/**
		 * One legal page id (e.g. terms page) might be linked to
		 * many different legal page types.
		 */
		if ( is_numeric( $id ) ) {
			$legal_page_types = self::get_legal_page_types( $id );
		}

		foreach( $legal_page_types as $legal_page_type ) {
			$id           = self::get_legal_page_id_by_type( $legal_page_type );
			$current_page = self::get_legal_page( $id );

			if ( 'yes' === get_option( "woocommerce_gzdp_legal_page_{$legal_page_type}_enabled" ) ) {
				if ( self::has_legal_page_manual_attachment( $legal_page_type ) ) {
					return true;
				} elseif ( $current_page && $current_page->has_file() ) {
					return true;
				}
			}
		}

		return false;
	}

	public static function maybe_remove_plain_attachment( $attach, $mail, $page_id ) {
		if ( self::has_legal_page_attachment( $page_id ) ) {
			return false;
		}

		return $attach;
	}

	/**
	 * @param $post_id
	 *
	 * @return boolean|PostDocument
	 */
	public static function get_legal_page( $post_id ) {
		$query = new Query( array(
			'reference_id' => $post_id
		) );

		$pages = $query->get_posts();
		$page  = false;

		if ( ! empty( $pages ) ) {
			$page = $pages[0];
		}

		return apply_filters( 'woocommerce_gzdp_legal_page', $page, $post_id );
	}
}