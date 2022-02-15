<?php
/**
 * All products block.
 *
 * @package WooCommerce\Blocks
 */

namespace Vendidero\StoreaBill\Editor\Blocks;

use Vendidero\StoreaBill\Document\Document;
use Vendidero\StoreaBill\Document\Item;
use Vendidero\StoreaBill\Package;

defined( 'ABSPATH' ) || exit;

/**
 * AllProducts class.
 */
class ItemMeta extends ItemTableColumnBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'item-meta';

	public function get_attributes() {
		$attributes = parent::get_attributes();
		$attributes['metaType']    = $this->get_schema_string();
		$attributes['hideIfEmpty'] = $this->get_schema_boolean( true );

		return $attributes;
	}

	/**
	 * Append frontend scripts when rendering the Product Categories List block.
	 *
	 * @param array  $attributes Block attributes. Default empty array.
	 * @param string $content    Block content. Default empty string.
	 * @return string Rendered block type output.
	 */
	public function render( $attributes = array(), $content = '' ) {
		self::maybe_setup_document();
		self::maybe_setup_document_item();

		if ( ! isset( $GLOBALS['document_item'] ) ) {
			return $content;
		}

		/**
		 * @var Item $document_item
		 */
		$document_item = $GLOBALS['document_item'];
		/**
		 * @var Document $document
		 */
		$document      = $GLOBALS['document'];

		$attributes    = $this->parse_attributes( $attributes );
		$meta_type     = $attributes['metaType'];

		/**
		 * Construct a shortcode
		 */
		$output         = apply_filters( "storeabill_{$document->get_type()}_item_meta_shortcode", '[document_item data="' . esc_attr( $meta_type ) . '"]', $meta_type, $document, $document_item );
		$shortcode_data = trim( do_shortcode( $output ) );

		if ( empty( $shortcode_data ) && true === $attributes['hideIfEmpty'] ) {
			return '';
		} else {
			return $this->wrap( $this->replace_placeholder( $content, $output ), $attributes );
		}
	}
}
