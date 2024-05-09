<?php

use SEOAIC\loaders\PostsGenerationLoader;

if (!current_user_can('seoaic_edit_plugin')) {
    return;
}

if (isset($_GET['settings-updated'])) {
    add_settings_error('seoaic_messages', 'seoaic_message', __('Settings Saved', 'mdsf'), 'updated');
}

settings_errors('seoaic_messages');

global $SEOAIC_OPTIONS, $SEOAIC;
$args = [
    'numberposts' => -1,
    'post_type' => 'seoaic-post',
    'post_status' => 'seoaic-idea',
    'order' => 'ASC',
    'orderby' => 'ID',
    'meta_query' => [
        'relation' => 'OR',
        [
	        'key' => 'seoaic_idea_source',
	        'compare' => 'NOT EXISTS'
        ]
    ]
];

if (!empty($_GET['filter']) && $_GET['filter'] === 'scheduled') {
    $args['meta_query'] = [
        'relation' => 'OR',
        [
            'key' => 'seoaic_idea_postdate',
            'value' => [''],
            'compare' => 'NOT IN'
        ]
    ];
}
/* else {
    $args['meta_query'] = [
        'relation' => 'OR',
        [
            'key' => 'seoaic_idea_postdate',
            'compare' => 'NOT EXISTS'
        ]
    ];
}*/

$args = $SEOAIC->multilang->update_post_args($args);

$ideas = get_posts($args);

$multi_current_language = $SEOAIC->multilang->get_current_language();

$ideas = $SEOAIC->multilang->sort_posts_by_languages($ideas);
$credits = $SEOAIC->get_api_credits();

?>
<div id="seoaic-admin-container" class="wrap">
    <h1 id="seoaic-admin-title">
        <?php echo seoai_get_logo('logo.svg'); ?>
        <span>
            <?php echo esc_html(get_admin_page_title()); ?>
        </span>
    </h1>
    <?= $SEOAIC->get_background_process_loader(); ?>
    <div id="seoaic-admin-body" class="columns-2 seoaic-with-loader idea-page">
        <div class="row full-width">
            <div class="col-6 left-side">
                <div class="header seoaic-flip-box">

                    <div class="seoaic-flip-container">

                        <div class="seoaic-flip-item seoaic-flip-front">

                            <div class="head-buttons">
                                <div class="btn-sc">
                                    <div class="info">
                                        <span class="info-btn">?</span>
                                        <div class="info-content">
                                            <h4>Mass Content Creation</h4>
                                            <p>You can generate multiple posts at once and schedule them over
                                                a specific period. Use Generate Ideas button, specify the number of
                                                ideas and
                                                select
                                                the “Add to posting schedule” checkbox
                                                to create a large number of blog posts in a batch process.</p>
                                        </div>
                                    </div>
                                    <button type="button"
                                        title="<?php _e('Generate ideas', 'seoaic');?>"
                                            class="button-primary seoaic-button-primary seoaic-generate-ideas-button modal-button"
                                            data-modal="#generate-ideas-new-keywords"
                                            data-action="seoaic_generate_ideas_new_keywords"
                                            data-form-callback="window_reload"
                                    ><?php _e('Generate ideas', 'seoaic');?>
                                    </button>
                                </div>
                                <button type="button"
                                        class="button-primary seoaic-button-primary outline modal-button"
                                        data-title="<?= __('Add new Idea', 'seoaic'); ?>"
                                        data-modal="#add-idea"
                                        data-mode="add"
                                        data-single="no"
                                        data-form-callback="window_reload"
                                    <?php if ($SEOAIC->multilang->is_multilang()) : ?>
                                        data-language-parent-id=""
                                        data-languages="true"
                                        data-language="<?= $SEOAIC->multilang->get_default_language() ?>"
                                    <?php endif; ?>
                                ><span class="vertical-align-middle dashicons dashicons-plus"></span> Add an Idea
                                    <div class="dn edit-form-items">
                                        <input type="hidden" name="item_name" value=""
                                               data-label="Name (separate ideas by new line)">
                                    </div>
                                </button>
                            </div>

                            <div class="schedule-switcher">

                                <div class="checkbox-wrapper-mc">
                                    <input id="idea-mass-create-all" type="checkbox" class="idea-mass-create"
                                           name="idea-mass-create-all" value="all">
                                    <label for="idea-mass-create-all" class="check">
                                        <div class="checkbox-wrapper-svg">
                                            <svg width="18px" height="18px" viewBox="0 0 18 18">
                                                <path d="M1,9 L1,3.5 C1,2 2,1 3.5,1 L14.5,1 C16,1 17,2 17,3.5 L17,14.5 C17,16 16,17 14.5,17 L3.5,17 C2,17 1,16 1,14.5 L1,9 Z"></path>
                                                <polyline points="1 9 7 14 15 4"></polyline>
                                            </svg>
                                        </div>
                                    </label>
                                </div>

                                <a href="/wp-admin/admin.php?page=seoaic-ideas"
                                   title="<?= (!empty($_GET['filter']) && $_GET['filter'] === 'scheduled') ? 'Show scheduled ideas' : 'Show unscheduled ideas' ?>"
                                   class="unscheduled<?= (!empty($_GET['filter']) && $_GET['filter'] === 'scheduled') ? '' : ' active' ?>">All
                                    ideas</a>

                                <a href="/wp-admin/admin.php?page=seoaic-ideas&filter=scheduled"
                                   title="<?= (!empty($_GET['filter']) && $_GET['filter'] === 'scheduled') ? 'Show scheduled ideas' : 'Show unscheduled ideas' ?>"
                                   class="scheduled<?= (!empty($_GET['filter']) && $_GET['filter'] === 'scheduled') ? ' active' : '' ?>">Scheduled
                                    posts</span></a>

                                <button type="button"
                                        class="button button-danger seoaic-remove-all-ideas-button ml-auto modal-button confirm-modal-button"
                                        data-post-id="all"
                                        data-modal="#seoaic-confirm-modal"
                                        data-action="seoaic_remove_idea"
                                        data-form-callback="window_reload"
                                        data-content="Do you want to remove ALL ideas?"
                                >Delete all ideas
                                    <div class="dn additional-form-items">
                                        <label><input class="seoaic-form-item" type="checkbox" name="schedule_too"
                                                      value="1"><?= __('Remove scheduled ideas too ', 'seoaic'); ?>
                                        </label>
                                    </div>
                                </button>

                            </div>
                        </div>

                        <div class="seoaic-flip-item seoaic-flip-side">
                            <div class="head-buttons">
                                <button title="Mass create" type="button"
                                        class="button-primary seoaic-button-primary mass-effect-button seoaic-generate-posts-button modal-button confirm-modal-button"
                                        data-modal="#seoaic-post-mass-creation-modal"
                                        data-callback-before="before_open_mass_create"
                                        data-action="seoaic_posts_mass_create"
                                        data-title="Posts mass creation"
                                        data-form-callback="window_reload"
                                        data-content="You will generate posts from <b class='additional-items-amount'></b> following ideas:"
                                >Mass create
                                    <div class="dn additional-form-items"></div>
                                </button>
                                <button type="button"
                                        class="button-primary seoaic-button-primary mass-effect-button outline modal-button confirm-modal-button"
                                        data-modal="#schedule-posts-modal"
                                        data-action="seoaic_schedule_posts"
                                        data-content="Do you want to schedule generation of <b class='additional-items-amount'></b> following ideas:"
                                        data-form-callback="window_reload"
                                ><?= __('Schedule posts', 'seoaic'); ?>
                                    <div class="dn additional-form-items"></div>
                                </button>
                            </div>
                            <div class="schedule-switcher">
                                <span class="seoaic-checked-amount">Selected: <span
                                            class="seoaic-checked-amount-num"></span></span>
                                <button type="button" class="idea-mass-create-uncheck-all">Remove selection</button>

                                <button type="button"
                                        class="button button-danger mass-effect-button seoaic-remove-all-ideas-button ml-auto modal-button confirm-modal-button"
                                        data-modal="#seoaic-confirm-modal"
                                        data-action="seoaic_remove_idea"
                                        data-form-callback="window_reload"
                                        data-content="Do you want to remove <b class='additional-items-amount'></b> following ideas?"
                                >Delete selected ideas
                                    <div class="dn additional-form-items"></div>
                                </button>

                            </div>
                        </div>

                    </div>

                </div>

                <div class="seoaic-ideas-posts">
                    <?php
                    $option = PostsGenerationLoader::getPostsOption();
                    $process_ideas = !empty($option['total']) ? $option['total'] : [];
                    $process_posts = !empty($option['done']) ? $option['done'] : [];

                    foreach ($ideas as $k => $idea) {
                        $idea_type = get_post_meta($idea->ID, '_idea_type', true);
                        $idea_type = !empty($idea_type) ? $idea_type : 'default';
                        $generating_status = in_array($idea->ID, $process_ideas) && !in_array($idea->ID, $process_posts)? ' post-is-generating' : '';
                        $seoaic_generate_status = get_post_meta($idea->ID, 'seoaic_generate_status', true);
                        $failed_status = 'failed' == $seoaic_generate_status ? ' failed' : '';
                        ?>

                        <div id="idea-post-<?= $idea->ID ?>"
                             class="post <?php echo esc_attr($generating_status) . esc_attr($failed_status);?>"
                            <?= (!empty($multi_current_language) && $multi_current_language !== $SEOAIC->multilang->get_post_language($idea->ID)) ? 'style="display:none;"' : '' ?>
                        >
                            <div class="idea-content" data-post-id="<?= $idea->ID; ?>">
                                <div class="num">
                                    <div class="checkbox-wrapper-mc">
                                        <input id="idea-mass-create-<?= $idea->ID; ?>" type="checkbox"
                                               class="idea-mass-create" name="idea-mass-create"
                                               value="<?= $idea->ID; ?>">
                                        <label for="idea-mass-create-<?= $idea->ID; ?>" class="check">
                                            <div class="checkbox-wrapper-svg">
                                                <svg width="18px" height="18px" viewBox="0 0 18 18">
                                                    <path d="M1,9 L1,3.5 C1,2 2,1 3.5,1 L14.5,1 C16,1 17,2 17,3.5 L17,14.5 C17,16 16,17 14.5,17 L3.5,17 C2,17 1,16 1,14.5 L1,9 Z"></path>
                                                    <polyline points="1 9 7 14 15 4"></polyline>
                                                </svg>
                                            </div>
                                            <span class="checkbox-wrapper-item-id"><?= $idea->ID; ?></span>
                                        </label>
                                    </div>
                                </div>
                                <div class="heading">
                                    <div class="seoaic-idea-icons"><?= $SEOAIC->ideas->get_idea_icons($idea->ID) ?></div>
                                    <div class="title td-idea-title"><?= $idea->post_title; ?></div>
                                    <?php if ($idea_type !== 'default') : ?>
                                        <div class="seoaic-idea-type"><?= seoaic_get_prompt_template_types()[$idea_type] ?></div>
                                    <?php endif; ?>

                                    <?php $SEOAIC->multilang->get_language_translations_control($idea->ID, $ideas); ?>

                                </div>

                                <button title="Edit idea" data-post-id="<?= $idea->ID ?>" type="button"
                                        class="button button-success seoaic-edit-idea-button ml-auto modal-button"
                                        data-modal="#edit-idea"
                                        data-mode="edit"
                                        data-form-callback="window_reload"
                                        data-content="<?= __('Edit Idea', 'seoaic'); ?>"
                                    <?php if ($SEOAIC->multilang->is_multilang() && false === $SEOAIC->multilang->get_post_language($idea->ID)) : ?>
                                        data-languages="true"
                                    <?php endif; ?>
                                >
                                    <div class="dn edit-form-items">
                                        <input type="hidden" name="item_id" value="<?= $idea->ID; ?>" data-label="Id">
                                        <input type="hidden" name="item_name" value="<?= $idea->post_title; ?>"
                                               data-label="Name">
                                    </div>
                                </button>
                                <button title="Remove idea" type="button"
                                        class="button button-danger seoaic-remove-idea-button modal-button confirm-modal-button"
                                        data-post-id="<?= $idea->ID ?>"
                                        data-modal="#seoaic-confirm-modal"
                                        data-action="seoaic_remove_idea"
                                        data-form-callback="window_reload"
                                        data-content="Do you want to remove this idea?"
                                >
                                </button>
                                <?php if ( isset($_GET['debug']) ) : ?>
                                <button title="Transform" type="button"
                                        class="button seoaic-transform-idea-button modal-button confirm-modal-button"
                                        data-post-id="<?= $idea->ID ?>"
                                        data-modal="#seoaic-confirm-modal"
                                        data-action="seoaic_transform_idea"
                                        data-form-callback="window_reload"
                                        data-content="Do you want to transform this idea?"
                                >
                                    <span class="dashicons dashicons-image-rotate"></span>
                                </button>
                                <?php endif; ?>
                            </div>

                            <div class="idea-btn">
                                <button title="Edit idea content" type="button" id="edit-idea-button-<?= $idea->ID ?>"
                                        class="button button-primary seoaic-button-primary seoaic-get-idea-content-button seoaic-ajax-button"
                                        data-post-id="<?= $idea->ID ?>"
                                        data-action="seoaic_get_idea_content"
                                        data-callback="view_idea_content"
                                        data-callback-before="before_get_idea_content"
                                >
                                    <span class="post-is-generating-label">generating</span>
                                </button>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>

            <div class="col-6 right-side">
                <div class="seoaic-content-idea-box">
                    <div class="seoaic-content-idea-box-slide">
                        <div class="header">
                            <div class="top-line">
                                <h4>Credits you have:</h4>
                                <a href="#" class="upgrade-my-plan">Upgrade My Plan</a>
                            </div>
                            <div class="results seoaic-credits-panel">
                                <div class="posts">
                                    <input type="hidden" id="posts-credit" name="posts-credit"
                                           value="<?= $credits['posts'] ?>">
                                    <input type="hidden" id="alert-posts-credit" name="alert-posts-credit"
                                           value="You have not enough posts credit!">
                                    <div class="num"><?= $credits['posts']; ?></div>
                                    <div class="text">Posts</div>
                                </div>
                                <div class="ideas">
                                    <div class="num"><?= $credits['ideas'] ?></div>
                                    <div class="text">Ideas</div>
                                </div>
                                <div class="frames">
                                    <div class="num"><?= $credits['frames'] ?></div>
                                    <div class="text">Frames</div>
                                </div>
                            </div>
                        </div>
                        <div class="idea-box">
                            <div class="seoaic-idea-content-section">
                                <div id="seoaic-idea-title"></div>
                                <button title="Generate frame" data-post-id="" type="button"
                                        class="seoaic-generate-skeleton-button seoaic-ajax-button"
                                        data-action="seoaic_generate_skeleton"
                                        data-callback="view_idea_content"
                                >
                                    Generate Frame
                                </button>

                                <button title="Generate post" data-post-id="" type="button"
                                        class="seoaic-generate-post-button seoaic-ajax-button"
                                        data-action="seoaic_generate_post"
                                        data-callback="window_generate_post"
                                        data-callback-before="before_generate_post"
                                        data-callback-before-content="You should save idea first!"
                                >
                                    Generate post
                                </button>
                            </div>
                            <div id="seoaic-idea-content-skeleton" class="seoaic-idea-content-section">
                                <div class="top">
                                    <h3 class="seoaic-section-idea-title">Subtitles</h3>
                                    <button title="Add subtitle" type="button"
                                            class="seoaic-add-idea-subtitle modal-button confirm-modal-button"
                                            data-modal="#add-idea"
                                            data-mode="add"
                                            data-title="Add subtitle"
                                            data-form-before-callback="add_item"
                                            data-action="subtitle"
                                    >
                                        <span class="vertical-align-middle dashicons dashicons-plus"></span><span>Add subtitle</span>
                                        <div class="dn edit-form-items">
                                            <input type="hidden" name="item_name" value="" data-label="Name">
                                        </div>
                                    </button>
                                </div>

                                <ul id="seoaic-idea-skeleton-sortable"
                                    class="seoaic-idea-content-section-subtitle"></ul>
                            </div>
                            <div id="seoaic-idea-content-keywords" class="seoaic-idea-content-section">
                                <div class="top">
                                    <h3 class="seoaic-section-idea-title">Keywords</h3>
                                    <button title="Add keyword" type="button"
                                            class="seoaic-add-idea-keyword modal-button confirm-modal-button"
                                            data-modal="#add-idea"
                                            data-mode="add"
                                            data-title="Add keyword"
                                            data-form-before-callback="add_item"
                                            data-action="keyword"
                                    >
                                        <span class="vertical-align-middle dashicons dashicons-plus"></span><span>Add keyword</span>
                                        <div class="dn edit-form-items">
                                            <input type="hidden" name="item_name" value="" data-label="Name">
                                        </div>
                                    </button>
                                </div>
                                <ul id="seoaic-idea-keywords" class="seoaic-idea-content-section-keyword"></ul>
                            </div>
                            <div id="seoaic-idea-content-description" class="seoaic-idea-content-section">
                                <div class="top">
                                    <h3 class="seoaic-section-idea-title">Description</h3>
                                </div>
                                <textarea type="text"
                                          class="seoaic-idea-content-description-textarea seoaic-on-update-save-content"
                                          name="seoaic-idea-content-description" rows="6"></textarea>

                            </div>

                            <div id="seoaic-idea-post-type" class="seoaic-idea-content-section">
                                <div class="top">
                                    <span class="seoaic-section-idea-title">Post type</span><span class="icon"></span>
                                </div>
                                <div class="choose-label mb-19">Select a post type</div>
                                <div class="choose-switchers">
                                    <select id="seoaic-post-type" data-action="seoaic_selectCategoriesIdea" class="seoaic-form-item form-select" name="seoaic_post_type" required="" default-value="<?=!empty($SEOAIC_OPTIONS['seoaic_post_type']) ? $SEOAIC_OPTIONS['seoaic_post_type'] : 'post';?>" data-target-select="seoaic-idea-content-category">
                                    <?php
                                        $post_types = seoaic_get_post_types();
                                        foreach ($post_types as $post_type) :
                                            ?>
                                            <option value="<?= $post_type ?>"><?= ucfirst($post_type) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div id="seoaic-idea-content-category" class="seoaic-idea-content-section">
                                <div class="top">
                                    <span class="seoaic-section-idea-title">Category</span><span class="icon"></span>
                                </div>
                                <div class="choose-label mb-19">Select a category</div>

                                <div class="choose-switchers terms-select"></div>

                            </div>

<!--                            <div id="seoaic-idea-content-generator" class="seoaic-idea-content-section">-->
<!--                                <div class="top">-->
<!--                                    <span class="seoaic-section-idea-title">Image generator</span><span-->
<!--                                            class="icon"></span>-->
<!--                                </div>-->
<!--                                <div class="choose-label mb-19">Select a service for generating pictures</div>-->
<!--                                <div class="choose-switchers">-->
<!--                                    <select id="seoaic-image-generator" class="seoaic-form-item form-select"-->
<!--                                            name="seoaic_image_generator" required=""-->
<!--                                            default-value="--><?php //= !empty($SEOAIC_OPTIONS['seoaic_image_generator']) ? $SEOAIC_OPTIONS['seoaic_image_generator'] : 'no_image'; ?><!--">-->
<!--                                        --><?php
//                                        $image_generators = seoaic_get_image_generators();
//                                        foreach ($image_generators as $key => $image_generator) :
//                                            ?>
<!--                                            <option value="--><?php //= $key; ?><!--">--><?php //= $image_generator ?><!--</option>-->
<!--                                        --><?php //endforeach; ?>
<!--                                    </select>-->
<!--                                </div>-->
<!--                            </div>-->

<!--                            <div id="seoaic-idea-content-thumbnail" class="seoaic-idea-content-section">-->
<!--                                <div class="top">-->
<!--                                    <span class="seoaic-section-idea-title">Image description</span>-->
<!--                                </div>-->
<!--                                <textarea type="text"-->
<!--                                          class="seoaic-idea-content-thumbnail-textarea seoaic-on-update-save-content"-->
<!--                                          name="seoaic-idea-content-thumbnail" rows="6"></textarea>-->
<!--                            </div>-->
                            <div class="seoaic-save-content-idea seoaic-idea-content-section">
                                <div class="top last">
                                    <h3 class="seoaic-section-idea-title">Posting time</h3>
                                </div>
                                <div class="bottom">
                                    <div class="idea-date-picker">
                                        <input type="text"
                                               class="seoaic-posting-idea-date"
                                               name="seoaic-posting-idea-date"
                                               placeholder="dd/mm/yyyy --:--"
                                               readonly>
                                        <div class="picker-call"></div>
                                    </div>
                                    <button type="button" class="seoaic-cancel-content-idea-button ml-auto">
                                        Cancel
                                    </button>
                                    <button title="Save idea" data-post-id="" type="button"
                                            class="button-primary seoaic-button-primary seoaic-save-content-idea-button"
                                            data-action="seoaic_save_content_idea"
                                            data-callback="skeleton_saved"
                                            data-changed="false"
                                    >Save idea
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="lds-dual-ring"></div>


    </div>
</div>