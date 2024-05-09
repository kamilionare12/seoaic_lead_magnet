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
        <div class="inner-login forgot">
            <h2 class="tc ws-nw">Account recovery</h2>
            <div class="tc">Back to <a href="<?= get_admin_url() ?>admin.php?page=seoaic">login</a> page
            </div>
            <form id="seoaic-forgot" class="seoaic-form" name="seoaic-forgot" method="post" data-callback="forgot_callback">
                <input type="hidden" class="seoaic-form-item" name="action" value="seoaic_forgot">

                <div class="col-12 step-1">
                    <label for="seoaic_email">E-mail</label>
                    <input id="seoaic_email" class="seoaic-form-item form-input" name="email" type="email" required>
                </div>

                <div class="col-12 dn step-2">
                    <label for="seoaic_recovery_code" class="db">Code (check your email)</label>
                    <input id="seoaic_recovery_code" class="form-input" name="recovery_code" type="text">
                </div>

                <div class="col-12 dn step-2">
                    <label for="seoaic_password">Password</label>
                    <input id="seoaic_password" class="form-input" name="password" type="password">
                </div>

                <div class="col-12 dn step-2">
                    <label for="seoaic_repeat_password">Repeat password</label>
                    <input id="seoaic_repeat_password" class="form-input" name="repeat_password" type="password">
                </div>

                <div class="col-12">
                    <button id="seoaic_submit" type="submit" class="button-primary seoaic-button-primary" data-step="1" data-step-1="Send recovery code" data-step-2="Apply recovery code" data-step-3="Set a new password">Send recovery code</button>
                </div>

            </form>
        </div>
        <div class="lds-dual-ring"></div>
    </div>
</div>