<?php

/**
* Plugin Name: WP SEO AI
* Plugin URI:
* Description: SEOAI client Wordpress Plugin, generate post, image via openai. Node.js.
* Author: SEO AI OY
* Version: 2.10.7
* Requires at least: 5.5
* Requires PHP: 7.2.7
* Author URI:
* Text Domain: seoaic
*/

define( 'SEOAIC_FILE', __FILE__ );
define( 'SEOAIC_DIR', plugin_dir_path( __FILE__ ) );
define( 'SEOAIC_URL', plugin_dir_url( __FILE__ ) );
define( 'SEOAIC_LOG', wp_upload_dir()['basedir'] . '/seoaic/logs/' );

if (!defined( 'SEOAIC_BACK_URL' )) {
    define( 'SEOAIC_BACK_URL', '{SEOAIC_BACK_URL}');
}

require( SEOAIC_DIR . 'plugin-update-checker-5.3/plugin-update-checker.php' );
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$MyUpdateChecker = PucFactory::buildUpdateChecker(
    'https://updates.wpseoai.com/?action=get_metadata&slug={SEOAIC_PLUGIN_ENV_SLUG}',
    SEOAIC_FILE,
    '{SEOAIC_PLUGIN_ENV_SLUG}'
);

if ( ! file_exists( SEOAIC_DIR . "vendor/autoload.php" ) ) {
    return;
}

require_once 'vendor/autoload.php';

if ( ! class_exists('\SEOAIC\SEOAIC') ) {
    return;
}

require_once( SEOAIC_DIR . 'inc/functions.php' );
require_once( SEOAIC_DIR . 'inc/settings.php' );
$SEOAIC = new \SEOAIC\SEOAIC();
