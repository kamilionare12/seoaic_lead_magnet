<?php

use SEOAIC\SEOAIC;

if (!current_user_can('seoaic_edit_plugin')) {
    return;
}

if (isset($_GET['settings-updated'])) {
    add_settings_error('seoaic_messages', 'seoaic_message', __('Settings Saved', 'mdsf'), 'updated');
}

settings_errors('seoaic_messages');

global $SEOAIC, $SEOAIC_OPTIONS;

$services = $SEOAIC_OPTIONS['seoaic_services'] ?? [];
$locations = $SEOAIC_OPTIONS['seoaic_locations'] ?? [];
$pillar_links = $SEOAIC_OPTIONS['seoaic_pillar_links'] ?? [];
$post_prompt_tmplts = $SEOAIC_OPTIONS['seoaic_posts_mass_generate_prompt_templates'] ?? [];

$ser = '';
foreach ((array)$services as $s) {
    if (is_array($s)) {
        $ser .= '<div class="service-section">
                <div class="item">
                    <input placeholder="Service name" type="text" class="form-input light service-input" name="service-input" value="' . esc_attr($s['name']) . '" required/>
                    <textarea placeholder="Service description (optional)" class="form-input light" name="service-text" autocomplete="off" rows="3">' . esc_html($s['text']) . '</textarea>
                    <a href="#" class="delete delete-service" title="Remove"></a>
                </div>
             </div>';
    }
}

$post_prompt_tmplts_html = '';
foreach ((array)$post_prompt_tmplts as $p) {
    $post_prompt_tmplts_html .= '
        <div class="prompt-section">
            <div class="item">
                <textarea placeholder="Prompt template" name="post-prompt-teplate" class="form-input light post-prompt-teplate-input" autocomplete="off" rows="3">' . esc_html($p) . '</textarea>
                <a href="#" class="delete delete-prompt" title="' . __('Remove', 'seoaic') . '"></a>
            </div>
        </div>';
}

$loc = '';
foreach ((array)$locations as $s) {
    $loc .= '<div class="item">
                <input type="text" class="form-input light location-input" name="location-input" value="' . esc_attr($s) . '" required readonly/>
                <a href="#" class="delete delete-location" title="Remove"></a>
             </div>';
}

$languages = seoaic_get_languages();
$selected_language = !empty($SEOAIC_OPTIONS['seoaic_language']) ? $SEOAIC_OPTIONS['seoaic_language'] : 'English';

/*$writing_styles = seoaic_get_writing_styles();
$selected_writing_style = !empty($SEOAIC_OPTIONS['seoaic_writing_style']) ? $SEOAIC_OPTIONS['seoaic_writing_style'] : 'Default';*/

$locations = seoaic_get_locations();
$selected_location = !empty($SEOAIC_OPTIONS['seoaic_location']) ? $SEOAIC_OPTIONS['seoaic_location'] : 'United States';

$image_generator_default = !empty($SEOAIC_OPTIONS['seoaic_image_generator']) ? $SEOAIC_OPTIONS['seoaic_image_generator'] : 'no_image';
$image_generators = seoaic_get_image_generators();

$post_types = seoaic_get_post_types();
$selected_post_type = !empty($SEOAIC_OPTIONS['seoaic_post_type']) ? $SEOAIC_OPTIONS['seoaic_post_type'] : 'post';
$exclude_taxonomies = seoaic_get_taxonomies_checkboxes();

$postTemplates = get_page_templates(null, $selected_post_type);
$selectedPostTemplate = !empty($SEOAIC_OPTIONS['seoaic_post_template']) ? $SEOAIC_OPTIONS['seoaic_post_template'] : '';

$categories = seoaic_get_categories($selected_post_type);

$image_styles = [
    'default' => 'Default'
];

$colors = [
    '#d62828' => '#d62828',
    '#ff99c8' => '#ff99c8',
    '#3a86ff' => '#3a86ff',
    '#ffc300' => '#ffc300',
    '#03045e' => '#03045e',
    '#6c584c' => '#6c584c',
    '#00bbf9' => '#00bbf9',
    '#415a77' => '#415a77',
    '#7b2cbf' => '#7b2cbf',
    '#fdf0d5' => '#fdf0d5',
    '#38b000' => '#38b000',
    '#b23a48' => '#b23a48'
];

$createdPostsQuery = new WP_Query([
    'posts_per_page'    => -1,
    'post_type'         => 'post',
    'meta_key'          => 'seoaic_posted',
    'meta_value'        => '1',
]);

$defaultScheduleChecked = !isset($SEOAIC_OPTIONS['seoaic_schedule_days']) ? 'checked' : '';
?>
<div id="seoaic-admin-container" class="wrap settings-page">
    <h1 id="seoaic-admin-title">
        <?php echo seoai_get_logo('logo.svg'); ?>
        <span class="seoaic-admin-title-subtitle">
            <span class="seoaic-admin-title-subpage"><?php echo esc_html(get_admin_page_title()); ?></span>
            <span class="seoaic-admin-title-version">
                Version <?= esc_html(get_plugin_data(SEOAIC_FILE)['Version']); ?>
            </span>
        </span>
    </h1>
    <?= $SEOAIC->get_background_process_loader(); ?>
    <div id="seoaic-admin-body" class="seoaic-with-loader bg-settings">
        <div class="lds-dual-ring"></div>
        <form id="seoaic-settings" class="seoaic-form row" name="seoaic-settings" method="post" autocomplete="off">
            <input type="hidden" class="seoaic-form-item" name="action" value="seoaic_settings">

            <div class="col-6 left-side">
                <div class="row">
                    <div class="col-12">
                        <label for="seoaic_server"><?php _e('Use SSL Verification', 'seoaic');?></label>
                        <div class="toggle-choose-server">
                            <label class="switch">
                                <input class="seoaic-toggle seoaic-form-item" type="checkbox"
                                       name="seoaic_ssl_verifypeer"
                                       value="on" <?php echo !empty($SEOAIC_OPTIONS['seoaic_ssl_verifypeer']) ? 'checked' : '';?>>
                                <span class="slider round"><span
                                            class="seoaic-toggle-value seoaic-toggle-value-1">On</span><span
                                            class="seoaic-toggle-value seoaic-toggle-value-2">Off</span></span>
                            </label>
                        </div>
                    </div>

                    <div class="col-12">
                        <label for="seoaic_server"><?php _e('Show competitor traffic graph', 'seoaic');?></label>
                        <div class="toggle-choose-server">
                            <label class="switch">
                                <input class="seoaic-toggle seoaic-form-item" type="checkbox"
                                       name="seoaic_competitors_traffic_graph"
                                       value="on" <?php echo !empty($SEOAIC_OPTIONS['seoaic_competitors_traffic_graph']) ? 'checked' : '';?>>
                                <span class="slider round"><span
                                            class="seoaic-toggle-value seoaic-toggle-value-1">On</span><span
                                            class="seoaic-toggle-value seoaic-toggle-value-2">Off</span></span>
                            </label>
                        </div>
                    </div>

                    <?php if (current_user_can('manage_options')) { ?>
                        <div class="col-12">
                            <label for="seoaic_server"><?php _e('Allow to use plugin for the following roles', 'seoaic');?></label>
                            <div class="toggle-choose-role">
                                <div class="checkbox-list">
                                    <?php
                                    $roles = wp_roles()->roles;
                                    unset($roles['administrator'], $roles['company']);

                                    foreach ($roles as $role => $role_info) {
                                        $checked = isset($SEOAIC_OPTIONS['seoaic_access_role']) && in_array($role, $SEOAIC_OPTIONS['seoaic_access_role']) ? ' checked' : '';
                                        ?>
                                        <div class="checkbox-wrapper-mc">
                                            <input id="seoaic_access-role-<?php echo esc_attr($role);?>" class="seoaic-form-item"
                                                   name="seoaic_access_role[]"
                                                   type="checkbox"
                                                   value="<?php echo $role;?>"
                                                   <?php echo $checked;?> />
                                            <label for="seoaic_access-role-<?php echo $role ?>" class="check">
                                                <svg width="18px" height="18px" viewBox="0 0 18 18">
                                                    <path d="M1,9 L1,3.5 C1,2 2,1 3.5,1 L14.5,1 C16,1 17,2 17,3.5 L17,14.5 C17,16 16,17 14.5,17 L3.5,17 C2,17 1,16 1,14.5 L1,9 Z"></path>
                                                    <polyline points="1 9 7 14 15 4"></polyline>
                                                </svg>
                                                <span><?php echo $role_info['name'] ?></span>
                                            </label>
                                        </div>
                                    <?php }
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php } ?>

                    <div class="col-12">
                        <label for="seoaic_company_website"><?php _e('Company website', 'seoaic');?></label>

                        <input id="seoaic_company_website" class="seoaic-form-item form-input light" name="seoaic_company_website"
                               type="url"
                               placeholder="https://example.com"
                               autocomplete="none"
                               pattern="[Hh][Tt][Tt][Pp][Ss]?:\/\/(?:(?:[a-zA-Z\u00a1-\uffff0-9]+-?)*[a-zA-Z\u00a1-\uffff0-9]+)(?:\.(?:[a-zA-Z\u00a1-\uffff0-9]+-?)*[a-zA-Z\u00a1-\uffff0-9]+)*(?:\.(?:[a-zA-Z\u00a1-\uffff]{2,}))(?::\d{2,5})?(?:\/[^\s]*)?"
                               value="<?= !empty($SEOAIC_OPTIONS['seoaic_company_website']) ? esc_attr($SEOAIC_OPTIONS['seoaic_company_website']) : esc_attr(get_bloginfo('url'));?>"
                               required
                        />
                        <span class="validation-message"><?php _e('Not valid URL', 'seoaic');?></span>
                    </div>

                    <div class="col-12">
                        <label for="seoaic_business_name"><?php _e('Business Name', 'seoaic');?></label>

                        <input id="seoaic_business_name" class="seoaic-form-item form-input light"
                               name="seoaic_business_name"
                               type="text"
                               value="<?= !empty($SEOAIC_OPTIONS['seoaic_business_name']) ? esc_attr($SEOAIC_OPTIONS['seoaic_business_name']) : esc_attr(get_option('blogname', true));?>"
                               required>
                    </div>

                    <div class="col-12">
                        <label for="seoaic_industry"><?php _e('Industry', 'seoaic');?></label>

                        <input id="seoaic_industry" class="seoaic-form-item form-input light" name="seoaic_industry"
                               type="text"
                               value="<?= !empty($SEOAIC_OPTIONS['seoaic_industry']) ? esc_attr($SEOAIC_OPTIONS['seoaic_industry']) : '';?>"
                               required/>
                    </div>

                    <div class="col-12">
                        <label for="seoaic_business_description"><?php _e('Description of the company', 'seoaic');?></label>

                        <textarea id="seoaic_business_description" class="seoaic-form-item form-input light mb-0"
                                  name="seoaic_business_description" rows="6"
                                  required><?php echo !empty($SEOAIC_OPTIONS['seoaic_business_description']) ? esc_html($SEOAIC_OPTIONS['seoaic_business_description']) : esc_html(get_option('blogdescription', true));?></textarea>
                        <div class="seoaic-text-right">
                            <button type="button"
                                    class="button button-link max-w-50 modal-button settings-generate-description-btn"
                                    data-modal="#settings-description-generate-modal"
                                    data-action="seoaic_settings_generate_description"
                                    data-form-callback="settings_update_description"
                            ><?php _e('Generate Description', 'seoaic');?></button>
                        </div>
                    </div>

                    <div class="col-12">
                        <label><?php _e('Company services', 'seoaic');?></label>

                        <div class="seoaic_input_repeater seoaic_services mb-19">
                            <div id="services_list" class="list">
                                <?php echo $ser; ?>
                            </div>
                            <a href="#" class="add" data-add="service-input"
                               title="Add Service"><?php _e('Add Service', 'seoaic');?></a>
                        </div>
                    </div>

                    <div class="col-12">
                        <label for="seoaic_phone"><?php _e('Phone', 'seoaic');?></label>

                        <input id="seoaic_phone" class="seoaic-form-item form-input light" name="seoaic_phone"
                               type="text"
                               value="<?= !empty($SEOAIC_OPTIONS['seoaic_phone']) ? esc_attr($SEOAIC_OPTIONS['seoaic_phone']) : '';?>"
                               required/>
                    </div>

                    <div class="col-12">
                        <label for="seoaic_location"><?php _e('Location', 'seoaic');?></label>

                        <select id="seoaic_location" class="seoaic-form-item form-select mb-19"
                                name="seoaic_location"
                                required>
                            <?php foreach ($locations as $key => $location) : ?>
                                <option value="<?= esc_attr($location);?>"
                                    <?= ($location === $selected_location) ? 'selected' : ''; ?>
                                ><?= esc_html($location);?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label for="seoaic_language"><?php _e('Language', 'seoaic');?></label>

                        <select id="seoaic_language" class="seoaic-form-item form-select mb-19"
                                name="seoaic_language"
                                required>
                            <?php foreach ($languages as $language) : ?>
                                <option value="<?= esc_attr($language);?>"
                                    <?= ($language === $selected_language) ? 'selected' : ''; ?>
                                ><?= esc_html($language);?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?= $SEOAIC->multilang->get_multilang_checkboxes(); ?>

                    <div class="col-12">
                        <label for="seoaic_writing_style"><?php _e('Writing Style (ex.: Friendly, Professional, Educational, Formal)', 'seoaic');?></label>
                        <input type="text" required
                               id="seoaic_writing_style"
                               name="seoaic_writing_style"
                               class="seoaic-form-item form-input light"
                               value="<?php echo !empty($SEOAIC_OPTIONS['seoaic_writing_style']) ? esc_attr($SEOAIC_OPTIONS['seoaic_writing_style']) : 'Friendly';?>"/>
                    </div>

<!--                    <div class="col-12">-->
<!--                        <label for="seoaic_image_generator">--><?php //_e('Default image generator', 'seoaic');?><!--</label>-->
<!---->
<!--                        <select id="seoaic_image_generator" class="seoaic-form-item form-select mb-5"-->
<!--                                name="seoaic_image_generator"-->
<!--                                required>-->
<!--                            --><?php
//                            foreach ($image_generators as $key => $image_generator) : ?>
<!--                                <option value="--><?php //= esc_attr($key);?><!--"-->
<!--                                    --><?php //= ($key === $image_generator_default) ? 'selected' : '';?>
<!--                                >--><?php //= esc_html($image_generator);?><!--</option>-->
<!--                            --><?php //endforeach; ?>
<!--                        </select>-->
<!--                    </div>-->
<!---->
<!--                    <div class="col-12">-->
<!--                        <label for="seoaic_image_style">--><?php //_e('Image Style', 'seoaic');?><!--</label>-->
<!---->
<!--                        <select id="seoaic_image_style" class="seoaic-form-item form-select mb-5"-->
<!--                                name="seoaic_image_style"-->
<!--                                required>-->
<!--                            --><?php //foreach ($image_styles as $key => $img_style) : ?>
<!--                                <option value="--><?php //= esc_attr($key);?><!--"-->
<!--                                    --><?php //= (!empty($SEOAIC_OPTIONS['seoaic_image_style']) && $key === $SEOAIC_OPTIONS['seoaic_image_style']) ? 'selected' : '';?>
<!--                                >--><?php //= esc_html($img_style);?><!--</option>-->
<!--                            --><?php //endforeach; ?>
<!--                        </select>-->
<!--                    </div>-->
<!---->
<!--                    <div class="col-12">-->
<!--                        <label for="seoaic_image_colors">--><?php //_e('Image Colors', 'seoaic');?><!--</label>-->
<!---->
<!--                        <input id="seoaic_image_colors" data-name="Main colors"-->
<!--                               class="seoaic-form-item form-input light color-palette-select"-->
<!--                               name="seoaic_image_colors"-->
<!--                               type="text"-->
<!--                               value="--><?php //= !empty($SEOAIC_OPTIONS['seoaic_image_colors']) ? esc_attr($SEOAIC_OPTIONS['seoaic_image_colors']) : '';?><!--"/>-->
<!--                        <div class="color-palettes">-->
<!--                            --><?php
//                            foreach ($colors as $key => $color) {
//                                echo '<div class="color" data-color="' . esc_attr($color) . '" style="background-color: ' . esc_attr($color) . '"></div>';
//                            }
//                            ?>
<!--                        </div>-->
<!---->
<!--                        <input id="seoaic_image_colors_accent" data-name="Accent colors"-->
<!--                               class="seoaic-form-item form-input light color-palette-select"-->
<!--                               name="seoaic_image_colors_accent"-->
<!--                               type="text"-->
<!--                               value="--><?php //= !empty($SEOAIC_OPTIONS['seoaic_image_colors_accent']) ? esc_attr($SEOAIC_OPTIONS['seoaic_image_colors_accent']) : '';?><!--"/>-->
<!--                        <div class="color-palettes">-->
<!--                            --><?php
//                            foreach ($colors as $key => $color) {
//                                echo '<div class="color" data-color="' . esc_attr($color) . '" style="background-color: ' . esc_attr($color) . '"></div>';
//                            }
//                            ?>
<!--                        </div>-->
<!---->
<!--                        <input id="seoaic_image_colors_additional" data-name="Additional colors"-->
<!--                               class="seoaic-form-item form-input light color-palette-select"-->
<!--                               name="seoaic_image_colors_additional"-->
<!--                               type="text"-->
<!--                               value="--><?php //= !empty($SEOAIC_OPTIONS['seoaic_image_colors_additional']) ? esc_attr($SEOAIC_OPTIONS['seoaic_image_colors_additional']) : '';?><!--"/>-->
<!--                        <div class="color-palettes">-->
<!--                            --><?php
//                            foreach ($colors as $key => $color) {
//                                echo '<div class="color" data-color="' . esc_attr($color) . '" style="background-color: ' . esc_attr($color) . '"></div>';
//                            }
//                            ?>
<!--                        </div>-->
<!--                    </div>-->

                    <div class="col-12">
                        <label for="seoaic_content_guidelines"><?php _e('Content Guidelines', 'seoaic');?></label>

                        <textarea id="seoaic_content_guidelines" class="seoaic-form-item form-input light mb-0"
                                  name="seoaic_content_guidelines" rows="6"><?php echo isset($SEOAIC_OPTIONS['seoaic_content_guidelines']) ? esc_html($SEOAIC_OPTIONS['seoaic_content_guidelines']) : 'When referring to us as a company speak about "Us" or "We" instead of them.';?></textarea>
                    </div>
                </div>
            </div>

            <div class="col-6 right-side">
                <a href="<?php echo admin_url('admin.php?page=seoaic-onboarding-wizard'); ?>" class="seoaic_schedule_keywords">
                    <div class="title-key">
                        <h3><?php _e('Wizard', 'seoaic'); ?></h3>
                        <p><?php _e('Make onboarding a breeze with our new feature - give it a try!', 'seoaic');?></p>
                    </div>
                    <div class="arrow-key">
                        <div></div>
                    </div>
                </a>

                <a href="<?php echo admin_url('admin.php?page=seoaic-keywords'); ?>" class="seoaic_schedule_keywords">
                    <div class="title-key">
                        <h3>Keywords</h3>
                        <p><?php _e('Go to customize keywords', 'seoaic');?></p>
                    </div>
                    <div class="arrow-key">
                        <div></div>
                    </div>
                </a>

                <a href="<?php echo admin_url('admin.php?page=seoaic-locations'); ?>" class="seoaic_schedule_keywords">
                    <div class="title-key">
                        <h3><?php _e('Locations', 'seoaic'); ?></h3>
                        <p><?php _e('Go to customize Locations', 'seoaic');?></p>
                    </div>
                    <div class="arrow-key">
                        <div></div>
                    </div>
                </a>

                <div class="seoaic_pillar_settings">
                    <div class="pillar_content">
                        <h2>Pillar content links</h2>
                        <div class="toggle-choose-server">
                            <label class="switch">
                                <input class="seoaic-toggle seoaic-form-item" type="checkbox"
                                       name="seoaic_pillar_link_action"
                                       value="on" <?php echo !empty($SEOAIC_OPTIONS['seoaic_pillar_link_action']) ? 'checked' : '';?>>
                                <span class="slider round"><span
                                            class="seoaic-toggle-value seoaic-toggle-value-1">On</span><span
                                            class="seoaic-toggle-value seoaic-toggle-value-2">Off</span></span>
                            </label>
                        </div>
                    </div>

                    <div class="seoaic-language-wrap hidden">
                        <?php $SEOAIC->multilang->get_languages_select(); ?>
                    </div>

                    <div class="seoaic_pillar_wrap_table">

                        <table class="seoaic_pillar_wrap">
                            <thead class="seoaic_pillar_header">
                                <tr>
                                    <?php if($SEOAIC->multilang->is_multilang()) : ?>
                                        <th class="seoaic_pillar-lang">Language</th>
                                    <?php endif; ?>
                                    <th class="seoaic_pillar-name">Name</th>
                                    <th class="seoaic_pillar-link">Link</th>
                                    <th class="seoaic_pillar-description">Description</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody class="seoaic_pillar_wrap" id="seoaic_pillar_tbody">
                                <?php
                                    foreach ((array) $pillar_links as $s) {
                                        if ( is_array($s) ) { ?>
                                            <tr class="seoaic_pillar_item">
                                                <?php if($SEOAIC->multilang->is_multilang()) : ?>
                                                    <td class="seoaic_pillar-lang"><div class="seoaic_pillar_lang"><?php $SEOAIC->multilang->get_languages_select(esc_attr($s['lang']), true); ?></div></td>
                                                <?php endif; ?>

                                                    <td class="seoaic_pillar-name"><div class="seoaic_pillar_name"><input type="text" name="pillar-name" disabled value="<?php echo esc_attr($s['name']) ?>"></div></td>
                                                    <td class="seoaic_pillar-link"><div class="seoaic_pillar_link"><input type="url" name="pillar-url" disabled value="<?php echo esc_attr($s['url']) ?>"></div></td>
                                                    <td class="seoaic_pillar-description"><div class="seoaic_pillar_description"><textarea name="" name="pillar-description" disabled id=""><?php echo esc_html($s['text']) ?></textarea></div></td>
                                                    <td>
                                                        <div class="seoaic_pillar_controll">
                                                            <button title="Edit pillar link" type="button" class="button seoaic-edit-pillar"></button>
                                                            <button title="Remove pillar" type="button" class="button seoaic-remove-idea-button delete-pillar"></button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php
                                        }
                                    }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="seoaic_pillar_add">
                        <a href="#" class="seoaic_pillar_add_btn" data-add="pillar-input" title="+ add a link">+ add a link</a>
                    </div>
                </div>


                <div class="seoaic_schedule_settings">
                    <h2><?php _e('Posts generating schedule', 'seoaic');?></h2>

                    <table class="table">
                        <tbody>
                        <tr>
                            <th scope="row">
                                <input id="seoaic_schedule_days_monday" class="seoaic-form-item"
                                       name="seoaic_schedule_days"
                                       type="checkbox"
                                       value="monday" <?= (isset($SEOAIC_OPTIONS['seoaic_schedule_days']['monday'])) ? 'checked' : ''; ?> />

                            </th>
                            <td>
                                <label for="seoaic_schedule_days_monday" class="seoaic_schedule_days_label">
                                    <?php _e('Monday', 'seoaic');?>
                                </label>
                            </td>
                            <td>
                                <div class="time-select">
                                    <input id="seoaic_schedule_monday_time" class="form-input-time seoaic-form-item"
                                           name="seoaic_schedule_monday_time"
                                           type="text"
                                           value="<?= !empty($SEOAIC_OPTIONS['seoaic_schedule_days']['monday']['time']) ? esc_attr($SEOAIC_OPTIONS['seoaic_schedule_days']['monday']['time']) : '1:00 am';?>"/>
                                    <span class="done"></span><span class="edit"></span>
                                </div>
                            </td>
                            <td>
                                <div class="num-select">
                                    <input id="seoaic_schedule_monday_posts" class="form-input-num seoaic-form-item"
                                           name="seoaic_schedule_monday_posts"
                                           type="number"
                                           min="0"
                                           max="99"
                                           value="<?= !empty($SEOAIC_OPTIONS['seoaic_schedule_days']['monday']['posts']) ? esc_attr($SEOAIC_OPTIONS['seoaic_schedule_days']['monday']['posts']) : '1';?>"/>
                                    <div class="spin">
                                        <div class="up bt"></div>
                                        <div class="down bt"></div>
                                    </div>
                                    <span class="ready"></span>
                                    <span class="label">posts</span><span class="edit"></span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <input id="seoaic_schedule_days_tuesday" class="seoaic-form-item"
                                       name="seoaic_schedule_days"
                                       type="checkbox"
                                       value="tuesday"
                                       <?php echo isset($SEOAIC_OPTIONS['seoaic_schedule_days']['tuesday']) ? 'checked' : $defaultScheduleChecked;?> />
                            </th>
                            <td>
                                <label for="seoaic_schedule_days_tuesday" class="seoaic_schedule_days_label">
                                    <?php _e('Tuesday', 'seoaic');?>
                                </label>
                            </td>
                            <td>
                                <div class="time-select">
                                    <input id="seoaic_schedule_tuesday_time" class="form-input-time seoaic-form-item"
                                           name="seoaic_schedule_tuesday_time"
                                           type="text"
                                           value="<?= !empty($SEOAIC_OPTIONS['seoaic_schedule_days']['tuesday']['time']) ? esc_attr($SEOAIC_OPTIONS['seoaic_schedule_days']['tuesday']['time']) : '1:00 am';?>"/>
                                    <span class="done"></span><span class="edit"></span>
                                </div>
                            </td>
                            <td>
                                <div class="num-select">
                                    <input id="seoaic_schedule_tuesday_posts" class="form-input-num seoaic-form-item"
                                           name="seoaic_schedule_tuesday_posts"
                                           type="number"
                                           min="0"
                                           max="99"
                                           value="<?= !empty($SEOAIC_OPTIONS['seoaic_schedule_days']['tuesday']['posts']) ? esc_attr($SEOAIC_OPTIONS['seoaic_schedule_days']['tuesday']['posts']) : '1';?>"/>
                                    <div class="spin">
                                        <div class="up bt"></div>
                                        <div class="down bt"></div>
                                    </div>
                                    <span class="ready"></span>
                                    <span class="label">posts</span><span class="edit"></span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <input id="seoaic_schedule_days_wednesday" class="seoaic-form-item"
                                       name="seoaic_schedule_days"
                                       type="checkbox"
                                       value="wednesday" <?= (isset($SEOAIC_OPTIONS['seoaic_schedule_days']['wednesday'])) ? 'checked' : ''; ?> />

                            </th>
                            <td>
                                <label for="seoaic_schedule_days_wednesday" class="seoaic_schedule_days_label">
                                    <?php _e('Wednesday', 'seoaic');?>
                                </label>
                            </td>
                            <td>
                                <div class="time-select">
                                    <input id="seoaic_schedule_wednesday_time" class="form-input-time seoaic-form-item"
                                           name="seoaic_schedule_wednesday_time"
                                           type="text"
                                           value="<?= !empty($SEOAIC_OPTIONS['seoaic_schedule_days']['wednesday']['time']) ? esc_attr($SEOAIC_OPTIONS['seoaic_schedule_days']['wednesday']['time']) : '1:00 am';?>"/>
                                    <span class="done"></span><span class="edit"></span>
                                </div>
                            </td>
                            <td>
                                <div class="num-select">
                                    <input id="seoaic_schedule_wednesday_posts" class="form-input-num seoaic-form-item"
                                           name="seoaic_schedule_wednesday_posts"
                                           type="number"
                                           min="0"
                                           max="99"
                                           value="<?= !empty($SEOAIC_OPTIONS['seoaic_schedule_days']['wednesday']['posts']) ? esc_attr($SEOAIC_OPTIONS['seoaic_schedule_days']['wednesday']['posts']) : '1';?>"/>
                                    <div class="spin">
                                        <div class="up bt"></div>
                                        <div class="down bt"></div>
                                    </div>
                                    <span class="ready"></span>
                                    <span class="label">posts</span><span class="edit"></span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <input type="checkbox"
                                       id="seoaic_schedule_days_thursday"
                                       name="seoaic_schedule_days"
                                       class="seoaic-form-item"
                                       value="thursday"
                                       <?php echo isset($SEOAIC_OPTIONS['seoaic_schedule_days']['thursday']) ? 'checked' : $defaultScheduleChecked;?> />
                            </th>
                            <td>
                                <label for="seoaic_schedule_days_thursday" class="seoaic_schedule_days_label">
                                    <?php _e('Thursday', 'seoaic');?>
                                </label>
                            </td>
                            <td>
                                <div class="time-select">
                                    <input id="seoaic_schedule_thursday_time" class="form-input-time seoaic-form-item"
                                           name="seoaic_schedule_thursday_time"
                                           type="text"
                                           value="<?= !empty($SEOAIC_OPTIONS['seoaic_schedule_days']['thursday']['time']) ? esc_attr($SEOAIC_OPTIONS['seoaic_schedule_days']['thursday']['time']) : '1:00 am';?>"/>
                                    <span class="done"></span><span class="edit"></span>
                                </div>
                            </td>
                            <td>
                                <div class="num-select">
                                    <input id="seoaic_schedule_thursday_posts" class="form-input-num seoaic-form-item"
                                           name="seoaic_schedule_thursday_posts"
                                           type="number"
                                           min="0"
                                           max="99"
                                           value="<?= !empty($SEOAIC_OPTIONS['seoaic_schedule_days']['thursday']['posts']) ? esc_attr($SEOAIC_OPTIONS['seoaic_schedule_days']['thursday']['posts']) : '1';?>"/>
                                    <div class="spin">
                                        <div class="up bt"></div>
                                        <div class="down bt"></div>
                                    </div>
                                    <span class="ready"></span>
                                    <span class="label">posts</span><span class="edit"></span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <input id="seoaic_schedule_days_friday" class="seoaic-form-item"
                                       name="seoaic_schedule_days"
                                       type="checkbox"
                                       value="friday" <?= (isset($SEOAIC_OPTIONS['seoaic_schedule_days']['friday'])) ? 'checked' : ''; ?> />
                            </th>
                            <td>
                                <label for="seoaic_schedule_days_friday" class="seoaic_schedule_days_label">
                                    <?php _e('Friday', 'seoaic');?>
                                </label>
                            </td>
                            <td>
                                <div class="time-select">
                                    <input id="seoaic_schedule_friday_time" class="form-input-time seoaic-form-item"
                                           name="seoaic_schedule_friday_time"
                                           type="text"
                                           value="<?= !empty($SEOAIC_OPTIONS['seoaic_schedule_days']['friday']['time']) ? esc_attr($SEOAIC_OPTIONS['seoaic_schedule_days']['friday']['time'] ): '1:00 am';?>"/>
                                    <span class="done"></span><span class="edit"></span>
                                </div>
                            </td>
                            <td>
                                <div class="num-select">
                                    <input id="seoaic_schedule_friday_posts" class="form-input-num seoaic-form-item"
                                           name="seoaic_schedule_friday_posts"
                                           type="number"
                                           min="0"
                                           max="99"
                                           value="<?= !empty($SEOAIC_OPTIONS['seoaic_schedule_days']['friday']['posts']) ? esc_attr($SEOAIC_OPTIONS['seoaic_schedule_days']['friday']['posts']) : '1';?>"/>
                                    <div class="spin">
                                        <div class="up bt"></div>
                                        <div class="down bt"></div>
                                    </div>
                                    <span class="ready"></span>
                                    <span class="label">posts</span><span class="edit"></span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <input id="seoaic_schedule_days_saturday" class="seoaic-form-item"
                                       name="seoaic_schedule_days"
                                       type="checkbox"
                                       value="saturday" <?= (isset($SEOAIC_OPTIONS['seoaic_schedule_days']['saturday'])) ? 'checked' : ''; ?> />
                            </th>
                            <td>
                                <label for="seoaic_schedule_days_saturday" class="seoaic_schedule_days_label">
                                    <?php _e('Saturday', 'seoaic');?>
                                </label>
                            </td>
                            <td>
                                <div class="time-select">
                                    <input id="seoaic_schedule_saturday_time" class="form-input-time seoaic-form-item"
                                           name="seoaic_schedule_saturday_time"
                                           type="text"
                                           value="<?= !empty($SEOAIC_OPTIONS['seoaic_schedule_days']['saturday']['time']) ? esc_attr($SEOAIC_OPTIONS['seoaic_schedule_days']['saturday']['time']) : '1:00 am';?>"/>
                                    <span class="done"></span><span class="edit"></span>
                                </div>
                            </td>
                            <td>
                                <div class="num-select">
                                    <input id="seoaic_schedule_saturday_posts" class="form-input-num seoaic-form-item"
                                           name="seoaic_schedule_saturday_posts"
                                           type="number"
                                           min="0"
                                           max="99"
                                           value="<?= !empty($SEOAIC_OPTIONS['seoaic_schedule_days']['saturday']['posts']) ? esc_attr($SEOAIC_OPTIONS['seoaic_schedule_days']['saturday']['posts']) : '1';?>"/>
                                    <div class="spin">
                                        <div class="up bt"></div>
                                        <div class="down bt"></div>
                                    </div>
                                    <span class="ready"></span>
                                    <span class="label">posts</span><span class="edit"></span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <input id="seoaic_schedule_days_sunday" class="seoaic-form-item"
                                       name="seoaic_schedule_days"
                                       type="checkbox"
                                       value="sunday" <?= (isset($SEOAIC_OPTIONS['seoaic_schedule_days']['sunday'])) ? 'checked' : ''; ?> />
                            </th>
                            <td>
                                <label for="seoaic_schedule_days_sunday" class="seoaic_schedule_days_label">
                                    <?php _e('Sunday', 'seoaic');?>
                                </label>
                            </td>
                            <td>
                                <div class="time-select">
                                    <input id="seoaic_schedule_sunday_time" class="form-input-time seoaic-form-item"
                                           name="seoaic_schedule_sunday_time"
                                           type="text"
                                           value="<?= !empty($SEOAIC_OPTIONS['seoaic_schedule_days']['sunday']['time']) ? esc_attr($SEOAIC_OPTIONS['seoaic_schedule_days']['sunday']['time']) : '1:00 am';?>"/>
                                    <span class="done"></span><span class="edit"></span>
                                </div>
                            </td>
                            <td>
                                <div class="num-select">
                                    <input id="seoaic_schedule_sunday_posts" class="form-input-num seoaic-form-item"
                                           name="seoaic_schedule_sunday_posts"
                                           type="number"
                                           min="0"
                                           max="99"
                                           value="<?= !empty($SEOAIC_OPTIONS['seoaic_schedule_days']['sunday']['posts']) ? esc_attr($SEOAIC_OPTIONS['seoaic_schedule_days']['sunday']['posts']) : '1';?>"/>
                                    <div class="spin">
                                        <div class="up bt"></div>
                                        <div class="down bt"></div>
                                    </div>
                                    <span class="ready"></span>
                                    <span class="label">posts</span><span class="edit"></span>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>

                </div>

                <div id="seoaic-idea-exclude-taxonomies" class="col-12">
                    <label for="seoaic-idea-exclude-taxonomies"><?php _e('Exclude taxonomies', 'seoaic');?></label>
                    <div class="terms-select">
                        <?= $exclude_taxonomies;?>
                    </div>
                </div>

                <div class="col-12 seoaic-select-post-type">
                    <label for="seoaic_post_type"><?php _e('Post type to save', 'seoaic');?></label>

                    <select id="seoaic_post_type"
                            class="seoaic-form-item form-select mb-5"
                            name="seoaic_post_type"
                            data-action="seoaic_getCategoriesOfPosttype"
                            data-target-select="seoaic-idea-content-category"
                            required>
                        <?php foreach ($post_types as $post_type) : ?>
                            <option value="<?= esc_attr($post_type);?>"
                                <?= ($selected_post_type === $post_type) ? 'selected' : ''; ?>
                            ><?= esc_html(ucfirst($post_type)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 seoaic-select-post-seoaic_post_template">
                    <label for="seoaic_post_template"><?php _e('Post template', 'seoaic');?></label>

                    <select id="seoaic_post_template"
                            class="seoaic-form-item form-select mb-5"
                            name="seoaic_post_template">
                        <?php
                        echo SEOAIC::makePostTemplatesOptions($postTemplates, $selectedPostTemplate);
                        ?>
                    </select>
                </div>

                <div id="seoaic-idea-content-category" class="col-12 seoaic-select-post-type-cat">
                    <label for="seoaic_default_category"><?php _e('Default posts category', 'seoaic');?></label>
                    <div class="terms-select">
                        <?=$categories;?>
                    </div>
                </div>

                <div class="col-12">
                    <label for="seoaic_default_category"><?php _e('Prompt templates', 'seoaic');?></label>
                    <div class="seoaic_input_repeater posts_mass_generate_prompt_templates">
                        <div class="prompts-list">
                            <?php echo $post_prompt_tmplts_html;?>
                        </div>
                        <a href="#" class="add-prompt" title="<?php _e('Add Prompt Template', 'seoaic');?>"><?php _e('Add Prompt Template', 'seoaic');?></a>
                    </div>
                </div>

                <div class="col-12">
                    <label for="seoaic_industry"><?php _e('Delay post publication, hours', 'seoaic');?></label>

                    <input id="seoaic_publish_delay" class="seoaic-form-item form-input light"
                           name="seoaic_publish_delay"
                           type="text"
                           value="<?php echo !empty($SEOAIC_OPTIONS['seoaic_publish_delay']) ? esc_attr($SEOAIC_OPTIONS['seoaic_publish_delay']) : '120';?>"
                           required/>
                </div>

                <div class="col-12">
                    <label for="seoaic_generate_internal_links"><?php _e('Generate internal links in content', 'seoaic');?></label>

                    <select id="seoaic_generate_internal_links" class="seoaic-form-item form-select mb-5"
                            name="seoaic_generate_internal_links"
                            required>
                        <option value="0">No</option>
                        <option value="1" <?= !empty($SEOAIC_OPTIONS['seoaic_generate_internal_links']) ? 'selected' : ''; ?>>
                            Yes
                        </option>
                    </select>
                </div>

                <div class="col-12">
                    <label for="seoaic_show_related_articles"><?php _e('Show related articles', 'seoaic');?></label>

                    <select id="seoaic_show_related_articles" class="seoaic-form-item form-select mb-5"
                            name="seoaic_show_related_articles"
                            required>
                        <option value="0">No</option>
                        <option value="1" <?= !empty($SEOAIC_OPTIONS['seoaic_show_related_articles']) ? 'selected' : ''; ?>>
                            Yes
                        </option>
                    </select>
                </div>

                <div class="col-12">
                    <label for="seoaic_related_articles_count"><?php _e('Amount of related articles', 'seoaic');?></label>
                    <input id="seoaic_related_articles_count" class="seoaic-form-item form-input light"
                           name="seoaic_related_articles_count"
                           type="number"
                           min="1"
                           max="20"
                           value="<?= !empty($SEOAIC_OPTIONS['seoaic_related_articles_count']) ? esc_attr($SEOAIC_OPTIONS['seoaic_related_articles_count']) : '5';?>"
                           required/>
                </div>

                <div class="col-12">
                    <label for="seoaic_hide_posts"><?php _e('Hide posts created by SEO AI', 'seoaic');?></label>

                    <select id="seoaic_hide_posts" class="seoaic-form-item form-select mb-5"
                            name="seoaic_hide_posts"
                            required>
                        <option value="0">No</option>
                        <option value="1" <?= !empty($SEOAIC_OPTIONS['seoaic_hide_posts']) ? 'selected' : ''; ?>>
                            Yes
                        </option>
                    </select>
                </div>

                <div class="col-12">
                    <label for="seoaic_hide_posts"><?php _e('Make specific SEO AI posts visible', 'seoaic');?>
                        <div class="info inline right">
                            <span class="info-btn">?</span>
                            <div class="info-content">
                                <p><?php _e('You can make specific posts visible even if the general SEO AI posts visibility setting is set to hidden.<br>Optional field.', 'seoaic');?></p>
                            </div>
                        </div>
                    </label>

                    <input type="hidden" name="seoaic_visible_posts[]" class="seoaic-form-item" value="">
                    <select id="seoaic_visible_posts" class="seoaic-form-item form-select mb-5"
                            name="seoaic_visible_posts" multiple>
                        <?php
                        if ($createdPostsQuery->have_posts()) {
                            $visiblePostsIDs = !empty($SEOAIC_OPTIONS['seoaic_visible_posts']) ? array_values($SEOAIC_OPTIONS['seoaic_visible_posts']) : [];

                            while ($createdPostsQuery->have_posts()) {
                                $createdPostsQuery->the_post();
                                $id = get_the_ID();
                                $selected = in_array($id, $visiblePostsIDs) ? 'selected="selected"' : '';
                                ?>
                                <option value=<?php echo $id;?> <?php echo $selected;?>><?php echo get_the_title();?></option>
                                <?php
                            }
                        } else {
                            ?>
                            <option value="">No posts found</option>
                            <?php
                        }
                        ?>
                    </select>
                </div>

                <div id="seoiac-subtitles-range" class="seoaic-settings-range col-12" data-min="0" data-max="15"
                     data-step="1">
                    <label><?php _e('Min/max subtitles in generated frame', 'seoaic');?>:
                        <span class="range-min"><?php echo !empty($SEOAIC_OPTIONS['seoaic_subtitles_range_min']) ? esc_html($SEOAIC_OPTIONS['seoaic_subtitles_range_min']) : '2';?></span>
                        -
                        <span class="range-max"><?php echo !empty($SEOAIC_OPTIONS['seoaic_subtitles_range_max']) ? esc_html($SEOAIC_OPTIONS['seoaic_subtitles_range_max']) : '6';?></span>
                        <input id="seoaic_subtitles_range_min" class="seoaic-settings-range-min seoaic-form-item"
                               type="hidden" name="seoaic_subtitles_range_min"
                               value="<?php echo !empty($SEOAIC_OPTIONS['seoaic_subtitles_range_min']) ? esc_attr($SEOAIC_OPTIONS['seoaic_subtitles_range_min']) : '2';?>">
                        <input id="seoaic_subtitles_range_max" class="seoaic-settings-range-max seoaic-form-item"
                               type="hidden" name="seoaic_subtitles_range_max"
                               value="<?php echo !empty($SEOAIC_OPTIONS['seoaic_subtitles_range_max']) ? esc_attr($SEOAIC_OPTIONS['seoaic_subtitles_range_max']) : '6';?>">
                    </label>
                    <div id="seoiac-subtitles-range-slider" class="seoaic-settings-range-slider"></div>
                </div>

                <div id="seoiac-words-range" class="seoaic-settings-range col-12" data-min="0" data-max="2500"
                     data-step="10">
                    <label><?php _e('Min/max words in generated posts', 'seoaic');?>:
                        <span class="range-min"><?php echo !empty($SEOAIC_OPTIONS['seoaic_words_range_min']) ? esc_html($SEOAIC_OPTIONS['seoaic_words_range_min']) : '500';?></span>
                        -
                        <span class="range-max"><?php echo !empty($SEOAIC_OPTIONS['seoaic_words_range_max']) ? esc_html($SEOAIC_OPTIONS['seoaic_words_range_max']) : '1000';?></span>
                        <input id="seoaic_words_range_min" class="seoaic-settings-range-min seoaic-form-item"
                               type="hidden" name="seoaic_words_range_min"
                               value="<?php echo !empty($SEOAIC_OPTIONS['seoaic_words_range_min']) ? esc_attr($SEOAIC_OPTIONS['seoaic_words_range_min']) : '500';?>">
                        <input id="seoaic_words_range_max" class="seoaic-settings-range-max seoaic-form-item"
                               type="hidden" name="seoaic_words_range_max"
                               value="<?php echo !empty($SEOAIC_OPTIONS['seoaic_words_range_max']) ? esc_attr($SEOAIC_OPTIONS['seoaic_words_range_max']) : '1000';?>">
                    </label>
                    <div id="seoiac-words-range-slider" class="seoaic-settings-range-slider"></div>
                </div>

                <div class="col-12">
                    <button id="seoaic_submit" type="submit" class="button button-primary seoaic-button-primary"><?php _e('Save All', 'seoaic');?></button>
                </div>
            </div>

        </form>
    </div>
</div>

<?php if($SEOAIC->multilang->is_multilang()) : ?>
<script id="seoaic_pillar_template" type="text/template">
    <tr class="seoaic_pillar_item">
        <td class="seoaic_pillar-lang"><div class="seoaic_pillar_lang"></div></td>
        <td class="seoaic_pillar-name"><div class="seoaic_pillar_name"><input type="text" name="pillar-name" placeholder="Name" value=""></div></td>
        <td class="seoaic_pillar-link"><div class="seoaic_pillar_link"><input type="url" name="pillar-url" placeholder="Url" value=""></div></td>
        <td class="seoaic_pillar-description"><div class="seoaic_pillar_description"><textarea name="" name="pillar-description" placeholder="Description"></textarea></div></td>
        <td>
            <div class="seoaic_pillar_controll">
                <button title="Edit pillar link" type="button" class="button seoaic-edit-pillar"></button>
                <button title="Remove pillar" type="button" class="button seoaic-remove-idea-button delete-pillar"></button>
            </div>
        </td>
    </tr>
</script>
<?php else : ?>
    <script id="seoaic_pillar_template" type="text/template">
        <tr class="seoaic_pillar_item">
            <td class="seoaic_pillar-name"><div class="seoaic_pillar_name"><input type="text" name="pillar-name" placeholder="Name" value=""></div></td>
            <td class="seoaic_pillar-link"><div class="seoaic_pillar_link"><input type="url" name="pillar-url" placeholder="Url" value=""></div></td>
            <td  class="seoaic_pillar-description"><div class="seoaic_pillar_description"><textarea name="" name="pillar-description" placeholder="Description"></textarea></div></td>
            <td>
                <div class="seoaic_pillar_controll">
                    <button title="Edit pillar link" type="button" class="button seoaic-edit-pillar"></button>
                    <button title="Remove pillar" type="button" class="button seoaic-remove-idea-button delete-pillar"></button>
                </div>
            </td>
        </tr>
    </script>
<?php endif; ?>