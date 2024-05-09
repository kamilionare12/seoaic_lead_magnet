<?php

defined( 'ABSPATH' ) || exit;

?>
<div id="seoaic-performance-modal" class="seoaic-modal">
    <div class="seoaic-modal-background seoaic-modal-close"></div>
    <div class="seoaic-popup">
        <div class="seoaic-popup__header">
            <span class="seoaic-modal-close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L15 15M15 1L1 15" stroke="#F1EBF7" stroke-width="2"/>
                </svg>
            </span>
            <h3><?php echo __( 'Performance', 'seoaic' ); ?></h3>
            <p class="modal-title"></p>
        </div>
        <div class="seoaic-popup__content fs-18">
            <div id="confirm-modal-content" class="modal-content">
                <div class="performance-modal_wrap">
                    <div class="on-page-score">
                        <h4>On-page Score</h4>
                        <div class="graph-wrap" >
                            <div class="graph">
                                <div class="pie no-round" style="--p:52"><span>0</span></div>
                            </div>
                            <ul class="page-score-report">
                                <li class="page-score-top">90-100</li>
                                <li class="page-score-middle">50-89</li>
                                <li class="page-score-low">0-49</li>
                            </ul>
                        </div>
                    </div>
                    <div class="time-to-interactive_wrap">
                        <div class="time-to-interactive">
                            <h4>Time To Interactive</h4>
                            <span>256ms</span>
                        </div>
                        <button type="button" class="seoaic-popup__btn seoaic-modal-close"><?php echo __( 'OK', 'seoaic' ); ?></button>
                    </div>
                </div> 
            </div>
        </div>
    </div>
</div>
