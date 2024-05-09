<?php
if (!current_user_can('seoaic_edit_plugin')) {
    return;
}

$checkActionCreate = false;

if (isset($_GET['action']) && $_GET['action'] === 'create') {
    $checkActionCreate = true;
}

$activeClass = 'hide';

if (!$checkActionCreate) {
    $activeClass = 'show';
}

global $SEOAIC;
?>
<div id="seoaic-admin-container" class="wrap">
    <h1 id="seoaic-admin-title">
        <?php echo seoai_get_logo('logo.svg'); ?>
        <span>
            <?php echo esc_html(get_admin_page_title()); ?>
        </span>
    </h1>
    <?= $SEOAIC->get_background_process_loader(); ?>
    <div id="seoaic-admin-body" class="seoaic-with-loader knowledge-bases">
        <div class="inner">

            <div class="top">
                <button type="button" class="button-primary seoaic-button-primary seoaic-create-kb-button" data-action="create">
                    <?php esc_html_e('+ Create An AI Knowledge Base') ?>
                </button>
            </div>

            <div class="bottom" id="knowledge_base">
                <div class="flex-table">
                    <div class="row-line heading">
                        <div class="name">
                            <?php esc_html_e('Name') ?>
                        </div>
                        <div class="description">
                            <?php esc_html_e('Description') ?>
                        </div>
                        <div class="status">
                            <?php esc_html_e('Status') ?>
                        </div>
                        <div class="tokens">
                            <?php esc_html_e('Tokens') ?>
                        </div>
                        <div class="actions">
                            <?php esc_html_e('Actions') ?>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        <div class="knowledge-base-content" style="display: none;">
            <div class="knowledge-base-item">
                <div class="knowledge-base-head">
                    <h3><span>/01</span><?php echo __('Basic Information', 'seoaic') ?></h3>
                    <p><?php echo __('Basic information about your  AI knowledge base.', 'seoaic') ?></p>
                </div>
                <div class="knowledge-base-body">
                    <form action="" id="knowledge-base-form">
                        <div class="form-item">
                            <label for="knowledge-base-name"><?php echo __('Name *', 'seoaic') ?></label>
                            <input type="text" id="knowledge-base-name" name="knowledge-base-name" require>
                        </div>
                        <div class="form-item">
                            <label for="knowledge-base-description"><?php echo __('Description', 'seoaic') ?></label>
                            <textarea name="knowledge-base-description" id="knowledge-base-description" rows="4"></textarea>
                        </div>

                        <?php if ($checkActionCreate) { ?>
                            <div class="form-item-bottom">
                                <button type="button" class="button-primary seoaic-button-primary seoaic-save-kb-button" data-action="edit">
                                    <?php esc_html_e('Create Knowledge Base') ?>
                                </button>
                            </div>
                        <?php } else { ?>
                            <div class="form-item-bottom">
                                <button type="button" class="button-primary seoaic-button-primary seoaic-save-kb-action" data-action="edit">
                                    <?php esc_html_e('Save Knowledge Base') ?>
                                </button>
                            </div>
                        <?php } ?>
                    </form>
                </div>
            </div>
            
            <?php if (!$checkActionCreate) { ?>
            <div class="knowledge-base-item knowledge-base-data-source">
                <div class="knowledge-base-head">
                    <h3><span>/02</span><?php echo __('Data Sources', 'seoaic') ?></h3>
                    <p><?php echo __('Please add the sources you want to use for your knowledge base. You can use either pages, domains or plain text.', 'seoaic') ?></p>
                </div>
                <div class="knowledge-base-body">
                    <p><?php echo __('Add or edit your knowledge base below.', 'seoaic') ?></p>

                    <form class="data-sources-wrap" id="data-sources">

                        <div class="data-sources-item data-sources-item-main">
                            <div class="data-sources-head">
                                <ul>
                                    <li class="active"><?php echo __('Domain', 'seoaic') ?></li>
                                    <li><?php echo __('Page', 'seoaic') ?></li>
                                    <li><?php echo __('Text', 'seoaic') ?></li>
                                </ul>
                                <div class="data-sources-content-wrap active">
                                    <div class="data-sources-content">
                                        <div class="form-item seoaic-form-item-url">
                                            <label for="data-sources-domain"><?php echo __('URL / Domain', 'seoaic') ?> <span><?php echo __('Check our <a href="#">‘URL Setup Guide’</a> for step-by-step instructions and best practices.', 'seoaic') ?></span></label>
                                            <div class="data-sources-domain-wrap">
                                                <span></span>
                                                <input type="url" name="data-sources-domain" class="data_source_item data_source_url" data-mode="domain">
                                            </div>
                                        </div>
                                        <div class="form-item form-item-pages">
                                            <label for="data-sources-pages"><?php echo __('Max pages', 'seoaic') ?></span></label>
                                            <input type="number" name="data-sources-pages" class="data_source_item data_source_max_pages" min="10" value="10">
                                        </div>
                                    </div>
                                </div>
                                <div class="data-sources-content-wrap">
                                    <div class="data-sources-content">
                                        <div class="form-item seoaic-form-item-url">
                                            <label for="data-sources-page"><?php echo __('URL / Page', 'seoaic') ?> <span><?php echo __('Check our <a href="#">‘URL Setup Guide’</a> for step-by-step instructions and best practices.', 'seoaic') ?></span></label>
                                            <div class="data-sources-domain-wrap">
                                                <span></span>
                                                <input type="url" name="data-sources-page" class="data_source_item data_source_url" data-mode="page">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="data-sources-content-wrap">
                                    <div class="data-sources-content">
                                        <div class="form-item seoaic-form-item-url">
                                            <label for="data-sources-text"><?php echo __('Text area', 'seoaic') ?> <span><?php echo __('Lorem ipsum dolor sit amet consectetur. At turpis porta vulputate mauris', 'seoaic') ?></span></label>
                                            <div class="data-sources-domain-wrap">
                                                <textarea name="data-sources-text" class="data_source_item" data-mode="text" rows="4"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="scanning-rules">
                                <a href="#" class="show_scanning_rules"><?php echo __('Show scanning rules', 'seoaic') ?></a>
                                <div class="scanning_rules-content">
                                    <div class="scanning_rules-content-wrap">
                                        <div class="scanning_rules-content-item scanning_rules-include">
                                            <h4><?php echo __('Include rules:', 'seoaic') ?> <span><?php echo __('Define the URL ranges OR URLs including words you want the scanner to include', 'seoaic') ?></span></h4>
                                            <div class="form-item">
                                                <input type="text" name="include_rules" class="include-rules" value="*">
                                                <button class="add-rules" data-rules="include"></button>
                                                <button class="remove-rules" data-rules="exclude"><span></span></button>
                                            </div>
                                        </div>

                                        <div class="scanning_rules-content-item scanning_rules-exclude">
                                            <h4><?php echo __('Exclude rules:', 'seoaic') ?> <span><?php echo __('Define the URL ranges OR URLs including words you want the scanner to exclude', 'seoaic') ?></span></h4>
                                            <div class="form-item">
                                                <input type="text" name="exclude_rules" class="exclude-rules" value="">
                                                <button class="add-rules" data-rules="exclude"></button>
                                                <button class="remove-rules" data-rules="exclude"><span></span></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" class="data_source_item data_source_mode" value="domain">
                        </div>

                        <div class="data-source-scan">
                            <a href="#" class="scan_sources"><?php echo __('Scan Sources', 'seoaic') ?></a>
                            <a href="#" class="add_sources"><?php echo __('+ Add a new data source', 'seoaic') ?></a>
                            <div class="action-knowledge-base hide">
                                <button type="button" class="button-primary seoaic-button-primary seoaic-train-kb-button">
                                    <?php esc_html_e('Train') ?>
                                </button>
                                <button type="button" class="button-primary seoaic-button-primary seoaic-rescan-kb-button">
                                    <?php esc_html_e('Rescan') ?>
                                </button>
                            </div>
                        </div>

                        <input type="hidden" id="knowledge_bases_id" class="data_source_mode" value="">
                    </form>
                    
                </div>
            </div> 
            <div class="knowledge-base-item knowledge-base-data-source-item" id="seoaic-knowledge-base-pages">
                <div class="knowledge-base-head">
                    <h3><span>/03</span><?php echo __('AI Knowledge Base', 'seoaic') ?></h3>
                    <p><?php echo __('These are the pages that have been added to your knowledge base. You can rescan and train your model here.', 'seoaic') ?></p>
                </div>
                <div class="knowledge-base-body">
                    <div class="data-item-actions">
                        <div class="mass-actions">
                            <div class="select-all-data-items">
                                <div class="checkbox-wrapper-mc">
                                    <input id="source-item-all" type="checkbox" class="source-item-all" name="source-item" value="all">
                                    <label for="source-item-all" class="check">
                                        <div class="checkbox-wrapper-svg">
                                            <svg width="18px" height="18px" viewBox="0 0 18 18">
                                                <path d="M1,9 L1,3.5 C1,2 2,1 3.5,1 L14.5,1 C16,1 17,2 17,3.5 L17,14.5 C17,16 16,17 14.5,17 L3.5,17 C2,17 1,16 1,14.5 L1,9 Z"></path>
                                                <polyline points="1 9 7 14 15 4"></polyline>
                                            </svg>
                                        </div>
                                    </label>
                                </div>
                                <?php _e('Select All', 'seoaic') ?>
                            </div>
                            <button id="data-source-item-remove"><?php _e('Delete Selected', 'seoaic') ?></button>
                        </div>
                        <div class="search-wrap">
                            <div class="search">
                                <input type="text" class="data_search" placeholder="<?php _e('Search...', 'seoaic') ?>">
                                <button type="submit" data-search="1" class="seoaic_data_source_search"></button>
                            </div>
                            <a href="" class="filter-clear-btn"></a>
                        </div>
                        <div class="status-wrap">
                            <label class="mr-15 mb-0"><?php _e('Knowledge base status', 'seoaic') ?>:</label>
                            <div class="status"></div>
                        </div>
                    </div>
                    <div class="bottom">
                        <div class="flex-table">
                            <div class="row-line heading">
                                <div class="name">
                                    <?php esc_html_e('URLs') ?>
                                </div>
                                <div class="status">
                                    <?php esc_html_e('Status') ?>
                                </div>
                                <div class="description">
                                    <?php esc_html_e('Last Scan') ?>
                                </div>
                                <div class="tokens">
                                    <?php esc_html_e('Tokens') ?>
                                </div>
                                <div class="actions">
                                    <?php esc_html_e('Actions') ?>
                                </div>
                            </div>

                            <div class="seoaic-pagination pagination" id="pagination-container"></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>

        <div class="lds-dual-ring"></div>
    </div>
</div>