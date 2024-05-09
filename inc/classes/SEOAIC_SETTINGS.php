<?php

namespace SEOAIC;

class SEOAIC_SETTINGS
{
    private $seoaic;

    public function __construct($seoaic)
    {
        $this->seoaic = $seoaic;

        add_action('wp_ajax_seoaic_settings_generate_description', [$this, 'generateDescription']);
    }

    public static function getLanguage()
    {
        global $SEOAIC_OPTIONS;

        return !empty($SEOAIC_OPTIONS['seoaic_language']) ? $SEOAIC_OPTIONS['seoaic_language'] : 'English';
    }

    public static function getIndustry()
    {
        global $SEOAIC_OPTIONS;

        return !empty($SEOAIC_OPTIONS['seoaic_industry']) ? $SEOAIC_OPTIONS['seoaic_industry'] : '';
    }

    public static function getBusinessName()
    {
        global $SEOAIC_OPTIONS;

        return !empty($SEOAIC_OPTIONS['seoaic_business_name']) ? $SEOAIC_OPTIONS['seoaic_business_name'] : get_option('blogname', true);
    }

    public static function getBusinessDescription()
    {
        global $SEOAIC_OPTIONS;

        return !empty($SEOAIC_OPTIONS['seoaic_business_description']) ? $SEOAIC_OPTIONS['seoaic_business_description'] : get_option('blogdescription', true);
    }

    private function setBusinessDescription($value)
    {
        $value = stripslashes(sanitize_textarea_field($value));
        $this->seoaic->set_option('seoaic_business_description', $value);
    }

    public static function getLocation()
    {
        global $SEOAIC_OPTIONS;

        return !empty($SEOAIC_OPTIONS['seoaic_location']) ? $SEOAIC_OPTIONS['seoaic_location'] : 'United States';
    }

    public static function getCompanyWebsite($part = '')
    {
        global $SEOAIC_OPTIONS;

        $companyWebsite = !empty($SEOAIC_OPTIONS['seoaic_company_website']) ? $SEOAIC_OPTIONS['seoaic_company_website'] : get_bloginfo('url');

        if ('host' == $part) {
            return wp_parse_url($companyWebsite)['host'];
        }

        return $companyWebsite;
    }

    public static function getSEOAICPostType()
    {
        global $SEOAIC_OPTIONS;

        return !empty($SEOAIC_OPTIONS['seoaic_post_type']) ? $SEOAIC_OPTIONS['seoaic_post_type'] : 'post';
    }

    public static function getGenerateInternalLinks()
    {
        global $SEOAIC_OPTIONS;

        return !empty($SEOAIC_OPTIONS['seoaic_generate_internal_links']) ? $SEOAIC_OPTIONS['seoaic_generate_internal_links'] : '';
    }

    public function generateDescription()
    {
        $prompt = !empty($_POST['prompt']) ? strip_tags($_POST['prompt']) : '';

        if (empty($prompt)) {
            SEOAICAjaxResponse::error('Empty prompt.')->wpSend();
        }

        $result = $this->seoaic->curl->initWithReturn('/api/ai/company-description', [
            'prompt' => $prompt,
        ], true);

        if (
            !empty($result)
            && 'success' == $result['status']
            && !empty($result['description'])
        ) {
            $description = $result['description'];
        }

        if (!empty($description)) {
            $this->setBusinessDescription($description);
            SEOAICAjaxResponse::success()->addFields([
                'content' => [
                    'description' => $description,
                ],
            ])->wpSend();
        }

        SEOAICAjaxResponse::alert('Could not generate description.')->wpSend();
    }
    public static function getPostsDefaultCategories()
    {
        global $SEOAIC_OPTIONS;

        return !empty($SEOAIC_OPTIONS['seoaic_default_category']) ? $SEOAIC_OPTIONS['seoaic_default_category'] : [];
    }

    public static function getPostsGeneratePromptTemplates()
    {
        global $SEOAIC_OPTIONS;

        return !empty($SEOAIC_OPTIONS['seoaic_posts_mass_generate_prompt_templates']) ? $SEOAIC_OPTIONS['seoaic_posts_mass_generate_prompt_templates'] : [];
    }


    public static function setPostsGeneratePromptTemplates($value)
    {
        self::save('seoaic_posts_mass_generate_prompt_templates', $value);
    }

    private static function save($key, $value)
    {
        global $SEOAIC_OPTIONS;
        $SEOAIC_OPTIONS[$key] = $value;
        update_option('seoaic_options', $SEOAIC_OPTIONS);
    }
}