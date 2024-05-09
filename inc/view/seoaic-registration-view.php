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
    <div id="seoaic-admin-body" class="seoaic-toggle-container">
        <div class="inner-login registration">
            <h2 class="tc">Registration</h2>
            <div class="tc">Have an account? <a href="<?= get_admin_url() ?>admin.php?page=seoaic">Log in</a></div>
            <form id="seoaic-login" class="seoaic-form" name="seoaic-login" method="post" data-callback="window_href_setinfo">
                <input type="hidden" class="seoaic-form-item" name="action" value="seoaic_registration">

                <div class="col-12">
                    <label for="seoaic_email">E-mail</label>
                    <input id="seoaic_email" class="seoaic-form-item form-input" name="email" type="email" required>
                </div>

                <div class="col-12">
                    <label for="seoaic_password">Password</label>
                    <input id="seoaic_password" class="seoaic-form-item form-input" name="password" type="password"
                           required>
                </div>

                <div class="col-12">
                    <label for="seoaic_repeat_password">Repeat password</label>
                    <input id="seoaic_repeat_password" class="seoaic-form-item form-input" name="repeat_password"
                           type="password" required>
                </div>

                <div class="col-12">
                    <button id="seoaic_submit" type="submit" class="button-primary seoaic-button-primary">Sign up</button>
                </div>

            </form>
        </div>
        <div class="lds-dual-ring"></div>
    </div>
</div>