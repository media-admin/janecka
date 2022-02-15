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
class ItemField extends ItemTableColumnBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'item-field';

	public function get_attributes() {
		return array(
			'className'             => $this->get_schema_string(),
			'borderColor'           => $this->get_schema_string(),
			'customBorderColor'     => $this->get_schema_string(),
			'textColor'             => $this->get_schema_string(),
			'customTextColor'       => $this->get_schema_string(),
			'backgroundColor'       => $this->get_schema_string(),
			'fontSize'              => $this->get_schema_string( '' ),
			'customFontSize'        => $this->get_schema_string( '' ),
			'placeholder'           => $this->get_schema_string(),
			'customBackgroundColor' => $this->get_schema_string(),
			'renderTotal'           => $this->get_schema_number( 1 ),
			'renderNumber'          => $this->get_schema_number( 1 ),
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
		$classes        = array_merge( sab_generate_block_classes( $attributes ), array( 'sab-item-field' ) );
		$classes        = array_diff( $classes, array( 'without-has-border-color' ) );
		$styles         = sab_generate_block_styles( $attributes );

		$attributes['placeholder'] = empty( $attributes['placeholder'] ) ? '&nbsp;' : $attributes['placeholder'];

		return $this->wrap( '<table style="' . sab_print_styles( $styles, false ) . '" class="' . sab_print_html_classes( $classes, false ) . '"><tr><td class="placeholder">' . $attributes['placeholder'] . '</td></tr></table>', $attributes );
	}
}
