<?php

defined( 'ABSPATH' ) || exit;

?>
<div id="seoaic-confirm-modal" class="seoaic-modal">
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
        <div class="seoaic-popup__content fs-18">
            <div id="confirm-modal-content" class="modal-content"></div>
            <form id="confirm-form" class="seoaic-form" method="post" data-callback="">
                <input id="confirm_action" class="seoaic-form-item" type="hidden" name="action" value="">
                <input id="confirm_item_id" class="seoaic-form-item" type="hidden" name="item_id" value="">
                <div class="additional-items"></div>
            </form>
        </div>
        <div class="seoaic-popup__footer flex-justify">
            <button type="button" class="seoaic-popup__btn-left seoaic-modal-close"><?= __( 'No', 'seoaic' ); ?></button>
            <button type="submit" form="confirm-form" class="seoaic-popup__btn seoaic-popup__btn-right"><?= __( 'Yes', 'seoaic' ); ?></button>
        </div>
    </div>
</div>
