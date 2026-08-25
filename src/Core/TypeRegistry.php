<?php

namespace VanillaCms\Core;

final class TypeRegistry
{
    private static array $pages = [];
    private static array $templates = [];

    public static function registerPage(string $slug, string $label): void
    {
        self::$pages[$slug] = ['slug' => $slug, 'label' => $label];
    }

    public static function registerTemplate(string $slug, string $label): void
    {
        self::$templates[$slug] = ['slug' => $slug, 'label' => $label];
    }

    public static function pages(): array
    {
        return self::$pages;
    }

    public static function templates(): array
    {
        return self::$templates;
    }

    public static function getPage(string $slug): ?array
    {
        return self::$pages[$slug] ?? null;
    }

    public static function getTemplate(string $slug): ?array
    {
        return self::$templates[$slug] ?? null;
    }
}
