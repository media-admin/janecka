<?php
/**
 * Item Totals block.
 */
namespace Vendidero\StoreaBill\Editor\Blocks;

use Vendidero\StoreaBill\Document\Document;

defined( 'ABSPATH' ) || exit;

/**
 * AllProducts class.
 */
class ItemTotals extends DynamicBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'item-totals';

	public function get_attributes() {
		return array(
			'className'      => $this->get_schema_string(),
			'hasDenseLayout' => $this->get_schema_boolean( false ),
		);
	}

	public function pre_render( $content, $block ) {
		self::maybe_setup_document();

		if ( ! isset( $GLOBALS['document'] ) ) {
			return $content;
		}

		/**
		 * @var Document $document
		 */
		$document = $GLOBALS['document'];

		if ( ! is_a( $document, '\Vendidero\StoreaBill\Interfaces\TotalsContainable' ) ) {
			return $content;
		}

		$attributes = $this->parse_attributes( $block['attrs'] );
		$classes    = array( ( $attributes['hasDenseLayout'] ? 'sab-item-totals-has-dense-layout' : '' ) );

		if ( ! empty( $block['innerBlocks'] ) ) {
			$content = sab_get_template_html( 'blocks/item-totals/totals.php', array(
				'totals'  => $block,
				'classes' => $classes,
			) );
		}

		return $content;
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
