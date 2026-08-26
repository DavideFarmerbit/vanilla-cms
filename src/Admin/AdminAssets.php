<?php

namespace VanillaCms\Admin;

final class AdminAssets
{
    private static string $cssUrl = '';
    private static string $jsUrl = '';

    public static function set(string $cssUrl, string $jsUrl): void
    {
        self::$cssUrl = $cssUrl;
        self::$jsUrl = $jsUrl;
    }

    public static function cssUrl(): string
    {
        return self::$cssUrl;
    }

    public static function jsUrl(): string
    {
        return self::$jsUrl;
    }
}
