<?php
if (!current_user_can('seoaic_edit_plugin')) {
    return;
}

if (isset($_GET['settings-updated'])) {
    add_settings_error('seoaic_messages', 'seoaic_message', __('Settings Saved', 'mdsf'), 'updated');
}

global $SEOAIC, $SEOAIC_OPTIONS;
$SEOAIC->competitors->Add_New_Competitor();
$SEOAIC->competitors->clean_all_competitors_data();
$SEOAIC->competitors->update_competitors_data();
$ready_to_update = !empty($SEOAIC_OPTIONS['ready_to_update_competitors']) ? $SEOAIC_OPTIONS['ready_to_update_competitors'] : [];
$ready_to_update_count = count($ready_to_update);
$competitors_options = isset($SEOAIC_OPTIONS['competitors']) ? count($SEOAIC_OPTIONS['competitors']) : 0;
?>

<div id="seoaic-admin-container" class="wrap">
    <h1 id="seoaic-admin-title">
        <?php echo seoai_get_logo('logo.svg'); ?>
        <span>
            <?php echo esc_html(get_admin_page_title()); ?>
        </span>
    </h1>

    <?= $SEOAIC->get_background_process_loader(); ?>

    <div id="seoaic-admin-body"
         class="seoaic-with-loader competitors-page"
         data-update-modal-content="<?php echo esc_attr(strval($ready_to_update_count))?> competitors can be updated"
         data-migrate-modal-content="Action is required: <?php echo esc_attr($competitors_options)?> competitors needs to be migrated"
         data-ready-to-update="<?php echo esc_attr(json_encode($ready_to_update))?>"
         data-migrate-competitors-options="<?php echo esc_attr($competitors_options)?>"
    >
        <div class="inner">

            <div class="top">

                <?php echo $SEOAIC->competitors->Competitors_Page_Top_Tabs_HTML(); ?>

            </div>

            <div class="bottom">
                <div class="flex-table compare">

                    <?php echo $SEOAIC->competitors->Competitors_Compare_Section_HTML(); ?>

                </div>
                <div class="flex-table search-terms-table">

                    <?php echo $SEOAIC->competitors->Competitors_Search_Terms_HTML(); ?>

                </div>

                <a href="#" data-action="seoaicAddToRankTracking" data-terms=""
                   data-location=""><?php _e('Add selected to rank tracking', 'seoaiс'); ?>
                </a>
            </div>
            <div class="bottom-section">
                <div class="pagination"></div>
                <div class="per-page"></div>
            </div>

        </div>
        <div class="lds-dual-ring"></div>
    </div>
</div>