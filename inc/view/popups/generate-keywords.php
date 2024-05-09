<?php

use SEOAIC\keyword_types\KeywordHeadTermType;

defined('ABSPATH') || exit;

global $SEOAIC;

$keywordTypesRadios = $SEOAIC->keywords->makeKeywordTypesRadios();
$headTermKeywords = $SEOAIC->keywords->getKeywordsByType(new KeywordHeadTermType());
$id = !empty($headTermKeywords) ? $headTermKeywords[0]['id'] : '';
$midTailTermKeywords = $SEOAIC->keywords->getChildKeywordsByParentID($id);
?>
<div id="generate-keywords" class="seoaic-modal" data-cross-modal-id="add-keyword-modal">
    <div class="seoaic-modal-background seoaic-modal-close"></div>
    <div class="seoaic-popup">
        <div class="seoaic-popup__header">
            <span class="seoaic-modal-close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L15 15M15 1L1 15" stroke="#F1EBF7" stroke-width="2"/>
                </svg>
            </span>
            <h3><?php _e('Generate Keywords', 'seoaic');?></h3>
        </div>
        <div class="seoaic-popup__content">
            <form id="generate-keywords-form" class="seoaic-form" method="post" data-callback="window_reload">
                <input class="seoaic-form-item" type="hidden" name="action" value="seoaic_generate_keywords_prompt">

                <div class="seoaic-popup__field">
                    <label class="text-label"><?php _e('How many keywords do you want to generate?', 'seoaic');?></label>
                    <input type="number" name="keywords_count" value="10" class="seoaic-form-item">
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
                    <label class="text-label mb-13"><?php _e('Keywords type', 'seoaic');?></label>
                    <div class="seoaic-keyword-type-wrapper seoaic-keyword-type-wrapper-generate w-100">
                        <?php echo $keywordTypesRadios;?>
                    </div>
                </div>

                <div class="seoaic-popup__field seoaic-keywords-custom-prompt-wrapper">
                    <label class="text-label"><?php _e('Custom prompt', 'seoaic');?></label>
                    <textarea name="keywords_prompt" class="seoaic-form-item"></textarea>
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
        <div class="seoaic-popup__footer flex-right">
            <button id="btn-generate-keywords" type="submit" form="generate-keywords-form" class="seoaic-popup__btn"><?php echo __('Generate Keywords', 'seoaic'); ?></button>
        </div>
    </div>
</div>
