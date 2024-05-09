<?php

defined( 'ABSPATH' ) || exit;

?>
<div id="generate-terms" class="seoaic-modal">
    <div class="seoaic-modal-background seoaic-modal-close"></div>
    <div class="seoaic-popup generate-terms">
        <div class="seoaic-popup__header">
            <span class="seoaic-modal-close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L15 15M15 1L1 15" stroke="#F1EBF7" stroke-width="2"/>
                </svg>
            </span>
            <div class="heading">
                <h3><?php echo __('Search Terms Suggestions', 'seoaic'); ?></h3>
                <p><?php echo __('Main keyword: ', 'seoaic'); ?><span><?php echo __('', 'seoaic'); ?></span></p>
            </div>
        </div>
        <div class="seoaic-popup__content">
            <div class="table">
                <div class="heading">
                    <div class="table-row">
                        <div class="domain"><?php echo __( 'Keyword', 'seoaic' ); ?></div>
                        <div class="avg_position"><?php echo __( 'Search volume', 'seoaic' ); ?></div>
                        <div class="visibility selected"><?php echo __( 'Selected', 'seoaic' ); ?> <span>(0)</span> <a class="unselect" href="#"><?php echo __( 'Unselect All', 'seoaic' ); ?></a></div>
                    </div>
                </div>
                <div class="body"></div>
            </div>
        </div>
        <div class="seoaic-popup__footer">
            <div class="add-additional-terms">
                <div class="field-group">
                    <label for="add-idea-name" class="text-label"><?php esc_html_e('Add Sub-terms (separated by comma). Ex:
                        cup,table', 'seoaic');?>
                    </label>
                    <input id="add-idea-name" class="seoaic-form-item" type="text" name="item_name" value=""
                           required="">
                </div>
                <div class="open-button">
                    <a data-open="<?php esc_attr_e('Add Sub-Terms Manually', 'seoaic');?>" data-close="<?php esc_attr_e('Close', 'seoaic');?>" href="#"><?php esc_attr_e('Add Sub-Terms Manually', 'seoaic');?></a>
                </div>
            </div>
            <button type="button" data-action="seoaicAddSubTerms" class="seoaic-popup__btn seoaic-modal-close generate_terms"><?php echo __( 'Add Selected', 'seoaic' ); ?></button>
        </div>
    </div>
</div>