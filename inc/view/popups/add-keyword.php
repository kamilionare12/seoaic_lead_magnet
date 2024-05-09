<?php

use SEOAIC\keyword_types\KeywordHeadTermType;

defined('ABSPATH') || exit;

global $SEOAIC;

$keywordTypesRadios = $SEOAIC->keywords->makeKeywordTypesRadios();
$headTermKeywords = $SEOAIC->keywords->getKeywordsByType(new KeywordHeadTermType());
$id = !empty($headTermKeywords) ? $headTermKeywords[0]['id'] : '';
$midTailTermKeywords = $SEOAIC->keywords->getChildKeywordsByParentID($id);
?>
<div id="add-keyword-modal" class="seoaic-modal" data-cross-modal-id="generate-keywords">
    <div class="seoaic-modal-background seoaic-modal-close"></div>
    <div class="seoaic-popup">
        <div class="seoaic-popup__header">
            <span class="seoaic-modal-close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L15 15M15 1L1 15" stroke="#F1EBF7" stroke-width="2"/>
                </svg>
            </span>
            <h3><?php _e('Add The Keyword', 'seoaic');?></h3>
        </div>
        <div class="seoaic-popup__content">
            <form id="add-keywords-form" class="seoaic-form" method="post">
                <input type="hidden" name="action" value="seoaic_add_keyword" class="seoaic-form-item">
                <div class="seoaic-popup__field">
                    <label class="text-label"><?php _e('Add keywords (separated by comma). Ex: cup,table', 'seoaic');?></label>
                    <input type="text" name="item_name" value="" required class="seoaic-form-item">
                </div>

                <div class="keywords-loc-and-lang-wrapper d-flex seoaic-gap-15">
                    <div class="seoaic-popup__field seoaic-w-100">
                        <label class="text-label mb-13"><?php _e('Location', 'seoaic');?></label>
                        <select name="location" required class="seoaic-form-item form-select"></select>
                    </div>

                    <div class="seoaic-popup__field seoaic-w-100">
                        <label class="text-label mb-13"><?php _e('Language', 'seoaic');?></label>
                        <select name="language" required class="seoaic-form-item form-select"></select>
                    </div>
                </div>

                <div class="seoaic-popup__field">
                    <label class="text-label"><?php _e('Keywords type', 'seoaic');?></label>
                    <div class="seoaic-keyword-type-wrapper seoaic-keyword-type-wrapper-add w-100 mt-15">
                        <?php echo $keywordTypesRadios;?>
                    </div>
                </div>

                <div class="seoaic-popup__field seoaic-terms-selector seoaic-head-terms-wrapper">
                    <label class="text-label mb-13"><?php _e('Head term', 'seoaic');?></label>
                    <select name="head_term_id" class="seoaic-form-item form-select">
                        <?php
                        echo $SEOAIC->keywords::makeKeywordsOptionsTags($headTermKeywords);
                        ?>
                    </select>
                </div>

                <div class="dn seoaic-popup__field seoaic-terms-selector seoaic-mid-tail-terms-wrapper">
                    <label class="text-label mb-13"><?php _e('Mid-tail keyword', 'seoaic');?></label>
                    <select name="mid_tail_id" class="seoaic-form-item form-select">
                        <?php
                        echo $SEOAIC->keywords::makeKeywordsOptionsTags($midTailTermKeywords);
                        ?>
                    </select>
                </div>
            </form>
        </div>
        <div class="seoaic-popup__footer">
            <button type="submit"
                    form="add-keywords-form"
                    id="btn-add-keyword"
                    class="seoaic-popup__btn"
                    data-type="add"
            ><?php _e('Add', 'seoaic');?></button>
        </div>
    </div>
</div>