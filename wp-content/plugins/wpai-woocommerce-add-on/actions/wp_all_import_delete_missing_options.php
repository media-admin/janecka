<?php

/**
 * @param $post_type
 * @param $post
 */
function pmwi_wp_all_import_delete_missing_options( $post_type, $post ) {
	if ( $post_type == 'product' && $post['wizard_type'] == 'new'): ?>
        <div class="input">
            <input type="hidden" name="missing_records_stock_status" value="0" />
            <input type="checkbox" id="missing_records_stock_status" name="missing_records_stock_status" value="1" <?php echo $post['missing_records_stock_status'] ? 'checked="checked"': '' ?>/>
            <label for="missing_records_stock_status"><?php _e('Instead of deletion, set missing records to out of stock', PMWI_Plugin::TEXT_DOMAIN) ?></label>
            <a href="#help" class="wpallimport-help" title="<?php _e('Option to set the stock status to out of stock instead of deleting the product entirely.', PMWI_Plugin::TEXT_DOMAIN) ?>" style="position:relative; top:-2px;">?</a>
        </div>
    <?php endif;
}
