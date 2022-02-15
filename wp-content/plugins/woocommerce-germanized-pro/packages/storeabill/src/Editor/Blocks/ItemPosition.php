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
class ItemPosition extends ItemTableColumnBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'item-position';

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
		$document       = $GLOBALS['document'];
		$attributes     = $this->parse_attributes( $attributes );
		$output         = apply_filters( "storeabill_{$document->get_type()}_item_position_shortcode", '[document_item data="current_position"]', $attributes, $document_item, $document );
		$shortcode_data = trim( do_shortcode( $output ) );

		if ( empty( $shortcode_data ) ) {
			return '';
		} else {
			return $this->wrap( $this->replace_placeholder( $content, $output ), $attributes );
		}
	}
}
