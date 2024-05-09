<?php

defined( 'ABSPATH' ) || exit;

global $SEOAIC;

?>
<div id="seoaic_post_mass_translate_modal" class="seoaic-modal">
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
            <form id="post-mass-translate-form" class="mt-0 seoaic-form" method="post" data-callback="window_reload">
                <input class="seoaic-form-item" type="hidden" name="action" value="seoaic_posts_mass_translate">
                <div class="additional-items mt-0"></div>
                <div class="seoaic-popup__field">
                    <label class="mb-10" for="seoaic_multilanguages"><?php _e('Translate to Language', 'seoaic');?></label>
                    <?php
                    $SEOAIC->multilang->get_multilang_checkboxes("", "radio");
                    ?>
                </div>
            </form>
        </div>
        <div class="seoaic-popup__footer">
            <div class="flex-justify">
                <button form="post-mass-translate-form" id="posts-mass-translate-button" type="submit" class="seoaic-popup__btn seoaic-popup__btn"><?php _e('Translate', 'seoaic');?></button>
            </div>
        </div>
    </div>
</div>