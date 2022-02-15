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
<table class="sab-item-totals-wrapper" autosize="1" style="page-break-inside: avoid">
	<tbody>
		<tr>
			<td class="sab-item-totals-wrapper-first"></td>
			<td class="sab-item-totals-wrapper-last">
				<table class="sab-item-totals <?php sab_print_html_classes( $classes ); ?>">
					<tbody>
						<?php echo sab_render_blocks( $totals['innerBlocks'] ); ?>
					</tbody>
				</table>
			</td>
		</tr>
	</tbody>
</table>