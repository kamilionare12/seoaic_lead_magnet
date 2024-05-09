<?php

namespace SEOAIC\helpers;

class WPTransients
{
    public static function getCachedValue(string $name)
    {
        return get_transient($name);
    }

    public static function cacheValue(string $name, $value, int $expiration = 0): bool
    {
        return set_transient($name, $value, $expiration);
    }

    public static function deleteCachedValue(string $name): bool {
        return delete_transient($name);
    }
}
