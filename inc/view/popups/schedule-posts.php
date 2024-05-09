<?php

defined( 'ABSPATH' ) || exit;

?>
<div id="schedule-posts-modal" class="seoaic-modal">
    <div class="seoaic-modal-background seoaic-modal-close"></div>
    <div class="seoaic-popup">
        <div class="seoaic-popup__header">
            <span class="seoaic-modal-close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L15 15M15 1L1 15" stroke="#F1EBF7" stroke-width="2"/>
                </svg>
            </span>
            <h3><?php echo __( 'Schedule posting', 'seoaic' ); ?></h3>
        </div>
        <div class="seoaic-popup__content fs-18">
            <div class="modal-content"></div>
            <form id="schedule-posts-form" class="seoaic-form" method="post" data-callback="window_reload">
                <input class="seoaic-form-item" type="hidden" name="action" value="seoaic_schedule_posts">
                <div class="additional-items"></div>
                <div class="seoaic-popup__field">
                    <label class="text-label"><?php echo __( 'Choose your start posting date', 'seoaic' ); ?></label>
                    <div class="seoaic-date-picker">
                        <input type="text" name="idea_posting_date" value="<?=date('Y-m-d')?>" class="seoaic-date-picker-input mt-0 seoaic-form-item">
                        <div class="picker-call"></div>
                    </div>
                </div>
            </form>
        </div>
        <div class="seoaic-popup__footer flex-justify">
            <button type="button" class="seoaic-popup__btn-left seoaic-modal-close"><?= __( 'No', 'seoaic' ); ?></button>
            <button type="submit" form="schedule-posts-form" class="seoaic-popup__btn-right seoaic-popup__btn"><?php echo __( 'Schedule', 'seoaic' ); ?></button>
        </div>
    </div>
</div>
