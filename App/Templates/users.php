<?php

use SEOAIC\SEOAIC;

if (!current_user_can('seoaic_edit_plugin')) {
    return;
}

if (isset($_GET['settings-updated'])) {
    add_settings_error('seoaic_messages', 'seoaic_message', __('Settings Saved', 'mdsf'), 'updated');
}

settings_errors('seoaic_messages');

global $SEOAIC, $SEOAIC_OPTIONS;

?>
<div id="seoaic-admin-container" class="wrap settings-page">
    <h1 id="seoaic-admin-title">
        <?php echo seoai_get_logo('logo.svg'); ?>
        <span class="seoaic-admin-title-subtitle">
            <span class="seoaic-admin-title-subpage"><?php echo esc_html(get_admin_page_title()); ?></span>
            <span class="seoaic-admin-title-version">
                Version <?= esc_html(get_plugin_data(SEOAIC_LM_FILE)['Version']); ?>
            </span>
        </span>
    </h1>
    <div id="seoaic-admin-body" class="seoaic-with-loader bg-settings">
        <div class="lds-dual-ring"></div>
        <form id="seoaic-settings" class="seoaic-form row" name="seoaic-settings" method="post" autocomplete="off">
            <input type="hidden" class="seoaic-form-item" name="action" value="seoaic_settings">

            <div class="col-6 left-side">
                <div class="row">

                </div>
            </div>

            <div class="col-6 right-side">

            </div>

        </form>
    </div>
</div>