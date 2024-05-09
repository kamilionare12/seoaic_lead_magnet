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
    $selected_keywords_slugs = array_map(function ($item) {
        return $item->slug;
    }, $selected_keywords);

} else {
    $keywordsInstanse = new SEOAIC_KEYWORDS(new SEOAIC());
    $keywords = $keywordsInstanse->getKeywords();
}


?>
<div id="generate-ideas-new-keywords" class="seoaic-modal">
    <div class="seoaic-modal-background seoaic-modal-close"></div>
    <div class="seoaic-popup">
        <div class="seoaic-popup__header">
            <span class="seoaic-modal-close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L15 15M15 1L1 15" stroke="#F1EBF7" stroke-width="2"/>
                </svg>
            </span>
            <h3><?php _e('Generate Ideas', 'seoaic');?></h3>
        </div>
        <div class="seoaic-popup__content">
            <form id="generate-idea-form-new" class="seoaic-form" method="post" data-callback="window_reload">
                <input class="seoaic-form-item" type="hidden" name="action" value="seoaic_generate_ideas_new_keywords">
                <p class="seoaic-popup__description">
                    <?php _e('This option is useful when there is a need to generate multiple posts at once, either for immediate publishing or for scheduling them over a specific period. It streamlines the process of generating a significant amount of content efficiently.', 'seoaic');?>
                </p>
                <div class="seoaic-popup__field">
                    <label class="text-label"><?php _e('How many ideas do you want to generate?', 'seoaic');?></label>
                    <input type="number" name="ideas_count" value="10" class="seoaic-form-item">
                </div>
                <div class="seoaic-popup__field">
                    <label class="text-label mb-10"><?php _e('Idea type', 'seoaic');?></label>
                    <select name="idea_template_type" class="seoaic-form-item form-select">
                        <?php
                        foreach (seoaic_get_prompt_template_types() as $key => $template_type) {
                            ?>
                            <option value="<?php echo $key;?>"><?php echo $template_type;?></option>
                            <?php
                        }
                        ?>
                    </select>
                </div>
                <div class="seoaic-popup__field">
                    <div class="top label-keyword">
                        <label class="mb-10 mt-20 text-label"><?php echo $label;?></label>
                        <a href="<?php echo admin_url('admin.php?page=seoaic-keywords');?>"
                           class="link-keywords"><?php _e('Open the keywords table', 'seoaic');?>
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
                                echo '<option value="' . $keyword['id'] . '">' . $keyword['name'] . '</option>';
                            }
                        }
                        ?>
                    </select>
                    <div class="d-flex mt-15">
                        <?php
                        if ($rank_tracker_page) {
                            ?>
                            <input type="hidden" name="search_terms_page" value="true" class="seoaic-form-item">
                            <?php
                        }
                        
                        if ($keywords) {
                            ?>
                            <div class="all-selector check mr-15">
                                <input type="checkbox" name="select_all_checkbox" id="select_all_checkbox" value="value">
                                <label for="select_all_checkbox">Select all</label>
                            </div>
                            <?php
                        }
                        ?>
                        <div class="check">
                            <input type="checkbox" name="generate_keywords_separately" class="seoaic-form-item" id="generate_keywords_separately" value="generate">
                            <label for="generate_keywords_separately"><?php _e('Generate separately', 'seoaic') ?></label>
                        </div>
                    </div>
                    <input type="hidden" name="selected_keywords" value="" class="seoaic-form-item">
                    <?php echo SEOAIC::seoaic_select_service();?>
                    <?php echo \SEOAIC_LOCATIONS::seoaicSelectLocationGroup() ?>
                </div>
                <div class="seoaic-popup__field">
                    <label class="text-label"><?php _e('Custom prompt', 'seoaic');?></label>
                    <textarea name="idea_prompt"
                              class="seoaic-form-item"><?php echo !empty($SEOAIC_OPTIONS['seoaic_idea_prompt']) ? $SEOAIC_OPTIONS['seoaic_idea_prompt'] : '';?></textarea>
                </div>

                <?php echo $SEOAIC->multilang->get_multilang_checkboxes();?>

            </form>
        </div>
        <div class="seoaic-popup__footer flex-right">
            <button id="btn-generate-ideas" type="submit" form="generate-idea-form-new" class="seoaic-popup__btn">
                <?php _e('Generate Ideas', 'seoaic');?>
            </button>
        </div>
    </div>
</div>
