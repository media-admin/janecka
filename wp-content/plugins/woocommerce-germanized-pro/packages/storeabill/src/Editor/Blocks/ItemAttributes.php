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
class ItemAttributes extends ItemTableColumnBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'item-attributes';

	public function get_attributes() {
		return array(
			'className'       => $this->get_schema_string(),
			'textColor'       => $this->get_schema_string(),
			'customTextColor' => $this->get_schema_string(),
			'customAttributes' =>  array(
				'type'    => 'array',
				'default' => array(),
			),
			'renderTotal'     => $this->get_schema_number( 1 ),
			'renderNumber'    => $this->get_schema_number( 1 )
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
		$document_item   = $GLOBALS['document_item'];
		$attributes      = $this->parse_attributes( $attributes );
		$classes         = array_merge( sab_generate_block_classes( $attributes ), array( 'item-attributes', 'sab-item-attributes' ) );
		$styles          = sab_generate_block_styles( $attributes );
		$current_style   = 'list';

		if ( strpos( $attributes['className'], 'is-style-attribute-line' ) !== false ) {
			$current_style = 'line';
		}

		if ( 'list' === $current_style ) {
			/**
			 * As MPDF does not support styling block elements within tables we need to
			 * wrap every list element within its own table (Ugly hack indeed).
			 */
			$output = sab_display_item_attributes( $document_item, array(
				'before'       => '<table class="sab-item-attributes-table ' . sab_print_html_classes( $classes, false ) . '" ><tr class="first"><td style="' . sab_print_styles( $styles, false ) . '"><ul class="sab-item-attributes"><li>',
				'after'        => '</li></ul></td></tr></table>',
				'separator'    => '</li></ul></td></tr><tr><td style="' . sab_print_styles( $styles, false ) . '"><ul class="sab-item-attributes"><li>',
				'echo'         => false,
			) );
		} else {
			$separator = apply_filters( 'storeabill_document_item_attributes_line_separator', ', ' );

			$output = sab_display_item_attributes( $document_item, array(
				'before'       => '<span style="' . sab_print_styles( $styles, false ) . '">',
				'after'        => '</span>',
				'separator'    => $separator . ' <span style="' . sab_print_styles( $styles, false ) . '">',
				'echo'         => false,
			) );
		}

		return $this->wrap( $output, $attributes );
	}
}
