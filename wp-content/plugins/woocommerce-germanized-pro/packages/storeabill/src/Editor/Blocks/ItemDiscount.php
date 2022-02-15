<?php
/**
 * Item Discount block.
 */
namespace Vendidero\StoreaBill\Editor\Blocks;

use Vendidero\StoreaBill\Document\Document;
use Vendidero\StoreaBill\Document\Item;
use Vendidero\StoreaBill\Package;

defined( 'ABSPATH' ) || exit;

/**
 * AllProducts class.
 */
class ItemDiscount extends ItemTableColumnBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'item-discount';

	public function get_attributes() {
		$attributes = parent::get_attributes();

		$attributes['discountType'] = array(
			'type'        => 'string',
			'enum'        => array( 'absolute', 'percentage' ),
			'default'     => 'absolute',
		);

		$attributes['showPricesIncludingTax'] = $this->get_schema_boolean( true );
		$attributes['hideIfEmpty'] = $this->get_schema_boolean( false );

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
		$document = $GLOBALS['document'];

		$attributes = $this->parse_attributes( $attributes );
		$output     = 0;

		if ( is_a( $document_item, '\Vendidero\StoreaBill\Interfaces\Discountable' ) ) {

			if ( 'percentage' === $attributes['discountType'] ) {
				$output = $document_item->get_discount_percentage();
			} else {
				$getter = $this->get_item_total_getter( 'discount_total', $attributes['showPricesIncludingTax'] );

				if ( is_callable( array( $document_item, $getter ) ) ) {
					$output = $document_item->$getter();
				}
			}
		} else {
			$output = 0;
		}

		/**
		 * Skip for empty discounts.
		 */
		if ( true === $attributes['hideIfEmpty'] && empty( $output ) ) {
			return '';
		}

		if ( 'percentage' === $attributes['discountType'] ) {
			$output = sab_format_percentage( $output );
		} else {
			if ( is_callable( array( $document, 'get_formatted_price' ) ) ) {
				$output = $document->get_formatted_price( $output, 'discount' );
			} else {
				$output = sab_format_price( $output );
			}
		}

		return $this->wrap( $this->replace_placeholder( $content, $output ), $attributes );
	}
}
