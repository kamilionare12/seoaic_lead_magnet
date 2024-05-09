<?php

namespace SEOAIC;

class Wizard
{
    const VIEWS_DIR = SEOAIC_DIR . 'inc/view/wizard/';
    const TEMP_DATA_LIFETIME = 6 * HOUR_IN_SECONDS;
    const FIELD_KEYWORDS = 'wizard_keywords';
    const FIELD_SELECTED_KEYWORDS = 'wizard_selected_keywords';
    const FIELD_GENERATED_IDEAS_IDS = 'wizad_generated_ideas_ids';
    const KEYWORDS_COUNT = 20;
    const IDEAS_COUNT = 5;

    private $sidebar_template_file;
    private $seoaic;
    private $steps;

    function __construct($_seoaic)
    {
        $this->seoaic = $_seoaic;
        $this->sidebar_template_file = self::VIEWS_DIR . 'seoaic-wizard-steps-sidebar-view.php';

        $this->initSteps();
        $this->initSettings();

        // add_action('wp_ajax_seoaic_run_wizard', [$this, 'ajaxRunWizard']);
        add_action('wp_ajax_seoaic_wizard_step_back', [$this, 'ajaxStepBack']);
        add_action('wp_ajax_seoaic_wizard_generate_keywords', [$this, 'ajaxGenerateKeywords']);
        add_action('wp_ajax_seoaic_wizard_reload_entities', [$this, 'ajaxReloadEntities']);
        add_action('wp_ajax_seoaic_wizard_select_keywords', [$this, 'ajaxSelectKeywords']);
        add_action('wp_ajax_seoaic_wizard_generate_ideas', [$this, 'ajaxGenerateIdeas']);
        add_action('wp_ajax_seoaic_wizard_posts_mass_create', [$this, 'ajaxPostsMassCreate']);
        add_action('wp_ajax_seoaic_wizard_reset', [$this, 'ajaxWizardReset']);
    }

    private function initSteps()
    {
        $this->steps = [
            [
                "title"     => "Generate keywords",
                "subtitle"  => ""
            ], [
                "title"     => "Select which ones to track",
                "subtitle"  => ""
            ], [
                "title"     => "Create ideas",
                "subtitle"  => "based on the selected keywords"
            ], [
                "title"     => "Pick title",
                "subtitle"  => "to generate post"
            ], [
                "title"     => "Generate Content",
                "subtitle"  => ""
            ],
        ];
    }

    private function initSettings($reset=false)
    {
        global $SEOAIC_OPTIONS;

        $update = false;

        if (!isset($SEOAIC_OPTIONS['wizard_settings'])) {
            $wizard_settings = [];
            $update = true;
        } else {
            $wizard_settings = $SEOAIC_OPTIONS['wizard_settings'];
        }

        if (
            empty($wizard_settings['display_keywords'])
            || $reset
        ) {
            $wizard_settings['display_keywords'] = 'new';
            $update = true;
        }

        if (
            empty($wizard_settings['display_ideas'])
            || $reset
        ) {
            $wizard_settings['display_ideas'] = 'new';
            $update = true;
        }

        if ($update) {
            $this->seoaic->set_option('wizard_settings', $wizard_settings);
        }

        if ($reset) {
            $this->seoaic->set_option('seoaic_wizard_step', 1);
        }
    }

    public function runWizard()
    {
        global $SEOAIC_OPTIONS;

        $step = !empty($SEOAIC_OPTIONS['seoaic_wizard_step']) ? $SEOAIC_OPTIONS['seoaic_wizard_step'] : 1;

        switch ($step) {
            case 1:
                delete_transient(self::FIELD_KEYWORDS);
                delete_transient(self::FIELD_SELECTED_KEYWORDS);
                delete_transient(self::FIELD_GENERATED_IDEAS_IDS);
                break;
            case 2:
            case 3:
            case 4:
            case 5:
                break;
        }

        $step_content = $this->makeStepsSidebar($step);
        $step_content .= $this->makeStepContent($step);

        echo $step_content;
    }

    private function getStepsCount()
    {
        return count($this->steps);
    }

    private function isValidStep($step)
    {
        if (
            $step >= 1
            && $step > $this->getStepsCount()
        ) {
            return false;
        }

        return true;
    }

    private function makeStepsSidebar($step=1)
    {
        if ($this->isValidStep($step)) {
            foreach ($this->steps as $i => &$s) {
                if ($i < $step - 1) {
                    $s['passed'] = true;
                } elseif ($i == $step - 1) {
                    $s['is_active'] = true;
                } else {
                    $s['passed'] = false;
                }
            }
        }

        ob_start();

        if (file_exists($this->sidebar_template_file)) {
            $steps = $this->steps;
            include_once ($this->sidebar_template_file);
        }

        return ob_get_clean();
    }

    private function makeStepContent($step=1)
    {
        $step_file = self::VIEWS_DIR . 'steps/seoaic-wizard-step-' . $step . '.php';
        if ($this->isValidStep($step)) {
            ob_start();

            if (file_exists($step_file)) {
                include_once ($step_file);
            }

            return ob_get_clean();
        }

        return '';
    }

    // public function ajaxRunWizard() {
    //     $step = !empty($_REQUEST['step']) ? intval($_REQUEST['step']) : 1;

    //     ob_start();
    //     $this->runWizard($step);

    //     $data = [
    //         'step_content'  => ob_get_clean(),
    //     ];

    //     wp_send_json($data);
    // }

    public function ajaxStepBack()
    {
        global $SEOAIC_OPTIONS;

        $step = 0;

        if (
            !empty($SEOAIC_OPTIONS['seoaic_wizard_step'])
            && is_numeric($SEOAIC_OPTIONS['seoaic_wizard_step'])
        ) {
            $step = intval($SEOAIC_OPTIONS['seoaic_wizard_step']) - 1;
        }

        if ($step < 1) {
            $step = 1;
        }

        $this->seoaic->set_option('seoaic_wizard_step', $step);

        SEOAICAjaxResponse::success()->wpSend();
    }

    public function ajaxGenerateKeywords()
    {
        $prompt = !empty($_REQUEST['keywords_prompt']) ? $_REQUEST['keywords_prompt'] : '';
        $new_keywords = $this->seoaic->keywords->generate(self::KEYWORDS_COUNT, $prompt, null, $return_new = true);

        set_transient(self::FIELD_KEYWORDS, $new_keywords, self::TEMP_DATA_LIFETIME);

        $this->seoaic->set_option('seoaic_wizard_step', 2);

        SEOAICAjaxResponse::success()->addFields([
            'keywords' => $new_keywords,
        ])->wpSend();
    }

    public function ajaxReloadEntities()
    {
        global $SEOAIC_OPTIONS;

        $entities = !empty($_REQUEST['entities']) ? $_REQUEST['entities'] : '';
        $type = !empty($_REQUEST['type']) ? $_REQUEST['type'] : '';
        $old_type = '';
        $new_type = '';
        $settings_field = '';
        $wizard_settings = $SEOAIC_OPTIONS['wizard_settings'];
        $update = false;

        if (empty($entities)) {
            SEOAICAjaxResponse::error('No entity set.')->wpSend();
        }

        switch ($entities) {
            case 'keywords':
            case 'ideas':
                $settings_field = 'display_' . $entities;
                break;

            case 'keyword':
            case 'idea':
                $settings_field = 'display_' . $entities . 's';
                break;
        }

        if (empty($settings_field)) {
            SEOAICAjaxResponse::error('Wrong entity.')->wpSend();
        }

        $old_type = $wizard_settings[$settings_field];

        switch ($type) {
            case 'new':
                $new_type = $type;
                break;

            default:
                $new_type = 'all';
                break;
        }

        if ($old_type != $new_type) {
            $wizard_settings[$settings_field] = $new_type;
            $update = true;
        }

        if ($update) {
            $this->seoaic->set_option('wizard_settings', $wizard_settings);
        }

        SEOAICAjaxResponse::success()->wpSend();
    }

    public function ajaxSelectKeywords()
    {
        $keywords = $this->seoaic->keywords->getKeywords();
        $selected_keywords = explode(',', stripslashes(sanitize_text_field($_REQUEST['item_id'])));
        $wizard_selected_keywords = [];

        if (
            !empty($keywords)
            && is_array($keywords)
        ) {
            foreach ($keywords as $k) {
                if (in_array($k['id'], $selected_keywords)) {
                    $wizard_selected_keywords[] = $k;
                }
            }
        }
        set_transient(self::FIELD_SELECTED_KEYWORDS, $wizard_selected_keywords, SELF::TEMP_DATA_LIFETIME);
        $this->seoaic->set_option('seoaic_wizard_step', 3);

        SEOAICAjaxResponse::success()->wpSend();
    }

    // wrapper for original ajax function
    public function ajaxGenerateIdeas()
    {
        $selected_keywords = get_transient(self::FIELD_SELECTED_KEYWORDS);
        $selected_keywords = !empty($selected_keywords) ? $selected_keywords : [];
        $_REQUEST['select-keywords'] = array_map(function($kw) {
            return $kw['id'];
        }, $selected_keywords);

        $this->seoaic->ideas->generateIdeasNewKeywords(true, self::IDEAS_COUNT);
        $ids = $this->seoaic->ideas->getGeneratedIdeasIDs();

        set_transient(self::FIELD_GENERATED_IDEAS_IDS, $ids, self::TEMP_DATA_LIFETIME);
        $this->seoaic->set_option('seoaic_wizard_step', 4);

        SEOAICAjaxResponse::success()->wpSend();
    }

    // wrapper for original ajax function
    public function ajaxPostsMassCreate()
    {
        global $SEOAIC_OPTIONS;

        $this->seoaic->set_option('seoaic_wizard_step', 5);

        if (!empty($SEOAIC_OPTIONS['seoaic_words_range_min'])) {
            $words_min = intval($SEOAIC_OPTIONS['seoaic_words_range_min']);
        } else {
            $words_min = SEOAIC_POSTS::WORDS_MIN;
        }

        if (!empty($SEOAIC_OPTIONS['seoaic_words_range_max'])) {
            $words_max = intval($SEOAIC_OPTIONS['seoaic_words_range_max']);
        } else {
            $words_max = SEOAIC_POSTS::WORDS_MAX;
        }

        if (!empty($SEOAIC_OPTIONS['seoaic_subtitles_range_min'])) {
            $subtitles_min = intval($SEOAIC_OPTIONS['seoaic_subtitles_range_min']);
        } else {
            $subtitles_min = SEOAIC_POSTS::SUBTITLES_MIN;
        }

        if (!empty($SEOAIC_OPTIONS['seoaic_subtitles_range_max'])) {
            $subtitles_max = intval($SEOAIC_OPTIONS['seoaic_subtitles_range_max']);
        } else {
            $subtitles_max = SEOAIC_POSTS::SUBTITLES_MAX;
        }

        $_REQUEST['seoaic_subtitles_min'] = $subtitles_min;
        $_REQUEST['seoaic_subtitles_max'] = $subtitles_max;
        $_REQUEST['seoaic_words_min'] = $words_min;
        $_REQUEST['seoaic_words_max'] = $words_max;
        $_REQUEST['seoaic_post_status'] = 'draft';
        $_REQUEST['seoaic-mass-idea-date'] = date('Y-m-d'); // to set properly the status field
        $_REQUEST['seoaic-background-generation'] = 'yes';

        $this->seoaic->posts->postsMassGenerate();
        // responce is returned in postsMassGenerate() function
    }

    public function ajaxWizardReset()
    {
        $this->initSettings($reset = true);

        SEOAICAjaxResponse::success()->wpSend();
    }
}
