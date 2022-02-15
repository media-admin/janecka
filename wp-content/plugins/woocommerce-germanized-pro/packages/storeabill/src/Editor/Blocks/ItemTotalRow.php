<?php
/**
 * All products block.
 *
 * @package WooCommerce\Blocks
 */

namespace Vendidero\StoreaBill\Editor\Blocks;

use Vendidero\StoreaBill\Document\Document;
use Vendidero\StoreaBill\Document\Item;
use Vendidero\StoreaBill\Interfaces\Summable;
use Vendidero\StoreaBill\Invoice\Invoice;
use Vendidero\StoreaBill\Package;

defined( 'ABSPATH' ) || exit;

/**
 * AllProducts class.
 */
class ItemTotalRow extends DynamicBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'item-total-row';

	public function get_available_shortcodes() {
		return array(
			array(
				'title'     => _x( 'Tax Rate', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'shortcode' => 'document_total?data=rate&total_type=taxes',
			)
		);
	}

	public function get_attributes() {
		return array(
			'totalType'             => $this->get_schema_string( 'total' ),
			'heading'               => $this->get_schema_string( false ),
			'content'               => $this->get_schema_string( '{total}' ),
			'borderColor'           => $this->get_schema_string(),
			'className'             => $this->get_schema_string(),
			'customBorderColor'     => $this->get_schema_string(),
			'textColor'             => $this->get_schema_string(),
			'customTextColor'       => $this->get_schema_string(),
			'fontSize'              => $this->get_schema_string( '' ),
			'hideIfEmpty'           => $this->get_schema_boolean( false ),
			'renderNumber'          => $this->get_schema_number( 1 ),
			'renderTotal'           => $this->get_schema_number( 1 ),
			'borders'               => array(
				'type'    => 'array',
				'default' => [],
				'items'   => array(
					'type' => 'string'
				),
			),
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
		$document   = $GLOBALS['document'];
		$attributes = $this->parse_attributes( $attributes );
		$attributes['totalType'] = sab_map_invoice_total_type( $attributes['totalType'], $document );

		$classes    = array_merge( sab_generate_block_classes( $attributes ), array( 'item-total' ) );
		$styles     = sab_generate_block_styles( $attributes );

		$document_totals = $document->get_totals( $attributes['totalType'] );

		$total_content   = $attributes['content'];
		$classes[]       = 'sab-item-total-row';
		$classes[]       = 'sab-item-total-row-' . str_replace( '_', '-', $attributes['totalType'] );

		foreach( $attributes['borders'] as $border ) {
			$classes[] = 'sab-item-total-row-border-' . $border;
		}

		if ( ! empty( $document_totals ) ) {
			$count   = 0;
			$content = '';

			foreach( $document_totals as $total ) {

				if ( false !== $attributes['heading'] ) {
					$total->set_label( $attributes['heading'] );
				}

				/**
				 * Remove the actual net tax rate in case only one tax rate is included.
				 */
				if ( 'nets' === $attributes['totalType'] && 1 === sizeof( $document_totals ) ) {
					$label = sab_remove_placeholder_tax_rate( $total->get_label() );
					$total->set_label( $label );
				}

				/**
				 * In case a fee has a negative total amount - force a discount label.
				 */
				if ( 'fee' === $attributes['totalType'] && $total->get_total() < 0 ) {
					$total->set_label( _x( 'Discount', 'storeabill-core', 'woocommerce-germanized-pro' ) );
				} elseif( 'fees' === $attributes['totalType'] && $total->get_total() < 0 ) {
					$total->set_label( _x( 'Discount: %s', 'storeabill-core', 'woocommerce-germanized-pro' ) );
				}

				/**
				 * Skip for empty amounts.
				 */
				if ( ( true === $attributes['hideIfEmpty'] && empty( $total->get_total() ) ) || apply_filters( "storeabill_hide_{$document->get_type()}_total_row", false, $attributes, $total, $document ) ) {
					continue;
				}

				$count++;

				/**
				 * Remove border top styles
				 */
				if ( $count > 1 ) {
					$classes = array_diff( $classes, array( 'sab-item-total-row-border-top', 'has-border-top' ) );

					if ( $count < sizeof( $document_totals ) ) {
						$classes = array_diff( $classes, array( 'sab-item-total-row-border-bottom', 'has-border-bottom' ) );
					}
				}

				do_action( "storeabill_setup_{$document->get_type()}_total_row", $total, $document );

				$total_classes = array_merge( $classes, sab_get_html_loop_classes( 'sab-item-total-row', $attributes['renderTotal'], $count ) );
				Package::setup_document_total( $total );

				$total_html_content = sab_get_template_html( 'blocks/item-totals/total.php', array(
					'total'           => $total,
					'formatted_label' => $total->get_formatted_label(),
					'formatted_total' => str_replace(  '{total}', $total->get_formatted_total(), $total_content ),
					'classes'         => $total_classes,
					'styles'          => $styles,
				) );

				$content .= sab_do_shortcode( $total_html_content );
			}
		}

		return $content;
	}
}
