<?php

namespace SEOAIC;

use SEOAIC\DB\KeywordsPostsTable;
use SEOAIC\relations\KeywordsPostsRelation;

class SEOAIC_IDEAS
{
    public const IDEA_TYPE = 'seoaic-post';
    public const IDEA_STATUS = 'seoaic-idea';
    private $seoaic;
    private $generatedIdeasIDs;

    function __construct ( $_seoaic )
    {
        $this->seoaic = $_seoaic;
        $this->generatedIdeasIDs = [];

        add_action('wp_ajax_seoaic_add_idea', [$this, 'add_idea']);
        add_action('wp_ajax_seoaic_edit_idea', [$this, 'edit_idea']);
        add_action('wp_ajax_seoaic_remove_idea', [$this, 'remove_idea']);
        add_action('wp_ajax_seoaic_get_idea_content', [$this, 'get_idea_content']);
        add_action('wp_ajax_seoaic_save_content_idea', [$this, 'save_content_idea']);
        add_action('wp_ajax_seoaic_remove_idea_posting_date', [$this, 'remove_idea_posting_date']);
        add_action('wp_ajax_seoaic_generate_ideas', [$this, 'generate'], 2);
        add_action('wp_ajax_seoaic_generate_ideas_new_keywords', [$this, 'generateIdeasNewKeywords'], 2);
        add_action('wp_ajax_seoaic_get_blog_settings', [$this, 'blog_idea_settings'], 2);
        add_action('wp_ajax_nopriv_seoaic_get_blog_settings', [$this, 'blog_idea_settings'], 2);
        add_action('wp_ajax_seoaic_Update_credits_real_time', [$this, 'Update_credits_real_time'], 2);

    }

    /**
     * Checks if provided entity is Idea
     * @param object|array @entity Post
     * @return bool
     */
    public static function isIdea($entity): bool
    {
        if (is_array($entity)) {
            return !empty($entity['post_type'])
                && self::IDEA_TYPE == $entity['post_type']
                && !empty($entity['post_status'])
                && self::IDEA_STATUS == $entity['post_status'];
        }

        return !empty($entity->post_type)
            && self::IDEA_TYPE == $entity->post_type
            && !empty($entity->post_status)
            && self::IDEA_STATUS == $entity->post_status;
    }

    /**
     * Ajax action - add idea
     */
    public function add_idea( $args = [] )
    {

        $defaults = [
            'name'      => $_REQUEST['item_name'] ?? '',
            'language'  => $_REQUEST['seoaic_ml_language'] ?? '',
            'post_type' => 'seoaic-post',
            'parent_id' => $_REQUEST['seoaic-multilanguage-parent-id'] ?? 0,
            'return'    => false,
            'multi'     => true,
        ];

        $parsed_args = wp_parse_args( $args, $defaults );

        if (empty($parsed_args['name'])) {
            wp_die();
        }

        $title = stripslashes(sanitize_textarea_field($parsed_args['name']));

        $title = explode("\n", $title);
        $message = '';

        foreach ( $title as $_title ) {

            $id = wp_insert_post([
                'post_title' => $_title,
                'post_type' => 'seoaic-post',
                'post_status' => 'seoaic-idea',
            ]);

            $this->seoaic->multilang->add_new_idea_manually($id, $parsed_args);

            if ( $parsed_args['return'] ) {
                return $id;
            }

            $message .= '<p>Idea <b>#' . $id . '</b> <span class="gray">«' . $_title . '»</span> has been added!</p>';
        }

        wp_send_json( [
            'status'  => 'alert',
            'message' => $message,
        ] );
    }

    /**
     * Ajax action - edit (update) idea
     */
    public function edit_idea ( $args = [] )
    {
        if (!current_user_can('seoaic_edit_plugin')) {
            wp_die();
        }

        $defaults = [
            'id'        => $_REQUEST['item_id'] ?? 0,
            'name'      => $_REQUEST['item_name'] ?? '',
            'language'  => $_REQUEST['seoaic_ml_language'] ?? '',
            'post_type' => 'seoaic-post',
            'parent_id' => $_REQUEST['seoaic-multilanguage-parent-id'] ?? 0,
            'return'    => false,
            'multi'     => true,
        ];

        $parsed_args = wp_parse_args( $args, $defaults );


        if (empty($parsed_args['name']) || empty($parsed_args['id'])) {
            wp_die();
        }

        $title = stripslashes(sanitize_text_field($parsed_args['name']));
        $id = intval($parsed_args['id']);

        wp_update_post([
            'post_title' => $title,
            'ID' => $id,
        ]);

        $this->seoaic->multilang->add_new_idea_manually($id, $parsed_args);

        wp_send_json( [
            'status'  => 'success',
            'message' => 'Idea #' . $id . ' updated!',
        ] );
    }

    /**
     * Ajax action - remove idea
     */
    public function remove_idea()
    {
        if (!current_user_can('seoaic_edit_plugin')) {
            wp_die();
        }

        if (empty($_REQUEST['item_id']) && empty($_REQUEST['idea-mass-create']) ) {
            wp_die();
        }

        if ( $_REQUEST['item_id'] === 'all' ) {
            $this->remove_all_ideas();
        }

        if ( !empty($_REQUEST['item_id']) ) {
            $ids = [$_REQUEST['item_id']];
        } else {
            $ids = is_array($_REQUEST['idea-mass-create']) ? $_REQUEST['idea-mass-create'] : [$_REQUEST['idea-mass-create']];
        }
        $message = '';

        foreach ( $ids as $id ) {
            $id = intval($id);
            $title = get_the_title($id);
            $isIdea = get_post_type($id) === 'seoaic-post';

            if ( !$isIdea ) {
                $message .= '<p>Idea <b>#' . $id . '</b> not exists!</p>';
            } else {
                $deleteResult = wp_delete_post($id, true);

                if ($deleteResult) {
                    KeywordsPostsRelation::deleteByPostID($id);
                }
                $message .= '<p>Idea <b>#' . $id . '</b> <span class="gray">«' . $title . '»</span> removed!</p>';
            }
        }

        wp_send_json( [
            'status'  => 'success',
            'message' => $message,
        ] );
    }

    /**
     * Ajax action - remove all ideas
     */
    public function remove_all_ideas()
    {
        $args = [
            'numberposts' => -1,
            'post_type' => 'seoaic-post',
            'post_status' => 'seoaic-idea',
        ];
        $schedule_too = false;
        if (!empty($_REQUEST['schedule_too']) && $_REQUEST['schedule_too'] === '1') {
            $schedule_too = true;
        }

        $ideas = get_posts($args);

        foreach ($ideas as $idea) {
            if (!$schedule_too) {
                $post_date = get_post_meta($idea->ID, 'seoaic_idea_postdate', true);
                if (!empty($post_date)) {
                    continue;
                }
            }

            $deleteResult = wp_delete_post($idea->ID, true);

            if ($deleteResult) {
                KeywordsPostsRelation::deleteByPostID($idea->ID);
            }
        }

        wp_send_json( [
            'status'  => 'alert',
            'message' => 'All ideas have been removed!',
        ] );
    }

    /**
     * Ajax action - get idea content
     */
    public function get_idea_content()
    {

        global $SEOAIC_OPTIONS;

        if (empty($_REQUEST['item_id'])) {
            wp_die();
        }

        $id = intval($_REQUEST['item_id']);

        $idea_content = get_post_meta($id, 'seoaic_idea_content', true);
        if ( !empty($idea_content) ) {
            $idea_content = json_decode($idea_content, true);
        }

        $idea_categories = !empty($idea_content['idea_post_type']) ? seoaic_get_categories($idea_content['idea_post_type'], $id) : seoaic_get_categories( (!empty($SEOAIC_OPTIONS['seoaic_post_type']) ? $SEOAIC_OPTIONS['seoaic_post_type'] : 'post') );

        wp_send_json([
            'status'  => 'success',
            'content' => [
                'idea_name'     => get_the_title($id),
                'idea_content'  => $idea_content,
                'idea_postdate' => str_replace([' ', ':00'], ['T', ''], get_post_meta($id, 'seoaic_idea_postdate', true)),
                'idea_categories' => $idea_categories
            ]
        ], null, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Ajax action - save idea content
     */
    public function save_content_idea()
    {
        if (empty($_REQUEST['id'])) {
            wp_send_json([
                'status' => 'error',
                'message' => 'Empty data!',
            ]);
        }

        $id = intval($_REQUEST['id']);

        if (!empty($_REQUEST['idea_post_date'])) {
            $idea_postdate = str_replace('T', ' ', sanitize_text_field($_REQUEST['idea_post_date'])) . ':00';

            if ($idea_postdate < date('Y-m-d H:i:s')) {
                wp_send_json([
                    'status' => 'error',
                    'message' => 'It can`t be the past date!',
                ]);
            }

        } else {
            $idea_postdate = NULL;
        }

        if (isset($_REQUEST['idea_content'])) {
            $idea_content = sanitize_text_field($_REQUEST['idea_content']);
            update_post_meta($id, 'seoaic_idea_content', $idea_content);
        }

        $this->update_ideas_post_date($id, $idea_postdate);

        wp_send_json([
            'status'  => 'alert',
            'message' => 'Idea content saved!',
            'content' => [
                'idea_id'    => $id,
                'idea_icons' => $this->get_idea_icons($id),
            ]
        ]);
    }

    /**
     * Ajax action - remove idea posting date
     */
    public function remove_idea_posting_date()
    {
        if (empty($_REQUEST['id'])) {
            wp_send_json([
                'status' => 'error',
                'message' => 'Empty data!',
            ]);
        }

        $id = intval($_REQUEST['id']);

        $this->update_ideas_post_date($id, NULL);

        echo 'success';
        wp_die();
    }

    /**
     * Get ideas (can be used in ajax call)
     *
     * @param bool $return
     * @param int $n
     */
    public function generate($return = false, $n = 10, $prompt = '', $competitor_keywords = '')
    {
//        error_reporting(E_ALL);
//        ini_set('display_errors', '1');

        global $SEOAIC_OPTIONS;

        if (!empty($_REQUEST['ideas_count'])) {
            $n = intval($_REQUEST['ideas_count']);
        }

        $idea_prompt = !empty($_REQUEST['idea_prompt']) ? stripslashes(sanitize_text_field($_REQUEST['idea_prompt'])) : '';

        if($prompt) {
            $idea_prompt = $prompt;
        }

        $serviceName = '';
        $serviceText = '';

        if ( !empty($SEOAIC_OPTIONS['seoaic_services']) && isset($_REQUEST['select_service']) && $_REQUEST['select_service'] !== '' ) {
            $serviceName = $SEOAIC_OPTIONS['seoaic_services'][(int)$_REQUEST['select_service']]['name'];
            $serviceText = $SEOAIC_OPTIONS['seoaic_services'][(int)$_REQUEST['select_service']]['text'];
        }

        $language = $this->seoaic->multilang->filter_request_multilang();

        if (isset($_REQUEST['search_terms_page'])) {
            $keywords = '';
            $search_terms = !empty($_REQUEST['selected_keywords']) ? implode(', ', $_REQUEST['selected_keywords']) : '';
        } else {
            $search_terms = '';
            $keywords = !empty($_REQUEST['selected_keywords']) ? implode(', ', $_REQUEST['selected_keywords']) : '';
        }

        if($competitor_keywords) {
            $keywords = $competitor_keywords;
        }

        $data = [
            'title' => !empty($SEOAIC_OPTIONS['seoaic_business_name']) ? $SEOAIC_OPTIONS['seoaic_business_name'] : get_option('blogname', true),
            'description' => get_option('blogdescription', true),
            'language' => $language,
            'idea_type' => !empty($_REQUEST['idea_template_type']) ? stripslashes(sanitize_text_field($_REQUEST['idea_template_type'])) : 'default',
            'content' => '',
            'prompt' => $idea_prompt,
            'company' => !empty($SEOAIC_OPTIONS['seoaic_business_name']) ? $SEOAIC_OPTIONS['seoaic_business_name'] : get_option('blogname', true),
            'industry' => !empty($SEOAIC_OPTIONS['seoaic_industry']) ? $SEOAIC_OPTIONS['seoaic_industry'] : '',
            'company_desc' => !empty($SEOAIC_OPTIONS['seoaic_business_description']) ? $SEOAIC_OPTIONS['seoaic_business_description'] : get_option('blogdescription', true),
            'keywords' => $keywords,
            'search_term' => $search_terms,
            //'service' => !empty($_REQUEST['selected_services']) ? implode(', ', $_REQUEST['selected_services']) : '',
            'service' => !empty($serviceName) ? ($serviceText ? $serviceName . ' (' . $serviceText . ')' : $serviceName ) : '',
            'n' => $n,
        ];

        $first_location = [];

        if ( !empty($_REQUEST['selected_locations']) && is_array($_REQUEST['selected_locations']) ) {
            $first_location = array_shift($_REQUEST['selected_locations']);
            $data['locations'] = $first_location;
        }

        $result = $this->seoaic->curl->init('api/ai/ideas', $data, true, false, true);

        $SEOAIC_OPTIONS['seoaic_idea_prompt'] = $idea_prompt;
        update_option('seoaic_options', $SEOAIC_OPTIONS);

        $idea_id = 0;
        //$idea_schedule = intval($_REQUEST['idea_schedule']);
        $idea_schedule = false;
        $date = false;
        //$date = sanitize_text_field($_REQUEST['posting_date']);

        $raw_content = !empty($result['content']) ? $result['content'] : [];

        $content = [];
        $key = 0;
        $languagesArray = explode(',', $language);


        foreach ($raw_content as $ideas) {
            foreach ($ideas as $idea_key => $_title) {
                $content[] = [
                    'key' => $idea_key,
                    'title' => $_title,
                    'language' => $this->seoaic->multilang->get_language_by($languagesArray[$key]),
                    'is_default' => ($languagesArray[$key] === $this->seoaic->multilang->get_default_language()),
                ];
            }

            $key ++;
        }

        if (
            !$this->seoaic->multilang->is_multilang()
            && !empty($_REQUEST['selected_locations'])
            && is_array($_REQUEST['selected_locations'])
        ) {
            $base_content = $content;
            foreach ($base_content as $idea) {
                foreach ($_REQUEST['selected_locations'] as $location) {
                    $content[] = [
                        "title" => str_replace($first_location, $location, $idea['title'])
                    ];
                }
            }
        }

        $insert_posts = [];

        // Filter out ideas duplicates from response
        $names = array_column($content, 'title');
        $unique_names = array_unique($names);
        $unique_array = array();
        foreach ($content as $item) {
            if (in_array($item['title'], $unique_names)) {
                $unique_array[] = $item;
                unset($unique_names[array_search($item['title'], $unique_names)]);
            }
        }

        $content = $unique_array;

        foreach ($content as $key => &$idea) {
            if (empty($idea['title'])) {
                break;
            }

            $title = trim($idea['title'], '"');

            $idea_id = wp_insert_post([
              'post_title' => wp_check_invalid_utf8($title),
              'post_type' => 'seoaic-post',
              'post_status' => 'seoaic-idea',
            ]);

            if (empty($idea_id)) {
                continue;
            }

            $idea['idea_id'] = $idea_id;
            $this->generatedIdeasIDs[] = $idea_id;

            update_post_meta($idea_id, '_idea_prompt_data', $data['prompt']);
            update_post_meta($idea_id, '_idea_type', $data['idea_type']);
            update_post_meta($idea_id, '_idea_keywords_data', explode(',', $data['keywords']));

            $insert_posts[$idea_id] = $title;

            if (!empty($idea_schedule)) {
                $days = $SEOAIC_OPTIONS['seoaic_schedule_days'];

                if (empty($days)) {
                    continue;
                }

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
                                'relation' => 'OR',
                                [
                                    'key' => 'seoaic_idea_postdate',
                                    'value' => $date,
                                    'compare' => 'LIKE'
                                ]
                            ]
                        ]);

                        if (count($_have_posted_idea) < $_posts) {
                            $this->update_ideas_post_date($idea_id, $date . ' ' . date('H:i:s', strtotime($days[$_day]['time'])));
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
        }

        $this->seoaic->multilang->add_new_ideas_generation($content);

        if ($return && !empty($idea_id)) {
            return $idea_id;
        }

        $message = '<div class="mb-19">New ideas have been generated:</div>';
        $message .= self::makeIdeasRows($insert_posts);

        wp_send_json([
            'status'  => 'alert',
            'message' => $message,
        ]);
    }

    public function generateIdeasNewKeywords($return = false, $n = 10)
    {
        global $SEOAIC_OPTIONS;

        KeywordsPostsTable::createIfNotExists();

        if (!empty($_REQUEST['ideas_count'])) {
            $n = intval($_REQUEST['ideas_count']);
        }

        $idea_prompt = !empty($_REQUEST['idea_prompt']) ? stripslashes(sanitize_text_field($_REQUEST['idea_prompt'])) : '';

        $serviceName = '';
        $serviceText = '';


        if (
            !empty($SEOAIC_OPTIONS['seoaic_services'])
            && isset($_REQUEST['select_service'])
            && $_REQUEST['select_service'] !== ''
        ) {
            $serviceName = $SEOAIC_OPTIONS['seoaic_services'][(int)$_REQUEST['select_service']]['name'];
            $serviceText = $SEOAIC_OPTIONS['seoaic_services'][(int)$_REQUEST['select_service']]['text'];
        }

        $language = $this->seoaic->multilang->filter_request_multilang();
        $selectedKeywordsIDs = !empty($_REQUEST['select-keywords']) ? $_REQUEST['select-keywords'] : [];
        $keywords = $selectedKeywordsIDs ? $this->seoaic->keywords->getKeywordsByIDs($selectedKeywordsIDs) : [];
        $keywordsIDs = array_map(function ($item) {
            return $item['id'];
        }, $keywords);
        $keywordsNamesArray = array_map(function ($item) {
            return $item['name'];
        }, $keywords);

        $data = [
            'title' => SEOAIC_SETTINGS::getBusinessName(),
            'description' => get_option('blogdescription', true),
            'language' => $language,
            'idea_type' => !empty($_REQUEST['idea_template_type']) ? stripslashes(sanitize_text_field($_REQUEST['idea_template_type'])) : 'default',
            'content' => '',
            'prompt' => $idea_prompt,
            'company' => SEOAIC_SETTINGS::getBusinessName(),
            'industry' => SEOAIC_SETTINGS::getIndustry(),
            'company_desc' => SEOAIC_SETTINGS::getBusinessDescription(),
            'keywords' => implode(', ', $keywordsNamesArray),
            'search_term' => '',
            //'service' => !empty($_REQUEST['selected_services']) ? implode(', ', $_REQUEST['selected_services']) : '',
            'service' => !empty($serviceName) ? ($serviceText ? $serviceName . ' (' . $serviceText . ')' : $serviceName ) : '',
            'n' => $n,
        ];
// error_log('data '.print_r($data, true));return ;

        $first_location = [];

        if (
            !empty($_REQUEST['selected_locations'])
            && is_array($_REQUEST['selected_locations'])
        ) {
            $first_location = array_shift($_REQUEST['selected_locations']);
            $data['locations'] = $first_location;
        }

        if (!empty($_REQUEST['generate_keywords_separately'])) {
            $data['generate_keywords_separately'] = true;
        }

        $result = $this->seoaic->curl->init('api/ai/ideas', $data, true, false, true);

        $SEOAIC_OPTIONS['seoaic_idea_prompt'] = $idea_prompt;
        update_option('seoaic_options', $SEOAIC_OPTIONS);

        $idea_id = 0;
        //$idea_schedule = intval($_REQUEST['idea_schedule']);
        $idea_schedule = false;
        $date = false;
        //$date = sanitize_text_field($_REQUEST['posting_date']);

        $raw_content = !empty($result['content']) ? $result['content'] : [];

        $content = [];
        $key = 0;
        $languagesArray = explode(',', $language);


        foreach ($raw_content as $ideas) {
            foreach ($ideas as $idea_key => $_title) {
                $content[] = [
                    'key' => $idea_key,
                    'title' => $_title,
                    'language' => $this->seoaic->multilang->get_language_by($languagesArray[$key]),
                    'is_default' => ($languagesArray[$key] === $this->seoaic->multilang->get_default_language()),
                ];
            }

            $key ++;
        }

        if (
            !$this->seoaic->multilang->is_multilang()
            && !empty($_REQUEST['selected_locations'])
            && is_array($_REQUEST['selected_locations'])
        ) {
            $base_content = $content;
            foreach ($base_content as $idea) {
                foreach ($_REQUEST['selected_locations'] as $location) {
                    $content[] = [
                        "title" => str_replace($first_location, $location, $idea['title'])
                    ];
                }
            }
        }

        $insert_posts = [];

        // Filter out ideas duplicates from response
        $names = array_column($content, 'title');
        $unique_names = array_unique($names);
        $unique_array = array();
        foreach ($content as $item) {
            if (in_array($item['title'], $unique_names)) {
                $unique_array[] = $item;
                unset($unique_names[array_search($item['title'], $unique_names)]);
            }
        }

        $content = $unique_array;

        foreach ($content as $key => &$idea) {
            if (empty($idea['title'])) {
                break;
            }

            $title = trim($idea['title'], '"');

            $idea_id = wp_insert_post([
                'post_title'    => wp_check_invalid_utf8($title),
                'post_type'     => 'seoaic-post',
                'post_status'   => 'seoaic-idea',
            ]);

            if (empty($idea_id)) {
                continue;
            }

            $idea['idea_id'] = $idea_id;
            $insert_posts[$idea_id] = $title;
            $this->generatedIdeasIDs[] = $idea_id;

            $this->updateIdeaData($idea_id, [
                '_idea_prompt_data' => $data['prompt'],
                '_idea_type' => $data['idea_type'],
                '_idea_keywords_data', explode(',', $data['keywords']),
            ]);

            if (!empty($idea_schedule)) {
                $days = $SEOAIC_OPTIONS['seoaic_schedule_days'];

                if (empty($days)) {
                    continue;
                }

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
                                'relation' => 'OR',
                                [
                                    'key' => 'seoaic_idea_postdate',
                                    'value' => $date,
                                    'compare' => 'LIKE'
                                ]
                            ]
                        ]);

                        if (count($_have_posted_idea) < $_posts) {
                            $this->update_ideas_post_date($idea_id, $date . ' ' . date('H:i:s', strtotime($days[$_day]['time'])));
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
        }

        KeywordsPostsRelation::setRelations($keywordsIDs, $this->generatedIdeasIDs);

        $this->seoaic->multilang->add_new_ideas_generation($content);

        if ($return && !empty($idea_id)) {
            return $idea_id;
        }

        $message = '<div class="mb-19">New ideas have been generated:</div>';
        $message .= self::makeIdeasRows($insert_posts);
        $button = '<div class="mr-15">
            <button 
                title="Generate" 
                type="button" 
                class="button-primary seoaic-button-primary mass-effect-button seoaic-generate-posts-button modal-button confirm-modal-button" 
                data-modal="#seoaic-post-mass-creation-modal" 
                data-action="seoaic_posts_mass_create" 
                data-title="Posts mass creation" 
                data-content="You will generate posts from following ideas:">
                Generate
                <div class="dn additional-form-items">';

        $button .= self::makaIdeasGenerateButton($insert_posts);
        $button .= "</div></button></div>";

        SEOAICAjaxResponse::alert($message)->addFields(['button' => $button])->wpSend();
    }

    /**
     * Updates Idea's data (meta fields)
     * @param array|int $idea Idea or ID
     * @param array $data meta fields to update. Assoc array in a "key => value" format
     * @return bool
     */
    private function updateIdeaData($idea, $data = [])
    {
        if (empty($data)) {
            return false;
        }

        if (
            is_numeric($idea)
            && (int) $idea == $idea
        ) {
            $id = $idea;
        } else {
            $id = $idea['id'];
        }

        $updateRes = wp_update_post([
            'ID'            => $id,
            'meta_input'    => $data,
        ]);

        if (is_wp_error($updateRes)) {
            return false;
        }

        return true;
    }

    public function getGeneratedIdeasIDs()
    {
        return $this->generatedIdeasIDs;
    }

    private static function makeIdeasRows($ideas=[])
    {
        ob_start();
        foreach ($ideas as $key => $value) {
            ?>
            <div class="mb-10 alert-added-ideas">
                <b class="num-col">#<?php echo $key;?></b>
                <div class="title-col">
                    <input type="text" name="idea_updated_title" value="<?php echo $value;?>" class="seoaic-form-item idea-updated-title">
                    <span class="idea-orig-title"><?php echo $value;?></span>
                </div>
                <div class="btn1-col">
                    <span class="save idea-btn" title="Save" data-action="seoaic_edit_idea" data-post-id="<?php echo $key;?>"></span>
                    <span class="edit idea-btn" title="Edit"></span>
                </div>
                <div class="btn2-col">
                    <span class="delete idea-btn" title="Delete" data-action="seoaic_remove_idea" data-post-id="<?php echo $key;?>"></span>
                </div>
            </div>
            <?php
        }
        return ob_get_clean();
    }

    private static function makaIdeasGenerateButton($ideas=[]) {
        ob_start();
        foreach ($ideas as $key => $value) {
            ?>
                <label data-id="label-idea-mass-create-<?php echo $key;?>">
                    <input type="checkbox" checked="" class="seoaic-form-item" name="idea-mass-create" value="<?php echo $key;?>"> <b>#<?php echo $key;?></b> - <?php echo $value;?><label></label>
                </label>
            <?php
        }
        return ob_get_clean();
    }

    /**
     * Get idea`s icons
     *
     * @param int $id
     */
    public function get_idea_icons ( $id = 0 ) {
        if ( empty($id) ) {
            return '';
        }

        $idea_content = get_post_meta($id, 'seoaic_idea_content', true);
        if (!empty($idea_content)) {
            $idea_content = json_decode($idea_content, true);
        }
        $idea_date = get_post_meta($id, 'seoaic_idea_postdate', true);

        $icons = '';

        if (!empty($idea_date)) {
            $idea_post_date = date("F j, Y, g:i a", strtotime($idea_date));

            $icons .= '<span title="Posting date ' . $idea_post_date . '" class="date">' . $idea_post_date . '</span>';
        }

        $icons .= !empty($idea_content['idea_skeleton']) ? '<span title="Idea`s structure" class="seoaic-idea-icon seoaic-idea-icon-structure"></span>' : '';
        $icons .= !empty($idea_content['idea_keywords']) ? '<span title="Idea`s keywords" class="seoaic-idea-icon seoaic-idea-icon-keywords"></span>' : '';
        $icons .= !empty($idea_content['idea_description']) ? '<span title="Idea`s description" class="seoaic-idea-icon seoaic-idea-icon-description"></span>' : '';
        $icons .= !empty($idea_content['idea_thumbnail']) ? '<span title="Idea`s thumbnail" class="seoaic-idea-icon seoaic-idea-icon-thumbnail"></span>' : '';

        return $icons;
    }

    /**
     * Update ideas post date
     *
     * @param int $idea_id
     * @param string $date
     */
    public function update_ideas_post_date ( $idea_id = 0, $date = '' )
    {
        if (empty($idea_id) || (!empty($date) && strtotime($date) < time())) {
            return false;
        }

        if (NULL === $date) {
            delete_post_meta($idea_id, 'seoaic_idea_postdate');
        } else {
            update_post_meta($idea_id, 'seoaic_idea_postdate', $date);
        }
    }

    /**
     * Give idea setting to SEO AI server
     *
     */
    public function blog_idea_settings () {

        if ( !$this->seoaic->auth->check_api_token($_REQUEST['email'], $_REQUEST['token']) ) {
            wp_send_json( [
                'status'  => 'Auth error!',
            ] );
        }

        if ( empty($_REQUEST['item_id']) ) {
            wp_send_json( [
                'status'  => 'Idea error!',
            ] );
        }

        $data = $this->seoaic->posts->generate_post(false, true);
        $data['status'] = 'success';

        wp_send_json( $data );
    }

    /**
     * Update credits real time
     *
     */
    public function Update_credits_real_time ()
    {
        global $SEOAIC_OPTIONS;

        $data = [
            'email' => $SEOAIC_OPTIONS['seoaic_api_email'],
            'domain' => $_SERVER['HTTP_HOST'],
        ];

        $result = $this->seoaic->curl->init('api/companies/credits', $data, true, true, true);

        $this->seoaic->set_api_credits($result);

        SEOAICAjaxResponse::success()->addFields($result)->wpSend();
    }
}
