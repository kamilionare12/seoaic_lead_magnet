<?php

// seoaic-with-loader position-relative bg-commect seoaic-loading-no-slide seoaic-loading

if (!current_user_can('seoaic_edit_plugin')) {
    return;
}

if (isset($_GET['settings-updated'])) {
    add_settings_error('seoaic_messages', 'seoaic_message', __('Settings Saved', 'mdsf'), 'updated');
}

settings_errors('seoaic_messages');

global $SEOAIC, $SEOAIC_OPTIONS;
?>
<div id="seoaic-admin-container" class="wrap">
    <h1 id="seoaic-admin-title">
        <?php echo seoai_get_logo('logo.svg'); ?>
        <span>
            <?php echo esc_html(get_admin_page_title()); ?>
        </span>
    </h1>
    <?=$SEOAIC->get_background_process_loader();?>
    <div id="seoaic-admin-body" class="seoaic-with-loader position-relative connected bg-commect">
        <div class="inner-connected tc">
            <h2 class="mb-40">You are connected<br>to the <span class="accent">SEO AI Service</span></h2>
            <p class="mb-13 mt-0 fs-small fw-400">Your connection e-mail:</p>
            <p class="mt-0 mb-0 fs-20 fw-700"><?= $SEOAIC_OPTIONS['seoaic_api_email'] ?></p>
            <p class="mb-13 fs-small mt-40 fw-400">Your connection API Key:</p>
            <p class="mt-0 mb-0 fs-20 fw-700"><?= substr_replace($SEOAIC_OPTIONS['seoaic_api_token'], '************************', 7, strlen($SEOAIC_OPTIONS['seoaic_api_token']) - 14) ?></p>

            <?php if ( empty($SEOAIC_OPTIONS['seoaic_scanned']) ) : ?>
                <p class="fs-24 fw-700 mt-80 mb-40 position-relative hide-on-scanning">You need to scan your site first<span class="arrow-to-scan"></span></p>
                <button type="button" class="button-primary seoaic-button-primary seoaic-scan-site seoaic-ajax-button max-w-300 hide-on-scanning"
                        data-action="seoaic_scan_site"
                        data-callback="window_reload"
                >Start Scanning
                </button>
            <?php else : ?>
                <p class="fs-24 fw-700 mt-80 mb-10">Scanned!</p>
                <p class="fs-16 fw-400 lh-1 mt-0 mb-30">Complete the settings for your blog and start generating ideas now!</p>
                <a class="button-primary seoaic-button-primary max-w-300" href="/wp-admin/admin.php?page=seoaic-ideas">Start</a>
            <?php endif; ?>

            <button class="seoaic-disconnect-button button-link hide-on-scanning modal-button" type="button"
                    data-modal="#seoaic-disconnect"
                    data-form-callback="window_reload"
            >Disconnect</button>

            <?php if ( empty($SEOAIC_OPTIONS['seoaic_scanned']) ) : ?>
                <div class="lds-indication show-on-scanning">
                    <p class="fs-24 fw-700 mt-80">Scanning...</p>
                    <div class="indication position-relative"><span class="loader accent-bg"></span></div>
                </div>
            <?php endif; ?>
        </div>

        <div class="lds-dual-ring"></div>
    </div>
</div>