<?php

defined('ABSPATH') || exit;

global $SEOAIC_OPTIONS;

$tabs = '<div class="menu-section-seoai seoai-px-0 seoai-mb-2-pr medium-size seoai-graphs-tabs" id="seoai-graphs-tabs">
            <ul class="seoai-flex-start link-spaces">
                <li>
                    <a class="tab positions checked" data-chart-id="#chart_competitors_positions" data-chart-type="area" data-charts="[]" href="#">Ranking Positions</a>
                </li>
                <li>
                    <a class="tab traffic" data-chart-id="#chart_competitors_positions" data-chart-type="area" data-charts="[]" href="#">Traffic Volume</a>
                </li>
            </ul>
        </div>';

$traffic_graph = !empty($SEOAIC_OPTIONS['seoaic_competitors_traffic_graph']) ? $tabs : '<div class="seoai-mb-2-pr fw-700 fs-16 ml-10">' . esc_html('Positions') . '</div>';
?>
<div id="ranking-modal" class="seoaic-modal">
    <div class="seoaic-modal-background seoaic-modal-close"></div>
    <div class="seoaic-popup ranking-modal">
        <div class="seoaic-popup__header">
            <span class="seoaic-modal-close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L15 15M15 1L1 15" stroke="#F1EBF7" stroke-width="2"/>
                </svg>
            </span>
            <h3><?php echo __('Ranking Positions', 'seoaic'); ?></h3>
        </div>
        <div class="seoaic-popup__content">
            <div class="table tabs-wrapper">
                <?php echo $traffic_graph; ?>
                <div id="chart_competitors_positions" class="graph-data" style="max-width: 100%"></div>
            </div>
        </div>
        <div class="seoaic-popup__footer">
            <button type="button"
                    class="seoaic-popup__btn seoaic-modal-close"
            ><?php echo __('OK', 'seoaic'); ?></button>
        </div>
    </div>
</div>