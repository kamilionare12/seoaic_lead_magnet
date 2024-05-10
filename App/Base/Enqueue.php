<?php

namespace App\Base;

final class Enqueue {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ self::class, 'enqueueStyles' ] );
        add_action( 'wp_enqueue_scripts', [ self::class, 'enqueueScripts' ] );
        add_action( 'admin_enqueue_scripts', [ self::class, 'enqueueStyles' ] );
        add_action( 'admin_enqueue_scripts', [ self::class, 'enqueueScripts' ] );
    }

    public static function enqueueStyles() {
        wp_enqueue_style( 'lead_magnet_main', SEOAIC_LM_URL . '/assets/css/main.min.css', array(), filemtime(SEOAIC_LM_DIR . '/assets/css/main.min.css'));
    }

    public static function enqueueScripts() {
        wp_enqueue_script( 'lead_magnet_main-js', SEOAIC_LM_URL . '/assets/js/main.min.js', array(), filemtime(SEOAIC_LM_DIR . '/assets/js/main.min.js'), true );
    }
}
