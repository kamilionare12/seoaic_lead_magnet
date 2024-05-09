<?php

namespace SEOAIC;

use Exception;
use SEOAIC\loaders\PostsEditLoader;
use SEOAIC\loaders\PostsGenerationLoader;
use SEOAIC\loaders\PostsReviewLoader;
use SEOAIC\posts_mass_actions\PostsMassEdit;
use SEOAIC\posts_mass_actions\PostsMassReview;
use SEOAIC\posts_mass_actions\PostsMassTranslate;
use SEOAIC\relations\KeywordsPostsRelation;
use SEOAIC\SEOAIC;
use SEOAIC\SEOAICAjaxResponse;
use SEOAIC\SEOAIC_IDEAS;
use WP_Query;

class SEOAIC_POSTS
{
    public const DEBUG_CLASS = true;
    public const SUBTITLES_MIN = 0;
    public const SUBTITLES_MAX = 6;
    public const WORDS_MIN = 0;
    public const WORDS_MAX = 1000;
    public const PER_REQUEST__UPDATES = 10;
    public const PER_REQUEST__UPDATES_CONTENT = 10;
    public const PER_REQUEST__GENERATE = 10;
    public const PER_REQUEST__GENERATE_CONTENT = 10;
    public const GENERATE_MODE = 'generate';
    public const EDIT_MODE = 'edit';
    // public const REVIEW_MODE = 'review';
    // public const POLL_MODES = [self::GENERATE_MODE, self::EDIT_MODE, self::REVIEW_MODE];
    public const POLL_MODES = [self::GENERATE_MODE, self::EDIT_MODE];
    public const WORDS_COUNT_FIELD = 'words_count';
    public const EDIT_STATUS_FIELD = 'seoaic_update_status';
    public const EDIT_STATUS_TIME_FIELD = 'seoaic_update_status_time';
    // public const REVIEW_STATUS_FIELD = 'seoaic_review_status';
    // public const REVIEW_STATUS_TIME_FIELD = 'seoaic_review_time';

    private $seoaic;

    public function __construct($_seoaic)
    {
        $this->seoaic = $_seoaic;

        add_action('admin_head', [$this, 'seoaic_admin_head'], 10);
        add_action('init', [$this, 'seoaic_register_post_meta']);
        add_action('init', [new PostsMassEdit($this->seoaic), 'init']);
        add_action('init', [new PostsMassReview($this->seoaic), 'init']);
        add_action('init', [new PostsMassTranslate($this->seoaic), 'init']);
        add_action('after_delete_post', [$this, 'removeKeywordPostRelation'], 10, 2);

        add_filter('posts_where', [$this, 'title_like_posts_where'], 10, 2);

        add_action('save_post', [$this, 'resetReviewResults']);
        add_action('save_post', [$this, 'countContentWords'], 10, 2);
        add_action('wp_insert_post', [$this, 'setCreatedDate'], 10, 2);

        // ajax
        add_action('wp_ajax_seoaic_filter_schedule', [$this, 'filter_schedule']);
        add_action('wp_ajax_seoaic_schedule_posts', [$this, 'schedule_posts']);
        add_action('wp_ajax_seoaic_generate_post', [$this, 'background_generation']);
        add_action('wp_ajax_seoaic_regenerate_image', [$this, 'regenerate_image']);
        add_action('wp_ajax_seoaic_publish_post', [$this, 'publish_post']);

        add_action('wp_ajax_seoaic_posts_mass_create', [$this, 'postsMassGenerate']);
        add_action('wp_ajax_seoaic_posts_mass_generate_check_status', [$this, 'postsMassGenerateCheckStatus']);
        add_action('wp_ajax_seoaic_posts_mass_generate_check_status_manually', [$this, 'postsMassGenerateCheckStatusManually']);
        add_action('wp_ajax_seoaic_posts_mass_generate_stop', [$this, 'postsMassGenerateStop']);
        add_action('wp_ajax_seoaic_clear_background_option', [$this, 'clearGenerationBackgroundOption']);

        add_action('wp_ajax_seoaic_posts_mass_edit', [$this, 'postsMassEditAjax']);
        add_action('wp_ajax_seoaic_posts_mass_edit_check_status', [$this, 'postsMassEditCheckStatusAjax']);
        add_action('wp_ajax_seoaic_posts_mass_stop_edit', [$this, 'postsMassEditStopAjax']);
        add_action('wp_ajax_seoaic_clear_edit_background_option', [$this, 'postsMassEditClearBackgroundOption']);

        add_action('wp_ajax_seoaic_posts_mass_review', [$this, 'postsMassReviewAjax']);
        // add_action('wp_ajax_seoaic_posts_mass_review_check_status', [$this, 'postsMassReviewCheckStatus']);
        add_action('wp_ajax_seoaic_posts_mass_review_check_status', [$this, 'postsMassReviewCheckStatusAjax']);
        add_action('wp_ajax_seoaic_posts_mass_stop_review', [$this, 'postsMassReviewStopAjax']);
        add_action('wp_ajax_seoaic_clear_review_background_option', [$this, 'postsMassReviewClearBackgroundOption']);

        add_action('wp_ajax_seoaic_posts_mass_translate', [$this, 'postsMassTranslateAjax']);
        add_action('wp_ajax_seoaic_posts_mass_translate_check_status', [$this, 'postsMassTranslateCheckStatusAjax']);

        add_action('wp_ajax_seoaic_getCategoriesOfPosttype', [$this, 'getCategoriesOfPosttype']);
        add_action('wp_ajax_seoaic_selectCategoriesIdea', [$this, 'selectCategoriesIdea']);

        add_action('wp_ajax_seoaic_transform_idea', [$this, 'transform_idea']);

        add_action('wp_ajax_seoaic_posts_mass_generate_save_prompt_template', [$this, 'massGenerateSavePromptTemplate']);
        add_action('wp_ajax_seoaic_posts_mass_generate_delete_prompt_template', [$this, 'massGenerateDeletePromptTemplate']);

        // cron
        add_filter('cron_schedules', [$this, 'add_cron_interval']);
        add_action('seoaic_posts_generate_check_status_cron_hook', [$this, 'cronPostsGenerateCheckStatus']);
        add_action('seoaic_posts_edit_check_status_cron_hook', [$this, 'cronPostsEditCheckStatus']);
        // add_action('seoaic_posts_review_check_status_cron_hook', [$this, 'cronPostsReviewCheckStatus']);
    }

    /**
     * Writes logs with details like class name and caller function. Uses print_r function for parameters. Keeps the last N lines.
     */
    public function debugLog(...$args)
    {
        if (self::DEBUG_CLASS) {
            $argsString = '';
            $maxLines = 3000;
            $fileName = SEOAIC_LOG . 'mass_posts_debug.txt';

            $argsPrintedArray = array_map(function ($item) {
                return print_r($item, true);
            }, $args);
            $argsString = implode(' -- ', $argsPrintedArray);

            $func_name = debug_backtrace()[1]['function'];
            $str = '[' . wp_date('Y-m-d H:i:s') . '] ' . __CLASS__ . ' -> ' . $func_name . '(): ' . $argsString . "\r\n";
            // file_put_contents($fileName, $str, FILE_APPEND);

            // remove old lines, and write a new one to the end
            if (!file_exists($fileName)) {
                $file = [];
            } else {
                $file = file($fileName);
                $file = array_slice($file, count($file) - $maxLines);
            }
            array_push($file, $str);
            file_put_contents($fileName, implode('', $file));
        }
    }

    public function removeKeywordPostRelation($post_id, $post)
    {
        if (SEOAIC_SETTINGS::getSEOAICPostType() !== $post->post_type) {
            return;
        }

        KeywordsPostsRelation::deleteByPostID($post_id);
    }

    private function unregisterPostsCheckStatusCron($mode = '')
    {
        if (
            !empty($mode)
            && in_array($mode, self::POLL_MODES)
        ) {
            $methodName = 'unregisterPosts' . ucfirst($mode) . 'CheckStatusCron';
            if (method_exists($this, $methodName)) {
                $this->$methodName();
            }
        }
    }

    private function startBackendPolling($mode = '')
    {
        $startPollingMethod = 'start' . ucfirst($mode) . 'Progress';
        if (
            in_array($mode, self::POLL_MODES)
            && method_exists($this, $startPollingMethod)
        ) {
            $this->$startPollingMethod();
        } else {
            $this->debugLog('method not found: ' . $startPollingMethod);
        }

        $cronMethod = 'registerPosts' . ucfirst($mode)  . 'CheckStatusCron';
        if (method_exists($this, $cronMethod)) {
            $this->$cronMethod();
        }
    }

    private function stopBackendPolling($mode = '')
    {
        $stopPollingMethod = 'stop' . ucfirst($mode) . 'Progress';
        if (
            in_array($mode, self::POLL_MODES)
            && method_exists($this, $stopPollingMethod)
        ) {
            $this->$stopPollingMethod();
        }
    }

    public function isProcessingInProgress($mode = '')
    {
        $isInProgressMethod = 'is' . ucfirst($mode) . 'InProgress';
        if (
            in_array($mode, self::POLL_MODES)
            && method_exists($this, $isInProgressMethod)
        ) {
            return $this->$isInProgressMethod();
        }

        return false;
    }


    private function startGenerateProgress()
    {
        update_option('seoaic_mass_content_generate', '1');
    }

    private function stopGenerateProgress()
    {
        update_option('seoaic_mass_content_generate', '0');
        $this->unregisterPostsCheckStatusCron(self::GENERATE_MODE);
    }

    public function isGenerateInProgress()
    {
        return '1' == get_option('seoaic_mass_content_generate');
    }


    // private function startEditProgress()
    // {
    //     update_option('seoaic_mass_content_edit', '1');
    // }

    // private function stopEditProgress()
    // {
    //     update_option('seoaic_mass_content_edit', '0');
    //     $this->unregisterPostsCheckStatusCron(self::EDIT_MODE);
    // }

    // public function isEditInProgress()
    // {
    //     return '1' == get_option('seoaic_mass_content_edit');
    // }


    // private function startReviewProgress()
    // {
    //     update_option('seoaic_mass_content_review', '1');
    // }

    // private function stopReviewProgress()
    // {
    //     update_option('seoaic_mass_content_review', '0');
    //     $this->unregisterPostsCheckStatusCron(self::REVIEW_MODE);
    // }

    // public function isReviewInProgress()
    // {
    //     return '1' == get_option('seoaic_mass_content_review');
    // }



    private function massGenerateCheckingStatusLock()
    {
        $this->debugLog('lock');
        update_option('seoaic_mass_generate_checking_lock', '1');
    }

    private function massGenerateCheckingStatusUnLock()
    {
        $this->debugLog('unlock');
        update_option('seoaic_mass_generate_checking_lock', '0');
    }

    private function isMassGenerateCheckingStatusLocked()
    {
        return '1' == get_option('seoaic_mass_generate_checking_lock');
    }


    // private function massEditCheckingStatusLock()
    // {
    //     $this->debugLog('lock');
    //     update_option('seoaic_mass_edit_checking_lock', '1');
    // }

    // private function massEditCheckingStatusUnLock()
    // {
    //     $this->debugLog('unlock');
    //     update_option('seoaic_mass_edit_checking_lock', '0');
    // }

    // private function isMassEditCheckingStatusLocked()
    // {
    //     return '1' == get_option('seoaic_mass_edit_checking_lock');
    // }


    // private function massReviewCheckingStatusLock()
    // {
    //     $this->debugLog('lock');
    //     update_option('seoaic_mass_review_checking_lock', '1');
    // }

    // private function massReviewCheckingStatusUnLock()
    // {
    //     $this->debugLog('unlock');
    //     update_option('seoaic_mass_review_checking_lock', '0');
    // }

    // private function isMassReviewCheckingStatusLocked()
    // {
    //     return '1' == get_option('seoaic_mass_review_checking_lock');
    // }

    public function transform_idea()
    {

        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        $id = intval($_REQUEST['item_id']);

        $post_data = [
            'ID' => $id,
            'post_type' => 'post',
            'post_date' => date('Y-m-d H:i:s'),
            'post_date_gmt' => gmdate('Y-m-d H:i:s'),
            'post_status' => 'publish'
        ];

        $wpml_idea_settings = $this->seoaic->multilang->get_wpml_idea_settings($id, 'post');

        wp_update_post($post_data);

        if (false !== $wpml_idea_settings) {
            $this->seoaic->multilang->set_wpml_idea_settings($id, $wpml_idea_settings);
        }

        SEOAICAjaxResponse::alert('Idea transformed!')->wpSend();
    }

    /**
     * Add post ID to editor
     */
    public function seoaic_admin_head()
    {
        if (isset($_GET['post'])) {
            echo '<link class="seoaic-home-url" href="' . get_bloginfo('url') . '"/>';

            $P = $_GET['post'];
            $SGD = $P ? get_post_meta($P, 'seoaic_generate_description', true) : '';
            $post = $SGD ?? '';
            echo $post ? '<link id="seoaic-promt-key" href="#" data-key="' . $post . '">' : '';
        }
    }

    /**
     * Filtering posts in schedule page
     */
    public function filter_schedule()
    {
        $order = $_REQUEST['order'];
        $search = $_REQUEST['search'];
        $minDate = $_REQUEST['mindate'];
        $maxDate = $_REQUEST['maxdate'];

        $minD = date("Y-m-d", strtotime($minDate));
        $maxD = date("Y-m-d", strtotime($maxDate));

        // WP Query
        $args = array(
            'numberposts' => -1,
            'post_type' => 'seoaic-post',
            'post_status' => 'seoaic-idea',
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => 'seoaic_idea_postdate',
                    'value' => [''],
                    'compare' => 'NOT IN'
                ]
            ],
            's' => $search,
            'orderby' => 'meta_value',
            'order' => $order,
        );

        if ($minDate && $maxDate) {
            $args['meta_query'] = [
                'relation' => 'AND',
                [
                    'key' => 'seoaic_idea_postdate',
                    'value' => [''],
                    'compare' => 'NOT IN'
                ],
                [
                    'key' => 'seoaic_idea_postdate',
                    'value' => array($minD, $maxD),
                    //'value' => array('2023-07-16', '2023-08-06'),
                    'compare' => 'BETWEEN',
                    'type' => 'DATE'
                ]
            ];
        }

        // If taxonomy is not set, remove key from array and get all posts
        if (!$order) {
            unset($args['order']);
        }

        if (!$search) {
            unset($args['a']);
        }

        $idea = new \WP_Query($args);

        if ($idea->have_posts()) : while ($idea->have_posts()) : $idea->the_post(); ?>

            <?php
            //print_r($minDate);
            //print_r(date("Y-m-d", $minDate));
            //print_r(date("Y-m-d", $maxDate));
            $idea_time = strtotime(get_post_meta(get_the_id(), 'seoaic_idea_postdate', true));
            $idea_post_date = date("F j, Y, g:i a", $idea_time);
            ?>
            <div class="post">
                <div class="content">
                    <div class="title"><?= get_the_title(); ?></div>
                </div>

                <div class="seoaic-change-posting-idea-date-td">
                    <input id="seoaic-posting-idea-date-checkbox-<?= $idea->ID ?>"
                           class="seoaic-posting-idea-date-checkbox" name="seoaic-posting-idea-date-checkbox"
                           type="checkbox">
                    <label for="seoaic-posting-idea-date-checkbox-<?= $idea->ID ?>">
                        <span class="seoaic-posting-idea-date-string"><?= $idea_post_date; ?></span>
                        <span class="dashicons dashicons-edit"></span>
                    </label>
                    <button title="Remove idea from posting schedule" type="button" data-post-id="<?= $idea->ID ?>"
                            class="seoaic-remove-idea-post-date-button"
                            data-action="seoaic_remove_idea_posting_date">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                    <input type="datetime-local" class="seoaic-change-posting-idea-date"
                           name="seoaic-change-posting-idea-date" data-value="<?= date("Y-m-d\TH:i", $idea_time) ?>"
                           value="<?= date("Y-m-d\TH:i", $idea_time) ?>">
                    <button title="Change post date" type="button" data-post-id="<?= get_the_id() ?>"
                            class="seoaic-change-idea-post-date-button button button-success"
                            data-action="seoaic_save_content_idea">
                        <span class="dashicons dashicons-saved"></span>
                    </button>
                    <button title="Close" type="button"
                            class="seoaic-change-idea-post-date-button-close button button-danger"
                            style="display: none !important;">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
            </div>

        <?php endwhile; ?>
        <?php else:
            ?>
            <h2>No posts found</h2>
        <?php endif;

        wp_die();
    }

    /**
     * Schedule posts generating and publishing
     */
    public function schedule_posts()
    {
        global $SEOAIC_OPTIONS;

        if (empty($SEOAIC_OPTIONS['seoaic_schedule_days'])) {
            SEOAICAjaxResponse::error('Set posting schedule in the Settings page first!')->wpSend();
        }

        $days = $SEOAIC_OPTIONS['seoaic_schedule_days'];
        $date = !empty($_REQUEST['posting_date']) ? sanitize_text_field($_REQUEST['posting_date']) : date('Y-m-d');

        if (empty($_REQUEST['idea-mass-create'])) {
            SEOAICAjaxResponse::error('Nothing to schedule!')->wpSend();
        }

        $ideas = is_array($_REQUEST['idea-mass-create']) ? $_REQUEST['idea-mass-create'] : [$_REQUEST['idea-mass-create']];

        foreach ($ideas as $idea_id) {

            $idea_id = intval($idea_id);

            delete_post_meta($idea_id, 'seoaic_idea_postdate');

            $days_loop = true;
            $datetime = time();

            while ($days_loop) {

                $_day = strtolower(date('l', $datetime));
                $_posts = $days[$_day]['posts'];

                if (isset($days[$_day])) {

                    $_have_posted_idea = get_posts([
                        'numberposts' => 99,
                        'fields' => 'ids',
                        'post_type' => 'seoaic-post',
                        'post_status' => 'seoaic-idea',
                        'meta_query' => [
                            [
                                'key' => 'seoaic_idea_postdate',
                                'value' => $date,
                                'compare' => 'LIKE'
                            ]
                        ]
                    ]);

                    if (count($_have_posted_idea) < $_posts) {
                        if (false === $this->seoaic->ideas->update_ideas_post_date($idea_id, $date . ' ' . date('H:i:s', strtotime($days[$_day]['time'])))) {

                            $datetime = strtotime($date . ' +1 day');
                            $date = date('Y-m-d', $datetime);
                        } else {
                            $days_loop = false;
                        }
                    } else {

                        $datetime = strtotime($date . ' +1 day');
                        $date = date('Y-m-d', $datetime);
                    }
                } else {

                    $datetime = strtotime($date . ' +1 day');
                    $date = date('Y-m-d', $datetime);
                }

            }
        }

        SEOAICAjaxResponse::alert('Posting schedule saved!')->wpSend();
    }

    public function prepare_item_data($request_data = [])
    {
        global $SEOAIC_OPTIONS;

        if (empty($request_data['item_id'])) {
            wp_die();
        }

        $id = intval($request_data['item_id']);
        $editor = $request_data['data_editor'] ?? false;
        $mass_prompt = !empty($request_data['mass_prompt']) ? stripslashes(sanitize_textarea_field($request_data['mass_prompt'])) : '';
        $mass_service = !empty($request_data['mass_service']) ? trim($request_data['mass_service']) : '';

        $idea_prompt = get_post_meta($id, '_idea_prompt_data', true);
        $name = !empty($SEOAIC_OPTIONS['seoaic_business_name']) ? $SEOAIC_OPTIONS['seoaic_business_name'] : get_option('blogname', true);
        $industry = !empty($SEOAIC_OPTIONS['seoaic_industry']) ? " on the industry of " . $SEOAIC_OPTIONS['seoaic_industry'] : '';
        $desc = !empty($SEOAIC_OPTIONS['seoaic_business_description']) ? $SEOAIC_OPTIONS['seoaic_business_description'] : get_option('blogdescription', true);
        $content_guidelines = !empty($SEOAIC_OPTIONS['seoaic_content_guidelines']) ? $SEOAIC_OPTIONS['seoaic_content_guidelines'] : '';

        if (!empty($request_data['seoaic_subtitles_min'])) {
            $subtitles_min = intval($request_data['seoaic_subtitles_min']);
        } elseif (!empty($SEOAIC_OPTIONS['seoaic_subtitles_range_min'])) {
            $subtitles_min = intval($SEOAIC_OPTIONS['seoaic_subtitles_range_min']);
        } else {
            $subtitles_min = 0;
        }

        if (!empty($request_data['seoaic_subtitles_max'])) {
            $subtitles_max = intval($request_data['seoaic_subtitles_max']);
        } elseif (!empty($SEOAIC_OPTIONS['seoaic_subtitles_range_max'])) {
            $subtitles_max = intval($SEOAIC_OPTIONS['seoaic_subtitles_range_max']);
        } else {
            $subtitles_max = 6;
        }

        if (!empty($request_data['seoaic_words_min'])) {
            $words_min = intval($request_data['seoaic_words_min']);
        } elseif (!empty($SEOAIC_OPTIONS['seoaic_words_range_min'])) {
            $words_min = intval($SEOAIC_OPTIONS['seoaic_words_range_min']);
        } else {
            $words_min = 0;
        }

        if (!empty($request_data['seoaic_words_max'])) {
            $words_max = intval($request_data['seoaic_words_max']);
        } elseif (!empty($SEOAIC_OPTIONS['seoaic_words_range_max'])) {
            $words_max = intval($SEOAIC_OPTIONS['seoaic_words_range_max']);
        } else {
            $words_max = 1000;
        }

        $idea_content = get_post_meta($id, 'seoaic_idea_content', true);
        $idea_content = !empty($idea_content) ? json_decode($idea_content, true) : '';

        $idea_generator = !empty($idea_content['idea_thumbnail_generator']) ? $idea_content['idea_thumbnail_generator'] : seoaic_get_default_image_generator();
        $idea_type = get_post_meta($id, '_idea_type', true);

        if ($editor) {
            $subtitles = get_post_meta($id, 'seoaic_article_subtitles', true) ?? [];
            $keywords = get_post_meta($id, 'seoaic_article_keywords', true) ?? [];
            $thumb = get_post_meta($id, '_thumb_yes_seoaic', true);
            $thumbGen = get_post_meta($id, 'seoaic_idea_thumbnail_generator', true) ? get_post_meta($id, 'seoaic_idea_thumbnail_generator', true) : 'gpt';
            $thumbGen = $thumb ? $thumbGen : 'no_image';
            $thumbDesc = get_post_meta($id, 'seoaic_generate_description', true) ?? [];

        } else {
            $subtitles = !empty($idea_content['idea_skeleton']) ? $idea_content['idea_skeleton'] : [];
            $keywords = !empty($idea_content['idea_keywords']) ? $idea_content['idea_keywords'] : [];
            $thumbGen = !empty($idea_generator) ? $idea_generator : 'gpt';
            $thumbDesc = !empty($idea_content['idea_thumbnail']) ? $idea_content['idea_thumbnail'] : '';
        }

        $data_raw = [
            'idea' => get_the_title($id),
            'idea_type' => !empty($idea_type) ? $idea_type : 'default',
            'subtitles' => $subtitles,
            'keywords' => $keywords,
            'thumbnail' => $thumbDesc,
            'thumbnail_generator' => $thumbGen,
            'language' => $this->seoaic->multilang->get_post_language($id),
            'writing_style' => !empty($SEOAIC_OPTIONS['seoaic_writing_style']) ? $SEOAIC_OPTIONS['seoaic_writing_style'] : '',
            'prompt' => !empty($mass_prompt) ? $mass_prompt : $idea_prompt,
            'mass_service' => $mass_service,
            'name' => !empty($name) ? $name : '',
            'industry' => !empty($industry) ? $industry : '',
            'desc' => !empty($desc) ? $desc : '',
            'words_min' => $words_min,
            'words_max' => $words_max,
            'subtitles_min' => $subtitles_min,
            'subtitles_max' => $subtitles_max,
            'internal_links' => $this->getInternalLinks($id),
            'pillar_links' => $this->getPillarLinks($this->seoaic->multilang->get_post_language($id)),
            'content_guidelines' => $content_guidelines,
        ];

        $original_id = get_post_meta($id, 'seoaic_ml_original_post', true);

        if (!empty($original_id)) {
            $post = get_post($original_id);
            $data_raw['original_title'] = $post->post_title;
            $data_raw['original_post'] = $post->post_content;
        }

        return [
            'data' => $this->formatItemFields($data_raw), // formatted
            'data_raw' => $data_raw,
        ];
    }

    public function getItemDataById($id = '')
    {
        global $SEOAIC_OPTIONS;

        if (empty($id)) {
            wp_die();
        }

        $editor = $request_data['data_editor'] ?? false;
        $mass_service = !empty($request_data['mass_service']) ? trim($request_data['mass_service']) : '';

        $idea_prompt = get_post_meta($id, '_idea_prompt_data', true);
        $name = !empty($SEOAIC_OPTIONS['seoaic_business_name']) ? $SEOAIC_OPTIONS['seoaic_business_name'] : get_option('blogname', true);
        $industry = !empty($SEOAIC_OPTIONS['seoaic_industry']) ? " on the industry of " . $SEOAIC_OPTIONS['seoaic_industry'] : '';
        $desc = !empty($SEOAIC_OPTIONS['seoaic_business_description']) ? $SEOAIC_OPTIONS['seoaic_business_description'] : get_option('blogdescription', true);
        $content_guidelines = !empty($SEOAIC_OPTIONS['seoaic_content_guidelines']) ? $SEOAIC_OPTIONS['seoaic_content_guidelines'] : '';

        $idea_content = get_post_meta($id, 'seoaic_idea_content', true);
        $idea_content = !empty($idea_content) ? json_decode($idea_content, true) : '';

        $idea_generator = !empty($idea_content['idea_thumbnail_generator']) ? $idea_content['idea_thumbnail_generator'] : seoaic_get_default_image_generator();
        $idea_type = get_post_meta($id, '_idea_type', true);

        if ($editor) {
            $subtitles = get_post_meta($id, 'seoaic_article_subtitles', true) ?? [];
            $keywords = get_post_meta($id, 'seoaic_article_keywords', true) ?? [];
            $thumb = get_post_meta($id, '_thumb_yes_seoaic', true);
            $thumbGen = get_post_meta($id, 'seoaic_idea_thumbnail_generator', true) ? get_post_meta($id, 'seoaic_idea_thumbnail_generator', true) : 'gpt';
            $thumbGen = $thumb ? $thumbGen : 'no_image';
            $thumbDesc = get_post_meta($id, 'seoaic_generate_description', true) ?? [];

        } else {
            $subtitles = !empty($idea_content['idea_skeleton']) ? $idea_content['idea_skeleton'] : [];
            $keywords = !empty($idea_content['idea_keywords']) ? $idea_content['idea_keywords'] : [];
            $thumbGen = !empty($idea_generator) ? $idea_generator : 'gpt';
            $thumbDesc = !empty($idea_content['idea_thumbnail']) ? $idea_content['idea_thumbnail'] : '';
        }

        $data_raw = [
            'idea' => get_the_title($id),
            'idea_type' => !empty($idea_type) ? $idea_type : 'default',
            'subtitles' => $subtitles,
            'keywords' => $keywords,
            'thumbnail' => $thumbDesc,
            'thumbnail_generator' => $thumbGen,
            'language' => $this->seoaic->multilang->get_post_language($id),
            'prompt' => !empty($mass_prompt) ? $mass_prompt : $idea_prompt,
            'mass_service' => $mass_service ?? '',
            'internal_links' => $this->getInternalLinks($id),
            'pillar_links' => $this->getPillarLinks($this->seoaic->multilang->get_post_language($id)),
        ];

        $original_id = get_post_meta($id, 'seoaic_ml_original_post', true);

        if (!empty($original_id)) {
            $post = get_post($original_id);
            $data_raw['original_title'] = $post->post_title;
            $data_raw['original_post'] = $post->post_content;
        }

        return [
            'data' => $this->formatItemFields($data_raw), // formatted
            'data_raw' => $data_raw,
        ];
    }

    private function formatItemFields($data = [])
    {
        array_walk($data, function (&$item, $key) {
            if (
                ("subtitles" == $key || "keywords" == $key)
                && is_array($item)
            ) {
                $item = implode(',', $item);
            }
        });
        return $data;
    }

    /**
     * Generate and save post via openai
     * @param bool $mass_create
     */
    public function generate_post($mass_create = false, $only_settings = false)
    {
        global $SEOAIC_OPTIONS;

        if (empty($_REQUEST['item_id'])) {
            wp_die();
        }

        $request_data = $_REQUEST;
        list('data' => $data, 'data_raw' => $data_raw) = $this->prepare_item_data($request_data);
        // error_log('data '.print_r($data, true));
        // error_log('data_raw '.print_r($data_raw, true));

        if ($only_settings) {
            return $data;
        }

        $id = intval($_REQUEST['item_id']);
        $post_data = [
            'ID' => $id,
            'post_type' => 'post',
            'post_date' => date('Y-m-d H:i:s'),
            'post_date_gmt' => gmdate('Y-m-d H:i:s'),
            'post_status' => 'draft'
        ];
        $mass_set_thumbnail = isset($_REQUEST['mass_set_thumbnail']) ? $_REQUEST['mass_set_thumbnail'] : '';

        if (isset($_REQUEST['seoaic_post_status'])) {
            switch ($_REQUEST['seoaic_post_status']) {
                case 'publish':
                    $post_data['post_status'] = 'publish';
                    break;
                case 'delay':
                    $publish_delay = !empty($SEOAIC_OPTIONS['seoaic_publish_delay']) ? intval($SEOAIC_OPTIONS['seoaic_publish_delay']) : 0;
                    if ($publish_delay > 0) {
                        $publish_time = time() + (3600 * $publish_delay);
                        $post_data['post_status'] = 'future';
                        $post_data['post_date'] = date('Y-m-d H:i:s', $publish_time);
                        $post_data['post_date_gmt'] = gmdate('Y-m-d H:i:s', $publish_time);
                    }
                    break;
                case 'schedule':
                    $schedule_start_date = (!empty($_REQUEST['seoaic-mass-idea-date']) && $_REQUEST['seoaic-mass-idea-date'] >= date('Y-m-d')) ? $_REQUEST['seoaic-mass-idea-date'] : date('Y-m-d');

                    $post_data['post_status'] = 'future';
                    $posting_date = $this->get_schedule_posting_date($schedule_start_date);

                    if (false !== $posting_date) {
                        $post_data['post_date'] = $posting_date;
                        $post_data['post_date_gmt'] = gmdate('Y-m-d H:i:s', strtotime($posting_date));
                        update_post_meta($id, 'seoaic_idea_postdate', $posting_date);
                        wp_update_post(['ID' => $id, 'post_status' => 'future', 'post_date' => '3000-01-01 00:00:00', 'post_date_gmt' => '3000-01-01 00:00:00']);
                    }
                    break;
            }
        }

        $idea_content = get_post_meta($id, 'seoaic_idea_content', true);
        $idea_content = !empty($idea_content) ? json_decode($idea_content, true) : '';

        $post_data['post_type'] = !empty($idea_content['idea_post_type']) ? $idea_content['idea_post_type'] : SEOAIC_SETTINGS::getSEOAICPostType();

        $result = $this->seoaic->curl->init('api/ai/post', $data, true, true, true);

        $content = !empty($result['content']) ? $result['content'] : '';
        $post_content = !empty($content['content']) ? $content['content'] : '';

        $post_content = stripslashes($post_content);
        $post_data['post_content'] = $this->prepareGeneratedContent($post_content);

        $wpml_idea_settings = $this->seoaic->multilang->get_wpml_idea_settings($id, $post_data['post_type']);

        wp_update_post($post_data);

        if (false !== $wpml_idea_settings) {
            $this->seoaic->multilang->set_wpml_idea_settings($id, $wpml_idea_settings);
        }

        if (!empty($result['frame'])) {
            $post_desc = !empty($result['frame']['description']) ? $result['frame']['description'] : '';
            $post_keywords = !empty($result['frame']['keywords']) ? array_shift($result['frame']['keywords']) : [];
        } else {
            $post_desc = !empty($idea_content['idea_description']) ? $idea_content['idea_description'] : '';
            $post_keywords = !empty($idea_content['idea_keywords']) ? array_shift($idea_content['idea_keywords']) : [];
        }

        update_post_meta($id, 'seoaic_posted', 1);
        update_post_meta($id, '_yoast_wpseo_metadesc', $post_desc);
        update_post_meta($id, '_yoast_wpseo_focuskw', $post_keywords);
        update_post_meta($id, 'seoaic_generate_description', $data_raw['thumbnail']);
        //update_post_meta($id, 'seoaic_article_description', $data['desc']);
        update_post_meta($id, 'seoaic_article_subtitles', $data_raw['subtitles']);
        update_post_meta($id, 'seoaic_article_keywords', $data_raw['keywords']);

        $language_code = $this->seoaic->multilang->get_post_language($id, 'code');

        $categories = false;
        if (!empty($idea_content['idea_category'])) {

            $categories = is_array($idea_content['idea_category']) ? $idea_content['idea_category'] : [$idea_content['idea_category']];

        } elseif (!empty($SEOAIC_OPTIONS['seoaic_default_category'])) {

            $categories = is_array($SEOAIC_OPTIONS['seoaic_default_category']) ? $SEOAIC_OPTIONS['seoaic_default_category'] : [$SEOAIC_OPTIONS['seoaic_default_category']];

        }

        if (!empty($categories)) {
            foreach ($categories as $key => $category) {
                $append = $key === 0 ? false : true;
                wp_set_post_terms($id, [
                    $this->seoaic->multilang->get_term_translation($category, $language_code)
                ], get_term($category)->taxonomy, $append);
            }
        }

        // Clickdrop image generate
        if ($mass_set_thumbnail) {

            $attachment_id = $mass_set_thumbnail;
            set_post_thumbnail($id, $attachment_id);

        } elseif (!empty($content['image'])) {

            $url = $content['image'];
            $attachment_id = SEOAIC::seoaicUploadFile($url, $data['idea']);
            set_post_thumbnail($id, $attachment_id);

        }


        if (!$mass_create) {

            $image_generator_box = '';

            if ($data['thumbnail_generator'] !== 'no_image') {
                $image_generators = seoaic_get_image_generators();

                $image_generator_box .= '<div class="generated-post-thumbnail">
                                    <div class="holder">
                                        ' . get_the_post_thumbnail($id) . '
                                    </div>

                                    <div class="seoaic-module">
                                    <input type="checkbox" name="regenerate_image" id="regenerate_image">
                                    <label for="regenerate_image">Don\'t like the image? <span>Generate a new!</span>
                                    <div class="info">
                                        <span class="info-btn">?</span>
                                            <div class="info-content">
                                                <h4>Generate new image</h4>
                                                <p>You can try regenerating image with another service if you are not satisfied with it.
                                            </p>
                                        </div>
                                    </div>
                                    </label>
                                    <div class="selections">
                                    <textarea class="promt-key">' . $data['thumbnail'] . '</textarea>
                                    <select class="seoaic-form-item form-select regenerate-select-modal" name="seoaic_regenerate-select-modal" required="">';

                foreach ($image_generators as $key => $image_generator) {
                    if ($key === 'no_image') {
                        continue;
                    }

                    $image_generator_box .= '<option value="' . $key . '"';
                    if ($key === $data['thumbnail_generator']) {
                        $image_generator_box .= 'selected';
                    }
                    $image_generator_box .= '>' . $image_generator . '</option>';
                }

                $image_generator_box .= '</select>
                                    <div class="btn-sc">
                                        <div class="info">
                                            <span class="info-btn">?</span>
                                            <div class="info-content">
                                                <h4>Generate new image</h4>
                                                <p>You can try regenerating image with another service if you are not satisfied with it.
                                                </p>
                                            </div>
                                        </div>
                                        <button data-callback="regenerate_image_modal" data-desc="' . $data['thumbnail'] . '" data-action="seoaic_regenerate_image" data-type="modal" data-post="' . $id . '" title="Regenerate image" type="button" class="button-primary seoaic-generate-image-button" data-action="seoaic_generate_ideas">New image
                                        </button>
                                    </div>
                                    </div>
                                    </div>
                                </div>';

            }

            wp_send_json([
                'status' => 'success',
                'content' => [
                    'post_id' => $id,
                    'content' => '<div class="generated-post-box">
                                        ' . $image_generator_box . '
                                        <div class="generated-post-content">
                                            ' . $post_data['post_content'] . '
                                        </div>
                                    </div>'
                ],
                'editor_content' => $post_data['post_content'],
                'thumbnail' => get_post_thumbnail_id($id)
            ]);
        } else {
            wp_send_json([
                'status' => 'generating',
                'content' => [
                    'post_id' => $id,
                ],
                'editor_content' => $post_data['post_content'],
                'thumbnail' => get_post_thumbnail_id($id)
            ]);
        }
    }

    /**
     * Regenerate image
     */
    public function regenerate_image()
    {
        global $SEOAIC_OPTIONS;

        $id = intval($_REQUEST['post']);
        $data = [
            'prompt' => stripslashes(sanitize_text_field($_REQUEST['promt'])),
            'thumbnail_generator' => sanitize_text_field($_REQUEST['gen']),
        ];

        $result = $this->seoaic->curl->init('api/ai/images', $data, true, true, true);


        $image_url = $result['image_url'];

        //$attachment_id = media_sideload_image($image_url, $id, $data['prompt'], 'id');

        $attachment_id = SEOAIC::seoaicUploadFile($image_url, get_the_title($id));

        set_post_thumbnail($id, $attachment_id);

        $idea_content = get_post_meta($id, 'seoaic_idea_content', true);

        $idea_content = !empty($idea_content) ? json_decode($idea_content, true) : [
            'idea_thumbnail' => '',
            'idea_thumbnail_generator' => '',
            'idea_skeleton' => '',
            'idea_keywords' => '',
            'idea_description' => '',
        ];

        $idea_content['idea_thumbnail'] = $data['prompt'];
        $idea_content['idea_thumbnail_generator'] = $data['thumbnail_generator'];

        update_post_meta($id, 'seoaic_idea_content', json_encode($idea_content));
        update_post_meta($id, 'seoaic_generate_description', $data['prompt']);

        // wp_send_json([
        //     'status' => 'success',
        //     'content' => [
        //         'post_id' => $id,
        //         'content' => get_the_post_thumbnail($id),
        //         'featured_media' => get_post_thumbnail_id($id)
        //     ]
        // ]);
        SEOAICAjaxResponse::success()->addFields([
            'content' => [
                'post_id' => $id,
                'content' => get_the_post_thumbnail($id),
                'featured_media' => get_post_thumbnail_id($id)
            ]
        ])->wpSend();
    }

    /**
     * Posts mass create in background
     */
    public function background_generation($ideas_from_source)
    {
        $ideas = [];
        $thumb = 0;
        $idea_id = 0;

        if (isset($_REQUEST['seoaic_mass_set_thumbnail'])) {
            $thumb = (int)$_REQUEST['seoaic_mass_set_thumbnail'];
        }

        if($ideas_from_source) {
            $_REQUEST['idea-mass-create'] = $ideas_from_source;
        }

        if ($_REQUEST['idea-mass-create']) {
            if (is_array($_REQUEST['idea-mass-create'])) {
                foreach ($_REQUEST['idea-mass-create'] as $item) {
                    $ideas[] = intval($item);
                    if (!empty($thumb)) {
                        set_post_thumbnail(intval($item), $thumb);
                    }
                }
            } elseif (isset($_REQUEST['idea-mass-create'])) {
                $item = intval($_REQUEST['idea-mass-create']);
                $ideas[] = $item;
            }
        } else {
            $item = intval($_REQUEST['item_id']);
            $ideas[] = $item;
            $idea_id = $item;
        }

        $prev_option = PostsGenerationLoader::getPostsOption();

        if (
            isset($prev_option['total'])
            && isset($prev_option['done'])
            && count($prev_option['total']) > count($prev_option['done'])
        ) {
            $option_ideas = array_merge($ideas, $prev_option['total'] ?? []);
            $option_posts = $prev_option['done'];
        } else {
            $option_ideas = $ideas;
            $option_posts = [];
        }

        $option = [
            'total' => $option_ideas,
            'done' => $option_posts,
        ];

        $post_status = !empty($_REQUEST['seoaic_post_status']) ? $_REQUEST['seoaic_post_status'] : '';
        if(isset($_REQUEST['set_post_status'])) {
            $post_status = $_REQUEST['set_post_status'];
        }

        if (
            !empty($_REQUEST['seoaic-mass-idea-date'])
            && $_REQUEST['seoaic-mass-idea-date'] >= date('Y-m-d')
        ) {
            $schedule_start_date = $_REQUEST['seoaic-mass-idea-date'];
            foreach ($ideas as $idea) {
                $posting_date = $this->get_schedule_posting_date($schedule_start_date);
                if (isset($_REQUEST['set_post_date'])) {
                    $posting_date = $_REQUEST['set_post_date'];
                }
                if($post_status === 'publish' || $post_status === 'draft') {
                    $posting_date = date("Y-m-d H:i:s");
                }
                update_post_meta($idea, 'seoaic_idea_postdate', $posting_date);
                update_post_meta($idea, 'seoaic_idea_poststatus', $post_status);
                wp_update_post([
                    'ID' => $idea,
                    'post_status' => 'future',
                    'post_date' => '3000-01-01 00:00:00',
                    'post_date_gmt' => '3000-01-01 00:00:00'
                ]);
            }
        }

        PostsGenerationLoader::setPostsOption($option);

        $formData = [
            'prompt' => '',
            'words_min' => self::WORDS_MIN,
            'words_max' => self::WORDS_MAX,
            'subtitles_min' => self::SUBTITLES_MIN,
            'subtitles_max' => self::SUBTITLES_MAX,
            'domain' => $_SERVER['HTTP_HOST'],
            'posting_date' => null,
            'manual_mass_thumb' => $thumb,
            'type' => 'post',
            'categories' => [],
        ];

        if (isset($_REQUEST['mass_prompt'])) {
            $formData['prompt'] = stripslashes(sanitize_textarea_field($_REQUEST['mass_prompt']));
        }

        if (isset($_REQUEST['seoaic_knowledge_base'])) {
            $formData['knowledge_ids'] = $_REQUEST['seoaic_knowledge_base'];
        }

        if (!empty($_REQUEST['seoaic_default_category'])) {
            $formData['categories'] = $_REQUEST['seoaic_default_category'];
        }

        $intFields = [
            'seoaic_words_range_min' => 'words_min',
            'seoaic_words_range_max' => 'words_max',
            'seoaic_subtitles_range_min' => 'subtitles_min',
            'seoaic_subtitles_range_max' => 'subtitles_max',
        ];

        foreach ($intFields as $requestField => $resultField) {
            if (isset($_REQUEST[$requestField])) {
                $formData[$resultField] = intval($_REQUEST[$requestField]);
            }
        }

        if (
            !empty($_REQUEST['seoaic-translate-from-origin'])
            && $_REQUEST['seoaic-translate-from-origin'] === 'yes'
        ) {
            $basket = [];
            foreach ($ideas as $idea) {
                if (in_array($idea, $basket)) {
                    continue;
                }

                $linked_ideas = $this->seoaic->multilang->get_post_translations($idea);
                update_post_meta($idea, 'seoaic_ml_generated_data', $formData);

                foreach ($linked_ideas as $linked_idea) {
                    if (
                        $linked_idea == $idea
                        || !array_search($linked_idea, $ideas)
                    ) {
                        continue;
                    }

                    $basket[] = $linked_idea;
                    update_post_meta($linked_idea, 'seoaic_ml_original_post', $idea);
                }
            }

            foreach ($basket as $removed_idea) {
                $key = array_search($removed_idea, $ideas);
                unset($ideas[$key]);
            }
            $ideas = array_values($ideas);
        }

        $seoaicData = SEOAIC::getSEOAICData();

        if($ideas_from_source) {
            $requestData = [

                'knowledge_ids' => !empty($formData['knowledge_ids']) ? [$formData['knowledge_ids']] : [],
                'prompt'    => $formData['prompt'],
                'domain'    => $formData['domain'],
                'name'      => $seoaicData['name'],
                'industry'  => $seoaicData['industry'],
                'desc'      => $seoaicData['desc'],
                'content_guidelines'    => $seoaicData['content_guidelines'],
                'words_min'     => $formData['words_min'],
                'words_max'     => $formData['words_max'],
                'subtitles_min' => $formData['subtitles_min'],
                'subtitles_max' => $formData['subtitles_max'],
                'writing_style' => $seoaicData['writing_style'],
                'manual_mass_thumb' => $thumb,
                'ideas' => [],
            ];
        } else {
            $requestData = [
                'knowledge_ids' => !empty($formData['knowledge_ids']) ? [$formData['knowledge_ids']] : [],
                'prompt'    => $formData['prompt'],
                'domain'    => $formData['domain'],
                'name'      => $seoaicData['name'],
                'industry'  => $seoaicData['industry'],
                'desc'      => $seoaicData['desc'],
                'content_guidelines'    => $seoaicData['content_guidelines'],
                //'pillar_links'  => $formData['pillar_links'],
                'words_min'     => $formData['words_min'],
                'words_max'     => $formData['words_max'],
                'subtitles_min' => $formData['subtitles_min'],
                'subtitles_max' => $formData['subtitles_max'],
                'writing_style' => $seoaicData['writing_style'],
                'manual_mass_thumb' => $thumb,
                'ideas' => [],
            ];
        }

        $ideasDataArray = [];
        foreach ($ideas as $i=>$idea) {
            delete_post_meta($idea, 'seoaic_generate_status');
            list('data' => $idea_data, 'data_raw' => $data_raw) = $this->getItemDataById($idea);
            $idea_source_data = get_post_meta($idea, 'seoaic_idea_source', true);

            $ideasDataArray[] = [
                'idea_id'           => $idea,
                'idea'              => $idea_data['idea'],
                'idea_type'         => $idea_data['idea_type'],
                'subtitles'         => $idea_data['subtitles'],
                'keywords'          => $idea_data['keywords'],
                'thumbnail'         => $idea_data['thumbnail'],
                'thumbnail_generator' => 'no_image',
                'language'          => $idea_data['language'],
                'internal_links'    => $idea_data['internal_links'],
                'pillar_links'    => $idea_data['pillar_links']
            ];

            if($ideas_from_source) {
                $ideasDataArray[$i]['base_content'] = $idea_source_data['source_content'];
                $ideasDataArray[$i]['manual_mass_thumb'] = $thumb;
                $ideasDataArray[$i]['idea_type'] = 'content';
            }

        }

        $ideasChunks = array_chunk($ideasDataArray, self::PER_REQUEST__GENERATE);
        foreach ($ideasChunks as $ideasChunk) {
            $requestData['ideas'] = $ideasChunk;

            $this->debugLog('Mode: Generate; Request:', $requestData);

            // $result = $this->seoaic->curl->init('api/schedule', $data, true, false, true);
            $result = $this->seoaic->curl->init('api/ai/posts/generate', $requestData, true, false, true);

            error_log("<?php\n" . var_export($requestData, true) . ";\n?>");
            error_log("<?php\n" . var_export($result, true) . ";\n?>");

            $this->debugLog('Mode: Generate; Response:', $result);
        }

        $this->startBackendPolling(self::GENERATE_MODE);

        SEOAICAjaxResponse::success()->addFields([
            // 'data' => $requestData,
            // 'result' => $result,
            'content' => [
                'post_id' => $idea_id,
                'loader'  => $this->seoaic->get_background_process_loader(true),
            ]
        ])->wpSend();
    }

    /**
     * Posts mass create
     */
    public function postsMassGenerate($ideas_from_source = [])
    {
        $this->background_generation($ideas_from_source);

        SEOAICAjaxResponse::waiting()->wpSend();
    }

    // public function postsMassEdit()
    // {
    //     $this->postsMassProcess(self::EDIT_MODE);
    // }

    // public function postsMassReview()
    // {
    //     $this->postsMassProcess(self::REVIEW_MODE);
    // }

    /**
     * Posts mass edit/review
     */
    // public function postsMassProcess($mode = self::EDIT_MODE)
    // {
    //     global $SEOAIC_OPTIONS;

    //     $ids = [];
    //     $loader_ids = [];
    //     $posts = [];
    //     $prompt = !empty($_REQUEST['mass_prompt']) ? $_REQUEST['mass_prompt'] : '';

    //     if (!empty($_REQUEST['post-mass-edit'])) {
    //         $selected_ids = $_REQUEST['post-mass-edit'];

    //         if (is_array($selected_ids)) {
    //             $ids = $selected_ids;
    //         } elseif (
    //             is_numeric($selected_ids)
    //             && intval($selected_ids) == $selected_ids
    //         ) {
    //             $ids = [$selected_ids];
    //         }
    //     }

    //     if (empty($ids)) {
    //         SEOAICAjaxResponse::error('No posts selected')->wpSend();
    //     }

    //     $translateStatusField = (new PostsMassTranslate($this->seoaic))->getStatusField();
    //     $reviewStatusField = (new PostsMassReview($this->seoaic))->getStatusField();
    //     $query = new WP_Query([
    //         'posts_per_page'    => -1,
    //         'post_type'         => 'any',
    //         'post__in'          => $ids,
    //         'meta_query'        => [
    //             'relation' => 'AND',
    //             [
    //                 'key' => 'seoaic_posted',
    //                 'value' => '1',
    //                 'compare' => '=',
    //             ],
    //             [
    //                 'relation' => 'OR',
    //                 [
    //                     'key' => self::EDIT_STATUS_FIELD,
    //                     'value' => 'pending',
    //                     'compare' => '!=',
    //                 ],
    //                 [
    //                     'key' => self::EDIT_STATUS_FIELD,
    //                     'compare' => 'NOT EXISTS',
    //                 ],
    //             ],
    //             [
    //                 'relation' => 'OR',
    //                 [
    //                     'key' => $reviewStatusField,
    //                     'value' => 'reviewing',
    //                     'compare' => '!=',
    //                 ],
    //                 [
    //                     'key' => $reviewStatusField,
    //                     'compare' => 'NOT EXISTS',
    //                 ],
    //             ],
    //             [
    //                 'relation' => 'OR',
    //                 [
    //                     'key' => $translateStatusField,
    //                     'value' => 'translating',
    //                     'compare' => '!=',
    //                 ],
    //                 [
    //                     'key' => $translateStatusField,
    //                     'compare' => 'NOT EXISTS',
    //                 ],
    //             ],
    //         ],
    //     ]);

    //     if ($query->have_posts()) {
    //         while ($query->have_posts()) {
    //             $query->the_post();
    //             $post_id = get_the_ID();

    //             switch ($mode) {
    //                 case self::EDIT_MODE:
    //                     $post_language = $this->seoaic->multilang->get_post_language($post_id);
    //                     $post_language = $post_language ? $post_language : 'English';
    //                     break;

    //                 // case self::REVIEW_MODE:
    //                 //     $post_language = 'English'; // use default language to be able to parse response
    //             }
    //             $posts[] = [
    //                 'id'        => $post_id,
    //                 'content'   => get_the_content(),
    //                 'language'  => $post_language
    //             ];
    //         }
    //     }

    //     if (empty($posts)) {
    //         SEOAICAjaxResponse::error('No posts found')->wpSend();
    //     }

    //     $posts_chunks = array_chunk($posts, self::PER_REQUEST__UPDATES);
    //     foreach ($posts_chunks as $posts_chunk) {
    //         $data = [
    //             // 'prompt'    => $this->makePostsPrompt($prompt, $mode),
    //             'prompt'    => $prompt,
    //             'posts'     => $posts_chunk,
    //         ];

    //         foreach ($posts_chunk as $post) {
    //             if (self::EDIT_MODE == $mode) {
    //                 update_post_meta($post['id'], self::EDIT_STATUS_FIELD, 'pending');
    //                 update_post_meta($post['id'], self::EDIT_STATUS_TIME_FIELD, '');
    //             // } elseif (self::REVIEW_MODE == $mode) {
    //             //     update_post_meta($post['id'], self::REVIEW_STATUS_FIELD, 'reviewing');
    //             //     update_post_meta($post['id'], 'seoaic_review_prompt', $prompt);
    //             }
    //         }

    //         $this->debugLog('Mode= '.$mode, array_map(function ($item) {
    //             if (
    //                 !empty($item['posts'])
    //                 && is_array($item['posts'])
    //             ) {
    //                 foreach ($item['posts']as &$post) {
    //                     $post['content'] = substr($post['content'], 0, 50) . '...';
    //                 }
    //             }
    //             return $item;
    //         }, [$data]));

    //         $url = '/api/ai/posts/updates';
    //         // if (self::REVIEW_MODE == $mode) {
    //         //     $url = '/api/ai/posts/review';
    //         // }
    //         $result = $this->seoaic->curl->initWithReturn($url, $data, true, true);

    //         if (
    //             empty($result['status'])
    //             || 'success' != $result['status']
    //         ) {
    //             foreach ($posts_chunk as $post) {
    //                 if (self::EDIT_MODE == $mode) {
    //                     update_post_meta($post['id'], self::EDIT_STATUS_FIELD, '');
    //                 // } elseif (self::REVIEW_MODE == $mode) {
    //                 //     update_post_meta($post['id'], self::REVIEW_STATUS_FIELD, '');
    //                 }
    //             }

    //             $this->debugLog('[ERROR] Response status is not \'success\': ' . print_r($result, true));
    //             $msg = isset($result['message']) ? $result['message'] : 'Some error happened';
    //             SEOAICAjaxResponse::error($msg)->wpSend();
    //         } else {
    //             foreach ($posts_chunk as $post) {
    //                 $loader_ids[] = $post['id'];
    //             }
    //         }
    //     }

    //     $this->startBackendPolling($mode);

    //     $loader = null;
    //     if ($mode == self::EDIT_MODE) {
    //         $loader = new PostsEditLoader();

    //     // } elseif ($mode == self::REVIEW_MODE) {
    //     //     $loader = new PostsReviewLoader();
    //     }

    //     if (!is_null($loader)) {
    //         $option = $loader::getPostsOption();
    //         if (count($option['done']) == count($option['total'])) { // reset
    //             $option = [
    //                 'total' => $loader_ids,
    //                 'done' => [],
    //             ];
    //         } else {
    //             $option['total'] = array_merge($option['total'], $loader_ids);
    //         }

    //         $loader::setPostsOption($option);
    //     }

    //     SEOAICAjaxResponse::success()->wpSend();
    // }

    // private function makePostsPrompt($prompt='', $mode=self::EDIT_MODE)
    // {
    //     if (self::REVIEW_MODE == $mode) {
    //         $prompt = 'Condition: ' . $prompt . '. If condition is true return yes, otherwise return no. Give the answer in one word.';
    //     }

    //     return $prompt;
    // }

    // public function postsMassEditCheckStatus()
    // {
    //     try {
    //         $edit_in_progress = $this->isProcessingInProgress(self::EDIT_MODE);
    //         if (!$edit_in_progress) {
    //             $this->debugLog('!edit_in_progress');
    //             $this->unregisterPostsCheckStatusCron(self::EDIT_MODE);
    //             return;
    //         }

    //         if ($this->isMassEditCheckingStatusLocked()) {
    //             $this->debugLog('is locked');
    //             return;
    //         } else {
    //             $this->massEditCheckingStatusLock(); // to block simultaneous run
    //         }
    //         $this->debugLog('run');

    //         $status_result = $this->seoaic->curl->init('/api/ai/posts/updates/status', [], true, true, true);
    //         $this->debugLog('status_result ', $status_result);

    //         if (
    //             !empty($status_result['failed'])
    //             && is_array($status_result['failed'])
    //         ) {
    //             $this->massEditProccessFailedPosts($status_result['failed']);
    //         }

    //         if (
    //             !empty($status_result['completed'])
    //             && is_array($status_result['completed'])
    //         ) {
    //             $this->massEditProccessCompletedPosts($status_result['completed']);
    //         }

    //         $option = PostsEditLoader::getPostsOption();
    //         $response_fields = [
    //             'status' => '',
    //             'width' => !empty($option) ? count($option['done']) / count($option['total']) * 100 : 0,
    //             'done' => $option['done'],
    //         ];


    //         if ( // all posts were obtained - stop proccess
    //             isset($status_result['pending'])
    //             && empty($status_result['pending'])
    //             && isset($status_result['completed'])
    //             && empty($status_result['completed'])
    //         ) {
    //             $clear_result = $this->seoaic->curl->init('/api/ai/posts/updates/clear', ['full' => false], true, true, true);

    //             $this->stopBackendPolling(self::EDIT_MODE);
    //             $this->massEditCheckingStatusUnLock(); // IMPORTANT: don't forget to unlock before any response

    //             $response_fields['status'] = 'complete';
    //             SEOAICAjaxResponse::alert('All posts have been updated.')->addFields($response_fields)->wpSend();
    //         }
    //     } catch (Exception $e) {
    //         $this->debugLog('Catch error: ', $e->getMessage());
    //     }

    //     $this->massEditCheckingStatusUnLock(); // IMPORTANT: don't forget to unlock before any response
    //     $response_fields['status'] = 'in_progress';

    //     SEOAICAjaxResponse::success()->addFields($response_fields)->wpSend();
    // }

    // private function massEditProccessFailedPosts($ids = [])
    // {
    //     if (empty($ids)) {
    //         return;
    //     }

    //     // make sure posts with obtained IDs exist
    //     $failed_post_ids = get_posts([
    //         'fields' => 'ids',
    //         'post_type' => 'any',
    //         'post_status' => 'any',
    //         'include' => $ids,
    //         'numberposts' => -1,
    //         'meta_query' => [
    //             'relation' => 'AND',
    //             [
    //                 'key' => 'seoaic_posted',
    //                 'value' => '1',
    //                 'compare' => '=',
    //             ],
    //             [
    //                 'key' => self::EDIT_STATUS_FIELD,
    //                 'value' => 'pending',
    //                 'compare' => '=',
    //             ],
    //         ],
    //     ]);

    //     if (!empty($failed_post_ids)) {
    //         $option = PostsEditLoader::getPostsOption();
    //         foreach ($failed_post_ids as $failed_post_id) {
    //             update_post_meta($failed_post_id, self::EDIT_STATUS_FIELD, 'failed');
    //             update_post_meta($failed_post_id, self::EDIT_STATUS_TIME_FIELD, time());
    //             $option['done'][] = $failed_post_id;
    //         }
    //         PostsEditLoader::setPostsOption($option);
    //     }
    // }

    // private function massEditProccessCompletedPosts($ids = [])
    // {
    //     if (empty($ids)) {
    //         return;
    //     }

    //     $option = PostsEditLoader::getPostsOption();

    //     // make sure posts with obtained IDs exist
    //     $completed_post_ids = get_posts([
    //         'fields' => 'ids',
    //         'post_type' => 'any',
    //         'post_status' => 'any',
    //         'include' => $ids,
    //         'numberposts' => self::PER_REQUEST__UPDATES_CONTENT,
    //         'meta_query' => [
    //             'relation' => 'AND',
    //             [
    //                 'key' => 'seoaic_posted',
    //                 'value' => '1',
    //                 'compare' => '=',
    //             ],
    //             [
    //                 'key' => self::EDIT_STATUS_FIELD,
    //                 'value' => 'pending',
    //                 'compare' => '=',
    //             ],
    //         ],
    //     ]);
    //     $data = [
    //         'post_ids' => $completed_post_ids
    //     ];

    //     $content_result = $this->seoaic->curl->init('/api/ai/posts/updates/content', $data, true, true, true);

    //     if (
    //         !empty($content_result)
    //         && is_array($content_result)
    //     ) {
    //         foreach ($content_result as $key => $updated_data) {
    //             if (
    //                 empty($updated_data)
    //                 || empty($updated_data['id'])
    //                 || empty($updated_data['content'])
    //                 || !in_array($updated_data['id'], $completed_post_ids)
    //             ) {
    //                 continue;
    //             }

    //             $post = wp_update_post([
    //                 'ID'            => $updated_data['id'],
    //                 'post_content'  => $updated_data['content'],
    //             ], true);

    //             if (is_wp_error($post)) {
    //                 $errors = $post->get_error_messages();
    //                 foreach ($errors as $error) {
    //                     $this->debugLog('[ERROR] Error on mass post updating: post #' . $updated_data['id'] . '; err: ' . print_r($error, true));
    //                 }
    //             } else {
    //                 update_post_meta($updated_data['id'], self::EDIT_STATUS_FIELD, 'completed');
    //                 update_post_meta($updated_data['id'], self::EDIT_STATUS_TIME_FIELD, time());
    //                 $option['done'][] = $updated_data['id'];
    //             }
    //         }

    //         PostsEditLoader::setPostsOption($option);
    //     }
    // }

    // public function massReviewProccessCompletedPosts($ids = [])
    // {
    //     if (empty($ids)) {
    //         return;
    //     }

    //     // make sure posts with obtained IDs exist
    //     $completed_post_ids = get_posts([
    //         'fields' => 'ids',
    //         'post_type' => 'any',
    //         'post_status' => 'any',
    //         'include' => $ids,
    //         'numberposts' => self::PER_REQUEST__UPDATES_CONTENT,
    //         'meta_query' => [
    //             'relation' => 'AND',
    //             [
    //                 'key' => 'seoaic_posted',
    //                 'value' => '1',
    //                 'compare' => '=',
    //             ],
    //             [
    //                 'key' => self::REVIEW_STATUS_FIELD,
    //                 'value' => 'reviewing',
    //                 'compare' => '=',
    //             ],
    //         ],
    //     ]);
    //     $data = [
    //         'post_ids' => $completed_post_ids
    //     ];

    //     $content_result = $this->seoaic->curl->init('/api/ai/posts/review/content', $data, true, true, true);

    //     if (
    //         !empty($content_result)
    //         && is_array($content_result)
    //     ) {
    //         $option = PostsReviewLoader::getPostsOption();

    //         foreach ($content_result as $key => $updated_data) {
    //             if (
    //                 empty($updated_data)
    //                 || empty($updated_data['id'])
    //                 || empty($updated_data['content'])
    //                 || !in_array($updated_data['id'], $completed_post_ids)
    //             ) {
    //                 continue;
    //             }

    //             update_post_meta($updated_data['id'], self::REVIEW_STATUS_FIELD, 'completed');
    //             update_post_meta($updated_data['id'], self::REVIEW_STATUS_TIME_FIELD, time());
    //             update_post_meta($updated_data['id'], 'seoaic_review_result_original', $updated_data['content']);
    //             $review_result = $this->makeReviewResult($updated_data['content']);
    //             update_post_meta($updated_data['id'], 'seoaic_review_result', $review_result);

    //             $option['done'][] = $updated_data['id'];
    //         }

    //         PostsReviewLoader::setPostsOption($option);
    //     }
    // }

    // private function makeReviewResult($str = '')
    // {
    //     $str = strtolower(trim($str, '.'));
    //     if (
    //         'yes' == $str
    //         || 'no' == $str
    //     ) {
    //         return $str;
    //     }

    //     return 'unknown';
    // }

    // public function postsMassEditStop()
    // {
    //     $posts = [];
    //     $query = new WP_Query([
    //         'posts_per_page' => -1,
    //         'post_type' => 'any',
    //         'meta_query' => [
    //             'relation' => 'AND',
    //             [
    //                 'key' => 'seoaic_posted',
    //                 'value' => '1',
    //                 'compare' => '=',
    //             ],
    //             [
    //                 'key' => self::EDIT_STATUS_FIELD,
    //                 'value' => 'pending',
    //                 'compare' => '=',
    //             ],
    //         ],
    //     ]);

    //     if ($query->have_posts()) {
    //         while ($query->have_posts()) {
    //             $query->the_post();

    //             $posts[] = [
    //                 'id' => get_the_ID(),
    //             ];
    //         }
    //     }

    //     foreach ($posts as $post) {
    //         update_post_meta($post['id'], self::EDIT_STATUS_FIELD, '');
    //         update_post_meta($post['id'], self::EDIT_STATUS_TIME_FIELD, '');
    //     }

    //     $result = $this->seoaic->curl->init('/api/ai/posts/updates/clear', ['full' => true], true, true, true);

    //     $this->debugLog();
    //     $this->stopEditProgress(); // stops backend polling
    //     $this->massEditCheckingStatusUnLock(); // IMPORTANT: don't forget to unlock before any response
    //     PostsEditLoader::deletePostsOption();

    //     SEOAICAjaxResponse::alert('Posts edit have been stopped.')->wpSend();
    // }



    // public function postsMassReviewCheckStatus()
    // {
    //     try {
    //         $review_in_progress = $this->isProcessingInProgress(self::REVIEW_MODE);
    //         if (!$review_in_progress) {
    //             $this->debugLog('!review_in_progress');
    //             $this->unregisterPostsCheckStatusCron(self::REVIEW_MODE);
    //             return;
    //         }

    //         if ($this->isMassReviewCheckingStatusLocked()) {
    //             $this->debugLog('is locked');
    //             return;
    //         } else {
    //             $this->massReviewCheckingStatusLock(); // to block simultaneous run
    //         }
    //         $this->debugLog('run');

    //         $status_result = $this->seoaic->curl->init('/api/ai/posts/review/status', [], true, true, true);
    //         $this->debugLog('status_result ', $status_result);

    //         if (
    //             !empty($status_result['failed'])
    //             && is_array($status_result['failed'])
    //         ) {
    //             //TODO: implement this
    //             // $this->massEditProccessFailedPosts($status_result['failed']);
    //         }

    //         if (
    //             !empty($status_result['completed'])
    //             && is_array($status_result['completed'])
    //         ) {
    //             $this->massReviewProccessCompletedPosts($status_result['completed']);
    //         }

    //         $option = PostsReviewLoader::getPostsOption();
    //         $response_fields = [
    //             'status' => '',
    //             'width' => !empty($option) ? count($option['done']) / count($option['total']) * 100 : 0,
    //             'done' => $option['done'],
    //         ];


    //         if ( // all posts were obtained - stop proccess
    //             isset($status_result['pending'])
    //             && empty($status_result['pending'])
    //             && isset($status_result['completed'])
    //             && empty($status_result['completed'])
    //         ) {
    //             $clear_result = $this->seoaic->curl->init('/api/ai/posts/review/clear', ['full' => false], true, true, true);

    //             $this->stopBackendPolling(self::REVIEW_MODE);
    //             $this->massReviewCheckingStatusUnLock(); // IMPORTANT: don't forget to unlock before any response

    //             $response_fields['status'] = 'complete';
    //             SEOAICAjaxResponse::alert('All posts have been reviewed.')->addFields($response_fields)->wpSend();
    //         }
    //     } catch (Exception $e) {
    //         $this->debugLog('Catch error: ', $e->getMessage());
    //     }

    //     $this->massReviewCheckingStatusUnLock(); // IMPORTANT: don't forget to unlock before any response
    //     $response_fields['status'] = 'in_progress';
    //     SEOAICAjaxResponse::success()->addFields($response_fields)->wpSend();
    // }

    // private function massReviewProccessFailedPosts($ids=[])
    // {
    //     if (empty($ids)) {
    //         return;
    //     }

    //     // make sure posts with obtained IDs exist
    //     $failed_post_ids = get_posts([
    //         'fields' => 'ids',
    //         'post_type' => 'any',
    //         'post_status' => 'any',
    //         'include' => $ids,
    //         'numberposts' => -1,
    //         'meta_query' => [
    //             'relation' => 'AND',
    //             [
    //                 'key' => 'seoaic_posted',
    //                 'value' => '1',
    //                 'compare' => '=',
    //             ],
    //             [
    //                 'key' => self::EDIT_STATUS_FIELD,
    //                 'value' => 'pending',
    //                 'compare' => '=',
    //             ],
    //         ],
    //     ]);

    //     if (!empty($failed_post_ids)) {
    //         $option = PostsEditLoader::getPostsOption();
    //         foreach ($failed_post_ids as $failed_post_id) {
    //             update_post_meta($failed_post_id, self::EDIT_STATUS_FIELD, 'failed');
    //             update_post_meta($failed_post_id, self::EDIT_STATUS_TIME_FIELD, time());
    //             $option['done'][] = $failed_post_id;
    //         }
    //         PostsEditLoader::setPostsOption($option);
    //     }
    // }

    // public function postsMassReviewStop()
    // {
    //     $posts = [];
    //     $query = new WP_Query([
    //         'posts_per_page' => -1,
    //         'post_type' => 'any',
    //         'meta_query' => [
    //             'relation' => 'AND',
    //             [
    //                 'key' => 'seoaic_posted',
    //                 'value' => '1',
    //                 'compare' => '=',
    //             ],
    //             [
    //                 'key' => self::REVIEW_STATUS_FIELD,
    //                 'value' => 'reviewing',
    //                 'compare' => '=',
    //             ],
    //         ],
    //     ]);

    //     if ($query->have_posts()) {
    //         while ($query->have_posts()) {
    //             $query->the_post();

    //             $posts[] = [
    //                 'id' => get_the_ID(),
    //             ];
    //         }
    //     }

    //     foreach ($posts as $post) {
    //         // update_post_meta($post['id'], self::REVIEW_STATUS_FIELD, '');
    //         // update_post_meta($post['id'], self::REVIEW_STATUS_TIME_FIELD, '');
    //         $this->resetReviewResults($post['id']);
    //     }

    //     $result = $this->seoaic->curl->init('/api/ai/posts/updates/clear', ['full' => true], true, true, true);

    //     $this->debugLog();
    //     $this->stopReviewProgress(); // stops backend polling
    //     $this->massReviewCheckingStatusUnLock(); // IMPORTANT: don't forget to unlock before any response

    //     SEOAICAjaxResponse::alert('Posts review have been stopped.')->wpSend();
    // }

    public function postsMassReviewClearBackgroundOption()
    {
        PostsReviewLoader::deletePostsOption();
        SEOAICAjaxResponse::success()->wpSend();
    }


    public function postsMassEditClearBackgroundOption()
    {
        PostsEditLoader::deletePostsOption();
        SEOAICAjaxResponse::success()->wpSend();
    }

    /**
     * Publish post
     */
    public function publish_post()
    {
        global $SEOAIC_OPTIONS;

        if (empty($_REQUEST['item_id'])) {
            wp_die();
        }

        $id = intval($_REQUEST['item_id']);

        $post_data = [
            'ID' => $id,
            'post_date' => date('Y-m-d H:i:s'),
            'post_date_gmt' => gmdate('Y-m-d H:i:s'),
            'post_status' => 'draft'
        ];

        $message = 'Post saved as draft!';
        switch ($_REQUEST['seoaic_post_status']) {
            case 'publish':
                $post_data['post_status'] = 'publish';
                $message = 'Post published!';
                break;
            case 'delay':
                $publish_delay = !empty($SEOAIC_OPTIONS['seoaic_publish_delay']) ? intval($SEOAIC_OPTIONS['seoaic_publish_delay']) : 0;

                if ($publish_delay > 0) {
                    $publish_time = time() + (3600 * $publish_delay);
                    $post_data['post_status'] = 'future';
                    $post_data['post_date'] = date('Y-m-d H:i:s', $publish_time);
                    $post_data['post_date_gmt'] = gmdate('Y-m-d H:i:s', $publish_time);
                    $message = 'Post scheduled!';
                }
                break;
        }

        wp_update_post($post_data);

        SEOAICAjaxResponse::alert($message)->wpSend();
    }

    /**
     * Save generated post
     */
    public function save_generated_post($request_data)
    {
        global $SEOAIC_OPTIONS;

        if (
            empty($request_data['ideaId'])
            || intval($request_data['ideaId']) != $request_data['ideaId']
        ) {
            //TODO: refactor
            echo 'error';
            wp_die();
        }

        $id = intval($request_data['ideaId']);
        $content = $request_data['content'];

        $post_data = [
            'ID' => $id,
            'post_type' => 'post',
            'post_date' => date('Y-m-d H:i:s'),
            'post_date_gmt' => gmdate('Y-m-d H:i:s'),
            'post_status' => 'publish'
        ];

        //TODO: move to translate
        // if ( !empty($_REQUEST['title']) ) {
        //     $post_data = sanitize_text_field($_REQUEST['title']);
        // }

        $publish_date = get_post_meta($id, 'seoaic_idea_postdate', true);
        if (!empty($publish_date)) {
            $post_data['post_status'] = 'future';
            $post_data['post_date'] = $publish_date;
            $post_data['post_date_gmt'] = gmdate('Y-m-d H:i:s', strtotime($publish_date));
        } else {
            $publish_delay = !empty($SEOAIC_OPTIONS['seoaic_publish_delay']) ? intval($SEOAIC_OPTIONS['seoaic_publish_delay']) : 0;

            if ($publish_delay > 0) {
                $publish_time = time() + (3600 * $publish_delay);
                $post_data['post_status'] = 'future';
                $post_data['post_date'] = date('Y-m-d H:i:s', $publish_time);
                $post_data['post_date_gmt'] = gmdate('Y-m-d H:i:s', $publish_time);
            }
        }

        $post_status = get_post_meta($id, 'seoaic_idea_poststatus', true);

        if ($post_status === 'draft') {
            $post_data['post_status'] = $post_status;
        }

        $post_content = !empty($content['content']) ? stripslashes($content['content']) : '';

        $title = '';
        if (preg_match('|<h1[^>]*?>(.*?)</h1>|si', $post_content, $arr)) {
            $title = $arr[1];
        }
        if($title) {
            $slug = sanitize_title($title);
            $post_data['post_title'] = $title;
            $post_data['post_name'] = $slug;
        }

        $post_content = $this->prepareGeneratedContent($post_content);
        $post_content = preg_replace('#<h1(.*?)>(.*?)</h1>#is', '', $post_content);
        $post_data['post_content'] = $post_content;
        // end h1 mods

        $thumbnail = sanitize_text_field($content['image']);

        $idea_content = get_post_meta($id, 'seoaic_idea_content', true);
        $idea_content = !empty($idea_content) ? json_decode($idea_content, true) : '';

        $post_data['post_type'] = !empty($idea_content['idea_post_type']) ? $idea_content['idea_post_type'] : SEOAIC_SETTINGS::getSEOAICPostType();

        if (!empty($content['frame'])) {
            $idea_frame = json_decode($content['frame'], true);
            $post_desc = !empty($idea_frame['description']) ? $idea_frame['description'] : '';
            $post_keywords = !empty($idea_frame['keywords']) ? array_shift($idea_frame['keywords']) : '';
        } else {
            $post_desc = !empty($idea_content['idea_description']) ? $idea_content['idea_description'] : '';
            $post_keywords = !empty($idea_content['idea_keywords']) ? array_shift($idea_content['idea_keywords']) : '';
        }

        $wpml_idea_settings = $this->seoaic->multilang->get_wpml_idea_settings($id, $post_data['post_type']);

        wp_update_post($post_data);

        $this->saveWordsCountMeta(get_post($id));

        if (false !== $wpml_idea_settings) {
            $this->seoaic->multilang->set_wpml_idea_settings($id, $wpml_idea_settings);
        }

        $this->debugLog('Meta Description: ', $post_desc);
        $this->debugLog('Focus Keyword: ', $post_keywords);

        update_post_meta($id, 'seoaic_posted', 1);
        update_post_meta($id, 'post_created_date', time());
        update_post_meta($id, '_yoast_wpseo_metadesc', $post_desc);
        update_post_meta($id, '_yoast_wpseo_focuskw', $post_keywords);
        update_post_meta($id, 'rank_math_description', $post_desc);
        update_post_meta($id, 'rank_math_focus_keyword', $post_keywords);

        $postTemplate = !empty($SEOAIC_OPTIONS['seoaic_post_template']) ? $SEOAIC_OPTIONS['seoaic_post_template'] : '';
        if (!empty($postTemplate)) {
            update_post_meta($id, '_wp_page_template', $postTemplate);
        }

        $language_code = $this->seoaic->multilang->get_post_language($id, 'code');
        $formData = get_post_meta($id, 'seoaic_ml_generated_data', true);

        $categories = false;
        if (!empty($idea_content['idea_category'])) {
            $categories = is_array($idea_content['idea_category']) ? $idea_content['idea_category'] : [$idea_content['idea_category']];

        } else {
            if (!empty($formData['categories'])) { // selected categories
                $categories = is_array($formData['categories']) ? $formData['categories'] : [$formData['categories']];
            } else { // default
                $defaultCategories = SEOAIC_SETTINGS::getPostsDefaultCategories();
                if (!empty($defaultCategories)) {
                    $categories = is_array($defaultCategories) ? $defaultCategories : [$defaultCategories];
                }
            }
        }

        if (!empty($categories)) {
            foreach ($categories as $key => $category) {
                $append = $key === 0 ? false : true;
                wp_set_post_terms($id, [
                    $this->seoaic->multilang->get_term_translation($category, $language_code)
                ], get_term($category)->taxonomy, $append);
            }
        }

        $attachment_id = false;
        if (!empty($content['image'])) {
            $image_url = $content['image'];

            //TODO use native WordPress function called media_sideload_image. Need to specify right encoding type in backend for S3 bucket.
            $attachment_id = SEOAIC::seoaicUploadFile($image_url, $thumbnail);

            set_post_thumbnail($id, $attachment_id);
        }

        $option = PostsGenerationLoader::getPostsOption();

        if (
            in_array($id, $option['total'])
            && !in_array($id, $option['done'])
        ) {
            $option['done'][] = $id;

            PostsGenerationLoader::setPostsOption($option);
        }

        if ($this->seoaic->multilang->is_multilang()) {
            $multilang_ideas = get_posts([
                'numberposts' => -1,
                'post_type' => 'seoaic-post',
                'post_status' => 'seoaic-idea',
                'fields' => 'ids',
                'meta_query' => [
                    'relation' => 'AND',
                    [
                        'key' => 'seoaic_ml_original_post',
                        'value' => $id,
                        'compare' => '='
                    ]
                ],
            ]);

            $option = PostsGenerationLoader::getPostsOption();
            $ideas_to_generate = [];

            foreach ($multilang_ideas as $idea) {
                if (false !== $attachment_id) {
                    set_post_thumbnail($idea, $attachment_id);
                }

                if (in_array($idea, $option['total'])) {
                    $ideas_to_generate[] = $idea;
                }
            }


            if (!empty($ideas_to_generate)) {
                // $this->seoaic->curl->init('api/schedule', $generated_data, true, false, true);

                $seoaicData = SEOAIC::getSEOAICData();
                $requestData = [
                    'knowledge_ids' => $formData['knowledge_ids'] ? [$formData['knowledge_ids']] : [],
                    'prompt'    => $formData['prompt'],
                    'domain'    => $formData['domain'],
                    'name'      => $seoaicData['name'],
                    'industry'  => $seoaicData['industry'],
                    'desc'      => $seoaicData['desc'],
                    'content_guidelines'    => $seoaicData['content_guidelines'],
                    //'pillar_links'  => $formData['pillar_links'],
                    'words_min'     => $formData['words_min'],
                    'words_max'     => $formData['words_max'],
                    'subtitles_min' => $formData['subtitles_min'],
                    'subtitles_max' => $formData['subtitles_max'],
                    'writing_style' => $seoaicData['writing_style'],
                    // 'manual_mass_thumb' => $thumb,
                    'ideas' => [],
                ];

                $ideasDataArray = [];
                foreach ($ideas_to_generate as $idea) {
                    delete_post_meta($idea, 'seoaic_generate_status');
                    list('data' => $idea_data, 'data_raw' => $data_raw) = $this->getItemDataById($idea);

                    $ideasDataArray[] = [
                        'idea_id'           => $idea,
                        'idea'              => $idea_data['idea'],
                        'idea_type'         => $idea_data['idea_type'],
                        'subtitles'         => $idea_data['subtitles'],
                        'keywords'          => $idea_data['keywords'],
                        'thumbnail'         => $idea_data['thumbnail'],
                        'thumbnail_generator' => $idea_data['thumbnail_generator'],
                        'language'          => $idea_data['language'],
                        'internal_links'    => $idea_data['internal_links'],
                        'pillar_links'    => $idea_data['pillar_links'],
                    ];
                }
                $requestData['ideas'] = $ideasDataArray;

                $this->debugLog('Mode: Generate (multilang); Request:', $requestData);

                $result = $this->seoaic->curl->init('api/ai/posts/generate', $requestData, true, false, true);
            }
        }
    }

    private function prepareGeneratedContent($content = '')
    {
        // Sometimes AI sends unnecessary notifications in to the content separated with "```" OR "---", so we cut them off with explode
        $content = explode("```", $content);
        $content = count($content) > 1 ? $content[1] : $content[0];
        // OR "---"
        $content = explode("---", $content);
        $content = count($content) > 1 ? $content[1] : $content[0];
        //Replacement of possible unnecessary parts from content
        //$content = preg_replace("/<([a-z][a-z0-9]*)[^>]*?(\/?)>/si", '<$1$2>', $content);
        $content = preg_replace('/^[^,]*<h1>\s*/', '<h1>', $content);
        $content = preg_replace("/<h(.+?)>subtitle:(.+?)/i", '<h$1>$2', $content);
        $content = preg_replace("/<h(.+?)>subtitle(.+?)/i", '<h$1>$2', $content);
        $content = preg_replace("/html$/i", "", $content);
        $content = preg_replace("/html$/i", "", $content);
        $content = preg_replace("/html\\\\n$/i", "", $content);
        $content = preg_replace("/html\\\\n/i", "", $content);
        $content = preg_replace("/plaintext\\\\n/i", "", $content);
        $content = str_replace("html", '', $content);
        $content = str_replace("html\n", '', $content);
        $content = str_replace("html\\n", '', $content);
        $content = str_replace("plaintext\n", '', $content);
        $content = preg_replace('#<meta(.*?)>#is', '', $content);
        $content = preg_replace('#<title(.*?)>(.*?)</title>#is', '', $content);
        $content = preg_replace('#<br>(.*?)<br>#is', '', $content);
        $content = preg_replace('#<br>#is', '', $content);
        $content = preg_replace('#<br>(.*?)lang="(.*?)"<br>#is', '', $content);
        $content = preg_replace('#</>#is', '', $content);

        return $content;
    }

    public function seoaic_filter_content($content)
    {
        return $this->prepareGeneratedContent($content);
    }

    public function postsMassGenerateCheckStatusManually()
    {
        $this->postsMassGenerateCheckStatus(true);
    }

    public function postsMassGenerateCheckStatus($manually = false)
    {
        try {
            $generate_in_progress = $this->isProcessingInProgress(self::GENERATE_MODE);
            if (!$generate_in_progress) {
                $this->debugLog('!generate_in_progress');
                $this->unregisterPostsCheckStatusCron(self::GENERATE_MODE);
                return;
            }

            if (!$manually) {
                if ($this->isMassGenerateCheckingStatusLocked()) {
                    $this->debugLog('is locked');
                    return;
                } else {
                    $this->massGenerateCheckingStatusLock(); // to block simultaneous run
                }
            }

            $this->debugLog($manually ? 'run manually' : 'run auto');

            // $result = $this->seoaic->curl->init('api/ai/schedule/check', [], true, false, true);
            $status_result = $this->seoaic->curl->init('api/ai/posts/generate/status', [], true, false, true);
            $this->debugLog('status_result ', $status_result);

            if (
                !empty($status_result['failed'])
                && is_array($status_result['failed'])
            ) {
                $this->massGenerateProccessFailedPosts($status_result['failed']);
            }

            if (
                !empty($status_result['completed'])
                && is_array($status_result['completed'])
            ) {
                $this->massGenerateProccessCompletedPosts($status_result['completed']);
            }

            if ( // all posts were obtained - stop proccess
                isset($status_result['pending'])
                && empty($status_result['pending'])
                && isset($status_result['completed'])
                && empty($status_result['completed'])
            ) {
                $clear_result = $this->seoaic->curl->init('/api/ai/posts/generate/clear', ['full' => false], true, true, true);

                $this->stopBackendPolling(self::GENERATE_MODE);
            }

            $option = PostsGenerationLoader::getPostsOption();
            $width = !empty($option['total']) ? count($option['done']) / count($option['total']) * 100 : 0;

            $data = [
                'status' => 'progress',
                'width' => $width,
                'posts' => $option['done'],
            ];

            if (!empty($option)) {
                $width = count($option['done']) / count(array_unique($option['total'])) * 100;
                $data['width'] = $width;

                // update_option('seoaic_background_post_generation', $option);
                if (100 == $width) {
                    $data['status'] = 'complete';
                }
            }

            $msg = '';

            if ('complete' === $data['status']) {
                if (
                    empty($_REQUEST['simple_post'])
                    || empty(intval($_REQUEST['simple_post']))
                ) {
                    $msg = 'Posts generated successful!';

                } else {
                    $data['post_content'] = $this->makePostContentField($data);
                }
            }
        } catch (Exception $e) {
            $this->debugLog('Catch error: ', $e->getMessage());
        }

        $this->massGenerateCheckingStatusUnLock(); // IMPORTANT: don't forget to unlock before any response

        SEOAICAjaxResponse::complete($msg)->addFields($data)->wpSend();
    }

    private function makePostContentField($data)
    {
        global $SEOAIC_OPTIONS;

        $idea_id = intval($_REQUEST['simple_post']);
        $post_content = get_post_field('post_content', $idea_id);

        $image_generator_box = '';
        $idea_content = get_post_meta($idea_id, 'seoaic_idea_content', true);
        if (!empty($idea_content)) {
            $idea_content = json_decode($idea_content, true);
        }

        $default_thumbnail_generator = !empty($SEOAIC_OPTIONS['seoaic_image_generator']) ? $SEOAIC_OPTIONS['seoaic_image_generator'] : 'no_image';
        $thumbnail_generator = !empty($idea_content['idea_thumbnail_generator']) ? $idea_content['idea_thumbnail_generator'] : $default_thumbnail_generator;
        $idea_thumb = !empty($idea_content['idea_thumbnail']) ? $idea_content['idea_thumbnail'] : '';

        if ($thumbnail_generator !== 'no_image') {
            $image_generators = seoaic_get_image_generators();

            $image_generator_box .= '
                <div class="generated-post-thumbnail">
                    <div class="holder">
                        ' . get_the_post_thumbnail($idea_id) . '
                    </div>

                    <div class="seoaic-module">
                    <input type="checkbox" name="regenerate_image" id="regenerate_image">
                    <label for="regenerate_image">Don\'t like the image? <span>Generate a new!</span>
                    <div class="info">
                        <span class="info-btn">?</span>
                            <div class="info-content">
                                <h4>Generate new image</h4>
                                <p>You can try regenerating image with another service if you are not satisfied with it.
                            </p>
                        </div>
                    </div>
                    </label>
                    <div class="selections">
                    <textarea class="promt-key">' . $idea_thumb . '</textarea>
                    <select class="seoaic-form-item form-select regenerate-select-modal" name="seoaic_regenerate-select-modal" required="">';

            foreach ($image_generators as $key => $image_generator) {
                if ($key === 'no_image') {
                    continue;
                }

                $selected = !empty($data['thumbnail_generator']) && $key === $data['thumbnail_generator'] ? ' selected' : '';
                $image_generator_box .= '<option value="' . $key . '"' . $selected . '>';
                $image_generator_box .=     $image_generator;
                $image_generator_box .= '</option>';
            }

            $image_generator_box .= '
                </select>
                    <div class="btn-sc">
                        <div class="info">
                            <span class="info-btn">?</span>
                            <div class="info-content">
                                <h4>Generate new image</h4>
                                <p>You can try regenerating image with another service if you are not satisfied with it.
                                </p>
                            </div>
                        </div>
                        <button data-callback="regenerate_image_modal" data-desc="' . $idea_thumb . '" data-action="seoaic_regenerate_image" data-type="modal" data-post="' . $idea_id . '" title="Regenerate image" type="button" class="button-primary seoaic-generate-image-button" data-action="seoaic_generate_ideas">New image
                        </button>
                    </div>
                    </div>
                    </div>
                </div>';
        }

        return '
            <div class="generated-post-box">
            ' . $image_generator_box . '
                <div class="generated-post-content">
                    ' . $post_content . '
                </div>
            </div>';
    }

    private function massGenerateProccessFailedPosts($ids = [])
    {
        if (empty($ids)) {
            return;
        }

        // make sure posts with obtained IDs exist
        $failed_post_ids = get_posts([
            'fields' => 'ids',
            'post_type' => 'seoaic-post',
            'post_status' => 'seoaic-idea',
            'include' => $ids,
            'numberposts' => -1,
        ]);

        if (!empty($failed_post_ids)) {
            $option = PostsGenerationLoader::getPostsOption();
            foreach ($failed_post_ids as $failed_post_id) {
                update_post_meta($failed_post_id, 'seoaic_generate_status', 'failed');
                $option['done'][] = $failed_post_id;
            }

            PostsGenerationLoader::setPostsOption($option);
        }
    }

    private function massGenerateProccessCompletedPosts($ids = [])
    {
        if (empty($ids)) {
            return;
        }

        // make sure ideas with obtained IDs exist
        $completed_ideas_ids = get_posts([
            'fields' => 'ids',
            'post_type' => 'seoaic-post',
            'post_status' => 'seoaic-idea',
            'include' => $ids,
            'numberposts' => self::PER_REQUEST__GENERATE_CONTENT,
        ]);


        if (empty($completed_ideas_ids)) {
            return;
        }

        $data = [
            'idea_ids' => $completed_ideas_ids
        ];

        $content_result = $this->seoaic->curl->init('/api/ai/posts/generate/content', $data, true, true, true);

        if (
            !empty($content_result)
            && is_array($content_result)
        ) {
            foreach ($content_result as $key => $generated_data) {
                if (
                    empty($generated_data)
                    || empty($generated_data['ideaId'])
                    || empty($generated_data['content'])
                    || !in_array($generated_data['ideaId'], $completed_ideas_ids)
                ) {
                    continue;
                }

                delete_post_meta($generated_data['ideaId'], 'seoaic_generate_status');
                $this->save_generated_post($generated_data);
            }
        }
    }


    /**
     * Remove background loading process
     */
    public function clearGenerationBackgroundOption()
    {
        PostsGenerationLoader::deletePostsOption();
        SEOAICAjaxResponse::success()->wpSend();
    }

    /**
     * Stop background loading process
     */
    public function postsMassGenerateStop()
    {
        $result = $this->seoaic->curl->init('api/ai/posts/generate/clear', ['full' => true], true, false, true);
        $this->debugLog('Clear result:', $result);
        $this->stopGenerateProgress(); // stops backend polling
        $this->massGenerateCheckingStatusUnLock(); // IMPORTANT: don't forget to unlock before any response

        $this->clearGenerationBackgroundOption();
    }

    /**
     * Image uploader
     */

    public static function seoaicImageUploader($save = false)
    {

        $a = false;
        $a .= '<div class="seoaic_image_uploader">

                    <div class="image-preview-wrapper">
                        <div class="top"></div>
                        <div class="bottom">
                            <a
                            href="#"
                            data-upl="' . esc_attr('Set image') . '"
                            class="remove_selected_image">

                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-circle" viewBox="0 0 16 16">
                                  <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                  <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                                </svg>

                            </a>
                            <a
                            href="#"
                            class="change_selected_image upload_image_button">' . esc_html('Change') . '</a>
                        </div>
                    </div>

                    <div class="uploader_buttons_wrapper">

                        <a href="#"
                        class="upload_image_button"
                        data-change="' . esc_attr('Change image') . '"
                        >' . esc_attr('Set image') . '</a>

                        <input
                        type="hidden"
                        name="seoaic_mass_set_thumbnail"
                        class="set_image_id seoaic-form-item"
                        value=""
                        data-thumb-id="">

                    </div>';

        if ($save) {
            $a .= '<input type="submit" name="submit_image_selector" value="Save" class="button-primary">';
        }

        $a .= '</div>';

        return $a;
    }

    public function get_schedule_posting_date($start_date)
    {
        global $SEOAIC_OPTIONS;

        $days = !empty($SEOAIC_OPTIONS['seoaic_schedule_days']) ? $SEOAIC_OPTIONS['seoaic_schedule_days'] : '';

        if (empty($days)) {
            return false;
        }

        $days_loop = true;
        $datetime = strtotime($start_date);
        $date = $start_date;

        while ($days_loop) {
            $_day = strtolower(date('l', $datetime));

            if (isset($days[$_day])) {
                $_posts = $days[$_day]['posts'];
                $post_types = seoaic_get_post_types();
                $post_types[] = 'seoaic-post';

                $_have_posted_idea = get_posts([
                    'numberposts' => 99,
                    'fields' => 'ids',
                    'post_type' => $post_types,
                    'post_status' => 'future',
                    'meta_query' => [
                        'relation' => 'OR',
                        [
                            'key' => 'seoaic_idea_postdate',
                            'value' => $date,
                            'compare' => 'LIKE'
                        ]
                    ]
                ]);

                if (count($_have_posted_idea) < $_posts) {
                    return $date . ' ' . date('H:i:s', strtotime($days[$_day]['time']));

                    $days_loop = false;
                } else {

                    $datetime = strtotime($date . ' +1 day');
                    $date = date('Y-m-d', $datetime);
                }
            } else {

                $datetime = strtotime($date . ' +1 day');
                $date = date('Y-m-d', $datetime);
            }

        }
    }


    /**
     * Custom meta for gutenberg sidebar
     */
    public function seoaic_register_post_meta()
    {

        register_post_meta('', '_improvement_type_select', [
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'auth_callback' => function () {
                return true;
            },
            'default' => 'improve_an_existing'
        ]);

        register_post_meta('', '_thumb_yes_seoaic', [
            'show_in_rest' => true,
            'single' => true,
            'type' => 'boolean',
            'auth_callback' => function () {
                return true;
            }
        ]);

        register_post_meta('', '_frame_yes_seoaic', [
            'show_in_rest' => true,
            'single' => true,
            'type' => 'boolean',
            'auth_callback' => function () {
                return true;
            }
        ]);

        // thumb generate prompt
        register_post_meta('', 'seoaic_generate_description', [
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'auth_callback' => function () {
                return true;
            }
        ]);

        // improve Instructions Prompt
        register_post_meta('', 'seoaic_improve_instructions_prompt', [
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'auth_callback' => function () {
                return true;
            }
        ]);

        // Previously backup content before an improvement
        register_post_meta('', 'seoaic_rollback_content_improvement', [
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'auth_callback' => function () {
                return true;
            }
        ]);

        register_post_meta('', 'seoaic_idea_thumbnail_generator', [
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'auth_callback' => function () {
                return true;
            }
        ]);

        register_post_meta('', 'seoaic_article_description', [
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'auth_callback' => function () {
                return true;
            }
        ]);

        //        register_post_meta( '', 'seoaic_article_subtitles', [
        //            'show_in_rest' => true,
        //            'single' => true,
        //            'type' => 'array',
        //            'auth_callback' => function() {
        //                return true;
        //            }
        //        ] );

        register_post_meta('post', 'seoaic_article_subtitles', [
            'show_in_rest' => [
                'schema' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                ],
            ],
            'single' => true,
            'type' => 'array',
        ]);

        register_post_meta('post53', 'seoaic_article_keywords', [
            'show_in_rest' => [
                'schema' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                ],
            ],
            'single' => true,
            'type' => 'array',
        ]);
    }

    /**
     * @return string
     */
    private function getInternalLinks($ideaId = null, $n = 5)
    {
        $posts = [];

        if (
            !empty($ideaId)
            && is_numeric($ideaId)
            && !empty(SEOAIC_SETTINGS::getGenerateInternalLinks())
        ) {
            $getKeywordsPageLinks = function ($keywords) {
                return array_map(
                    function ($mapItem) {
                        return $mapItem['page_link'];
                    },
                    array_filter($keywords, function ($filterItem) {
                        return !empty($filterItem['page_link']) && is_numeric($filterItem['page_link']);
                    })
                );
            };

            $ideaKeywords_WPPosts = KeywordsPostsRelation::getKeywordsByPostId($ideaId);
            $ideaKeywords = $this->seoaic->keywords->convertFormatFromPostsToKeywords($ideaKeywords_WPPosts);
            $keywordsPagesLinksIds = $getKeywordsPageLinks($ideaKeywords);

            // 1-2 Keyword's Page links
            if (!empty($keywordsPagesLinksIds)) {
                if (2 < count($keywordsPagesLinksIds)) {
                    shuffle($keywordsPagesLinksIds);
                    $keywordsPagesLinksIds = array_slice($keywordsPagesLinksIds, 0, 2);
                }

                $keywordsPagesLinksPosts = get_posts([
                    'post_type'     => 'any',
                    'numberposts'   => -1,
                    'lang'          => '',
                    'include'       => $keywordsPagesLinksIds,
                ]);
                $posts = array_merge($posts, $keywordsPagesLinksPosts);
            }

            // 3-4 Related Posts
            $ideaLanguage = $this->seoaic->multilang->get_post_language($ideaId);
            $ideaLanguageCode = $this->seoaic->multilang->get_post_language($ideaId, 'code');
            $relatedPostsAll = KeywordsPostsRelation::getRelatedPosts($ideaId);

            $relatedPosts = array_filter($relatedPostsAll, function ($item) use ($ideaLanguage) {
                $lang = $this->seoaic->multilang->get_post_language($item->ID);
                return $lang == $ideaLanguage;
            });
            if (!empty($relatedPosts)) {
                shuffle($relatedPosts);
                $posts = array_merge($posts, $relatedPosts);

                if ($n < count($posts)) {
                    $posts = array_slice($posts, 0, $n);
                }
            }

            if (0 == count($posts)) {
                $posts = array_merge($posts, $this->getRandomPosts($n, [], $ideaLanguageCode));
            }
            // if not enough related posts - add random
            // if ($n > count($posts)) {
            //     $diffN = $n - count($posts);
            //     $randomPosts = $this->getRandomPosts($diffN, array_map(function ($item) {
            //         return $item->ID;
            //     }, $posts));
            //     $posts = array_merge($posts, $randomPosts);
            // }
            return $this->makePostsPermalinkAndTitleString($posts);
        }

        return '';
    }

    private function getRandomPosts($n = 5, $excludeIDs = [], $lang = ''): array
    {
        $args = array(
            'post_type' => 'any',
            'post_status' => 'publish',
            'lang' => $lang,
            'numberposts' => $n,
            'orderby' => 'rand',
            'meta_query' => [
                [
                    'key' => 'seoaic_posted',
                    'value' => 1,
                    'compare' => '='
                ]
            ]
        );

        if (!empty($excludeIDs)) {
            if (!is_array($excludeIDs)) {
                $excludeIDs = [$excludeIDs];
            }
            $args['post__not_in'] = $excludeIDs;
        }

        $similar_posts = get_posts($args);
        $similar_posts_query = $this->seoaic->multilang->sort_posts_by_languages($similar_posts);

        if (!empty($similar_posts_query)) {
            return $similar_posts_query;
        }

        return [];
    }

    /**
     * @param stdClass[] $posts array of WP posts
     */
    private function makePostsPermalinkAndTitleString($posts = []): string
    {
        $links = [];

        if (!empty($posts)) {
            foreach ($posts as $post) {
                $links[] = get_permalink($post->ID) . " - " . get_the_title($post->ID);
            }

            return implode(', ', $links) . '.';
        }

        return '';
    }

    private function getPillarLinks($lang = '')
    {
        global $SEOAIC_OPTIONS, $SEOAIC;
        $pillar_links = [];

        if (!empty($SEOAIC_OPTIONS['seoaic_pillar_links']) && is_array($SEOAIC_OPTIONS['seoaic_pillar_links']) && $SEOAIC_OPTIONS['seoaic_pillar_link_action']) {
            foreach ($SEOAIC_OPTIONS['seoaic_pillar_links'] as $item) {
                if (is_array($item)) {
                    if ($SEOAIC->multilang->is_multilang() && $lang) {
                        if ($item['lang'] === $lang) {
                            $pillar_links[] = $item['url'] . " - " . $item['text'];
                        }
                    } else {
                        $pillar_links[] = $item['url'] . " - " . $item['text'];
                    }
                }
            }

            if (!empty($pillar_links)) {
                return implode(', ', $pillar_links) . '.';
            }
            return '';
        }

        return '';
    }

    public static function seoaic_dropdown_cats($taxonomy, $category, $class = 'seoaic-form-item form-select', $id = 'seoaic-category', $name = 'seoaic_category')
    {
        return wp_dropdown_categories(
            [
                'echo' => false,
                'taxonomy' => $taxonomy,
                'hide_if_empty' => false,
                'show_option_none' => 'Select category',
                'hide_empty' => '0',
                'show_option_all' => '',
                'value_field' => '',
                'selected' => $category,
                'id' => $id,
                'class' => $class,
                'name' => $name,
                'required' => true,
            ]
        );
    }

    public static function getCategoriesOfPosttype()
    {

        global $SEOAIC_OPTIONS;

        $post_type = "post";

        if (empty($_REQUEST['post_type'])) {
            wp_die();
        } else {
            $post_type = $_REQUEST['post_type'];
        }

        $html = seoaic_get_categories($post_type);

        wp_send_json(
            [
                "select" => $html,
            ]
        );



        $taxonomy = get_object_taxonomies($post_type);
        $taxonomy = $taxonomy[0] === "language" ? ($taxonomy[1] === "post_translations" ? ($taxonomy[2] === "product_type" ? "product_cat" : $taxonomy[2]) : $taxonomy[1]) : $taxonomy[0];

        $separator = ', ';

        if (!empty($taxonomy) && !is_wp_error($taxonomy)) {

            $html = '';

            $terms = self::seoaic_dropdown_cats($taxonomy, $SEOAIC_OPTIONS["seoaic_default_category"], 'seoaic-form-item form-select mb-5', 'seoaic_default_category', 'seoaic_default_category');

            $html .= '<div class="col-12 col-12 seoaic-select-post-type-cat"><label for="seoaic_default_category">Default posts category</label>';
            $html .= $terms;
            $html .= '</div>';

            wp_send_json(
                [
                    "select" => $html,
                    "taxonomy" => $taxonomy,
                    "selected" => $SEOAIC_OPTIONS["seoaic_default_category"],
                ]
            );
        }

        wp_die();
    }

    public static function selectCategoriesIdea()
    {
        global $SEOAIC_OPTIONS;

        $category = !empty($SEOAIC_OPTIONS["seoaic_default_category"]) ? $SEOAIC_OPTIONS["seoaic_default_category"] : 0;

        $id = 0;
        if (!empty($_REQUEST['idea_post_id'])) {
            $id = $_REQUEST['idea_post_id'];
            $category = get_post_meta($id, 'seoaic_idea_content');
            $category = !empty(json_decode($category[0])->idea_category) ? json_decode($category[0])->idea_category : $SEOAIC_OPTIONS["seoaic_default_category"];

            $post_type = get_post_meta($id, 'seoaic_idea_content');
            $post_type = !empty(json_decode($post_type[0])->idea_post_type) ? json_decode($post_type[0])->idea_post_type : $_REQUEST['post_type'];
        }

        $post_type = !empty($_REQUEST['post_type']) ? $_REQUEST['post_type'] : "post";

        $html = seoaic_get_categories($post_type, $id);

        wp_send_json(
            [
                "select" => $html,
                "idea" => !empty($id) ? $id : '',
                "post_type" => !empty($_REQUEST['post_type']) ? $_REQUEST['post_type'] : '',
            ]
        );

        $taxonomy = get_object_taxonomies($post_type);
        $taxonomy = $taxonomy[0] === "language" ? ($taxonomy[1] === "post_translations" ? ($taxonomy[2] === "product_type" ? "product_cat" : $taxonomy[2]) : $taxonomy[1]) : $taxonomy[0];

        $separator = ', ';

        if (!empty($taxonomy) && !is_wp_error($taxonomy)) {

            $html = '';

            $terms = self::seoaic_dropdown_cats($taxonomy, $category);

            $html .= '<div id="seoaic-idea-content-category" class="seoaic-idea-content-section">
                      <div class="top">
                          <span class="seoaic-section-idea-title">Category</span><span class="icon"></span>
                      </div>
                      <div class="choose-label mb-19">Select a category</div>
                      <div class="choose-switchers">';
            $html .= $terms;
            $html .= '</div></div>';

            wp_send_json(
                [
                    "select" => $html,
                    "taxonomy" => $taxonomy,
                    "selected" => $SEOAIC_OPTIONS["seoaic_default_category"],
                    "idea" => !empty($id) ? $id : '',
                    "post_type" => isset($_REQUEST['post_type']) ? $_REQUEST['post_type'] : '',
                ]
            );
        }

        wp_die();
    }

    public function countContentWords($post_id, $post)
    {
        if (
            wp_is_post_revision($post_id)
            || SEOAIC_IDEAS::IDEA_TYPE == $post->post_type // ideas - skip
        ) {
            return;
        }

        $seoaicPosted = get_post_meta($post_id, 'seoaic_posted', true);
        if (1 == $seoaicPosted) {
            $this->saveWordsCountMeta($post);

            $this->updateAllPostsWordsCount();
        }
    }

    public function setCreatedDate($post_id, $post)
    {
        if (
            wp_is_post_revision($post_id)
            || SEOAIC_IDEAS::IDEA_TYPE == $post->post_type  // ideas - skip
        ) {
            return;
        }

        $seoaicPosted = get_post_meta($post_id, 'seoaic_posted', true);
        if (1 == $seoaicPosted) {
            $postCreatedDate = get_post_meta($post_id, 'post_created_date', true);
            if (empty($postCreatedDate)) {
                update_post_meta($post_id, 'post_created_date', time());
            }
        }
    }

    private function updateAllPostsWordsCount()
    {
        $allSEOAICPosts = get_posts([
            'numberposts'   => -1,
            'post_type'     => 'any',
            'post_status' => 'any',
            'meta_query'    => [
                'relation'  => 'AND',
                [
                    'key'       => 'seoaic_posted',
                    'value'     => '1',
                    'compare'   => '=',
                ],
                [
                    'key'       => self::WORDS_COUNT_FIELD,
                    'compare'   => 'NOT EXISTS',
                ],
            ],
        ]);

        foreach ($allSEOAICPosts as $post) {
            if (SEOAIC_IDEAS::IDEA_TYPE != $post->post_type) {
                $this->saveWordsCountMeta($post);
            }
        }
    }

    private function saveWordsCountMeta($post)
    {
        $content = strip_tags($post->post_content);
        $wordsCount = str_word_count($content, 0);
        update_post_meta($post->ID, self::WORDS_COUNT_FIELD, $wordsCount);
    }

    public static function getMaxWordsCount()
    {
        global $wpdb;
        $query = "SELECT max(meta_value) FROM {$wpdb->prefix}postmeta WHERE meta_key='".self::WORDS_COUNT_FIELD."'";
        $the_max = $wpdb->get_var($query);

        return $the_max;
    }


    public function unregisterCrons()
    {
        $this->debugLog('[CRON]');
        $this->unregisterPostsCheckStatusCron(self::GENERATE_MODE);
        // $this->unregisterPostsCheckStatusCron(self::EDIT_MODE);
        // $this->unregisterPostsCheckStatusCron(self::REVIEW_MODE);
        (new PostsMassEdit($this->seoaic))->unregisterPostsCheckStatusCron();
        (new PostsMassReview($this->seoaic))->unregisterPostsCheckStatusCron();
        (new PostsMassTranslate($this->seoaic))->unregisterPostsCheckStatusCron();
    }

    private function registerPostsGenerateCheckStatusCron()
    {
        $this->debugLog('[CRON]');
        if (!wp_next_scheduled('seoaic_posts_generate_check_status_cron_hook')) {
            wp_schedule_event(time() + 3 * 60, '5_minutes', 'seoaic_posts_generate_check_status_cron_hook');
        }
    }

    private function unregisterPostsGenerateCheckStatusCron()
    {
        $this->debugLog('[CRON]');
        $timestamp = wp_next_scheduled('seoaic_posts_generate_check_status_cron_hook');
        wp_unschedule_event($timestamp, 'seoaic_posts_generate_check_status_cron_hook');
    }

    // private function registerPostsEditCheckStatusCron()
    // {
    //     $this->debugLog('[CRON]');
    //     if (!wp_next_scheduled('seoaic_posts_edit_check_status_cron_hook')) {
    //         wp_schedule_event(time(), '5_minutes', 'seoaic_posts_edit_check_status_cron_hook');
    //     }
    // }

    // private function unregisterPostsEditCheckStatusCron()
    // {
    //     $this->debugLog('[CRON]');
    //     $timestamp = wp_next_scheduled('seoaic_posts_edit_check_status_cron_hook');
    //     wp_unschedule_event($timestamp, 'seoaic_posts_edit_check_status_cron_hook');
    // }

    // private function registerPostsReviewCheckStatusCron()
    // {
    //     $this->debugLog('[CRON]');
    //     if (!wp_next_scheduled('seoaic_posts_review_check_status_cron_hook')) {
    //         wp_schedule_event(time(), '5_minutes', 'seoaic_posts_review_check_status_cron_hook');
    //     }
    // }

    // private function unregisterPostsReviewCheckStatusCron()
    // {
    //     $this->debugLog('[CRON]');
    //     $timestamp = wp_next_scheduled('seoaic_posts_review_check_status_cron_hook');
    //     wp_unschedule_event($timestamp, 'seoaic_posts_review_check_status_cron_hook');
    // }



    public function add_cron_interval($schedules)
    {
        $schedules['5_minutes'] = [
            'interval' => 5 * 60,
            'display'  => esc_html__('Every 5 minutes'),
        ];
        $schedules['30_seconds'] = [
            'interval' => 30,
            'display'  => esc_html__('Every 30 seconds'),
        ];

        return $schedules;
    }

    public function cronPostsGenerateCheckStatus()
    {
        $this->debugLog('[CRON] cronExec');
        $this->postsMassGenerateCheckStatus();

        return;
    }

    // public function cronPostsEditCheckStatus()
    // {
    //     $this->debugLog('[CRON] cronExec');
    //     $this->postsMassEditCheckStatus();

    //     return;
    // }

    // public function cronPostsReviewCheckStatus()
    // {
    //     $this->debugLog('[CRON] cronExec');
    //     $this->postsMassReviewCheckStatus();

    //     return;
    // }

    public function title_like_posts_where($where, $wp_query)
    {
        global $wpdb;

        if ($post_title_like = $wp_query->get('post_title_like')) {
            $where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'%' . esc_sql($wpdb->esc_like(urldecode($post_title_like))) . '%\'';
        }

        return $where;
    }

    public function postsMassEditAjax()
    {
        $instance = new PostsMassEdit($this->seoaic);
        $this->postsMassActionRun($instance);
    }

    public function postsMassEditCheckStatusAjax()
    {
        $instance = new PostsMassEdit($this->seoaic);
        $this->postsMassActionCheckStatus($instance);
    }

    public function postsMassEditStopAjax()
    {
        $instance = new PostsMassEdit($this->seoaic);
        $this->postsMassActionStop($instance);
    }


    public function resetReviewResults($post_id)
    {
        if (1 == get_post_meta($post_id, 'seoaic_posted', true)) {
            //reset all review data
            (new PostsMassReview($this->seoaic))->resetReviewResults($post_id);
        }
    }

    public function postsMassReviewAjax()
    {
        $instance = new PostsMassReview($this->seoaic);
        $this->postsMassActionRun($instance);
    }

    public function postsMassReviewCheckStatusAjax()
    {
        $instance = new PostsMassReview($this->seoaic);
        $this->postsMassActionCheckStatus($instance);
    }

    public function postsMassReviewStopAjax()
    {
        $instance = new PostsMassReview($this->seoaic);
        $this->postsMassActionStop($instance);
    }


    public function postsMassTranslateAjax()
    {
        $instance = new PostsMassTranslate($this->seoaic);
        $this->postsMassActionRun($instance);
    }


    public function postsMassActionRun($instance)
    {
        try {
            $data = $instance->prepareData($_REQUEST);
            $result = $instance->sendActionRequest($data);

            if ($instance->processActionResults($result)) {
                $errors = $instance->getErrors();
                if (empty($errors)) {
                    SEOAICAjaxResponse::success($instance->successfullRunMessage)->wpSend();
                } else {
                    SEOAICAjaxResponse::alert("Run with some errors:<br>".$errors)->wpSend();
                }
            }
        } catch (Exception $e) {
            SEOAICAjaxResponse::error($e->getMessage())->wpSend();
        }

        SEOAICAjaxResponse::error('Something went wrong')->wpSend();
    }

    public function postsMassTranslateCheckStatusAjax()
    {
        $instance = new PostsMassTranslate($this->seoaic);

        $this->postsMassActionCheckStatus($instance);
    }

    public function postsMassActionCheckStatus($instance)
    {
        list('done' => $done, 'failed' => $failed, 'is_running' => $isRunning) = $instance->getStatusResults();
        $status = $isRunning ? 'in progress' : 'complete';
        $message = $isRunning ? '' : $instance->completeMessage;

        SEOAICAjaxResponse::success($message)->addFields([
            'status'    => $status,
            'done'      => $done,
            'failed'    => $failed,
        ])->wpSend();
    }

    public function postsMassActionStop($instance)
    {
        $instance->stop();

        SEOAICAjaxResponse::alert($instance->stopMessage)->wpSend();
    }


    public function massGenerateSavePromptTemplate()
    {
        if (empty($_POST['id'])) {
            SEOAICAjaxResponse::error('Wrong template ID');
        }

        if (empty($_POST['text'])) {
            SEOAICAjaxResponse::error('Text is empty');
        }

        $templates = SEOAIC_SETTINGS::getPostsGeneratePromptTemplates();
        if (
            !empty($templates)
            && is_array($templates)
        ) {
            foreach ($templates as $i => &$template) {
                if ($i == $_POST['id']) {
                    $template = esc_html($_POST['text']);
                }
            }

            SEOAIC_SETTINGS::setPostsGeneratePromptTemplates($templates);
        }

        SEOAICAjaxResponse::success()->wpSend();
    }

    public function massGenerateDeletePromptTemplate()
    {
        if (empty($_POST['id'])) {
            SEOAICAjaxResponse::error('Wrong template ID');
        }

        $templates = SEOAIC_SETTINGS::getPostsGeneratePromptTemplates();
        if (
            !empty($templates)
            && is_array($templates)
            && isset($templates[$_POST['id']])
        ) {
            unset($templates[$_POST['id']]);

            SEOAIC_SETTINGS::setPostsGeneratePromptTemplates($templates);
        }

        SEOAICAjaxResponse::success()->wpSend();
    }
}
