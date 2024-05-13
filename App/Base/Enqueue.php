<?php

namespace App\Base;

use SEOAIC\SEOAIC;

final class Enqueue
{

    public function __construct()
    {
        add_action('wp_head', [self::class, 'custom_script_in_head']);
        add_action('wp_enqueue_scripts', [self::class, 'seoai_admin_to_front_css']);
        add_action('wp_enqueue_scripts', [self::class, 'seoai_admin_to_front_JS']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueueStyles']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueueScripts']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueStyles']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueScripts']);
    }

    public static function custom_script_in_head()
    {
        ?>
        <script type="text/javascript">
            var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
        </script>
        <?php
    }

    public static function enqueueStyles()
    {
        wp_enqueue_style('lead_magnet_main', SEOAIC_LM_URL . '/assets/css/main.min.css', array(), filemtime(SEOAIC_LM_DIR . '/assets/css/main.min.css'));
    }

    public static function enqueueScripts()
    {
        wp_enqueue_script('lead_magnet_main-js', SEOAIC_LM_URL . '/assets/js/main.min.js', array(), filemtime(SEOAIC_LM_DIR . '/assets/js/main.min.js'), true);
        wp_localize_script('lead_magnet_main-js', 'adminPage', array(
            'adminUrl' => SEOAIC::getAdminUrl('admin.php')
        ));
        wp_localize_script('lead_magnet_main-js', 'ajax', array(
            'url' => admin_url('admin-ajax.php')
        ));
    }

    public static function seoai_admin_to_front_css()
    {
        wp_enqueue_style('seoai_main', SEOAIC_URL . '/assets/css/main.min.css', array(), filemtime(SEOAIC_DIR . '/assets/css/main.min.css'));
        wp_enqueue_style('seoai_admin_main', SEOAIC_URL . '/assets/css/seoaic-admin.css', array(), filemtime(SEOAIC_DIR . '/assets/css/seoaic-admin.css'));
    }

    public static function seoai_admin_to_front_JS()
    {
        wp_enqueue_script( 'seoaic_admin_main_js', SEOAIC_URL . '/assets/js/main.min.js', array(), filemtime(SEOAIC_DIR . '/assets/js/main.min.js'), true );
        wp_localize_script('seoaic_admin_main_js', 'ajax', array(
            'url' => admin_url('admin-ajax.php')
        ));
    }
}
