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
			/**
			 * Make sure to check whether total types are valid (e.g. custom line item types), legacy vouchers
			 */
			if ( is_a( $document, '\Vendidero\StoreaBill\Invoice\Invoice' ) ) {
				$line_item_types            = $document->get_line_item_types();
				$is_legacy_template         = version_compare( $document->get_template()->get_version(), '1.9.0', '<' );
				$is_legacy_voucher_template = $is_legacy_template && sizeof( $document->get_items( 'voucher' ) ) > 0;

				if ( $is_legacy_voucher_template && ! in_array( 'voucher', $line_item_types ) ) {
					$total_index             = sizeof( $block['innerBlocks'] );
					$has_voucher_total_block = false;

					foreach( $block['innerBlocks'] as $index => $inner_block ) {
						if ( isset( $inner_block['attrs']['totalType'] ) ) {
							if ( in_array( $inner_block['attrs']['totalType'], array( 'vouchers', 'voucher' ) ) ) {
								$has_voucher_total_block = true;
							} elseif ( 'total' === $inner_block['attrs']['totalType'] ) {
								$total_index = $index;
							}
						}
					}

					if ( ! $has_voucher_total_block ) {
						array_splice( $block['innerBlocks'], $total_index, 0, array(
							array(
								'blockName' => 'storeabill/item-total-row',
								'attrs'     => array(
									'totalType'   => 'vouchers',
									'heading'     => sprintf( esc_attr_x( 'Voucher: %s (Multipurpose)', 'storeabill-item-total', 'woocommerce-germanized-pro' ), '[document_total data="code" total_type="vouchers"]' ),
									'hideIfEmpty' => true,
								),
								'innerBlocks'  => array(),
								'innerHTML'    => '',
								'innerContent' => array(),
							)
						) );
					}
				}
			}

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
