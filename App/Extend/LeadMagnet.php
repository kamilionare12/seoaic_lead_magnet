<?php

namespace App\Extend;

class LeadMagnet extends \SEOAIC\SEOAIC
{
    public function __construct()
    {
        parent::__construct();
        if ( ! is_admin() ) {
            add_action('wp_footer', [$this, 'popups'], 10);
        }
    }

    public function popups()
    {
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
        include_once(SEOAIC_DIR . 'inc/view/popups/ranking-modal.php');
        include_once(SEOAIC_DIR . 'inc/view/popups/performance-modal.php');
    }
}