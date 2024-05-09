<?php

defined('ABSPATH') || exit;

global $SEOAIC;
?>
<div id="settings-description-generate-modal" class="seoaic-modal">
    <div class="seoaic-modal-background seoaic-modal-close"></div>
    <div class="seoaic-popup">
        <div class="seoaic-popup__header">
            <span class="seoaic-modal-close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L15 15M15 1L1 15" stroke="#F1EBF7" stroke-width="2"/>
                </svg>
            </span>
            <h3><?php _e('Generate Description', 'seoaic');?></h3>
        </div>
        <div class="seoaic-popup__content">
            <form method="post"
                id="settings-description-generate-form"
                class="seoaic-form"
            >
                <input type="hidden" name="action" value="seoaic_add_keyword" class="seoaic-form-item">
                <div class="seoaic-popup__field">
                    <label class="text-label"><?php _e('Write something about company... ', 'seoaic');?></label>
                    <textarea required
                            name="prompt"
                            class="seoaic-form-item"
                            rows="5"
                            placeholder="... and we'll do the rest."
                    ></textarea>
                </div>
            </form>
        </div>
        <div class="seoaic-popup__footer">
            <button type="submit"
                    form="settings-description-generate-form"
                    id="btn-add-keyword"
                    class="seoaic-popup__btn"

            ><?php _e('Generate', 'seoaic');?></button>
        </div>
    </div>
</div>