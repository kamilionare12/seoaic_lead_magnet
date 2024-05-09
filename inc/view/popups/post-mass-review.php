<?php

defined( 'ABSPATH' ) || exit;

?>
<div id="seoaic_post_mass_review_modal" class="seoaic-modal">
    <div class="seoaic-modal-background seoaic-modal-close"></div>
    <div class="seoaic-popup">
        <div class="seoaic-popup__header">
            <span class="seoaic-modal-close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L15 15M15 1L1 15" stroke="#F1EBF7" stroke-width="2"/>
                </svg>
            </span>
            <h3 class="modal-title" data-title="<?php _e('Confirm', 'seoaic');?>"><?php _e('Confirm', 'seoaic');?></h3>
        </div>
        <div class="seoaic-popup__content fs-18">
            <label class="modal-content mb-10"></label>
            <form id="post-mass-review-form" class="mt-0 seoaic-form" method="post" data-callback="window_reload">
                <input class="seoaic-form-item" type="hidden" name="action" value="seoaic_posts_mass_review">
                <div class="additional-items mt-0"></div>
                <div class="seoaic-popup__field">
                    <label class="mb-10" for="posts_review_mass_prompt"><?php _e('Custom prompt', 'seoaic');?></label>
                    <textarea name="mass_prompt" required id="posts_review_mass_prompt" class="seoaic-form-item mt-0"></textarea>
                </div>
            </form>
        </div>
        <div class="seoaic-popup__footer">
            <div class="flex-justify">
                <button form="post-mass-review-form" id="posts-mass-review-button" type="submit" class="seoaic-popup__btn seoaic-popup__btn"><?php _e('Review', 'seoaic');?></button>
            </div>
        </div>
    </div>
</div>
