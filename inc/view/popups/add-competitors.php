<?php

defined('ABSPATH') || exit;

global $SEOAIC;
?>
<div id="add-competitors" class="seoaic-modal">
    <div class="seoaic-modal-background seoaic-modal-close"></div>
    <div class="seoaic-popup add-competitors">
        <div class="seoaic-popup__header">
            <span class="seoaic-modal-close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L15 15M15 1L1 15" stroke="#F1EBF7" stroke-width="2"/>
                </svg>
            </span>
            <div class="heading">
                <h3><?php echo __('Competitors', 'seoaic'); ?></h3>
                <p><?php echo __('keyword: ', 'seoaic'); ?>
                    <span><?php echo __('socal media marketing', 'seoaic'); ?></span></p>
            </div>
        </div>
        <div class="seoaic-popup__content">
            <div class="top">
                <?php echo $SEOAIC->rank->my_article_popup_top_table_analysis(); ?>
            </div>
            <div class="table">
                <div class="averages">
                    <div class="label-row">Averages</div>
                    <div class="row-line article-analysis">

                        <div class="h1-titles">
                            —
                        </div>
                        <div class="h2-titles">
                            —
                        </div>
                        <div class="sentences">
                            —
                        </div>
                        <div class="keyword-density">
                            —
                        </div>
                        <div class="words">
                            —
                        </div>
                        <div class="paragraphs">
                            —
                        </div>
                        <div class="readability">
                            —
                        </div>
                    </div>
                </div>
                <div class="heading">
                    <div class="table-row">
                        <div class="visibility selected"><?php echo __('Selected', 'seoaic'); ?> <span>(0)</span> <a
                                    class="unselect" href="#"><?php echo __('Unselect All', 'seoaic'); ?></a></div>
                        <div class="domain"><?php echo __('Domain', 'seoaic'); ?></div>
                        <div class="avg_position"><?php echo __('Google Position', 'seoaic'); ?></div>
                    </div>
                </div>
                <div class="content-table">
                    <div class="body"></div>
                    <div class="competitor-article">
                        <a href="#" class="load-more-btn" data-action="">View more</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="seoaic-popup__pre-footer">
            <button type="button" data-action=""
                    class="seoaic-popup__btn open-modal open-content-explorer"><?php echo __('Open Content Explorer', 'seoaic'); ?></button>
        </div>
        <div class="seoaic-popup__footer">
            <button type="button" data-action="seoaicAddedCompetitors"
                    class="seoaic-popup__btn seoaic-modal-close add_competitors"><?php echo __('Save', 'seoaic'); ?></button>
        </div>
    </div>
</div>