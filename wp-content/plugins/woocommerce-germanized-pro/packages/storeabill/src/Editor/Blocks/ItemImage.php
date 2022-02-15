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
class ItemImage extends ItemTableColumnBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'item-image';

	public function get_attributes() {
		return array(
			'className'       => $this->get_schema_string(),
			'customWidth'     => $this->get_schema_number( 75 ),
			'renderTotal'     => $this->get_schema_number( 1 ),
			'renderNumber'    => $this->get_schema_number( 1 ),
		);
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
		$document_item  = $GLOBALS['document_item'];
		$attributes     = $this->parse_attributes( $attributes );
		$src            = $document_item->get_image_url( '', true );
		$is_placeholder = strpos( $src, 'placeholder.png' ) !== false;
		$width          = absint( $attributes['customWidth'] );
		$src            = sab_get_asset_path_by_url( $src );

		return $this->wrap( '<img style="width: ' . esc_attr( $width ) . 'px;" class="sab-document-item-img ' . ( $is_placeholder ? 'is-placeholder' : '' ) . '" src="' . esc_url( $src ) . '" />', $attributes );
	}
}
