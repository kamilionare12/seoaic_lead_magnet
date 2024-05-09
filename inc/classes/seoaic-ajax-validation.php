<?php

class SeoaicAjaxValidation
{
    private $allowed_actions = [
        'seoaic_update_company_credits',
        'seoaic_get_blog_settings',
        'seoaic_posts_mass_generate_check_status',
    ];

    public function __construct()
    {
        add_action('admin_init', [$this, 'checkAjaxPermissions']);
        add_action('admin_enqueue_scripts', [$this, 'seoaicAdminNonceParams']);
        add_action('enqueue_block_assets', [$this, 'seoaicGutenbergNonceParams']);
    }

    public function checkAjaxPermissions()
    {
        if (!wp_doing_ajax()) {
            return;
        }

        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

        if ($this->isSeoaicAction($action) && !$this->isActionExcluded($action)) {
            $this->userValidation($action);
            $this->nonceValidation($action);
        }
    }

    private function isSeoaicAction($action)
    {
       return strpos($action, 'seoaic') === 0;
    }

    private function userValidation($action)
    {
        if (!current_user_can('seoaic_edit_plugin')) {
            wp_send_json([
                'status'  => 'alert',
                'message' => __('Permission denied', 'seoaic'),
            ]);
        }
    }

    private function nonceValidation($action)
    {
        $nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field($_REQUEST['_wpnonce']) : false;

        if (empty($nonce) || !wp_verify_nonce($nonce, $action . '_nonce')) {
            wp_send_json([
                'status'  => 'alert',
                'message' => __('Oops! It seems like something went wrong. Please refresh the page and try again. If the issue persists, contact our support team for assistance. Error: Invalid security token.', 'seoaic'),
            ]);
        }
    }

    private function isActionExcluded($action)
    {
        return in_array($action, $this->allowed_actions);
    }

    private function ajaxNonceActions() {
        $nonce_actions = [
            'seoaic_get_keyword_serp' => wp_create_nonce('seoaic_get_keyword_serp_nonce'),
            'seoaic_get_child_keywords' => wp_create_nonce('seoaic_get_child_keywords_nonce'),
            'seoaic_filter_schedule' => wp_create_nonce('seoaic_filter_schedule_nonce'),
            'seoaicGetCountries' => wp_create_nonce('seoaicGetCountries_nonce'),
            'seoaicGetGroupLocation' => wp_create_nonce('seoaicGetGroupLocation_nonce'),
            'seoaicUpdateSearchTerms' => wp_create_nonce('seoaicUpdateSearchTerms_nonce'),
            'seoaic_send_upgrade_plan' => wp_create_nonce('seoaic_send_upgrade_plan_nonce'),
            'seoaic_settings' => wp_create_nonce('seoaic_settings_nonce'),
            'seoaic_settings_generate_description' => wp_create_nonce('seoaic_settings_generate_description_nonce'),
            'seoaic_registration' => wp_create_nonce('seoaic_registration_nonce'),
            'seoaic_login' => wp_create_nonce('seoaic_login_nonce'),
            'seoaic_forgot' => wp_create_nonce('seoaic_forgot_nonce'),
            'seoaic_disconnect' => wp_create_nonce('seoaic_disconnect_nonce'),
            'seoaic_get_idea_content' => wp_create_nonce('seoaic_get_idea_content_nonce'),
            'seoaic_clear_background_option' => wp_create_nonce('seoaic_clear_background_option_nonce'),
            'seoaic_add_idea' => wp_create_nonce('seoaic_add_idea_nonce'),

            'seoaic_posts_mass_generate_stop' => wp_create_nonce('seoaic_posts_mass_generate_stop_nonce'),
            'seoaic_posts_mass_generate_check_status_manually' => wp_create_nonce('seoaic_posts_mass_generate_check_status_manually_nonce'),
            'seoaic_scan_site' => wp_create_nonce('seoaic_scan_site_nonce'),
            'seoaic_schedule_posts' => wp_create_nonce('seoaic_schedule_posts_nonce'),
            'seoaic_posts_mass_create' => wp_create_nonce('seoaic_posts_mass_create_nonce'),
            'seoaic_posts_mass_generate_save_prompt_template' => wp_create_nonce('seoaic_posts_mass_generate_save_prompt_template_nonce'),
            'seoaic_posts_mass_generate_delete_prompt_template' => wp_create_nonce('seoaic_posts_mass_generate_delete_prompt_template_nonce'),

            'seoaic_posts_mass_edit' => wp_create_nonce('seoaic_posts_mass_edit_nonce'),
            'seoaic_posts_mass_edit_check_status' => wp_create_nonce('seoaic_posts_mass_edit_check_status_nonce'),
            'seoaic_posts_mass_stop_edit' => wp_create_nonce('seoaic_posts_mass_stop_edit_nonce'),
            'seoaic_clear_edit_background_option' => wp_create_nonce('seoaic_clear_edit_background_option_nonce'),

            'seoaic_posts_mass_review' => wp_create_nonce('seoaic_posts_mass_review_nonce'),
            'seoaic_posts_mass_review_check_status' => wp_create_nonce('seoaic_posts_mass_review_check_status_nonce'),
            'seoaic_posts_mass_stop_review' => wp_create_nonce('seoaic_posts_mass_stop_review_nonce'),
            'seoaic_clear_review_background_option' => wp_create_nonce('seoaic_clear_review_background_option_nonce'),

            'seoaic_posts_mass_translate' => wp_create_nonce('seoaic_posts_mass_translate_nonce'),
            'seoaic_posts_mass_translate_check_status' => wp_create_nonce('seoaic_posts_mass_translate_check_status_nonce'),

            'seoaic_edit_idea' => wp_create_nonce('seoaic_edit_idea_nonce'),
            'seoaic_remove_idea' => wp_create_nonce('seoaic_remove_idea_nonce'),
            'seoaic_generate_skeleton' => wp_create_nonce('seoaic_generate_skeleton_nonce'),
            'seoaic_save_content_idea' => wp_create_nonce('seoaic_save_content_idea_nonce'),

            'seoaic_add_keyword' => wp_create_nonce('seoaic_add_keyword_nonce'),

            'seoaic_update_keywords' => wp_create_nonce('seoaic_update_keywords_nonce'),
            'seoaic_remove_keyword' => wp_create_nonce('seoaic_remove_keyword_nonce'),
            'seoaic_remove_and_reassign_keyword' => wp_create_nonce('seoaic_remove_and_reassign_keyword_nonce'),
            'seoaic_keywords_get_siblings_keywords' => wp_create_nonce('seoaic_keywords_get_siblings_keywords_nonce'),
            'seoaic_keywords_poll_rank_data' => wp_create_nonce('seoaic_keywords_poll_rank_data_nonce'),
            'seoaic_keywords_category_add' => wp_create_nonce('seoaic_keywords_category_add_nonce'),
            'seoaic_keywords_get_categories' => wp_create_nonce('seoaic_keywords_get_categories_nonce'),
            'seoaic_keywords_update_category' => wp_create_nonce('seoaic_keywords_update_category_nonce'),
            'seoaic_keywords_delete_category' => wp_create_nonce('seoaic_keywords_delete_category_nonce'),
            'seoaic_keywords_set_category' => wp_create_nonce('seoaic_keywords_set_category_nonce'),
            'seoaic_set_keyword_link' => wp_create_nonce('seoaic_set_keyword_link_nonce'),
            'seoaic_keyword_get_created_ideas' => wp_create_nonce('seoaic_keyword_get_created_ideas_nonce'),
            'seoaic_keyword_get_created_posts' => wp_create_nonce('seoaic_keyword_get_created_posts_nonce'),

            'seoaic_get_locatios' => wp_create_nonce('seoaic_get_locatios_nonce'),
            'seoaic_get_location_languages' => wp_create_nonce('seoaic_get_location_languages_nonce'),

            'seoaicDeleteGroupLocation' => wp_create_nonce('seoaicDeleteGroupLocation_nonce'),
            'seoaicSaveLocationGroup' => wp_create_nonce('seoaicSaveLocationGroup_nonce'),
            'seoaicAddSearchTerms' => wp_create_nonce('seoaicAddSearchTerms_nonce'),
            'seoaicRemoveSearchTerms' => wp_create_nonce('seoaicRemoveSearchTerms_nonce'),
            'seoaic_get_search_term_competitors' => wp_create_nonce('seoaic_get_search_term_competitors_nonce'),
            'seoaicGetKeywordSuggestions' => wp_create_nonce('seoaicGetKeywordSuggestions_nonce'),
            'seoaicAddedCompetitors' => wp_create_nonce('seoaicAddedCompetitors_nonce'),
            'seoaicAddGroupLocation' => wp_create_nonce('seoaicAddGroupLocation_nonce'),
            'seoaicRenameGroupLocation' => wp_create_nonce('seoaicRenameGroupLocation_nonce'),
            'seoaic_generate_ideas' => wp_create_nonce('seoaic_generate_ideas_nonce'),
            'seoaic_generate_ideas_new_keywords' => wp_create_nonce('seoaic_generate_ideas_new_keywords_nonce'),
            'seoaic_generate_keywords_prompt' => wp_create_nonce('seoaic_generate_keywords_prompt_nonce'),
            'seoaicAddSubTerms' => wp_create_nonce('seoaicAddSubTerms_nonce'),
            'seoaic_publish_post' => wp_create_nonce('seoaic_publish_post_nonce'),
            'seoaic_regenerate_image' => wp_create_nonce('seoaic_regenerate_image_nonce'),
            'seoaic_setinfo' => wp_create_nonce('seoaic_setinfo_nonce'),
            'seoaic_remove_idea_posting_date' => wp_create_nonce('seoaic_remove_idea_posting_date_nonce'),
            'seoaic_improve_post' => wp_create_nonce('seoaic_improve_post_nonce'),
            'seoaicLocationsOptions' => wp_create_nonce('seoaicLocationsOptions_nonce'),
            'seoaic_generate_post' => wp_create_nonce('seoaic_generate_post_nonce'),
            'seoaic_getCategoriesOfPosttype' => wp_create_nonce('seoaic_getCategoriesOfPosttype_nonce'),
            'seoaic_selectCategoriesIdea' => wp_create_nonce('seoaic_selectCategoriesIdea_nonce'),
            'seoaic_Update_credits_real_time' => wp_create_nonce('seoaic_Update_credits_real_time_nonce'),
            'seoaic_Get_on_page_seo_data' => wp_create_nonce('seoaic_Get_on_page_seo_data_nonce'),
            'seoaic_transform_idea' => wp_create_nonce('seoaic_transform_idea_nonce'),
            'seoaic_get_knowledge_base_list' => wp_create_nonce('seoaic_get_knowledge_base_list_nonce'),
            'seoaic_create_knowledge_base' => wp_create_nonce('seoaic_create_knowledge_base_nonce'),
            'seoaic_wizard_generate_keywords' => wp_create_nonce('seoaic_wizard_generate_keywords_nonce'),
            'seoaic_wizard_reload_entities' => wp_create_nonce('seoaic_wizard_reload_entities_nonce'),
            'seoaic_wizard_step_back' => wp_create_nonce('seoaic_wizard_step_back_nonce'),
            'seoaic_wizard_select_keywords' => wp_create_nonce('seoaic_wizard_select_keywords_nonce'),
            'seoaic_wizard_generate_ideas' => wp_create_nonce('seoaic_wizard_generate_ideas_nonce'),
            'seoaic_wizard_posts_mass_create' => wp_create_nonce('seoaic_wizard_posts_mass_create_nonce'),
            'seoaic_wizard_reset' => wp_create_nonce('seoaic_wizard_reset_nonce'),
            'seoaic_Add_New_Competitor' => wp_create_nonce('seoaic_Add_New_Competitor_nonce'),
            'seoaic_remove_competitor' => wp_create_nonce('seoaic_remove_competitor_nonce'),
            'seoaic_Competitors_Compare_Section_HTML' => wp_create_nonce('seoaic_Competitors_Compare_Section_HTML_nonce'),
            'seoaic_Competitors_Search_Terms_HTML' => wp_create_nonce('seoaic_Competitors_Search_Terms_HTML_nonce'),
            'seoaic_Remove_Term' => wp_create_nonce('seoaic_Remove_Term_nonce'),
            'seoaic_Get_My_Rank_Search_Term' => wp_create_nonce('seoaic_Get_My_Rank_Search_Term_nonce'),
            'seoaic_Generate_Article_Based_Search_Term' => wp_create_nonce('seoaic_Generate_Article_Based_Search_Term_nonce'),
            'seoaic_Prepare_Article_Based_Search_Term' => wp_create_nonce('seoaic_Prepare_Article_Based_Search_Term_nonce'),
            'seoaic_Check_Terms_Update_Progress' => wp_create_nonce('seoaic_Check_Terms_Update_Progress_nonce'),
            'seoaicAddCompetitorsTerms' => wp_create_nonce('seoaicAddCompetitorsTerms_nonce'),
            'seoaic_compare_other_positions' => wp_create_nonce('seoaic_compare_other_positions_nonce'),
            'seoaic_compare_my_article' => wp_create_nonce('seoaic_compare_my_article_nonce'),
            'seoaic_compare_my_competitors' => wp_create_nonce('seoaic_compare_my_competitors_nonce'),
            'seoaic_compare_analysis' => wp_create_nonce('seoaic_compare_analysis_nonce'),
            'seoaic_Progress_Values' => wp_create_nonce('seoaic_Progress_Values_nonce'),
            'seoaic_get_seo_audit_data' => wp_create_nonce('seoaic_get_seo_audit_data_nonce'),
            'seoaic_create_seo_audit' => wp_create_nonce('seoaic_create_seo_audit_nonce'),
            'seoaic_remove_knowledge_base' => wp_create_nonce('seoaic_remove_knowledge_base_nonce'),
            'seoaic_save_knowledge_base_data_sources' => wp_create_nonce('seoaic_save_knowledge_base_data_sources_nonce'),
            'seoaic_get_data_sources_list' => wp_create_nonce('seoaic_get_data_sources_list_nonce'),
            'seoaic_rerun_data_source' => wp_create_nonce('seoaic_rerun_data_source_nonce'),
            'seoaic_remove_data_source' => wp_create_nonce('seoaic_remove_data_source_nonce'),
            'seoaic_train_data_source' => wp_create_nonce('seoaic_train_data_source_nonce'),
            'seoaic_train_knowledge_base' => wp_create_nonce('seoaic_train_knowledge_base_nonce'),
            'seoaic_rerun_knowledge_base' => wp_create_nonce('seoaic_rerun_knowledge_base_nonce'),
            'seoaic_get_crawled_pages' => wp_create_nonce('seoaic_get_crawled_pages_nonce'),
            'seoaic_rerun_sources' => wp_create_nonce('seoaic_rerun_sources_nonce'),
            'seoaic_remove_sources' => wp_create_nonce('seoaic_remove_sources_nonce'),
            'seoaic_run_dashboard_data' => wp_create_nonce('seoaic_run_dashboard_data_nonce'),
            'seoaic_dashboard_HTML' => wp_create_nonce('seoaic_dashboard_HTML_nonce'),
            'seoaicFilteringTerms' => wp_create_nonce('seoaicFilteringTerms_nonce'),
            'seoaic_update_competitor_data' => wp_create_nonce('seoaic_update_competitor_data_nonce'),
            'seoaic_my_article_popup_top_table_analysis' => wp_create_nonce('seoaic_my_article_popup_top_table_analysis_nonce'),
            'seoaic_competitor_article_popup_table_analysis' => wp_create_nonce('seoaic_competitor_article_popup_table_analysis_nonce'),
            'seoaic_settings_get_post_type_templates' => wp_create_nonce('seoaic_settings_get_post_type_templates_nonce'),
            'seoaic_save_knowledge_base' => wp_create_nonce('seoaic_save_knowledge_base_nonce'),
            'seoaic_validate_positions_real_terms_count' => wp_create_nonce('seoaic_validate_positions_real_terms_count_nonce'),
            'seoaic_compare_competitors' => wp_create_nonce('seoaic_compare_competitors_nonce'),
            'seoaic_get_top_google_analysis' => wp_create_nonce('seoaic_get_top_google_analysis_nonce'),
            'seoaic_migrate_competitors_from_options' => wp_create_nonce('seoaic_migrate_competitors_from_options_nonce'),
        ];

        return $nonce_actions;
    }

    public function seoaicAdminNonceParams()
    {
        wp_localize_script( 'seoaic_admin_main_js', 'wp_nonce_params', $this->ajaxNonceActions());
    }

    public function seoaicGutenbergNonceParams()
    {
        wp_localize_script('seoaic_gutenberg_js', 'wp_nonce_params', $this->ajaxNonceActions());
    }
}
