<?php

use SEOAIC\Wizard;

$selected_keywords = get_transient(Wizard::FIELD_SELECTED_KEYWORDS);
$selected_keywords = json_decode(json_encode($selected_keywords));
$selected_keywords_exist = !empty($selected_keywords) && is_array($selected_keywords);
$data_selected_keywords = [];
?>
<div class="step-container keywords" data-step="3">
    <p class="step-number">Step 3</p>
    <p class="step-header mb-40">Create ideas based on selected keywords.</p>
    <div class="step-content inner">
        <div class="bottom">
            <?php
            if ($selected_keywords_exist) {
                $html = '<div class="flex-table generated-keywords-table">';
                foreach ($selected_keywords as $keyword) {
                    $data_selected_keywords[] = $keyword->id;

                    $search_volume = !empty($keyword->search_volume) ? $keyword->search_volume : '-';
                    $competition_class = !empty($keyword->competition) ? ' ' . $keyword->competition : '';
                    $competition = !empty($keyword->competition) ? $keyword->competition : '-';
                    $cpc = !empty($keyword->cpc) ? '$' . $keyword->cpc : '-';

                    $html .= '
                    <div class="row-line">
                        <div class="check display-none"></div>
                        <div class="keyword">
                            <span>' . $keyword->name . '</span>
                        </div>
                        <div class="search-vol text-center">' . $search_volume . '</div>
                        <div class="difficulty text-center' . $competition_class . '">' . $competition . '</div>
                        <div class="cpc text-center">' . $cpc . '</div>
                    </div>';
                }
                $html .= '</div>';

                echo $html;
            } else {
                ?>
                <p class="no-keywords text-center">No keywords were selected. Please get back and repeat the previous step.</p>
                <?php
            }
            ?>
        </div>
        <div class="buttons-row mt-40">
            <button title="Back" type="button"
                    class="transparent-button-primary outline ml-auto seoaic-ajax-button"
                    data-action="seoaic_wizard_step_back"
                    data-callback="window_reload">
                <?php _e('Back', 'seoaic');?>
            </button>
            <?php
            if ($selected_keywords_exist) {
                $csv_data_selected_keywords = implode(',', $data_selected_keywords);
                ?>
                <button title="Generate ideas" type="button"
                        id="wizard_generate_ideas_button"
                        disabled
                        class="button-primary seoaic-button-primary generate-keyword-based seoaic-wizard-generate-ideas-button modal-button ml-15"
                        data-modal="#wizard_generate_ideas"
                        data-selected-keywords="<?php echo $csv_data_selected_keywords;?>"
                        data-action="seoaic_wizard_generate_ideas"
                        data-form-callback="window_reload">
                    <?php _e('Generate Ideas', 'seoaic');?>
                </button>
                <?php
            }
            ?>
        </div>
    </div>
</div>