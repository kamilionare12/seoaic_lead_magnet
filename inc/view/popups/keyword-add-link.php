<?php

defined('ABSPATH') || exit;

global $wpdb;

$postTypes = get_post_types([
    'public' => true,
], 'names');
$args = [
    'post_type'     => $postTypes,
    'numberposts'   => -1,
];
$pages = get_posts($args);

$customLinks = $wpdb->get_col(
    $wpdb->prepare("
        SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm
        WHERE pm.meta_key = %s
        AND pm.meta_value NOT REGEXP '^[0-9]+$'
    ", ['page_link'])
);
?>
<div id="add-keyword-link-modal" class="seoaic-modal">
    <div class="seoaic-modal-background seoaic-modal-close"></div>
    <div class="seoaic-popup">
        <div class="seoaic-popup__header">
            <span class="seoaic-modal-close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L15 15M15 1L1 15" stroke="#F1EBF7" stroke-width="2"/>
                </svg>
            </span>
            <h3><?php _e('Add Keyword Link', 'seoaic');?></h3>
        </div>
        <div class="seoaic-popup__content">
            <form id="add-keyword-link-form" class="seoaic-form" method="post">
                <input type="hidden" name="action" value="seoaic_add_keyword_link" class="seoaic-form-item">
                <input type="hidden" name="post_id" value="" class="seoaic-form-item">
                <div class="seoaic-popup__field">
                    <label class="text-label mb-5"><?php _e('Keyword link', 'seoaic');?></label>
                    <select name="page_link" class="seoaic-keyword-page-link seoaic-form-item form-select">
                        <?php
                        if (!empty($customLinks)) {
                            foreach ($customLinks as $customLink) {
                                ?>
                                <option value="<?php echo esc_attr($customLink);?>"><?php echo esc_html($customLink);?></option>
                                <?php
                            }
                        }
                        if (!empty($pages)) {
                            foreach ($pages as $page) {
                                $permalink = get_permalink($page->ID);
                                ?>
                                <option
                                    value="<?php echo esc_attr($page->ID);?>"
                                    data-permalink=<?php echo esc_attr($permalink);?>
                                ><?php echo esc_html($page->post_title);?></option>
                                <?php
                            }
                        }
                        ?>
                    </select>
                </div>
            </form>
        </div>
        <div class="seoaic-popup__footer">
            <button type="submit"
                    form="add-keyword-link-form"
                    id="btn-add-keyword-link"
                    class="seoaic-popup__btn"
                    data-type="add"
            ><?php _e('Save', 'seoaic');?></button>
        </div>
    </div>
</div>