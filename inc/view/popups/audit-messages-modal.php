<?php

defined( 'ABSPATH' ) || exit;

?>
<div id="seoaic-messages-modal" class="seoaic-modal">
    <div class="seoaic-modal-background seoaic-modal-close"></div>
    <div class="seoaic-popup">
        <div class="seoaic-popup__header">
            <span class="seoaic-modal-close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L15 15M15 1L1 15" stroke="#F1EBF7" stroke-width="2"/>
                </svg>
            </span>
            <h3 class="modal-title"></h3>
        </div>
        <div class="seoaic-popup__content fs-18">
            <div id="confirm-modal-content" class="modal-content"></div>
        </div>
        <div class="seoaic-popup__footer flex-right">
            <button type="button" class="seoaic-popup__btn seoaic-popup__btn-right seoaic-modal-close"><?php echo __( 'OK', 'seoaic' ); ?></button>
        </div>
    </div>
</div>
