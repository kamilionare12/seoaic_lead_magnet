<?php
if (!current_user_can('seoaic_edit_plugin')) {
    return;
}

if (isset($_GET['settings-updated'])) {
    add_settings_error('seoaic_messages', 'seoaic_message', __('Settings Saved', 'mdsf'), 'updated');
}

settings_errors('seoaic_messages');
?>
<div id="seoaic-admin-container" class="wrap">
    <h1 id="seoaic-admin-title">
        <?php echo seoai_get_logo('logo.svg'); ?>
        <span>
            <?php echo esc_html(get_admin_page_title()); ?>
        </span>
    </h1>
    <div id="seoaic-admin-body">
        <div class="inner-login registration">
            <h2 class="tc">Set your info</h2>
            <div class="tc"></div>
            <form id="seoaic-login" class="seoaic-form" name="seoaic-setinfo" method="post" data-callback="window_href_seoaic">
                <input type="hidden" class="seoaic-form-item" name="action" value="seoaic_setinfo">

                <div class="col-12">
                    <label for="seoaic_business_name">Company name</label>
                    <input id="seoaic_business_name" class="seoaic-form-item form-input" name="seoaic_business_name" type="text" required value="<?= !empty($SEOAIC_OPTIONS['seoaic_business_name']) ? $SEOAIC_OPTIONS['seoaic_business_name'] : get_option('blogname', true); ?>">
                </div>

                <div class="col-12">
                    <label for="seoaic_phone">Phone</label>
                    <input id="seoaic_phone" class="seoaic-form-item form-input" name="seoaic_phone"
                           type="tel" required value="<?= !empty($SEOAIC_OPTIONS['seoaic_phone']) ? $SEOAIC_OPTIONS['seoaic_phone'] : ''; ?>">
                </div>

                <div class="col-12">
                    <label for="seoaic_location">Country</label>
                    <select id="seoaic_location" class="seoaic-form-item form-input mb-19"
                            name="seoaic_location"
                            required>
                        <?php
                            $locations = seoaic_get_locations();
                            $selected_location = !empty($SEOAIC_OPTIONS['seoaic_location']) ? $SEOAIC_OPTIONS['seoaic_location'] : 'United States';
                            foreach ( $locations as $key => $location ) :
                        ?>
                            <option value="<?=$location;?>"
                                <?=($location === $selected_location) ? 'selected' : ''; ?>
                            ><?=$location;?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <button id="seoaic_submit" type="submit" class="button-primary seoaic-button-primary">Save</button>
                </div>

            </form>
        </div>
        <div class="lds-dual-ring"></div>
    </div>
</div>