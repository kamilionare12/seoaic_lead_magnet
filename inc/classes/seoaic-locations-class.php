<?php

class SEOAIC_LOCATIONS
{

    function __construct()
    {
        add_action('wp_ajax_seoaicGetCountries', [$this, 'seoaicGetCountries']);
        add_action('wp_ajax_seoaicLocationsOptions', [$this, 'seoaicLocationsOptions']);
        add_action('wp_ajax_seoaicAddGroupLocation', [$this, 'seoaicAddGroupLocation']);
        add_action('wp_ajax_seoaicDeleteGroupLocation', [$this, 'seoaicDeleteGroupLocation']);
        add_action('wp_ajax_seoaicRenameGroupLocation', [$this, 'seoaicRenameGroupLocation']);
        add_action('wp_ajax_seoaicGetGroupLocation', [$this, 'seoaicGetGroupLocation']);
        add_action('wp_ajax_seoaicSaveLocationGroup', [$this, 'seoaicSaveLocationGroup']);
    }

    public function seoaicAddGroupLocation()
    {

        global $SEOAIC_OPTIONS;

        $try = $SEOAIC_OPTIONS['location_groups'];
        $id = str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);

        $a = $_REQUEST['item_name'] ?? '';
        $b = explode("\n", $a);
        $c = [];
        foreach ($b as $i => $d) {
            $nm = trim(preg_replace('/[^\p{L}\s\']+/u', '', $d));
            $nm = preg_replace('/\d/', '', $nm);
            if ($nm)
                $c[] = [
                    'id' => $id . $i,
                    'name' => $nm,
                    'locations' => [],
                    'data_html' => '',
                    'mode' => ''
                ];
        }

        if ((array)$try) :
            $SEOAIC_OPTIONS['location_groups'] = array_merge($c, $SEOAIC_OPTIONS['location_groups']);
        else :
            $SEOAIC_OPTIONS['location_groups'] = $c;
        endif;

        update_option('seoaic_options', $SEOAIC_OPTIONS);

        wp_send_json([
            'status' => 'success',
        ]);

    }

    public function seoaicDeleteGroupLocation()
    {

        global $SEOAIC_OPTIONS;

        $try = $SEOAIC_OPTIONS['location_groups'] ?? [];

        $id = $_REQUEST['idea-mass-create'] ?? explode(',', $_REQUEST['item_id']);

        if ($id == ['all']) : $try = []; endif;

        $try = array_filter($try, function ($d) use ($id) {
            foreach ($id as &$c) {
                if ($d['id'] === $c) {
                    return false;
                }
            }
            return true;
        });

        $e = array_values(array_filter($try));

        $SEOAIC_OPTIONS['location_groups'] = $e;

        update_option('seoaic_options', $SEOAIC_OPTIONS);

        wp_send_json([
            'status' => 'success',
        ]);

    }

    public function seoaicRenameGroupLocation()
    {

        global $SEOAIC_OPTIONS;

        $a = $_REQUEST['item_id'];
        $b = $_REQUEST['item_name'];

        foreach ($SEOAIC_OPTIONS['location_groups'] as &$с) {
            if ($с['id'] == (int)$a) {
                $с['name'] = $b;
            }
        }

        update_option('seoaic_options', $SEOAIC_OPTIONS);

        wp_send_json([
            'status' => 'success',
        ]);

    }

    public function seoaicGetGroupLocation()
    {
        global $SEOAIC_OPTIONS;
        $try = $SEOAIC_OPTIONS['location_groups'];

        if (empty($_REQUEST['item_id'])) {
            wp_die();
        }

        $id = (int)$_REQUEST['item_id'];

        $a = $try[array_search($id, array_column($try, 'id'))];

        $locations = '';
        foreach ((array)$a['data_html'] as $s) {
            $locations .= '<div class="item">
                            <input type="text" class="form-input light location-input" name="location-input" value="' . $s . '" required autocomplete="off"/>
                            <a href="#" class="delete delete-location" title="Remove"></a>
                         </div>';
        }

        wp_send_json([
            'status' => 'success',
            'content' => [
                'name' => $a['name'],
                'id' => $a['id'],
                'mode' => $a['mode'] ?? '',
                'locations' => $a['locations'] ?? '',
                //'locations' => $locations,
                //'data_html' => str_replace('\\', '', $a['data_html']),
                'data_html' => strtr ($a['data_html'], array (
                    '\\' => '',
                    'value=\"Afghanistan\"' => 'value="Afghanistan" selected',
                    'value=\"Birine\"' => 'value="Birine" selected',
                    )),
            ]
        ]);
    }

    public function seoaicSaveLocationGroup()
    {
        global $SEOAIC_OPTIONS;

        $a = $_REQUEST['id'];
        $b = $_REQUEST['locations'];
        $data_html = ($_REQUEST['mode'] === 'api') ? $_REQUEST['data_html'] : '';

        foreach ($SEOAIC_OPTIONS['location_groups'] as &$с) {
            if ($с['id'] == (int)$a) {
                $с['locations'] = $b;
                $с['data_html'] = $data_html;
                $с['mode'] = $_REQUEST['mode'];
            }
        }

        update_option('seoaic_options', $SEOAIC_OPTIONS);

        wp_die();
    }

    public static function seoaicSelectLocationGroup()
    {

        global $SEOAIC_OPTIONS;
        $groups = $SEOAIC_OPTIONS['location_groups'] ?? '';

        $a = '';

        if ((array)$groups) {

            $a .= '<div class="linked-label label-location position-relative flex-center-x"><label class="mb-10 mt-20">' . __('Choose location group', 'seoaic') . '</label><a target="_blank" href="' . admin_url('admin.php?page=seoaic-locations') . '" class="mb-10 mt-20 small">Edit groups</a></div>';
            $a .= '<select name="select_location" class="seoaic-form-item form-select mass_service" multiple>';

            foreach ((array)$groups as $s) {
                $num ='';
                $val = [];
                
                if ($s) {
                    $num = $s['locations'] ? ' (' . count($s['locations']) . ')' : '';
                    foreach ((array)$s['locations'] as $loc) {
                        if ($loc)
                            $loc = explode(' ┄ ', $loc);
                        $loc = end($loc);
                        $val[] = $loc;
                    }
                }

                $a .= $num ? '<option value="' . implode(',', $val) . '">' . $s['name'] . $num . '</option>' : '';
            }

            $a .= '</select>';

        }

        return $a;
    }

    public static function seoaicDisplayGroupLocations(): string
    {

        global $SEOAIC_OPTIONS;

        $a = $SEOAIC_OPTIONS['location_groups'] ?? '';

        $b = '';

        if (!$a) {
            return '';
        }
        foreach ((array)$a as $i => $c) {

            $counter = $i + 1;

            $b .= '
            <div id="idea-post-' . $c['id'] . '" class="post">
                            <div class="idea-content" data-post-id="' . $c['id'] . '">
                                <div class="num">
                                    <div class="checkbox-wrapper-mc">
                                        <input id="idea-mass-create-' . $c['id'] . '" type="checkbox" class="idea-mass-create" name="idea-mass-create" value="' . $c['id'] . '">
                                        <label for="idea-mass-create-' . $c['id'] . '" class="check">
                                            <div class="checkbox-wrapper-svg"><svg width="18px" height="18px" viewBox="0 0 18 18">
                                                    <path d="M1,9 L1,3.5 C1,2 2,1 3.5,1 L14.5,1 C16,1 17,2 17,3.5 L17,14.5 C17,16 16,17 14.5,17 L3.5,17 C2,17 1,16 1,14.5 L1,9 Z"></path>
                                                    <polyline points="1 9 7 14 15 4"></polyline>
                                                </svg></div>
                                            <span class="checkbox-wrapper-item-id">' . $counter . '</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="heading">
                                    <div class="title td-idea-title">' . $c['name'] . '</div>
                                </div>

                                <button title="Edit group name" data-post-id="' . $c['id'] . '" type="button"
                                        class="button button-success seoaic-edit-idea-button ml-auto modal-button"
                                        data-modal="#edit-group"
                                        data-mode="edit"
                                        data-form-callback="window_reload"
                                        data-content="' . __('Edit group', 'seoaic') . '"
                                >
                                    <div class="dn edit-form-items">
                                        <input type="hidden" name="item_id" value="' . $c['id'] . '" data-label="Id">
                                        <input type="hidden" name="item_name" value="' . $c['name'] . '" data-label="Name">
                                    </div>
                                </button>
                                <button title="Remove group" type="button"
                                        class="button button-danger seoaic-remove-idea-button modal-button confirm-modal-button"
                                        data-post-id="' . $c['id'] . '"
                                        data-modal="#seoaic-confirm-modal"
                                        data-action="seoaicDeleteGroupLocation"
                                        data-form-callback="window_reload"
                                        data-content="Do you want to remove this group?"
                                ></button>
                            </div>

                            <div class="idea-btn">
                                <button title="Edit group locations" type="button"
                                        class="button button-primary seoaic-button-primary seoaic-get-idea-content-button"
                                        data-post-id="' . $c['id'] . '"
                                        data-action="seoaicGetGroupLocation"
                                        data-callback="getLocationGroup"
                                        data-callback-before="before_get_idea_content"
                                >
                                </button>
                            </div>
                        </div>
            ';
        }

        return $b;
    }

    public static function seoaicGetCountries()
    {
        $country = sanitize_text_field($_REQUEST['country_id'] ?? 0);
        $state = sanitize_text_field($_REQUEST['state_id'] ?? 0);
        $city = sanitize_text_field($_REQUEST['city_id'] ?? 0);

        $endPoint = "/api/geo";

        if ($country && $state) :
            $endPoint = "/api/geo?country_id=" . $country . "&state_id=" . $state;
        elseif ( $country ) :
            $endPoint = "/api/geo?country_id=" . $country;
        endif;

        $url = seoai_get_backend_url($endPoint);

        $response = wp_remote_get($url, array(
            'sslverify' => seoaic_ssl_verifypeer(),
            'headers'   => array(
                'Content-Type' => 'application/json',
                'x-api-key'     => 'your-api-key-123',
            ),
        ));

        if (is_wp_error($response)) {
            wp_send_json([
                'status' => 'error',
                'message' => $response->get_error_message(),
            ]);
        }

        $get = json_decode(wp_remote_retrieve_body($response), true);

        $a = '';

        if ($get) {
            foreach ($get as $b) {
                $a .= '<option data-id="' . esc_attr($b['id']) . '" value="' . esc_attr($b['title']) . '">' . esc_html($b['name']) . ($b['name'] !== $b['title'] ? ' (' . esc_html($b['title']) . ')' : '') . '</option>';
            }
        }

        wp_send_json([
            'status' => 'success',
            'content' => [
                'content' => $a,
            ]
        ]);
    }
}