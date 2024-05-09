<?php

use SEOAIC\loaders\PostsEditLoader;
use SEOAIC\loaders\PostsReviewLoader;
use SEOAIC\posts_mass_actions\PostsMassEdit;
use SEOAIC\posts_mass_actions\PostsMassReview;
use SEOAIC\posts_mass_actions\PostsMassTranslate;
use SEOAIC\SEOAIC;
use SEOAIC\SEOAIC_POSTS;

if ( ! current_user_can( 'seoaic_edit_plugin' ) ) {
    return;
}

if ( isset( $_GET['settings-updated'] ) ) {
    add_settings_error( 'seoaic_messages', 'seoaic_message', __( 'Settings Saved', 'mdsf' ), 'updated' );
}

settings_errors( 'seoaic_messages' );

global $SEOAIC, $SEOAIC_OPTIONS;

function SEOAIC_isValidDate($dateString='') {
    return (bool)strtotime($dateString);
}

function SEOAIC_makeDateQuery() {
    $date_query = [];

    if (
        !empty($_GET['seoaic_publ_datefrom'])
        && SEOAIC_isValidDate(urldecode($_GET['seoaic_publ_datefrom']))
    ) {
        $date_query['after'] = urldecode($_GET['seoaic_publ_datefrom']);
    }
    if (
        !empty($_GET['seoaic_publ_dateto'])
        && SEOAIC_isValidDate(urldecode($_GET['seoaic_publ_dateto']))
    ) {
        $date_query['before'] = urldecode($_GET['seoaic_publ_dateto']) . ' 23:59:59';
    }
    if (!empty($date_query)) {
        $date_query['inclusive'] = true;
    }

    return [$date_query];
}

function SEOAIC_addTitleQuery(&$args) {
    if (!empty($_GET['seoaic_title'])) {
        $args['post_title_like'] = $_GET['seoaic_title'];
    }
}

function SEOAIC_addWordsRangeQuery(&$args) {
    $min = null;
    $max = null;

    $is_valid = function($value) {
        return !empty($value)
            && is_numeric($value)
            && intval($value)  == $value;
    };

    $min = isset($_GET['seoaic_words_min']) && $is_valid($_GET['seoaic_words_min']) ? $_GET['seoaic_words_min'] : null;
    $max = isset($_GET['seoaic_words_max']) && $is_valid($_GET['seoaic_words_max']) ? $_GET['seoaic_words_max'] : null;

    if (empty($args['meta_query'])) {
        $args['meta_query'] = [
            'relation' => 'AND',
        ];
    }

    if (!empty($min)) {
        $args['meta_query'][] = [
            'key' => SEOAIC_POSTS::WORDS_COUNT_FIELD,
            'value' => $min,
            'compare' => '>=',
            'type' => 'NUMERIC',
        ];
    }
    if (!empty($max)) {
        $args['meta_query'][] = [
            'key' => SEOAIC_POSTS::WORDS_COUNT_FIELD,
            'value' => $max,
            'compare' => '<=',
            'type' => 'NUMERIC',
        ];
    }
}

function SEOAIC_addCreatedDateQuery(&$args) {
    $after = null;
    $before = null;

    if (
        !empty($_GET['seoaic_create_datefrom'])
        && SEOAIC_isValidDate(urldecode($_GET['seoaic_create_datefrom']))
    ) {
        $after = strtotime(urldecode($_GET['seoaic_create_datefrom']));
    }
    if (
        !empty($_GET['seoaic_create_dateto'])
        && SEOAIC_isValidDate(urldecode($_GET['seoaic_create_dateto']))
    ) {
        $before = strtotime(urldecode($_GET['seoaic_create_dateto']) . ' 23:59:59');
    }


    if (empty($args['meta_query'])) {
        $args['meta_query'] = [
            'relation' => 'AND',
        ];
    }

    if (!empty($after)) {
        $args['meta_query'][] = [
            'key' => 'post_created_date',
            'value' => $after,
            'compare' => '>=',
        ];
    }
    if (!empty($before)) {
        $args['meta_query'][] = [
            'key' => 'post_created_date',
            'value' => $before,
            'compare' => '<=',
        ];
    }
}

function SEOAIC_countPostsMap($status = '') {
    if ('all' == $status) {
        $args = array(
            'posts_per_page'    => -1,
            'post_type'         => 'any',
            'meta_key'          => 'seoaic_posted',
            'meta_value'        => '1',
            'date_query'        => SEOAIC_makeDateQuery(),
        );

    } else if (0 === strpos($status, 'edit:')) {
        $status_substr = str_replace('edit:', '', $status);
        $args = array(
            'posts_per_page'    => -1,
            'post_type'         => 'any',
            'date_query'        => SEOAIC_makeDateQuery(),
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'seoaic_posted',
                    'value' => '1',
                    'compare' => '=',
                ],
                [
                    'key' => 'seoaic_update_status',
                    'value' => $status_substr,
                    'compare' => '=',
                ],
            ],
        );

    } else if (0 === strpos($status, 'review:')) {
        $status_substr = explode(':', str_replace('review:', '', $status));
        $args = array(
            'posts_per_page'    => -1,
            'post_type'         => 'any',
            'date_query'        => SEOAIC_makeDateQuery(),
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'seoaic_posted',
                    'value' => '1',
                    'compare' => '=',
                ],
                [
                    'key' => 'seoaic_review_status',
                    'value' => $status_substr[0],
                    'compare' => '=',
                ],
            ],
        );
        if (!empty($status_substr[1])) {
            $args['meta_query'][] = [
                'key' => 'seoaic_review_result',
                'value' => $status_substr[1],
                'compare' => '=',
            ];
        }
    }
    SEOAIC_addTitleQuery($args);
    SEOAIC_addWordsRangeQuery($args);
    SEOAIC_addCreatedDateQuery($args);

    $args['cache_results'] = false;
    $args['post_status'] = 'any';

    $posts_query = new WP_Query($args);
    return $posts_query->post_count;
}

function SEOAIC_countPosts($statuses) {
    return array_combine($statuses, array_map('SEOAIC_countPostsMap', $statuses));
}

function SEOAIC_postsPagination($query) {
    ?>
    <div class="seoaic-pagination pagination">
        <?php
        $big = 999999999;
        $paged = !empty($_GET['paged']) ? $_GET['paged'] : 1;
        echo paginate_links([
            'base'         => str_replace([$big, '&#038;'], ['%#%', '&'], get_pagenum_link($big)),
            'total'        => $query->max_num_pages,
            // 'current'      => max( 1, get_query_var( 'paged' ) ),
            'current'      => max(1, $paged),
            'format'       => '?paged=%#%',
            'show_all'     => false,
            'type'         => 'plain',
            'end_size'     => 2,
            'mid_size'     => 1,
            'prev_next'    => true,
            'prev_text'    => sprintf('<i></i> %1$s', __('Prev', 'seoaic')),
            'next_text'    => sprintf('%1$s <i></i>', __('Next', 'seoaic')),
            'add_args'     => false,
            'add_fragment' => '',
        ]);
        ?>
    </div>
    <?php
}

$editInProgress = (new PostsMassEdit($SEOAIC))->isRunning();
$reviewInProgress = (new PostsMassReview($SEOAIC))->isRunning();
$translateInProgress = (new PostsMassTranslate($SEOAIC))->isRunning();

$edit_status = '';
$review_status = '';

if (!empty($_GET['edit_status'])) {
    switch ($_GET['edit_status']) {
        case 'pending':
        case 'completed':
        case 'failed':
            $edit_status = $_GET['edit_status'];
            break;
    }
}

if (!empty($_GET['review_status'])) {
    switch ($_GET['review_status']) {
        case 'reviewing':
        case 'completed:yes':
        case 'completed:no':
        case 'completed:unknown':
        case 'failed':
            $review_status = $_GET['review_status'];
            break;
    }
}

$edit_is_pending = 'pending' == $edit_status;
$edit_is_completed = 'completed' == $edit_status;
$edit_is_failed = 'failed' == $edit_status;

$review_is_reviewing = 'reviewing' == $review_status;
$review_is_completed_yes = 'completed:yes' == $review_status;
$review_is_completed_no = 'completed:no' == $review_status;
$review_is_completed_unknown = 'completed:unknown' == $review_status;
$review_is_failed = 'failed' == $review_status;


$statuses = ['all', 'edit:pending', 'edit:completed', 'edit:failed', 'review:completed:yes', 'review:completed:no', 'review:completed:unknown', 'review:reviewing', 'review:failed'];
$counts_by_status = SEOAIC_countPosts($statuses);

$available_per_page_options = [10, 20, 50, 100, 200, 500, 1000];
$selected_per_page = !empty($_GET['per_page']) && is_numeric($_GET['per_page']) && in_array($_GET['per_page'], $available_per_page_options) ? intval($_GET['per_page']) : 10;
$paged = !empty($_GET['paged']) && is_numeric($_GET['paged']) ? intval($_GET['paged']) : 1;

// main query
$args = [
    'posts_per_page'    => $selected_per_page,
    'paged'             => $paged,
    'post_type'         => 'any',
    'date_query'        => SEOAIC_makeDateQuery(),
    'meta_query'        => [
        'relation'      => 'AND',
        [
            'key'       => 'seoaic_posted',
            'value'     => '1',
            'compare'   => '=',
        ],
    ],
];

if (!empty($edit_status)) {
    $args['meta_query'][] = [
        [
            'key' => 'seoaic_update_status',
            'value' => $edit_status,
            'compare' => '=',
        ],
    ];

} elseif (!empty($review_status)) {
    $review_status_arr = explode(':', $review_status);
    $args['meta_query'][] = [
        [
            'key' => 'seoaic_review_status',
            'value' => $review_status_arr[0],
            'compare' => '=',
        ],
    ];
    if (!empty($review_status_arr[1])) {
        $args['meta_query'][] = [
            'key' => 'seoaic_review_result',
            'value' => $review_status_arr[1],
            'compare' => '=',
        ];
    }
}
SEOAIC_addTitleQuery($args);
SEOAIC_addWordsRangeQuery($args);
SEOAIC_addCreatedDateQuery($args);

$args['cache_results'] = false;
$args['post_status'] = 'any';

$query = new WP_Query($args);
$publishedDateFromValue = !empty($_GET['seoaic_publ_datefrom']) && SEOAIC_isValidDate(urldecode($_GET['seoaic_publ_datefrom'])) ? urldecode($_GET['seoaic_publ_datefrom']) : '';
$publishedDateToValue = !empty($_GET['seoaic_publ_dateto']) && SEOAIC_isValidDate(urldecode($_GET['seoaic_publ_dateto'])) ? urldecode($_GET['seoaic_publ_dateto']) : '';
$createdDateFromValue = !empty($_GET['seoaic_create_datefrom']) && SEOAIC_isValidDate(urldecode($_GET['seoaic_create_datefrom'])) ? urldecode($_GET['seoaic_create_datefrom']) : '';
$createdDateToValue = !empty($_GET['seoaic_create_dateto']) && SEOAIC_isValidDate(urldecode($_GET['seoaic_create_dateto'])) ? urldecode($_GET['seoaic_create_dateto']) : '';

$maxWordsCount = SEOAIC_POSTS::getMaxWordsCount();
$maxWordsCountCeil = ceil($maxWordsCount / 10) * 10;
$countMinDefault = 0;
$countMaxDefault = $maxWordsCount;
$countMin = isset($_GET['seoaic_words_min']) ? $_GET['seoaic_words_min'] : $countMinDefault;
$countMax = isset($_GET['seoaic_words_max']) ? $_GET['seoaic_words_max'] : $countMaxDefault;

$adminUrl = SEOAIC::getAdminUrl('admin.php');
$isMultilang = (new SEOAIC_MULTILANG(new SEOAIC()))->is_multilang();
?>
<div id="seoaic-admin-container" class="wrap">
    <h1 id="seoaic-admin-title">
        <?php echo seoai_get_logo( 'logo.svg' ); ?>
        <span><?php echo esc_html( get_admin_page_title() ); ?></span>
    </h1>
    <?php echo $SEOAIC->get_background_process_loader();?>
    <div id="seoaic-admin-body" class="seoaic-with-loader posts-page">
        <div class="seoaic-posts-table full-width">
            <div class="header seoaic-flip-box">
                <div class="seoaic-flip-container">
                    <div class="seoaic-flip-item seoaic-flip-front">
                        <div class="head-buttons">
                            <div class="w-100 max-w-50 d-flex gap-15">
                                <button type="button"
                                        title="Stops posts mass edit proccess"
                                        class="button-primary seoaic-button-primary outline modal-button mass-effect-button confirm-modal-button max-w-50 <?php echo $editInProgress ? '' : 'd-none';?>"
                                        data-modal="#seoaic-confirm-modal"
                                        data-action="seoaic_posts_mass_stop_edit"
                                        data-form-callback="window_reload"
                                        data-content="Do you really want to stop posts editing?"
                                >
                                    Stop posts editing
                                </button>
                                <button type="button"
                                        title="Stops posts mass review proccess"
                                        class="button-primary seoaic-button-primary outline modal-button mass-effect-button confirm-modal-button max-w-50 <?php echo $reviewInProgress ? '' : 'd-none';?>"
                                        data-modal="#seoaic-confirm-modal"
                                        data-action="seoaic_posts_mass_stop_review"
                                        data-form-callback="window_reload"
                                        data-content="Do you really want to stop posts review?"
                                >
                                    Stop posts review
                                </button>
                            </div>
                            <div class="w-100 max-w-50">
                                <div class="posts-per-page">
                                    <label class="text-label">Posts per page</label>
                                    <select id="posts_per_page" class="form-select pp-select">
                                        <?php
                                        foreach ($available_per_page_options as $option_value) {
                                            $selected_attr = $selected_per_page == $option_value ? ' selected="selected"' : '';
                                            ?>
                                            <option value="<?php echo esc_attr(strval($option_value))?>" <?php echo $selected_attr;?>><?php echo esc_html(strval($option_value));?></option>
                                            <?php
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div>
                            <form action="" class="posts-search-form mt-20">
                                <div class="f-text">
                                    <span class="filters-label">Filter posts:</span>
                                </div>
                                <div class="f-title">
                                    <label class="text-label">Post title keywords</label>
                                    <input type="text" name="filter_post_title" class="form-input" value="<?php echo !empty($_GET['seoaic_title']) ? esc_attr(urldecode($_GET['seoaic_title'])) : '';?>">
                                </div>
                                <div class="f-words-count seoaic-settings-range seoaic-settings-range-2 mt-0 mb-0" data-min="0" data-max="<?php echo $maxWordsCountCeil;?>" data-step="10">
                                    <label>Content words range:
                                        <span class="range-min"><?php echo $countMin;?></span> -
                                        <span class="range-max"><?php echo $countMax;?></span>
                                        <input id="seoaic_words_range_min" class="seoaic-settings-range-min seoaic-form-item" type="hidden" name="words_range_min" value="<?php echo $countMin;?>">
                                        <input id="seoaic_words_range_max" class="seoaic-settings-range-max seoaic-form-item" type="hidden" name="words_range_max" value="<?php echo $countMax;?>">
                                    </label>
                                    <div id="seoiac-modal-subtitles-range-slider" class="seoaic-settings-range-slider"></div>
                                </div>
                                <div class="f-date">
                                    <label class="text-label">Published / scheduled</label>
                                    <div class="position-relative">
                                        <input type="text" name="filter_post_date_from"
                                            value="<?php echo esc_attr($publishedDateFromValue);?>"
                                            class="mt-0 seoaic-form-item form-input"
                                            placeholder="Published from"
                                        >
                                        <div class="picker-call"></div>
                                    </div>
                                </div>
                                <div class="f-date">
                                    <label class="text-label"></label>
                                    <div class="position-relative">
                                        <input type="text" name="filter_post_date_to"
                                            value="<?php echo esc_attr($publishedDateToValue);?>"
                                            class="mt-0 seoaic-form-item form-input"
                                            placeholder="Published to"
                                        >
                                        <div class="picker-call"></div>
                                    </div>
                                </div>
                                <div class="f-date">
                                    <label class="text-label">Created date</label>
                                    <div class="position-relative">
                                        <input type="text" name="filter_post_created_date_from"
                                            value="<?php echo esc_attr($createdDateFromValue);?>"
                                            class="mt-0 seoaic-form-item form-input"
                                            placeholder="Created from"
                                        >
                                        <div class="picker-call"></div>
                                    </div>
                                </div>
                                <div class="f-date">
                                    <label class="text-label"></label>
                                    <div class="position-relative">
                                        <input type="text" name="filter_post_created_date_to"
                                            value="<?php echo esc_attr($createdDateToValue);?>"
                                            class="mt-0 seoaic-form-item form-input"
                                            placeholder="Created to"
                                        >
                                        <div class="picker-call"></div>
                                    </div>
                                </div>
                                <div class="f-btn position-relative">
                                    <button type="submit"
                                        class="filter-search-btn"
                                        title="Filter"
                                    ></button>
                                </div>
                                <div class="f-btn position-relative">
                                    <a href="<?php echo esc_attr($adminUrl);?>?page=seoaic-created-posts" class="filter-clear-btn"
                                    title="Clear all filters"></a>
                                </div>
                            </form>
                        </div>

                        <div class="filters-switcher mt-15">
                            <div class="filter-rows">
                                <div class="filter-row">
                                    <?php
                                    $__get = $_GET;
                                    unset($__get['review_status']);
                                    ?>
                                    <label class="mr-15 mb-0">Mass edit status:</label>
                                    <a href="<?php echo esc_attr($adminUrl);?>?<?php echo http_build_query(array_merge($__get, array('edit_status'=>'completed')));?>" title="Posts that were updated" class="filter-btn <?php echo $edit_is_completed ? 'active' : '';?>">Completed: <?php echo esc_html($counts_by_status['edit:completed']);?></a>
                                    <a href="<?php echo esc_attr($adminUrl);?>?<?php echo http_build_query(array_merge($__get, array('edit_status'=>'pending')));?>" title="Posts that are still queued for updating" class="filter-btn <?php echo $edit_is_pending ? 'active' : '';?>">Pending: <?php echo esc_html($counts_by_status['edit:pending']);?></a>
                                    <a href="<?php echo esc_attr($adminUrl);?>?<?php echo http_build_query(array_merge($__get, array('edit_status'=>'failed')));?>" title="Posts which processing was failed" class="filter-btn <?php echo $edit_is_failed ? 'active' : '';?>">Failed: <?php echo esc_html($counts_by_status['edit:failed']);?></a>
                                </div>
                                <div class="filter-row">
                                    <?php
                                    $__get = $_GET;
                                    unset($__get['edit_status']);
                                    ?>
                                    <label class="mr-15 mb-0">Mass review status:</label>
                                    <a href="<?php echo esc_attr($adminUrl);?>?<?php echo http_build_query(array_merge($__get, array('review_status'=>'completed:yes')));?>" title="Posts that meet the review prompt condition" class="filter-btn <?php echo $review_is_completed_yes ? 'active' : '';?>">Yes: <?php echo esc_html($counts_by_status['review:completed:yes']);?></a>
                                    <a href="<?php echo esc_attr($adminUrl);?>?<?php echo http_build_query(array_merge($__get, array('review_status'=>'completed:no')));?>" title="Posts that do not meet the review prompt condition" class="filter-btn <?php echo $review_is_completed_no ? 'active' : '';?>">No: <?php echo esc_html($counts_by_status['review:completed:no']);?></a>
                                    <a href="<?php echo esc_attr($adminUrl);?>?<?php echo http_build_query(array_merge($__get, array('review_status'=>'completed:unknown')));?>" title="Posts that needed to be checked manually" class="filter-btn <?php echo $review_is_completed_unknown ? 'active' : '';?>">Requires attention: <?php echo esc_html($counts_by_status['review:completed:unknown']);?></a>
                                    <a href="<?php echo esc_attr($adminUrl);?>?<?php echo http_build_query(array_merge($__get, array('review_status'=>'reviewing')));?>" title="Posts that needed to be checked manually" class="filter-btn <?php echo $review_is_reviewing ? 'active' : '';?>">Reviewing: <?php echo esc_html($counts_by_status['review:reviewing']);?></a>
                                    <a href="<?php echo esc_attr($adminUrl);?>?<?php echo http_build_query(array_merge($__get, array('review_status'=>'failed')));?>" title="Posts that needed to be checked manually" class="filter-btn <?php echo $review_is_failed ? 'active' : '';?>">Failed: <?php echo esc_html($counts_by_status['review:failed']);?></a>
                                </div>
                                <div class="filter-row">
                                    <div class="checkbox-wrapper-mc mr-15 ">
                                        <input id="posts_mass_edit_all" type="checkbox" class="posts-mass-edit" name="posts_mass_edit_all" value="all">
                                        <label for="posts_mass_edit_all" class="check" title="Check all posts on current page">
                                            <div class="checkbox-wrapper-svg">
                                                <svg width="18px" height="18px" viewBox="0 0 18 18">
                                                    <path d="M1,9 L1,3.5 C1,2 2,1 3.5,1 L14.5,1 C16,1 17,2 17,3.5 L17,14.5 C17,16 16,17 14.5,17 L3.5,17 C2,17 1,16 1,14.5 L1,9 Z"></path>
                                                    <polyline points="1 9 7 14 15 4"></polyline>
                                                </svg>
                                            </div>
                                        </label>
                                    </div>
                                    <?php
                                    $__get = $_GET;
                                    unset($__get['edit_status']);
                                    unset($__get['review_status']);
                                    ?>
                                    <a href="<?php echo esc_attr($adminUrl);?>?<?php echo http_build_query($__get);?>" title="Show all posts" class="filter-btn <?php echo empty($edit_status) && empty($review_status) ? 'active' : '';?>">All posts: <?php echo esc_html($counts_by_status['all']);?></a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="seoaic-flip-item seoaic-flip-side">
                        <div class="head-buttons">
                            <button type="button"
                                    title="Mass content edit"
                                    class="button-primary seoaic-button-primary mass-effect-button seoaic-edit-posts-button modal-button confirm-modal-button position-relative max-w-25"
                                    data-modal="#seoaic_post_mass_edit_modal"
                                    data-action="seoaic_posts_mass_edit"
                                    data-title="Posts mass content edit"
                                    data-form-callback="window_reload"
                                    data-content="You will edit the following posts:"
                            >
                                Mass content edit
                                <div class="info">
                                    <span class="info-btn">?</span>
                                    <div class="info-content">
                                        <h4>Mass Content Edit</h4>
                                        <p>You can edit multiple posts at once.</p>
                                    </div>
                                </div>
                                <div class="dn additional-form-items"></div>
                            </button>

                            <button type="button"
                                    title="Mass content review"
                                    class="button-primary seoaic-button-primary mass-effect-button seoaic-review-posts-button modal-button confirm-modal-button position-relative max-w-25"
                                    data-modal="#seoaic_post_mass_review_modal"
                                    data-action="seoaic_posts_mass_review"
                                    data-title="Posts mass content review"
                                    data-form-callback="window_reload"
                                    data-content="The content of the following posts will be reviewed:"
                            >
                                Mass content review
                                <div class="info">
                                    <span class="info-btn">?</span>
                                    <div class="info-content">
                                        <h4>Mass Content Review</h4>
                                        <p>You can make a review for multiple posts at once.</p>
                                    </div>
                                </div>
                                <div class="dn additional-form-items"></div>
                            </button>

                            <?php
                            if ($isMultilang) {
                                ?>
                                <button type="button"
                                        title="Mass content translate"
                                        class="button-primary seoaic-button-primary mass-effect-button seoaic-translate-posts-button modal-button confirm-modal-button position-relative max-w-25"
                                        data-modal="#seoaic_post_mass_translate_modal"
                                        data-action="seoaic_posts_mass_translate"
                                        data-title="Posts mass content translate"
                                        data-form-callback="window_reload"
                                        data-content="The content of the following posts will be translated:"
                                >
                                    Mass content translate
                                    <div class="info">
                                        <span class="info-btn">?</span>
                                        <div class="info-content">
                                            <h4>Mass Content Translate</h4>
                                            <p>You can make a translate for multiple posts at once.</p>
                                        </div>
                                    </div>
                                    <div class="dn additional-form-items"></div>
                                </button>
                                <?php
                            }
                            ?>
                        </div>
                        <div class="filters-switcher mt-15">
                            <div class="filter-rows">
                                <div class="filter-row">
                                    <span class="seoaic-checked-amount">Selected: <span class="seoaic-checked-amount-num">0</span></span>
                                    <button type="button" class="posts-mass-edit-uncheck-all filter-btn">Remove selection</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            if ($query->have_posts()) {
                if ($editInProgress) {
                    ?>
                    <input type="hidden" name="mass_edit_in_progress" id="mass_edit_in_progress" value="1"
                        data-action="seoaic_posts_mass_edit_check_status"
                        data-loader="#<?php echo (new PostsEditLoader)->getID();?>"
                        data-modal="#seoaic-confirm-modal"
                        data-form-callback="window_reload"
                    >
                    <?php

                } elseif ($reviewInProgress) {
                    ?>
                    <input type="hidden" name="mass_review_in_progress" id="mass_review_in_progress" value="1"
                        data-action="seoaic_posts_mass_review_check_status"
                        data-loader="#<?php echo (new PostsReviewLoader)->getID();?>"
                        data-modal="#seoaic-confirm-modal"
                        data-form-callback="window_reload"
                    >
                    <?php

                } else if ($translateInProgress) {
                    ?>
                    <input type="hidden" name="mass_translate_in_progress" id="mass_translate_in_progress" value="1"
                        data-action="seoaic_posts_mass_translate_check_status"
                    >
                    <?php
                }
                SEOAIC_postsPagination($query);
                ?>
                <div class="seoaic-posts-table__container">
                    <div class="seoaic-posts-table__row seoaic-posts-table__row--head">
                        <div class="seoaic-posts-table__row-item"><?php _e('ID', 'seoaic');?></div>
                        <div class="seoaic-posts-table__row-item"><?php _e('Image', 'seoaic');?></div>
                        <div class="seoaic-posts-table__row-item"><?php _e('Title', 'seoaic');?></div>
                        <div class="seoaic-posts-table__row-item"><?php _e('Status', 'seoaic');?></div>
                        <div class="seoaic-posts-table__row-item"><?php _e('Mass Edit Status', 'seoaic');?></div>
                        <div class="seoaic-posts-table__row-item"><?php _e('Review Results', 'seoaic');?></div>
                        <div class="seoaic-posts-table__row-action-item seoaic-posts-table__row-action-item--head"></div>
                    </div>
                    <?php
                    while ($query->have_posts()) {
                        $query->the_post();

                        $status = get_post_status();
                        $status_label = 'publish' == $status ? __('Published', 'seoaic') : ucfirst($status);
                        $id = get_the_ID();
                        $for_id = 'posts_mass_edit_' . $id;
                        $update_status = get_post_meta($id, 'seoaic_update_status', true);
                        $update_status_time = get_post_meta($id, 'seoaic_update_status_time', true);
                        $review_status = get_post_meta($id, 'seoaic_review_status', true);
                        $review_time = get_post_meta($id, 'seoaic_review_time', true);
                        $review_prompt = get_post_meta($id, 'seoaic_review_prompt', true);
                        $review_result_original = get_post_meta($id, 'seoaic_review_result_original', true);
                        $review_result = get_post_meta($id, 'seoaic_review_result', true);
                        $post_created_date = get_post_meta($id, 'post_created_date', true);

                        $is_pending = 'pending' == $update_status;
                        $is_reviewing = 'reviewing' == $review_status;
                        $is_reviewed = 'completed' == $review_status;
                        ?>
                        <div class="seoaic-posts-table__row post<?php echo ($is_pending || $is_reviewing) ? ' updating' : '';?>" id="seoaic_post_<?php echo $id;?>">
                            <div class="seoaic-posts-table__row-item position-relative col-id">
                                <div>
                                    <?php
                                    if (
                                        !$is_pending
                                        && !$is_reviewing
                                    ) {
                                        ?>
                                        <div class="checkbox-wrapper-mc">
                                            <input type="checkbox" name="posts_mass_edit" id="<?php echo $for_id;?>" class="post-mass-edit" value="<?php echo $id;?>">
                                            <label for="<?php echo $for_id;?>" class="check">
                                                <div class="checkbox-wrapper-svg">
                                                    <svg width="18px" height="18px" viewBox="0 0 18 18">
                                                        <path d="M1,9 L1,3.5 C1,2 2,1 3.5,1 L14.5,1 C16,1 17,2 17,3.5 L17,14.5 C17,16 16,17 14.5,17 L3.5,17 C2,17 1,16 1,14.5 L1,9 Z"></path>
                                                        <polyline points="1 9 7 14 15 4"></polyline>
                                                    </svg>
                                                </div>
                                                <span id="<?php echo $for_id;?>"><?php echo $id;?></span>
                                            </label>
                                        </div>
                                        <?php
                                    } else {
                                        ?>
                                        <span><?php echo $id;?></span>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="seoaic-posts-table__row-item"><?php echo get_the_post_thumbnail($id, 'full', ['class' => 'seoaic-post-image']);?></div>
                            <div class="seoaic-posts-table__row-item">
                                <div>
                                    <div class="seoaic-post-datetime">Published / scheduled: <?php echo get_post_datetime()->format('F j, Y, h:i a');?></div>
                                    <?php
                                    if ($post_created_date) {
                                        $formattedDate = wp_date('F j, Y, h:i a', intval($post_created_date));
                                        ?>
                                        <div class="seoaic-post-datetime">Created: <?php echo $formattedDate;?></div>
                                        <?php
                                    }
                                    ?>
                                    <div class="seoaic-post-title"><?php echo esc_html(get_the_title());?></div>
                                    <div class="seoaic-post-langs-wrapper mt-10">
                                        <?php
                                        $SEOAIC->multilang->displayPostLanguageFlag($id);
                                        $SEOAIC->multilang->displayPostTranslationsFlags($id);
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <div class="seoaic-posts-table__row-item">
                                <div>
                                    <span class="post-status post-status--<?php echo $status; ?>"><?php echo $status_label; ?></span>
                                </div>
                            </div>
                            <div class="seoaic-posts-table__row-item">
                                <div>
                                    <?php
                                    if (!empty($update_status)) {
                                        ?>
                                        <div class="tc">
                                            <span class="update-status update-status-<?php echo esc_attr($update_status);?>"><?php _e($update_status, 'seoaic');?></span>
                                            <?php
                                            if (!empty($update_status_time)) {
                                                ?>
                                                </br><span><?php echo wp_date('M j, Y', $update_status_time) . '<br>' . wp_date('h:i a', $update_status_time);?></span>
                                                <?php
                                            }
                                            ?>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="seoaic-posts-table__row-item">
                                <?php
                                if ($is_reviewed) {
                                    $hover_title = '';
                                    if (
                                        'yes' == $review_result
                                        || 'no' == $review_result
                                    ) {
                                        $hover_title = 'Shows if the post\'s content meets the propmt condition.';
                                    } else {
                                        $hover_title = 'Need manually check the result of review.';
                                    }
                                    $review_label_id = 'review_details_'.$id;
                                    ?>
                                    <div class="review-result w-100">
                                        <div class="tc mb-10 review-result-text review-result-<?php echo esc_attr($review_result);?>" title="<?php echo esc_attr($hover_title);?>">
                                            <?php echo esc_html($review_result);?>
                                        </div>
                                        <div class="review-date tc mb-10">
                                            <?php echo wp_date('M j, Y', $review_time) . '<br>' . wp_date('h:i a', $review_time);?>
                                        </div>
                                        <input type="checkbox" class="d-none" id="<?php echo esc_attr($review_label_id);?>">
                                        <div class="review-details-wrapper tc">
                                            <div class="w-100">
                                                <div class="review-details w-100">
                                                    <b>Prompt:</b><br><?php echo esc_html($review_prompt);?>
                                                    <br><b>Result:</b><br><?php echo esc_html($review_result_original);?>
                                                </div>
                                            </div>
                                        </div>
                                        <label class="w-100 tc" for="<?php echo esc_attr($review_label_id);?>"></label>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                            <div class="seoaic-posts-table__row-action-item">
                                <?php
                                $status_text = '';
                                if ($is_pending) {
                                    $status_text = $update_status;
                                } elseif ($is_reviewing) {
                                    $status_text = $review_status;
                                }

                                if (!empty($status_text)) {
                                    ?>
                                    <span class="post-updating-label"><?php echo esc_html($status_text);?></span>
                                    <?php
                                } else {
                                    ?>
                                    <a title="<?php _e('Edit post', 'seoaic'); ?>" target="_blank" href="<?php echo get_edit_post_link();?>">
                                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                                    </a>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                <?php
                SEOAIC_postsPagination($query);
                wp_reset_postdata();
            } else {
                ?>
                <p>No posts at the moment.</p>
                <?php
            }
            ?>
        </div>
        <div class="lds-dual-ring"></div>
    </div>
</div>