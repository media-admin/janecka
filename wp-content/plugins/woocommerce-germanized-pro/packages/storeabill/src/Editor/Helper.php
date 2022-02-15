<?php

namespace Vendidero\StoreaBill\Editor;

use Exception;
use Vendidero\StoreaBill\Admin\Settings;
use Vendidero\StoreaBill\Countries;
use Vendidero\StoreaBill\Document\FirstPageTemplate;
use Vendidero\StoreaBill\Document\Journal;
use Vendidero\StoreaBill\Document\Template;
use Vendidero\StoreaBill\Editor\Blocks\Block;
use Vendidero\StoreaBill\Fonts\Embed;
use Vendidero\StoreaBill\Fonts\Fonts;
use Vendidero\StoreaBill\Package;

use WP_Error;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

class Helper {

	protected static $blocks = null;

	protected static $asset_data = array();

	protected static $font_embed = null;

	protected static $has_registered_assets = false;

	public static function init() {
		global $wp_version;

		add_filter( 'template_include', array( __CLASS__, 'template_loader' ) );
		add_filter( 'replace_editor', array( __CLASS__, 'conditionally_load_template' ), 10, 2 );

		add_action( 'storeabill_load_block_editor', array( __CLASS__, 'register_assets' ), 10 );
		add_action( 'storeabill_load_block_editor', array( __CLASS__, 'get_blocks' ), 11 );
		add_action( 'rest_api_init', array( __CLASS__, 'get_blocks' ), 10 );

		add_action( 'init', array( __CLASS__, 'register_meta' ) );

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'add_style_data' ), 30 );
		add_action( 'wp_print_footer_scripts', array( __CLASS__, 'enqueue_asset_data' ), 1 );
		add_action( 'admin_print_footer_scripts', array( __CLASS__, 'enqueue_asset_data' ), 1 );

		/**
		 * Lets remove third party specific editor styles for our templates.
		 */
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'prevent_third_party_assets' ), 999 );
		add_action( 'enqueue_block_assets', array( __CLASS__, 'prevent_third_party_assets' ), 999 );

		add_filter( 'use_block_editor_for_post', array( __CLASS__, 'remove_theme_editor_styles' ), 999, 2 );
		add_filter( 'block_editor_no_javascript_message', array( __CLASS__, 'reset_theme_file_path_filter' ), 999 );

		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_editor_assets' ) );
		add_filter( 'block_editor_settings_all', array( __CLASS__, 'prevent_theme_settings' ), 999, 2 );

		add_action( 'admin_footer', array( __CLASS__, 'add_footer_styles' ), 150 );

		/**
		 * Creating new posts support
		 */
		add_action( 'wp_insert_post', array( __CLASS__, 'on_insert_new' ), 20, 3 );

		add_action( 'storeabill_refresh_template_shortcodes', array( __CLASS__, 'refresh_template_shortcodes' ) );
		add_action( 'storeabill_before_journal_object_save', array( __CLASS__, 'maybe_refresh_template_shortcodes' ) );

		add_action( 'admin_init', array( __CLASS__, 'maybe_merge_template' ), 10 );
		add_action( 'admin_init', array( __CLASS__, 'force_redirect_on_archive' ), 10 );

		if ( version_compare( $wp_version, '5.8.0', '>=' ) ) {
			add_filter( 'block_categories_all', array( __CLASS__, 'register_category_all' ), 10, 2 );
			add_filter( 'allowed_block_types_all', array( __CLASS__, 'allowed_block_types_all' ), 10, 2 );
		} else {
			add_filter( 'block_categories', array( __CLASS__, 'register_category' ), 10, 2 );
			add_filter( 'allowed_block_types', array( __CLASS__, 'allowed_block_types' ), 10, 2 );
		}
	}

	public static function register_category_all( $categories, $block_context ) {
		if ( is_a( $block_context, 'WP_Block_Editor_Context' ) && $block_context->post ) {
			return self::register_category( $categories, $block_context->post );
		}

		return $categories;
	}

	public static function register_category( $categories, $post ) {
		if ( self::is_document_template( $post ) ) {
			$categories = array_merge(
				$categories,
				array(
					array(
						'slug' => 'storeabill',
						'title' => _x( 'Storeabill', 'storeabill-core', 'woocommerce-germanized-pro' ),
					),
				)
			);
		}

		return $categories;
	}

	/**
	 * Do not allow admins to directly edit/add document templates via the
	 * custom post type archive edit page. Redirect to settings instead.
	 */
	public static function force_redirect_on_archive() {
		global $pagenow;

		if ( 'edit.php' === $pagenow && isset( $_GET['post_type'] ) && 'document_template' === $_GET['post_type'] ) {
			$referer = wp_get_referer();
			$url     = Settings::get_admin_url();

			if ( ! empty( $referer ) ) {
				$parts = parse_url( $referer );
				parse_str( $parts['query'], $args );

				if ( isset( $args['post'] ) ) {
					$post_id = absint( $args['post'] );

					if ( $document_template = sab_get_document_template( $post_id ) ) {
						$document_type = $document_template->get_document_type();
						$url           = apply_filters( "storeabill_admin_{$document_type}_templates_archive_url", add_query_arg( array( 'section' => ( 'invoice' === $document_type ? '' : $document_type . 's' ) ), $url ), $document_type, $url );
					}
				}
			}

			wp_safe_redirect( $url );
			exit();
		}
	}

	/**
	 * Maybe merge the default layout from a certain document type into
	 * an existing (current) template.
	 */
	public static function maybe_merge_template() {
		if ( current_user_can( 'manage_storeabill' ) ) {
			if ( isset( $_GET['do'], $_GET['post'], $_GET['action'], $_GET['_wpnonce'], $_GET['base_document_type'] ) && 'edit' === $_GET['action'] && 'merge' === $_GET['do'] ) {
				if ( wp_verify_nonce( $_GET['_wpnonce'], 'sab-merge-template' ) ) {
					$document_type = sab_clean( $_GET['base_document_type'] );
					$post_id       = absint( $_GET['post'] );
					$post          = get_post( $post_id );
					$ref           = remove_query_arg( array( '_wpnonce', 'do', 'document_type' ), wp_get_referer() );

					if ( $post && self::is_document_template( $post ) && sab_get_document_type( $document_type ) ) {
						if ( $base_template = sab_get_default_document_template( $document_type ) ) {
							$result = self::merge_from_template( $base_template, $post_id );

							if ( $result ) {
								wp_safe_redirect( $ref );
								exit();
							}
						}
					}
				}
			}
		}
	}

	/**
	 * This method allows merging one layout into another layout to easily
	 * copy layouts from one document template to another.
	 *
	 * @param integer|Template $base_template The template to be used as template for merging.
	 * @param integer|Template $to_merge_template The template which will be adjusted.
	 *
	 * @return bool
	 */
	public static function merge_from_template( $base_template, $to_merge_template ) {
		if ( is_numeric( $base_template ) ) {
			$base_template = sab_get_document_template( $base_template );
		}

		if ( is_numeric( $to_merge_template ) ) {
			$to_merge_template = sab_get_document_template( $to_merge_template );
		}

		if ( ! $base_template || ! $to_merge_template ) {
			return false;
		}

		$content          = $base_template->get_content();
		$header_content   = self::get_block_content( 'storeabill/header', $content, true );
		$footer_content   = self::get_block_content( 'storeabill/footer', $content, true );
		$address_content  = self::get_block_content( 'storeabill/address', $content, true );

		$to_merge_content = $to_merge_template->get_content();

		if ( $header_content ) {
			$to_merge_content = self::replace_block_content( 'storeabill/header', $to_merge_content, $header_content );
		}

		if ( $footer_content ) {
			$to_merge_content = self::replace_block_content( 'storeabill/footer', $to_merge_content, $footer_content );
		}

		if ( $address_content ) {
			$to_merge_content = self::replace_block_content( 'storeabill/address', $to_merge_content, $address_content, false );
		}

		$base_document_type     = sab_get_document_type( $base_template->get_document_type() );
		$to_merge_document_type = sab_get_document_type( $to_merge_template->get_document_type() );

		/**
		 * In case the document types of the templates equal or
		 * their group (e.g. accounting) equals, allow merging additional data e.g. the item table and totals.
		 */
		if ( $base_template->get_document_type() === $to_merge_template->get_document_type() || $base_document_type->group === $to_merge_document_type->group ) {

			if ( sab_document_type_supports( $to_merge_template->get_document_type(), 'totals' ) ) {
				$item_totals = self::get_block_content( 'storeabill/item-totals', $content, true );

				if ( $item_totals ) {
					$to_merge_content = self::replace_block_content( 'storeabill/item-totals', $to_merge_content, $item_totals );
				}
			}

			if ( sab_document_type_supports( $to_merge_template->get_document_type(), 'items' ) ) {
				$item_table_content = self::get_block_content( 'storeabill/item-table', $content, true );

				if ( $item_table_content ) {
					$to_merge_content = self::replace_block_content( 'storeabill/item-table', $to_merge_content, $item_table_content );
				}
			}

			if ( 'accounting' === $to_merge_document_type->group ) {
				$reverse_charge     = self::get_block_content( 'storeabill/reverse-charge-notice', $content, true );
				$third_country      = self::get_block_content( 'storeabill/third-country-notice', $content, true );

				if ( $third_country ) {
					$to_merge_content = self::replace_block_content( 'storeabill/third-country-notice', $to_merge_content, $third_country );
				}

				if ( $reverse_charge ) {
					$to_merge_content = self::replace_block_content( 'storeabill/reverse-charge-notice', $to_merge_content, $reverse_charge );
				}
			}
		}

		$to_merge_template->set_content( $to_merge_content );
		$to_merge_template->set_margins( $base_template->get_margins( 'edit' ) );
		$to_merge_template->set_color( $base_template->get_color( 'edit' ) );
		$to_merge_template->set_pdf_template_id( $base_template->get_pdf_template_id( 'edit' ) );
		$to_merge_template->set_font_size( $base_template->get_font_size( 'edit' ) );
		$to_merge_template->set_fonts( $base_template->get_fonts( 'edit' ) );

		$to_merge_template->save();

		/**
		 * Copy first page data. Add custom first page template if not yet existent.
		 */
		if ( $base_template->has_custom_first_page() ) {
			$base_template_first_page = $base_template->get_first_page();

			if ( ! $to_merge_template->has_custom_first_page() ) {
				$to_merge_first_page_template = new FirstPageTemplate();
				$to_merge_first_page_template->set_parent_id( $to_merge_template->get_id() );
				$to_merge_first_page_template->save();
			} else {
				$to_merge_first_page_template = $to_merge_template->get_first_page();
			}

			$content          = $base_template_first_page->get_content();
			$header_content   = self::get_block_content( 'storeabill/header', $content, true );
			$footer_content   = self::get_block_content( 'storeabill/footer', $content, true );

			$to_merge_content = $to_merge_first_page_template->get_content();

			if ( $header_content ) {
				$to_merge_content = self::replace_block_content( 'storeabill/header', $to_merge_content, $header_content );
			}

			if ( $footer_content ) {
				$to_merge_content = self::replace_block_content( 'storeabill/footer', $to_merge_content, $footer_content );
			}

			$to_merge_first_page_template->set_content( $to_merge_content );
			$to_merge_first_page_template->set_margins( $base_template_first_page->get_margins( 'edit' ) );
			$to_merge_first_page_template->set_pdf_template_id( $base_template_first_page->get_pdf_template_id( 'edit' ) );

			$to_merge_first_page_template->save();
		} elseif( $to_merge_template->has_custom_first_page() ) {
			$to_merge_template->get_first_page()->delete( true );
		}

		return true;
	}

	protected static function replace_block_content( $block_name, $content, $new_content, $append_if_not_found = true ) {
		$start  = self::get_block_regex( $block_name, 'start' );
		$end    = self::get_block_regex( $block_name, 'end' );
		$result = $content;

		if ( self::get_block_content( $block_name, $content ) ) {
			$result = preg_replace( '#('. $start .')(.*)(' . preg_quote( $end ) . ')#siU', $new_content, $content );
		} elseif ( $append_if_not_found ) {
			$result .= "\n\r" . $new_content;
		}

		return $result;
	}

	protected static function get_block_regex( $block_name, $type = 'start' ) {
		if ( 'start' === $type ) {
			return '<!-- wp:' . preg_quote( $block_name ) . ' ({(.*)}(\s)*)?-->';
		} elseif ( 'end' === $type ) {
			return '<!-- /wp:' . $block_name . ' -->';
		}

		return '';
	}

	public static function get_block_content( $block_name, $content, $first_only = false ) {
		$start = self::get_block_regex( $block_name, 'start' );
		$end   = self::get_block_regex( $block_name, 'end' );

		preg_match_all( '#('. $start .')(.*)(' . preg_quote( $end ) . ')#siU', $content, $matches );

		if ( ! empty( $matches[0] ) ) {
			if ( $first_only ) {
				return $matches[0][0];
			} else {
				return $matches[0];
			}
		}

		return false;
	}

	/**
	 * @param Journal $journal
	 */
	public static function maybe_refresh_template_shortcodes( $journal ) {
		$changes = $journal->get_changes();

		if ( ! empty( $changes ) && $journal->get_id() > 0 ) {
			self::queue_refresh_template_shortcodes( $journal->get_type() );
		}
	}

	/**
	 * Parses the template content and replaces shortcode placeholders with actual shortcodes
	 * that are marked as needing a refresh. Next time the user opens the template editor,
	 * shortcodes will be refreshed via API calls. Enabled by default.
	 *
	 * @param integer $template_id template id
	 */
	public static function refresh_template_shortcodes( $template_id ) {
		if ( ( $template = sab_get_document_template( $template_id ) ) && apply_filters( 'storeabill_refresh_document_template_shortcodes', true, $template_id ) ) {
			$content = $template->get_content();

			if ( strpos( $content, 'document-shortcode-needs-refresh' ) === false ) {
				$content = str_replace( 'document-shortcode-needs-refresh', '', $content );
				$content = str_replace( 'document-shortcode', 'document-shortcode document-shortcode-needs-refresh', $content );

				$template->set_content( $content );
				$template->save();
			}
		}
	}

	/**
	 * Queue refreshing document preview shortcodes e.g. after settings saving or updating.
	 *
	 * @param string $document_type
	 */
	public static function queue_refresh_template_shortcodes( $document_type = '' ) {
		$templates = sab_get_document_templates( $document_type, true );
		$count     = 0;

		foreach( $templates as $template ) {
			$count++;

			$args = array(
				'template_id' => $template->get_id(),
			);

			$queue = WC()->queue();

			/**
			 * Cancel outstanding events and queue new.
			 */
			$queue->cancel_all( 'storeabill_refresh_template_shortcodes', $args, 'storeabill-document-preview' );

			$queue->schedule_single(
				time() + ( $count * 50 ),
				'storeabill_refresh_template_shortcodes',
				$args,
				'storeabill-document-preview'
			);
		}
	}

	/**
	 * Register blocks in case a server renderer request was fired (to make sure dynamic blocks are registered).
	 *
	 * @param $result
	 * @param \WP_REST_Server $server
	 * @param \WP_REST_Request $request
	 *
	 * @return mixed
	 */
	public static function register_blocks_api( $result, $server, $request ) {
		if ( $request && strpos( 'server-renderer', $request->get_route() ) !== false ) {
			self::get_blocks();
		}

		return $result;
	}

	public static function on_insert_new( $post_id, $post, $update ) {
		if ( ! $update && self::is_document_template( $post ) ) {

			if ( ! isset( $_GET['document_type'] ) ) {
				return;
			}

			/**
			 * Do not update for first page templates
			 */
			if ( ! empty( $post->post_parent ) ) {
				return;
			}

			$document_type = sab_clean( $_GET['document_type'] );

			if ( ! sab_get_document_type( $document_type ) ) {
				$document_type = 'invoice';
			}

			if ( $template = sab_get_document_template( $post_id ) ) {
				$template->set_document_type( $document_type );

				do_action( 'storeabill_before_create_new_document_template', $template, $post_id, $post );

				$template->save();
			}
		}
	}

	protected static function get_font_embed( $template ) {
		if ( is_null( self::$font_embed ) ) {
			$fonts            = $template->get_fonts();
			self::$font_embed = false;

			if ( ! empty( $fonts ) ) {
				self::$font_embed = new Embed( $template->get_fonts(), $template->get_font_display_types() );
			}
		}

		return self::$font_embed;
	}

	public static function add_footer_styles() {
		global $post;

		if ( self::is_document_template( $post ) ) {
			if ( $template = sab_get_document_template( $post ) ) {
				if ( $embed = self::get_font_embed( $template ) ) {
					$inline_css = $embed->get_inline_css();

					echo '<style class="sab-font-inline">' . $inline_css . '</style>';
				}
			}
		}
	}

	public static function add_style_data() {
		global $post;

		if ( self::is_document_template( $post ) ) {

			if ( $template = sab_get_document_template( $post ) ) {
				if ( $embed = self::get_font_embed( $template ) ) {
					$facet_css  = $embed->get_font_facets_css();

					wp_add_inline_style( 'sab-block-editor', $facet_css );
				}
			}
		}
	}

	public static function get_core_asset_data() {
		return array(
			'itemTotalTypes' => array(),
			'itemMetaTypes'  => array(),
			'isFirstPage'    => false,
			'templateType'   => '',
		);
	}

	protected static function execute_lazy_asset_data() {
		global $post;

		if ( $post && self::is_document_template( $post ) && ( $template = sab_get_document_template( $post, true ) ) ) {
			$document_type    = sab_get_document_type( $template->get_document_type() );
			$default_template = sab_get_document_template( $post );
			$preview          = sab_get_document_preview( $template->get_document_type(), true );
			$edit_link        = false;

			if ( $template->is_first_page() ) {
				$edit_link = $default_template->get_edit_url();
			} elseif ( $template->has_custom_first_page() ) {
				$edit_link = $template->get_first_page()->get_edit_url();
			}

			$line_item_types        = $template->get_line_item_types();
			$line_item_type_options = array();

			if ( empty( $line_item_types ) ) {
				$line_item_types = $document_type->default_line_item_types;
			}

			$item_types        = $document_type->available_line_item_types;
			$item_type_options = array();

			foreach( $item_types as $item_type ) {
				$item_type_options[ $item_type ] = sab_get_document_item_type_title( sab_maybe_prefix_document_item_type( $item_type, $template->get_document_type() ) );
			}

			foreach( $line_item_types as $item_type ) {
				$line_item_type_options[ $item_type ] = sab_get_document_item_type_title( sab_maybe_prefix_document_item_type( $item_type, $template->get_document_type() ) );
			}

			$document_types = array();

			foreach( sab_get_document_types() as $type ) {
				$document_types[ $type ] = sab_get_document_type_label( $type );
			}

			self::$asset_data['templateType']         = $template->get_type();
			self::$asset_data['title']                = $template->get_title( $template->is_first_page() ? 'view' : 'edit' );

			self::$asset_data['documentType']         = $template->get_document_type();
			self::$asset_data['documentTypes']        = $document_types;

			self::$asset_data['documentTypeTitle']    = sab_get_document_type_label( $document_type );
			self::$asset_data['isFirstPage']          = $template->is_first_page();
			self::$asset_data['linkedEditLink']       = $edit_link ? $edit_link : '';
			self::$asset_data['marginTypesSupported'] = $template->is_first_page() ? array( 'top', 'bottom' ) : array( 'top', 'left', 'bottom', 'right' );
			self::$asset_data['defaultMargins']       = $template->get_default_margins();
			self::$asset_data['lineItemTypes']        = $line_item_type_options;
			self::$asset_data['itemTypes']            = $item_type_options;
			self::$asset_data['dateFormat']           = sab_date_format();
			self::$asset_data['dateTypes']            = apply_filters( 'storeabill_document_template_editor_date_types', $document_type->date_types, $document_type );
			self::$asset_data['supports']             = $document_type->supports;
			self::$asset_data['discountTotalTypes']   = array();
			self::$asset_data['dynamicContentBlocks'] = self::get_dynamic_content_blocks();
			self::$asset_data['defaultInnerBlocks']   = self::get_default_inner_blocks( $template->get_document_type() );
			self::$asset_data['assets_url']           = trailingslashit( Package::get_assets_url() );
			self::$asset_data['attribute_slugs']      = array_values( wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_name' ) );
			self::$asset_data['barcodeTypes']         = sab_get_barcode_types();
			self::$asset_data['barcodeCodeTypes']     = sab_get_document_type_barcode_code_types( $template->get_document_type() );
			self::$asset_data['allowedBlockTypes']    = self::allowed_block_types( array(), $post );

			if ( sab_document_type_supports( $template->get_document_type(), 'discounts' ) ) {
				self::$asset_data['discountTotalTypes'] = array(
					'before_discounts' => _x( 'Before discounts', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'after_discounts'  => _x( 'After discounts', 'storeabill-core', 'woocommerce-germanized-pro' )
				);
			}

			self::$asset_data['itemTableBlockTypes']  = array(
				'core/paragraph',
				'core/spacer',
				'core/separator',
			);

			self::$asset_data['shortcodes'] = array(
				'blocks'        => array(),
				'document_item' => array(),
				'document'      => array(),
				'setting'       => array(),
			);

			foreach ( self::get_blocks() as $block ) {
				self::$asset_data['shortcodes']['blocks']['storeabill/' . $block->get_name()] = $block->get_available_shortcodes();

				if ( is_a( $block, '\Vendidero\StoreaBill\Editor\Blocks\ItemTableColumnBlock' ) ) {
					self::$asset_data['itemTableBlockTypes'][] = 'storeabill/' . $block->get_name();
				}
			}

			/**
			 * Merge document type shortcodes.
			 */
			self::$asset_data['shortcodes'] = apply_filters( 'storeabill_document_template_editor_available_shortcodes', array_merge_recursive( self::$asset_data['shortcodes'], $document_type->shortcodes ), $template->get_document_type() );

			if ( ! $template->is_first_page() ) {
				self::$asset_data['customFontSizes']    = array_values( sab_get_document_font_sizes() );
				self::$asset_data['defaultFontSize']    = sab_get_document_default_font_size();
				self::$asset_data['defaultColor']       = sab_get_document_default_color();
				self::$asset_data['fonts']              = array_values( Fonts::get_fonts_select() );
				self::$asset_data['fontDisplayTypes']   = $template->get_font_display_types();
				self::$asset_data['defaultFont']        = $template->get_default_font();
				self::$asset_data['fontVariationTypes'] = sab_get_font_variant_types();
				self::$asset_data['mergeBaseUrl']       = html_entity_decode( wp_nonce_url( add_query_arg( array( 'do' => 'merge' ), get_edit_post_link( $template->get_id(), 'edit' ) ), 'sab-merge-template' ) );

				foreach( $document_type->total_types as $type => $data ) {
					$data = wp_parse_args( $data, array(
						'title'       => '',
						'desc'        => '',
						'hide_editor' => false,
					) );

					/**
					 * Skip in case the total type should not be visible within the editor.
					 */
					if ( $data['hide_editor'] ) {
						continue;
					}

					self::$asset_data['itemTotalTypes'][] = array(
						'icon'    => '',
						'title'   => $data['desc'],
						'default' => $data['title'],
						'type'    => $type,
					);
				}

				/**
				 * Register item meta data ready to preview in the editor.
				 */
				foreach( array_keys( self::$asset_data['itemTypes'] ) as $type ) {
					self::$asset_data['itemMetaTypes'][ $type ] = $preview ? $preview->get_item_preview_meta( $type ) : array();
				}
			}

			// Get preview data by initially performing a REST request
			$request  = new WP_REST_Request( 'GET', '/sab/v1/' . $document_type->api_endpoint . '/preview' );
			$response = rest_do_request( $request );

			if ( 200 === $response->get_status() ) {
				$server   = rest_get_server();
				$data     = wp_json_encode( $server->response_to_data( $response, false ) );

				self::$asset_data['preview'] = json_decode( $data );
			}
		}
	}

	protected static function get_default_inner_blocks( $document_type ) {
		$document_type_object = sab_get_document_type( $document_type );

		$default_inner_blocks = array(
			'storeabill/item-table' => array(
				array(
					'name' => 'storeabill/item-table-column',
					'attributes' => array(
						'heading' => '<strong>' . _x( 'Name', 'storeabill-core', 'woocommerce-germanized-pro' ) . '</strong>',
					),
					'innerBlocks' => array(
						array(
							'name'       => 'storeabill/item-name',
							'attributes' => array(),
						),
					),
				),
				array(
					'name' => 'storeabill/item-table-column',
					'attributes' => array(
						'heading' => '<strong>' . _x( 'Quantity', 'storeabill-core', 'woocommerce-germanized-pro' ) . '</strong>',
						'align'   => 'right',
					),
					'innerBlocks' => array(
						array(
							'name'       => 'storeabill/item-quantity',
							'attributes' => array(),
						),
					),
				),
			),
		);

		if ( 'accounting' === $document_type_object->group ) {
			$default_inner_blocks = array(
				'storeabill/item-table'  => array(
					array(
						'name' => 'storeabill/item-table-column',
						'attributes' => array(
							'heading' => '<strong>' . _x( 'Name', 'storeabill-core', 'woocommerce-germanized-pro' ) . '</strong>',
							'width'   => 45,
						),
						'innerBlocks' => array(
							array(
								'name'       => 'storeabill/item-name',
								'attributes' => array(),
							),
						),
					),
					array(
						'name' => 'storeabill/item-table-column',
						'attributes' => array(
							'heading' => '<strong>' . _x( 'Quantity', 'storeabill-core', 'woocommerce-germanized-pro' ) . '</strong>',
							'align'   => 'center'
						),
						'innerBlocks' => array(
							array(
								'name'       => 'storeabill/item-quantity',
								'attributes' => array(),
							),
						),
					),
					array(
						'name' => 'storeabill/item-table-column',
						'attributes' => array(
							'heading' => '<strong>' . _x( 'Price', 'storeabill-core', 'woocommerce-germanized-pro' ) . '</strong>',
							'align'   => 'center'
						),
						'innerBlocks' => array(
							array(
								'name'       => 'storeabill/item-price',
								'attributes' => array(),
							),
						),
					),
					array(
						'name' => 'storeabill/item-table-column',
						'attributes' => array(
							'heading' => '<strong>' . _x( 'Discount', 'storeabill-core', 'woocommerce-germanized-pro' ) . '</strong>',
							'align'   => 'center'
						),
						'innerBlocks' => array(
							array(
								'name'       => 'storeabill/item-discount',
								'attributes' => array(),
							),
						),
					),
					array(
						'name' => 'storeabill/item-table-column',
						'attributes' => array(
							'heading' => '<strong>' . _x( 'Total', 'storeabill-core', 'woocommerce-germanized-pro' ) . '</strong>',
							'align'   => 'right',
						),
						'innerBlocks' => array(
							array(
								'name'       => 'storeabill/item-line-total',
								'attributes' => array(),
							),
						),
					),
				),
				'storeabill/item-totals' => array(
					array(
						'name' => 'storeabill/item-total-row',
						'attributes' => array(
							'heading'   => _x( 'Subtotal', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'totalType' => 'line_subtotal_after',
						),
					),
					array(
						'name' => 'storeabill/item-total-row',
						'attributes' => array(
							'heading'     => _x( 'Fee', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'totalType'   => 'fee',
							'hideIfEmpty' => true,
						),
					),
					array(
						'name' => 'storeabill/item-total-row',
						'attributes' => array(
							'heading'     => _x( 'Shipping', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'totalType'   => 'shipping',
							'border'      => 'bottom',
							'borderColor' => 'black',
						),
					),
					array(
						'name' => 'storeabill/item-total-row',
						'attributes' => array(
							'heading'        => '<strong>' . _x( 'Total', 'storeabill-core', 'woocommerce-germanized-pro' ) . '</strong>',
							'totalType'      => 'total',
							'content'        => '<strong>{total}</strong>',
							'borderColor'    => 'black',
							'customFontSize' => 16,
							'border'         => 'bottom'
						),
					),
					array(
						'name' => 'storeabill/item-total-row',
						'attributes' => array(
							'heading'        => _x( 'Net', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'totalType'      => 'net',
							'customFontSize' => 11,
						),
					),
					array(
						'name' => 'storeabill/item-total-row',
						'attributes' => array(
							'heading'        => _x( 'Tax %s %%', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'totalType'      => 'taxes',
							'customFontSize' => 11,
						),
					),
				),
			);
		}

		return apply_filters( "storeabill_document_template_editor_default_inner_blocks", $default_inner_blocks, $document_type );
	}

	protected static function initialize_core_asset_data() {
		$settings = apply_filters(
			'storeabill_document_template_editor_settings',
			array()
		);

		// note this WILL wipe any data already registered to these keys because
		// they are protected.
		self::$asset_data = array_replace_recursive( $settings, self::get_core_asset_data() );
	}

	public static function enqueue_asset_data() {
		if ( wp_script_is( 'sab-settings', 'enqueued' ) ) {

			self::initialize_core_asset_data();
			self::execute_lazy_asset_data();

			$data = rawurlencode( wp_json_encode( self::$asset_data ) );

			wp_add_inline_script(
				'sab-settings',
				"var sabSettings = sabSettings || JSON.parse( decodeURIComponent( '"
				. esc_js( $data )
				. "' ) );",
				'before'
			);
		}
	}

	protected static function is_document_template( $post ) {
		if ( $post && 'document_template' === $post->post_type ) {
			return true;
		}

		return false;
	}

	public static function conditionally_load_template( $replace, $post ) {

		if ( self::is_document_template( $post ) ) {

			// Setup custom filters
			add_theme_support( 'editor-font-sizes', array_values( sab_get_document_font_sizes() ) );

			// Remove custom theme supports
			remove_theme_support( 'align-wide' );
			remove_theme_support( 'editor-color-palette' );
			remove_theme_support( 'editor-gradient-presets' );

			/**
			 * This action indicates that the current request
			 * is a StoreaBill editor request which will load all the editor blocks and assets.
			 */
			do_action( 'storeabill_load_block_editor' );

			return $replace;
		}

		return $replace;
	}

	/**
	 * @param $args
	 * @param \WP_Block_Editor_Context $block_editor_context
	 */
	public static function prevent_theme_settings( $args, $block_editor_context ) {
		if ( $block_editor_context->post && self::is_document_template( $block_editor_context->post ) ) {
			unset( $args['colors'] );
			unset( $args['gradients'] );

			$args['styles']                 = array();
			$args['disableCustomColors']    = false;
			$args['disableCustomGradients'] = true;
			$args['enableCustomUnits']      = false;
			$args['supportsLayout']         = false;

			if ( isset( $args['__experimentalFeatures']['color']['customDuotone'] ) ) {
				$args['__experimentalFeatures']['color']['customDuotone'] = false;
			}

			if ( isset( $args['__experimentalFeatures']['color']['palette']['theme'] ) ) {
				unset( $args['__experimentalFeatures']['color']['palette']['theme'] );
			}
		}

		return $args;
	}

	public static function remove_theme_editor_styles( $enable, $post ) {
		if ( self::is_document_template( $post ) ) {
			if ( function_exists( 'remove_editor_styles' ) ) {
				remove_editor_styles();
				remove_theme_support( 'wp-block-styles' );
				remove_theme_support( 'editor-color-palette' );
				remove_theme_support( 'disable-custom-font-sizes' );

				add_theme_support( 'disable-custom-colors' );
			}

			/**
			 * Setup a global filter to prevent Gutenberg from loading theme-specific settings for the editor (e.g. theme.json)
			 */
			add_filter( 'theme_file_path', array( __CLASS__, 'prevent_valid_theme_json_file' ), 999, 2 );
		}

		return $enable;
	}

	public static function reset_theme_file_path_filter( $message ) {
		remove_filter( 'theme_file_path', array( __CLASS__, 'prevent_valid_theme_json_file' ), 999 );

		return $message;
	}

	/**
	 * Prevent Gutenberg from loading the theme.json file which may override color palettes.
	 *
	 * @param $stylesheet_dir
	 * @param $file
	 *
	 * @return mixed|string
	 */
	public static function prevent_valid_theme_json_file( $stylesheet_dir, $file ) {
		if ( $file && 'theme.json' === $file ) {
			$stylesheet_dir = trailingslashit( WP_CONTENT_DIR ) . 'themes';
		}

		return $stylesheet_dir;
	}

	protected static function allow_third_party_asset( $src ) {
		$allow = true;

		/**
		 * Whitelist assets from StoreaBill
		 */
		$whitelist = apply_filters( 'storeabill_document_template_editor_asset_whitelist_paths', array(
			'plugins/storeabill',
			'packages/storeabill'
		) );

		/**
		 * Check whether the asset belongs to an extension (theme, plugin).
		 */
		if ( strpos( $src, 'wp-content/' ) !== false && strpos( $src, 'wp-content/plugins/gutenberg/' ) === false ) {
			$allow = false;

			foreach( $whitelist as $file_whitelist ) {
				if ( strpos( $src, $file_whitelist ) !== false ) {
					$allow = true;
					break;
				}
			}
		}

		return apply_filters( 'storeabill_document_template_editor_allow_third_party_asset', $allow, $src );
	}

	public static function prevent_third_party_assets() {
		global $post;

		if ( self::is_document_template( $post ) ) {
			global $wp_styles, $wp_scripts;

			foreach( $wp_styles->registered as $key => $style ) {
				if ( ! self::allow_third_party_asset( $style->src ) ) {
					wp_dequeue_style( $style->handle );
				}
			}

			foreach( $wp_scripts->registered as $key => $script ) {
				if ( ! self::allow_third_party_asset( $script->src ) ) {
					wp_dequeue_script( $script->handle );
				}
			}
		}
	}

	public static function enqueue_editor_assets() {
		global $post;

		if ( self::is_document_template( $post ) ) {
			wp_enqueue_script( 'sab-document-main-panel' );
			wp_enqueue_script( 'sab-document-margins-panel' );
			wp_enqueue_script( 'sab-document-fonts-panel' );
			wp_enqueue_script( 'sab-document-background-panel' );
		}
	}

	public static function register_assets() {
		self::register_style( 'sab-block-editor', Package::get_url() . '/build/editor/editor.css', array( 'wp-edit-blocks' ) );
		wp_style_add_data( 'sab-block-editor', 'rtl', 'replace' );

		if ( ! self::$has_registered_assets ) {
			$inline_css = '';

			foreach( sab_get_document_font_sizes() as $type => $size ) {
				$inline_css .= '.editor-styles-wrapper .has-' . sanitize_key( $size['slug'] ) . '-font-size, .has-' . sanitize_key( $size['slug'] ) . '-font-size {
					font-size: ' . esc_attr( $size['size'] ) . 'px;
				} ';
			}

			wp_add_inline_style( 'sab-block-editor', $inline_css );
		}

		self::register_script( 'sab-settings', Package::get_url() . '/build/editor/settings.js' );
		self::register_script( 'sab-blocks', Package::get_url() . '/build/editor/blocks.js' );
		self::register_script( 'sab-vendors', Package::get_url() . '/build/editor/vendors.js', [], false );
		self::register_script( 'sab-format-types', Package::get_url() . '/build/editor/format-types.js' );

		self::register_script( 'sab-document-main-panel', Package::get_url() . '/build/editor/document-main-panel.js' );
		self::register_script( 'sab-document-margins-panel', Package::get_url() . '/build/editor/document-margins-panel.js' );
		self::register_script( 'sab-document-fonts-panel', Package::get_url() . '/build/editor/document-fonts-panel.js' );
		self::register_script( 'sab-document-background-panel', Package::get_url() . '/build/editor/document-background-panel.js' );

		do_action( 'storeabill_document_editor_register_assets', self::$has_registered_assets );

		self::$has_registered_assets = true;
	}

	public static function register_meta() {

		register_post_meta('document_template', '_pdf_template_id', array(
			'show_in_rest'      => true,
			'type'              => 'integer',
			'single'            => true,
			'sanitize_callback' => 'absint',
			'auth_callback'     => function() {
				return current_user_can( 'manage_storeabill' );
			}
		) );

		register_post_meta('document_template', '_fonts', array(
			'show_in_rest' => array(
				'schema' => array(
					'type'       => 'object',
					'properties' => array(
						'default' => array(
							'type' => 'object',
							'single'     => true,
							'properties' => array(
								'name' => array(
									'type' => 'string'
								),
								'variants' => array(
									'type'  => 'object',
									'properties' => array(
										'regular' => array(
											'type' => 'string',
										),
										'bold' => array(
											'type' => 'string',
										),
										'italic' => array(
											'type' => 'string',
										),
										'bold_italic' => array(
											'type' => 'string',
										)
									),
								),
							),
						),
					),
				),
			),
			'type'              => 'object',
			'single'            => true,
			'auth_callback'     => function() {
				return current_user_can( 'manage_storeabill' );
			}
		) );

		register_post_meta('document_template', '_margins', array(
			'show_in_rest' => array(
				'schema' => array(
					'type'       => 'object',
					'properties' => array(
						'left' => array(
							'type' => 'string',
						),
						'right'  => array(
							'type' => 'string',
						),
						'top'  => array(
							'type' => 'string',
						),
						'bottom'  => array(
							'type' => 'string',
						),
					),
				),
			),
			'type'              => 'object',
			'single'            => true,
			'sanitize_callback' => array( __CLASS__, 'sanitize_margins' ),
			'auth_callback'     => function() {
				return current_user_can( 'manage_storeabill' );
			}
		) );

		register_post_meta('document_template', '_font_size', array(
			'show_in_rest' => array(
				'schema' => array(
					'type'  => 'string',
				),
			),
			'type'              => 'object',
			'single'            => true,
			'sanitize_callback' => array( __CLASS__, 'sanitize_font_size' ),
			'auth_callback'     => function() {
				return current_user_can( 'manage_storeabill' );
			}
		) );

		register_post_meta('document_template', '_color', array(
			'show_in_rest' => array(
				'schema' => array(
					'type'  => 'string',
				),
			),
			'type'              => 'string',
			'single'            => true,
			'auth_callback'     => function() {
				return current_user_can( 'manage_storeabill' );
			}
		) );
	}

	public static function sanitize_font_size( $font_size ) {
		return sanitize_text_field( $font_size );
	}

	public static function sanitize_margins( $margins ) {
		$defaults = array(
			'left'   => 0,
			'top'    => 0,
			'bottom' => 0,
			'right'  => 0,
		);

		$margins = array_intersect_key( $margins, $defaults );
		$margins = array_map( 'sab_format_decimal', $margins );

		return $margins;
	}

	public static function get_editor_templates( $document_type ) {
		$templates = array();

		if ( 'invoice' === $document_type ) {
			$templates = array(
				'default' => '\Vendidero\StoreaBill\Editor\Templates\DefaultInvoice'
			);
		} elseif( 'invoice_cancellation' === $document_type ) {
			$templates = array(
				'default' => '\Vendidero\StoreaBill\Editor\Templates\DefaultInvoiceCancellation'
			);
		}

		return apply_filters( "storeabill_{$document_type}_editor_templates", $templates );
	}

	public static function get_default_editor_template( $document_type ) {
		$default_template_name = '';

		if ( $document_type_data = sab_get_document_type( $document_type ) ) {
			$default_template_name = $document_type_data->default_template;
		}

		return $default_template_name;
	}

	/**
	 * @param string $document_type
	 * @param string $name
	 *
	 * @return bool|\Vendidero\StoreaBill\Editor\Templates\Template
	 */
	public static function get_editor_template( $document_type, $name = 'default' ) {
		$templates = self::get_editor_templates( $document_type );
		$template  = array_key_exists( $name, $templates ) ? $templates[ $name ] : false;

		/**
		 * As a fallback use the default template registered for the document type.
		 */
		if ( false === $template ) {
			if ( $document_type_data = sab_get_document_type( $document_type ) ) {
				$default_template_name = self::get_default_editor_template( $document_type );
				$template              = array_key_exists( $default_template_name, $templates ) ? $templates[ $default_template_name ] : false;
			}
		}

		return $template;
	}

	/**
	 * @return Block[]
	 */
	public static function get_blocks() {
		if ( is_null( self::$blocks ) ) {

			self::$blocks = array();

			$blocks = array(
				'ItemTableColumn',
				'ItemTable',
				'ItemName',
				'ItemImage',
				'ItemField',
				'ItemPosition',
				'ItemPrice',
				'ItemAttributes',
				'ItemQuantity',
				'ItemSku',
				'ItemDiscount',
				'ItemTaxRate',
				'ItemLineTotal',
				'ItemDifferentialTaxationNotice',
				'ItemMeta',
				'ItemTotals',
				'ItemTotalRow',
				'Header',
				'Footer',
				'Address',
				'Logo',
				'DocumentTitle',
				'PageNumber',
				'DocumentDate',
				'Barcode',
				'DocumentStyles',
				'ThirdCountryNotice',
				'ReverseChargeNotice',
				'ShippingAddress'
			);

			foreach ( $blocks as $class ) {
				$class    = __NAMESPACE__ . '\\Blocks\\' . $class;
				$instance = new $class();

				$instance->register_script();
				$instance->register_type();

				self::$blocks[ $instance->get_name() ] = $instance;
			}

			foreach( self::get_dynamic_content_blocks() as $block_name => $dynamic_content_block ) {
				$block = wp_parse_args( $dynamic_content_block, array(
					'title'           => '',
					'render_callback' => null,
				) );

				$class    = __NAMESPACE__ . '\\Blocks\\DynamicContent';
				$instance = new $class( $block_name, $block );

				$instance->register_script();
				$instance->register_type();

				self::$blocks[ $instance->get_name() ] = $instance;
			}
		}

		return self::$blocks;
	}

	protected static function get_dynamic_content_blocks() {
		return apply_filters( 'storeabill_document_template_editor_dynamic_content_blocks', array() );
	}

	/**
	 * @param $block_name
	 *
	 * @return Block|bool
	 */
	public static function get_block( $block_name ) {
		$block_name = str_replace( 'storeabill/', '', $block_name );
		$blocks     = self::get_blocks();

		if ( array_key_exists( $block_name, $blocks ) ) {
			return $blocks[ $block_name ];
		}

		return false;
	}

	/**
	 * Registers a script according to `wp_register_script`, additionally loading the translations for the file.
	 *
	 * @since 2.0.0
	 *
	 * @param string $handle       Name of the script. Should be unique.
	 * @param string $src          Full URL of the script, or path of the script relative to the WordPress root directory.
	 * @param array  $dependencies Optional. An array of registered script handles this script depends on. Default empty array.
	 * @param bool   $has_i18n     Optional. Whether to add a script translation call to this file. Default 'true'.
	 */
	public static function register_script( $handle, $src, $dependencies = [], $has_i18n = true ) {
		$relative_src = str_replace( Package::get_url() . '/', '', $src );
		$asset_path   = Package::get_path() . '/' . str_replace( '.js', '.asset.php', $relative_src );

		if ( file_exists( $asset_path ) ) {
			$asset        = require $asset_path;
			$dependencies = isset( $asset['dependencies'] ) ? array_merge( $asset['dependencies'], $dependencies ) : $dependencies;
			$version      = ! empty( $asset['version'] ) ? $asset['version'] : self::get_file_version( $relative_src );
		} else {
			$version = self::get_file_version( $relative_src );
		}

		wp_register_script( $handle, $src, $dependencies, $version, true );

		if ( $has_i18n && function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( $handle, apply_filters( 'storeabill_script_translations_i18n_domain', 'storeabill' ), apply_filters( 'storeabill_script_translations_i18n_path', Package::get_path() . '/i18n/languages' ) );
		}
	}

	/**
	 * Registers a style according to `wp_register_style`.
	 *
	 * @since 2.0.0
	 *
	 * @param string $handle Name of the stylesheet. Should be unique.
	 * @param string $src    Full URL of the stylesheet, or path of the stylesheet relative to the WordPress root directory.
	 * @param array  $deps   Optional. An array of registered stylesheet handles this stylesheet depends on. Default empty array.
	 * @param string $media  Optional. The media for which this stylesheet has been defined. Default 'all'. Accepts media types like
	 *                       'all', 'print' and 'screen', or media queries like '(orientation: portrait)' and '(max-width: 640px)'.
	 */
	public static function register_style( $handle, $src, $deps = [], $media = 'all' ) {
		$filename = str_replace( plugins_url( '/', __DIR__ ), '', $src );
		$ver      = self::get_file_version( $filename );

		wp_register_style( $handle, $src, $deps, $ver, $media );
	}

	/**
	 * Get the file modified time as a cache buster if we're in dev mode.
	 *
	 * @param string $file Local path to the file.
	 * @return string The cache buster value to use for the given file.
	 */
	protected static function get_file_version( $file ) {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( Package::get_path() . $file ) ) {
			return filemtime( Package::get_path() . $file );
		}

		return Package::get_version();
	}

	/**
	 * @param Template $template
	 * @param string   $output_type
	 */
	public static function preview( $template, $output_type = 'pdf' ) {
		$error = new WP_Error();

		try {
			if ( ! $template ) {
				throw new Exception( _x( 'Missing default or first page template.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
			}

			$type = $template->get_document_type();

			if ( $preview = sab_get_document_preview( $type ) ) {
				$preview->set_template( $template );

				if ( 'html' === $output_type ) {
					echo $preview->get_html();
					exit();
				} else {
					$result = $preview->preview();

					if ( is_wp_error( $result ) ) {
						throw new Exception( $result->get_error_message() );
					}
				}
			} else {
				throw new Exception( _x( 'Document type does not support previewing.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
			}
		} catch( Exception $e ) {
			$error->add( 'preview-error', $e->getMessage() );
		}

		if ( sab_wp_error_has_errors( $error ) ) {
			return $error;
		} else {
			return true;
		}
	}

	public static function template_loader( $template ) {

		if ( is_embed() ) {
			return $template;
		}

		if ( is_singular( 'document_template' ) ) {
			global $post;

			if ( $doc_template = sab_get_document_template( $post->ID ) ) {
				$output_type = isset( $_GET['output_type'] ) ? sab_clean( $_GET['output_type'] ) : 'pdf';
				$result      = self::preview( $doc_template, $output_type );

				if ( is_wp_error( $result ) ) {
					wp_die( $result );
				}
			}
		}

		return $template;
	}

	public static function allowed_block_types_all( $allowed_block_types, $block_context ) {
		if ( is_a( $block_context, 'WP_Block_Editor_Context' ) && $block_context->post ) {
			return self::allowed_block_types( $allowed_block_types, $block_context->post );
		}

		return $allowed_block_types;
	}

	public static function allowed_block_types( $allowed_block_types, $post ) {
		if ( self::is_document_template( $post ) && ( $template = sab_get_document_template( $post, true ) ) ) {

			$allowed_block_types = array(
				'core/paragraph',
				'core/heading',
				'core/spacer',
				'core/table',
				'core/columns',
				'core/column',
				'core/group',
				'core/image',
				'core/separator',
				'core/nextpage',
				'core/list',
				'core/quote',
				'core/html',
				'storeabill/header',
				'storeabill/logo',
				'storeabill/footer',
				'storeabill/document-styles',
				'storeabill/page-number',
			);

			$total_block_types = array(
				'storeabill/item-totals',
				'storeabill/item-total-row'
			);

			$item_total_block_types = array(
				'storeabill/item-price',
				'storeabill/item-discount',
				'storeabill/item-line-total',
				'storeabill/item-tax-rate'
			);

			/**
			 * Some blocks are only available within default templates.
			 */
			if ( ! $template->is_first_page() ) {

				$document_type_object = sab_get_document_type( $template->get_document_type() );

				$allowed_block_types = array_merge( $allowed_block_types, array(
					'storeabill/address',
					'storeabill/document-title',
					'storeabill/document-date',
					'storeabill/barcode'
				) );

				/**
				 * Add blocks only available to documents supporting items.
				 */
				if ( sab_document_type_supports( $template->get_document_type(), 'items' ) ) {
					$allowed_block_types = array_merge( array(
						'storeabill/item-table',
						'storeabill/item-table-column',
						'storeabill/item-name',
						'storeabill/item-image',
						'storeabill/item-field',
						'storeabill/item-position',
						'storeabill/item-sku',
						'storeabill/item-meta',
						'storeabill/item-attributes',
						'storeabill/item-quantity',
					), $allowed_block_types );
				}

				/**
				 * Differential taxation notice is quite special
				 */
				if ( apply_filters( 'storeabill_enable_differential_taxation', in_array( Countries::get_base_country(), array( 'DE', 'AT' ) ) ) ) {
					$item_total_block_types[] = 'storeabill/item-differential-taxation-notice';
				}

				/**
				 * Add blocks only available to documents supporting totals.
				 */
				if ( sab_document_type_supports( $template->get_document_type(), 'totals' ) ) {
					$allowed_block_types = array_merge( $total_block_types, $allowed_block_types );
				}

				/**
				 * Add blocks only available to documents supporting item totals.
				 */
				if ( sab_document_type_supports( $template->get_document_type(), 'item_totals' ) ) {
					$allowed_block_types = array_merge( $item_total_block_types, $allowed_block_types );
				}

				/**
				 * Allow document types to register additional, supported blocks.
				 */
				if ( $document_type_object && ! empty( $document_type_object->additional_blocks ) ) {
					$allowed_block_types = array_merge( $document_type_object->additional_blocks, $allowed_block_types );
				}
			}

			// Add template (lazy loading - check the document type)
			$post_type_object = get_post_type_object( 'document_template' );

			$allowed_block_types = apply_filters( 'storeabill_document_template_editor_available_blocks', $allowed_block_types, $template->get_document_type(), $template );
		}

		return $allowed_block_types;
	}
}