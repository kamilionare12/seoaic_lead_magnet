<?php

defined( 'ABSPATH' ) || exit;

global $SEOAIC;
?>
<div id="seoaic_post_mass_edit_modal" class="seoaic-modal">
    <div class="seoaic-modal-background seoaic-modal-close"></div>
    <div class="seoaic-popup">
        <div class="seoaic-popup__header">
            <span class="seoaic-modal-close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L15 15M15 1L1 15" stroke="#F1EBF7" stroke-width="2"/>
                </svg>
            </span>
            <h3 class="modal-title" data-title="<?php echo __('Confirm', 'seoaic');?>"><?php echo __('Confirm', 'seoaic');?></h3>
        </div>
        <div class="seoaic-popup__content fs-18">
            <label class="modal-content mb-10"></label>
            <form id="post-mass-edit-form" class="mt-0 seoaic-form" method="post" data-callback="window_reload">
                <input class="seoaic-form-item" type="hidden" name="action" value="seoaic_posts_mass_edit">
                <div class="additional-items mt-0"></div>
                <div class="seoaic-popup__field">
                    <label class="mb-10" for="posts_edit_mass_prompt"><?php echo __('Custom prompt', 'seoaic');?></label>
                    <textarea name="mass_prompt" required id="posts_edit_mass_prompt" class="seoaic-form-item mt-0"></textarea>
                </div>
            </form>
        </div>
        <div class="seoaic-popup__footer">
            <div class="flex-justify">
                <button form="post-mass-edit-form" id="posts-mass-edit-button" type="submit" class="seoaic-popup__btn seoaic-popup__btn"><?php echo  __( 'Edit', 'seoaic');?></button>
            </div>
        </div>
    </div>
</div>
