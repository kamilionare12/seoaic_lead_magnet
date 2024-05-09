<?php

defined( 'ABSPATH' ) || exit;

global $SEOAIC;

?>
<div id="edit-idea" class="seoaic-modal">
    <div class="seoaic-modal-background seoaic-modal-close"></div>
    <div class="seoaic-popup">
        <div class="seoaic-popup__header">
            <span class="seoaic-modal-close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L15 15M15 1L1 15" stroke="#F1EBF7" stroke-width="2"/>
                </svg>
            </span>
            <h3 class="modal-title" data-title="<?=__( 'Edit an item', 'seoaic'); ?>"></h3>
        </div>
        <div class="seoaic-popup__content">
            <form id="edit-idea-form_IDEA" class="seoaic-form" method="post">
                <input class="seoaic-form-item" type="hidden" name="action" value="seoaic_edit_idea">
                <input id="item_edit_id" class="seoaic-form-item" type="hidden" name="item_id" value="">
                <div class="seoaic-popup__field">
                    <label class="text-label"><?php echo __( 'Name', 'seoaic' ); ?></label>
                    <input id="item_edit_name" class="seoaic-form-item" type="text" name="item_name" value="">
                </div>

                <?php $SEOAIC->multilang->get_languages_select(); ?>
            </form>
        </div>
        <div class="seoaic-popup__footer">
            <button type="submit" form="edit-idea-form_IDEA" class="seoaic-popup__btn"><?php echo __( 'Save', 'seoaic' ); ?></button>
        </div>
    </div>
</div>
