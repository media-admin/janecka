<?php
/**
 * Item table block.
 */
namespace Vendidero\StoreaBill\Editor\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * AllProducts class.
 */
class ItemTable extends DynamicBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'item-table';

	public function get_attributes() {
		return array(
			'borderColor'                  => $this->get_schema_string(),
			'className'                    => $this->get_schema_string(),
			'customBorderColor'            => $this->get_schema_string(),
			'customHeadingBackgroundColor' => $this->get_schema_string(),
			'showPricesIncludingTax'       => $this->get_schema_boolean( true ),
			'hasDenseLayout'               => $this->get_schema_boolean( false ),
			'borders'                      => array(
				'type'    => 'array',
				'default' => ['horizontal'],
				'items'   => array(
					'type' => 'string'
				),
			)
		);
	}

	public function pre_render( $content, $block ) {
		$columns          = array();
		$count            = 0;
		$auto_width_count = 0;
		$total_width      = 0;

		$attributes     = $this->parse_attributes( $block['attrs'] );
		$classes        = sab_generate_block_classes( $attributes, $block['innerContent'][0] );
		$border_classes = sab_filter_html_classes( $classes, 'border' );
		$styles         = sab_generate_block_styles( $attributes );

		/**
		 * Generate column data.
		 */
		foreach( $block['innerBlocks'] as $column ) {
			$count++;

			if ( $block_type = \WP_Block_Type_Registry::get_instance()->get_registered( $column['blockName'] ) ) {

				$attributes = is_array( $column['attrs'] ) ? $column['attrs'] : array();
				$attributes = $block_type->prepare_attributes_for_render( $attributes );

				$column_classes    = ( ! empty( $attributes['className'] ) ) ? implode( ' ', $attributes['className'] ) : array();
				$column_classes[]  = 'sab-item-table-column';
				$column_classes[]  = 'sab-item-table-column-' . $attributes['align'];
				$column_classes    = array_merge( $column_classes, sab_get_html_loop_classes( 'sab-item-table-column', sizeof( $block['innerBlocks'] ), $count ) );
				$column_classes    = array_unique( array_merge( $column_classes, $border_classes ) );
				$heading_styles    = sab_generate_block_styles( $attributes, array( 'headingTextColor' => 'textColor', 'headingBackgroundColor' => 'backgroundColor', 'headingFontSize' => 'fontSize' ) );
				$column_styles     = array();

				/**
				 * Copy global border color styles to headings and rows to support custom border colors
				 */
				if ( isset( $styles['border-color'] ) && ! empty( $styles['border-color'] ) ) {
					$column_styles['border-color']  = $styles['border-color'];
					$heading_styles['border-color'] = $styles['border-color'];
				}

				$new_column = array(
					'width'          => ( ! empty( $attributes['width'] ) ) ? $attributes['width'] . '%' : '',
					'plain_width'    => ( ! empty( $attributes['width'] ) ) ? $attributes['width'] : '',
					'heading'        => '',
					'classes'        => $column_classes,
					'innerBlocks'    => $column['innerBlocks'],
					'header_styles'  => $heading_styles,
					'styles'         => $column_styles,
					'header_classes' => sab_get_html_classes( $column['innerContent'][0] )
				);

				if ( $dom = sab_load_html_dom( $column['innerHTML'] ) ) {
					$spans = $dom->getElementsByTagName( 'span' );

					if ( ! empty( $spans ) ) {
						$span                  = $spans[0];
						$new_column['heading'] = $span->ownerDocument->saveXML( $span );
					}
				} else {
					preg_match( '#<\s*?span\b[^>]* class="item-column-heading-text">(.*?)</span\b[^>]*>#s', $column['innerHTML'], $matches );

					if ( ! empty( $matches ) ) {
						$new_column['heading'] = $matches[0];
					}
				}

				if ( empty( $new_column['plain_width'] ) ) {
					// Auto calculate width
					$auto_width_count++;
				} else {
					$total_width += $new_column['plain_width'];
				}

				$columns[] = $new_column;
			}
		}

		if ( $auto_width_count > 0 ) {
			$width_remaining = 100 - $total_width;
			$auto_width      = '';

			if ( $width_remaining > 0 ) {
				$auto_width = round( $width_remaining / $auto_width_count, 3 );
			}

			foreach( $columns as $key => $column ) {
				if ( empty( $column['plain_width'] ) ) {
					$columns[ $key ]['plain_width'] = $auto_width;
					$columns[ $key ]['width']       = ! empty( $auto_width ) ? $auto_width . '%' : '';
				}
			}
		}

		if ( ! empty( $columns ) ) {
			self::maybe_setup_document();

			if ( ! isset( $GLOBALS['document'] ) ) {
				return $content;
			}

			$content = sab_get_template_html( 'blocks/item-table/table.php', array(
				'document' => $GLOBALS['document'],
				'columns'  => $columns,
				'classes'  => $classes,
				'styles'   => $styles,
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