<?php

defined( 'ABSPATH' ) || exit;

?>
<div id="wizard-view-post-content" class="seoaic-modal">
    <div class="seoaic-modal-background seoaic-modal-close"></div>
    <div class="seoaic-popup seoaic-popup-wide">
        <div class="seoaic-popup__header">
            <span class="seoaic-modal-close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L15 15M15 1L1 15" stroke="#F1EBF7" stroke-width="2"/>
                </svg>
            </span>
            <h3 class="modal-title" data-title="<?php echo __( 'Generated Post', 'seoaic');?>"><?php echo __( 'Confirm', 'seoaic');?></h3>
        </div>
        <div class="seoaic-popup__content fs-16">
            <div id="confirm-modal-content" class="modal-content-parse">
                <h2 class="post-title mb-30"></h2>
                <div class="post-content"></div>
            </div>
        </div>
    </div>
</div>
