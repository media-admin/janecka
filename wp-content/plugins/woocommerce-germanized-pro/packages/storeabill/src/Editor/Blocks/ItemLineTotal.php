<?php
/**
 * Item Line Total block.
 */
namespace Vendidero\StoreaBill\Editor\Blocks;

use Vendidero\StoreaBill\Document\Document;
use Vendidero\StoreaBill\Document\Item;
use Vendidero\StoreaBill\Package;

defined( 'ABSPATH' ) || exit;

/**
 * AllProducts class.
 */
class ItemLineTotal extends ItemTableColumnBlock {

	public function get_attributes() {
		$attributes = parent::get_attributes();

		$attributes['discountTotalType'] = array(
			'type'    => 'string',
			'enum'    => array( 'before_discounts', 'after_discounts' ),
			'default' => 'before_discounts',
		);

		$attributes['showPricesIncludingTax'] = $this->get_schema_boolean( true );

		return $attributes;
	}

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'item-line-total';

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

		if ( is_a( $document_item, '\Vendidero\StoreaBill\Interfaces\Summable' ) ) {
			$getter = $this->get_item_total_getter( 'total', $attributes['showPricesIncludingTax'], $attributes['discountTotalType'] );
			$output = '';

			if ( is_callable( array( $document_item, $getter ) ) ) {
				$output = $document_item->$getter();

				if ( is_callable( array( $document, 'get_formatted_price' ) ) ) {
					$output = $document->get_formatted_price( $output );
				} else {
					$output = sab_format_price( $output );
				}
			}

			return $this->wrap( $this->replace_placeholder( $content, $output ), $attributes );
		}

		return $content;
	}
}
