<?php

/**
 * Functions
 */


add_action('admin_init', 'seoaic_LM_settings_init');
add_action('admin_menu', 'seoaic_LM_options_page');

/**
 * Add settings
 */
function seoaic_LM_settings_init()
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
function seoaic_LM_options_page()
{

    $capability = 'seoaic_edit_plugin';

    add_menu_page(
        'Connection',
        'Lead Magnet II',
        $capability,
        'seoaic-lm',
        'seoaic_LM_options_html'
    );

    add_submenu_page('seoaic-lm', 'Dashboard', 'Dashboard', $capability, 'seoaic-lm', NULL);
    add_submenu_page('seoaic-lm', 'Users', 'Users', $capability, 'seoaic-lm-users', 'seoaic_LM_options_html');
    add_submenu_page('seoaic-lm', 'Settings', 'Settings', $capability, 'seoaic-lm-settings', 'seoaic_LM_options_html');

}

/**
 * Render seoai options page html
 */
function seoaic_LM_options_html()
{

    $page_name = explode('-', $_GET['page']);
    $page_name = end($page_name);

    if ($page_name !== 'lm') {
        include(SEOAIC_LM_DIR . 'App/templates/' . $page_name . '.php');
    } else {
        include(SEOAIC_LM_DIR . 'App/templates/home.php');
    }
}
