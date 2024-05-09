<?php

defined( 'ABSPATH' ) || exit;

?>
<div id="generated-post" class="seoaic-modal">
    <div class="seoaic-modal-background seoaic-modal-close"></div>
    <div class="seoaic-popup">
        <div class="seoaic-popup__header">
            <span class="seoaic-modal-close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L15 15M15 1L1 15" stroke="#F1EBF7" stroke-width="2"/>
                </svg>
            </span>
            <h3><?php echo __( 'Generated Post', 'seoaic' ); ?></h3>
        </div>
        <form id="generated-post-form" class="seoaic-form" method="post" data-callback="window_reload">
            <input class="seoaic-form-item" type="hidden" name="action" value="seoaic_publish_post">
            <input id="generated_post_id" class="seoaic-form-item" type="hidden" name="item_id" value="">
        </form>
        <div class="seoaic-popup__content">
            <div class="modal-content"></div>
        </div>
        <div class="seoaic-popup__footer">
            <select form="generated-post-form" name="seoaic_post_status" class="seoaic_post_status seoaic-form-item">
                <option value="publish">Publish immediately</option>
                <?php
                $publish_delay = !empty($SEOAIC_OPTIONS['seoaic_publish_delay']) ? intval($SEOAIC_OPTIONS['seoaic_publish_delay']) : 0;
                if ( $publish_delay > 0 ) :
                    ?>
                    <option value="delay">Delay publication</option>
                <?php endif; ?>
                <option value="draft">Leave draft</option>
            </select>
            <button class="seoaic-popup__btn seoaic-popup__publish" form="generated-post-form"><?= __( 'Save', 'seoaic' ); ?></button>
        </div>
    </div>
</div>
