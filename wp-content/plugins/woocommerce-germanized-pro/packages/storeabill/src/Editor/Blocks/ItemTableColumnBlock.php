<?php
/**
 * Item table column abstract block.
 */
namespace Vendidero\StoreaBill\Editor\Blocks;

use Vendidero\StoreaBill\Package;

defined( 'ABSPATH' ) || exit;

/**
 * AbstractBlock class.
 */
abstract class ItemTableColumnBlock extends DynamicBlock {

	public function get_attributes() {
		return array(
			'className'       => $this->get_schema_string(),
			'textColor'       => $this->get_schema_string(),
			'customTextColor' => $this->get_schema_string(),
			'renderTotal'     => $this->get_schema_number( 1 ),
			'renderNumber'    => $this->get_schema_number( 1 ),
		);
	}

	protected function replace_placeholder( $content, $replacement ) {
		if ( empty( $content ) || strpos( $content, '{content}' ) === false ) {
			$content = '<p class="wp-block-storeabill-' . esc_attr( $this->block_name ) . ' sab-block-item-content sab-block">{content}</p>';
		}

		return str_replace( '{content}', $replacement, $content );
	}

	protected function get_item_total_getter( $prefix, $inc_tax = true, $discount_total_type = '' ) {
		$getter = 'get_' . $prefix;

		if ( 'before_discounts' === $discount_total_type ) {
			$getter .= '_subtotal';

			if ( strpos( $prefix, 'total' ) !== false ) {
				$getter = 'get_' . str_replace( 'total', 'subtotal', $prefix );
			}
		}

		if ( ! $inc_tax ) {
			if ( strpos( $prefix, '_total' ) !== false ) {
				$getter = str_replace( '_total', '', $getter );
			}

			$getter .= '_net';
		}

		return $getter;
	}

	protected function wrap( $output, $attributes ) {
		$attributes = wp_parse_args( $attributes, array(
			'renderNumber' => 1,
			'renderTotal'  => 1,
		) );

		$output_sanitized = trim( preg_replace( '/\s+/', '', $output ) );

		/**
		 * Do not wrap empty content.
		 */
		if ( empty( $output_sanitized ) ) {
			return '';
		}

		$render_class = $attributes['renderNumber'] === 1 ? 'item-data-first' : 'item-data-' . $attributes['renderNumber'];

		if ( $attributes['renderNumber'] === $attributes['renderTotal'] ) {
			$render_class .= ' item-data-last';
		}

		$render_class .= ' ' . esc_attr( 'sab-item-data-' . $this->block_name );

		return '<table class="item-data ' . $render_class . '"><tr><td>' . $output . '</td></tr></table>';
	}
}