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

class Barcode extends DynamicBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'barcode';

	public function get_attributes() {
		return array(
			'barcodeType' => array(
				'type'    => 'string',
				'enum'    => array_keys( sab_get_barcode_types() ),
				'default' => 'C39',
			),
			'codeType'    => $this->get_schema_string( 'document?data=order_number' ),
			'size'        => $this->get_schema_string( "normal" ),
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

		/**
		 * @var Document $document
		 */
		$document         = $GLOBALS['document'];
		$this->attributes = $this->parse_attributes( $attributes );
		$this->content    = $content;
		$code             = '';
		$barcode_type     = $this->attributes['barcodeType'];

		/**
		 * Support EPC QR Code: https://en.wikipedia.org/wiki/EPC_QR_code
		 */
		if ( 'epc' === $this->attributes['codeType'] ) {
			if ( is_a( $document, '\Vendidero\StoreaBill\Interfaces\Invoice' ) ) {
				$bank_account = sab_get_base_bank_account_data();

				if ( ! empty( $bank_account['iban'] ) && ! empty( $bank_account['bic'] ) ) {
					if ( apply_filters( "storeabill_invoice_display_girocode", ( ! $document->is_paid() && $document->get_total() > 0 ), $document ) ) {
						$subject      = $document->get_title();
						$total        = $document->get_total();
						$currency     = is_callable( array( $document, 'get_currency' ) ) ? $document->get_currency() : 'EUR';
						$currency     = empty( $currency ) ? 'EUR' : $currency;

						$code         = 'BCD\n001\n1\nSCT\n' . $bank_account['bic'] . '\n' . $bank_account['holder'] . '\n' . $bank_account['iban'] . '\n' . strtoupper( $currency ) . wc_format_decimal( $total, 2 ) . '\n\n' . $subject;

						// Force barcode type to be QR
						$barcode_type = 'QR';
					}
				}
			}
		} else {
			$shortcode_query  = sab_query_to_shortcode( $this->attributes['codeType'] );
			$code             = trim( do_shortcode( $shortcode_query ) );
		}

		$styles = sab_generate_block_styles( $this->attributes );
		$size   = 1;

		if ( 'small' === $this->attributes['size'] ) {
			$size = 0.8;
		} elseif( 'medium' === $this->attributes['size'] ) {
			$size = 1.5;
		} elseif( 'big' === $this->attributes['size'] ) {
			$size = 2;
		}

		if ( ! empty( $code ) ) {
			$this->content = '<barcode disableborder="1" error="M" style="' . sab_print_styles( $styles, false ) . '" class="barcode" code="' . esc_attr( $code ) . '" type="' . esc_attr( $barcode_type ) .'" size="' . esc_attr( $size ) . '" />';
		}

		return $this->wrap( $this->content, $this->attributes );
	}

	protected function wrap( $output, $attributes ) {
		if ( $this->is_render_api_request() ) {
			return $output;
		}

		$classes   = sab_generate_block_classes( $attributes );
		$styles    = sab_generate_block_styles( $attributes );
		$classes[] = 'sab-barcode';
		$classes[] = 'sab-barcode-' . $this->attributes['barcodeType'];

		return '<div class="' . sab_print_html_classes( $classes, false ) . '" style="' . sab_print_styles( $styles, false ) . '">' . $output . '</div>';
	}
}
