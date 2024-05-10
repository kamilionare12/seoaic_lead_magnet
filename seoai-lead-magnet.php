<?php

/**
 * Plugin Name: WP SEO AI - Lead Magnet Addon
 * Plugin URI:
 * Description: SEOAI client Lead Magnet Addon Plugin.
 * Author: SEO AI OY
 * Version: 0.01
 * Requires at least: 5.5
 * Requires PHP: 7.2.7
 * Author URI:
 */

if ( ! class_exists('\SEOAIC\SEOAIC') ) {
    return;
}

define('TM_TEXTDOMAIN', 'seoaic');

define( 'SEOAIC_LM_FILE', __FILE__ );
define( 'SEOAIC_LM_DIR', plugin_dir_path( __FILE__ ) );
define( 'SEOAIC_LM_URL', plugin_dir_url( __FILE__ ) );
define( 'SEOAIC_LM_LOG', wp_upload_dir()['basedir'] . '/seoaic-lm/logs/' );

if (!defined( 'SEOAIC_LM_BACK_URL' )) {
    define( 'SEOAIC_LM_BACK_URL', '{SEOAIC_LM_BACK_URL}');
}

require( SEOAIC_LM_DIR . 'plugin-update-checker-5.3/plugin-update-checker.php' );
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$MyUpdateChecker = PucFactory::buildUpdateChecker(
    'https://updates.wpseoai.com/?action=get_metadata&slug={SEOAIC_PLUGIN_ENV_SLUG}',
    SEOAIC_LM_FILE,
    '{SEOAIC_PLUGIN_ENV_SLUG}'
);

// Require once the Composer Autoload
if (file_exists(SEOAIC_LM_DIR . "vendor/autoload.php" )) {
    require_once SEOAIC_LM_DIR . "vendor/autoload.php";
}

if (class_exists('App\Init')) {
    App\Init::register_services();
}


//
//
//if ( ! file_exists( SEOAIC_LM_DIR . "vendor/autoload.php" ) ) {
//    return;
//}
//
//require_once 'vendor/autoload.php';

//require_once( SEOAIC_LM_DIR . 'App/functions.php' );
//$SEOAIC = new \SEOAIC\SEOAIC();
