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
<footer id="footer-first-page">
	<?php echo sab_render_blocks( $document->get_template()->get_first_page()->get_footer_blocks() ); ?>
</footer>
