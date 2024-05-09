<?php

defined( 'ABSPATH' ) || exit;

?>
<div id="seoaic-disconnect" class="seoaic-modal">
    <div class="seoaic-modal-background seoaic-modal-close"></div>
    <div class="seoaic-popup">
        <div class="seoaic-popup__header">
            <span class="seoaic-modal-close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L15 15M15 1L1 15" stroke="#F1EBF7" stroke-width="2"/>
                </svg>
            </span>
            <h3 class="modal-title" data-title="<?=__( 'Disconnect from SEO AI', 'seoaic'); ?>"></h3>
        </div>
        <div class="seoaic-popup__content">
            <form id="seoaic-disconnect-form" class="seoaic-form" method="post">
                <input class="seoaic-form-item" type="hidden" name="action" value="seoaic_disconnect">
                <div class="seoaic-popup__field">
                    <input id="seoaic-clear" class="seoaic-form-item" type="checkbox" name="seoaic_clear" value="yes">
                    <label for="seoaic-clear" class=""><?php echo __( 'Clear SEO AI data from this site', 'seoaic' ); ?></label>
                </div>
            </form>
        </div>
        <div class="seoaic-popup__footer flex-justify">
            <button type="button" class="seoaic-popup__btn-left seoaic-modal-close">No</button>
            <button type="submit" form="seoaic-disconnect-form" class="seoaic-popup__btn seoaic-popup__btn-right"><?php echo __( 'Disconnect', 'seoaic' ); ?></button>
        </div>
    </div>
</div>
