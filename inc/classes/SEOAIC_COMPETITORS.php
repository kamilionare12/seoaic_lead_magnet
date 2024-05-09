<?php

namespace SEOAIC;

use Exception;

class SEOAIC_COMPETITORS
{
    private $seoaic;
    private const COMPETITORS_AS_CATEGORIES_KEY = 'seoaic_competitors';
    private const TERMS_POST_TYPE = 'seoaic-search-terms';

    //TODO
    private const SEARCH_TERMS_CACHE_KEY = 'seoaic-search-terms';

    public function __construct($_seoaic)
    {
        $this->seoaic = $_seoaic;
        add_action('wp_ajax_seoaic_Add_New_Competitor', [$this, 'Add_New_Competitor'], 99);
        add_action('wp_ajax_seoaic_remove_competitor', [$this, 'Remove_Competitor'], 99);
        add_action('wp_ajax_seoaic_Remove_Term', [$this, 'Remove_Term'], 99);
        add_action('wp_ajax_seoaic_Competitors_Compare_Section_HTML', [$this, 'Competitors_Compare_Section_HTML']);
        add_action('wp_ajax_seoaic_Competitors_Search_Terms_HTML', [$this, 'Competitors_Search_Terms_HTML']);
        add_action('wp_ajax_seoaic_Check_Terms_Update_Progress', [$this, 'Check_Terms_Update_Progress']);
        add_action('wp_ajax_seoaic_Progress_Values', [$this, 'set_values_my_rank_in_progress']);
        add_action('wp_ajax_seoaic_Prepare_Article_Based_Search_Term', [$this, 'Prepare_Article_Based_Search_Term']);
        add_action('wp_ajax_seoaic_update_competitor_data', [$this, 'update_competitors_data']);
        add_action('wp_ajax_seoaic_validate_positions_real_terms_count', [$this, 'validate_positions_all_competitors']);
        add_action('wp_ajax_seoaic_compare_competitors', [$this, 'compare_competitors']);
        add_action('wp_ajax_seoaic_get_top_google_analysis', [$this, 'get_top_google_analysis']);
        add_action('wp_ajax_seoaic_migrate_competitors_from_options', [$this, 'migrate_competitors_from_options']);
        add_action('wp_ajax_seoaic_compare_my_article',         [$this, 'COMPARE_my_article']);
        add_action('wp_ajax_seoaic_compare_my_competitors',     [$this, 'COMPARE_my_competitors']);
        add_action('wp_ajax_seoaic_compare_analysis',           [$this, 'COMPARE_analysis']);
        add_action('wp_ajax_seoaic_compare_other_positions',    [$this, 'COMPARE_other_positions']);

        add_action('init', [$this, 'registerCompetitorsAsTaxonomy']);
    }

    public function registerCompetitorsAsTaxonomy()
    {
        register_taxonomy(self::COMPETITORS_AS_CATEGORIES_KEY, self::TERMS_POST_TYPE, [
            'public' => false,
            'rewrite' => false,
        ]);
    }

    public function seoaic_location()
    {
        global $SEOAIC_OPTIONS;
        return !empty($SEOAIC_OPTIONS['seoaic_location']) ? $SEOAIC_OPTIONS['seoaic_location'] : 'United States';
    }

    public function seoaic_language()
    {
        global $SEOAIC_OPTIONS;
        return !empty($SEOAIC_OPTIONS['seoaic_language']) ? $SEOAIC_OPTIONS['seoaic_language'] : 'English';
    }

    public function seoaic_company_website_url()
    {
        global $SEOAIC_OPTIONS;
        $company_website = !empty($SEOAIC_OPTIONS['seoaic_company_website']) ? $SEOAIC_OPTIONS['seoaic_company_website'] : get_bloginfo('url');
        return preg_replace('/^www\./', '', wp_parse_url($company_website)['host']);
        //return wp_parse_url($company_website)['host'];
    }

    public function request_data_compertitors($language, $location, $url)
    {
        global $SEOAIC_OPTIONS, $SEOAIC;

        $data = [
            'email' => $SEOAIC_OPTIONS['seoaic_api_email'],
            'token' => $SEOAIC_OPTIONS['seoaic_api_token'],
            'language' => $language,
            'location' => $location,
            'target' => $url,
            'add_terms' => 'word',
            'date' => date('m/d/Y h:i:s a', time()),
        ];

        $result = $SEOAIC->curl->init('api/ai/competitors-search-terms', $data, true, true, true);
        $rank_history = $SEOAIC->curl->init('api/ai/historical-rank', $data, true, true, true);
        $backlinks_result = $SEOAIC->curl->init('api/ai/backlinks-domain', $data, true, true, true);

        $backlinks = isset($backlinks_result['data'][0]['results']['result'][0]['backlinks']) ? intval($backlinks_result['data'][0]['results']['result'][0]['backlinks']) : 0;
        $rank_history = !empty($rank_history['data']) ? $rank_history['data'] : [];
        $next_update = time() + MONTH_IN_SECONDS;

        return [
            'result' => $result,
            'rank_history' => $rank_history,
            'backlinks_result' => $backlinks,
            'next_update' => $next_update,
        ];

    }

    public function competitors_fields($ID, $next_update, $url, $slugLocation, $location, $search_terms, $backlinks, $rank_history, $reordered_rank_history = [])
    {
        global $SEOAIC, $SEOAIC_OPTIONS;

        $my_site = $this->seoaic_company_website_url();
        $slug = $url . $slugLocation;
        $tax = self::COMPETITORS_AS_CATEGORIES_KEY;

        if ($ID) {

            $competitor = wp_update_term($ID, $tax, [
                'name' => $url . ' (' . $location . ')',
                'slug' => $slug
            ]);

        } else {

            $competitor = wp_insert_term(
                $url . ' (' . $location . ')',
                $tax,
                [
                    'slug' => $slug,
                ]
            );

        }

        if (is_wp_error($competitor)) {

            SEOAICAjaxResponse::error('Error: ' . $slug . ' ' . $competitor->get_error_message())->wpSend();

        } else {

            $id = $competitor['term_id'];
            if ($url === $my_site) {
                $SEOAIC_OPTIONS['my_competitor_term_id'] = $id;
                update_option('seoaic_options', $SEOAIC_OPTIONS);
            }

            $competitor_values =
                [
                    'next_update' => $next_update,
                    'url' => $url . $slugLocation,
                    'url_live' => $url,
                    'url_display' => $url . ' (' . $location . ')',
                    'backlinks' => $backlinks,
                    'location' => $location,
                    'historical_rank' => !$reordered_rank_history ? $this->reorder_positions_stats($rank_history) : $reordered_rank_history,
                    'search_terms' => $search_terms,
                    'my_website' => false
                ];

            foreach ($competitor_values as $key => $value) {
                update_term_meta($id, $key, $value);
            }

            if ($url === $my_site) {
                $SEOAIC->rank->combine_all_ranks();
                $this->validate_positions_all_competitors(false);
            }
        }
    }

    public function update_competitors_search_term_field($competitor_id, $search_term_index, $field, $new_value)
    {
        $earch_terms = $this->get_search_terms($competitor_id);
        $earch_terms[$search_term_index][$field] = $new_value;
        update_term_meta($competitor_id, 'search_terms', $earch_terms);
    }

    public function get_competitors()
    {
        $my_website_url = $this->seoaic_company_website_url();
        $get_competitors = get_terms(
            [
                'taxonomy' => self::COMPETITORS_AS_CATEGORIES_KEY,
                'hide_empty' => false,
                'meta_query' => [
                    [
                        'key' => 'url_live',
                        'value' => $my_website_url,
                        'compare' => '!='
                    ]
                ]
            ]);

        $get_my = get_terms([
            'taxonomy' => self::COMPETITORS_AS_CATEGORIES_KEY,
            'hide_empty' => false,
            'meta_query' => [
                [
                    'key' => 'url_live',
                    'value' => $my_website_url,
                    'compare' => '='
                ]
            ]
        ]);

        if (isset($get_my[0])) {
            array_unshift($get_competitors, $get_my[0]);
        }

        return $get_competitors;
    }

    public function get_competitor_field_val($id, $field_name)
    {
        return get_term_meta($id, $field_name, true);
    }

    public function get_search_terms($competitor_id)
    {
        $search_terms = $this->get_competitor_field_val($competitor_id, 'search_terms');
        if (!$search_terms) {
            $search_terms = [];
        }
        return $search_terms;
    }

    public function get_search_terms_field_val($competitor_id, $field_name)
    {
        $search_terms = $this->get_search_terms($competitor_id);
        $values = [];
        foreach ($search_terms as $term) {
            $values[] = intval($term[$field_name]);
        }
        return $values;
    }

    public function get_competitors_field_val($field_name)
    {
        $competitors = $this->get_competitors();
        $values = [];
        foreach ($competitors as $competitor) {
            $values[] = get_term_meta($competitor->term_id, $field_name, true);
        }
        return $values;
    }

    /**
     * Prepare Competitors URL
     * @throws Exception
     * $domains string
     */
    public function Add_New_Competitor($domains = '', $loco = '', $i = 0, $update = false)
    {

        global $SEOAIC_OPTIONS, $SEOAIC;
        $SEOAIC = new SEOAIC();

        $my_site = $this->seoaic_company_website_url();

        if (!$this->get_competitors()) {
            $item_name = $my_site;
        }

        if (empty($_REQUEST['item_name']) and empty($item_name) and empty($domains)) {
            return;
        }

        if ($domains && $update) {
            $_REQUEST['item_name'] = $domains;
        }

        $language = $this->seoaic_language();
        $location = $this->seoaic_location();

        if (!empty($_REQUEST['seoaic_location'])) {
            $location = $_REQUEST['seoaic_location'];
            $language = seoaic_get_preferred_language($location);
        }

        if (!empty($loco)) {
            $location = $loco;
            $language = seoaic_get_preferred_language($loco);
        }

        $slugLocation = '_' . $this->Slug($location);

        $competitors = !empty($_REQUEST['item_name']) ? explode(',', $_REQUEST['item_name']) : [];

        // remove all spaces
        $competitors = preg_replace('/\s+/', '', $competitors);

        $regex = "((https?|ftp)\:\/\/)?";
        $regex .= "([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?";
        $regex .= "([a-z0-9-.]*)\.([a-z]{2,10})";
        $regex .= "(\:[0-9]{2,5})?";
        $regex .= "(\/([a-z0-9+\$_-]\.?)+)*\/?";
        $regex .= "(\?[a-z+&\$_.-][a-z0-9;:@&%=+\/\$_.-]*)?";
        $regex .= "(#[a-z_.-][a-z0-9+\$_.-]*)?";

        //$addedURLS = [];
        $addedURLS_locations = [];
        $get_competitors = $this->get_competitors();
        if (!empty($get_competitors)) {
            foreach ($get_competitors as $competitor) {
                $id = $competitor->term_id;
                $url = $this->get_competitor_field_val($id, 'url');
                $addedURLS_locations[] = !empty($url) ? $url : '';
            }
        }

        $validURL = [];
        $notValidURL = [];
        $existURL = [];
        $myOwnURL = [];

        foreach (array_unique($competitors) as $competitor) {
            $url = self::Slugify_url($competitor);
            if ($update) {
                $validURL[] = $url;
            } else {
                if ($url === $my_site) {
                    $myOwnURL[] = $url;
                } else {
                    if (in_array($url . $slugLocation, $addedURLS_locations)) {
                        $existURL[] = $url;
                    } else {
                        if (preg_match("/^$regex$/i", $url)) {
                            $validURL[] = $url;
                        } else {
                            $notValidURL[] = $url;
                        }
                    }
                }
            }
        }

        // Add our website url in to competitors with a first run
        $all_urls = $this->get_competitors_field_val('url_live');
        if (!in_array($my_site, $all_urls)) {
            array_unshift($validURL, $my_site);
        }

        $validURL = array_unique($validURL);

        foreach ($validURL as $url) {

            $data_request = $this->request_data_compertitors($language, $location, $url);

            $result = $data_request['result'];
            $rank_history = $data_request['rank_history'];
            $backlinks_result = $data_request['backlinks_result'];
            $next_update = $data_request['next_update'];

            $search_terms = [];
            if (!empty($result['data']) && count($result['data']) > 1) {
                foreach ($result['data'] as $terms) {
                    $search_terms[random_int(100000000, 999999999)] = $terms;
                }
            }

            if ($update) {

                if ($url == $my_site) {
                    $SEOAIC_OPTIONS['on_page_seo_data']['historical_positions'] = $this->reorder_positions_stats($rank_history);
                }

                $this->competitors_fields($i, $next_update, $url, $slugLocation, $location, $search_terms, $backlinks_result, $rank_history);

                $validURL = $validURL ? 'Competitor «' . implode(',', $validURL) . '» has been updated! <br><br>' : '';

                wp_send_json([]);
            }

            if ($url == $my_site) {
                $SEOAIC_OPTIONS['on_page_seo_data']['historical_positions'] = $this->reorder_positions_stats($rank_history);
            }

            $this->competitors_fields(0, $next_update, $url, $slugLocation, $location, $search_terms, $backlinks_result, $rank_history);

        }

        $validURL = $validURL ? 'Competitors «' . implode(',', $validURL) . '» has been added! <br><br>' : '';
        $notValidURL = $notValidURL ? 'Not valid URL «' . implode(',', $notValidURL) . '» check and try again! <br><br>' : '';
        $existURL = $existURL ? 'Competitors «' . implode(',', $existURL) . '» already exists! <br><br>' : '';
        $myOwnURL = $myOwnURL ? 'You can\'t add your own website: «' . implode(',', $myOwnURL) . '» <br><br>' : '';

        if (empty($_REQUEST['action'])) {
            return;
        }

        SEOAICAjaxResponse::alert($validURL . $notValidURL . $existURL . $myOwnURL)->wpSend();
    }

    private function fix_www_domain_url()
    {
        $my_competitor = $this->our_own_website_url_for_competitors();

        if (strpos($my_competitor['my_website'], 'www.') === 0) {
            $this->change_our_own_url();
        }
    }

    /**
     * @throws Exception
     */
    public function change_our_own_url()
    {

        if (!$this->get_competitors()) {
            return;
        }

        $our_competitor_id = $this->our_own_website_index();

        $language = $this->seoaic_language();
        $location = $this->seoaic_location();
        $slugLocation = '_' . $this->Slug($location);

        $company_website = $this->seoaic_company_website_url();
        $my_competitor = $this->our_own_website_url_for_competitors();

        $my_website = $my_competitor['my_website'];
        $my_location = $my_competitor['location'];

        if ($my_website == $company_website && $my_location == $location) {
            return;
        }

        $data_request = $this->request_data_compertitors($language, $location, $company_website);

        $result = $data_request['result'];
        $rank_history = $data_request['rank_history'];
        $backlinks_result = $data_request['backlinks_result'];
        $next_update = $data_request['next_update'];

        $search_terms = [];
        if (!empty($result['data'])) {
            foreach ($result['data'] as $terms) {
                $search_terms[random_int(100000000, 999999999)] = $terms;
            }
        }

        $slug = $company_website . $slugLocation;
        $tax = self::COMPETITORS_AS_CATEGORIES_KEY;
        $term_exist = get_term_by('slug', $slug, $tax);
        if ($term_exist && $term_exist->term_id != $our_competitor_id) {
            wp_delete_term($term_exist->term_id, $tax);
        }

        $this->competitors_fields($our_competitor_id, $next_update, $company_website, $slugLocation, $location, $search_terms, $backlinks_result, $rank_history);

    }

    public function reorder_positions_stats($positions)
    {
        $reordered = [];
        if (!empty($positions)) {

            $pos_1 = 0;
            $pos_2_3 = 0;
            $pos_4_10 = 0;
            $pos_11_20 = 0;
            $pos_21_30 = 0;
            $pos_31_40 = 0;
            $pos_41_50 = 0;
            $pos_51_60 = 0;
            $pos_61_70 = 0;
            $pos_71_80 = 0;
            $pos_81_90 = 0;
            $pos_91_100 = 0;

            foreach ($positions as $i => $position) {
                if (is_array($position)) {
                    $metrics_updated = [];
                    foreach ($position['metrics'] as $key => $metrics) {
                        if ($key == 'pos_1') {
                            $pos_1 = $metrics;
                        } else if ($key == 'pos_2_3') {
                            $pos_2_3 = $metrics;
                        } else if ($key == 'pos_4_10') {
                            $pos_4_10 = $metrics;
                        } else if ($key == 'pos_11_20') {
                            $pos_11_20 = $metrics;
                        } else if ($key == 'pos_21_30') {
                            $pos_21_30 = $metrics;
                        } else if ($key == 'pos_31_40') {
                            $pos_31_40 = $metrics;
                        } else if ($key == 'pos_41_50') {
                            $pos_41_50 = $metrics;
                        } else if ($key == 'pos_51_60') {
                            $pos_51_60 = $metrics;
                        } else if ($key == 'pos_61_70') {
                            $pos_61_70 = $metrics;
                        } else if ($key == 'pos_71_80') {
                            $pos_71_80 = $metrics;
                        } else if ($key == 'pos_81_90') {
                            $pos_81_90 = $metrics;
                        } else if ($key == 'pos_91_100') {
                            $pos_91_100 = $metrics;
                        }

                        $positions[$i]['metrics']['pos_1_3'] = intval($pos_1) + intval($pos_2_3);
                        $positions[$i]['metrics']['pos_4_10'] = intval($pos_4_10);
                        $positions[$i]['metrics']['pos_11_30'] = intval($pos_11_20) + intval($pos_21_30);
                        $positions[$i]['metrics']['pos_31_50'] = intval($pos_31_40) + intval($pos_41_50);
                        $positions[$i]['metrics']['pos_51_100'] = intval($pos_51_60) + intval($pos_61_70) + intval($pos_71_80) + intval($pos_81_90) + intval($pos_91_100);

                        unset($positions[$i]['metrics']['pos_1']);
                        unset($positions[$i]['metrics']['pos_2_3']);
                        unset($positions[$i]['metrics']['pos_11_20']);
                        unset($positions[$i]['metrics']['pos_21_30']);
                        unset($positions[$i]['metrics']['pos_31_40']);
                        unset($positions[$i]['metrics']['pos_41_50']);
                        unset($positions[$i]['metrics']['pos_51_60']);
                        unset($positions[$i]['metrics']['pos_61_70']);
                        unset($positions[$i]['metrics']['pos_71_80']);
                        unset($positions[$i]['metrics']['pos_81_90']);
                        unset($positions[$i]['metrics']['pos_91_100']);

                    }
                }

                $reordered = $positions;
            }

        }

        return $reordered;
    }

    public function validate_positions_real_terms_count($competitor_id = 0)
    {

        if (!$competitor_id) {
            $competitor_id = $this->our_own_website_index();
        }

        $historical_rank = $this->get_competitor_field_val($competitor_id, 'historical_rank');
        $search_terms = $this->get_competitor_field_val($competitor_id, 'search_terms');
        $last_metrics = !empty($historical_rank[0]['metrics']) ? $historical_rank[0]['metrics'] : [];

        $pos_1_3 = !empty($last_metrics['pos_1_3']) ? $last_metrics['pos_1_3'] : 0;
        $pos_4_10 = !empty($last_metrics['pos_4_10']) ? $last_metrics['pos_4_10'] : 0;
        $pos_11_30 = !empty($last_metrics['pos_11_30']) ? $last_metrics['pos_11_30'] : 0;
        $pos_31_50 = !empty($last_metrics['pos_31_50']) ? $last_metrics['pos_31_50'] : 0;
        $pos_51_100 = !empty($last_metrics['pos_51_100']) ? $last_metrics['pos_51_100'] : 0;

        $real_1_3 = [];
        $real_4_10 = [];
        $real_11_30 = [];
        $real_31_50 = [];
        $real_51_100 = [];

        foreach ((array)$search_terms as $term) {
            $position = $term['position'] ?? 0;
            $position = is_string($position) ? intval($position) : $position;
            if ($position >= 1 && $position <= 3) {
                $real_1_3[] = $position;
            }
            if ($position >= 4 && $position <= 10) {
                $real_4_10[] = $position;
            }
            if ($position >= 11 && $position <= 30) {
                $real_11_30[] = $position;
            }
            if ($position >= 31 && $position <= 50) {
                $real_31_50[] = $position;
            }
            if ($position >= 51 && $position <= 100) {
                $real_51_100[] = $position;
            }
        }

        if ($pos_1_3 < count($real_1_3)) {
            $historical_rank[0]['metrics']['pos_1_3'] = count($real_1_3);
        }

        if ($pos_4_10 < count($real_4_10)) {
            $historical_rank[0]['metrics']['pos_4_10'] = count($real_4_10);
        }

        if ($pos_11_30 < count($real_11_30)) {
            $historical_rank[0]['metrics']['pos_11_30'] = count($real_11_30);
        }

        if ($pos_31_50 < count($real_31_50)) {
            $historical_rank[0]['metrics']['pos_31_50'] = count($real_31_50);
        }

        if ($pos_51_100 < count($real_51_100)) {
            $historical_rank[0]['metrics']['pos_51_100'] = count($real_51_100);
        }

        update_term_meta($competitor_id, 'historical_rank', $historical_rank);
    }

    public function validate_positions_all_competitors($wp_send_json = true)
    {
        $status = false;

        $competitors = $this->get_competitors();

        foreach ($competitors as $competitor) {
            $this->validate_positions_real_terms_count($competitor->term_id);
            $status = true;
        }

        if ($wp_send_json) {
            wp_send_json([
                'status' => $status
            ]);
        }
    }

    /**
     * Remove Term
     */
    public function Remove_Term()
    {

        if (empty($_REQUEST['item_id'])) {
            wp_die();
        }

        $ID = $_REQUEST['competitor_index'];
        $terms = explode(',', $_REQUEST['item_id']);
        $removed_terms = [];
        $search_terms = $this->get_search_terms($ID);

        foreach ($terms as $term) {
            $removed_terms[] = $search_terms[$term]['keyword'];
            unset($search_terms[$term]);
        }
        update_term_meta($ID, 'search_terms', $search_terms);

        SEOAICAjaxResponse::alert('Competitor removed: ' . implode(',', $removed_terms))->wpSend();

    }

    /**
     * Remove Competitor
     */
    public function Remove_Competitor()
    {

        if (empty($_REQUEST['item_id'])) {
            wp_die();
        }

        $id = $_REQUEST['item_id'];

        $name = get_term_by('id', $id, self::COMPETITORS_AS_CATEGORIES_KEY)->name;
        $result = wp_delete_term($id, self::COMPETITORS_AS_CATEGORIES_KEY);

        if (is_wp_error($result)) {
            SEOAICAjaxResponse::error('Error:' . $result->get_error_message())->wpSend();
        } else {
            SEOAICAjaxResponse::alert('Competitor removed: ' . $name)->wpSend();
        }

    }

    /**
     * HTML Competitors page top tabs
     */
    public function Competitors_Page_Top_Tabs_HTML(): string
    {

        global $SEOAIC_OPTIONS;

        $competitors = $this->get_competitors();

        $m_0 = !$competitors ? 'ml-0' : '';

        $my_site = $this->seoaic_company_website_url();

        $html = '';
        $select = '';

        if (count($competitors) > 0) :
            $all = false;

            $html .= '<div class="competitors-watch dragscroll">';

            if (count($competitors) > 0) :
                $all = true;

                $html .= '<a class="active" href="#" data-index="-1" data-action="seoaic_Competitors_Compare_Section_HTML">
                            <span class="competitor active">' . __('All Competitors', 'seoaic') . '</span>
                          </a>';

                $select .= '<option value="-1">' . __('All Competitors', 'seoaic') . '</option>';
            endif;

            $i = 0;
            foreach ($competitors as $competitor) :
                $id = $competitor->term_id;
                $url = $this->get_competitor_field_val($id, 'url');
                $url_live = $this->get_competitor_field_val($id, 'url_live');
                $url_display = $this->get_competitor_field_val($id, 'url_display');
                $location = $this->get_competitor_field_val($id, 'location');

                $active = '';
                if ($id === 0 && !$all) {
                    $active = 'class="active"';
                }

                if ($url_live) :

                    $our_website = $url_live == $my_site ? 'our_website' : '';
                    $post_id = $url_live == $my_site ? '' : esc_attr($url);

                    $html .=
                        '<a class="' . $our_website . '" ' . $active . ' href="#" data-index="' . $id . '" data-action="seoaic_Competitors_Compare_Section_HTML" data-location="' . esc_html($location) . '">
                            <span class="competitor">
                                ' . esc_html($url_display) . '
                            </span>
                            <span data-post-id="' . $post_id . '"></span>
                        </a>';

                    $select .= '<option value="' . $id . '">' . esc_html($url_display) . '</option>';

                endif;
                $i++;
            endforeach;
            $html .= '</div>';
        endif;

        if (count($competitors) > 0) :
            $html .= '
                    <select class="select2-competitors">
                      ' . $select . '
                    </select>';
        endif;

        $migrated_competitors = isset($SEOAIC_OPTIONS['migrated_competitors']) ? json_encode($SEOAIC_OPTIONS['migrated_competitors']) : '';

        $html .= '<button title="Add new competitor"
                        data-title="' . __('Add a New Competitor', 'seoaic') . '"
                        type="button"
                        class="add-new-competitor-button competitor-button modal-button ' . esc_attr($m_0) . '"
                        data-action="seoaic_add_new_competitor"
                        data-modal="#add-idea"
                        data-mode="add"
                        data-form-callback="window_reload"
                >' . __('+ Add New', 'seoaic') . '
                    <div class="dn edit-form-items">
                        <input type="hidden" name="item_name" value="" data-label="' . __('Competitors’s Website', 'seoaic') . '">
                        <input type="hidden" name="action" value="seoaic_Add_New_Competitor">
                    </div>
                </button>';

        $html .= '<button data-title="Generate Competitor Content" 
                        type="button"
                        class="generate-competitor-content-btn button-primary outline ml-auto modal-button"
                        data-selected="get-selected"
                        data-post-id=""
                        data-competitor-id=""
                        disabled="disabled"
                        data-modal="#seoaic-post-mass-creation-modal"
                        data-action="seoaic_Prepare_Article_Based_Search_Term"
                        data-form-callback="window_reload"
                        data-migrated-competitors="' . $migrated_competitors . '"
                >' . __('Generate Competitor Content', 'seoaic') . '
                    <div class="dn edit-form-items">
                        <input type="hidden" name="item_name" value=""
                               data-label="' . __('Generate Competitor Content. Ex: cup,table', 'seoaic') . '">
                        <input type="hidden" name="action" value="seoaic_Generate_Article_Based_Search_Term">
                    </div>
                </button>';

        $html .= '<button title="Add new competitor"
                        data-title="' . __('Compare', 'seoaic') . '"
                        type="button"
                        class="competitor-compare competitor-button modal-button ' . esc_attr($m_0) . '"
                        data-action="seoaic_compare_competitor"
                        data-modal="#competitors-compare"
                        data-mode="add"
                        data-form-callback="window_reload"
                        disabled="disabled"
                >' . __('Compare', 'seoaic') . '
                </button>';

        $html .= '<button title="Remove" disabled type="button"
                        class="seoaic-remove-main modal-button confirm-modal-button ml-0"
                        data-modal="#seoaic-confirm-modal"
                        data-action="seoaic_Remove_Term"
                        data-form-callback="window_reload"
                        data-content="' . __('Do you want to remove selected keywords?', 'seoaic') . '"
                        data-selected="get-selected"
                        data-post-id=""
                ></button>';

        return $html;

    }

    public function our_own_website_index()
    {

        global $SEOAIC_OPTIONS;

        $id = !isset($SEOAIC_OPTIONS['my_competitor_term_id']) ? 0 : $SEOAIC_OPTIONS['my_competitor_term_id'];

        if (!$id) {
            return 0;
        }

        return $id;
    }

    public function our_own_website_url_for_competitors()
    {
        $our_website_term_id = $this->our_own_website_index();
        $url_live = $this->get_competitor_field_val($our_website_term_id, 'url_live');
        $location = $this->get_competitor_field_val($our_website_term_id, 'location');

        return [
            'my_website' => $url_live ?? '',
            'location' => $location ?? '',
            'index' => $our_website_term_id ?? 0
        ];
    }

    /**
     * Competitors Compare Section HTML
     */
    public function Competitors_Compare_Section_HTML(): string
    {

        $key = '';
        if (isset($_REQUEST['index'])) {
            $key = $_REQUEST['index'];
        }

        $competitors = $this->get_competitors();
        $my_site = $this->seoaic_company_website_url();
        $first_index = $this->our_own_website_index();

        $html = '';
        if (!empty($competitors)) {

            $html = '<div class="row-line heading">
                    <div class="check"></div>
                    <div class="company-name"></div>
                    <div class="page"></div>
                    <div class="position">
                        ' . __('Position', 'seoaic') . esc_html(' 1-3') . '
                    </div>
                    <div class="position">
                        ' . __('Position', 'seoaic') . esc_html(' 4-10') . '
                    </div>
                    <div class="position">
                        ' . __('Position', 'seoaic') . esc_html(' 11-30') . '
                    </div>
                    <div class="position">
                        ' . __('Position', 'seoaic') . esc_html(' 31-50') . '
                    </div>
                    <div class="position">
                        ' . __('Position', 'seoaic') . esc_html(' 51-100') . '
                    </div>
                    <div class="open-all-positions"></div>
                    <div class="delete"></div>
                </div>';

            foreach ((array)$competitors as $competitor) {

                $id = $competitor->term_id;
                $url = $this->get_competitor_field_val($id, 'url');
                $url_display = $this->get_competitor_field_val($id, 'url_display');
                $url_live = $this->get_competitor_field_val($id, 'url_live');
                $historical_rank = $this->get_competitor_field_val($id, 'historical_rank');
                $backlinks = $this->get_competitor_field_val($id, 'backlinks');
                $my_website = $this->our_own_website_index() == $id;

                if ($key && $key != -1 && $id != $first_index && $id != $key) {
                    continue;
                }

                $highlight = '';
                $siteURL = empty($url_display) ? $url : $url_display;

                if ($my_site == $url_live) {
                    $highlight = 'highlight';
                    $siteURL = $url_live;
                    $remove_id = '';
                } else {
                    $remove_id = $url;
                }

                if (isset($historical_rank[0]['metrics'])) {
                    $metrics = $historical_rank[0]['metrics'];
                }

                $pos_1 = empty($metrics['pos_1_3']) ? '-' : $metrics['pos_1_3'];
                $pos_2_3 = empty($metrics['pos_4_10']) ? '-' : $metrics['pos_4_10'];
                $pos_4_10 = empty($metrics['pos_11_30']) ? '-' : $metrics['pos_11_30'];
                $pos_11_20 = empty($metrics['pos_31_50']) ? '-' : $metrics['pos_31_50'];
                $pos_21_30 = empty($metrics['pos_51_100']) ? '-' : $metrics['pos_51_100'];
                $count = intval($pos_1) + intval($pos_2_3) + intval($pos_4_10) + intval($pos_11_20) + intval($pos_21_30);
                $backlinks = empty($backlinks) ? '-' : intval($backlinks);
                $etv = empty($metrics['etv']) ? 0 : $metrics['etv'];
                $rank_history = empty($historical_rank) ? [] : $historical_rank;
                $estimated_paid_traffic_cost = empty($metrics['estimated_paid_traffic_cost']) ? 0 : $metrics['estimated_paid_traffic_cost'];
                $inactive = !empty($metrics) ? '' : 'disabled';
                $etv = $etv ? number_format(round($etv), 0, ' ', ' ') : '-';
                $estimated_paid_traffic_cost = $estimated_paid_traffic_cost ? esc_html('$ ') . number_format(round($estimated_paid_traffic_cost), 2, ',', ' ') : '-';
                $my_website = !empty($my_website) ? ' disabled="disabled" ' : '';
                $checked = !empty($my_website) ? ' checked="checked" ' : '';
                $html .= '<div class="row-line ' . $highlight . '">
                            <div class="check competitor-check-key">
                                <input ' . $my_website . $checked . ' class="competitor-check-key" name="competitor-check-key" type="checkbox" data-competitor="' . $id . '" data-item="' . $url_live . '">
                            </div>
                            <div class="company-name"><span data-i="' . $id . '">' . esc_html($siteURL) . '</span></div>
                            <div class="page">
                                <div class="inner">
                                    <div class="key">' . __('Estimated traffic volume:', 'seoaic') . '</div>
                                    <div class="value">' . $etv . '</div>
                                    <div class="key">' . __('Estimated traffic worth:', 'seoaic') . '</div>
                                    <div class="value">' . $estimated_paid_traffic_cost . '</div>
                                    <div class="key">' . __('Total keywords ranked:', 'seoaic') . '</div>
                                    <div class="value">' . $count . '</div>
                                    <div class="key">' . __('Backlinks count:', 'seoaic') . '</div>
                                    <div class="value">' . number_format(intval($backlinks)) . '</div>
                                </div>
                            </div>
                            <div class="position pos_1">
                                ' . $pos_1 . '
                            </div>
                            <div class="position pos_2_3">
                                ' . $pos_2_3 . '
                            </div>
                            <div class="position pos_4_10">
                                ' . $pos_4_10 . '
                            </div>
                            <div class="position pos_11_20">
                                ' . $pos_11_20 . '
                            </div>
                            <div class="position pos_21_30">
                                ' . $pos_21_30 . '
                            </div>
                            <div class="open-all-positions">
                                <a href="#" data-modal="#ranking-modal"
                                   data-ranking=""
                                   class="modal-button ' . $inactive . '"
                                   data-chart-type="area"
                                   data-chart-id="#chart_competitors_positions"
                                   data-charts="' . esc_attr(json_encode($rank_history)) . '"
                                   data-charts-traffic="' . json_encode($rank_history) . '"
                                   >' . __('show historical graphs', 'seoaic') . '</a>
                            </div>
                            <div class="delete column-key">
                                <button title="' . __('Remove', 'seoaic') . '" type="button" class="seoaic-remove modal-button confirm-modal-button"
                                        class="remove modal-button"
                                        title="' . __('Remove competitor', 'seoaic') . '"
                                        data-post-id="' . $id . '"
                                        data-modal="#seoaic-confirm-modal"
                                        data-action="seoaic_remove_competitor"
                                        data-form-callback="window_reload"
                                        data-content="' . __('Do you want to remove this competitor:', 'seoaic') . ' ' . $remove_id . '?"
                                ></button>
                            </div>
                        </div>';
            }
        }

        if ($key) {
            wp_send_json($html);
        }

        return $html;

    }

    /**
     * Terms pagination HTML
     */
    public function terms_pagination(array $search_terms, int $page_size, int $page)
    {
        $total_records = count($search_terms);
        $total_pages = ceil($total_records / $page_size);
        if ($page > $total_pages) {
            $page = $total_pages;
        }
        if ($page < 1) {
            $page = 1;
        }
        $offset = ($page - 1) * $page_size;
        $search_terms = array_slice($search_terms, $offset, $page_size, true);
        // page links
        $N = min($total_pages, 4);
        $pages_links = array();
        $tmp = $N;
        if ($tmp < $page || $page > $N) {
            $tmp = 2;
        }
        for ($i = 1; $i <= $tmp; $i++) {
            $pages_links[$i] = $i;
        }
        if ($page > $N && $page <= ($total_pages - $N + 2)) {
            for ($i = $page - 3; $i <= $page + 3; $i++) {
                if ($i > 0 && $i < $total_pages) {
                    $pages_links[$i] = $i;
                }
            }
        }
        $tmp = $total_pages - $N + 1;
        if ($tmp > $page - 2) {
            $tmp = $total_pages - 1;
        }
        for ($i = $tmp; $i <= $total_pages; $i++) {
            if ($i > 0) {
                $pages_links[$i] = $i;
            }
        }
        $prev = 0;
        $pagination = '';
        $per_page_wrap = '';
        $prev_page = '';
        if (!empty($search_terms) || intval($total_pages) == 1) {
            $pagination .= '<div class="seoaic-pagination">';
            foreach ($pages_links as $key => $p) {
                if (count($pages_links) > 1) {
                    if (($p - $prev) > 1) {
                        $pagination .= '<a class="page-numbers pe-none" style="pointer-events:none;" href="#">' . esc_html('...') . '</a>';
                    }
                    $prev = $p;
                    $style_active = '';
                    if ($p == $page) {
                        $style_active = 'style="color: #fff;background-color: #100717;pointer-events:none;"';
                    }
                    if ($key === array_key_first($pages_links) && $page > 1) {
                        $prev = $page - 1;
                        $pagination .= '<a class="page-numbers" href="#" data-page="' . $prev . '">' . __('Prev', 'seoaic') . '</a>';
                    }
                    $pagination .= '<a class="page-numbers" ' . $style_active . ' href="#" data-page="' . $p . '">' . $p . '</a>';
                    if ($key === array_key_last($pages_links) && $p !== $page) {
                        $next = $page + 1;
                        $pagination .= '<a class="page-numbers" href="#" data-page="' . $next . '">' . __('Next', 'seoaic') . '</a>';
                    }
                } else {
                    $pagination = '';
                }
            }
        } else {
            $pagination = '<div class="seoaic-pagination"><span class="empty">' . __('No terms found', 'seoaic') . '</span></div>';
        }

        $pagination .= '</div>';

        if ($total_records > $page_size) {
            $per_page = array(50, 100, 250, 500, 1000);
            $options = '';
            foreach ($per_page as $per) {
                $selected = (intval($per) === $page_size) ? 'selected' : '';
                $options .= '<option value="' . $per . '" ' . $selected . '>' . $per . '</option>';
            }
            $label = '<span class="text-label">' . __('Per page', 'seoaic') . '</span>';
            $select = '<select class="form-select" data-page="1">' . $options . '</select>';

            $per_page_wrap .= $label . $select;
        }

        return [
            'pagination_html' => $pagination,
            'rows_array' => $search_terms,
            'per_page' => $per_page_wrap
        ];
    }

    public function sort_order_array($array, $key, $order = 'ASC')
    {
        $get_keys = array_keys($array);

        $wek = [];
        foreach ($array as $index => $row) {
            $wek[$index] = $row[$key];
        }

        $order = ($order == 'DESC') ? SORT_DESC : SORT_ASC;

        array_multisort($wek, $order, $array, $get_keys);

        return array_combine($get_keys, $array);
    }

    /**
     * Competitors Search Terms HTML
     */
    public function Competitors_Search_Terms_HTML(): string
    {
        $key = '';
        if (isset($_REQUEST['index'])) {
            $key = $_REQUEST['index'];
        }
        $page = !isset($_REQUEST['page']) ? 0 : $_REQUEST['page'];
        $per_page = !isset($_REQUEST['per_page']) ? 50 : intval($_REQUEST['per_page']);
        $keyword = !isset($_REQUEST['keyword']) ? '' : $_REQUEST['keyword'];
        $my_rank = !isset($_REQUEST['my_rank']) ? 'all-rank-terms' : $_REQUEST['my_rank'];
        $rank_min = !isset($_REQUEST['rank-min']) ? 0 : intval($_REQUEST['rank-min']);
        $rank_max = !isset($_REQUEST['rank-max']) ? 0 : intval($_REQUEST['rank-max']);
        $search_vol_min = !isset($_REQUEST['search-vol-min']) ? 0 : intval($_REQUEST['search-vol-min']);
        $search_vol_max = !isset($_REQUEST['search-vol-max']) ? 0 : intval($_REQUEST['search-vol-max']);
        $order_name = !isset($_REQUEST['order_name']) ? 'position' : $_REQUEST['order_name'];
        $order = !isset($_REQUEST['order']) ? 'DESC' : $_REQUEST['order'];
        $search_terms = $this->get_search_terms($key);
        $searches = $this->get_search_terms_field_val($key, 'search_volume');
        $positions = $this->get_search_terms_field_val($key, 'position');
        $our_own_website_id = $this->our_own_website_index();

        $select_my_rank = '
        <select>
            <option value="all-rank-terms">' . __('All ranks', 'seoaic') . '</option>
            <option value="i-rank-with">' . __('I\'m ranking', 'seoaic') . '</option>
            <option value="i-dont-rank-with">' . __('I\'m not ranked', 'seoaic') . '</option>
        </select>';

        $my_website = $key == $our_own_website_id ? __('All ranks', 'seoaic') : $select_my_rank;

        $searches_min = $searches ? min($searches) : 0;
        $searches_min = intval($searches_min) ? $searches_min : 0;
        $searches_max = $searches ? max($searches) : 0;
        $searches_max = intval($searches_max) ? $searches_max : 0;
        $positions_min = $positions ? min($positions) : 0;
        $positions_min = intval($positions_min) ? $positions_min : 0;
        $positions_max = $positions ? max($positions) : 0;
        $positions_max = intval($positions_max) ? $positions_max : 0;

        $html = '';
        $pagination = '';

        if (!empty($search_terms)) {

            if (!$keyword && !$rank_min && !$rank_max && !$search_vol_min && !$search_vol_max && !$page) {
                $html .=
                    '<div class="row-line heading">
                        <div class="check">
                            <input name="seoaic-select-all-keywords" type="checkbox">
                        </div>
                        <div class="keyword seoai-filterable seoai-filter-text left-opener" data-sort="keyword" data-competitor-index="' . $key . '">
                            ' . __('Keyword', 'seoaic') . '
                            <span class="sorting">
                                <span class="asc">&#9662</span>
                                <span class="desc">&#9662</span>
                            </span>
                        </div>
                        <div class="page">
                            ' . __('Page', 'seoaic') . '
                        </div>
                        <div class="rank seoai-filterable seoai-filter-num"  data-min="' . $positions_min . '" data-max="' . $positions_max . '" data-sort="rank" data-competitor-index="' . $key . '">
                           
                            ' . $my_website . '
                            <span class="sorting">
                                <span class="asc">▾</span>
                                <span class="desc">▾</span>
                            </span>
                        </div>
                        <div class="search-vol seoai-filterable seoai-filter-num seoai-opener-extra-height" data-min="' . $searches_min . '" data-max="' . $searches_max . '" data-sort="search-vol" data-competitor-index="' . $key . '">
                           ' . __('Search Volume', 'seoaic') . '
                            <span class="sorting">
                                <span class="asc">&#9662</span>
                                <span class="desc">&#9662</span>
                            </span>
                        </div>
                        <div class="difficulty">
                            ' . __('Difficulty', 'seoaic') . '
                        </div>
                        <div class="value-key ETC">
                            ' . __('ETC', 'seoaic') . '
                        </div>
                        <div class="value-key ETV">
                            ' . __('ETV', 'seoaic') . '
                        </div>
                        <div class="delete"></div>
                    </div>';
            }

            if ($keyword || $rank_min || $rank_max || $search_vol_min || $search_vol_max) {

                $filter = [
                    'keyword' => $keyword,
                    'position_min' => $rank_min,
                    'position_max' => $rank_max,
                    'search_vol_min' => $search_vol_min,
                    'search_vol_max' => $search_vol_max,
                    'my_rank' => $my_rank
                ];

                $search_terms = array_filter($search_terms, function ($item) use ($filter) {

                    $s = $filter['keyword'];

                    if (strlen(trim(preg_replace('/\xc2\xa0/', '', $s))) == 0) {
                        $filter['keyword'] = '';
                    }

                    $word = !is_array($item['keyword']) ? $item['keyword'] : implode(' ', $item['keyword']);

                    $rank = intval($item['my_rank']);

                    return
                        ($filter['keyword'] == '' || strpos($word, trim($filter['keyword'])) !== false)
                        &&
                        (($filter['search_vol_min'] == 0 || $item['search_volume'] >= $filter['search_vol_min']) && ($filter['search_vol_max'] == 0 || $item['search_volume'] <= $filter['search_vol_max']))
                        &&
                        (($filter['position_min'] == 0 || intval($item['position']) >= $filter['position_min']) && ($filter['position_max'] == 0 || intval($item['position']) <= $filter['position_max']))
                        &&
                        (
                            ($filter['my_rank'] === 'all-rank-terms')
                            ||
                            ($filter['my_rank'] === 'i-dont-rank-with' && ($rank == 0))
                            ||
                            ($filter['my_rank'] === 'i-rank-with' && intval($item['my_rank']) > 0)
                        );
                });

            }

            if ($order_name) {
                switch ($order_name) {
                    case 'rank':
                        $order_name = 'position';
                        break;
                    case 'search-vol':
                        $order_name = 'search_volume';
                        break;
                }
                $search_terms = $this->sort_order_array($search_terms, $order_name, $order);
            }

            if (!$page) {
                $page = 1;
            }

            $terms_pagination = $this->terms_pagination($search_terms, $per_page, intval($page));
            $search_terms = $terms_pagination['rows_array'];
            $pagination = !empty($terms_pagination['pagination_html']) ? $terms_pagination['pagination_html'] : '';

            foreach ($search_terms as $i => $term) {

                $loading = $term['my_rank'] === -1 ? ' view loading' : '';
                $my_rank = !empty($term['my_rank']) && $term['my_rank'] > 0 ? $term['my_rank'] : ($loading ? '' : __('Show ', 'seoaic'));
                $my_rank_class = !empty($term['my_rank']) && $term['my_rank'] > 0 ? '' : 'empty' . $loading;
                $updated = !empty($term['my_rank']) ? 'ready' : '';
                $status = $loading ? ' term-ready-to-update running' : '';

                $html .=
                    '<div class="row-line">
                        <div class="check"><input class="seoaic-check-key" name="seoaic-check-key" type="checkbox" data-keyword="' . $i . '" data-term="' . $term['keyword'] . '">
                        </div>
                        <div class="keyword" data-i="' . $i . '"><span>' . $term['keyword'] . '</span>' . $this->GetCreatedPosts(740994, 697953951) . '</div>
                        <div class="page">
                            ' . (!empty($term['page']) ? '<a href="' . $term['page'] . '" target="_blank">' . $term['page'] . '</a>' : '-') . '
                            ' . (!empty($term['meta_description']) ? '<span>' . $term['meta_description'] . '</span>' : '-') . '
                        </div>
                        <div class="rank ' . $updated . $status . '" data-term="' . $i . '" data-competitor="' . $key . '">
                            ' . (!empty($term['position']) ? '<a href="' . $term['page'] . '" target="_blank">' . $term['position'] . '</a>' : '-') . '
                            <div class="' . $my_rank_class . '"><span>' . $my_rank . '</span>' . __(' my rank', 'seoaic') . '</div>
                        </div>
                        <div class="search-vol">' . (!empty($term['search_volume']) ? $term['search_volume'] : '-') . '</div>
                        
                        <div class="difficulty ' . (!empty($term['difficulty']) ? $term['difficulty'] : '-') . '">
                            <span>' . (!empty($term['difficulty']) ? $term['difficulty'] : '-') . '</span>
                        </div>
                        
                        <div class="value-key ETC">
                            <span>' . (!empty($term['etc']) ? round(intval($term['etc'])) : '-') . '</span>
                        </div>
                        
                        <div class="value-key ETV">
                            <span>' . (!empty($term['etv']) ? round(intval($term['etv'])) : '-') . '</span>
                        </div>
                        
                        <div class="delete column-key">
                            <button title="' . __('Remove', 'seoaic') . '" type="button" class="seoaic-remove modal-button confirm-modal-button"
                                data-modal="#seoaic-confirm-modal"
                                data-action="seoaic_Remove_Term"
                                data-form-callback="window_reload"
                                data-content="' . __('Do you want to remove this keyword?', 'seoaic') . '"
                                data-post-id="' . $term['keyword'] . '"
                                ></button>
                        </div>
                    </div>';

            }
        } else {
            if ($key != -1 and $key != '') {
                $html .= '<div class="no-search-terms-found">' . __('No Search Terms Found', 'seoaic') . '<span></span></div>';
            }
        }

        if ($key) {
            wp_send_json(
                [
                    'html' => $html,
                    'pagination' => $pagination,
                    'per_page' => !empty($terms_pagination) ? $terms_pagination['per_page'] : ''
                ]
            );
        }

        return $html;

    }

    /**
     * Get created posts with Search term
     * string $key
     * string $value
     */
    public function GetCreatedPosts($competitor, $term)
    {

        $args = [
            'post_status' => ['publish', 'draft', 'future'],
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'seoai_competitor_index',
                    'value' => intval($competitor),
                ],
                [
                    'key' => 'seoai_term_index',
                    'value' => intval($term),
                ],
            ],
        ];

        $query = get_posts($args);
        $count = count($query);
        $label = $count == 1 ? __(' post created', 'seoaic') : __(' posts created', 'seoaic');
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
        if ($count > 0) {
            $posts .= '<a href="#" href="#" data-action="seoaic_generate_ideas" data-modal="' . $modal . '" class="modal-button confirm-modal-button" data-content="' . esc_attr(json_encode($list_posts)) . '" data-modal-title="' . __('Created posts', 'seoaic') . '">' . $count . $label . '</a>';
        }

        return $posts;
    }

    public function set_values_my_rank_in_progress(): void
    {
        if (!isset($_REQUEST['term'])) {
            wp_die();
        }

        $term = $_REQUEST['term'];
        $index = $_REQUEST['index'];

        $this->update_competitors_search_term_field($index, $term, 'my_rank', -1);

        wp_send_json([
            'status' => true
        ]);
    }

    public function request_my_rank_value($competitor_id, $term_index, $term_keyword, $return = false)
    {
        global $SEOAIC_OPTIONS, $SEOAIC;

        $language = $this->seoaic_language();
        $location = $this->seoaic_location();
        $our_own_website_url = $this->our_own_website_url_for_competitors();

        $data = [
            'email' => $SEOAIC_OPTIONS['seoaic_api_email'],
            'token' => $SEOAIC_OPTIONS['seoaic_api_token'],
            'language' => $language,
            'location' => $location,
            'target' => $our_own_website_url['my_website'],
            'limit' => 100,
            'add_terms' => $term_keyword,
        ];

        $result = $SEOAIC->curl->init('api/ai/rank-search-terms', $data, true, true, true);

        $my_rank = !isset($result['data'][0]['position']) ? '—' : $result['data'][0]['position'];

        $this->update_competitors_search_term_field($competitor_id, $term_index, 'my_rank', $my_rank);

        if ($return) {
            return $my_rank;
        }
    }

    public function Check_Terms_Update_Progress()
    {

        if (!isset($_REQUEST['term'])) {
            wp_die();
        }

        $competitor_id = $_REQUEST['index'];
        $term_index = $_REQUEST['term'];

        $search_terms = $this->get_search_terms($competitor_id);
        $term_keyword = $search_terms[$term_index]['keyword'];

        $html = [];

        $get_my_rank = $this->request_my_rank_value($competitor_id, $term_index, $term_keyword, true);

        $loading = $get_my_rank === -1 ? ' view loading' : '';
        $my_rank = !empty($get_my_rank) && $get_my_rank > 0 ? $get_my_rank : ($loading ? '' : __('Show ', 'seoaic'));
        $my_rank_class = !empty($get_my_rank) && $get_my_rank > 0 ? '' : 'empty' . $loading;
        $updated = !empty($get_my_rank) ? 'ready' : '';
        $status = $loading ? ' term-ready-to-update running' : '';

        if (!empty($term_index)) {
            $html = [
                'html' => '<div class="' . $my_rank_class . '" data-i="' . $term_index . '"><span>' . $my_rank . '</span>' . __(' my rank', 'seoaic') . '</div>',
                'index_term' => $term_index,
                'index_comp' => $competitor_id,
                'updated' => $updated,
                'status' => $status,
            ];
        }

        if ($competitor_id) {
            wp_send_json([
                'status' => true,
                'html' => $html
            ]);
        }

    }

    public function recursive_get_array_vlues($array, &$result)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $this->recursive_get_array_vlues($value, $result);
            } elseif ($key === 'text' || $key === 'h_title') {
                $result[] = $value;
            }
        }
    }

    private function seoaic_get_external_content($url)
    {
        global $SEOAIC_OPTIONS, $SEOAIC;

        $plain_text = [];

        $data = [
            'email' => $SEOAIC_OPTIONS['seoaic_api_email'],
            'token' => $SEOAIC_OPTIONS['seoaic_api_token'],
            'url' => $url,
        ];

        $response = $SEOAIC->curl->init('api/ai/content-parsing', $data, true, true, true);

        $this->recursive_get_array_vlues($response, $plain_text);

        return implode(' ', $plain_text);
    }

    /**
     * Prepare Article Based Search Term
     */
    public function Prepare_Article_Based_Search_Term()
    {
        global $SEOAIC;

        $id = !isset($_REQUEST['competitor']) ? '' : $_REQUEST['competitor'];
        $terms = !isset($_REQUEST['idea-mass-create']) ? [] : $_REQUEST['idea-mass-create'];
        $terms = !is_array($terms) ? [$terms] : $terms;
        $created_ideas = [];

        foreach ($terms as $term) {

            $search_terms = $this->get_search_terms($id);
            $term_keyword = $search_terms[intval($term)]['keyword'];
            $source_page = $search_terms[intval($term)]['page'];

            $source_content = !empty($this->seoaic_get_external_content($source_page)) ? $this->seoaic_get_external_content($source_page) : $term_keyword;

            $source_data = [
                'term' => $term_keyword,
                'source_content' => $source_content
            ];

            $ID = $SEOAIC->ideas->generate(true, 1, $source_content, $term_keyword);
            update_post_meta($ID, 'seoaic_idea_source', $source_data);
            $created_ideas[] = $ID;
        }

        $SEOAIC->posts->postsMassGenerate($created_ideas);

        wp_send_json([
            'competitor' => $id,
            'term' => $terms,
            'status' => 'success',
        ]);

    }

    /**
     * Slugify string
     */
    public function Slug($text)
    {
        $text = preg_replace('~[^\pL\d]+~u', '_', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '_', $text);
        $text = strtolower($text);
        if (empty($text)) {
            return 'n-a';
        }
        return $text;
    }

    /**
     * Slugify URL
     * param $text string
     */
    public static function Slugify_url($text)
    {
        $text = trim($text, '/');
        if (!preg_match('#^http(s)?://#', $text)) {
            $text = 'http://' . $text;
        }
        $text = parse_url($text);
        $path = '';
        $text = preg_replace('/^www\./', '', $text['host']);
        $text = $text . $path;
        $text = preg_replace('~[^\pL\d.\/-]+~u', '', $text);
        $text = preg_replace("~\/(?!.*\/)(.*)~", '', $text);
        $text = trim($text, '');
        $text = strtolower($text);
        if (empty($text)) {
            return 'n-a';
        }
        return $text;
    }


    public function update_competitors_data()
    {

        global $SEOAIC_OPTIONS;

        $ready_to_update_competitors = [];

        $api = !empty($SEOAIC_OPTIONS['seoaic_api_email']) ? $SEOAIC_OPTIONS['seoaic_api_email'] : false;

        $update = isset($_GET['update-competitors']) && $_GET['update-competitors'] == $api ? true : false;

        $time_update = $update ? (time() * 100) : time();

        $competitors = $this->get_competitors();

        foreach ($competitors as $competitor) {

            $ID = $competitor->term_id;
            $next_update = $this->get_competitor_field_val($ID, 'next_update');
            $historical_rank = $this->get_competitor_field_val($ID, 'historical_rank');
            $my_website = $this->get_competitor_field_val($ID, 'my_website');
            $url_live = $this->get_competitor_field_val($ID, 'url_live');
            $url = $this->get_competitor_field_val($ID, 'url');
            $location = $this->get_competitor_field_val($ID, 'location');
            if (
                isset($next_update) && intval($next_update) < $time_update
                or !isset($next_update)
                or !isset($historical_rank)
                or !isset($my_website)
            ) {

                $url = empty($url_live) ? $url : $url_live;
                $seoaic_location = $this->seoaic_location();
                $location = empty($location) ? $seoaic_location : $location;

                $ready_to_update_competitors[] = [
                    'index' => $ID,
                    'domain' => $url,
                    'location' => $location,
                ];
            }
        }

        $SEOAIC_OPTIONS['ready_to_update_competitors'] = $ready_to_update_competitors;
        update_option('seoaic_options', $SEOAIC_OPTIONS);

        $item = !empty($_REQUEST['domain']) ? $_REQUEST['domain'] : '';
        $location = !empty($_REQUEST['location']) ? $_REQUEST['location'] : '';
        $index = !empty($_REQUEST['index']) ? $_REQUEST['index'] : 0;

        $this->fix_www_domain_url();

        if (empty($item)) {
            return;
        }

        $updated_competitors = array_filter($SEOAIC_OPTIONS['ready_to_update_competitors'], function ($item) use ($index) {
            return $item['index'] !== $index;
        });

        $SEOAIC_OPTIONS['ready_to_update_competitors'] = $updated_competitors;
        update_option('seoaic_options', $SEOAIC_OPTIONS);

        if (!empty($SEOAIC_OPTIONS['ready_to_update_competitors']) or !$update) {
            $this->Add_New_Competitor($item, $location, $index, true);
        }

    }

    public function search_volume_of_first_term($competitors_ids, $term_keyword)
    {
        $search_volume = 0;
        foreach ($competitors_ids as $id) {
            $search_terms = $this->get_search_terms($id);
            foreach ($search_terms as $term) {
                if ($term_keyword === $term['keyword']) {
                    $search_volume = isset($term['search_volume']) ? intval($term['search_volume']) : 0;
                    break;
                }
            }
        }
        return $search_volume == 0 ? '—' : $search_volume;
    }

    public function get_competitor_position_by_term($id, $term_keyword)
    {
        $search_terms = $this->get_search_terms($id);
        $position = 0;
        foreach ($search_terms as $term) {
            if ($term_keyword === $term['keyword']) {
                $position = !empty($term['position']) ? $term['position'] : 0;
                break;
            }
        }

        return $position == 0 ? '—' : $position;
    }

    public function get_competitor_location($id)
    {
        $location = $this->seoaic_location();
        $competitor_location = $this->get_competitor_field_val($id, 'location');
        return empty($competitor_location) ? $location : $competitor_location;
    }

    public function compare_competitors()
    {

        if (empty($_REQUEST['competitors'])) {
            wp_die();
        }

        $competitors = $_REQUEST['competitors'];
        $competitors = explode(',', $competitors);

        // Collecting the cross terms
        $terms = [];
        $competitors_heading_html = '';
        foreach ($competitors as $competitor) {
            $location = $this->get_competitor_location($competitor);
            $search_terms = $this->get_search_terms($competitor);
            if (
                isset($search_terms)
                && is_array($search_terms)
            ) {
                $get_terms = [];
                foreach ($search_terms as $term) {
                    if (isset($term['keyword'])) {
                        $get_terms[] = $term['keyword'];
                    }
                }
                $terms[] = array_values(array_unique($get_terms));
                $url_live = $this->get_competitor_field_val($competitor, 'url_live');

                $competitors_heading_html .= '
                <div class="col website" data-location="' . $location . '" data-website="' . $url_live . '" data-column="' . sanitize_title($url_live . ' ' . $location) . '">
                    <div class="inner">' . $url_live . '
                        <span class="sorting">
                            <span class="asc">▾</span>
                            <span class="desc">▾</span>
                        </span>
                    </div>
                </div>
                ';
            }
        }
        $terms = call_user_func_array("array_merge", $terms);
        $counts = array_count_values($terms);
        $duplicates = array_filter($counts, function ($value) {
            return $value >= 0;
        });

        $terms = [];
        foreach ($duplicates as $key => $value) {
            $terms[] = $key;
        }
        // END get cross terms

        $term_html = '';
        $search_volumes = [];
        foreach ($terms as $term) {
            $search_vol = $this->search_volume_of_first_term($competitors, $term);
            $search_volumes[] = intval($search_vol);
        }
        foreach ($terms as $i => $term) {
            $competitors_html = '';
            $search_vol = $this->search_volume_of_first_term($competitors, $term);
            foreach ($competitors as $competitor) {
                $location = $this->get_competitor_location($competitor);

                $url_live = $this->get_competitor_field_val($competitor, 'url_live');
                $competitors_html .= '
                <div class="col website ' . sanitize_title($url_live . ' ' . $location) . '">
                    <div class="inner">' . $this->get_competitor_position_by_term($competitor, $term) . '</div>
                </div>
                ';
            }
            $term_html .=
                '<div class="table-row">
                <div class="col checkbox">
                    <div class="inner">
                        <div class="position-relative">
                            <input type="checkbox" class="seoaic-checkbox select-compare" data-term="' . $term . '" data-term-id="' . $i . '">
                        </div>
                    </div>
                </div>
                <div class="col keyword">
                    <div class="inner">' . $term . '</div>
                </div>
                <div class="col serp">
                    <div class="inner"><a href="#" data-keyword="' . $term . '" data-location="" data-action="seoaic_compare_competitors_term" data-modal="#add-competitors" class="modal-button"><span>' . __('View', 'seoaic') . '</span></a></div>
                </div>
                <div class="col search-volume">
                    <div class="inner">' . $search_vol . '</div>
                </div>
                ' . $competitors_html . '
            </div>';
        }

        $filterable = min($search_volumes) !== max($search_volumes) ? 'seoai-filterable' : '';

        $html = '
        <div class="table-competitors-compare dragscroll">
            <div class="table-row heading">
                <div class="col checkbox">
                    <div class="inner">
                        <div class="position-relative">
                            <input type="checkbox" class="seoaic-checkbox select-all-compare">
                        </div>
                    </div>
                </div>
                <div class="col keyword" data-column="keyword">
                    <div class="inner">' . __('Keyword', 'seoaic') . '
                        <span class="sorting">
                            <span class="asc">▾</span>
                            <span class="desc">▾</span>
                        </span>
                    </div>
                </div>
                <div class="col serp">
                    <div class="inner"><button disabled data-modal="#add-keyword-modal" class="serp-selected modal-button">' . __('Add to keywords', 'seoaic') . '</button></div>
                </div>
                <div class="col search-volume ' . $filterable . ' seoai-filter-num float-slider" data-sort="search-volume-compare" data-column="search-volume" data-min="' . min($search_volumes) . '" data-max="' . max($search_volumes) . '">
                    <div class="inner">' . __('Search volume', 'seoaic') . '
                        <span class="sorting">
                            <span class="asc">▾</span>
                            <span class="desc">▾</span>
                        </span>
                    </div>
                </div>
                ' . $competitors_heading_html . '
            </div>
            ' . $term_html . '
        </div>
        ';

        wp_send_json([
            'html' => $html,
            'search_vol_min' => min($search_volumes),
            'search_vol_max' => max($search_volumes)
        ]);

    }

    public function get_search_term_index($keyword, $location, $option_name)
    {
        global $SEOAIC_OPTIONS;
        $search_terms = !empty($SEOAIC_OPTIONS[$option_name]['google']) ? $SEOAIC_OPTIONS[$option_name]['google'] : [];
        $index = '';
        foreach ($search_terms as $key => $term) {
            if ($term['keyword'] === $keyword && $term['location'] === $location) {
                $index = $key;
            }
        }
        return $index;
    }

    public function COMPARE_my_article() {
        global $SEOAIC;
        $location = $_REQUEST['location'];
        $keyword = $_REQUEST['keyword'];
        $option = 'terms_to_compare';
        $SEOAIC->rank->limit_terms_to_compere_store(10);
        $index = self::get_search_term_index($keyword, $location, $option);
        if (!$index) {
            $term = $SEOAIC->rank->Add_Terms([$keyword], 'google', '', $location, false, true, $option);
            $index = !empty($term[0]['index']) ? $term[0]['index'] : 0;
        }
        $my_article = $SEOAIC->rank->my_article_popup_top_table_analysis($index, true, $option);
        wp_send_json($my_article);
    }

    public function COMPARE_my_competitors() {
        global $SEOAIC;
        $location = $_REQUEST['location'];
        $keyword = $_REQUEST['keyword'];
        $comp = $_REQUEST['competitors'];
        $option = 'terms_to_compare';
        $index = self::get_search_term_index($keyword, $location, $option);
        $competitors = $SEOAIC->rank->Get_Competitors(true, $index, $comp, $option);
        wp_send_json($SEOAIC->rank->competitors_table_html($competitors));
    }

    public function COMPARE_analysis() {
        global $SEOAIC,$SEOAIC_OPTIONS;
        $location = $_REQUEST['location'];
        $keyword = $_REQUEST['keyword'];
        $comp = $_REQUEST['competitors'];
        $option = 'terms_to_compare';
        $index = self::get_search_term_index($keyword, $location, $option);
        $keyword = $SEOAIC_OPTIONS[$option]['google'][$index]['keyword'];
        $competitor_article = $SEOAIC->rank->competitor_article_popup_table_analysis($index, $keyword, $comp, $option, true);
        wp_send_json($competitor_article);
    }

    public function COMPARE_other_positions()
    {
        global $SEOAIC;
        $location = $_REQUEST['location'];
        $keyword = $_REQUEST['keyword'];
        $option = 'terms_to_compare';
        $index = self::get_search_term_index($keyword, $location, $option);
        $top_five_competitors = $SEOAIC->rank->competitors_table_html($SEOAIC->rank->Get_Competitors(true, $index, [], $option), 5);
        wp_send_json($this->other_top_google_positions($top_five_competitors, ''));
    }

    public function get_top_google_analysis() {
        global $SEOAIC;
        $location = $_REQUEST['location'];
        $keyword = $_REQUEST['keyword'];
        $option = 'terms_to_compare';
        $competitor = $_REQUEST['competitor'];
        $index = $this->get_search_term_index($keyword, $location, $option);
        $competitor_article = $SEOAIC->rank->competitor_article_popup_table_analysis($index, $keyword, [$competitor], $option);
        wp_send_json($competitor_article);
    }

    public function other_top_google_positions($competitors, $analysis) {

        $html = '<div class="other-top-5-positions">';

            $html .= '<h3>' . __("Top 5 in google", "seoaic") . '</h3>';

            $html .= '<div class="content-table">
                        <div class="body">' . $competitors . '</div>
                        <div class="competitor-article loading">
                        ' . $analysis . '
                            <a href="#" class="load-more-btn" >View more</a>
                        </div>
                      </div>';

        $html .= '</div>';

        return $html;
    }

    public function remove_all_competitor_terms($taxonomy)
    {
        global $wpdb;
        $sql = "DELETE FROM {$wpdb->terms} WHERE term_id IN (
            SELECT term_id FROM {$wpdb->term_taxonomy} WHERE taxonomy = '{$taxonomy}'
        )";
        $wpdb->query($sql);
        $deleted_term_count = $wpdb->rows_affected;

        $sql = "DELETE FROM {$wpdb->termmeta} WHERE term_id IN (
            SELECT term_id FROM {$wpdb->term_taxonomy} WHERE taxonomy = '{$taxonomy}'
        )";
        $wpdb->query($sql);
        $deleted_meta_count = $wpdb->rows_affected;

        return "Removed {$deleted_term_count} terms and {$deleted_meta_count} metadata entries from '{$taxonomy}'.";
    }

    public function combine_url_location($array)
    {
        $data = [];
        foreach ((array)$array as $i => $item) {
            $data[] = [
                'url_live' => $item['url_live'] ?? '',
                'location' => $item['location'] ?? '',
                'index' => $i,
            ];
        }

        return $data;
    }

    public function migrate_competitors_from_options()
    {

        global $SEOAIC_OPTIONS;

        $competitors_options = !empty($SEOAIC_OPTIONS['competitors']) ? $SEOAIC_OPTIONS['competitors'] : [];
        if ($competitors_options && !isset($SEOAIC_OPTIONS['migrated_competitors'])) {
            $SEOAIC_OPTIONS['migrated_competitors'] = $this->combine_url_location($competitors_options);
        }

        $tax = self::COMPETITORS_AS_CATEGORIES_KEY;
        $count = 0;
        foreach ($competitors_options as $index => $competitor) {

            $ID = 0;
            $next_update = $competitor['next_update'];
            $url = $competitor['url_live'];
            $slugLocation = '_' . $this->Slug($competitor['location']);
            $location = $competitor['location'];
            $search_terms = $competitor['search_terms'];
            $backlinks_result = $competitor['backlinks'];
            $reordered_rank_history = $competitor['historical_rank'];

            unset($SEOAIC_OPTIONS['competitors'][$index]);
            update_option('seoaic_options', $SEOAIC_OPTIONS);

            $slug = $url . $slugLocation;
            $term_exist = get_term_by('slug', $slug, $tax);
            if (!$term_exist) {
                $this->competitors_fields($ID, $next_update, $url, $slugLocation, $location, $search_terms, $backlinks_result, [], $reordered_rank_history);
            }

            if ($count == 1) {
                break;
            }

            $count++;
        }

        wp_send_json([
            'url' => $url ?? '',
            'location' => $location ?? ''
        ]);

    }

    public function clean_all_competitors_data()
    {
        if (isset($_GET['remove-competitors']) && $_GET['remove-competitors']) {
            $this->remove_all_competitor_terms(self::COMPETITORS_AS_CATEGORIES_KEY);
        }
    }
}