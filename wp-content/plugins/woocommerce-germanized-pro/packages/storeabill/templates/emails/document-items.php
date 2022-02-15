<?php
/**
 * @var \Vendidero\StoreaBill\Document\Item[] $items
 *
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit;

$text_align  = is_rtl() ? 'right' : 'left';
$margin_side = is_rtl() ? 'left' : 'right';

foreach ( $items as $item_id => $item ) :
	if ( ! apply_filters( 'storeabill_email_document_item_visible', true, $item ) ) {
		continue;
	}
	?>
	<tr class="document_item">
		<?php foreach( $columns as $column ) : ?>
			<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;">
				<?php if ( 'name' === $column ) : ?>
					<?php echo wp_kses_post( apply_filters( 'storeabill_email_document_item_name', $item->get_name(), $item, $plain_text ) ); ?>
				<?php elseif( 'quantity' === $column ) : ?>
					<?php echo wp_kses_post( apply_filters( 'storeabill_email_document_item_quantity', $item->get_quantity(), $item, $plain_text ) ); ?>
				<?php else : ?>
					<?php
					$getter = "get_$column";
					if ( is_callable( array( $item, $getter ) ) ) : ?>
						<?php echo wp_kses_post( apply_filters( 'storeabill_email_document_item_' . $column, $document->$getter(), $item, $plain_text ) ); ?>
					<?php else: ?>
						<?php echo wp_kses_post( apply_filters( 'storeabill_email_document_item_' . $column, '', $item, $plain_text ) ); ?>
					<?php endif; ?>
				<?php endif; ?>

				<?php do_action( "storeabill_email_after_document_item_column_{$column}", $item, $document, $plain_text ); ?>
			</td>
		<?php endforeach; ?>
	</tr>
<?php endforeach; ?>
