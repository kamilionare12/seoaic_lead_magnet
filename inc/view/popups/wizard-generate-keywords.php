<?php

use SEOAIC\Wizard;

defined( 'ABSPATH' ) || exit;

?>
<div id="wizard_generate_keywords" class="seoaic-modal">
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
            <form id="wizard_generate_keywords_form" class="seoaic-form" method="post" data-callback="window_reload">
                <input class="seoaic-form-item" type="hidden" name="action" value="seoaic_generate_keywords_prompt">
                <label class="text-label"><?php _e('Number of keywords', 'seoaic');?>: <b><?php echo Wizard::KEYWORDS_COUNT;?></b></label>
                <div class="seoaic-popup__field">
                    <label class="text-label" for="wizard_keywords_prompt_field"><?php _e('Custom prompt', 'seoaic');?></label>
                    <textarea name="keywords_prompt" class="seoaic-form-item" id="wizard_keywords_prompt_field"></textarea>
                </div>

                <div class="keywords-loc-and-lang-wrapper d-flex seoaic-gap-15">
                    <div class="seoaic-popup__field seoaic-terms-selector seoaic-w-100">
                        <label class="text-label mb-13"><?php _e('Location', 'seoaic');?></label>
                        <select name="location" required class="seoaic-form-item form-select"></select>
                    </div>

                    <div class="seoaic-popup__field seoaic-terms-selector seoaic-w-100">
                        <label class="text-label mb-13"><?php _e('Language', 'seoaic');?></label>
                        <select name="language" required class="seoaic-form-item form-select"></select>
                    </div>
                </div>
            </form>
        </div>
        <div class="seoaic-popup__footer flex-right">
            <button id="btn-generate-keywords" type="submit" form="wizard_generate_keywords_form" class="seoaic-popup__btn"><?php _e('Generate Keywords', 'seoaic');?></button>
        </div>
    </div>
</div>
