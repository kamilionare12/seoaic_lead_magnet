<?php

defined( 'ABSPATH' ) || exit;

?>
<div id="serp-keyword" class="seoaic-modal">
    <div class="seoaic-modal-background seoaic-modal-close"></div>
    <div class="seoaic-popup serp-popup">
        <div class="seoaic-popup__header">
            <span class="seoaic-modal-close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L15 15M15 1L1 15" stroke="#F1EBF7" stroke-width="2"/>
                </svg>
            </span>
            <h3><?php echo __( 'Keyword SERP Competitors', 'seoaic' ); ?></h3>
        </div>
        <div class="seoaic-popup__content">
            <div class="table">
                <div class="heading">
                    <div class="table-row">
                        <div class="domain"><?php echo __( 'Domain', 'seoaic' ); ?></div>
                        <div class="avg_position"><?php echo __( 'Average Position', 'seoaic' ); ?></div>
                        <div class="visibility"><?php echo __( 'Visibility', 'seoaic' ); ?></div>
                    </div>
                </div>
                <div class="body">
                </div>
            </div>
        </div>
        <div class="seoaic-popup__footer">
            <button type="button" class="seoaic-popup__btn seoaic-modal-close"><?php echo __( 'OK', 'seoaic' ); ?></button>
        </div>
    </div>
</div>