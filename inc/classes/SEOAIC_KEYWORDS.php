<?php

namespace SEOAIC;

use Exception;
use SEOAIC\DB\KeywordsPostsTable;
use SEOAIC\helpers\WPTransients;
use SEOAIC\keyword_types\KeywordHeadTermType;
use SEOAIC\keyword_types\KeywordLongTailTermType;
use SEOAIC\keyword_types\KeywordMidTailTermType;
use SEOAIC\relations\KeywordsPostsRelation;

class SEOAIC_KEYWORDS
{
    private $seoaic;
    private const KEYWORD_POST_TYPE = 'seoaic-keyword';
    private const KEYWORDS_CACHE_KEY = 'seoaic_keywords';
    private const KEYWORDS_CATEGORIES_KEY = 'keywords_categories';

    public function __construct($_seoaic)
    {
        $this->seoaic = $_seoaic;

        add_action('wp_ajax_seoaic_update_keywords', [$this, 'updateKeywordsAjax']);
        add_action('wp_ajax_seoaic_generate_keywords_prompt', [$this, 'generateKeywordsAjax']);
        add_action('wp_ajax_seoaic_add_keyword', [$this, 'addKeywordAjax']);
        add_action('wp_ajax_seoaic_set_keyword_link', [$this, 'setKeywordLinkAjax']);
        add_action('wp_ajax_seoaic_remove_keyword', [$this, 'removeKeywordAjax']);
        add_action('wp_ajax_seoaic_remove_and_reassign_keyword', [$this, 'removeAndReassignKeywordAjax']);
        add_action('wp_ajax_seoaic_get_keyword_serp', [$this, 'getKeywordSerp']);
        add_action('wp_ajax_seoaic_get_child_keywords', [$this, 'getChildKeywordsAjax']);
        add_action('wp_ajax_seoaic_keywords_get_siblings_keywords', [$this, 'getSiblingsKeywordsAjax']);
        add_action('wp_ajax_seoaic_keywords_category_add', [$this, 'categoryAddAjax']);
        add_action('wp_ajax_seoaic_keywords_get_categories', [$this, 'getKeywordsCategoriesAjax']);
        add_action('wp_ajax_seoaic_keywords_update_category', [$this, 'updateKeywordsCategory']);
        add_action('wp_ajax_seoaic_keywords_delete_category', [$this, 'deleteKeywordsCategory']);
        add_action('wp_ajax_seoaic_keywords_set_category', [$this, 'categorySetAjax']);
        add_action('wp_ajax_seoaic_keywords_poll_rank_data', [$this, 'getRankBulkAjax']);
        add_action('wp_ajax_seoaic_keyword_get_created_ideas', [$this, 'getCreatedIdeasAjax']);
        add_action('wp_ajax_seoaic_keyword_get_created_posts', [$this, 'getCreatedPostsAjax']);

        add_action('init', [$this, 'createRelationTables'], 0);
        add_action('init', [$this, 'registerCategoriesTaxonomy'], 0);
    }

    public function createRelationTables()
    {
        KeywordsPostsTable::createIfNotExists();
    }

    public function registerCategoriesTaxonomy()
    {
        register_taxonomy(self::KEYWORDS_CATEGORIES_KEY, self::KEYWORD_POST_TYPE, [
            'public' => false,
            'rewrite' => false,
        ]);
    }

    private static function makeKeywordSlug($string)
    {
        return preg_replace('/\s+/', '_', strtolower($string));
    }

    public function makeKeywordTypesOptions()
    {
        $html = '';
        $html .= (new KeywordHeadTermType())->makeOptionTag();
        $html .= (new KeywordMidTailTermType())->makeOptionTag();
        $html .= (new KeywordLongTailTermType())->makeOptionTag();

        return $html;
    }

    public function makeKeywordTypesRadios()
    {
        $html = '';
        $html .= (new KeywordHeadTermType())->makeRadioTag(true);
        $html .= (new KeywordMidTailTermType())->makeRadioTag();
        $html .= (new KeywordLongTailTermType())->makeRadioTag();

        return $html;
    }

    private function isHeadTermType($string = '')
    {
        return (new KeywordHeadTermType())->getName() === $string;
    }

    private function isMidTailTermType($string = '')
    {
        return (new KeywordMidTailTermType())->getName() === $string;
    }

    private function isLongTailTermType($string = '')
    {
        return (new KeywordLongTailTermType())->getName() === $string;
    }

    private function addKeyword($name = '', $parentID = 0)
    {
        if (empty(trim($name))) {
            return false;
        }

        if (!is_numeric($parentID)) {
            $parentID = intval($parentID);
        }

        $id = wp_insert_post([
            'post_title'    => $name,
            'post_type'     => self::KEYWORD_POST_TYPE,
            'post_name'     => self::makeKeywordSlug($name),
            'post_parent'   => $parentID,
        ]);

        if (is_wp_error($id)) {
            return false;
        }

        return $id;
    }

    /**
     * Set Keyword type
     * @param int $id Keyword ID
     * @param string $type Type: head term, mid-tail term, long-tail term
     */
    private function setKeywordType($id, $type): void
    {
        $this->updateKeywordData($id, ['keyword_type' => $type]);
    }

    /**
     * Update Keyword's link
     * @param int $id
     * @param string $link
     */
    private function setKeywordLink($id, $link): bool
    {
        return $this->updateKeywordData($id, ['page_link' => $link]);
    }

    public function convertKeywordsToPosts()
    {
        global $SEOAIC_OPTIONS;

        $keywordsConverted = !empty($SEOAIC_OPTIONS['keywords_converted_to_posts']) ? $SEOAIC_OPTIONS['keywords_converted_to_posts'] : 0;

        if (1 != $keywordsConverted) {
            $keywords = !empty($SEOAIC_OPTIONS['keywords']) ? $SEOAIC_OPTIONS['keywords'] : [];
            foreach ($keywords as $keyword) {
                if ($id = $this->addKeyword($keyword['name'])) {
                    if (isset($keyword['search_volume'])) {
                        update_post_meta($id, 'search_volume', $keyword['search_volume']);
                    }
                    if (isset($keyword['competition'])) {
                        update_post_meta($id, 'competition', $keyword['competition']);
                    }
                    if (isset($keyword['cpc'])) {
                        update_post_meta($id, 'cpc', $keyword['cpc']);
                    }
                    if (isset($keyword['location'])) {
                        update_post_meta($id, 'location', $keyword['location']);
                    }
                    if (isset($keyword['serp']['data'])) {
                        update_post_meta($id, 'serp_data', $keyword['serp']['data']);
                    }
                    if (isset($keyword['serp']['last_update'])) {
                        update_post_meta($id, 'serp_last_update', $keyword['serp']['last_update']);
                    }
                }
            }

            $this->seoaic->set_option('keywords_converted_to_posts', 1);
        }
    }

    private static function normalizeKeywordsFields($keywords = [])
    {
        if (
            !empty($keywords)
            && is_array($keywords)
        ) {
            foreach ($keywords as &$keyword) {
                if (!empty($keyword['rank_data'])) {
                    $keyword['rank_data'] = maybe_unserialize($keyword['rank_data']);
                }
            }
        }

        return $keywords;
    }

    public function getKeywords()
    {
        global $wpdb;

        if ($keywords = WPTransients::getCachedValue(self::KEYWORDS_CACHE_KEY)) {
            return $keywords;
        }

        $query = "SELECT
            p.ID as id,
            p.post_title as name,
            p.post_name as slug,
            p.post_parent as parent_id,
            search_volume__meta.meta_value as search_volume,
            keyword_type__meta.meta_value as keyword_type,
            competition__meta.meta_value as competition,
            cpc__meta.meta_value as cpc,
            rank__meta.meta_value as rank_data,
            rank_last_update__meta.meta_value as rank_last_update,
            rank_request_status__meta.meta_value as rank_request_status,
            search_intent_label__meta.meta_value as search_intent_label,
            location__meta.meta_value as location,
            language__meta.meta_value as lang,
            serp__meta.meta_value as serp_data,
            serp_last_update__meta.meta_value as serp_last_update,
            page_link__meta.meta_value as page_link,
            page_link_table.post_title as page_link_title,
            (
                SELECT GROUP_CONCAT(kp_relation.post_id)
                FROM seoaic_keywords_posts kp_relation
                LEFT JOIN wp_posts p2 on p2.ID = kp_relation.post_id
                WHERE kp_relation.keyword_id = p.ID AND p2.post_type = %s AND p2.post_status = %s
                GROUP BY kp_relation.keyword_id
            ) as ideas_created,
            (
                SELECT GROUP_CONCAT(kp_relation.post_id)
                FROM seoaic_keywords_posts kp_relation
                LEFT JOIN wp_posts p2 on p2.ID = kp_relation.post_id
                WHERE kp_relation.keyword_id = p.ID AND p2.post_type != %s AND p2.post_status != %s
                GROUP BY kp_relation.keyword_id
            ) as posts_created
        FROM {$wpdb->prefix}posts p
        LEFT JOIN {$wpdb->prefix}postmeta search_volume__meta on p.ID=search_volume__meta.post_id and search_volume__meta.meta_key='search_volume'
        LEFT JOIN {$wpdb->prefix}postmeta competition__meta on p.ID=competition__meta.post_id and competition__meta.meta_key='competition'
        LEFT JOIN {$wpdb->prefix}postmeta cpc__meta on p.ID=cpc__meta.post_id and cpc__meta.meta_key='cpc'
        LEFT JOIN {$wpdb->prefix}postmeta rank__meta on p.ID=rank__meta.post_id and rank__meta.meta_key='rank_data'
        LEFT JOIN {$wpdb->prefix}postmeta rank_last_update__meta on p.ID=rank_last_update__meta.post_id and rank_last_update__meta.meta_key='rank_last_update'
        LEFT JOIN {$wpdb->prefix}postmeta rank_request_status__meta on p.ID=rank_request_status__meta.post_id and rank_request_status__meta.meta_key='rank_request_status'
        LEFT JOIN {$wpdb->prefix}postmeta location__meta on p.ID=location__meta.post_id and location__meta.meta_key='location'
        LEFT JOIN {$wpdb->prefix}postmeta language__meta on p.ID=language__meta.post_id and language__meta.meta_key='language'
        LEFT JOIN {$wpdb->prefix}postmeta search_intent_label__meta on p.ID=search_intent_label__meta.post_id and search_intent_label__meta.meta_key='search_intent_label'
        LEFT JOIN {$wpdb->prefix}postmeta serp__meta on p.ID=serp__meta.post_id and serp__meta.meta_key='serp_data'
        LEFT JOIN {$wpdb->prefix}postmeta serp_last_update__meta on p.ID=serp_last_update__meta.post_id and serp_last_update__meta.meta_key='serp_last_update'
        LEFT JOIN {$wpdb->prefix}postmeta keyword_type__meta on p.ID=keyword_type__meta.post_id and keyword_type__meta.meta_key='keyword_type'
        LEFT JOIN {$wpdb->prefix}postmeta page_link__meta on p.ID=page_link__meta.post_id and page_link__meta.meta_key='page_link'
        LEFT JOIN {$wpdb->prefix}posts page_link_table on page_link_table.ID=page_link__meta.meta_value and page_link__meta.meta_key='page_link'
        WHERE p.post_type=%s";

        $preparedQuery = $wpdb->prepare($query, [
            [SEOAIC_IDEAS::IDEA_TYPE],
            [SEOAIC_IDEAS::IDEA_STATUS],
            [SEOAIC_IDEAS::IDEA_TYPE],
            [SEOAIC_IDEAS::IDEA_STATUS],
            self::KEYWORD_POST_TYPE,
        ]);
        $keywords = $wpdb->get_results($preparedQuery, ARRAY_A);

        WPTransients::cacheValue(self::KEYWORDS_CACHE_KEY, $keywords, 30);

        return self::normalizeKeywordsFields($keywords);
    }

    public function getKeywordByID($id)
    {
        if (!is_numeric($id)) {
            return false;
        }

        $keyword = $this->getKeywordsByIDs([$id]);
        if (
            $keyword
            && !empty($keyword[0])
        ) {
            return $keyword[0];
        }

        return false;
    }

    /**
     * Get Keywords by IDs
     * @param array $ids
     * @return array
     */
    public function getKeywordsByIDs($ids = [])
    {
        global $wpdb;

        if (empty($ids)) {
            return false;
        } elseif (
            !is_array($ids)
            && is_numeric($ids)
        ) {
            $ids = [$ids];
        }

        $idsCount = count($ids);
        $idsPlaceholders = implode(', ', array_fill(0, $idsCount, '%d'));

        $query = "SELECT
            p.ID as id,
            p.post_title as name,
            p.post_name as slug,
            p.post_parent as parent_id,
            keyword_type__meta.meta_value as keyword_type,
            search_volume__meta.meta_value as search_volume,
            competition__meta.meta_value as competition,
            cpc__meta.meta_value as cpc,
            rank__meta.meta_value as rank_data,
            rank_last_update__meta.meta_value as rank_last_update,
            rank_request_status__meta.meta_value as rank_request_status,
            search_intent_label__meta.meta_value as search_intent_label,
            location__meta.meta_value as location,
            language__meta.meta_value as lang,
            serp__meta.meta_value as serp_data,
            serp_last_update__meta.meta_value as serp_last_update,
            page_link__meta.meta_value as page_link,
            page_link_table.post_title as page_link_title,
            (
                SELECT GROUP_CONCAT(kp_relation.post_id)
                FROM seoaic_keywords_posts kp_relation
                LEFT JOIN wp_posts p2 on p2.ID = kp_relation.post_id
                WHERE kp_relation.keyword_id = p.ID AND p2.post_type = %s AND p2.post_status = %s
                GROUP BY kp_relation.keyword_id
            ) as ideas_created,
            (
                SELECT GROUP_CONCAT(kp_relation.post_id)
                FROM seoaic_keywords_posts kp_relation
                LEFT JOIN wp_posts p2 on p2.ID = kp_relation.post_id
                WHERE kp_relation.keyword_id = p.ID AND p2.post_type != %s AND p2.post_status != %s
                GROUP BY kp_relation.keyword_id
            ) as posts_created
        FROM {$wpdb->prefix}posts p
        LEFT JOIN {$wpdb->prefix}postmeta search_volume__meta on p.ID=search_volume__meta.post_id and search_volume__meta.meta_key='search_volume'
        LEFT JOIN {$wpdb->prefix}postmeta competition__meta on p.ID=competition__meta.post_id and competition__meta.meta_key='competition'
        LEFT JOIN {$wpdb->prefix}postmeta cpc__meta on p.ID=cpc__meta.post_id and cpc__meta.meta_key='cpc'
        LEFT JOIN {$wpdb->prefix}postmeta rank__meta on p.ID=rank__meta.post_id and rank__meta.meta_key='rank_data'
        LEFT JOIN {$wpdb->prefix}postmeta rank_last_update__meta on p.ID=rank_last_update__meta.post_id and rank_last_update__meta.meta_key='rank_last_update'
        LEFT JOIN {$wpdb->prefix}postmeta rank_request_status__meta on p.ID=rank_request_status__meta.post_id and rank_request_status__meta.meta_key='rank_request_status'
        LEFT JOIN {$wpdb->prefix}postmeta location__meta on p.ID=location__meta.post_id and location__meta.meta_key='location'
        LEFT JOIN {$wpdb->prefix}postmeta language__meta on p.ID=language__meta.post_id and language__meta.meta_key='language'
        LEFT JOIN {$wpdb->prefix}postmeta search_intent_label__meta on p.ID=search_intent_label__meta.post_id and search_intent_label__meta.meta_key='search_intent_label'
        LEFT JOIN {$wpdb->prefix}postmeta serp__meta on p.ID=serp__meta.post_id and serp__meta.meta_key='serp_data'
        LEFT JOIN {$wpdb->prefix}postmeta serp_last_update__meta on p.ID=serp_last_update__meta.post_id and serp_last_update__meta.meta_key='serp_last_update'
        LEFT JOIN {$wpdb->prefix}postmeta keyword_type__meta on p.ID=keyword_type__meta.post_id and keyword_type__meta.meta_key='keyword_type'
        LEFT JOIN {$wpdb->prefix}postmeta page_link__meta on p.ID=page_link__meta.post_id and page_link__meta.meta_key='page_link'
        LEFT JOIN {$wpdb->prefix}posts page_link_table on page_link_table.ID=page_link__meta.meta_value and page_link__meta.meta_key='page_link'
        WHERE p.post_type=%s
        AND p.ID in (" . $idsPlaceholders . ")";

        $preparedQuery = $wpdb->prepare($query, array_merge(
            [SEOAIC_IDEAS::IDEA_TYPE],
            [SEOAIC_IDEAS::IDEA_STATUS],
            [SEOAIC_IDEAS::IDEA_TYPE],
            [SEOAIC_IDEAS::IDEA_STATUS],
            [self::KEYWORD_POST_TYPE],
            $ids
        ));
        $keywords = $wpdb->get_results($preparedQuery, ARRAY_A);

        return self::normalizeKeywordsFields($keywords);
    }

    /**
     * Converts from WP_Post object or array into Keywords array
     */
    public function convertFormatFromPostsToKeywords($keywordPosts = [])
    {
        $keywords = [];

        if (!empty($keywordPosts)) {
            $keywordsIds = array_map(function ($item) {
                if (is_array($item)) {
                    return $item['ID'];
                }
                if (is_object($item)) {
                    return $item->ID;
                }
                return '';
            }, $keywordPosts);
            $keywords = $this->getKeywordsByIDs($keywordsIds);
        }

        return $keywords;
    }

    /**
     * Gets Keywords by type.
     * @param object $typeObject instanse of Key
     */
    public function getKeywordsByType($typeObject = null)
    {
        $result = [];

        if (
            is_null($typeObject)
            || !is_a($typeObject, 'SEOAIC\keyword_types\KeywordBaseType')
        ) {
            error_log(' '.print_r('! SEOAIC\keyword_types\KeywordBaseType', true));
            return $result;
        }

        $keywords = $this->getKeywords();
        $isHeadTermType = $this->isHeadTermType($typeObject->getName());

        foreach ($keywords as $keyword) {
            if (
                $keyword['keyword_type'] == $typeObject->getName()
                || (
                    $isHeadTermType
                    && is_null($keyword['keyword_type'])
                )
            ) {
                $result[] = $keyword;
            }
        }

        return $result;
    }

    public function getChildKeywordsAjax()
    {
        if (
            empty($_REQUEST['id'])
            || !is_numeric($_REQUEST['id'])
        ) {
            SEOAICAjaxResponse::error('Keyword not selected!')->wpSend();
        }

        $keywords = $this->getChildKeywordsByParentID($_REQUEST['id']);
        $fields = [
            'back_action' => !empty($_REQUEST['back_action']) ? $_REQUEST['back_action'] : '',
            'keywords' => $keywords,
        ];
        if (
            isset($_REQUEST['options_html'])
            && 1 == $_REQUEST['options_html']
        ) {
            $fields['options_html'] = $this->makeKeywordsOptionsTags($keywords);
        }

        SEOAICAjaxResponse::success()->addFields($fields)->wpSend();
    }

    public function getSiblingsKeywordsAjax()
    {
        if (
            empty($_REQUEST['keyword_id'])
            || !is_numeric($_REQUEST['keyword_id'])
        ) {
            SEOAICAjaxResponse::error('Keyword not selected!')->wpSend();
        }

        $currentKeyword = $this->getKeywordByID($_REQUEST['keyword_id']);

        $childKeywords = $this->getChildKeywordsByParentID($currentKeyword['parent_id']);
        $filteredChildKeywords = array_filter($childKeywords, function ($item) use ($currentKeyword) {
            return $item['id'] != $currentKeyword['id'];
        });

        $returnArray = array_map(function ($item) {
            return [
                'id'    => $item['id'],
                'name'  => $item['name'],
            ];
        }, $filteredChildKeywords);

        $fields = [
            'keywords' => $returnArray,
        ];

        if (
            isset($_REQUEST['options_html'])
            && 1 == $_REQUEST['options_html']
        ) {
            $fields['options_html'] = $this->makeKeywordsOptionsTags($filteredChildKeywords);
        }

        SEOAICAjaxResponse::success()->addFields($fields)->wpSend();
    }

    /**
     * Get child keywords by ID
     */
    public function getChildKeywordsByParentID($id = null)
    {
        if (
            is_null($id)
            || !is_numeric($id)
        ) {
            return [];
        }

        $args = [
            'post_type' => self::KEYWORD_POST_TYPE,
            'post_status' => 'any',
            'post_parent' => $id,
            'posts_per_page' => -1,
            'fields' => 'ids'
        ];

        $childKeywordsIDs = get_posts($args);
        $childKeywords = $this->getKeywordsByIDs($childKeywordsIDs);

        return $childKeywords ? $childKeywords : [];
    }

    public static function makeKeywordsOptionsTags($keywords = []): string
    {
        $html = '';

        if (
            !empty($keywords)
            && is_array($keywords)
        ) {
            foreach ($keywords as $keyword) {
                $html .= '<option value="' . esc_attr($keyword['id']) . '">' . esc_html($keyword['name']) . '</option>';
            }

        } else {
            $html .= '<option value="" disabled>Nothing found</option>';
        }

        return $html;
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
            && (int) $keyword == $keyword
        ) {
            $id = $keyword;
        } else {
            $id = $keyword['id'];
        }

        $updateRes = wp_update_post([
            'ID'            => $id,
            'meta_input'    => $data,
        ]);

        if (is_wp_error($updateRes)) {
            return false;
        }

        WPTransients::deleteCachedValue(self::KEYWORDS_CACHE_KEY);

        return true;
    }

    /**
     * Generate keywords prompt
     */
    public function generateKeywordsAjax()
    {
        $n = !empty($_REQUEST['keywords_count']) ? $_REQUEST['keywords_count'] : 1;
        $prompt = !empty($_REQUEST['keywords_prompt']) ? $_REQUEST['keywords_prompt'] : '';
        $type = !empty($_REQUEST['keyword_type'][0]) ? $_REQUEST['keyword_type'][0] : '';
        $formData = [
            'head_term_id'  => !empty($_REQUEST['head_term_id']) ? intval($_REQUEST['head_term_id']) : '',
            'mid_tail_id'   => !empty($_REQUEST['mid_tail_id']) ? intval($_REQUEST['mid_tail_id']) : '',
        ];

        $keywords = $this->generate($n, $prompt, $type, true, $formData);

        SEOAICAjaxResponse::success('updated')->addFields([
            'content' => [
                'content' => $this->makeKeywordsTableMarkup($keywords),
            ],
        ])->wpSend();
    }

    /**
     * Generate keywords
     * @param int $n count of keywords to be generated
     * @param string $prompt prompt
     * @param string $type Keyword type (head, mid-tail, long-tail term). Optional. Head term by default
     * @param bool $return_new return only new generated or all keywords
     */
    public function generate($n, $prompt = '', $type = null, $return_new = false, $formData = [])
    {
        global $SEOAIC_OPTIONS;

        $parentPostId = 0;
        $generatedKeywordsIDs = [];
        $generatedKeywordsArray = [];
        $type = !empty($type) ? $type : (new KeywordHeadTermType())->getName();
        $location = sanitize_text_field($_REQUEST['location']);
        $language = sanitize_text_field($_REQUEST['language']);
        // $currentKeywords = $this->getKeywords();
        // $currentKeywordsNames = array_map(function ($kw) {
        //     return $kw['name'];
        // }, $currentKeywords);

        // $data = [
        //     'title'         => SEOAIC_SETTINGS::getBusinessName(),
        //     'language'      => $language,
        //     'description'   => SEOAIC_SETTINGS::getBusinessDescription(),
        //     'industry'      => SEOAIC_SETTINGS::getIndustry(),
        //     'content'       => $prompt,
        //     'domain'        => get_bloginfo('url'),
        //     'prompt'        => $prompt,
        //     'n'             => intval($n),
        //     'current'       => implode(', ', $currentKeywordsNames),
        // ];

        // $keywordsAddResult = $this->seoaic->curl->init('api/ai/keywords', $data, true, false, true);

        if ($this->isHeadTermType($type)) {
            $generatedKeywordsArray = $this->generateHeadTermKeywords($language, $prompt, intval($n));

        } elseif ($this->isMidTailTermType($type)) {
            $formData['location'] = $location;
            $formData['language'] = $language;
            $formData['limit'] = $n;
            $generatedKeywordsArray = $this->generateMidTailTermKeywords($formData);
            $parentPostId = !empty($_REQUEST['head_term_id']) ? intval($_REQUEST['head_term_id']) : 0;

        } elseif ($this->isLongTailTermType($type)) {
            $formData['location'] = $location;
            $formData['language'] = $language;
            $formData['limit'] = $n;
            $generatedKeywordsArray = $this->generateLongTailTermKeywords($formData);
            $parentPostId = !empty($_REQUEST['mid_tail_id']) ? intval($_REQUEST['mid_tail_id']) : 0;
        }

        if (empty($generatedKeywordsArray)) {
            SEOAICAjaxResponse::error('No unique Keywords were generated!')->wpSend();
        }

        foreach ($generatedKeywordsArray as $keyword) {
            if ($id = $this->addKeyword($keyword, $parentPostId)) {
                $generatedKeywordsIDs[] = $id;
                $this->setKeywordType($id, $type);
            }
        }

        $newKeywords = $this->getKeywordsByIDs($generatedKeywordsIDs);

        $fieldsData = [
            'keywords' => $newKeywords,
            'location' => $location,
            'language' => $language,
            'search_volumes' => [],
            'search_intents' => [],
        ];

        $data = [
            'keywords' => $generatedKeywordsArray,
            'location' => $location,
            'language' => $language,
            'mode' => 'auto',
            'email' => $SEOAIC_OPTIONS['seoaic_api_email'],
        ];

        if (!empty($generatedKeywordsArray)) {
            if ($kwSearchVolumeResult = $this->requestSearchVolumes($data)) {
                $fieldsData['search_volumes'] = $kwSearchVolumeResult['data'];
            }

            if ($keywordsSearchIntents = $this->requestSearchIntent($data, $newKeywords)) {
                $fieldsData['search_intents'] = $keywordsSearchIntents;
            }

            $this->queueRank($data, $newKeywords);
            // if (false !== $keywordsRank) {
            //     $fieldsData['rank'] = $keywordsRank;
            // }

            $this->updateKeywordsFields($fieldsData);
        }

        WPTransients::deleteCachedValue(self::KEYWORDS_CACHE_KEY);

        if ($return_new) {
            if (empty($newKeywords)) {
                return [];
            }

            return $this->getKeywordsByIDs($generatedKeywordsIDs);
        }

        return $this->getKeywords();
    }

    /**
     * Makes request to backend (ChatGPT) to generate Head Term keywords
     * @param string $language
     * @param string $prompt
     * @param int $n number of posts to generate. Default 1
     * @return array
     */
    private function generateHeadTermKeywords($language, $prompt = '', $n = 1): array
    {
        $currentKeywords = $this->getKeywords();
        $currentKeywordsNames = array_map(function ($kw) {
            return $kw['name'];
        }, $currentKeywords);

        $data = [
            'title'         => SEOAIC_SETTINGS::getBusinessName(),
            'language'      => $language,
            'description'   => SEOAIC_SETTINGS::getBusinessDescription(),
            'industry'      => SEOAIC_SETTINGS::getIndustry(),
            'content'       => $prompt,
            'domain'        => get_bloginfo('url'),
            'prompt'        => $prompt,
            'n'             => intval($n),
            'current'       => implode(', ', $currentKeywordsNames),
        ];

        $keywordsAddResult = $this->seoaic->curl->init('api/ai/keywords', $data, true, false, true);
        if (
            empty($keywordsAddResult['status'])
            || $keywordsAddResult['status'] !== 'success'
            || empty($keywordsAddResult['content'])
        ) {
            SEOAICAjaxResponse::alert('Keywords not generated!')->wpSend();
        }

        $skipped_keywords = $this->removeDuplicatedKeywordsFromInput($keywordsAddResult['content'], $currentKeywords);
        $generatedKeywordsArray = $keywordsAddResult['content'];

        return $generatedKeywordsArray;
    }

    private function generateMidTailTermKeywords($formData = [])
    {
        global $SEOAIC_OPTIONS;

        $limit = !empty($formData['limit']) ? $formData['limit'] : 1;
        $data = [
            'location'  => !empty($formData['location']) ? $formData['location'] : '',
            'language'  => !empty($formData['language']) ? $formData['language'] : '',
            // 'limit'     => $limit,
            'limit'     => 8, // max available related keywords for 0 level from DataForSEO
            'keyword'   => '',
            'email'     => $SEOAIC_OPTIONS['seoaic_api_email'],
        ];

        if (
            !empty($formData['head_term_id'])
            && is_numeric($formData['head_term_id'])
        ) {
            $keyword = $this->getKeywordByID($formData['head_term_id']);

            if (!empty($keyword)) {
                $data['keyword'] = $keyword['name'];
            }
        }

        foreach ($data as $key => $value) {
            if (empty($value)) {
                SEOAICAjaxResponse::error('Empty required field "' . $key . '"');
            }
        }

        // $keywordsAddResult = $this->seoaic->curl->init('/api/ai/keyword-suggestions', $data, true, false, true);
        $keywordsAddResult = $this->seoaic->curl->init('/api/ai/related-keywords', $data, true, false, true);

        if (
            empty($keywordsAddResult['status'])
            || $keywordsAddResult['status'] !== 'success'
            || empty($keywordsAddResult['data'])
        ) {
            SEOAICAjaxResponse::alert('Keywords not generated!')->wpSend();
        }

        // $generatedKeywordsArray = array_map(function ($item) {
        //     return $item['keyword'];
        // }, $keywordsAddResult['data']);
        $generatedKeywordsArray = $keywordsAddResult['data'];

        $currentKeywords = $this->getKeywords();
        $skipped_keywords = $this->removeDuplicatedKeywordsFromInput($generatedKeywordsArray, $currentKeywords);

        if (count($generatedKeywordsArray) > $limit) {
            $generatedKeywordsArray = array_slice($generatedKeywordsArray, 0, $limit);
        }

        return $generatedKeywordsArray;
    }

    private function generateLongTailTermKeywords($formData = [])
    {
        global $SEOAIC_OPTIONS;

        $data = [
            'location'  => !empty($formData['location']) ? $formData['location'] : '',
            'language'  => !empty($formData['language']) ? $formData['language'] : '',
            'limit'     => !empty($formData['limit']) ? $formData['limit'] : 1,
            'keyword'   => '',
            'email'     => $SEOAIC_OPTIONS['seoaic_api_email'],
        ];

        if (
            !empty($formData['head_term_id'])
            && is_numeric($formData['head_term_id'])
        ) {
            $keyword = $this->getKeywordByID($formData['mid_tail_id']);

            if (!empty($keyword)) {
                $data['keyword'] = $keyword['name'];
            }
        }

        foreach ($data as $key => $value) {
            if (empty($value)) {
                SEOAICAjaxResponse::error('Empty required field "' . $key . '"');
            }
        }

        $keywordsAddResult = $this->seoaic->curl->init('/api/ai/related-keywords', $data, true, false, true);

        if (
            empty($keywordsAddResult['status'])
            || $keywordsAddResult['status'] !== 'success'
            || empty($keywordsAddResult['data'])
        ) {
            SEOAICAjaxResponse::alert('Keywords not generated!')->wpSend();
        }

        $generatedKeywordsArray = $keywordsAddResult['data'];

        $currentKeywords = $this->getKeywords();
        $skipped_keywords = $this->removeDuplicatedKeywordsFromInput($generatedKeywordsArray, $currentKeywords);

        return $generatedKeywordsArray;
    }

    /**
     * Get statistic for keywords and returns it with keywords
     * @param bool $manual
     * @param string $mode
     * @param array $keywords optional field. Gets statistics for all keywords if no data are provided
     * @return array
     */
    public function generateKeywordsStat(bool $manual, string $mode, $keywords = [])
    {
        global $SEOAIC_OPTIONS;

        $keywords = !empty($keywords) && is_array($keywords) ? $keywords : $this->getKeywords();
        $ids = array_map(function ($kw) {
            return $kw['id'];
        }, $keywords);

        if (empty($keywords)) {
            return [];
        }

        $update = $manual ? $SEOAIC_OPTIONS['keywords_stat_update_manual'] : $SEOAIC_OPTIONS['keywords_stat_update'];

        $keywords = array_map(function ($kw) {
            if (empty($kw['lang'])) {
                $firstLang = $this->seoaic->multilang->getFirstLanguageByLocationName($kw['location']);
                $kw['lang'] = $firstLang['name'];
            }
            return $kw;
        }, $keywords);

        if (time() > $update) {
            $requestData = [
                // 'keywords' => $keywordsNames,
                'keywords' => $keywords,
                'location' => '',
                'language' => '',
                'mode' => $mode,
                'email' => $SEOAIC_OPTIONS['seoaic_api_email'],
            ];

            $fieldsData = [
                'keywords' => $keywords,
                'search_volumes' => [],
                'search_intents' => [],
            ];

            $fieldsData['search_volumes'] = $this->getSearchVolumes($requestData, $manual);
            $fieldsData['search_intents'] = $this->getSearchIntents($requestData);
            // error_log('fieldsData '.print_r($fieldsData, true));
            $this->queueRank($requestData, $keywords);

            $this->updateKeywordsFields($fieldsData);
        }

        return $this->getKeywordsByIDs($ids);
    }

    private function updateKeywordsFields($fieldsData = [])
    {
        foreach ($fieldsData['keywords'] as $keyword) {
            $keywordNameLower = strtolower($keyword['name']);
            $newData = [
                'location' => isset($fieldsData['location']) ? $fieldsData['location'] : (!empty($keyword['location']) ? $keyword['location'] : ''),
                'language' => isset($fieldsData['language']) ? $fieldsData['language'] : (!empty($keyword['lang']) ? $keyword['lang'] : ''),
            ];

            if (
                !empty($fieldsData['search_volumes'])
                && is_array($fieldsData['search_volumes'])
            ) {
                foreach ($fieldsData['search_volumes'] as $keywordSearchVolume) {
                    if ($keyword['name'] !== $keywordSearchVolume['keyword']) {
                        continue;
                    }

                    $newData['search_volume'] = !empty($keywordSearchVolume['search_volume']) ? $keywordSearchVolume['search_volume'] : '';
                    $newData['competition'] = !empty($keywordSearchVolume['competition']) ? $keywordSearchVolume['competition'] : '';
                    $newData['cpc'] = !empty($keywordSearchVolume['cpc']) ? $keywordSearchVolume['cpc'] : '';
                    break;
                }
            }

            if (
                !empty($fieldsData['search_intents'])
                && is_array($fieldsData['search_intents'])
                && array_key_exists($keywordNameLower, $fieldsData['search_intents'])
            ) {
                $newData['search_intent_label'] = $fieldsData['search_intents'][$keywordNameLower]['label'];
            }

            // if (
            //     !empty($fieldsData['rank'][$keyword['name']])
            // ) {
            //     $keywordRankData = $fieldsData['rank'][$keyword['name']];

            //     if (!empty($keywordRankData['positions'])) {
            //         $newData['rank_data'] = $keywordRankData['positions'];
            //     }

            //     if (!empty($keywordRankData['rank_last_update'])) {
            //         $newData['rank_last_update'] = $keywordRankData['rank_last_update'];
            //     }
            // }

            $this->updateKeywordData($keyword, $newData);
        }
    }

    /**
     * Update keywords
     */
    public function updateKeywordsAjax()
    {
        global $SEOAIC_OPTIONS;

        $keywords = $this->getKeywords();
        $keywords = $this->generateKeywordsStat(true, 'manual', $keywords);
        $next = date('d/m/Y H:i:s', $SEOAIC_OPTIONS['keywords_stat_update_manual']);

        SEOAICAjaxResponse::success('updated')->addFields([
            'content' => [
                'content' => $this->makeKeywordsTableMarkup($keywords),
                'notify' => esc_html('Keywords have been updated. The next manual update is available on ') . $next,
            ],
        ])->wpSend();
    }

    /**
     * Add keywords
     */
    public function addKeywordAjax()
    {
        global $SEOAIC_OPTIONS;

        if (empty($_REQUEST['item_name'])) {
            SEOAICAjaxResponse::error('Value can`t be empty!')->wpSend();
        }
        if (
            empty($_REQUEST['location'])
            || empty($_REQUEST['language'])
        ) {
            SEOAICAjaxResponse::error('Location/Language can`t be empty!')->wpSend();
        }

        $keywordsNamesArray = explode(',', stripslashes(sanitize_text_field($_REQUEST['item_name'])));
        $keywordsNamesArray = array_map('trim', $keywordsNamesArray);
        $keywordsNamesArray = array_filter($keywordsNamesArray);
        $keywordsNamesArray = array_unique($keywordsNamesArray);

        if (empty($keywordsNamesArray)) {
            SEOAICAjaxResponse::error('No unique keywords found!')->wpSend();
        }

        $skippedKeywords = $this->removeDuplicatedKeywordsFromInput($keywordsNamesArray);
        // $location = SEOAIC_SETTINGS::getLocation();
        // $language = SEOAIC_SETTINGS::getLanguage();
        $location = sanitize_text_field($_REQUEST['location']);
        $language = sanitize_text_field($_REQUEST['language']);
        $ids = [];
        $addedKeywordsNames = [];
        $parentPostId = 0;
        $keywordType = !empty($_REQUEST['keyword_type'][0]) ? $_REQUEST['keyword_type'][0] : '';

        if ($this->isMidTailTermType($keywordType)) {
            if (
                empty($_REQUEST['head_term_id'])
                || !is_numeric($_REQUEST['head_term_id'])
                || intval($_REQUEST['head_term_id']) != $_REQUEST['head_term_id']
            ) {
                SEOAICAjaxResponse::error('No Head Term selected!')->wpSend();
            }

            $parentPostId = intval($_REQUEST['head_term_id']);

        } elseif ($this->isLongTailTermType($keywordType)) {
            if (
                empty($_REQUEST['mid_tail_id'])
                || !is_numeric($_REQUEST['mid_tail_id'])
                || intval($_REQUEST['mid_tail_id']) != $_REQUEST['mid_tail_id']
            ) {
                SEOAICAjaxResponse::error('No Mid-Tail Term selected!')->wpSend();
            }

            $parentPostId = intval($_REQUEST['mid_tail_id']);
        }

        foreach ($keywordsNamesArray as $keywordName) {
            if ($id = $this->addKeyword($keywordName, $parentPostId)) {
                $ids[] = $id;
                $addedKeywordsNames[] = strtolower($keywordName);
                $this->setKeywordType($id, $keywordType);
            }
        }

        $addedKeywords = $this->getKeywordsByIDs($ids);

        if (!$addedKeywords) {
            SEOAICAjaxResponse::error('No keywords were added!')->wpSend();
        }

        $fieldsData = [
            'keywords' => $addedKeywords,
            'location' => $location,
            'language' => $language,
            'search_volumes' => [],
            'search_intents' => [],
        ];

        $data = [
            'keywords' => $keywordsNamesArray,
            'location' => $location,
            'language' => $language,
            'mode' => 'auto',
            'email' => $SEOAIC_OPTIONS['seoaic_api_email'],
        ];

        if ($kwSearchVolumeResult = $this->requestSearchVolumes($data)) {
            $fieldsData['search_volumes'] = $kwSearchVolumeResult['data'];
        }

        if ($keywordsSearchIntents = $this->requestSearchIntent($data, $addedKeywords)) {
            $fieldsData['search_intents'] = $keywordsSearchIntents;
        }

        $this->queueRank($data, $addedKeywords);
        // if (false !== $keywordsRank) {
        //     $fieldsData['rank'] = $keywordsRank;
        // }

        $this->updateKeywordsFields($fieldsData);

        $message = [];
        if ($addedKeywordsNames) {
            $message[] = 'Keywords «' . implode(", ", $addedKeywordsNames) . '» have been added!';
        }
        if ($skippedKeywords) {
            $message[] = 'Keywords «' . implode(", ", $skippedKeywords) . '» have been skipped (duplicates)!';
        }

        WPTransients::deleteCachedValue(self::KEYWORDS_CACHE_KEY);

        SEOAICAjaxResponse::alert(implode('<br />', $message))->wpSend();
    }

    public function removeKeywordAjax()
    {
        if (empty($_REQUEST['item_id'])) {
            SEOAICAjaxResponse::error('Value can`t be empty!')->wpSend();
        }

        $deletedKeywords = [];
        $selectedKeywordsIDs = array_filter(explode(',', stripslashes(sanitize_text_field($_REQUEST['item_id']))), function ($item) {
            return is_numeric($item);
        });
        $selectedKeywords = $this->getKeywordsByIDs($selectedKeywordsIDs);

        foreach ($selectedKeywords as $_keyword) {
            if ($this->removeKeyword($_keyword)) {
                $deletedKeywords[] = $_keyword['name'];
            }
        }

        if (!empty($deletedKeywords)) {
            SEOAICAjaxResponse::success('Keywords «' . implode(', ', $deletedKeywords) . '» has been deleted!')->wpSend();
        }

        SEOAICAjaxResponse::error('Keywords do not exist!')->wpSend();
    }

    public function removeAndReassignKeywordAjax()
    {
        if (
            empty($_REQUEST['delete_keyword_id'])
            || !is_numeric($_REQUEST['delete_keyword_id'])
            || empty($_REQUEST['reassign_keyword_id'])
            || !is_numeric($_REQUEST['reassign_keyword_id'])
        ) {
            SEOAICAjaxResponse::error('Value can`t be empty!')->wpSend();
        }

        $errMsgs = [];
        $deletedKeywords = [];
        $selectedKeywordsIDs = [$_REQUEST['delete_keyword_id']];

        $reassignKeyword = $this->getKeywordByID($_REQUEST['reassign_keyword_id']);
        if (empty($reassignKeyword['id'])) {
            SEOAICAjaxResponse::error('Keyword not found!')->wpSend();
        }

        foreach ($selectedKeywordsIDs as $selectedKeywordID) {
            $selectedKeyword = $this->getKeywordByID($selectedKeywordID);

            if (!empty($selectedKeyword['id'])) {
                $childItems = $this->getChildKeywordsByParentID($selectedKeyword['id']);

                if (!empty($childItems)) {
                    foreach ($childItems as $child_item) {
                        $result = wp_update_post([
                            'ID'            => $child_item['id'],
                            'post_parent'   => $reassignKeyword['id'],
                        ]);

                        if (is_wp_error($result)) {
                            $errMsgs[] = $result->get_error_message();
                        } elseif (0 == $result) {
                            $errMsgs[] = 'Keyword "' . $child_item['name'] . '" not reassigned!';
                        }
                    }

                    if (empty($errMsgs)) {
                        if ($this->removeKeyword($selectedKeyword)) {
                            $deletedKeywords[] = $selectedKeyword['name'];
                        }
                    } else {
                        SEOAICAjaxResponse::alert('Errors: '.implode(' ', $errMsgs) . ' Deletion was rejected!');
                    }
                }
            }
        }

        if (!empty($deletedKeywords)) {
            SEOAICAjaxResponse::alert('Keywords «' . implode(', ', $deletedKeywords) . '» has been deleted!')->wpSend();
        }

        SEOAICAjaxResponse::error('Keywords do not exist!')->wpSend();
    }

    private function removeKeyword($keyword)
    {
        if (
            !empty($keyword)
            && !empty($keyword['id'])
        ) {
            $result = wp_delete_post($keyword['id'], true);

            if ($result) {
                KeywordsPostsRelation::deleteByKeywordID($keyword['id']);
            }

            return $result;
        }

        return false;
    }

    /**
     * Returns sanitized keywords
     * @param array $keywords array of keywords to be sanitized. If array is empty function gets all keywords from database and sanitizes it.
     * @return array
     */
    public function sanitizeKeywords($keywords = [])
    {
        if (
            empty($keywords)
            || !is_array($keywords)
        ) {
            $keywords = $this->getKeywords();
        }

        if (empty($keywords)) {
            return [];
        }

        $returnKeywords = [];

        foreach ($keywords as $keyword) {
            if (!trim($keyword['name'])) {
                $this->removeKeyword($keyword);
            } else {
                $returnKeywords[] = $keyword;
            }
        }

        return $returnKeywords;
    }

    private function isKeywordExists($keywordName, $currentKeywords = [])
    {
        $currentKeywords = !empty($currentKeywords) ? $currentKeywords : $this->getKeywords();
        foreach ($currentKeywords as $k) {
            if ($keywordName === $k['name']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Removes existing keywords from input. Modifies the provided array of keywords.
     */
    private function removeDuplicatedKeywordsFromInput(&$keywords, $currentKeywords = [])
    {
        $cleanedKeywords = [];
        foreach ($keywords as $i => $keyword) {
            if ($this->isKeywordExists(strtolower($keyword), $currentKeywords)) {
                $cleanedKeywords[] = $keyword;
                unset($keywords[$i]);
            }
        }

        $keywords = array_values($keywords); // fix indexes after duplicates removal

        return $cleanedKeywords;
    }

    private function makeKeywordsHierarchy($keywords = [])
    {
        $result = [];
        $headTermKeywords = array_filter($keywords, function ($item) {
            return $this->isHeadTermType($item['keyword_type']) || empty($item['parent_id']);
        });
        foreach ($headTermKeywords as &$keyword) {
            $this->makeChildKeywords($keyword, $keywords);
            $result[] = $keyword;
        }

        return $result;
    }

    private function makeChildKeywords(&$parent, $keywords)
    {
        $parent['child_items'] = [];
        foreach ($keywords as $keyword) {
            if ($parent['id'] == $keyword['parent_id']) {
                $parent['child_items'][] = $keyword;
            }
        }

        if (empty($parent['child_items'])) {
            return;
        } else {
            foreach ($parent['child_items'] as &$child_item) {
                $this->makeChildKeywords($child_item, $keywords);
            }
        }
    }

    public function makeKeywordsTableMarkup($keywords = [])
    {
        $keywords = !empty($keywords) && is_array($keywords) ? $keywords : $this->getKeywords();
        $keywordsHierarchy = $this->makeKeywordsHierarchy($keywords);
        usort($keywordsHierarchy, function ($a, $b) {
            return intval($a['search_volume']) <= intval($b['search_volume']) ? 1 : -1;
        });

        $html = '';

        foreach ($keywordsHierarchy as $keyword) {
            $html .= $this->makeKeywordRowRecursive($keyword);
        }

        return $html;
    }

    private function getCompetitors($keywordID) {
        $keyword = $this->getKeywordByID($keywordID);
        $serp_data = !isset($keyword['serp_data']) ? false : unserialize($keyword['serp_data']);
        $competitors = !isset($serp_data['added_competitors']) ? [] : $serp_data['added_competitors'];

        $html = '';
        if ($competitors) {
            $html .= '<ul>';
            foreach ($competitors as $competitor) {
                $html .= '<li><a href="#" class="modal-button" data-modal="#competitor-compare" data-position="' . $competitor['position'] . '"><span>' . str_replace("www.", "", $competitor['domain']) . '</span></a><span class="pos"><i class="icon-step-posotion"></i>' . $competitor['position'] . '</span></li>';
            }
            $html .= '</ul>';
        }

        return $html;
    }

    private function makeKeywordRowRecursive($keyword, $parentID = null)
    {
        // error_log(' '.print_r($keyword, true));
        $html = '';
        $isCompetitorsButtonDisabled = empty($keyword['search_volume']) || $keyword['search_volume'] < 100;
        $searchIntentLabel = !empty($keyword['search_intent_label']) ? '<span class="search-intent-label search-intent-label-' . esc_attr($keyword['search_intent_label']) . '">' . esc_html__($keyword['search_intent_label']) . '</span>' : '-';
        $headTermTypeName = (new KeywordHeadTermType())->getName();
        $midTailTermTypeName = (new KeywordMidTailTermType())->getName();
        $longTailTermTypeName = (new KeywordLongTailTermType())->getName();
        $keywordType = !empty($keyword['keyword_type']) ? $keyword['keyword_type'] : $headTermTypeName;
        $dataIDAttr = ' data-id="' . $keyword['id'] . '"';
        $cssClass = !empty($keyword['child_items']) ? ' seoaic-has-children' : '';
        $SERPLastUpdate = $this->getSERPLastUpdate($keyword);
        // $SERPIconClass = $this->isSERPDataValid($keyword) ? 'dashicons-saved' : 'dashicons-visibility';
        $ideasCount = 0;
        $postsCount = 0;

        if (!empty($keyword['ideas_created'])) {
            $ideasCount = count(explode(',' , $keyword['ideas_created']));
        }
        if (!empty($keyword['posts_created'])) {
            $postsCount = count(explode(',' , $keyword['posts_created']));
        }

        $html .= '
        <div class="row-line ' . $cssClass . ' ' . $keywordType . '"' . $dataIDAttr . '>
            <div class="row-line-container">
                <div class="check">
                    <input type="checkbox"
                        class="seoaic-check-key"
                        name="seoaic-check-key"
                        data-id="' . $keyword['id'] . '"
                        data-keyword="' . $keyword['slug'] . '"
                        data-has-children="' . (int)(!empty($keyword['child_items']) && is_array($keyword['child_items'])) . '"
                    >
                </div>

                <div class="keyword ' . ($headTermTypeName == $keywordType ? 'seoaic-closed' : '') . '">
                    <span>' . $keyword['name'] . '</span>';
                    if (
                        $headTermTypeName == $keywordType
                        || $midTailTermTypeName == $keywordType
                    ) {
                        $childType = $headTermTypeName == $keywordType ? $midTailTermTypeName : $longTailTermTypeName;
                        $btnTitle = "Generate more " . ($headTermTypeName == $keywordType ? 'mid-tail' : 'long-tail') . ' terms';
                        $html .= '<button class="create-more-keywords position-relative" title="' . __($btnTitle) . '" data-for-id="' . $keyword['id'] . '" data-for-parent="' . $keyword['parent_id'] . '" data-type="' . $childType . '">+</button>';
                    }
                $html .= '</div>';
        if ($headTermTypeName == $keywordType) {
            $keywordsCategories = get_the_terms($keyword['id'], self::KEYWORDS_CATEGORIES_KEY);

            $html .= '
                <div class="category tc">';
            if (false === $keywordsCategories) {
                $html .= '
                    <button class="add-keyword-category modal-button"
                        data-modal="#keywords-set-category-modal"
                        data-action="seoaic_keyword_set_category"
                        data-mode="set-category"
                    ><span class="dashicons dashicons-plus"></span>set cluster
                        <div class="dn edit-form-items">
                            <input type="hidden" name="keyword_id" value="' . $keyword['id'] . '">
                        </div>
                    </button>';
            } else {
                $html .= '
                    <button title="' . __('Change Cluster', 'seoaic') . '"
                        class="update-keyword-category modal-button"
                        data-modal="#keywords-set-category-modal"
                        data-action="seoaic_keyword_set_category"
                        data-category-id="' . $keywordsCategories[0]->term_id . '"
                        data-mode="set-category"
                    ><span>' . $keywordsCategories[0]->name . '</span>
                        <div class="dn edit-form-items">
                            <input type="hidden" name="keyword_id" value="' . $keyword['id'] . '">
                        </div>
                    </button>';
            }
            $html .= '
                </div>';
        }

        $html .= '
                <div class="search-vol text-center">' . (!empty($keyword['search_volume']) ? $keyword['search_volume'] : '-') . '</div>

                <div class="difficulty text-center' . (!empty($keyword['competition']) ? ' ' . $keyword['competition'] : '') . '">' . (!empty($keyword['competition']) ? $keyword['competition'] : '-') . '</div>

                <div class="cpc text-center">' . (!empty($keyword['cpc']) ? '$' . $keyword['cpc'] : '-') . '</div>

                <div class="rank text-center keyword-' . $keyword['id'] . '-rank">';
        if ('queued' == $keyword['rank_request_status']) {
            $html .= '<div class="queued"></div>';
        } else {
            $html .= self::makeDisplayRankValue($keyword['rank_data']);
        }

//        <span class="vertical-align-middle dashicons ' . $SERPIconClass . '"></span>
//        <span class="serp-link-label">' . __('+ Show competitors', 'seoaic') . '</span>
        $linkTitle = !empty($keyword['page_link_title']) ? $keyword['page_link_title'] : (!is_numeric($keyword['page_link']) ? $keyword['page_link'] : '');
        $html .= '
                </div>

                <div class="serp competitors text-center">

                ' . $this->getCompetitors($keyword['id']) . '

                    <button title="' . __('Last update') . ': ' . $SERPLastUpdate . '"
                            data-title="Competitors" type="button" ' . ($isCompetitorsButtonDisabled ? 'disabled' : '') . '
                            class="button-primary outline modal-button"
                            data-modal="#add-competitors"
                            data-action="seoaic_get_search_term_competitors"
                            data-id="' . $keyword['id'] . '"
                            data-keyword="' . $keyword['slug'] . '"
                    >
                    ' . __('+ Show competitors', 'seoaic') . '
                    </button>
                </div>

                <div class="search-intent text-center">' . $searchIntentLabel . '</div>

                <div class="created-posts text-center">
                    <div
                        title="' . __("Show Ideas", "seoaic") . '"';
                    if (0 == $ideasCount) {
                        $html .= '
                        class="mb-5"';
                    } else {
                        $html .= '
                        class="modal-button mb-5 seoaic-cursor-pointer"
                        data-modal="#keywords-show-created-modal"
                        data-action="seoaic_keyword_get_created_ideas"
                        data-id="' . $keyword['id'] . '"
                        data-modal-title="' . __('Created Ideas', 'seoaic') . '"';
                    }
                    $html .= '
                    >Ideas: ' . $ideasCount. '</div>
                    <div
                        title="' . __("Show Posts", "seoaic") . '"';
                    if (0 == $postsCount) {
                        $html .= '
                        class="mb-5"';
                    } else {
                        $html .= '
                        class="modal-button mb-5 seoaic-cursor-pointer"
                        data-modal="#keywords-show-created-modal"
                        data-action="seoaic_keyword_get_created_posts"
                        data-id="' . $keyword['id'] . '"
                        data-modal-title="' . __('Created Posts', 'seoaic') . '"';
                    }
                    $html .= '
                    >Posts: ' . $postsCount . '</div>
                </div>

                <div class="location text-center">
                    ' . (!empty($keyword['location']) ? $keyword['location'] : '-') . '</br>
                    ' . (!empty($keyword['lang']) ? $keyword['lang'] : '-') . '
                </div>

                <div class="link text-center">'
                . (
                    !empty($keyword['page_link'])
                    ? '<span class="seoaic-keyword-link modal-button dashicons dashicons-admin-links"
                            title="' . esc_html($linkTitle) . '"
                            data-modal="#add-keyword-link-modal"
                            data-action="seoaic_set_keyword_link"
                            data-form-callback="keyword_update_link_icon"
                            data-link-post-id="' . $keyword['id'] . '"
                            data-post-link="' . $keyword['page_link'] . '"
                        ></span>'
                    : '<button title="' . __('Add Link') . '" type="button"
                            class="seoaic-keyword-add-link modal-button"
                            data-modal="#add-keyword-link-modal"
                            data-action="seoaic_set_keyword_link"
                            data-form-callback="keyword_update_link_icon"
                            data-link-post-id="' . $keyword['id'] . '"
                            data-post-link=""
                        >
                            <span class="dashicons dashicons-plus"></span> add link
                        </button>'
                ) .
                '</div>

                <div class="ta-c">';
        if (
            !empty($keyword['child_items'])
            && is_array($keyword['child_items'])
        ) {
            $html .= '<button title="' . __('Remove with re-assign') . '" type="button"
                            class="seoaic-remove modal-button confirm-modal-button confirm-remove-and-reassign-modal-button"
                            data-modal="#seoaic-remove-and-reassign-confirm-modal"
                            data-action="seoaic_remove_and_reassign_keyword"
                            data-form-callback="window_reload"
                            data-content="This Keyword has child terms. Do you want to remove it?"
                            data-keyword-id="' . esc_attr($keyword['id']) . '"
                        >
                            <div class="additional-form-items">
                                <input class="seoaic-form-item" type="hidden" name="delete_keyword_id" value="' . esc_attr($keyword['id']) . '">
                            </div>
                    </button>';
        } else {
            $html .= '<button title="' . __('Remove') . '" type="button"
                            class="seoaic-remove modal-button confirm-modal-button"
                            data-modal="#seoaic-confirm-modal"
                            data-action="seoaic_remove_keyword"
                            data-form-callback="window_reload"
                            data-content="Do you want to remove this keyword?"
                            data-post-id="' . $keyword['id'] . '"
                        ></button>';
        }
        $html .= '</div>
            </div>';

        if (
            !empty($keyword['child_items'])
            && is_array($keyword['child_items'])
        ) {
            $html .= '<div class="child-items-wrapper position-relative ' . ($headTermTypeName == $keywordType ? 'd-none' : '') . '" id="seoaic_kw_children_' . $keyword['id'] . '">';
            foreach ($keyword['child_items'] as $child_item) {
                $html .= $this->makeKeywordRowRecursive($child_item, $keyword['id']);
            }
            $html .= '</div>';
        }

        $html .= '
        </div>';

        return $html;
    }

    private static function makeDisplayRankValue($rank = []): string
    {
        if (
            empty($rank)
            || !is_array($rank)
        ) {
            return '-';
        }

        usort($rank, function ($a, $b) {
            return ($a['position'] < $b['position']) ? -1 : 1;
        });

        ob_start();
        ?>
        <div>
            <span class="seoaic-cursor-help" title="<?php _e('Highest rank', 'seoaic');?>"><?php echo esc_html($rank[0]['position']);?> </span><span class="seoaic-cursor-help" title="<?php _e('Total ranked pages', 'seoaic');?>">(<?php echo count($rank);?>)</span>
            <br>
            <span class="rank-view-more modal-button fs-small" data-modal="#rank-keyword-modal"><?php _e('view all', 'seoaic');?></span>
            <div class="rank-details d-none">
                <?php
                foreach ($rank as $record) {
                    ?>
                    <div class="table-row">
                        <div><?php echo esc_html($record['position']);?></div>
                        <div><?php echo esc_html($record['page']);?></div>
                    </div>
                    <?php
                }
        ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function groupBy($key, $data)
    {
        $result = array();

        foreach ($data as $val) {
            if (array_key_exists($key, $val)) {
                $result[$val[$key]][] = $val;
            } else {
                $result[""][] = $val;
            }
        }

        return $result;
    }

    public function groupByLocationAndLanguage($keywords = []): array
    {
        $keywordsGroupedByLocationAndLang = [];
        $keywordsGroupedByLocation = self::groupBy('location', $keywords);

        foreach ($keywordsGroupedByLocation as $locationName => $_keywords) {
            $keywordsGroupedByLocationAndLang[$locationName] = [];
            $keywordsGroupedByLang = self::groupBy('lang', $_keywords);

            foreach ($keywordsGroupedByLang as $langName => $_keywords2) {
                $keywordsGroupedByLocationAndLang[$locationName][$langName] = $_keywords2;
            }
        }

        return $keywordsGroupedByLocationAndLang;
    }

    public function getSearchVolumes($data, $manual)
    {
        global $SEOAIC_OPTIONS;

        $searchVolumes = [];
        $allKeywordsGrouped = $this->groupByLocationAndLanguage($data['keywords']);

        $next_update_auto = (30 * DAY_IN_SECONDS) + time();
        $next_update_manual = (30 * DAY_IN_SECONDS) + time();
        if (empty($SEOAIC_OPTIONS['keywords_stat_update'])) {
            $SEOAIC_OPTIONS['keywords_stat_update'] = 0;
        }
        if (empty($SEOAIC_OPTIONS['keywords_stat_update_manual'])) {
            $SEOAIC_OPTIONS['keywords_stat_update_manual'] = 0;
        }

        foreach ($allKeywordsGrouped as $location => $languagesData) {
            foreach ($languagesData as $language => $keywordsGroup) {
                $data['keywords'] = array_map(function ($item) {
                    return $item['name'];
                }, $keywordsGroup);
                $data['location'] = !empty($location) ? $location : SEOAIC_SETTINGS::getLocation();
                // $data['language'] = !empty($language) ? $language : SEOAIC_SETTINGS::getLanguage();
                $data['language'] = !empty($language) ? $language : '';
                if (empty($data['language'])) {
                    $firstLang = $this->seoaic->multilang->getFirstLanguageByLocationName($data['location']);
                    $data['language'] = $firstLang['name'];
                }

                if ($kwSearchVolumeResult = $this->requestSearchVolumes($data)) {
                    $searchVolumes = array_merge($searchVolumes, $kwSearchVolumeResult['data']);

                    $update_auto = isset($kwSearchVolumeResult['KEYWORD_STATS_UPDATE_FREQUENCY']) ? ($kwSearchVolumeResult['KEYWORD_STATS_UPDATE_FREQUENCY'] * HOUR_IN_SECONDS) + time() : $next_update_manual;
                    // $update_manual = isset($kwSearchVolumeResult['KEYWORD_STATS_UPDATE_FREQUENCY_MANUALLY']) ? ($kwSearchVolumeResult['KEYWORD_STATS_UPDATE_FREQUENCY_MANUALLY'] * HOUR_IN_SECONDS) + time() : $next_update_auto;
                    $update_manual = isset($kwSearchVolumeResult['KEYWORD_STATS_UPDATE_FREQUENCY_MANUALLY']) ? ($kwSearchVolumeResult['KEYWORD_STATS_UPDATE_FREQUENCY_MANUALLY'] * HOUR_IN_SECONDS) + time() : $next_update_auto;

                    if ($manual) {
                        // $SEOAIC_OPTIONS['keywords_stat_update_manual'] = $update_auto;
                        $SEOAIC_OPTIONS['keywords_stat_update_manual'] = time() + 24 * HOUR_IN_SECONDS; // check only on plugin side
                    } else {
                        $SEOAIC_OPTIONS['keywords_stat_update'] = $update_manual;
                    }

                }
            }
        }
        update_option('seoaic_options', $SEOAIC_OPTIONS);

        return $searchVolumes;
    }

    public function getSearchIntents($data)
    {
        $searchIntents = [];
        $allKeywordsGrouped = $this->groupByLocationAndLanguage($data['keywords']);

        foreach ($allKeywordsGrouped as $location => $languagesData) {
            foreach ($languagesData as $language => $keywordsGroup) {
                $data['location'] = !empty($location) ? $location : SEOAIC_SETTINGS::getLocation();
                $data['language'] = !empty($language) ? $language : SEOAIC_SETTINGS::getLanguage();

                if ($kwSearchIntentResult = $this->requestSearchIntent($data, $keywordsGroup)) {
                    $searchIntents = array_merge($searchIntents, $kwSearchIntentResult['data']);
                }
            }
        }

        return $searchIntents;
    }

    /**
     * Pulls Search Volumes data for keywords
     * @param array $data
     * @return array|false
     */
    public function requestSearchVolumes($data = [])
    {
        $result = $this->seoaic->curl->init('api/ai/keywords-search-volume', $data, true, true, true);

        if (
            !empty($result['status'])
            && $result['status'] === 'success'
        ) {
            return $result;
        }

        return false;
    }

    /**
     * Pulls Search Intent data for keywords that have no such data
     * @param array $data
     * @param array $keywords
     * @return array|false
     */
    private function requestSearchIntent($data, $keywords = [])
    {
        $filteredKeywords = [];
        $keywords = !empty($keywords) && is_array($keywords) ? $keywords : $this->getKeywords();

        // filter only keywords that have no intent label
        foreach ($keywords as $keyword) {
            if (
                !array_key_exists('search_intent_label', $keyword)
                || !$keyword['search_intent_label']
            ) {
                $filteredKeywords[] = $keyword['name'];
            }
        }

        if (!empty($filteredKeywords)) {
            $data['keywords'] = $filteredKeywords;
            $result = $this->seoaic->curl->init('api/ai/keywords-search-intent', $data, true, true, true);

            if (
                !empty($result['status'])
                && $result['status'] === 'success'
                && isset($result['data'])
            ) {
                return $result['data'];
            }
        }

        return false;
    }

    /**
     * Collects Rank data needed for processing in background (AJAX), and stores it as keyword's meta field
     * @param array $data data needed for rank request
     * @param array $keywords array of Keywords
     * @return void
     */
    private function queueRank($data = [], $keywords = [])
    {
        global $SEOAIC_OPTIONS;

        // update rank once per 30 days
        $filteredKeywords = array_filter($keywords, function ($keyword) {
            return empty($keyword['rank_last_update'])
                || $keyword['rank_last_update'] + 30 * DAY_IN_SECONDS < time();
        });

        if (empty($filteredKeywords)) {
            return false;
        }

        foreach ($filteredKeywords as $keyword) {
            $this->updateKeywordData($keyword, [
                'rank_request_status' => 'queued',
                'rank_request_data' => [
                    'email'     => $data['email'],
                    'token'     => $SEOAIC_OPTIONS['seoaic_api_token'],
                    'language'  => $data['language'],
                    'location'  => $data['location'],
                    'target'    => SEOAIC_SETTINGS::getCompanyWebsite('host'),
                    'add_terms' => $keyword['name'],
                ],
                'rank_data' => [],
            ]);
        }
    }

    /**
     * Gets Keywords with rank data to be requested, 5 per time. Note: Keywords are in WP Post format.
     * @return array array of post objects
     */
    private function getKeywordsWithQueuedRank()
    {
        $keywords = get_posts([
            'post_type' => self::KEYWORD_POST_TYPE,
            'post_status' => 'any',
            // 'numberposts' => -1,
            'numberposts' => 5,
            'meta_query' => [
                [
                    'key'   => 'rank_request_status',
                    'value' => 'queued',
                ]
            ]
        ]);

        return $keywords;
    }

    /**
     * Sends request to backend and returns response
     * @param array $rankRequestData array of fields neede for request
     * @return array|false
     */
    private function requestRank($rankRequestData = [])
    {
        $rankSearchTermsResult = $this->seoaic->curl->init('api/ai/rank-search-terms', $rankRequestData, true, true, true);

        if (
            !empty($rankSearchTermsResult['status'])
            && $rankSearchTermsResult['status'] === 'success'
        ) {
            return $rankSearchTermsResult;
        }

        return false;
    }

    /**
     * Gets Rank data, updates Keyword's Rank meta field
     * @return array|false
     */
    private function backgroundProcessRank()
    {
        $keywordsToProcess = $this->getKeywordsWithQueuedRank();
        $result = [];

        if (!empty($keywordsToProcess)) {
            try {
                foreach ($keywordsToProcess as $k) {
                    $this->updateKeywordData($k->ID, [
                        'rank_request_status' => 'requested',
                    ]);
                }

                foreach ($keywordsToProcess as $keywordToProcess) {
                    $rankPositions = [];
                    $keywordNewData = [
                        'rank_request_status' => 'completed',
                    ];

                    $rankRequestData = get_post_meta($keywordToProcess->ID, 'rank_request_data', true);
                    $rankSearchTermsResult = $this->requestRank($rankRequestData);

                    if (
                        !empty($rankSearchTermsResult['data'])
                        && is_array($rankSearchTermsResult['data'])
                    ) {
                        foreach ($rankSearchTermsResult['data'] as $row) {
                            $rankPositions[] = [
                                'position'  => !empty($row['position']) ? $row['position'] : '',
                                'page'      => !empty($row['page']) ? $row['page'] : '',
                            ];
                        }

                        if (!empty($rankPositions)) {
                            $keywordNewData['rank_data'] = $rankPositions;
                        }
                    }

                    $keywordNewData['rank_last_update'] = time();
                    $this->updateKeywordData($keywordToProcess->ID, $keywordNewData);

                    $result[] = [
                        'id' => $keywordToProcess->ID,
                        'rank_data' => $rankPositions,
                        'html' => self::makeDisplayRankValue($rankPositions),
                    ];
                }
            } catch (Exception $e) {
                // revert status back
                foreach ($keywordsToProcess as $k) {
                    $this->updateKeywordData($k->ID, [
                        'rank_request_status' => 'queued',
                    ]);
                }
            }

            return $result;
        }

        return false;
    }

    /**
     * Checks if there are any Keywords with Rank field in 'queued' status
     */
    public function isBackgroundRankProcessInProgress()
    {
        $keywords = $this->getKeywordsWithQueuedRank();

        if (!empty($keywords)) {
            return true;
        }

        return false;
    }

    public function getRankBulkAjax()
    {

        if ($result = $this->backgroundProcessRank()) {
            SEOAICAjaxResponse::success()->addFields([
                'completed' => false,
                'data' => $result,
            ])->wpSend();
        }

        SEOAICAjaxResponse::success()->addFields([
            'completed' => true,
            'data' => [],
        ])->wpSend();
    }

    private function requestKeywordSerp($keyword)
    {
        $data = [
            'location' => !empty($keyword['location']) ? $keyword['location'] : SEOAIC_SETTINGS::getLocation(),
            'language' => !empty($keyword['lang']) ? $keyword['lang'] : SEOAIC_SETTINGS::getLanguage(),
            'email'    => wp_get_current_user()->user_email,
            'keywords' => [$keyword['name']],
        ];

        $result = $this->seoaic->curl->init('api/ai/keywords-serp-competitors', $data, true, false, true);

        if (
            !empty($result['status'])
            && 'success' === $result['status']
            && !empty($result['data'])
            && !empty($result['data'][$keyword['name']])
        ) {
            return $result['data'][$keyword['name']];
        }

        return [];
    }

    private function updateKeywordSerp($keyword)
    {
        $keywordSerp = $this->requestKeywordSerp($keyword);

        return $this->updateKeywordData($keyword, [
            'serp_last_update'  => time(),
            'serp_data'         => $keywordSerp,
        ]);
    }

    public function getKeywordSerp()
    {
        $keyword = null;

        if (
            !empty($_POST['id'])
            && is_numeric($_POST['id'])
        ) {
            $keyword = $this->getKeywordByID($_POST['id']);
        }

        if (
            is_null($keyword)
            || $keyword['slug'] != $_POST['keyword']
        ) {
            SEOAICAjaxResponse::error('No Keyword found!')->wpSend();
        }

        if ($this->isSERPDataValid($keyword)) {
            SEOAICAjaxResponse::success()->addFields([
                'serp' => maybe_unserialize($keyword['serp_data']),
            ])->wpSend();

        } else {
            if ($this->updateKeywordSerp($keyword)) {
                $keyword = $this->getKeywordByID($keyword['id']);

                SEOAICAjaxResponse::success()->addFields([
                    'serp' => maybe_unserialize($keyword['serp_data']),
                ])->wpSend();
            }
        }

        SEOAICAjaxResponse::error('No Keyword data found!')->wpSend();
    }

    private function getSERPLastUpdate($keyword)
    {
        $serpDate = __('never');

        if (array_key_exists('serp_data', $keyword)) {
            $serp_time = $keyword['serp_last_update'];

            if (time() - $serp_time < DAY_IN_SECONDS * 7) {
                $serpDate = date('M j, Y', $serp_time);
            }
        }

        return $serpDate;
    }

    private function isSERPDataValid($keyword)
    {
        return array_key_exists('serp_data', $keyword)
            && time() - $keyword['serp_last_update'] < DAY_IN_SECONDS * 7;
    }

    public function setKeywordLinkAjax()
    {
        if (
            empty($_REQUEST['post_id'])
            || !is_numeric($_REQUEST['post_id'])
        ) {
            SEOAICAjaxResponse::error('Wrong ID parameter.')->wpSend();
        }

        $link = !empty($_REQUEST['page_link']) ? trim($_REQUEST['page_link']) : '';

        if ($result = $this->setKeywordLink(intval($_REQUEST['post_id']), $link)) {
            $keyword = $this->getKeywordByID($_REQUEST['post_id']);
            $linkTitle = !empty($keyword['page_link_title']) ? $keyword['page_link_title'] : (!is_numeric($keyword['page_link']) ? $keyword['page_link'] : '');
            $content = '<span class="seoaic-keyword-link modal-button dashicons dashicons-admin-links" title="' . esc_attr($linkTitle) . '" data-modal="#add-keyword-link-modal" data-action="seoaic_set_keyword_link" data-form-callback="keyword_update_link_icon" data-link-post-id="' . esc_attr($_REQUEST['post_id']) . '" data-post-link="' . esc_attr($link) . '"></span>';

            if (empty($link)) {
                $content = '<button title="' . __('Add Link') . '" type="button"
                    class="seoaic-keyword-add-link modal-button"
                    data-modal="#add-keyword-link-modal"
                    data-action="seoaic_set_keyword_link"
                    data-form-callback="keyword_update_link_icon"
                    data-post-id="' . esc_attr($_REQUEST['post_id']) . '"
                    data-post-link=""
                >
                    <span class="dashicons dashicons-plus"></span> add link
                </button>';
            }

            SEOAICAjaxResponse::alert('Link updated')->addFields([
                'content' => [
                    'id' => $_REQUEST['post_id'],
                    'content' => $content,
                ],
            ])->wpSend();
        }

        SEOAICAjaxResponse::error('Unknown error.')->wpSend();
    }

    private function categoryAdd($categoryName = '')
    {
        $result = wp_insert_category([
            'taxonomy' => self::KEYWORDS_CATEGORIES_KEY,
            'cat_name' => trim($categoryName),
            'category_description' => '',
            'category_nicename' => sanitize_title(trim($categoryName)),
            'category_parent' => '',
        ], true);

        return $result;
    }

    public function categoryAddAjax()
    {
        if (empty(trim($_REQUEST['category_name']))) {
            SEOAICAjaxResponse::error('Empty category name')->wpSend();
        }

        $result = $this->categoryAdd($_REQUEST['category_name']);

        if (is_wp_error($result)) {
            SEOAICAjaxResponse::error('Error:' . $result->get_error_message())->wpSend();
        }

        $category = get_term_by('term_id', $result, self::KEYWORDS_CATEGORIES_KEY);

        SEOAICAjaxResponse::success()->addFields([
            'html' => self::makeKeywordCategoryRow($category),
        ])->wpSend();
    }

    public function categorySetAjax()
    {
        if (
            empty($_REQUEST['keyword_id'])
            || !isset($_REQUEST['category_id'])
            || !is_numeric($_REQUEST['keyword_id'])
        ) {
            SEOAICAjaxResponse::alert('Not enough parameters!')->wpSend();
        }

        $keywordID = intval($_REQUEST['keyword_id']);
        $categoryID = $_REQUEST['category_id'];

        if (empty($categoryID)) { // unset all
            $result = wp_set_object_terms($keywordID, [], self::KEYWORDS_CATEGORIES_KEY);
        } else {
            if (!is_numeric($categoryID)) { // dynamically added category, add it first
                $categoryID = $this->categoryAdd($categoryID);
            }

            $result = wp_set_object_terms($keywordID, [intval($categoryID)], self::KEYWORDS_CATEGORIES_KEY);
        }

        if (is_wp_error($result)) {
            SEOAICAjaxResponse::error('Error: ' . $result->get_error_message())->wpSend();
        }

        SEOAICAjaxResponse::success()->wpSend();
    }


    public function getKeywordsCategories()
    {
        return get_categories([
            'taxonomy'      => self::KEYWORDS_CATEGORIES_KEY,
            'hide_empty'    => false,
            'orderby'       => 'name',
            'order'         => 'ASC',
        ]);
    }

    public function makeKeywordsCategoriesOptions($keywordsCategories)
    {
        $html = '';
        if (!empty($keywordsCategories)) {
            foreach ($keywordsCategories as $category) {
                $html .= '<option value="' . $category->term_id . '">' . $category->name . '</option>';
            }
        }

        return $html;
    }

    public function getKeywordsCategoriesAjax()
    {
        $keywordsCategories = $this->getKeywordsCategories();
        $fields = [
            'categories' => $keywordsCategories,
        ];

        if (
            !empty($_REQUEST['options_html'])
            && 1 == $_REQUEST['options_html']
        ) {
            $fields['options_html'] = '<option value="">None</option>';
            $fields['options_html'] .= $this->makeKeywordsCategoriesOptions($keywordsCategories);
        }

        SEOAICAjaxResponse::success()->addFields($fields)->wpSend();
    }

    public function deleteKeywordsCategory()
    {
        if (
            empty($_REQUEST['category_id'])
            || !is_numeric($_REQUEST['category_id'])
        ) {
            SEOAICAjaxResponse::error('Wrong request parameters!')->wpSend();
        }

        $result = wp_delete_term($_REQUEST['category_id'], self::KEYWORDS_CATEGORIES_KEY);

        if (is_wp_error($result)) {
            SEOAICAjaxResponse::error('Error: ' . $result->get_error_message())->wpSend();
        } elseif (false == $result) {
            SEOAICAjaxResponse::error('Category was not removed.')->wpSend();
        }

        SEOAICAjaxResponse::success()->wpSend();
    }

    public function updateKeywordsCategory()
    {
        if (
            empty($_REQUEST['category_id'])
            || !is_numeric($_REQUEST['category_id'])
        ) {
            SEOAICAjaxResponse::error('Wrong request parameters!')->wpSend();
        }

        $categoryName = esc_html(trim($_REQUEST['category_name']));
        $result = wp_update_term($_REQUEST['category_id'], self::KEYWORDS_CATEGORIES_KEY, [
            'name' => $categoryName,
            'slug' => sanitize_title($categoryName),
        ]);

        if (is_wp_error($result)) {
            SEOAICAjaxResponse::error('Error: ' . $result->get_error_message())->wpSend();
        } elseif (false == $result) {
            SEOAICAjaxResponse::error('Category was not updated.')->wpSend();
        }

        SEOAICAjaxResponse::success()->addFields([
            'category_name' => $categoryName,
        ])->wpSend();
    }

    public static function makeKeywordCategoryRow($category)
    {
        ob_start();
        ?>
        <div class="table-row">
            <div class="titles-col">
                <span><?php echo esc_html($category->name);?></span>
                <input type="text" name="category_name" value="<?php echo esc_attr($category->name);?>" class="dn">
            </div>
            <div class="buttons-col">
                <div class="edit-remove-buttons">
                    <button title="<?php _e('Update');?>" type="button" class="seoaic-edit seoaic-edit-category-button"></button>
                    <button title="<?php _e('Remove');?>" type="button" class="seoaic-remove seoaic-remove-category-button"></button>
                </div>

                <div class="update-confirm-buttons dn">
                    <?php _e('Update');?>?
                    <button type="button" class="seoaic-cancel-btn  seoaic-update-category-button-cancel"><?php _e('Cancel');?></button>
                    <button type="button" class="seoaic-confirm-btn seoaic-update-category-button-confirm" data-cat-id="<?php echo $category->term_id;?>"><?php _e('Confirm');?></button>
                </div>

                <div class="remove-confirm-buttons dn">
                    <?php _e('Remove');?>?
                    <button type="button" class="seoaic-cancel-btn seoaic-remove-category-button-cancel"><?php _e('Cancel');?></button>
                    <button type="button" class="seoaic-confirm-btn seoaic-remove-category-button-confirm" data-cat-id="<?php echo $category->term_id;?>"><?php _e('Confirm');?></button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function getCreatedIdeasAjax()
    {
        if (
            empty($_POST['id'])
            || !is_numeric($_POST['id'])
        ) {
            SEOAICAjaxResponse::error('No ID set.')->wpSend();
        }

        $keyword = $this->getKeywordByID($_POST['id']);

        if (empty($keyword['id'])) {
            SEOAICAjaxResponse::error('No keyword found.')->wpSend();
        }

        $html = '';
        $ideas = !empty($keyword['ideas_created']) ? explode(',', $keyword['ideas_created']) : [];

        if (!empty($ideas)) {
            foreach ($ideas as $id) {
                $idea = get_post($id);
                if (
                    $idea
                    && SEOAIC_IDEAS::isIdea($idea)
                ) {
                    $html .= self::makeKeywordCreatedIdeaRow($idea);
                }
            }
        }

        SEOAICAjaxResponse::success()->addFields([
            'html' => $html
        ])->wpSend();
    }

    public function getCreatedPostsAjax()
    {
        if (
            empty($_POST['id'])
            || !is_numeric($_POST['id'])
        ) {
            SEOAICAjaxResponse::error('No ID set.')->wpSend();
        }

        $keyword = $this->getKeywordByID($_POST['id']);

        if (empty($keyword['id'])) {
            SEOAICAjaxResponse::error('No keyword found.')->wpSend();
        }

        $html = '';
        $ideas = !empty($keyword['posts_created']) ? explode(',', $keyword['posts_created']) : [];

        if (!empty($ideas)) {
            foreach ($ideas as $id) {
                $idea = get_post($id);
                if (
                    $idea
                    && !SEOAIC_IDEAS::isIdea($idea)
                ) {
                    $html .= self::makeKeywordCreatedPostRow($idea);
                }
            }
        }

        SEOAICAjaxResponse::success()->addFields([
            'html' => $html
        ])->wpSend();
    }

    public static function makeKeywordCreatedIdeaRow($idea)
    {
        ob_start();
        if (!empty($idea)) {
            ?>
            <div class="table-row">
                <div class="text-start">
                    <b class="mr-15">#<?php echo $idea->ID;?></b><span><?php echo $idea->post_title;?></span>
                </div>
            </div>
            <?php
        }

        return ob_get_clean();
    }

    public static function makeKeywordCreatedPostRow($post)
    {
        ob_start();
        if (!empty($post)) {
            ?>
            <div class="table-row">
                <div class="text-start">
                    <b class="mr-15"><a href="<?php echo get_edit_post_link($post->ID);?>" target="_blank">#<?php echo $post->ID;?> <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M21 13v10h-21v-19h12v2h-10v15h17v-8h2zm3-12h-10.988l4.035 4-6.977 7.07 2.828 2.828 6.977-7.07 4.125 4.172v-11z"/></svg></a></b><span><?php echo $post->post_title;?></span>
                </div>
            </div>
            <?php
        }

        return ob_get_clean();
    }
}
