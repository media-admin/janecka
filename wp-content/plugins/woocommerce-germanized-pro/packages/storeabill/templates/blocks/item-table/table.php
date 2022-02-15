<?php
/**
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit;

/**
 * @var \Vendidero\StoreaBill\Document\Document $document
 */
global $document;
?>
<table class="sab-item-table <?php sab_print_html_classes( $classes ); ?>" autosize="1" style="<?php sab_print_styles( $styles ); ?>">
	<thead>
		<tr class="sab-item-table-row sab-item-table-row-header">
			<?php foreach( $columns as $column ) : ?>
				<th class="sab-item-table-column-header <?php sab_print_html_classes( array_merge( $column['classes'], $column['header_classes'] ) ); ?>" style="<?php sab_print_styles( array_merge( $column['header_styles'], array( 'width' => $column['width'] ) ) ); ?>"><?php echo $column['heading']; ?></th>
			<?php endforeach; ?>
		</tr>
	</thead>
	<tbody>
		<?php
            $items     = apply_filters( "storeabill_{$document->get_type()}_item_table_items", $document->get_items( $document->get_line_item_types() ), $document );
            $count     = 0;
            $item_size = sizeof( $items );

            foreach( $items as $item ) :
                $count++;
	            $item->set_current_position( $count );
            ?>
                <?php sab_get_template( 'blocks/item-table/row.php', array(
                    'document'      => $document,
                    'count'         => $count,
                    'item'          => $item,
                    'item_size'     => $item_size,
                    'columns'       => $columns
                ) ); ?>
            <?php endforeach; ?>
	</tbody>
</table>