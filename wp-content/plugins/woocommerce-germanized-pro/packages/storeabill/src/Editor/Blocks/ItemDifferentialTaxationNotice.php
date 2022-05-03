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
class ItemDifferentialTaxationNotice extends ItemTableColumnBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'item-differential-taxation-notice';

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
		$document = $GLOBALS['document'];

		$attributes        = $this->parse_attributes( $attributes );
		$output            = '';
		$is_reverse_charge = ( is_a( $document, '\Vendidero\StoreaBill\Invoice\Invoice' ) && $document->is_reverse_charge() ) ? true : false;

		/**
		 * Show notice only for products subject to differential taxation (in case there is no reverse charge available)
		 */
		if ( is_a( $document_item, '\Vendidero\StoreaBill\Invoice\ProductItem' ) && $document_item->has_differential_taxation() && ! $is_reverse_charge ) {
			$output = $content;
		}

		if ( ! empty( $output ) ) {
			return $this->wrap( wp_kses_post( $output ), $attributes );
		} else {
			return $output;
		}
	}
}
