<?php

use Vendidero\StoreaBill\Exceptions\DocumentRenderException;
use Vendidero\StoreaBill\Interfaces\PDF;
use Vendidero\StoreaBill\Package;

/**
 * StoreaBill Core Functions
 *
 * General core functions available on both the front-end and admin.
 *
 * @version 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include core functions (available in both admin and frontend).
require SAB_ABSPATH . 'includes/sab-conditional-functions.php';
require SAB_ABSPATH . 'includes/sab-document-functions.php';
require SAB_ABSPATH . 'includes/sab-document-style-functions.php';
require SAB_ABSPATH . 'includes/sab-invoice-functions.php';
require SAB_ABSPATH . 'includes/sab-journal-functions.php';
require SAB_ABSPATH . 'includes/sab-rest-functions.php';
require SAB_ABSPATH . 'includes/sab-formatting-functions.php';

function sab_doing_it_wrong( $function, $message, $version ) {
	wc_doing_it_wrong( $function, $message, $version );
}

/**
 * @param WP_Error $error
 *
 * @return bool
 */
function sab_wp_error_has_errors( $error ) {
	if ( is_callable( array( $error, 'has_errors' ) ) ) {
		return $error->has_errors();
	} else {
		$errors = $error->errors;

		return ( ! empty( $errors ) ? true : false );
	}
}

/**
 * Locate a template and return the path for inclusion.
 *
 * This is the load order:
 *
 * yourtheme/$template_path/$template_name
 * yourtheme/$template_name
 * $default_path/$template_name
 *
 * @param string $template_name Template name.
 * @param string $template_path Template path. (default: '').
 * @param string $default_path  Default path. (default: '').
 * @return string
 */
function sab_locate_template( $template_name, $template_path = '', $default_path = '' ) {
	if ( ! $template_path ) {
		$template_path = Package::get_template_path();
	}

	if ( ! $default_path ) {
		$default_path = Package::get_path() . '/templates/';
	}

	/**
	 * Allow filtering the default template path before locating theme templates
	 * to allow third party developers to easily add their template path as default template path.
	 */
	$default_path = apply_filters( 'storeabill_default_template_path', $default_path, $template_name );

	// Look within passed path within the theme - this is priority.
	$template = locate_template(
		apply_filters( 'storeabill_locate_theme_template_locations', array(
			trailingslashit( $template_path ) . $template_name,
			$template_name,
		), $template_name )
	);

	// Get default template/.
	if ( ! $template || SAB_TEMPLATE_DEBUG_MODE ) {
		$template = $default_path . $template_name;
	}

	// Return what we found.
	return apply_filters( 'storeabill_locate_template', $template, $template_name, $template_path );
}

/**
 * Get other templates (e.g. product attributes) passing attributes and including the file.
 *
 * @param string $template_name Template name.
 * @param array  $args          Arguments. (default: array).
 * @param string $template_path Template path. (default: '').
 * @param string $default_path  Default path. (default: '').
 */
function sab_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	$cache_key = sanitize_key( implode( '-', array( 'template', $template_name, $template_path, $default_path, SAB_VERSION ) ) );
	$template  = (string) wp_cache_get( $cache_key, 'storeabill' );

	if ( ! $template ) {
		$template = sab_locate_template( $template_name, $template_path, $default_path );
		wp_cache_set( $cache_key, $template, 'storeabill' );
	}

	// Allow 3rd party plugin filter template file from their plugin.
	$filter_template = apply_filters( 'storeabill_get_template', $template, $template_name, $args, $template_path, $default_path );

	if ( $filter_template !== $template ) {
		if ( ! file_exists( $filter_template ) ) {
			/* translators: %s template */
			sab_doing_it_wrong( __FUNCTION__, sprintf( _x( '%s does not exist.', 'storeabill-core', 'woocommerce-germanized-pro' ), '<code>' . $template . '</code>' ), '2.1' );
			return;
		}
		$template = $filter_template;
	}

	$action_args = array(
		'template_name' => $template_name,
		'template_path' => $template_path,
		'located'       => $template,
		'args'          => $args,
	);

	if ( ! empty( $args ) && is_array( $args ) ) {
		extract( $args ); // @codingStandardsIgnoreLine
	}

	do_action( 'storeabill_before_template_part', $action_args['template_name'], $action_args['template_path'], $action_args['located'], $action_args['args'] );

	include $action_args['located'];

	do_action( 'storeabill_after_template_part', $action_args['template_name'], $action_args['template_path'], $action_args['located'], $action_args['args'] );
}

function sab_get_currencies() {
	return get_woocommerce_currencies();
}

function sab_get_default_currency() {
	return get_woocommerce_currency();
}

/**
 * Like wc_get_template, but returns the HTML instead of outputting.
 *
 * @see wc_get_template
 * @since 2.5.0
 * @param string $template_name Template name.
 * @param array  $args          Arguments. (default: array).
 * @param string $template_path Template path. (default: '').
 * @param string $default_path  Default path. (default: '').
 *
 * @return string
 */
function sab_get_template_html( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	ob_start();
	sab_get_template( $template_name, $args, $template_path, $default_path );

	return ob_get_clean();
}

/**
 * @param string $renderer
 *
 * @return PDF
 *
 * @throws DocumentRenderException
 */
function sab_get_pdf_renderer( $renderer = '', $args = array() ) {
	$renderers = apply_filters( 'storeabill_pdf_renderers', array(
		'mpdf' => '\Vendidero\StoreaBill\PDF\MpdfRenderer'
	) );

	$args = wp_parse_args( $args, array(
		'template' => false,
	) );

	if ( ! is_a( $args['template'], '\Vendidero\StoreaBill\Document\DefaultTemplate' ) ) {
		throw new DocumentRenderException( _x( 'Missing document template.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
	}

	if ( empty( $renderer ) ) {
		$renderer = apply_filters( 'storeabill_default_pdf_renderer', 'mpdf' );
	}

	if ( ! array_key_exists( $renderer, $renderers ) ) {
		$renderer = 'mpdf';
	}

	$class = $renderers[ $renderer ];

	return new $class( $args );
 }

/**
 * @param string $merger
 *
 * @return \Vendidero\StoreaBill\Interfaces\PDFMerge
 */
function sab_get_pdf_merger( $merger = '' ) {
	$mergers = apply_filters( 'storeabill_pdf_renderers', array(
		'mpdf' => '\Vendidero\StoreaBill\PDF\MpdfMerger'
	) );

	if ( empty( $merger ) ) {
		$merger = apply_filters( 'storeabill_default_pdf_merger', 'mpdf' );
	}

	if ( ! array_key_exists( $merger, $mergers ) ) {
		$merger = 'mpdf';
	}

	$class = $mergers[ $merger ];

	return new $class();
}

/**
 * Define a constant if it is not already defined.
 *
 * @since 3.0.0
 * @param string $name  Constant name.
 * @param mixed  $value Value.
 */
function sab_maybe_define_constant( $name, $value ) {
	if ( ! defined( $name ) ) {
		define( $name, $value );
	}
}

function sab_add_block_class( $html, $new_classes ) {
	if ( is_array( $new_classes ) ) {
		$new_classes = implode( " ", $new_classes );
	}

	$has_class = true;
	$str       = $html;

	$tags = array(
		'p',
		'div'
	);

	foreach( $tags as $tag ) {
		if ( substr( trim( $html ), 0, strlen( $tag ) + 2 ) === '<' . $tag . '>' ) {
			$str       = preg_replace( "/<$tag([> ])/", '<' . $tag . ' class="' . $new_classes . '"$1', $html, 1 );
			$has_class = false;
		}
	}

	if ( $has_class && preg_match( '/class="(.*?)"/s', $html ) ) {
		$str = preg_replace('/class="(.*?)"/s', 'class="\1 ' . $new_classes . '"', $html, 1 );
	}

	return $str;
}

function _sab_parse_blocks_recursively( $block ) {

	if ( empty( $block['innerContent'] ) ) {
		return $block;
	}

	// Add global class
	$block['innerContent'][0] = sab_add_block_class( $block['innerContent'][0], 'sab-block' );

	if ( 'core/columns' === $block['blockName'] ) {

		// Count columns
		$column_count             = sizeof( $block['innerBlocks'] );
		$count                    = 0;
		$total_width_remaining    = 100;
		$has_explicit_width       = false;
		$blocks_auto_width        = 0;

		foreach( $block['innerBlocks'] as $inner_key => $inner_block ) {
			++$count;

			$attrs = wp_parse_args( $inner_block['attrs'], array(
				'width' => 0
			) );

			/**
			 * Since WP 5.6 specific units might be attached
			 */
			$attrs['width'] = sab_strip_unit_from_number( $attrs['width'] );

			if ( is_numeric( $attrs['width'] ) && $attrs['width'] > 0 ) {
				$has_explicit_width = true;
				$total_width_remaining -= $attrs['width'];
			} else {
				$blocks_auto_width++;
			}

			$new_classes = sab_get_html_loop_classes( 'wp-block-column', $column_count, $count );

			$block['innerBlocks'][ $inner_key ]['innerContent'][0] = sab_add_block_class( $block['innerBlocks'][ $inner_key ]['innerContent'][0], $new_classes );

			/**
			 * Check if column is an empty div. If that is the case - add whitespace to keep floating widths.
			 */
			if ( strpos( $block['innerBlocks'][ $inner_key ]['innerContent'][0], '></div>' ) !== false ) {
				$block['innerBlocks'][ $inner_key ]['innerContent'][0] = str_replace( '></div>', '>&nbsp;</div>', $block['innerBlocks'][ $inner_key ]['innerContent'][0] );
			}

		}

		/**
		 * In case on of the columns has a specific width we'll need to manually
		 * calculate remaining widths to support floats within PDFs.
		 */
		if ( $has_explicit_width && $column_count > 1 ) {

			$total_width_remaining   = 100;
			$total_columns_remaining = $column_count;
			$column_padding          = apply_filters( 'storeabill_document_column_block_padding', 5, $block );
			$padding                 = ( ( $column_count - 1 ) * $column_padding ) / $column_count;

			foreach( $block['innerBlocks'] as $inner_key => $inner_block ) {
				$attrs = wp_parse_args( $inner_block['attrs'], array(
					'width' => 0
				) );

				/**
				 * Since WP 5.6 specific units might be attached
				 */
				$attrs['width'] = sab_strip_unit_from_number( $attrs['width'] );

				/**
				 * Seems like one of the columns does not contain explicit width
				 */
				if ( empty( $attrs['width'] ) ) {
					$attrs['width'] = floor( $total_width_remaining / ( $total_columns_remaining > 0 ? $total_columns_remaining : 1 ) );
				}

				$width = $attrs['width'];

				if ( empty( $width ) && $blocks_auto_width > 0 ) {
					$width = $total_width_remaining / $blocks_auto_width;
				}

				if ( ( $total_width_remaining - $width ) < 0 ) {
					$width = $total_width_remaining;
					$total_width_remaining = 0;
				} else {
					$total_width_remaining = $total_width_remaining - $width;
				}

				$total_columns_remaining--;

				$width -= $padding;
				$width = floor($width * 10 ) / 10;

				if ( ! empty( $width ) ) {
					$html  = $block['innerBlocks'][ $inner_key ]['innerContent'][0];
					$style = 'width: ' . $width . '%';

					if ( strpos( $html, 'style=' ) !== false ) {
						$html = preg_replace('/(<[^>]+) style=".*?"/i', '$1 style="' . esc_attr( $style ) . '"', $html );
					} else {
						$html = str_replace( '>', ' style="' . esc_attr( $style ) . '">', $html );
					}

					$block['innerBlocks'][ $inner_key ]['innerContent'][0] = $html;
				}
			}
		}

		/**
		 * Add new wrapping div which spans the columns (floats, size).
		 */
		array_splice( $block['innerContent'], 1, 0, '<div class="sab-block-columns-wrapper">' );
		$block['innerContent'][1] = sab_add_block_class( $block['innerContent'][1], 'wp-block-columns-' . $column_count );

		array_push( $block['innerContent'], '</div>' );

		array_push( $block['innerContent'], '<div class="clearfix"></div>' );

	} elseif( 'core/table' === $block['blockName'] ) {

		if ( $dom = sab_load_html_dom( $block['innerHTML'] ) ) {
			$figure  = $dom->getElementsByTagName( 'figure' )[0];
			$tbody   = $figure->getElementsByTagName( 'tbody' );

			$changed = false;

			if ( ! empty( $tbody ) ) {
				$count = 0;
				$tbody = $tbody[0];
				$trs   = $tbody->getElementsByTagName( 'tr' );

				if ( ! empty( $trs ) ) {
					$total = sizeof( $trs );

					foreach( $trs as $tr ) {
						$classes = sab_get_html_loop_classes( 'sab-table-row', $total, ++$count );
						$classes = array_filter( array_merge( (array) $tr->getAttribute( 'class' ), $classes ) );

						$tr->setAttribute( 'class', sab_print_html_classes( $classes, false ) );

						$changed = true;
					}
				}
			}

			/**
			 * Save HTML
			 */
			if ( $changed ) {
				$html = $dom->saveXML( $figure );

				$block['innerHTML']       = $html;
				$block['innerContent'][0] = $html;
			}
		}
	} elseif ( 'core/spacer' === $block['blockName'] ) {
		/**
		 * Replace spacer height with padding-top for better PDF compatibility (e.g. within footers).
		 */
		$block['innerContent'][0] = preg_replace( '/height:(\w+)/i', 'padding-top:${1}', $block['innerContent'][0] );
		$block['innerHTML'] = $block['innerContent'][0];

	} elseif( in_array( $block['blockName'], array( 'storeabill/logo', 'core/image' ) ) ) {

		$output_type = isset( $_GET['output_type'] ) ? sab_clean( $_GET['output_type'] ) : 'pdf';

		/**
		 * Do not transform URLs to paths in HTML mode.
		 */
		if ( 'html' !== $output_type ) {
			$block_content = $block['innerContent'][0];

			/**
			 * Replace image URLs with absolute paths
			 */
			$dom  = sab_load_html_dom( $block_content );
			$src  = '';
			$path = '';

			if ( $dom ) {
				$xpath = new DOMXPath( $dom );
				$src   = $xpath->evaluate("string(//img/@src)");
			} else {
				preg_match( '/src="([^"]*)"/i', $block_content, $matches );

				if ( ! empty( $matches[1] ) ) {
					$src = $matches[1];
				}
			}

			if ( ! empty( $src ) ) {
				$path = sab_get_asset_path_by_url( $src );
			}

			if ( ! empty( $path ) && @file_exists( $path ) ) {
				$block_content = str_replace( $src, $path, $block_content );
			}

			$block['innerContent'][0] = $block_content;
			$block['innerHTML'] = $block_content;
		}
	} elseif( strpos( $block['blockName'], 'storeabill/' ) !== false ) {
		if ( $sab_block = \Vendidero\StoreaBill\Editor\Helper::get_block( $block['blockName'] ) ) {
			$block = $sab_block->parse_block( $block );
		}
	}

	if ( isset( $block['innerBlocks'] ) && ! empty( $block['innerBlocks'] ) ) {
		foreach( $block['innerBlocks'] as $key => $inner_block ) {
			$block['innerBlocks'][ $key ] = _sab_parse_blocks_recursively( $inner_block );
		}
	}

	return $block;
}

function _sab_parse_block_filter( $block ) {
	global $post;

	if ( ! $post || 'document_template' !== $post->post_type ) {
		return $block;
	}

	if ( empty( $block['innerContent'] ) ) {
		return $block;
	}

	$block = _sab_parse_blocks_recursively( $block );

	return $block;
}

function _sab_maybe_render_block_filter( $html, $block ) {
	global $post;

	if ( ! $post || 'document_template' !== $post->post_type ) {
		return $html;
	}

	if ( strpos( $block['blockName'], 'storeabill/' ) !== false ) {
		if ( $sab_block = \Vendidero\StoreaBill\Editor\Helper::get_block( $block['blockName'] ) ) {
			if ( is_a( $sab_block, '\Vendidero\StoreaBill\Editor\Blocks\DynamicBlock' ) ) {
				$html = $sab_block->pre_render( $html, $block );
			}
		}
	}

	return $html;
}

function _sab_filter_render_shortcodes_start() {
	global $shortcode_tags;

	$GLOBALS['sab_original_shortcode_tags'] = $shortcode_tags;

	remove_all_shortcodes();
}

function _sab_filter_render_shortcodes_end() {
	global $shortcode_tags, $sab_original_shortcode_tags;

	if ( is_array( $sab_original_shortcode_tags ) ) {
		$shortcode_tags = $sab_original_shortcode_tags;
	}
}

/**
 * Remove all default shortcodes before rendering PDF files and re-register them afterwards
 */
add_action( 'storeabill_before_render_document', '_sab_filter_render_shortcodes_start', 5 );
add_action( 'storeabill_after_render_document', '_sab_filter_render_shortcodes_end', 5 );

add_filter( 'storeabill_blocks_html', 'wptexturize' );
add_filter( 'storeabill_blocks_html', 'convert_smilies', 20 );
add_filter( 'storeabill_blocks_html', 'shortcode_unautop' );
add_filter( 'storeabill_blocks_html', 'prepend_attachment' );

add_filter( 'storeabill_blocks_html', 'sab_blocks_convert_shortcodes', 11 );
add_filter( 'storeabill_blocks_html', 'do_shortcode', 12 ); // AFTER wpautop()
add_filter( 'storeabill_blocks_html', 'sab_blocks_convert_rgba', 13 );

add_filter( 'render_block_data', '_sab_parse_block_filter', 150, 2 );
add_filter( 'pre_render_block', '_sab_maybe_render_block_filter', 150, 2 );

function sab_get_asset_path_by_url( $src ) {
	$output_type = isset( $_GET['output_type'] ) ? sab_clean( $_GET['output_type'] ) : 'pdf';

	if ( 'pdf' !== $output_type ) {
		return $src;
	}

	// Remove site url from src - allow sub directories
	$has_ssl             = strstr( $src, 'https://' ) ? true : false;
	$site_url            = untrailingslashit( site_url() );
	$site_url            = $has_ssl ? str_replace( 'http://', 'https://', $site_url ) : str_replace( 'https://', 'http://', $site_url );
	$path                = str_replace( $site_url, '', $src );
	$wp_content_basename = basename( WP_CONTENT_DIR );
	$pdf_supports_path   = true;
	$mpdf_version         = \Vendidero\StoreaBill\PDF\MpdfRenderer::get_version();

	if ( version_compare( $mpdf_version, '8.0', '<' ) ) {
		$pdf_supports_path = false;
	}

	if ( ! apply_filters( 'storeabill_mpdf_supports_absolute_paths', $pdf_supports_path, $src ) ) {
		return $src;
	}

	if ( strpos( $path, $wp_content_basename ) !== false ) {
		$path = str_replace( '//', '/', str_replace( $wp_content_basename, '', $path ) );
		$path = untrailingslashit( WP_CONTENT_DIR ) . $path;
	}

	if ( ! @file_exists( $path ) ) {
		return $src;
	}

	return $path;
}

function sab_render_blocks( $blocks ) {
	/**
	 * Remove potential theme or plugin filters which might adjust the block HTML.
	 */
	remove_all_filters( 'render_block' );

	/**
	 * Allow third party extension to register their own render_block filters via this hook.
	 */
	do_action( 'storeabill_before_render_blocks', $blocks );

	/**
	 * Make sure that StoreaBill blocks are available
	 * and registered before rendering blocks.
	 */
	$register_blocks = \Vendidero\StoreaBill\Editor\Helper::get_blocks();
	$output          = '';
	$count           = 0;
	$total           = sizeof( $blocks );

	foreach ( $blocks as $block ) {
		$block['attrs']['renderNumber'] = ++$count;
		$block['attrs']['renderTotal']  = $total;

		$output .= render_block( $block );
	}

	return apply_filters( 'storeabill_blocks_html', $output );
}

function sab_setup_global( $name, $var ) {
	$GLOBALS[ $name ] = $var;
}

function sab_do_shortcode( $html ) {
	$html = sab_blocks_convert_shortcodes( $html );
	$html = do_shortcode( $html );

	return $html;
}

function sab_load_html_dom( $html ) {
	if ( ! class_exists( 'DOMDocument' ) ) {
		Package::log( 'Missing PHP DOM extension. Please ask your host to activate the PHP DOM extension for better render support.' );
		return false;
	}

	libxml_use_internal_errors( true );
	$dom = new DOMDocument( '1.0', 'utf-8' );
	$dom->preserveWhiteSpace  = true;
	$dom->formatOutput        = false;
	$dom->strictErrorChecking = false;

	// Load without HTML wrappers
	@$dom->loadHTML( '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $html );
	// Explicitly force utf-8 encoding
	$dom->encoding = 'UTF-8';

	libxml_clear_errors();

	return $dom;
}

function sab_blocks_convert_rgba( $content ) {
	/**
	 * mPDF does not handle alpha transparency for texts - remove transparent 0, 0, 0, 0 placeholder with explicit value
	 */
	$content = str_replace( 'rgba(0, 0, 0, 0)', 'transparent', $content );

	return $content;
}

/**
 * This function replaces HTML elements (document-shortcode) containing real content (produced by document editor)
 * with the actual shortcode (which will then be executed to dynamically insert data).
 *
 * @param $content
 *
 * @return string
 */
function sab_blocks_convert_shortcodes( $content ) {
	if ( $dom = sab_load_html_dom( $content ) ) {
		$new_selector  = 'document-shortcode';
		$finder        = new DomXPath( $dom );
		$nodes         = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $new_selector ')]");
		$nodes         = is_array( $nodes ) || is_a( $nodes, 'DOMNodeList' ) ? $nodes : array( $nodes );

		/**
		 * Force opening and closing the editor-placeholder span.
		 * Some nested elements (e.g. tables within columns) may otherwise still use the non-supported auto-closing
		 * which will lead to problems while replacing the old node content with the new content.
		 */
		$content = str_replace( '<span class="editor-placeholder"/>', '<span class="editor-placeholder"></span>', $content );

		if ( ! empty( $nodes ) ) {
			foreach( $nodes as $node ) {
				if ( $attr = $node->getAttribute( 'data-shortcode' ) ) {
					// Use LIBXML_NOEMPTYTAG to make sure elements are not auto-closed e.g. <span></span> -> <span />
					$old_html          = $node->ownerDocument->saveXML( $node );
					$shortcode         = sab_query_to_shortcode( $attr );
					$classes           = explode( ' ', $node->getAttribute( 'class' ) );
					$spans             = $node->getElementsByTagName( 'span' );
					$found_placeholder = false;

					if ( ! empty( $spans ) ) {
						foreach( $spans as $span ) {
							if ( strpos( $span->getAttribute( 'class' ), 'editor-placeholder' ) !== false ) {
								$found_placeholder = true;
								$sibling  = $span->nextSibling;
								$siblings = array();

								while( $sibling ) {
									$siblings[] = $sibling;
									$sibling    = $sibling->nextSibling;
								}

								foreach( $siblings as $sibling ) {
									// Remove everything after editor-placeholder
									$span->parentNode->removeChild( $sibling );
								}

								$new_node = $dom->createTextNode( $shortcode );

								// Insert placeholder right after editor-placeholder
								try {
									$span->parentNode->insertBefore( $new_node, $span->nextSibling );
								} catch( \Exception $e ) {
									$span->parentNode->appendChild( $new_node );
								}

								// Remove editor node
								$span->parentNode->removeChild( $span );

								break;
							}
						}
					}

					// In case we didnt find the placeholder: Remove inner HTML and add shortcode instead.
					if ( ! $found_placeholder ) {
						$shortcode_node = $dom->createTextNode( $shortcode );
						$children       = $node->childNodes;

						foreach ( $children as $child ) {
							$node->removeChild( $child );
						}

						$node->appendChild( $shortcode_node );
					}

					// Mark the shortcode as converted to prevent replacing already executed shortcode data.
					$classes = array_diff( $classes, array( 'document-shortcode' ) );
					$node->setAttribute( 'class', trim( implode( ' ', $classes ) . ' shortcode-content' ) );

					$node->removeAttribute( 'data-shortcode' );
					$node->removeAttribute( 'data-tooltip' );
					$node->removeAttribute( 'contenteditable' );

					/**
					 * This replacement is necessary as we need to explicitly store editor-placeholder with opening and closing tags
					 * so that the RichText content does not encapsulate the content within.
					 */
					$old_html   = str_replace( array( '<span class="editor-placeholder"/>' ), array( '<span class="editor-placeholder"></span>' ), $old_html );
					$node_html  = $node->ownerDocument->saveXML( $node );
					$node_html  = str_replace( '<span class="editor-placeholder"/>', '<span class="editor-placeholder"></span>', $node_html );

					$content    = str_replace( $old_html, $node_html, $content );



					/**
					 * Replace with special char decoded as fallback
					 */
					$old_html   = htmlspecialchars_decode( $old_html );
					$content    = str_replace( $old_html, $node_html, $content );

					/**
					 * Replace with entity decoded html as fallback
					 */
					$old_html = sab_convert_html_shortcode_content( $old_html );
					$content  = str_replace( $old_html, $node_html, $content );
				}
			}
		}
	} else {
		preg_match_all('/data-shortcode="([^"]*)"/', $content, $matches );

		/**
		 * Ugly hack to replace shortcodes with actual shortcodes for rendering.
		 * Will only be used as a fallback in case dom extension does not exist.
		 * Does not respect inner tags.
		 */
		if ( ! empty( $matches[1] ) ) {
			foreach( $matches[1] as $query ) {
				$shortcode = sab_query_to_shortcode( $query );
				$query     = preg_quote( $query );
				$content   = preg_replace( "#data-shortcode=\"$query\">(.*?)<span class=\"editor-placeholder\"></span>([\s\S]*?)</span>#", '>' . $shortcode . '</span>', $content );
			}
		}
	}

	return $content;
}

function sab_convert_html_shortcode_content( $content ) {
	$trans = get_html_translation_table( HTML_ENTITIES, ENT_NOQUOTES );
	unset( $trans['<'], $trans['>'] );
	$content = sab_decode_entities_full( strtr( $content, $trans ) );

	return $content;
}

/**
 * Helper function for drupal_html_to_text().
 *
 * Calls helper function for HTML 4 entity decoding.
 * Per: http://www.lazycat.org/software/html_entity_decode_full.phps
 */
function sab_decode_entities_full( $string, $quotes = ENT_COMPAT, $charset = 'ISO-8859-1' ) {
	return html_entity_decode( preg_replace_callback( '/&([a-zA-Z][a-zA-Z0-9]+);/', 'sab_convert_entity', $string ), $quotes, $charset );
}

/**
 * Helper function for decode_entities_full().
 *
 * This contains the full HTML 4 Recommendation listing of entities, so the default to discard
 * entities not in the table is generally good. Pass false to the second argument to return
 * the faulty entity unmodified, if you're ill or something.
 * Per: http://www.lazycat.org/software/html_entity_decode_full.phps
 */
function sab_convert_entity( $matches, $destroy = true ) {
	static $table = array( 'quot' => '&#34;','amp' => '&#38;','lt' => '&#60;','gt' => '&#62;','OElig' => '&#338;','oelig' => '&#339;','Scaron' => '&#352;','scaron' => '&#353;','Yuml' => '&#376;','circ' => '&#710;','tilde' => '&#732;','ensp' => '&#8194;','emsp' => '&#8195;','thinsp' => '&#8201;','zwnj' => '&#8204;','zwj' => '&#8205;','lrm' => '&#8206;','rlm' => '&#8207;','ndash' => '&#8211;','mdash' => '&#8212;','lsquo' => '&#8216;','rsquo' => '&#8217;','sbquo' => '&#8218;','ldquo' => '&#8220;','rdquo' => '&#8221;','bdquo' => '&#8222;','dagger' => '&#8224;','Dagger' => '&#8225;','permil' => '&#8240;','lsaquo' => '&#8249;','rsaquo' => '&#8250;','euro' => '&#8364;','fnof' => '&#402;','Alpha' => '&#913;','Beta' => '&#914;','Gamma' => '&#915;','Delta' => '&#916;','Epsilon' => '&#917;','Zeta' => '&#918;','Eta' => '&#919;','Theta' => '&#920;','Iota' => '&#921;','Kappa' => '&#922;','Lambda' => '&#923;','Mu' => '&#924;','Nu' => '&#925;','Xi' => '&#926;','Omicron' => '&#927;','Pi' => '&#928;','Rho' => '&#929;','Sigma' => '&#931;','Tau' => '&#932;','Upsilon' => '&#933;','Phi' => '&#934;','Chi' => '&#935;','Psi' => '&#936;','Omega' => '&#937;','alpha' => '&#945;','beta' => '&#946;','gamma' => '&#947;','delta' => '&#948;','epsilon' => '&#949;','zeta' => '&#950;','eta' => '&#951;','theta' => '&#952;','iota' => '&#953;','kappa' => '&#954;','lambda' => '&#955;','mu' => '&#956;','nu' => '&#957;','xi' => '&#958;','omicron' => '&#959;','pi' => '&#960;','rho' => '&#961;','sigmaf' => '&#962;','sigma' => '&#963;','tau' => '&#964;','upsilon' => '&#965;','phi' => '&#966;','chi' => '&#967;','psi' => '&#968;','omega' => '&#969;','thetasym' => '&#977;','upsih' => '&#978;','piv' => '&#982;','bull' => '&#8226;','hellip' => '&#8230;','prime' => '&#8242;','Prime' => '&#8243;','oline' => '&#8254;','frasl' => '&#8260;','weierp' => '&#8472;','image' => '&#8465;','real' => '&#8476;','trade' => '&#8482;','alefsym' => '&#8501;','larr' => '&#8592;','uarr' => '&#8593;','rarr' => '&#8594;','darr' => '&#8595;','harr' => '&#8596;','crarr' => '&#8629;','lArr' => '&#8656;','uArr' => '&#8657;','rArr' => '&#8658;','dArr' => '&#8659;','hArr' => '&#8660;','forall' => '&#8704;','part' => '&#8706;','exist' => '&#8707;','empty' => '&#8709;','nabla' => '&#8711;','isin' => '&#8712;','notin' => '&#8713;','ni' => '&#8715;','prod' => '&#8719;','sum' => '&#8721;','minus' => '&#8722;','lowast' => '&#8727;','radic' => '&#8730;','prop' => '&#8733;','infin' => '&#8734;','ang' => '&#8736;','and' => '&#8743;','or' => '&#8744;','cap' => '&#8745;','cup' => '&#8746;','int' => '&#8747;','there4' => '&#8756;','sim' => '&#8764;','cong' => '&#8773;','asymp' => '&#8776;','ne' => '&#8800;','equiv' => '&#8801;','le' => '&#8804;','ge' => '&#8805;','sub' => '&#8834;','sup' => '&#8835;','nsub' => '&#8836;','sube' => '&#8838;','supe' => '&#8839;','oplus' => '&#8853;','otimes' => '&#8855;','perp' => '&#8869;','sdot' => '&#8901;','lceil' => '&#8968;','rceil' => '&#8969;','lfloor' => '&#8970;','rfloor' => '&#8971;','lang' => '&#9001;','rang' => '&#9002;','loz' => '&#9674;','spades' => '&#9824;','clubs' => '&#9827;','hearts' => '&#9829;','diams' => '&#9830;','nbsp' => '&#160;','iexcl' => '&#161;','cent' => '&#162;','pound' => '&#163;','curren' => '&#164;','yen' => '&#165;','brvbar' => '&#166;','sect' => '&#167;','uml' => '&#168;','copy' => '&#169;','ordf' => '&#170;','laquo' => '&#171;','not' => '&#172;','shy' => '&#173;','reg' => '&#174;','macr' => '&#175;','deg' => '&#176;','plusmn' => '&#177;','sup2' => '&#178;','sup3' => '&#179;','acute' => '&#180;','micro' => '&#181;','para' => '&#182;','middot' => '&#183;','cedil' => '&#184;','sup1' => '&#185;','ordm' => '&#186;','raquo' => '&#187;','frac14' => '&#188;','frac12' => '&#189;','frac34' => '&#190;','iquest' => '&#191;','Agrave' => '&#192;','Aacute' => '&#193;','Acirc' => '&#194;','Atilde' => '&#195;','Auml' => '&#196;','Aring' => '&#197;','AElig' => '&#198;','Ccedil' => '&#199;','Egrave' => '&#200;','Eacute' => '&#201;','Ecirc' => '&#202;','Euml' => '&#203;','Igrave' => '&#204;','Iacute' => '&#205;','Icirc' => '&#206;','Iuml' => '&#207;','ETH' => '&#208;','Ntilde' => '&#209;','Ograve' => '&#210;','Oacute' => '&#211;','Ocirc' => '&#212;','Otilde' => '&#213;','Ouml' => '&#214;','times' => '&#215;','Oslash' => '&#216;','Ugrave' => '&#217;','Uacute' => '&#218;','Ucirc' => '&#219;','Uuml' => '&#220;','Yacute' => '&#221;','THORN' => '&#222;','szlig' => '&#223;','agrave' => '&#224;','aacute' => '&#225;','acirc' => '&#226;','atilde' => '&#227;','auml' => '&#228;','aring' => '&#229;','aelig' => '&#230;','ccedil' => '&#231;','egrave' => '&#232;','eacute' => '&#233;','ecirc' => '&#234;','euml' => '&#235;','igrave' => '&#236;','iacute' => '&#237;','icirc' => '&#238;','iuml' => '&#239;','eth' => '&#240;','ntilde' => '&#241;','ograve' => '&#242;','oacute' => '&#243;','ocirc' => '&#244;','otilde' => '&#245;','ouml' => '&#246;','divide' => '&#247;','oslash' => '&#248;','ugrave' => '&#249;','uacute' => '&#250;','ucirc' => '&#251;','uuml' => '&#252;','yacute' => '&#253;','thorn' => '&#254;','yuml' => '&#255;'
	);
	if ( isset( $table[ $matches[1] ] ) ) {
		return $table[ $matches[1] ];
	} else {
		return $destroy ? '' : $matches[0];
	}
}

function sab_calculate_discount_percentage( $before_discount, $discount ) {
	$percent = 0;

	if ( $before_discount > 0 && $discount > 0 ) {
		$percent = ( ( $discount ) * 100 ) / $before_discount;
	}

	return sab_format_decimal( $percent, wc_get_price_decimals() );
}

/**
 * Clean all output buffers.
 *
 * Can prevent errors, for example: transfer closed with 3 bytes remaining to read.
 */
function sab_clean_buffers() {
	if ( ob_get_level() ) {
		$levels = ob_get_level();
		for ( $i = 0; $i < $levels; $i++ ) {
			@ob_end_clean(); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
		}
	} else {
		@ob_end_clean(); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
	}
}

function sab_get_absolute_file_path( $file ) {
	// Optional wrapper(s).
	$regExp = '%^(?<wrappers>(?:[[:print:]]{2,}://)*)';

	// Optional root prefix.
	$regExp .= '(?<root>(?:[[:alpha:]]:/|/)?)';

	// Actual path.
	$regExp .= '(?<path>(?:[[:print:]]*))$%';

	$parts       = [];
	$is_absolute = false;

	if ( preg_match( $regExp, $file, $parts ) ) {
		if ( '' !== $parts['root'] ) {
			$is_absolute = true;
		}
	}

	// If the file is relative, prepend upload dir.
	if ( $file && ! $is_absolute ) {
		$uploads = \Vendidero\StoreaBill\UploadManager::get_upload_dir();

		if ( false === $uploads['error'] ) {
			$file = $uploads['basedir'] . "/$file";
		}
	}

	return $file;
}

function sab_get_export_types() {
	return apply_filters( 'storeabill_export_types', array(
		'csv'  => _x( 'CSV', 'storeabill-core', 'woocommerce-germanized-pro' ),
		'file' => _x( 'ZIP', 'storeabill-core', 'woocommerce-germanized-pro' )
	) );
}

function sab_get_address_salutation( $args = array() ) {
	$args = wp_parse_args( $args, array(
		'title'      => '',
		'first_name' => '',
		'last_name'  => ''
	) );

	$default_format = apply_filters( 'storeabill_default_address_salutation_format', '{first_name}' );
	$format         = $default_format;

	foreach( $args as $type => $value ) {
		$format = str_replace( "{{$type}}", $value, $format );
	}

	return apply_filters( 'storeabill_address_salutation', preg_replace('/\s+/', ' ', $format ), $args );
}

function sab_help_tip( $tip, $allow_html = true ) {
	if ( $allow_html ) {
		$tip = wc_sanitize_tooltip( $tip );
	} else {
		$tip = esc_attr( $tip );
	}

	return '<span class="sab-tip sab-help-tip woocommerce-help-tip" data-tip="' . $tip . '"></span>';
}

/**
 * Run a MySQL transaction query, if supported.
 *
 * @since 2.5.0
 * @param string $type Types: start (default), commit, rollback.
 * @param bool   $force use of transactions.
 */
function sab_transaction_query( $type = 'start', $force = false ) {
	global $wpdb;

	$wpdb->hide_errors();

	sab_maybe_define_constant( 'SAB_USE_TRANSACTIONS', true );

	if ( ( defined( 'SAB_USE_TRANSACTIONS' ) && true === SAB_USE_TRANSACTIONS ) || $force ) {
		switch ( $type ) {
			case 'commit':
				$wpdb->query( 'COMMIT' );
				break;
			case 'rollback':
				$wpdb->query( 'ROLLBACK' );
				break;
			default:
				$wpdb->query( 'START TRANSACTION' );
				break;
		}
	}
}

/**
 * @param string $type E.g. render or sync.
 *
 * @return boolean
 */
function sab_allow_deferring( $type = 'render' ) {
	$allow_defer = true;

	if ( 'render' === $type || 'auto' === $type ) {
		if ( is_admin() && current_user_can( 'manage_storeabill' ) ) {
			$allow_defer = false;
		}
	}

	if ( apply_filters( 'storeabill_disable_deferring', false ) ) {
		$allow_defer = false;
	}

	return apply_filters( "storeabill_allow_{$type}_deferring", $allow_defer );
}

function sab_load_data_store( $name ) {
	$name = substr( $name, 0, 4 ) !== 'sab_' ? 'sab_' . $name : $name;

	return WC_Data_Store::load( $name );
}

function sab_get_logger() {
	return wc_get_logger();
}

function sab_switch_to_site_locale() {
	if ( function_exists( 'switch_to_locale' ) ) {
		switch_to_locale( get_locale() );

		// Filter on plugin_locale so load_plugin_textdomain loads the correct locale.
		add_filter( 'plugin_locale', 'get_locale' );

		Package::load_plugin_textdomain();

		do_action( 'storeabill_switched_to_site_locale' );
	}
}

function sab_restore_locale() {
	if ( function_exists( 'restore_previous_locale' ) ) {
		restore_previous_locale();

		// Remove filter.
		remove_filter( 'plugin_locale', 'get_locale' );

		Package::load_plugin_textdomain();

		do_action( 'storeabill_restored_locale' );
	}
}

function sab_switch_to_email_locale( $email, $lang = false ) {
	do_action( 'storeabill_switch_email_locale', $email, $lang );
}

function sab_restore_email_locale( $email ) {
	do_action( 'storeabill_restore_email_locale', $email );
}

function sab_add_number_precision( $value, $round = true ) {
	return wc_add_number_precision( $value, $round );
}

function sab_remove_number_precision( $value ) {
	return wc_remove_number_precision( $value );
}

function sab_get_barcode_types() {
	/**
	 * @see https://mpdf.github.io/what-else-can-i-do/barcodes.html
	 */
	return apply_filters( 'storeabill_barcode_types', array(
		'C39'   => _x( 'Code 39', 'storeabill-barcode-type', 'woocommerce-germanized-pro' ),
		'C93'   => _x( 'Code 93', 'storeabill-barcode-type', 'woocommerce-germanized-pro' ),
		'C128A' => _x( 'Code 128', 'storeabill-barcode-type', 'woocommerce-germanized-pro' ),
		'QR'    => _x( 'QR Code', 'storeabill-barcode-type', 'woocommerce-germanized-pro' )
	) );
}

function sab_get_base_bank_account_data( $field = '' ) {
	$data = \Vendidero\StoreaBill\Countries::get_base_bank_account_data();

	if ( empty( $field ) ) {
		return $data;
	} else {
		return array_key_exists( $field, $data ) ? $data[ $field ] : '';
	}
}

function sab_get_wildcard_postcodes( $postcode, $country = '' ) {
	return wc_get_wildcard_postcodes( $postcode, $country );
}

function sab_get_random_key( $max_length = -1 ) {
	$key       = array( ABSPATH, time() );
	$constants = array( 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT', 'SECRET_KEY' );

	foreach ( $constants as $constant ) {
		if ( defined( $constant ) ) {
			$key[] = constant( $constant );
		}
	}

	shuffle( $key );

	$key = md5( serialize( $key ) );

	if ( $max_length > 0 ) {
		$key = substr( $key, 0, $max_length );
	}

	return $key;
}