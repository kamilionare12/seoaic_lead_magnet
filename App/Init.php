<?php
/**
 * Init plugin classes
 */

namespace App;
final class init {
    private static function get_services(): array {
        return array(
            Base\Enqueue::class,
            Base\Menu::class
        );
    }

    public static function register_services() {
        foreach ( self::get_services() as $class ) {
            new $class;
        }
    }
}
