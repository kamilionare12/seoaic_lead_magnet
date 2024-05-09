<?php

defined( 'ABSPATH' ) || exit;

?>
<div id="plan-modal" class="seoaic-modal seoaic-modal-bg">
    <div class="seoaic-popup">
        <div class="seoaic-popup__header">
            <span class="seoaic-modal-close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L15 15M15 1L1 15" stroke="#F1EBF7" stroke-width="2"></path>
                </svg>
            </span>
            <h3><?php echo __( 'Upgrade My Plan', 'seoaic' ); ?></h3>
        </div>
        <div class="seoaic-popup__content">
            <form class="upgrade-plan-form">
                <div class="seoaic-popup__field">
                    <label class="text-label"><?php echo __( 'Posts monthly', 'seoaic' ); ?><span class="small">How many posts you want to generate monthly?</span></label>
                    <input type="number" name="posts" class="posts-num-input" value="" data-min="10">
                </div>
                <div class="seoaic-popup__field">
                    <label class="text-label"><?php echo __( 'Ideas monthly', 'seoaic' ); ?><span class="small">How many ideas you want to generate monthly?</span></label>
                    <input type="number" name="ideas" class="ideas-num-input" value="" data-min="10">
                </div>
                <div class="seoaic-popup__field">
                    <label class="text-label"><?php echo __( 'Your email', 'seoaic' ); ?><span class="small">The email address to which you will receive the details to upgrade your plan</span> </label>
                    <input type="text" name="email" value="" class="upgrade-email">
                    <p class="result"></p>
                </div>
            </form>
        </div>
        <div class="seoaic-popup__footer">
            <button id="submit-plan" class="seoaic-popup__btn" data-action="seoaic_send_upgrade_plan"><?php echo __( 'Submit', 'seoaic' ); ?></button>
        </div>
    </div>
</div>