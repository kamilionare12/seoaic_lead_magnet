<?php
if (!current_user_can('seoaic_edit_plugin')) {
    return;
}

if (isset($_GET['settings-updated'])) {
    add_settings_error('seoaic_messages', 'seoaic_message', __('Settings Saved', 'mdsf'), 'updated');
}

settings_errors('seoaic_messages');

global $SEOAIC_OPTIONS;
?>
<div id="seoaic-admin-container" class="wrap">
    <h1 id="seoaic-admin-title">
        <?php echo seoai_get_logo('logo.svg'); ?>
        <span>
            <?php echo esc_html(get_admin_page_title()); ?>
        </span>
    </h1>
    <div id="seoaic-admin-body" class="columns-2 seoaic-with-loader idea-page locations-page">
        <div class="row full-width">
            <div class="col-6 left-side">
                <div class="header seoaic-flip-box">

                    <div class="head-buttons">

                        <button data-title="<?= __('Add locations group', 'seoaic'); ?>" type="button"
                                class="button-primary seoaic-button-primary outline modal-button"
                                data-modal="#add-locations-group"
                                data-mode="add"
                                data-single="no"
                                data-form-callback="window_reload"
                        ><span class="vertical-align-middle dashicons dashicons-plus"></span> Add locations group
                            <div class="dn edit-form-items">
                                <input type="hidden" name="item_name" value=""
                                       data-label="Separate each group name by new line">
                            </div>
                        </button>
                    </div>

                    <div class="seoaic-flip-container">
                        <?php if (!empty($SEOAIC_OPTIONS['location_groups'])) : ?>
                            <div class="seoaic-flip-item seoaic-flip-front">

                                <div class="schedule-switcher">

                                    <div class="checkbox-wrapper-mc">
                                        <input id="idea-mass-create-all" type="checkbox" class="idea-mass-create"
                                            name="idea-mass-create-all" value="all">
                                        <label for="idea-mass-create-all" class="check">
                                            <div class="checkbox-wrapper-svg">
                                                <svg width="18px" height="18px" viewBox="0 0 18 18">
                                                    <path d="M1,9 L1,3.5 C1,2 2,1 3.5,1 L14.5,1 C16,1 17,2 17,3.5 L17,14.5 C17,16 16,17 14.5,17 L3.5,17 C2,17 1,16 1,14.5 L1,9 Z"></path>
                                                    <polyline points="1 9 7 14 15 4"></polyline>
                                                </svg>
                                            </div>
                                        </label>
                                    </div>
                                    <button type="button"
                                            class="button button-danger seoaic-remove-all-ideas-button ml-auto modal-button confirm-modal-button"
                                            data-post-id="all"
                                            data-modal="#seoaic-confirm-modal"
                                            data-action="seoaicDeleteGroupLocation"
                                            data-form-callback="window_reload"
                                            data-content="Do you want to remove ALL groups?"
                                    >Delete all groups
                                    </button>

                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="seoaic-flip-item seoaic-flip-side">
                            <div class="schedule-switcher">
                                <span class="seoaic-checked-amount">Selected: <span
                                            class="seoaic-checked-amount-num"></span></span>
                                <button type="button" class="idea-mass-create-uncheck-all">Unselect</button>

                                <button type="button"
                                        class="button button-danger mass-effect-button seoaic-remove-all-ideas-button ml-auto modal-button confirm-modal-button"
                                        data-modal="#seoaic-confirm-modal"
                                        data-action="seoaicDeleteGroupLocation"
                                        data-form-callback="window_reload"
                                        data-content="Do you want to remove <b class='additional-items-amount'></b> following ideas?"
                                >Delete selected groups
                                    <div class="dn additional-form-items"></div>
                                </button>

                            </div>
                        </div>

                    </div>

                </div>

                <div class="seoaic-ideas-posts">

                    <?= \SEOAIC_LOCATIONS::seoaicDisplayGroupLocations(); ?>

                </div>
            </div>

            <div class="col-6 right-side">
                <div class="seoaic-content-idea-box">
                    <div class="seoaic-content-idea-box-slide">
                        <div class="header">

                        </div>
                        <div class="idea-box">
                            <div class="seoaic-idea-content-section">
                                <div id="seoaic-idea-title"></div>
                            </div>
                            <div id="seoaic-idea-content-skeleton" class="seoaic-idea-content-section">
                                <div class="top">
                                    <h3 class="seoaic-section-idea-title">Locations</h3>
                                    <div class="info inline right">
                                        <span class="info-btn">?</span>
                                        <div class="info-content">
                                            <h4>Locations</h4>
                                            <p>You can add locations where you provide your service(s).
                                                These locations can be used when creating ideas
                                                for future articles.
                                            </p>
                                        </div>
                                    </div>
                                    <button title="Add subtitle" type="button"
                                            class="seoaic-add-idea-subtitle modal-button confirm-modal-button"
                                            data-modal="#add-idea"
                                            data-mode="add"
                                            data-title="Add subtitle"
                                            data-form-before-callback="add_item"
                                            data-action="subtitle"
                                    >
                                    </button>
                                </div>

                                <div class="seoaic-loaction-form-container mb-40">
                                    <input form="seoaicLocationsForm" class="seoaic-form-item" type="hidden" name="mode" value="">
                                    <form
                                            id="seoaicLocationsForm"
                                            method="post"
                                            class="seoaic_input_repeater seoaic_locations"
                                            data-action="seoaicSaveLocationGroup"
                                            data-post-id=""
                                    >
                                        <div id="location_list" class="list">
                                            <?php echo $loc ?? ''; ?>
                                        </div>
                                        <a href="#" class="add add-loction" data-add="location-input" data-readonly="0"
                                           title="Add location (API)"><?php esc_html_e('Add location (API)', 'seoaic'); ?></a>
                                    </form>

                                    <span class="seoaic-or">or</span>

                                    <div id="seoaic-idea-content-keywords">
                                        <ul id="seoaic-idea-locations" class="seoaic-idea-content-section-keyword seoaic-idea-content-section-location"></ul>

                                        <button title="Add locations manually" type="button" class="seoaic-add-idea-keyword seoaic-add-location-manually modal-button confirm-modal-button"
                                                data-single="no"
                                                data-modal="#add-idea"
                                                data-mode="add"
                                                data-title="Add locations manually"
                                                data-form-before-callback="add_item"
                                                data-action="location"
                                        >
                                            <span class="vertical-align-middle dashicons dashicons-plus"></span><span>Add locations manually</span>
                                            <div class="dn edit-form-items">
                                                <input type="hidden" name="item_name" value="" data-label="Location (separate by new line)">
                                            </div>
                                        </button>

                                    </div>

                                </div>

                            </div>

                            <div class="seoaic-save-content-idea seoaic-idea-content-section">
                                <div class="top last">
                                </div>
                                <div class="bottom">
                                    <button type="button" class="seoaic-cancel-content-idea-button ml-auto">
                                        Cancel
                                    </button>
                                    <button title="Save group"
                                            type="submit"
                                            class="button-primary seoaic-button-primary"
                                            data-action="seoaicSaveLocationGroup"
                                            form="seoaicLocationsForm"
                                    >Save group
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="lds-dual-ring"></div>


    </div>
</div>