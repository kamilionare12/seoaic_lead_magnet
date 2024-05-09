<?php

namespace SEOAIC;

use SEOAIC\DB\KeywordsPostsTable;
use SEOAIC\loaders\PostsEditLoader;
use SEOAIC\loaders\PostsGenerationLoader;
use SEOAIC\loaders\PostsReviewLoader;

class SEOAIC
{
    public $curl;
    public $auth;
    public $scanner;
    public $ideas;
    public $frames;
    public $posts;
    public $keywords;
    public $improve;
    public $locations;
    public $multilang;
    public $wizard;
    public $rank;
    public $ajax_validation;
    public $dashboard;
    public $competitors;
    public $audit_data;
    public $knowledge_base;
    public $settings;


    /**
     * Init main plugin functionality, actions and filters
     */
    function __construct()
    {
        global $SEOAIC_OPTIONS;

        $SEOAIC_OPTIONS = get_option('seoaic_options');
        if (empty($SEOAIC_OPTIONS['seoaic_post_type'])) {
            $SEOAIC_OPTIONS['seoaic_post_type'] = 'post';
        }

        register_activation_hook( SEOAIC_DIR . 'seoai-client.php', [$this, 'on_activation'] );
        register_deactivation_hook( SEOAIC_DIR . 'seoai-client.php', [$this, 'on_deactivation'] );

        add_action('admin_init', [$this, 'seoaic_admin_init']);
        add_action('admin_enqueue_scripts', [$this, 'seoaic_admin_enqueue_scripts']);
        //add_action('enqueue_block_assets', [$this, 'seoaic_gutenberg_enqueue_scripts']);
        add_action('wp_ajax_seoaic_send_upgrade_plan', [$this, 'seoaic_send_upgrade_plan']);
        add_action('wp_ajax_seoaic_settings', [$this, 'seoaic_settings']);
        add_action('wp_ajax_seoaic_setinfo', [$this, 'seoaic_setinfo']);
        add_action('wp_ajax_seoaic_update_company_credits', [$this, 'seoaic_update_company_credits']);
        add_action('wp_ajax_nopriv_seoaic_update_company_credits', [$this, 'seoaic_update_company_credits']);
        add_action('wp_ajax_seoaic_settings_get_post_type_templates', [$this, 'seoaicSettingsGetPostTypeTemplates']);
        add_action('admin_footer', [$this, 'popups'], 10);
        add_action('admin_notices', [$this, 'seoaic_admin_notice']);

        require_once( SEOAIC_DIR . 'inc/classes/seoaic-curl-class.php' );
        require_once( SEOAIC_DIR . 'inc/classes/seoaic-auth-class.php' );
        require_once( SEOAIC_DIR . 'inc/classes/seoaic-scanner-class.php' );
        //require_once( SEOAIC_DIR . 'inc/classes/seoaic-ideas-class.php' );
        require_once( SEOAIC_DIR . 'inc/classes/seoaic-frames-class.php' );
        require_once( SEOAIC_DIR . 'inc/classes/seoaic-rank-tracker-class.php' );
        require_once( SEOAIC_DIR . 'inc/classes/seoaic-locations-class.php' );
        require_once( SEOAIC_DIR . 'inc/classes/seoaic-improve-class.php' );
        require_once( SEOAIC_DIR . 'inc/classes/seoaic-multilang-class.php' );
        require_once( SEOAIC_DIR . 'inc/classes/seoaic-ajax-validation.php' );
        require_once( SEOAIC_DIR . 'inc/classes/seoaic-audit-data-class.php' );

        $this->curl = new SEOAIC_CURL($this);
        $this->auth = new \SEOAIC_AUTH($this);
        $this->scanner = new \SEOAIC_SCANNER($this);
        $this->ideas = new SEOAIC_IDEAS($this);
        $this->frames = new \SEOAIC_FRAMES($this);
        $this->posts = new SEOAIC_POSTS($this);
        $this->keywords = new SEOAIC_KEYWORDS($this);
        $this->locations = new \SEOAIC_LOCATIONS($this);
        $this->improve = new \SEOAIC_IMPROVE($this);
        $this->rank = new \SEOAIC_RANK($this);
        $this->multilang = new \SEOAIC_MULTILANG($this);
        $this->ajax_validation = new \SeoaicAjaxValidation();
        $this->dashboard = new SEOAIC_DASHBOARD($this);
        $this->wizard = new Wizard($this);
        $this->competitors = new SEOAIC_COMPETITORS($this);
        $this->audit_data = new \SeoaicAuditData($this);
        $this->knowledge_base = new SeoaicKnowledgeBase($this);
        $this->settings = new SEOAIC_SETTINGS($this);
    }

    public function on_activation() {
        $seoaicBasedir = wp_upload_dir()['basedir'] . '/seoaic';

        if (!is_dir($seoaicBasedir)) {
            mkdir($seoaicBasedir);
        }

        if (!is_dir($seoaicBasedir . '/logs')) {
            mkdir($seoaicBasedir . '/logs');
        }

        KeywordsPostsTable::createIfNotExists();
    }

    public function on_deactivation()
    {
        $this->posts->unregisterCrons();
        delete_transient('seoaic_seo_audit_data');
    }

    public function set_option ($key, $value) {
        global $SEOAIC_OPTIONS;
        $SEOAIC_OPTIONS[$key] = $value;
        update_option('seoaic_options', $SEOAIC_OPTIONS);
    }

    public function seoaic_admin_init()
    {
        if ( !empty($_GET['page']) && $_GET['page'] === 'seoaic-settings' ) {
            global $SEOAIC_OPTIONS;

            $data = [
                'step'  => 'credits',
                'site_url'  => get_home_url()
            ];

//            $result = $this->curl->init('api/ai/scanning', $data, true, false, true);
//
//            if ( !empty($result['status']) && $result['status'] === 'success' ) {
//                $this->set_api_credits($result['credits']);
//            }

        }

        if (
                isset($_GET['page']) && $_GET['page'] === 'seoaic-ideas'  ||
                isset($_GET['page']) && $_GET['page'] === 'seoaic-competitors'
        ) {
            wp_enqueue_media();
        }

    }


    /**
     * Ajax action - save settings
     */
    public function seoaic_settings()
    {
        global $SEOAIC_OPTIONS;

        foreach ($_POST as $key => $value) {
            if ('seoaic_visible_posts' == $key) {
                $old_value = !empty($SEOAIC_OPTIONS[$key]) ? $SEOAIC_OPTIONS[$key] : [];
                $this->update_seoaic_posts_visibility_meta($old_value, $value);
            }

            switch ($key) {
                case 'seoaic_company_website':
                case 'seoaic_business_name':
                case 'seoaic_industry':
                case 'seoaic_business_description':
                case 'seoaic_content_guidelines':
                case 'seoaic_language':
                case 'seoaic_location':
                case 'seoaic_keywords':
                case 'seoaic_keywords_stat':
                case 'seoaic_writing_style':
                case 'seoaic_default_category':
                case 'seoaic_image_generator':
                case 'seoaic_image_style':
                case 'seoaic_image_colors':
                case 'seoaic_image_colors_accent':
                case 'seoaic_image_colors_additional':
                case 'seoaic_schedule_days':
                case 'seoaic_publish_delay':
                case 'seoaic_generate_internal_links':
                case 'seoaic_hide_posts':
                case 'seoaic_visible_posts':
                case 'seoaic_show_related_articles':
                case 'seoaic_related_articles_count':
                case 'seoaic_phone':
                case 'seoaic_words_range_min':
                case 'seoaic_words_range_max':
                case 'seoaic_subtitles_range_min':
                case 'seoaic_subtitles_range_max':
                case 'seoaic_services':
                case 'seoaic_pillar_links':
                case 'seoaic_locations':
                case 'seoaic_post_type':
                case 'seoaic_post_template':
                case 'seoaic-exclude-taxonomy':
                case 'seoaic_multilanguages':
                case 'seoaic_posts_mass_generate_prompt_templates':
                    if (is_array($value)) {
                        $SEOAIC_OPTIONS[$key] = [];
                        foreach ($value as $item) {
                            $item = map_deep($item, 'sanitize_text_field' );

                            if ($key === 'seoaic_schedule_days') {
                                $SEOAIC_OPTIONS[$key][$item] = [
                                    'posts' => intval($_POST['seoaic_schedule_' . $item . '_posts']),
                                    'time' => sanitize_text_field($_POST['seoaic_schedule_' . $item . '_time']),
                                ];
                            } elseif ($key === 'seoaic_services') {
                                $c = [];

                                foreach ($value as $a) {
                                    if ($a['name']) {
                                        $c[] = [
                                            'name' => stripslashes($a['name']),
                                            'text' => stripslashes($a['text']),
                                        ];
                                    } else {
                                        $c[] = [];
                                    }
                                }

                                $SEOAIC_OPTIONS[$key] = $c;

                            } elseif ('seoaic_visible_posts' == $key && count($value) > 1) {
                                if (!empty(trim($item))) {
                                    $SEOAIC_OPTIONS[$key][] = $item;
                                }

                            } elseif ($key === 'seoaic_pillar_links') {
                                $c = [];

                                foreach ($value as $a) {

                                    if (!empty($a['url'] && !empty($a['text']))) {
                                        $c[] = [
                                            'lang' => sanitize_text_field($a['lang']),
                                            'name' => sanitize_text_field($a['name']),
                                            'url' => sanitize_text_field($a['url']),
                                            'text' => sanitize_text_field($a['text']),
                                        ];
                                    }
                                }

                                $SEOAIC_OPTIONS[$key] = $c;

                            } else {
                                $SEOAIC_OPTIONS[$key][] = $item;
                            }
                        }
                    } else {
                        $SEOAIC_OPTIONS[$key] = stripslashes(sanitize_textarea_field($value));
                    }
                    break;
            }
        }

        if (isset($_REQUEST['seoaic-exclude-taxonomy'])) {
            $SEOAIC_OPTIONS['seoaic-exclude-taxonomy'] = $_REQUEST['seoaic-exclude-taxonomy'];
        } else {
            $SEOAIC_OPTIONS['seoaic-exclude-taxonomy'] = [];
        }

        if (isset($_REQUEST['seoaic_default_category'])) {
            $SEOAIC_OPTIONS['seoaic_default_category'] = $_REQUEST['seoaic_default_category'];
        } else {
            $SEOAIC_OPTIONS['seoaic_default_category'] = [];
        }

        if ( !isset($_POST['seoaic_multilanguages']) ) {
            unset($SEOAIC_OPTIONS['seoaic_multilanguages']);
        }

        if ( !isset($_POST['seoaic_schedule_days']) ) {
            // unset($SEOAIC_OPTIONS['seoaic_schedule_days']);
            $SEOAIC_OPTIONS['seoaic_schedule_days'] = [];
        }

        if (!isset($_POST['seoaic_posts_mass_generate_prompt_templates'])) {
            $SEOAIC_OPTIONS['seoaic_posts_mass_generate_prompt_templates'] = [];
        }

        if (current_user_can('manage_options')) {
            if (isset($_REQUEST['seoaic_access_role'])) {
                $SEOAIC_OPTIONS['seoaic_access_role'] = $_REQUEST['seoaic_access_role'];
            } else {
                $SEOAIC_OPTIONS['seoaic_access_role'] = [];
            }
        }

        $SEOAIC_OPTIONS['seoaic_ssl_verifypeer'] = !empty($_REQUEST['seoaic_ssl_verifypeer']);
        $SEOAIC_OPTIONS['seoaic_competitors_traffic_graph'] = !empty($_REQUEST['seoaic_competitors_traffic_graph']);
        $SEOAIC_OPTIONS['seoaic_pillar_link_action'] = !empty($_REQUEST['seoaic_pillar_link_action']);

        unset($SEOAIC_OPTIONS['seoaic_server']);

        update_option('seoaic_options', $SEOAIC_OPTIONS);

        $this->competitors->change_our_own_url();
        $this->rank->combine_all_ranks();

        wp_send_json([
                'status' => true
        ]);

        wp_die();
    }

    private function update_seoaic_posts_visibility_meta($old_value=[], $value=[]) {
        $meta_key = 'seoaic_visible_post';
        $filter_empty = function($item) {
            return !empty(trim($item));
        };
        $old_value = array_filter($old_value, $filter_empty);
        $value = array_values(array_filter($value, $filter_empty));

        $remove_values = array_diff($old_value, $value);
        $create_values = array_diff($value, $old_value);

        if (!empty($remove_values)) {
            foreach ($remove_values as $old_id) {
                // Updating to an empty value instead of delete
                // to reduce the DB table ID counter usage
                // in case of frequent changes.
                update_post_meta($old_id, $meta_key, '');
            }
        }

        if (!empty($create_values)) {
            foreach ($create_values as $id) {
                update_post_meta($id, $meta_key, 1);
            }
        }
    }

    public function seoaic_setinfo () {
        global $SEOAIC_OPTIONS;

        foreach ($_POST as $key => $value) {
            switch ($key) {
                case 'seoaic_business_name':
                case 'seoaic_location':
                case 'seoaic_phone':
                    $SEOAIC_OPTIONS[$key] = sanitize_text_field($value);
                    break;
            }
        }
        $SEOAIC_OPTIONS['seoaic_language'] = seoaic_get_preferred_language($SEOAIC_OPTIONS['seoaic_location']);

        update_option('seoaic_options', $SEOAIC_OPTIONS);

        wp_send_json([
            'status'  => 'success',
        ]);
    }

    /**
     * Enqueue necessary admin styles and scripts
     */
    public function seoaic_admin_enqueue_scripts()
    {
        $current_version = get_plugin_data(SEOAIC_FILE)['Version'];

        wp_enqueue_style('seoaic_admin_global_css', SEOAIC_URL . 'assets/css/seoaic-admin-global.css', array(), $current_version);

        $pos = strpos(get_current_screen()->base, 'seoaic');
        if (!($pos || $pos === 0)) {
            return;
        }

        wp_enqueue_style('seoaic_admin_css', SEOAIC_URL . 'assets/css/seoaic-admin.css', array(), $current_version);
        wp_enqueue_style('seoaic_admin_fonts_css', SEOAIC_URL . 'assets/fonts/fonts.css');
        wp_enqueue_style('seoaic_admin_main_css', SEOAIC_URL . 'assets/css/main.min.css', array(), $current_version);

        wp_register_script('seoaic_admin_main_js', SEOAIC_URL . 'assets/js/main.min.js', array(), $current_version, true);
        wp_enqueue_script('seoaic_admin_main_js', SEOAIC_URL . 'assets/js/main.min.js');
        wp_localize_script('seoaic_admin_main_js', 'adminPage', array(
            'adminUrl' => SEOAIC::getAdminUrl('admin.php')
        ));
    }

    public function seoaic_gutenberg_enqueue_scripts()
    {
        if (!is_admin()) {
            return;
        }
        $current_version = get_plugin_data(SEOAIC_FILE)['Version'];
        wp_enqueue_style('seoaic_gutenberg_css', SEOAIC_URL . 'assets/css/gutenberg.min.css', array(), $current_version);
        wp_register_script('seoaic_gutenberg_js', SEOAIC_URL . 'assets/js/gutenberg.min.js', array( 'wp-element', 'wp-components', 'wp-plugins', 'wp-data' ), $current_version, true);
        wp_enqueue_script('seoaic_gutenberg_js', SEOAIC_URL . 'assets/js/gutenberg.min.js');
    }

    public function seoaic_update_company_credits ()
    {
        global $SEOAIC_OPTIONS;

        if (!$this->auth->check_api_token($_REQUEST['email'], $_REQUEST['token'])) {
            echo 'error';
            wp_die();
        }

        if ( !empty($_REQUEST['credits']) ) {
            $credits = json_decode(stripslashes($_REQUEST['credits']), true);
            $this->set_api_credits($credits);
        }
    }

    public function seoaic_send_upgrade_plan()
    {
        if (!current_user_can('seoaic_edit_plugin')) {
            wp_die();
        }

        if (empty($_REQUEST['email'])) {
            SEOAICAjaxResponse::alert('Email can`t be empty!')->wpSend();
        }

        $postsNum = intval($_REQUEST['postsNum']);
        $ideasNum = intval($_REQUEST['ideasNum']);
        $email = $_REQUEST['email'];

        $data = [
            'email' => $email,
            'posts' => $postsNum,
            'ideas' => $ideasNum,
        ];

        $result = $this->curl->init('api/companies/upgrade-credits-request', $data, true, true, true);

        if (!empty($result)) {
            SEOAICAjaxResponse::alert('Request sent')->wpSend();
        }
    }

    public function popups()
    {
        $pos = strpos(get_current_screen()->base, 'seoaic');
        if (!($pos || $pos === 0)) {
            return;
        }
        include_once(SEOAIC_DIR . 'inc/view/popups/confirm-modal.php');
        include_once(SEOAIC_DIR . 'inc/view/popups/alert-modal.php');
        include_once(SEOAIC_DIR . 'inc/view/popups/generated-post.php');
        include_once(SEOAIC_DIR . 'inc/view/popups/post-mass-creation.php');
        include_once(SEOAIC_DIR . 'inc/view/popups/schedule-posts.php');
        include_once(SEOAIC_DIR . 'inc/view/popups/disconnect.php');
        include_once(SEOAIC_DIR . 'inc/view/popups/add-idea.php');
        include_once(SEOAIC_DIR . 'inc/view/popups/generate-ideas.php');
        include_once(SEOAIC_DIR . 'inc/view/popups/generate-ideas-new-keywords.php');
        include_once(SEOAIC_DIR . 'inc/view/popups/edit-group.php');
        include_once(SEOAIC_DIR . 'inc/view/popups/edit-idea.php');
        include_once(SEOAIC_DIR . 'inc/view/popups/wizard-post-content.php');
        include_once(SEOAIC_DIR . 'inc/view/popups/wizard-generate-keywords.php');
        include_once(SEOAIC_DIR . 'inc/view/popups/wizard-generate-ideas.php');
        include_once(SEOAIC_DIR . 'inc/view/popups/wizard-generate-posts.php');
        include_once(SEOAIC_DIR . 'inc/view/popups/plan-modal.php');
        include_once(SEOAIC_DIR . 'inc/view/popups/serp-keyword.php');
        include_once(SEOAIC_DIR . 'inc/view/popups/rank-history.php');
        include_once(SEOAIC_DIR . 'inc/view/popups/competitors-compare.php');
        include_once(SEOAIC_DIR . 'inc/view/popups/generate-terms.php');
        include_once(SEOAIC_DIR . 'inc/view/popups/add-competitors.php');
        include_once(SEOAIC_DIR . 'inc/view/popups/competitor-compare.php');
        include_once(SEOAIC_DIR . 'inc/view/popups/add-locations-group.php');
        include_once(SEOAIC_DIR . 'inc/view/popups/search-terms-posts.php');
        include_once(SEOAIC_DIR . 'inc/view/popups/search-terms-update-modal.php');
        // include_once(SEOAIC_DIR . 'inc/view/popups/search-terms-posts.php'); // duplicate
        include_once(SEOAIC_DIR . 'inc/view/popups/ranking-modal.php');
        include_once(SEOAIC_DIR . 'inc/view/popups/performance-modal.php');
    }

    public function set_api_credits( $credits = [] ) {

        if ( !empty($credits['client_version']) ) {
            update_option('seoaic_client_version', sanitize_text_field($credits['client_version']));
        }
        update_option('seoaic_ideas_credit', intval($credits['ideas_limit']) - intval($credits['ideas_used']));
        update_option('seoaic_frames_credit', intval($credits['frames_limit']) - intval($credits['frames_used']));
        update_option('seoaic_posts_credit', intval($credits['posts_limit']) - intval($credits['posts_used']));
        update_option('seoaic_keywords_credit', intval($credits['keywords_limit']) - intval($credits['keywords_used']));
        update_option('seoaic_keywords_stats_credit', intval($credits['keywords_stats_limit']) - intval($credits['keywords_stats_used']));

    }

    public function get_api_credits() {
        $credits = [
            'ideas' => 0,
            'frames' => 0,
            'posts' => 0,
            'keywords' => 0,
            'keywords_stats' => 0,
        ];
        foreach ($credits as $key => $credit) {
            $credits[$key] = get_option('seoaic_' . $key . '_credit', true);
        }

        return $credits;
    }

    public function seoaic_admin_notice () {
        $current_version = get_plugin_data(SEOAIC_FILE)['Version'];
        $uploaded_version = get_option('seoaic_client_version', true);

        if ( version_compare($current_version, $uploaded_version) < 0 ) {
            echo '<div class="notice notice-warning">SEO AI plugin has new version ' . $uploaded_version . '! Please,
                    <button type="button"
                        class="update-plugin-button seoaic-ajax-button"
                        data-action="seoaic_update_plugin"
                        data-callback="window_reload"
                    >update</button>.
                </div>';
        }
    }

    public static function seoaic_select_service() {

        global $SEOAIC_OPTIONS;
        $services = $SEOAIC_OPTIONS['seoaic_services'] ?? '';

        $a = '';

        if ($services) {

            $a .= '<label class="mb-10 mt-20">' . __( 'Choose service', 'seoaic' ) . '</label>';
            $a .= '<select name="select_service" class="seoaic-form-item form-select mass_service" multiple>';

            $a .= '<option value="">Select a Service</option>';

            foreach ($services as $i=>$s) {
                if (isset($s['name'])) {
                    $a .= '<option value="' . $i . '">' . $s['name'] . '</option>';
                }
            }

            $a .= '</select>';

        }

        return $a;
    }

    public function get_background_process_loader($return = false)
    {
        $postsGenerationLoader = new PostsGenerationLoader();
        $this->makeBackgroundGenerationLoader($return, $postsGenerationLoader);

        $postsEditLoader = new PostsEditLoader();
        $this->makeBackgroundGenerationLoader($return, $postsEditLoader);

        $postsReviewLoader = new PostsReviewLoader();
        $this->makeBackgroundGenerationLoader($return, $postsReviewLoader);
    }

    private function makeBackgroundGenerationLoader($return, $loader)
    {
        $option = $loader::getPostsOption();

        if ($return) {
            ob_start();
        }

        if (
            !empty($option)
            && $option['total']
        ) {
            $total = array_unique($option['total']);
            $width = count($option['done']) / count($total) * 100;
            ?>
            <div id="<?php echo esc_attr($loader->getID());?>"
                class="seoaic-admin-posts-loader <?php echo $width === 100 ? 'seoaic-background-process-finished' : '';?>"
                title="<?php esc_attr_e($loader->getTitle());?>"
            >
                <div class="seoaic-background-process-box" style="background-color:<?php echo esc_attr($loader->getBackgroundColor());?>;">
                    <div class="seoaic-background-process-loader" style="width:<?php echo esc_attr($width);?>%; background-color:<?php echo esc_attr($loader->getFillColor());?>;"></div>
                    <div class="seoaic-background-process-loader seoaic-background-process-loader-bottom" style="width:<?php echo esc_attr($width);?>%; background-color:<?php echo esc_attr($loader->getFillColor());?>;"></div>
                    <div class="seoaic-background-process-content">
                        <div class="seoaic-background-process-content-overflow">
                            <p><b><?php esc_html_e($loader->getTitle());?>:</b></p>
                            <?php foreach ($total as $id) {
                                ?>
                                <p class="seoaic-background-process-p-<?php echo esc_attr($id) . (in_array($id, $option['done']) ? ' seoaic-background-process-generated' : '');?>"><b><?php echo esc_html($id);?></b> - <?php echo get_the_title($id);?></p>
                                <?php
                            }
                            ?>
                        </div>
                        <div class="seoaic-background-process-control">
                            <button type="button" class="seoaic-background-process-closer">Minimize panel</button>
                            <?php
                            if ($width === 100) {
                                ?>
                                <button type="button"
                                        class="seoaic-background-process-close modal-button confirm-modal-button"
                                        data-modal="#seoaic-confirm-modal"
                                        data-action="<?php echo esc_attr($loader->getCloseAction());?>"
                                        data-form-callback="window_reload"
                                        data-content="Do you want to close panel?"
                                >Close panel</button>
                                <?php
                            } else {
                                if ($loader->isCheckManuallyButtonDisplayed) {
                                    ?>
                                    <button type="button"
                                            class="seoaic-check-manually modal-button confirm-modal-button"
                                            data-modal="#seoaic-confirm-modal"
                                            data-action="<?php echo esc_attr($loader->getCheckManualyAction());?>"
                                            data-form-callback="window_reload"
                                            data-content="Do you want to pull results manually?"
                                    >Check results manually</button>
                                    <?php
                                }
                                if ($loader->isStopButtonDisplayed) {
                                    ?>
                                    <button type="button"
                                            class="seoaic-background-process-stop modal-button confirm-modal-button"
                                            data-modal="#seoaic-confirm-modal"
                                            data-action="<?php echo esc_attr($loader->getStopAction());?>"
                                            data-form-callback="window_reload"
                                            data-content="Do you want to stop background process?"
                                    >Stop process</button>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <button type="button" class="seoaic-background-process-opener"></button>
                </div>
            </div>
            <?php
        }

        if ($return) {
            $result = ob_get_contents();
            ob_end_clean();
            return $result;
        }
    }

    // String to slug
    public static function seoaicSlugify($text){
        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, '-');

        // remove duplicated - symbols
        $text = preg_replace('~-+~', '-', $text);

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

    // Upload extermal images to media
    public static function seoaicUploadFile( $url, $img_tile ) {
        include_once( ABSPATH . 'wp-admin/includes/image.php' );
        $image_url = $url;
        $loop = explode('/', getimagesize($image_url)['mime']);
        $imagetype = end($loop);
        $uniq_name = $img_tile ? self::seoaicSlugify($img_tile) . '_' . (int)microtime(true) : date('dmY').''. (int)microtime(true);
        $filename = $uniq_name.'.'.$imagetype;

        $uploaddir = wp_upload_dir();
        $uploadfile = $uploaddir['path'] . '/' . $filename;
        $contents= file_get_contents($image_url);
        $savefile = fopen($uploadfile, 'w');
        fwrite($savefile, $contents);
        fclose($savefile);

        $wp_filetype = wp_check_filetype(basename($filename), null );
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => $img_tile,
            'post_content' => '',
            'post_status' => 'inherit'
        );

        $attach_id = wp_insert_attachment( $attachment, $uploadfile );
        $imagenew = get_post( $attach_id );
        $fullsizepath = get_attached_file( $imagenew->ID );
        $attach_data = wp_generate_attachment_metadata( $attach_id, $fullsizepath );
        wp_update_attachment_metadata( $attach_id, $attach_data );

        return $attach_id;

    }

    public static function getSEOAICData()
    {
        global $SEOAIC_OPTIONS;

        $seoaicData = [
            'name' => !empty($SEOAIC_OPTIONS['seoaic_business_name']) ? $SEOAIC_OPTIONS['seoaic_business_name'] : get_option('blogname', true),
            'industry' => !empty($SEOAIC_OPTIONS['seoaic_industry']) ? " on the industry of " . $SEOAIC_OPTIONS['seoaic_industry'] : '',
            'desc' => !empty($SEOAIC_OPTIONS['seoaic_business_description']) ? $SEOAIC_OPTIONS['seoaic_business_description'] : get_option('blogdescription', true),
            'writing_style' => !empty($SEOAIC_OPTIONS['seoaic_writing_style']) ? $SEOAIC_OPTIONS['seoaic_writing_style'] : '',
            // 'internal_links' => $item_data['internal_links'], // moved to ideasData
            //'pillar_links' => self::getPillarLinks(), // moved to ideasData
            'content_guidelines' => !empty($SEOAIC_OPTIONS['seoaic_content_guidelines']) ? $SEOAIC_OPTIONS['seoaic_content_guidelines'] : '',
        ];

        return $seoaicData;
    }

    public static function seoaicSettingsGetPostTypeTemplates()
    {
        if (!empty($_REQUEST['post_type'])) {
            $postTemplates = get_page_templates(null, $_REQUEST['post_type']);
            SEOAICAjaxResponse::success()->addFields([
                'options' => self::makePostTemplatesOptions($postTemplates)
            ])->wpSend();
        }
    }

    public static function makePostTemplatesOptions($templates=[], $selectedPostTemplate='')
    {
        global $SEOAIC_OPTIONS;

        if (empty($selectedPostTemplate)) {
            $selectedPostTemplate = !empty($SEOAIC_OPTIONS['seoaic_post_template']) ? $SEOAIC_OPTIONS['seoaic_post_template'] : '';
        }

        ob_start();

        if (is_array($templates)) {
            $templates = array_reverse($templates, true);
            $templates['Default template'] = '';
            $templates = array_reverse($templates, true);

            foreach ($templates as $tmpltTitle => $tmpltName) {
                $selected = $selectedPostTemplate === $tmpltName ? ' selected' : '';
                ?>
                <option value="<?php echo esc_attr($tmpltName);?>"<?php echo $selected;?>><?php echo esc_html($tmpltTitle);?></option>
                <?php
            }
        }

        return trim(ob_get_clean());
    }

    public static function getAdminUrl($path='')
    {
        return admin_url($path);
    }
}