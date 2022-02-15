<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @var string $document_type
 * @var \Vendidero\StoreaBill\Document\DefaultTemplate[] $templates
 * @var \Vendidero\StoreaBill\Editor\Templates\Template[] $editor_templates
 * @var integer $default_template_id
 */
?>
<div class="sab-document-templates" data-document-type="<?php echo esc_attr( $document_type ); ?>">
    <div class="notice-wrapper"></div>
    <table class="widefat sab-document-templates-table sab-settings-table fixed striped page" cellspacing="0">
        <thead>
            <tr>
                <th class="column-name"><?php echo esc_html_x(  'Template', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></th>
                <th class="column-default"><?php echo esc_html_x(  'Default', 'storeabill-core', 'woocommerce-germanized-pro' ); ?> <?php echo sab_help_tip( sprintf( _x( 'This indicates whether the template is currently used to render %s or not.', 'storeabill-core', 'woocommerce-germanized-pro' ), sab_get_document_type_label( $document_type, 'plural' ) ) ); ?></th>
                <th class="column-actions"><?php echo esc_html_x(  'Actions', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach( $templates as $template ) : ?>
                <tr id="sab-document-template-<?php echo esc_attr( $template->get_id() ); ?>" class="sab-document-template" data-id="<?php echo esc_attr( $template->get_id() ); ?>">
                    <td class="column-name"><a href="<?php echo esc_url( $template->get_edit_url() ); ?>" target="_blank"><?php echo $template->get_title( 'view', false ); ?></a></td>
                    <td class="column-default">
                        <span class="sab-activation-status status-<?php echo ( $template->get_id() == $default_template_id ? 'enabled' : 'disabled' ); ?>"></span>
                    </td>
                    <td class="column-actions">
                        <a class="button sab-action-button sab-tip edit" data-tip="<?php echo esc_attr( _x( 'Edit', 'storeabill-core', 'woocommerce-germanized-pro' ) ); ?>" href="<?php echo esc_url( $template->get_edit_url() ); ?>" target="_blank"><?php _ex( 'Edit', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></a>
                        <a class="button sab-action-button sab-tip preview" data-tip="<?php echo esc_attr( _x( 'Preview', 'storeabill-core', 'woocommerce-germanized-pro' ) ); ?>" href="<?php echo esc_url( $template->get_preview_url() ); ?>" target="_blank"><?php _ex( 'Preview', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></a>
                        <?php if ( ! $template->has_custom_first_page() ) : ?>
                            <a class="button sab-tip sab-action-button create-first-page" data-tip="<?php echo esc_attr( _x( 'Create custom first page template', 'storeabill-core', 'woocommerce-germanized-pro' ) ); ?>" href="#"><?php _ex( 'Create custom first page template', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></a>
                        <?php endif; ?>
                        <a class="button sab-action-button sab-tip copy" data-tip="<?php echo esc_attr( _x( 'Copy', 'storeabill-core', 'woocommerce-germanized-pro' ) ); ?>" href="#"><?php _ex( 'Copy', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></a>
                        <a class="button sab-action-button sab-tip delete" data-tip="<?php echo esc_attr( _x( 'Delete', 'storeabill-core', 'woocommerce-germanized-pro' ) ); ?>" href="#"><?php _ex( 'Delete', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></a>
                    </td>
                </tr>
                <?php if ( $template->has_custom_first_page() && ( $child_template = $template->get_first_page() ) ) : ?>
                    <tr class="child sab-document-template" id="document-template-<?php echo esc_attr( $child_template->get_id() ); ?>" data-id="<?php echo esc_attr( $child_template->get_id() ); ?>">
                        <td class="column-name"><a href="<?php echo esc_url( $child_template->get_edit_url() ); ?>" target="_blank">— <?php echo $child_template->get_title( 'view', false ); ?></a></td>
                        <td class="column-default">&ndash;</td>
                        <td class="column-actions">
                            <a class="button sab-action-button sab-tip edit" data-tip="<?php echo esc_attr( _x( 'Edit', 'storeabill-core', 'woocommerce-germanized-pro' ) ); ?>" href="<?php echo esc_url( $child_template->get_edit_url() ); ?>" target="_blank"><?php _ex( 'Edit', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></a>
                            <a class="button sab-action-button sab-tip preview" data-tip="<?php echo esc_attr( _x( 'Preview', 'storeabill-core', 'woocommerce-germanized-pro' ) ); ?>" href="<?php echo esc_url( $child_template->get_preview_url() ); ?>" target="_blank"><?php _ex( 'Preview', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></a>
                            <a class="button sab-action-button sab-tip delete" data-tip="<?php echo esc_attr( _x( 'Delete', 'storeabill-core', 'woocommerce-germanized-pro' ) ); ?>" href="#"><?php _ex( 'Delete', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></a>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
        <tr>
            <th colspan="2" class="sab-document-template-default-actions">
                <label for="document_template_default"><?php _ex( 'Default', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></label>
                <select name="document_template_default" class="sab-document-template-default">
                    <?php foreach( sab_get_document_templates( $document_type ) as $template ) : ?>
                        <option value="<?php echo esc_attr( $template->get_id() ); ?>" <?php selected( $default_template_id, $template->get_id() ); ?>><?php echo esc_html( $template->get_title( 'view', false ) ); ?></option>
                    <?php endforeach; ?>
                </select>
	            <?php echo sab_help_tip( sprintf( _x( 'Choose a default template which will be used when creating new %s.', 'storeabill-core', 'woocommerce-germanized-pro' ), sab_get_document_type_label( $document_type, 'plural' ) ) ); ?>
            </th>
            <th colspan="1">
                <a title="<?php echo esc_attr_x( 'Create a new template', 'storeabill-core', 'woocommerce-germanized-pro' ); ?>" class="button button-primary <?php echo ( sizeof( $editor_templates ) > 1 ? 'thickbox' : 'sab-add-template' ); ?>" href="<?php echo ( sizeof( $editor_templates ) > 1 ? '#TB_inline?&width=600&height=250&inlineId=sab-add-editor-template-modal' : '#' ); ?>"><?php _ex( 'Add new', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></a>
            </th>
        </tr>
        </tfoot>
    </table>

    <?php if ( sizeof( $editor_templates ) > 1 ) :
	    add_thickbox();
        ?>
        <div id="sab-add-editor-template-modal" style="display:none;" class="sab-add-editor-template">
            <div class="sab-add-editor-template-content" data-document-type="<?php echo esc_attr( $document_type ); ?>">
                <div class="sab-editor-template-inner">
                    <p class="description"><?php _ex( 'Choose one of the following templates to start.', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></p>

                    <div class="sab-editor-template-choose">
		                <?php foreach( $editor_templates as $editor_template ) : ?>
                            <div class="sab-editor-template-preview <?php echo ( \Vendidero\StoreaBill\Editor\Helper::get_default_editor_template( $document_type ) === $editor_template::get_name() ? 'active' : '' ); ?>" data-template="<?php echo esc_attr( $editor_template::get_name() ); ?>">
                                <h5 class="sab-editor-template-title"><?php echo $editor_template::get_title(); ?></h5>
                                <img class="sab-editor-template-screenshot" src="<?php echo esc_url( $editor_template::get_screenshot_url() ); ?>" />
                            </div>
		                <?php endforeach; ?>
                    </div>
                </div>
                <div class="sab-editor-template-footer">
                    <p class="button-wrapper">
                        <a class="button button-primary sab-editor-template-choose-submit sab-add-template" href="#"><?php _ex( 'Create template', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></a>
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>