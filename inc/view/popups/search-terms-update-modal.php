<?php

defined( 'ABSPATH' ) || exit;

?>
<div id="search-terms-update-modal" class="seoaic-modal">

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
            <div class="modal-content"></div>
            <div class="status-update">
                <div class="numeric fs-24 fw-700 mt-20 mb-19">
                    <span class="from">0</span>
                    <span class="sep">/</span>
                    <span class="to"></span>
                </div>
                    <div class="lds-indication show-on-scanning">
                        <div class="indication position-relative"><span class="loader accent-bg"></span></div>
                    </div>
            </div>
            <ul class="status-terms-added"></ul>
        </div>
        <div class="seoaic-popup__footer flex-justify">
            <button type="button" class="seoaic-popup__btn-left seoaic-modal-close"><?= __( 'Close', 'seoaic' ); ?></button>
            <button type="button" form="confirm-form" class="seoaic-popup__btn seoaic-popup__btn-right"><?= __( 'Update', 'seoaic' ); ?></button>
        </div>
    </div>
</div>