<?php
/**
 * All products block.
 *
 * @package WooCommerce\Blocks
 */

namespace Vendidero\StoreaBill\Editor\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * AllProducts class.
 */
class DocumentStyles extends DynamicBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'document-styles';

	public function render( $attributes = array(), $content = '' ) {
		return '';
	}
}
