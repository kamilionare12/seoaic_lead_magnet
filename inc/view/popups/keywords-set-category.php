<?php

defined('ABSPATH') || exit;

?>
<div id="keywords-set-category-modal" class="seoaic-modal">
    <div class="seoaic-modal-background seoaic-modal-close"></div>
    <div class="seoaic-popup rank-popup">
        <div class="seoaic-popup__header">
            <span class="seoaic-modal-close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L15 15M15 1L1 15" stroke="#F1EBF7" stroke-width="2"/>
                </svg>
            </span>
            <h3><?php _e('Set Cluster', 'seoaic');?></h3>
        </div>
        <div class="seoaic-popup__content">
            <form id="keywords_set_category_form">
                <input type="hidden" name="keyword_id" value="">
                <label for="seoaic_keywords_categories">Cluster</label>
                <select name="seoaic_keywords_categories" id="seoaic_keywords_categories" class="seoaic-form-item form-select">
                    <option value="">None</option>
                </select>
            </form>
        </div>
        <div class="seoaic-popup__footer">
            <button type="submit"
                form="keywords_set_category_form"
                class="seoaic-popup__btn"
            >
                <?php _e('Set', 'seoaic');?>
            </button>
        </div>
    </div>
</div>