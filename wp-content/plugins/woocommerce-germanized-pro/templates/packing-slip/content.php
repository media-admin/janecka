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
<div id="content" class="page-content" role="main">
	<?php echo sab_render_blocks( $document->get_template()->get_content_blocks() ); ?>
</div>