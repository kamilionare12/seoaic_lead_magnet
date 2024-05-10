<?php

namespace App\Base;

final class Menu
{

    public function __construct()
    {
        add_action('admin_init', [$this, 'init_menu']);
        add_action('admin_menu', [$this, 'menu_pages']);
    }

    public function init_menu()
    {

        register_setting('seoaic_lm', 'seoaic_lm_options');

        add_settings_section(
            'seoaic_lm_platform_api',
            __('SEO AI Lead Magnet settings', 'seoaic'),
            '',
            'seoaic-lm'
        );
    }

    /**
     * Add menu and submenu in admin panel menu
     */
    public function menu_pages()
    {

        $capability = 'seoaic_edit_plugin';

        add_menu_page(
            'Connection',
            'Lead Magnet II',
            $capability,
            'seoaic-lm',
            [$this, 'template_html']
        );

        add_submenu_page('seoaic-lm', 'Dashboard', 'Dashboard', $capability, 'seoaic-lm', NULL);
        add_submenu_page('seoaic-lm', 'Users', 'Users', $capability, 'seoaic-lm-users', [$this, 'template_html']);
        add_submenu_page('seoaic-lm', 'Settings', 'Settings', $capability, 'seoaic-lm-settings', [$this, 'template_html']);

    }

    /**
     * Render seoai options page html
     */
    public function template_html()
    {

        $page_name = explode('-', $_GET['page']);
        $page_name = end($page_name);

        if ($page_name !== 'lm') {
            include(SEOAIC_LM_DIR . 'App/templates/' . $page_name . '.php');
        } else {
            include(SEOAIC_LM_DIR . 'App/templates/home.php');
        }
    }
}
