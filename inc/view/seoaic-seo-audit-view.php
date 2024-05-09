<?php
if (!current_user_can('seoaic_edit_plugin')) {
    return;
}

if (isset($_GET['settings-updated'])) {
    add_settings_error('seoaic_messages', 'seoaic_message', __('Settings Saved', 'mdsf'), 'updated');
}

settings_errors('seoaic_messages');

global $SEOAIC, $SEOAIC_OPTIONS;

?>
<div id="seoaic-admin-container" class="wrap seoaic-audit-page">
    <h1 id="seoaic-admin-title">
        <?php echo seoai_get_logo('logo.svg'); ?>
        <span>
            <?php echo esc_html(get_admin_page_title()); ?>
        </span>
    </h1>
    <?= $SEOAIC->get_background_process_loader(); ?>      

    <div id="seoaic-admin-body" class="columns-2 seoaic-with-loader tab-sections seo-audit-page">
        <div class="page-title">
            <h1>Site Audit: <span><?php echo $_SERVER['SERVER_NAME'] ?></span></h1>
            <button class="seoaic_audit_btn seoaic_create_audit seoaic_create_audit_disabled" disabled="true" data-title="<?php echo __('Scan now', 'seoaic') ?>"><?php echo __('Scan now', 'seoaic') ?></button>
        </div>

        <div class="progress-container" >
            <ul>
                <li>
                    <p><?php echo __('Max Crawl Pages', 'seoaic') ?>:</p>
                    <div  id="progress-max-crawl-pages"><span>0</span></div>
                </li>
                <li>
                    <p><?php echo __('Pages in Queue', 'seoaic') ?>:</p>
                    <div  id="progress-pages-in-queue"><span>0</span></div>
                </li>
                <li>
                    <p><?php echo __('Pages Crawled', 'seoaic') ?>:</p>
                    <div  id="progress-pages-crawled"><span>0</span></div>
                </li>
            </ul>

            <h2 id="progress-status-message"><span><?php echo __('In progress...', 'seoaic') ?></span></h2>
            <div class="progress-bar-wrap">
                <div id="audit-progress-bar"></div>
            </div>
        </div>

        <div class="audit-container">
            <div class="menu-section">
                <ul>
                    <li>
                        <a class="overview tab" data-tab="overview" href="#overview"><?php echo __('Overview', 'seoaic') ?></a>
                    </li>
                    <li>
                        <a class="issues tab" data-tab="issues" href="#issues"><?php echo __('Issues', 'seoaic') ?></a>
                    </li>
                    <li>
                        <a class="crawled-pages tab" data-tab="crawled-pages" href="#crawled-pages"><?php echo __('Crawled pages', 'seoaic') ?></a>
                    </li>
                    <li>
                        <a class="statistics tab" data-tab="statistics" href="#statistics"><?php echo __('Statistics', 'seoaic') ?></a>
                    </li>
                    <li>
                        <a class="compare-crawls tab" data-tab="compare-crawls" href="#compare-crawls"><?php echo __('Compare Crawls', 'seoaic') ?></a>
                    </li>
                </ul>
            </div>
            <?php
                include_once(SEOAIC_DIR . 'inc/view/tabs/overview-tab.php');
                include_once(SEOAIC_DIR . 'inc/view/tabs/issues-tab.php');
                include_once(SEOAIC_DIR . 'inc/view/tabs/crawled-pages-tab.php');
                include_once(SEOAIC_DIR . 'inc/view/tabs/statistics-tab.php');
                include_once(SEOAIC_DIR . 'inc/view/tabs/compare-crawls-tab.php');
            ?>
        </div>

    </div>