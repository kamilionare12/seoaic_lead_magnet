<?php

use SEOAIC\Wizard;

defined( 'ABSPATH' ) || exit;

?>
<div id="wizard_generate_posts" class="seoaic-modal">
    <div class="seoaic-modal-background seoaic-modal-close"></div>
    <div class="seoaic-popup">
        <div class="seoaic-popup__header">
            <span class="seoaic-modal-close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L15 15M15 1L1 15" stroke="#F1EBF7" stroke-width="2"/>
                </svg>
            </span>
            <h3><?php _e('Generate Post', 'seoaic');?></h3>
        </div>
        <div class="seoaic-popup__content">
            <label class="modal-content mb-10">You will generate post from <b class="additional-items-amount">1</b> following idea:</label>
            <form id="wizard_generate_posts_form" class="seoaic-form" method="post" data-callback="window_reload">
                <input class="seoaic-form-item" type="hidden" name="action" value="seoaic_wizard_posts_mass_create">
                <div class="additional-items mass-create mt-0"></div>
                <div class="seoaic-popup__field">
                    <label class="text-label" for="wizard_posts_prompt_field"><?php _e('Custom prompt', 'seoaic');?></label>
                    <textarea name="mass_prompt" class="seoaic-form-item" id="wizard_posts_prompt_field"></textarea>
                </div>
            </form>
        </div>
        <div class="seoaic-popup__footer flex-right">
            <button id="btn-generate-posts" type="submit" form="wizard_generate_posts_form" class="seoaic-popup__btn" disabled><?php _e('Generate Post', 'seoaic');?></button>
        </div>
    </div>
</div>
