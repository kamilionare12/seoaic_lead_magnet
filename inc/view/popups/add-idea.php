<?php

use SEOAIC\SEOAIC;
use SEOAIC\SEOAIC_KEYWORDS;

global $SEOAIC,$SEOAIC_OPTIONS;

defined('ABSPATH') || exit;

$search_engine = isset($_GET['search_engine']) ? $_GET['search_engine'] : 'google';
$rank_tracker_page = isset($_GET['page']) && $_GET['page'] == 'seoaic-rank-tracker' ? true : false;
$competitors_page = isset($_GET['page']) && $_GET['page'] == 'seoaic-competitors' ? true : false;

$locations = seoaic_google_ads_available_locations();

$selected_location = !empty($SEOAIC_OPTIONS['seoaic_location']) ? $SEOAIC_OPTIONS['seoaic_location'] : 'United States';

if ($rank_tracker_page) {
    $keywordsInstanse = new SEOAIC_KEYWORDS(new SEOAIC());
    $keywords = $keywordsInstanse->getKeywords();
}

?>
<div id="add-idea" class="seoaic-modal">
    <div class="seoaic-modal-background seoaic-modal-close"></div>
    <div class="seoaic-popup">
        <div class="seoaic-popup__header">
            <span class="seoaic-modal-close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L15 15M15 1L1 15" stroke="#F1EBF7" stroke-width="2"/>
                </svg>
            </span>
            <h3 class="modal-title" data-title="<?= __('Add an item', 'seoaic'); ?>"></h3>
        </div>
        <div class="seoaic-popup__content">
            <form id="add-idea-form" class="seoaic-form" method="post">
                <input class="seoaic-form-item" type="hidden" name="action" value="seoaic_add_idea">
                <div class="seoaic-popup__field">
                    <label for="add-idea-name" class="text-label"><?php echo __('Name', 'seoaic'); ?></label>
                    <input id="add-idea-name" class="seoaic-form-item" type="text" name="item_name" value="" required>
                </div>
                <?php if ($rank_tracker_page && !empty($keywords) || $competitors_page)  : ?>

                    <?php if ( ! $competitors_page)  : ?>
                        <div class="seoaic-popup__field">
                            <div class="top">
                                <label class="mb-10 mt-20 text-label"><?php echo __('Choose keywords', 'seoaic'); ?></label>
                                <a href="<?php echo admin_url('admin.php?page=seoaic-keywords'); ?>"
                                   class="link-keywords"><?php echo __('Open the keywords table', 'seoaic'); ?></a>
                            </div>
                            <select name="select-keywords" multiple="multiple" class="seoaic-form-item">
                                <?php
                                foreach ($keywords as $keyword) {
                                    echo '<option value="' . $keyword['slug'] . '">' . $keyword['name'] . '</option>';
                                }
                                ?>
                            </select>

                            <div class="all-selector check">
                                <input class="select_all_checkbox" type="checkbox" name="select_all_terms"
                                       id="select_all_terms" value="value">
                                <label for="select_all_terms">Select all</label>
                            </div>

                            <input type="hidden" name="selected_keywords" value="" class="seoaic-form-item">

                        </div>
                    <?php endif; ?>

                    <div class="seoaic-popup__field">
                        <label for="seoaic_location" class="mb-10 mt-20 text-label">Location</label>

                        <select id="seoaic_location" class="seoaic-form-item form-select mb-19"
                                name="seoaic_location"
                                required>
                            <?php foreach ($locations as $key => $location) : ?>
                                <option value="<?= $location; ?>"
                                    <?= ($location === $selected_location) ? 'selected' : ''; ?>
                                ><?= $location; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <?php $SEOAIC->multilang->get_translation_parent_input(); ?>
                <?php $SEOAIC->multilang->get_languages_select(); ?>

            </form>
        </div>
        <div class="seoaic-popup__footer">
            <button type="submit" form="add-idea-form"
                    class="seoaic-popup__btn"><?php echo __('Create', 'seoaic'); ?></button>
        </div>
    </div>
</div>
