<?php

namespace App\Ajax;
final class Ajax
{
    private $allowed_actions = [
        'seoaic_update_company_credits',
        'seoaic_get_blog_settings',
        'seoaic_posts_mass_generate_check_status',
    ];

    public function __construct()
    {
        add_action('admin_init', [$this, 'checkAjaxPermissions']);
        add_action('wp_enqueue_scripts', [$this, 'seoaicAdminNonceParams']);
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
            'seoaic_remove_keyword' => wp_create_nonce('seoaic_remove_keyword_nonce'),
        ];

        return $nonce_actions;
    }

    public function seoaicAdminNonceParams()
    {
        wp_localize_script( 'seoaic_admin_main_js', 'wp_nonce_params', $this->ajaxNonceActions());
    }

}