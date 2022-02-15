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
class DocumentDate extends DynamicBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'document-date';

	public function get_attributes() {
		return array(
			'align'     => $this->get_schema_align(),
			'format'    => $this->get_schema_string( sab_date_format() ),
			'dateType'  => $this->get_schema_string( 'date' ),
		);
	}

	protected function replace_placeholder( $content, $replacement ) {
		if ( empty( $content ) ) {
			$content = '<p class="wp-block-storeabill-' . esc_attr( $this->block_name ) . ' sab-block">{content}</p>';
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
		$output           = '[document data="' . esc_attr( $this->attributes['dateType'] ) . '" format="' . esc_attr( $this->attributes['format'] ) . '"]';

		return $this->replace_placeholder( $this->content, $output );
	}
}
