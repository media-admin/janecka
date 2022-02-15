<?php
if(!defined('ABSPATH')) {
    die();
}
?>
<?php
$addons = new \Wpae\App\Service\Addons\AddonService();
if(class_exists('GF_Export_Add_On')) {
    $addon = GF_Export_Add_On::get_instance();
}

?>

<h2></h2> <!-- Do not remove -->

<script type="text/javascript">
    (function ($, ajaxurl, wp_all_export_security) {

        $(document).ready(function () {
            $('.open_cron_scheduling').click(function () {

                var itemId = $(this).data('itemid');
                openSchedulingDialog(itemId, $(this), '<?php echo esc_js(PMXE_ROOT_URL); ?>/static/img/preloader.gif');
            });
        });
    })(jQuery, ajaxurl, wp_all_export_security);

    window.pmxeHasSchedulingSubscription = <?php echo PMXE_Plugin::hasActiveSchedulingLicense() ? 'true' : 'false';  ?>;
</script>
<div class="wpallexport-header" style="overflow:hidden; height: 70px; padding-top: 10px; margin-bottom: -15px;">
    <div class="wpallexport-logo"></div>
    <div class="wpallexport-title">
        <h3><?php esc_html_e('Manage Exports', 'wp_all_export_plugin'); ?></h3>
    </div>
</div>

<?php if ($this->errors->get_error_codes()): ?>
    <?php $this->error() ?>
<?php endif ?>

<form method="get">
    <input type="hidden" name="page" value="<?php echo esc_attr($this->input->get('page')) ?>"/>
    <p class="search-box">
        <label for="search-input" class="screen-reader-text"><?php esc_html_e('Search Exports', 'wp_all_export_plugin') ?>
            :</label>
        <input id="search-input" type="text" name="s" value="<?php echo esc_attr($s) ?>"/>
        <input type="submit" class="button" value="<?php esc_html_e('Search Exports', 'wp_all_export_plugin') ?>">
    </p>
</form>

<?php
// define the columns to display, the syntax is 'internal name' => 'display name'
$columns = array(
    'id' => esc_html__('ID', 'wp_all_export_plugin'),
    'name' => esc_html__('Name', 'wp_all_export_plugin'),
    'actions' => '',
    'data' => esc_html__('Query', 'wp_all_export_plugin'),
    //'format'        => __('Format', 'wp_all_export_plugin'),
    'summary' => esc_html__('Summary', 'wp_all_export_plugin'),
    //'registered_on'	=> __('Last Export', 'wp_all_export_plugin'),
    'info' => esc_html__('Info & Options', 'wp_all_export_plugin'),
);

//if ( ! wp_all_export_is_compatible()) unset($columns['info']);

if (!current_user_can(PMXE_Plugin::$capabilities)) {
    unset($columns['info']);
}

$columns = apply_filters('pmxe_manage_imports_columns', $columns);

?>

<form method="post" id="import-list" action="<?php echo esc_attr(remove_query_arg('pmxe_nt')); ?>">

    <input type="hidden" name="action" value="bulk"/>
    <?php wp_nonce_field('bulk-exports', '_wpnonce_bulk-exports') ?>

    <div class="tablenav">
        <div class="alignleft actions">
            <?php if (current_user_can(PMXE_Plugin::$capabilities)) { ?>

            <select name="bulk-action">
                <option value="" selected="selected"><?php esc_html_e('Bulk Actions', 'wp_all_export_plugin') ?></option>
                <option value="delete"><?php esc_html_e('Delete', 'wp_all_export_plugin') ?></option>
                <option value="allow_client_mode"><?php esc_html_e('Toggle Client Mode', 'wp_all_export_plugin') ?></option>
            </select>
            <input type="submit" value="<?php esc_attr_e('Apply', 'wp_all_export_plugin') ?>" name="doaction"
                   id="doaction" class="button-secondary action"/>
        </div>

        <?php } ?>
        <?php if ($page_links): ?>
            <div class="tablenav-pages">
                <?php echo $page_links_html = sprintf(
                    '<span class="displaying-num">' . esc_html__('Displaying %s&#8211;%s of %s', 'wp_all_export_plugin') . '</span>%s',
                    number_format_i18n((intval($pagenum) - 1) * intval($perPage) + 1),
                    number_format_i18n(min(intval($pagenum * $perPage), intval($list->total()))),
                    number_format_i18n($list->total()),
                    $page_links
                ) ?>
            </div>
        <?php endif ?>
    </div>
    <div class="clear"></div>

    <table class="widefat pmxe-admin-exports">
        <thead>
        <tr>
            <?php if (current_user_can(PMXE_Plugin::$capabilities)) { ?>
                <th class="manage-column column-cb check-column" scope="col">
                    <input type="checkbox"/>
                </th>
            <?php } ?>
            <?php
            $col_html = '';
            foreach ($columns as $column_id => $column_display_name) {
                $column_link = "<a href='";
                $order2 = 'ASC';
                if ($order_by == $column_id)
                    $order2 = ($order == 'DESC') ? 'ASC' : 'DESC';

                $column_link .= esc_url(add_query_arg(array('order' => $order2, 'order_by' => $column_id), $this->baseUrl));
                $column_link .= "'>{$column_display_name}</a>";
                $col_html .= '<th scope="col" class="column-' . $column_id . ' ' . ($order_by == $column_id ? $order : '') . '">' . $column_link . '</th>';
            }
            echo $col_html;
            ?>
        </tr>
        </thead>
        <tfoot>
        <tr>
            <?php if (current_user_can(PMXE_Plugin::$capabilities)) { ?>
                <th class="manage-column column-cb check-column" scope="col">
                    <input type="checkbox"/>
                </th>
            <?php } ?>
            <?php echo $col_html; ?>
        </tr>
        </tfoot>
        <tbody id="the-pmxi-admin-import-list" class="list:pmxe-admin-exports">
        <?php if ($list->isEmpty()): ?>
            <tr>
                <td colspan="<?php echo esc_attr(count($columns) + 1) ?>"><?php esc_html_e('No previous exports found.', 'wp_all_export_plugin') ?></td>
            </tr>
        <?php else: ?>
            <?php

            $is_secure_import = PMXE_Plugin::getInstance()->getOption('secure');

            $class = '';
            ?>
            <?php foreach ($list as $item):
                if ( is_array($item['options']['cpt']) && isset($item['options']['cpt'][0]) ) {
                    $cpt = $item['options']['cpt'][0];
                } else if ( !empty($item['options']['cpt']) ) {
                    $cpt = $item['options']['cpt'];
                } else {
                    $cpt = '';
                }


                $is_rapid_addon_export = true;

                if (strpos($cpt, 'custom_') !== 0) {
                    $is_rapid_addon_export = false;
                }
                ?>

                <?php $class = ('alternate' == $class) ? '' : 'alternate'; ?>
                <tr class="<?php echo $class; ?>" valign="middle">
                    <?php if (current_user_can(PMXE_Plugin::$capabilities)) { ?>
                        <th scope="row" class="check-column">
                            <input type="checkbox" id="item_<?php echo $item['id'] ?>" name="items[]"
                                   value="<?php echo esc_attr($item['id']) ?>"/>
                        </th>
                    <?php } ?>
                    <?php foreach ($columns as $column_id => $column_display_name): ?>
                        <?php
                        switch ($column_id):
                            case 'id':
                                ?>
                                <th valign="top" scope="row">
                                    <?php echo $item['id'] ?>
                                </th>
                                <?php
                                break;
                            case 'name':
                                ?>
                                <td style="min-width: 325px;">
                                    <strong><?php echo wp_all_export_clear_xss($item['friendly_name']); ?></strong> <br>
                                    <div class="row-actions">
                                        <?php if (current_user_can(PMXE_Plugin::$capabilities)) { ?>
                                            <span class="edit">
                                                <a class="edit"
                                                  href="<?php echo esc_url(add_query_arg(array('id' => $item['id'], 'action' => 'template'), $this->baseUrl)) ?>"><?php esc_html_e('Edit Export', 'wp_all_export_plugin') ?></a></span>
                                            |
                                            <span class="edit">
                                                <a class="edit"
                                                  href="<?php echo esc_url(add_query_arg(array('id' => $item['id'], 'action' => 'options'), $this->baseUrl)) ?>"><?php esc_html_e('Export Settings', 'wp_all_export_plugin') ?></a></span>

                                        <?php } ?>
                                        <?php if (!$is_secure_import and $item['attch_id']): ?>
                                            | <span class="update"> <a class="update"

                                                                     href="<?php echo esc_url(add_query_arg(array('id' => $item['id'], 'action' => 'get_file', '_wpnonce' => wp_create_nonce('_wpnonce-download_feed')), $this->baseUrl)) ?>"><?php echo esc_html(strtoupper(wp_all_export_get_export_format($item['options']))); ?></a></span>
                                            <?php if (!empty($item['options']['bundlepath']) and PMXE_Export_Record::is_bundle_supported($item['options'])): ?>
                                                | <span class="update"><a class="update"
                                                                        href="<?php echo esc_url(add_query_arg(array('id' => $item['id'], 'action' => 'bundle', '_wpnonce' => wp_create_nonce('_wpnonce-download_bundle')), $this->baseUrl)) ?>"><?php esc_html_e('Bundle', 'wp_all_export_plugin'); ?></a></span>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <?php if ($item['options']['export_to'] == XmlExportEngine::EXPORT_TYPE_XML && $item['options']['xml_template_type'] == XmlExportEngine::EXPORT_TYPE_GOOLE_MERCHANTS) : ?>

                                            <?php if ($is_secure_import and !empty($item['options']['filepath'])): ?>
                                               | <span class="update"><a class="update"
                                                                        href="<?php echo esc_url(add_query_arg(array('id' => $item['id'], 'action' => 'get_file', '_wpnonce' => wp_create_nonce('_wpnonce-download_feed')), $this->baseUrl)) ?>"><?php if (current_user_can(PMXE_Plugin::CLIENT_MODE_CAP) && !current_user_can(PMXE_Plugin::$capabilities)) { ?>Download <?php } ?>
                                                        TXT</a></span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?php if ($is_secure_import && !empty($item['options']['filepath'])): ?>
                                                | <span class="update"><a class="update"
                                                                        href="<?php echo esc_url(add_query_arg(array('id' => $item['id'], 'action' => 'get_file', '_wpnonce' => wp_create_nonce('_wpnonce-download_feed')), $this->baseUrl)) ?>"><?php if (current_user_can(PMXE_Plugin::CLIENT_MODE_CAP) && !current_user_can(PMXE_Plugin::$capabilities)) { ?>Download <?php } ?><?php echo strtoupper(wp_all_export_get_export_format($item['options'])); ?></a></span>
                                                <?php if (current_user_can(PMXE_Plugin::$capabilities) && !empty($item['options']['bundlepath']) && PMXE_Export_Record::is_bundle_supported($item['options'])): ?>
                                                    | <span class="update"><a class="update"
                                                                              href="<?php echo esc_url(add_query_arg(array('id' => $item['id'], 'action' => 'bundle', '_wpnonce' => wp_create_nonce('_wpnonce-download_bundle')), $this->baseUrl)) ?>"><?php esc_html_e('Bundle', 'wp_all_export_plugin'); ?></a></span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <?php if (!empty($item['options']['split_large_exports']) and !empty($item['options']['split_files_list']) and current_user_can(PMXE_Plugin::$capabilities)): ?>
                                            | <span class="update"><a class="update"
                                                                      href="<?php echo esc_url(add_query_arg(array('id' => $item['id'], 'action' => 'split_bundle', '_wpnonce' => wp_create_nonce('_wpnonce-download_split_bundle')), $this->baseUrl)) ?>"><?php printf(esc_html__('Split %ss', 'wp_all_export_plugin'), strtoupper(wp_all_export_get_export_format($item['options']))); ?></a></span> |
                                        <?php endif; ?>

                                        <?php if (current_user_can(PMXE_Plugin::$capabilities)) { ?>
                                            | <span class="delete"><a class="delete"
                                                                      href="<?php echo esc_url(add_query_arg(array('id' => $item['id'], 'action' => 'delete'), $this->baseUrl)) ?>"><?php esc_html_e('Delete', 'wp_all_export_plugin') ?></a></span>
                                        <?php } ?>
                                    </div>
                                </td>
                                <?php
                                break;
                            case 'info':
                                ?>
                                <td style="min-width: 180px;">
                                    <?php if (current_user_can(PMXE_Plugin::$capabilities)) { ?>
                                        <a
                                            <?php
                                            // Ensure arrays exist that are required for further processing.
                                            if (!is_array($item['options']['cpt'])) {
                                                $item['options']['cpt'] = array($item['options']['cpt']);
                                            }

                                            if( !is_array($item['options']['cc_type'])){
	                                            $item['options']['cc_type'] = array($item['options']['cc_type']);
                                            }
                                            // Disable scheduling options for User exports if User Export Add-On isn't enabled
                                            if (
                                                ((in_array('users', $item['options']['cpt']) || in_array('shop_customer', $item['options']['cpt'])) && !$addons->isUserAddonActive()) ||
                                                ($item['options']['export_type'] == 'advanced' && $item['options']['wp_query_selector'] == 'wp_user_query' && !$addons->isUserAddonActive())
                                            ) {
                                                ?>
                                                href="<?php echo esc_url(add_query_arg(array('id' => $item['id'], 'action' => 'options'), $this->baseUrl)) ?>"
                                                <?php
                                            // Disable scheduling options for WooCo exports if WooCo Export Add-On isn't enabled and the relevant free add-on isn't enabled
                                            } else if (
                                                (( (in_array('product', $item['options']['cpt']) && in_array('product_variation', $item['options']['cpt']) && !$addons->isWooCommerceProductAddonActive()) ||
                                                   (in_array('shop_order', $item['options']['cpt']) && !$addons->isWooCommerceOrderAddonActive()) ||
                                                   in_array('shop_coupon', $item['options']['cpt']) ||
                                                   in_array('shop_review', $item['options']['cpt']) ||
                                                   (isset($item['options']['xml_template_type']) && $item['options']['xml_template_type'] == \XmlExportEngine::EXPORT_TYPE_GOOLE_MERCHANTS) )
                                                 &&
                                                 !$addons->isWooCommerceAddonActive()
                                                )
                                                ||
                                                ($item['options']['export_type'] == 'advanced' && in_array($item['options']['exportquery']->query['post_type'], array('shop_coupon')) && !$addons->isWooCommerceAddonActive())
                                                ||
                                                ($item['options']['export_type'] == 'advanced' && in_array($item['options']['exportquery']->query['post_type'], array('shop_order')) && !$addons->isWooCommerceAddonActive() && !$addons->isWooCommerceOrderAddonActive())
                                                ||
                                                ($item['options']['export_type'] == 'advanced' && in_array($item['options']['exportquery']->query['post_type'], array(array('product', 'product_variation'), )) && !$addons->isWooCommerceAddonActive() && !$addons->isWooCommerceProductAddonActive())
                                            ) {
                                                ?>
                                                href="<?php echo esc_url(add_query_arg(array('id' => $item['id'], 'action' => 'options'), $this->baseUrl)) ?>"
                                                <?php
                                            // Disable scheduling options for ACF exports if ACF Export Add-On isn't enabled
                                            } else if (
                                                ((!in_array('comments', $item['options']['cpt']) || !in_array('shop_review', $item['options']['cpt'])) && in_array('acf', $item['options']['cc_type']) && !$addons->isAcfAddonActive()) ||
                                                ($item['options']['export_type'] == 'advanced' && $item['options']['wp_query_selector'] != 'wp_comment_query' && in_array('acf', $item['options']['cc_type']) && !$addons->isAcfAddonActive())
                                            ) {
                                                ?>
                                                href="<?php echo esc_url(add_query_arg(array('id' => $item['id'], 'action' => 'options'), $this->baseUrl)) ?>"
                                                <?php
                                            } else {

                                           ?>
                                            href="javascript:void(0);" class="open_cron_scheduling"

                                            <?php } ?>
                                                data-itemid="<?php echo $item['id']; ?>"><?php esc_html_e('Scheduling Options', 'wp_all_export_plugin'); ?></a>
                                        <br>
                                    <?php } ?>

                                    <?php if ($item['options']['export_to'] === XmlExportEngine::EXPORT_TYPE_XML && $item['options']['xml_template_type'] == XmlExportEngine::EXPORT_TYPE_GOOLE_MERCHANTS) : ?>
                                        <a href="<?php echo add_query_arg(array('id' => $item['id'], 'action' => 'google_merchants_info'), $this->baseUrl) ?>"><?php esc_html_e('Google Merchant Center Info', 'wp_all_export_plugin'); ?></a>
                                    <?php endif; ?>
                                    <?php
                                    $isImportAllowedSpecification = new \Wpae\App\Specification\IsImportAllowed();
                                    $is_re_import_allowed = $isImportAllowedSpecification->isSatisfied($item);
                                    ?>
                                    <?php


                                    if ($item['options']['export_to'] == 'csv'
                                        || (empty($item['options']['xml_template_type'])
                                            || !in_array($item['options']['xml_template_type'],
                                                array('custom', 'XmlGoogleMerchants')))): ?>
                                        <?php if (wp_all_export_is_compatible() and !empty($item['options']['import_id']) and $is_re_import_allowed): ?>
                                            <a href="<?php echo add_query_arg(array('page' => 'pmxi-admin-import', 'id' => $item['options']['import_id'], 'deligate' => 'wpallexport'), remove_query_arg('page', $this->baseUrl)); ?>"><?php esc_html_e("Import with WP All Import", "wp_all_export_plugin"); ?></a>
                                            <br/>
                                        <?php endif; ?>
                                        <?php
                                        if (!in_array($item['options']['wp_query_selector'], array('wp_comment_query')) and (empty($item['options']['cpt']) or !in_array('comments', $item['options']['cpt']))) {
                                            if (!empty($item['options']['tpl_data'])) {
                                                ?>
                                                <a href="<?php echo add_query_arg(array('id' => $item['id'], 'action' => 'templates'), $this->baseUrl) ?>"><?php esc_html_e('Download Import Templates', 'wp_all_export_plugin'); ?></a>
                                                <?php
                                            }
                                        }
                                        ?>
                                    <?php endif;
                                    ?>
                                    <br/>
                                    <?php if (current_user_can(PMXE_Plugin::$capabilities && $item['client_mode_enabled'])) { ?>
                                        <span style="color: green;">Client mode enabled</span>
                                    <?php } ?>

                                </td>
                                <?php
                                break;
                            case 'data':
                                ?>
                                <td>
                                    <?php
                                    if (!empty($item['options']['cpt'])) {

                                        echo '<strong>' . __('Post Types: ') . '</strong> <br/>';

                                        if($is_rapid_addon_export) {
                                            $form = GFAPI::get_form($item['options']['sub_post_type_to_export']);
                                            echo 'Gravity Form Entries:<br/>';
                                            echo $form['title'];
                                        } else {
                                            echo implode(', ', $item['options']['cpt']);
                                        }
                                    }
                                    else {
                                        echo $item['options']['wp_query'];
                                    }?>
                                </td>
                                <?php
                                break;
                            case 'format':
                                ?>
                                <td>
                                    <strong><?php echo ($item['options']['export_to'] == 'csv' && !empty($item['options']['export_to_sheet'])) ? $item['options']['export_to_sheet'] : $item['options']['export_to']; ?></strong>
                                </td>
                                <?php
                                break;
                            case 'registered_on':
                                ?>
                                <td>
                                    <?php if ('0000-00-00 00:00:00' == $item['registered_on']): ?>
                                        <em>never</em>
                                    <?php else: ?>
                                        <?php echo mysql2date(__('Y/m/d g:i a', 'wp_all_export_plugin'), $item['registered_on']) ?>
                                    <?php endif ?>
                                </td>
                                <?php
                                break;
                            case 'summary':
                                ?>
                                <td>
                                    <?php
                                    if ($item['triggered'] and !$item['processing']) {
                                        esc_html_e('triggered with cron', 'wp_all_export_plugin');
                                        if ($item['last_activity'] != '0000-00-00 00:00:00') {
                                            $diff = ceil((time() - strtotime($item['last_activity'])) / 60);
                                            ?>
                                            <br>
                                            <span <?php if ($diff >= 10) echo 'style="color:red;"'; ?>>
											<?php
                                            printf(esc_html__('last activity %s ago', 'wp_all_export_plugin'), human_time_diff(strtotime($item['last_activity']), time()));
                                            ?>
											</span>
                                            <?php
                                        }
                                    } elseif ($item['processing']) {
                                        esc_html_e('currently processing with cron', 'wp_all_export_plugin');
                                        echo '<br/>';
                                        printf('Records Processed %s', $item['exported']);
                                        if ($item['last_activity'] != '0000-00-00 00:00:00') {
                                            $diff = ceil((time() - strtotime($item['last_activity'])) / 60);
                                            ?>
                                            <br>
                                            <span <?php if ($diff >= 10) echo 'style="color:red;"'; ?>>
											<?php
                                            printf(esc_html__('last activity %s ago', 'wp_all_export_plugin'), human_time_diff(strtotime($item['last_activity']), time()));
                                            ?>
											</span>
                                            <?php
                                        }
                                    } elseif ($item['executing']) {
                                        esc_html_e('Export currently in progress', 'wp_all_export_plugin');
                                        if ($item['last_activity'] != '0000-00-00 00:00:00') {
                                            $diff = ceil((time() - strtotime($item['last_activity'])) / 60);
                                            ?>
                                            <br>
                                            <span <?php if ($diff >= 10) echo 'style="color:red;"'; ?>>
											<?php
                                            printf(esc_html__('last activity %s ago', 'wp_all_export_plugin'), human_time_diff(strtotime($item['last_activity']), time()));
                                            ?>
											</span>
                                            <?php
                                        }
                                    } elseif ($item['canceled'] and $item['canceled_on'] != '0000-00-00 00:00:00') {
                                        printf(esc_html__('Export Attempt at %s', 'wp_all_export_plugin'), get_date_from_gmt($item['canceled_on'], "m/d/Y g:i a"));
                                        echo '<br/>';
                                        esc_html_e('Export canceled', 'wp_all_export_plugin');
                                    } else {
                                        printf(esc_html__('Last run: %s', 'wp_all_export_plugin'), ($item['registered_on'] == '0000-00-00 00:00:00') ? __('never', 'wp_all_export_plugin') : get_date_from_gmt($item['registered_on'], "m/d/Y g:i a"));
                                        echo '<br/>';
                                        printf(esc_html__('%d Records Exported', 'wp_all_export_plugin'), $item['exported']);
                                        echo '<br/>';
                                        $export_to = ($item['options']['export_to'] == 'csv' && !empty($item['options']['export_to_sheet'])) ? $item['options']['export_to_sheet'] : $item['options']['export_to'];
                                        if ($item['options']['export_to'] == XmlExportEngine::EXPORT_TYPE_XML && $item['options']['xml_template_type'] == XmlExportEngine::EXPORT_TYPE_GOOLE_MERCHANTS) {
                                            $export_to = 'Google Merchants Feed';
                                        }
                                        printf(esc_html__('Format: %s', 'wp_all_export_plugin'), $export_to);
                                        echo '<br/>';
                                        //printf(__('%d records', 'wp_all_export_plugin'), $item['post_count']);
                                    }

                                    if ($item['settings_update_on'] != '0000-00-00 00:00:00' and $item['last_activity'] != '0000-00-00 00:00:00' and strtotime($item['settings_update_on']) > strtotime($item['last_activity'])) {
                                        ?>
                                        <strong><?php esc_html_e('settings edited since last run', 'wp_all_export_plugin'); ?></strong>
                                        <?php
                                    }

                                    ?>
                                </td>
                                <?php
                                break;
                            case 'actions':
                                ?>
                                <td style="min-width: 130px;">
                                    <?php if (!$item['processing'] and !$item['executing']): ?>
                                        <!--h2 style="float:left;"><a class="add-new-h2" href="<?php echo add_query_arg(array('id' => $item['id'], 'action' => 'edit'), $this->baseUrl); ?>"><?php esc_html_e('Edit', 'wp_all_export_plugin'); ?></a></h2-->
                                        <h2 style="float:left;"><a class="add-new-h2"
                                                                   href="<?php echo add_query_arg(array('id' => $item['id'], 'action' => 'update'), $this->baseUrl); ?>"><?php esc_html_e('Run Export', 'wp_all_export_plugin'); ?></a>
                                        </h2>
                                    <?php elseif ($item['processing']) : ?>
                                        <h2 style="float:left;"><a class="add-new-h2"
                                                                   href="<?php echo add_query_arg(array('id' => $item['id'], 'action' => 'cancel'), $this->baseUrl); ?>"><?php esc_html_e('Cancel Cron', 'wp_all_export_plugin'); ?></a>
                                        </h2>
                                    <?php elseif ($item['executing']) : ?>
                                        <h2 style="float:left;"><a class="add-new-h2"
                                                                   href="<?php echo add_query_arg(array('id' => $item['id'], 'action' => 'cancel'), $this->baseUrl); ?>"><?php esc_html_e('Cancel', 'wp_all_export_plugin'); ?></a>
                                        </h2>
                                    <?php endif; ?>
                                </td>
                                <?php
                                break;
                            default:
                                ?>
                                <td>
                                    <?php do_action('pmxe_manage_imports_column', $column_id, $item); ?>
                                </td>
                                <?php
                                break;
                        endswitch;
                        ?>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        <?php endif ?>
        </tbody>
    </table>

    <div class="tablenav">
        <?php if ($page_links): ?>
            <div class="tablenav-pages"><?php echo $page_links_html ?></div><?php endif ?>

        <div class="alignleft actions">
            <?php if (current_user_can(PMXE_Plugin::$capabilities)) { ?>

                <select name="bulk-action2">
                    <option value="" selected="selected"><?php esc_html_e('Bulk Actions', 'wp_all_export_plugin') ?></option>
                    <?php if (empty($type) or 'trash' != $type): ?>
                        <option value="delete"><?php esc_html_e('Delete', 'wp_all_export_plugin') ?></option>
                        <option value="allow_client_mode"><?php esc_html_e('Toggle Client Mode', 'wp_all_export_plugin') ?></option>
                    <?php else: ?>
                        <option value="restore"><?php esc_html_e('Restore', 'wp_all_export_plugin') ?></option>
                        <option value="delete"><?php esc_html_e('Delete Permanently', 'wp_all_export_plugin') ?></option>
                    <?php endif ?>
                </select>
                <input type="submit" value="<?php esc_attr_e('Apply', 'wp_all_export_plugin') ?>" name="doaction2"
                       id="doaction2" class="button-secondary action"/>
            <?php } ?>

        </div>
    </div>
    <div class="clear"></div>
    <a href="http://soflyy.com/" target="_blank"
       class="wpallexport-created-by"><?php esc_html_e('Created by', 'wp_all_export_plugin'); ?> <span></span></a>

</form>
<div class="wpallexport-overlay"></div>
<div class="wpallexport-loader"
     style="border-radius: 5px; z-index: 999999; display:none; position: fixed;top: 200px;    left: 50%; width: 100px;height: 100px;background-color: #fff; text-align: center;">
    <img style="margin-top: 45%;" src="<?php echo PMXE_ROOT_URL; ?>/static/img/preloader.gif"/>
</div>


<div class="wpallexport-super-overlay"></div>

<fieldset class="optionsset column rad4 wp-all-export-scheduling-help">

    <div class="title">
        <span style="font-size:1.5em;"
              class="wpallexport-add-row-title"><?php esc_html_e('Automatic Scheduling', 'wp_all_export_plugin'); ?></span>
    </div>

    <?php
    include_once __DIR__ . '/../../../src/Scheduling/views/SchedulingHelp.php';
    ?>
</fieldset>