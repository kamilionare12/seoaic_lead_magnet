<?php

use SEOAIC\loaders\PostsGenerationLoader;
use SEOAIC\posts_mass_actions\PostsMassTranslate;
use SEOAIC\SEOAIC;
use SEOAIC\SEOAIC_CURL;
use SEOAIC\SEOAICAjaxResponse;

class SEOAIC_MULTILANG
{
    const TRANSIENT_LOCATIONS_FIELD = 'seoaic_locations';
    const TRANSIENT_LAST_LOCATION_FIELD = 'seoaic_last_location';
    private $seoaic;

    function __construct($_seoaic)
    {
        $this->seoaic = $_seoaic;

        //add_action('init', [$this, 'ml_class_init']);

        add_action('wp_ajax_seoaic_get_locatios', [$this, 'getLocationsAjax']);
        add_action('wp_ajax_seoaic_get_location_languages', [$this, 'getLocationLanguagesAjax']);
    }

    /**
     * Init Multilanguage class init
     */
    function ml_class_init () {
        $post_types = seoaic_get_post_types();

        foreach ( $post_types as $post_type ) {
            add_action('bulk_actions-edit-' . $post_type, [$this, 'add_bulk_action_translate']);
            add_action('handle_bulk_actions-edit-' . $post_type, [$this, 'handle_bulk_action_translate'], 20, 3);
        }
        //add_action('restrict_manage_posts', [$this, 'admin_filter_languages']);
    }

    /**
     * Show languages in post table page
     */
    function admin_filter_languages () {
        //$this->get_multilang_checkboxes();
    }

    /**
     * Add bulk action
     * @param $bulk_actions array
     * @return array
     */
    function add_bulk_action_translate ( $bulk_actions ) {
        $bulk_actions['seoaic_translate'] = 'Translate';

        $this->get_multilang_checkboxes();

        return $bulk_actions;
    }

    /**
     * Handle bulk action
     * @param $redirect_url string
     * @param $action string
     * @param $post_ids array
     */
    function handle_bulk_action_translate ( $redirect_url, $action, $post_ids ) {
        if ( $action === 'seoaic_translate' && !empty($_REQUEST['seoaic_multilanguages']) ) {
            $posts_to_translate = [];

            foreach ( $post_ids as $post_id ) {
                $languages_to_translate = $_REQUEST['seoaic_multilanguages'];
                $post_language = $this->get_post_language($post_id, 'locale');
                $post_type = get_post_type($post_id);

                if ( empty($post_language) ) {
                    $args = [
                        'language'  => $this->get_default_language(),
                        'post_type' => $post_type,
                        'parent_id' => 0,
                        'multi'     => false,
                    ];
                    $this->add_new_idea_manually($post_id, $args);
                    $post_language = $this->get_post_language($post_id, 'locale');
                }

                $post_translations = $this->get_post_translations($post_id);

                $post_translated_ideas = [];
                if ( empty($post_translations) ) {
                    $post_translations = [$this->get_post_language($post_id, 'code') => $post_id];
                }

                foreach ( $post_translations as $post_translation_locale => $post_translation_id ) {

                    if ( $post_translation_id == $post_id ) {
                        if ( ($key = array_search($post_language, $languages_to_translate)) !== false )
                            unset($languages_to_translate[$key]);
                        continue;
                    }

                    $post_translation_type = get_post_type($post_translation_id);
                    if ( $post_translation_type !== 'seoaic-post' ) {
                        if ( ($key = array_search($this->get_post_language($post_translation_id, 'locale'), $languages_to_translate)) !== false )
                            unset($languages_to_translate[$key]);
                        continue;
                    }

                    $post_translated_ideas[$post_translation_locale] = $post_translation_id;

                    if ( ($key = array_search($this->get_post_language($post_translation_id, 'locale'), $languages_to_translate)) !== false )
                        unset($languages_to_translate[$key]);
                }

                if ( empty($languages_to_translate) && empty($post_translated_ideas) ) {
                    continue;
                }

                $idea_content = get_post_meta($post_id, 'seoaic_idea_content', true);

                $title = get_the_title($post_id);
                foreach ( $languages_to_translate as $language_locale ) {
                    $language = $this->get_language_by($language_locale, 'locale');
                    $args = [
                        'name'      => $title . ' (' . $language['name'] . ')',
                        'language'  => $language['name'],
                        'parent_id' => $post_id,
                        'return'    => true,
                    ];
                    $idea_id = $this->seoaic->ideas->add_idea($args);

                    $post_translated_ideas[$language['code']] = $idea_id;
                }

                $posts_to_translate[$post_id] = [
                    'thumbnail' => get_post_thumbnail_id($post_id),
                    'idea_content' => $idea_content,
                    'post_type' => $post_type,
                    'translate' => $post_translated_ideas,
                ];
            }

            $ideas_array = [];
            $post_type = '';

            foreach ( $posts_to_translate as $post_id => $post ) {
                $post_type = $post['post_type'];

                foreach ( $post['translate'] as $idea_language => $idea_id ) {
                    $ideas_array[] = $idea_id;

                    update_post_meta($idea_id, 'seoaic_idea_content', $post['idea_content']);
                    update_post_meta($idea_id, 'seoaic_ml_original_post', $post_id);

                    if (!empty($post['thumbnail'])) {
                        set_post_thumbnail($idea_id, $post['thumbnail']);
                    }
                }
            }

            $data = [
                'domain' => $_SERVER['HTTP_HOST'],
                'idea_id' => $ideas_array,
                'posting_date' => NULL,
                'manual_mass_thumb' => 0,
                'type' => $post_type,
            ];

            $prev_option = PostsGenerationLoader::getPostsOption();

            if (
                isset($prev_option['total'])
                && isset($prev_option['done'])
                && count($prev_option['total']) > count($prev_option['done'])
            ) {
                $option_ideas = array_merge($ideas_array, $prev_option['total'] ?? []);
                $option_posts = $prev_option['done'];
            } else {
                $option_ideas = $ideas_array;
                $option_posts = [];
            }

            $option = [
                'total' => $option_ideas,
                'done' => $option_posts,
            ];

            PostsGenerationLoader::setPostsOption($option);

            // TODO: change to new endpoints
            // api/ai/posts/translate
            $result = $this->seoaic->curl->init('api/schedule', $data, true, false, true);

            return $redirect_url;
        }
    }


    /**
     * WordPress is multilingual
     *
     * @return boolean|string
     */
    public function is_multilang () {

        if ( $this->_is_polylang_active() ) {
            return 'polylang';
        }

        if ( $this->_is_wpml_active() ) {
            return 'wpml';
        }

        return false;
    }

    /**
     * Check if Polylang is active
     * @return boolean
     */
    private function _is_polylang_active() {
        return function_exists('pll_the_languages') && function_exists('pll_count_posts');
    }

    /**
     * Check if WPML SitePress is active
     * @return boolean
     */
    private function _is_wpml_active() {
        return class_exists('SitePress');
    }

    /**
     * Get WordPress default language
     * @return false|string
     */
    public function get_default_language ( $key = 'name' ) {

        switch ( $this->is_multilang() ) {
            case 'polylang':
                if ( $key === 'code' ) {
                    return pll_default_language();
                }
                return pll_default_language( $key );
                break;
            case 'wpml':
                $language = apply_filters('wpml_default_language', NULL );
                if ( $key === 'code' ) {
                    return $language;
                }
                $language = $this->get_language_by($language, 'code');
                if ( !empty($language[$key]) ) {
                    return $language[$key];
                }
                break;
        }

        return false;
    }

    /**
     * Get all WordPress active languages
     * @return array
     */
    public function get_multilanguages () {
        $langs = [];

        switch ( $this->is_multilang() ) {
            case 'polylang':
                foreach ( pll_languages_list(['fields' => []]) as $_lang ) {
                    $langs[] = [
                        'code' => $_lang->slug,
                        'name' => $_lang->name,
                        'locale' => $_lang->locale,
                        'flag' => $_lang->flag_url,
                    ];
                }
                break;
            case 'wpml':
                foreach ( apply_filters( 'wpml_active_languages', NULL ) as $_lang ) {
                    $langs[] = [
                        'code' => $_lang['language_code'],
                        'name' => $_lang['translated_name'],
                        'locale' => $_lang['default_locale'],
                        'flag' => $_lang['country_flag_url'],
                    ];
                }
                break;
        }

        return $langs;
    }

    /**
     * Get post language
     * @param int $post_id
     * @return false|string
     */
    public function get_post_language ( $post_id, $key = 'name' ) {

        if ( empty($post_id) ) return false;

        global $SEOAIC_OPTIONS;
        // $language = !empty($SEOAIC_OPTIONS['seoaic_language']) ? $SEOAIC_OPTIONS['seoaic_language'] : 'English';
        $language = false;

        switch ( $this->is_multilang() ) {
            case 'polylang':
                switch ( $key ) {
                    case 'code': $key = 'slug'; break;
                }
                $pllLanguage = pll_get_post_language( $post_id, $key );
                if ($pllLanguage) {
                    $language = $pllLanguage;
                }
                break;
            case 'wpml':
                $wpmlLanguage = apply_filters( 'wpml_post_language_details', NULL, $post_id );
                switch ( $key ) {
                    case 'name': $key = 'display_name'; break;
                    case 'code': $key = 'language_code'; break;
                    //case 'locale': $key = 'default_locale'; break;
                }
                if ($wpmlLanguage) {
                    $language = $wpmlLanguage[$key];
                }
                break;
        }
        if (!$this->is_multilang()) {
            $language = $SEOAIC_OPTIONS['seoaic_language'];
        }
        return $language;
    }

    /**
     * Get language`s image tag
     * @param string $language_in
     * @return string
     */
    public function get_language_flag_img ( $language_in ) {
        $flag = $this->get_language_info($language_in, 'flag');

        if ( !empty($flag) ) {
            return '<img src="' . $flag . '" width="16" height="11" alt="' . $language_in . '">';
        }

        return '';
    }

    /**
     * Get language information as name, code, flag, locale
     * @param string $language_in
     * @param string $key
     * @return false|string
     */
    public function get_language_info ( $language_in, $key = 'name' ) {

        $language = $this->get_language_by($language_in);

        if ( !empty($language) && array_key_exists($key, $language) ) {
            return $language[$key];
        }

        return false;
    }

    /**
     * Get language by name, code, locale
     * @param string $language_in
     * @param string $by
     * @return false|array
     */
    public function get_language_by ( $language_in, $by = 'name' ) {
        foreach ( $this->get_multilanguages() as $_lang ) {
            if ( $language_in === $_lang[$by] ) {
                return $_lang;
            }
        }

        return false;
    }

    /**
     * Get post existing translations
     * @param int $post_id
     * @return false|array
     */
    public function get_post_translations ( $post_id, $post_type = 'post_seoaic-post' ) {
        switch ( $this->is_multilang() ) {
            case 'polylang':
                return pll_get_post_translations($post_id);
                break;
            case 'wpml':
                if ( !apply_filters( 'wpml_element_has_translations', null, $post_id, $post_type ) )
                    return false;

                global $wpdb;

                $sql = "SELECT `trid` FROM `{$wpdb->prefix}icl_translations` WHERE `element_id` = {$post_id} AND `element_type` LIKE 'post_%'";
                $trid = $wpdb->get_var($sql);

                if ( empty($trid) )
                    return false;

                $sql = "SELECT `element_id`, `language_code` FROM `{$wpdb->prefix}icl_translations` WHERE `trid` = {$trid} AND `element_type` LIKE 'post_%'";
                $translations = $wpdb->get_results( $sql );

                $translationsArray = [];
                foreach ( $translations as $translation ) {
                    $translationsArray[$translation->language_code] = intval($translation->element_id);
                }

                return $translationsArray;
                break;
        }

        return false;
    }

    /**
     * Sort posts by languages
     * @param array $posts
     * @return array
     */
    public function sort_posts_by_languages ( $posts = [] ) {

        if ( $this->is_multilang() ) {
            $new_order_posts = [];

            foreach ( $posts as $post ) {

                if ( isset($new_order_posts[$post->ID]) ) continue;

                $new_order_posts[$post->ID] = $post;

                $translations = $this->get_post_translations($post->ID);

                if ( empty($translations) )
                    continue;

                foreach ( $translations  as $translation ) {

                    foreach ( $posts as $_post ) {

                        if ( isset($new_order_posts[$_post->ID]) ) continue;

                        if ( $translation === $_post->ID ) {
                            $new_order_posts[$_post->ID] = $_post;
                            break;
                        }
                    }
                }
            }

            return $new_order_posts;
        }

        return $posts;
    }

    /**
     * Get languages as comma separated string for cURL param
     * @return string
     */
    public function filter_request_multilang () {
        $language = $this->get_selected_language();

        if ( !$this->is_multilang() || empty($_REQUEST['seoaic_multilanguages']) ) {
            return $language;
        }

        $languages = $this->get_multilanguages();

        if ( empty($languages) ) {
            return $language;
        }

        $post_languages = [];

        foreach ( $languages as $language ) {
            if ( in_array($language['locale'], $_REQUEST['seoaic_multilanguages']) ) {
                $post_languages[] = $language['name'];
            }
        }

        if ( empty($post_languages) ) {
            return $language;
        }

        return implode(',', $post_languages);
    }

    /**
     * Set post language
     * @param int $post_id
     * @param string $language
     */
    public function set_post_language ( $post_id, $language ) {

        switch ( $this->is_multilang() ) {
            case 'polylang':
                pll_set_post_language($post_id, $language);
                break;
            case 'wpml':

                break;
        }
    }

    /**
     * Set post translations
     * @param array $translations
     */
    public function save_post_translations ( $translations ) {

        if ( empty($translations) ) {
            return false;
        }

        switch ( $this->is_multilang() ) {
            case 'polylang':
                pll_save_post_translations($translations);
                break;
            case 'wpml':

                break;
        }
    }

    /**
     * Get WordPress active language
     * @return false|string
     */
    public function get_current_language () {

        switch ( $this->is_multilang() ) {
            case 'polylang':
                $current = pll_current_language();
                $language = $this->get_language_by($current, 'code');
                if ( !empty($language['name']) ) {
                    return $language['name'];
                }
                break;
            case 'wpml':
                $current = apply_filters( 'wpml_current_language', null );
                $language = $this->get_language_by($current, 'code');
                if ( !empty($language['name']) ) {
                    return $language['name'];
                }
                break;
        }

        return false;
    }

    /**
     * Update post args for get_posts
     * @param array $args
     * @return array $args
     */
    public function update_post_args ( $args ) {

        switch ( $this->is_multilang() ) {
            case 'polylang':
                $current = pll_current_language();
                if ( !empty($current) ) {
                    $args['lang'] = $current;
                }
                break;
            case 'wpml':

                break;
        }

        return $args;
    }

    /**
     * Get terms in WordPress default language
     * @return array $terms
     */
    public function get_terms ( $post_type = 'post' ) {

        $taxonomies = get_object_taxonomies(['post_type' => $post_type]);

        $args = [
            'hide_empty' => false,
            'taxonomy' => $taxonomies,
        ];

        $terms = false;

        switch ( $this->is_multilang() ) {
            case 'polylang':
                $args['lang'] = $this->get_default_language('code');
                $terms = get_terms($args);

                $new_terms = [];
                $default_language = $this->get_default_language('code');
                foreach ( $terms as $term ) {
                    if ( $term->taxonomy === 'language' ) continue;
                    if ( $term->taxonomy === 'post_translations' ) continue;

                    $term_language = pll_get_term_language( $term->term_id );
                    if ( false === $term_language || $term_language === $default_language ) {
                        $new_terms[] = $term;
                    }
                }
                $terms = $new_terms;
                break;
            case 'wpml':
                $default_language = $this->get_default_language('code');

                $terms = get_terms($args);

                $new_terms = [];
                foreach ( $terms as $term ) {
                    if ( $term->taxonomy === 'translation_priority' ) continue;

                    $term_info = apply_filters( 'wpml_element_language_details', null, ['element_id' => $term->term_id, 'element_type' => 'category'] );

                    if ( $term_info->language_code === $default_language ) {
                        $new_terms[] = $term;
                    }
                }
                $terms = $new_terms;
                break;
            default:
                $terms = get_terms($args);
        }

        return $terms;
    }

    /**
     * Get term translation
     * @param int $term_id
     * @param string $language
     * @return array $terms
     */
    public function get_term_translation ( $term_id, $language = false ) {

        $term_id = intval($term_id);

        if ( empty($language) ) {
            return $term_id;
        }

        switch ( $this->is_multilang() ) {
            case 'polylang':
                $term_id = pll_get_term($term_id, $language);
                break;
            case 'wpml':
                return apply_filters( 'wpml_object_id', $term_id, 'category', false, $language );
                break;
        }

        return $term_id;
    }

    /**
     * Construct buttons to control translations
     * @param int $post_id
     * @param array $posts
     * @return string
     */
    public function get_language_translations_control ( $post_id, $posts ) {
        if ( !$this->is_multilang() ) return '';

        $post_language = $this->get_post_language($post_id);
        $current_flag = $this->get_language_flag_img($post_language);
        $translations = $this->get_post_translations($post_id);

        $languages = $this->get_multilanguages();

        if ( empty($post_language) ) return '';

        ?>
        <div class="seoic-translations-buttons">

            <span title="Current language (<?=$post_language?>)"><?=$current_flag?></span>

            <?php foreach ( $languages as $language ) : ?>
                <?php
                    if ( $post_language === $language['name'] ) continue;

                    $translation_flag = $this->get_language_flag_img($language['name']);

                    if ( !empty($translations[$language['code']]) ) :
                        if ( isset($posts[$translations[$language['code']]]) ) :

                ?>
                        <button type="button" title="Edit <?=$language['name']?> translation"
                                class="seoaic-link-translation seoaic-button-link"
                                data-target="#edit-idea-button-<?=$translations[$language['code']]?>"
                        ><?=$translation_flag?></button>

                    <?php else : ?>
                        <a class="generated-post-link" target="_blank" title="This translation is already generated" href="<?=get_edit_post_link($translations[$language['code']])?>"><?=$translation_flag?></a>
                    <?php endif; ?>
                <?php else : ?>

                    <button type="button" title="Add <?=$language['name']?> translation"
                            class="seoaic-add-translation modal-button"
                            data-title="<?=__( 'Add ' . $language['name'] . ' translation', 'seoaic' ); ?>"
                            data-modal="#add-idea"
                            data-mode="add"
                            data-single="yes"
                            data-form-callback="window_reload"
                            data-language-parent-id="<?=$post_id?>"
                            data-languages="false"
                            data-language="<?=$language['name']?>"
                    ><?=$translation_flag?>
                        <div class="dn edit-form-items">
                            <input type="hidden" name="item_name" value="" data-label="Name">
                        </div>
                    </button>

                <?php endif; ?>
            <?php endforeach; ?>

        </div>
        <?php
    }

    /**
     * Construct post's language flag
     * @param int $post_id Post ID
     * @return string
     */
    public function getPostLanguageAndFlag($post_id)
    {
        $return = [
            'language' => '',
            'flag' => '',
        ];

        if (
            !$this->is_multilang()
            || empty($post_id)
            || !is_numeric($post_id)
        ) {
            return $return;
        }

        $post_language = $this->get_post_language($post_id);

        if (empty($post_language)) {
            return $return;
        }

        $return['language'] = $post_language;
        $return['flag'] = $this->get_language_flag_img($post_language);

        return $return;
    }

    public function displayPostLanguageFlag($post_id)
    {
        list('language' => $language, 'flag' => $flag) = $this->getPostLanguageAndFlag($post_id);
        ?>
        <div class="seoaic-post-lang">
            <span title="<?php _e('Post language');?>: <?php echo esc_attr($language);?>"><?php echo $flag;?></span>
        </div>
        <?php
    }

    public function getPostTranslationsLanguageAndFlag($postID)
    {
        $return = [
            'done' => [],
            'in_progress' => [],
        ];

        if (
            !$this->is_multilang()
            || empty($postID)
            || !is_numeric($postID)
        ) {
            return $return;
        }

        $translations = $this->get_post_translations($postID);
        if (!empty($translations)) {
            foreach ($translations as $code => $translationID) {
                if ($postID == $translationID) {
                    continue;
                }
                $doneLang = $this->get_language_by($code, 'code');
                $return['done'][] = [
                    'language'  => !empty($doneLang['name']) ? $doneLang['name'] : '',
                    'flag'      => !empty($doneLang['name']) ? $this->get_language_flag_img($doneLang['name']) : '',
                    'translation_id' => $translationID,
                ];
            }
        }

        $massTranslateInstance = new PostsMassTranslate($this->seoaic);

        if ($massTranslateInstance->isPostTranslating($postID)) {
            $inProgressLang = get_post_meta($postID, $massTranslateInstance->getLanguageField(), true);

            $return['in_progress'][] = [
                'language' => !empty($inProgressLang['name']) ? $inProgressLang['name'] : '',
                'code' => !empty($inProgressLang['code']) ? $inProgressLang['code'] : '',
                'flag' => !empty($inProgressLang['name']) ? $this->get_language_flag_img($inProgressLang['name']) : '',
                'status' => 'in-progress',
            ];

        } else if ($massTranslateInstance->isPostTranslateFailed($postID)) {
            $inProgressLang = get_post_meta($postID, $massTranslateInstance->getLanguageField(), true);
            $return['in_progress'][] = [
                'language' => !empty($inProgressLang['name']) ? $inProgressLang['name'] : '',
                'flag' => !empty($inProgressLang['name']) ? $this->get_language_flag_img($inProgressLang['name']) : '',
                'status' => 'failed',
            ];
        }

        return $return;
    }

    public function displayPostTranslationsFlags($post_id)
    {
        list('done' => $done, 'in_progress' => $inProgress) = $this->getPostTranslationsLanguageAndFlag($post_id);

        if (
            !empty($done)
            || !empty($inProgress)
        ) {
            ?>
            <div class="langs-separator"></div>
            <div class="seoaic-post-translations d-flex">
                <?php
                if (
                    !empty($done)
                    && is_array($done)
                ) {
                    foreach ($done as $translation) {
                        $link = get_edit_post_link($translation['translation_id']);
                        ?>
                        <a
                            title="<?php _e('Post translation');?>: <?php echo esc_attr($translation['language']);?>"
                            href="<?php echo esc_attr($link);?>"
                            target="_blank"
                        ><?php echo $translation['flag'];?></a>
                        <?php
                    }
                }

                if (!empty($inProgress)) {
                    ?>
                    <a
                        title="<?php _e('Post translation');?>: <?php echo esc_attr($inProgress[0]['language']);?>"
                        class="translating language-<?php echo esc_attr($inProgress[0]['language']);?>"
                        href="#"
                        target="_blank"
                    ><?php echo $inProgress[0]['flag'];?></a>
                    <?php
                }
                ?>
            </div>
            <?php
        }
    }

    /**
     * Construct input for translation parent post
     * @return string
     */
    public function get_translation_parent_input () {
        if ( !$this->is_multilang() ) return '';

        ?>
        <input type="hidden" class="seoaic-form-item seoaic-multilanguage-parent-id" name="seoaic-multilanguage-parent-id" value="">
        <?php
    }

    /**
     * Construct select with active languages
     */
    public function get_languages_select($fix_selected_language = '', $disabled = false) {
        if ($this->is_multilang()) {
            $languages = $this->get_multilanguages();
            $select_language = $this->get_current_language();

            if (empty($select_language)) {
                $select_language = $this->get_default_language();
            }

            if (!empty($fix_selected_language)) {
                $select_language = $fix_selected_language;
            }

            ?>
            <select class="seoaic-form-item form-select seoaic-language" name="seoaic_ml_language" required="" <?php echo $disabled ? 'disabled' : ''?>>
                <?php foreach ($languages as $language) : ?>
                    <option <?= ($select_language === $language['name']) ? 'selected' : '' ?> value="<?= $language['name'] ?>">
                        <?= $language['name'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php
        }
    }


    /**
     * Construct checkboxes with active languages
     */
    public function get_multilang_checkboxes($label = 'Languages to generate', $type = 'checkbox')
    {
        global $SEOAIC_OPTIONS;

        if ( $this->is_multilang() ) : ?>
            <div class="col-12 multilang-box">
                <label for="seoaic_server">
                    <?php _e($label, 'seoaic');?>
                    <span class="language-label-description
                        <?php
                        if (
                            isset($SEOAIC_OPTIONS['seoaic_multilanguages'])
                            && count($SEOAIC_OPTIONS['seoaic_multilanguages']) > 0
                        ){
                            ?>
                            language-label-description-hidden
                            <?php
                        }
                        ?>
                        ">Will be used default language (<?php echo $this->get_selected_language();?>)</span>
                </label>
                <div class="toggle-choose-role">
                    <div class="checkbox-list">
                        <?php
                        $counter = rand(0, 10000000);
                        foreach ( $this->get_multilanguages() as $lang ) :
                            ?>
                            <div class="checkbox-wrapper-mc">
                                <?php
                                if (('checkbox' == $type)) {
                                    ?>
                                    <input id="seoaic_multilang-<?= $counter; ?>-<?=$lang['locale'];?>"
                                        class="seoaic-form-item seoaic_multilang-input"
                                        name="seoaic_multilanguages[]"
                                        type="checkbox"
                                        value="<?=$lang['locale'];?>"
                                        <?php
                                            if (
                                                isset($SEOAIC_OPTIONS['seoaic_multilanguages']) && in_array($lang['locale'], $SEOAIC_OPTIONS['seoaic_multilanguages'])
                                            ) {
                                                echo ' checked ';
                                            }
                                        ?>
                                    />
                                    <?php
                                } else if ('radio' == $type) {
                                    ?>
                                    <input id="seoaic_multilang-<?= $counter; ?>-<?php echo $lang['locale'];?>"
                                        class="seoaic-form-item seoaic_multilang-input"
                                        name="seoaic_multilanguages"
                                        type="radio"
                                        value="<?php echo $lang['locale'];?>"
                                        <?php
                                            if (
                                                isset($SEOAIC_OPTIONS['seoaic_multilanguages'])
                                                && in_array($lang['locale'], $SEOAIC_OPTIONS['seoaic_multilanguages'])
                                            ) {
                                                echo ' checked ';
                                            }
                                        ?>
                                    />
                                    <?php
                                }
                                ?>

                                <label for="seoaic_multilang-<?= $counter++; ?>-<?=$lang['locale'];?>" class="check">
                                    <svg width="18px" height="18px" viewBox="0 0 18 18">
                                        <path d="M1,9 L1,3.5 C1,2 2,1 3.5,1 L14.5,1 C16,1 17,2 17,3.5 L17,14.5 C17,16 16,17 14.5,17 L3.5,17 C2,17 1,16 1,14.5 L1,9 Z"></path>
                                        <polyline points="1 9 7 14 15 4"></polyline>
                                    </svg>
                                    <span>
                                        <?php if ( !empty($lang['flag']) ) : ?>
                                            <img src="<?=$lang['flag']?>" width="16" height="11" title="<?=$lang['name']?>" alt="<?=$lang['name']?>">
                                        <?php endif; ?>
                                    <?=$lang['name'] . ' (' . $lang['locale'] . ')';?>
                                    </span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php
        endif;
    }

    /**
     * Get selected language
     */
    public function get_selected_language() {
        if ( $this->is_multilang() ) {
            $selected_language = $this->get_current_language();

            if ( empty($selected_language) ) {
                $selected_language = $this->get_default_language();
            }
        } else {
            global $SEOAIC_OPTIONS;
            $selected_language = !empty($SEOAIC_OPTIONS['seoaic_language']) ? $SEOAIC_OPTIONS['seoaic_language'] : 'English';
        }

        return $selected_language;
    }

    /**
     * @param int $post_id
     */
    public function setPostLanguage($post_id, $args, $force = false)
    {
        $multi = $this->is_multilang();

        if (
            $multi
            && !empty($args['language'])
            && (
                empty($this->get_post_language($post_id))
                || $force
            )
        ) {
            switch ($multi) {
                case 'polylang':
                    $language = sanitize_text_field($args['language']);
                    $language_code = $this->seoaic->multilang->get_language_info($language, 'code');
                    $this->seoaic->multilang->set_post_language($post_id, $language_code);
                    $translation = [
                        $language_code => $post_id
                    ];

                    if (!empty($args['parent_id'])) {
                        $parent_id = intval($args['parent_id']);
                        $parent_translations = $this->seoaic->multilang->get_post_translations($parent_id);
                        $translation = array_merge($translation, $parent_translations);
                    }
                    $this->seoaic->multilang->save_post_translations($translation);
                    break;

                case 'wpml':
                    $original_post_language_info = !empty($args['parent_id']) ? apply_filters('wpml_element_language_details', null, ['element_id' => intval($args['parent_id']), 'element_type' => 'post_' . get_post_type(intval($args['parent_id']))]) : false;

                    $set_language_args = [
                        'element_id' => $post_id,
                        'element_type' => 'post_' . $args['post_type'],
                        'trid' => !empty($original_post_language_info) ? $original_post_language_info->trid : false,
                        'language_code' => $this->seoaic->multilang->get_language_info(sanitize_text_field($args['language']), 'code'),
                        'source_language_code' => !empty($original_post_language_info) ? $original_post_language_info->language_code : NULL,
                    ];

                    do_action('wpml_set_element_language_details', $set_language_args);

                break;
            }
        }
    }

    /**
     * Add new idea manually. Set translation.
     * @param int $post_id
     */
    public function add_new_idea_manually ($post_id, $args, $force = false)
    {
        $multi = $this->is_multilang();

        switch ( $multi ) {
            case 'polylang':
                if (
                    !empty($args['language'])
                    && (
                        false === $this->get_post_language($post_id)
                        || $force
                    )
                ) {
                    $language = sanitize_text_field($args['language']);
                    $language_code = $this->seoaic->multilang->get_language_info($language, 'code');
                    $this->seoaic->multilang->set_post_language($post_id, $language_code);
                    $translation = [
                        $language_code => $post_id
                    ];
                    if ( !empty($args['parent_id']) ) {
                        $parent_id = intval($args['parent_id']);
                        $parent_translations = $this->seoaic->multilang->get_post_translations($parent_id);
                        $translation = array_merge($translation, $parent_translations);
                    }
                    $this->seoaic->multilang->save_post_translations($translation);
                }
                break;
            case 'wpml':
                if (
                    !empty($args['language'])
                    && (
                        empty($this->get_post_language($post_id))
                        || $force
                    )
                ) {
                    $original_post_language_info = !empty($args['parent_id']) ? apply_filters('wpml_element_language_details', null, ['element_id' => intval($args['parent_id']), 'element_type' => 'post_' . get_post_type(intval($args['parent_id']))]) : false;

                    $set_language_args = [
                        'element_id' => $post_id,
                        'element_type' => 'post_' . $args['post_type'],
                        'trid' => !empty($original_post_language_info) ? $original_post_language_info->trid : false,
                        'language_code' => $this->seoaic->multilang->get_language_info(sanitize_text_field($args['language']), 'code'),
                        'source_language_code' => !empty($original_post_language_info) ? $original_post_language_info->language_code : NULL,
                    ];

                    do_action('wpml_set_element_language_details', $set_language_args);
                }
                break;
        }

        if (
            $multi
            && !empty($args['multi'])
            && $args['multi']
        ) {
            $translations = $this->get_post_translations($post_id);
            foreach ( $translations as $translation ) {
                $post_type = get_post_type($translation);
                if ( 'seoaic-post' !== $post_type ) {
                    update_post_meta($translation, 'seoaic_ml_generated_data', []);
                    break;
                }
            }
        }
    }

    /**
     * Add new idea manually. Set translation.
     * @param array $ideas
     */
    public function add_new_ideas_generation ( array $ideas, $post_type = 'seoaic-post' ) {
        switch ( $this->is_multilang() ) {
            case 'polylang':
                foreach ($ideas as $key => $_idea) {
                    $this->seoaic->multilang->set_post_language($_idea['idea_id'], $_idea['language']['code']);
                }

                foreach ( $ideas as $key => $_idea ) {

                    if ( !$_idea['is_default'] ) continue;

                    $translations = [];

                    foreach ( $ideas as $idea_trans ) {
                        if ( empty($idea_trans['language']) ) continue;

                        if ( $idea_trans['key'] === $_idea['key'] ) {
                            $translations[$idea_trans['language']['code']] = $idea_trans['idea_id'];
                        }
                    }

                    $this->seoaic->multilang->save_post_translations($translations);
                }
                break;
            case 'wpml':
                $defaults = [];
                foreach ($ideas as $key => $_idea) {
                    if ( !$_idea['is_default'] ) continue;

                    $defaults[$_idea['key']] = $_idea['idea_id'];

                    $set_language_args = [
                        'element_id' => $_idea['idea_id'],
                        'element_type' => 'post_' . $post_type,
                        'trid' => false,
                        'language_code' => $_idea['language']['code'],
                        'source_language_code' => NULL,
                    ];

                    do_action('wpml_set_element_language_details', $set_language_args);
                }

                foreach ($ideas as $key => $_idea) {
                    if ( $_idea['is_default'] ) continue;

                    $original_post_language_info = [
                        'element_id' => $defaults[$_idea['key']],
                        'element_type' => 'post_' . $post_type,
                    ];
                    $original_post_language_info = apply_filters('wpml_element_language_details', null, $original_post_language_info);

                    $set_language_args = [
                        'element_id' => $_idea['idea_id'],
                        'element_type' => 'post_' . $post_type,
                        'trid' => !empty($original_post_language_info) ? $original_post_language_info->trid : false,
                        'language_code' => $_idea['language']['code'],
                        'source_language_code' => !empty($original_post_language_info) ? $original_post_language_info->language_code : NULL,
                    ];

                    do_action('wpml_set_element_language_details', $set_language_args);
                }
                break;
        }
    }

    /**
     *Get idea settings if WPML.
     * @param int $id
     * @param string $post_type
     * @return array
     */
    public function get_wpml_idea_settings ( $id, $post_type = 'post' ) {
        if ( $this->is_multilang() !== 'wpml' ) return false;

        global $wpdb;

        $sql = "SELECT `translation_id`, `trid`, `language_code`, `source_language_code`
                    FROM `{$wpdb->prefix}icl_translations`
                    WHERE `element_id` = {$id} AND `element_type` LIKE 'post_%'";
        $settings = $wpdb->get_row($sql);

        $wpdb->update( "{$wpdb->prefix}icl_translations", [ 'element_type' => 'post_' . $post_type ], [ 'translation_id' => $settings->translation_id ] );

        $settings->element_type = 'post_' . $post_type;

        return $settings;
    }

    /**
     *Set idea settings if WPML.
     * @param int $id
     * @param array $settings
     */
    public function set_wpml_idea_settings ( $id, $settings ) {
        if ( $this->is_multilang() !== 'wpml' ) return false;

        global $wpdb;

        $sql = "UPDATE `{$wpdb->prefix}icl_translations`
                    SET `trid` = '{$settings->trid}',
                        `language_code` = '{$settings->language_code}',
                        `source_language_code` = '{$settings->source_language_code}'
                    WHERE `element_id` = {$id} AND `element_type` = '{$settings->element_type}'";
        $wpdb->query($sql);
    }

    public function getLocationsWithLanguages()
    {
        $locations = $this->getSavedLocations();

        if (empty($locations)) {
            $locations = $this->requestLocationsWithLanguages();
            $locations = array_values($locations);
            usort($locations, function ($a, $b) {
                return strcmp($a['name'], $b['name']);
            });

            $this->setSavedLocations($locations);
        }

        return $locations;
    }

    public function getLocationsAjax()
    {
        $locations = $this->getLocationsWithLanguages();

        if (
            empty($locations)
            || !is_array($locations)
        ) {
            SEOAICAjaxResponse::error('No locations')->wpSend();
        }

        $fields = [
            'locations' => array_map(function ($location) {
                return $location['name'];
            }, $locations),
        ];

        if (
            !empty($_REQUEST['options_html'])
            && 1 == $_REQUEST['options_html']
        ) {
            $selected = $this->getLastSelectedLocation();
            $fields['options_html'] = self::makeLocationsOptions($locations, $selected);
        }

        SEOAICAjaxResponse::success()->addFields($fields)->wpSend();
    }

    public function getLocationLanguagesAjax()
    {
        if (empty($_REQUEST['location'])) {
            SEOAICAjaxResponse::error('Empty location')->wpSend();
        }

        $locations = $this->getLocationsWithLanguages();

        if (
            empty($locations)
            || !is_array($locations)
        ) {
            SEOAICAjaxResponse::error('No locations')->wpSend();
        }

        $responseFields = [
            'languages' => [],
        ];

        foreach ($locations as $location) {
            if (
                $location['name'] == $_REQUEST['location']
                && !empty($location['languages'])
            ) {
                $this->setLastSelectedLocation($location);
                $responseFields['languages'] = $location['languages'];

                if (
                    !empty($_REQUEST['options_html'])
                    && 1 == $_REQUEST['options_html']
                ) {
                    $responseFields['options_html'] = self::makeLocationLanguagesOptions($location);
                }

                SEOAICAjaxResponse::success()->addFields($responseFields)->wpSend();
            }
        }

        SEOAICAjaxResponse::success('Nothing here')->addFields($responseFields)->wpSend();
    }

    private function requestLocationsWithLanguages()
    {
        $request = new SEOAIC_CURL(new SEOAIC);
        $result = $request->setMethodGet()->initWithReturn('/api/ai/locations-and-languages', [], false, true);

        if (
            !empty($result['status'])
            && 'success' == $result['status']
            && !empty($result['data'])
        ) {
            return $result['data'];
        }

        return [];
    }

    public static function makeLocationsOptions($locationsWithLanguages = [], $default = null): string
    {
        $html = '';
        if (!empty($locationsWithLanguages)) {
            foreach ($locationsWithLanguages as $location) {
                $selected = !empty($default) && $location['name'] == $default['name'] ? ' selected' : '';
                $html .= '<option value="' . esc_attr($location['name']) . '"' . $selected . '>' . esc_html($location['name']) . '</option>';
            }
        }

        return $html;
    }

    public static function makeLocationLanguagesOptions($location = null, $selected = null)
    {
        $html = '';
        if (
            !empty($location)
            && !empty($location['languages'])
        ) {
            $languages = $location['languages'];
            usort($languages, function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });

            foreach ($languages as $lang) {
                $selected = !empty($selected) && $lang['name'] == $selected ? ' selected' : '';
                $html .= '<option value="' . esc_attr($lang['name']) . '"' . $selected . '>' . esc_html($lang['name']) . '</option>';
            }
        }

        return $html;
    }

    public function getFirstLanguageByLocationName($locationName = null)
    {
        if (!empty($locationName)) {
            $locations = $this->getLocationsWithLanguages();
            foreach ($locations as $location) {
                if (
                    $locationName == $location['name']
                    && !empty($location['languages'])
                ) {
                    return array_values($location['languages'])[0];
                }
            }
        }

        return false;
    }

    public function getSavedLocations()
    {
        return get_transient(self::TRANSIENT_LOCATIONS_FIELD);
    }

    public function setSavedLocations($locations = [])
    {
        set_transient(self::TRANSIENT_LOCATIONS_FIELD, $locations, 2 * MINUTE_IN_SECONDS);
    }

    public function getLastSelectedLocation()
    {
        return get_transient(self::TRANSIENT_LAST_LOCATION_FIELD);
    }

    public function setLastSelectedLocation($location): void
    {
        set_transient(self::TRANSIENT_LAST_LOCATION_FIELD, $location);
    }
}