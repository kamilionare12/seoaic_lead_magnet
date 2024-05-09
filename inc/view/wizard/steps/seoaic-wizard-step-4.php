<?php

use SEOAIC\Wizard;

global $SEOAIC_OPTIONS, $SEOAIC;

$args = [
    'numberposts' => -1,
    'post_type' => 'seoaic-post',
    'post_status' => 'seoaic-idea',
    'order' => 'ASC',
    'orderby' => 'ID',
];
$all_ideas = get_posts($args);
$is_all = isset($SEOAIC_OPTIONS['wizard_settings']['display_ideas']) && 'all' == $SEOAIC_OPTIONS['wizard_settings']['display_ideas'];
$ideas = $all_ideas;
$generated_ideas_ids = get_transient(Wizard::FIELD_GENERATED_IDEAS_IDS);
if (
    !$is_all
    && !empty($generated_ideas_ids)
) {
    $ideas = array_filter($all_ideas, function($idea) use ($generated_ideas_ids) {
        return in_array($idea->ID, $generated_ideas_ids);
    });
}
$ideas_exist = !empty($ideas) && is_array($ideas);

$credits = $SEOAIC->get_api_credits();
$selected_keywords = get_transient(Wizard::FIELD_SELECTED_KEYWORDS);
$selected_keywords_names = !empty($selected_keywords) ? array_map(function($kw) {
    return $kw['name'];
}, $selected_keywords) : [];
?>
<div class="step-container" data-step="4">
    <p class="step-number">Step 4</p>
    <p class="step-header mb-40">Here are the ideas! Pick one title to generate post.</p>
    <div class="step-content inner">
        <div class="bottom">
            <?php
            if ($ideas_exist) {
                ?>
                <div class="ideas-settings mb-10">
                    <button class="wizard-entities-type-button <?php echo $is_all ? ' active' : '';?>"
                            data-action="seoaic_wizard_reload_entities"
                            data-entities="ideas"
                            data-type="all"
                            data-callback="window_reload"
                    >
                        Show all
                    </button>
                    <button class="wizard-entities-type-button <?php echo !$is_all ? ' active' : '';?>"
                            data-action="seoaic_wizard_reload_entities"
                            data-entities="ideas"
                            data-type="new"
                            data-callback="window_reload"
                    >
                        Show new
                    </button>
                </div>
                <div class="seoaic-ideas-posts">
                    <input type="hidden" id="posts-credit" name="posts-credit" value="<?php echo $credits['posts'];?>">
                    <input type="hidden" id="alert-posts-credit" name="alert-posts-credit" value="You have not enough posts credit!">
                    <?php
                    $option = get_option('seoaic_background_post_generation', false);
                    $process = !empty($option['ideas']) ? $option['ideas'] : [];

                    foreach ($ideas as $idea) {
                        $idea_type = get_post_meta($idea->ID, '_idea_type', true);
                        $idea_type = !empty($idea_type) ? $idea_type : 'default';
                        ?>
                        <div id="idea-post-<?php echo $idea->ID;?>"
                             class="post <?php echo in_array($idea->ID, $process) ? 'post-is-generating' : '';?>"
                            <?php echo (!empty($multi_current_language) && $multi_current_language !== $SEOAIC->multilang->get_post_language($idea->ID)) ? 'style="display:none;"' : '';?>
                        >
                            <div class="idea-content" data-post-id="<?php echo $idea->ID;?>">
                                <div class="num">
                                    <div class="checkbox-wrapper-mc">
                                        <input id="idea-mass-create-<?php echo $idea->ID;?>" type="checkbox"
                                               class="idea-mass-create" name="idea-mass-create"
                                               value="<?php echo $idea->ID;?>">
                                        <label for="idea-mass-create-<?php echo $idea->ID;?>" class="check">
                                            <div class="checkbox-wrapper-svg">
                                                <svg width="18px" height="18px" viewBox="0 0 18 18">
                                                    <path d="M1,9 L1,3.5 C1,2 2,1 3.5,1 L14.5,1 C16,1 17,2 17,3.5 L17,14.5 C17,16 16,17 14.5,17 L3.5,17 C2,17 1,16 1,14.5 L1,9 Z"></path>
                                                    <polyline points="1 9 7 14 15 4"></polyline>
                                                </svg>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div class="heading">
                                    <div class="title td-idea-title"><?php echo $idea->post_title;?></div>
                                    <?php if ($idea_type !== 'default') : ?>
                                        <div class="seoaic-idea-type"><?php echo seoaic_get_prompt_template_types()[$idea_type];?></div>
                                    <?php endif; ?>

                                    <?php $SEOAIC->multilang->get_language_translations_control($idea->ID, $ideas); ?>

                                </div>
                                <button title="Edit idea" data-post-id="<?php echo $idea->ID;?>" type="button"
                                        class="button button-success seoaic-edit-idea-button ml-auto modal-button"
                                        data-modal="#edit-idea"
                                        data-mode="edit"
                                        data-form-callback="window_reload"
                                        data-content="<?php _e('Edit Idea', 'seoaic');?>"
                                    <?php if ($SEOAIC->multilang->is_multilang() && false === $SEOAIC->multilang->get_post_language($idea->ID)) : ?>
                                        data-languages="true"
                                    <?php endif; ?>
                                >
                                    <div class="dn edit-form-items">
                                        <input type="hidden" name="item_id" value="<?php echo $idea->ID;?>" data-label="Id">
                                        <input type="hidden" name="item_name" value="<?php echo $idea->post_title;?>" data-label="Name">
                                    </div>
                                </button>
                                <button title="Remove idea" type="button"
                                        class="button button-danger seoaic-remove-idea-button modal-button confirm-modal-button"
                                        data-post-id="<?php echo $idea->ID;?>"
                                        data-modal="#seoaic-confirm-modal"
                                        data-action="seoaic_remove_idea"
                                        data-form-callback="window_reload"
                                        data-content="Do you want to remove this idea?"
                                >
                                </button>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                <?php
            } else {
                ?>
                <p class="no-keywords text-center">No ideas were generated. Please get back and repeat the previous step.</p>
                <?php
            }
            ?>
        </div>
        <div class="buttons-row mt-40">
            <button data-title="Back" type="button"
                    class="transparent-button-primary outline ml-auto seoaic-ajax-button"
                    data-action="seoaic_wizard_step_back"
                    data-callback="window_reload">
                <?php _e('Back', 'seoaic');?>
            </button>
            <?php
            if ($ideas_exist) {
                ?>
                <!-- <button title="Generate Content" type="button"
                        class="button-primary seoaic-button-primary mass-effect-button seoaic-generate-posts-button modal-button confirm-modal-button generate-keyword-based ml-15"
                        data-modal="#seoaic-post-mass-creation-modal" data-callback-before="before_open_mass_create" data-action="seoaic_wizard_posts_mass_create"
                        data-form-callback="window_reload"
                        data-content="You will generate posts from <b class='additional-items-amount'></b> following ideas:">
                    <?php _e('Generate Content', 'seoaic');?>
                    <div class="dn additional-form-items"></div>
                </button> -->
                <button title="Generate Content" type="button"
                        disabled
                        id="wizard_generate_posts_button"
                        class="button-primary seoaic-button-primary mass-effect-button seoaic-wizard-generate-posts-button generate-keyword-based confirm-modal-button modal-button ml-15"
                        data-modal="#wizard_generate_posts"
                        data-action="seoaic_wizard_posts_mass_create"
                        data-form-callback="window_reload"
                        data-selected-keywords="<?php echo implode(',',$selected_keywords_names);?>"
                        data-content="You will generate posts from <b class='additional-items-amount'></b> following idea(s):">
                    <?php _e('Generate Content', 'seoaic');?>
                    <div class="dn additional-form-items"></div>
                </button>
                <?php
            }
            ?>
        </div>
    </div>
</div>