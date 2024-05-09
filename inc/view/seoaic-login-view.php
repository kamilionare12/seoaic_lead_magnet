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
        <div class="inner-login login">
            <h2 class="tc">Log in</h2>
            <div class="tc">Have no account? <a href="<?= get_admin_url() ?>admin.php?page=seoaic&subpage=seoaic-registration">Sign up</a>
            </div>
            <form id="seoaic-login" class="seoaic-form" name="seoaic-login" method="post" data-callback="window_href_setinfo">
                <input type="hidden" class="seoaic-form-item" name="action" value="seoaic_login">

                <div class="col-12">
                    <label for="seoaic_email">E-mail</label>
                    <input id="seoaic_email" class="seoaic-form-item form-input" name="email" type="email" required>
                </div>

                <div class="col-12">
                    <label for="seoaic_password" class="db">Password <a class="gradient-link fr" href="<?= get_admin_url() ?>admin.php?page=seoaic&subpage=seoaic-forgot">Forgot?</a></label>
                    <input id="seoaic_password" class="seoaic-form-item form-input" name="password" type="password" required>
                </div>

                <div class="col-12">
                    <button id="seoaic_submit" type="submit" class="button-primary seoaic-button-primary">Log in</button>
                </div>

            </form>
        </div>
        <div class="lds-dual-ring"></div>
    </div>
</div>