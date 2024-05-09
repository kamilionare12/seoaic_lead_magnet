<?php

use SEOAIC\SEOAIC;
use SEOAIC\SEOAIC_KEYWORDS;
use SEOAIC\Wizard;

global $SEOAIC, $SEOAIC_OPTIONS;

$keywordsInstanse = new SEOAIC_KEYWORDS(new SEOAIC());
$is_all = isset($SEOAIC_OPTIONS['wizard_settings']['display_keywords']) && 'all' == $SEOAIC_OPTIONS['wizard_settings']['display_keywords'];

if ($is_all) {
    $wizard_keywords = $keywordsInstanse->getKeywords();
} else {
    $wizard_keywords = get_transient(Wizard::FIELD_KEYWORDS);
}
$wizard_keywords = json_decode(json_encode($wizard_keywords));
$keywords_exist = !empty($wizard_keywords) && is_array($wizard_keywords);
$wizard_selected_keywords = get_transient(Wizard::FIELD_SELECTED_KEYWORDS);
$wizard_selected_keywords = false !== $wizard_selected_keywords ? $wizard_selected_keywords : [];
$wizard_selected_keywords = json_decode(json_encode($wizard_selected_keywords));
$selected_slugs = array_map(function($item) {
    return $item->slug;
}, $wizard_selected_keywords);
?>
<div class="step-container keywords" data-step="2">
    <p class="step-number">Step 2</p>
    <p class="step-header mb-40">Choose the keywords you'd like to use to generate your content.</p>
    <div class="step-content inner">
        <div class="bottom">
            <div class="header seoaic-flip-box keywords-settings">

                <div class="seoaic-flip-container">

                    <div class="seoaic-flip-item seoaic-flip-front">

                        <div class="schedule-switcher">

                            <div class="checkbox-wrapper-mc">
                                <input id="wizard_keywords_check_all" type="checkbox" class="seoaic-check-key"
                                    name="wizard_keywords_check_all" value="all">
                                <label for="wizard_keywords_check_all" class="check" title="Select all">
                                    <div class="checkbox-wrapper-svg">
                                        <svg width="18px" height="18px" viewBox="0 0 18 18">
                                            <path d="M1,9 L1,3.5 C1,2 2,1 3.5,1 L14.5,1 C16,1 17,2 17,3.5 L17,14.5 C17,16 16,17 14.5,17 L3.5,17 C2,17 1,16 1,14.5 L1,9 Z"></path>
                                            <polyline points="1 9 7 14 15 4"></polyline>
                                        </svg>
                                    </div>
                                </label>
                            </div>

                            <div class="">
                                <button class="wizard-entities-type-button <?php echo $is_all ? ' active' : '';?>"
                                        data-action="seoaic_wizard_reload_entities"
                                        data-entities="keywords"
                                        data-type="all"
                                        data-callback="window_reload"
                                >
                                    Show all
                                </button>
                                <button class="wizard-entities-type-button <?php echo !$is_all ? ' active' : '';?>"
                                        data-action="seoaic_wizard_reload_entities"
                                        data-entities="keywords"
                                        data-type="new"
                                        data-callback="window_reload"
                                >
                                    Show new
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="seoaic-flip-item seoaic-flip-side">
                        <div class="schedule-switcher">
                            <span class="seoaic-checked-amount">Selected: <span class="seoaic-checked-amount-num"></span></span>
                            <button type="button" id="wizard_keywords_uncheck_all">Remove selection</button>
                        </div>
                    </div>

                </div>

            </div>
            <?php
            if ($keywords_exist) {
                $html = '<div class="flex-table generated-keywords-table">';
                foreach ($wizard_keywords as $keyword) {
                    $search_volume = !empty($keyword->search_volume) ? $keyword->search_volume : '-';
                    $competition_class = !empty($keyword->competition) ? ' ' . $keyword->competition : '';
                    $competition = !empty($keyword->competition) ? $keyword->competition : '-';
                    $cpc = !empty($keyword->cpc) ? '$' . $keyword->cpc : '-';
                    $checked = in_array($keyword->slug, $selected_slugs) ? ' checked="checked"' : '';

                    $html .= '
                    <div class="row-line">
                        <div class="check">
                            <input class="seoaic-check-key" name="seoaic-check-key" type="checkbox" data-keyword="' . $keyword->id . '"' . $checked . '>
                        </div>
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
                <p class="no-keywords text-center">No keywords were generated. Please get back and repeat the previous step.</p>
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
            <button type="button" title="Apply"
                    id="apply_selected_keywords"
                    disabled
                    class="button-primary seoaic-button-primary seoaic-wizard-select-keywords-button generate-keyword-based ml-15"
                    data-action="seoaic_wizard_select_keywords"
                    data-callback="window_reload">
                <?php _e('Apply', 'seoaic');?>
            </button>
        </div>
    </div>
</div>