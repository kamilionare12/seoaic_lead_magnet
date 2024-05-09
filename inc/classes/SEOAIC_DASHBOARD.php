<?php

namespace SEOAIC;

class SEOAIC_DASHBOARD
{
    private $seoaic;

    public function __construct($_seoaic)
    {
        $this->seoaic = $_seoaic;
        add_action('wp_ajax_seoaic_run_dashboard_data', [$this, 'run_dashboard_data']);
        add_action('wp_ajax_seoaic_dashboard_HTML', [$this, 'dashboard_HTML']);
    }

    public function set_all_settings_data()
    {
        global $SEOAIC_OPTIONS, $SEOAIC;

        $language = $SEOAIC->competitors->seoaic_language();
        $location = $SEOAIC->competitors->seoaic_location();
        $site_url = $SEOAIC->competitors->seoaic_company_website_url();
        $api_email = !empty($SEOAIC_OPTIONS['seoaic_api_email']) ? $SEOAIC_OPTIONS['seoaic_api_email'] : 'not valid email';
        $api_token = !empty($SEOAIC_OPTIONS['seoaic_api_token']) ? $SEOAIC_OPTIONS['seoaic_api_token'] : 'not valid token';
        $time_updated = !empty($SEOAIC_OPTIONS['on_page_seo_data']['time_updated']) ? $SEOAIC_OPTIONS['on_page_seo_data']['time_updated'] : 0;
        $time_next_update = !empty($SEOAIC_OPTIONS['on_page_seo_data']['time_next_update']) ? $SEOAIC_OPTIONS['on_page_seo_data']['time_next_update'] : 0;
        $positions = !isset($SEOAIC_OPTIONS['on_page_seo_data']['positions']) ? [] : $SEOAIC_OPTIONS['on_page_seo_data']['positions'];
        $backlinks = !isset($SEOAIC_OPTIONS['on_page_seo_data']['data'][0]['backlinks']) ? 0 : $SEOAIC_OPTIONS['on_page_seo_data']['data'][0]['backlinks'];
        $etv = !empty($positions['etv']) ? round($positions['etv']) : 0;
        $paid_traffic_cost = !empty($positions['estimated_paid_traffic_cost']) ? round($positions['estimated_paid_traffic_cost']) : 0;
        $page_rank = !empty($positions['count']) ? round($positions['count']) : 0;

        return [
            'refresh_interval' => DAY_IN_SECONDS,
            'language' => $language,
            'location' => $location,
            'api_email' => $api_email,
            'api_token' => $api_token,
            'site_url' => $site_url,
            'time_updated' => $time_updated,
            'time_next_update' => $time_next_update,
            'positions' => $positions,
            'backlinks' => $backlinks,
            'etv' => $etv,
            'paid_traffic_cost' => $paid_traffic_cost,
            'page_rank' => $page_rank,
        ];
    }

    /**
     * @throws Exception
     */
    public function run_dashboard_data()
    {
        global $SEOAIC, $SEOAIC_OPTIONS;

        $all_settings = $this->set_all_settings_data();

        $next_update_time = $all_settings['time_next_update'];
        $refresh_interval = $all_settings['refresh_interval'];
        $our_website_ID = $SEOAIC->competitors->our_own_website_index();

        $backlinks = $SEOAIC->competitors->get_competitor_field_val($our_website_ID, 'backlinks') ?? 0;
        $historical_rank = get_term_meta( $our_website_ID, 'historical_rank', true) ?? [];
        $search_terms = get_term_meta( $our_website_ID, 'search_terms', true) ?? [];
        $etv = !empty($search_terms) && is_array($search_terms) ? round(reset($search_terms)['positions']['etv']) : 0;
        $paid_traffic_cost = !empty($search_terms) && is_array($search_terms) ? round(reset($search_terms)['positions']['estimated_paid_traffic_cost']) : 0;
        $pos_1_3 = $historical_rank[0]['metrics']['pos_1_3'] ?? 0;
        $pos_4_10 = $historical_rank[0]['metrics']['pos_4_10'] ?? 0;
        $pos_11_30 = $historical_rank[0]['metrics']['pos_11_30'] ?? 0;
        $pos_31_50 = $historical_rank[0]['metrics']['pos_31_50'] ?? 0;
        $pos_51_100 = $historical_rank[0]['metrics']['pos_51_100'] ?? 0;
        $ranked_terms = intval($pos_1_3) + intval($pos_4_10) + intval($pos_11_30) + intval($pos_31_50) + intval($pos_51_100);

        //$backlinks = 75;
        $monthly = !isset($SEOAIC_OPTIONS['dashboard_seo_data_monthly']) ? [] : $SEOAIC_OPTIONS['dashboard_seo_data_monthly'];
        $backlinks_changed = isset($monthly[0]['backlinks']) && $backlinks == $monthly[0]['backlinks'];
        $etv_changed = isset($monthly[0]['etv']) && $etv == $monthly[0]['etv'];
        $paid_traffic_cost_changed = isset($monthly[0]['paid_traffic_cost']) && $paid_traffic_cost == $monthly[0]['paid_traffic_cost'];
        $ranked_pages_changed = isset($monthly[0]['page_rank']) && $ranked_terms == $monthly[0]['page_rank'];

        if (!$backlinks_changed || !$etv_changed || !$paid_traffic_cost_changed || !$ranked_pages_changed) {
            $SEOAIC_OPTIONS['dashboard_seo_data_monthly'][0]['backlinks'] = $backlinks;
            $SEOAIC_OPTIONS['dashboard_seo_data_monthly'][0]['etv'] = $etv;
            $SEOAIC_OPTIONS['dashboard_seo_data_monthly'][0]['paid_traffic_cost'] = $paid_traffic_cost;
            $SEOAIC_OPTIONS['dashboard_seo_data_monthly'][0]['page_rank'] = $ranked_terms;
            update_option('seoaic_options', $SEOAIC_OPTIONS);
        }

        if (
            $next_update_time && $next_update_time < time()
            || empty($SEOAIC_OPTIONS['on_page_seo_data'])
            || empty($monthly)) {

            $last_update = strtotime('-1 months');
            $next_update = strtotime('+1 months');

            if ($backlinks || $etv || $paid_traffic_cost || $ranked_terms) {

                $SEOAIC_OPTIONS['on_page_seo_data'] = [
                    'time_updated' => time(),
                    'time_next_update' => time() + $refresh_interval,
                    'interval' => $refresh_interval,
                ];

                $updateMonthly = [
                    'last_updated' => $last_update,
                    'next_update' => $next_update,
                    'str_to_time' => time(),
                    'etv' => $etv,
                    'paid_traffic_cost' => $paid_traffic_cost,
                    'page_rank' => $ranked_terms,
                    'backlinks' => round($backlinks),
                ];

                if (empty($SEOAIC_OPTIONS['dashboard_seo_data_monthly'])) {
                    for ($i = 0; $i < 3; $i++) {
                        array_unshift($monthly, $updateMonthly);
                        $SEOAIC_OPTIONS['dashboard_seo_data_monthly'] = array_slice($monthly, 0, 3);
                    }
                }

                if (empty($monthly[0]['next_update']) || $monthly[0]['next_update'] > time()) {
                    array_unshift($monthly, $updateMonthly);
                    $SEOAIC_OPTIONS['dashboard_seo_data_monthly'] = array_slice($monthly, 0, 3);
                }

                update_option('seoaic_options', $SEOAIC_OPTIONS);
            }

            wp_send_json([
                'next' => $next_update_time,
                'current' => time(),
                'monthly' => $SEOAIC_OPTIONS['dashboard_seo_data_monthly'],
                'backlinks' => $monthly[0]['backlinks'],
                'backlinks_current' => $backlinks
            ]);
        }

        $data_update = [
            'status' => __('Not yet ready to update', 'seoaic'),
        ];
        if (!$backlinks_changed || !$etv_changed || !$paid_traffic_cost_changed || !$ranked_pages_changed) {
            $data_update['html'] = $this->dashboard_HTML();
        }

        wp_send_json($data_update);

    }

    public function get_historical_positions_our_website($last = false)
    {

        global $SEOAIC;

        $our_website_ID = $SEOAIC->competitors->our_own_website_index();
        $history = get_term_meta( $our_website_ID, 'historical_rank', true) ?? [];

        if ($last) {
            return !isset($history[0]["metrics"]) ? [] : $history[0]["metrics"];
        }

        return $history;

    }

    public function store_month_data_HTML()
    {

        global $SEOAIC_OPTIONS;

        $monthly = !empty($SEOAIC_OPTIONS['dashboard_seo_data_monthly']) ? $SEOAIC_OPTIONS['dashboard_seo_data_monthly'] : [];

        $current = date("F", strtotime('-1 months'));
        $prev = date("F", strtotime('-2 months'));
        $prev_prev = date("F", strtotime('-3 months'));

        $etv = !empty($monthly[0]['etv']) ? $monthly[0]['etv'] : 0;
        $paid_traffic_cost = !empty($monthly[0]['paid_traffic_cost']) ? $monthly[0]['paid_traffic_cost'] : 0;
        $page_rank = !empty($monthly[0]['page_rank']) ? $monthly[0]['page_rank'] : 0;
        $Backlinks = !empty($monthly[0]['backlinks']) ? $monthly[0]['backlinks'] : 0;

        $etv_prev = !empty($monthly[1]['etv']) ? $monthly[1]['etv'] : 0;
        $etv_prev = $etv - $etv_prev;
        $etv_prev = $etv_prev >= 0 ? '+' . $etv_prev : $etv_prev;

        $paid_traffic_cost_prev = !empty($monthly[1]['paid_traffic_cost']) ? $monthly[1]['paid_traffic_cost'] : 0;
        $paid_traffic_cost_prev = $paid_traffic_cost - $paid_traffic_cost_prev;
        $paid_traffic_cost_prev = $paid_traffic_cost_prev >= 0 ? '+' . $paid_traffic_cost_prev : $paid_traffic_cost_prev;

        $page_rank_prev = !empty($monthly[1]['page_rank']) ? $monthly[1]['page_rank'] : 0;
        $page_rank_prev = $page_rank - $page_rank_prev;
        $page_rank_prev = $page_rank_prev >= 0 ? '+' . $page_rank_prev : $page_rank_prev;

        $Backlinks_prev = !empty($monthly[1]['backlinks']) ? $monthly[1]['backlinks'] : 0;
        $Backlinks_prev = $Backlinks - $Backlinks_prev;
        $Backlinks_prev = $Backlinks_prev >= 0 ? '+' . $Backlinks_prev : $Backlinks_prev;

        $etv_prev_prev = !empty($monthly[2]['etv']) ? $monthly[2]['etv'] : 0;
        $etv_prev_prev = $etv - $etv_prev_prev;
        $etv_prev_prev = $etv_prev_prev >= 0 ? '+' . $etv_prev_prev : $etv_prev_prev;

        $paid_traffic_cost_prev_prev = !empty($monthly[2]['paid_traffic_cost']) ? $monthly[2]['paid_traffic_cost'] : 0;
        $paid_traffic_cost_prev_prev = $paid_traffic_cost - $paid_traffic_cost_prev_prev;
        $paid_traffic_cost_prev_prev = $paid_traffic_cost_prev_prev >= 0 ? '+' . $paid_traffic_cost_prev_prev : $paid_traffic_cost_prev_prev;

        $page_rank_prev_prev = !empty($monthly[2]['page_rank']) ? $monthly[2]['page_rank'] : 0;
        $page_rank_prev_prev = $page_rank - $page_rank_prev_prev;
        $page_rank_prev_prev = $page_rank_prev_prev >= 0 ? '+' . $page_rank_prev_prev : $page_rank_prev_prev;

        $Backlinks_prev_prev = !empty($monthly[2]['backlinks']) ? $monthly[2]['backlinks'] : 0;
        $Backlinks_prev_prev = $Backlinks - $Backlinks_prev_prev;
        $Backlinks_prev_prev = $Backlinks_prev_prev >= 0 ? '+' . $Backlinks_prev_prev : $Backlinks_prev_prev;

        return '
        <div class="col month-stats">
            <div class="inner">
                <div class="top-table">
                    <div>' . $current . '</div>
                    <div>' . $prev . '</div>
                    <div>' . $prev_prev . '</div>
                </div>
                <div class="table-line">
                    <div class="label">' . __('Estimated traffic volume', 'seoaic') . '</div>
                    <div class="values">
                        <div>' . number_format($etv) . '</div>
                        <div>' . $etv_prev . '</div>
                        <div>' . $etv_prev_prev . '</div>
                    </div>
                </div>
                <div class="table-line">
                    <div class="label">' . __('Estimated traffic worth', 'seoaic') . '</div>
                    <div class="values">
                        <div>' . esc_html('$ ') . number_format($paid_traffic_cost, 2, ',', ' ') . '</div>
                        <div>' . $paid_traffic_cost_prev . '</div>
                        <div>' . $paid_traffic_cost_prev_prev . '</div>
                    </div>
                </div>
                <div class="table-line">
                    <div class="label">' . __('Total keywords ranked', 'seoaic') . '</div>
                    <div class="values">
                        <div>' . number_format($page_rank) . '</div>
                        <div>' . $page_rank_prev . '</div>
                        <div>' . $page_rank_prev_prev . '</div>
                    </div>
                </div>
                <div class="table-line ">
                    <div class="label">' . __('Backlink count', 'seoaic') . '</div>
                    <div class="values last">
                        <div>' . number_format($Backlinks) . '</div>
                        <div>' . $Backlinks_prev . '</div>
                        <div>' . $Backlinks_prev_prev . '</div>
                    </div>
                </div>
            </div>
        </div>';
    }

    public function dashboard_HTML()
    {
        global $SEOAIC_OPTIONS, $SEOAIC;

        $keywords_nm = $SEOAIC->keywords->getKeywords();
        $keywords_nm = !empty($keywords_nm) ? count($keywords_nm) : 0;
        $competitors = $SEOAIC->competitors->get_competitors();
        $comp_number = !empty($competitors) && $competitors > 0 ? count($competitors) - 1 : 0;

        $our_website_ID = $SEOAIC->competitors->our_own_website_index();
        $search_terms = get_term_meta( $our_website_ID, 'search_terms', true) ?? [];
        $ranked_pages = is_array($search_terms) ? reset($search_terms) : [];
        $ranked_pages = !empty($ranked_pages['positions']['count']) ? $ranked_pages['positions']['count'] : 0;

        $terms_html = '';
        $pages_html = '';
        $positions = $SEOAIC->competitors->get_search_terms_field_val($our_website_ID, 'position');
        //array_multisort($positions, SORT_ASC, $search_terms);
        foreach((array)$search_terms as $term) {
            if($term) {
                $search_vol = !empty($term['search_volume']) ? $term['search_volume'] : '-';
                $meta_desk = !empty($term['meta_description']) ? $term['meta_description'] : '-';
                $position = !empty($term['position']) ? $term['position'] : '-';
                $page = !empty($term['page']) ? $term['page'] : '-';
                if($term['position'] <= 20) {
                    $terms_html .=
                        '<div class="row-line">
                   <div class="keyword">
                        <span>' . $term['keyword'] . '</span>
                    </div>
                    <div class="rank">
                        <a href="' . $page . '" target="_blank">' . $position . '</a>
                    </div>
                   <div class="search-vol">' . $search_vol . '</div>
                </div>';
                    $pages_html .=
                        '<div class="row-line">
                    <div class="page">
                        <a href="' . $page . '" target="_blank">' . $page . '</a>
                        <span>' . $meta_desk . '</span>
                    </div>
                    <div class="rank">
                        <a href="' . $page . '" target="_blank">' . $position . '</a>
                    </div>
                </div>';
                }
            }
        }

        $historical_position = $this->get_historical_positions_our_website();
        $historical_position = $historical_position ? $historical_position : [];
        $created_posts_count =
            [
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => 'seoaic_posted',
                        'value' => '1',
                    ]
                ]
            ];

        $positions = '';
        $ranks = $this->get_historical_positions_our_website(true);
        $pos_1_3 = !isset($ranks['pos_1_3']) ? '-' : $ranks['pos_1_3'];
        $pos_4_10 = !isset($ranks['pos_4_10']) ? '-' : $ranks['pos_4_10'];
        $pos_11_30 = !isset($ranks['pos_11_30']) ? '-' : $ranks['pos_11_30'];
        $pos_31_50 = !isset($ranks['pos_31_50']) ? '-' : $ranks['pos_31_50'];
        $pos_51_100 = !isset($ranks['pos_51_100']) ? '-' : $ranks['pos_51_100'];

        $tabs =
            '<div class="menu-section-seoai seoai-px-0 seoai-mb-2-pr small-size seoai-graphs-tabs">
                <ul class="seoai-flex-start link-spaces">
                    <li>
                        <a class="tab dashboad-tab positions checked" data-chart-id="#chart_competitors_positions" data-chart-type="area" data-charts="' . esc_attr(json_encode($historical_position)) . '" href="#">Ranking Positions</a>
                    </li>
                    <li>
                        <a class="tab dashboad-tab traffic" data-chart-id="#chart_competitors_positions" data-chart-type="area" data-charts="' . esc_attr(json_encode($historical_position)) . '" href="#">Traffic Volume</a>
                    </li>
                </ul>
            </div>';

        $traffic_graph = !empty($SEOAIC_OPTIONS['seoaic_competitors_traffic_graph']) ? $tabs : '<div class="seoai-mb-2-pr fw-700 fs-16 ml-10">' . esc_html('Positions') . '</div>';


        $positions .= !empty($ranks) ? '
                <div class="table-line-float">
                    <div class="key">' . __('Position', 'seoaic') . ' 1-3:</div>
                    <div>' . $pos_1_3 . '</div>
                </div>
                <div class="table-line-float">
                    <div class="key">' . __('Position', 'seoaic') . ' 4-10:</div>
                    <div>' . $pos_4_10 . '</div>
                </div>
                <div class="table-line-float">
                    <div class="key">' . __('Position', 'seoaic') . ' 11-30:</div>
                    <div>' . $pos_11_30 . '</div>
                </div>
                <div class="table-line-float">
                    <div class="key">' . __('Position', 'seoaic') . ' 31-50:</div>
                    <div>' . $pos_31_50 . '</div>
                </div>
                <div class="table-line-float">
                    <div class="key">' . __('Position', 'seoaic') . ' 51-100:</div>
                    <div>' . $pos_51_100 . '</div>
                </div>
        ' : '';

        $positions = $positions
            ? $positions . '<a 
            class="view modal-button" 
            href="#" 
            data-modal="#ranking-modal"
            data-chart-type="area"
            data-chart-id="#chart_competitors_positions"
            data-charts="' . esc_attr(json_encode($historical_position)) . '"
            >' . __('view ranking history', 'seoaic') . '</a>'
            : '<span class="no-data">' . __('Currently no data', 'seoaic') . '</span>';

        $html = '
        <div class="col dashboard-ranking-graph tabs-wrapper">
            <div class="inner">
                <div
                class="dashboard-graph-data"
                data-chart-type="area"
                data-chart-id="#dashboard_chart_positions"
                ></div>
                ' . $traffic_graph . '
                <div 
                id="dashboard_chart_positions" 
                class="dashboard-ranking-graph-build"
                data-chart-type="area" 
                data-charts="' . esc_attr(json_encode($historical_position)) . '"
                ></div>
            </div>
        </div>';

        $html .= $this->store_month_data_HTML();

        $html .= '
        <div class="col ranking-position">
            <div class="inner">
                <div class="table-title">' . __('Ranking Position', 'seoaic') . '</div>
                ' . $positions . '
            </div>
        </div>';

        $html .= '
        <div class="col number-value">
            <div class="inner">
                <div class="value">' . $keywords_nm . '</div>
                <div class="title">' . __('Keywords generated', 'seoaic') . '</div>
            </div>
        </div>
        
        <div class="col number-value">
            <div class="inner">
                <div class="value">' . count(get_posts($created_posts_count)) . '</div>
                <div class="title">' . __('Content generated', 'seoaic') . '</div>
            </div>
        </div>
        
        <div class="col number-value">
            <div class="inner">
                <div class="value">' . $comp_number . '</div>
                <div class="title">' . __('Competitors tracked', 'seoaic') . '</div>
            </div>
        </div>

        <div class="col number-value">
            <div class="inner">
                <div class="value">' . $ranked_pages . '</div>
                <div class="title">' . __('Ranked pages', 'seoaic') . '</div>
            </div>
        </div>';

        $html .= '
        <div class="col table-list two-columns">
            <div class="inner">
                <div class="title">' . __('Top keywords', 'seoaic') . '</div>
                <div class="table">
                    <div class="flex-table top-keywords-dashboard">
                        <div class="row-line heading">
                            <div class="keyword">' . __('Keyword', 'seoaic') . '</div>
                            <div class="rank">' . __('Rank', 'seoaic') . '</div>
                            <div class="search-vol">' . __('Search Volume', 'seoaic') . '</div>
                        </div>
                    </div>
                    <div class="flex-table top-keywords-dashboard">
                        ' . $terms_html . '
                    </div>
                </div>
            </div>
        </div>';

        $html .=
            '<div class="col table-list three-columns">
            <div class="inner">
                <div class="title">' . __('Top pages', 'seoaic') . '</div>
                <div class="table">
                    <div class="flex-table top-pages-dashboard">
                        <div class="row-line heading">
                            <div class="page">' . __('Page', 'seoaic') . '</div>
                            <div class="rank">' . __('Rank', 'seoaic') . '</div>
                        </div>
                    </div>
                    <div class="flex-table top-pages-dashboard">
                        ' . $pages_html . '
                    </div>
                </div>
            </div>
        </div>';

        return $html;
    }
}
