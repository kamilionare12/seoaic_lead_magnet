<?php

defined( 'ABSPATH' ) || exit;

?>
<div id="add-locations-group" class="seoaic-modal">
    <div class="seoaic-modal-background seoaic-modal-close"></div>
    <div class="seoaic-popup">
        <div class="seoaic-popup__header">
            <span class="seoaic-modal-close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L15 15M15 1L1 15" stroke="#F1EBF7" stroke-width="2"/>
                </svg>
            </span>
            <h3 class="modal-title" data-title="<?=__( 'Add an item', 'seoaic'); ?>"></h3>
        </div>
        <div class="seoaic-popup__content">
            <form id="add-group-form_location" class="seoaic-form" method="post">
                <input class="seoaic-form-item" type="hidden" name="action" value="seoaicAddGroupLocation">
                <div class="seoaic-popup__field">
                    <label for="add-idea-name" class="text-label"><?php echo __( 'Name', 'seoaic' ); ?></label>
                    <input id="add-idea-name" class="seoaic-form-item" type="text" name="item_name" value="" required>
                </div>
            </form>
        </div>
        <div class="seoaic-popup__footer">
            <button type="submit" form="add-group-form_location" class="seoaic-popup__btn"><?php echo __( 'Create', 'seoaic' ); ?></button>
        </div>
    </div>
</div>