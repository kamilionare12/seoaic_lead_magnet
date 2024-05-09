<?php
global $SEOAIC, $SEOAIC_OPTIONS;

$query = new \WP_Query([
    'posts_per_page'    => -1,
    'post_type'         => 'post',
    'meta_key'          => 'seoaic_posted',
    'meta_value'        => '1',
]);

$completed = false;
$option = get_option('seoaic_background_post_generation', false);
if (!empty($option)) {
    $width = count($option['posts']) / count($option['ideas']) * 100;
}

if (
    empty($option)
    || $width === 100
) {
    $completed = true;
}

$header_text = !$completed ? 'Generating content of the selected ideas.' : 'Generated content of the selected ideas.';
?>
<div class="step-container" data-step="5">
    <p class="step-number">Step 5</p>
    <p class="step-header mb-40"><?php echo $header_text;?></p>
    <div class="step-content inner">
        <div class="bottom">
            <?php
            if (!$completed) {
                ?>
                <div class="lds-dual-ring"></div>
                <?php
            } elseif ($query->have_posts()) {
                ?>
                <div class="seoaic-posts-table">
                    <div class="seoaic-posts-table__container">
                        <div class="position-relative">
                            <div class="tip-wrapper position-absolute">
                                <div class="tip position-absolute ">
                                    <div class="title mb-5">Tap to view generated content</div>
                                    <div class="text-center">
                                        <svg width="17" height="28" viewBox="0 0 17 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M5.81519 0.276464C10.1124 9.19015 8.36446 21.5746 6.95332 26.6527M1.19539 19.7356C2.58156 21.4008 5.54583 24.9618 6.31355 25.8841C7.27321 27.037 6.50464 27.6767 11.5003 23.5182C15.4969 20.1914 15.9837 19.7862 15.7275 19.9994" stroke="black"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                        while ($query->have_posts()) {
                            $query->the_post();
                            $id = get_the_ID();
                            ?>
                            <div class="seoaic-posts-table__row">
                                <div class="seoaic-posts-table__row-item text-left" data-post-id="<?php echo $id;?>">
                                    <div class="seoaic-post-title mt-0"><?php echo get_the_title();?></div>
                                    <div class="seoaic-dni seoaic-post-content"><?php echo get_the_content();?></div>
                                </div>
                                <div class="seoaic-posts-table__row-action-item">
                                    <a title="<?php _e('View content', 'seoaic');?>"
                                        target="_blank" href="#"
                                        class="wizard-view-post-content modal-button"
                                        data-post-id="<?php echo $id;?>"
                                        data-modal="#wizard-view-post-content">
                                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                                    </a>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <?php
            } else {
                ?>
                <p class="no-keywords text-center">No posts were generated. Please get back and repeat the previous step.</p>
                <?php
            }
            ?>
        </div>
        <div class="buttons-row mt-40">
            <div>
                <button id="wizard_restart_button"
                    class="btn-link confirm-modal-button modal-button"
                    data-modal="#seoaic-confirm-modal"
                    data-action="seoaic_wizard_reset"
                    data-form-callback="window_reload"
                    data-content="Do you want to start wizard from the very beginning?">
                    Restart Wizard
                </button>
            </div>
            <div class="d-flex">
                <button data-title="Back" type="button"
                        class="transparent-button-primary outline ml-auto seoaic-ajax-button"
                        data-action="seoaic_wizard_step_back"
                        data-callback="window_reload">
                    <?php _e('Back', 'seoaic');?>
                </button>
                <button title="Upgrade My Plan" type="button"
                        class="button-primary seoaic-button-primary upgrade-my-plan generate-keyword-based ml-15 px-70 position-relative">
                    <?php _e('Upgrade My Plan', 'seoaic');?>
                </button>
            </div>
        </div>
    </div>
</div>