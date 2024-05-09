<?php
if (!current_user_can('seoaic_edit_plugin')) {
    return;
}

if (isset($_GET['settings-updated'])) {
    add_settings_error('seoaic_messages', 'seoaic_message', __('Settings Saved', 'mdsf'), 'updated');
}

settings_errors('seoaic_messages');

global $SEOAIC, $SEOAIC_OPTIONS;

$SEOAIC->keywords->convertKeywordsToPosts();

$keywords = $SEOAIC->keywords->getKeywords();
$keywords = $SEOAIC->keywords->sanitizeKeywords($keywords);
$keywords = $SEOAIC->keywords->generateKeywordsStat(false, 'auto', $keywords);
$keywordsCategoriesOptions = $SEOAIC->keywords->makeKeywordsCategoriesOptions(
    $SEOAIC->keywords->getKeywordsCategories()
);
$isRankInProgress = $SEOAIC->keywords->isBackgroundRankProcessInProgress();

$manual_time = !empty($SEOAIC_OPTIONS['keywords_stat_update_manual']) ? $SEOAIC_OPTIONS['keywords_stat_update_manual'] : 0;
$mu = time() > $manual_time ? '' : 'disabled';

?>
<div id="seoaic-admin-container" class="wrap">
    <h1 id="seoaic-admin-title">
        <?php echo seoai_get_logo('logo.svg'); ?>
        <span>
            <?php echo esc_html(get_admin_page_title()); ?>
        </span>
    </h1>
    <?=$SEOAIC->get_background_process_loader();?>
    <div id="seoaic-admin-body" class="seoaic-with-loader keywords">
        <div class="inner">

            <div class="top">
                <div class="search seoaic-mr-20">
                    <input id="keywords_search_input" type="text" placeholder="Enter a keyword" class="seoaic-h-100-i">
                    <button type="submit" id="keywords_search_do_search"></button>
                </div>

                <button type="button"
                        title="<?php _e('Generate ideas', 'seoaic');?>"
                        class="button-primary seoaic-button-primary seoaic-generate-ideas-button generate-keyword-based modal-button seoaic-mr-20"
                        data-action="seoaic_generate_ideas_new_keywords"
                        data-modal="#generate-ideas-new-keywords"
                >
                    <?php _e('Generate keyword-based ideas', 'seoaic');?>
                </button>

                <button title="<?php _e('Manage Clusters', 'seoaic');?>"
                        data-title="Manage Clusters" type="button"
                        class="manage-categories secondary-btn outline modal-button seoaic-mr-20"
                        data-modal="#keywords-manage-categories-modal"
                        data-action="seoaic_add_keyword"
                >Clusters</button>

                <button data-title="Generate keywords" type="button"
                        class="add-keyword generate-keywords button-primary outline modal-button ml-auto seoaic-mr-20"
                        data-modal="#generate-keywords"
                        data-mode="generate"
                        data-form-callback="window_reload"
                >Generate keywords
                    <div class="dn edit-form-items">
                        <input type="hidden" name="item_name" value="" data-label="Name">
                        <input type="hidden" name="action" value="seoaic_add_keyword">
                    </div>
                </button>

                <button title="<?php _e('Add new keywords', 'seoaic');?>"
                        data-title="Add new keywords" type="button"
                        class="add-keyword add-keyword-manual small-btn button-primary outline modal-button"
                        data-modal="#add-keyword-modal"
                        data-mode="add"
                        data-action="seoaic_add_keyword"
                        data-form-callback="window_reload"
                ><span class="vertical-align-middle dashicons dashicons-plus-alt2"></span>
                    <div class="dn edit-form-items">
                        <input type="hidden" name="item_name" value="" data-label="Add keywords (separated by comma). Ex: cup,table">
                        <input type="hidden" name="action" value="seoaic_add_keyword">
                    </div>
                </button>

                <a href="#" class="seoaic_update_keywords seoaic-ajax-button small-btn <?php esc_attr_e($mu, 'seoaic');?>"
                   data-callback="update_keywords_manual"
                   data-action="seoaic_update_keywords"
                   title="Update keywords"></a>

                <button title="Remove" disabled type="button" class="seoaic-remove-keywords-bulk modal-button confirm-modal-button small-btn"
                        data-modal="#seoaic-confirm-modal"
                        data-action="seoaic_remove_keyword"
                        data-form-callback="window_reload"
                        data-content="Do you want to remove selected keywords?"
                        data-selected="get-selected-keywords"
                        data-post-id=""
                ></button>
            </div>

            <div class="top-scrollbar-wrapper">
                <div class="top-scrollbar">
                </div>
            </div>
            <div class="bottom">
                <div class="seoaic-keywords-table">
                    <?php
                    if ($isRankInProgress) {
                        ?>
                        <input type="hidden" name="is_rank_in_progress" id="is_rank_in_progress" value="1">
                        <?php
                    }
                    if (!empty($keywords)) {
                        ?>
                        <div class="row-line heading">
                            <div class="row-line-container">
                                <div class="check">
                                    <input name="seoaic-select-all-keywords" type="checkbox">
                                </div>

                                <div class="keyword text-center" data-column="keyword">
                                    <?php _e('Keywords', 'seoaic');?>
                                    <span class="sorting">
                                        <span class="asc">&darr;</span>
                                        <span class="desc">&darr;</span>
                                    </span>
                                </div>

                                <div class="category text-center">
                                    <label for="intent-filter"></label>
                                    <select id="category-filter">
                                        <option value="_all" selected><?php _e('All Clusters', 'seoaic');?></option>
                                        <option value="_without_cluster"><?php _e('Without Cluster', 'seoaic');?></option>
                                        <?php echo $keywordsCategoriesOptions;?>
                                    </select>
                                </div>

                                <div class="search-vol text-center" data-order="DESC"  data-column="search-vol">
                                    <span><?php _e('Search', 'seoaic');?><br><?php _e('volume', 'seoaic');?></span>
                                    <span class="sorting">
                                        <span class="asc">&darr;</span>
                                        <span class="desc">&darr;</span>
                                    </span>
                                </div>

                                <div class="difficulty text-center" data-column="difficulty">
                                    <?php _e('Difficulty', 'seoaic');?>
                                    <span class="sorting">
                                        <span class="asc">&darr;</span>
                                        <span class="desc">&darr;</span>
                                    </span>
                                </div>

                                <div class="cpc text-center" data-column="cpc">
                                    <?php _e('CPC', 'seoaic');?>
                                    <span class="sorting">
                                        <span class="asc">&darr;</span>
                                        <span class="desc">&darr;</span>
                                    </span>
                                </div>

                                <div class="rank text-center"><?php _e('Rank', 'seoaic');?></div>

                                <div class="serp competitors text-center">
                                    <span><?php _e('SERP', 'seoaic');?><br><?php _e('Competitors', 'seoaic');?></span>
                                </div>

                                <div class="search-intent">
                                    <label for="intent-filter"></label>
                                    <select id="intent-filter">
                                        <option value="" selected><?php _e('All Search Intent', 'seoaic');?></option>
                                        <option value="informational"><?php _e('Informational', 'seoaic');?></option>
                                        <option value="navigational"><?php _e('Navigational', 'seoaic');?></option>
                                        <option value="commercial"><?php _e('Commercial', 'seoaic');?></option>
                                        <option value="transactional"><?php _e('Transactional', 'seoaic');?></option>
                                    </select>
                                </div>

                                <div class="created text-center">
                                    <?php _e('Created', 'seoaic');?>
                                    <span class="sorting">
                                        <span class="asc">&darr;</span>
                                        <span class="desc">&darr;</span>
                                    </span>
                                </div>

                                <div class="location text-center">
                                    <?php
                                    _e('Country', 'seoaic');
                                    echo '</br>';
                                    _e('Language', 'seoaic');
                                    ?>
                                </div>

                                <div class="link text-center"><?php _e('Link', 'seoaic');?></div>

                                <div class="delete"></div>
                            </div>
                        </div>
                        <?php
                        echo $SEOAIC->keywords->makeKeywordsTableMarkup($keywords);
                    } else {
                        _e('No keywords at the moment', 'seoaic');
                    }
                    ?>
                </div>
            </div>

        </div>
        <div class="lds-dual-ring"></div>


    </div>
</div>