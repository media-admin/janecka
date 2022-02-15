<?php
/**
 * Item Tax Rate block.
 */
namespace Vendidero\StoreaBill\Editor\Blocks;

use Vendidero\StoreaBill\Document\Document;
use Vendidero\StoreaBill\Document\Item;
use Vendidero\StoreaBill\Package;

defined( 'ABSPATH' ) || exit;

/**
 * AllProducts class.
 */
class ItemTaxRate extends ItemTableColumnBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'item-tax-rate';

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
		$attributes    = $this->parse_attributes( $attributes );

		if ( is_a( $document_item, '\Vendidero\StoreaBill\Interfaces\Taxable' ) ) {
			$output    = '';
			$count     = 0;

			if ( $document_item->has_taxes() ) {
				foreach( $document_item->get_tax_rates() as $tax_rate ) {
					$output .= ( $count > 1 ? apply_filters( 'storeabill_tax_rate_separator', ' | ' ) : '' ) . $tax_rate->get_formatted_percentage_html();
				}
			} else {
				$output = sab_format_tax_rate_percentage( 0, array( 'html' => true ) );
			}

			return $this->wrap( $this->replace_placeholder( $content, $output ), $attributes );
		}

		return $content;
	}
}
