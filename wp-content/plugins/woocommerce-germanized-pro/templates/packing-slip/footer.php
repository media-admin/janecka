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
<footer id="footer">
	<?php echo sab_render_blocks( $document->get_template()->get_footer_blocks() ); ?>
</footer>
