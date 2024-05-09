<div class="overview-section tab-content" data-tab-list="overview">
    <div class="row full-width">
        <div class="left-side">
            <div id="seoai-site-health-score" class="col seo-score">
                <div class="inner progress-chart"
                        style="--progress: 0;">
                    <div class="title"><?php _e('Site Health', 'seoaic') ?></div>
                    <div class="score-wrap">
                        <div class="score">
                            <div class="skills-container">
                                <div class="circular-progress html"></div>
                            </div>
                        </div>
                        <div class="value">0</div>
                    </div>
                </div>
            </div>
            <div class="col number-value orphan-pages-wrap">
                <div class="inner">
                    <div class="value" id="seoaic-orphan-pages"><span>0</span></div>
                    <div class="title">
                        <?php _e('Orphan pages', 'seoaic') ?>
                        <button type="button" class="outline button_border modal-button button_view dashicons dashicons-eye" data-message="errors" data-key="is_orphan_page" data-title="Orphan pages" data-modal="#seoaic-alert-modal" data-action="seoaic_get_seo_audit_data"></button>
                    </div>
                </div>
            </div>
        </div>
        <div class="right-side">
            <div class="col">
                <div class="message-container messages">
                    <div class="errors seoai_item">
                        <div class="title"><?php echo __('Errors', 'seoaic') ?></div>
                        <div class="value seoaic-error-count"></div>
                    </div>
                    <div class="warnings seoai_item">
                        <div class="title"><?php echo __('Warnings', 'seoaic') ?></div>
                        <div class="value seoaic-warning-count"></div>
                    </div>
                    <div class="notices seoai_item">
                        <div class="title"><?php echo __('Notices', 'seoaic') ?></div>
                        <div class="value seoaic-notice-count"></div>
                    </div>
                </div>
            </div>

            <div class="col">
                <h3>Thematic Reports</h3>
                <div class="message-container thematic">
                    <div class="thematic-wrap">
                        <div class="thematic-left_side">

                            <div class="seoai_item" id="seoai-https">
                                <div class="title"><?php echo __('HTTPS', 'seoaic') ?></div>
                                <div class="graph-wrap">
                                    <div class="graph">
                                        <div class="pie no-round"></div>
                                    </div>
                                    <div class="graph-value"><span>0</span>%</div>
                                </div>
                            </div>

                            <div class="seoai_item" id="seoai-performance">
                                <div class="title"><?php echo __('Site Performance', 'seoaic') ?></div>
                                <div class="graph-wrap">
                                    <div class="graph">
                                        <div class="pie no-round"></div>
                                    </div>
                                    <div class="graph-value"><span>0</span>%</div>
                                </div>
                            </div>

                            <div class="seoai_item" id="seoai-markup">
                                <div class="title"><?php echo __('Micro - markup', 'seoaic') ?></div>
                                <div class="graph-wrap" >
                                    <div class="graph">
                                        <div class="pie no-round"></div>
                                    </div>
                                    <div class="graph-value"><span>0</span>%</div>
                                </div>
                            </div>
                        </div>

                        <div class="thematic-right_side">
                            <div class="seoai_item">
                                <div class="title"><?php echo __('Crawled Pages', 'seoaic') ?></div>
                                <div class="crawl-status" id="thematic-crawl"><span>0</span> <i><?php echo __('no changes', 'seoaic') ?></i></div>
                                <div class="graph-wrap crawled-graph">
                                    <div class="graph">
                                        <div class="crawled-pages-graph">
                                            <div id="graph-2xx"></div>
                                            <div id="graph-3xx"></div>
                                            <div id="graph-4xx"></div>
                                            <div id="graph-5xx"></div>
                                        </div>
                                    </div>
                                </div>
                                <ul class="thematic-report">
                                    <li class="thematic-2xx" id="thematic-2xx"><p><?php echo __('Page status 2хх', 'seoaic') ?></p><span>0</span></li>
                                    <li class="thematic-3xx" id="thematic-3xx"><p><?php echo __('Page status 3хх', 'seoaic') ?></p><span>0</span></li>
                                    <li class="thematic-4xx" id="thematic-4xx"><p><?php echo __('Page status 4хх', 'seoaic') ?></p><span>0</span></li>
                                    <li class="thematic-5xx" id="thematic-5xx"><p><?php echo __('Page status 5хх', 'seoaic') ?></p><span>0</span></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>