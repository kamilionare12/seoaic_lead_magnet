<?php

use SEOAIC\Wizard;

defined( 'ABSPATH' ) || exit;

?>
<div id="wizard_generate_ideas" class="seoaic-modal">
    <div class="seoaic-modal-background seoaic-modal-close"></div>
    <div class="seoaic-popup">
        <div class="seoaic-popup__header">
            <span class="seoaic-modal-close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L15 15M15 1L1 15" stroke="#F1EBF7" stroke-width="2"/>
                </svg>
            </span>
            <h3><?php _e('Generate Ideas', 'seoaic');?></h3>
        </div>
        <div class="seoaic-popup__content">
            <form id="wizard_generate_ideas_form" class="seoaic-form" method="post" data-callback="window_reload">
                <input class="seoaic-form-item" type="hidden" name="action" value="seoaic_generate_keywords_prompt">
                <label class="text-label"><?php _e('Number of Ideas', 'seoaic');?>: <b><?php echo Wizard::IDEAS_COUNT;?></b></label>
                <div class="seoaic-popup__field">
                    <label class="text-label mb-10"><?php _e('Idea type', 'seoaic');?></label>
                    <select name="idea_template_type" class="seoaic-form-item form-select">
                        <?php
                        foreach (seoaic_get_prompt_template_types() as $key => $template_type) {
                            ?>
                            <option value="<?php echo esc_attr($key);?>"><?php esc_html_e($template_type);?></option>
                            <?php
                        }
                        ?>
                    </select>
                </div>
                <div class="seoaic-popup__field">
                    <label class="text-label" for="wizard_ideas_prompt_field"><?php _e('Custom prompt', 'seoaic');?></label>
                    <textarea name="idea_prompt" class="seoaic-form-item" id="wizard_ideas_prompt_field"></textarea>
                </div>
            </form>
        </div>
        <div class="seoaic-popup__footer flex-right">
            <button id="btn-generate-ideas" type="submit" form="wizard_generate_ideas_form" class="seoaic-popup__btn"><?php _e('Generate Ideas', 'seoaic');?></button>
        </div>
    </div>
</div>
