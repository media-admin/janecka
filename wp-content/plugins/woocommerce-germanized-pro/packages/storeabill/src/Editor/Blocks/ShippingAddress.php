<?php
/**
 * All products block.
 *
 * @package WooCommerce\Blocks
 */

namespace Vendidero\StoreaBill\Editor\Blocks;

use Vendidero\StoreaBill\Document\Document;
use Vendidero\StoreaBill\Document\Item;

defined( 'ABSPATH' ) || exit;

/**
 * AllProducts class.
 */
class ShippingAddress extends DynamicBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'shipping-address';

	public function get_attributes() {
		return array(
			'align'               => $this->get_schema_align(),
			'hideIfEqualsBilling' => $this->get_schema_boolean( false ),
		);
	}

	protected function replace_placeholder( $content, $replacement ) {
		if ( empty( $content ) ) {
			$content = '<address class="address-content">{content}</address>';
		}

		return str_replace( '{content}', $replacement, $content );
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

		if ( ! isset( $GLOBALS['document'] ) ) {
			return $content;
		}

		/**
		 * @var Document $document
		 */
		$document         = $GLOBALS['document'];
		$this->attributes = $this->parse_attributes( $attributes );
		$this->content    = $content;

		if ( is_a( $document, '\Vendidero\StoreaBill\Invoice\Invoice' ) ) {

			if ( $this->attributes['hideIfEqualsBilling'] && ! $document->has_differing_shipping_address() ) {
				return '';
			}

			$output        = $document->get_formatted_shipping_address();
			$this->content = $this->replace_placeholder( $content, $output );
		}

		return $this->content;
	}
}
