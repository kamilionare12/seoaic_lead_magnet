<?php

defined('ABSPATH') || exit;

global $SEOAIC;

$keywordsCategories = $SEOAIC->keywords->getKeywordsCategories();
?>
<div id="keywords-manage-categories-modal" class="seoaic-modal">
    <div class="seoaic-modal-background seoaic-modal-close"></div>
    <div class="seoaic-popup rank-popup">
        <div class="seoaic-popup__header">
            <span class="seoaic-modal-close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L15 15M15 1L1 15" stroke="#F1EBF7" stroke-width="2"/>
                </svg>
            </span>
            <h3><?php _e('Manage Clusters', 'seoaic');?></h3>
        </div>
        <div class="seoaic-popup__content">
            <form action="" id="keywords_add_category_form" class="mb-30">
                <input type="text" name="keywords_category_name" id="keywords_category_name" placeholder="Cluster name">
                <button type="submit">Add Cluster</button>
            </form>
            <div class="table">
                <div class="body">
                    <?php
                    foreach ($keywordsCategories as $keywordsCategory) {
                        echo $SEOAIC->keywords::makeKeywordCategoryRow($keywordsCategory);
                    }
                    ?>
                </div>
            </div>
        </div>
        <div class="seoaic-popup__footer">
            <button type="button" class="seoaic-popup__btn seoaic-modal-close"><?php _e('Close', 'seoaic');?></button>
        </div>
    </div>
</div>