<?php

use SEOAIC\helpers\WPTransients;

class SEOAIC_RANK
{
    private $seoaic;

    private const KEYWORDS_CACHE_KEY = 'seoaic_keywords';

    function __construct($_seoaic)
    {

        $this->seoaic = $_seoaic;

        add_action('wp_ajax_seoaicAddSearchTerms', [$this, 'Add']);
        add_action('wp_ajax_seoaicAddSubTerms', [$this, 'Add']);
        add_action('wp_ajax_seoaicRemoveSearchTerms', [$this, 'Remove']);
        add_action('wp_ajax_seoaic_get_search_term_competitors', [$this, 'Get_Competitors']);
        add_action('wp_ajax_seoaicAddedCompetitors', [$this, 'Added_Competitors']);
        add_action('wp_ajax_seoaicGetKeywordSuggestions', [$this, 'Get_Keyword_Suggestions']);
        add_action('wp_ajax_seoaicAddCompetitorsTerms', [$this, 'Add_Competitors_Terms']);
        add_action('wp_ajax_seoaicUpdateSearchTerms', [$this, 'Update_Terms']);
        add_action('wp_ajax_seoaicFilteringTerms', [$this, 'filtering_terms_HTML']);
        add_action('wp_ajax_seoaic_my_article_popup_top_table_analysis', [$this, 'my_article_popup_top_table_analysis']);
        add_action('wp_ajax_seoaic_competitor_article_popup_table_analysis', [$this, 'competitor_article_popup_table_analysis']);
    }

    /**
     * Slugify string
     */
    public function Slug($text)
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '_', $text);
        // transliterate
        //$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        // remove unwanted characters
        //$text = preg_replace('~[^-\w]+~', '_', $text);
        // trim
        $text = trim($text, '-');
        // remove duplicated - symbols
        $text = preg_replace('~-+~', '_', $text);
        // lowercase
        $text = strtolower($text);
        if (empty($text)) {
            return 'n-a';
        }
        return $text;
    }

    public function Move_Index($array, $find, $move)
    {
        $out = array_splice($array, intval($find), 1);
        array_splice($array, intval($move), 0, $out);
        return $array;
    }

    /**
     * Adding Action Terms
     */
    public function Add()
    {
        if (empty($_REQUEST['item_name']) && empty($_REQUEST['select-keywords'])) {
            wp_die();
        }

        $search_engine = !empty($_GET['search_engine']) ? $_GET['search_engine'] : 'google';
        $parent = !empty($_REQUEST['parent_term']) ? $_REQUEST['parent_term'] : '';
        //$select_keywords = isset($_REQUEST['selected_keywords']) ? $_REQUEST['selected_keywords'] : [];
        $location = !empty($_REQUEST['location']) ? $_REQUEST['location'] : 'United States';
        $terms = isset($_REQUEST['item_name']) ? explode(',', strtolower(stripslashes(sanitize_text_field($_REQUEST['item_name'])))) : '';
        $terms = !empty($terms) ? array_map('trim', $terms) : [];

        $all_therms = array_filter($terms);
        $all_therms = array_values($all_therms);

        $this->Add_Terms($all_therms, $search_engine, $parent, $location);

        wp_send_json([
            'status' => 'alert',
            'message' => 'Keywords «' . implode(',', $all_therms) . '» has been added!',
            'html' => $this->search_terms_HTML(),
        ]);

    }

    /**
     * Update Search Terms
     * param
     * string $search_engine
     */

    //TODO
    public function Update_Terms(): void
    {

        global $SEOAIC_OPTIONS;

        $search_engine = 'google';
        $run_update = 0;

        if (isset($_REQUEST['run_update'])) {
            $run_update = intval($_REQUEST['run_update']);
            $keyword = $_REQUEST['keyword'];
            $location = $_REQUEST['location'];
            $parent_term = $_REQUEST['parent_term'];
        }

        $ready_to_update = [];

        $api = !empty($SEOAIC_OPTIONS['seoaic_api_email']) ? $SEOAIC_OPTIONS['seoaic_api_email'] : false;

        $update = isset($_GET['update-terms']) && $_GET['update-terms'] == $api ? true : false;

        $time_update = $update ? (time() * 100) : time();

        if (isset($SEOAIC_OPTIONS['search_terms'][$search_engine])) {
            foreach ((array)$SEOAIC_OPTIONS['search_terms'][$search_engine] as $index => $term) {
                if (isset($term['next_update']) && $term['next_update'] < $time_update
                    ||
                    !isset($term['next_update'])
                    ||
                    !isset($term['description'])
                    ||
                    !isset($term['title'])
                    ||
                    !isset($term['article_analysis'])
                    ||
                    !isset($term['historical_rank'])
                ) {

                    $ready_to_update[] = [
                        "keyword" => $term['keyword'],
                        "slug" => $term['slug'],
                        "location" => !empty($term['location']) ? $term['location'] : (!empty($SEOAIC_OPTIONS['seoaic_location']) ? $SEOAIC_OPTIONS['seoaic_location'] : 'United States'),
                        "parent_term" => $term['parent_term'],
                        "last_update" => !empty($term['last_update']) ? $term['last_update'] : '',
                        "next_update" => !empty($term['next_update']) ? $term['next_update'] : '',
                        "index" => $index,
                    ];

                }
            }
        }

        $SEOAIC_OPTIONS['search_terms_ready_to_update'] = $ready_to_update;
        update_option('seoaic_options', $SEOAIC_OPTIONS);

        if ($run_update) {

            $search_terms = explode(",", $keyword);
            $this->Add_Terms($search_terms, $search_engine, $parent_term, $location, true);

            // remove updated term
            $updated = array_filter($SEOAIC_OPTIONS['search_terms_ready_to_update'], function ($item) use ($keyword) {
                return $item['keyword'] !== $keyword;
            });

            $SEOAIC_OPTIONS['search_terms_ready_to_update'] = array_values($updated);

            update_option('seoaic_options', $SEOAIC_OPTIONS);

            wp_send_json(
                [
                    "updated" => 1,
                    "term_updated" => $keyword
                ]
            );

        }

    }

    /**
     * Add Search Terms
     */
    public function Add_Terms($terms, $search_engine, $parent, $location, $update = false, $return = false, $option_name = 'search_terms')
    {
        global $SEOAIC_OPTIONS, $SEOAIC;

        $next_update = (30 * DAY_IN_SECONDS) + time();

        if (empty($location)) {
            $location = $SEOAIC->competitors->seoaic_location();
        }

        $language = !empty($SEOAIC_OPTIONS['seoaic_language']) ? $SEOAIC_OPTIONS['seoaic_language'] : 'English';
        $limit = 100;

        $company_website = !empty($SEOAIC_OPTIONS['seoaic_company_website']) ? $SEOAIC_OPTIONS['seoaic_company_website'] : get_bloginfo('url');
        $company_website = wp_parse_url($company_website)['host'];

        $here = '';
        $all_terms = !empty($SEOAIC_OPTIONS[$option_name][$search_engine]) ? $SEOAIC_OPTIONS[$option_name][$search_engine] : [];

        $get_all_terms = array_column($all_terms, 'slug');

        $added_terms = [];

        foreach ($terms as $term_index => $term) {

            $data = [
                'email' => $SEOAIC_OPTIONS['seoaic_api_email'],
                'token' => $SEOAIC_OPTIONS['seoaic_api_token'],
                'language' => $language,
                'location' => $location,
                'target' => $company_website,
                'limit' => $limit,
                'add_terms' => $term,
                'keywords' => [$term],
            ];

            if (!$update) {
                if (in_array($this->Slug($term . '_' . $location), $get_all_terms)) {
                    $here = '<li>Term "' . $term . '" for ' . $location . ' - <span class="red">already exists</span></li>';
                    break;
                }
            }

            $result = $this->seoaic->curl->init('api/ai/rank-search-terms', $data, true, true, true);
            $historical_rank = $this->seoaic->curl->init('api/ai/keywords-ranks', $data, true, true, true);

            $keyword = isset($result['data'][0]['keyword']) ? str_replace('\\', '', strtolower($result['data'][0]['keyword'])) : str_replace('\\', '', strtolower($term));
            $position = isset($result['data'][0]['position']) ? (int)$result['data'][0]['position'] : '';
            $page = !empty($result['data'][0]['page']) ? $result['data'][0]['page'] : '';
            $description = !empty($result['data'][0]['description']) ? $result['data'][0]['description'] : '';
            $title = !empty($result['data'][0]['title']) ? $result['data'][0]['title'] : '';
            $article_analysis = !empty($page) ? $this->parse_content_analysis($page, $term, $location) : [];

            $rank_history = [];
            foreach ((array)$historical_rank['data'] as $rank) {
                $rank_history = array_merge($rank_history, $rank['values']);
            }

            foreach ($rank_history as $i_rank => $rank) {
                $str_date = $rank[0];
                $rank_history[$i_rank][0] = strtotime($str_date) * 1000;
            }

            $rank_history_time_str = !empty($rank_history) ?
                [
                    [
                        'name' => $term,
                        'colors' => '#3538FE',
                        'data' => $rank_history
                    ]
                ]
                :
                [
                    ['data' => []]
                ];

            if ($update) {

                foreach ($SEOAIC_OPTIONS[$option_name][$search_engine] as $index => $keyword) {

                    if (!isset($keyword['location'])) {
                        $SEOAIC_OPTIONS[$option_name][$search_engine][$index]['location'] = $location;
                        $SEOAIC_OPTIONS[$option_name][$search_engine][$index]['slug'] = $keyword['slug'] . '_' . $this->Slug($location);
                    }

                    if ($keyword['keyword'] == $term && $keyword['location'] == $location) {

                        $SEOAIC_OPTIONS[$option_name][$search_engine][$index]['position'] = isset($result['data'][0]['position']) ? (int)$result['data'][0]['position'] : '';
                        $SEOAIC_OPTIONS[$option_name][$search_engine][$index]['page'] = $page;
                        $SEOAIC_OPTIONS[$option_name][$search_engine][$index]['search_page'] = !empty($result['data'][0]['search_page']) ? $result['data'][0]['search_page'] : '';
                        $SEOAIC_OPTIONS[$option_name][$search_engine][$index]['last_update'] = time();
                        $SEOAIC_OPTIONS[$option_name][$search_engine][$index]['next_update'] = $next_update;
                        $SEOAIC_OPTIONS[$option_name][$search_engine][$index]['description'] = $description;
                        $SEOAIC_OPTIONS[$option_name][$search_engine][$index]['title'] = $title;
                        $SEOAIC_OPTIONS[$option_name][$search_engine][$index]['article_analysis'] = !empty($article_analysis) ? $article_analysis : [];
                        $SEOAIC_OPTIONS[$option_name][$search_engine][$index]['historical_rank'] = $rank_history_time_str;
                    }
                }

                update_option('seoaic_options', $SEOAIC_OPTIONS);

                $this->combine_rank_tracking_positions($keyword, $position, $page, $location);
                $this->combine_terms_with_my_rank_competitors($keyword, $position);

                break;
            }

            $loco = isset($result['data'][0]['location']) ? $result['data'][0]['location'] : $location;
            $slug = isset($result['data'][0]['keyword']) ? $this->Slug($result['data'][0]['keyword'] . '_' . $loco) : $this->Slug($term . '_' . $loco);

            $added_terms[] = [
                'keyword' => $keyword,
                'slug' => $slug,
                'index' => $term_index
            ];

            $SEOAIC_OPTIONS[$option_name][$search_engine][] = [
                'keyword' => $keyword,
                'slug' => $slug,
                "position" => $position,
                "added_competitors" => [],
                "parent_term" => isset($parent) ? $parent : '',
                "search_volume" => 0,
                "location" => $loco,
                "page" => $page,
                'search_page' => isset($result['data'][0]['search_page']) ? $result['data'][0]['search_page'] : '',
                "rank_history" => $rank_history_time_str,
                "serp" => [
                    'last_update' => 0,
                    'data' => '',
                ],
                "keyword_suggestions" => [
                    'last_update' => 0,
                    'data' => '',
                ],
                "sub_terms" => [],
                'last_update' => time(),
                'next_update' => $next_update,
                'description' => $description,
                'title' => $title,
                'article_analysis' => $article_analysis,
                'historical_rank' => $rank_history_time_str
            ];

            $this->combine_rank_tracking_positions($keyword, $position, $page, $loco);
            $this->combine_terms_with_my_rank_competitors($keyword, $position);

            // Change index of sub term (move it after parent)
            if (isset($parent)) {
                $termsArray = $SEOAIC_OPTIONS[$option_name][$search_engine];
                $find = 0;
                $move = 0;
                foreach ($termsArray as $i => $val) :
                    if ($val['slug'] == $parent) {
                        $move = intval($i) + 1;
                    }
                endforeach;

                foreach ($termsArray as $i => $val) :
                    if ($val['parent_term'] == $parent) {
                        $find = intval($i);
                    }
                endforeach;

                $termsArray = $this->Move_Index($termsArray, $find, $move);

                $SEOAIC_OPTIONS[$option_name][$search_engine] = $termsArray;

                update_option('seoaic_options', $SEOAIC_OPTIONS);

            }

            $here = '<li>Term "' . $term . '" for ' . $location . ' - <span class="green">added</span></li>';

        }

        $this->Search_Volume($search_engine, $terms, $location, $option_name);

        $SEOAIC_OPTIONS['search_terms_ready_to_update'] = [];

        update_option('seoaic_options', $SEOAIC_OPTIONS);

        if ($return) {
            return $added_terms;
        }

        if (!$update) {
            wp_send_json($here ? $here : '');
        }

    }


    /**
     * Add Competitors Terms
     */
    public function Add_Competitors_Terms()
    {

        global $SEOAIC_OPTIONS;

        $search_engine = isset($_GET['search_engine']) ? $_GET['search_engine'] : 'google';
        $terms = isset($_REQUEST['selected_keywords']) ? $_REQUEST['selected_keywords'] : '';
        $location = isset($_REQUEST['seoaic_location']) ? $_REQUEST['seoaic_location'] : '';
        $parent = isset($_REQUEST['parent_keyword']) ? $_REQUEST['parent_keyword'] : '';
        $terms = explode(',', stripslashes(sanitize_text_field($terms)));
        $terms = array_map('trim', $terms);

        $this->Add_Terms($terms, $search_engine, $parent, $location);

        // Sort children Search volume High -> Low
        usort($SEOAIC_OPTIONS['search_terms'][$search_engine], function ($a, $b) use ($parent) {
            if ($a["parent_term"] === $parent && $b["parent_term"] === $parent) {
                if (isset($b["search_volume"])) {
                    return (int)$b["search_volume"] - (int)$a["search_volume"];
                }
            }
            return 0;
        });

        update_option('seoaic_options', $SEOAIC_OPTIONS);

        wp_send_json([
            'status' => 'error',
            'message' => 'Search Terms «' . implode(',', $terms) . '» has been added!<br><br><a href="' . get_admin_url(null, 'admin.php?page=seoaic-rank-tracker') . '" target="_blank">View Rank Tracker Page<a/>',
            //'content' => $sub_terms,
        ]);

    }

    /**
     * Remove terms
     *  Ajax
     */
    public function Remove()
    {
        global $SEOAIC_OPTIONS;
        $search_engine = isset($_GET['search_engine']) ? $_GET['search_engine'] : 'google';
        $try = $SEOAIC_OPTIONS['search_terms'][$search_engine];

        $slugs = isset($_REQUEST['item_id']) ? explode(',', $_REQUEST['item_id']) : '';

        $try = array_filter($try, function ($term) use ($slugs) {
            foreach ($slugs as &$slug) {
                if ($term['slug'] === $slug) {
                    return false;
                }
            }
            return true;
        });

        foreach ($slugs as $slug) {
            foreach ($try as &$term) {
                if ($term['parent_term'] == $slug) {
                    $term['parent_term'] = '';
                }
            }
        }

        $terms_index = array_values(array_filter($try));

        $SEOAIC_OPTIONS['search_terms'][$search_engine] = $terms_index;
        $SEOAIC_OPTIONS['search_terms_ready_to_update'] = [];
        update_option('seoaic_options', $SEOAIC_OPTIONS);

        wp_send_json([
            'status' => 'success',
        ]);
    }

    /**
     * Get search volume
     *  params
     *  string $search_engine
     *  array $terms
     */
    public function Search_Volume($search_engine, $terms, $location, $single_term_value = false, $option_name = 'search_terms')
    {

        global $SEOAIC_OPTIONS, $SEOAIC;

        if (empty($location)) {
            $location = $SEOAIC->competitors->seoaic_location();
        }

        $language = $SEOAIC->competitors->seoaic_language();

        $data = [
            'keywords' => $terms,
            'location' => $location,
            'language' => $language,
            'mode' => 'auto',
            'email' => $SEOAIC_OPTIONS['seoaic_api_email'],
        ];

        $result = $this->seoaic->curl->init('api/ai/keywords-search-volume', $data, true, true, true);

        if (!empty($result['status']) && $result['status'] === 'success') {

            // filter terms and set search_volume
            foreach ($result['data'] as $keyword) {

                foreach ($SEOAIC_OPTIONS[$option_name][$search_engine] as &$term) {
                    if ($term['slug'] == $this->Slug($keyword['keyword'] . '_' . $location)) {
                        $term['search_volume'] = isset($keyword['search_volume']) ? $keyword['search_volume'] : '-';
                    }
                }
            }
            if ($single_term_value) {
                return $result['data'][0]['search_volume'];
            }
        }
    }

    public function request_content_analysis($text)
    {

        if (empty($text)) {
            return [];
        }

        global $SEOAIC, $SEOAIC_OPTIONS;

        $data = [
            'email' => $SEOAIC_OPTIONS['seoaic_api_email'],
            'token' => $SEOAIC_OPTIONS['seoaic_api_token'],
            'language' => 'English',
            'text' => $text,
        ];

        return $SEOAIC->curl->init('api/ai/text-summary', $data, true, true, true);
    }

    /**
     * Updates Keyword's data (meta fields)
     * @param array|int $keyword Keyword or ID
     * @param array $data meta fields to update. Assoc array in a "key => value" format
     * @return bool
     */
    private function updateKeywordData($keyword, $data = [])
    {
        if (empty($data)) {
            return false;
        }

        if (
            is_numeric($keyword)
            && (int)$keyword == $keyword
        ) {
            $id = $keyword;
        } else {
            $id = $keyword['id'];
        }

        $updateRes = wp_update_post([
            'ID' => $id,
            'meta_input' => $data,
        ]);

        if (is_wp_error($updateRes)) {
            return false;
        }

        WPTransients::deleteCachedValue(self::KEYWORDS_CACHE_KEY);

        return true;
    }

    /**
     * Request Competitors
     * params
     * array $keyword
     * string $search_engine
     */
    private function Request_Competitors($keyword, $search_engine = 'google', $option_name = 'search_terms', $data_id = 0)
    {
        global $SEOAIC_OPTIONS;

        $keyword_name = !empty($keyword['keyword']) ? $keyword['keyword'] : $keyword['name'];
        $location = !empty($SEOAIC_OPTIONS['seoaic_location']) ? $SEOAIC_OPTIONS['seoaic_location'] : 'United States';
        $data = [
            'email' => $SEOAIC_OPTIONS['seoaic_api_email'],
            'token' => $SEOAIC_OPTIONS['seoaic_api_token'],
            'language' => !empty($SEOAIC_OPTIONS['seoaic_language']) ? $SEOAIC_OPTIONS['seoaic_language'] : 'English',
            'location' => !empty($SEOAIC_OPTIONS['seoaic_location']) ? $SEOAIC_OPTIONS['seoaic_location'] : 'United States',
            'keywords' => [$keyword_name],
        ];

        $result = $this->seoaic->curl->init('api/ai/keywords-serp-competitors', $data, true, true, true);

        $index = -1;

        if (!empty($result['status']) && $result['status'] === 'success') {

            if ($data_id) {
                $page = !isset($keyword['rank_data'][0]['page']) ? '' : $keyword['rank_data'][0]['page'];
                $this->updateKeywordData($data_id, ['serp_data' => [
                    'last_update' => time(),
                    'article_analysis' => !empty($page) ? $this->parse_content_analysis($page, $keyword_name, $location) : [],
                    'data' => $result['data'][$keyword['name']]
                ]]);

                return $data_id;
            }

            foreach ($SEOAIC_OPTIONS[$option_name][$search_engine] as $i => $keyword) {
                if (in_array($keyword['keyword'], array_keys($result['data']))) {
                    $index = $i;
                    $SEOAIC_OPTIONS[$option_name][$search_engine][$i]['serp'] = [
                        'last_update' => time(),
                        'data' => $result['data'][$keyword['keyword']]
                    ];
                }
            }
            update_option('seoaic_options', $SEOAIC_OPTIONS);
        }

        return $index;
    }

    public function get_spesific_competitors($find_competitors, $all_competitors)
    {

        $unique_domains = [];
        $result = array_filter($all_competitors, function ($item) use (&$unique_domains) {
            $domain = $item['domain'];
            if (in_array($domain, $unique_domains)) {
                return false;
            }
            $unique_domains[] = $domain;
            return true;
        });

        $serp_data = [];
        if ($find_competitors) {
            foreach ($find_competitors as $i => $competitor) {
                $serp_data[] = [
                    'domain' => $competitor,
                ];
                foreach ($result as $domain) {
                    if (str_replace("www.", "", $domain['domain']) === $competitor) {
                        if(!isset($serp_data[$i]['avg_position'])) {
                            $serp_data[$i] = $domain;
                        }
                    }
                }
            }

            foreach ($result as $index=>$domain) {
                foreach ($serp_data as $i=>$serp_domain) {
                    if (str_replace("www.", "", $serp_domain['domain']) === $domain['domain']) {
                        unset($serp_data[$i]);
                        $serp_data[$index] = $domain;
                    }
                }
            }
        }

        return $serp_data;
    }

    public function competitors_table_html($competitors, $limit = -1)
    {
        global $SEOAIC;
        $competitors_html = [];
        $current_domain = $SEOAIC->competitors->seoaic_company_website_url();

        $count = 1;
        foreach ($competitors as $i => $competitor) {
            $domain = !empty($competitor['domain']) ? $competitor['domain'] : '';
            $current_domain_class = ($current_domain === $domain) ? ' highlighted' : '';
            $position = !empty($competitor['avg_position']) ? $competitor['avg_position'] : '-';
            $check_box = !$current_domain_class ? '<input class="seoaic-competitor-check" name="seoaic-check-competitor" type="checkbox" data-position="' . $position . '" value="' . $domain . '">' : '';
            $url = !empty($competitor['url']) ? esc_url($competitor['url']) : '-';
            $title = !empty($competitor['title']) ? esc_html($competitor['title']) : '-';
            $description = !empty($competitor['description']) ? esc_html($competitor['description']) : '-';

            $competitors_html[] = <<<HTML
            <div class="table-row{$current_domain_class}" data-index="{$i}">
                <div class="visibility selected">
                    <div class="check">{$check_box}</div>
                </div>
                <div class="domain">
                    <div class="inner">
                        <span class="limit-text-lines lines-1">{$domain}</span>
                        <a target="_blank" href="{$url}">{$title}</a>
                    </div>
                </div>
                <div class="avg_position">{$position}</div>
            </div>
            <div class="article-info">
                <div class="neta">
                    <div class="inner">
                        <div class="title limit-text-lines lines-2">Page Description</div>
                        <div class="description limit-text-lines lines-5">{$description}</div>
                    </div>
                </div>
            </div>
        HTML;

            if($limit > 0 && $count == $limit) {
                break;
            }

            $count ++;
        }

        return implode('', $competitors_html);
    }

    /**
     * Get Competitors
     * params
     */
    public function Get_Competitors($return = false, $keyword = '', $competitors = [], $option_name = 'search_terms')
    {
        global $SEOAIC_OPTIONS, $SEOAIC;

        $search_engine = isset($_GET['search_engine']) ? $_GET['search_engine'] : 'google';

        if ($keyword) {
            $_POST['keyword'] = $keyword;
        }

        $current = !empty($SEOAIC_OPTIONS[$option_name][$search_engine][intval($_POST['keyword'])])
            ? $SEOAIC_OPTIONS[$option_name][$search_engine][intval($_POST['keyword'])]
            : [];

        $interval = DAY_IN_SECONDS * 7;
        //$interval = DAY_IN_SECONDS * 30;

        $data_id = 0;
        $serp = 'serp';
        $serp_data = '';
        $next_update = isset($current[$serp]['last_update']) && is_numeric($current[$serp]['last_update']) && $current[$serp]['last_update'] > 0 ? $current[$serp]['last_update'] + ($interval) : false;
        if (isset($_POST['data_id'])) {
            $data_id = intval($_POST['data_id']);
            $serp = 'serp_data';
            $current = $SEOAIC->keywords->getKeywordByID($data_id);
            $serp_data = unserialize($SEOAIC->keywords->getKeywordByID($data_id)[$serp]);
            $next_update = isset($serp_data['last_update']) && is_numeric($serp_data['last_update']) && $serp_data['last_update'] > 0 ? $serp_data['last_update'] + ($interval) : false;
        }

        if (
            array_key_exists($serp, $current)
            && array_key_exists('article_analysis', $serp_data ? $serp_data : $current)
            && time() <= $next_update
        ) {

            if ($return) {
                if($competitors) {
                    return $this->get_spesific_competitors($competitors, $current[$serp]['data']);
                } else {
                    return $current[$serp]['data'];
                }
            }
            if ($data_id) {
                wp_send_json($this->competitors_table_html($serp_data['data']));
            }

            wp_send_json($this->competitors_table_html($current['serp']['data']));
        } else {
            $index = $this->Request_Competitors($current, $search_engine, $option_name, $data_id);

            if ($index > -1) {
                if ($return) {
                    if($competitors) {
                        $all_domains = array_column($SEOAIC_OPTIONS[$option_name][$search_engine][$index][$serp]['data'], 'domain');

                        foreach ($competitors as $competitor) {
                            if(!in_array($competitor, $all_domains)) {
                                $SEOAIC_OPTIONS[$option_name][$search_engine][$index][$serp]['data'][] = [
                                    'domain' => $competitor,
                                ];
                                update_option('seoaic_options', $SEOAIC_OPTIONS);
                            }
                        }

                        return $this->get_spesific_competitors($competitors, $SEOAIC_OPTIONS[$option_name][$search_engine][$index][$serp]['data']);
                    } else {
                        return $SEOAIC_OPTIONS[$option_name][$search_engine][$index]['serp']['data'];
                    }
                }
                if ($data_id) {
                    $updated = unserialize($SEOAIC->keywords->getKeywordByID($index)[$serp]);
                    wp_send_json($this->competitors_table_html($updated['data']));
                }
                wp_send_json($this->competitors_table_html($SEOAIC_OPTIONS[$option_name][$search_engine][$index]['serp']['data']));
            }
        }

        wp_send_json([
            'status' => true
        ]);
    }


    /**
     * Request Keyword Suggestion
     *  params
     *  array $keyword
     *  string $search_engine
     */
    private function Request_Keyword_Suggestions($keyword, $search_engine)
    {
        global $SEOAIC_OPTIONS, $SEOAIC;

        $data = [
            'email' => $SEOAIC_OPTIONS['seoaic_api_email'],
            'token' => $SEOAIC_OPTIONS['seoaic_api_token'],
            'language' => !empty($SEOAIC_OPTIONS['seoaic_language']) ? $SEOAIC_OPTIONS['seoaic_language'] : 'English',
            'location' => !empty($SEOAIC_OPTIONS['seoaic_location']) ? $SEOAIC_OPTIONS['seoaic_location'] : 'United States',
            'keyword' => $keyword['keyword'],
        ];

        $result = $this->seoaic->curl->init('api/ai/keyword-suggestions', $data, true, true, true);

        $index = -1;

        if (!empty($result['status']) && $result['status'] === 'success') {
            foreach ($SEOAIC_OPTIONS['search_terms'][$search_engine] as $i => $term) {

                if ($term['keyword'] == $keyword['keyword']) {
                    $index = $i;
                    $SEOAIC_OPTIONS['search_terms'][$search_engine][$i]['keyword_suggestions'] = [
                        'last_update' => time(),
                        'data' => $result['data']
                    ];
                }
            }

            update_option('seoaic_options', $SEOAIC_OPTIONS);
        }

        return $index;
    }

    public function keyword_suggestions_list_HTML($keywords, $location)
    {

        global $SEOAIC_OPTIONS;
        $keywords_html = '';
        $search_engine = isset($_GET['search_engine']) ? $_GET['search_engine'] : 'google';
        $all_slugs = array_column($SEOAIC_OPTIONS['search_terms'][$search_engine], 'slug');
        foreach ((array)$keywords as $keyword) {

            $current_slug = $keyword['slug'] . '_' . $location;
            if (in_array($current_slug, $all_slugs)) {
                $selected =
                    '<div class="already-there">' . __('Added', 'seoaic') . '</div>';
            } else {
                $selected =
                    '<div class="check">
                        <input class="seoaic-competitor-check" name="seoaic-check-competitor" type="checkbox" data-slug="' . $keyword['slug'] . '" value="' . $keyword['keyword'] . '">
                    </div>';
            }

            $keywords_html .=
                '<div class="table-row">
                    <div class="keyword">' . $keyword['keyword'] . '</div>
                    <div class="searches">' . $keyword['search_volume'] . '</div>
                    <div class="visibility selected">
                        ' . $selected . '
                    </div>
                </div>';
        }

        return $keywords_html;

    }

    /**
     * Get Keyword Suggestions
     * params
     */
    public function Get_Keyword_Suggestions()
    {
        global $SEOAIC_OPTIONS;

        $search_engine = isset($_GET['search_engine']) ? $_GET['search_engine'] : 'google';

        $current = [];

        foreach ($SEOAIC_OPTIONS['search_terms'][$search_engine] as $keyword) {
            if ($keyword['slug'] === $_POST['keyword']) {
                $current = $keyword;
            }
        }

        if (
            array_key_exists('keyword_suggestions', $current)
            && time() - $current['keyword_suggestions']['last_update'] < DAY_IN_SECONDS * 7
        ) {
            wp_send_json($this->keyword_suggestions_list_HTML($current['keyword_suggestions']['data'], $_POST['location']));
        } else {
            $index = $this->Request_Keyword_Suggestions($current, $search_engine);
            if ($index > -1) {
                wp_send_json($this->keyword_suggestions_list_HTML($SEOAIC_OPTIONS['search_terms'][$search_engine][$index]['keyword_suggestions']['data'], $_POST['location']));
            }
        }

        die();
    }

    /**
     * Get Competitors
     * params
     */
    public function Added_Competitors()
    {
        global $SEOAIC_OPTIONS, $SEOAIC;

        $search_engine = isset($_GET['search_engine']) ? $_GET['search_engine'] : 'google';
        $competitors = isset($_REQUEST['competitors']) ? $_REQUEST['competitors'] : [];
        $keyword_slug = isset($_REQUEST['keyword_slug']) ? $_REQUEST['keyword_slug'] : '';
        $data_id = isset($_REQUEST['data_id']) ? $_REQUEST['data_id'] : 0;

        if ($data_id) {
            $term = $SEOAIC->keywords->getKeywordByID($data_id);
            $serp_data = unserialize($term['serp_data']);

            $serp_data['added_competitors'] = $competitors;
            $this->updateKeywordData($data_id, ['serp_data' => $serp_data]);

        } else {
            foreach ($SEOAIC_OPTIONS['search_terms'][$search_engine] as &$term) {

                if ($term['slug'] == $keyword_slug) {
                    $term['added_competitors'] = $competitors;
                }
            }

            update_option('seoaic_options', $SEOAIC_OPTIONS);
        }

        $list = '';
        if (isset($competitors[0])) {
            $list .= '<ul>';
            foreach ($competitors as $single) {
                if ($single)
                    $list .= '<li><a href="#" class="modal-button" data-modal="#competitor-compare" data-position="' . $single['position'] . '"><span>' . str_replace("www.", "", $single['domain']) . '</span></a><span class="pos"><i class="icon-step-posotion"></i>' . $single['position'] . '</span></li>';
            }
            $list .= '</ul>';
        }

        wp_send_json([
            'status' => 'success',
            'competitors' => $list,
        ]);

        die();
    }

    public function filtering_terms_HTML()
    {

        global $SEOAIC_OPTIONS;

        $search_engine = !empty($_GET['search_engine']) ? $_GET['search_engine'] : 'google';
        $search_terms_data = !empty($SEOAIC_OPTIONS['search_terms'][$search_engine]) ? $SEOAIC_OPTIONS['search_terms'][$search_engine] : [];

        $searches = array_column($search_terms_data, 'search_volume');
        $positions = array_column($search_terms_data, 'position');

        $searches_min = $searches ? min($searches) : 0;
        $searches_min = intval($searches_min) ? $searches_min : 0;
        $searches_max = $searches ? max($searches) : 0;
        $searches_max = intval($searches_max) ? $searches_max : 0;

        $positions_min = $positions ? min($positions) : 0;
        $positions_min = intval($positions_min) ? $positions_min : 0;
        $positions_max = $positions ? max($positions) : 0;
        $positions_max = intval($positions_max) ? $positions_max : 0;

        $html = '<div class="filter-section">
                    <div class="filtering">
                        <form class="top start">
                            <div class="search">
                                <input name="term_name" data-default="" class="search-input terms-filter-input" type="text" placeholder="Keyword">
                            </div>
                            <div class="sort">
                                <label>Search volume</label>
                                <div class="sort-values">
                                    <input name="search_vol_min" data-default="' . $searches_min . '" type="number" class="terms-filter-input" id="from-search-vol" value="' . $searches_min . '" data-default="">
                                    <span class="sep"></span>
                                    <input name="search_vol_max" data-default="' . $searches_max . '" type="number" class="terms-filter-input" id="to-search-vol" value="' . $searches_max . '" data-default="">
                                </div>
                            </div>
                            <div class="sort">
                                <label>Google position</label>
                                <div class="sort-values">
                                    <input name="position_min" type="number" data-default="' . $positions_min . '" class="terms-filter-input" id="from-position" value="' . $positions_min . '" data-default="">
                                    <span class="sep"></span>
                                    <input name="position_max" type="number" data-default="' . $positions_max . '" class="terms-filter-input" id="to-position" value="' . $positions_max . '" data-default="">
                                </div>
                            </div>
                            <a href="#" class="clear-filter">' . __('clear filter', 'seoaic') . '</a>
                        </form>
                    </div>
                </div>';

        if (!empty($_REQUEST['action'])) {

            $term_name = !empty($_REQUEST['term_name']) ? $_REQUEST['term_name'] : '';
            $search_vol_min = !empty($_REQUEST['search_vol_min']) ? $_REQUEST['search_vol_min'] : 0;
            $search_vol_max = !empty($_REQUEST['search_vol_max']) ? $_REQUEST['search_vol_max'] : 0;
            $position_min = !empty($_REQUEST['position_min']) ? $_REQUEST['position_min'] : 0;
            $position_max = !empty($_REQUEST['position_max']) ? $_REQUEST['position_max'] : 0;

            $filter = [
                'keyword' => $term_name,
                'search_vol_min' => $search_vol_min,
                'search_vol_max' => $search_vol_max,
                'position_min' => $position_min,
                'position_max' => $position_max
            ];

            wp_send_json([
                'html' => $this->search_terms_HTML($filter)
            ]);

        }

        return $html;
    }

    public function search_terms_HTML($filter = [])
    {
        global $SEOAIC, $SEOAIC_OPTIONS;
        $search_engine = isset($_GET['search_engine']) ? $_GET['search_engine'] : 'google';
        $search_terms = isset($SEOAIC_OPTIONS['search_terms'][$search_engine]) ? $SEOAIC_OPTIONS['search_terms'][$search_engine] : [];

        if (!empty($filter)) {
            $search_terms = array_filter($search_terms, function ($item) use ($filter) {

                $s = $filter['keyword'];
                if (strlen(trim(preg_replace('/\xc2\xa0/', '', $s))) == 0) {
                    $filter['keyword'] = '';
                }

                return (
                        $filter['keyword'] == '' || strpos($item['keyword'], trim($filter['keyword'])) !== false)
                    &&
                    (($filter['search_vol_min'] == 0 || $item['search_volume'] >= $filter['search_vol_min']) && ($filter['search_vol_max'] == 0 || $item['search_volume'] <= $filter['search_vol_max']))
                    &&
                    (($filter['position_min'] == 0 || $item['position'] >= $filter['position_min']) && ($filter['position_max'] == 0 || $item['position'] <= $filter['position_max']));
            });
        }

        $html = '';
        if (!empty($search_terms)) {
            $html .= '
            <div class="row-line heading">
                <div class="check">
                    <input name="seoaic-select-all-keywords" type="checkbox">
                </div>
                <div class="keyword">
                    ' . __('Keywords', 'seoaic') . '
                    <span class="sorting">
                                    <span class="asc">&#9662</span>
                                    <span class="desc">&#9662</span>
                                </span>
                </div>
                <div class="search_engine">
                    ' . __('Google position', 'seoaic') . '
                    <span class="sorting">
                                    <span class="asc">▾</span>
                                    <span class="desc">▾</span>
                                </span>
                </div>
                <div class="page">
                    ' . __('Page', 'seoaic') . '
                </div>
                <div class="search-vol" data-order="ASC">
                    ' . __('Searches', 'seoaic') . '
                    <span class="sorting">
                                    <span class="asc">&#9662</span>
                                    <span class="desc">&#9662</span>
                                </span>
                </div>
                <div class="competitors">
                    ' . __('Competitors', 'seoaic') . '
                </div>
                <div class="rank-history">
                    ' . __('Rank history', 'seoaic') . '
                </div>
                <div class="rank-location">
                    <select id="rank-location-filter"></select>
                </div>
                <div class="created-posts">
                    ' . __('Created', 'seoaic') . '
                </div>
                <div class="delete"></div>
            </div>';

            foreach ($search_terms as $index => $term) {
                $competitors = '';
                if (isset($term['added_competitors'][0]) && !$term['added_competitors'][0] == '') {
                    $competitors .= '<ul>';
                    foreach ($term['added_competitors'] as $single) {
                        $competitors .= '<li><a href="#" class="modal-button" data-modal="#competitor-compare" data-position="' . $single['position'] . '"><span>' . str_replace("www.", "", $single['domain']) . '</span></a><span class="pos"><i class="icon-step-posotion"></i>' . $single['position'] . '</span></li>';
                    }
                    $competitors .= '</ul>';
                }

                $parent = $term['parent_term'] ? 'data-children="' . $term['parent_term'] . '"' : 'data-parent="' . $term['slug'] . '"';
                $hideGeerateButton = $term['parent_term'] ? ' hide' : '';

                $location = !isset($term['location']) ? '' : $term['location'];
                $rank_history = !isset($term['historical_rank']) || !is_array($term['historical_rank']) ? [] : $term['historical_rank'];

                $html .= '<div class="row-line ' . $term['parent_term'] . '" ' . $parent . '>
                                <div class="check"><input class="seoaic-check-key" name="seoaic-check-key" type="checkbox" data-keyword="' . $term['slug'] . '">
                                </div>
                                <div class="keyword"><span>' . $term['keyword'] . '</span>
                                    <button title="Collapse" type="button" 
                                        class="collapse-button"
                                        data-keyword-name="' . $term['slug'] . '"
                                        data-modal="#seoaic-confirm-modal"
                                        ></button>
                                </div>
                                <div class="search_engine">
                                    ' . (!empty($term['position']) ? '<a href="' . $term['search_page'] . '" target="_blank">' . $term['position'] . '</a>' : '-') . '
                                </div>
                                <div class="page">' . (!empty($term['page']) ? '<a href="' . $term['page'] . '" target="_blank">' . $term['page'] . '</a>' : '-') . '</div>
                                <div class="search-vol">' . (!empty($term['search_volume']) ? $term['search_volume'] : '-') . '</div>
                                
                                <div class="competitors column-key">
                                    ' . $competitors . '
                                    <a href="#"
                                            data-title="Competitors" 
                                            data-index="' . $index . '" 
                                            class="modal-button ' . (empty($term['search_volume']) ? 'disabled' : '') . '"
                                            data-modal="#add-competitors"
                                            data-action="seoaic_get_search_term_competitors"
                                            data-keyword="' . $term['slug'] . '"
                                            data-term-keyword="' . $term['keyword'] . '"
                                    >' . __('+ Show competitors', 'seoaic') . '</a>
                                </div>
                                
                                <div class="rank-history">
                                    <a href="#" 
                                    data-modal="#rank-history" 
                                    data-title="' . __('Rank History', 'seoaic') . '" 
                                    data-chart-id="#chart_term_positions" 
                                    data-chart-type="area" 
                                    data-charts="' . esc_attr(json_encode($rank_history)) . '"
                                    class="modal-button" 
                                    >' . __('View', 'seoaic') . '</a>
                                </div>
                                
                                <div class="rank-location" data-location-slug="' . $this->Slug($location) . '">
                                    <span>' . $location . '</span>
                                </div>
                                
                                <div class="created-posts">
                                    ' . \SEOAIC_RANK::GetCreatedPosts('_idea_keywords_data', $term['keyword']) . '
                                </div>
                                
                                <div class="delete column-key">
                                    <a href="#"
                                            data-title="Generate terms" 
                                            class="modal-button' . $hideGeerateButton . '"
                                            data-modal="#generate-terms"
                                            data-action="seoaicGetKeywordSuggestions"
                                            data-keyword="' . $term['slug'] . '"
                                            data-location="' . $location . '"
                                    >' . __('+Generate Terms', 'seoaic') . '
                                    </a>
                                    <button title="Remove" type="button" class="seoaic-remove modal-button confirm-modal-button"
                                        data-modal="#seoaic-confirm-modal"
                                        data-action="seoaicRemoveSearchTerms"
                                        data-form-callback="window_reload"
                                        data-content="Do you want to remove this keyword?"
                                        data-post-id="' . $term['slug'] . '"
                                        ></button>
                                </div>
                            </div>';
            }
        } else {
            $html .= __('No search terms found', 'seoaic');
        }

        return $html;
    }

    public function get_keywords_languages($id) {
        global $SEOAIC;
        $return = [];
        $location = $SEOAIC->competitors->seoaic_location();
        $search_terms = $SEOAIC->competitors->get_competitor_field_val($id, 'search_terms');
        foreach ($search_terms as $term) {
            $return[] = [
                'keyword' => $term['keyword'] ?? '',
                'location' => $term['location'] ?? $location,
            ];
        }
        return $return;
    }

    public function combine_rank_tracking_positions($term, $term_position, $page, $location)
    {

        global $SEOAIC;

        $id = $SEOAIC->competitors->our_own_website_index();
        $company_location = $SEOAIC->competitors->seoaic_location();

        if (!$id) {
            return;
        }

        $search_terms = $SEOAIC->competitors->get_competitor_field_val($id, 'search_terms');
        $historical_rank = $SEOAIC->competitors->get_competitor_field_val($id, 'historical_rank');
        $search_terms_keywords = $SEOAIC->competitors->get_search_terms_field_val($id, 'keyword');

        if (!in_array($term, $search_terms_keywords) && $term_position > 0 && $location === $company_location) :

            $search_terms[] = [
                'keyword' => $term,
                'position' => $term_position,
                'page' => $page,
                'search_volume' => $this->Search_Volume('google', [$term], '', true)
            ];

            $positions = !empty($historical_rank[0]['metrics']) ? $historical_rank[0]['metrics'] : [];

            if ($term_position >= 1 and $term_position <= 3) {
                $current_positions = !isset($positions['pos_1_3']) ? 0 : $positions['pos_1_3'];
                $historical_rank[0]['metrics']['pos_1_3'] = intval($current_positions) + 1;
            } else if ($term_position >= 4 and $term_position <= 10) {
                $current_positions = !isset($positions['pos_4_10']) ? 0 : $positions['pos_4_10'];
                $historical_rank[0]['metrics']['pos_4_10'] = intval($current_positions) + 1;
            } else if ($term_position >= 11 and $term_position <= 30) {
                $current_positions = !isset($positions['pos_11_30']) ? 0 : $positions['pos_11_30'];
                $historical_rank[0]['metrics']['pos_11_30'] = intval($current_positions) + 1;
            } else if ($term_position >= 31 and $term_position <= 50) {
                $current_positions = !isset($positions['pos_31_50']) ? 0 : $positions['pos_31_50'];
                $historical_rank[0]['metrics']['pos_31_50'] = intval($current_positions) + 1;
            } else if ($term_position >= 51 and $term_position <= 100) {
                $current_positions = !isset($positions['pos_51_100']) ? 0 : $positions['pos_51_100'];
                $historical_rank[0]['metrics']['pos_51_100'] = intval($current_positions) + 1;
            }

            update_term_meta($id, 'search_terms', $search_terms);
            update_term_meta($id, 'historical_rank', $historical_rank);

        endif;
    }

    public function combine_terms_with_my_rank_competitors($term, $position)
    {

        global $SEOAIC;

        $competitors = $SEOAIC->competitors->get_competitors();

        $my_competitor_id = $SEOAIC->competitors->our_own_website_index();

        foreach ($competitors as $competitor) {
            $ID = $competitor->term_id;
            if ($ID === $my_competitor_id) {
                continue;
            }

            $search_terms = $SEOAIC->competitors->get_search_terms($ID);
            foreach ((array)$search_terms as $in => $search_term) {
                if (isset($search_term['keyword']) && $search_term['keyword'] === $term) {
                    $search_terms[$in]['my_rank'] = $position;
                }
            }
            update_term_meta($ID, 'search_terms', $search_terms);
        }
    }


    public function combine_all_ranks()
    {
        global $SEOAIC_OPTIONS, $SEOAIC;
        $search_engine = !isset($_GET['search_engine']) ? 'google' : $_GET['search_engine'];
        foreach ((array)$SEOAIC_OPTIONS['search_terms'][$search_engine] as $term) {
            $SEOAIC->rank->combine_rank_tracking_positions($term['keyword'], $term['position'], $term['page'], $term['location']);
            $SEOAIC->rank->combine_terms_with_my_rank_competitors($term['keyword'], $term['position']);
        }
    }

    /**
     * Get created posts with Search term
     * string $key
     * string $value
     */
    public static function GetCreatedPosts($key, $value)
    {

        $args = array(

            'meta_key' => $key,
            'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private'),

            'meta_query' => array(
                array(
                    'key' => $key,
                    'value' => $value,
                    'compare' => 'LIKE',
                )
            )
        );

        $query = get_posts($args);
        $count = count($query);
        $label = $count == 1 ? __(' post', 'seoaic') : __(' posts', 'seoaic');
        $modal = $count < 1 ? '#generate-ideas' : '#search-terms-posts';

        $posts = '';
        $list_posts = [];

        foreach ($query as $post) {
            $title = $post->post_title;
            $id = $post->ID;
            $status = $post->post_status;
            $link = $status == 'publish' ? get_the_permalink($id) : get_edit_post_link($id);
            $type = $status == 'publish' ? '' : '(' . $status . ')';

            $list_posts[] = [
                "title" => $title,
                "id" => $id,
                "link" => $link,
                "type" => $type
            ];
        }

        $posts .= '<a href="#" href="#" data-action="seoaic_generate_ideas" data-modal="' . $modal . '" class="modal-button confirm-modal-button" data-content="' . esc_attr(json_encode($list_posts)) . '" data-modal-title="' . __('Created posts', 'seoaic') . '">' . $count . $label . '</a>';

        return $posts;

    }

    public function recursiveArrayValues($array, &$result)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $this->recursiveArrayValues($value, $result);
            } elseif ($key === 'text' || $key === 'h_title') {
                $result[] = $value;
            }
        }
    }

    public function short_number($number)
    {
        $base = log($number, 1000);
        $suffixes = array('', 'k', 'm', 'b', 't', 'kv', 'kk', 'mk', 'mm', 'bm', 'tm', 'kvm', 'kkm', 'mkm');

        return round(pow(1000, $base - floor($base)), 1) . $suffixes[floor($base)];
    }

    public function get_content_analysis($url, $target, $keyword, $location) {

        global $SEOAIC_OPTIONS;
        $data = [
            'email' => $SEOAIC_OPTIONS['seoaic_api_email'],
            'token' => $SEOAIC_OPTIONS['seoaic_api_token'],
            'url' => $url,
            'target' => $target,
            'keyword' => $keyword,
            'location' => $location,
            'language' => 'English',
        ];

        return $this->seoaic->curl->init('/api/ai/keyword-stats', $data, true, true, true);
    }

    public function get_data($data, $empty_response = 0) {
        $return = $empty_response;
        if(isset($data)) {
            $return = $data;
        }
        return $return;
    }

    public function parse_content_analysis($url, $keyword, $location)
    {

        if (empty($url)) {
            return [];
        }

        $parse_url = wp_parse_url($url);
        $target = $parse_url['host'];

        $parsing = $this->get_content_analysis($url, $target, $keyword, $location);

        if (!$parsing) {
            return
                [
                    'p' => 0,
                    'rank_count' => 0,
                    'backlinks_count' => 0,
                    'backlinks_short' => '',
                    'h1' => 0,
                    'h2' => 0,
                    'sentences' => 0,
                    'keyword_density' => 0,
                    'words' => 0,
                    'automated_readability_index' => 0,
                    'url' => $url
                ];
        }

        $rank_count = $this->get_data($parsing["data"]["domain_backlinks"]["rank"], 0);
        $backlinks_count = $this->get_data($parsing["data"]["domain_backlinks"]["backlinks"], 0);
        $rank_help = $rank_count ? '<span class="help">Domain Rank: ' . $rank_count . '</span> ' : '';
        $backlinks_help = $backlinks_count ? '<span class="help">Backlinks: ' . $backlinks_count . '</span> ' : '';
        $help = $rank_help || $backlinks_help ? '<div class="help-wrap">' . $rank_help . $backlinks_help . '</div> ' : '';
        $backlinks_short = $rank_count ? $rank_count . ' <small>(' . $this->short_number($backlinks_count) . ')</small>' . $help : '';

        $h1_tags = $this->get_data($parsing["data"]['on_page_seo']['meta']['htags']['h1'], []);
        $h2_tags = $this->get_data($parsing["data"]['on_page_seo']['meta']['htags']['h2'], []);
        $page_score = $this->get_data($parsing["data"]['on_page_seo']['onpage_score'], 0);

        $words = $this->get_data($parsing["data"]['content_analysis']['words'], 0);
        $sentences = $this->get_data($parsing["data"]['content_analysis']['sentences'], 0);
        $automated_readability_index = $this->get_data($parsing["data"]['content_analysis']['automated_readability_index'], 0);
        $plain_text_cleaned = $this->get_data($parsing["data"]['parsed_text'], 0);

        $keyword_density = $keyword && $plain_text_cleaned ? substr_count(strtolower($plain_text_cleaned), strtolower($keyword)) : 0;
        $keyword_density_count = number_format($keyword_density, 0, '');
        $keyword_density_percentage = $keyword_density && $words ? $keyword_density / $words * 100 : 0;
        $keyword_density = $keyword_density_count ? $keyword_density_count . ' <small>(' . number_format($keyword_density_percentage, 2) . '%)</small>' : 0;

        return
            [
                'p' => $page_score,
                'rank_count' => $rank_count,
                'backlinks_count' => $backlinks_count,
                'backlinks_short' => $backlinks_short,
                'h1' => $h1_tags,
                'h2' => $h2_tags,
                'sentences' => $sentences,
                'keyword_density' => $keyword_density,
                'keyword_density_percentage' => $keyword_density_percentage,
                'words' => $words,
                'automated_readability_index' => round($automated_readability_index),
                'url' => $url
            ];
    }

    public function table_row_HTML($analysis_url, $h1_count, $h1_array, $h2_count, $h2_array, $sentences, $keyword_density, $words, $paragraphs, $automated_readability_index, $keyword_density_percentage, $rank_count, $backlinks_short, $backlinks_count, $index)
    {

        $h1_list = '';
        if ($h1_array && is_array($h1_array)) {
            $h1_list .= '<ul>';
            foreach ($h1_array as $li) {
                $h1_list .= '<li>' . esc_html($li) . '</li>';
            }
            $h1_list .= '</ul>';
        } else {
            $h1_list .= '—';
        }

        $h2_list = '';
        if ($h2_array && is_array($h2_array)) {
            $h2_list .= '<ul>';
            foreach ($h2_array as $li) {
                $h2_list .= '<li>' . esc_html($li) . '</li>';
            }
            $h2_list .= '</ul>';
        } else {
            $h2_list .= '—';
        }

        return '<div class="row-line article-analysis" data-url="' . esc_url($analysis_url) . '" data-index="' . $index . '">
                    <div class="h1-titles" data-val="' . $h1_count . '">
                        ' . $h1_count . '
                    </div>
                    <div class="h2-titles" data-val="' . $h2_count . '">
                        ' . $h2_count . '
                    </div>
                    <div class="sentences" data-val="' . $sentences . '">
                        ' . $sentences . '
                    </div>
                    <div class="keyword-density" data-val="' . $keyword_density . '" data-val-percentage="' . $keyword_density_percentage . '">
                        ' . $keyword_density . '
                    </div>
                    <div class="words" data-val="' . $words . '">
                        ' . $words . '
                    </div>
                    <div class="paragraphs" data-val="' . $rank_count . '" data-val-backlinks="' . $backlinks_count . '">
                        ' . $backlinks_short . '
                    </div>
                    <div class="readability" data-val="' . $automated_readability_index . '">
                        ' . $automated_readability_index . '
                    </div>
                </div>
                <div class="article-info">
                    <div class="h1-tags">
                        <div class="inner">
                            <div class="title">H1</div>
                           ' . $h1_list . '
                        </div>
                    </div>
                    <div class="h2-tags">
                        <div class="inner">
                            <div class="title">H2</div>
                            ' . $h2_list . '
                        </div>
                    </div>
                </div>
                ';
    }

    public function analysis_table_HTML($article_analysis, $key)
    {
        $h1 = !empty($article_analysis['h1']) ? $article_analysis['h1'] : '—';
        $h2 = !empty($article_analysis['h2']) ? $article_analysis['h2'] : '—';
        $h1_count = !empty(is_array($h1)) ? count($h1) : '—';
        $h2_count = !empty(is_array($h2)) ? count($h2) : '—';
        $sentences = !empty($article_analysis['sentences']) ? $article_analysis['sentences'] : '—';
        $keyword_density = !empty($article_analysis['keyword_density']) ? $article_analysis['keyword_density'] : '—';
        $keyword_density_percentage = !empty($article_analysis['keyword_density_percentage']) ? $article_analysis['keyword_density_percentage'] : '—';
        $words = !empty($article_analysis['words']) ? $article_analysis['words'] : '—';
        $paragraphs = !empty($article_analysis['p']) ? $article_analysis['p'] : '—';
        $rank_count = !empty($article_analysis['rank_count']) ? $article_analysis['rank_count'] : '—';
        $backlinks_short = !empty($article_analysis['backlinks_short']) ? $article_analysis['backlinks_short'] : '—';
        $backlinks_count = !empty($article_analysis['backlinks_count']) ? $article_analysis['backlinks_count'] : '—';
        $automated_readability_index = !empty($article_analysis['automated_readability_index']) ? $article_analysis['automated_readability_index'] : '—';
        $analysis_url = !empty($article_analysis['url']) ? $article_analysis['url'] : '—';

        return $this->table_row_HTML($analysis_url, $h1_count, $h1, $h2_count, $h2, $sentences, $keyword_density, $words, $paragraphs, $automated_readability_index, $keyword_density_percentage, $rank_count, $backlinks_short, $backlinks_count, $key);
    }

    public function limit_terms_to_compere_store($limit) {
        global $SEOAIC_OPTIONS;
        $terms_to_compere = !isset($SEOAIC_OPTIONS['terms_to_compare']) ? [] : $SEOAIC_OPTIONS['terms_to_compare'];
        $terms_to_compere_google = !isset($SEOAIC_OPTIONS['terms_to_compare']['google']) ? [] : $SEOAIC_OPTIONS['terms_to_compare']['google'];
        if(
            count($terms_to_compere) > $limit
            ||
            count($terms_to_compere_google) > $limit) {
            $last_element = array_pop($terms_to_compere_google);
            array_unshift($terms_to_compere_google, $last_element);
            $SEOAIC_OPTIONS['terms_to_compare']['google'] = array_slice($terms_to_compere_google, 0, 10);
            update_option('seoaic_options', $SEOAIC_OPTIONS);
        }
    }
    public function competitor_article_popup_table_analysis($term_index = false, $keyword = '', $competitors = [], $option_name = 'search_terms', $array_map = false)
    {
        global $SEOAIC_OPTIONS, $SEOAIC;

        $search_engine = 'google';

        $index = isset($_REQUEST['index']) ? intval($_REQUEST['index']) : 0;
        $term_keyword = !isset($_REQUEST['term_keyword']) ? '' : $_REQUEST['term_keyword'];

        if ($term_index) {
            $index = $term_index;
            $term_keyword = $keyword;
        }

        $term_data = !empty($SEOAIC_OPTIONS[$option_name][$search_engine][$index]) ? $SEOAIC_OPTIONS[$option_name][$search_engine][$index] : [];
        $term = !empty($term_data['serp']['data']) ? $term_data['serp']['data'] : [];
        $location = $SEOAIC_OPTIONS[$option_name][$search_engine][$index]['location'] ?? '';
        $updated_competitors = [];
        $i = 0;
        $html = '';
        $break_on = 3;
        $serp_data = [];
        if ($competitors) {
            $term = $this->get_spesific_competitors($competitors, $term);
            $break_on = count($term);
            $location = $_REQUEST['location'];
            $term_keyword = $_REQUEST['keyword'];
        }
        if (isset($_REQUEST['keyword_serp'])) {
            $term_name = $SEOAIC->keywords->getKeywordByID($index);
            $location = $term_name['location'];
            $term_keyword = $term_name['name'];
            $serp_data = unserialize($term_name['serp_data']);
            $term = $serp_data['data'];
            $my_article_analysis = $serp_data['article_analysis'];
        }

        foreach ($term as $key => $competitor) {

            if (!empty($competitor) && empty(array_column($term, 'article_analysis')) || isset($_REQUEST['load_more']) && !array_key_exists('article_analysis', $competitor)) {

                $url = !empty($competitor['url']) ? $competitor['url'] : '';
                $article_analysis = $this->parse_content_analysis($url, $term_keyword, $location);

                if ($serp_data) {
                    $serp_data['data'][$key]['article_analysis'] = $article_analysis;
                    $this->updateKeywordData($index, ['serp_data' => $serp_data]);
                } else {
                    $SEOAIC_OPTIONS[$option_name][$search_engine][$index]['serp']['data'][$key]['article_analysis'] = $article_analysis;
                    update_option('seoaic_options', $SEOAIC_OPTIONS);
                }

                $html .= $this->analysis_table_HTML($article_analysis, $key);
                $i++;
            }

            if ($i == $break_on) {
                break;
            }
        }

        if (isset($_REQUEST['load_more'])) {
            $term = $updated_competitors;
        }

//        if($array_map) {
//            $competitors_has_analysis = array_map(function($item) {
//                return $item['article_analysis'];
//            }, $term);
//        } else {
//            $competitors_has_analysis = array_column($term, 'article_analysis');
//        }

        $competitors_has_analysis = array_column($term, 'article_analysis');

        error_log("<?php\n" . var_export($term, true) . ";\n?>");
        error_log("<?php\n" . var_export($competitors_has_analysis, true) . ";\n?>");
        foreach ($competitors_has_analysis as $key => $competitor) {
            $html .= $this->analysis_table_HTML($competitor, $key);
        }

        if (isset($_REQUEST['index']) && !$term_index) {
            wp_send_json($html);
        }
        return $html;

    }

    public function my_article_popup_top_table_analysis($i = '', $return = false, $option_name = 'search_terms')
    {
        global $SEOAIC_OPTIONS, $SEOAIC;

        $search_engine = 'google';
        $index = isset($_REQUEST['index']) ? intval($_REQUEST['index']) : 0;
        if ($i) {
            $index = $i;
        }
        $term = !empty($SEOAIC_OPTIONS[$option_name][$search_engine][$index]) ? $SEOAIC_OPTIONS[$option_name][$search_engine][$index] : [];
        $page = !empty($term['page']) ? $term['page'] : '';
        $position = !empty($term['position']) ? $term['position'] : '';
        if (isset($_REQUEST['keyword_serp'])) {
            $term = $SEOAIC->keywords->getKeywordByID($index);
            $serp_data = unserialize($term['serp_data']);
            $page = !empty($term['rank_data'][0]['page']) ? $term['rank_data'][0]['page'] : '';
            $position = !empty($term['rank_data'][0]['position']) ? $term['rank_data'][0]['position'] : '';
            $term = $serp_data;
        }

        $page = !empty($page) ? $page : '—';
        $position = !empty($position) ? $position : '—';
        $description = !empty($term['description']) ? $term['description'] : '—';
        $title = !empty($term['title']) ? $term['title'] : '';
        $paragraphs = !empty($page) ? !empty($term['article_analysis']['p']) ? $term['article_analysis']['p'] : '—' : '—';
        $rank_count = !empty($term['article_analysis']['rank_count']) ? $term['article_analysis']['rank_count'] : '—';
        $backlinks_short = !empty($term['article_analysis']['backlinks_short']) ? $term['article_analysis']['backlinks_short'] : '—';
        $backlinks_count = !empty($term['article_analysis']['backlinks_count']) ? $term['article_analysis']['backlinks_count'] : '—';
        $h1 = !empty($page) ? !empty($term['article_analysis']['h1']) ? $term['article_analysis']['h1'] : '—' : '—';
        $h1_count = !empty($page) ? !empty(is_array($h1)) ? count($h1) : '—' : '—';
        $h2 = !empty($page) ? !empty($term['article_analysis']['h2']) ? $term['article_analysis']['h2'] : '—' : '—';
        $h2_count = !empty($page) ? !empty(is_array($h2)) ? count($h2) : '—' : '—';
        $sentences = !empty($page) ? !empty($term['article_analysis']['sentences']) ? $term['article_analysis']['sentences'] : '—' : '—';
        $keyword_density = !empty($page) ? !empty($term['article_analysis']['keyword_density']) ? $term['article_analysis']['keyword_density'] : '—' : '—';
        $words = !empty($page) ? !empty($term['article_analysis']['words']) ? $term['article_analysis']['words'] : '—' : '—';
        $automated_readability_index = !empty($page) ? !empty($term['article_analysis']['automated_readability_index']) ? $term['article_analysis']['automated_readability_index'] : '—' : '—';

        $toggle = $h1 == '—' && $h2 == '—' && $description == '—' ? '' : '<div class="toggle"></div>';

        $h1_list = '';
        if ($h1 && is_array($h1)) {
            $h1_list .= '<ul>';
            foreach ($h1 as $li) {
                $h1_list .= '<li>' . $li . '</li>';
            }
            $h1_list .= '</ul>';
        }

        $h2_list = '';
        if ($h2 && is_array($h2)) {
            $h2_list .= '<ul>';
            foreach ($h2 as $li) {
                $h2_list .= '<li>' . $li . '</li>';
            }
            $h1_list .= '</ul>';
        }

        $html = '<div class="flex-table">
                    <div class="row-line heading">
                        <div class="my-website-info"><span class="label-accent">' . __('My article', 'seoaic') . '</span></div>
                        <div class="google-position">
                            <span class="label">' . __('Google Position', 'seoaic') . '</span>
                        </div>
                        <div class="h1-titles">
                            <span class="label">' . __('H1 Titles', 'seoaic') . '</span>
                        </div>
                        <div class="h2-titles">
                            <span class="label">' . __('H2 Titles', 'seoaic') . '</span>
                        </div>
                        <div class="sentences">
                            <span class="label">' . __('Sentences', 'seoaic') . '</span>
                        </div>
                        <div class="keyword-density">
                            <span class="label">' . __('Keyword density', 'seoaic') . '</span>
                        </div>
                        <div class="words">
                            <span class="label">' . __('Words', 'seoaic') . '</span>
                        </div>
                        <div class="paragraphs">
                            <span class="label">' . __('DR & Backlinks', 'seoaic') . '</span>
                        </div>
                        <div class="readability">
                            <span class="label">' . __('Readability', 'seoaic') . '</span>
                        </div>

                        <div class="delete"></div>
                    </div>
                    <div class="row-line highlight my-article">
                        <div class="my-website-info">
                            <div class="inner">
                                <span class="page-url limit-text-lines lines-1">' . $page . '</span>
                                <span class="page-title limit-text-lines lines-1">' . $title . '</span>
                                ' . $toggle . '
                            </div>
                        </div>
                        <div class="google-position">
                            ' . $position . '
                        </div>
                        <div class="h1-titles">
                            ' . $h1_count . '
                        </div>
                        <div class="h2-titles">
                            ' . $h2_count . '
                        </div>
                        <div class="sentences">
                            ' . $sentences . '
                        </div>
                        <div class="keyword-density">
                            ' . $keyword_density . '
                        </div>
                        <div class="words">
                            ' . $words . '
                        </div>
                        <div class="paragraphs">
                            ' . $backlinks_short . '
                        </div>
                        <div class="readability">
                            ' . $automated_readability_index . '
                        </div>
                        <div class="delete column-key"></div>

                    </div>
                    <div class="my-article-info">
                        <div class="neta">
                            <div class="inner">
                                <div class="title limit-text-lines lines-2">' . __('Page Description', 'seoaic') . '</div>
                                <div class="description limit-text-lines lines-5">' . $description . '</div>
                            </div>
                        </div>
                        <div class="h1-tags">
                            <div class="inner">
                                <div class="title">H1</div>
                               ' . $h1_list . '
                            </div>
                        </div>
                        <div class="h2-tags">
                            <div class="inner">
                                <div class="title">H2</div>
                                ' . $h2_list . '
                            </div>
                            <a href="#" class="my-content-toggle-modal seoaic-view-more">' . __('View more', 'seoaic') . '</a>
                        </div>
                    </div>
                </div>';

        if (isset($_REQUEST['index']) && !$return) {
            wp_send_json($html);
        }
        return $html;
    }
}