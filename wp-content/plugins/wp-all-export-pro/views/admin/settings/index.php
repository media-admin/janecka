<?php
if(!defined('ABSPATH')) {
    die();
}
?>
<?php if (!$is_license_active): ?>
    <form name="settings" method="post" action="" class="settings">


        <h2 style="padding:0px;"></h2>
        <div class="wpallexport-header">
            <div class="wpallexport-logo"></div>
            <div class="wpallexport-title">
                <h3><?php esc_html_e('Settings', 'wp_all_export_plugin'); ?></h3>
            </div>
        </div>
        <div class="wpallexport-setting-wrapper">
            <?php if ($this->errors->get_error_codes()): ?>
                <?php $this->error() ?>
            <?php endif ?>

            <h3><?php esc_html_e('Licenses', 'wp_all_export_plugin') ?></h3>

            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row"><label><?php esc_html_e('WP All Export License Key', 'wp_all_export_plugin'); ?></label></th>
                    <td>
                        <input type="password" class="regular-text" name="license"
                               value="<?php if (!empty($post['license'])) esc_attr_e(PMXE_Plugin::decode($post['license'])); ?>"/>
                        <?php if (!empty($post['license'])) { ?>

                            <?php if (!empty($post['license_status']) && $post['license_status'] == 'valid') { ?>
                                <div class="license-status inline updated"><?php esc_html_e('Active', 'wp_all_export_plugin'); ?></div>
                            <?php } else { ?>
                                <?php if ( !empty($_POST['license'] ) ) { ?>
                                    <div class="license-status inline error"><?php echo $post['license_status']; ?></div>
                                <?php } ?>
                            <?php } ?>

                        <?php } ?>
                        <p class="description"><?php wp_kses_post(_e('A license key is required to access plugin updates. You can use your license key on an unlimited number of websites. Do not distribute your license key to 3rd parties. You can get your license key in the <a target="_blank" href="http://www.wpallimport.com/portal">customer portal</a>.', 'wp_all_export_plugin')); ?></p>
                        <p class="submit-buttons">
                            <?php wp_nonce_field('edit-license', '_wpnonce_edit-license') ?>
                            <input type="hidden" name="is_license_submitted" value="1"/>
                            <input type="submit" class="button-primary" value="Save License"/>
                        </p>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </form>

    <form name="settings" method="post" action="" class="settings">

        <table class="form-table">
            <tbody>

            <tr>
                <th scope="row"><label><?php esc_html_e('Automatic Scheduling License Key', 'wp_all_export_plugin'); ?></label>
                </th>
                <td>
                    <input type="password" class="regular-text" name="scheduling_license"
                           value="<?php if (!empty($post['scheduling_license'])) esc_attr_e(PMXE_Plugin::decode($post['scheduling_license'])); ?>"/>
                    <?php if (!empty($post['scheduling_license'])) { ?>

                        <?php if (!empty($post['scheduling_license_status']) && $post['scheduling_license_status'] == 'valid') { ?>
                            <div class="license-status inline updated"><?php esc_html_e('Active', 'wp_all_export_plugin'); ?></div>
                        <?php } else { ?>
                            <input type="submit" class="button-secondary" name="pmxe_scheduling_license_activate"
                                   value="<?php esc_html_e('Activate License', 'wp_all_export_plugin'); ?>"/>
                            <?php if ( !empty( $_POST['scheduling_license'] ) ) { ?>
                                <div class="license-status inline error"><?php echo esc_html($post['scheduling_license_status']); ?></div>
                            <?php } ?>
                        <?php } ?>

                    <?php } ?>
                    <?php
                    $scheduling = \Wpae\Scheduling\Scheduling::create();
                    if (!($scheduling->checkLicense())) {
                        ?>
                        <p class="description"><?php wp_kses_post(_e('A license key is required to use Automatic Scheduling. If you have already subscribed, <a href="https://www.wpallimport.com/portal/automatic-scheduling/" target="_blank">click here to access your license key</a>.<br>If you don\'t have a license, <a href="https://www.wpallimport.com/checkout/?edd_action=add_to_cart&download_id=515704" target="_blank">click here to subscribe</a>.', 'wp_all_export_plugin')); ?></p>
                        <?php
                    }
                    ?>
                    <p class="submit-buttons">
                        <?php wp_nonce_field('edit-license', '_wpnonce_edit-scheduling-license') ?>
                        <input type="hidden" name="is_scheduling_license_submitted" value="1"/>
                        <input type="submit" class="button-primary" value="Save License"/>
                    </p>
                </td>
            </tr>
            </tbody>
        </table>
    </form>
<?php endif; ?>

<form class="settings" method="post" action="<?php echo esc_url($this->baseUrl); ?>" enctype="multipart/form-data">

    <?php if ($is_license_active): ?>

    <div class="wpallexport-header">
        <div class="wpallexport-logo"></div>
        <div class="wpallexport-title">
            <h3><?php esc_html_e('Settings', 'wp_all_export_plugin'); ?></h3>
        </div>
    </div>

    <h2 style="padding:0;"></h2>

    <div class="wpallexport-setting-wrapper">
        <?php if ($this->errors->get_error_codes()): ?>
            <?php $this->error() ?>
        <?php endif ?>

        <?php if (!empty($license_message)): ?>
            <div class="updated"><p><?php echo esc_html($license_message); ?></p></div>
        <?php endif; ?>

        <?php endif; ?>

        <h3><?php esc_html_e('Import/Export Templates', 'wp_all_export_plugin') ?></h3>
        <?php $templates = new PMXE_Template_List();
        $templates->getBy()->convertRecords() ?>
        <?php wp_nonce_field('delete-templates', '_wpnonce_delete-templates') ?>
        <?php if ($templates->total()): ?>
            <table>
                <?php foreach ($templates as $t): ?>
                    <tr>
                        <td>
                            <label class="selectit" for="template-<?php echo esc_attr($t->id) ?>"><input
                                        id="template-<?php echo esc_attr($t->id) ?>" type="checkbox" name="templates[]"
                                        value="<?php echo esc_attr($t->id) ?>"/> <?php echo esc_html(wp_all_export_clear_xss($t->name)); ?></label>
                        </td>
                    </tr>
                <?php endforeach ?>
            </table>
            <p class="submit-buttons">
                <input type="submit" class="button-primary" name="delete_templates"
                       value="<?php esc_html_e('Delete Selected', 'wp_all_export_plugin') ?>"/>
                <input type="submit" class="button-primary" name="export_templates"
                       value="<?php esc_html_e('Export Selected', 'wp_all_export_plugin') ?>"/>
            </p>
        <?php else: ?>
            <em><?php esc_html_e('There are no templates saved', 'wp_all_export_plugin') ?></em>
        <?php endif ?>
        <p>
            <input type="hidden" name="is_templates_submitted" value="1"/>
            <input type="file" name="template_file"/>
            <input type="submit" class="button-primary" name="import_templates"
                   value="<?php esc_html_e('Import Templates', 'wp_all_export_plugin') ?>"/>
        </p>
        <?php if ($is_license_active): ?>
    </div>
<?php endif ?>

</form>
<br/>

<form name="settings" class="settings" method="post" action="<?php echo esc_url($this->baseUrl) ?>">

    <h3><?php esc_html_e('Cron Exports', 'wp_all_export_plugin') ?></h3>

    <table class="form-table">
        <tbody>
        <tr>
            <th scope="row"><label><?php esc_html_e('Secret Key', 'wp_all_export_plugin'); ?></label></th>
            <td>
                <input type="text" class="regular-text" name="cron_job_key"
                       value="<?php echo esc_attr($post['cron_job_key']); ?>"/>
                <p class="description"><?php esc_html_e('Changing this will require you to re-create your existing cron jobs.', 'wp_all_export_plugin'); ?></p>
            </td>
        </tr>
        </tbody>
    </table>

    <div class="clear"></div>

    <h3><?php esc_html_e('Files', 'wp_all_export_plugin') ?></h3>

    <table class="form-table">
        <tbody>
        <tr>
            <th scope="row"><label><?php esc_html_e('Secure Mode', 'wp_all_export_plugin'); ?></label></th>
            <td>
                <fieldset style="padding:0;">
                    <legend class="screen-reader-text"><span><?php esc_html_e('Secure Mode', 'wp_all_export_plugin'); ?></span>
                    </legend>
                    <input type="hidden" name="secure" value="0"/>
                    <label for="secure"><input type="checkbox" value="1" id="secure"
                                               name="secure" <?php echo(($post['secure']) ? 'checked="checked"' : ''); ?>><?php esc_html_e('Randomize folder names', 'wp_all_export_plugin'); ?>
                    </label>
                </fieldset>
                <p class="description">
                    <?php
                    $wp_uploads = wp_upload_dir();
                    ?>
                    <?php printf(__('If enabled, exported files and temporary files will be saved in a folder with a randomized name in %s.<br/><br/>If disabled, exported files will be saved in the Media Library.', 'wp_all_export_plugin'), esc_html($wp_uploads['basedir'] . DIRECTORY_SEPARATOR . WP_ALL_EXPORT_UPLOADS_BASE_DIRECTORY)); ?>
                </p>
            </td>
        </tr>
        </tbody>
    </table>
    <a name="license-key"></a>

    <h3><?php esc_html_e('Zapier Integration', 'wp_all_export_plugin') ?></h3>

    <table class="form-table">
        <tbody>
        <tr>
            <th scope="row"><label><?php esc_html_e('Getting Started', 'wp_all_export_plugin'); ?></label></th>
            <td>
                <p class="description"><?php printf(__('Zapier acts as a middle man between WP All Export and hundreds of other popular apps. To get started go to Zapier.com, create an account, and make a new Zap. Read more: <a target="_blank" href="https://zapier.com/zapbook/wp-all-export-pro/">https://zapier.com/zapbook/wp-all-export-pro/</a>', 'wp_all_export_plugin'), "https://zapier.com/zapbook/wp-all-export-pro/"); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label><?php esc_html_e('API Key', 'wp_all_export_plugin'); ?></label></th>
            <td>
                <input type="text" class="regular-text" name="zapier_api_key" readOnly="readOnly"
                       value="<?php if (!empty($post['zapier_api_key'])) esc_attr_e($post['zapier_api_key']); ?>"/>
                <input type="submit" class="button-secondary" name="pmxe_generate_zapier_api_key"
                       value="<?php esc_html_e('Generate New API Key', 'wp_all_export_plugin'); ?>"/>
                <p class="description"><?php esc_html_e('Changing the key will require you to update your existing Zaps on Zapier.', 'wp_all_export_plugin'); ?></p>
                <p class="submit-buttons">
                    <?php wp_nonce_field('edit-settings', '_wpnonce_edit-settings') ?>
                    <input type="hidden" name="is_settings_submitted" value="1"/>
                    <input type="submit" class="button-primary" value="Save Settings"/>
                </p>
            </td>
        </tr>
        </tbody>
    </table>
</form>

<?php if ($is_license_active): ?>
    <form name="settings" method="post" action="" class="settings">

        <h3><?php esc_html_e('Licenses', 'wp_all_export_plugin') ?></h3>

        <table class="form-table">
            <tbody>
            <tr>
                <th scope="row"><label><?php esc_html_e('WP All Export License Key', 'wp_all_export_plugin'); ?></label></th>
                <td>
                    <input type="password" class="regular-text" name="license"
                           value="<?php if (!empty($post['license'])) esc_attr_e(PMXE_Plugin::decode($post['license'])); ?>"/>
                    <?php if (!empty($post['license'])) { ?>

                        <?php if (!empty($post['license_status']) && $post['license_status'] == 'valid') { ?>
                            <div class="license-status inline updated"><?php esc_html_e('Active', 'wp_all_export_plugin'); ?></div>
                        <?php } else { ?>
                            <input type="submit" class="button-secondary" name="pmxe_license_activate"
                                   value="<?php esc_html_e('Activate License', 'wp_all_export_plugin'); ?>"/>
                            <?php if ( !empty($_POST['license'] ) ) { ?>
                                <div class="license-status inline error"><?php echo $post['license_status']; ?></div>
                            <?php } ?>
                        <?php } ?>

                    <?php } ?>
                    <p class="description"><?php echo wp_kses_post(__('A license key is required to access plugin updates. You can use your license key on an unlimited number of websites. Do not distribute your license key to 3rd parties. You can get your license key in the <a target="_blank" href="http://www.wpallimport.com/portal">customer portal</a>.', 'wp_all_export_plugin')); ?></p>
                    <p class="submit-buttons">
                        <?php wp_nonce_field('edit-license', '_wpnonce_edit-license') ?>
                        <input type="hidden" name="is_license_submitted" value="1"/>
                        <input type="submit" class="button-primary" value="Save License"/>
                    </p>
                </td>
            </tr>
            </tbody>
        </table>
    </form>


    <form name="settings" method="post" action="" class="settings">

        <table class="form-table">
            <tbody>

            <tr>
                <th scope="row"><label><?php esc_html_e('Scheduling License Key', 'wp_all_export_plugin'); ?></label></th>
                <td>
                    <input type="password" class="regular-text" name="scheduling_license"
                           value="<?php if (!empty($post['scheduling_license'])) esc_attr_e(PMXE_Plugin::decode($post['scheduling_license'])); ?>"/>
                    <?php if (!empty($post['scheduling_license'])) { ?>

                        <?php if (!empty($post['scheduling_license_status']) && $post['scheduling_license_status'] == 'valid') { ?>
                            <div class="license-status inline updated"><?php esc_html_e('Active', 'wp_all_export_plugin'); ?></div>
                        <?php } else { ?>
                            <?php if ( !empty( $_POST['scheduling_license'] ) ) { ?>
                                <div class="license-status inline error"><?php echo $post['scheduling_license_status']; ?></div>
                            <?php } ?>
                        <?php } ?>

                    <?php } ?>
                    <?php
                    $scheduling = \Wpae\Scheduling\Scheduling::create();
                    if (!($scheduling->checkLicense())) {
                        ?>
                        <p class="description"><?php echo wp_kses_post(__('A license key is required to use Automatic Scheduling. If you have already subscribed, <a href="https://www.wpallimport.com/portal/automatic-scheduling/" target="_blank">click here to access your license key</a>.<br>If you don\'t have a license, <a href="https://www.wpallimport.com/checkout/?edd_action=add_to_cart&download_id=515704" target="_blank">click here to subscribe</a>.', 'wp_all_export_plugin')); ?></p>
                        <?php
                    }
                    ?>
                    <p class="submit-buttons">
                        <?php wp_nonce_field('edit-license', '_wpnonce_edit-scheduling-license') ?>
                        <input type="hidden" name="is_scheduling_license_submitted" value="1"/>
                        <input type="submit" class="button-primary" value="Save License"/>
                    </p>
                </td>
            </tr>
            </tbody>
        </table>
    </form>
<?php endif; ?>

<?php
$uploads = wp_upload_dir();
$functions = $uploads['basedir'] . DIRECTORY_SEPARATOR . WP_ALL_EXPORT_UPLOADS_BASE_DIRECTORY . DIRECTORY_SEPARATOR . 'functions.php';
$functions_content = file_get_contents($functions);
?>
<hr/>
<div class="function-editor">
    <h3><?php esc_html_e('Function Editor', 'pmxe_plugin') ?></h3>

    <textarea id="wp_all_export_code"
            name="wp_all_export_code"><?php echo (empty($functions_content)) ? "<?php\n\n?>" : esc_textarea($functions_content); ?></textarea>

    <div class="input" style="margin-top: 10px;">

        <div class="input" style="display:inline-block; margin-right: 20px;">
            <input type="button" class="button-primary wp_all_export_save_functions"
                value="<?php esc_html_e("Save Functions", 'wp_all_export_plugin'); ?>"/>
            <a href="#help" class="wpallexport-help"
            title="<?php printf(__("Add functions here for use during your export. You can access this file at %s", "wp_all_export_plugin"), preg_replace("%.*wp-content%", "wp-content", $functions)); ?>"
            style="top: 0;">?</a>
            <div class="wp_all_export_functions_preloader"></div>
        </div>
        <div class="input wp_all_export_saving_status">

        </div>

    </div>
</div>
<hr/>
<form name="client-mode-settings" method="post" action="" class="client-mode-settings">

    <div>
        <h3>Client Mode</h3>
        <div style="float: left; width: 20%;">
            Roles With Access
        </div>
        <div style="float: left; width: 70%;">

            <?php foreach ($roles as $key => $role) {
                $roleObject = get_role($key);
                ?>
                <input type="checkbox" id="role-<?php echo $key; ?>"
                       value="<?php echo $key; ?>"
                    <?php if(is_array($post['client_mode_roles']) && in_array($key, $post['client_mode_roles'])) {?> checked="checked" <?php } ?>
                    <?php if($roleObject->has_cap('manage_options')) {?> disabled="disabled" checked="checked" <?php }?>
                       name="client_mode_roles[]"/>
                <label
                        for="role-<?php echo $key; ?>"><?php echo $role['name']; ?> <br/></label>
            <?php } ?>
            <p class="submit-buttons">
                <?php wp_nonce_field('edit-client-mode-settings', '_wpnonce_edit-client_mode_settings') ?>
                <input type="hidden" name="is_client_mode_submitted" value="1"/>
                <input type="submit" class="button-primary" value="Save Client Mode Settings"/>
            </p>
        </div>
    </div>
</form>
<a href="http://soflyy.com/" target="_blank"
   class="wpallexport-created-by"><?php esc_html_e('Created by', 'wp_all_export_plugin'); ?> <span></span></a>