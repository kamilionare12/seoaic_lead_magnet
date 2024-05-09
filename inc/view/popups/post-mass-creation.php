<?php

defined( 'ABSPATH' ) || exit;

use SEOAIC\SEOAIC;
use SEOAIC\SEOAIC_POSTS;
use SEOAIC\SEOAIC_SETTINGS;

global $SEOAIC, $SEOAIC_OPTIONS;

$postPromptTmplts = $SEOAIC_OPTIONS['seoaic_posts_mass_generate_prompt_templates'] ?? [];
?>
<div id="seoaic-post-mass-creation-modal" class="seoaic-modal">
    <div class="seoaic-modal-background seoaic-modal-close"></div>
    <div class="seoaic-popup">
        <div class="seoaic-popup__header">
            <span class="seoaic-modal-close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L15 15M15 1L1 15" stroke="#F1EBF7" stroke-width="2"/>
                </svg>
            </span>
            <h3 class="modal-title" data-title="<?=__( 'Confirm', 'seoaic'); ?>"><?=__( 'Confirm', 'seoaic'); ?></h3>
        </div>
        <div class="seoaic-popup__content fs-18 pb-0 position-relative">
            <?php
            if (
                !empty($postPromptTmplts)
                && is_array($postPromptTmplts)
            ) {
                ?>
                <div class="prompt-templates-section position-absolute">
                    <div class="label d-inline-block position-relative fs-16 mb-13">
                        <b><?php _e('Prompt templates', 'seoaic');?></b>
                    </div>
                    <div class="templates d-flex">
                        <?php
                        foreach ($postPromptTmplts as $i => $promptTemplate) {
                            ?>
                            <div class="template-wrapper">
                                <div class="template fs-small position-relative">
                                    <div class="template-text"><?php echo esc_html($promptTemplate);?></div>
                                    <div class="prompt-editor-wrapper">
                                        <textarea class="prompt-template-editor"><?php echo esc_html($promptTemplate);?></textarea>
                                        <div class="prompt-editor-buttons d-flex">
                                            <div
                                                class="seoaic-editor-save seoaic-editor-btn"
                                                title="<?php _e('Save template', 'seoaic');?>"
                                                data-id="<?php echo esc_attr($i);?>"
                                                data-action="seoaic_posts_mass_generate_save_prompt_template"
                                            ></div>
                                            <div class="seoaic-editor-cancel seoaic-editor-btn" title="<?php _e('Cancel', 'seoaic');?>"></div>
                                        </div>
                                    </div>
                                    <div class="buttons position-absolute">
                                        <button title="<?php _e('Edit template', 'seoaic');?>" type="button" class="button button-success seoaic-edit-prompt-template"></button>
                                        <button title="<?php _e('Delete template', 'seoaic');?>" type="button" class="button button-success seoaic-delete-prompt-template ml-5"></button>

                                    </div>
                                    <div class="delete-confirm-section position-absolute">
                                        <div class="prompt-deletion position-absolute">
                                            <div class="prompt-deletion-title">Delete?</div>
                                            <div class="prompt-deletion-body d-flex">
                                                <div
                                                    class="seoaic-prompt-confirm-delete-yes"
                                                    title="<?php _e('Yes', 'seoaic');?>"
                                                    data-id="<?php echo esc_attr($i);?>"
                                                    data-action="seoaic_posts_mass_generate_delete_prompt_template"
                                                ></div>
                                                <div class="seoaic-prompt-confirm-delete-no" title="<?php _e('No', 'seoaic');?>"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                <?php
            }
            ?>

            <label class="modal-content mb-10"></label>
            <form id="post-mass-creation-form" class="mt-0 seoaic-form" method="post" data-callback="window_reload">
                <input class="seoaic-form-item" type="hidden" name="action" value="seoaic_posts_mass_create">
                <div class="additional-items mass-create mt-0"></div>
                <div class="seoaic-popup__field">
                    <label class="mb-10"><?php echo __( 'Custom prompt', 'seoaic' ); ?></label>
                    <textarea name="mass_prompt" class="seoaic-form-item mt-0"></textarea>
                    <?php echo SEOAIC::seoaic_select_service(); ?>
                </div>
                <div class="seoaic-popup__field">
                    <label class="mb-10"><?php echo __( 'Select knowledge base', 'seoaic' ); ?></label>
                    <select name="seoaic_knowledge_base" class="seoaic-form-item form-select">
                        <option value="" id="select_knowledge_default" selected disabled hidden>Choose here knowledge base</option>
                    </select>
                </div>
                <div class="seoaic-popup__field">
                    <label class="mb-10"><?php echo __( 'Set thumbnail for selected posts', 'seoaic' ); ?></label>
                    <?php
                        echo SEOAIC_POSTS::seoaicImageUploader();
                    ?>
                </div>
                <?php
                $categories = seoaic_get_categories(SEOAIC_SETTINGS::getSEOAICPostType());
                ?>
                <div id="seoaic-idea-content-category" class="col-12 seoaic-select-post-type-cat">
                    <label for="seoaic_default_category"><?php _e('Posts categories', 'seoaic');?></label>
                    <div class="terms-select">
                        <?php echo $categories;?>
                    </div>
                </div>
            </form>
            <div class="lds-indication">
                <div class="indication-title flex-justify">
                    <span class="on-generating">Generating...
                        <span class="generating-process">
                            <span class="dn">Hold on tight - Your content is being generated</span>
                            <span class="dn">Hold on tight - Your content is being reviewed</span>
                            <span class="dn">Hold on tight - Your content is SEO optimised</span>
                            <span class="dn">Hold on tight - Your content is humanised</span>
                        </span>
                    </span>
                    <span class="vis-on-generated">Generated!</span> <span class="generating-num"><span class="generating-num-pos"></span>/<span class="generating-num-total"></span></span></div>
                <div class="indication position-relative"><span class="loader accent-bg"></span></div>
            </div>
        </div>
        <div class="seoaic-popup__footer">
            <div class="flex-justify modal-ranges">
                <div id="seoiac-modal-subtitles-range" class="seoaic-settings-range" data-min="0" data-max="15" data-step="1">
                    <label>Subtitles range:
                        <span class="range-min"><?=!empty($SEOAIC_OPTIONS['seoaic_subtitles_range_min']) ? $SEOAIC_OPTIONS['seoaic_subtitles_range_min'] : '0'; ?></span> -
                        <span class="range-max"><?=!empty($SEOAIC_OPTIONS['seoaic_subtitles_range_max']) ? $SEOAIC_OPTIONS['seoaic_subtitles_range_max'] : '15'; ?></span>
                        <input form="post-mass-creation-form" id="seoaic_modal_subtitles_range_min" class="seoaic-settings-range-min seoaic-form-item" type="hidden" name="seoaic_subtitles_range_min" value="<?=!empty($SEOAIC_OPTIONS['seoaic_subtitles_range_min']) ? $SEOAIC_OPTIONS['seoaic_subtitles_range_min'] : '0'; ?>">
                        <input form="post-mass-creation-form" id="seoaic_modal_subtitles_range_max" class="seoaic-settings-range-max seoaic-form-item" type="hidden" name="seoaic_subtitles_range_max" value="<?=!empty($SEOAIC_OPTIONS['seoaic_subtitles_range_max']) ? $SEOAIC_OPTIONS['seoaic_subtitles_range_max'] : '15'; ?>">
                    </label>
                    <div id="seoiac-modal-subtitles-range-slider" class="seoaic-settings-range-slider"></div>
                </div>

                <div id="seoiac-modal-words-range" class="seoaic-settings-range" data-min="0" data-max="2500" data-step="10">
                    <label>Post words range:
                        <span class="range-min"><?=!empty($SEOAIC_OPTIONS['seoaic_words_range_min']) ? $SEOAIC_OPTIONS['seoaic_words_range_min'] : '0'; ?></span> -
                        <span class="range-max"><?=!empty($SEOAIC_OPTIONS['seoaic_words_range_max']) ? $SEOAIC_OPTIONS['seoaic_words_range_max'] : '2500'; ?></span>
                        <input form="post-mass-creation-form" id="seoaic_modal_words_range_min" class="seoaic-settings-range-min seoaic-form-item" type="hidden" name="seoaic_words_range_min" value="<?=!empty($SEOAIC_OPTIONS['seoaic_words_range_min']) ? $SEOAIC_OPTIONS['seoaic_words_range_min'] : '0'; ?>">
                        <input form="post-mass-creation-form" id="seoaic_modal_words_range_max" class="seoaic-settings-range-max seoaic-form-item" type="hidden" name="seoaic_words_range_max" value="<?=!empty($SEOAIC_OPTIONS['seoaic_words_range_max']) ? $SEOAIC_OPTIONS['seoaic_words_range_max'] : '2500'; ?>">
                    </label>
                    <div id="seoiac-modal-words-range-slider" class="seoaic-settings-range-slider"></div>
                </div>
            </div>

            <?php if ( $SEOAIC->multilang->is_multilang() ) : ?>
            <div class="mb-10">
                <label>
                    <input form="post-mass-creation-form" id="seoaic-translate-from-origin" class="seoaic-form-item" type="checkbox" name="seoaic-translate-from-origin" value="yes" checked>
                    Translate posts from origin
                </label>
            </div>
            <?php endif; ?>

            <div class="flex-justify">
                <button type="button" class="seoaic-popup__btn-left seoaic-modal-close novis-on-generating novis-on-generated"><?= __( 'No', 'seoaic' ); ?></button>
                <button type="button" class="seoaic-popup__btn-right seoaic-modal-close seoaic-popup__btn vis-on-generated"><?= __( 'OK', 'seoaic' ); ?></button>

                <div class="hide-on-generating hide-on-generated seoaic-mass-creation-posting">
                    <select form="post-mass-creation-form" name="seoaic_post_status" class="seoaic_post_status seoaic-form-item">
                        <?php
                            $publish_delay = !empty($SEOAIC_OPTIONS['seoaic_publish_delay']) ? intval($SEOAIC_OPTIONS['seoaic_publish_delay']) : 0;
                            if ( $publish_delay > 0 ) :
                        ?>
                        <option value="delay">Delay publication</option>
                        <?php endif; ?>

                        <option value="publish">Publish immediately</option>
                        <option value="draft">Leave draft</option>

                        <?php if ( !empty($SEOAIC_OPTIONS['seoaic_schedule_days']) ) : ?>
                            <option value="schedule">Schedule publication</option>
                        <?php endif; ?>
                    </select>

                    <?php
                    $currentDate = date('Y-m-d');
                    $date = new DateTime($currentDate);
                    $publish_delay = !empty($SEOAIC_OPTIONS['seoaic_publish_delay']) ? intval($SEOAIC_OPTIONS['seoaic_publish_delay']) : 0;
                    $date->modify("+$publish_delay hours");
                    $delayDate = $date->format('Y-m-d');
                    ?>
                    <div class="idea-date-picker" style="display: none;">
                        <input type="date"
                               form="post-mass-creation-form"
                               class="seoaic-mass-idea-date seoaic-form-item"
                               name="seoaic-mass-idea-date"
                               value="<?=$delayDate?>"
                               min="<?=$delayDate?>"
                        >
                    </div>
                </div>

                <button form="post-mass-creation-form" id="posts-mass-generate-button" type="submit" class="seoaic-popup__btn seoaic-popup__btn-right hide-on-generated"><?= __( 'Generate', 'seoaic' ); ?></button>
            </div>
        </div>
    </div>
</div>
