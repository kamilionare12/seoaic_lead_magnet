<?php
if (!current_user_can('seoaic_edit_plugin')) {
    return;
}

if (isset($_GET['settings-updated'])) {
    add_settings_error('seoaic_messages', 'seoaic_message', __('Settings Saved', 'mdsf'), 'updated');
}

global $SEOAIC, $SEOAIC_OPTIONS;
$SEOAIC->rank->Update_Terms();
$update_ready_terms = !empty($SEOAIC_OPTIONS['search_terms_ready_to_update']) ? ' data-update-ready-terms="' . esc_attr(json_encode($SEOAIC_OPTIONS['search_terms_ready_to_update'])) . '"' : '';
$count_update_ready_terms = !empty($SEOAIC_OPTIONS['search_terms_ready_to_update']) ? count($SEOAIC_OPTIONS['search_terms_ready_to_update']) : '';
?>
<div id="seoaic-admin-container" class="wrap">
    <h1 id="seoaic-admin-title">
        <?php echo seoai_get_logo('logo.svg'); ?>
        <span>
            <?php echo esc_html(get_admin_page_title()); ?>
        </span>
    </h1>
    <?= $SEOAIC->get_background_process_loader(); ?>
    <div id="seoaic-admin-body" class="seoaic-with-loader rank-tracker"<?php echo $update_ready_terms; ?>
         data-update-modal-content="<?php echo sprintf("%d search terms can be updated.", $count_update_ready_terms); ?>">
        <div class="inner">

            <div class="top">

                <button title="Generate ideas" type="button"
                        class="button-primary seoaic-button-primary seoaic-generate-ideas-button generate-keyword-based modal-button"
                        data-action="seoaic_generate_ideas"
                        data-modal="#generate-ideas"

                ><?php esc_html_e('Generate search-terms based ideas') ?></button>

                <div class="show-filters">Filter terms</div>

                <button data-title="Add new keywords" type="button"
                        class="add-keyword add-search-term button-primary outline modal-button ml-auto"
                        data-modal="#add-idea"
                        data-mode="add"
                        data-form-callback="window_reload"
                >
                    <span class="vertical-align-middle dashicons dashicons-plus"></span> <?php esc_html_e('Add Search Terms'); ?>
                    <div class="dn edit-form-items">
                        <input type="hidden" name="item_name" value=""
                               data-label="Add Search Terms (separated by comma). Ex: cup,table">
                        <input type="hidden" name="action" value="seoaicAddSearchTerms">
                    </div>
                </button>

                <a href="#" class="seoaic_update_keywords seoaic-ajax-button disabled hidden"
                   data-callback="update_keywords_manual" data-action="seoaic_update_keywords"
                   title="Update keywords">
                </a>

                <button title="Remove" disabled type="button"
                        class="seoaic-remove-main modal-button confirm-modal-button ml-0"
                        data-modal="#seoaic-confirm-modal"
                        data-action="seoaicRemoveSearchTerms"
                        data-form-callback="window_reload"
                        data-content="Do you want to remove selected keywords?"
                        data-selected="get-selected"
                        data-post-id=""
                ></button>
            </div>

            <div class="bottom">

                <?php echo $SEOAIC->rank->filtering_terms_HTML(); ?>

                <div class="flex-table search-terms-list">

                    <?php echo $SEOAIC->rank->search_terms_HTML(); ?>

                </div>
            </div>

        </div>
        <div class="lds-dual-ring"></div>
    </div>
</div>