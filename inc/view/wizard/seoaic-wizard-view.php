<?php
if (!current_user_can('seoaic_edit_plugin')) {
    return;
}

global $SEOAIC, $SEOAIC_OPTIONS;

?>
<div id="seoaic-admin-container" class="wrap">
    <h1 id="seoaic-admin-title">
        <?php echo seoai_get_logo('logo.svg');?>
        <span><?php echo esc_html(get_admin_page_title());?></span>
    </h1>
    <?php echo $SEOAIC->get_background_process_loader();?>
    <div id="seoaic-admin-body" class="seoaic-with-loader wizard bg-wizard">
        <div class="inner">
            <div class="headers">
                <h2 class="text-left mb-0">Welcome</h2>
                <h3 class="wizard-subtitle mt-10">Generate your content easily with SEO AI tool.</h3>
            </div>
            <div class="content mt-20">
                <?php $SEOAIC->wizard->runWizard();?>
            </div>
        </div>
        <div class="lds-dual-ring"></div>
    </div>
</div>