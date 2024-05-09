<?php

if (!current_user_can('seoaic_edit_plugin')) {
    return;
}

if (isset($_GET['settings-updated'])) {
    add_settings_error('seoaic_messages', 'seoaic_message', __('Settings Saved', 'mdsf'), 'updated');
}

settings_errors('seoaic_messages');

global $SEOAIC, $SEOAIC_OPTIONS;

if (isset($_GET['clean_dashboard']) && $_GET['clean_dashboard'] == 'true') {
    $SEOAIC_OPTIONS['on_page_seo_data'] = [];
    $SEOAIC_OPTIONS['dashboard_seo_data_monthly'] = [];
    update_option('seoaic_options', $SEOAIC_OPTIONS);
}
$competitors_options = isset($SEOAIC_OPTIONS['competitors']) ? count($SEOAIC_OPTIONS['competitors']) : 0;
?>
<div id="seoaic-admin-container" class="wrap"
>
    <h1 id="seoaic-admin-title">
        <?php echo seoai_get_logo('logo.svg'); ?>
        <span>
            <?php echo esc_html(get_admin_page_title()); ?>
        </span>
    </h1>
    <?= $SEOAIC->get_background_process_loader(); ?>
    <div id="seoaic-admin-body" class="columns-2 seoaic-with-loader dashboard-page"
         data-update-ready=""
         data-migrate-modal-content="Action is required: <?php echo esc_attr($competitors_options)?> competitors needs to be migrated"
         data-migrate-competitors-options="<?php echo esc_attr($competitors_options)?>"
    >
        <div class="row full-width">
            <?php echo $SEOAIC->dashboard->dashboard_HTML();?>
        </div>

        <div class="lds-dual-ring"></div>

    </div>
</div>