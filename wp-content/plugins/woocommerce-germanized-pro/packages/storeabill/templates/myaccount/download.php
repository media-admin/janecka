<?php
/**
 * @var \Vendidero\StoreaBill\Document\Document[] $documents
 * @var string $document_title
 *
 * @version 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>

<div class="sab-documents-download">
	<h3><?php printf( _x( 'Download %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $document_title ); ?></h3>

	<?php foreach( $documents as $document ) : ?>
		<a class="button button-document-download" href="<?php echo $document->get_download_url( apply_filters( 'storeabill_woo_customer_force_document_download', false ) ); ?>" target="_blank"><?php printf( _x( 'Download %s', 'storeabill-core', 'woocommerce-germanized-pro' ), apply_filters( 'storeabill_woo_customer_document_name', $document->get_title(), $document ) ); ?></a>
	<?php endforeach; ?>
</div>
