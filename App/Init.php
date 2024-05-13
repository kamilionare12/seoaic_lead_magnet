<?php
/**
 * Init plugin classes
 */

namespace App;
final class init {
    private static function get_services(): array {
        return array(
            Base\Enqueue::class,
            Base\Menu::class,
            Extend\Keywords::class,
            Extend\LeadMagnet::class,
            Ajax\Ajax::class,
        );
    }

    public static function register_classes() {
        foreach ( self::get_services() as $class ) {
            new $class;
        }
    }
}
