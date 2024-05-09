<?php

defined('ABSPATH') || exit;

use SEOAIC\SEOAIC;
use SEOAIC\SEOAIC_KEYWORDS;
use SEOAIC\Wizard;

global $SEOAIC_OPTIONS, $SEOAIC;

$search_engine = isset($_GET['search_engine']) ? $_GET['search_engine'] : 'google';
$rank_tracker_page = isset($_GET['page']) && $_GET['page'] == 'seoaic-rank-tracker' ? true : false;
$wizard_page = isset($_GET['page']) && $_GET['page'] == 'seoaic-onboarding-wizard' ? true : false;
$wizard_data_attr = $wizard_page ? ' data-wizard="1"' : '';
$label = __('Choose keywords', 'seoaic');
$selected_keywords_slugs = [];

if ($rank_tracker_page) {
    $keywords = !empty($SEOAIC_OPTIONS['search_terms'][$search_engine]) ? $SEOAIC_OPTIONS['search_terms'][$search_engine] : [];
    $label = __('Choose search terms', 'seoaic');
} elseif ($wizard_page) {
    $keywords = get_transient(Wizard::FIELD_KEYWORDS);
    $keywords = false !== $keywords ? $keywords : [];
    $keywords = json_decode(json_encode($keywords), true);
    $selected_keywords = get_transient(Wizard::FIELD_SELECTED_KEYWORDS);
    $selected_keywords = false !== $selected_keywords ? $selected_keywords : [];
    $selected_keywords = json_decode(json_encode($selected_keywords));
    $selected_keywords_slugs = array_map(function($item) {
        return $item->slug;
    }, $selected_keywords);
} else {
    $keywordsInstanse = new SEOAIC_KEYWORDS(new SEOAIC());
    $keywords = $keywordsInstanse->getKeywords();
}



?>
<div id="generate-ideas" class="seoaic-modal">
    <div class="seoaic-modal-background seoaic-modal-close"></div>
    <div class="seoaic-popup">
        <div class="seoaic-popup__header">
            <span class="seoaic-modal-close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L15 15M15 1L1 15" stroke="#F1EBF7" stroke-width="2"/>
                </svg>
            </span>
            <h3><?php echo __('Generate Ideas', 'seoaic'); ?></h3>
        </div>
        <div class="seoaic-popup__content">
            <form id="generate-idea-form" class="seoaic-form" method="post" data-callback="window_reload">
                <input class="seoaic-form-item" type="hidden" name="action" value="seoaic_generate_ideas">
                <p class="seoaic-popup__description">
                    <?php echo __('This option is useful when there is a need to generate multiple posts at once, either for immediate publishing or for scheduling them over a specific period. It streamlines the process of generating a significant amount of content efficiently.', 'seoaic'); ?>
                </p>
                <div class="seoaic-popup__field">
                    <label class="text-label"><?php echo __('How many ideas do you want to generate?', 'seoaic'); ?></label>
                    <input type="number" name="ideas_count" value="10" class="seoaic-form-item">
                </div>
                <div class="seoaic-popup__field">
                    <label class="text-label mb-10"><?php echo __('Idea type', 'seoaic'); ?></label>
                    <select name="idea_template_type" class="seoaic-form-item form-select">
                        <?php foreach (seoaic_get_prompt_template_types() as $key => $template_type) : ?>
                            <option value="<?= $key ?>"><?= $template_type ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="seoaic-popup__field">
                    <div class="top">
                        <label class="mb-10 mt-20 text-label"><?php echo $label; ?></label>
                        <a href="<?php echo admin_url('admin.php?page=seoaic-keywords'); ?>"
                           class="link-keywords"><?php echo __('Open the keywords table', 'seoaic'); ?>
                        </a>
                    </div>

                    <select name="select-keywords" multiple="multiple" class="seoaic-form-item"<?php echo $wizard_data_attr;?>>
                        <?php
                            if ($rank_tracker_page) {
                                foreach ($keywords as $keyword) {
                                    echo '<option value="' . $keyword['slug'] . '">' . $keyword['keyword'] . '</option>';
                                }
                            } elseif ($wizard_page) {
                                foreach ($keywords as $keyword) {
                                    $selected = in_array($keyword['slug'], $selected_keywords_slugs) ? ' selected="selected"' : '';
                                    echo '<option value="' . $keyword['slug'] . '"' . $selected . '>' . $keyword['name'] . '</option>';
                                }
                            } else {
                                foreach ($keywords as $keyword) {
                                    echo '<option value="' . $keyword['slug'] . '">' . $keyword['name'] . '</option>';
                                }
                            }
                        ?>
                    </select>
                    <?php if ($rank_tracker_page) { ?>
                        <input type="hidden" name="search_terms_page" value="true" class="seoaic-form-item">
                    <?php } ?>

                    <?php if ($keywords) : ?>
                        <div class="all-selector check">
                            <input type="checkbox" name="select_all_checkbox" id="select_all_checkbox" value="value">
                            <label for="select_all_checkbox">Select all</label>
                        </div>
                    <?php endif; ?>
                    <input type="hidden" name="selected_keywords" value="" class="seoaic-form-item">
                    <?php echo SEOAIC::seoaic_select_service(); ?>
                    <?php echo \SEOAIC_LOCATIONS::seoaicSelectLocationGroup() ?>
                </div>
                <div class="seoaic-popup__field">
                    <label class="text-label"><?php echo __('Custom prompt', 'seoaic'); ?></label>
                    <textarea name="idea_prompt"
                              class="seoaic-form-item"><?= !empty($SEOAIC_OPTIONS['seoaic_idea_prompt']) ? $SEOAIC_OPTIONS['seoaic_idea_prompt'] : ''; ?></textarea>
                </div>

                <?=$SEOAIC->multilang->get_multilang_checkboxes()?>

            </form>
        </div>
        <div class="seoaic-popup__footer flex-right">
            <button id="btn-generate-ideas" type="submit" form="generate-idea-form" class="seoaic-popup__btn">
                <?php echo __('Generate Ideas', 'seoaic'); ?>
            </button>
        </div>
    </div>
</div>
