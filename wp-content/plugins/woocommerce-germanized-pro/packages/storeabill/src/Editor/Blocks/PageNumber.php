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
class PageNumber extends DynamicBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'page-number';

	public function get_attributes() {
		return array(
			'align' => $this->get_schema_align(),
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

		if ( ! isset( $GLOBALS['document'] ) ) {
			return $content;
		}

		$attributes   = $this->parse_attributes( $attributes );
		$replacements = array( '<!--current_page_no-->', '<!--total_pages_no-->' );

		return $this->wrap( $this->replace_placeholders( $content, $replacements ), $attributes );
	}

	protected function replace_placeholders( $content, $replacements ) {
		if ( empty( $content ) ) {
			$content = '<p class="wp-block-storeabill-' . esc_attr( $this->block_name ) . 'sab-block">' . sprintf( _x( 'Page %1$s of %2$s', 'storeabill-core', 'woocommerce-germanized-pro' ), '{page}', '{total}' ) . '</p>';
		}

		return str_replace( array( '{current_page_no}', '{total_pages}' ), $replacements, $content );
	}
}
