<?php
/**
 * Item Table Column block.
 */
namespace Vendidero\StoreaBill\Editor\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * AllProducts class.
 */
class ItemTableColumn extends DynamicBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'item-table-column';

	public function get_attributes() {
		return array(
			'verticalAlignment'      => $this->get_schema_vertical_align( 'top' ),
			'width'                  => $this->get_schema_number( false ),
			'className'              => $this->get_schema_string(),
			'align'                  => $this->get_schema_align( 'left' ),
			'headingTextColor'       => $this->get_schema_string(),
			'headingBackgroundColor' => $this->get_schema_string(),
			'headingFontSize'        => $this->get_schema_string( '' ),
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
		return $content;
	}
}
