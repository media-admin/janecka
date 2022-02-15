<?php
if(!defined('ABSPATH')) {
    die();
}
?>
<?php
/** @var $post */
/** @var string $random */
$random = uniqid();
?>
<div class="product_variations">
    <h4 style="margin-top: 20px;"><?php esc_html_e('Product Variations', 'wp_all_export_plugin'); ?>
        <a href="#help" class="wpallexport-help"
         style="position: relative; top: 1px; left: 2px; margin-left: 0;"
         title="<?php wp_kses_post(__('WooCommerce stores each product variation as a separate product in the database, along with a parent product to tie all of the variations together.<br/><br/>If the product title is \'T-Shirt\', then the parent product will be titled \'T-Shirt\', and in the database each size/color combination will be a separate product with a title like \'Variation #23 of T-Shirt\'.', 'wp_all_export_plugin')); ?>">?</a></h4>
    <div class="input" style="display: inline-block; width: 100%;">
        <div>
            <label>
                <input type="radio" class="export_variations <?php if (PMXE_EDITION != 'paid') {
                    echo "variations_disabled";
                } ?>"
                    <?php if(XmlExportEngine::get_addons_service()->isWooCommerceProductAddonActive()) { echo "disabled"; }?>
                       value="<?php echo esc_attr(XmlExportEngine::VARIABLE_PRODUCTS_EXPORT_PARENT_AND_VARIATION); ?>" <?php if ($post['export_variations'] == XmlExportEngine::VARIABLE_PRODUCTS_EXPORT_PARENT_AND_VARIATION && PMXE_EDITION == 'paid' && XmlExportEngine::get_addons_service()->isWooCommerceAddonActive()) { ?> checked="checked"  <?php } ?>
                       name="<?php echo esc_attr($random)?>_export_variations"/><?php esc_html_e("Export product variations and their parent products", 'wp_all_export_plugin'); ?>
            </label>
            <div class="sub-options sub-options-<?php echo esc_attr(XmlExportEngine::VARIABLE_PRODUCTS_EXPORT_PARENT_AND_VARIATION);?>">
                <label style="display: block; margin-bottom: 8px;">
                    <input
                           <?php if(XmlExportEngine::get_addons_service()->isWooCommerceProductAddonActive()) { echo "disabled"; }?>
                           type="radio"
                           name="<?php echo esc_attr($random); ?>_export_variations_title_1"
                           value="<?php echo esc_attr(XmlExportEngine::VARIATION_USE_PARENT_TITLE); ?>"
                        <?php if($post['export_variations_title'] == XmlExportEngine::VARIATION_USE_PARENT_TITLE) {?>
                            checked="checked"
                        <?php }?>
                           class="export_variations_title"><?php esc_html_e("Product variations use the parent product title", 'wp_all_export_plugin');?>
                </label>
                <div class="clear"></div>
                <label style="display: block; margin-bottom: 8px;">
                    <input type="radio"
                        <?php if(XmlExportEngine::get_addons_service()->isWooCommerceProductAddonActive()) { echo "disabled"; }?>
                           name="<?php echo esc_attr($random); ?>_export_variations_title_1"
                           value="<?php echo esc_attr(XmlExportEngine::VARIATION_USE_DEFAULT_TITLE); ?>"
                        <?php if($post['export_variations_title'] == XmlExportEngine::VARIATION_USE_DEFAULT_TITLE) { ?>
                            checked="checked"
                        <?php } ?>
                           class="export_variations_title"><?php esc_html_e("Product variations use the default variation product title", 'wp_all_export_plugin'); ?>
                </label>
            </div>
        </div>
        <div class="clear"></div>
        <div style="margin: 6px 0;">
            <label>
                <input type="radio" class="export_variations"
                    <?php if(XmlExportEngine::get_addons_service()->isWooCommerceProductAddonActive()) { echo "disabled"; }?>
                       value="<?php echo esc_attr(XmlExportEngine::VARIABLE_PRODUCTS_EXPORT_VARIATION); ?>" <?php if ($post['export_variations'] == XmlExportEngine::VARIABLE_PRODUCTS_EXPORT_VARIATION || PMXE_EDITION == 'free') { ?> checked="checked" <?php } ?>
                          name="<?php echo $random; ?>_export_variations"/><?php esc_html_e("Only export product variations", 'wp_all_export_plugin'); ?>
            </label>
            <div class="sub-options sub-options-<?php echo esc_attr(XmlExportEngine::VARIABLE_PRODUCTS_EXPORT_VARIATION); ?>">
                <label style="display: block; margin-bottom: 8px;">
                    <input type="radio"
                        <?php if(XmlExportEngine::get_addons_service()->isWooCommerceProductAddonActive()) { echo "disabled"; }?>
                           name="<?php echo esc_attr($random); ?>_export_variations_title_2"
                              value="<?php echo esc_attr(XmlExportEngine::VARIATION_USE_PARENT_TITLE); ?>"
                              <?php if($post['export_variations_title'] == XmlExportEngine::VARIATION_USE_PARENT_TITLE) {?>
                                  checked="checked"
                              <?php }?>
                              class="export_variations_title"><?php esc_html_e("Product variations use the parent product title", 'wp_all_export_plugin'); ?>
                </label>
                <div class="clear"></div>
                <label>
                    <input type="radio"
                        <?php if(XmlExportEngine::get_addons_service()->isWooCommerceProductAddonActive()) { echo "disabled"; }?>

                           name="<?php echo esc_attr($random); ?>_export_variations_title_2"
                              value="<?php echo esc_attr(XmlExportEngine::VARIATION_USE_DEFAULT_TITLE); ?>"
                              <?php if($post['export_variations_title'] == XmlExportEngine::VARIATION_USE_DEFAULT_TITLE) {?>
                                  checked="checked"
                              <?php } ?>
                              class="export_variations_title"><?php esc_html_e("Product variations use the default variation product title", 'wp_all_export_plugin'); ?>
                </label>
            </div>
        </div>
        <div class="clear"></div>
        <div style="margin: 6px 0;">
            <label>
                <input type="radio" class="export_variations <?php if (PMXE_EDITION != 'paid') {
                    echo "variations_disabled";
                } ?>"
                    <?php if(XmlExportEngine::get_addons_service()->isWooCommerceProductAddonActive()) { echo "disabled"; echo " checked"; }?>

                       value="<?php echo esc_attr(XmlExportEngine::VARIABLE_PRODUCTS_EXPORT_PARENT); ?>" <?php if ($post['export_variations'] == XmlExportEngine::VARIABLE_PRODUCTS_EXPORT_PARENT && PMXE_EDITION == 'paid') { ?> checked="checked" <?php } ?>
                          name="<?php echo $random?>_export_variations"/><?php esc_html_e("Only export parent products", 'wp_all_export_plugin'); ?>
            </label>
        </div>
        <?php if( XmlExportEngine::get_addons_service()->isWooCommerceProductAddonActive() ){ ?>
            <div class="wpallexport-free-edition-notice" style="padding: 20px; margin-bottom: 10px; display: block;">
                <p>The WooCommerce Export Add-On is required to export variations.</p>
                <a style="margin: 1em 0;" class="upgrade_link" target="_blank" href="http://www.wpallimport.com/portal/discounts?utm_source=export-plugin-pro&utm_medium=upgrade-notice&utm_campaign=export-variations">
                    Purchase the WooCommerce Export Add-On</a>
            </div>
        <?php } ?>
    </div>
</div>