<?php
/**
 * Admin View: Invoice Settings
 */

if ( ! defined( 'ABSPATH' ) )
	exit;

$template = sab_get_default_document_template( 'post_document' );

if ( ! $template ) {
    return;
}
?>
<p style="margin-top: 0;">
    <?php _e( 'Seamlessly preview and adjust the layout of your automatically generated PDF files.', 'woocommerce-germanized-pro' ); ?>
</p>
<p class="wc-gzdp-legal-page-button-wrapper">
    <a class="button button-primary" target="_blank" href="<?php echo esc_url( $template->get_edit_url() ); ?>"><?php echo __( 'Adjust template', 'woocommerce-germanized-pro' ); ?></a>
	<?php if ( $template->has_custom_first_page() ) : ?>
        <a class="button button-secondary" target="_blank" href="<?php echo esc_url( $template->get_first_page()->get_edit_url() ); ?>"><?php echo __( 'Adjust first page template', 'woocommerce-germanized-pro' ); ?></a>
        <a class="delete" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'wc_gzdp_remove_legal_first_page' ), admin_url( 'admin-post.php' ) ), 'wc-gzdp-remove-legal-first-page' ) ); ?>"><?php echo __( 'Remove first page template', 'woocommerce-germanized-pro' ); ?></a>
	<?php else : ?>
        <a class="button button-secondary" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'wc_gzdp_add_legal_first_page' ), admin_url( 'admin-post.php' ) ), 'wc-gzdp-add-legal-first-page' ) ); ?>"><?php echo __( 'Create custom first page template', 'woocommerce-germanized-pro' ); ?></a>
	<?php endif; ?>
</p>